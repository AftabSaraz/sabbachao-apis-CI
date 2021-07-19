<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller {
    function __construct()
    {
		parent::__construct();
		$this->load->model('HomeModel');
		$this->load->model('ProductModel');
		$this->load->model('UserModel');
    }
	public function getSliderImages()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		if($method != 'GET'){
			json_output(400,array('status' => 400,'message' => 'Method must be GET'));
		} else {
			$params = json_decode(file_get_contents('php://input'), TRUE);
			$response = $this->ProductModel->getSliderImages();
			json_output($response['status'],$response);
		}
	}
	public function offers()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		if($method != 'GET'){
			json_output(400,array('status' => 400,'message' => 'Method must be GET'));
		} else {
			$params = json_decode(file_get_contents('php://input'), TRUE);
			// $params['user_id'] = $this->MyModel->verify_token($params);
			// if (!$params['user_id']) {
			// 	return;
			// }
			
			$response = $this->UserModel->offers();
			json_output($response['status'],$response);
		}
	}
	public function addAddress()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		$params = json_decode(file_get_contents('php://input'), TRUE);
		if($method != 'POST'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else {
			$params['customer_id'] = $this->MyModel->verify_token($params);
			if (!$params['customer_id']) {
				return;
			}
			$params['full_name']= empty($params['full_name'])?'':$params['full_name'];
			$params['contact']= empty($params['contact'])?'':$params['contact'];
			$params['address']= empty($params['address'])?'':$params['address'];
			$params['city']= empty($params['city'])?'':$params['city'];
			$params['province']= empty($params['province'])?'':$params['province'];
			$params['label']= empty($params['label'])?'':$params['label'];
			$params['latitude']= empty($params['latitude'])?'':$params['latitude'];
			$params['longitude']= empty($params['longitude'])?'':$params['longitude'];
			
			if(empty($params['latitude']) || empty($params['longitude']) || empty($params['full_name']) || empty($params['contact']) || empty($params['address']) || empty($params['city']) || empty($params['province']) || empty($params['label'])){
				return json_output(400,array('status' => 400,'message' => "All fields are required"));
			}
			
			$response = $this->UserModel->addCustomerAddress($params);
			json_output($response['status'],$response);
		}
	}
	public function defaultAddress()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		$params = json_decode(file_get_contents('php://input'), TRUE);
		if($method != 'POST'){
			json_output(400,array('status' => 400,'message' => 'Method POST'));
		}else {
			$params['customer_id'] = $this->MyModel->verify_token($params);
			if (!$params['customer_id']) {
				return;
			}
			
			$params['address_id']= empty($params['address_id'])?'':$params['address_id'];
			if(empty($params['address_id'])){
				return json_output(400,array('status' => 400,'message' => "address_id is required"));
			}
			
			$response = $this->UserModel->defaultAddress($params);
			json_output($response['status'],$response);
		}
	}
	public function getDefaultAddress()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		$params = json_decode(file_get_contents('php://input'), TRUE);
		if($method != 'GET'){
			json_output(400,array('status' => 400,'message' => 'Request Method GET'));
		}else {
			$params['customer_id'] = $this->MyModel->verify_token($params);
			if (!$params['customer_id']) {
				return;
			}
// 			$params['address_id']= empty($params['address_id'])?'':$params['address_id'];
// 			if(empty($params['address_id'])){
// 				return json_output(400,array('status' => 400,'message' => "address_id is required"));
// 			}
			$response = $this->UserModel->getDefaultAddress($params);
			json_output($response['status'],$response);
		}
	}
	public function customerAddress()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		$action  = $this->input->get('action', TRUE);
		$params = json_decode(file_get_contents('php://input'), TRUE);
		if($action && $action=='get'){
			if($method != 'GET'){
				json_output(400,array('status' => 400,'message' => 'Method for request should be GET'));
			} else {
				$params['user_id'] = $this->MyModel->verify_token($params);
				if (!$params['user_id']) {
					return;
				}
				
				$response = $this->UserModel->getCustomerAddresses($params);
				json_output($response['status'],$response);
			}
		}elseif($action && $action=='update'){
			if($method != 'PUT'){
				json_output(400,array('status' => 400,'message' => 'Method for request should be PUT'));
			} else {
				$params['customer_id'] = $this->MyModel->verify_token($params);
				if (!$params['customer_id']) {
					return;
				}
				
				//return json_output(400,array('status' => 400,'message' => $params));
				$response = $this->UserModel->updateCustomerAddressLabel($params);
				json_output($response['status'],$response);
			}
		}elseif($action && $action=='delete'){
		    if($method != 'DELETE'){
				json_output(400,array('status' => 400,'message' => 'Method for request should be DELETE'));
			} else {
				$params['customer_id'] = $this->MyModel->verify_token($params);
				if (!$params['customer_id']) {
					return;
				}
				$params['address_id']= empty($params['address_id'])?'':$params['address_id'];
				if(empty($params['address_id'])){
				    return json_output(400,array('status' => 400,'message' => 'address_id is required'));
				}
				
				//return json_output(400,array('status' => 400,'message' => $params));
				$response = $this->UserModel->deleteCustomerAddress($params);
				json_output($response['status'],$response);
			}
		}elseif($action!="update" && $action!="get"){
			return json_output(400,array('status' => 400,'message' => 'Invalid action.'));
		}elseif($action==""){
			return json_output(400,array('status' => 400,'message' => 'Action is required'));
		}
	}
	public function products()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		//return json_output(400,array('status' => 400,'message' => $method));
		$action  = $this->input->get('action', TRUE);
		$params = json_decode(file_get_contents('php://input'), TRUE);
		if($action && $action=='get'){
			if($method != 'POST'){
				json_output(400,array('status' => 400,'message' => 'Method for request should be POST'));
			} else {
				$params['search_products']= empty($params['search_products'])?'':$params['search_products'];
    			if(empty($params['search_products'])){
    				return json_output(400,array('status' => 400,'message' => "search_products is required."));
    			}
				$response = $this->ProductModel->getAllProducts($params);
				json_output($response['status'],$response);
			}
		}else if($action && $action=='getCart'){
			if($method != 'GET'){
				json_output(400,array('status' => 400,'message' => 'Method for request should be GET'));
			} else {
				$params['user_id'] = $this->MyModel->verify_token($params);
				if (!$params['user_id']) {
					return;
				}
				$response = $this->ProductModel->getCart($params);
				json_output($response['status'],$response);
			}
		}else if($action && $action=='update'){
			if($method != 'PUT'){
				json_output(400,array('status' => 400,'message' => 'Method for request should be PUT'));
			} else {
				$params['user_id'] = $this->MyModel->verify_token($params);
				if (!$params['user_id']) {
					return;
				}
				
				//return json_output(400,array('status' => 400,'message' => $params));
				$response = $this->UserModel->updateCustomerAddressLabel($params);
				json_output($response['status'],$response);
			}
		}else if($action && $action=='deleteCartItem'){
			if($method != "DELETE"){
				json_output(400,array('status' => 400,'message' => 'Method for request should be DELETE'));
			} else {
				$params['user_id'] = $this->MyModel->verify_token($params);
				if (!$params['user_id']) {
					return;
				}
				$params['product_id']= empty($params['product_id'])?'':$params['product_id'];
				if(empty($params['product_id'])){
					return json_output(400,array('status' => 400,'message' => "product_id is required"));
				}
				$response = $this->ProductModel->deleteCartItem($params);
				json_output($response['status'],$response);
			}
		}else if($action==""){
			if($method != 'POST'){
				json_output(400,array('status' => 400,'message' => 'Method for request should be POST'));
			} else {
				$params['user_id'] = $this->MyModel->verify_token($params);
				if (!$params['user_id']) {
					return;
				}
				$response = $this->ProductModel->addToCart($params);
				json_output($response['status'],$response);
			}
		}
	}
	public function storePrice()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		if($method != 'POST'){
			json_output(400,array('status' => 400,'message' => 'Method must be POST'));
		} else {
			$params = json_decode(file_get_contents('php://input'), TRUE);
			$params['user_id'] = $this->MyModel->verify_token($params);
			if (!$params['user_id']) {
				return;
			}
		    $params['latitude']= empty($params['latitude'])?'':$params['latitude'];
			$params['longitude']= empty($params['longitude'])?'':$params['longitude'];
			if(empty($params['latitude']) && empty($params['longitude'])){
				return json_output(400,array('status' => 400,'message' => "latitude & longitude are required."));
			}
			$getCart = $this->ProductModel->getCart($params);
			if(isset($getCart['cart_data'])){
			    $NearByStores = $this->HomeModel->NearByStores($params);
    		    $store_ids = array_column($NearByStores['data'], 'id');
                $response = $this->HomeModel->NearByStoresProductPrice($store_ids,$getCart['cart_data']);
    			json_output($response['status'],$response);
			}else{
			    return json_output(400,array('status' => 400,'message'=>'Cart is Empty'));
			}
		}
	}
	public function placeOrder()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		if($method != 'POST'){
			json_output(400,array('status' => 400,'message' => 'Method must be POST'));
		} else {
			$params = json_decode(file_get_contents('php://input'), TRUE);
			$params['user_id'] = $this->MyModel->verify_token($params);
			if (!$params['user_id']) {
				return;
			}
			$params['address_id']= empty($params['address_id'])?'':$params['address_id'];
			$params['store_id']= empty($params['store_id'])?'':$params['store_id'];
			$params['pickUp_or_homeDelivery']= empty($params['pickUp_or_homeDelivery'])?'':$params['pickUp_or_homeDelivery'];
			$params['latitude']= empty($params['latitude'])?'':$params['latitude'];
			$params['longitude']= empty($params['longitude'])?'':$params['longitude'];
			if(empty($params['store_id']) && empty($params['pickUp_or_homeDelivery']) && empty($params['address_id']) && empty($params['latitude']) && empty($params['longitude'])){
				return json_output(400,array('status' => 400,'message' => "address_id & latitude & longitude & store_id & pickUp_or_homeDelivery are required."));
			}
			$getCart = $this->ProductModel->getCart($params);
			if(isset($getCart['cart_data'])){
    		    $product_ids = array_column($getCart['cart_data'], 'id');
    		    $cart_id =$getCart['cart_data'][0]['cart_id'];
    		    $store_id = $params['store_id'];
    		    $response = $this->UserModel->placeOrder($cart_id,$store_id,$product_ids,$params);
    			json_output($response['status'],$response);
			}else{
			    return json_output(400,array('status' => 400,'message'=>'Cart is Empty'));
			}
		}
	}
	public function orderHistory()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		if($method != 'POST'){
			json_output(400,array('status' => 400,'message' => 'Method must be POST'));
		} else {
			$params = json_decode(file_get_contents('php://input'), TRUE);
			$params['user_id'] = $this->MyModel->verify_token($params);
			if (!$params['user_id']) {
				return;
			}
			$response = $this->UserModel->orderHistory($params);
    		json_output($response['status'],$response);
		}
	}
	public function updateOrderHistoryTitle()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		if($method != 'POST'){
			json_output(400,array('status' => 400,'message' => 'Method must be POST'));
		} else {
			$params = json_decode(file_get_contents('php://input'), TRUE);
			$params['user_id'] = $this->MyModel->verify_token($params);
			if (!$params['user_id']) {
				return;
			}
			$params['title']= empty($params['title'])?'':$params['title'];
			$params['order_id']= empty($params['order_id'])?'':$params['order_id'];
			if(empty($params['order_id']) && empty($params['order_id'])){
			    return json_output(400,array('status' => 400,'message' => "order_id & title are required"));
			}
			$response = $this->UserModel->updateOrderHistoryTitle($params);
    		json_output($response['status'],$response);
		}
	}
	public function orderDetail()
	{	
		$method = $_SERVER['REQUEST_METHOD'];
		if($method != 'POST'){
			json_output(400,array('status' => 400,'message' => 'Method must be POST'));
		} else {
			$params = json_decode(file_get_contents('php://input'), TRUE);
			$params['user_id'] = $this->MyModel->verify_token($params);
			if (!$params['user_id']) {
				return;
			}
			$params['order_id']= empty($params['order_id'])?'':$params['order_id'];
			if(empty($params['order_id'])){
			    return json_output(400,array('status' => 400,'message' => "order_id is required"));
			}
			$response = $this->UserModel->orderDetail($params);
    		json_output($response['status'],$response);
		}
	}
}
