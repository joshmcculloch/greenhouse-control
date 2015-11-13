from server import Database

class Node(object):
	
	def __init__(self, db_id):
		self.db_id = db_id
		self.nodes = []
		self.inputtype = "none"
		self.max_in = 0
		
	def getValue(self):
		return None
		
	def getValueType(self):
		return "none"
		
	def linkNode(self, node):
		if len(self.nodes) == self.max_in:
			raise Exception("Already reached maximum number of input nodes.")
		elif self.inputtype != "none" and node.getValueType() == self.inputtype:
			self.nodes.append(node)
		else:
			raise Exception("Match error: (%s) !=> (%s)" %(node.getValueType(), self.inputtype))
		
	def clearLinks(self):
		self.nodes = []
				

class SensorNode(Node):
	
	def __init__(self, db_id, sensor):
		Node.__init__(self,db_id)
		self.sensor = sensor
		self.inputtype = "none"
		self.max_in = 0
		
	def getValue(self):
		return self.sensor.readValue()
		
	def getValueType(self):
		return "float"
	
class ActuatorNode(Node):
	
	def __init__(self, db_id):
		Node.__init__(self,db_id)
		self.inputtype = "bool"
		self.max_in = 1
		
	def getValue(self):
		return self.nodes[0].getValue()
		
	def getValueType(self):
		return "bool"
	
class ScheduleNode(Node):
	
	def __init__(self, db_id):
		Node.__init__(self,db_id)
		self.inputtype = "none"
		self.max_in = 0
		
	def getValue(self):
		return False
		
	def getValueType(self):
		return "bool"
		
class GreaterThanNode(Node):
	
	def __init__(self, db_id, value):
		Node.__init__(self,db_id)
		self.value = value
		self.inputtype = "float"
		self.max_in = 1
		
	def getValue(self):
		if len(self.nodes) < 1:
			raise Exception("GreaterThanNode is missing an input")
		return self.nodes[0].getValue() > self.value
		
	def getValueType(self):
		return "bool"
	
class LessThanNode(Node):
	
	def __init__(self, db_id, value):
		Node.__init__(self,db_id)
		self.value = value
		self.inputtype = "float"
		self.max_in = 1
		
	def getValue(self):
		if len(self.nodes) < 1:
			raise Exception("LessThanNode is missing an input")
		return self.nodes[0].getValue() < self.value
		
	def getValueType(self):
		return "bool"
	
class AndNode(Node):
	
	def __init__(self, db_id):
		Node.__init__(self,db_id)
		self.inputtype = "bool"
		self.max_in = 2
		
	def getValue(self):
		if len(self.nodes) < 2:
			raise Exception("AndNode is missing an input")
		return self.nodes[0].getValue() and self.nodes[1].getValue()
		
	def getValueType(self):
		return "bool"
		
class OrNode(Node):
	
	def __init__(self, db_id):
		Node.__init__(self,db_id)
		self.inputtype = "bool"
		self.max_in = 2
		
	def getValue(self):
		if len(self.nodes) < 2:
			raise Exception("OrNode is missing an input")
		return self.nodes[0].getValue() or self.nodes[1].getValue()
		
	def getValueType(self):
		return "bool"
	
class NotNode(Node):
	
	def __init__(self, db_id):
		Node.__init__(self,db_id)
		self.inputtype = "bool"
		self.max_in = 1
		
	def getValue(self):
		if len(self.nodes) < 1:
			raise Exception("NotNode is missing an input")
		return not(self.nodes[0].getValue())
		
	def getValueType(self):
		return "bool"
		

		
class RuleManager(object):
	
	def __init__(self, database, sensors):
		self.database = database
		self.nodes = dict()
		self.sensors = sensors
		self.loadNodes()
		self.loadLinks()
		
	def loadNodes(self):
		for row in self.database.execute("SELECT * FROM nodes", [], require_commit=False):
			if row['type_id'] == 1:
				sensor = None
				for s in self.sensors:
					if s.db_id == row['sensor_id']:
						sensor = s 
				if sensor == None:
					raise Exception("Unable to find sensor %i" %row['sensor_id'])
				self.nodes[row['id']] = SensorNode(row['id'], None)
			elif row['type_id'] == 2:
				self.nodes[row['id']] = GreaterThanNode(row['id'], row['value'])
			elif row['type_id'] == 3:
				self.nodes[row['id']] = LessThanNode(row['id'], row['value'])
			elif row['type_id'] == 4:
				self.nodes[row['id']] = AndNode(row['id'])
			elif row['type_id'] == 5:
				self.nodes[row['id']] = OrNode(row['id'])
			elif row['type_id'] == 6:
				self.nodes[row['id']] = NotNode(row['id'])
			elif row['type_id'] == 7:
				self.nodes[row['id']] = ActuatorNode(row['id'])
			elif row['type_id'] == 8:
				self.nodes[row['id']] = ScheduleNode(row['id'])
				
	def loadLinks(self):
		for row in self.database.execute("SELECT * FROM nodelinks", [], require_commit=False):
			node_in = self.nodes[row['node_in']]
			node_out = self.nodes[row['node_out']]
			node_in.linkNode(node_out)
			
	def getNodeState(self, nodeid):
		return self.nodes[nodeid].getValue()
		
if __name__ == "__main__":
	from green_control import Sensor, Clock, DHT_Temp, DHT_Humid, Moisture_Probe
	testserver = Database("greenhouse.ini")
	
	sensors = []
	sensors.append(Clock(1, testserver))
	sensors.append(DHT_Temp(3, testserver))
	sensors.append(DHT_Humid(4, testserver))
	sensors.append(DHT_Temp(5, testserver))
	sensors.append(DHT_Humid(6, testserver))
	sensors.append(DHT_Temp(7, testserver))
	sensors.append(DHT_Humid(8, testserver))
	sensors.append(DHT_Temp(9, testserver))
	sensors.append(DHT_Humid(10, testserver))
	sensors.append(Sensor(11, testserver))
	sensors.append(Moisture_Probe(12, testserver))

	ruleManager = RuleManager(testserver, sensors)
	print(len(ruleManager.nodes))
	print(ruleManager.getNodeState(15))

			
		
	
