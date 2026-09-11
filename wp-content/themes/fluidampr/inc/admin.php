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
 * Hide unused WooCommerce admin menus.
 *
 * Catalog-first launch: keep WooCommerce → Settings and Status, plus the
 * Products menu. Hide store ops (Home, Orders, Coupons, Reports, Extensions)
 * and the Payments / Analytics / Marketing top-level items.
 *
 * @return void
 */
function fluidampr_hide_unused_woocommerce_menus() {
	global $menu, $submenu;

	if ( is_array( $menu ) ) {
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

	if ( empty( $submenu['woocommerce'] ) || ! is_array( $submenu['woocommerce'] ) ) {
		return;
	}

	$hidden_sub_titles = array( 'home', 'orders', 'coupons', 'reports', 'extensions' );
	$hidden_sub_slugs  = array(
		'wc-admin',
		'wc-orders',
		'edit.php?post_type=shop_order',
		'edit.php?post_type=shop_coupon',
		'coupons-moved',
		'wc-reports',
		'wc-addons',
	);

	$kept = array();

	foreach ( $submenu['woocommerce'] as $item ) {
		$title = strtolower( trim( wp_strip_all_tags( (string) ( $item[0] ?? '' ) ) ) );
		$slug  = (string) ( $item[2] ?? '' );

		$hide_slug = in_array( $slug, $hidden_sub_slugs, true )
			|| 0 === strpos( $slug, 'wc-admin' );

		if ( in_array( $title, $hidden_sub_titles, true ) || $hide_slug ) {
			continue;
		}

		$kept[] = $item;
	}

	usort(
		$kept,
		static function ( $a, $b ) {
			$order = array(
				'wc-settings' => 0,
				'wc-status'   => 1,
			);
			$a_slug = (string) ( $a[2] ?? '' );
			$b_slug = (string) ( $b[2] ?? '' );
			$a_pos  = $order[ $a_slug ] ?? 10;
			$b_pos  = $order[ $b_slug ] ?? 10;

			return $a_pos <=> $b_pos;
		}
	);

	$submenu['woocommerce'] = $kept;
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

/**
 * Do not register the WooCommerce Extensions / Marketplace submenu.
 *
 * @return bool
 */
function fluidampr_hide_woocommerce_addons_page() {
	return false;
}
add_filter( 'woocommerce_show_addons_page', 'fluidampr_hide_woocommerce_addons_page' );
