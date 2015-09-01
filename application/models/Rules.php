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
        $query = $this->db->query('SELECT * FROM nodes WHERE actuator_id=? OR actuator_id=-1', array($actuator_id));
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

