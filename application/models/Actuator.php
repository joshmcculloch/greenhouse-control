<?php

class Actuator extends CI_Model {

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    function create_actuator($greenhouse_id, $name) {
        $this->load->model("Rules");

        //Create a rule system and node for the new actuator
        $rule_system_id = $this->Rules->create_rule_system($greenhouse_id, $name." rule system");
        $node_id = $this->Rules->createNode ($rule_system_id, 7, 0, 0, 0);

        //Create the actuator
        $this->db->query('INSERT INTO actuators (greenhouse_id, name, mode_id, node_id, pin, status)
VALUES (?, ?, ?, ?, ?, ?)',
            array($greenhouse_id, $name, 2, $node_id, 0, "Just Created"));
        $actuator_id = $this->db->insert_id();

        //Create the schedule for the new actuator
        $this->Schedule->create_schedule($greenhouse_id, $actuator_id);
    }

    function get_actuators($greenhouse_id)
    {
        $query = $this->db->query('SELECT id, greenhouse_id, name, mode_id, status FROM actuators WHERE greenhouse_id=?',
            array($greenhouse_id));
        return $query->result_array();
    }

    function get_actuator($actuator_id)
    {
        $query = $this->db->query('SELECT * FROM actuators WHERE id=?', array($actuator_id));
        return $query->row();
    }

    function set_mode($actuator_id, $mode_id)
    {
        $query = $this->db->query("UPDATE actuators SET mode_id=?, status='Waiting for greenhouse to respond' WHERE id=? AND mode_id<>?",
            array($mode_id, $actuator_id, $mode_id,)
        );
    }

    function get_rule_system_id($actuator_id)
    {
        $node_id = $this->get_actuator($actuator_id)->node_id;
        $this->load->model("Rules");
        return $this->Rules->getNode($node_id)["rule_system_id"];
    }

    function rename_actuator($actuator_id, $name) {
        $this->db->query('UPDATE actuators SET name=? WHERE id=?', array($name, $actuator_id));
    }
}