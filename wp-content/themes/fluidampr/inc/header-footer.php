<?php
/**
 * Header/footer helpers that do not copy Enfold parent templates unless required.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render an inline SVG icon from the child-theme sprite set.
 *
 * @param string $name Icon name.
 * @param string $class Extra class.
 * @return string
 */
function fluidampr_icon( $name, $class = '' ) {
	$icons = array(
		'search'    => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.8"/><path d="M16.2 16.2L21 21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
		'menu'      => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
		'close'     => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
		'instagram' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.8" y="3.8" width="16.4" height="16.4" rx="4.4" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="3.6" stroke="currentColor" stroke-width="1.6"/><circle cx="17.1" cy="6.9" r="1" fill="currentColor"/></svg>',
	);

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	$class = trim( 'fluid-icon fluid-icon--' . sanitize_html_class( $name ) . ' ' . $class );

	return '<span class="' . esc_attr( $class ) . '">' . $icons[ $name ] . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG is hardcoded.
}

/**
 * Echo a escaped internal or external URL for a named site page.
 *
 * @param string $option_key Theme option key.
 * @return string
 */
function fluidampr_page_url( $option_key ) {
	$path = fluidampr_get_option( $option_key );

	if ( '' === $path ) {
		return home_url( '/' );
	}

	if ( 0 === strpos( $path, 'http' ) ) {
		return $path;
	}

	$slug = trim( (string) $path, '/' );
	$page = $slug ? get_page_by_path( $slug ) : null;

	if ( $page instanceof WP_Post ) {
		$permalink = get_permalink( $page );

		if ( $permalink ) {
			return $permalink;
		}
	}

	return home_url( $path );
}
