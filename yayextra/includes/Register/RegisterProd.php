<?php
namespace YayExtra\Register;

use YayExtra\Register\ScriptName;
use YayExtra\Utils\SingletonTrait;
defined( 'ABSPATH' ) || exit;
/** Register in Production Mode */
class RegisterProd {

	use SingletonTrait;

	/** Hooks Initialization */
	protected function __construct() {
		add_action( 'init', array( $this, 'register_all_scripts' ) );
	}

	public function register_all_scripts() {
		$deps = array( 'react', 'react-dom', 'wp-hooks', 'wp-i18n', 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wc-components' );
		wp_register_script( ScriptName::PAGE_SETTINGS, YAYE_URL . 'assets/dist/js/main.js', $deps, YAYE_VERSION, true );
	}
}
