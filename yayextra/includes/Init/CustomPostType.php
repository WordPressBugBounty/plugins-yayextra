<?php

namespace YayExtra\Init;

use YayExtra\Helper\Utils;

defined( 'ABSPATH' ) || exit;
/**
 * Plugin activate/deactivate logic
 */
class CustomPostType {

	/**
	 * Add actions for init custom post type.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
	}

	/**
	 * Register custom post type
	 */
	public static function register_post_type() {
		$labels       = array(
			'name'               => __( 'Option Set', 'yayextra' ),
			'singular_name'      => __( 'Option Set', 'yayextra' ),
			'add_new'            => __( 'Add New Option Set', 'yayextra' ),
			'add_new_item'       => __( 'Add a new Option Set', 'yayextra' ),
			'edit_item'          => __( 'Edit Option Set', 'yayextra' ),
			'new_item'           => __( 'New Option Set', 'yayextra' ),
			'view_item'          => __( 'View Option Set', 'yayextra' ),
			'search_items'       => __( 'Search Option Set', 'yayextra' ),
			'not_found'          => __( 'No Option Set found', 'yayextra' ),
			'not_found_in_trash' => __( 'No Option Set currently trashed', 'yayextra' ),
			'parent_item_colon'  => '',
		);
		$capabilities = array();
		$args         = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => false,
			'query_var'           => true,
			'rewrite'             => true,
			'capability_type'     => 'yaye_option_set',
			'capabilities'        => $capabilities,
			'hierarchical'        => false,
			'menu_position'       => null,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'supports'            => array( 'title', 'author', 'thumbnail' ),
		);
		register_post_type( 'yaye_option_set', $args );
	}

	/**
	 * Get list option set with params or get all without params.
	 *
	 * @param array   $params filter information.
	 * @param boolean $force_all  Allow get all option sets.
	 *
	 * @return array
	 */
	public static function get_list_option_set( $params = array(), $force_all = false ) {
		global $wpdb;

		$search = ! empty( $params['search'] ) ? $params['search'] : ''; // Option set name
		$option_set_id = ! empty( $params['option_set_id'] ) && is_numeric( $params['option_set_id'] ) ? (int) $params['option_set_id'] : 0; // Option set ID
		$limit = ! empty( $params['page_size'] ) && is_numeric( $params['page_size'] ) ? (int) $params['page_size'] : 10;
		$page = ! empty( $params['current'] ) && is_numeric( $params['current'] ) ? (int) $params['current'] : 1;

		// Build query components
		$base_where = "p.post_type = 'yaye_option_set' AND p.post_status = 'publish'";
		
		// Handle search by ID
		if ( ! empty( $option_set_id ) ) {
			$join_clause = '';
			$search_where = " AND p.ID = %d";
			$search_params = array( $option_set_id );
		} else {
			// Handle search by name (existing functionality)
			$join_clause = ! empty( $search ) ? " INNER JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id" : '';
			$search_where = ! empty( $search ) ? " AND pm.meta_key = '_yaye_name' AND pm.meta_value LIKE %s" : '';
			$search_params = ! empty( $search ) ? array( '%' . $wpdb->esc_like( $search ) . '%' ) : array();
		}

		// Get total count
		$count_query = "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->prefix}posts p{$join_clause} WHERE {$base_where}{$search_where}";
		$total_items = ! empty( $search_params ) ? $wpdb->get_var( $wpdb->prepare( $count_query, $search_params ) ) : $wpdb->get_var( $count_query );

		// Handle force_all case
		if ( $force_all ) {
			$all_query = "SELECT DISTINCT p.* FROM {$wpdb->prefix}posts p{$join_clause} WHERE {$base_where}{$search_where} ORDER BY p.post_date DESC";
			return ! empty( $search_params ) ? $wpdb->get_results( $wpdb->prepare( $all_query, $search_params ) ) : $wpdb->get_results( $all_query );
		}

		// Get paginated results
		$offset = ( $page - 1 ) * $limit;
		$query = "SELECT DISTINCT p.* FROM {$wpdb->prefix}posts p{$join_clause} WHERE {$base_where}{$search_where} ORDER BY p.post_date DESC LIMIT %d OFFSET %d";
		$final_params = array_merge( $search_params, array( $limit, $offset ) );
		$query_result = $wpdb->get_results( $wpdb->prepare( $query, $final_params ) );
		// Build option set list
		$option_set_list = array();
		if ( ! empty( $query_result ) ) {
			foreach ( $query_result as $post ) {
				$option_set_list[] = self::build_option_set_data( $post->ID );
			}
		}

		return array(
			'option_set_list' => $option_set_list,
			'current_page'    => $page,
			'total_items'     => $total_items,
		);
	}

	/**
	 * Build option set data array from post ID.
	 *
	 * @param int $post_id Post ID.
	 * @param bool $with_defaults Whether to include default values for empty fields.
	 * @return array
	 */
	private static function build_option_set_data( $post_id, $with_defaults = false ) {
		$name = get_post_meta( $post_id, '_yaye_name', true );
		$description = get_post_meta( $post_id, '_yaye_description', true );
		$status = get_post_meta( $post_id, '_yaye_status', true );
		$options = get_post_meta( $post_id, '_yaye_options', true );
		$actions = get_post_meta( $post_id, '_yaye_actions', true );
		$products = get_post_meta( $post_id, '_yaye_products', true );
		$custom_css = get_post_meta( $post_id, '_yaye_custom_css', true );

		return array(
			'id'          => $post_id,
			'name'        => $with_defaults ? ( $name ? $name : '' ) : $name,
			'description' => $with_defaults ? ( $description ? $description : '' ) : $description,
			'status'      => $with_defaults ? ( $status ? $status : 0 ) : $status,
			'options'     => $with_defaults ? ( $options ? $options : array() ) : $options,
			'actions'     => $with_defaults ? ( $actions ? $actions : array() ) : $actions,
			'products'    => $with_defaults ? ( $products ? $products : array() ) : $products,
			'custom_css'  => $with_defaults ? ( $custom_css ? $custom_css : '' ) : $custom_css,
		);
	}

	/**
	 * Get option set by id
	 *
	 * @param int $option_set_id Option set id.
	 *
	 * @return array
	 */
	public static function get_option_set( $option_set_id ) {
		return self::build_option_set_data( $option_set_id, true );
	}

	/**
	 * Get option in option set by option_id.
	 *
	 * @param int $option_set_id Option set id.
	 * @param int $option_id     Option id.
	 *
	 * @return array
	 */
	public static function get_option( $option_set_id, $option_id ) {
		$options = get_post_meta( $option_set_id, '_yaye_options', true );
		if ( ! empty( $options ) ) {
			foreach ( $options as $option ) {
				if ( $option_id === $option['id'] ) {
					return $option;
				}
			}
		}
		return array();
	}

	/**
	 * Get list option set by list ids.
	 *
	 * @param int $option_set_ids List option set id.
	 *
	 * @return array
	 */
	public static function get_option_set_array( $option_set_ids = array() ) {
		$result = array();

		if ( ! empty( $option_set_ids ) && is_array( $option_set_ids ) ) {
			foreach ( $option_set_ids as $option_set_id ) {
				$id = (int) $option_set_id;

				$actions = get_post_meta( $id, '_yaye_actions', true );
				foreach ( $actions as $idx_action => $action ) {
					if ( ! empty( $action['subActions'] ) ) {
						foreach ( $action['subActions'] as $idx_subaction => $sub_action ) {
							$action_val = ! empty( $sub_action['subActionValue'] ) ? $sub_action['subActionValue'] : 0;
							$sub_action_val = Utils::get_price_from_currency_plugin( $action_val );
							$actions[ $idx_action ]['subActions'][ $idx_subaction ]['subActionValueYayCurrency'] = $sub_action_val;
						}
					}
				}

				$result[] = array(
					'id'          => $id,
					'name'        => get_post_meta( $id, '_yaye_name', true ),
					'description' => get_post_meta( $id, '_yaye_description', true ),
					'status'      => get_post_meta( $id, '_yaye_status', true ),
					'options'     => get_post_meta( $id, '_yaye_options', true ),
					'actions'     => $actions,
					'products'    => get_post_meta( $id, '_yaye_products', true ),
					'custom_css'  => get_post_meta( $id, '_yaye_custom_css', true ),
				);
			}
		}

		return $result;
	}

	/**
	 * Create new option set.
	 *
	 * @return int New option set id.
	 */
	public static function create_new_option_set() {
			$args      = array(
				'post_content' => '',
				// 'post_date'     => current_time( 'Y-m-d H:i:s' ),
				// 'post_date_gmt' => current_time( 'Y-m-d H:i:s' ),
				'post_type'    => 'yaye_option_set',
				'post_title'   => 'YayExtra Option Set',
				'post_status'  => 'publish',
			);
			$insert_id = wp_insert_post( $args );

			update_post_meta( $insert_id, '_yaye_name', 'Sample Option Set' );
			update_post_meta( $insert_id, '_yaye_description', 'Sample description' );
			update_post_meta( $insert_id, '_yaye_status', 0 );
			update_post_meta( $insert_id, '_yaye_options', array() );
			update_post_meta( $insert_id, '_yaye_actions', array() );
			update_post_meta(
				$insert_id,
				'_yaye_products',
				array(
					'product_filter_type'          => 1, // 1 : one by one (default), 2 : by conditions,
					'product_filter_one_by_one'    => array(),
					'product_filter_by_conditions' => array(
						'match_type' => array(
							'label' => 'Any',
							'value' => 'any',
						),
						'conditions' => array(),
					),
				)
			);
			update_post_meta( $insert_id, '_yaye_custom_css', '' );

			return $insert_id;
	}

	/**
	 * Duplicate option set.
	 *
	 * @param int $id Id of option set need to be duplicated.
	 *
	 * @return int New option set id.
	 */
	public static function duplicate_option_set( $id ) {
		if ( ! empty( $id ) ) {
			$args      = array(
				'post_content' => '',
				'post_type'    => 'yaye_option_set',
				'post_title'   => 'YayExtra Option Set',
				'post_status'  => 'publish',
			);
			$insert_id = wp_insert_post( $args );

			$option_set_org = self::get_option_set( (int) $id );
			$option_list    = $option_set_org['options'];
			$action_list    = $option_set_org['actions'];
			foreach($option_list as $key => $option) {
				$option_id_old = $option['id'];
				$option_id_new = Utils::gen_uuid();
				// New option id
				$option_list[$key]['id'] = $option_id_new;

				// Replace by New option id of Option Logics
				$option_list_clone = $option_list;
				foreach($option_list_clone as $key_clone => $option_clone) { 
					if ( $option_clone['id'] !== $option_id_new ) { // must be different the new option id
						$option_clone_logics = $option_clone['logics'];
						foreach($option_clone_logics as $logic_clone_key => $opt_clone_logic) {
							if( ! empty( $opt_clone_logic ) && ! empty ( $opt_clone_logic['option'] ) ) {
								if( $opt_clone_logic['option']['id'] === $option_id_old) { 
									$option_list[$key_clone]['logics'][$logic_clone_key]['option']['id'] = $option_id_new;
									$option_list[$key_clone]['logics'][$logic_clone_key]['option']['value'] = $option_id_new;
								}
							}
						}
					}
				}
			
				// Replace by New option id of Action Logics
				foreach($action_list as $action_key => $action) {
					$action_id_new = Utils::gen_uuid();
					// New option id
					$action_list[$action_key]['id'] = $action_id_new;
					if ( ! empty( $action['conditions'] ) ) {
						$action_conditions = $action['conditions'];
						foreach($action_conditions as $action_condition_key => $action_condition) {
							if( ! empty( $action_condition ) && ! empty ( $action_condition['optionId'] )) {
								if( $action_condition['optionId']['id'] === $option_id_old) {
									$action_list[$action_key]['conditions'][$action_condition_key]['optionId']['id']    = $option_id_new;
									$action_list[$action_key]['conditions'][$action_condition_key]['optionId']['value'] = $option_id_new;
								}
							}
						}
					}
			
				}
			}

			update_post_meta( $insert_id, '_yaye_name', $option_set_org['name'] );
			update_post_meta( $insert_id, '_yaye_description', $option_set_org['description'] );
			update_post_meta( $insert_id, '_yaye_status', $option_set_org['status'] );
			update_post_meta( $insert_id, '_yaye_options', $option_list );
			update_post_meta( $insert_id, '_yaye_actions', $action_list );
			update_post_meta( $insert_id, '_yaye_products', $option_set_org['products'] );
			update_post_meta( $insert_id, '_yaye_custom_css', $option_set_org['custom_css'] );

			return $insert_id;
		} else {
			return null;
		}
	}
	/**
	 * Create option set from data.
	 *
	 * @param int $data Data.
	 *
	 * @return int New option set id.
	 */
	public static function create_option_set_from_data( $data ) {
		if ( empty( $data ) ) {
			return null;
		}

		$args      = array(
			'post_content' => '',
			'post_type'    => 'yaye_option_set',
			'post_title'   => 'YayExtra Option Set',
			'post_status'  => 'publish',
		);
		$insert_id = wp_insert_post( $args );

		update_post_meta( $insert_id, '_yaye_name', $data['name'] );
		update_post_meta( $insert_id, '_yaye_description', $data['description'] );
		update_post_meta( $insert_id, '_yaye_status', $data['status'] );
		update_post_meta( $insert_id, '_yaye_options', $data['options'] );
		update_post_meta( $insert_id, '_yaye_actions', $data['actions'] );
		update_post_meta( $insert_id, '_yaye_products', $data['products'] );
		update_post_meta( $insert_id, '_yaye_custom_css', $data['custom_css'] );

		return $insert_id;
	}
}
