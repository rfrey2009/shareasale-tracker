<?php
class ShareASale_WC_Tracker_Analytics {

	/**
	* @var int $merchant_id ShareASale Merchant ID number setting
	* @var float $version Plugin version
	*/

	private $version;

	public function __construct( $version ) {
		$this->version     = $version;

		$options           = get_option( 'shareasale_wc_tracker_options' );
		$this->merchant_id = $options['merchant-id'];
	}
	//adds defer and async to second-chance pixel js
	public function script_loader_tag( $tag, $handle, $src ) {

		$async_scripts = array( 'shareasale-wc-tracker-analytics-second-chance' );

	    if ( in_array( $handle, $async_scripts ) ) {
	        return '<script type="text/javascript" src="' . $src . '" async="async" defer="defer"></script>' . "\n";
	    }

	    return $tag;

	}

	public function enqueue_scripts( $hook ) {
		//required analytics on every page
		wp_enqueue_script(
			'shareasale-wc-tracker-analytics',
			plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics.js',
			'',
			$this->version
		);

		wp_localize_script(
			'shareasale-wc-tracker-analytics',
			'shareasaleWcTrackerAnalytics',
			array(
				'merchantId' => $this->merchant_id,
			)
		);
	}

	public function woocommerce_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {

		error_log( 'ITEM ADDED TO CART!' );

	}

	public function woocommerce_checkout_init( $instance ) {

		error_log( 'CHECKOUT INITIATED!' );

	}

	public function woocommerce_applied_coupon( $coupon_code ) {

		error_log( 'COUPON APPLIED!' );

	}

	public function woocommerce_thankyou( $order_id ) {

		error_log( 'CONVERSION DONE!' );

		wp_enqueue_script(
			'shareasale-wc-tracker-analytics-second-chance',
			'https://shareasale-analytics.com/j.js',
			'',
			$this->version,
			true
		);

	}
}
