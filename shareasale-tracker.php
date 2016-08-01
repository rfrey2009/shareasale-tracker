<?php
/*
 Plugin Name:       ShareASale Tracker
 Author:			ShareASale.com, Inc.
 Description:       Setup ShareASale's Affiliate network's conversion tracking in WooCommerce
 Version:           1.0
 Depends:  			WooCommerce
 License:           GPL-2.0+
 License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

//don't allow access from a web browser
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'SHAREASALE_TRACKER_PLUGIN_FILENAME', plugin_basename( __FILE__ ) );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-shareasale-tracker.php';

function run_shareasale_tracker() {
	$shareasale_tracker = new ShareASale_Tracker();
	$shareasale_tracker->run();
}

run_shareasale_tracker();
