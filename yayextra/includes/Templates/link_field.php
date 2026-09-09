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

$link_label  = ! empty( $data['linkLabel'] ) ? $data['linkLabel'] : __( 'Learn more', 'yayextra' );
$link_url    = ! empty( $data['linkUrl'] ) ? $data['linkUrl'] : '#';
$link_target = ( ! empty( $data['linkTarget'] ) && '_self' === $data['linkTarget'] ) ? '_self' : '_blank';
$link_style  = ( ! empty( $data['linkStyle'] ) && 'button' === $data['linkStyle'] ) ? 'button' : 'text';
$rel_attr    = '_blank' === $link_target ? 'noopener noreferrer' : '';
$link_class  = 'yayextra-link__trigger yayextra-link__trigger--' . $link_style;
if ( 'button' === $link_style ) {
	// Inherit theme / WooCommerce button colors (same pattern as steps nav).
	$link_class .= ' button alt wp-element-button';
}

echo '<div class="yayextra-option-field-wrap' . ( $class_names ? ' ' . esc_attr( $class_names ) : '' ) . '" data-option-field-id="' . esc_attr( $data['id'] ) . '" data-option-field-type="link">';
echo '<div class="yayextra-link">';
echo '<a class="' . esc_attr( $link_class ) . '" href="' . esc_url( $link_url ) . '" target="' . esc_attr( $link_target ) . '" rel="' . esc_attr( $rel_attr ) . '">' . esc_html( $link_label ) . '</a>';
echo '</div>';
if ( ! empty( $data['description'] ) ) {
	echo '<p class="yayextra-description">' . wp_kses_post( nl2br( $data['description'] ) ) . '</p>';
}
echo '</div>';
