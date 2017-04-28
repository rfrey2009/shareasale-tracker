<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Reconciler {

	private $version, $api, $logger;

	public function __construct( $version ) {
		$this->version = $version;
		$this->load_dependencies();
	}

	private function load_dependencies() {
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-wc-tracker-api.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-wc-tracker-reconciliation-logger.php';

		$settings = get_option( 'shareasale_wc_tracker_options' );

		if ( @$settings['merchant-id'] && @$settings['api-token'] && @$settings['api-secret'] && 1 == @$settings['reconciliation-setting'] ) {

			$this->api    = new ShareASale_WC_Tracker_API( $settings['merchant-id'], $settings['api-token'], $settings['api-secret'] );
			$this->logger = new ShareASale_WC_Tracker_Reconciliation_Logger( $this->version );
		}
	}

	public function woocommerce_order_partially_refunded( $order_id, $refund_id ) {
		if ( $this->api ) {
			$order   = new WC_Order( $order_id );
			$refund  = new WC_Order_Refund( $refund_id );
			$details = $this->crunch( $order, $refund );
			//if current refund does not have an impact on the subtotal, ignore
			if ( $details['current_refund'] <= 0 ) {
				return;
			}

			$req = $this->api->edit_trans(
				$details['order_number'],
				$details['order_date'],
				$details['new_amount'],
				$details['refund_reason']
			)->exec();

			if ( $req ) {
				$result = $this->api->get_response();
			} else {
				$result = $this->api->errors->get_error_message();
			}
			//if not an API error but still not a success, don't log
			if ( 'Transaction Not Found' !== $result && 'Multiple Transactions Found (ambiguous order number)' !== $result ) {
				$this->logger->log(
					'edit',
					$details['refund_reason'],
					$details['current_refund'],
					$details['previous_amount'],
					$details['new_amount'],
					$details['order_number'],
					$result,
					$details['refund_date']
				);
			}
		}
	}

	public function woocommerce_order_fully_refunded( $order_id, $refund_id ) {
		if ( $this->api ) {
			$order   = new WC_Order( $order_id );
			$refund  = new WC_Order_Refund( $refund_id );
			$details = $this->crunch( $order, $refund );
			//if current refund does not have an impact on the subtotal, ignore
			if ( $details['current_refund'] <= 0 ) {
				return;
			}

			$subtotal_finalized  = ( 0 === $details['previous_amount'] ? $details['subtotal'] : $details['previous_amount'] );

			$req = $this->api->void_trans(
				$details['order_number'],
				$details['order_date'],
				$details['refund_reason']
			)->exec();

			if ( $req ) {
				$result = $this->api->get_response();
			} else {
				$result = $this->api->errors->get_error_message();
			}
			//if not an API error but still not a success, don't log
			if ( 'Transaction Not Found' !== $result && 'Multiple Transactions Found (ambiguous order number)' !== $result ) {
				$this->logger->log(
					'void',
					$details['refund_reason'],
					$details['current_refund'],
					$subtotal_finalized,
					0.00,
					$details['order_number'],
					$result,
					$details['refund_date']
				);
			}
		}
	}

	private function crunch( $order, $refund ) {
		$order_number = $order->get_order_number();
		$order_date   = date( 'm/d/Y', strtotime( version_compare( WC()->version, '3.0' ) >= 0 ? $order->get_date_created() : $order->order_date ) );

		$grand_total       = $order->get_total();
		$total_shipping    = version_compare( WC()->version, '3.0' ) >= 0 ? $order->get_shipping_total() : $order->get_total_shipping();
		$total_taxes       = $order->get_total_tax();
		$subtotal          = $grand_total - ( $total_shipping + $total_taxes );
		$subtotal_refunded =
			$order->get_total_refunded() - ( $order->get_total_tax_refunded() + $order->get_total_shipping_refunded() );

		$new_amount        = $subtotal - $subtotal_refunded;
		if ( $new_amount < 0 ) {
			$new_amount = 0;
		}

		$previous_amount = ( $this->logger->get_previous_log_subtotal_after( $order_number ) ? $this->logger->get_previous_log_subtotal_after( $order_number ) : $subtotal );
		$current_refund  = ( $subtotal === $previous_amount ? $subtotal_refunded : round( $previous_amount - $new_amount, 3 ) );
		$refund_date     = version_compare( WC()->version, '3.0' ) >= 0 ? $refund->get_date_created() : $refund->date;
		$refund_reason   = version_compare( WC()->version, '3.0' ) >= 0 ? $refund->get_reason() : $refund->get_refund_reason();

		return array(
			'order_number'    => $order_number,
			'order_date'      => $order_date,
			'subtotal'        => $subtotal,
			'previous_amount' => $previous_amount,
			'new_amount'      => $new_amount,
			'current_refund'  => $current_refund,
			'refund_date'     => $refund_date,
			'refund_reason'   => $refund_reason,
		);
	}
}
