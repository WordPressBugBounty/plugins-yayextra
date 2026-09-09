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

$layout = ( isset( $data['productListLayout'] ) && 'grid' === $data['productListLayout'] ) ? 'grid' : 'list';
$columns = isset( $data['productListColumns'] ) ? absint( $data['productListColumns'] ) : 3;
if ( $columns < 1 ) {
	$columns = 3;
}
if ( $columns > 6 ) {
	$columns = 6;
}
$gap = isset( $data['productListGap'] ) && is_numeric( $data['productListGap'] ) ? $data['productListGap'] : '12';

$fields = array(
	'name'  => true,
	'image' => true,
	'price' => true,
);
if ( ! empty( $data['productListFields'] ) && is_array( $data['productListFields'] ) ) {
	$fields['name']  = ! empty( $data['productListFields']['name'] );
	$fields['image'] = ! empty( $data['productListFields']['image'] );
	$fields['price'] = ! empty( $data['productListFields']['price'] );
}

$snapshots = ! empty( $data['productListItems'] ) && is_array( $data['productListItems'] ) ? $data['productListItems'] : array();
$ordered_ids = array();
foreach ( $snapshots as $snapshot ) {
	if ( ! empty( $snapshot['productId'] ) ) {
		$ordered_ids[] = $snapshot['productId'];
	}
}
$live_items = Utils::get_product_list_items_by_ids( $ordered_ids );
$live_map   = array();
foreach ( $live_items as $live_item ) {
	$live_map[ $live_item['productId'] ] = $live_item;
}

$items = array();
foreach ( $snapshots as $snapshot ) {
	$product_id = isset( $snapshot['productId'] ) ? (string) $snapshot['productId'] : '';
	if ( '' === $product_id ) {
		continue;
	}
	if ( isset( $live_map[ $product_id ] ) ) {
		$items[] = $live_map[ $product_id ];
	} else {
		$items[] = array(
			'productId'  => $product_id,
			'title'      => isset( $snapshot['title'] ) ? $snapshot['title'] : '',
			'imageUrl'   => isset( $snapshot['imageUrl'] ) ? $snapshot['imageUrl'] : '',
			'imageAlt'   => isset( $snapshot['imageAlt'] ) ? $snapshot['imageAlt'] : '',
			'price'      => isset( $snapshot['price'] ) ? $snapshot['price'] : '',
			'productUrl' => isset( $snapshot['productUrl'] ) ? $snapshot['productUrl'] : '#',
		);
	}
}

$container_style = 'gap: ' . esc_attr( $gap ) . 'px;';
if ( 'grid' === $layout ) {
	$container_style = 'grid-template-columns: repeat(' . $columns . ', 1fr); gap: ' . esc_attr( $gap ) . 'px;';
}

echo '<div class="yayextra-option-field-wrap' . ( $class_names ? ' ' . esc_attr( $class_names ) : '' ) . '" data-option-field-id="' . esc_attr( $data['id'] ) . '" data-option-field-type="product_list">';
echo '<div class="yayextra-product-list">';

if ( empty( $items ) ) {
	echo '<p class="yayextra-product-list__empty">' . esc_html__( 'No products selected.', 'yayextra' ) . '</p>';
} else {
	echo '<div class="yayextra-product-list-container layout-' . esc_attr( $layout ) . '" style="' . esc_attr( $container_style ) . '">';
	foreach ( $items as $item ) {
		$show_info =
			( $fields['name'] && ! empty( $item['title'] ) ) ||
			( $fields['price'] && ! empty( $item['price'] ) );
		$is_image_only = $fields['image'] && ! empty( $item['imageUrl'] ) && ! $show_info;
		$item_class    = 'yayextra-product-list__item';
		if ( $is_image_only ) {
			$item_class .= ' yayextra-product-list__item--image-only';
		}
		$url = ! empty( $item['productUrl'] ) ? $item['productUrl'] : '#';

		echo '<a class="' . esc_attr( $item_class ) . '" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">';
		if ( $fields['image'] && ! empty( $item['imageUrl'] ) ) {
			$alt = ! empty( $item['imageAlt'] ) ? $item['imageAlt'] : ( ! empty( $item['title'] ) ? $item['title'] : '' );
			echo '<img class="yayextra-product-list__image" src="' . esc_url( $item['imageUrl'] ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" />';
		}
		if ( $show_info ) {
			echo '<div class="yayextra-product-list__info">';
			if ( $fields['name'] && ! empty( $item['title'] ) ) {
				echo '<span class="yayextra-product-list__name">' . esc_html( $item['title'] ) . '</span>';
			}
			if ( $fields['price'] && ! empty( $item['price'] ) ) {
				echo '<span class="yayextra-product-list__price">' . esc_html( $item['price'] ) . '</span>';
			}
			echo '</div>';
		}
		echo '</a>';
	}
	echo '</div>';
}

echo '</div>';
if ( ! empty( $data['description'] ) ) {
	echo '<p class="yayextra-description">' . wp_kses_post( nl2br( $data['description'] ) ) . '</p>';
}
echo '</div>';
