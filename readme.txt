=== ShareASale WooCommerce Tracker ===
Tags: Affiliate, marketing, ShareASale, tracking, WooCommerce
Requires at least: 3.0.1
Tested up to: 4.7
Stable tag: 1.1.3
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

d. If you would like to create a product datafeed file for upload to ShareASale, go to the "Datafeed Generation" tab. With a few easy clicks our plugin will generate a basic product datafeed file you can upload to Creatives >> Datafeed in ShareASale. Be sure to review the errors/warnings after generating a product datafeed in case you need to make some fixes yourself. See this blog post for more information on the importance of a product datafeed.

http://blog.shareasale.com/2014/04/21/free-slide-deck-from-shareasales-datafeed-tune-up-webinar/

You can view or re-download the product datafeed files you've generated in the past 30 days in the table at the bottom of this tab.

e. If you're interested in our Conversion Lines feature that lets Merchants setup advanced Affiliate attribution beyond just standard last-click crediting, go to the "Advanced Analytics" tab. You can enter your advanced analytics passkey or if you do not have one yet, request it by contacting ShareASale support (shareasale@shareasale.com), subj: attn Ryan - tech team.

For example, Affiliates can have their credit determined by when items are added to cart, whether coupons are used, or if other special Affiliates also referred a click. Read more on our Conversion Lines feature here:

http://blog.shareasale.com/2015/02/04/conversion-lines-where-the-tracking-gap-ends/

== Changelog ==

= 1.1.3 =

* Added new default category and subcategory number options for products in the datafeed generation tab.
* Added an option to automatically send ShareASale your WooCommerce coupons so your Affiliates can promote them. Check the "send to ShareASale" box while editing a WooCommerce coupon.

= 1.1.2 =
* Minor tweaks for users with older versions of PHP (v5.3 - 5.5) or mistakenly orphaned product variations.

= 1.1.1 =
* Updated admin menu bar icon to use WordPress standard dashicons-star-filled instead of yellow ShareASale star logo.

= 1.1 =
* Second release. Compatible with WooCommerce 3.0!
* Added product datafeed generation to help Merchants create product datafeed files, a useful type of creative asset Affiliates can use to promote individual products.
* Added advanced analytics, which lets ShareASale track various pre-conversion cart events (coupon added, items added to cart, etc). Useful with the ShareASale Conversion Lines feature.
* General bug fixes and improvements under the hood.
* Send any feedback to shareasale@shareasale.com, subj: attn Ryan - tech team.

= 1.0 =
* Initial release.
* Send any feedback to shareasale@shareasale.com, subj: attn Ryan - tech team.