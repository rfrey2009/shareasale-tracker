<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Analytics {

	/**
	* @var int $merchant_id ShareASale Merchant ID number setting
	* @var float $version Plugin version
	*/

	private $merchant_id, $version;

	public function __construct( $version ) {
		$this->version     = $version;

		$options           = get_option( 'shareasale_wc_tracker_options' );
		$this->merchant_id = $options['merchant-id'];
	}

	//adds defer and async attributes to second-chance pixel script tag
	public function script_loader_tag( $tag, $handle, $src ) {
		//list of enqueued/registered script handles to add the defer and aync attributes
		$async_scripts = array( 'shareasale-wc-tracker-analytics-second-chance' );

	    if ( in_array( $handle, $async_scripts, true ) ) {
	        return '<script type="text/javascript" src="' . $src . '" async="async" defer="defer"></script>' . "\n";
	    } else {
	    	return $tag;
	    }
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
		$product = new WC_Product( $product_id );
		$sku     = $product->get_sku();

		error_log("
			cart_item_key:  $cart_item_key \r\n 
			product_id:     $product_id \r\n 
			quantity:       $quantity \r\n 
			variation_id:   $variation_id \r\n 
		    variation:      " . print_r( $variation, true ) . "\r\n  
			cart_item_data: " . print_r( $cart_item_data, true ) . " \r\n
		");

		wp_enqueue_script(
			'shareasale-wc-tracker-analytics-add-to-cart',
			plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-add-to-cart.js',
			array( 'shareasale-wc-tracker-analytics' ),
			$this->version
		);
		//single item add to cart
		wp_localize_script(
			'shareasale-wc-tracker-analytics-add-to-cart',
			'shareasaleWcTrackerAnalyticsAddToCart',
			array(
				'skulist'      => $sku,
				'pricelist'    => '',
				'quantitylist' => $quantity,
			)
		);
	}

	public function woocommerce_checkout_init( $instance ) {

		//error_log( print_r( $instance, true ) );

		wp_enqueue_script(
			'shareasale-wc-tracker-analytics-begin-checkout',
			plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-begin-checkout.js',
			array( 'shareasale-wc-tracker-analytics' ),
			$this->version
		);
		//multi-item checkout possible
		wp_localize_script(
			'shareasale-wc-tracker-analytics-begin-checkout',
			'shareasaleWcTrackerAnalyticsBeginCheckout',
			array(
				'skulist'      => '',
				'pricelist'    => '',
				'quantitylist' => '',
			)
		);
	}

	public function woocommerce_applied_coupon( $coupon_code ) {

		wp_enqueue_script(
			'shareasale-wc-tracker-analytics-applied-coupon',
			plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-applied-coupon.js',
			array( 'shareasale-wc-tracker-analytics' ),
			$this->version
		);

		wp_localize_script(
			'shareasale-wc-tracker-analytics-applied-coupon',
			'shareasaleWcTrackerAnalyticsAppliedCoupon',
			array(
				'couponcode' => $coupon_code,
			)
		);
	}

	public function woocommerce_thankyou( $order_id ) {
		$order = new WC_Order( $order_id );
		$ordernumber = $order->get_order_number();

		wp_enqueue_script(
			'shareasale-wc-tracker-analytics-conversion',
			plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-conversion.js',
			array( 'shareasale-wc-tracker-analytics' ),
			$this->version
		);

		wp_localize_script(
			'shareasale-wc-tracker-analytics-conversion',
			'shareasaleWcTrackerAnalyticsConversion',
			array(
				'ordernumber' => $ordernumber,
			)
		);
		//last arg ensures it goes in the footer, beneath the normal ShareASale_WC_Tracker_Pixel() instance
		wp_enqueue_script(
			'shareasale-wc-tracker-analytics-second-chance',
			'https://shareasale-analytics.com/j.js',
			'',
			$this->version,
			true
		);
	}
}
