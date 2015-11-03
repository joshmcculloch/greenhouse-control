import threading
import pymysql
import pymysql.cursors
import configparser
from time import sleep

class Database(object):
	
	def __init__(self, config_file):
		self.connected = False
		self.connecting = False
		self.db_lock = threading.Lock()
		self.connection = None
		self.config = None
		self.load_config(config_file)
		self.open_connection()
		
		
	def load_config(self, config_file):
		self.config = configparser.ConfigParser()
		self.config.read(config_file)
		
	def open_connection(self):
		print("Connecting to",self.config['SQL CREDS']['host'])
		while not self.connected:
			try:
				self.connection = pymysql.connect(
					host		= self.config['SQL CREDS']['host'], 
					port		= int(self.config['SQL CREDS']['port']), 
					user		= self.config['SQL CREDS']['user'], 
					password	= self.config['SQL CREDS']['password'], 
					db			= self.config['SQL CREDS']['db'],
					cursorclass=pymysql.cursors.DictCursor)
				self.connected = True
				self.connection.socket.settimeout(10)
				print("Connected to",self.config['SQL CREDS']['host'])
			except pymysql.err.OperationalError:
				print("Unable to connect to database... retrying in 5sec")
				sleep(5)
		
		
	def execute(self, query, params, require_commit=True, allow_fail=False, cache_on_fail=False):
		#Keep retrying until we can return a value which meets the requirements
		while True:
			self.db_lock.acquire();
			if not self.connected:
				self.db_lock.release()
				#if we are allowed to fail we will return false otherwise wait 1 sec and try again
				if allow_fail:
					return False
				else:
					sleep(1)
			else:
				try:
					with self.connection.cursor() as cursor:
						cursor.execute(query, params);
						result = cursor.fetchall()
						if require_commit:
							self.connection.commit()
						self.db_lock.release()
						return result
						
				except pymysql.err.OperationalError:
					print("Operational Error");
					print("Starting the reconnection procedure")
					self.connected = False
					threading.Thread(target=self.open_connection).start()
					result = False
					self.db_lock.release()
					#if we are allowed to fail we will return false otherwise wait 1 sec and try again
					if allow_fail:
						return False
					else:
						sleep(1)
				
				except pymysql.err.InternalError:
					print("Internal Error");
					print("Not sure what caused this, will handled this as a failed query")
					result = False
					self.db_lock.release()
					#if we are allowed to fail we will return false otherwise wait 1 sec and try again
					if allow_fail:
						return False
					else:
						sleep(1)
	
			
		
if __name__ == "__main__":
	test_server = Database("greenhouse.ini")
	import time
	
	def work(_id):
		for i in range(1000):
			print(_id,":",i)
			row = False
			while row == False:
				
				row = test_server.execute("SELECT pin, log FROM sensors WHERE id = %s",[1], require_commit=False, allow_fail=True, cache_on_fail=False)
				if row == False:
					print(_id,"retrying")
				time.sleep(1)

	for i in range(10):
		threading.Thread(target=work, args=(i,)).start()

	while threading.active_count() > 1:
		time.sleep(1)
	
	
