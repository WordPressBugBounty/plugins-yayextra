<?php
namespace YayExtra;

// use YayExtra\Integrations\YayCurrency;
use YayExtra\Integrations\WooCommerceNameYourPrice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Integrations {

	/**
	 * Instance of the Integrations class.
	 *
	 * @var Integrations
	 */
	protected static $instance = null;

	/**
	 * Function ensure only one instance created.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		// new YayCurrency();
		new WooCommerceNameYourPrice();
	}
}

