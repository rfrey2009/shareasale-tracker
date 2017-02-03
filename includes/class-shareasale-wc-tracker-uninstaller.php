<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Uninstaller {

	public static function uninstall() {
		global $wpdb;
		$logs_table = $wpdb->prefix . 'shareasale_wc_tracker_logs';
		//drop logs table
		$query = 'DROP TABLE ' . $logs_table;
		$wpdb->query( $query );
		//remove settings
		unregister_setting( 'shareasale_wc_tracker_options', 'shareasale_wc_tracker_options' );
		delete_option( 'shareasale_wc_tracker_options' );
	}

	public static function disable() {
		//for later use
		return;
	}
}
