<?php
class ShareASale_Tracker_Reconciler {

	private $api;

	public function __construct( $version ) {
		$this->version = $version;
		$this->load_dependencies();
	}

	private function load_dependencies() {
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-tracker-api.php';
	}

	public function woocommerce_order_partially_refunded() {

	}

	public function woocommerce_order_fully_refunded() {

	}
}
