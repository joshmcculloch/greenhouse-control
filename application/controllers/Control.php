<?php

class Control extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('User');
        $this->load->model('Actuator');
    }

    public function get_control()
    {
        echo json_encode($this->Actuator->get_actuators());
    }

    public function set_control()
    {
        if ($this->User->get_level("remote-control/edit")) {
            $this->load->helper('url');
            $this->load->library('form_validation');

            $this->form_validation->set_rules('id', 'id', 'required');
            $this->form_validation->set_rules('mode', 'mode', 'required');

            if ($this->form_validation->run() == false) {
                echo json_encode($this->Actuator->get_actuators());
            } else {
                $this->Actuator->set_actuator(
                    $this->input->post('id'),
                    $this->input->post('mode')
                );
                echo json_encode($this->Actuator->get_actuators());
            }
        }
    }
}