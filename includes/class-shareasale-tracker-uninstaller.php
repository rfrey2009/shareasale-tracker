<?php
class ShareASale_Tracker_Uninstaller {

	public static function uninstall() {
		global $wpdb;
		$logs_table = $wpdb->prefix . '_shareasale_tracker_logs';
		//drop logs table
		$query = 'DROP TABLE ' . $logs_table;
		$wpdb->query( $query );
		//remove settings
		unregister_setting( 'tracker_options','tracker_options' );
		delete_option( 'tracker_options' );
	}

	public static function disable() {
		//for later use
		return;
	}
}
