<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Pixel {

	/**
	* @var WC_Order $order WooCommere order object https://docs.woothemes.com/wc-apidocs/class-WC_Order.html
	* @var string $version Plugin version
	*/

	private $order, $version;

	public function __construct( $version ) {
		$this->version = $version;

		return $this;
	}

	public function script_loader_tag( $tag, $handle, $src ) {
		$async_scripts = array( 'shareasale-wc-tracker-analytics-second-chance' );
		$other_scripts = array(
			'shareasale-wc-tracker-admin-js',
			'shareasale-wc-tracker-notices-js',
			'shareasale-wc-tracker-analytics',
			'shareasale-wc-tracker-analytics-cart-observer',
			'shareasale-wc-tracker-analytics-add-to-cart',
			'shareasale-wc-tracker-analytics-begin-checkout',
			'shareasale-wc-tracker-analytics-applied-coupon',
			'shareasale-wc-tracker-analytics-conversion',
			'shareasale-wc-tracker-triggered',
			'shareasale-wc-tracker-pixel',
		);

		if ( in_array( $handle, $async_scripts, true ) ) {
			return '<script type="text/javascript" src="' . $src . '" defer async data-noptimize></script>' . "\n";
		} else {
			return $tag;
		}

		if ( in_array( $handle, $other_scripts, true ) ) {
			return '<script type="text/javascript" src="' . $src . '" data-noptimize></script>' . "\n";
		} else {
			return $tag;
		}
	}

	public function woocommerce_thankyou( $order_id ) {
		$options        = get_option( 'shareasale_wc_tracker_options' );
		$merchant_id    = @$options['merchant-id'];
		$store_id       = @$options['store-id'];
		$xtype          = @$options['xtype'];
		$prev_triggered = get_post_meta( $order_id, 'shareasale-wc-tracker-triggered', true );

		if ( ! $order_id || ! $merchant_id ) {
			echo '<!-- no ShareASale merchant ID entered or order ID doesn\'t exist-->';
			return;
		}
		//allow &troubleshooting=1 so tech/launch team can view past referrer URLs and check for pixel presence. Still doesn't fire second-chance or advanced analytics though
		if ( $prev_triggered && ! isset( $_GET['troubleshooting'] ) ) {
			echo '<!-- ShareASale pixel was previously triggered -->';
			return;
		}

		if ( $store_id ) {
			$store_id = '&storeID=' . $store_id;
		}

		$this->order = new WC_Order( $order_id );

		switch ( $xtype ) {
			case 'customer_billing_country_code':
				$xtype = '&xtype=' . ( version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_billing_country() : $this->order->billing_country );
				break;

			case 'customer_billing_state_code':
				$xtype = '&xtype=' . ( version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_billing_state() : $this->order->billing_state );
				break;

			case 'customer_billing_city_code':
				$xtype = '&xtype=' . ( version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_billing_city() : $this->order->billing_city );
				break;

			case 'customer_shipping_country_code':
				$xtype = '&xtype=' . ( version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_shipping_country() : $this->order->shipping_country );
				break;

			case 'customer_shipping_state_code':
				$xtype = '&xtype=' . ( version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_shipping_state() : $this->order->shipping_state );
				break;

			case 'customer_shipping_city_code':
				$xtype = '&xtype=' . ( version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_shipping_city() : $this->order->shipping_city );
				break;

			case 'customer_id':
				$xtype = '&xtype=' . $this->order->get_user_id();
				break;

			case 'customer_device_type':
				$xtype = '&xtype=' . ( wp_is_mobile() ? 'mobile' : 'desktop' );
				break;

			case 'payment_type':
				$xtype = '&xtype=' . ( version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_payment_method_title() : $this->order->payment_method_title );
				break;

			case 'payment_shipping':
				$xtype = '&xtype=' . $this->order->get_shipping_method();
				break;

			default:
				$xtype = '&xtype=';
		}

		$product_data = $this->get_product_data();

		$params = array(
				'amount'       => $this->get_order_amount(),
				'tracking'     => $this->order->get_order_number(),
				'transtype'    => 'sale',
				'merchantID'   => $merchant_id,
				'skulist'      => $product_data->skulist,
				'quantitylist' => $product_data->quantitylist,
				'pricelist'    => $product_data->pricelist,
				'couponcode'   => $this->get_coupon_codes(),
				'currency'     => $this->get_currency(),
				'newcustomer'  => $this->get_customer_status(),
				'v'            => $this->version,
			);

		$query_string = '?' . http_build_query( $params );
		$url          = 'https://shareasale.com/sale.cfm' . $query_string . $store_id . $xtype;
		//backup pixel if JS is disabled (rare)
		$noscript_pixel = '<noscript><img id = "_SHRSL_img_1" src = "' . $url . '" width = "1" height = "1"></noscript>';
		echo wp_kses( $noscript_pixel, array(
									'noscript' => array(),
									'img' => array(
										'id'           => true,
										'src'          => true,
										'width'        => true,
										'height'       => true,
									),
								)
		);

		//updates post meta client-side for this order to mark it as pixel displayed
		$src = esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-triggered.js' );
		wp_enqueue_script(
			'shareasale-wc-tracker-triggered',
			$src,
			array( 'jquery' ),
			$this->version
		);
		//called post_id here because that's really the WPDB entry that's meta is being updated
		wp_localize_script(
			'shareasale-wc-tracker-triggered',
			'shareasaleWcTrackerTriggeredData',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wp_ajax_shareasale_wc_tracker_triggered' ),
				'post_id' => $order_id,
			)
		);
		//add the actual tracking pixel to the page via JS
		$src2 = esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-pixel.js' );
		wp_enqueue_script(
			'shareasale-wc-tracker-pixel',
			$src2,
			array( 'shareasale-wc-tracker-triggered' ),
			$this->version
		);

		wp_localize_script(
			'shareasale-wc-tracker-pixel',
			'shareasaleWcTrackerPixel',
			array(
				'src'    => $url,
				'onload' => 'shareasaleWcTrackerTriggered()',
				'id'     => '_SHRSL_img_1',
			)
		);
		//second-chance pixel domain swap in case of adblockers
		$src3 = esc_url( 'https://shareasale-analytics.com/j.js' );
		wp_enqueue_script(
			'shareasale-wc-tracker-analytics-second-chance',
			$src3,
			array( 'shareasale-wc-tracker-pixel' ),
			$this->version
		);
	}

	public function wp_ajax_nopriv_shareasale_wc_tracker_triggered() {
		$this->wp_ajax_shareasale_wc_tracker_triggered();
	}

	public function wp_ajax_shareasale_wc_tracker_triggered() {
		$nonce    = wp_verify_nonce( $_POST['nonce'], 'wp_ajax_shareasale_wc_tracker_triggered' );
		$order_id = intval( $_POST['post_id'] );
		if ( $nonce && $order_id ) {
			add_post_meta( $order_id, 'shareasale-wc-tracker-triggered', date( 'Y-m-d H:i:s' ), true );
			wp_send_json( array( 'order_id' => $order_id ) );
		} else {
			wp_send_json( array( 'order_id' => false ) );
		}
	}

	private function get_order_amount() {

		$grand_total    = $this->order->get_total();
		$total_shipping = version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_shipping_total() : $this->order->get_total_shipping();
		$total_taxes    = $this->order->get_total_tax();
		$subtotal       = $grand_total - ( $total_shipping + $total_taxes );

		if ( $subtotal < 0 ) {
			$subtotal = 0;
		}

		return $subtotal;
	}

	private function get_product_data() {

		$product_data = new stdClass();

		$items = $this->order->get_items();
		$last_index = array_search( end( $items ), $items, true );

		foreach ( $items as $index => $item ) {
			$delimiter = $index === $last_index ? '' : ',';
			$product   = 0 != $item['variation_id'] ? new WC_Product_Variation( $item['variation_id'] ) : new WC_Product( $item['product_id'] );
			$sku       = $product->get_sku();

			isset( $product_data->skulist ) ? $product_data->skulist .= $sku . $delimiter : $product_data->skulist = $sku . $delimiter;

			isset( $product_data->pricelist ) ? $product_data->pricelist .= round( ( $item['line_total'] / $item['qty'] ), 2 ) . $delimiter : $product_data->pricelist = round( ( $item['line_total'] / $item['qty'] ), 2 ) . $delimiter;

			isset( $product_data->quantitylist ) ? $product_data->quantitylist .= $item['qty'] . $delimiter : $product_data->quantitylist = $item['qty'] . $delimiter;
		}

		return $product_data;

	}

	private function get_customer_status() {
		$newcustomer = '';
		if ( method_exists( $this->order, 'get_user_id' ) ) {

			$customer_user_id = $this->order->get_user_id();
			if ( 0 !== $customer_user_id ) {
				$user_orders = get_posts(
					array(
						'post_type'   => wc_get_order_types(),
						'meta_key'    => '_customer_user',
						'meta_value'  => $customer_user_id,
						'numberposts' => -1,
						'post_status' => array_keys( wc_get_order_statuses() ),
					)
				);
				$order_count = count( $user_orders );
				$newcustomer = ($order_count > 1 ? 0 : 1);
			}
		}

		return $newcustomer;

	}

	private function get_coupon_codes() {

		$couponcode = implode( ', ', $this->order->get_used_coupons() );

		return $couponcode;
	}

	private function get_currency() {

		$currency = version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_currency() : $this->order->get_order_currency();

		return $currency;
	}
}
