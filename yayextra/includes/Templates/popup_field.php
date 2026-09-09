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

$trigger_content = ! empty( $data['triggerContent'] ) ? $data['triggerContent'] : __( 'Click here to view details', 'yayextra' );
$trigger_style   = ( ! empty( $data['triggerStyle'] ) && 'button' === $data['triggerStyle'] ) ? 'button' : 'text';
$popup_title     = ! empty( $data['popupTitle'] ) ? $data['popupTitle'] : __( 'Information', 'yayextra' );
$popup_content   = ! empty( $data['popupContent'] ) ? $data['popupContent'] : '<p>' . esc_html__( 'Popup content goes here', 'yayextra' ) . '</p>';
$popup_id        = 'yayextra-popup-' . $data['id'];

echo '<div class="yayextra-option-field-wrap' . ( $class_names ? ' ' . esc_attr( $class_names ) : '' ) . '" data-option-field-id="' . esc_attr( $data['id'] ) . '" data-option-field-type="popup">';
echo '<div class="yayextra-popup">';

if ( 'button' === $trigger_style ) {
	echo '<button type="button" class="yayextra-popup__trigger yayextra-popup__trigger--button button alt wp-element-button" data-yayextra-popup-open="' . esc_attr( $popup_id ) . '">' . esc_html( $trigger_content ) . '</button>';
} else {
	echo '<button type="button" class="yayextra-popup__trigger yayextra-popup__trigger--text" data-yayextra-popup-open="' . esc_attr( $popup_id ) . '">' . esc_html( $trigger_content ) . '</button>';
}

echo '<div id="' . esc_attr( $popup_id ) . '" class="yayextra-popup__overlay" aria-hidden="true" hidden>';
echo '<div class="yayextra-popup__modal" role="dialog" aria-modal="true" aria-label="' . esc_attr__( 'Popup', 'yayextra' ) . '">';
echo '<div class="yayextra-popup__header">';
echo '<div class="yayextra-popup__title">' . Utils::sanitize_option_html( $popup_title ) . '</div>';
echo '<button type="button" class="yayextra-popup__close" data-yayextra-popup-close aria-label="' . esc_attr__( 'Close popup', 'yayextra' ) . '">';
echo '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M15 5L5 15M5 5L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
echo '</button>';
echo '</div>';
echo '<div class="yayextra-popup__body"><div class="yayextra-popup__content">' . Utils::sanitize_option_html( $popup_content ) . '</div></div>';
echo '</div>';
echo '</div>';

echo '</div>';
if ( ! empty( $data['description'] ) ) {
	echo '<p class="yayextra-description">' . wp_kses_post( nl2br( $data['description'] ) ) . '</p>';
}
echo '</div>';
