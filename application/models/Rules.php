<?php

class Rules extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    function getNodes ($actuator_id)
    {
        $query = $this->db->query('SELECT
nodes.id,
nodes.actuator_id,
nodes.type_id,
nodes.sensor_id,
nodes.value,
nodes.xpos,
nodes.ypos,
actuators.name as \'actuator_name\',
sensors.name as \'sensor_name\'
FROM nodes
LEFT JOIN (SELECT id, name FROM actuators) as actuators on actuator_id=actuators.id
LEFT JOIN (SELECT id, name FROM sensors) as sensors on sensor_id=sensors.id
WHERE actuator_id=? OR actuator_id=-1',
            array($actuator_id));
        return $query->result_array();
    }

    function getLinks ($actuator_id)
    {
        $query = $this->db->query('SELECT * FROM nodelinks WHERE actuator_id=? OR actuator_id=-1', array($actuator_id));
        return $query->result_array();
    }

    function getNodeTypes ()
    {
        $query = $this->db->query('SELECT * FROM nodetype');
        return $query->result_array();
    }

    function saveNode ()
    {

    }

    function saveLink ()
    {

    }

}

