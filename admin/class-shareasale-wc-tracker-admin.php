<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Admin {
	/**
	* @var string $version Plugin version
	*/
	private $version;

	public function __construct( $version ) {
		$this->version = $version;
		$this->load_dependencies();
	}

	private function load_dependencies() {
		require_once plugin_dir_path( __FILE__ ) . '../includes/class-shareasale-wc-tracker-api.php';
		require_once plugin_dir_path( __FILE__ ) . '../includes/class-shareasale-wc-tracker-datafeed.php';
		require_once plugin_dir_path( __FILE__ ) . '../includes/class-shareasale-wc-tracker-installer.php';
	}

	public function enqueue_styles( $hook ) {

		$hooks = array(
			'toplevel_page_shareasale_wc_tracker',
			'shareasale-wc-tracker_page_shareasale_wc_tracker_automatic_reconciliation',
			'shareasale-wc-tracker_page_shareasale_wc_tracker_datafeed_generation',
			'shareasale-wc-tracker_page_shareasale_wc_tracker_advanced_analytics',
		);

		if ( in_array( $hook, $hooks, true ) ) {
			wp_enqueue_style(
				'shareasale-wc-tracker-admin',
				esc_url( plugin_dir_url( __FILE__ ) . 'css/shareasale-wc-tracker-admin.css' ),
				array(),
				$this->version
			);
		}
	}

	public function enqueue_scripts( $hook ) {

		$hooks = array(
			'shareasale-wc-tracker_page_shareasale_wc_tracker_automatic_reconciliation',
			'shareasale-wc-tracker_page_shareasale_wc_tracker_datafeed_generation',
			'shareasale-wc-tracker_page_shareasale_wc_tracker_advanced_analytics',
		);

		if ( in_array( $hook, $hooks, true ) ) {
			wp_enqueue_script(
				'shareasale-wc-tracker-admin-js',
				esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-admin.js' ),
				array( 'jquery' ),
				$this->version
			);
		}
	}

	public function woocommerce_coupon_options( $post_id ) {
		//only support this feature in new WooCommerce
		if ( version_compare( WC()->version, '3.0' ) < 0 ) return;

		$options          = get_option( 'shareasale_wc_tracker_options' );
		$has_api_settings = ! empty( $options['merchant-id'] ) && ! empty( $options['api-token'] ) && ! empty( $options['api-secret'] );
		$status           = $has_api_settings ? null : array( 'disabled' => 'disabled' );
		$description      = $has_api_settings ? 'If checked this will send the coupon and/or any updates to ShareASale for your Affiliates to promote.' : 'You must have API settings entered in the <a target="_blank" href="' .
				esc_url( admin_url( 'admin.php?page=shareasale_wc_tracker_automatic_reconciliation' ) ) .
				'">ShareASale WC Tracker plugin settings</a> to use this feature.';

		woocommerce_wp_hidden_input( array(
			'id'    => 'shareasale_wc_tracker_coupon_upload_enabled',
			'value' => 'no',
		) );

		woocommerce_wp_checkbox( array(
			'id'          => 'shareasale_wc_tracker_coupon_upload_enabled',
			'label'       => 'Send to ShareASale?',
			'description' => $description,
			//if $has_api_settings is true, then null will have woocommerce_wp_checkbox() set shareasale_wc_tracker_coupon_upload post_meta key to previously saved value
			'value'             => $has_api_settings ? null : 'no',
			'custom_attributes' => $status,
		) );
	}

	public function woocommerce_coupon_options_save( $post_id, $post ) {
		//only support this feature in new WooCommerce
		//woocommerce_save_data nonce already safely checked by now
		if ( version_compare( WC()->version, '3.0' ) < 0 ) return;

		$options       = get_option( 'shareasale_wc_tracker_options' );
		$prev_deal_id  = get_post_meta( $post_id, 'shareasale_wc_tracker_coupon_uploaded', true );
		$new_setting   = sanitize_text_field( $_POST['shareasale_wc_tracker_coupon_upload_enabled'] );
		/*
		* instantiating this ShareASale_WC_Tracker_API without a possible merchant-id, api-token, or api-secret is okay
		* it won't be used to make actual API calls unless at least $new_setting is 'yes,' which can only be true if there are already proper API credentials set...
		*/
		$shareasale_api = new ShareASale_WC_Tracker_API( $options['merchant-id'], $options['api-token'], $options['api-secret'] );
		$coupon         = new WC_Coupon( $post_id );

		if ( $options['store-id'] ) {
			$coupon->shareasale_wc_tracker_store_id = $options['store-id'];
		}

		if ( 'yes' == $new_setting && $prev_deal_id ) {
			//this is an update, so deal edit
			$coupon->shareasale_wc_tracker_deal_id = $prev_deal_id;
			$req = $shareasale_api->deal_edit( $coupon )->exec();
			if ( ! $req ) {
				add_settings_error(
					'shareasale_wc_tracker_coupon_edited',
					esc_attr( 'coupon-edited' ),
					'Coupon could not be edited in ShareASale' .
					$shareasale_api->errors->get_error_code() . ' &middot; ' . $shareasale_api->errors->get_error_message()
				);
				set_transient( 'settings_errors', get_settings_errors(), 30 );
				$new_setting = 'no';
			} else {
				add_settings_error(
					'shareasale_wc_tracker_coupon_edited',
					esc_attr( 'coupon-edited' ),
					'Coupon edited in ShareASale.',
					'notice-success'
				);
				set_transient( 'settings_errors', get_settings_errors(), 30 );
			}
		} elseif ( 'yes' == $new_setting ) {
			//this is new, so deal upload
			$req = $shareasale_api->deal_upload( $coupon )->exec();
			if ( ! $req ) {
				add_settings_error(
					'shareasale_wc_tracker_coupon_uploaded',
					esc_attr( 'coupon-uploaded' ),
					'Coupon could not be uploaded to ShareASale' .
					$shareasale_api->errors->get_error_code() . ' &middot; ' . $shareasale_api->errors->get_error_message()
				);
				set_transient( 'settings_errors', get_settings_errors(), 30 );
				$new_setting = 'no';
			} else {
				$pieces  = array_map( 'trim', explode( '-', $shareasale_api->get_response() ) );
				$deal_id = $pieces[1];
				$coupon->update_meta_data( 'shareasale_wc_tracker_coupon_uploaded', $deal_id );
				add_settings_error(
					'shareasale_wc_tracker_coupon_uploaded',
					esc_attr( 'coupon-uploaded' ),
					'Coupon uploaded to ShareASale.',
					'notice-success'
				);
				set_transient( 'settings_errors', get_settings_errors(), 30 );
			}
		}
		//using new WooCommerce CRUD methods here
		$coupon->update_meta_data( 'shareasale_wc_tracker_coupon_upload_enabled', $new_setting );
		$coupon->save_meta_data();
	}

	public function admin_notices() {
		//see https://wordpress.stackexchange.com/questions/23701/how-should-one-implement-add-settings-error-on-custom-menu-pages
		global $post_id;
		global $wp_settings_errors;

		if ( 'shop_coupon' !== get_post_type( $post_id ) ) return;
		//make sure to use the settings_errors transient that lasts between pages and not just $wp_settings_errors global from memory gone between page loads
		//do this by simulating the same &settings-updated=1 WordPress behavior found in wp-admin/includes/template.php without having that GET parameter actually set...
		$wp_settings_errors = array_merge( (array) $wp_settings_errors, array_filter( (array) get_transient( 'settings_errors' ) ) );
		delete_transient( 'settings_errors' );
		settings_errors( 'shareasale_wc_tracker_coupon_uploaded' );
		settings_errors( 'shareasale_wc_tracker_coupon_edited' );
	}

	public function woocommerce_product_options_general_product_data() {
		woocommerce_wp_text_input( array(
			'id'          => 'shareasale_wc_tracker_datafeed_product_category',
			'label'       => '<img style="vertical-align:middle;" src="' . esc_url( plugin_dir_url( __FILE__ ) . 'images/star_logo.png' ) . '"> Product Category',
			'desc_tip'    => 'true',
			'placeholder' => 'Enter a valid ShareASale category',
			'type'        => 'number',
			)
		);
		woocommerce_wp_text_input( array(
			'id'          => 'shareasale_wc_tracker_datafeed_product_subcategory',
			'label'       => '<img style="vertical-align:middle;" src="' . esc_url( plugin_dir_url( __FILE__ ) . 'images/star_logo.png' ) . '"> Product Subcategory',
			'desc_tip'    => 'true',
			'placeholder' => 'Enter a valid ShareASale subcategory',
			'type'        => 'number',
			)
		);
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-woocommerce-product-category-subcategory-out-link.php';
	}

	public function woocommerce_process_product_meta( $post_id ) {
		//woocommerce_save_data nonce already safely checked by now
		if ( ! empty( $_POST['shareasale_wc_tracker_datafeed_product_category'] ) ) {
			update_post_meta( $post_id, 'shareasale_wc_tracker_datafeed_product_category', esc_attr( $_POST['shareasale_wc_tracker_datafeed_product_category'] ) );
		}
		if ( ! empty( $_POST['shareasale_wc_tracker_datafeed_product_subcategory'] ) ) {
			update_post_meta( $post_id, 'shareasale_wc_tracker_datafeed_product_subcategory', esc_attr( $_POST['shareasale_wc_tracker_datafeed_product_subcategory'] ) );
		}
	}

	public function admin_init() {
		$options = get_option( 'shareasale_wc_tracker_options' );
		register_setting( 'shareasale_wc_tracker_options', 'shareasale_wc_tracker_options', array( $this, 'sanitize_settings' ) );

		add_settings_section( 'shareasale_wc_tracker_required', 'Required Merchant Info', array( $this, 'render_settings_required_section_text' ), 'shareasale_wc_tracker' );
		add_settings_field( 'merchant-id', '*Merchant ID', array( $this, 'render_settings_input' ), 'shareasale_wc_tracker', 'shareasale_wc_tracker_required',
			array(
				'label_for'   => 'merchant-id',
				'id'          => 'merchant-id',
				'name'        => 'merchant-id',
				'value'       => ! empty( $options['merchant-id'] ) ? $options['merchant-id'] : '',
				//mimicking WordPress's own " $type='$type'" output format for disabled(), checked(), and selected() functions...
				'status'      => "required='required'",
				'size'        => 22,
				'type'        => 'text',
				'placeholder' => 'ShareASale Merchant ID',
				'class'       => 'shareasale-wc-tracker-option',
			)
		);

		add_settings_section( 'shareasale_wc_tracker_optional', 'Optional Pixel Info', array( $this, 'render_settings_optional_section_text' ), 'shareasale_wc_tracker' );
		add_settings_field( 'store-id', 'Store ID', array( $this, 'render_settings_input' ), 'shareasale_wc_tracker', 'shareasale_wc_tracker_optional',
			array(
				'label_for'   => 'store-id',
				'id'          => 'store-id',
				'name'        => 'store-id',
				'value'       => ! empty( $options['store-id'] ) ? $options['store-id'] : '',
				'status'      => '',
				'size'        => '',
				'type'        => 'number',
				'placeholder' => 'ID',
				'class'       => 'shareasale-wc-tracker-option shareasale-wc-tracker-option-number',
			)
		);
		add_settings_field( 'xtype', 'Merchant-Defined Type', array( $this, 'render_settings_select' ), 'shareasale_wc_tracker', 'shareasale_wc_tracker_optional',
			array(
				'label_for'   => 'xtype',
				'id'          => 'xtype',
				'name'        => 'xtype',
				'value'       => ! empty( $options['xtype'] ) ? $options['xtype'] : '',
				'status'      => '',
				'size'        => '',
				'type'        => 'select',
				'placeholder' => '',
				'class'       => 'shareasale-wc-tracker-option',
			)
		);
		add_settings_section( 'shareasale_wc_tracker_reconciliation', 'Automate Reconciliation', array( $this, 'render_settings_reconciliation_section_text' ), 'shareasale_wc_tracker_automatic_reconciliation' );
		add_settings_field( 'reconciliation-setting-hidden', '', array( $this, 'render_settings_input' ), 'shareasale_wc_tracker_automatic_reconciliation', 'shareasale_wc_tracker_reconciliation',
			array(
				'id'          => 'reconciliation-setting-hidden',
				'name'        => 'reconciliation-setting',
				'value'       => 0,
				'status'      => '',
				'size'        => 1,
				'type'        => 'hidden',
				'placeholder' => '',
				'class'       => 'shareasale-wc-tracker-option-hidden',
		));
		add_settings_field( 'reconciliation-setting', 'Automate', array( $this, 'render_settings_input' ), 'shareasale_wc_tracker_automatic_reconciliation', 'shareasale_wc_tracker_reconciliation',
			array(
				'label_for'   => 'reconciliation-setting',
				'id'          => 'reconciliation-setting',
				'name'        => 'reconciliation-setting',
				'value'       => 1,
				'status'      => checked( @$options['reconciliation-setting'], 1, false ),
				'size'        => 18,
				'type'        => 'checkbox',
				'placeholder' => '',
				'class'       => 'shareasale-wc-tracker-option',
		));

		add_settings_section( 'shareasale_wc_tracker_api', 'API Settings', array( $this, 'render_settings_api_section_text' ), 'shareasale_wc_tracker_automatic_reconciliation' );
		add_settings_field( 'api-token', '*API Token', array( $this, 'render_settings_input' ), 'shareasale_wc_tracker_automatic_reconciliation', 'shareasale_wc_tracker_api',
			array(
				'label_for'   => 'api-token',
				'id'          => 'api-token',
				'name'        => 'api-token',
				'value'       => ! empty( $options['api-token'] ) ? $options['api-token'] : '',
				'status'      => disabled( @$options['reconciliation-setting'], 0, false ) . " required='required'",
				'size'        => 20,
				'type'        => 'text',
				'placeholder' => 'Enter your API Token',
				'class'       => 'shareasale-wc-tracker-option',
		));
		add_settings_field( 'api-secret', '*API Secret', array( $this, 'render_settings_input' ), 'shareasale_wc_tracker_automatic_reconciliation', 'shareasale_wc_tracker_api',
			array(
				'label_for'   => 'api-secret',
				'id'          => 'api-secret',
				'name'        => 'api-secret',
				'value'       => ! empty( $options['api-secret'] ) ? $options['api-secret'] : '',
				'status'      => disabled( @$options['reconciliation-setting'], 0, false ) . " required='required'",
				'size'        => 34,
				'type'        => 'text',
				'placeholder' => 'Enter your API Secret',
				'class'       => 'shareasale-wc-tracker-option',
		));

		add_settings_section( 'shareasale_wc_tracker_datafeed_generation', 'Optional Product Default Category/Subcategory', array( $this, 'render_settings_datafeed_generation_section_text' ), 'shareasale_wc_tracker_datafeed_generation' );
		add_settings_field( 'default-category', 'Default Category', array( $this, 'render_settings_input' ), 'shareasale_wc_tracker_datafeed_generation', 'shareasale_wc_tracker_datafeed_generation',
			array(
				'label_for'   => 'default-category',
				'id'          => 'default-category',
				'name'        => 'default-category',
				'value'       => ! empty( $options['default-category'] ) ? $options['default-category'] : '',
				'status'      => 'min=1 max=17',
				'size'        => '',
				'type'        => 'number',
				'placeholder' => 'Cat',
				'class'       => 'shareasale-wc-tracker-option shareasale-wc-tracker-option-number',
		));
		add_settings_field( 'default-subcategory', 'Default Subcategory', array( $this, 'render_settings_input' ), 'shareasale_wc_tracker_datafeed_generation', 'shareasale_wc_tracker_datafeed_generation',
			array(
				'label_for'   => 'default-subcategory',
				'id'          => 'default-subcategory',
				'name'        => 'default-subcategory',
				'value'       => ! empty( $options['default-subcategory'] ) ? $options['default-subcategory'] : '',
				'status'      => 'min=1 max=187',
				'size'        => '',
				'type'        => 'number',
				'placeholder' => 'Sub',
				'class'       => 'shareasale-wc-tracker-option shareasale-wc-tracker-option-number',
		));

		$callback = @$options['analytics-setting'] ? 'render_settings_analytics_enabled_section_text' : 'render_settings_analytics_disabled_section_text';

		add_settings_section( 'shareasale_wc_tracker_analytics', 'Advanced Analytics', array( $this, $callback ), 'shareasale_wc_tracker_advanced_analytics' );
		add_settings_field( 'analytics-setting-hidden', '', array( $this, 'render_settings_input' ), 'shareasale_wc_tracker_advanced_analytics', 'shareasale_wc_tracker_analytics',
			array(
				'id'          => 'analytics-setting-hidden',
				'name'        => 'analytics-setting',
				'value'       => 0,
				'status'      => '',
				'size'        => 1,
				'type'        => 'hidden',
				'placeholder' => '',
				'class'       => 'shareasale-wc-tracker-option-hidden',
		));
		add_settings_field( 'analytics-setting', 'Enable', array( $this, 'render_settings_input' ), 'shareasale_wc_tracker_advanced_analytics', 'shareasale_wc_tracker_analytics',
			array(
				'label_for'   => 'analytics-setting',
				'id'          => 'analytics-setting',
				'name'        => 'analytics-setting',
				'value'       => 1,
				'status'      => checked( @$options['analytics-setting'], 1, false ),
				'size'        => 18,
				'type'        => 'checkbox',
				'placeholder' => '',
				'class'       => 'shareasale-wc-tracker-option',
		));
		add_settings_field( 'analytics-passkey', 'Analytics Passkey', array( $this, 'render_settings_input' ), 'shareasale_wc_tracker_advanced_analytics', 'shareasale_wc_tracker_analytics',
			array(
				'label_for'   => 'analytics-passkey',
				'id'          => 'analytics-passkey',
				'name'        => 'analytics-passkey',
				'value'       => ! empty( $options['analytics-passkey'] ) ? $options['analytics-passkey'] : '',
				'status'      => disabled( @$options['analytics-setting'], 0, false ) . " required='required'",
				'size'        => 28,
				'type'        => 'text',
				'placeholder' => 'Enter your Required Passkey',
				'class'       => 'shareasale-wc-tracker-option',
		));

	}
	//this is here because it runs on admin_init hook unfortunately
	public function plugin_upgrade() {
		$current_version = get_option( 'shareasale_wc_tracker_version' );
		$latest_version  = $this->version;
		//at first installation, shareasale_wc_tracker_version actually gets defined here even if not an upgrade
		if ( -1 === version_compare( $current_version, $latest_version ) ) {
			ShareASale_WC_Tracker_Installer::upgrade( $current_version, $latest_version );
		}
	}

	public function admin_menu() {

		/** Add the top-level admin menu */
		$page_title = 'ShareASale WooCommerce Tracker Settings';
		$menu_title = 'ShareASale WC Tracker';
		$capability = 'manage_options';
		$menu_slug  = 'shareasale_wc_tracker';
		$callback   = array( $this, 'render_settings_page' );
		$icon_url   = 'dashicons-star-filled';
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $callback, $icon_url );

		$sub_menu_title = 'Tracking Settings';
		add_submenu_page( $menu_slug, $page_title, $sub_menu_title, $capability, $menu_slug, $callback );

		$submenu_page_title = 'Automatic Reconciliation';
		$submenu_title      = 'Automatic Reconciliation';
		$submenu_slug       = 'shareasale_wc_tracker_automatic_reconciliation';
		$submenu_function   = array( $this, 'render_settings_page_submenu' );
		add_submenu_page( $menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function );

		$submenu_page_title = 'Product Datafeed Generation';
		$submenu_title      = 'Product Datafeed Generation';
		$submenu_slug       = 'shareasale_wc_tracker_datafeed_generation';
		$submenu_function   = array( $this, 'render_settings_page_subsubmenu' );
		add_submenu_page( $menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function );

		$submenu_page_title = 'Advanced Analytics';
		$submenu_title      = 'Advanced Analytics';
		$submenu_slug       = 'shareasale_wc_tracker_advanced_analytics';
		$submenu_function   = array( $this, 'render_settings_page_subsubsubmenu' );
		add_submenu_page( $menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function );
	}

	public function render_settings_page() {
		//must be included so regular setting saves show general 'Settings saved' notice
		//and also any setting errors on the stack without a slug (first arg in add_settings_error() function) are displayed using settings_errors()
		include_once 'options-head.php';
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			add_settings_error(
				'shareasale_wc_tracker_woocommerce_warning',
				esc_attr( 'woocommerce-warning' ),
				'WooCommerce plugin must be installed and activated to use this plugin.'
			);
			settings_errors( 'shareasale_wc_tracker_woocommerce_warning', false, true );
			return;
		}

		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings.php';
	}

	public function render_settings_page_submenu() {
		//must be included so regular setting saves show general 'Settings saved' notice
		//and also any setting errors on the stack without a slug (first arg in add_settings_error() function) are displayed using settings_errors()
		include_once 'options-head.php';
		if ( ! function_exists( 'curl_version' ) ) {
			add_settings_error(
				'shareasale_wc_tracker_curl_warning',
				esc_attr( 'cURL-warning' ),
				'cURL is not enabled on your server. Please contact your webhost to have cURL enabled to use automatic reconciliation.'
			);
			settings_errors( 'shareasale_wc_tracker_curl_warning', false, true );
			return;
		}

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			add_settings_error(
				'shareasale_wc_tracker_woocommerce_warning',
				esc_attr( 'woocommerce-warning' ),
				'WooCommerce plugin must be installed and activated to use this plugin.'
			);
			settings_errors( 'shareasale_wc_tracker_woocommerce_warning', false, true );
			return;
		}

		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-automatic-reconciliation.php';
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-automatic-reconciliation-table.php';
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-automatic-reconciliation-pagination.php';
	}

	public function render_settings_page_subsubmenu() {
		//must be included so regular setting saves show general 'Settings saved' notice
		//and also any setting errors on the stack without a slug (first arg in add_settings_error() function) are displayed using settings_errors()
		include_once 'options-head.php';
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			add_settings_error(
				'shareasale_wc_tracker_woocommerce_warning',
				esc_attr( 'woocommerce-warning' ),
				'WooCommerce plugin must be installed and activated to use this plugin.'
			);
			settings_errors( 'shareasale_wc_tracker_woocommerce_warning', false, true );
			return;
		}

		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-datafeed-generation.php';
	}

	public function render_settings_page_subsubsubmenu() {
		//must be included so regular setting saves show general 'Settings saved' notice
		//and also any setting errors on the stack without a slug (first arg in add_settings_error() function) are displayed using settings_errors()
		include_once 'options-head.php';
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			add_settings_error(
				'shareasale_wc_tracker_woocommerce_warning',
				esc_attr( 'woocommerce-warning' ),
				'WooCommerce plugin must be installed and activated to use this plugin.'
			);
			settings_errors( 'shareasale_wc_tracker_woocommerce_warning', false, true );
			return;
		}

		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-advanced-analytics.php';
	}

	public function wp_ajax_generate_datafeed() {
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'generate-datafeed' ) ) {
			add_settings_error(
				'shareasale_wc_tracker_datafeed_warning',
				esc_attr( 'datafeed-security' ),
				'There was a security error generating this datafeed. Try refreshing the page and generating again.'
			);
			settings_errors( 'shareasale_wc_tracker_datafeed_warning' );
			wp_die();
		}

		$url    = add_query_arg( 'page', 'shareasale_wc_tracker_datafeed_generation', esc_url( admin_url( 'admin.php' ) ) );
		$dir    = plugin_dir_path( __FILE__ ) . 'datafeeds';
		$file   = trailingslashit( $dir ) . date( 'mdY' ) . '.csv';
		$inputs = array( '_wpnonce', 'action' );
		$creds  = request_filesystem_credentials( $url, '', false, $dir, $inputs );

		if ( ! $creds ) {
			//stop here, we can't even write to /datafeeds yet and need credentials form...
			wp_die();
		}

		if ( ! WP_Filesystem( $creds ) ) {
			//we got credentials but they don't work, so try form again and now also prompt an error msg...
			request_filesystem_credentials( $url, '', true, $dir, $inputs );
			wp_die();
		}

		//access granted! instantiate a ShareASale_WC_Tracker_Datafeed() object here and start exporting products to csv
		global $wp_filesystem;
		$datafeed = new ShareASale_WC_Tracker_Datafeed( $this->version, $wp_filesystem );
		if ( $datafeed ) {
			$datafeed->export( $file );
			$datafeed->clean_up( $dir, 60 );
		}

		//then show the csv files and their info in the table template
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-datafeed-generation-table.php';
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-datafeed-generation-pagination.php';
		wp_die();
	}

	public function render_settings_required_section_text() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-required-section-text.php';
	}

	public function render_settings_optional_section_text() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-optional-section-text.php';
	}

	public function render_settings_reconciliation_section_text() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-reconciliation-section-text.php';
	}

	public function render_settings_api_section_text() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-api-section-text.php';
	}

	public function render_settings_datafeed_generation_section_text() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-datafeed-generation-section-text.php';
	}

	public function render_settings_analytics_enabled_section_text() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-analytics-enabled-section-text.php';
	}

	public function render_settings_analytics_disabled_section_text() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-analytics-disabled-section-text.php';
	}

	public function render_settings_input( $attributes ) {
		$template      = file_get_contents( plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-input.php' );
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
										'max'         => true,
										'min'         => true,
										'name'        => true,
										'placeholder' => true,
										'required'    => true,
										'size'        => true,
										'type'        => true,
										'value'       => true,
										'width'       => true,
									),
								)
		);
	}

	public function render_settings_select( $attributes ) {
		$template      = file_get_contents( plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-select.php' );
		$template_data = array_map( 'esc_attr', $attributes );

		foreach ( $template_data as $macro => $value ) {
			$template = str_replace( "!!$macro!!", $value, $template );
		}
		//find the current saved option and replace its value to add the selected attribute
		$template = str_replace( '"' . $template_data['value'] . '"', '"' . $template_data['value'] . '" selected="selected"', $template );

		echo wp_kses( $template, array(
									'select' => array(
										'autofocus' => true,
										'class'     => true,
										'disabled'  => true,
										'form'      => true,
										'id'        => true,
										'multiple'  => true,
										'name'      => true,
										'required'  => true,
										'size'      => true,
									),
									'optgroup' => array(
										'class'    => true,
										'disabled' => true,
										'id'       => true,
										'label'    => true,
									),
									'option' => array(
										'class'    => true,
										'disabled' => true,
										'id'       => true,
										'label'    => true,
										'selected' => true,
										'value'    => true,
									),
								)
		);

	}

	//add shortcut to settings page from the plugin admin entry for dealsbar
	public function render_settings_shortcut( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=shareasale_wc_tracker' ) ) . '">Settings</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	public function sanitize_settings( $new_settings = array() ) {
		$old_settings      = get_option( 'shareasale_wc_tracker_options' ) ? get_option( 'shareasale_wc_tracker_options' ) : array();
		//$diff_new_settings is necessary to check whether API credentials have actually changed or not
		$diff_new_settings = array_diff_assoc( $new_settings, $old_settings );
		$final_settings    = array_merge( $old_settings, $new_settings );

		if ( empty( $final_settings['merchant-id'] ) ) {
			add_settings_error(
				'shareasale_wc_tracker_merchant_id',
				esc_attr( 'merchant-id' ),
				'You must enter a ShareASale Merchant ID in the <a href="' . esc_url( admin_url( 'admin.php?page=shareasale_wc_tracker' ) ) . '">Tracking Settings</a> tab.'
			);
		}

		if ( 1 == $final_settings['reconciliation-setting'] ) {

			if ( isset( $diff_new_settings['merchant-id'] ) || isset( $diff_new_settings['api-token'] ) || isset( $diff_new_settings['api-secret'] ) || 0 == $old_settings['reconciliation-setting'] ) {

				$shareasale_api = new ShareASale_WC_Tracker_API( $final_settings['merchant-id'], $final_settings['api-token'], $final_settings['api-secret'] );
				$req = $shareasale_api->token_count()->exec();

				if ( ! $req ) {
					add_settings_error(
						'shareasale_wc_tracker_api_settings',
						esc_attr( 'api' ),
						'Your API credentials did not work. Check your merchant ID, API token, and API key.
						<span style = "font-size: 10px">'
						. $shareasale_api->errors->get_error_code() . ' &middot; ' . $shareasale_api->errors->get_error_message() .
						'</span>'
					);
					//if API credentials failed, sanitize those options prior to saving and turn off automatic reconcilation
					$final_settings['api-token'] = $final_settings['api-secret'] = '';
					$final_settings['reconciliation-setting'] = 0;
				}
			}
		}

		if ( 1 == $final_settings['analytics-setting'] && $final_settings['merchant-id'] ) {
			/*
			* If you're reading this, the passkey we provide is not really intended to be a secure form of authentication whatsoever...
			* It's just there to ensure Merchants have first contacted ShareASale and reached a plan for actually using advanced analytics with our Conversion Lines feature.
			* So don't just reverse engineer this to get your "passkey" (a simple crc32 hash of your Merchant ID...) or hack the db option directly.
			* It won't do any good if Conversion Lines isn't already setup on our end too.
			* Email us first to talk about your Affiliate attribution/commission strategy. We'd love to help!
			* - Ryan Stark, Technical Team Lead, ShareASale.com, Inc.
			*/
			$checksum = hash( 'crc32', $final_settings['merchant-id'] );
			if ( trim( $final_settings['analytics-passkey'] ) !== $checksum ) {
				add_settings_error(
					'shareasale_wc_tracker_analytics_passkey',
					esc_attr( 'analytics' ),
					'Enter a valid passkey from the ShareASale Tech Team.'
				);
				$final_settings['analytics-passkey'] = '';
				$final_settings['analytics-setting'] = 0;
			}
		} elseif ( empty( $final_settings['merchant-id'] ) ) {
				$final_settings['analytics-setting'] = 0;
		}
		return $final_settings;
	}
}
