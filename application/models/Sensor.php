<?php

class Sensor extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    function create_sensor($greenhouse_id, $name, $pin) {
        $this->load->model('Rules');
        $node_id = $this->Rules->create_node(-1, 1, 0, 0); //Create a node for the sensor
        $this->db->query('INSERT INTO sensors (greenhouse_id, name, pin, node_id, log)  VALUES (?, ?, ?, ?, ?, ?)',
            array($greenhouse_id, $name, $pin, $node_id, 1));
    }

    function set_name($sensor_id, $name) {
        $this->db->query('UPDATE sensors SET name=? WHERE id=?',
            array($name, $sensor_id));
    }

    function set_pin($sensor_id, $pin) {
        $this->db->query('UPDATE sensors SET pin=? WHERE id=?',
            array($pin, $sensor_id));
    }

    function get_value($sensor_id) {
        $this->db->query('SELECT value, time FROM sensor_data WHERE sensor_id=? ORDER BY time DESC LIMIT 1',
            array($pin, $sensor_id));
    }
}