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
			}else{
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
			if(isset($params['username']) && isset($params['contact']) && isset($params['password']) ){
					if ($params['username'] == "" || $params['contact'] == "" || $params['password'] == "") {
							$respStatus = 400;
							$resp = array('status' => 400,'message' =>  'All Fields are required');
					} else {
						$respStatus = 201;
						$randomIdLength=10;
						$token = '';
						do {
							$bytes = random_bytes($randomIdLength);
							$token .= str_replace(
								['.','/','='], 
								'',
								base64_encode($bytes)
							);
						} while (strlen($token) < $randomIdLength);
						$params['password_token'] = $token;
						$params['password'] = crypt($params['password'],$token);
						$resp = $this->MyModel->signup($params);
						$resp['status'] === 201 && $resp =  $this->MyModel->login($params['contact'],$params['password'], true);
					}
			}else{
				$respStatus = 400;
				$resp = array('status' => 400,'message' =>  'All Fields are required');
			}
			json_output($respStatus,$resp); 
		}
	}
	public function verify()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		if($method != 'POST'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		} else {
			$params = json_decode(file_get_contents('php://input'), TRUE);
				if ($params['contact'] == "") {
						$resp = array('status' => 400,'message' =>  'contact is required');
					} else {
		        		$resp = $this->MyModel->verify($params);
					}
			json_output($resp['status'],$resp); 
		}
	}
	public function forgotpassword()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		if($method != 'POST'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		} else {
			
			$params = json_decode(file_get_contents('php://input'), TRUE);
				$params['contact'] = empty($params['contact'])?'':$params['contact'];
				$params['password']= empty($params['password'])?'':$params['password'];
				$params['confirm_password']= empty($params['confirm_password'])?'':$params['confirm_password'];
				if ($params['contact'] != "" && $params['password'] != "" && $params['confirm_password']!="") {
					if($params['password'] == $params['confirm_password']){
						$randomIdLength=10;
						$token = '';
						do {
							$bytes = random_bytes($randomIdLength);
							$token .= str_replace(
								['.','/','='], 
								'',
								base64_encode($bytes)
							);
						} while (strlen($token) < $randomIdLength);
						$params['password_token'] = $token;
						$params['password'] = crypt($params['password'],$token);
						$response = $this->MyModel->forgotpassword($params);
						json_output($response['status'],$response);
					}else{
						json_output(400,array('status' => 400,'message' => "Confirm Password not Matched"));
					}
				}else{
					if($params['contact']=="" && $params['password']=="" && $params['confirm_password']==""){
						$msg= "Contact & Password & Confirm Password fields are required.";
					}elseif($params['contact']==""){
						$msg= "Contact is required.";
					}elseif($params['password']==""){
						$msg="Password is required.";
					}elseif($params['confirm_password']==""){
						$msg="Confirm Password is required.";
					}
					json_output(400,array('status' => 400,'message' => $msg));
				}
		}
	}
	public function update()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		if($method != 'POST'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		} else {
			$action  = $this->input->get('action', TRUE);
			$params = json_decode(file_get_contents('php://input'), TRUE);
			if($action && $action=='data'){
				$params['user_id'] = $this->MyModel->verify_token($params);
				if (!$params['user_id']) {
					return;
				}
				$response = $this->MyModel->updateUser($params);
				json_output($response['status'],$response);
			}elseif($action && $action=='changepassword'){
				$params['user_id'] = $this->MyModel->verify_token($params);
				if (!$params['user_id']) {
					return;
				}
				$params['new_password']= empty($params['new_password'])?'':$params['new_password'];
				$params['confirm_password']= empty($params['confirm_password'])?'':$params['confirm_password'];
				if(empty($params['new_password']) || empty($params['confirm_password'])){
					return json_output(400,array('status' => 400,'message' => "Provide New Password and Confirm Password"));
				}
				if($params['new_password'] != $params['confirm_password']){
					return json_output(400,array('status' => 400,'message' => "Confirm Password not Matched"));
				}
				$params['old_password']= empty($params['old_password'])?'':$params['old_password'];
				if(empty($params['old_password'])){
					return json_output(400,array('status' => 400,'message' => "Provide old password"));
				}
				$response = $this->MyModel->changepassword($params);
				json_output($response['status'],$response);
			}elseif($action==""){
				return json_output(400,array('status' => 400,'message' => 'Action is required'));
			}
			
			//$response = $this->MyModel->updateUser($params);
			//json_output($response['status'],$response);
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
