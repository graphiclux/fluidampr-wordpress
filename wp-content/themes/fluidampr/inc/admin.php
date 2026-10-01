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

/**
 * Leaflet Map: move the plugin's top-level admin menu under Settings.
 *
 * The plugin (class.admin.php) registers a top-level "Leaflet Map" menu with two
 * pages (Settings: manage_options, slug "leaflet-map"; Shortcode helper:
 * edit_posts, slug "leaflet-shortcode-helper"). It has no post types or
 * taxonomies. We re-register the same pages, with the same slugs, capabilities
 * and the plugin's own callbacks, under Settings → options-general.php and drop
 * the top-level item. Plugin files are not modified.
 *
 * New URLs: options-general.php?page=leaflet-map and
 *           options-general.php?page=leaflet-shortcode-helper
 * Old admin.php?page=… URLs are redirected (see fluidampr_redirect_old_leaflet_urls()).
 *
 * @return void
 */
function fluidampr_move_leaflet_menu_under_settings() {
	global $submenu;

	if ( ! class_exists( 'Leaflet_Map_Admin' ) || empty( $submenu['leaflet-map'] ) ) {
		return;
	}

	$callbacks = array(
		'leaflet-map'              => 'settings_page',
		'leaflet-shortcode-helper' => 'shortcode_page',
	);
	$instance  = Leaflet_Map_Admin::init();
	$items     = $submenu['leaflet-map'];

	// Remove the top-level entry (either slug, depending on the user's role) and its submenu.
	remove_menu_page( 'leaflet-map' );
	remove_menu_page( 'leaflet-shortcode-helper' );
	unset( $submenu['leaflet-map'] );

	foreach ( $items as $item ) {
		$slug = (string) ( $item[2] ?? '' );

		if ( ! isset( $callbacks[ $slug ] ) ) {
			continue;
		}

		add_submenu_page(
			'options-general.php',
			(string) ( $item[3] ?? $item[0] ),
			(string) $item[0],
			(string) $item[1],
			$slug,
			array( $instance, $callbacks[ $slug ] )
		);
	}
}
add_action( 'admin_menu', 'fluidampr_move_leaflet_menu_under_settings', 9999 );

/**
 * Send the plugin's old admin.php?page=leaflet-* URLs to the new Settings location.
 *
 * Covers bookmarks and any plugin-generated links or redirects.
 *
 * @return void
 */
function fluidampr_redirect_old_leaflet_urls() {
	global $pagenow;

	if ( 'admin.php' !== $pagenow || empty( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$page = sanitize_key( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ! in_array( $page, array( 'leaflet-map', 'leaflet-shortcode-helper' ), true ) ) {
		return;
	}

	$args = map_deep( wp_unslash( $_GET ), 'sanitize_text_field' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	wp_safe_redirect( add_query_arg( $args, admin_url( 'options-general.php' ) ) );
	exit;
}
add_action( 'admin_init', 'fluidampr_redirect_old_leaflet_urls', 1 );

/**
 * Point the plugin row "Settings" link at the new location.
 *
 * @param array<int|string, string> $links Action links.
 * @return array<int|string, string>
 */
function fluidampr_leaflet_plugin_settings_link( $links ) {
	foreach ( $links as $key => $link ) {
		if ( false !== strpos( $link, 'admin.php?page=leaflet-map' ) ) {
			$links[ $key ] = str_replace( 'admin.php?page=leaflet-map', 'options-general.php?page=leaflet-map', $link );
		}
	}

	return $links;
}
add_filter( 'plugin_action_links_leaflet-map/leaflet-map.php', 'fluidampr_leaflet_plugin_settings_link', 20 );
