<?php
class ShareASale_Tracker_Reconciler {

	private $api, $logger, $version;

	public function __construct( $version ) {
		$this->version = $version;
		$this->load_dependencies();
	}

	private function load_dependencies() {
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-tracker-api.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-tracker-reconciliation-logger.php';

		$settings = get_option( 'tracker_options' );

		if ( $settings['merchant-id'] && $settings['api-token'] && $settings['api-secret'] ) {
			$this->api = new ShareASale_Tracker_API( $settings['merchant-id'], $settings['api-token'], $settings['api-secret'] );
		}
		$this->logger = new ShareASale_Tracker_Reconciliation_Logger( $this->version );
	}

	//ShareASale_Tracker_API method signatures
	//edit_trans( $order_number, $date, $new_amount )
	//void_trans( $order_number, $date, $reason = '' )
	public function woocommerce_order_partially_refunded( $order_id, $refund_id ) {
		if ( $this->api ) {
			$order        = new WC_Order( $order_id );
			$order_number = $order->get_order_number();
			$order_date   = date( 'm/d/Y', strtotime( $order->order_date ) );

			$grand_total       = $order->get_total();
			$total_shipping    = $order->get_total_shipping();
			$total_taxes       = $order->get_total_tax();
			$subtotal          = $grand_total - ( $total_shipping + $total_taxes );
			$subtotal_refunded =
				$order->get_total_refunded() - ( $order->get_total_tax_refunded() + $order->get_total_shipping_refunded() );

			$new_amount        = $subtotal - $subtotal_refunded;
			if ( $new_amount < 0 ) {
				$new_amount = 0;
			}
			$previous_new_amount = $this->logger->get_previous_log_subtotal_after( $order_number );

			$refund         = new WC_Order_Refund( $refund_id );
			$current_refund = ( 0 === $previous_new_amount ? $subtotal_refunded : $previous_new_amount - $new_amount );
			$refund_date    = $refund->date;
			$refund_reason  = $refund->get_refund_reason();

			if ( 0 !== $current_refund ) {

				$req = $this->api->edit_trans( $order_number, $order_date, $new_amount )->exec();
				if ( $req ) {
					$result = $this->api->get_response();
				} else {
					$result = $this->api->get_error_msg();
				}

				$this->logger->log_reconcile(
					'edit', //edit or void
					$refund_reason,
					$current_refund, //deducted
					($new_amount + $current_refund), //subtotal before
					$new_amount, //subtotal after
					$order_number,
					$result, //api response
					$refund_date
				);
			}
		}
	}

	public function woocommerce_order_fully_refunded( $order_id, $refund_id ) {
		if ( $this->api ) {
			$order          = new WC_Order( $order_id );
			$order_number   = $order->get_order_number();
			$order_date     = date( 'm/d/Y', strtotime( $order->order_date ) );

			$refund         = new WC_Order_Refund( $refund_id );
			$current_refund = $this->logger->get_previous_log_subtotal_after( $order_number );
			$refund_date    = $refund->date;
			$refund_reason  = $refund->get_refund_reason();
			$subtotal       = $current_refund;

			$req = $this->api->void_trans( $order_number, $order_date, $refund_reason )->exec();
			if ( $req ) {
				$result = $this->api->get_response();
			} else {
				$result = $this->api->get_error_msg();
			}

			$this->logger->log_reconcile(
				'void',
				$refund_reason,
				$current_refund,
				$subtotal,
				0.00,
				$order_number,
				$result,
				$refund_date
			);
		}
	}
}
