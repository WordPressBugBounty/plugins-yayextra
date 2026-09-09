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

$height = isset( $data['spacingHeight'] ) && is_numeric( $data['spacingHeight'] ) ? $data['spacingHeight'] : '100';
if ( (float) $height < 0 ) {
	$height = '100';
}

$spacing_style = 'width: 100%; height: ' . $height . 'px;';

echo '<div class="yayextra-option-field-wrap' . ( $class_names ? ' ' . esc_attr( $class_names ) : '' ) . '" data-option-field-id="' . esc_attr( $data['id'] ) . '" data-option-field-type="spacing">';
echo '<div class="yayextra-spacing"><div class="yayextra-spacing__block" style="' . esc_attr( $spacing_style ) . '"></div></div>';
if ( ! empty( $data['description'] ) ) {
	echo '<p class="yayextra-description">' . wp_kses_post( nl2br( $data['description'] ) ) . '</p>';
}
echo '</div>';
