<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Uninstaller {

	public static function uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		global $wpdb;
		$logs_table      = $wpdb->prefix . 'shareasale_wc_tracker_logs';
		$datafeeds_table = $wpdb->prefix . 'shareasale_wc_tracker_datafeeds';
		//drop tables
		$query = 'DROP TABLE ' . $logs_table . ', ' . $datafeeds_table;
		$wpdb->query( $query );
		//remove settings
		unregister_setting( 'shareasale_wc_tracker_options', 'shareasale_wc_tracker_options' );
		delete_option( 'shareasale_wc_tracker_options' );
		delete_option( 'shareasale_wc_tracker_version' );
		delete_option( 'shareasale_wc_tracker_generate_scheduled_datafeed_ftp_failed' );
		//unschedule possible automated product datafeed FTP upload
		wp_unschedule_event( wp_next_scheduled( 'shareasale_wc_tracker_generate_scheduled_datafeed' ), 'shareasale_wc_tracker_generate_scheduled_datafeed' );
	}

	public static function disable() {
		//for later use
		return;
	}
}
