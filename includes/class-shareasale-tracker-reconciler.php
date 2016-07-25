<?php
class ShareASale_Tracker_Reconciler {

	private $api;

	public function __construct( $version ) {
		$this->version = $version;
		$this->load_dependencies();
	}

	private function load_dependencies() {
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-tracker-api.php';
		$settings = get_option( 'tracker_options' );

		if ( $settings['merchant-id'] && $settings['api-token'] && $settings['api-secret'] ) {
			$this->api = new ShareASale_Tracker_API( $settings['merchant-id'], $settings['api-token'], $settings['api-secret'] );
		}
	}
	//edit_trans( $order_number, $date, $new_amount )
	//void_trans( $order_number, $date, $reason = '' )

	public function woocommerce_order_partially_refunded( $order_id ) {
		if ( $this->api ) {
			$order  = new WC_Order( $order_id );

			$grand_total      = $order->get_total();
			$total_shipping   = $order->get_total_shipping();
			$total_taxes      = $order->get_total_tax();
			$subtotal         = $grand_total - ( $total_shipping + $total_taxes );
			$sas_refunded =
				$order->get_total_refunded() - ( $order->get_total_tax_refunded() + $order->get_total_shipping_refunded() );

			$new_amount = $subtotal - $sas_refunded;
			$date       = date( 'm/d/Y', strtotime( $order->order_date ) );

			$req = $this->api->edit_trans( $order->get_order_number(), $date, $new_amount )->exec();
			if ( $req ) {
				$result = $this->api->get_response();
				error_log( $result );
			} else {
				$result = $this->api->get_error_msg();
				error_log( $result );
			}

			error_log( "\n" );
			$query = $this->api->get_last_query();
			error_log( $query );
		}
	}

	public function woocommerce_order_fully_refunded( $order_id, $refund_id ) {
		if ( $this->api ) {
			$order  = new WC_Order( $order_id );
			$refund = new WC_Order_Refund( $refund_id );
			$date   = date( 'm/d/Y', strtotime( $order->order_date ) );

			$req = $this->api->void_trans( $order->get_order_number(), $date, $refund->get_refund_reason() )->exec();
			if ( $req ) {
				$result = $this->api->get_response();
				error_log( $result );
			} else {
				$result = $this->api->get_error_msg();
				error_log( $result );
			}

			error_log( "\n" );
			$query = $this->api->get_last_query();
			error_log( $query );
		}
	}
}
