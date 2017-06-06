<?php
/*
 Plugin Name:       ShareASale WooCommerce Tracker
 Author:			ShareASale.com, Inc.
 Description:       Setup ShareASale's Affiliate network's tracking in WooCommerce, automate reconciliation, and generate datafeeds
 Version:           1.1.3
 Depends:  			WooCommerce
 License:           GPL-2.0+
 License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

//don't allow access from a web browser
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'SHAREASALE_WC_TRACKER_PLUGIN_FILENAME', plugin_basename( __FILE__ ) );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-shareasale-wc-tracker.php';

function run_shareasale_wc_tracker( $version ) {
	$shareasale_wc_tracker = new ShareASale_WC_Tracker( $version );
	$shareasale_wc_tracker->run();
}

$version = '1.1.3';
run_shareasale_wc_tracker( $version );
