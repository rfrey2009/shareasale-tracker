<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_API {

	private $api_version = 2.8;
	private $api_token;
	private $api_secret;
	private $merchant_id;
	private $headers;
	private $timestamp;
	private $action;
	private $query;
	private $last_query;
	private $error_msg;
	private $response;

	public function __construct( $merchant_id, $api_token, $api_secret ) {
		$this->merchant_id  = $merchant_id;
		$this->api_token    = $api_token;
		$this->api_secret   = $api_secret;
		return $this;
	}

	private function build_url( $params = array() ) {
		$protocol    = 'https://';
		$hostname    = 'api.shareasale.com/';
		$handler     = 'w.cfm';
		$params      = array_merge(
			array(
				'action'     => $this->action,
				'merchantid' => $this->merchant_id,
				'token'      => $this->api_token,
				'version'    => $this->api_version,
			),
			$params
		);

		$query_string = '?' . http_build_query( $params );
		$url = $protocol . $hostname . $handler . $query_string;
		return $url;
	}

	private function authenticate() {
		//can't authenticate without an action verb set already...
		if ( ! $this->action ) {
			return false;
		}

		$this->timestamp  = gmdate( DATE_RFC1123 );
		//build auth headers
		$sig      = $this->api_token . ':' . $this->timestamp . ':' . $this->action . ':' . $this->api_secret;
		$sig_hash = hash( 'sha256', $sig );

		$this->headers = array( "x-ShareASale-Date: {$this->timestamp}", "x-ShareASale-Authentication: {$sig_hash}" );
		return true;
	}

	public function edit_trans( $order_number, $order_date, $new_amount, $new_comment ) {
		$this->action = 'edit';
		$params       = array(
							'ordernumber' => $order_number,
							'date'        => $order_date,
							'newamount'   => $new_amount,
						);
		//only pass a new comment if it exists so as not to overwrite the standard ShareASale transaction comment "Sale - Ordernumber"
		if ( $new_comment ) {
			$params['newcomment'] = $new_comment;
		}

		$this->query  = $this->build_url( $params );
		return $this;
	}

	public function void_trans( $order_number, $order_date, $reason = '' ) {
		$this->action = 'void';
		$params       = array(
							'ordernumber' => $order_number,
							'date'        => $order_date,
							'reason'      => $reason,
						);
		$this->query  = $this->build_url( $params );
		return $this;
	}

	public function token_count() {
		$this->action = 'apitokencount';
		$this->query  = $this->build_url();
		return $this;
	}

	public function exec() {
		//build authentication headers before making API request
		if ( ! $this->authenticate() ) {
			$this->error_msg = 'Could not authenticate. No API action value.';
			return false;
		}

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $this->query );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->headers );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		//make the API request
		$response = curl_exec( $ch );
		curl_close( $ch );
		//set last_query property and clear out current query property
		$this->last_query = $this->query;
		$this->query      = '';
		if ( strpos( $response, 'Error Code' ) ) {
			// error occurred... store it and return false
			$this->error_msg = trim( $response );
			return false;
		}
		$this->response = trim( $response );
		return true;
	}

	//getters
	public function get_merchant_id() {
		return $this->merchant_id;
	}

	public function get_last_query() {
		return $this->last_query;
	}

	public function get_response() {
		return $this->response;
	}

	public function get_error_msg() {
		return $this->error_msg;
	}
}

