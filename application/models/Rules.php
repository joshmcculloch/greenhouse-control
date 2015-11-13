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

    function createNode ($actuator_id, $type_id, $xpos, $ypos, $value) {
        $this->db->query('INSERT INTO nodes (actuator_id, type_id, sensor_id, value, xpos, ypos)
VALUES (?, ?, ?, ?, ?, ?)', [$actuator_id, $type_id, 0, $value, $xpos, $ypos]);
        return $this->db->insert_id();
    }

    function updateNode($id, $xpos, $ypos, $value) {
        $this->db->query('UPDATE nodes SET value=?, xpos=?, ypos=? WHERE id=?',
            [$value, $xpos, $ypos, $id]);
    }

    function linkNodes($actuator, $node_in, $node_out) {
        $this->db->query('INSERT INTO nodelinks (actuator_id, node_in, node_out)
VALUES (?, ?, ?)', [$actuator, $node_in, $node_out]);
    }

    function unlinkNodes($node_in, $node_out) {
        $this->db->query('DELETE FROM nodelinks WHERE node_in=? AND node_out=?', [$node_in, $node_out]);
    }

    function deleteNode($id) {
        $this->db->query('DELETE FROM nodelinks WHERE node_in=? or node_out=?', [$id, $id]);
        $this->db->query('DELETE FROM nodes WHERE id=?', [$id]);
    }


}

