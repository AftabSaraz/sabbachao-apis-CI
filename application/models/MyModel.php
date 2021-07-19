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

    public function login($contact,$password, $bypass=false)
    {
        $q  = $this->db->select('password,id,contact')->from('users')->where('contact',$contact)->get()->row();
        if($q == ""){
            return array('status' => 400,'message' => 'Invalid contact or Password.');
        } else {
            $hashed_password = $q->password;
            $d_contact = $q->contact;
            //$encrypt_password = $this->encrypt->encode($password);
            $id              = $q->id;
            if ($bypass || ($hashed_password ==$password && $d_contact == $contact)) {
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
               $this->db->where('id',$id)->update('users',array('last_login' => $last_login));
               $this->db->insert('users_authentication',array('users_id' => $id,'token' => $token,'expired_at' => $expired_at));
               if ($this->db->trans_status() === FALSE){
                  $this->db->trans_rollback();
                  return array('status' => 500,'message' => 'Internal server error.');
               } else {
                  $this->db->trans_commit();
                  $data = $this->db->select('id,username,email,contact,dob,gender')->from('users')->where('id',$id)->order_by('id','desc')->get()->result();
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
    public function signup($data)
    {       //$data['password'] = $this->encrypt->encode($data['password']);
            $c = $this->db->select('contact')->from('users')->where('contact',$data['contact'])->get()->row();
            if($c && $c->contact==$data['contact']){
                return array('status' => 401,'message' => 'Contact already exists');
            }else{
                    $this->db->insert('users',$data);
                    $insert_id = $this->db->insert_id();
                    $data = $this->db->select('id,username,email,contact,dob,gender')->from('users')->where('id',$insert_id)->order_by('id','desc')->get()->result();
                    return array('status' => 201,'message' => 'SignUp Successfully','data'=>$data);
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

    public function book_all_data()
    {
        return $this->db->select('id,title,author')->from('books')->order_by('id','desc')->get()->result();
    }

    public function book_detail_data($id)
    {
        return $this->db->select('id,title,author')->from('books')->where('id',$id)->order_by('id','desc')->get()->row();
    }
    
    public function book_update_data($id,$data)
    {
        $this->db->where('id',$id)->update('books',$data);
        return array('status' => 200,'message' => 'Data has been updated.');
    }

    public function book_delete_data($id)
    {
        $this->db->where('id',$id)->delete('books');
        return array('status' => 200,'message' => 'Data has been deleted.');
    }

}
