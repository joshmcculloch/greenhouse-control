<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Greenhouse extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->helper(array('form', 'url'));
    }

    public function index()
    {
        $this->load->view('test_upload', array('error' => ' ' ));
    }

    function do_upload($camera_id)
    {
        $config['upload_path'] = './images/';
        $config['allowed_types'] = 'jpg';
        $config['max_size']	= '256';
        $config['max_width']  = '1024';
        $config['max_height']  = '768';
        $config['file_name']  = 'camera_'.$camera_id.".jpg";

        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload())
        {
            $error = array('error' => $this->upload->display_errors());

            echo "0";
        }
        else
        {
            $data = array('upload_data' => $this->upload->data());

            echo "1";
        }
    }

}
