<?php
class ShareASale_Tracker_Installer {

	private static function load_dependencies() {
		//necessary for using dbDelta() to create and update WordPress tables
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	}

	public static function install() {
		self::load_dependencies();

		add_option( 'tracker_options', '' );

		global $wpdb;
		$logs_table = $wpdb->prefix . 'shareasale_tracker_logs';
		$query = 'CREATE TABLE ' . $logs_table . ' (
			id int(11) NOT NULL AUTO_INCREMENT,
			action varchar(20) DEFAULT NULL,
			reason varchar(255) NOT NULL,
			deducted decimal(9,2) NOT NULL,
			subtotal_before decimal(9,2) NOT NULL,
			subtotal_after decimal(9,2) NOT NULL,
			order_number int(11) NOT NULL,
			response varchar(255) NOT NULL,
			date datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY  date (date)
			) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=latin1';

		dbDelta( $query );
	}
}
