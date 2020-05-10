<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Analytics {

	/**
	* @var string $version Plugin version
	* @var mixed $options Plugin settings for use in a few places
	*/

	private $version, $options;

	public function __construct( $version ) {
		$this->version = $version;
		$this->options = get_option( 'shareasale_wc_tracker_options' );
	}

	//for cart fragment updates
	public function wp_head() {
		if ( empty( $this->options['analytics-setting'] ) ) {
			return;
		}
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-analytics-noscript.php';
	}

	public function enqueue_scripts( $hook ) {
		if ( empty( $this->options['analytics-setting'] ) ) {
			return;
		}

		//required base analytics on every page
		$src         = esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics.js' );
		$src2        = esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-cart-observer.js' );
		$merchant_id = $this->options['merchant-id'];

		wp_enqueue_script(
			'shareasale-wc-tracker-analytics',
			$src,
			array(),
			$this->version
		);

		// $this->shareasale_wc_tracker_add_data(
		// 	'shareasale-wc-tracker-analytics',
		// 	'var shareasaleWcTrackerAnalytics = ' . wp_json_encode(
		// 		array(
		// 			'merchantId' => $merchant_id,
		// 		)
		// 	)
		// );

		wp_localize_script(
			'shareasale-wc-tracker-analytics',
			'shareasaleWcTrackerAnalytics',
			array(
				'merchantId' => $merchant_id,
			)
		);

		if ( is_cart() || is_shop() ) {
			wp_enqueue_script(
				'shareasale-wc-tracker-analytics-cart-observer',
				$src2,
				array( 'jquery' ),
				$this->version
			);

			// $this->shareasale_wc_tracker_add_data(
			// 	'shareasale-wc-tracker-analytics-cart-observer',
			// 	'var shareasaleWcTrackerAnalyticsCartObserver = ' . wp_json_encode(
			// 		array(
			// 			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			// 		)
			// 	)
			// );

			wp_localize_script(
				'shareasale-wc-tracker-analytics-cart-observer',
				'shareasaleWcTrackerAnalyticsCartObserver',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
				)
			);
		}
	}

	public function wp_ajax_nopriv_shareasale_wc_tracker_update_cart_action_cart_updated() {
		$this->wp_ajax_shareasale_wc_tracker_update_cart_action_cart_updated();
	}

	public function wp_ajax_shareasale_wc_tracker_update_cart_action_cart_updated() {
		$this->woocommerce_ajax_added_to_cart();
		$fragments = $this->woocommerce_add_to_cart_fragments( array() );
		wp_send_json( $fragments );
	}

	public function woocommerce_ajax_added_to_cart() {
		if ( empty( $this->options['analytics-setting'] ) ) {
			return;
		}

		$items = WC()->cart->get_cart();
		$lists = $this->calculate_lists( $items );

		$skulist      = $lists['skulist'];
		$pricelist    = $lists['pricelist'];
		$quantitylist = $lists['quantitylist'];

		$this->ajax_product_data = array(
			'skulist'      => $skulist,
			'pricelist'    => $pricelist,
			'quantitylist' => $quantitylist,
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
		$src  = esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-add-to-cart.js?v=' . $this->version );
		$src2 = esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-cache-buster.js?v=' . $this->version );
		//$fragments is an array with maybe an existing key named after its HTML value's class, 'div.widget_shopping_cart_content'
		//add new keys named after their HTML value's id. These will replace existing same id <noscript> HTML elements
		ob_start();
		?>
			<script id="shareasale-wc-tracker-analytics-add-to-cart-ajax-model" type="text/javascript">
				var shareasaleWcTrackerAnalyticsAddToCart = <?php echo wp_json_encode( $this->ajax_product_data ) ?>;
			</script>

		<?php $fragments['#shareasale-wc-tracker-analytics-add-to-cart-ajax-model'] = ob_get_contents(); ob_clean(); ?>
			
			<script id="shareasale-wc-tracker-analytics-add-to-cart-ajax" type="text/javascript" src="<?php echo esc_attr( $src ); ?>"></script>

		<?php $fragments['#shareasale-wc-tracker-analytics-add-to-cart-ajax'] = ob_get_contents(); ob_clean(); ?>
			
			<script id="shareasale-wc-tracker-analytics-add-to-cart-ajax-cb" type="text/javascript" src="<?php echo esc_attr( $src2 ); ?>"></script>

		<?php $fragments['#shareasale-wc-tracker-analytics-add-to-cart-ajax-cb'] = ob_get_clean();
		return $fragments;
	}

	public function woocommerce_add_to_cart() {
		if ( empty( $this->options['analytics-setting'] ) ) {
			return;
		}

		if ( defined( 'WC_DOING_AJAX' ) && DOING_AJAX ) {
			//let woocommerce_ajax_added_to_cart() handle it
			return;
		}

		$items = WC()->cart->get_cart();
		$lists = $this->calculate_lists( $items );

		$skulist      = $lists['skulist'];
		$pricelist    = $lists['pricelist'];
		$quantitylist = $lists['quantitylist'];

		$src = esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-add-to-cart.js' );
		wp_enqueue_script(
			'shareasale-wc-tracker-analytics-add-to-cart',
			$src,
			array( 'shareasale-wc-tracker-analytics' ),
			$this->version
		);

		// $this->shareasale_wc_tracker_add_data(
		// 	'shareasale-wc-tracker-analytics-add-to-cart',
		// 	'var shareasaleWcTrackerAnalyticsAddToCart = ' . wp_json_encode(
		// 		array(
		// 			'skulist'      => $skulist,
		// 			'pricelist'    => $pricelist,
		// 			'quantitylist' => $quantitylist,
		// 		)
		// 	)
		// );

		wp_localize_script(
			'shareasale-wc-tracker-analytics-add-to-cart',
			'shareasaleWcTrackerAnalyticsAddToCart',
			array(
				'skulist'      => $skulist,
				'pricelist'    => $pricelist,
				'quantitylist' => $quantitylist,
			)
		);
	}

	public function woocommerce_before_checkout_form() {
		if ( empty( $this->options['analytics-setting'] ) ) {
			return;
		}

		$items = WC()->cart->get_cart();
		$lists = $this->calculate_lists( $items );

		$skulist      = $lists['skulist'];
		$pricelist    = $lists['pricelist'];
		$quantitylist = $lists['quantitylist'];

		$src   = esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-begin-checkout.js' );
		//not even sure if you can do an AJAX checkout... onepage plugins maybe?
		if ( defined( 'WC_DOING_AJAX' ) && DOING_AJAX ) {
			ob_start();
			?>
				<!-- first localize product data -->
				<script type="text/javascript">
					var shareasaleWcTrackerAnalyticsBeginCheckout = 
					<?php
					echo wp_json_encode(
						array(
							'skulist'      => $skulist,
							'pricelist'    => $pricelist,
							'quantitylist' => $quantitylist,
						)
					)
					?>;
				</script>
				<!-- run checkout began call for ShareASale analytics -->
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

			// $this->shareasale_wc_tracker_add_data(
			// 	'shareasale-wc-tracker-analytics-begin-checkout',
			// 	'var shareasaleWcTrackerAnalyticsBeginCheckout = ' . wp_json_encode(
			// 		array(
			// 			'skulist'      => $skulist,
			// 			'pricelist'    => $pricelist,
			// 			'quantitylist' => $quantitylist,
			// 		)
			// 	)
			// );

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
		if ( empty( $this->options['analytics-setting'] ) ) {
			return;
		}

		$src = esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-applied-coupon.js' );

		if ( defined( 'WC_DOING_AJAX' ) && DOING_AJAX ) {
			//this only works because of show_notice() in cart.js apply_coupon method, otherwise the <script> analytics below included in the ajax response wouldn't be added to the page at all!!
			//move to client-side cart-observer listener that kicks off ajax call instaed of server-side hook?
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

			// $this->shareasale_wc_tracker_add_data(
			// 	'shareasale-wc-tracker-analytics-applied-coupon',
			// 	'var shareasaleWcTrackerAnalyticsAppliedCoupon = ' . wp_json_encode(
			// 		array(
			// 			'couponcode' => $coupon_code,
			// 		)
			// 	)
			// );
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
		if ( empty( $this->options['analytics-setting'] ) || $prev_triggered ) {
			return;
		}

		$order       = new WC_Order( $order_id );
		$ordernumber = $order->get_order_number();

		$src  = esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-analytics-conversion.js' );

		wp_enqueue_script(
			'shareasale-wc-tracker-analytics-conversion',
			$src,
			array( 'shareasale-wc-tracker-analytics' ),
			$this->version
		);

		// $this->shareasale_wc_tracker_add_data(
		// 	'shareasale-wc-tracker-analytics-conversion',
		// 	'var shareasaleWcTrackerAnalyticsConversion = ' . wp_json_encode(
		// 		array(
		// 			'ordernumber' => $ordernumber,
		// 		)
		// 	)
		// );

		wp_localize_script(
			'shareasale-wc-tracker-analytics-conversion',
			'shareasaleWcTrackerAnalyticsConversion',
			array(
				'ordernumber' => $ordernumber,
			)
		);
	}

	// public function shareasale_wc_tracker_add_data( $handle, $json ) {
	// 	//Collect input data
	// 	$data = array();
	// 	$data[ $handle ] = $json;

	//     // Append data for relevant script handle
	//     add_filter(
	//         'script_loader_tag',
	//         function( $tag, $hndl, $src ) use ( &$data, $handle ) {
	//             // Nothing to do if no match
	//             if ( ! isset( $data[ $hndl ] ) ) {
	//                 return $tag;
	//             }

	//             // Append data
	//             $tag = sprintf(
	//                 "<script type='text/javascript' data-noptimize>\n/* <![CDATA[ */\n%s;\n/* ]]> */\n</script>" . PHP_EOL,
	//                 $data[ $hndl ]
	//             ) . $tag;

	//             return $tag;
	//         },
	// 	10, 3 );
	// }

	private function calculate_lists( $items ) {
		$last_index = array_search( end( $items ), $items, true );
		$skulist = $pricelist = $quantitylist = '';

		foreach ( $items as $index => $item ) {
			$delimiter = $index === $last_index ? '' : ',';
			//$product   = new WC_Product( $item['product_id'] );
			$product   = $item['data'];
			$sku       = $product->get_sku();

			isset( $skulist ) ? $skulist .= $sku . $delimiter : $skulist = $sku . $delimiter;

			isset( $pricelist ) ? $pricelist .= round( ( $item['line_total'] / $item['quantity'] ), 2 ) . $delimiter : $pricelist = round( ( $item['line_total'] / $item['quantity'] ), 2 ) . $delimiter;

			isset( $quantitylist ) ? $quantitylist .= $item['quantity'] . $delimiter : $quantitylist = $item['quantity'] . $delimiter;
		}

		return array( 'skulist' => $skulist, 'pricelist' => $pricelist, 'quantitylist' => $quantitylist );
	}
}
