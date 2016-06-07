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
 
    /**
    * Loads the plugin's dependencies
    */
    private function load_dependencies() {
        /** 
        * This WordPress option will just store the Merchant ID
        */
        add_option( 'tracker_options', '' );

        /** 
        * The loader is required here, the admin required in define_admin_hooks(), and pixel in define_woocommerce_hooks();
        */

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-shareasale-tracker-admin.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-tracker-pixel.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-tracker-loader.php';
        $this->loader = new ShareASale_Tracker_Loader();
    }

    /**
    * Setup the actions/methods to run on the ShareASale_Tracker_Admin object when certain WordPress hooks happen
    * No filters used yet in v1.0
    */
    private function define_admin_hooks() {
 
        $admin = new ShareASale_Tracker_Admin( $this->get_version() );
        $this->loader->add_action( 'admin_init', $admin, 'admin_init' );
        $this->loader->add_action( 'admin_menu', $admin, 'admin_menu' );
 
    }

    private function define_woocommerce_hooks() {
 
        $woocommerce = new ShareASale_Tracker_WooCommerce( $this->get_version() );
        $this->loader->add_action( 'woocommerce_thankyou', $woocommerce, 'woocommerce_thankyou' );
 
    }

    /**
    * Wrapper for the loader object to execute now that dependencies and hooks were setup in the constructor
    */
    public function run() {
        $this->loader->run();
    }

    /**
    * Simply returns the plugin version
    * Useful for cache-busting on the frontend
    */
    public function get_version() {
        return $this->version;
    }
 
}