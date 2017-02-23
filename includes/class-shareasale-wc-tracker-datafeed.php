<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Datafeed {

	/**
	* @var float $version Plugin version
	* @var WP_Filesystem $filesystem WordPress filesystem object https://codex.wordpress.org/Filesystem_API
	* @var string $error_msg any failure messages
	*/
	private $version, $filesystem, $error_msg;

	public function __construct( $version, $filesystem ) {
		$this->version    = $version;
		$this->filesystem = $filesystem;

		if ( ! $this->filesystem instanceof WP_Filesystem ) {
			$this->error_msg = 'WP Filesystem API not initialized properly';
			return false;
		}

		return $this;
	}

	public function export( $dir ) {
		$product_posts  = $this->get_all_product_posts();
		$rows = array();

		foreach ( $product_posts as $product_post ) {
			//WC_Product constructor accepts WP post objects
			$product                  = new WC_Product( $product_post );
			$product->cross_sell_skus = $this->get_cross_sell_skus( $product );
			$rows[]                   = $this->make_row( $product );
			unset( $product );
		}

		$content = '';
		foreach ( $rows as $row ) {
			$content .= implode( ',', $row ) . "\r\n";
		}
		unset( $rows );

		$this->write_file( $dir, $content );
		$this->compress();

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

		foreach ( $product->get_cross_sells() as $cross_sell_product_id ) {
			$cross_sell_skus[] = get_post_meta( $cross_sell_product_id, '_sku', true );
		}

		return $cross_sell_skus;
	}

	private function make_row( $product ) {
		$options     = get_option( 'shareasale_wc_tracker_options' );
		$merchant_id = @$options['merchant-id'];

		$row = array(
				'SKU'                                   => $product->get_sku(),
				'Name'                                  => $product->get_title(),
				'URL'                                   => $product->get_permalink(),
				'Price'                                 => $product->get_sale_price(),
				'Retailprice'                           => $product->get_price(),
				'FullImage'                             => wp_get_attachment_image_src( $product->get_gallery_attachment_ids()[0], 'shop_single' )[0],
				'ThumbnailImage'                        => wp_get_attachment_image_src( $product->get_gallery_attachment_ids()[0], 'shop_thumbnail' )[0],
				'Commission'                            => '',
				'Category'                              => '',
				'Subcategory'                           => '',
				'Description'                           => $product->get_post_data()->post_content,
				'SearchTerms'                           => '',
				'Status'                                => 'instock' == $product->stock_status? 'instock' : 'soldout',
				'MerchantID'                            => empty( $merchant_id ) ? '' : $merchant_id,
				'Custom1'                               => '',
				'Custom2'                               => '',
				'Custom3'                               => '',
				'Custom4'                               => '',
				'Custom5'                               => '',
				'Manufacturer'                          => $product->get_attribute( 'manufacturer' ),
				'PartNumber'                            => $product->get_attribute( 'partnumber' ),
				'MerchantCategory'                      => '',
				'MerchantSubcategory'                   => '',
				'ShortDescription'                      => '',
				'ISBN'                                  => $product->get_attribute( 'ISBN' ),
				'UPC'                                   => $product->get_attribute( 'UPC' ),
				'CrossSell'                             => implode( ',', $product->cross_sell_skus ),
				'MerchantGroup'                         => '',
				'MerchantSubgroup'                      => '',
				'CompatibleWith'                        => '',
				'CompareTo'                             => '',
				'QuantityDiscount'                      => '',
				'Bestseller'                            => $product->is_featured() ? 1 : 0,
				'AddToCartURL'                          => $prodcut->add_to_cart_url,
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
				'mobileImage'                           => wp_get_attachment_image_src( $product->get_gallery_attachment_ids()[0], 'shop_single' )[0],
				'mobileThumbnail'                       => wp_get_attachment_image_src( $product->get_gallery_attachment_ids()[0], 'shop_thumbnail' )[0],
				'ReservedForFutureUse'                  => '',
				'ReservedForFutureUse'                  => '',
				'ReservedForFutureUse'                  => '',
				'ReservedForFutureUse'                  => '',
			);

		return array_map( array( $this, 'wrap_row' ), $row );
	}

	private static function wrap_row( $value ) {
		$value = trim( $value );
		return '"' . str_replace( '"', '""', $value ) . '"';
	}

	private function write_file( $dir, $content ) {
		$header = '"SKU","Name","URL","Price","Retailprice","FullImage","ThumbnailImage","Commission","Category","Subcategory","Description","SearchTerms","Status","MerchantID","Custom1","Custom2","Custom3","Custom4","Custom5","Manufacturer","PartNumber","MerchantCategory","MerchantSubcategory","ShortDescription","ISBN","UPC","CrossSell","MerchantGroup","MerchantSubgroup","CompatibleWith","CompareTo","QuantityDiscount","Bestseller","AddToCartURL","ReviewsRSSURL","Option1","Option2","Option3","Option4","Option5","customCommissions","customCommissionIsFlatRate","customCommissionNewCustomerMultiplier","mobileURL","mobileImage","mobileThumbnail","ReservedForFutureUse","ReservedForFutureUse","ReservedForFutureUse","ReservedForFutureUse"' . "\r\n";

		$content = $header . $content;

		$filename = trailingslashit( $dir ) . 'datafeed.csv';

		if ( ! $this->filesystem->put_contents( $filename, $content, FS_CHMOD_FILE ) ) {
			error_log( 'couldn\'t write!' );
		}
	}

	private function compress() {

	}

	public function get_error_msg() {
		return $this->error_msg;
	}
}
