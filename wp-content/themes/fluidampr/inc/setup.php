<?php
/**
 * Theme supports, menus, and image sizes.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register child-theme supports and menus.
 *
 * @return void
 */
function fluidampr_setup() {
	load_child_theme_textdomain( 'fluidampr', FLUIDAMPR_THEME_PATH . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array(
		'height'      => 80,
		'width'       => 280,
		'flex-height' => true,
		'flex-width'  => true,
	) );

	add_image_size( 'fluidampr-card', 640, 800, true );
	add_image_size( 'fluidampr-hero', 960, 960, false );
	add_image_size( 'fluidampr-cta', 960, 640, true );

	register_nav_menus(
		array(
			'fluidampr_primary'   => __( 'Primary overlay menu', 'fluidampr' ),
			'fluidampr_footer_1'  => __( 'Footer — Products', 'fluidampr' ),
			'fluidampr_footer_2'  => __( 'Footer — Technology', 'fluidampr' ),
			'fluidampr_legal'     => __( 'Footer — Legal', 'fluidampr' ),
		)
	);
}
add_action( 'after_setup_theme', 'fluidampr_setup', 20 );

/**
 * Default Fluidampr contact and brand values.
 *
 * @return array<string, string>
 */
function fluidampr_default_options() {
	return array(
		'phone'            => '(716) 592-1000',
		'phone_href'       => 'tel:+17165921000',
		'email'            => 'info@fluidampr.com',
		'address'          => "180 Zoar Valley Road\nSpringville, NY 14141",
		'facebook'         => 'https://www.facebook.com/Fluidampr',
		'instagram'        => 'https://www.instagram.com/theoriginalfluidampr/',
		'youtube'          => 'https://www.youtube.com/user/Fluidampr',
		'linkedin'         => '',
		'finder_page'      => '/find-your-damper/',
		'buy_page'         => '/where-to-buy/',
		'catalog_page'     => '/products/',
		'newsletter_note'           => __( 'Get the latest updates on new equipment arrivals and market insights delivered to you.', 'fluidampr' ),
		'instagram_access_token'    => '',
		'instagram_feed_shortcode'  => '',
	);
}

/**
 * Get a theme option with a sanitized default.
 *
 * @param string $key Option key.
 * @return string
 */
function fluidampr_get_option( $key ) {
	$defaults = fluidampr_default_options();
	$saved    = get_theme_mod( 'fluidampr_' . $key, isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' );

	return is_string( $saved ) ? $saved : '';
}

/**
 * Logo URL from Enfold Theme Options (Enfold → Theme Options → Logo).
 *
 * @return string
 */
function fluidampr_logo_url() {
	if ( function_exists( 'avia_get_option' ) ) {
		$enfold_logo = avia_get_option( 'logo' );

		if ( is_numeric( $enfold_logo ) ) {
			$url = wp_get_attachment_image_url( (int) $enfold_logo, 'full' );

			if ( $url ) {
				return $url;
			}
		} elseif ( is_string( $enfold_logo ) && '' !== $enfold_logo ) {
			return $enfold_logo;
		}
	}

	$custom = get_theme_mod( 'custom_logo' );

	if ( $custom ) {
		$url = wp_get_attachment_image_url( (int) $custom, 'full' );

		if ( $url ) {
			return $url;
		}
	}

	return FLUIDAMPR_THEME_URI . '/assets/images/logo.png';
}

/**
 * Enfold Theme Options logo markup (same output Enfold uses in the header).
 *
 * @param string $tag Heading/wrapper tag Enfold wraps the logo in.
 * @return string
 */
function fluidampr_enfold_logo_html( $tag = 'span' ) {
	if ( ! function_exists( 'avia_logo' ) ) {
		$url = fluidampr_logo_url();

		return '<a class="fluid-header__logo" href="' . esc_url( home_url( '/' ) ) . '"><img src="' . esc_url( $url ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" decoding="async"></a>';
	}

	$fallback = defined( 'AVIA_BASE_URL' ) ? AVIA_BASE_URL . 'images/layout/logo.png' : '';

	return (string) avia_logo( $fallback, '', $tag, true );
}
