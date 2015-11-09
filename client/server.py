import threading
import pymysql
import pymysql.cursors
import configparser
import sqlite3
import pickle
from time import sleep

class Database(object):
	
	def __init__(self, config_file):
		self.connected = False
		self.connecting = False
		self.db_lock = threading.Lock()
		self.connection = None
		self.config = None
		self.local_db = None
		self.load_config(config_file)
		self.open_cache_db()
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
				self.retry_cache()
			except pymysql.err.OperationalError:
				print("Unable to connect to database... retrying in 5sec")
				sleep(5)
				
	def open_cache_db(self):
		self.local_db = sqlite3.connect(self.config['Data Cache']['filename'], check_same_thread=False)
		cursor = self.local_db.cursor() 
		cursor.execute("create table if not exists query_cache (id INTEGER PRIMARY  KEY, query BLOB)")
		self.local_db.commit()	
		
	def cache_query(self, query, params):
		print("caching", query)
		cursor = self.local_db.cursor() 
		cursor.execute("insert into query_cache (query) VALUES (?)", [pickle.dumps((query, params))])
		self.local_db.commit()
			
	def retry_cache(self):
		cursor = self.local_db.cursor()
		successful_ids = []
		row = cursor.execute("select count(*) from query_cache").fetchone()
		if row[0] > 0:
			print(row[0],"Queries cached... retrying now.")
		else:
			print("No quieres to be retried")
			return
		for row in cursor.execute("select id, query from query_cache order by id asc"):
			query, params = pickle.loads(row[1])
			if False == self.execute(query, params, require_commit=True, allow_fail=False, cache_on_fail=False):
				print("query failed")
				break
			else:
				successful_ids.append((row[0],))
				print("Query retry successfull")
		
		row = cursor.execute("select count(*) from query_cache").fetchone()
		if row[0] > 0:
			print("Strange! There are still quries to be retried")
		else:
			print("All quieres successfully retried")
		cursor.executemany("delete from query_cache where id=?",successful_ids)
		self.local_db.commit()
		
		
		
	def execute(self, query, params=[], require_commit=True, allow_fail=False, cache_on_fail=False):
		#Keep retrying until we can return a value which meets the requirements
		while True:
			self.db_lock.acquire();
			if not self.connected:
				#if we are allowed to fail we will return false otherwise wait 1 sec and try again
				if allow_fail:
					if cache_on_fail:
						self.cache_query(query,params)
					self.db_lock.release()
					return False
				else:
					self.db_lock.release()
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
					#if we are allowed to fail we will return false otherwise wait 1 sec and try again
					if allow_fail:
						if cache_on_fail:
							self.cache_query(query,params)
						self.db_lock.release()
						return False
					else:
						self.db_lock.release()
						sleep(1)
				
				except pymysql.err.InternalError:
					print("Internal Error");
					print("Not sure what caused this, will handled this as a failed query")
					result = False
					self.db_lock.release()
					#if we are allowed to fail we will return false otherwise wait 1 sec and try again
					if allow_fail:
						if cache_on_fail:
							self.cache_query(query,params)
						self.db_lock.release()
						return False
					else:
						self.db_lock.release()
						sleep(1)
	
			
		
if __name__ == "__main__":
	test_server = Database("greenhouse.ini")
	import time
	__testing__ = "threading"
	
	def work(_id):
		for i in range(10):
			print(_id,":",i)
			row = False

			while row == False:
				
				row = test_server.execute("SELECT pin, log FROM sensors WHERE id = %s",[1], require_commit=False, allow_fail=True, cache_on_fail=True)
				if row == False:
					print(_id,"retrying")
				time.sleep(1)
				
	def populate_cache():
		for i in range(10):
			row = test_server.execute("SELECT pin, log FROM sensors WHERE id = %s",[1], require_commit=False, allow_fail=True, cache_on_fail=True)
			row = test_server.execute("SELECT pin, log FROM sensors", require_commit=False, allow_fail=True, cache_on_fail=True)

	if __testing__ == "threading":
		for i in range(10):
			threading.Thread(target=work, args=(i,)).start()

		while threading.active_count() > 1:
			time.sleep(1)
	elif __testing__ == "cache":
		populate_cache()
		test_server.retry_cache()
	
	
