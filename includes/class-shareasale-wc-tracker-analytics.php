<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Analytics {

	/**
	* @var float $version Plugin version
	*/

	private $merchant_id, $version;

	public function __construct( $version ) {
		$this->version = $version;
	}

	//adds defer, async, and id attributes to specific <script/> tags
	public function script_loader_tag( $tag, $handle, $src ) {
	    if ( 'shareasale-wc-tracker-analytics-second-chance' === $handle ) {
	        return '<script type="text/javascript" src="' . $src . '" async="async" defer="defer"></script>';
	    } elseif ( 'shareasale-wc-tracker-analytics-add-to-cart-ajax' === $handle ) {
	        return '<script type="text/javascript" id="shareasale-wc-tracker-analytics-add-to-cart-ajax" src="' . $src . '"></script>';
	    } else {
	    	return $tag;
	    }
	}

	public function enqueue_scripts( $hook ) {
		//required analytics on every page
		$src         = plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics.js';
		$options     = get_option( 'shareasale_wc_tracker_options' );
		$merchant_id = $options['merchant-id'];

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
				'merchantId' => $merchant_id,
			)
		);

		wp_enqueue_script(
			'shareasale-wc-tracker-analytics-add-to-cart-ajax',
			'test.com',
			'',
			$this->version
		);
	}

	public function woocommerce_ajax_added_to_cart( $product_id ) {
		$product  = new WC_Product( $product_id );
		$sku      = $product->get_sku();
		//fortunately can't have a discount yet...
		$price    = $product->get_price();
		//quantity should be one on non-single product page where AJAX happens
		$quantity = 1;

		//if woocommerce_add_to_cart_fragments() were anonymous rather than a method, could use closure here instead...
		$this->ajax_product_data = array(
			'skulist'      => $sku,
			'pricelist'    => $price,
			'quantitylist' => $quantity,
		);
		add_filter( 'woocommerce_add_to_cart_fragments',
			array(
				$this,
				'woocommerce_add_to_cart_fragments',
			)
		);
	}

	public function woocommerce_add_to_cart_fragments( $fragments ) {
		$src = plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-add-to-cart.js?v=' . $this->version;

		ob_start();
		?>
		<script type="text/javascript">
			var shareasaleWcTrackerAnalyticsAddToCart = <?php echo json_encode( $this->ajax_product_data ) ?>;
		</script>
		<script id = "shareasale-wc-tracker-analytics-add-to-cart-ajax" type="text/javascript" src="<?php echo esc_attr( $src ); ?>"></script>
		<?php
		$fragments['script#shareasale-wc-tracker-analytics-add-to-cart-ajax'] = ob_get_clean();
		return $fragments;
	}

	public function woocommerce_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			//let woocommerce_ajax_added_to_cart() handle it
			return;
		}

		$product = new WC_Product( $product_id );
		//fortunately can't have a discount yet...
		$price   = $product->get_price();
		$sku     = $product->get_sku();

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
				'pricelist'    => $price,
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
