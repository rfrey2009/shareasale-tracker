=== ShareASale WooCommerce Tracker ===
Tags: Affiliate, marketing, ShareASale, tracking, WooCommerce
Requires at least: 3.0.1
Tested up to: 4.7
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The ShareASale WooCommerce Tracker sets up the tracking pixel on a Merchant's cart and allows commission auto-edits if customers are refunded.

== Installation ==

1. Login to ShareASale and jot down your Merchant ID. It's printed at the top of the Merchant dashboard in ShareASale.

2. In the plugin's settings "Tracking Settings" tab (star icon on the WordPress sidebar), enter your Merchant ID and click "Save Settings." This is all that's required to setup basic ShareASale conversion tracking in your WooCommerce cart. See below for optional features. 

3. OPTIONAL:

a. If you are on ShareASale's StoresConnect system, you should have a store ID number assigned for each store/site in your Affiliate program. On the "Tracking Settings" tab, enter the store ID for the site you're installing tracking using this plugin. If you are not on ShareASale's StoresConnect, simply leave this field blank.

b. If you would like to pass ShareASale special information about each Affiliate-referred sale not normally captured in our standard tracking pixel solution, you can use the "Merchant-Defined Type" drop-down settings. 

For example, if you chose "Device Type (mobile or desktop)" ShareASale would track the customer's device type, and inside ShareASale you could setup varying commission rule adjustments based on this device type. Feel free to send us any ideas for new merchant-defined types as plugin feedback to shareasale@shareasale.com.

c. To use automatic reconciliation between your WooCommerce cart and ShareASale so Affiliate commissions are automatically edited anytime you refund customers' orders, you'll need to add a few more settings.

i. Login to ShareASale and visit Tools >> Merchant API page. If it's not enabled, click "enable API." Otherwise, find your API TOKEN and API KEY at the top of the page. Copy these so you can paste them into the plugin settings (iii below).

ii. While still on that Merchant API page, change the "IP Address" drop-down setting to "Require IP address match for versions 1.1 and lower." You can also keep the default setting ("Require IP address match for all API calls") if you know your site's hosting IP address and can enter it above the token field. In either case, press "Update Settings" when finished.

iii. In the plugin's settings page "Automate Reconciliation" tab back in WordPress, check the "Automate" box and then input your API settings (key/token) respective fields. Save your settings. If there is an error saving your settings and a red warning is at the top, contact ShareASale support (shareasale@shareasale.com) for assistance.

iv. Any automatically edited or voided sales in ShareASale will be logged in the table at the bottom of this tab for reference.

== Changelog ==

= 1.0 =
* Initial release
* Send any feedback to shareasale@shareasale.com, subj: attn Ryan - tech team