<?php
namespace YayExtra\Init;

use YayExtra\Helper\Utils;
use YayExtra\Init\CustomPostType;
use YayExtra\Classes\ProductPage;
defined( 'ABSPATH' ) || exit;
/**
 * Ajax class
 */
class Ajax {

	/**
	 * Add actions for init class.
	 */

	public function __construct() {
		$this->add_ajax_event();
	}

	/**
	 * Get no-private events.
	 *
	 * @return array
	 */
	public function define_noprivate_events() {
		return array(
			// 'handle_image_upload',
			// 'handle_image_swatches_upload',
		);
	}

	/**
	 * Get private events.
	 *
	 * @return array
	 */
	public function define_private_events() {
		return array(
			'get_option_sets',
			'get_option_set',
			'add_new_option_set',
			'import_option_sets',
			'duplicate_option_set',
			'change_option_set_status',
			'delete_option_set',
			'delete_option_sets',
			'save_option_set',
			'get_product_list',
			'get_product_category_list',
			'get_product_tag_list',
			'filter_product_meta',
			'get_product_list_items',
			'get_product_picker_suggestions',
			// 'handle_image_upload',
			// 'handle_image_swatches_upload',
			'save_settings',
			'get_settings',
			'update_option_set_products_one_by_one',
			'update_reviewed_flag',
			'sync_assigned_products',
		);
	}

	/**
	 * Add wp ajax events.
	 *
	 * @return void
	 */
	public function add_ajax_event() {

		// no-private events.
		$noprivate_events = $this->define_noprivate_events();
		foreach ( $noprivate_events as $event ) {
			add_action( 'wp_ajax_nopriv_yaye_' . $event, array( $this, $event ) );
		}

		// private events.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		$private_events = $this->define_private_events();
		foreach ( $private_events as $event ) {
			add_action( 'wp_ajax_yaye_' . $event, array( $this, $event ) );
		}
	}

	/**
	 * Ajax get option sets with pagination.
	 *
	 * @throws \Exception Exception when get data.
	 *
	 * @return void
	 */
	public function get_option_sets() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$pagination = sanitize_text_field( wp_unslash( isset( $_POST['params'] ) ? $_POST['params'] : "" ) );
			$pagination = json_decode( $pagination, true );

			$data            = CustomPostType::get_list_option_set( $pagination );
			$list_option_set = $data['option_set_list'];
			wp_send_json_success(
				array(
					'list_option_set' => $list_option_set,
					'current_page'    => $data['current_page'],
					'total_items'     => $data['total_items'],
				),
				200
			);
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Add missing UUIDs to optionValues inside Option Set.
	 */
	private function migrate_option_value_id($option_set) {

		$has_changed = false;

		if (empty($option_set['options']) || !is_array($option_set['options'])) {
			return array(
				'data'        => $option_set,
				'has_changed' => false,
			);
		}

		foreach ($option_set['options'] as &$option) {

			if (empty($option['optionValues']) || !is_array($option['optionValues'])) {
				continue;
			}

			$first = $option['optionValues'][0] ?? null;

			// if id is not empty -> skip
			if (!empty($first['id'])) {
				continue;
			}

			// if id is empty -> migrate for all items
			foreach ($option['optionValues'] as &$valueItem) {
				if (empty($valueItem['id'])) {
					$valueItem['id'] = wp_generate_uuid4();
					$has_changed = true;
				}
			}
		}

		return array(
			'data'        => $option_set,
			'has_changed' => $has_changed,
		);
	}

/**
 * Migrate old logic value data to include id (id in optionValue).
 */
private function migrate_option_set_logic_value( $option_set ) {
	$has_changed = false;

	if ( empty( $option_set['options'] ) || ! is_array( $option_set['options'] ) ) {
		return array( 'data' => $option_set, 'has_changed' => false );
	}

	/**
	 * Helper: always return array of items
	 */
	$normalize_value = function( $value ) {
		if ( empty( $value ) ) {
			return array();
		}
		// If already array of objects → return
		if ( is_array( $value ) && isset( $value[0] ) && is_array( $value[0] ) ) {
			return $value;
		}
		// Convert single object → array
		return array( $value );
	};

	/**
	 * MIGRATE LOGICS
	 */
	$force_array_types = array( 'checkbox', 'button_multi', 'swatches_multi' );
	foreach ( $option_set['options'] as &$option ) {

		if ( empty( $option['logics'] ) ) {
			continue;
		}

		foreach ( $option['logics'] as &$logic ) {

			// If already has ID → skip
			if ( isset( $logic['value']['id'] ) && $logic['value']['id'] !== '' ) {
				continue;
			}

			if ( empty( $logic['option']['id'] ) ) {
				continue;
			}

			$target_option_id = $logic['option']['id'];

			// Find corresponding option
			$target_option = null;
			foreach ( $option_set['options'] as $opt ) {
				if ( $opt['id'] === $target_option_id ) {
					$target_option = $opt;
					break;
				}
			}
			if ( ! $target_option || empty( $target_option['optionValues'] ) ) {
				continue;
			}

			// Normalize value to array
			$original_value = $logic['value'];
			$value_list     = $normalize_value( $original_value );

			$mapped_list = array();

			foreach ( $value_list as $item ) {
				$current_value = $item['value'] ?? null;
				if ( ! $current_value ) {
					$mapped_list[] = $item;
					continue;
				}

				// Match in optionValues
				$found = false;
				foreach ( $target_option['optionValues'] as $ov ) {
					if ( $ov['value'] === $current_value ) {
						$mapped_list[] = array(
							'id'    => $ov['id'],
							'value' => $ov['value'],
							'label' => $item['label'] ?? $ov['value'],
						);
						$has_changed = true;
						$found       = true;
						break;
					}
				}

				if ( ! $found ) {
					// fallback
					$mapped_list[] = $item;
				}
			}

			// Restore single or array
			$option_type = $logic['option']['type']['value'] ?? null;
			if ( in_array( $option_type, $force_array_types, true ) ) {
				$logic['value'] = $mapped_list;
			} else {
				$logic['value'] = ( count( $mapped_list ) === 1 ) ? $mapped_list[0] : $mapped_list;
			}
		}
	}

	/**
	 * MIGRATE ACTION CONDITIONS
	 */
	if ( ! empty( $option_set['actions'] ) ) {
		foreach ( $option_set['actions'] as &$action ) {

			if ( empty( $action['conditions'] ) ) {
				continue;
			}

			foreach ( $action['conditions'] as &$condition ) {

				if ( isset( $condition['value']['id'] ) && $condition['value']['id'] !== '' ) {
					continue;
				}

				$target_option_id = $condition['optionId']['id'] ?? null;
				if ( ! $target_option_id ) {
					continue;
				}

				// Find corresponding option
				$target_option = null;
				foreach ( $option_set['options'] as $opt ) {
					if ( $opt['id'] === $target_option_id ) {
						$target_option = $opt;
						break;
					}
				}
				if ( ! $target_option ) {
					continue;
				}

				$original_value = $condition['value'];
				$value_list     = $normalize_value( $original_value );

				$mapped_list = array();

				foreach ( $value_list as $item ) {
					$current_value = $item['value'] ?? null;

					if ( ! $current_value ) {
						$mapped_list[] = $item;
						continue;
					}

					$found = false;
					foreach ( $target_option['optionValues'] as $ov ) {
						if ( $ov['value'] === $current_value ) {
							$mapped_list[] = array(
								'id'    => $ov['id'],
								'value' => $ov['value'],
								'label' => $item['label'] ?? $ov['value'],
							);
							$has_changed = true;
							$found       = true;
							break;
						}
					}

					if ( ! $found ) {
						$mapped_list[] = $item;
					}
				}

				$cond_type = $condition['type']['value'] ?? null;
				if ( in_array( $cond_type, $force_array_types, true ) ) {
					$condition['value'] = $mapped_list;
				} else {
					$condition['value'] = ( count( $mapped_list ) === 1 ) ? $mapped_list[0] : $mapped_list;
				}
			}
		}
	}

	return array(
		'data'        => $option_set,
		'has_changed' => $has_changed,
	);
}

	/**
	 * Ajax get option set by id.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function get_option_set() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$id         = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : null ) );
			$option_set = CustomPostType::get_option_set( $id );
			//migrate option set data
			$migrated_option_value_id = $this->migrate_option_value_id( $option_set );
			$option_set = $migrated_option_value_id['data'];

			if ( $migrated_option_value_id['has_changed'] ) {
				update_post_meta( $id, '_yaye_options', $option_set['options'] );
			}

			$migrated_logics_actions = $this->migrate_option_set_logic_value( $option_set );
			$option_set = $migrated_logics_actions['data'];

			if ( $migrated_logics_actions['has_changed'] ) {
				update_post_meta( $id, '_yaye_options', $option_set['options'] );
				update_post_meta( $id, '_yaye_actions', $option_set['actions'] );
			}
			
			
			//get products
			$filters = get_post_meta( $id, '_yaye_products', true );

			if ( 1 === $filters['product_filter_type'] ) {
				$params = array(
					'option_set_id' => $id,
					'current'       => 1,
					'page_size'     => 10,
					'product_type'  => 'all',
				);
				$data   = Utils::get_products_match( null, null, $params );
			} else {
				$params    = array(
					'current'   => 1,
					'page_size' => 10,
				);
				$apply     = $filters['product_filter_by_conditions']['match_type'];
				$condition = $filters['product_filter_by_conditions']['conditions'];
				$data      = Utils::get_products_match( $condition, $apply, $params );
			}

			wp_send_json_success(
				array(
					'option_set'     => $option_set,
					'total_products' => $data['total_items'],
				),
				200
			);
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax add new option set.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function add_new_option_set() {
		try {
			Utils::check_nonce();
			$option_set_id = CustomPostType::create_new_option_set();
			wp_send_json_success( array( 'option_set_id' => $option_set_id ), 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax duplicate option set.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function duplicate_option_set() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$id            = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : null ) );
			$option_set_id = CustomPostType::duplicate_option_set( $id );
			if ( ! empty( $option_set_id ) ) {
				wp_send_json_success( array( 'option_set_id' => $option_set_id ), 200 );
			} else {
				wp_send_json_error( array( 'msg' => __( 'Duplicate option set failed.', 'yayextra' ) ) );
			}
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax Import option sets.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function import_option_sets() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$files = $_FILES;

			/**
			 * Check do not have JSON file.
			 */
			if ( empty( $files ) ) {
				wp_send_json_error( array( 'msg' => __( 'Import at least 1 JSON file', 'yayextra' ) ) );
			}

			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			$count = 0;

			foreach ( $files as $file ) {

				/**
				 * If file type is not JSON then skip
				 */
				if ( 'application/json' !== $file['type'] ) {
					continue;
				}

				if ( empty( $file['tmp_name'] ) ) {
					continue;
				}

				/**
				 * Get file content
				 */
				$file_tmp_name = sanitize_text_field( $file['tmp_name'] );
				$file_content  = $wp_filesystem->get_contents( $file_tmp_name );
				$data          = json_decode( $file_content, true );
				$data          = $data['optionSets'];

				/**
				 * Get valid data from file
				 */
				// $data = Utils::check_valid_option_set_data( $data );

				if ( empty( $data ) ) {
					continue;
				}

				$data = array_reverse( $data );
				foreach ( $data as $option_set ) {
					/**
				 * Create option set from data
				 */
					$option_set_id = CustomPostType::create_option_set_from_data( $option_set );

					if ( ! empty( $option_set_id ) ) {
						$count ++;
					}
				}
			}

			wp_send_json_success( array( 'count' => $count ), 200 );

		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax change option set status.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function change_option_set_status() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$id    = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : null ) );
			$value = sanitize_text_field( wp_unslash( isset( $_POST['value'] ) ? $_POST['value'] : null ) );
			update_post_meta( $id, '_yaye_status', $value );
			wp_send_json_success( 'success', 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax delete option set.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function delete_option_set() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$id = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : null ) );
			wp_delete_post( $id );
			wp_send_json_success( 'success', 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax delete option sets.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function delete_option_sets() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$ids = sanitize_text_field( wp_unslash( isset( $_POST['ids'] ) ? $_POST['ids'] : "" ) );
			$ids = json_decode( $ids, true );
			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					wp_delete_post( $id );
				}
			}
			wp_send_json_success( 'success', 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax save option set.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function save_option_set() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$raw        = isset( $_POST['option_set'] ) ? wp_unslash( $_POST['option_set'] ) : '';
			$option_set = json_decode( $raw, true );
			// Only entity-decode the payload if JSON parse failed (do NOT decode string
			// values like "&lt;a&gt;Lui&lt;/a&gt;" — those must stay literal in the editor).
			if ( ! is_array( $option_set ) ) {
				$option_set = json_decode( html_entity_decode( $raw, ENT_COMPAT, 'UTF-8' ), true );
			}

			if ( empty( $option_set ) || ! is_array( $option_set ) || empty( $option_set['id'] ) ) {
				throw new \Exception( __( 'Invalid option set data', 'yayextra' ) );
			}

			$id = $option_set['id'];
			if ( ! empty( $option_set['options'] ) && is_array( $option_set['options'] ) ) {
				$option_set['options'] = Utils::sanitize_option_html_fields( $option_set['options'] );
			}
			update_post_meta( $id, '_yaye_name', $option_set['name'] );
			update_post_meta( $id, '_yaye_description', $option_set['description'] );
			update_post_meta( $id, '_yaye_status', $option_set['status'] );
			update_post_meta( $id, '_yaye_options', $option_set['options'] );
			update_post_meta( $id, '_yaye_actions', $option_set['actions'] );
			update_post_meta( $id, '_yaye_products', $option_set['products'] );
			update_post_meta( $id, '_yaye_custom_css', $option_set['custom_css'] );
			wp_send_json_success( 'success', 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax get product list category.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function get_product_category_list() {
		try {
			Utils::check_nonce();
			$result = Utils::get_product_categories();
			wp_send_json_success( array( 'product_category_list' => $result ), 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax get product list product tag.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function get_product_tag_list() {
		try {
			Utils::check_nonce();
			$result = Utils::get_product_tags();
			wp_send_json_success( array( 'product_tag_list' => $result ), 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax get product list product tag.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function get_product_list() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$condition = sanitize_text_field( wp_unslash( isset( $_POST['condition'] ) ? $_POST['condition'] : "" ) );
			$condition = json_decode( $condition, true );
			$apply     = sanitize_text_field( wp_unslash( isset( $_POST['apply'] ) ? $_POST['apply'] : "" ) );
			$apply     = json_decode( $apply, true );

			$params = sanitize_text_field( wp_unslash( isset( $_POST['params'] ) ? $_POST['params'] : "" ) );
			$params = json_decode( $params, true );

			$response_data = Utils::get_products_match( $condition, $apply, $params );

			wp_send_json_success(
				array(
					'list_product' => $response_data['product_list'],
					'current_page' => $response_data['current_page'],
					'total_items'  => $response_data['total_items'],
				),
				200
			);
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax get product by meta.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function filter_product_meta() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$filter        = sanitize_text_field( wp_unslash( isset( $_POST['filter'] ) ? $_POST['filter'] : "" ) );
			$filter        = json_decode( $filter, true );
			$response_data = Utils::filter_product_meta( $filter );
			wp_send_json_success( $response_data, 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax get product list items by IDs (for Product list option type).
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function get_product_list_items() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$product_ids = sanitize_text_field( wp_unslash( isset( $_POST['product_ids'] ) ? $_POST['product_ids'] : '' ) );
			$product_ids = json_decode( $product_ids, true );
			if ( ! is_array( $product_ids ) ) {
				$product_ids = array();
			}
			$response_data = Utils::get_product_list_items_by_ids( $product_ids );
			wp_send_json_success(
				array(
					'items' => $response_data,
				),
				200
			);
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax get a few recent products for Product list picker empty state.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function get_product_picker_suggestions() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$limit = isset( $_POST['limit'] ) ? absint( wp_unslash( $_POST['limit'] ) ) : 5;
			wp_send_json_success( Utils::get_product_picker_suggestions( $limit ), 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax for upload image.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function handle_image_upload() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$FILES      = $_FILES;
			$image_data = Utils::sanitize_array( isset( $FILES['yayextra-product-image'] ) ? $FILES['yayextra-product-image'] : array() );

			$product_page = ProductPage::get_instance();
			$restl        = $product_page->handle_upload_file_default( $image_data );

			if ( empty( $restl['error'] ) && ! empty( $restl['file'] ) ) {
				$file_url   = wc_clean( $restl['url'] );
				$upload_dir = wp_upload_dir( null, false );

				$base_url  = $upload_dir['baseurl'] . '/';
				$base_dir  = $upload_dir['basedir'] . '/';
				$image_url = str_replace( $base_url, '', $file_url );
				if ( empty( $restl['tc'] ) ) {
					$product_id = sanitize_text_field( wp_unslash( isset( $_POST['product_id'] ) ? $_POST['product_id'] : '' ) );

					$image_dir     = $base_dir . $image_url;
					$insert_img_id = wp_insert_attachment(
						array(
							'guid'           => $file_url,
							'post_mime_type' => $image_data['type'],
							'post_title'     => preg_replace( '/\.[^.]+$/', '', $image_data['name'] ),
							'post_content'   => '',
							'post_status'    => 'inherit',
						),
						$image_dir
					);

					// wp_generate_attachment_metadata() won't work if you do not include this file.
					require_once ABSPATH . 'wp-admin/includes/image.php';
					// Generate and save the attachment metas into the database.
					wp_update_attachment_metadata( $insert_img_id, wp_generate_attachment_metadata( $insert_img_id, $image_dir ) );
					update_post_meta( $insert_img_id, '_wp_attached_file', $image_url );

					// Update thumbnail for current product.
					update_post_meta( intval( $product_id ), '_thumbnail_id', $insert_img_id );

					wp_send_json_success( array( 'msg' => esc_html__( 'Upload Image successful', 'yayextra' ) ), 200 );
				}
			} else {
				wp_send_json_error( array( 'msg' => $restl['error'] ) );
			}
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax for upload image.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function handle_image_swatches_upload() {
		try {
			Utils::check_nonce();
			$opt_set_id = ! empty( $_REQUEST['optSetId'] ) ? sanitize_text_field( $_REQUEST['optSetId'] ) : null;
			$opt_id     = ! empty( $_REQUEST['optId'] ) ? sanitize_text_field( $_REQUEST['optId'] ) : null;
			$opt_val    = ! empty( $_REQUEST['optVal'] ) ? sanitize_text_field( wp_unslash($_REQUEST['optVal']) ) : null;

			if ( $opt_set_id && $opt_id && $opt_val ) {
				$FILES      = $_FILES;
				$image_data = Utils::sanitize_array( $FILES['file'] );

				$product_page = ProductPage::get_instance();
				$restl        = $product_page->handle_upload_file_default( $image_data );

				if ( empty( $restl['error'] ) && ! empty( $restl['file'] ) ) {
					$file_url = wc_clean( $restl['url'] );

					if ( empty( $restl['tc'] ) ) {
						// Handle update swatches image
						$option_metas = get_post_meta( $opt_set_id, '_yaye_options', true );
						if ( ! empty( $option_metas ) ) {
							foreach ( $option_metas as $index => $opt ) {
								if ( $opt_id === $opt['id'] ) {
									if ( ! empty( $opt['optionValues'] ) ) {
										foreach ( $opt['optionValues'] as $inx => $optVal ) {
											if ( trim($opt_val) === trim($optVal['value']) ) {
												$option_metas[ $index ]['optionValues'][ $inx ]['imageUrl'] = $file_url;
											}
										}
									}
								}
							}
						}

						update_post_meta( $opt_set_id, '_yaye_options', $option_metas );

						wp_send_json_success(
							array(
								'msg'     => esc_html__( 'Upload Image successful', 'yayextra' ),
								'img_url' => $file_url,
							),
							200
						);
					}
				} else {
					wp_send_json_error( array( 'msg' => $restl['error'] ) );
				}
			} else {
				wp_send_json_error( array( 'msg' => esc_html__( 'Data is empty', 'yayextra' ) ) );
			}
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax get settings.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function get_settings() {
		try {
			Utils::check_nonce();
			$data = Utils::get_settings();
			wp_send_json_success(
				array(
					'settings' => $data,
				),
				200
			);
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax save settings.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function save_settings() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}

			$settings = ! empty( $_POST['settings'] ) ? sanitize_text_field( wp_unslash( $_POST['settings'] ) ) : "";

			Utils::update_settings( json_decode( $settings, true ) );
			wp_send_json_success( 'success', 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	public function update_option_set_products_one_by_one() {
		try {
			Utils::check_nonce();
			$object_data                     = ! empty( $_REQUEST['objectData'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['objectData'] ) ) : "";
			$decoded_object_data             = json_decode( $object_data, true );
			$yaye_product_post_meta          = get_post_meta( $decoded_object_data['optionSetID'], '_yaye_products' );
			$array_product_filter_one_by_one = $yaye_product_post_meta[0]['product_filter_one_by_one'];

			if ( 'assign' === $decoded_object_data['type'] ) {
				$merged_array = array_merge( $decoded_object_data['productIdCheckedList'], $array_product_filter_one_by_one );
				$yaye_product_post_meta[0]['product_filter_one_by_one'] = $merged_array;
			} else {
				$filtered_array = array_values( array_diff( $array_product_filter_one_by_one, $decoded_object_data['productIdCheckedList'] ) );
				$yaye_product_post_meta[0]['product_filter_one_by_one'] = $filtered_array;
			}

			update_post_meta( $decoded_object_data['optionSetID'], '_yaye_products', $yaye_product_post_meta[0] );
			wp_send_json_success( 'success', 200 );

		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	public function update_reviewed_flag() {
		try {
			Utils::check_nonce();
			update_option( 'yaye_reviewed_flag', true );
			wp_send_json_success( 'success', 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	public function sync_assigned_products() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}

			$option_set_id = isset( $_POST['option_set_id'] ) ? intval( $_POST['option_set_id'] ) : 0;
			if ( ! $option_set_id ) {
				throw new \Exception( __( 'Invalid option set ID', 'yayextra' ) );
			}

			$yaye_products = get_post_meta( $option_set_id, '_yaye_products', true );
			$original      = ! empty( $yaye_products['product_filter_one_by_one'] ) ? $yaye_products['product_filter_one_by_one'] : array();

			$filtered = array();
			foreach ( $original as $prod_id ) {
				$product = wc_get_product( $prod_id );
				if ( $product ) {
					if ( 'publish' === $product->get_status() ) {
						$filtered[] = $prod_id;
					}
				}
			}
			$removed = count( $original ) - count( $filtered );

			if ( $removed > 0 ) {
				$yaye_products['product_filter_one_by_one'] = $filtered;
				update_post_meta( $option_set_id, '_yaye_products', $yaye_products );
			}

			wp_send_json_success( array( 'removed' => $removed, 'product_filter_one_by_one' => $filtered ), 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}
}
