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

class Mode(Enum):
	unkown = -1
	on = 1
	off = 2
	program = 3
	disabled = 4

class ScheduleError(Exception):
		pass

class Schedule(object):

	def __init__(self, db_id, cursor, db_lock):
		self.db_lock = db_lock
		self.db_id = db_id
		self.cursor = cursor
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
		self.db_lock.acquire()
		self.cursor.execute("SELECT active_time, delay_time FROM schedule WHERE actuator_id = %s", [self.db_id])
		if self.cursor.rowcount == 336:
			count = 0;
			for row in self.cursor:
				#if self.days[count//48][count%48] != row[0]:
				#	print("Updating {0} {1} : {2}".format(count//48,count%48,row[0]))
				self.days[count//48][count%48][0] = row[0]
				self.days[count//48][count%48][1] = row[1]
				count += 1
				
		else:
			raise ScheduleError("Invalid number of schedule slots returned: {0}".format(self.cursor.rowcount))
		self.db_lock.release()

	def save_schedule(self):
		pass

class Rule(object):
	pass

class Actuator(object):

	def __init__(self, db_id, cursor, coms, pin, db_lock):
		self.db_id = db_id
		self.cursor = cursor
		self.coms = coms
		self.pin = pin
		self.db_lock = db_lock
		self.update_interval = 5
		self.next_update = 0
		self.schedule = Schedule(db_id, cursor, db_lock)
		self.rules = []
		self.mode = Mode(-1)
		self.state = -1 # -1 for unkown state
		self.name = ""
		self.internal_update_settings()

	def update_settings(self):
		if self.next_update < time.time():
			threading.Thread(target=self.internal_update_settings).start()
		
	def internal_update_settings(self):
		self.db_lock.acquire()
		self.cursor.execute("SELECT name, mode_id FROM actuators WHERE id = %s", [self.db_id])
		if self.cursor.rowcount == 1:
			self.name, mode = self.cursor.fetchone()
			new_mode = Mode(mode)
			if new_mode != self.mode:
				self.mode = new_mode
				print("{0} mode set to {1}".format(self.name, self.mode.name))	
		else:
			raise BaseException("No Actuator in database: {0}".format(self.db_id))
		self.db_lock.release()
		
		if self.mode == Mode.program:
			self.schedule.load_schedule();
		
		self.set_status()
		self.next_update = time.time() + self.update_interval

	def update(self):
		self.update_relay()

	def update_relay(self):
		new_state = self.compute_state()
		if new_state != self.state:
			print("{0} => {1}".format(self.name, "Active" if new_state == 1 else "Off"))
			self.state = new_state
			print("Pin {0}, State {1}".format(self.pin, self.state))
			print("{0:b}".format((self.state^1) << 4 | self.pin), (self.state^1) << 4 | self.pin)
			self.coms.write(bytes([(self.state^1) << 4 | self.pin]))
			self.set_status()


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
		self.db_lock.acquire()
		self.cursor.execute("UPDATE actuators SET status=%s WHERE id=%s", [status, self.db_id])
		self.db_lock.release()
			

class Sensor(object):

	def __init__(self, db_id, cursor, db_lock):
		self.db_id = db_id
		self.next_log = 0
		self.log_interval = 1200
		self.log_enabled = False
		self.pin = 0
		self.db_lock = db_lock
		self.cursor = cursor
		self.is_valid = True
		print(self.db_id)
		self.db_lock.acquire()
		self.cursor.execute("SELECT pin, log FROM sensors WHERE id = %s", [self.db_id])
		if self.cursor.rowcount == 1:
			row = self.cursor.fetchone()
			self.pin = int(row[0])
			self.log_enabled = row[1] == 1
		else:
			raise BaseException("No Sensor in database")
		self.db_lock.release()

		self.test_value = 0
		
	def arduino_msg(self, message):
		pass
		

	def log(self):
		if self.log_enabled:
			if self.next_log < time.time() and self.is_valid:
				threading.Thread(target=self.internal_log).start()
				
				
	def internal_log(self):
		self.db_lock.acquire()
		print("Sensor {0} = {1}".format(self.db_id, self.read()))
		self.cursor.execute("INSERT INTO sensor_data (sensor_id, value, time) VALUE (%s, %s, %s)", [self.db_id, self.read(), time.strftime('%Y-%m-%d %H:%M:%S', time.gmtime())])
		self.next_log = time.time() + self.log_interval
		self.db_lock.release()
				
	def read(self):
		self.test_value += 0.1
		return 18 + math.sin(time.time()/86400.0*2*math.pi+self.db_id)*10 + random.uniform(-1.5, 1.5)

class Clock(Sensor):

	def read(self):
		return time.time()
		
class ArduinoSensor(Sensor):
	
	def __init__(self, db_id, cursor, db_lock):
		Sensor.__init__(self, db_id, cursor, db_lock)
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
				print("Sensor {0}: {1}".format(self.db_id, message[1]))

	
if __name__ == "__main__":
	parser = OptionParser()
	parser.add_option("-c", "--config", dest="config",
		help="configuration file",
		default="greenhouse.ini")
	options, args = parser.parse_args()

	config = configparser.ConfigParser()
	config.read(options.config)
	
	db_lock = threading.Lock()
	connection = pymysql.connect(host=config['SQL CREDS']['host'], 
		port=int(config['SQL CREDS']['port']), 
		user=config['SQL CREDS']['user'], 
		password=config['SQL CREDS']['password'], 
		db=config['SQL CREDS']['db'])
	cursor = connection.cursor()
	
	#coms = io.BytesIO(); 
	coms = serial.Serial("/dev/ttyUSB0")
	
	print("Fetching sensor information... ",end="")
	sensors = []
	sensors.append(Clock(1, cursor, db_lock))
	sensors.append(DHT_Temp(3, cursor, db_lock))
	sensors.append(DHT_Humid(4, cursor, db_lock))
	sensors.append(DHT_Temp(5, cursor, db_lock))
	sensors.append(DHT_Humid(6, cursor, db_lock))
	sensors.append(DHT_Temp(7, cursor, db_lock))
	sensors.append(DHT_Humid(8, cursor, db_lock))
	sensors.append(DHT_Temp(9, cursor, db_lock))
	sensors.append(DHT_Humid(10, cursor, db_lock))
	sensors.append(Sensor(11, cursor, db_lock))
	sensors.append(Moisture_Probe(12, cursor, db_lock))
	connection.commit()
	print("Done!\n")
	
	print("Fetching actuator information... ")
	actuators = []
	actuators.append(Actuator(1, cursor, coms, 13, db_lock))
	actuators.append(Actuator(2, cursor, coms, 12, db_lock))
	actuators.append(Actuator(3, cursor, coms, 11, db_lock))
	actuators.append(Actuator(4, cursor, coms, 10, db_lock))
	actuators.append(Actuator(5, cursor, coms, 9,  db_lock))
	actuators.append(Actuator(6, cursor, coms, 8,  db_lock))
	actuators.append(Actuator(7, cursor, coms, 7,  db_lock))
	actuators.append(Actuator(8, cursor, coms, 6,  db_lock))
	
	help(cursor)
	print("Actuators configured!\n")

	print("Taking control of the greenhouse now!")
	try:
		count = 0
		while True:
			
			while (coms.inWaiting() > 0):
				sensor_data = coms.readline().decode('utf-8')
				for sensor in sensors:
					sensor.arduino_msg(sensor_data)
				
			for sensor in sensors:
				sensor.log()
					
			for actuator in actuators:
				actuator.update_settings()
			
			for actuator in actuators:
				actuator.update()
			
			db_lock.acquire()
			connection.commit()
			db_lock.release()
			
			time.sleep(0.5)
			count += 1
	except KeyboardInterrupt:
		coms.close()

	
	
