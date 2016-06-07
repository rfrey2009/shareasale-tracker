<?php
 
class ShareASale_Tracker_WooCommerce {

     /**
   * @var WC_Order $order WooCommere order object https://docs.woothemes.com/wc-apidocs/class-WC_Order.html
   * @var float $version Plugin version
   */

    private $order, $version;

    public function __construct( $version ) {
        $this->version = $version;
    } 
    
    public function woocommerce_thankyou($order_id) {

        if(!$order_id) return;

        $this->order = new WC_Order($order_id); 
        
        $merchantID = get_option( 'tracker_options' )['Merchant ID'];
        $product_data = $this->get_product_data();

        echo '<img src="https://shareasale.com/sale.cfm?amount=' . $this->get_order_amount() . 
                                                      '&tracking=' . $this->order->get_order_number() . 
                                                      '&transtype=sale&merchantID=' . $merchantID . 
                                                      '&skulist=' . $product_data->skulist . 
                                                      '&quantitylist=' . $product_data->quantitylist . 
                                                      '&pricelist=' . $product_data->pricelist . 
                                                      '&couponcode=' . $this->get_coupon_codes() . 
                                                      '&currency=' . $this->get_currency() . 
                                                      '&newcustomer=' . $this->get_customer_status() . 
                                                      '&v=' . $this->get_version() . 
                                                      '" width="1" height="1">';

    }    

    private function get_order_amount(){

        $grand_total = $this->order->get_total();
        //$total_discount = $this->order->get_cart_discount();
        $total_shipping = $this->order->get_total_shipping();
        $total_taxes = $this->order->get_total_tax();
        $subtotal = $grand_total - ($total_shipping + $total_taxes);

        if ($subtotal < 0)
            $subtotal = 0;

        return $subtotal;

    }

    private function get_product_data(){

        $product_data = new stdClass();

        //Let's get the items in the order
        $items = $this->order->get_items();

        $last_index = array_search(end($items), $items, true);
        foreach ($items as $index => $item){

            $delimiter = $index === $last_index ? '' : ',';

            $id = $item['product_id'];
            $product = new WC_Product($id);
            $sku = $product->get_sku();     
            isset($product_data->skulist) ? $product_data->skulist .= $sku . $delimiter : $product_data->skulist = $sku . $delimiter;
            isset($product_data->pricelist) ? $product_data->pricelist .= round(($item['line_total'] / $item['qty']), 2) . $delimiter : $product_data->pricelist = round(($item['line_total'] / $item['qty']), 2) . $delimiter;
            isset($product_data->quantitylist) ? $product_data->quantitylist .= $item['qty'] . $delimiter : $product_data->quantitylist = $item['qty'] . $delimiter;            
            
        }

        return $product_data;

    }

    private function get_customer_status(){
        //assume guest checkout so no newcustomer info passed to ShareASale at all
        $newcustomer = '';
        //check if get_user_id() exists since WC docs says it does, but a few merchants have had "call to undefined method" fatal errors in WC_Order when using it...
        if (method_exists($this->order, 'get_user_id')){
            //it exists, so now lets find out the hard way if this is a new customer or not...
            $customer_user_id = $this->order->get_user_id();
            if($customer_user_id != 0){ //is it a guest checkout with user ID 0, or a real customer with an assigned ID?
                $user_orders = get_posts(
                    array(
                        'post_type'   => 'shop_order', 
                        'meta_key'    => '_customer_user', 
                        'meta_value'  => $customer_user_id,
                        'posts_per_page' => -1,
                        'post_status' => array_keys( wc_get_order_statuses() )
                    )
                );
                $order_count = count($user_orders);
                //whether new or existing customer based on order count so far
                $newcustomer = ($order_count > 1 ? 0 : 1);
            }
        }

        return $newcustomer;  

    }

    private function get_coupon_codes(){

        $couponcode = implode(', ', $this->order->get_used_coupons());

        return $couponcode;
    }

    private function get_currency(){

        $currency = $this->order->get_order_currency();

        return $currency;
    }

    public function get_version() {
        return $this->version;
    }    
}