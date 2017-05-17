<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker {
	/**
	* @var ShareASale_WC_Tracker_Analytics $analytics object that controls advanced analytics (add-to-cart, coupon code, etc.) setup
	* @var ShareASale_WC_Tracker_Loader $loader Loader object that coordinates actions and filters between core plugin and admin classes
	* @var string $plugin_slug WordPress Slug for this plugin
	* @var string $version Plugin version
	*/
	private $analytics, $loader, $plugin_slug, $version;

	public function __construct( $version ) {

		$this->plugin_slug = 'shareasale-wc-tracker-slug';
		$this->version     = $version;

		$this->load_dependencies();

		$this->define_frontend_hooks();
		$this->define_admin_hooks();
		$this->define_woocommerce_hooks();
		$this->define_installer_hooks();
		$this->define_uninstaller_hooks();
	}

	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-shareasale-wc-tracker-admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-wc-tracker-pixel.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-wc-tracker-reconciler.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-wc-tracker-analytics.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-wc-tracker-loader.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-wc-tracker-installer.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-wc-tracker-uninstaller.php';

		$this->loader    = new ShareASale_WC_Tracker_Loader();
		//both define_frontend_hooks() and define_woocommerce_hooks() rely on $analytics object so instantiate it here instead
		$this->analytics = new ShareASale_WC_Tracker_Analytics( $this->version );
	}

	private function define_frontend_hooks() {
		$this->loader->add_action( 'wp_head',            $this->analytics, 'wp_head' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this->analytics, 'enqueue_scripts' );
		//analytics filter
		$this->loader->add_filter( 'script_loader_tag',  $this->analytics, 'script_loader_tag',
			array( 'priority' => 10, 'args' => 3 )
		);
	}

	private function define_admin_hooks() {
		$admin = new ShareASale_WC_Tracker_Admin( $this->version );
		$this->loader->add_action( 'admin_enqueue_scripts',     $admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts',     $admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_init',                $admin, 'admin_init' );
		$this->loader->add_action( 'admin_init',                $admin, 'plugin_upgrade' );
		$this->loader->add_action( 'admin_menu',                $admin, 'admin_menu' );
		$this->loader->add_action( 'wp_ajax_generate_datafeed', $admin, 'wp_ajax_generate_datafeed' );
		//for adding and saving custom post meta (ShareASale category/subactegory number values) to the WC products page general section
		$this->loader->add_action( 'woocommerce_product_options_general_product_data', $admin, 'woocommerce_product_options_general_product_data' );
		$this->loader->add_action( 'woocommerce_process_product_meta',                 $admin, 'woocommerce_process_product_meta' );
		//for adding and saving custom post meta ("upload to ShareASale?" checkbox) to the WC coupons page general section
		$this->loader->add_action( 'woocommerce_coupon_options', 		   $admin, 'woocommerce_coupon_options'	);
		$this->loader->add_action( 'woocommerce_coupon_options_save', $admin, 'woocommerce_coupon_options_save',
			array( 'priority' => 10, 'args' => 2 )
		);
		$this->loader->add_action( 'admin_notices', $admin, 'admin_notices' );

		//admin filters
		$this->loader->add_filter( 'plugin_action_links_' . SHAREASALE_WC_TRACKER_PLUGIN_FILENAME, $admin, 'render_settings_shortcut' );
	}

	private function define_woocommerce_hooks() {
		//conversion tracking pixel
		$pixel = new ShareASale_WC_Tracker_Pixel( $this->version );
		$this->loader->add_action( 'woocommerce_thankyou', $pixel, 'woocommerce_thankyou' );
		//automatic reconciliation
		$reconciler = new ShareASale_WC_Tracker_Reconciler( $this->version );
		$this->loader->add_action( 'woocommerce_order_partially_refunded', $reconciler, 'woocommerce_order_partially_refunded',
			array( 'priority' => 10, 'args' => 2 )
		);
		$this->loader->add_action( 'woocommerce_order_fully_refunded', $reconciler, 'woocommerce_order_fully_refunded',
			array( 'priority' => 10, 'args' => 2 )
		);
		//advanced analytics
		$this->loader->add_action( 'woocommerce_add_to_cart',          $this->analytics, 'woocommerce_add_to_cart',
			array( 'priority' => 10, 'args' => 6 )
		);
		$this->loader->add_action( 'woocommerce_ajax_added_to_cart',   $this->analytics, 'woocommerce_ajax_added_to_cart' );
		$this->loader->add_action( 'woocommerce_before_checkout_form', $this->analytics, 'woocommerce_before_checkout_form' );
		$this->loader->add_action( 'woocommerce_applied_coupon',       $this->analytics, 'woocommerce_applied_coupon' );
		//this action MUST stay priority number lower than the woocommerce_thankyou $pixel action above, so it executes BEFORE post meta is added to the order
		$this->loader->add_action( 'woocommerce_thankyou',             $this->analytics, 'woocommerce_thankyou',
			array( 'priority' => 9, 'args' => 1 )
		);
	}

	private function define_installer_hooks() {
	    register_activation_hook( SHAREASALE_WC_TRACKER_PLUGIN_FILENAME, array( 'ShareASale_WC_Tracker_Installer', 'install' ) );
	}

	private function define_uninstaller_hooks() {
		register_deactivation_hook( SHAREASALE_WC_TRACKER_PLUGIN_FILENAME, array( 'ShareASale_WC_Tracker_Uninstaller', 'disable' ) );
	    register_uninstall_hook( SHAREASALE_WC_TRACKER_PLUGIN_FILENAME, array( 'ShareASale_WC_Tracker_Uninstaller', 'uninstall' ) );
	}

	public function run() {
		$this->loader->run();
	}
}
