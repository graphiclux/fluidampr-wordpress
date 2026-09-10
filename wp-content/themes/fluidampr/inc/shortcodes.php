<?php
/**
 * Child-theme shortcodes used when Enfold cannot match Figma cleanly.
 *
 * These stay presentational. Product/SEMA data continues to live in the
 * Fluidampr SEMA plugin and is only consumed through its public shortcode
 * and REST API.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Fluidampr shortcodes.
 *
 * @return void
 */
function fluidampr_register_shortcodes() {
	add_shortcode( 'fluid_finder_panel', 'fluidampr_shortcode_finder_panel' );
	add_shortcode( 'fluid_instagram_feed', 'fluidampr_shortcode_instagram_feed' );
	add_shortcode( 'fluid_newsletter_form', 'fluidampr_shortcode_newsletter_form' );
}
add_action( 'init', 'fluidampr_register_shortcodes' );

/**
 * Compact/full Find Your Damper panel.
 *
 * @param array<string, string> $atts Shortcode attributes.
 * @return string
 */
function fluidampr_shortcode_finder_panel( $atts ) {
	$atts = shortcode_atts(
		array(
			'mode'         => 'compact',
			'browse_url'   => '',
			'browse_label' => __( 'Browse all dampers', 'fluidampr' ),
		),
		$atts,
		'fluid_finder_panel'
	);

	$browse_url = $atts['browse_url'] ? $atts['browse_url'] : fluidampr_page_url( 'catalog_page' );
	$is_full    = 'full' === $atts['mode'];
	$plugin     = shortcode_exists( 'fluidampr_finder' );

	ob_start();
	?>
	<div class="fluid-finder <?php echo $is_full ? 'fluid-finder--full' : 'fluid-finder--compact'; ?>" data-fluid-finder data-mode="<?php echo esc_attr( $atts['mode'] ); ?>">
		<div class="fluid-finder__bar">
			<h2 class="fluid-finder__title"><?php esc_html_e( 'Find your damper', 'fluidampr' ); ?></h2>
			<p class="fluid-finder__subtitle"><?php esc_html_e( 'Search by vehicle, engine, or part number.', 'fluidampr' ); ?></p>
		</div>

		<div class="fluid-finder__body">
			<div class="fluid-finder__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Finder type', 'fluidampr' ); ?>">
				<button type="button" class="fluid-finder__tab is-active" role="tab" aria-selected="true" data-finder-tab="vehicle"><?php esc_html_e( 'Vehicle', 'fluidampr' ); ?></button>
				<button type="button" class="fluid-finder__tab" role="tab" aria-selected="false" data-finder-tab="engine"><?php esc_html_e( 'Engine', 'fluidampr' ); ?></button>
				<button type="button" class="fluid-finder__tab" role="tab" aria-selected="false" data-finder-tab="part"><?php esc_html_e( 'Part #', 'fluidampr' ); ?></button>
			</div>

			<div class="fluid-finder__panels">
				<div class="fluid-finder__panel is-active" data-finder-panel="vehicle" role="tabpanel">
					<?php if ( $is_full && $plugin ) : ?>
						<?php echo do_shortcode( '[fluidampr_finder]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php else : ?>
						<form class="fluid-finder__form" data-finder-form="vehicle" action="<?php echo esc_url( fluidampr_page_url( 'finder_page' ) ); ?>" method="get">
							<label class="fluid-finder__field">
								<span class="screen-reader-text"><?php esc_html_e( 'Year', 'fluidampr' ); ?></span>
								<select name="fy_year" data-finder-field="year">
									<option value=""><?php esc_html_e( 'Year', 'fluidampr' ); ?></option>
								</select>
							</label>
							<label class="fluid-finder__field">
								<span class="screen-reader-text"><?php esc_html_e( 'Make', 'fluidampr' ); ?></span>
								<select name="fy_make" data-finder-field="make">
									<option value=""><?php esc_html_e( 'Make', 'fluidampr' ); ?></option>
								</select>
							</label>
							<label class="fluid-finder__field">
								<span class="screen-reader-text"><?php esc_html_e( 'Model', 'fluidampr' ); ?></span>
								<select name="fy_model" data-finder-field="model">
									<option value=""><?php esc_html_e( 'Model', 'fluidampr' ); ?></option>
								</select>
							</label>
							<label class="fluid-finder__field">
								<span class="screen-reader-text"><?php esc_html_e( 'Submodel', 'fluidampr' ); ?></span>
								<select name="fy_submodel" data-finder-field="submodel">
									<option value=""><?php esc_html_e( 'Submodel', 'fluidampr' ); ?></option>
								</select>
							</label>
							<div class="fluid-finder__actions">
								<a class="fluid-finder__browse" href="<?php echo esc_url( $browse_url ); ?>"><?php echo esc_html( $atts['browse_label'] ); ?> <span aria-hidden="true">›</span></a>
								<button type="submit" class="fluid-button"><?php esc_html_e( 'Search', 'fluidampr' ); ?></button>
							</div>
						</form>
					<?php endif; ?>
				</div>

				<div class="fluid-finder__panel" data-finder-panel="engine" role="tabpanel" hidden>
					<form class="fluid-finder__form fluid-finder__form--single" action="<?php echo esc_url( fluidampr_page_url( 'finder_page' ) ); ?>" method="get">
						<label class="fluid-finder__field fluid-finder__field--wide">
							<span class="screen-reader-text"><?php esc_html_e( 'Engine', 'fluidampr' ); ?></span>
							<input type="search" name="fy_engine" placeholder="<?php esc_attr_e( 'Engine family, displacement, or code', 'fluidampr' ); ?>">
						</label>
						<div class="fluid-finder__actions">
							<a class="fluid-finder__browse" href="<?php echo esc_url( $browse_url ); ?>"><?php echo esc_html( $atts['browse_label'] ); ?> <span aria-hidden="true">›</span></a>
							<button type="submit" class="fluid-button"><?php esc_html_e( 'Search', 'fluidampr' ); ?></button>
						</div>
					</form>
				</div>

				<div class="fluid-finder__panel" data-finder-panel="part" role="tabpanel" hidden>
					<form class="fluid-finder__form fluid-finder__form--single" action="<?php echo esc_url( fluidampr_page_url( 'finder_page' ) ); ?>" method="get">
						<label class="fluid-finder__field fluid-finder__field--wide">
							<span class="screen-reader-text"><?php esc_html_e( 'Part number', 'fluidampr' ); ?></span>
							<input type="search" name="fy_part" placeholder="<?php esc_attr_e( 'Enter a part number', 'fluidampr' ); ?>">
						</label>
						<div class="fluid-finder__actions">
							<a class="fluid-finder__browse" href="<?php echo esc_url( $browse_url ); ?>"><?php echo esc_html( $atts['browse_label'] ); ?> <span aria-hidden="true">›</span></a>
							<button type="submit" class="fluid-button"><?php esc_html_e( 'Search', 'fluidampr' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Instagram feed used in the "Real builders" homepage region.
 *
 * @param array<string, string> $atts Shortcode attributes.
 * @param string|null           $content Optional nested feed shortcode.
 * @return string
 */
function fluidampr_shortcode_instagram_feed( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'shortcode' => '',
			'count'     => '3',
		),
		$atts,
		'fluid_instagram_feed'
	);

	$feed_shortcode = trim( (string) $atts['shortcode'] );

	if ( $content ) {
		$nested = trim( do_shortcode( $content ) );

		if ( '' !== $nested ) {
			$feed_shortcode = $content;
		}
	}

	ob_start();
	include FLUIDAMPR_THEME_PATH . '/template-parts/instagram-feed.php';
	return (string) ob_get_clean();
}

/**
 * Constant Contact newsletter form used in the Enfold footer page.
 *
 * @return string
 */
function fluidampr_shortcode_newsletter_form() {
	ob_start();
	?>
	<form class="fluid-footer__form" data-fluid-newsletter method="post" action="<?php echo esc_url( rest_url( 'fluidampr/v1/newsletter' ) ); ?>" novalidate>
		<label class="screen-reader-text" for="fluid-newsletter-email"><?php esc_html_e( 'Email address', 'fluidampr' ); ?></label>
		<input class="fluid-hp" type="text" name="company" value="" tabindex="-1" autocomplete="off" aria-hidden="true">
		<input id="fluid-newsletter-email" type="email" name="email" required placeholder="<?php esc_attr_e( 'Email address', 'fluidampr' ); ?>" autocomplete="email">
		<button type="submit" class="fluid-button"><?php esc_html_e( 'Sign Up', 'fluidampr' ); ?></button>
		<fieldset class="fluid-footer__audience">
			<legend><?php esc_html_e( 'I am a', 'fluidampr' ); ?></legend>
			<label>
				<input type="radio" name="audience" value="customer" checked>
				<?php esc_html_e( 'Customer', 'fluidampr' ); ?>
			</label>
			<label>
				<input type="radio" name="audience" value="dealer">
				<?php esc_html_e( 'Dealer', 'fluidampr' ); ?>
			</label>
		</fieldset>
		<p class="fluid-footer__form-status" role="status" hidden></p>
	</form>
	<?php
	return (string) ob_get_clean();
}
