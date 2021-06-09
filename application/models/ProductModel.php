<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ProductModel extends CI_Model {
    public function getSliderImages()
    { 
        $getSliderImages = $this->db->select('*')->from('sab_slider_images')->get()->result_array();
        if($getSliderImages){
            return array('status' => 200,'message' => 'Advertisement Slider Images','Total Slider Images'=>count($getSliderImages),'sliderImages'=>$getSliderImages);
        }else{
            return array('status' => 400,'message' => 'something went wrong');
        }
    }
    public function getAllProducts($data)
    { 
        $search = $data['search_products'];
        $this->db->select('*');
        $this->db->from('sab_inventory_products');
        $this->db->like('LOWER(sab_inventory_products.title)', strtolower($search));
        $all_products = $this->db->get();
        if($all_products){
            $all_products = $all_products->result_array();
            return array('status' => 200,'message' => 'Products','Total Products'=>count($all_products),'products'=>$all_products);
        }else{
            return array('status' => 400,'message' => 'Products Not Found');
        }
    }
    public function getCart($data)
    {   if($data['user_id']){
            $cart_id = $this->db->select('*')->from('sab_carts')->where(['customer_id'=>$data['user_id'],'status'=>1])->get()->result_array();
            if($cart_id){
                $this->db->select('*');
                $this->db->from('sab_inventory_products');
                $this->db->join('sab_cart_items', 'sab_inventory_products.id = sab_cart_items.product_id');
                $this->db->where(['sab_cart_items.cart_id'=>$cart_id[0]['cart_id'],'sab_cart_items.customer_id'=>$data['user_id'],'sab_cart_items.status'=>1]);
                $cart_items = $this->db->get()->result_array();
                //$cart_items= $this->db->select('*')->from('cart_items')->where(['cart_id'=>$cart_id->cart_id,'user_id'=>$data['user_id'],'status'=>1])->get()->result_array();
                if($cart_items){
                    return array('status' => 200,'message' => 'Cart','Total Cart Items : '=>count($cart_items),'cart_data'=>$cart_items);
                }else{
                    return array('status' => 400,'message' => 'Cart is Empty');
                }
            }else{
                return array('status' => 400,'message' => 'Cart is not Created Still');
            }
        }else{
            return array('status' => 400,'message' => 'something went wrong');
        }
    }
    public function deleteCartItem($data)
    {   if(count($data)==2 && $data['user_id'] && $data['product_id']){
            $cart_id = $this->db->select('*')->from('sab_carts')->where(['customer_id'=>$data['user_id'],'status'=>1])->get()->result_array();
            if($cart_id && count($cart_id)){
                $cart_item_deleted = $this->db->where(['cart_id'=>$cart_id[0]['cart_id'],'customer_id'=>$data['user_id'],'product_id'=>$data['product_id'],'status'=>1])->update('sab_cart_items',['status'=>0]);
                if($cart_item_deleted){
                    return array('status' => 200,'message' => 'Cart item is Deleted');
                }else{
                    return array('status' => 400,'message' => 'Cart Item is not Deleted');
                }
            }else{
                return array('status' => 400,'message' => 'Cart is not Created Still');
            }
        }else{
            return array('status' => 400,'message' => 'something went wrong');
        }
    }
    public function addToCart($data)
    {   
        $required = ['product_id','quantity','total_price','address_id'];
        if (count($data)==1) { return array('status' => 400,'required fields' =>$required); } 
        foreach($required as $column){
            if (!array_key_exists($column, $data)) {
                return array('status' => 400,'message' => "$column field is required");
            } else if(trim($data[$column]) === '') {
                return array('status' => 400,'message' => "$column can not be empty");
            }
        }
        $p_id = $this->db->select('*')->from('sab_inventory_products')->where(['id'=>$data['product_id']])->get()->result_array();
        if($data['user_id'] && count($p_id)){
            $cart_id = $this->db->select('*')->from('sab_carts')->where(['customer_id'=>$data['user_id'],'status'=>1])->get()->result_array();
            //return array('status' => 200,'message' => $cart_id[0]['cart_id']);
            if($cart_id && count($cart_id)){
                if($cart_id[0]['address_id'] != $data['address_id']){
                    $this->db->where(['customer_id'=>$data['user_id'],'status'=>1])->update('sab_carts',['address_id'=>$data['address_id']]);
                }
                $cart_item_result_id = $this->db->select('*')->from('sab_cart_items')->where(['cart_id'=>$cart_id[0]['cart_id'],'customer_id'=>$data['user_id'],'product_id'=>$data['product_id'],'status'=>1])->get()->result_array();
                if($cart_item_result_id){
                    //return array('status' => 200,'message' => $cart_item_result_id->cart_item_id);
                    $cart_item_result = $this->db->where('cart_item_id',$cart_item_result_id[0]['cart_item_id'])->update('sab_cart_items',['quantity'=>$data['quantity'],'total_price'=>$data['total_price']]);
                    if($cart_item_result){
                        return array('status' => 200,'message' => "Item updated successfully");
                    }else{
                        return array('status' => 400,'message' => "Item not updated please try again");
                    }
                }else{
                    $cart_item_result  = $this->db->insert('sab_cart_items',['cart_id'=>$cart_id[0]['cart_id'],'customer_id'=>$cart_id[0]['customer_id'],'product_id'=>$data['product_id'],'quantity'=>$data['quantity'],'total_price'=>$data['total_price']]);
                    if($cart_item_result){
                        return array('status' => 200,'message' => "Item in cart added successfully");
                    }else{
                        return array('status' => 400,'message' => "Item not added please try again");
                    }
                }
            }else{
                $cart_result  = $this->db->insert('sab_carts',['customer_id'=>$data['user_id'],'address_id'=>$data['address_id']]);
                $cart_id = $this->db->insert_id();
                if($cart_result){
                    $cart_item_result_id = $this->db->select('*')->from('sab_cart_items')->where(['cart_id'=>$cart_id,'customer_id'=>$data['user_id'],'product_id'=>$data['product_id'],'status'=>1])->get()->result_array();
                    if($cart_item_result_id && count($cart_item_result_id)){
                        $cart_item_result = $this->db->where('cart_item_id',$cart_item_result_id['cart_item_id'])->update('sab_cart_items',['quantity'=>$data['quantity'],'total_price'=>$data['total_price']]);
                        if($cart_item_result){
                            return array('status' => 200,'message' => "Item updated successfully");
                        }else{
                            return array('status' => 400,'message' => "Item not updated please try again");
                        }
                    }else{
                        $cart_item_result  = $this->db->insert('sab_cart_items',['cart_id'=>$cart_id,'customer_id'=>$data['user_id'],'product_id'=>$data['product_id'],'quantity'=>$data['quantity'],'total_price'=>$data['total_price']]);
                        if($cart_item_result){
                            return array('status' => 200,'message' => "Item in cart added successfully");
                        }else{
                            return array('status' => 400,'message' => "Item not added please try again");
                        }
                    }
                }else{
                    return array('status' => 400,'message' => "Product not added please try again");
                }
            }
        }else{
            return array('status' => 400,'message' => "This product is not availble");
        } 
    }
    
}
