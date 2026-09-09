<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$display_mode          = $params['display_mode'];
$sidebar_side          = $params['sidebar_side'];
$button_text           = $params['button_text'];
$back_text             = $params['back_text'];
$next_text             = $params['next_text'];
$done_text             = $params['done_text'];
$steps                 = $params['steps'];
$container_id          = $params['container_id'];
$title                 = $params['title'];
$step_total            = $params['step_total'];
$is_overlay            = $params['is_overlay'];
$render_step_children  = $params['render_step_children'];
$first_name            = ! empty( $steps[0]['name'] ) ? $steps[0]['name'] : '';
// translators: %1$s is the current step number, %2$s is the total number of steps
$status_text           = sprintf( __( 'Step %1$s of %2$s', 'yayextra' ), '1', (string) $step_total );
if ( '' !== $first_name ) {
	$status_text .= ' · ' . $first_name;
}
?>
<div
	class="yayextra-steps-container"
	data-display-mode="<?php echo esc_attr( $display_mode ); ?>"
	data-sidebar-side="<?php echo esc_attr( $sidebar_side ); ?>"
	data-steps-id="<?php echo esc_attr( $container_id ); ?>"
	data-furthest-complete="-1"
	data-step-count-tpl="<?php echo esc_attr( __( 'Step %1$s of %2$s', 'yayextra' ) ); ?>"
	data-review-label="<?php echo esc_attr__( 'Review', 'yayextra' ); ?>"
	data-review-empty="<?php echo esc_attr__( 'No selections yet', 'yayextra' ); ?>"
>
	<?php if ( $is_overlay ) : ?>
		<div class="yayextra-steps-trigger-wrap">
			<button type="button" class="button alt wp-element-button yayextra-steps-trigger yayextra-steps-open">
				<?php echo esc_html( $button_text ); ?>
			</button>
			<div class="yayextra-steps-status" aria-live="polite">
				<span class="yayextra-steps-status__dots">
					<?php foreach ( $steps as $index => $step ) : ?>
						<?php
						// translators: %s is the index number of this step (starts from 1)
						$step_name = ! empty( $step['name'] ) ? $step['name'] : sprintf( __( 'Step %s', 'yayextra' ), (string) ( $index + 1 ) );
						$dot_class = 0 === $index ? ' is-current' : ' is-upcoming';
						?>
						<span
							class="yayextra-steps-status__dot<?php echo esc_attr( $dot_class ); ?>"
							data-step-index="<?php echo esc_attr( (string) $index ); ?>"
							data-step-name="<?php echo esc_attr( $step_name ); ?>"
						></span>
					<?php endforeach; ?>
				</span>
				<span class="yayextra-steps-status__text"><?php echo esc_html( $status_text ); ?></span>
			</div>
		</div>
		<div
			class="yayextra-steps-overlay yayextra-steps-overlay--<?php echo esc_attr( $display_mode ); ?><?php echo 'sidebar' === $display_mode ? ' yayextra-steps-overlay--' . esc_attr( $sidebar_side ) : ''; ?>"
			aria-hidden="true"
		>
			<div class="yayextra-steps-overlay__backdrop yayextra-steps-backdrop"></div>
			<div class="yayextra-steps-overlay__panel" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( $title ); ?>">
				<div class="yayextra-steps-overlay__header">
					<span class="yayextra-steps-overlay__title"><?php echo esc_html( $title ); ?></span>
					<button type="button" class="yayextra-steps-overlay__close yayextra-steps-close" aria-label="<?php echo esc_attr__( 'Close', 'yayextra' ); ?>">&times;</button>
				</div>
				<div class="yayextra-steps-overlay__body">
	<?php endif; ?>

					<div class="yayextra-steps">
						<div class="yayextra-steps-progress" role="list">
							<?php foreach ( $steps as $index => $step ) : ?>
								<span role="listitem" class="yayextra-step-dot<?php echo 0 === $index ? ' is-active' : ''; ?>"></span>
							<?php endforeach; ?>
							<span class="yayextra-steps-count"><?php 
								// translators: %1$s is the current step number, %2$s is the total number of steps
								echo esc_html( sprintf( __( 'Step %1$s of %2$s', 'yayextra' ), '1', (string) $step_total ) ); ?>
							</span>
						</div>

						<?php foreach ( $steps as $index => $step ) : ?>
							<div
								class="yayextra-step-panel<?php echo 0 === $index ? ' is-active' : ''; ?>"
								data-step-index="<?php echo esc_attr( (string) $index ); ?>"
								<?php echo 0 === $index ? '' : ' hidden'; ?>
							>
								<?php if ( ! empty( $step['name'] ) ) : ?>
									<div class="yayextra-step-heading"><?php echo esc_html( $step['name'] ); ?></div>
								<?php endif; ?>
								<?php
								$children = isset( $step['children'] ) && is_array( $step['children'] ) ? $step['children'] : array();
								call_user_func( $render_step_children, $children );
								?>
							</div>
						<?php endforeach; ?>

						<div class="yayextra-steps-review" hidden></div>

						<div class="yayextra-steps-nav">
							<button type="button" class="button yayextra-steps-btn yayextra-step-back" disabled><?php echo esc_html( $back_text ); ?></button>
							<button type="button" class="button alt wp-element-button yayextra-steps-btn yayextra-step-next"><?php echo esc_html( $next_text ); ?></button>
							<button type="button" class="button alt wp-element-button yayextra-steps-btn yayextra-step-done" hidden><?php echo esc_html( $done_text ); ?></button>
						</div>
					</div>

	<?php if ( $is_overlay ) : ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
