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
 * Replace Enfold footer widgets/socket with the child-theme footer.
 *
 * @return void
 */
function fluidampr_use_custom_footer() {
	global $avia_config;

	if ( isset( $avia_config ) && is_array( $avia_config ) ) {
		$avia_config['footer_option']   = 'nofooterarea';
		$avia_config['footer_behavior'] = '';
	}
}
add_action( 'wp', 'fluidampr_use_custom_footer', 20 );

/**
 * Print the Fluidampr footer before Enfold closes the document.
 *
 * @return void
 */
function fluidampr_render_footer() {
	get_template_part( 'template-parts/footer' );
}
add_action( 'ava_before_footer', 'fluidampr_render_footer' );
