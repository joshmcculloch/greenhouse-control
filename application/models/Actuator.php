<?php

class Actuator extends CI_Model {

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    function get_actuators($greenhouse_id)
    {
        $query = $this->db->query('SELECT id, name, mode_id, status FROM actuators WHERE greenhouse_id=?',
            array($greenhouse_id));
        return $query->result_array();
    }

    function get_actuator($actuator_id)
    {
        $query = $this->db->query('SELECT id, greenhouse_id,  name, mode_id, status FROM actuators WHERE id=?', array($actuator_id));
        return $query->row();
    }

    function set_actuator($actuator_id, $mode_id)
    {
        $query = $this->db->query("UPDATE actuators SET mode_id=?, status='Waiting for greenhouse to respond' WHERE id=? AND mode_id<>?",
            array($mode_id, $actuator_id, $mode_id,)
        );
    }

    function rename_actuator($actuator_id, $name) {
        $this->db->query('UPDATE actuators SET name=? WHERE id=?', array($name, $actuator_id));
    }
}