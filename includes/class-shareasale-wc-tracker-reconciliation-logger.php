<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Reconciliation_Logger {
	/**
	* @var string $version Plugin version
	* @var Wpdb $wpdb WordPress global database connection singleton
	* @var string $table logs table name
	*/
	private $version, $wpdb, $table;

	public function __construct( $version ) {
		$this->version = $version;
		$this->load_dependencies();
	}

	private function load_dependencies() {
		global $wpdb;

		$this->wpdb  = &$wpdb;
		$this->table = $this->wpdb->prefix . 'shareasale_wc_tracker_logs';
	}

	public function log( $type, $reason, $deducted, $subtotal_before, $subtotal_after, $order_number, $response, $refund_date ) {

		$log = array(
					'action'          => $type,
					'reason'          => $reason,
					'deducted'        => $deducted,
					'subtotal_before' => $subtotal_before,
					'subtotal_after'  => $subtotal_after,
					'order_number'    => $order_number,
					'response'        => $response,
					'refund_date'     => $refund_date,
				);
		$this->wpdb->insert( $this->table, $log );
	}

	public function get_previous_log_subtotal_after( $order_number ) {

		$previous_subtotal_after = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"
				SELECT subtotal_after
				FROM {$this->table}
				WHERE order_number = %s
				ORDER BY id DESC
				LIMIT 0,1
				",
				$order_number
			)
		);

		if ( is_null( $previous_subtotal_after ) ) {
			$previous_subtotal_after = 0;
		}
		return $previous_subtotal_after;
	}
}
