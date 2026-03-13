<?php
namespace YayExtra;

use YayExtra\Integrations\YayCurrency;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Integrations {

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
		new YayCurrency();
	}
}

