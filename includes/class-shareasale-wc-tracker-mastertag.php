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
		/*changed src as of 1.4.5 in favor of Awin's master tag
		$src = esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-mastertag.js' );
		*/

		$mastertag = get_option( 'shareasale_wc_tracker_mastertag', array() );
		$awin_id   = empty( $mastertag['id'] ) || ! is_numeric( $mastertag['id'] ) ? 19038 : $mastertag['id'];

		$src = esc_url( 'https://www.dwin1.com/' . $awin_id . '.js' );
		wp_enqueue_script(
			'shareasale-wc-tracker-mastertag',
			$src,
			is_order_received_page() ? array( 'shareasale-wc-tracker-pixel' ) : array(),
			$this->version
		);
	}
}
