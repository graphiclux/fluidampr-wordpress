<?php
/**
 * WordPress admin cleanup.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hide WooCommerce Payments, Analytics, and Marketing from the admin sidebar.
 *
 * @return void
 */
function fluidampr_hide_unused_woocommerce_menus() {
	global $menu;

	if ( ! is_array( $menu ) ) {
		return;
	}

	$hidden_titles = array( 'payments', 'analytics', 'marketing' );

	foreach ( $menu as $index => $item ) {
		$title = strtolower( trim( wp_strip_all_tags( (string) ( $item[0] ?? '' ) ) ) );
		$slug  = (string) ( $item[2] ?? '' );

		$is_hidden_title = in_array( $title, $hidden_titles, true );
		$is_hidden_slug  = (
			0 === strpos( $slug, 'woocommerce-analytics' )
			|| 0 === strpos( $slug, 'woocommerce-marketing' )
			|| false !== strpos( $slug, 'wc-admin&path=/analytics' )
			|| false !== strpos( $slug, 'wc-admin&path=/marketing' )
			|| false !== strpos( $slug, 'wc-admin&path=/payments' )
			|| false !== strpos( $slug, 'tab=checkout&from=' )
		);

		if ( $is_hidden_title || $is_hidden_slug ) {
			unset( $menu[ $index ] );
		}
	}
}
add_action( 'admin_menu', 'fluidampr_hide_unused_woocommerce_menus', 999 );

/**
 * Turn off WooCommerce Analytics / Marketing admin features.
 *
 * @param array<int, string> $features Feature slugs.
 * @return array<int, string>
 */
function fluidampr_disable_woocommerce_admin_features( $features ) {
	return array_values(
		array_diff(
			(array) $features,
			array( 'analytics', 'marketing' )
		)
	);
}
add_filter( 'woocommerce_admin_features', 'fluidampr_disable_woocommerce_admin_features', 99 );
