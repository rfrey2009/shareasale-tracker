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
		$this->version = $version;

		$options           = get_option( 'shareasale_wc_tracker_options' );
		$this->merchant_id = $options['merchant-id'];
	}

	//adds defer and async attributes to second-chance pixel <script/>
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
		$src = plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics.js';
		wp_enqueue_script(
			'shareasale-wc-tracker-analytics',
			$src,
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

	public function woocommerce_ajax_added_to_cart( $product_id ) {
		$product = new WC_Product( $product_id );
		$sku     = $product->get_sku();

		add_filter( 'woocommerce_add_to_cart_fragments', array( 'ShareASale_WC_Tracker_Analytics', 'woocommerce_add_to_cart_fragments' ) );
	}

	public static function woocommerce_add_to_cart_fragments( $fragments ) {
		$src = plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-add-to-cart.js';

		ob_start();
		?>
		<script type="text/javascript"></script>;
		<script type="text/javascript" src="meow"></script>';
		<?php

		$fragments['a.cart-contents'] = ob_get_clean();

		return $fragments;
	}

	public function woocommerce_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		$product = new WC_Product( $product_id );
		$sku     = $product->get_sku();

		// error_log("
		// 	cart_item_key:  $cart_item_key \r\n
		// 	product_id:     $product_id \r\n
		// 	quantity:       $quantity \r\n
		// 	variation_id:   $variation_id \r\n
		//     variation:      " . print_r( $variation, true ) . "\r\n
		// 	cart_item_data: " . print_r( $cart_item_data, true ) . " \r\n
		// ");
		
		$src = plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-add-to-cart.js';
		wp_enqueue_script(
			'shareasale-wc-tracker-analytics-add-to-cart',
			$src,
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
		
		$src = plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-begin-checkout.js';
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			//echo '<script type="text/javascript"></script>';
			//echo '<script type="text/javascript" src="' . $src . '"></script>';
		} else {
			wp_enqueue_script(
				'shareasale-wc-tracker-analytics-begin-checkout',
				$src,
				array( 'shareasale-wc-tracker-analytics' ),
				$this->version
			);
			//multi-item checkout possible...
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
	}

	public function woocommerce_applied_coupon( $coupon_code ) {
		$src = plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-applied-coupon.js';
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			echo '<script type="text/javascript"></script>';
			echo '<script type="text/javascript" src="' . $src . '"></script>';
		} else {
			wp_enqueue_script(
				'shareasale-wc-tracker-analytics-applied-coupon',
				$src,
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
	}

	public function woocommerce_thankyou( $order_id ) {
		$order       = new WC_Order( $order_id );
		$ordernumber = $order->get_order_number();

		$src  = plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-conversion.js';
		$src2 = 'https://shareasale-analytics.com/j.js';
		wp_enqueue_script(
			'shareasale-wc-tracker-analytics-conversion',
			$src,
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
			$src2,
			array(),
			$this->version,
			true
		);
	}
}
