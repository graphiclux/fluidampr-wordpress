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
		'pin'       => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-6.2 7-11.2A7 7 0 1 0 5 9.8C5 14.8 12 21 12 21z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="9.8" r="2.2" stroke="currentColor" stroke-width="1.6"/></svg>',
		'book'      => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H20v16H7.5A2.5 2.5 0 0 0 5 21.5V5.5z" stroke="currentColor" stroke-width="1.6"/><path d="M5 19h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
		'shield'    => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3l8 3.2v6.2c0 4.4-3.1 7.6-8 8.8-4.9-1.2-8-4.4-8-8.8V6.2L12 3z" stroke="currentColor" stroke-width="1.6"/><path d="M8.8 12.1l2.2 2.2 4.4-4.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'car'       => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 14h16l-1.4-5.2A2 2 0 0 0 16.7 7H7.3a2 2 0 0 0-1.9 1.8L4 14z" stroke="currentColor" stroke-width="1.6"/><path d="M6 14v3.5M18 14v3.5M3.5 17.5h17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="7.5" cy="17.6" r="1.2" fill="currentColor"/><circle cx="16.5" cy="17.6" r="1.2" fill="currentColor"/></svg>',
		'phone'     => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7.2 3.8h3.1l1 4.2-2 1.2a12.4 12.4 0 0 0 5.5 5.5l1.2-2 4.2 1v3.1c0 .8-.7 1.5-1.5 1.5C9.8 18.3 5.7 14.2 5.7 5.3c0-.8.7-1.5 1.5-1.5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>',
		'mail'      => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="5.5" width="17" height="13" rx="1.6" stroke="currentColor" stroke-width="1.6"/><path d="M4.2 7.2L12 12.4l7.8-5.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'chevron'   => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'facebook'  => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14.5 8.5V6.8c0-.7.5-1 1.2-1h1.3V3h-2.3C12.3 3 11 4.5 11 6.6v1.9H9v2.8h2V21h3.5v-9.7h2.3l.4-2.8h-2.7z"/></svg>',
		'instagram' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.8" y="3.8" width="16.4" height="16.4" rx="4.4" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="3.6" stroke="currentColor" stroke-width="1.6"/><circle cx="17.1" cy="6.9" r="1" fill="currentColor"/></svg>',
		'youtube'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.6 7.6a3 3 0 0 0-2.1-2.1C17.7 5 12 5 12 5s-5.7 0-7.5.5a3 3 0 0 0-2.1 2.1A31 31 0 0 0 2 12a31 31 0 0 0 .4 4.4 3 3 0 0 0 2.1 2.1C6.3 19 12 19 12 19s5.7 0 7.5-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 22 12a31 31 0 0 0-.4-4.4zM10.2 15.2V8.8L15.6 12l-5.4 3.2z"/></svg>',
		'linkedin'  => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.7 9.3H4V20h2.7V9.3zM5.3 4A1.6 1.6 0 1 0 5.3 7.2 1.6 1.6 0 0 0 5.3 4zM20 20h-2.7v-5.6c0-1.6-.6-2.4-1.8-2.4-1.2 0-1.9.8-1.9 2.4V20H11V9.3h2.6v1.4c.5-.9 1.6-1.7 3.3-1.7 2.4 0 4.1 1.5 4.1 4.8V20z"/></svg>',
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
