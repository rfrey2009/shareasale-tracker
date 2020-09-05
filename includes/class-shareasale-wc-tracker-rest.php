<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Rest extends WP_REST_Controller {
	/*
	* Get, set, and delete Awin master tag flags (possibly more than just ID in the future)
		GET /shareasale-wc-tracker/v1/mastertag
		PUT /shareasale-wc-tracker/v1/mastertag/id
		DELETE /shareasale-wc-tracker/v1/mastertag/id

	* Get, and enable / disable Advanced Analytics
		GET /shareasale-wc-tracker/v1/advanced-analytics/status
		POST /shareasale-wc-tracker/v1/advanced-analytics/status
	*/

	public function register_routes() {
		$version = '1';
		$namespace = 'shareasale-wc-tracker/v' . $version;

		register_rest_route( $namespace, '/mastertag', array(
			array(
				'methods'  => 'GET',
				'permission_callback' => '__return_true',
				'callback' => array( $this, 'get_mastertag' ),
			),
			array(
				'methods'  => 'PUT',
				'permission_callback' => '__return_true',
				'callback' => array( $this, 'update_mastertag' ),
				'args' => array(
			    	'id' => array(
						'required' => true,
			        	'validate_callback' => function( $param, $request, $key ) {
			        		return is_numeric( $param );
			        	},
			    	),
			    ),
			),
		) );

		register_rest_route( $namespace, '/mastertag/(?P<key>\w+)', array(
				'methods'  => 'DELETE',
				'permission_callback' => '__return_true',
				'callback' => array( $this, 'delete_mastertag_value' ),
			)
		);

		register_rest_route( $namespace, '/advanced-analytics/status', array(
			array(
				'methods'  => 'GET',
				'permission_callback' => '__return_true',
				'callback' => array( $this, 'get_analytics_status' ),
			),
			array(
				'methods'  => 'POST',
				'permission_callback' => '__return_true',
				'callback' => array( $this, 'update_analytics_status' ),
				'args' => array(
			    	'enabled' => array(
						'required' => true,
			        	'validate_callback' => function( $param, $request, $key ) {
			        		return is_bool( $param );
			        	},
			    	),
			    ),
			),
		) );
	}

	public function get_mastertag( $request ) {
		if ( ! $this->check_origin() ) {
			return new WP_Error(
				'auth',
				__( 'Only ShareASale admins can retrieve the Awin mastertag', 'text-domain' ),
				array( 'status' => 403 )
			);
		}

		$mastertag = get_option( 'shareasale_wc_tracker_mastertag', array() );

		return new WP_REST_Response( $mastertag, 200 );
	}

	public function update_mastertag( $request ) {
		if ( ! $this->check_origin() ) {
			return new WP_Error(
				'auth',
				__( 'Only ShareASale admins can update flags on the Awin mastertag', 'text-domain' ),
				array( 'status' => 403 )
			);
		}

		//get parameters from request
		$params = $request->get_json_params();

		$mastertag_before = get_option( 'shareasale_wc_tracker_mastertag', array() );
		foreach ( $params as $key => $value ) {
			$mastertag_before[ $key ] = sanitize_text_field( $value );
		}

		update_option( 'shareasale_wc_tracker_mastertag', $mastertag_before );

		$mastertag_after = get_option( 'shareasale_wc_tracker_mastertag' );
		$response        = $mastertag_after == $mastertag_before;

		return new WP_REST_Response( array( 'success' => $response, 'mastertag' => $mastertag_after ), 200 );
	}

	public function delete_mastertag_value( $request ) {
		if ( ! $this->check_origin() ) {
			return new WP_Error(
				'auth',
				__( 'Only ShareASale admins can delete flags from the Awin mastertag', 'text-domain' ),
				array( 'status' => 403 )
			);
		}

		//get parameters from request
		$params = $request->get_params();
		$key    = $params['key'];

		$mastertag_before = get_option( 'shareasale_wc_tracker_mastertag', array() );
		if ( array_key_exists( $key, $mastertag_before ) ) {
			$was = $mastertag_before[ $key ];
			unset( $mastertag_before[ $key ] );
		} else {
			return new WP_Error(
				'missing',
				__( 'This mastertag flag could not be deleted because it was not found', 'text-domain' ),
				array( 'status' => 422 )
			);
		}

		update_option( 'shareasale_wc_tracker_mastertag', $mastertag_before );

		$mastertag_after = get_option( 'shareasale_wc_tracker_mastertag' );
		//probably redundant but whatever
		$response = ! array_key_exists( $key, $mastertag_after );

		return new WP_REST_Response( array( 'success' => $response, 'message' => 'Flag ' . $key . ' with value ' . $was . ' was deleted' ), 200 );
	}

	public function get_analytics_status( $request ) {
		if ( ! $this->check_origin() ) {
			return new WP_Error(
				'auth',
				__( 'Only ShareASale admins can retrieve the advanced analytics status', 'text-domain' ),
				array( 'status' => 403 )
			);
		}

		$options = get_option( 'shareasale_wc_tracker_options', array() );

		return new WP_REST_Response( array( 'enabled' => $options['analytics-setting'] ), 200 );
	}

	public function update_analytics_status( $request ) {
		if ( ! $this->check_origin() ) {
			return new WP_Error(
				'auth',
				__( 'Only ShareASale admins can update the advanced analytics status', 'text-domain' ),
				array( 'status' => 403 )
			);
		}

		//get parameters from request
		$params     = $request->get_json_params();
		$new_status = sanitize_text_field( $params['enabled'] );

		$options_before = get_option( 'shareasale_wc_tracker_options', array() );
		$options_before['analytics-setting'] = $new_status ? 1 : 0;

		update_option( 'shareasale_wc_tracker_options', $options_before );

		$options_after = get_option( 'shareasale_wc_tracker_options' );
		$response      = $options_after['analytics-setting'] == $new_status;

		return new WP_REST_Response( array( 'success' => $response, 'enabled' => $options_after['analytics-setting'] ), 200 );
	}
	//simple check to ensure only ShareASale admins can access. Unfortunately until WP has another form of REST authorization without relying on cookies or adding another plugin, this will have to do...
	private function check_origin() {
		$remote_ip = $_SERVER['REMOTE_ADDR'];
		$authorized_ips = array( '216.80.109.82','216.80.109.83','216.80.109.84','216.80.109.85','216.80.109.86','216.80.109.87','216.80.109.88','216.80.109.89','216.80.109.90','216.80.109.91','216.80.109.92','216.80.109.93','216.80.109.94','208.185.166.32','208.185.166.33','208.185.166.34','208.185.166.35','208.185.166.36','208.185.166.37','208.185.166.38','208.185.166.39','194.116.167.240','194.116.167.247','212.60.218.226','50.225.144.242','195.216.249.0','195.216.249.1','195.216.249.2','195.216.249.3','195.216.249.4','195.216.249.5','195.216.249.6','195.216.249.7','195.216.249.8','195.216.249.9','195.216.249.10','195.216.249.11','195.216.249.12','195.216.249.13','195.216.249.14','195.216.249.15','195.216.249.16','195.216.249.17','195.216.249.18','195.216.249.19','195.216.249.20','195.216.249.21','195.216.249.22','195.216.249.23','195.216.249.24','195.216.249.25','195.216.249.26','195.216.249.27','195.216.249.28','195.216.249.29','195.216.249.30','195.216.249.31','195.216.249.32','195.216.249.33','195.216.249.34','195.216.249.35','195.216.249.36','195.216.249.37','195.216.249.38','195.216.249.39','195.216.249.40','195.216.249.41','195.216.249.42','195.216.249.43','195.216.249.44','195.216.249.45','195.216.249.46','195.216.249.47','195.216.249.48','195.216.249.49','195.216.249.50','195.216.249.51','195.216.249.52','195.216.249.53','195.216.249.54','195.216.249.55','195.216.249.56','195.216.249.57','195.216.249.58','195.216.249.59','195.216.249.60','195.216.249.61','195.216.249.62','195.216.249.63','195.216.249.64','195.216.249.65','195.216.249.66','195.216.249.67','195.216.249.68','195.216.249.69','195.216.249.70','195.216.249.71','195.216.249.72','195.216.249.73','195.216.249.74','195.216.249.75','195.216.249.76','195.216.249.77','195.216.249.78','195.216.249.79','195.216.249.80','195.216.249.81','195.216.249.82','195.216.249.83','195.216.249.84','195.216.249.85','195.216.249.86','195.216.249.87','195.216.249.88','195.216.249.89','195.216.249.90','195.216.249.91','195.216.249.92','195.216.249.93','195.216.249.94','195.216.249.95','195.216.249.96','195.216.249.97','195.216.249.98','195.216.249.99','195.216.249.100','195.216.249.101','195.216.249.102','195.216.249.103','195.216.249.104','195.216.249.105','195.216.249.106','195.216.249.107','195.216.249.108','195.216.249.109','195.216.249.110','195.216.249.111','195.216.249.112','195.216.249.113','195.216.249.114','195.216.249.115','195.216.249.116','195.216.249.117','195.216.249.118','195.216.249.119','195.216.249.120','195.216.249.121','195.216.249.122','195.216.249.123','195.216.249.124','195.216.249.125','195.216.249.126','195.216.249.127','195.216.249.128','195.216.249.129','195.216.249.130','195.216.249.131','195.216.249.132','195.216.249.133','195.216.249.134','195.216.249.135','195.216.249.136','195.216.249.137','195.216.249.138','195.216.249.139','195.216.249.140','195.216.249.141','195.216.249.142','195.216.249.143','195.216.249.144','195.216.249.145','195.216.249.146','195.216.249.147','195.216.249.148','195.216.249.149','195.216.249.150','195.216.249.151','195.216.249.152','195.216.249.153','195.216.249.154','195.216.249.155','195.216.249.156','195.216.249.157','195.216.249.158','195.216.249.159','195.216.249.160','195.216.249.161','195.216.249.162','195.216.249.163','195.216.249.164','195.216.249.165','195.216.249.166','195.216.249.167','195.216.249.168','195.216.249.169','195.216.249.170','195.216.249.171','195.216.249.172','195.216.249.173','195.216.249.174','195.216.249.175','195.216.249.176','195.216.249.177','195.216.249.178','195.216.249.179','195.216.249.180','195.216.249.181','195.216.249.182','195.216.249.183','195.216.249.184','195.216.249.185','195.216.249.186','195.216.249.187','195.216.249.188','195.216.249.189','195.216.249.190','195.216.249.191','195.216.249.192','195.216.249.193','195.216.249.194','195.216.249.195','195.216.249.196','195.216.249.197','195.216.249.198','195.216.249.199','195.216.249.200','195.216.249.201','195.216.249.202','195.216.249.203','195.216.249.204','195.216.249.205','195.216.249.206','195.216.249.207','195.216.249.208','195.216.249.209','195.216.249.210','195.216.249.211','195.216.249.212','195.216.249.213','195.216.249.214','195.216.249.215','195.216.249.216','195.216.249.217','195.216.249.218','195.216.249.219','195.216.249.220','195.216.249.221','195.216.249.222','195.216.249.223','195.216.249.224','195.216.249.225','195.216.249.226','195.216.249.227','195.216.249.228','195.216.249.229','195.216.249.230','195.216.249.231','195.216.249.232','195.216.249.233','195.216.249.234','195.216.249.235','195.216.249.236','195.216.249.237','195.216.249.238','195.216.249.239','195.216.249.240','195.216.249.241','195.216.249.242','195.216.249.243','195.216.249.244','195.216.249.245','195.216.249.246','195.216.249.247','195.216.249.248','195.216.249.249','195.216.249.250','195.216.249.251','195.216.249.252','195.216.249.253','195.216.249.254','195.216.249.255' );

		return in_array( $remote_ip, $authorized_ips );
	}
}
