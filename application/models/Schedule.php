<?php

class Schedule extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    function init_schedule($actuator_id)
    {
        $this->db->query('DELETE FROM schedule WHERE actuator_id = ?', $actuator_id);

        for($day=0; $day<7; $day++) {
            for($half_hour=0; $half_hour<48; $half_hour++) {
                $this->db->query('INSERT INTO schedule (actuator_id, day, half_hour, duty, active_time, delay_time) VALUES (?, ?, ?, ?, ?, ?)',
                    array($actuator_id, $day, $half_hour, 0, 0, 0));
            }
        }
    }

    function get_schedule_old($actuator_id)
    {
        $query_schedule = $this->db->query('SELECT day, half_hour, duty FROM schedule WHERE actuator_id = ? ORDER BY day, half_hour',
            array($actuator_id));
        if ($query_schedule->num_rows() == 0) {
            //TODO check if the actuator exists before creating a new schedule
            $this->init_schedule($actuator_id);
            return $this->get_schedule($actuator_id);
        } else {
            return $query_schedule->result_array();
        }
    }

    function get_schedule($actuator_id)
    {
        $query_schedule = $this->db->query('SELECT day, half_hour, active_time, delay_time FROM schedule WHERE actuator_id = ? ORDER BY day, half_hour',
            array($actuator_id));
        if ($query_schedule->num_rows() == 0) {
            //TODO check if the actuator exists before creating a new schedule
            $this->init_schedule($actuator_id);
            return $this->get_schedule($actuator_id);
        } else {
            return $query_schedule->result_array();
        }
    }


    function set_schedule_old($actuator_id, $day, $half_hour, $duty)
    {
        $this->db->query('UPDATE schedule SET duty=? WHERE actuator_id=? AND day=? AND half_hour=?',
            array($duty, $actuator_id, $day, $half_hour));
    }


    function set_schedule($actuator_id, $day, $half_hour, $active_time, $delay_time) {
        $this->db->query('UPDATE schedule SET active_time=?, delay_time=? WHERE actuator_id=? AND day=? AND half_hour=?',
            array($active_time, $delay_time, $actuator_id, $day, $half_hour));
        $this->db->query('UPDATE actuators SET revision=(revision+1)WHERE id=?',
            array($actuator_id));
    }

}