<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Installer {

	private static function load_dependencies() {
		//necessary for using dbDelta() to create and update WordPress tables
		require_once( trailingslashit( ABSPATH ) . 'wp-admin/includes/upgrade.php' );
	}

	public static function install() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		self::load_dependencies();

		add_option( 'shareasale_wc_tracker_options', '' );
		//version will be purposely empty at first install even if up to date.
		//can't pass version arg to register_activation_hook...
		add_option( 'shareasale_wc_tracker_version', '' );

		global $wpdb;
		$logs_table = $wpdb->prefix . 'shareasale_wc_tracker_logs';
		$query      =
			'CREATE TABLE IF NOT EXISTS ' . $logs_table . ' (
			id int(11) NOT NULL AUTO_INCREMENT,
			action varchar(20) DEFAULT NULL,
			reason varchar(255) NOT NULL,
			deducted decimal(9,3) NOT NULL,
			subtotal_before decimal(9,3) NOT NULL,
			subtotal_after decimal(9,3) NOT NULL,
			order_number varchar(255) NOT NULL,
			response varchar(255) NOT NULL,
			refund_date datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY date (refund_date)
			) ENGINE=InnoDB';

		dbDelta( $query );

		$datafeeds_table = $wpdb->prefix . 'shareasale_wc_tracker_datafeeds';
		$query           =
			'CREATE TABLE IF NOT EXISTS ' . $datafeeds_table . ' ( 
			id INT(11) NOT NULL AUTO_INCREMENT,
			file VARCHAR(255) NOT NULL,
			warnings BLOB NOT NULL,
			product_count INT(7) NOT NULL, 
			generation_date DATETIME NOT NULL,
			generation_version varchar(20) NOT NULL,
			ftp_uploaded TINYINT(1) NOT NULL,
			PRIMARY KEY  (id)
			) ENGINE = InnoDB';

		dbDelta( $query );
	}

	/**
	* @var string $old_version what's stored in the db for wp_option
	* @var string $latest_version what's been instantiated in the plugin's class
	*/
	public static function upgrade( $old_version, $latest_version ) {
		self::load_dependencies();
		global $wpdb;
		$datafeeds_table = $wpdb->prefix . 'shareasale_wc_tracker_datafeeds';
		/* begin generic always-do upgrade routines
		* 1. clean out the datafeeds log table, because WordPress overwrites /admin/datafeed files on upgrades anyway, otherwise the HTML log table's pagination could get messed up
		*/
		$query = "TRUNCATE $datafeeds_table";
		$wpdb->query( $query );
		/* end generic always-do upgrade routines */

		/* begin upgrade to 1.3
		* don't need previous 1.1 upgrade code since it dealt with creating same datafeeds table, but without the last ftp_uploaded column
		* sql query below doesn't use "IF NOT EXISTS" anymore, since we're both altering the table and maybe creating it for the first time
		* dbDelta only uses create and update queries, not alter. It figures out "delta" changes itself and is very picky with syntax...
		*/
		if ( -1 === version_compare( $old_version, '1.3.1' ) ) {
			$query           =
				'CREATE TABLE ' . $datafeeds_table . ' ( 
				id INT(11) NOT NULL AUTO_INCREMENT,
				file VARCHAR(255) NOT NULL,
				warnings BLOB NOT NULL,
				product_count INT(7) NOT NULL, 
				generation_date DATETIME NOT NULL,
				generation_version varchar(20) NOT NULL,
				ftp_uploaded TINYINT(1) NOT NULL DEFAULT "0",
				PRIMARY KEY  (id)
				) ENGINE = InnoDB';

			dbDelta( $query );
		}
		/* end upgrade to 1.3 */
		update_option( 'shareasale_wc_tracker_version', $latest_version );
	}
}
