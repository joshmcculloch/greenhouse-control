<?php

class Greenhouse extends CI_Model {

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    function valid_id($greenhouse_id) {
        $query = $this->db->query('SELECT COUNT(*) as \'count\' FROM greenhouse WHERE id=?',
            array($greenhouse_id));
        return true ? $query->row()->count == 1 : false;
    }

    function get_name($greenhouse_id) {
        $query = $this->db->query('SELECT name FROM greenhouse WHERE id=?',
            array($greenhouse_id));
        return $query->row()->name;
    }

    function get_greenhouses($user_id) {
        if ($user_id == 0) {
            return [['id'=>1, 'name'=>'Demo Greenhouse']];
        }
        $query = $this->db->query('
SELECT
greenhouse.id as \'id\',
greenhouse.name as \'name\'
FROM user_greenhouse_link
LEFT JOIN greenhouse on greenhouse.id=user_greenhouse_link.greenhouse_id
WHERE user_greenhouse_link.user_id=?',
            array($user_id));
        return $query->result_array();
    }
}