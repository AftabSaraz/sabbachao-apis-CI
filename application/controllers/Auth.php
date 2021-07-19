<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

	public function login()
	{
	       
		$method = $_SERVER['REQUEST_METHOD'];
		if($method != 'POST'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		} else {
			//$check_auth_client = $this->MyModel->check_auth_client();
			$params = json_decode(file_get_contents('php://input'), TRUE);
			if ($params['contact'] != "" && $params['password'] != "") {
		        	$contact = $params['contact'];
		        	$password = $params['password'];
		            	
		        	$response = $this->MyModel->login($contact,$password);
		        	    
			  	json_output($response['status'],$response);
			} else {
			    if($params['contact']=="" && $params['password']==""){
			        $msg= "Contact & Password are required";
			    }elseif($params['password']==""){
			        $msg= "Password is required.";
			    }elseif($params['contact']==""){
			        $msg="Contact is required.";
			    }
			    json_output(400,array('status' => 400,'message' => $msg));
			}
		}
	}
    public function signup()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		if($method != 'POST'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		} else {
			$params = json_decode(file_get_contents('php://input'), TRUE);
				if ($params['username'] == "" || $params['contact'] == "" || $params['password'] == "") {
						$respStatus = 400;
						$resp = array('status' => 400,'message' =>  'All Fields are required');
					} else {
					        $respStatus = 201;
		        			$resp = $this->MyModel->signup($params);
		        	        $resp['status'] === 201 && $resp =  $this->MyModel->login($params['contact'],$params['password'], true);
					}
			json_output($respStatus,$resp); 
		}
	}
	public function logout()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		if($method != 'POST'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		} else {
			$check_auth_client = $this->MyModel->check_auth_client();
			if($check_auth_client == true){
		        	$response = $this->MyModel->logout();
				json_output($response['status'],$response);
			}
		}
	}
	
}
