<?php

use YayExtra\Helper\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data        = $params['data'];
$class_names = '';
if ( ! empty( $data['classNames'] ) ) {
	$class_names = Utils::convert_string( $data['classNames'], ',', ' ' );
}

$width_value = '100';
$width_unit  = '%';
if ( isset( $data['dividerWidth'] ) && is_array( $data['dividerWidth'] ) ) {
	if ( isset( $data['dividerWidth']['value'] ) && is_numeric( $data['dividerWidth']['value'] ) ) {
		$width_value = $data['dividerWidth']['value'];
	}
	if ( isset( $data['dividerWidth']['unit'] ) && in_array( $data['dividerWidth']['unit'], array( 'px', '%' ), true ) ) {
		$width_unit = $data['dividerWidth']['unit'];
	}
}
if ( '%' === $width_unit && (float) $width_value > 100 ) {
	$width_value = '100';
}

$height = isset( $data['dividerHeight'] ) && is_numeric( $data['dividerHeight'] ) ? $data['dividerHeight'] : '1';
if ( (float) $height < 0 ) {
	$height = '1';
}

$allowed_styles = array( 'solid', 'dashed', 'dotted', 'double', 'groove' );
$divider_style  = isset( $data['dividerStyle'] ) && in_array( $data['dividerStyle'], $allowed_styles, true ) ? $data['dividerStyle'] : 'solid';

$divider_color = '#000000';
if ( ! empty( $data['dividerColor'] ) ) {
	$sanitized_color = sanitize_hex_color( $data['dividerColor'] );
	if ( $sanitized_color ) {
		$divider_color = $sanitized_color;
	}
}

$divider_style_attr = 'width: ' . $width_value . $width_unit . '; height: 0; border-top-width: ' . $height . 'px; border-top-style: ' . $divider_style . '; border-top-color: ' . $divider_color . ';';

echo '<div class="yayextra-option-field-wrap' . ( $class_names ? ' ' . esc_attr( $class_names ) : '' ) . '" data-option-field-id="' . esc_attr( $data['id'] ) . '" data-option-field-type="divider">';
echo '<div class="yayextra-divider"><div class="yayextra-divider__line" style="' . esc_attr( $divider_style_attr ) . '"></div></div>';
if ( ! empty( $data['description'] ) ) {
	echo '<p class="yayextra-description">' . wp_kses_post( nl2br( $data['description'] ) ) . '</p>';
}
echo '</div>';
