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
/*todo:
*add pagination to automatic reconciliation log table. re-sorting too?
*finish sanitization
*finish xtype
*make sure automatic reconciliation only happens if user turned it on
*/
define( 'SHAREASALE_TRACKER_PLUGIN_FILENAME', plugin_basename( __FILE__ ) );
/*
* require the core plugin class
*/
require_once plugin_dir_path( __FILE__ ) . 'includes/class-shareasale-tracker.php';

/**
* Kicks off the plugin init
*/
function run_shareasale_tracker() {
	$shareasale_tracker = new ShareASale_Tracker();
	$shareasale_tracker->run();
}

run_shareasale_tracker();
