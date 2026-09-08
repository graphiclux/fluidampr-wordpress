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
	add_shortcode( 'fluid_feature_card', 'fluidampr_shortcode_feature_card' );
	add_shortcode( 'fluid_community_card', 'fluidampr_shortcode_community_card' );
	add_shortcode( 'fluid_cta_banner', 'fluidampr_shortcode_cta_banner' );
	add_shortcode( 'fluid_hero_heading', 'fluidampr_shortcode_hero_heading' );
	add_shortcode( 'fluid_home_hero', 'fluidampr_shortcode_home_hero' );
	add_shortcode( 'fluid_card_grid', 'fluidampr_shortcode_card_grid' );
	add_shortcode( 'fluid_community_grid', 'fluidampr_shortcode_community_grid' );
	add_shortcode( 'fluid_instagram_feed', 'fluidampr_shortcode_instagram_feed' );
	add_shortcode( 'fluid_site_footer', 'fluidampr_shortcode_site_footer' );
	add_shortcode( 'fluid_heading_group', 'fluidampr_shortcode_heading_group' );
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
								<select name="year" data-finder-field="year">
									<option value=""><?php esc_html_e( 'Year', 'fluidampr' ); ?></option>
								</select>
							</label>
							<label class="fluid-finder__field">
								<span class="screen-reader-text"><?php esc_html_e( 'Make', 'fluidampr' ); ?></span>
								<select name="make" data-finder-field="make" disabled>
									<option value=""><?php esc_html_e( 'Make', 'fluidampr' ); ?></option>
								</select>
							</label>
							<label class="fluid-finder__field">
								<span class="screen-reader-text"><?php esc_html_e( 'Model', 'fluidampr' ); ?></span>
								<select name="model" data-finder-field="model" disabled>
									<option value=""><?php esc_html_e( 'Model', 'fluidampr' ); ?></option>
								</select>
							</label>
							<label class="fluid-finder__field">
								<span class="screen-reader-text"><?php esc_html_e( 'Submodel', 'fluidampr' ); ?></span>
								<select name="submodel" data-finder-field="submodel" disabled>
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
							<input type="search" name="engine" placeholder="<?php esc_attr_e( 'Engine family, displacement, or code', 'fluidampr' ); ?>">
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
							<input type="search" name="part" placeholder="<?php esc_attr_e( 'Enter a part number', 'fluidampr' ); ?>">
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
 * Feature card used in the homepage grid.
 *
 * @param array<string, string> $atts Shortcode attributes.
 * @param string|null           $content Inner content.
 * @return string
 */
function fluidampr_shortcode_feature_card( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'icon'  => 'pin',
			'title' => '',
			'link'  => '',
			'label' => '',
		),
		$atts,
		'fluid_feature_card'
	);

	ob_start();
	?>
	<article class="fluid-card">
		<?php echo fluidampr_icon( $atts['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<h3 class="fluid-card__title"><?php echo esc_html( $atts['title'] ); ?></h3>
		<div class="fluid-card__text"><?php echo wp_kses_post( wpautop( $content ) ); ?></div>
		<?php if ( $atts['link'] && $atts['label'] ) : ?>
			<a class="fluid-card__link" href="<?php echo esc_url( $atts['link'] ); ?>"><?php echo esc_html( $atts['label'] ); ?> <span aria-hidden="true">›</span></a>
		<?php endif; ?>
	</article>
	<?php
	return (string) ob_get_clean();
}

/**
 * Community / builder photo card.
 *
 * @param array<string, string> $atts Shortcode attributes.
 * @return string
 */
function fluidampr_shortcode_community_card( $atts ) {
	$atts = shortcode_atts(
		array(
			'image'    => '',
			'title'    => '',
			'handle'   => '',
			'alt'      => '',
		),
		$atts,
		'fluid_community_card'
	);

	ob_start();
	?>
	<article class="fluid-community-card">
		<?php if ( $atts['image'] ) : ?>
			<img src="<?php echo esc_url( $atts['image'] ); ?>" alt="<?php echo esc_attr( $atts['alt'] ? $atts['alt'] : $atts['title'] ); ?>" loading="lazy" decoding="async" width="640" height="800">
		<?php else : ?>
			<div class="fluid-community-card__fallback" aria-hidden="true"></div>
		<?php endif; ?>
		<div class="fluid-community-card__overlay">
			<p class="fluid-community-card__title"><?php echo esc_html( $atts['title'] ); ?></p>
			<?php if ( $atts['handle'] ) : ?>
				<p class="fluid-community-card__handle"><?php echo esc_html( $atts['handle'] ); ?></p>
			<?php endif; ?>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}

/**
 * Dark CTA banner.
 *
 * @param array<string, string> $atts Shortcode attributes.
 * @param string|null           $content Inner content.
 * @return string
 */
function fluidampr_shortcode_cta_banner( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'title'      => '',
			'button'     => '',
			'url'        => '',
			'image'      => '',
			'image_alt'  => '',
		),
		$atts,
		'fluid_cta_banner'
	);

	ob_start();
	?>
	<section class="fluid-cta">
		<div class="fluid-cta__copy">
			<h2 class="fluid-cta__title"><?php echo esc_html( $atts['title'] ); ?></h2>
			<div class="fluid-cta__text"><?php echo wp_kses_post( wpautop( $content ) ); ?></div>
			<?php if ( $atts['url'] && $atts['button'] ) : ?>
				<a class="fluid-button" href="<?php echo esc_url( $atts['url'] ); ?>"><?php echo esc_html( $atts['button'] ); ?></a>
			<?php endif; ?>
		</div>
		<?php if ( $atts['image'] ) : ?>
			<div class="fluid-cta__media">
				<img src="<?php echo esc_url( $atts['image'] ); ?>" alt="<?php echo esc_attr( $atts['image_alt'] ); ?>" loading="lazy" decoding="async" width="720" height="480">
			</div>
		<?php endif; ?>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Hero headline with accented last line.
 *
 * @param array<string, string> $atts Shortcode attributes.
 * @return string
 */
function fluidampr_shortcode_hero_heading( $atts ) {
	$atts = shortcode_atts(
		array(
			'line_1' => __( 'Find the right damper.', 'fluidampr' ),
			'line_2' => __( 'Build it right.', 'fluidampr' ),
			'accent' => __( 'With confidence.', 'fluidampr' ),
		),
		$atts,
		'fluid_hero_heading'
	);

	ob_start();
	?>
	<h1 class="fluid-hero__heading">
		<span><?php echo esc_html( $atts['line_1'] ); ?></span>
		<span><?php echo esc_html( $atts['line_2'] ); ?></span>
		<span class="fluid-hero__accent"><?php echo esc_html( $atts['accent'] ); ?></span>
	</h1>
	<?php
	return (string) ob_get_clean();
}

/**
 * Homepage hero: heading, finder, and product visual.
 *
 * @param array<string, string> $atts Shortcode attributes.
 * @return string
 */
function fluidampr_shortcode_home_hero( $atts ) {
	$atts = shortcode_atts(
		array(
			'image' => FLUIDAMPR_THEME_URI . '/assets/images/hero-damper.svg',
			'alt'   => __( 'Fluidampr performance damper', 'fluidampr' ),
		),
		$atts,
		'fluid_home_hero'
	);

	ob_start();
	?>
	<section class="fluid-hero">
		<?php echo fluidampr_shortcode_hero_heading( array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="fluid-hero__stage">
			<?php echo fluidampr_shortcode_finder_panel( array( 'mode' => 'compact' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="fluid-hero__media">
				<img src="<?php echo esc_url( $atts['image'] ); ?>" alt="<?php echo esc_attr( $atts['alt'] ); ?>" width="640" height="640" decoding="async">
			</div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Feature card grid wrapper.
 *
 * @param array<string, string> $atts Shortcode attributes.
 * @param string|null           $content Inner shortcodes.
 * @return string
 */
function fluidampr_shortcode_card_grid( $atts, $content = null ) {
	unset( $atts );
	return '<div class="fluid-card-grid">' . do_shortcode( $content ) . '</div>';
}

/**
 * Community card grid wrapper.
 *
 * @param array<string, string> $atts Shortcode attributes.
 * @param string|null           $content Inner shortcodes.
 * @return string
 */
function fluidampr_shortcode_community_grid( $atts, $content = null ) {
	unset( $atts );
	return '<div class="fluid-community-grid">' . do_shortcode( $content ) . '</div>';
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
 * Full site footer for the Enfold footer page.
 *
 * @return string
 */
function fluidampr_shortcode_site_footer() {
	ob_start();
	get_template_part( 'template-parts/footer' );
	return (string) ob_get_clean();
}

/**
 * Eyebrow + heading + optional intro.
 *
 * @param array<string, string> $atts Shortcode attributes.
 * @param string|null           $content Intro text.
 * @return string
 */
function fluidampr_shortcode_heading_group( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'eyebrow' => '',
			'title'   => '',
			'align'   => 'left',
		),
		$atts,
		'fluid_heading_group'
	);

	ob_start();
	?>
	<div class="fluid-heading-group fluid-heading-group--<?php echo esc_attr( $atts['align'] ); ?>">
		<?php if ( $atts['eyebrow'] ) : ?>
			<p class="fluid-heading-group__eyebrow"><?php echo esc_html( $atts['eyebrow'] ); ?></p>
		<?php endif; ?>
		<?php if ( $atts['title'] ) : ?>
			<h2 class="fluid-heading-group__title"><?php echo esc_html( $atts['title'] ); ?></h2>
		<?php endif; ?>
		<?php if ( $content ) : ?>
			<div class="fluid-heading-group__intro"><?php echo wp_kses_post( wpautop( $content ) ); ?></div>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}
