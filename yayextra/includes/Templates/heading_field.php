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

$heading_tag = isset( $data['headingTag'] ) ? $data['headingTag'] : 'h3';
if ( ! in_array( $heading_tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ) {
	$heading_tag = 'h3';
}

$heading_content = '';
if ( ! empty( $data['headingContent'] ) ) {
	$heading_content = $data['headingContent'];
} elseif ( ! empty( $data['name'] ) ) {
	$heading_content = $data['name'];
}

$heading_styles = array();
$font_size_mode = isset( $data['headingFontSize'] ) ? $data['headingFontSize'] : 'default';
$font_size_px   = '';
if ( 'custom' === $font_size_mode && isset( $data['headingFontSizeCustom'] ) && is_numeric( $data['headingFontSizeCustom'] ) ) {
	$font_size_px = $data['headingFontSizeCustom'];
} elseif ( 'default' !== $font_size_mode && is_numeric( $font_size_mode ) ) {
	$font_size_px = $font_size_mode;
}
$font_weight = isset( $data['headingFontWeight'] ) ? $data['headingFontWeight'] : 'default';
if ( '' !== $font_size_px && (int) $font_size_px > 0 ) {
	$heading_styles[] = 'font-size: ' . absint( $font_size_px ) . 'px';
}
if ( 'default' !== $font_weight && is_numeric( $font_weight ) ) {
	$heading_styles[] = 'font-weight: ' . absint( $font_weight );
}
$heading_style_attr = $heading_styles ? esc_attr( implode( '; ', $heading_styles ) ) : '';

echo '<div class="yayextra-option-field-wrap' . ( $class_names ? ' ' . esc_attr( $class_names ) : '' ) . '" data-option-field-id="' . esc_attr( $data['id'] ) . '" data-option-field-type="heading">';
echo '<' . esc_attr( $heading_tag ) . ' class="yayextra-heading yayextra-heading--' . esc_attr( $heading_tag ) . '" style="' . esc_attr( $heading_style_attr ) . '">' . wp_kses_post( $heading_content ) . '</' . esc_attr( $heading_tag ) . '>';
if ( ! empty( $data['description'] ) ) {
	echo '<p class="yayextra-description">' . wp_kses_post( nl2br( $data['description'] ) ) . '</p>';
}
echo '</div>';
