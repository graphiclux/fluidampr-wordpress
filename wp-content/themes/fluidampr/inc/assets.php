<?php
/**
 * Asset loading with cache-busting and conditional enqueueing.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filemtime helper for cache busting.
 *
 * @param string $relative Path relative to the child theme root.
 * @return string
 */
function fluidampr_asset_version( $relative ) {
	$path = FLUIDAMPR_THEME_PATH . '/' . ltrim( $relative, '/' );

	if ( file_exists( $path ) ) {
		return (string) filemtime( $path );
	}

	return FLUIDAMPR_THEME_VERSION;
}

/**
 * Enqueue child-theme CSS/JS after Enfold.
 *
 * @return void
 */
function fluidampr_enqueue_assets() {
	$css_rel = 'assets/css/site.css';
	$js_rel  = 'assets/js/site.js';
	$deps    = array( 'fluidampr-style' );

	if ( is_singular() ) {
		$post_css = 'avia-single-post-' . get_queried_object_id();

		if ( wp_style_is( $post_css, 'registered' ) || wp_style_is( $post_css, 'enqueued' ) ) {
			$deps[] = $post_css;
		}
	}

	wp_enqueue_style(
		'fluidampr-style',
		get_stylesheet_uri(),
		array(),
		FLUIDAMPR_THEME_VERSION
	);

	wp_enqueue_style(
		'fluidampr-site',
		FLUIDAMPR_THEME_URI . '/' . $css_rel,
		$deps,
		fluidampr_asset_version( $css_rel )
	);

	fluidampr_ensure_sema_finder_assets();

	$js_deps = array();

	if ( wp_script_is( 'fluidampr-finder', 'enqueued' ) ) {
		$js_deps[] = 'fluidampr-finder';
	}

	wp_enqueue_script(
		'fluidampr-site',
		FLUIDAMPR_THEME_URI . '/' . $js_rel,
		$js_deps,
		fluidampr_asset_version( $js_rel ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	$finder_rest = '';

	if ( class_exists( '\Fluidampr\SemaIntegration\Finder\FinderRestController' ) ) {
		$finder_rest = esc_url_raw( rest_url( \Fluidampr\SemaIntegration\Finder\FinderRestController::ROUTE_NAMESPACE . '/' ) );
	}

	wp_localize_script(
		'fluidampr-site',
		'fluidamprTheme',
		array(
			'finderRest'     => $finder_rest,
			'finderPage'     => home_url( fluidampr_get_option( 'finder_page' ) ),
			'catalogPage'    => home_url( fluidampr_get_option( 'catalog_page' ) ),
			'newsletterRest' => esc_url_raw( rest_url( 'fluidampr/v1/newsletter' ) ),
			'restNonce'      => wp_create_nonce( 'wp_rest' ),
			'i18n'           => array(
				'searchLabel'      => __( 'Search the site', 'fluidampr' ),
				'menuLabel'        => __( 'Open menu', 'fluidampr' ),
				'closeLabel'       => __( 'Close', 'fluidampr' ),
				'selectYear'       => __( 'Year', 'fluidampr' ),
				'selectMake'       => __( 'Make', 'fluidampr' ),
				'selectModel'      => __( 'Model', 'fluidampr' ),
				'selectSub'        => __( 'Submodel', 'fluidampr' ),
				'loading'          => __( 'Loading…', 'fluidampr' ),
				'newsletterSending' => __( 'Signing up…', 'fluidampr' ),
				'newsletterError'   => __( 'Could not complete signup. Try again.', 'fluidampr' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'fluidampr_enqueue_assets', 1000002 );

/**
 * Load the SEMA finder script when the theme nests [fluidampr_finder]
 * inside [fluid_finder_panel]. The plugin only auto-enqueues when that
 * tag is in post_content, which Enfold ALB pages do not store.
 *
 * @return void
 */
function fluidampr_ensure_sema_finder_assets() {
	if ( ! defined( 'FLUIDAMPR_SEMA_INTEGRATION_URL' ) || ! is_singular() ) {
		return;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$blob = (string) $post->post_content . (string) get_post_meta( $post->ID, '_aviaLayoutBuilderCleanData', true );

	if ( false === strpos( $blob, 'fluid_finder_panel' ) && false === strpos( $blob, 'fluidampr_finder' ) ) {
		return;
	}

	$handle = 'fluidampr-finder';
	$ver    = defined( 'FLUIDAMPR_SEMA_INTEGRATION_VERSION' ) ? FLUIDAMPR_SEMA_INTEGRATION_VERSION : FLUIDAMPR_THEME_VERSION;

	wp_enqueue_style(
		$handle,
		FLUIDAMPR_SEMA_INTEGRATION_URL . 'assets/finder.css',
		array(),
		$ver
	);

	wp_enqueue_script(
		$handle,
		FLUIDAMPR_SEMA_INTEGRATION_URL . 'assets/finder.js',
		array(),
		$ver,
		true
	);

	if ( ! wp_script_is( $handle, 'enqueued' ) ) {
		return;
	}

	$localized = wp_scripts()->get_data( $handle, 'data' );

	if ( $localized ) {
		return;
	}

	$hide_prices = class_exists( '\Fluidampr\SemaIntegration\Catalog\CatalogModeSettings' )
		&& \Fluidampr\SemaIntegration\Catalog\CatalogModeSettings::hide_prices();

	wp_localize_script(
		$handle,
		'fluidamprFinderSettings',
		array(
			'restUrl'    => esc_url_raw( rest_url( 'fluidampr-sema/v1/' ) ),
			'hidePrices' => $hide_prices ? '1' : '0',
			'i18n'       => array(),
		)
	);
}

/**
 * Preload the latin heading/body font and logo for a faster first paint.
 *
 * @return void
 */
function fluidampr_resource_hints() {
	$font = FLUIDAMPR_THEME_URI . '/assets/fonts/titillium-web-700italic-latin.woff2';

	printf(
		"<link rel='preload' href='%s' as='font' type='font/woff2' crossorigin>\n",
		esc_url( $font )
	);
}
add_action( 'wp_head', 'fluidampr_resource_hints', 1 );

/**
 * Add a small extra class on the frontend body for child-theme scoping.
 *
 * @param array<int, string> $classes Body classes.
 * @return array<int, string>
 */
function fluidampr_body_class( $classes ) {
	$classes[] = 'fluidampr-theme';

	if ( is_front_page() ) {
		$classes[] = 'fluidampr-home';
	}

	return $classes;
}
add_filter( 'body_class', 'fluidampr_body_class' );
