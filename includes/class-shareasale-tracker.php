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
	}

	private function load_dependencies() {
		add_option( 'tracker_options', '' );

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-shareasale-tracker-admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-tracker-pixel.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-tracker-loader.php';
		$this->loader = new ShareASale_Tracker_Loader();
	}

	private function define_admin_hooks() {
		$admin = new ShareASale_Tracker_Admin( $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_init',            $admin, 'admin_init' );
		$this->loader->add_action( 'admin_menu',            $admin, 'admin_menu' );
	}

	private function define_woocommerce_hooks() {
		$woocommerce = new ShareASale_Tracker_Pixel( $this->get_version() );
		$this->loader->add_action( 'woocommerce_thankyou', $woocommerce, 'woocommerce_thankyou' );
	}

	public function run() {
		$this->loader->run();
	}

	public function get_version() {
		return $this->version;
	}
}
