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
 * pages (slug "leaflet-map", manage_options; slug "leaflet-shortcode-helper",
 * edit_posts). They appear under Settings as "Leaflet Map" and "Leaflet Shortcode Helper". It has no post types or
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
	// Clear names so the entries are easy to find among the core Settings pages.
	$labels    = array(
		'leaflet-map'              => 'Leaflet Map',
		'leaflet-shortcode-helper' => 'Leaflet Shortcode Helper',
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
			$labels[ $slug ],
			$labels[ $slug ],
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

/*
 * ---------------------------------------------------------------------------
 * Comments are switched off site-wide (existing comment data is NOT deleted).
 *
 * PRODUCT REVIEWS ON/OFF: WooCommerce reviews are comments on the "product"
 * post type, so they are switched off with everything else. Two switches
 * control them; each is a one-word edit (change true to false):
 *
 *   1. fluidampr_product_reviews_page_hidden()  -> Products > Reviews admin page
 *      true  = page hidden, direct URL redirects to the dashboard (current)
 *      false = Products > Reviews comes back
 *
 *   2. fluidampr_product_reviews_disabled()     -> reviews on the front end
 *      true  = products have no reviews tab/form, comments_open() is false
 *      false = product comment support and comments_open() are left to
 *              WooCommerce, so the Reviews tab and form work again
 *              (comments stay off for posts, pages and Knowledge Base)
 *
 * To fully bring product reviews back, flip BOTH to false. Either can also be
 * overridden without editing this file via the filters
 * 'fluidampr_hide_product_reviews_page' and 'fluidampr_disable_product_reviews'.
 * ---------------------------------------------------------------------------
 */

/**
 * Switch 1: hide the Products > Reviews admin screen.
 * Set to false to bring back the Products > Reviews screen.
 *
 * @return bool
 */
function fluidampr_product_reviews_page_hidden() {
	return (bool) apply_filters( 'fluidampr_hide_product_reviews_page', true ); // <-- change true to false.
}

/**
 * Switch 2: turn product reviews off on the front end.
 * Set to false to re-enable product comment support and comments_open() for products.
 *
 * @return bool
 */
function fluidampr_product_reviews_disabled() {
	return (bool) apply_filters( 'fluidampr_disable_product_reviews', true ); // <-- change true to false.
}

/**
 * Whether comments are forced off for a given post (switch 2 exempts products).
 *
 * @param int|WP_Post|null $post Post ID or object.
 * @return bool
 */
function fluidampr_comments_off_for( $post = null ) {
	return ! ( 'product' === get_post_type( $post ) && ! fluidampr_product_reviews_disabled() );
}

/**
 * Remove comment and trackback support from every post type.
 *
 * Runs late so custom post types and WooCommerce products are registered.
 *
 * @return void
 */
function fluidampr_remove_comment_support() {
	foreach ( get_post_types() as $post_type ) {
		if ( 'product' === $post_type && ! fluidampr_product_reviews_disabled() ) {
			continue; // Switch 2 is off: leave WooCommerce's product review support alone.
		}

		remove_post_type_support( $post_type, 'comments' );
		remove_post_type_support( $post_type, 'trackbacks' );
	}
}
add_action( 'init', 'fluidampr_remove_comment_support', 100 );

/**
 * Remove the Comments admin menu item.
 *
 * @return void
 */
function fluidampr_remove_comments_menu() {
	remove_menu_page( 'edit-comments.php' );
}
add_action( 'admin_menu', 'fluidampr_remove_comments_menu', 9999 );

/**
 * Remove the comments bubble from the admin bar (front end and admin).
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar.
 * @return void
 */
function fluidampr_remove_comments_admin_bar_node( $wp_admin_bar ) {
	$wp_admin_bar->remove_node( 'comments' );
}
add_action( 'admin_bar_menu', 'fluidampr_remove_comments_admin_bar_node', 999 );

/**
 * Remove the dashboard Recent Comments widget.
 *
 * @return void
 */
function fluidampr_remove_recent_comments_widget() {
	remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'side' );
}
add_action( 'wp_dashboard_setup', 'fluidampr_remove_recent_comments_widget', 999 );

/**
 * Send direct visits to the comment screens to the dashboard.
 *
 * @return void
 */
function fluidampr_redirect_comment_screens() {
	global $pagenow;

	if ( in_array( $pagenow, array( 'edit-comments.php', 'comment.php' ), true ) ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
}
add_action( 'admin_init', 'fluidampr_redirect_comment_screens', 1 );

/**
 * Close comments and pings (front end and new submissions).
 *
 * @param bool       $open    Whether open.
 * @param int|string $post_id Post ID.
 * @return bool
 */
function fluidampr_close_comments( $open, $post_id = 0 ) {
	return fluidampr_comments_off_for( $post_id ) ? false : $open;
}
add_filter( 'comments_open', 'fluidampr_close_comments', 99, 2 );
add_filter( 'pings_open', 'fluidampr_close_comments', 99, 2 );

/**
 * Hide existing comments from front-end templates (data stays in the database).
 *
 * @param array<int, object> $comments Comments.
 * @param int                $post_id  Post ID.
 * @return array<int, object>
 */
function fluidampr_empty_comments_array( $comments, $post_id = 0 ) {
	return ( is_admin() || ! fluidampr_comments_off_for( $post_id ) ) ? $comments : array();
}
add_filter( 'comments_array', 'fluidampr_empty_comments_array', 99, 2 );

/**
 * Report a zero comment count on the front end.
 *
 * @param int|string $count   Count.
 * @param int        $post_id Post ID.
 * @return int|string
 */
function fluidampr_zero_comment_count( $count, $post_id = 0 ) {
	return ( is_admin() || ! fluidampr_comments_off_for( $post_id ) ) ? $count : 0;
}
add_filter( 'get_comments_number', 'fluidampr_zero_comment_count', 99, 2 );

/**
 * Redirect comment feeds to the home page.
 *
 * @return void
 */
function fluidampr_redirect_comment_feeds() {
	if ( is_comment_feed() ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'fluidampr_redirect_comment_feeds', 1 );

/**
 * Hide Products > Reviews (WooCommerce, admin.php?page=product-reviews).
 *
 * Controlled by fluidampr_product_reviews_page_hidden(); see the switch notes
 * at the top of this comments block.
 *
 * @return void
 */
function fluidampr_hide_product_reviews_menu() {
	global $submenu;

	if ( ! fluidampr_product_reviews_page_hidden() ) {
		return;
	}

	$parent = 'edit.php?post_type=product';

	remove_submenu_page( $parent, 'product-reviews' );

	// Avoid leaving an empty Products menu for users who could only moderate reviews.
	if ( empty( $submenu[ $parent ] ) ) {
		remove_menu_page( $parent );
	}
}
add_action( 'admin_menu', 'fluidampr_hide_product_reviews_menu', 9999 );

/**
 * Send direct visits to the Product Reviews screen to the dashboard.
 *
 * Runs at the end of admin_menu (not admin_init): WordPress checks page access
 * right after the menu is built and would show "Sorry, you are not allowed to
 * access this page" for the removed submenu before admin_init is reached.
 *
 * @return void
 */
function fluidampr_redirect_product_reviews_page() {
	global $pagenow;

	if ( ! fluidampr_product_reviews_page_hidden() || ! in_array( $pagenow, array( 'edit.php', 'admin.php' ), true ) ) {
		return;
	}

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( 'product-reviews' === $page ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
}
add_action( 'admin_menu', 'fluidampr_redirect_product_reviews_page', 10000 );
