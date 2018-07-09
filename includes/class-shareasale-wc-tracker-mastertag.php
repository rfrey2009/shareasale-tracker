<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Mastertag {

	/**
	* @var string $version Plugin version
	*/

	private $version;

	public function __construct( $version ) {
		$this->version = $version;
	}	

	public function enqueue_scripts( $hook ) {
		//required mastertag on every page
		$src = esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-mastertag.js' );

		wp_enqueue_script(
			'shareasale-wc-tracker-mastertag',
			$src,
			array(),
			$this->version
		);
	}
}
