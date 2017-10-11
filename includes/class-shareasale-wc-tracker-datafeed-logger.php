<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Datafeed_Logger {
	/**
	* @var string $version Plugin version
	* @var Wpdb $wpdb WordPress global database connection singleton
	* @var string $table datafeeds table name
	*/
	private $version, $wpdb, $table;

	public function __construct( $version ) {
		$this->version = $version;
		$this->load_dependencies();
	}

	private function load_dependencies() {
		global $wpdb;

		$this->wpdb  = &$wpdb;
		$this->table = $this->wpdb->prefix . 'shareasale_wc_tracker_datafeeds';
	}

	public function log( $path, $serialized_product_warnings, $product_count, $date ) {

		$log = array(
					'file'               => $path,
					'warnings'           => $serialized_product_warnings,
					'product_count'      => $product_count,
					'generation_date'    => $date,
					'generation_version' => $this->version,
				);
		$this->wpdb->insert( $this->table, $log );
		return $this->wpdb->insert_id;
	}

	public function unlog( $path ) {
		//clean up database whenever files are cleaned from disk so pagination matches
		$this->wpdb->delete( $this->table, array( 'file' => $path ) );
	}
}
