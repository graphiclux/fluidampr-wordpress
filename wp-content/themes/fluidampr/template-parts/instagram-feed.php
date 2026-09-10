<?php
/**
 * Instagram feed region for "Real builders. Real results."
 *
 * Loads the latest posts from the child-theme Graph API cache when a token
 * is saved in Customizer. Otherwise uses curated fallback images so the
 * homepage still matches the Figma three-up tiles.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$count = isset( $atts['count'] ) ? (int) $atts['count'] : 3;
$count = max( 1, min( 21, $count ) );
$items = function_exists( 'fluidampr_instagram_get_items' )
	? fluidampr_instagram_get_items( $count )
	: array();

if ( ! $items ) {
	$items = function_exists( 'fluidampr_instagram_fallback_items' )
		? fluidampr_instagram_fallback_items()
		: array();
}

$handle = function_exists( 'fluidampr_instagram_handle' )
	? '@' . fluidampr_instagram_handle()
	: '@theoriginalfluidampr';
?>
<div class="fluid-instagram fluid-instagram--count-<?php echo esc_attr( (string) $count ); ?>" aria-label="<?php esc_attr_e( 'Fluidampr on Instagram', 'fluidampr' ); ?>">
	<div class="fluid-instagram__grid">
		<?php foreach ( $items as $index => $item ) : ?>
			<?php
			$permalink = ! empty( $item['permalink'] ) ? $item['permalink'] : fluidampr_get_option( 'instagram' );
			$image     = ! empty( $item['image'] ) ? $item['image'] : '';
			$title     = ! empty( $item['title'] ) ? $item['title'] : __( 'Fluidampr build', 'fluidampr' );
			$item_handle = ! empty( $item['handle'] ) ? $item['handle'] : $handle;
			?>
			<a class="fluid-instagram__item fluid-instagram__item--<?php echo esc_attr( (string) ( $index + 1 ) ); ?>" href="<?php echo esc_url( $permalink ); ?>" rel="noopener noreferrer" target="_blank">
				<?php if ( $image ) : ?>
					<img class="fluid-instagram__media" src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" width="480" height="600" loading="lazy" decoding="async">
				<?php else : ?>
					<span class="fluid-instagram__media" aria-hidden="true"></span>
				<?php endif; ?>
				<span class="fluid-instagram__overlay">
					<?php echo fluidampr_icon( 'instagram' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="fluid-instagram__title"><?php echo esc_html( $title ); ?></span>
					<span class="fluid-instagram__handle"><?php echo esc_html( $item_handle ); ?></span>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
</div>
