<?php
/**
 * Enfold cleanup that stays update-safe.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Disable Enfold's Portfolio CPT in admin and on the public site.
 *
 * @return void
 */
function fluidampr_disable_portfolio() {
	remove_action( 'init', 'portfolio_register' );
}
add_action( 'after_setup_theme', 'fluidampr_disable_portfolio', 20 );

/**
 * If Enfold still registers Portfolio, hide it from the admin UI.
 *
 * @param array<string, mixed> $args Post type args.
 * @return array<string, mixed>
 */
function fluidampr_hide_portfolio_cpt( $args ) {
	$args['public']              = false;
	$args['show_ui']             = false;
	$args['show_in_menu']        = false;
	$args['show_in_nav_menus']   = false;
	$args['show_in_admin_bar']   = false;
	$args['show_in_rest']        = false;
	$args['exclude_from_search'] = true;
	$args['has_archive']         = false;

	return $args;
}
add_filter( 'avf_portfolio_cpt_args', 'fluidampr_hide_portfolio_cpt' );

/**
 * Align Enfold header settings with the Figma header.
 *
 * @param array<string, mixed> $header Header settings.
 * @param string               $context Filter context.
 * @return array<string, mixed>
 */
function fluidampr_header_settings( $header, $context = '' ) {
	unset( $context );

	$header['header_layout']           = 'logo_left menu_right';
	$header['header_size']             = 'custom';
	$header['header_custom_size']      = '88';
	$header['header_sticky']           = 'disabled';
	$header['header_shrinking']        = 'disabled';
	$header['header_social']           = '';
	$header['header_secondary_menu']   = '';
	$header['header_phone_active']     = '';
	$header['header_searchicon']       = false;
	$header['header_title_bar']        = 'hidden_title_bar';
	$header['header_stretch']          = 'header_stretch';
	$header['header_menu_border']      = '';
	$header['menu_display']            = '';

	return $header;
}
add_filter( 'avf_header_setting_filter', 'fluidampr_header_settings', 20, 2 );

/**
 * Force a full-width layout without sidebars for marketing pages.
 *
 * @param array<string, mixed> $layout Layout config.
 * @param int                  $post_id Current post ID.
 * @return array<string, mixed>
 */
function fluidampr_fullwidth_layout( $layout, $post_id ) {
	unset( $post_id );

	if ( isset( $layout['fullsize'] ) ) {
		$layout['current']         = $layout['fullsize'];
		$layout['current']['main'] = 'fullsize';
	}

	return $layout;
}
add_filter( 'avia_layout_filter', 'fluidampr_fullwidth_layout', 20, 2 );

/**
 * Mark the Enfold page-as-footer wrapper so child CSS can restyle it.
 *
 * @param string $classes Extra classes.
 * @return string
 */
function fluidampr_footer_page_classes( $classes ) {
	return trim( $classes . ' fluid-footer-page' );
}
add_filter( 'avf_page_as_footer_extra_classes', 'fluidampr_footer_page_classes' );

/**
 * Keep the Footer page out of search results.
 *
 * @param WP_Query $query Query.
 * @return void
 */
function fluidampr_exclude_footer_page_from_search( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}

	$footer_id = (int) get_option( 'fluidampr_footer_page_id', 0 );

	if ( $footer_id ) {
		$not_in = $query->get( 'post__not_in' );
		$not_in = is_array( $not_in ) ? $not_in : array();
		$not_in[] = $footer_id;
		$query->set( 'post__not_in', $not_in );
	}
}
add_action( 'pre_get_posts', 'fluidampr_exclude_footer_page_from_search' );

/**
 * Noindex the Footer page if someone opens it directly.
 *
 * @param array<string, bool|string> $robots Robots directives.
 * @return array<string, bool|string>
 */
function fluidampr_footer_page_robots( $robots ) {
	$footer_id = (int) get_option( 'fluidampr_footer_page_id', 0 );

	if ( $footer_id && is_page( $footer_id ) ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}

	return $robots;
}
add_filter( 'wp_robots', 'fluidampr_footer_page_robots' );
