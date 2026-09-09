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

$html_content = '';
if ( ! empty( $data['htmlContent'] ) ) {
	$html_content = $data['htmlContent'];
} else {
	$html_content = __( 'This is HTML', 'yayextra' );
}

echo '<div class="yayextra-option-field-wrap' . ( $class_names ? ' ' . esc_attr( $class_names ) : '' ) . '" data-option-field-id="' . esc_attr( $data['id'] ) . '" data-option-field-type="html">';
echo '<div class="yayextra-html"><div class="yayextra-html__content">' . Utils::sanitize_option_html( $html_content ) . '</div></div>';
if ( ! empty( $data['description'] ) ) {
	echo '<p class="yayextra-description">' . wp_kses_post( nl2br( $data['description'] ) ) . '</p>';
}
echo '</div>';
