<?php

class Rules extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    function create_rule_system ($greenhouse_id, $name) {
        $this->db->query('INSERT INTO rule_system (greenhouse_id, name) VALUES (?, ?)',
            array($greenhouse_id, $name));
        return $this->db->insert_id();
    }

    function getNodes ($rule_system_id)
    {
        $query = $this->db->query('SELECT
nodes.id,
nodes.rule_system_id,
nodes.type_id,
nodes.value,
nodes.xpos,
nodes.ypos,
actuators.name as \'actuator_name\',
actuators.id as \'actuator_id\',
sensors.name as \'sensor_name\',
sensors.id as \'sensor_id\'
FROM nodes
LEFT JOIN (SELECT id, name, node_id FROM actuators) as actuators on actuators.node_id=nodes.id
LEFT JOIN (SELECT id, name, node_id FROM sensors) as sensors on sensors.node_id=nodes.id
WHERE rule_system_id=? OR rule_system_id=-1',
            array($rule_system_id));
        return $query->result_array();
    }

    function getNode($node_id) {
        $query = $this->db->query('SELECT * FROM nodes WHERE id=?', array($node_id));
        return $query->row_array();
    }

    function getLinks ($rule_system_id)
    {
        $query = $this->db->query('SELECT * FROM nodelinks WHERE rule_system_id=?', array($rule_system_id));
        return $query->result_array();
    }

    function getNodeTypes ()
    {
        $query = $this->db->query('SELECT * FROM nodetype');
        return $query->result_array();
    }

    function createNode ($rule_system_id, $type_id, $xpos, $ypos, $value) {
        $this->db->query('INSERT INTO nodes (rule_system_id, type_id, value, xpos, ypos)
VALUES (?, ?, ?, ?, ?)', [$rule_system_id, $type_id, $value, $xpos, $ypos]);
        return $this->db->insert_id();
    }

    function updateNode($id, $xpos, $ypos, $value) {
        $this->db->query('UPDATE nodes SET value=?, xpos=?, ypos=? WHERE id=?',
            [$value, $xpos, $ypos, $id]);
    }

    function linkNodes($rule_system_id, $node_in, $node_out) {
        $this->db->query('INSERT INTO nodelinks (rule_system_id, node_in, node_out)
VALUES (?, ?, ?)', [$rule_system_id, $node_in, $node_out]);
    }

    function unlinkNodes($node_in, $node_out) {
        $this->db->query('DELETE FROM nodelinks WHERE node_in=? AND node_out=?', [$node_in, $node_out]);
    }

    function deleteNode($id) {
        $this->db->query('DELETE FROM nodelinks WHERE node_in=? or node_out=?', [$id, $id]);
        $this->db->query('DELETE FROM nodes WHERE id=?', [$id]);
    }


}

