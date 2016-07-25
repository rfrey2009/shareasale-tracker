<?php
class ShareASale_Tracker {
	/**
	* @var ShareASale_Tracker_Loader $loader Loader object that coordinates actions and filters between core plugin and admin classes
	* @var string $plugin_slug WordPress Slug for this plugin
	* @var float $version Plugin version
	*/
	protected $loader, $plugin_slug, $version;

	public function __construct() {

		$this->plugin_slug = 'shareasale-tracker-slug';
		$this->version     = '1.0';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_woocommerce_hooks();
		$this->define_installer_hooks();
		$this->define_uninstaller_hooks();
	}

	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-shareasale-tracker-admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-tracker-pixel.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-tracker-reconciler.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-tracker-loader.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-tracker-installer.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-tracker-uninstaller.php';
		$this->loader = new ShareASale_Tracker_Loader();
	}

	private function define_admin_hooks() {
		$admin = new ShareASale_Tracker_Admin( $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_init',            $admin, 'admin_init' );
		$this->loader->add_action( 'admin_menu',            $admin, 'admin_menu' );
	}

	private function define_woocommerce_hooks() {
		//generates conversion tracking pixel
		$pixel = new ShareASale_Tracker_Pixel( $this->get_version() );
		$this->loader->add_action( 'woocommerce_thankyou', $pixel, 'woocommerce_thankyou' );
		/*does optional edits/voids automatically
		*1. setup new settings for api credentials and reconciliaton on/off
		*2. setup automatic reconciliation
		*3. setup logging of reconciliation attempts
		*4. display reconciliation logging to users
		*/
		$reconciler = new ShareASale_Tracker_Reconciler( $this->get_version() );
		$this->loader->add_action( 'woocommerce_order_partially_refunded', $reconciler, 'woocommerce_order_partially_refunded',
			array( 'priority' => 10, 'args' => 2 )
		);
		$this->loader->add_action( 'woocommerce_order_fully_refunded',     $reconciler, 'woocommerce_order_fully_refunded',
			array( 'priority' => 10, 'args' => 2 )
		);
	}

	private function define_installer_hooks() {
	    register_activation_hook( SHAREASALE_TRACKER_PLUGIN_FILENAME, array( 'ShareASale_Tracker_Installer', 'install' ) );
	}

	private function define_uninstaller_hooks() {
		register_deactivation_hook( SHAREASALE_TRACKER_PLUGIN_FILENAME, array( 'ShareASale_Tracker_Uninstaller', 'disable' ) );
	    register_uninstall_hook( SHAREASALE_TRACKER_PLUGIN_FILENAME, array( 'ShareASale_Tracker_Uninstaller', 'uninstall' ) );
	}

	public function run() {
		$this->loader->run();
	}

	public function get_version() {
		return $this->version;
	}
}
