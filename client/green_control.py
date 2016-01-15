#!/usr/bin/python3
import random
import math
import threading
import pymysql
import time
from enum import Enum
import serial
import io
import configparser
from optparse import OptionParser
from server import Database
import sys
from logger import Logger
import rule_system

VERBOSE_LEVEL = 1


class Mode(Enum):
	unkown = -1
	on = 1
	off = 2
	program = 3
	disabled = 4

class ScheduleError(Exception):
		pass

class Schedule(object):

	def __init__(self, db_id, database, nodes):
		self.db_id = db_id
		self.database = database
		self.days = []
		self.update_interval = 5
		self.next_update = 0
		for day in range(7):
			self.days.append([])
			for half_hour in range(48):
				self.days[day].append([0,0])
		
		rows = self.database.execute("SELECT id, node_id FROM schedule WHERE id = %s", [self.db_id], False)	
		nodes[rows[0]["node_id"]].schedule = self
	
	def get_schedule(self,day, hour, minute):
		half_hour = hour*2 + minute//30
		return self.days[day][half_hour]
		
	def get_state_now(self):
		day = time.localtime().tm_wday
		hour = time.localtime().tm_hour
		minute = time.localtime().tm_min
		active_time, delay_time = self.get_schedule(day, hour, minute)
		if active_time == 0:
			return 0
		elif delay_time == 0:
			return 1;
		else:
			return time.time()%(active_time + delay_time) < active_time

	def load_schedule(self):
		rows = self.database.execute("SELECT active_time, delay_time FROM schedule_times WHERE schedule_id=%s ORDER BY day, half_hour", [self.db_id], False)
		if len(rows) == 336:
			count = 0;
			for row in rows:
				self.days[count//48][count%48][0] = row["active_time"]
				self.days[count//48][count%48][1] = row["delay_time"]
				count += 1
				
		else:
			raise ScheduleError("Invalid number of schedule slots returned: {0}".format(self.cursor.rowcount))
		self.next_update = time.time() + self.update_interval
		
	def update_schedule(self):
		if time.time() > self.next_update:
			self.load_schedule()

	def save_schedule(self):
		pass

class Rule(object):
	pass

class Actuator(object):

	def __init__(self, db_id, database, coms, nodes):
		self.db_id = db_id
		self.database = database
		self.coms = coms
		self.pin = None
		self.revision = 0
		self.update_interval = 5
		self.next_update = 0
		self.rules = []
		self.mode = Mode(-1)
		self.state = -1 # -1 for unkown state
		self.name = ""
		rows = self.database.execute("SELECT id, node_id FROM actuators WHERE id = %s", [self.db_id], False)		
		self.node = nodes[rows[0]["node_id"]]
		self.update_settings()
		
	'''
	Gets the current actuator configuration from the server.
	'''
	def update_settings(self):
		if self.next_update < time.time():
			rows = self.database.execute("""
			SELECT 
				actuators.name, 
				actuators.mode_id, 
				actuators.revision,
				actuators.pin 
			FROM actuators 
			WHERE actuators.id = %s""", [self.db_id], False)
			if len(rows) == 1:
				row = rows[0]
				self.name, mode, current_revision, self.pin = row["name"], row["mode_id"], row["revision"], row["pin"]
				new_mode = Mode(mode)
				if new_mode != self.mode:
					self.mode = new_mode
					print("{0} mode set to {1}".format(self.name, self.mode.name))	
			else:
				raise BaseException("No Actuator in database: {0}".format(self.db_id))
			
			'''
			if self.mode == Mode.program and self.revision < current_revision:
				if VERBOSE_LEVEL > 0:
					print("Fetching new schedule")
				self.revision = current_revision
				self.schedule.load_schedule();'''
			
			self.update_relay()
			self.set_status()
			self.next_update = time.time() + self.update_interval

	'''
	Updates the check the relay is in the correct state and toggles if 
	required. Uploads the new state to the server.
	'''
	def update_relay(self):
		new_state = self.compute_state()
		if new_state != self.state:
			print("{0} => {1}".format(self.name, "Active" if new_state == 1 else "Off"))
			self.state = new_state
			if VERBOSE_LEVEL > 1:
				print("Pin {0}, State {1}".format(self.pin, self.state))
				print("{0:b}".format((self.state^1) << 4 | self.pin), (self.state^1) << 4 | self.pin)
			self.coms.write(bytes([(self.state^1) << 4 | self.pin]))
	'''
	Computes the state for the relay based on the current mode, schedule,
	and rules.
	'''
	def compute_state(self):
		if self.mode == Mode.on:
			return 1
		elif self.mode == Mode.off:
			return 0
		elif self.mode == Mode.program:
			return self.node.getValue()
		elif self.mode == Mode.disabled:
			return 0
		else:
			raise BaseException("Invalid Actuator Mode!")

	'''
	Send the current state of the actuator to the server.
	'''
	def set_status(self):
		status = ""
		if self.mode == Mode.on:
			status = "Manual On"
		elif self.mode == Mode.off:
			status = "Manual Off"
		elif self.mode == Mode.program:
			status = "Following Program"
			if self.state:
				status += ": On"
			else:
				status += ": Off"
		elif self.mode == Mode.disabled:
			status = "Relay Not Configured"
		else:
			status = "Error setting relay mode!"
		self.database.execute("UPDATE actuators SET status=%s WHERE id=%s", [status, self.db_id], allow_fail=True)
			

class Sensor(object):

	def __init__(self, db_id, database, nodes):
		self.db_id = db_id
		self.next_log = 0
		self.log_interval = 300
		self.log_enabled = False
		self.pin = 0
		self.database = database
		self.is_valid = True
		rows = self.database.execute("SELECT pin, node_id, log FROM sensors WHERE id = %s", [self.db_id], False)
		if len(rows) == 1:
			self.pin = int(rows[0]['pin'])
			self.log_enabled = rows[0]['log'] == 1
			nodes[rows[0]["node_id"]].sensor = self
		else:
			raise BaseException("No Sensor in database")
		print("Sensor",self.db_id,"logging =","enabled" if self.log_enabled else "disabled")

		self.test_value = 0
		
	def arduino_msg(self, message):
		pass
		

	def log(self):
		if self.log_enabled:
			if self.next_log < time.time() and self.is_valid:
				print("Sensor {0} = {1}".format(self.db_id, self.read()))
				self.database.execute("INSERT INTO sensor_data (sensor_id, value, time) VALUE (%s, %s, %s)", 
					[self.db_id, self.read(), time.strftime('%Y-%m-%d %H:%M:%S', time.gmtime())],
					require_commit=True, 
					allow_fail=True, 
					cache_on_fail=True)
				self.next_log = time.time() + self.log_interval

	def read(self):
		self.test_value += 0.1
		return 18 + math.sin(time.time()/86400.0*2*math.pi+self.db_id)*10 + random.uniform(-1.5, 1.5)

class Clock(Sensor):

	def read(self):
		return time.time()
		
class ArduinoSensor(Sensor):
	
	def __init__(self, db_id, database, nodes):
		Sensor.__init__(self, db_id, database, nodes)
		self.is_valid = False
		self.value = 0
		
	def read(self):
		return self.value
		
class DHT_Humid(ArduinoSensor):
	
	def arduino_msg(self, message):
		message = message.strip().split(',')
		if int(message[0]) == self.pin:
			if message[1] == "OK":
				self.is_valid = True
				self.value = float(message[2])
			else:
				self.is_valid = False
				if VERBOSE_LEVEL > 1:
					print("Sensor {0}: {1}".format(self.db_id, message[1]))
	
class DHT_Temp(ArduinoSensor):
	
	def arduino_msg(self, message):
		message = message.strip().split(',')
		if int(message[0]) == self.pin:
			if message[1] == "OK":
				self.is_valid = True
				self.value = float(message[3])
			else:
				self.is_valid = False
				if VERBOSE_LEVEL > 1:
					print("Sensor {0}: {1}".format(self.db_id, message[1]))

class Moisture_Probe(ArduinoSensor):

	def arduino_msg(self, message):
		message = message.strip().split(',')
		if int(message[0]) == self.pin:
			if message[1] == "OK":
				self.is_valid = True
				self.value = float(message[2])
			else:
				self.is_valid = False
				if VERBOSE_LEVEL > 1:
					print("Sensor {0}: {1}".format(self.db_id, message[1]))


def load_sensors(database, sensors, greenhouse_id, nodes):
	print("loading sensors from database...");
	for row in database.execute("SELECT id, sensor_type, name FROM sensors WHERE greenhouse_id=%s", [greenhouse_id], require_commit=False):
		if row["sensor_type"] == 1:
			print("Creating DHT Temp:", row["id"])
			sensors.append(DHT_Temp(row["id"], database, nodes))
		elif row["sensor_type"] == 2:
			print("Creating DHT Humidity:", row["id"])
			sensors.append(DHT_Humid(row["id"], database, nodes))
			
def load_schedules(database, schedules, greenhouse_id, nodes):
	print("loading schedules from database...");
	for row in database.execute("SELECT id, name FROM schedule WHERE greenhouse_id=%s", [greenhouse_id], require_commit=False):
		print("Creating schedule:", row["name"])
		schedules.append(Schedule(row["id"], database, nodes))

def load_actuators(database, actuators, greenhouse_id, coms, nodes):
	print("loading actuators from database...");
	for row in database.execute("SELECT id, pin, name FROM actuators WHERE greenhouse_id=%s", [greenhouse_id], require_commit=False):
		print("Creating actuator:", row["name"])
		actuators.append(Actuator(row["id"], database, coms, nodes))
	

	
if __name__ == "__main__":
	sys.stdout = Logger("greenhouse.log")
	sys.stderr = Logger("greenhouse.log")
	
	parser = OptionParser()
	parser.add_option("-c", "--config", dest="config",
		help="configuration file",
		default="greenhouse.ini")
	options, args = parser.parse_args()

	config = configparser.ConfigParser()
	config.read(options.config)
	
	VERBOSE_LEVEL = int(config['Debug']['verbosity'])
	
	if int(config['Debug']['simulate_serial']) == 1:
		print("Using simulated serial port")
		coms = io.BytesIO()
	else:
		coms = serial.Serial(config['Arduino']['serial'])
	
	database = Database(options.config);
	
	print("Loading Rule Systems...")
	ruleManager = rule_system.RuleManager(database, 1)
	
	print("Fetching sensor information... ",)
	sensors = []
	load_sensors(database, sensors, 1, ruleManager.nodes) 

	print("Setting up schedules... ")
	schedules = []
	load_schedules(database, schedules, 1, ruleManager.nodes) 
	
	print("Fetching actuator information... ")
	actuators = []
	load_actuators(database, actuators, 1, coms, ruleManager.nodes)

	running = True
	loop_time = 1.0
	
	def actuator_monitor():
		while running:
			start = time.time()
			for actuator in actuators:
				actuator.update_relay()
			time.sleep(loop_time - (time.time()-start))
	
	print("Taking control of the greenhouse now!")
	threading.Thread(target=actuator_monitor).start()
	
	try:
		while True:
			
			while (coms.inWaiting() > 0):
				try:
					sensor_data = coms.readline().decode('utf-8')
					for sensor in sensors:
						sensor.arduino_msg(sensor_data)
				except UnicodeDecodeError:
					#Throw away the line. The buffer was full and the message is garbage.
					continue
				
			for sensor in sensors:
				sensor.log()
					
			for actuator in actuators:
				actuator.update_settings()
				
			for schedule in schedules:
				schedule.update_schedule()
				
			time.sleep(1)
						
	except KeyboardInterrupt:
		running = False
		coms.close()

	
	
