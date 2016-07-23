<?php
class ShareASale_Tracker_Admin {
	/**
	* @var float $version Plugin version, used for cache-busting
	*/
	private $version;

	public function __construct( $version ) {
		$this->version = $version;
	}

	public function enqueue_styles( $hook ) {
		if ( 'toplevel_page_shareasale-tracker' === $hook ) {
				wp_enqueue_style(
					'shareasale-tracker-admin-css',
					plugin_dir_url( __FILE__ ) . 'css/shareasale-tracker-admin.css',
					array(),
					$this->version
				);
		}
	}

	public function admin_init() {
		$options = get_option( 'tracker_options' );
		register_setting( 'tracker_options', 'tracker_options' );

		add_settings_section( 'tracker_required', 'Required Merchant Info', array( $this, 'render_settings_required_text' ), 'shareasale-tracker' );
		add_settings_field( 'merchant-id', '*Merchant ID', array( $this, 'render_settings_input' ), 'shareasale-tracker', 'tracker_required',
			array(
				'label_for'   => 'merchant-id',
				'id'          => 'merchant-id',
				'name'        => 'merchant-id',
				'value'       => ! empty( $options['merchant-id'] ) ? $options['merchant-id'] : '',
				'size'        => 22,
				'type'        => 'text',
				'placeholder' => 'ShareASale Merchant ID',
			)
		);

		add_settings_section( 'tracker_optional', 'Optional Pixel Info', array( $this, 'render_settings_optional_text' ), 'shareasale-tracker' );
		add_settings_field( 'store-id', 'Store ID', array( $this, 'render_settings_input' ), 'shareasale-tracker', 'tracker_optional',
			array(
				'label_for'   => 'store-id',
				'id'          => 'store-id',
				'name'        => 'store-id',
				'value'       => ! empty( $options['store-id'] ) ? $options['store-id'] : '',
				'size'        => 3,
				'type'        => 'text',
				'placeholder' => 'ID',
			)
		);
		add_settings_field( 'xtype', 'Merchant-Defined Type', array( $this, 'render_settings_input' ), 'shareasale-tracker', 'tracker_optional',
			array(
				'label_for'   => 'xtype',
				'id'          => 'xtype',
				'name'        => 'xtype',
				'value'       => ! empty( $options['xtype'] ) ? $options['xtype'] : '',
				'size'        => 35,
				'type'        => 'text',
				'placeholder' => 'Xtype',
			)
		);
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
		$callback   = array( $this, 'render_settings_page' );
		$icon_url   = plugin_dir_url( __FILE__ ) . 'images/star_logo.png';
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $callback, $icon_url );
		/** Add submenu page with same slug as parent to ensure no duplicates
		* @see https://developer.wordpress.org/reference/functions/add_submenu_page/#Parameters
		*/
		$sub_menu_title = 'Settings';
		add_submenu_page( $menu_slug, $page_title, $sub_menu_title, $capability, $menu_slug, $callback );
	}

	public function render_settings_page() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-tracker-settings.php';
	}

	public function render_settings_required_text() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-tracker-settings-required-text.php';
	}

	public function render_settings_optional_text() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-tracker-settings-optional-text.php';
	}

	/**
	* Method that creates any HTML <input>, to be called in the WordPress add_settings_field() function
	*/
	public function render_settings_input( $attributes ) {
		$template      = file_get_contents( plugin_dir_path( __FILE__ ) . 'templates/shareasale-tracker-settings-input.php' );
		$template_data = array_map( 'esc_attr', $attributes );

		foreach ( $template_data as $macro => $value ) {
			$template = str_replace( "!!$macro!!", $value, $template );
		}

		echo wp_kses( $template, array(
									'input' => array(
										'id' => true,
										'name' => true,
										'placeholder' => true,
										'size' => true,
										'type' => true,
										'value' => true,
									),
								)
		);
	}
}
