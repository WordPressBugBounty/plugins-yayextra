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

$paragraph_content = '';
if ( ! empty( $data['paragraphContent'] ) ) {
	$paragraph_content = $data['paragraphContent'];
} elseif ( ! empty( $data['name'] ) ) {
	$paragraph_content = $data['name'];
} else {
	$paragraph_content = __( 'This is paragraph', 'yayextra' );
}

$paragraph_styles = array();
$font_size_mode   = isset( $data['paragraphFontSize'] ) ? $data['paragraphFontSize'] : 'default';
$font_size_px     = '';
if ( 'custom' === $font_size_mode && isset( $data['paragraphFontSizeCustom'] ) && is_numeric( $data['paragraphFontSizeCustom'] ) ) {
	$font_size_px = $data['paragraphFontSizeCustom'];
} elseif ( 'default' !== $font_size_mode && is_numeric( $font_size_mode ) ) {
	$font_size_px = $font_size_mode;
}
$font_weight = isset( $data['paragraphFontWeight'] ) ? $data['paragraphFontWeight'] : 'default';
if ( '' !== $font_size_px && (int) $font_size_px > 0 ) {
	$paragraph_styles[] = 'font-size: ' . absint( $font_size_px ) . 'px';
}
if ( 'default' !== $font_weight && is_numeric( $font_weight ) ) {
	$paragraph_styles[] = 'font-weight: ' . absint( $font_weight );
}
$paragraph_style_attr = $paragraph_styles ? esc_attr( implode( '; ', $paragraph_styles ) ) : '';

echo '<div class="yayextra-option-field-wrap' . ( $class_names ? ' ' . esc_attr( $class_names ) : '' ) . '" data-option-field-id="' . esc_attr( $data['id'] ) . '" data-option-field-type="paragraph">';
echo '<p class="yayextra-paragraph" style="' . esc_attr( $paragraph_style_attr ) . '">' . wp_kses_post( $paragraph_content ) . '</p>';
if ( ! empty( $data['description'] ) ) {
	echo '<p class="yayextra-description">' . wp_kses_post( nl2br( $data['description'] ) ) . '</p>';
}
echo '</div>';
