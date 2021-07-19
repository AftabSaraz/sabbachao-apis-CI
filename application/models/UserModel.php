<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class UserModel extends CI_Model {
    public function __construct() {
        parent::__construct();
        /* Load Models - ProductModel */
        $this->load->model('ProductModel');
        date_default_timezone_set("Asia/Karachi");
    }
    public function offers()
    {
        $offers = $this->db->select('*')->from('sab_offers')->get()->result_array();
        if($offers){
			return array('status' => 200,'message' => "Offers",'total ofers '=>count($offers),'data'=>$offers);
        }else{
            return array('status' => 400,'message' => "Offers not availble");
        } 
    }
    public function addCustomerAddress($data)
    { 
        if(count($data) ==1){
            return array('status' => 400,'message' => 'Data is required.');
        }else{
            $data['customer_city'] = $data['city'];
            $data['customer_status'] = 1;
            unset($data['city']);
            //return array('status' => 400,'message' => $data);
            $result  = $this->db->insert('sab_customer_addresses',$data);
            if($result){
                return array('status' => 200,'message' => 'Address added Successfully');
            }else{
                return array('status' => 400,'message' => 'Address Not added,try again');
            }
        } 
    }
    public function defaultAddress($data)
    { 
        if(count($data) ==1){
            return array('status' => 400,'message' => 'Data is required.');
        }else{
            $already_default_addresses = $this->db->select('*')->from('sab_customer_addresses')->where(['customer_id'=>$data['customer_id'],"default_address"=>'1'])->get()->result_array();
            if($already_default_addresses){
                $this->db->set(['default_address'=>0]);
                $this->db->where(['customer_id'=>$data['customer_id']]);
                $r= $this->db->update('sab_customer_addresses');
            }
            $this->db->set(['default_address'=>1]);
            $this->db->where(['id'=>$data['address_id'],'customer_id'=>$data['customer_id']]);
            $result = $this->db->update('sab_customer_addresses'); 
            if($result){
                return array('status' => 200,'message' => "Default Address Added");
            }else{
                return array('status' => 400,'message' => "Default Address not Added");
            }
        } 
    }
    public function getDefaultAddress($data)
    { 
        if(count($data) <=0){
            return array('status' => 400,'message' => 'Data is required.');
        }else{
            $default_addresses = $this->db->select('*')->from('sab_customer_addresses')->where(['customer_id'=>$data['customer_id'],"default_address"=>'1'])->get()->result_array();
            if($default_addresses){
                //return array('status' => 400,'message' => $default_addresses);
                return array('status' => 200,"latitude"=>$default_addresses[0]['latitude'],"longitude"=>$default_addresses[0]['longitude'],"default_address"=>$default_addresses[0]['address'],"default_label"=>$default_addresses[0]['label'],'default_address_id'=>$default_addresses[0]['id'],'message' => "Default Addresses",'total defaultAddresses '=>count($default_addresses));
            }else{
                return array('status' => 400,'message' => "Default Address not Found");
            }
        } 
    }
    public function getCustomerAddresses($data)
    { 
        if(count($data) ==1 && $data['user_id']){
            $customer_addresses = $this->db->select('*')->from('sab_customer_addresses')->where(['customer_id'=>$data['user_id'],"customer_status"=>'1'])->get()->result_array();
            if($customer_addresses){
                return array('status' => 200,'message' => 'Customer Addresses','Tolat Customer Addresses'=>count($customer_addresses),'data'=>$customer_addresses);
            }else{
                return array('status' => 400,'message' => 'Address Not added,try again');
            }
        }else{
            return array('status' => 400,'message' => "Address Not availble");
        } 
    }
    public function updateCustomerAddressLabel($data)
    {   //return array('status' => 200,'message' => $data);
        $required = ['label','address_id'];
        if (count($data)==1) { return array('status' => 400,'message' =>"label and address_id is required"); } 
        foreach($required as $column){
            if (!array_key_exists($column, $data)) {
                return array('status' => 400,'message' => "$column field is required");
            } else if(trim($data[$column]) === '') {
                return array('status' => 400,'message' => "$column can not be empty");
            }
        }
        $customer_id = $data['customer_id'];
        $address_id = $data['address_id'];
        unset($data['address_id']);
        $this->db->set($data);
        $this->db->where(['id'=>$address_id,'customer_id'=>$customer_id]);
        $result = $this->db->update('sab_customer_addresses'); 
        if($result){
            return array('status' => 200,'message' => "Data has been updated");
        }else{
            return array('status' => 400,'message' => "Data not Updated");
        }
    }
    public function deleteCustomerAddress($data)
    { //return array('status' => 200,'message' => $data);
        $this->db->set('customer_status','0');
        $this->db->where('id',$data['address_id']);
        $result = $this->db->update('sab_customer_addresses'); 
        if($result){
            // $customer_addresses = $this->db->select('*')->from('sab_customer_addresses')->where(['customer_id'=>$data['customer_id'],"customer_status"=>'1'])->get()->result_array();
            // if($customer_addresses){
            //     return array('status' => 200,'message' => 'Customer Addresses','Tolat Customer Addresses'=>count($customer_addresses),'data'=>$customer_addresses);
            // }else{
            //     return array('status' => 400,'message' => 'Something Went Wrong,try again');
            // }
            return array('status' => 200,'message' => "Customer Address has been Deleted");
        }else{
            return array('status' => 400,'message' => "Customer Address not Deleted");
        }
    }
    public function placeOrder($cart_id,$store_id,$product_ids,$data)
    {   $subTotal=0;
        $discountAmount=0;
        foreach($product_ids as $product_id){
            $this->db->select('sab_inventory_products.id as productId,sab_company_stores.max_price as DelivaryFee,sab_inventory_products.title as productName,sab_inventory_products.picture as productImage,sab_inventory_products.size as productSize,sab_inventory_products.price as productPrice,sab_store_inventory_products.price as storeProductPrice,sab_cart_items.quantity as cartQuantity');
            $this->db->from('sab_store_inventory_products');
            $this->db->join('sab_company_stores', 'sab_company_stores.id = sab_store_inventory_products.store_id');
            $this->db->join('sab_inventory_products', 'sab_inventory_products.id = sab_store_inventory_products.product_id');
            $this->db->join('sab_cart_items', 'sab_cart_items.product_id = sab_inventory_products.id');
            $this->db->where('sab_store_inventory_products.store_id', $store_id);
            $this->db->where('sab_cart_items.cart_id', $cart_id);
            $this->db->where('sab_cart_items.status', 1);
            $this->db->where('sab_store_inventory_products.product_id', $product_id);
            $result = $this->db->get()->result_array();
            //$store_p_a[] = $result;
            if(count($result)>0){
                $store_p_a[] = $result;
            }
            if($result){
                
                foreach($result as $v){
                    $subTotal = $this->safeData($subTotal)+($this->safeData($v['storeProductPrice'])*$this->safeData($v['cartQuantity']));
                    $discountAmount = $discountAmount+($this->safeData($v['productPrice'])*$this->safeData($v['cartQuantity']));
                }
               $meta[] = [
                'subTotal'=>$subTotal,
                'totalDiscount'=>($discountAmount-$subTotal),
                'data' => $result]; 
            }
        }
        //return array('status' => 200,'data'=>$meta);
        if($data['pickUp_or_homeDelivery']==1){
            $subTotal = ($subTotal)+($result[0]['DelivaryFee']);
        }
        if(count($store_p_a)>0){
            $data_array = [
                'order_title'=>'Shopping List',
                'pickupHome'=>$data['pickUp_or_homeDelivery']?$data['pickUp_or_homeDelivery']:0,
                'delivery_charges_normal'=>(($data['pickUp_or_homeDelivery']==1)?$result[0]['DelivaryFee']:0),
                'grand_total'=>$subTotal?$subTotal:0,
                'totalDiscount'=>($discountAmount-$subTotal)?($discountAmount-$subTotal):0,
                'shipping_address_id'=>$data['address_id']?$data['address_id']:0,
                'latitude'=>$data['latitude']?$data['latitude']:0,
                'longitude'=>$data['longitude']?$data['longitude']:0,
                'customer_id'=>$data['user_id']?$data['user_id']:0,
                'store_id'=>$store_id?$store_id:0,
                'status_type'=>"DELIVERED",
                'date_created'=>date("Y-m-d H:i:s"),
            ];
            $result  = $this->db->insert('sab_store_orders',$data_array);
            $order_id = $this->db->insert_id();
            if($order_id){
                foreach($store_p_a as $p){
                    $d = [
                    'quantity'=>$p[0]['cartQuantity']?$p[0]['cartQuantity']:0,
                    'unit_price'=>$p[0]['storeProductPrice']?$p[0]['storeProductPrice']:0,
                    'total_price'=>(($p[0]['storeProductPrice'])*($p[0]['cartQuantity']))?(($p[0]['storeProductPrice'])*($p[0]['cartQuantity'])):0,
                    'order_id'=>$order_id?$order_id:0,
                    's_p_id'=>$p[0]['productId']?$p[0]['productId']:0
                    ];
                    $r  = $this->db->insert('sab_store_order_products',$d);
                    if(!$r){
                        return array('status' => 400,'message'=>'oder not placed sab_store_order_products,try again');
                    }
                }
                $this->db->set(['status'=>0]);
                $this->db->where(['cart_id'=>$cart_id,'customer_id'=>$data['user_id']]);
                $this->db->update('sab_carts');
                return array('status' => 200,'message'=>'Order Placed Successfully');
            }else{
                return array('status' => 400,'message'=>'oder not placed ,try again');
            }
        }else{
            return array('status' => 400,'message'=>'Products Not Found');
        }
    }
    function safeData($v){
        if(is_numeric($v)){
            return $v;
        }
        return settype($v, "double");
    } 
    public function orderHistory($data)
    {   
        $orderHistory = $this->db->select('*')->from('sab_store_orders')->where(['customer_id'=>$data['user_id']])->get()->result_array();
        if($orderHistory){
            foreach($orderHistory as $o_h){
                $o_p = $this->db->select('COUNT(sab_store_order_products.s_p_id) AS total_items ')->from('sab_store_order_products')->where(['order_id'=>$o_h['id']])->get()->result_array();
                if($o_p){
                    $result[] =[
                    'title'=>$o_h['order_title'],
                    'order_id'=> $o_h['id'],
                    'date_created'=> $o_h['date_created'],
                    'sub_total' => $o_h['grand_total'],
                    'total_items'=>$o_p[0]['total_items'],
                    'status_type'=>$o_h['status_type'],
                    ]; 
                }
            }
            return array('status' => 200,'message' => 'OrderHistory','Tolat Orders'=>count($result),'data'=>$result);
        }else{
            return array('status' => 400,'message' => 'Orders Not Found');
        }
    }
    public function updateOrderHistoryTitle($data)
    {   
        $this->db->set(['order_title'=>$data['title']]);
        $this->db->where(['id'=>$data['order_id'],'customer_id'=>$data['user_id']]);
        $UpdateOrderHistoryTitle = $this->db->update('sab_store_orders');
        if($UpdateOrderHistoryTitle){
            return array('status' => 200,'message' => 'Title Updated Successfully');
        }else{
            return array('status' => 400,'message' => 'Title Not Updated');
        }
    }
    public function orderDetail($data)
    {   
        $order = $this->db->select('*')->from('sab_store_orders')->where(['id'=>$data['order_id']])->get()->result_array();
        $orderProducts = $this->db->select('*')->from('sab_store_order_products')->where(['order_id'=>$data['order_id']])->get()->result_array();
        $this->db->select('sab_customers.username as CustomerFullName, 
                sab_customers.mobile_number as CustomerContact,
                sab_customer_addresses.address as CustomerAddress,
                sab_customer_addresses.label as CustomerTitle,
                sab_company_stores.name as StoreName, 
                sab_company_stores.address as StoreAddress,
                sab_company_stores.owner_name as StoreOwnerName,
                sab_company_stores.owner_contact as StoreOwnerContact,
                sab_store_orders.id as OrderId,
                sab_store_orders.grand_total as SubTotal,
                sab_store_orders.totalDiscount as TotalDiscount,
                sab_store_orders.delivery_charges_normal as DeliveryFee,
                sab_store_orders.pickupHome as PickupHome,
                sab_store_orders.latitude as CustomerAddressLatitude,
                sab_store_orders.longitude as CustomerAddressLogitude,
                sab_store_orders.shipping_address_id as CustomerShippingAddress_id,
                sab_store_orders.status_type as OrderStatus');
        $this->db->from('sab_store_orders');
        $this->db->join('sab_customers', 'sab_customers.id = sab_store_orders.customer_id');
        $this->db->join('sab_company_stores', 'sab_company_stores.id = sab_store_orders.store_id');
        $this->db->join('sab_customer_addresses', 'sab_customer_addresses.id = sab_store_orders.shipping_address_id','left');
        $this->db->where('sab_store_orders.id', $order[0]['id']);
        $this->db->where('sab_store_orders.customer_id', $order[0]['customer_id']);
        $this->db->where('sab_store_orders.store_id', $order[0]['store_id']);
        $r= $this->db->get()->result_array();
        if($orderProducts){
            foreach($orderProducts as $orderProduct){
                $this->db->select('sab_inventory_products.title as ProductName, 
                sab_store_order_products.unit_price AS UnitPrice, 
                sab_store_order_products.quantity AS ProductQuantity, 
                sab_inventory_products.discount as Discount, 
                sab_inventory_products.size as ProductSize, 
                sab_inventory_products.picture as ProductImage');
                $this->db->from('sab_inventory_products');
                $this->db->join('sab_store_order_products', 'sab_store_order_products.s_p_id = sab_inventory_products.id');
                $this->db->join('sab_store_orders', 'sab_store_orders.id = sab_store_order_products.order_id');
                $this->db->where('sab_inventory_products.id', $orderProduct['s_p_id']);
                $this->db->where('sab_store_order_products.s_p_id', $orderProduct['s_p_id']);
                $this->db->where('sab_store_order_products.order_id', $orderProduct['order_id']);
                $result[] = $this->db->get()->result_array()[0];
            }
            return array('status' => 200,'message' => 'OrderDetail','meta'=>(count($r)>0)?$r[0]:null,'Total-Products'=>count($result),'Products'=>$result);
        }else{
            return array('status' => 400,'message' => 'Orders Not Found');
        }
    }
}
