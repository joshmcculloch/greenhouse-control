<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->model('User');
    }

    public function index()
    {
        $this->load->model('Actuator');
        $this->load->model('Graph');
        $this->load->helper('url');


        $data['actuators'] = $this->Actuator->get_actuators();
        $data['graphs'] = $this->Graph->get_front_page_graphs();
        $data['heading'] = 'Dashboard';

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
    }

    public function configure($actuator_id)
    {
        if ($this->User->get_level("config/view")) {
            $this->load->model('Actuator');
            $this->load->model('Schedule');
            $this->load->model('Rules');
            $this->load->helper('url');
            $actuator = $this->Actuator->get_actuator($actuator_id);

            $data['heading'] = $actuator[0]['name'] . ' configuration';
            $data['actuator_id'] = $actuator_id;

            $this->load->view('head', $data);
            if ($this->User->get_level("config/general/view")) {
                $this->load->view('panels/general_config_panel', $data);
            }
            if ($this->User->get_level("config/schedule/view")) {
                $data["edit_schedule"] = $this->User->get_level("config/schedule/edit");
                $this->load->view('panels/schedule_panel', $data);
            }
            if ($this->User->get_level("config/rules/view")) {
                $data['nodes'] = $this->Rules->getNodes($actuator_id);
                $data['nodetypes'] = $this->Rules->getNodeTypes();
                $data['links'] = $this->Rules->getLinks($actuator_id);
                $this->load->view('panels/rule_panel', $data);
            }
            if ($this->User->get_level("config/tests/view")) {
                $this->load->view('panels/test_panel', $data);
            }
            $this->load->view('tail');
        } else {
            show_error('You do not have permission to access this page', 403);
        }
    }

    public function init_schedule()
    {
        $this->load->helper( 'url');
        $this->load->library('form_validation');
        $this->load->model('Schedule');

        $this->form_validation->set_rules('actuator_id', 'actuator_id', 'required');


        if($this->form_validation->run() == false) {
            echo json_encode(array());
        } else {
            $actuator_id = $this->input->post('actuator_id');
            $this->Schedule->init_schedule($actuator_id);
            echo json_encode($this->Schedule->get_schedule($actuator_id));
        }
    }

    public function get_schedule($actuator_id)
    {
        $this->load->model('Schedule');
        echo json_encode($this->Schedule->get_schedule($actuator_id));
    }

    public function set_schedule()
    {
        if ($this->User->get_level("config/schedule/edit")) {
            $this->load->model('Schedule');
            $this->load->helper('url');
            $this->load->library('form_validation');

            $this->form_validation->set_rules('actuator_id', 'actuator_id', 'required');
            $this->form_validation->set_rules('day', 'day', 'required');
            $this->form_validation->set_rules('half_hour', 'half_hour', 'required');
            $this->form_validation->set_rules('active_time', 'active_time', 'required');
            $this->form_validation->set_rules('delay_time', 'delay_time', 'required');

            if ($this->form_validation->run() == false) {
                //echo "VALIDATION FAILED";
            } else {
                $this->Schedule->set_schedule($this->input->post('actuator_id'),
                    $this->input->post('day'),
                    $this->input->post('half_hour'),
                    $this->input->post('active_time'),
                    $this->input->post('delay_time'));
                //echo "SUCCESS";
            }
        }
    }

    public function login() {
        $this->load->helper( 'url');
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
                redirect("/", "location");
            } else {
                redirect("/", "location");
            }
        }
    }

    public function logout() {
        $this->load->helper( 'url');

        $this->User->logout();
        redirect("/", "location");

    }

    public function graph($period="day") {
        $this->load->model('Graph');
        $data['graphs'] = $this->Graph->get_front_page_graphs($period);
        if ($this->User->get_level("sensors/view")) {
            $this->load->view('panels/sensor_panel_data', $data);
        }
    }

    public function create_node()
    {
        if ($this->User->get_level("config/rules/edit")) {

            //$actuator_id, $type_id, $xpos, $ypos, $value
            $this->load->helper('url');
            $this->load->library('form_validation');
            $this->load->model('Rules');

            $this->form_validation->set_rules('actuator_id', 'actuator_id', 'required|numeric');
            $this->form_validation->set_rules('type_id', 'type_id', 'required|numeric');
            $this->form_validation->set_rules('xpos', 'xpos', 'required|numeric');
            $this->form_validation->set_rules('ypos', 'ypos', 'required|numeric');
            $this->form_validation->set_rules('value', 'value', 'required|numeric');

            if ($this->form_validation->run() == false) {
                echo "ERROR";//json_encode($this->Actuator->get_actuators());
            } else {
                echo $this->Rules->createNode(
                    $this->input->post('actuator_id'),
                    $this->input->post('type_id'),
                    $this->input->post('xpos'),
                    $this->input->post('ypos'),
                    $this->input->post('value')
                );
            }
        }
    }

    public function update_node()
    {
        if ($this->User->get_level("config/rules/edit")) {

            //$id, $xpos, $ypos, $value
            $this->load->helper('url');
            $this->load->library('form_validation');
            $this->load->model('Rules');

            $this->form_validation->set_rules('id', 'id', 'required|numeric');
            $this->form_validation->set_rules('xpos', 'xpos', 'required|numeric');
            $this->form_validation->set_rules('ypos', 'ypos', 'required|numeric');
            $this->form_validation->set_rules('value', 'value', 'required|numeric');

            if ($this->form_validation->run() == false) {
                echo "ERROR";//json_encode($this->Actuator->get_actuators());
            } else {
                $this->Rules->updateNode(
                    $this->input->post('id'),
                    $this->input->post('xpos'),
                    $this->input->post('ypos'),
                    $this->input->post('value')
                );
                echo "SUCCESS";
            }
        }
    }

    public function link_nodes()
    {
        if ($this->User->get_level("config/rules/edit")) {

            //$node_in, $node_out
            $this->load->helper('url');
            $this->load->library('form_validation');
            $this->load->model('Rules');

            $this->form_validation->set_rules('node_in', 'node_in', 'required|numeric');
            $this->form_validation->set_rules('node_out', 'node_out', 'required|numeric');
            $this->form_validation->set_rules('actuator', 'actuator', 'required|numeric');

            if ($this->form_validation->run() == false) {
                echo "ERROR";//json_encode($this->Actuator->get_actuators());
            } else {
                $this->Rules->linkNodes(
                    $this->input->post('actuator'),
                    $this->input->post('node_in'),
                    $this->input->post('node_out')
                );
                echo "SUCCESS";
            }
        }
    }

    public function unlink_nodes()
    {
        if ($this->User->get_level("config/rules/edit")) {

            //$node_in, $node_out
            $this->load->helper('url');
            $this->load->library('form_validation');
            $this->load->model('Rules');

            $this->form_validation->set_rules('node_in', 'node_in', 'required|numeric');
            $this->form_validation->set_rules('node_out', 'node_out', 'required|numeric');

            if ($this->form_validation->run() == false) {
                echo "ERROR";//json_encode($this->Actuator->get_actuators());
            } else {
                $this->Rules->unlinkNodes(
                    $this->input->post('node_in'),
                    $this->input->post('node_out')
                );
                echo "SUCCESS";
            }
        }
    }

    public function delete_node()
    {
        if ($this->User->get_level("config/rules/edit")) {

            //$id
            $this->load->helper('url');
            $this->load->library('form_validation');
            $this->load->model('Rules');

            $this->form_validation->set_rules('id', 'id', 'required|numeric');

            if ($this->form_validation->run() == false) {
                echo "ERROR";//json_encode($this->Actuator->get_actuators());
            } else {
                $this->Rules->deleteNode(
                    $this->input->post('id')
                );
                echo "SUCCESS";
            }
        }
    }
}
