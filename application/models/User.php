<?php

class User extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        if (!isset($_SESSION['loggedin'])) {
            $this->init();
        }
    }

    function init() {
        $data = array(
            'username'  => '',
            'loggedin' => FALSE,
            'userlevel' => 0
        );
        $this->session->set_userdata($data);
    }

    function login($username, $password) {
        if ($username == "admin" && $password == "admin" || TRUE) {
            $data = array(
                'username'  => $username,
                'loggedin' => TRUE,
                'userlevel' => 1
            );

            $this->session->set_userdata($data);
            return TRUE;
        } else {
            //$this->init();
            return FALSE;
        }
    }

    function logout() {
        $this->session->sess_destroy();
    }

    function get_level($action)
    {
        $query = $this->db->query('SELECT * FROM permissions WHERE action like ?', array($action));
        if ($query->num_rows() == 1)
        {
            $level = $query->row()->level;
            return $this->session->userlevel >= $level;
        } else {
            return FALSE;
        }


    }
}