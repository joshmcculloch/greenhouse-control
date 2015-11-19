<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class API extends CI_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->model('User');
        $this->load->model('Actuator');
    }

    public function get_actuator_modes($greenhouse_id)
    {
        if ($this->User->get_level("remote-control/view")) {
            echo json_encode($this->Actuator->get_actuators($greenhouse_id));
        }
    }

    public function set_actuator_modes()
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

    public function get_schedule($actuator_id)
    {
        if ($this->User->get_level("config/schedule/view")) {
            $this->load->model('Schedule');
            echo json_encode($this->Schedule->get_schedule($actuator_id));
        }
    }

    public function get_schedules() {

    }

    public function set_schedule()
    {
        if ($this->User->get_level("config/schedule/edit")) {
            $this->load->model('Schedule');
            $this->load->helper('url');
            $this->load->library('form_validation');

            $this->form_validation->set_rules('schedule_id', 'schedule_id', 'required');
            $this->form_validation->set_rules('day', 'day', 'required');
            $this->form_validation->set_rules('half_hour', 'half_hour', 'required');
            $this->form_validation->set_rules('active_time', 'active_time', 'required');
            $this->form_validation->set_rules('delay_time', 'delay_time', 'required');

            if ($this->form_validation->run() == false) {
                echo json_encode(array("error"=>"input validation failed"));
            } else {
                $this->Schedule->set_schedule($this->input->post('schedule_id'),
                    $this->input->post('day'),
                    $this->input->post('half_hour'),
                    $this->input->post('active_time'),
                    $this->input->post('delay_time'));
                echo json_encode(array("success"=>true));
            }
        }
    }

    public function create_node()
    {
        if ($this->User->get_level("config/rules/edit")) {
            $this->load->helper('url');
            $this->load->library('form_validation');
            $this->load->model('Rules');

            $this->form_validation->set_rules('actuator_id', 'actuator_id', 'required|numeric');
            $this->form_validation->set_rules('type_id', 'type_id', 'required|numeric');
            $this->form_validation->set_rules('xpos', 'xpos', 'required|numeric');
            $this->form_validation->set_rules('ypos', 'ypos', 'required|numeric');
            $this->form_validation->set_rules('value', 'value', 'required|numeric');

            if ($this->form_validation->run() == false) {
                echo json_encode(array("error"=>"input validation failed"));
            } else {
                $node_id = $this->Rules->createNode(
                    $this->input->post('actuator_id'),
                    $this->input->post('type_id'),
                    $this->input->post('xpos'),
                    $this->input->post('ypos'),
                    $this->input->post('value')
                );
                json_encode(array("node_id"=>$node_id, "success"=>true));
            }
        }
    }

    public function update_node()
    {
        if ($this->User->get_level("config/rules/edit")) {

            $this->load->helper('url');
            $this->load->library('form_validation');
            $this->load->model('Rules');

            $this->form_validation->set_rules('id', 'id', 'required|numeric');
            $this->form_validation->set_rules('xpos', 'xpos', 'required|numeric');
            $this->form_validation->set_rules('ypos', 'ypos', 'required|numeric');
            $this->form_validation->set_rules('value', 'value', 'required|numeric');

            if ($this->form_validation->run() == false) {
                echo json_encode(array("error"=>"input validation failed"));
            } else {
                $this->Rules->updateNode(
                    $this->input->post('id'),
                    $this->input->post('xpos'),
                    $this->input->post('ypos'),
                    $this->input->post('value')
                );
                echo json_encode(array("success"=>true));
            }
        }
    }

    public function link_nodes()
    {
        if ($this->User->get_level("config/rules/edit")) {

            $this->load->helper('url');
            $this->load->library('form_validation');
            $this->load->model('Rules');

            $this->form_validation->set_rules('node_in', 'node_in', 'required|numeric');
            $this->form_validation->set_rules('node_out', 'node_out', 'required|numeric');
            $this->form_validation->set_rules('actuator', 'actuator', 'required|numeric');

            if ($this->form_validation->run() == false) {
                echo json_encode(array("error"=>"input validation failed"));
            } else {
                $this->Rules->linkNodes(
                    $this->input->post('actuator'),
                    $this->input->post('node_in'),
                    $this->input->post('node_out')
                );
                echo json_encode(array("success"=>true));
            }
        }
    }

    public function unlink_nodes()
    {
        if ($this->User->get_level("config/rules/edit")) {

            $this->load->helper('url');
            $this->load->library('form_validation');
            $this->load->model('Rules');

            $this->form_validation->set_rules('node_in', 'node_in', 'required|numeric');
            $this->form_validation->set_rules('node_out', 'node_out', 'required|numeric');

            if ($this->form_validation->run() == false) {
                echo json_encode(array("error"=>"input validation failed"));
            } else {
                $this->Rules->unlinkNodes(
                    $this->input->post('node_in'),
                    $this->input->post('node_out')
                );
                echo json_encode(array("success"=>true));
            }
        }
    }

    public function delete_node()
    {
        if ($this->User->get_level("config/rules/edit")) {

            $this->load->helper('url');
            $this->load->library('form_validation');
            $this->load->model('Rules');

            $this->form_validation->set_rules('id', 'id', 'required|numeric');

            if ($this->form_validation->run() == false) {
                echo json_encode(array("error"=>"input validation failed"));
            } else {
                $this->Rules->deleteNode(
                    $this->input->post('id')
                );
                echo json_encode(array("success"=>true));
            }
        }
    }

    public function graph($period="day") {
        $this->load->model('Graph');
        $data['graphs'] = $this->Graph->get_front_page_graphs($period);
        if ($this->User->get_level("sensors/view")) {
            $this->load->view('panels/sensor_panel_data', $data);
        }
    }
}