<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->model('User');
        $this->load->model('Actuator');
        $this->load->model('Graph');
        $this->load->model('Greenhouse');
        $this->load->model('Schedule');
        $this->load->model('Rules');
        $this->load->helper('url');
    }

    public function index()
    {

        $this->load->helper('url');

        $data['heading'] = 'Home';
        $data['greenhouses'] = $this->Greenhouse->get_greenhouses($this->User->get_user_id());

        $this->load->view('head', $data);
        $this->load->view('/pages/home');
        $this->load->view('tail');

    }

    public function overview($greenhouse_id=-1)
    {

        $data['greenhouses'] = $this->Greenhouse->get_greenhouses($this->User->get_user_id());
        if ($greenhouse_id == -1 and sizeof($data['greenhouses']) > 0) {
            $greenhouse_id = $data['greenhouses'][0]['id'];
        }
        if ($this->Greenhouse->valid_id($greenhouse_id)) {
            $data['actuators'] = $this->Actuator->get_actuators($greenhouse_id);
            $data['graphs'] = $this->Graph->get_front_page_graphs($greenhouse_id);
            $data['heading'] = $this->Greenhouse->get_name($greenhouse_id);
            $data['greenhouse_id'] = $greenhouse_id;

            $this->load->view('head', $data);
            if ($this->User->get_level("live-stream/view")) {
                $this->load->view('panels/live_stream', $data);
            }
            if ($this->User->get_level("remote-control/view")) {
                $this->load->view('panels/remote_control', $data);
            }
            if ($this->User->get_level("sensors/view")) {
                $this->load->view('panels/sensor_panel', $data);
            }
            $this->load->view('tail');
        } else {
            show_error('You do not have permission to access this greenhouse', 403);
        }
    }

    public function debug (){
        $this->Actuator->create_actuator(1, "Test actuator 1");
        $this->Actuator->create_actuator(1, "Test actuator 2");
    }

    public function configure($actuator_id)
    {
        if ($this->User->get_level("config/view")) {
            $actuator = $this->Actuator->get_actuator($actuator_id);
            $greenhouse_id = $actuator->greenhouse_id;

            $data['heading'] = $actuator->name . ' configuration';
            $data['actuator_id'] = $actuator_id;
            $data['greenhouses'] = $this->Greenhouse->get_greenhouses($this->User->get_user_id());
            $data['greenhouse_id'] = $greenhouse_id;

            $this->load->view('head', $data);
            /*
            if ($this->User->get_level("config/general/view")) {
                $this->load->view('panels/general_config_panel', $data);
            }*/
            if ($this->User->get_level("config/schedule/view")) {
                $schedules = $this->Schedule->get_schedules_by_actuator($actuator_id);
                if (count($schedules) == 0) {
                    $this->Schedule->create_schedule($greenhouse_id, $actuator_id);
                    $schedules = $this->Schedule->get_schedules_by_actuator($actuator_id);
                }
                $data["edit_schedule"] = $this->User->get_level("config/schedule/edit");
                foreach($schedules as $schedule) {
                    $data["schedule_id"] = $schedule['id'];
                    $this->load->view('panels/schedule_panel', $data);
                }

            }

            if ($this->User->get_level("config/rules/view")) {
                $data['rule_system_id'] = $this->Actuator->get_rule_system_id($actuator_id);
                $data['nodes'] = $this->Rules->getNodes($data['rule_system_id']);
                $data['nodetypes'] = $this->Rules->getNodeTypes();
                $data['links'] = $this->Rules->getLinks($data['rule_system_id']);
                $this->load->view('panels/rule_panel', $data);
            }
            /*
            if ($this->User->get_level("config/tests/view")) {
                $this->load->view('panels/test_panel', $data);
            }*/
            $this->load->view('tail');
        } else {
            show_error('You do not have permission to access this page', 403);
        }
    }

    public function login() {
        $this->load->library('form_validation');

        $this->form_validation->set_rules('username', 'username', 'required');
        $this->form_validation->set_rules('password', 'password', 'required');
        if ($this->form_validation->run() == false) {
            redirect("/", "location");
        } else {
            if ($this->input->post('username') == "admin" && $this->input->post('password') == "admin") {
                $this->load->helper("url");
                redirect("https://www.youtube.com/watch?v=dQw4w9WgXcQ");
            }
            else if ($this->User->login($this->input->post('username'), $this->input->post('password'))) {
                redirect("/dashboard/overview", "location");
            } else {
                redirect("/", "location");
            }
        }
    }

    public function logout() {
        $this->User->logout();
        redirect("/", "location");

    }
}
