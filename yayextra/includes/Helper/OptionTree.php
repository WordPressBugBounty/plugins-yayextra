<?php
namespace YayExtra\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Walk the nested option tree (steps / accordions / leaf).
 */
class OptionTree {

	const KIND_LEAF       = 'leaf';
	const KIND_STEPS      = 'steps';
	const KIND_STEP       = 'step';
	const KIND_ACCORDIONS = 'accordions';
	const KIND_ACCORDION  = 'accordion';

	/**
	 * Option types locked to Pro. Lite storefront does not render these.
	 *
	 * @var string[]
	 */
	const PRO_ONLY_TYPES = array(
		'button_multi',
		'swatches_multi',
		'date_picker',
		'time_picker',
		'file_upload',
		'file_download',
		'image_upload',
		'popup',
		'product_list',
		'link',
	);

	/**
	 * @param array|null $option Option node.
	 * @return string
	 */
	public static function get_kind( $option ) {
		$type = isset( $option['type']['value'] ) ? (string) $option['type']['value'] : '';
		if ( in_array( $type, array( self::KIND_STEPS, self::KIND_STEP, self::KIND_ACCORDIONS, self::KIND_ACCORDION ), true ) ) {
			return $type;
		}
		return self::KIND_LEAF;
	}

	/**
	 * @param array|null $option Option node.
	 * @return bool
	 */
	public static function is_leaf( $option ) {
		return self::KIND_LEAF === self::get_kind( $option );
	}

	/**
	 * Whether this option type is Pro-only (not rendered on Lite storefront).
	 *
	 * @param string $type Option type value.
	 * @return bool
	 */
	public static function is_pro_only_type( $type ) {
		return in_array( (string) $type, self::PRO_ONLY_TYPES, true );
	}

	/**
	 * Flatten leaf options from a tree (skips steps/step containers).
	 *
	 * @param array $options Tree.
	 * @return array
	 */
	public static function flatten_leaves( $options = array() ) {
		$out = array();
		if ( empty( $options ) || ! is_array( $options ) ) {
			return $out;
		}
		foreach ( $options as $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}
			if ( self::is_leaf( $option ) ) {
				$out[] = $option;
				continue;
			}
			$children = isset( $option['children'] ) && is_array( $option['children'] ) ? $option['children'] : array();
			$out      = array_merge( $out, self::flatten_leaves( $children ) );
		}
		return $out;
	}

	/**
	 * Find a node by id anywhere in the tree.
	 *
	 * @param array  $options Tree.
	 * @param string $id      Node id.
	 * @return array
	 */
	public static function find_by_id( $options, $id ) {
		if ( empty( $options ) || ! is_array( $options ) ) {
			return array();
		}
		foreach ( $options as $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}
			if ( isset( $option['id'] ) && (string) $option['id'] === (string) $id ) {
				return $option;
			}
			$children = isset( $option['children'] ) && is_array( $option['children'] ) ? $option['children'] : array();
			$found    = self::find_by_id( $children, $id );
			if ( ! empty( $found ) ) {
				return $found;
			}
		}
		return array();
	}

	/**
	 * Frontend script expects a flat `options` list of leaves (logics / costs).
	 *
	 * @param array $sets Option sets from get_option_set_array.
	 * @return array
	 */
	public static function flatten_sets_for_script( $sets ) {
		if ( empty( $sets ) || ! is_array( $sets ) ) {
			return array();
		}
		foreach ( $sets as &$set ) {
			if ( ! empty( $set['options'] ) && is_array( $set['options'] ) ) {
				$set['options'] = self::flatten_leaves( $set['options'] );
			}
		}
		unset( $set );
		return $sets;
	}
}
