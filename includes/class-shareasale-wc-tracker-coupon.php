<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Coupon extends WC_Coupon  {

	protected $data = array(
		'code'                        => '',
		'amount'                      => 0,
		'date_created'                => null,
		'date_modified'               => null,
		'date_expires'                => null,
		'discount_type'               => 'fixed_cart',
		'description'                 => '',
		'usage_count'                 => 0,
		'individual_use'              => false,
		'product_ids'                 => array(),
		'excluded_product_ids'        => array(),
		'usage_limit'                 => 0,
		'usage_limit_per_user'        => 0,
		'limit_usage_to_x_items'      => null,
		'free_shipping'               => false,
		'product_categories'          => array(),
		'excluded_product_categories' => array(),
		'exclude_sale_items'          => false,
		'minimum_amount'              => '',
		'maximum_amount'              => '',
		'email_restrictions'          => array(),
		'used_by'                     => array(),
		'virtual'                     => false,
		'shareasale_wc_tracker_store_id' => '',
		'shareasale_wc_tracker_deal_id'  => '',
	);

	public function set_store_id( $id ) {
		$this->set_prop( 'shareasale_wc_tracker_store_id', $id );
	}

	public function set_deal_id( $id ) {
		$this->set_prop( 'shareasale_wc_tracker_deal_id', $id );
	}

	public function get_store_id( $context = 'view' ) {
		return $this->get_prop( 'shareasale_wc_tracker_store_id', $context );
	}

	public function get_deal_id( $context = 'view' ) {
		return $this->get_prop( 'shareasale_wc_tracker_deal_id', $context );
	}
}
