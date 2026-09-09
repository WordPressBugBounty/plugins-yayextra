<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items                     = $params['items'];
$container_id              = $params['container_id'];
$exclusive                 = ! empty( $params['exclusive'] );
$icon                      = $params['icon'];
$border_color              = $params['border_color'];
$background                = $params['background'];
$text_color                = $params['text_color'];
$render_accordion_children = $params['render_accordion_children'];
$visible_count          = count( $items );
$item_index             = 0;
?>
<div
	class="yayextra-accordion"
	data-accordions-id="<?php echo esc_attr( $container_id ); ?>"
	data-exclusive="<?php echo $exclusive ? '1' : '0'; ?>"
	data-icon="<?php echo esc_attr( $icon ); ?>"
	style="<?php echo esc_attr( '--yaye-accordion-border:' . $border_color . ';--yaye-accordion-bg:' . $background . ';--yaye-accordion-text:' . $text_color . ';border-color:' . $border_color . ';background:' . $background . ';color:' . $text_color ); ?>"
>
	<?php foreach ( $items as $index => $item ) : ?>
		<?php
		$item_id   = ! empty( $item['id'] ) ? $item['id'] : (string) $index;
		// translators: %s is the index number of this accordion (starts from 1)
		$item_name = ! empty( $item['name'] ) ? $item['name'] : sprintf( __( 'Accordion %s', 'yayextra' ), (string) ( $index + 1 ) );
		$is_open   = 0 === $item_index;
		++$item_index;
		$is_last = $item_index === $visible_count;
		?>
		<div
			class="yayextra-accordion__item<?php echo $is_open ? ' is-open' : ''; ?>"
			data-accordion-id="<?php echo esc_attr( $item_id ); ?>"
			style="<?php echo $is_last ? '' : esc_attr( 'border-bottom-color:' . $border_color ); ?>"
		>
			<button
				type="button"
				class="yayextra-accordion__trigger"
				aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
				aria-controls="yayextra-accordion-panel-<?php echo esc_attr( $item_id ); ?>"
			>
				<span class="yayextra-accordion__title"><?php echo esc_html( $item_name ); ?></span>
				<span class="yayextra-accordion__icon" aria-hidden="true">
					<?php if ( 'plus_minus' === $icon ) : ?>
						<span class="yayextra-accordion__icon-plus">+</span>
						<span class="yayextra-accordion__icon-minus">&minus;</span>
					<?php else : ?>
						<span class="yayextra-accordion__icon-chevron"></span>
					<?php endif; ?>
				</span>
			</button>
			<div
				id="yayextra-accordion-panel-<?php echo esc_attr( $item_id ); ?>"
				class="yayextra-accordion__content<?php echo $is_open ? ' is-open' : ''; ?>"
				role="region"
				aria-hidden="<?php echo $is_open ? 'false' : 'true'; ?>"
			>
				<div class="yayextra-accordion__content-inner">
					<div class="yayextra-accordion__content-body">
						<?php
						$children = isset( $item['children'] ) && is_array( $item['children'] ) ? $item['children'] : array();
						call_user_func( $render_accordion_children, $children );
						?>
					</div>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</div>
