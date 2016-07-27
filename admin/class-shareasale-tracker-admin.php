<?php
class ShareASale_Tracker_Admin {
	/**
	* @var float $version Plugin version
	*/
	private $version;

	public function __construct( $version ) {
		$this->version = $version;
	}

	private function load_dependencies() {
		require_once plugin_dir_path( __FILE__ ) . '../includes/class-shareasale-tracker-api.php';
	}

	public function enqueue_styles( $hook ) {
		if ( 'toplevel_page_shareasale_tracker' === $hook || 'shareasale-tracker_page_shareasale_tracker_automatic_reconciliation' === $hook ) {
			wp_enqueue_style(
				'shareasale-tracker-admin-css',
				plugin_dir_url( __FILE__ ) . 'css/shareasale-tracker-admin.css',
				array(),
				$this->version
			);
		}
	}

	public function enqueue_scripts( $hook ) {
		if ( 'toplevel_page_shareasale_tracker' === $hook || 'shareasale-tracker_page_shareasale_tracker_automatic_reconciliation' === $hook ) {
			wp_enqueue_script(
				'shareasale-tracker-admin-js',
				plugin_dir_url( __FILE__ ) . 'js/shareasale-tracker-admin.js',
				array( 'jquery' ),
				$this->version
			);
		}
	}

	public function admin_init() {
		$options = get_option( 'tracker_options' );
		register_setting( 'tracker_options', 'tracker_options', array( $this, 'sanitize_settings' ) );

		add_settings_section( 'tracker_required', 'Required Merchant Info', array( $this, 'render_settings_required_section_text' ), 'shareasale_tracker' );
		add_settings_field( 'merchant-id', '*Merchant ID', array( $this, 'render_settings_input' ), 'shareasale_tracker', 'tracker_required',
			array(
				'label_for'   => 'merchant-id',
				'id'          => 'merchant-id',
				'name'        => 'merchant-id',
				'value'       => ! empty( $options['merchant-id'] ) ? $options['merchant-id'] : '',
				'size'        => 22,
				'type'        => 'text',
				'placeholder' => 'ShareASale Merchant ID',
				'class'       => 'tracker-option',
			)
		);

		add_settings_section( 'tracker_optional', 'Optional Pixel Info', array( $this, 'render_settings_optional_section_text' ), 'shareasale_tracker' );
		add_settings_field( 'store-id', 'Store ID', array( $this, 'render_settings_input' ), 'shareasale_tracker', 'tracker_optional',
			array(
				'label_for'   => 'store-id',
				'id'          => 'store-id',
				'name'        => 'store-id',
				'value'       => ! empty( $options['store-id'] ) ? $options['store-id'] : '',
				'size'        => '',
				'type'        => 'number',
				'placeholder' => 'ID',
				'class'       => 'tracker-option tracker-option-number',
			)
		);
		add_settings_field( 'xtype', 'Merchant-Defined Type', array( $this, 'render_settings_input' ), 'shareasale_tracker', 'tracker_optional',
			array(
				'label_for'   => 'xtype',
				'id'          => 'xtype',
				'name'        => 'xtype',
				'value'       => ! empty( $options['xtype'] ) ? $options['xtype'] : '',
				'size'        => 35,
				'type'        => 'text',
				'placeholder' => 'Xtype',
				'class'       => 'tracker-option',
			)
		);
		add_settings_section( 'tracker_reconciliation', 'Automate Reconciliation', array( $this, 'render_settings_reconciliation_section_text' ), 'shareasale_tracker_automatic_reconciliation' );
		add_settings_field( 'reconciliation-setting-hidden', '', array( $this, 'render_settings_input' ), 'shareasale_tracker_automatic_reconciliation', 'tracker_reconciliation',
			array(
				'id'          => 'reconciliation-setting-hidden',
				'name'        => 'reconciliation-setting',
				'value'       => 0,
				'status'      => '',
				'size'        => 1,
				'type'        => 'hidden',
				'placeholder' => '',
				'class'       => 'tracker-option-hidden',
		));
		add_settings_field( 'reconciliation-setting', 'Automate', array( $this, 'render_settings_input' ), 'shareasale_tracker_automatic_reconciliation', 'tracker_reconciliation',
			array(
				'label_for'   => 'reconciliation-setting',
				'id'          => 'reconciliation-setting',
				'name'        => 'reconciliation-setting',
				'value'       => 1,
				'status'      => checked( @$options['reconciliation-setting'], 1, false ),
				'size'        => 18,
				'type'        => 'checkbox',
				'placeholder' => '',
				'class'       => 'tracker-option',
		));

		add_settings_section( 'tracker_api', 'API Settings', array( $this, 'render_settings_api_section_text' ), 'shareasale_tracker_automatic_reconciliation' );
		add_settings_field( 'api-token', '*API Token', array( $this, 'render_settings_input' ), 'shareasale_tracker_automatic_reconciliation', 'tracker_api',
			array(
				'label_for'   => 'api-token',
				'id'          => 'api-token',
				'name'        => 'api-token',
				'value'       => ! empty( $options['api-token'] ) ? $options['api-token'] : '',
				'status'      => disabled( @$options['reconciliation-setting'], 0, false ),
				'size'        => 20,
				'type'        => 'text',
				'placeholder' => 'Enter your API Token',
				'class'       => 'tracker-option',
				//'class'       => 1 == @$options['reconciliation-setting'] ? 'tracker-option' : 'tracker-option-hidden',
		));
		add_settings_field( 'api-secret', '*API Secret', array( $this, 'render_settings_input' ), 'shareasale_tracker_automatic_reconciliation', 'tracker_api',
			array(
				'label_for'   => 'api-secret',
				'id'          => 'api-secret',
				'name'        => 'api-secret',
				'value'       => ! empty( $options['api-secret'] ) ? $options['api-secret'] : '',
				'status'      => disabled( @$options['reconciliation-setting'], 0, false ),
				'size'        => 34,
				'type'        => 'text',
				'placeholder' => 'Enter your API Secret',
				'class'       => 'tracker-option',
				//'class'       => 1 == @$options['reconciliation-setting'] ? 'tracker-option' : 'tracker-option-hidden',
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
		$menu_slug  = 'shareasale_tracker';
		$callback   = array( $this, 'render_settings_page' );
		$icon_url   = plugin_dir_url( __FILE__ ) . 'images/star_logo.png';
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $callback, $icon_url );

		$sub_menu_title = 'Tracking Settings';
		add_submenu_page( $menu_slug, $page_title, $sub_menu_title, $capability, $menu_slug, $callback );

	    $submenu_page_title = 'Automatic Reconciliation';
	    $submenu_title      = 'Automatic Reconciliation';
	    $submenu_slug       = 'shareasale_tracker_automatic_reconciliation';
	    $submenu_function   = array( $this, 'render_settings_page_submenu' );
	   	add_submenu_page( $menu_slug, $submenu_page_title, $submenu_title, 'manage_options', $submenu_slug, $submenu_function );
	}

	public function render_settings_page() {
		include_once 'options-head.php';
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-tracker-settings.php';
	}

	public function render_settings_page_submenu() {
		include_once 'options-head.php';
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-tracker-settings-automatic-reconciliation.php';
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-tracker-settings-automatic-reconciliation-table.php';
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-tracker-settings-automatic-reconciliation-pagination.php';
	}

	public function render_settings_required_section_text() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-tracker-settings-required-section-text.php';
	}

	public function render_settings_optional_section_text() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-tracker-settings-optional-section-text.php';
	}

	public function render_settings_reconciliation_section_text() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-tracker-settings-reconciliation-section-text.php';
	}

	public function render_settings_api_section_text() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-tracker-settings-api-section-text.php';
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
										'checked'     => true,
										'class'       => true,
										'disabled'    => true,
										'height'      => true,
										'id'          => true,
										'name'        => true,
										'placeholder' => true,
										'size'        => true,
										'type'        => true,
										'value'       => true,
										'width'       => true,
									),
								)
		);
	}

	public function sanitize_settings( $new_settings = array() ) {
		$old_settings      = get_option( 'tracker_options' ) ?: array();
		//$diff_new_settings is necessary to check whether API credentials have actually changed or not
		$diff_new_settings = array_diff_assoc( $new_settings, $old_settings );
		$final_settings    = array_merge( $old_settings, $new_settings );

		if ( empty( $final_settings['merchant-id'] ) ) {
			add_settings_error(
				'tracker_required',
				'merchant-id',
				'You must enter a ShareASale Merchant ID in the <a href = "?page=shareasale_tracker">Tracking Settings</a> tab.'
			);
		}

		if ( 1 == $final_settings['reconciliation-setting'] ) {

			if ( isset( $diff_new_settings['merchant-id'] ) || isset( $diff_new_settings['api-token'] ) || isset( $diff_new_settings['api-secret'] ) || 0 == $old_settings['reconciliation-setting'] ) {

				$shareasale_api = new ShareASale_Tracker_API( $final_settings['merchant-id'], $final_settings['api-token'], $final_settings['api-secret'] );
				$req = $shareasale_api->token_count()->exec();

				if ( ! $req ) {
					add_settings_error(
						'tracker_api',
						'api',
						'Your API credentials did not work. Check your merchant ID, API token, and API key.
						<span style = "font-size: 10px">'
						. $shareasale_api->get_error_msg() .
						'</span>'
					);
					//if API credentials failed, sanitize those options prior to saving and turn off automatic reconcilation
					$final_settings['api-token'] = $final_settings['api-secret'] = '';
					$final_settings['reconciliation-setting'] = 0;
				}
			}
		}
		return $final_settings;
	}
}
