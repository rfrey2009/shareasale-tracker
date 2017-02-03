<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker {
	/**
	* @var ShareASale_WC_Tracker_Analytics $analytics object that controls advanced analytics (add-to-cart, coupon code, etc.) setup
	* @var ShareASale_WC_Tracker_Loader $loader Loader object that coordinates actions and filters between core plugin and admin classes
	* @var string $plugin_slug WordPress Slug for this plugin
	* @var float $version Plugin version
	*/
	protected $analytics, $loader, $plugin_slug, $version;

	public function __construct() {

		$this->plugin_slug = 'shareasale-wc-tracker-slug';
		$this->version     = '1.1';

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
		$this->analytics = new ShareASale_WC_Tracker_Analytics( $this->get_version() );
	}

	private function define_frontend_hooks() {
		$this->loader->add_action( 'wp_enqueue_scripts', $this->analytics, 'enqueue_scripts' );
	}

	private function define_admin_hooks() {
		$admin = new ShareASale_WC_Tracker_Admin( $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_init',            $admin, 'admin_init' );
		$this->loader->add_action( 'admin_menu',            $admin, 'admin_menu' );
		//admin filters
		$this->loader->add_filter( 'plugin_action_links_' . SHAREASALE_WC_TRACKER_PLUGIN_FILENAME, $admin, 'render_settings_shortcut' );
	}

	private function define_woocommerce_hooks() {
		//conversion tracking pixel
		$pixel = new ShareASale_WC_Tracker_Pixel( $this->get_version() );
		$this->loader->add_action( 'woocommerce_thankyou', $pixel, 'woocommerce_thankyou' );
		//automatic reconciliation
		$reconciler = new ShareASale_WC_Tracker_Reconciler( $this->get_version() );
		$this->loader->add_action( 'woocommerce_order_partially_refunded', $reconciler, 'woocommerce_order_partially_refunded',
			array( 'priority' => 10, 'args' => 2 )
		);
		$this->loader->add_action( 'woocommerce_order_fully_refunded', $reconciler, 'woocommerce_order_fully_refunded',
			array( 'priority' => 10, 'args' => 2 )
		);
		//advanced analytics
		$this->loader->add_action( 'woocommerce_add_to_cart', $this->analytics, 'woocommerce_add_to_cart',
			array( 'priority' => 10, 'args' => 6 )
		);
		$this->loader->add_action( 'woocommerce_checkout_init',  $this->analytics, 'woocommerce_checkout_init' );
		$this->loader->add_action( 'woocommerce_applied_coupon', $this->analytics, 'woocommerce_applied_coupon' );
		$this->loader->add_action( 'woocommerce_thankyou',       $this->analytics, 'woocommerce_thankyou' );
		//analytics filter
		$this->loader->add_filter( 'script_loader_tag',          $this->analytics, 'script_loader_tag',
			array( 'priority' => 10, 'args' => 3 )
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

	public function get_version() {
		return $this->version;
	}
}
