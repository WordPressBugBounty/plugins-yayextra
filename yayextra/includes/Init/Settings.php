<?php

namespace YayExtra\Init;

use YayExtra\Init\Ajax;
use YayExtra\Init\CustomPostType;
use YayExtra\Classes\ProductPage;
use YayExtra\Helper\Utils;
use YayExtra\Utils\SingletonTrait;
use YayExtra\Register\ScriptName;
defined( 'ABSPATH' ) || exit;
/**
 * Init some settings when plugin is loaded
 *
 * @class Settings
 */
class Settings {

	use SingletonTrait;

	private function __construct() {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		#support HPOS
		add_action(
			'before_woocommerce_init',
			function () {
				if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', YAYE_PLUGIN_FILE, true );
				}
			}
		);
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_scripts' ) );

		$this->init_option_settings();

		CustomPostType::init();
		new Ajax();
		ProductPage::get_instance();

	}

	public function admin_body_class( $classes ) {
		if ( strpos( $classes, 'yay-ui' ) === false ) {
			$classes .= ' yay-ui';
		}
		return $classes;
	}

	/**
	 * Enqueue scripts
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {

		$current_screen = get_current_screen();
		if ( 'yaycommerce_page_yayextra' === $current_screen->id ) {
			// Enqueue script for wp.media .
			wp_enqueue_media();

			// Enqueue script for wp color picker.
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );

			// Get all user roles.
			global $wp_roles;
			$user_roles = array();
			foreach ( $wp_roles->roles as $key => $role ) {
				$user_roles[] = array(
					'value' => $key,
					'label' => $role['name'],
				);
			}

			$option_set_id_list = $this->get_option_set_id_list();

			wp_enqueue_script( ScriptName::PAGE_SETTINGS );
			wp_enqueue_style( ScriptName::STYLE_SETTINGS );
			wp_enqueue_style( 'yayextra-css', YAYE_URL . 'assets/css/yayextra.css', array(), YAYE_VERSION );

			// Localize script for react.
			wp_localize_script(
				ScriptName::PAGE_SETTINGS, //YAYE_PREFIX,
				'yaye_data',
				array(
					'OPTION_SET_LIST'   => CustomPostType::get_option_set_array( $option_set_id_list ),
					'I18N'              => \YayExtra\I18n::getTranslation(),
					'ajax_url'          => admin_url( 'admin-ajax.php' ),
					'nonce'             => wp_create_nonce( 'yaye_nonce' ),
					'plugin_url'        => YAYE_URL,
					'site_url'          => YAYE_SITE_URL,
					'default_image_url' => \wc_placeholder_img_src(),
					'date_format'       => get_option( 'date_format' ),
					'time_format'       => get_option( 'time_format' ),
					'user_roles'        => $user_roles,
					'mine_types'        => Utils::get_mime_types(),
					'size_allow'        => size_format( wp_max_upload_size() ),
					'rest_url'          => esc_url_raw( rest_url() ),
					'rest_nonce'        => wp_create_nonce( 'wp_rest' ),
					'rest_base'         => 'yayextra/v1',
					'settings'          => get_option( 'yaye_settings' ),
					'reviewed'          => get_option( 'yaye_reviewed_flag' ),
					'currency'  		=> html_entity_decode( get_woocommerce_currency_symbol() ),
				)
			);

		}
	}

	/**
	 * Enqueue scripts
	 *
	 * @return void
	 */
	public function frontend_enqueue_scripts() {

		// Style for front end.
		wp_enqueue_style( 'yayextra-css', YAYE_URL . 'assets/css/yayextra.css', array(), YAYE_VERSION );

		if ( is_product() ) {

			// Enqueue script for date-time picker.
			wp_enqueue_script( 'yayextra-jquery-datetime-picker', YAYE_URL . 'assets/js/jquery.datetimepicker.min.js', array( 'jquery' ), YAYE_VERSION, true );

			// Enqueue script for yayextra process in front end.
			wp_enqueue_script( YAYE_PREFIX, YAYE_URL . 'assets/js/yayextra.js', array( 'jquery', 'jquery-ui-datepicker', 'yayextra-jquery-datetime-picker' ), YAYE_VERSION, true );
			wp_enqueue_script( 'yayextra-steps', YAYE_URL . 'assets/js/yayextra-steps.js', array( 'jquery', YAYE_PREFIX ), YAYE_VERSION, true );
			wp_enqueue_script( 'yayextra-accordions', YAYE_URL . 'assets/js/yayextra-accordions.js', array( 'jquery', YAYE_PREFIX ), YAYE_VERSION, true );

			wp_enqueue_media();

			$option_set_id_list = $this->get_option_set_id_list();
			$product            = wc_get_product();
			$price_html         = $product->get_price_html();

			// Localize script use in front end.
			wp_localize_script(
				YAYE_PREFIX,
				'YAYE_CLIENT_DATA',
				array(
					'OPTION_SET_LIST'  => \YayExtra\Helper\OptionTree::flatten_sets_for_script( CustomPostType::get_option_set_array( $option_set_id_list ) ),
					'price_html'       => $price_html,
					'date_format'      => get_option( 'date_format' ),
					'time_format'      => get_option( 'time_format' ),
					'ajax_url'         => admin_url( 'admin-ajax.php' ),
					'nonce'            => wp_create_nonce( 'yaye_nonce' ),
					'mime_image_types' => esc_attr( implode( ',', Utils::get_mime_image_types() ) ),
					'wc_currency'      => array(
						'decimal_separator'  => wc_get_price_decimal_separator(),
						'thousand_separator' => wc_get_price_thousand_separator(),
						'decimals'           => wc_get_price_decimals(),
					),
					'settings'         => get_option( 'yaye_settings' ),
					'hooks'                                 => array(
						'include_fee_discount_in_total_price' => apply_filters( 'yaye_include_fee_discount_in_total_price', false ),
					),
				)
			);
		}

		if ( is_cart() ) {
			wp_enqueue_script( 'yayextra-other', YAYE_URL . 'assets/js/yayextra_other.js', array( 'jquery' ), YAYE_VERSION, true );
		}
	}

	/**
	 * Generate plugin action link in plugin page.
	 *
	 * @return array
	 */
	public function get_option_set_id_list() {
		$result   = array();
		$opt_sets = CustomPostType::get_list_option_set( array(), true );
		if ( ! empty( $opt_sets ) ) {
			foreach ( $opt_sets as $opt_set ) {
				$opt_set_id = (int) $opt_set->ID;
				array_push( $result, $opt_set_id );
			}
		}

		return $result;
	}

	/**
	 * Generate initial option in database.
	 *
	 * @return void
	 */
	public function init_option_settings() {
		$settings = get_option( 'yaye_settings' );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$init_data = array(
			'general'     => array(
				'show_for_roles'        => array(),
				'hide_for_roles'        => array(),
				'show_additional_price' => true,
				'show_extra_subtotal'   => true,
				'show_total_price'      => true,
				'show_value_mini_cart'  => true,
				'update_product_price'  => false, // For pro version
				'applied_option_sets'   => array(
					'label' => 'All applicable option sets',
					'value' => 'all',
				),
			),
			'globalStyle' => array(
				'general'  => array(
					'label_font_size'         => '16',
					'label_font_weight'       => array(
						'label' => '400',
						'value' => 400,
					),
					// 'label_color'             => '#6d6d6d',
					'total_price_font_size'   => '16',
					'total_price_font_weight' => array(
						'label' => '400',
						'value' => 400,
					),
					'subtotal_price_font_size'   => '16',
					'subtotal_price_font_weight' => array(
						'label' => '400',
						'value' => 400,
					),
					// 'total_price_color'       => '#6d6d6d',
				),
				// 'text'     => array(
				// 'use_theme_default'  => true,
				// 'border_width'       => '1',
				// 'border_radius'      => '5',
				// 'border_style'       => array(
				// 'label' => 'Solid',
				// 'value' => 'solid',
				// ),
				// 'border_color'       => '#43454b',
				// 'padding'            => '10',
				// 'focus_styling'      => false,
				// 'focus_border_width' => '0',
				// 'focus_border_style' => array(
				// 'label' => 'Solid',
				// 'value' => 'solid',
				// ),
				// 'focus_border_color' => '#43454b',
				// ),
				// 'number'   => array(
				// 'use_theme_default'  => true,
				// 'border_width'       => '1',
				// 'border_radius'      => '5',
				// 'border_style'       => array(
				// 'label' => 'Solid',
				// 'value' => 'solid',
				// ),
				// 'border_color'       => '#43454b',
				// 'padding'            => '10',
				// 'focus_styling'      => false,
				// 'focus_border_width' => '0',
				// 'focus_border_style' => array(
				// 'label' => 'Solid',
				// 'value' => 'solid',
				// ),
				// 'focus_border_color' => '#43454b',
				// ),
				'checkbox' => array(
					// 'use_theme_default' => true,
					'height' => '',
					'width'  => '',
				),
				'radio'    => array(
					// 'use_theme_default' => true,
					'height' => '',
					'width'  => '',
				),
				// 'dropdown' => array(
				// 'use_theme_default' => true,
				// 'border_width'      => '1',
				// 'border_radius'     => '0',
				// 'border_style'      => array(
				// 'label' => 'Solid',
				// 'value' => 'solid',
				// ),
				// 'border_color'      => '#43454b',
				// 'padding'           => '2',
				// ),
				'swatches' => array(
					'width'                    => '38',
					'height'                   => '38',
					'border_width'             => '2',
					'border_color'             => '#f5f5f5',
					'border_style'             => array(
						'label' => 'Solid',
						'value' => 'solid',
					),
					'selected_border_width'    => '2',
					'selected_border_color'    => '#333333',
					'selected_border_style'    => array(
						'label' => 'Solid',
						'value' => 'solid',
					),
					'tooltip_position'         => array(
						'label' => 'Bottom',
						'value' => 'bottom',
					),
					'tooltip_background_color' => '#333333',
					'tooltip_text_color'       => '#ffffff',
					'corner_radius'            => '3',
				),
				'button'   => array(
					'border_width'              => '1',
					'border_radius'             => '5',
					'border_style'              => array(
						'label' => 'Solid',
						'value' => 'solid',
					),
					'border_color'              => '#e4e4e7',
					'background_color'          => '#ffffff',
					'text_color'                => '#333333',
					// 'hover_styling'             => false,
					'hover_border_color'        => '#333333',
					'hover_background_color'    => '#ffffff',
					'hover_text_color'          => '#333333',
					'selected_border_color'     => '#333333',
					'selected_background_color' => '#333333',
					'selected_text_color'       => '#ffffff',
					'tooltip_position'          => array(
						'label' => 'Bottom',
						'value' => 'bottom',
					),
					'tooltip_background_color'  => '#333333',
					'tooltip_text_color'        => '#ffffff',
				),
				'custom'   => array(
					'custom_css' => '',
				),
			),
			'actions'     => array(),
		);

		// Init if props does not exist.
		if ( empty( $settings['general'] ) ) {
			$settings['general'] = $init_data['general'];
		}
		if ( empty( $settings['globalStyle'] ) ) {
			$settings['globalStyle'] = $init_data['globalStyle'];
		}
		if ( empty( $settings['actions'] ) ) {
			$settings['actions'] = $init_data['actions'];
		}

		// Update new props.
		$settings['general'] = wp_parse_args( $settings['general'], $init_data['general'] );
		foreach ( $init_data['globalStyle'] as $key => $value ) {
			$settings['globalStyle'][ $key ] = wp_parse_args( $settings['globalStyle'][ $key ], $init_data['globalStyle'][ $key ] );
		}
		$settings['actions'] = wp_parse_args( $settings['actions'], $init_data['actions'] );

		update_option( 'yaye_settings', $settings );

	}
}
