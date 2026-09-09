<?php

use YayExtra\Helper\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$template_folder   = YAYE_PATH . 'includes/Templates';
$data              = $params['data'];
$option_value_list = ! empty( $data['optionValues'] ) && is_array( $data['optionValues'] ) ? $data['optionValues'] : array();

$class_names = '';
if ( ! empty( $data['classNames'] ) ) {
	$class_names = Utils::convert_string( $data['classNames'], ',', ' ' );
}

$image_size = ( isset( $data['imageSize'] ) && is_array( $data['imageSize'] ) ) ? $data['imageSize'] : array(
	'width'      => '200',
	'widthUnit'  => 'px',
	'height'     => '200',
	'heightUnit' => 'px',
);

$allowed_units = array( 'px', '%', 'auto' );
$width_unit    = isset( $image_size['widthUnit'] ) && in_array( $image_size['widthUnit'], $allowed_units, true ) ? $image_size['widthUnit'] : 'px';
$height_unit   = isset( $image_size['heightUnit'] ) && in_array( $image_size['heightUnit'], $allowed_units, true ) ? $image_size['heightUnit'] : 'px';
$width_value   = isset( $image_size['width'] ) && is_numeric( $image_size['width'] ) ? $image_size['width'] : '200';
$height_value  = isset( $image_size['height'] ) && is_numeric( $image_size['height'] ) ? $image_size['height'] : '200';
$img_width     = 'auto' === $width_unit ? 'auto' : $width_value . $width_unit;
$img_height    = 'auto' === $height_unit ? 'auto' : $height_value . $height_unit;

$image_layout  = ( isset( $data['imageLayout'] ) && 'grid' === $data['imageLayout'] ) ? 'grid' : 'list';
$image_columns = isset( $data['imageColumns'] ) ? absint( $data['imageColumns'] ) : 3;
if ( $image_columns < 1 ) {
	$image_columns = 3;
}
if ( $image_columns > 6 ) {
	$image_columns = 6;
}
$image_gap = isset( $data['imageGap'] ) && is_numeric( $data['imageGap'] ) ? $data['imageGap'] : '10';

$container_style = '';
if ( 'grid' === $image_layout ) {
	$container_style = 'grid-template-columns: repeat(' . $image_columns . ', 1fr); gap: ' . $image_gap . 'px;';
}

if ( 'grid' === $image_layout ) {
	$img_style = 'width: 100%; max-width: 100%; height: auto; object-fit: contain; object-position: center;';
} else {
	$img_style = 'width: ' . $img_width . '; max-width: 100%; height: ' . $img_height . '; object-fit: contain; object-position: center;';
}

echo '<div class="yayextra-option-field-wrap' . ( $class_names ? ' ' . esc_attr( $class_names ) : '' ) . '" data-option-field-id="' . esc_attr( $data['id'] ) . '" data-option-field-type="images">';
Utils::get_template_part( $template_folder, 'label_field', array( 'data' => $data ) );
echo '<div class="yayextra-images-container layout-' . esc_attr( $image_layout ) . '"' . ( $container_style ? ' style="' . esc_attr( $container_style ) . '"' : '' ) . '>';
foreach ( $option_value_list as $opt ) {
	if ( empty( $opt['imageUrl'] ) ) {
		continue;
	}
	echo '<div class="yayextra-images__item">';
	echo '<img src="' . esc_url( $opt['imageUrl'] ) . '" alt="" class="yayextra-images__image" style="' . esc_attr( $img_style ) . '" />';
	echo '</div>';
}
echo '</div>';
if ( ! empty( $data['description'] ) ) {
	echo '<p class="yayextra-description">' . wp_kses_post( nl2br( $data['description'] ) ) . '</p>';
}
echo '</div>';
