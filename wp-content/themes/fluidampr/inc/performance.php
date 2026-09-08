<?php
/**
 * Frontend performance hardening.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove emoji, embed, and other default WP front-end noise.
 *
 * @return void
 */
function fluidampr_disable_wp_noise() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	add_filter( 'emoji_svg_url', '__return_false' );
	add_filter( 'wp_lazy_loading_enabled', '__return_true' );
}
add_action( 'init', 'fluidampr_disable_wp_noise', 20 );

/**
 * Dequeue unused global assets on the public site.
 *
 * @return void
 */
function fluidampr_dequeue_unused_assets() {
	if ( is_admin() ) {
		return;
	}

	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'global-styles' );

	if ( ! is_singular( 'post' ) && ! is_home() && ! is_archive() ) {
		wp_dequeue_style( 'wp-block-library' );
	}

	wp_deregister_script( 'wp-embed' );

	if ( ! is_user_logged_in() ) {
		wp_dequeue_style( 'dashicons' );
	}
}
add_action( 'wp_enqueue_scripts', 'fluidampr_dequeue_unused_assets', 100 );

/**
 * Do not load Enfold Google Fonts; the child theme self-hosts Plus Jakarta Sans.
 *
 * @param bool $enabled Whether Enfold should print Google Fonts.
 * @return bool
 */
function fluidampr_disable_enfold_google_fonts( $enabled ) {
	return false;
}
add_filter( 'avf_output_google_webfonts_script', 'fluidampr_disable_enfold_google_fonts' );

/**
 * Skip Enfold Google Maps API unless a map element is actually present.
 *
 * @param bool $load Whether to load the Maps API.
 * @return bool
 */
function fluidampr_disable_maps_by_default( $load ) {
	return false;
}
add_filter( 'avf_load_google_map_api', 'fluidampr_disable_maps_by_default' );

/**
 * Trim jQuery migrate on the public site.
 *
 * @return void
 */
function fluidampr_remove_jquery_migrate() {
	if ( is_admin() ) {
		return;
	}

	global $wp_scripts;

	if ( isset( $wp_scripts->registered['jquery'] ) ) {
		$wp_scripts->registered['jquery']->deps = array_diff(
			$wp_scripts->registered['jquery']->deps,
			array( 'jquery-migrate' )
		);
	}
}
add_action( 'wp_default_scripts', 'fluidampr_remove_jquery_migrate' );
