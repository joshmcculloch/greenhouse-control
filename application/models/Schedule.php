<?php

class Schedule extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    function create_schedule($greenhouse_id, $actuator_id)
    {
        $this->load->model("Rules");
        $this->load->model("Actuator");

        $actuator_node_id = $this->Actuator->get_actuator($actuator_id)->node_id;
        echo $actuator_node_id;
        echo " - ";
        $rule_system_id = $this->Rules->getNode($actuator_node_id)["rule_system_id"];
        echo $rule_system_id;
        echo " - ";
        //Create Node
        $node_id = $this->Rules->createNode ($rule_system_id, 8, 0, 0, 0);
        echo $node_id;
        echo " - ";


        //Create Schedule
        $this->db->query('INSERT INTO schedule (greenhouse_id, node_id, actuator_id, name) VALUES (?, ?, ?, ?)',
            array($greenhouse_id, $node_id, $actuator_id, "unnamed schedule"));
        $schedule_id = $this->db->insert_id();

        //Populate schedule times
        $this->db->query('DELETE FROM schedule_times WHERE schedule_id = ?', $schedule_id);
        for($day=0; $day<7; $day++) {
            for($half_hour=0; $half_hour<48; $half_hour++) {
                $this->db->query('INSERT INTO schedule_times (schedule_id, day, half_hour, duty, active_time, delay_time) VALUES (?, ?, ?, ?, ?, ?)',
                    array($schedule_id, $day, $half_hour, 0, 0, 0));
            }
        }
        return $schedule_id;
    }

    function get_schedules_by_greenhouse($greenhouse_id) {
        $query = $this->db->query('SELECT * FROM schedule WHERE greenhouse_id=?',
            array($greenhouse_id));
        return $query->result_array();
    }

    function get_schedules_by_actuator($actuator_id) {
        $query = $this->db->query('SELECT * FROM schedule WHERE actuator_id=?',
            array($actuator_id));
        return $query->result_array();
    }

    function get_schedule_name($schedule_id) {
        $query = $this->db->query('SELECT name FROM schedule WHERE id=?',
            array($schedule_id));
        return $query->row()->name;
    }

    function get_schedule_times($schedule_id)
    {
        $query_schedule = $this->db->query('SELECT day, half_hour, active_time, delay_time FROM schedule_times WHERE schedule_id=? ORDER BY day, half_hour',
            array($schedule_id));
        return $query_schedule->result_array();
    }

    function set_schedule($schedule_id, $day, $half_hour, $active_time, $delay_time) {
        $this->db->query('UPDATE schedule_times SET active_time=?, delay_time=? WHERE schedule_id=? AND day=? AND half_hour=?',
            array($active_time, $delay_time, $schedule_id, $day, $half_hour));
    }

}