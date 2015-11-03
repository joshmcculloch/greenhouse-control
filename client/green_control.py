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

	def __init__(self, db_id, database):
		self.db_id = db_id
		self.database = database
		self.days = []
		for day in range(7):
			self.days.append([])
			for half_hour in range(48):
				self.days[day].append([0,0])
	
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
		rows = self.database.execute("SELECT active_time, delay_time FROM schedule WHERE actuator_id = %s", [self.db_id], False)
		if len(rows) == 336:
			count = 0;
			for row in rows:
				self.days[count//48][count%48][0] = row["active_time"]
				self.days[count//48][count%48][1] = row["delay_time"]
				count += 1
				
		else:
			raise ScheduleError("Invalid number of schedule slots returned: {0}".format(self.cursor.rowcount))

	def save_schedule(self):
		pass

class Rule(object):
	pass

class Actuator(object):

	def __init__(self, db_id, database, coms, pin):
		self.db_id = db_id
		self.database = database
		self.coms = coms
		self.pin = pin
		self.update_interval = 5
		self.next_update = 0
		self.schedule = Schedule(db_id, database)
		self.rules = []
		self.mode = Mode(-1)
		self.state = -1 # -1 for unkown state
		self.name = ""
		self.update_settings()
		
	'''
	Gets the current actuator configuration from the server.
	'''
	def update_settings(self):
		if self.next_update < time.time():
			rows = self.database.execute("SELECT name, mode_id FROM actuators WHERE id = %s", [self.db_id], False)
			if len(rows) == 1:
				self.name, mode = rows[0]["name"], rows[0]["mode_id"]
				new_mode = Mode(mode)
				if new_mode != self.mode:
					self.mode = new_mode
					print("{0} mode set to {1}".format(self.name, self.mode.name))	
			else:
				raise BaseException("No Actuator in database: {0}".format(self.db_id))
			
			if self.mode == Mode.program:
				self.schedule.load_schedule();
			
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
			# Follow schedule
			return self.schedule.get_state_now()
			# Follow rules
			return 0
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
		result = self.database.execute("UPDATE actuators SET status=%s WHERE id=%s", [status, self.db_id], allow_fail=True)
		if result == False:
			print("unable to date actuator state on website")
			

class Sensor(object):

	def __init__(self, db_id, database):
		self.db_id = db_id
		self.next_log = 0
		self.log_interval = 300
		self.log_enabled = False
		self.pin = 0
		self.database = database
		self.is_valid = True
		rows = self.database.execute("SELECT pin, log FROM sensors WHERE id = %s", [self.db_id], False)
		if len(rows) == 1:
			self.pin = int(rows[0]['pin'])
			self.log_enabled = rows[0]['log'] == 1
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
	
	def __init__(self, db_id, database):
		Sensor.__init__(self, db_id, database)
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
	
	print("Fetching sensor information... ",)
	sensors = []
	sensors.append(Clock(1, database))
	sensors.append(DHT_Temp(3, database))
	sensors.append(DHT_Humid(4, database))
	sensors.append(DHT_Temp(5, database))
	sensors.append(DHT_Humid(6, database))
	sensors.append(DHT_Temp(7, database))
	sensors.append(DHT_Humid(8, database))
	sensors.append(DHT_Temp(9, database))
	sensors.append(DHT_Humid(10, database))
	sensors.append(Sensor(11, database))
	sensors.append(Moisture_Probe(12, database))
	print("Done!\n")
	
	
	print("Fetching actuator information... ")
	actuators = []
	actuators.append(Actuator(1, database, coms, 13))
	actuators.append(Actuator(2, database, coms, 12))
	actuators.append(Actuator(3, database, coms, 11))
	actuators.append(Actuator(4, database, coms, 10))
	actuators.append(Actuator(5, database, coms, 9))
	actuators.append(Actuator(6, database, coms, 8))
	actuators.append(Actuator(7, database, coms, 7))
	actuators.append(Actuator(8, database, coms, 6))
	
	print("Actuators configured!\n")
	
	running = True
	
	def actuator_monitor():
		while running:
			for actuator in actuators:
				actuator.update_relay()
	
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
						
	except KeyboardInterrupt:
		running = False
		coms.close()

	
	
