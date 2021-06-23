<?php
    defined('BASEPATH') OR exit('No direct script access allowed');

    class MyModel extends CI_Model {

        var $client_service = "frontend-client";
        var $auth_key       = "simplerestapi";

        public function check_auth_client(){
            $client_service = $this->input->get_request_header('Client-Service', TRUE);
            $auth_key  = $this->input->get_request_header('Auth-Key', TRUE);
            if($client_service == $this->client_service && $auth_key == $this->auth_key){
                return true;
            } else {
                return json_output(401,array('status' => 401,'message' => 'Unauthorized.'));
            }
        }


        public function verify_token($params)
        {

            
            extract($_REQUEST);
            $token   = '';
            $token   = $this->input->get_request_header('token', TRUE);
           
            !$token  && $token  = $this->input->get('token', TRUE);
            !$token  && $token  = $this->input->post('token', TRUE);
          
            if($action == "changepassword")
            {
                $q = $this->db->select('*')->from('sab_rider_sessions')->where(['session_token'=>$token,'status_type'=>'1'])->get()->result_array();

                if($q)
                {   
                    return $q[0]['rider_id'];
                }
            }
            else if($action == "refrence_number")
            {
                $q = $this->db->select('*')->from('sab_rider_sessions')->where(['session_token'=>$token,'status_type'=>'1'])->get()->result_array();

                if($q)
                {   
                    return $q[0]['rider_id'];
                }
            }
            else if($action == "data")
            {
                $q = $this->db->select('*')->from('sab_rider_sessions')->where(['session_token'=>$token,'status_type'=>'1'])->get()->result_array();

                if($q)
                {   
                    return $q[0]['rider_id'];
                }
            }
            else if($action == "add_profile")
            {
                 $q = $this->db->select('*')->from('sab_rider_sessions')->where(['session_token'=>$token,'status_type'=>'1'])->get()->result_array();

                if($q)
                {   
                    return $q[0]['rider_id'];
                }
            }
            json_output(401,array('status' => 401,'message' => 'Unauthorized.'));
            return false;
        }

        public function login($contact,$password, $bypass=false)
        {
            $q  = $this->db->select('password,id,mobile_number AS contact,password_token')->from('sab_riders')->where('mobile_number',$contact)->get()->row();
            if($q == ""){
                return array('status' => 400,'message' => 'Invalid contact or Password.');
            } else {
                $id              = $q->id;
                if ($bypass || ($q->password == crypt($password,$q->password_token) && $q->contact == $contact)) {
                   $last_login = date('Y-m-d H:i:s');
                        $randomIdLength=60;
                        $token = '';
                        do {
                            $bytes = random_bytes($randomIdLength);
                            $token .= str_replace(
                                ['.','/','='], 
                                '',
                                base64_encode($bytes)
                            );
                        } while (strlen($token) < $randomIdLength);
                        //return $token;
                   //$token = crypt(substr( md5(rand()), 0, 7));
                   $expired_at = date("Y-m-d H:i:s", strtotime('+12 hours'));
                   $this->db->trans_start();
                   $this->db->where('id',$id)->update('sab_riders',array('last_login' => $last_login));
                   $this->db->where('rider_id',$id)->update('sab_rider_sessions',array('status_type' => 0));
                   $this->db->insert('sab_rider_sessions',array('rider_id' => $id,'session_token' => $token,'date_logout' => $expired_at));
                   if ($this->db->trans_status() === FALSE){
                      $this->db->trans_rollback();
                      return array('status' => 500,'message' => 'Internal server error.');
                   } else {
                      $this->db->trans_commit();
                      $data = $this->db->select('id,username,email,mobile_number AS contact,dob,gender,rider_type_id,reference_number')->from('sab_riders')->where('id',$id)->order_by('id','desc')->get()->result();
                      if($bypass==true){
                          $msg= "SignUp Successfully";
                      }else{
                          $msg= "Login Successfully";
                      }
                      return array('status' => 200,'message' =>$msg ,'data' => $data, 'token' => stripslashes($token));
                   }
                } else {
                   return array('status' => 400,'message' => 'Invalid contact or Password.');
                }
            }
        }

        public function getRiderByRef($code)
        {
                return $this->db->select('*')->from('sab_riders')->where('reference_number',$code)->get()->row();

        }

        public function signup($data)
        { 
                $c = $this->db->select('mobile_number')->from('sab_riders')->where('mobile_number',$data['contact'])->get()->row();
                if($c && $c->mobile_number==$data['contact']){
                    return array('status' => 401,'message' => 'Contact already exists');
                }else{
                    $data['mobile_number'] = $data['contact'];
                    unset($data['contact']);
                    $this->db->insert('sab_riders',$data);
                    $insert_id = $this->db->insert_id();
                    $data = $this->db->select('id,username,email,mobile_number AS contact,dob,gender,rider_type_id,reference_number')->from('sab_riders')->where('id',$insert_id)->order_by('id','desc')->get()->result();
                    return array('status' => 201,'message' => 'SignUp Successfully','data'=>$data);
                } 
        }

        public function verify($data)
        {
            $c = $this->db->select('mobile_number AS contact')->from('sab_riders')->where('mobile_number',$data['contact'])->get()->row();
            if($c && $c->contact==$data['contact']){
                return array('status' => 401,'message' => 'contact already exists');
            }else{
                return array('status' => 201,'message' => "contact does't exists");
            } 
        }

    	public function forgotpassword($data)
        {
            $c = $this->db->select('mobile_number AS contact,id')->from('sab_riders')->where('mobile_number',$data['contact'])->get()->row();
            if($c && $c->contact==$data['contact']){
    			$r = $this->db->where('id',$c->id)->update('sab_riders',['password'=>$data['password'],'password_token'=>$data['password_token']]);
    			if($r){
    				return array('status' => 200,'message' => 'Password has been updated.');
    			}else{
    				return array('status' => 200,'message' => 'Password not updated.');
    			}
            }else{
                return array('status' => 201,'message' => "contact does't exists");
            } 
        }

        public function changepassword($data)
        {
            
            $user = $this->db->select('*')->from('sab_riders')->where('id',$data['user_id'])->get()->row();
            if($user && ($user->password == crypt($data['old_password'],$user->password_token))){
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
                $data['password_token'] = $token;
                $data['password'] = crypt($data['new_password'],$token);
                $this->db->set(['password'=>$data['password'],'password_token'=>$data['password_token']]);
                $this->db->where('id',$data['user_id']);
                $result = $this->db->update('sab_riders'); 
                if($result){
                    return array('status' => 200,'message' => "Password has been updated");
                }else{
                    return array('status' => 400,'message' => "Password not Updated");
                }
            }else{
                return array('status' => 400,'message' => "Invalid old password");
            } 
        }

        public function get_reference_number($data)
        {
       
            $user = $this->db->select('*')->from('sab_riders')->where('id',$data['user_id'])->get()->row();
            if($user)
            {
                $q = $this->db->select('*')->from('sab_rider_sessions')->where('id',$data['user_id'])->get()->row();
                $reference_number = $user->reference_number;
                $token = $q->session_token;
                 return array('status' => 200,'message' => "OK", 'reference_number' => "$reference_number");
                
            }
        }

        public function updateUser($data)
        {   
            $validKeys = ['username','gender','email','dob','user_id'];
            if (count($data)==1) { return array('status' => 400,'message' => "Data required"); } 
            foreach($data as $column=>$value){
                if (!in_array($column, $validKeys)) {
                    return array('status' => 400,'message' => "Invalid attribute '$column'");
                } else if(trim($value) === '') {
                    return array('status' => 400,'message' => "$column can not be empty");
                }
            }
            $user_id = $data['user_id'];
            unset($data['user_id']);
            $this->db->set($data);
            $this->db->where('id', $user_id);
            $result = $this->db->update('sab_riders'); 
            if($result){
                return array('status' => 200,'message' => "Data has been updated");
            }else{
                return array('status' => 400,'message' => "Data not Updated");
            }
        }

        public function logout()
        {
            $users_id  = $this->input->get_request_header('User-ID', TRUE);
            $token     = $this->input->get_request_header('Authorization', TRUE);
            $this->db->where('users_id',$users_id)->where('token',$token)->delete('users_authentication');
            return array('status' => 200,'message' => 'Successfully logout.');
        }

        public function auth()
        {
            
            $users_id  = $this->input->get_request_header('User-ID', TRUE);
            $token     = $this->input->get_request_header('Authorization', TRUE);
            $q  = $this->db->select('expired_at')->from('users_authentication')->where('users_id',$users_id)->where('token',$token)->get()->row();
            if($q == ""){
                return json_output(401,array('status' => 401,'message' => 'Unauthorized.'));
            } else {
                if($q->expired_at < date('Y-m-d H:i:s')){
                    return json_output(401,array('status' => 401,'message' => 'Your session has been expired.'));
                } else {
                    $updated_at = date('Y-m-d H:i:s');
                    $expired_at = date("Y-m-d H:i:s", strtotime('+12 hours'));
                    $this->db->where('users_id',$users_id)->where('token',$token)->update('users_authentication',array('expired_at' => $expired_at,'updated_at' => $updated_at));
                    return array('status' => 200,'message' => 'Authorized.');
                }
            }
        }
     
        public function add_rider($params)
        {
            $api_key   = $this->input->get_request_header('api_key', TRUE);
           
            !$api_key  && $api_key  = $this->input->get('api_key', TRUE);
            !$api_key  && $api_key  = $this->input->post('api_key', TRUE);
            if($api_key=="zhjeDX8SH3ryV0RGk8f3ZWRigkkm3qxBw")
            {
                $data = $this->db->select('id AS "Rider ID",username AS "Rider Name",mobile_number AS "Rider Number",rider_image')->from('sab_riders')->where('reference_number',$params['reference_number'])->get()->result();
                    return array('status' => 201,'message' => 'Added Successfully','data'=>$data);
            }
            else
            { 
                return array('status' => 201,'message' => 'Something Went Wrong Please Try Again Later');
            }

        }

        public function send_rider_request($params)
        {
            $api_key   = $this->input->get_request_header('api_key', TRUE);
           
            !$api_key  && $api_key  = $this->input->get('api_key', TRUE);
            !$api_key  && $api_key  = $this->input->post('api_key', TRUE);
            if($api_key=="zhjeDX8SH3ryV0RGk8f3ZWRigkkm3qxBw")
            {
                $q = $this->db->insert('sab_seller_rider',$params);
                if($q)
                {
                    return array('status' => 201,'message' => 'Request Send Successfully');
                }
                else
                {
                    return array('status' => 201,'message' => 'Fail to send request! Try agian Later');
                }
            }
            else
            {
                 return array('status' => 201,'message' => 'Something Went Wrong!');
            }
        }

        public function rider_information($params)
        {

            $api_key   = $this->input->get_request_header('api_key', TRUE);
           
            !$api_key  && $api_key  = $this->input->get('api_key', TRUE);
            !$api_key  && $api_key  = $this->input->post('api_key', TRUE);
            if($api_key=="zhjeDX8SH3ryV0RGk8f3ZWRigkkm3qxBw")
            {
                $data = $this->db->select('username AS "Rider Name",mobile_number AS "Rider Number",rider_image, created_at AS "Rider Adding Date and Time"')->from('sab_riders')->where('id',$params['rider_id'])->get()->result();
                    return array('status' => 201,'message' => 'Rider Infomration','data'=>$data);
            }
            else
            {
                return array('status' => 201,'message' => 'Something Went Wrong Please Try Again Later');
            }
        }

        public function cancel_rider_request($params)
        {
            $api_key   = $this->input->get_request_header('api_key', TRUE);
           
            !$api_key  && $api_key  = $this->input->get('api_key', TRUE);
            !$api_key  && $api_key  = $this->input->post('api_key', TRUE);
            if($api_key=="zhjeDX8SH3ryV0RGk8f3ZWRigkkm3qxBw")
            {
                $q = $this->db->where(['rider_id' => $params['rider_id'], 'store_id' => $params['store_id']])->update('sab_seller_rider',array('status' => 6));
                if($q)
                {
                    return array('status' => 201,'message' => 'Request Cancel Successfully');
                }
                else
                {
                    return array('status' => 201,'message' => 'Request Fail ! Try agian Later');
                }
            }
            else
            {
                 return array('status' => 201,'message' => 'Something Went Wrong!');
            }
        }

        public function delete_rider_request($params)
        {
            $api_key   = $this->input->get_request_header('api_key', TRUE);
           
            !$api_key  && $api_key  = $this->input->get('api_key', TRUE);
            !$api_key  && $api_key  = $this->input->post('api_key', TRUE);
        
            if($api_key=="zhjeDX8SH3ryV0RGk8f3ZWRigkkm3qxBw")
            {
                $q = $this->db->where(['rider_id' => $params['rider_id'], 'store_id' => $params['store_id']])->update('sab_seller_rider',array('status' => 5));
                if($q)
                {
                    return array('status' => 201,'message' => 'Request Deleted Successfully');
                }
                else
                {
                    return array('status' => 201,'message' => 'Request Fail ! Try agian Later');
                }
            }
            else
            {
                 return array('status' => 201,'message' => 'Something Went Wrong!');
            }
        }
        public function rider_list($params)
        {
            $api_key   = $this->input->get_request_header('api_key', TRUE);
           
            !$api_key  && $api_key  = $this->input->get('api_key', TRUE);
            !$api_key  && $api_key  = $this->input->post('api_key', TRUE);
            if($api_key=="zhjeDX8SH3ryV0RGk8f3ZWRigkkm3qxBw")
            {
                $data = $this->db->select(
                    'sab_riders.id AS Rider ID,
                     sab_riders.username AS Rider Name,
                     sab_riders.mobile_number AS Rider Number,
                     sab_seller_rider.status AS Status
                    '
                )
                ->from('sab_seller_rider')
                ->where('store_id',$params['store_id'])
                ->join('sab_riders', 'sab_riders.id = sab_seller_rider.rider_id')
                ->get()->result();
                return array('status' => 201,'message' => 'Rider Infomration','data'=>$data);
            }
            else
            {
                return array('status' => 201,'message' => 'Something Went Wrong Please Try Again Later');
            }
        
        }
        public function send_image($data, $params)
        {
          
            $user_id = $params['user_id'];

            $user = $this->db->select('*')->from('sab_riders')->where('id',$user_id)->get()->row();
         
           
            $this->db->set(['rider_image'=>$data['upload_data']['file_name']]);
            $this->db->where('id',$user_id);
            $result = $this->db->update('sab_riders'); 
            if($result){
                return array('status' => 200,'message' => "Photo has been updated", 'rider_image' => $data['upload_data']['file_name']);
            }else{
                return array('status' => 400,'message' => "Photo not Updated");
            }
        }
    }
?>