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

	public function wp_head() {
		echo '
			<noscript id="shareasale-wc-tracker-analytics-add-to-cart-ajax-model"></noscript>
			<noscript id="shareasale-wc-tracker-analytics-add-to-cart-ajax"></noscript>
			<noscript id="shareasale-wc-tracker-analytics-add-to-cart-ajax-cb"></noscript>
			';
	}

	public function script_loader_tag( $tag, $handle, $src ) {
		$async_scripts = array( 'shareasale-wc-tracker-analytics-second-chance' );

		if ( in_array( $handle, $async_scripts, true ) ) {
			return '<script type="text/javascript" src="' . $src . '" async="async" defer="defer"></script>' . "\n";
		} else {
			return $tag;
		}
	}

	public function enqueue_scripts( $hook ) {
		//required base analytics on every page
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
	}

	public function woocommerce_ajax_added_to_cart( $product_id ) {
		$product  = new WC_Product( $product_id );
		$sku      = $product->get_sku();
		$price    = $product->get_price();
		//quantity should be one on non-single product page where AJAX happens
		$quantity = 1;

		$this->ajax_product_data = array(
			'skulist'      => $sku,
			'pricelist'    => $price,
			'quantitylist' => $quantity,
		);
		//inject the custom <script/> HTML fragments into the JSON response by using this WC filter
		add_filter( 'woocommerce_add_to_cart_fragments',
			array(
				$this,
				'woocommerce_add_to_cart_fragments',
			)
		);
	}

	public function woocommerce_add_to_cart_fragments( $fragments ) {
		$src  = plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-add-to-cart.js?v=' . $this->version;
		$src2 = plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-cache-buster.js?v=' . $this->version;

		ob_start();
		?>
		<!-- first localize product data -->
		<script id="shareasale-wc-tracker-analytics-add-to-cart-ajax-model" type="text/javascript">
			var shareasaleWcTrackerAnalyticsAddToCart = <?php echo wp_json_encode( $this->ajax_product_data ) ?>;
		</script>
		<?php
		$fragments['#shareasale-wc-tracker-analytics-add-to-cart-ajax-model'] = ob_get_clean();
		?>
		<!-- run atc call for ShareASale analytics -->
		<script id="shareasale-wc-tracker-analytics-add-to-cart-ajax" type="text/javascript" src="<?php echo esc_attr( $src ); ?>"></script>
		<?php
		$fragments['#shareasale-wc-tracker-analytics-add-to-cart-ajax'] = ob_get_clean();
		?>
		<!-- make sure the script tag isn't cached in HTML5 session storage as another WC cart fragment... -->
		<script id="shareasale-wc-tracker-analytics-add-to-cart-ajax-cb" type="text/javascript" src="<?php echo esc_attr( $src2 ); ?>"></script>
		<?php
		$fragments['#shareasale-wc-tracker-analytics-add-to-cart-ajax-cb'] = ob_get_clean();

		return $fragments;
	}

	public function woocommerce_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		if ( defined( 'WC_DOING_AJAX' ) && DOING_AJAX ) {
			//let woocommerce_ajax_added_to_cart() handle it
			return;
		}

		$product = new WC_Product( $product_id );
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

	public function woocommerce_before_checkout_form( $checkout ) {
		global $woocommerce;

		$src   = plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-begin-checkout.js';

		$items = $woocommerce->cart->get_cart();
		$last_index = array_search( end( $items ), $items, true );

		foreach ( $items as $index => $item ) {
			$delimiter = $index === $last_index ? '' : ',';
			$sku = ( new WC_Product( $item['product_id'] ) )->get_sku();

			isset( $skulist ) ? $skulist .= $sku . $delimiter : $skulist = $sku . $delimiter;

			isset( $pricelist ) ? $pricelist .= round( ( $item['line_total'] / $item['quantity'] ), 2 ) . $delimiter : $pricelist = round( ( $item['line_total'] / $item['quantity'] ), 2 ) . $delimiter;

			isset( $quantitylist ) ? $quantitylist .= $item['quantity'] . $delimiter : $quantitylist = $item['quantity'] . $delimiter;
		}

		if ( defined( 'WC_DOING_AJAX' ) && DOING_AJAX ) {
			ob_start();
			?>
			<!-- first localize coupon data -->
			<script type="text/javascript">
				var shareasaleWcTrackerAnalyticsBeginCheckout = 
				<?php
				echo wp_json_encode(
					array(
						'skulist'      => $skulist,
						'pricelist'    => $pricelist,
						'quantitylist' => $quantitylist,
					)
				) ?>;
			</script>
			<!-- run coupon applied call for ShareASale analytics -->
			<script type="text/javascript" src="<?php echo esc_attr( $src ); ?>"></script>
			<?php
			ob_end_flush();
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
					'skulist'      => $skulist,
					'pricelist'    => $pricelist,
					'quantitylist' => $quantitylist,
				)
			);
		}
	}

	public function woocommerce_applied_coupon( $coupon_code ) {
		$src = plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-applied-coupon.js';

		if ( defined( 'WC_DOING_AJAX' ) && DOING_AJAX ) {
			ob_start();
			?>
			<!-- first localize coupon data -->
			<script type="text/javascript">
				var shareasaleWcTrackerAnalyticsAppliedCoupon = <?php echo wp_json_encode( array( 'couponcode' => $coupon_code ) ) ?>;
			</script>
			<!-- run coupon applied call for ShareASale analytics -->
			<script type="text/javascript" src="<?php echo esc_attr( $src ); ?>"></script>
			<?php
			ob_end_flush();
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
		//don't bother if we've already fired a standard ShareASale_WC_Tracker_Pixel() for this
		$prev_triggered = get_post_meta( $order_id, 'shareasale-wc-tracker-triggered', true );
		if ( $prev_triggered ) {
			return;
		}

		$order          = new WC_Order( $order_id );
		$ordernumber    = $order->get_order_number();

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