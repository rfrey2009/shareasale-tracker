<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Datafeed {

	/**
	* Generates, compresses, and cleans up product datafeed files
	* @var string $version Plugin version
	* @var string $version WooCommerce version
	* @var WP_Filesystem $filesystem WordPress filesystem object https://codex.wordpress.org/Filesystem_API
	* @var WP_Error $errors any datafeed generation failure errors
	*/
	private $version, $wc_version, $filesystem;
	public $errors;

	public function __construct( $version, $filesystem ) {
		$this->version    = $version;
		$this->wc_version = WC()->version;
		$this->filesystem = $filesystem;
		$this->errors     = new WP_Error();
		$this->load_dependencies();

		if ( ! $this->filesystem instanceof WP_Filesystem ) {
			$this->errors->add( 'filesystem', 'WP Filesystem API not initialized properly!' );
			return false;
		}

		return $this;
	}

	private function load_dependencies() {
		require_once plugin_dir_path( __FILE__ ) . 'class-shareasale-wc-tracker-datafeed-logger.php';
		$this->logger = new ShareASale_WC_Tracker_Datafeed_Logger( $this->version );
	}

	public function export( $file ) {
		$product_posts  = $this->get_all_product_posts();
		$rows = array();

		foreach ( $product_posts as $product_post ) {
			//protect against instantiating somehow orphaned variations (causing an exception) by checking its parent for a post_type value too
			if ( 'product_variation' == $product_post->post_type && 'product' == get_post_type( $product_post->post_parent ) ) {
				$product = new WC_Product_Variation( $product_post );
			} elseif ( 'product' == $product_post->post_type ) {
				$product = new WC_Product( $product_post );
			} else {
				continue;
			}

			/*
			* don't bother with a variant product if it has the same non-unique SKU as its parent
			* can't use $product->get_parent_data()['sku'] unless WC v3.0+
			*/
			if ( $product instanceof WC_Product_Variation && $product->get_sku() == get_post_meta( $product_post->post_parent, '_sku', true ) ) {
				unset( $product );
				continue;
			}

			$product->cross_sell_skus = $this->get_cross_sell_skus( $product );
			$rows[]                   = $this->make_row( $product );
			unset( $product );
		}

		if ( ! empty( $rows ) ) {
			$header  = implode( ',', array_keys( $rows[0] ) );
			$content = $header . "\r\n";

			foreach ( $rows as $row ) {
				$content .= implode( ',', $row ) . "\r\n";
			}
			$product_count = count( $rows );
			unset( $rows );

			if ( $csv = $this->write( $file, $content ) ) {
				if ( ! $compressed = $csv->compress( $file ) ) {
					//couldn't compress, so notify user just a csv is available.
					add_settings_error(
						'shareasale_wc_tracker_zip',
						esc_attr( 'datafeed-zip' ),
						$this->errors->get_error_message( 'compress' ) . ' You will need to manually compress the generated csv file into a gz or zip archive before uploading to ShareASale.',
						'notice-warning'
					);
				}

				$path             = esc_url( $file . ( $compressed ? '.zip' : '' ) );
				$product_warnings = array(
					'sku'         => array(
						'messages' => $this->errors->get_error_messages( 'sku' ),
						'data'     => $this->errors->get_error_data( 'sku' ),
					),
					'url'         => array(
						'messages' => $this->errors->get_error_messages( 'url' ),
						'data'     => $this->errors->get_error_data( 'url' ),
					),
					'price'       => array(
						'messages' => $this->errors->get_error_messages( 'price' ),
						'data'     => $this->errors->get_error_data( 'price' ),
					),
					'category'    => array(
						'messages' => $this->errors->get_error_messages( 'category' ),
						'data'     => $this->errors->get_error_data( 'category' ),
					),
					'subcategory' => array(
						'messages' => $this->errors->get_error_messages( 'subcategory' ),
						'data'     => $this->errors->get_error_data( 'subcategory' ),
					),
					/*
					just get first merchant_id error code message since rest will be identical, store in an array for uniformity
					*/
					'merchant_id' => array(
						'messages' => $this->errors->get_error_message( 'merchant_id' ) ? array( $this->errors->get_error_message( 'merchant_id' ) ) : array(),
					),
				);

				$this->logger->log( $path, maybe_serialize( $product_warnings ), $product_count, date( 'Y-m-d H:i:s' ) );
			} else {
				//couldn't even create csv...
				add_settings_error(
					'shareasale_wc_tracker_csv',
					esc_attr( 'datafeed-csv' ),
					$this->errors->get_error_message( 'write' ) . ' Please contact your webhost for more information.'
				);
				settings_errors( 'shareasale_wc_tracker_csv' );
				return false;
			}

			add_settings_error(
				'shareasale_wc_tracker_success',
				esc_attr( 'datafeed-success' ),
				'Generating complete! Download from the link in the table below.',
				'updated'
			);
			settings_errors( 'shareasale_wc_tracker_success' );
			settings_errors( 'shareasale_wc_tracker_zip' );
		}

		return $this;
	}

	private function get_all_product_posts() {
		$product_posts = get_posts(
			array(
				'post_type'   => array( 'product', 'product_variation' ),
				'numberposts' => -1,
				'post_status' => 'publish',
				'order'       => 'ASC',
				'orderby'     => 'ID',
			)
		);

		return $product_posts;
	}

	private function get_cross_sell_skus( $product ) {
		$cross_sell_skus = array();

		if ( version_compare( $this->wc_version, '3.0' ) >= 0 ) {
			$cross_sell_product_ids = $product->get_cross_sell_ids();
		} else {
			$cross_sell_product_ids = $product->get_cross_sells();
		}

		foreach ( $cross_sell_product_ids as $cross_sell_product_id ) {
			$cross_sell_skus[] = get_post_meta( $cross_sell_product_id, '_sku', true );
		}

		return $cross_sell_skus;
	}

	private function make_row( $product ) {
		$options           = get_option( 'shareasale_wc_tracker_options' );
		$merchant_id       = @$options['merchant-id'];
		$product_id        = $product->get_id();
		$category          = get_post_meta( $product_id, 'shareasale_wc_tracker_datafeed_product_category', true ) ?: @$options['default-category'];
		$subcategory       = get_post_meta( $product_id, 'shareasale_wc_tracker_datafeed_product_subcategory', true ) ?: $options['default-subcategory'];
		$merchant_taxonomy = wc_get_product_terms( $product_id, 'product_cat',
			array(
				'orderby' => 'parent',
				'fields' => 'names',
			)
		);

		$row = array(
				//required
				'SKU'                                   => $product->get_sku() ? $product->get_sku() : $this->errors->add(
					'sku',
					'<a target="_blank" href="' . esc_url( get_edit_post_link( $product_id, '' ) ) . '">' . esc_html( $product_id ) . '</a> is missing a SKU.',
					$this->push_error_data( 'sku', $product_id )
				),
				'Name'                                  => $product->get_title(),
				//required
				'URL'                                   => $product->get_permalink() ? $product->get_permalink() : $this->errors->add(
					'url',
					'<a target="_blank" href="' . esc_url( get_edit_post_link( $product_id, '' ) ) . '">' . esc_html( $product_id ) . '</a> is missing a URL.',
					$this->push_error_data( 'url', $product_id )
				),
				//required
				'Price'                                 => $product->get_price() ? $product->get_price() : $this->errors->add(
					'price',
					'<a target="_blank" href="' . esc_url( get_edit_post_link( $product_id, '' ) ) . '">' . esc_html( $product_id ) . '</a> is missing a price.',
					$this->push_error_data( 'price', $product_id )
				),
				'Retailprice'                           => $product->get_regular_price(),
				'FullImage'                             => get_the_post_thumbnail_url( $product_id, 'shop_single' ),
				'ThumbnailImage'                        => get_the_post_thumbnail_url( $product_id, 'shop_thumbnail' ),
				'Commission'                            => '',
				//required
				'Category'                              => $category ? $category : $this->errors->add(
					'category',
					'<a target="_blank" href="' . esc_url( get_edit_post_link( $product_id, '' ) ) . '">' . esc_html( $product_id ) . '</a> is missing a ShareASale category number.',
					$this->push_error_data( 'category', $product_id )
				),
				//required
				'Subcategory'                           => $subcategory ? $subcategory : $this->errors->add(
					'subcategory',
					'<a target="_blank" href="' . esc_url( get_edit_post_link( $product_id, '' ) ) . '">' . esc_html( $product_id ) . '</a> is missing a ShareASale subcategory number.',
					$this->push_error_data( 'subcategory', $product_id )
				),
				'Description'                           => version_compare( $this->wc_version, '3.0' ) >= 0 ? get_post( $product_id )->post_content : $product->get_post_data()->post_content,
				'SearchTerms'                           => version_compare( $this->wc_version, '3.0' ) >= 0 ? strip_tags( wc_get_product_tag_list( $product_id, ',' ) ) : strip_tags( $product->get_tags( ',' ) ),
				'Status'                                => $product->is_in_stock() ? 'instock' : 'soldout',
				//required
				'MerchantID'                            => ! empty( $merchant_id ) ? $merchant_id : $this->errors->add(
					'merchant_id',
					'No <a href="' . esc_url( admin_url( 'admin.php?page=shareasale_wc_tracker' ) ) . '">Merchant ID</a> entered yet.'
				),
				'Custom1'                               => '',
				'Custom2'                               => '',
				'Custom3'                               => '',
				'Custom4'                               => '',
				'Custom5'                               => '',
				'Manufacturer'                          => $product->get_attribute( 'manufacturer' ),
				'PartNumber'                            => $product->get_attribute( 'partnumber' ),
				'MerchantCategory'                      => end( $merchant_taxonomy ),
				'MerchantSubcategory'                   => prev( $merchant_taxonomy ),
				'ShortDescription'                      => '',
				'ISBN'                                  => $product->get_attribute( 'ISBN' ),
				'UPC'                                   => $product->get_attribute( 'UPC' ),
				//array_filter used without callback argument to remove false values from array
				'CrossSell'                             => implode( ',', array_filter( $product->cross_sell_skus ) ),
				'MerchantGroup'                         => prev( $merchant_taxonomy ),
				'MerchantSubgroup'                      => prev( $merchant_taxonomy ),
				'CompatibleWith'                        => '',
				'CompareTo'                             => '',
				'QuantityDiscount'                      => '',
				'Bestseller'                            => $product->is_featured() ? 1 : 0,
				'AddToCartURL'                          => version_compare( $this->wc_version, '3.0' ) >= 0 ? $product->add_to_cart_url() : $product->add_to_cart_url,
				'ReviewsRSSURL'                         => '',
				'Option1'                               => '',
				'Option2'                               => '',
				'Option3'                               => '',
				'Option4'                               => '',
				'Option5'                               => '',
				'customCommissions'                     => '',
				'customCommissionIsFlatRate'            => 0,
				'customCommissionNewCustomerMultiplier' => 1,
				'mobileURL'                             => '',
				'mobileImage'                           => get_the_post_thumbnail_url( $product_id, 'shop_single' ),
				'mobileThumbnail'                       => get_the_post_thumbnail_url( $product_id, 'shop_thumbnail' ),
				'ReservedForFutureUse'                  => '',
				'ReservedForFutureUse'                  => '',
				'ReservedForFutureUse'                  => '',
				'ReservedForFutureUse'                  => '',
			);

		return array_map( array( $this, 'wrap_row' ), $row );
	}

	private function push_error_data( $code, $data ) {
		$error_data = $this->errors->get_error_data( $code );
		if ( is_array( $error_data ) ) {
			$error_data[] = $data;
			return $error_data;
		} else {
			return array( $data );
		}
	}

	private function wrap_row( $value ) {
		$value = trim( $value );
		return '"' . str_replace( '"', '""', $value ) . '"';
	}

	private function write( $file, $content ) {
		if ( ! $this->filesystem->put_contents( $file, $content, FS_CHMOD_FILE ) ) {
			//unfortunately WP_Filesystem doesn't have a more useful WP_Error for put_contents()...
			$this->errors->add( 'write', 'Couldn\'t write CSV file.', $file );
			return false;
		}

		return $this;
	}

	private function compress( $file ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			$this->errors->add( 'compress', 'Couldn\'t compress because PHP Zip extension not installed or enabled.' );
			return false;
		}

		$zip        = new ZipArchive;
		$compressed = $file . '.zip';
		$dir        = dirname( $file );
		//use the WP_Filesystem instance to temporary 0777 chmod the /datafeeds directory so less of a chance ZipArchive::open(), ::addFile(), or ::close() fail
		$this->filesystem->chmod( $dir, 0777 );

		if ( true !== $zip->open( $compressed, ZipArchive::CREATE ) ) {
			$this->errors->add( 'compress', 'Couldn\'t compress because the zip archive cannot be opened.', $compressed );
			$this->filesystem->chmod( $dir, FS_CHMOD_DIR );
			return false;
		}

		if ( ! $zip->addFile( $file, basename( $file ) ) ) {
		    $this->errors->add( 'compress', 'Couldn\'t compress because CSV file not found.', $file );
			$this->filesystem->chmod( $dir, FS_CHMOD_DIR );
			return false;
		}

		if ( ! $zip->close() ) {
		    $this->errors->add( 'compress', 'Couldn\'t compress because the zip archive cannot be closed.', $compressed );
			$this->filesystem->chmod( $dir, FS_CHMOD_DIR );
			return false;
		}

		//delete leftover csv now compressed, and change /datafeeds back to defined directory permissions for WP config...
		$this->filesystem->chmod( $dir, FS_CHMOD_DIR );
		$this->filesystem->delete( $file );
		return $this;
	}

	public function clean_up( $dir, $days_age = 30 ) {
		if ( ! is_numeric( $days_age ) ) {
			$days_age = 30;
		}

		$files = $this->filesystem->dirlist( $dir );

		foreach ( $files as $file_details ) {
			$file = trailingslashit( $dir ) . $file_details['name'];
			if ( time() - $file_details['lastmodunix'] > ( 60 * 60 * 24 * $days_age ) ) {
				$this->filesystem->delete( $file );
			}
		}
	}
}
