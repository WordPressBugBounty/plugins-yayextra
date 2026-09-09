<?php
namespace YayExtra\Integrations;

defined( 'ABSPATH' ) || exit;

// Link plugin: https://woocommerce.com/products/name-your-price/ --- Kathy Darling
class WooCommerceNameYourPrice {
	public function __construct() {
		if ( ! function_exists( 'wc_nyp_init' ) ) {
			return;
		}

		add_filter( 'yaye_cart_item_product', array( $this, 'woocommerce_cart_item_product' ), 10, 2 );
	}

	/**
	 * Filter woocommerce_cart_item_product to return product object
	 *
	 * @param object $product Product object.
	 * @param array $cart_item Cart item array.
	 * @return object Product object.
	 */
	public function woocommerce_cart_item_product( $product, $cart_item ) {
		if ( ! empty( $cart_item['data'] ) && is_object( $cart_item['data'] ) ) {
			return $cart_item['data'];
		}
		return $product;
	}
}