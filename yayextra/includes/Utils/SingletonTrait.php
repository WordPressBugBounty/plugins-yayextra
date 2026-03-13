<?php
namespace YayExtra\Utils;

trait SingletonTrait {

	private static $instances = [];

	protected function __construct() { }

	public static function get_instance( ...$args ) {
		$class = get_called_class();
		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new $class( ...$args );
		}

		return self::$instances[ $class ];
	}

	/** Singletons should not be cloneable. */
	protected function __clone() { }

	/** Singletons should not be restorable from strings. */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize a singleton.' );
	}
}
