<?php
 
class ShareASale_Tracker_Admin {
     /**
   * @var float $version Plugin version, used for cache-busting
   */
	private $version;
 
    public function __construct( $version ) {
        $this->version = $version;
    }    
    
    public function admin_init() {
        $options = get_option( 'tracker_options' );
        /**
        * tracker options is the settings group name and an option name for the plugin
        */
        register_setting( 'tracker_options', 'tracker_options' );

        add_settings_section( 'tracker_merchant_ID_section', 'Merchant', array( $this, 'render_settings_merchant_ID_text'), 'shareasale-tracker' );
        add_settings_field( 'tracker_merchant_ID', 'User ID', array( $this, 'render_settings_input'), 'shareasale-tracker', 'tracker_merchant_ID_section', array(
        'label_for' => 'tracker_merchant_ID',
        'id'        => 'tracker_merchant_ID',
        'name'      => 'Merchant ID',
        'value'     => @$options['Merchant ID'],
        'size'      => 20,
        'type'      => 'text'
        )); 
    }
    
    /**
    * Method to wrap the WordPress admin_menu_page() function
    */
    public function admin_menu() {
 
        /** Add the top-level admin menu */
        $page_title = 'ShareASale Tracker Settings';
        $menu_title = 'ShareASale Tracker';
        $capability = 'manage_options';
        $menu_slug  = 'shareasale-tracker';
        $callback   =  array( $this, 'render_settings_page' );
        $icon_url   =  plugin_dir_url( __FILE__ ) . 'images/star_logo.png';
        add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $callback, $icon_url );

        /** Add submenu page with same slug as parent to ensure no duplicates
        * @see https://developer.wordpress.org/reference/functions/add_submenu_page/#Parameters
        */
        $sub_menu_title = 'Settings';
        add_submenu_page( $menu_slug, $page_title, $sub_menu_title, $capability, $menu_slug, $callback );
 
    }    
    
    /**
    * Method that displays the markup for the settings page, to be called in the WordPress add_menu_page() function
    */
    public function render_settings_page() {
        require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-tracker-settings.php';
    }

    public function render_settings_merchant_ID_text() {
        require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-tracker-settings-merchant-id-text.php';
    }

    /**
    * Method that creates any HTML <input>, to be called in the WordPress add_settings_field() function
    */
    public function render_settings_input($args) {
        $id    = esc_attr( $args['id'] );
        $name  = esc_attr( $args['name'] );
        $value = esc_attr( $args['value'] );
        $size  = $args['size'] ? 'size = ' . esc_attr( $args['size']) : '' ;
        $type  = esc_attr( $args['type'] );
        echo "<input id = '$id' placeholder = 'Enter Your " . $name . "' type='$type' name='tracker_options[$name]' value='$value' $size />"; 
    }
}