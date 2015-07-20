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

    function create_user($username, $password, $level) {
        $query = $this->db->query('INSERT INTO users (username, password, level) VALUES (?, ?, ?)', array($username,sha1($password),$level));
    }

    function login($username, $password) {
        $query = $this->db->query('SELECT * FROM users WHERE username like ?', array($username));
        if ($query->num_rows() == 1) {
            $user =  $level = $query->row();
            if (sha1($password) == $user->password) {
                $data = array(
                    'username'  => $username,
                    'loggedin' => TRUE,
                    'userlevel' => $user->level
                );

                $this->session->set_userdata($data);
                return TRUE;
            }
        }
        return FALSE;
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