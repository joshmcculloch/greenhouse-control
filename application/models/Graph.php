<?php

class Graph extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    function get_front_page_graphs($greenhouse_id, $peroid="day")
    {
        //Get all of the graphs
        $query_graphs = $this->db->query('SELECT id, title, xaxis_title, yaxis_title FROM graphs WHERE front_page=1 AND greenhouse_id=?', array($greenhouse_id));
        $graphs = array();
        foreach($query_graphs->result_array() as $graph) {
            $graph = array(
                'id' => $graph['id'],
                'title' => $graph['title'],
                'xaxis_title' => $graph['xaxis_title'],
                'yaxis_title' => $graph['yaxis_title'],
                'sensors' => array()
            );

            //Get all of the sensors for this graph
            $query_graph_sensors = $this->db->query('SELECT sensor_id FROM graph_sensors WHERE graph_id = ?', array($graph['id']));
            foreach($query_graph_sensors->result_array() as $graph_sensor) {
                $sensor = array();
                //Get the sensor name
                $query_sensor = $this->db->query('SELECT name FROM sensors WHERE id = ?', array($graph_sensor['sensor_id']));
                $sensor['name'] = $query_sensor->row()->name;

                //Get the sensor data
                if($peroid == "year") {
                    $sensor['data'] = $this->get_sensor_year($graph_sensor['sensor_id']);
                }
                else if($peroid == "week") {
                    $sensor['data'] = $this->get_sensor_week($graph_sensor['sensor_id']);
                } else {
                    $sensor['data'] = $this->get_sensor_24h($graph_sensor['sensor_id']);
                }


                //Add the sensor to the graph
                $graph['sensors'][] = $sensor;

            }
            //Add the graph to the result
            $graphs[] = $graph;
        }
        return $graphs;
    }

    public function get_sensor_24h($sensor_id)
    {
        $sql = "SELECT value, CONVERT_TZ(time,'UTC', 'NZ') as 'time'
FROM sensor_data
WHERE sensor_id=? AND time > DATE_SUB(CONVERT_TZ(NOW(),'SYSTEM', 'UTC'), INTERVAL 1 DAY)
ORDER BY `time` ASC";
        $query = $this->db->query($sql, array($sensor_id));
        return $query->result_array();
    }

    public function get_sensor_week($sensor_id)
    {
        $sql = "SELECT AVG(value) as 'value', CONVERT_TZ(time,'UTC', 'NZ') as 'time'
FROM sensor_data
WHERE sensor_id=? AND time > DATE_SUB(CONVERT_TZ(NOW(),'SYSTEM', 'UTC'), INTERVAL 7 DAY)
GROUP BY DATE( time ), HOUR( time )
ORDER BY `time` ASC";
        $query = $this->db->query($sql, array($sensor_id));
        return $query->result_array();
    }

    public function get_sensor_year($sensor_id)
    {
        $sql = "SELECT AVG(value) as 'value', CONVERT_TZ(time,'UTC', 'NZ') as 'time'
FROM sensor_data
WHERE sensor_id=? AND time > DATE_SUB(CONVERT_TZ(NOW(),'SYSTEM', 'UTC'), INTERVAL 365 DAY)
GROUP BY DATE( time ), DAY( time )
ORDER BY `time` ASC";
        $query = $this->db->query($sql, array($sensor_id));
        return $query->result_array();
    }
}