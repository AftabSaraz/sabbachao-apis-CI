<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {
	function __construct()
    {
		parent::__construct();
		$this->load->model('HomeModel');
    }
	public function NearByStores()
	{ 
		$method = $_SERVER['REQUEST_METHOD'];
		if($method != 'POST'){
			json_output(400,array('status' => 400,'message' => 'Method for request should be POST'));
		} else {
		    $params = json_decode(file_get_contents('php://input'), TRUE);
		    $params['latitude']= empty($params['latitude'])?'':$params['latitude'];
			$params['longitude']= empty($params['longitude'])?'':$params['longitude'];
			if(empty($params['latitude']) && empty($params['longitude'])){
				return json_output(400,array('status' => 400,'message' => "latitude & longitude are required."));
			}
			$response = $this->HomeModel->NearByStores($params);
			json_output($response['status'],$response);
		}
	}	
}
