<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class HomeModel extends CI_Model {

    public function NearByStores($params){
        $longitude = $params['longitude'];
        $latitude = $params['latitude'];
        
        //$this->db->query("update sab_company_stores set home_delivary='1',pick_yourself='1',express_delivary='0',min_price='300',max_price='500',owner_name='Muhammad Salam',owner_contact='03312477922',store_image='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSo56jPdiqXhXrNfGd0xHqrjR_m95tRN3bK_g&usqp=CAU' where id = '34'");
        
        $meta = [];
        $data = $this->db->select('id,name,address,home_delivary,pick_yourself,express_delivary,min_price,max_price,owner_name,owner_contact,store_ratings,store_image,latitude,longitude,estimated_time')->from('sab_company_stores')->order_by('id','desc')->get()->result_array();
        //return array('status' => 200,'data'=>$data);
        foreach($data as $key=>$value){
            $d = getDistanceBetweenPointsNew($value["latitude"], $value["longitude"], $latitude, $longitude);
            if(($d*1000) <= 1000)
                $meta[] = $value;
        }
        return array('status' => 200,'message' => 'Total Stores: '.count($meta),'data'=>$meta);
    }  
    public function NearByStoresProductPrice($store_ids,$cart_data){
        $product_ids = [];
        foreach($cart_data as $c_d){
            $product_ids[] = $c_d['product_id'];
            $cart_id = $c_d['cart_id'];
        }
        // return array('status' => 200,'da'=>['p-id'=>$product_ids,'c-id'=>$cart_id]);
        $meta = [];
        foreach($store_ids as $store_id){
            $store_info = $this->db->select('id as store_id,name as storeName,owner_name as ownerName,owner_contact as ownerContact,max_price as DelivaryFee,estimated_time as EstimatedTime')->from('sab_company_stores')->where(['id'=>$store_id])->order_by('id','desc')->get()->result_array();
            $this->db->distinct('sab_store_inventory_products');
            $this->db->distinct('sab_inventory_products.id as productId');
            $this->db->select('sab_inventory_products.id as productId,sab_inventory_products.title as productName,sab_inventory_products.picture as productImage,sab_inventory_products.size as productSize,sab_inventory_products.price as productPrice,sab_store_inventory_products.price as storeProductPrice,sab_cart_items.quantity as cartQuantity');
            $this->db->from('sab_store_inventory_products');
            $this->db->join('sab_company_stores', 'sab_company_stores.id = sab_store_inventory_products.store_id');
            $this->db->join('sab_inventory_products', 'sab_inventory_products.id = sab_store_inventory_products.product_id');
            $this->db->join('sab_cart_items', 'sab_cart_items.product_id = sab_inventory_products.id');
            $this->db->where('sab_store_inventory_products.store_id', $store_id);
            $this->db->where('sab_cart_items.cart_id', $cart_id);
            $this->db->where('sab_cart_items.status', 1);
            $this->db->where_in('sab_store_inventory_products.product_id', $product_ids);
            $result = $this->db->get()->result_array();
            if($result && count($result) === count($product_ids)){
                $subTotal=0;
                $discountAmount=0;
                foreach($result as $v){
                    $subTotal = $this->safeData($subTotal)+($this->safeData($v['storeProductPrice'])*$this->safeData($v['cartQuantity']));
                    $discountAmount = $discountAmount+($this->safeData($v['productPrice'])*$this->safeData($v['cartQuantity']));
                }
               $meta[] = [
                 'cart_count' =>count($result),
                'subTotal'=>$subTotal,
                'totalDiscount'=>($discountAmount-$subTotal),
                'store' =>$store_info,
                'data' => $result]; 
            }
        }
        //  $meta['c'] = count($product_ids);
        // $meta['pids'] = $product_ids;
        //print_r($this->db->last_query());    
        return array('status' => 200,'data'=>$meta);
    }
    
    function safeData($v){
        if(is_numeric($v)){
            return $v;
        }
        return settype($v, "double");
    }
}
// Outside the Class
/**
 * Method to find the distance between 2 locations from its coordinates.
 * 
 * @param latitude1 LAT from point A
 * @param longitude1 LNG from point A
 * @param latitude2 LAT from point A
 * @param longitude2 LNG from point A
 * 
 * @return Float Distance in Kilometers.
 */
function getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2, $unit = 'Km') {
    $theta = $longitude1 - $longitude2;
    $distance = sin(deg2rad($latitude1)) * sin(deg2rad($latitude2)) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta));

    $distance = acos($distance); 
    $distance = rad2deg($distance); 
    $distance = $distance * 60 * 1.1515;

    switch($unit) 
    { 
        case 'Mi': break;
        case 'Km' : $distance = $distance * 1.609344; 
    }

    return (round($distance,2)); 
}
