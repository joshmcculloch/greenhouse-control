from server import Database

class Node(object):
	
	def __init__(self, db_id):
		self.db_id = db_id
		self.links = []
		
	def getValue(self):
		return False
		
	def getValueType(self):
		return "bool"
		
	def clearLinks(self):
		self.links = []
				

class Sensor(Node):
	
	def __init__(self, db_id, sensor):
		Node.__init__(self,db_id)
		self.sensor = sensor
		
	def getValue(self):
		return self.sensor.readValue()
		
	def getValueType(self):
		return "float"
	
class Actuator(Node):
	
	def __init__(self, db_id):
		Node.__init__(self,db_id)
		
	def getValue(self):
		return None
		
	def getValueType(self):
		return "none"
	
class Schedule(Node):
	
	def __init__(self, db_id):
		Node.__init__(self,db_id)
		
class Greater_Than(Node):
	
	def __init__(self, db_id, value):
		Node.__init__(self,db_id)
		self.node1 = None
		self.value = value
	
class Less_Than(Node):
	
	def __init__(self, db_id, value):
		Node.__init__(self,db_id)
		self.node1 = None
		self.value = value
	
class And(Node):
	
	def __init__(self, db_id):
		Node.__init__(self,db_id)
		self.node1 = None
		self.node2 = None
	
class Or(Node):
	
	def __init__(self, db_id):
		Node.__init__(self,db_id)
		self.node1 = None
		self.node2 = None
	
class Not(Node):
	
	def __init__(self, db_id):
		Node.__init__(self,db_id)
		self.node1 = None
		
class RuleManager(object):
	
	def __init__(self, database, sensors):
		self.database = database
		self.nodes = dict()
		self.loadNodes()
		#print(self.database.execute("SELECT * FROM nodelinks", [], require_commit=False))
		
	def loadNodes(self):
		for row in self.database.execute("SELECT * FROM nodes", [], require_commit=False):
			if row['type_id'] == 1:
				self.nodes[row['id']] = Sensor(row['id'], None)
			elif row['type_id'] == 2:
				self.nodes[row['id']] = Greater_Than(row['id'], row['value'])
			elif row['type_id'] == 3:
				self.nodes[row['id']] = Less_Than(row['id'], row['value'])
			elif row['type_id'] == 4:
				self.nodes[row['id']] = And(row['id'])
			elif row['type_id'] == 5:
				self.nodes[row['id']] = Or(row['id'])
			elif row['type_id'] == 6:
				self.nodes[row['id']] = Not(row['id'])
			elif row['type_id'] == 7:
				self.nodes[row['id']] = Actuator(row['id'])
			elif row['type_id'] == 8:
				self.nodes[row['id']] = Schedule(row['id'])
			
	def getNodeState(self, node_id):
		pass
		
if __name__ == "__main__":
	test_server = Database("greenhouse.ini")
	import time
	

	ruleManager = RuleManager(test_server, [])
	print(len(ruleManager.nodes))

			
		
	
