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
	
	def __init__(self, db_id):
		Node.__init__(self,db_id)
		self.sensor = None
		self.inputtype = "none"
		self.max_in = 0
		
	def getValue(self):
		if self.sensor != None:
			return self.sensor.read()
		else:
			raise Exception("Sensor has not been defined.")
		
	def getValueType(self):
		return "float"
	
class ActuatorNode(Node):
	
	def __init__(self, db_id):
		Node.__init__(self,db_id)
		self.inputtype = "bool"
		self.max_in = 1
		
	def getValue(self):
		#print("getting actuator value")
		return self.nodes[0].getValue()
		
	def getValueType(self):
		return "bool"
	
class ScheduleNode(Node):
	
	def __init__(self, db_id):
		Node.__init__(self,db_id)
		self.inputtype = "none"
		self.max_in = 0
		self.schedule = None
		
	def getValue(self):
		if self.schedule == None:
			raise Exception("Schedule has not been defined.")
		#print("quering schedule")
		return self.schedule.get_state_now()
		
	def getValueType(self):
		return "bool"
		
class GreaterThanNode(Node):
	
	def __init__(self, db_id, value):
		Node.__init__(self,db_id)
		self.value = value
		self.inputtype = "float"
		self.max_in = 1
		
	def getValue(self):
		#print("getting greater than value")
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
		#print("getting less than value")
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
		#print("getting and value")
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
		#print("getting or value")
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
		#print("getting not value")
		if len(self.nodes) < 1:
			raise Exception("NotNode is missing an input")
		return not(self.nodes[0].getValue())
		
	def getValueType(self):
		return "bool"
		
		
class RuleManager(object):
	
	def __init__(self, database, greenhouse_id):
		self.database = database
		self.nodes = dict()
		self.greenhouse_id = greenhouse_id
		self.loadNodes()
		self.loadLinks()
		
	def loadNodes(self):
		for row in self.database.execute("""SELECT nodes.id, nodes.type_id, nodes.value
		FROM nodes 
		LEFT JOIN (SELECT id, greenhouse_id, name FROM rule_system) as rs on rs.id=nodes.rule_system_id 
		WHERE rs.greenhouse_id=%s
		OR rs.greenhouse_id=(SELECT id 
			FROM rule_system 
			WHERE global=1 
			AND greenhouse_id=(SELECT greenhouse_id 
				FROM rule_system 
				WHERE id=%s
				LIMIT 1)
			LIMIT 1)""", [self.greenhouse_id,self.greenhouse_id], require_commit=False):
			if row['type_id'] == 1:
				self.nodes[row['id']] = SensorNode(row['id'])
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
		for row in self.database.execute("SELECT nl.id, nl.node_in, nl.node_out FROM nodelinks as nl LEFT JOIN (SELECT id, greenhouse_id, name FROM rule_system) as rs on rs.id=nl.rule_system_id WHERE rs.greenhouse_id=%s", [self.greenhouse_id], require_commit=False):
			node_in = self.nodes[row['node_in']]
			node_out = self.nodes[row['node_out']]
			node_in.linkNode(node_out)
	
			
	def getNodeState(self, nodeid):
		return self.nodes[nodeid].getValue()
		
if __name__ == "__main__":
	from green_control import Sensor, Schedule, load_sensors, load_schedules, load_actuators
	import io
	testserver = Database("greenhouse.ini")
	coms = io.BytesIO()


	sensors = []
	schedules = []
	actuators = []
	
	ruleManager = RuleManager(testserver, 1)
	load_sensors(testserver, sensors, 1, ruleManager.nodes) 
	load_schedules(testserver, schedules, 1, ruleManager.nodes) 
	load_actuators(testserver, actuators, 1, coms, ruleManager.nodes)
	
	for i in sensors:
		if i.db_id == 5:
			i.value = 15
			print("Seting Greenhouse 2 Temp to",i.value)
	
	for i in schedules:
		i.load_schedule()

	for i in actuators:
		print(i.name, i.compute_state())
	#print(len(ruleManager.nodes))
	#print(ruleManager.getNodeState(15))

			
		
	
