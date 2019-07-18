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
	private $response;
	public  $errors;

	public function __construct( $merchant_id, $api_token, $api_secret ) {
		$this->merchant_id = $merchant_id;
		$this->api_token   = $api_token;
		$this->api_secret  = $api_secret;
		$this->errors      = new WP_Error();
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

		$this->headers = array( 'x-ShareASale-Date' => $this->timestamp, 'x-ShareASale-Authentication' => $sig_hash );
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

	public function deal_upload( $sas_coupon ) {
		$this->action = 'dealUpload';
		return $this->deal_edit( $sas_coupon );
	}

	public function deal_edit( $sas_coupon ) {
		//could move the restrictions-building logic out of this API method, but the ShareASale_WC_Tracker_Admin::woocommerce_coupon_options_save() method is already pretty dense...
		$excluded_skus       = array();
		$excluded_categories = array();

		foreach ( $sas_coupon->get_excluded_product_ids() as $product_id ) {
		 	$excluded_skus[] = get_post_meta( $product_id, '_sku', true );
		}
		foreach ( $sas_coupon->get_excluded_product_categories() as $product_category_id ) {
			$category = get_term( $product_category_id );
	 		$excluded_categories[] = $category->name;
		}

		$restrictions = array(
			'individual_use'              => $sas_coupon->get_individual_use()         ? 'No stacking allowed.' : '',
			'exclude_sale_items'          => $sas_coupon->get_exclude_sale_items()     ? 'Sale items excluded.' : '',
			'max_spend'                   => $sas_coupon->get_maximum_amount()         ? 'Maximum amount: $' . $sas_coupon->get_maximum_amount() . '.' : '',
			'min_spend'                   => $sas_coupon->get_minimum_amount()         ? 'Minimum amount: $' . $sas_coupon->get_minimum_amount() . '.' : '',
			'excluded_skus'               => ! empty( $excluded_skus )                 ? 'Excluding SKU(s): ' . implode( ', ', $excluded_skus ) . '.' : '',
			'excluded_product_categories' => ! empty( $excluded_categories )           ? 'Excluding category(ies): ' . implode( ', ', $excluded_categories ) . '.' : '',
			'item_limit'                  => $sas_coupon->get_limit_usage_to_x_items() ? 'Limited to ' . $sas_coupon->get_limit_usage_to_x_items() . ' cart item(s).' : '',
			'user_limit'                  => $sas_coupon->get_usage_limit_per_user()   ? 'Limited to ' . $sas_coupon->get_usage_limit_per_user() . ' time(s) per user.' : '',
		);

		$params = array(
			'title'           => $sas_coupon->get_description(),
			'endDate'         => ! is_null( $sas_coupon->get_date_expires() ) ? $sas_coupon->get_date_expires()->date( 'm/d/Y' ) : 'NEVER',
			'category'        => $sas_coupon->get_discount_type(),
			'textDescription' => $sas_coupon->get_description(),
			'restrictions'    => implode( ' ', array_filter( $restrictions ) ),
			'couponcode'      => $sas_coupon->get_code(),
			'storeId'         => $sas_coupon->get_store_id( 'shareasale_wc_tracker_store_id' ),

		);

		if ( $sas_coupon->get_deal_id( 'shareasale_wc_tracker_deal_id' ) ) {
			$this->action     = 'dealEdit';
			$params['dealID'] = $sas_coupon->get_deal_id( 'shareasale_wc_tracker_deal_id' );
		}

		$this->query = $this->build_url( $params );
		return $this;
	}

	public function exec() {
		//build authentication headers before making API request
		if ( ! $this->authenticate() ) {
			$this->errors->add( 'auth', 'Could not authenticate. No API action value.' );
			return false;
		}

		$response = wp_safe_remote_get(
			$this->query,
			array(
				'headers' => $this->headers,
				'sslverify' => false,
			)
		);
		$body = $response['body'];
		//set last_query property and clear out current query property
		$this->last_query = $this->query;
		$this->query      = '';

		if ( strpos( $body, 'Error Code' ) ) {
			$pieces  = array_map( 'trim', explode( '-', $body ) );
			$code    = str_replace( 'Error Code', '', $pieces[1] );
			$message = str_replace( ':', '', $pieces[0] );
			if ( 4002 == $code ) {
				$message .= ' &middot; Input your webhost\'s IP address (' . $pieces[2] . ') or turn off IP address matching <a target="_blank" href ="' . esc_url( 'https://account.shareasale.com/m-apiips.cfm' ) . '">in ShareASale</a>';
			}
			$data = $this->last_query;
			// error occurred... store it and return false
			$this->errors->add( $code, $message, $data );
			return false;
		}
		$this->response = trim( $body );
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
}
