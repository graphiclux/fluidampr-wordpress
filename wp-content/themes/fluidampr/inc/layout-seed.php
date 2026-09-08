<?php
/**
 * Seeds editable marketing pages with Advanced Layout Builder content.
 *
 * Product/SEMA architecture is not created here. Pages only reference the
 * public finder shortcode where a vehicle lookup is required.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add a one-time setup page under Appearance.
 *
 * @return void
 */
function fluidampr_register_setup_page() {
	add_theme_page(
		__( 'Fluidampr setup', 'fluidampr' ),
		__( 'Fluidampr setup', 'fluidampr' ),
		'manage_options',
		'fluidampr-setup',
		'fluidampr_render_setup_page'
	);
}
add_action( 'admin_menu', 'fluidampr_register_setup_page' );

/**
 * Render the setup screen.
 *
 * @return void
 */
function fluidampr_render_setup_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$seeded = false;

	if ( isset( $_POST['fluidampr_seed'] ) && check_admin_referer( 'fluidampr_seed_pages' ) ) {
		fluidampr_seed_site();
		$seeded = true;
	}

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Fluidampr setup', 'fluidampr' ) . '</h1>';

	if ( $seeded ) {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Pages, menus, and the Footer page were installed as Enfold Layout Builder content. Edit any page (including Footer) in the Avia Layout Builder. Enfold → Footer is set to use the Footer page.', 'fluidampr' ) . '</p></div>';
	}

	echo '<p>' . esc_html__( 'Creates the homepage, interior pages, and Footer page using Enfold Layout Builder elements so they can be edited in the builder. Running this again updates seeded pages without duplicating them.', 'fluidampr' ) . '</p>';
	echo '<form method="post">';
	wp_nonce_field( 'fluidampr_seed_pages' );
	submit_button( __( 'Install / refresh site layout', 'fluidampr' ), 'primary', 'fluidampr_seed' );
	echo '</form>';

	if ( function_exists( 'fluidampr_render_constant_contact_settings' ) ) {
		fluidampr_render_constant_contact_settings();
	}

	echo '</div>';
}

/**
 * Seed pages after the child theme is activated.
 *
 * @return void
 */
function fluidampr_after_switch_theme() {
	if ( ! get_option( 'fluidampr_seeded', false ) ) {
		fluidampr_seed_site();
	}
}
add_action( 'after_switch_theme', 'fluidampr_after_switch_theme' );

/**
 * Create or update pages, menus, and reading settings.
 *
 * @return void
 */
function fluidampr_seed_site() {
	$pages = fluidampr_seed_page_definitions();
	$ids   = array();

	foreach ( $pages as $slug => $page ) {
		$ids[ $slug ] = fluidampr_upsert_page( $slug, $page );
	}

	if ( ! empty( $ids['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $ids['home'] );
	}

	if ( ! empty( $ids['news'] ) ) {
		update_option( 'page_for_posts', (int) $ids['news'] );
	}

	fluidampr_seed_menus( $ids );

	if ( ! empty( $ids['site-footer'] ) ) {
		fluidampr_assign_enfold_footer_page( (int) $ids['site-footer'] );
	}

	update_option( 'fluidampr_seeded', 1 );
	set_theme_mod( 'fluidampr_instagram', 'https://www.instagram.com/theoriginalfluidampr/' );
}

/**
 * Insert or update a seeded page and mark it as ALB fullwidth.
 *
 * @param string               $slug Page slug.
 * @param array<string, mixed> $page Page data.
 * @return int
 */
function fluidampr_upsert_page( $slug, $page ) {
	$existing = get_page_by_path( $slug );
	$postarr  = array(
		'post_title'   => $page['title'],
		'post_name'    => $slug,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => $page['content'],
	);

	if ( $existing instanceof WP_Post ) {
		$postarr['ID'] = $existing->ID;
		$post_id       = wp_update_post( $postarr, true );
	} else {
		$post_id = wp_insert_post( $postarr, true );
	}

	if ( is_wp_error( $post_id ) ) {
		return 0;
	}

	update_post_meta( $post_id, '_aviaLayoutBuilder_active', 'active' );
	update_post_meta( $post_id, '_aviaLayoutBuilderCleanData', $page['content'] );
	update_post_meta( $post_id, 'layout', 'fullsize' );
	update_post_meta( $post_id, 'sidebar', 'hidden' );
	update_post_meta( $post_id, 'header_title_bar', 'hidden_title_bar' );

	if ( class_exists( 'ShortcodeHelper' ) && function_exists( 'Avia_Builder' ) ) {
		$previous_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
		$GLOBALS['post'] = get_post( $post_id );

		$tree = ShortcodeHelper::build_shortcode_tree( $page['content'] );
		Avia_Builder()->save_shortcode_tree( $post_id, $tree );
		Avia_Builder()->element_manager()->updated_post_content( $page['content'], $post_id );

		$GLOBALS['post'] = $previous_post;
	} else {
		delete_post_meta( $post_id, '_avia_builder_shortcode_tree' );
	}

	if ( ! empty( $page['is_footer'] ) ) {
		update_post_meta( $post_id, 'footer', 'nofooterarea' );
	} else {
		delete_post_meta( $post_id, 'footer' );
	}

	return (int) $post_id;
}

/**
 * Point Enfold at the seeded Footer page (Theme Options → Footer).
 *
 * @param int $page_id Footer page ID.
 * @return void
 */
function fluidampr_assign_enfold_footer_page( $page_id ) {
	$page_id = (int) $page_id;

	if ( $page_id < 1 ) {
		return;
	}

	update_option( 'fluidampr_footer_page_id', $page_id );

	if ( ! function_exists( 'avia_update_option' ) ) {
		return;
	}

	avia_update_option( 'display_widgets_socket', 'page_in_footer' );
	avia_update_option( 'footer_page', (string) $page_id );
	avia_update_option( array( 'footer', 'display_widgets_socket' ), 'page_in_footer' );
	avia_update_option( array( 'footer', 'footer_page' ), (string) $page_id );

	// So editors can add any Layout Builder element without a usage rescan.
	avia_update_option( 'disable_alb_elements', 'load_all' );
	avia_update_option( array( 'performance', 'disable_alb_elements' ), 'load_all' );
}

/**
 * Create primary and footer menus from seeded pages.
 *
 * @param array<string, int> $ids Page IDs keyed by slug.
 * @return void
 */
function fluidampr_seed_menus( $ids ) {
	$menus = array(
		'fluidampr_primary'  => array(
			'name'  => 'Fluidampr Primary',
			'items' => array( 'find-your-damper', 'products', 'where-to-buy', 'knowledge-center', 'technology', 'community', 'contact' ),
		),
		'fluidampr_footer_1' => array(
			'name'  => 'Footer Products',
			'items' => array( 'find-your-damper', 'instructions', 'where-to-buy' ),
		),
		'fluidampr_footer_2' => array(
			'name'  => 'Footer Technology',
			'items' => array( 'support', 'news', 'contact' ),
		),
		'fluidampr_legal'    => array(
			'name'  => 'Footer Legal',
			'items' => array( 'privacy-policy', 'terms' ),
		),
	);

	$locations = get_theme_mod( 'nav_menu_locations', array() );

	foreach ( $menus as $location => $config ) {
		$menu = wp_get_nav_menu_object( $config['name'] );

		if ( ! $menu ) {
			$menu_id = wp_create_nav_menu( $config['name'] );
		} else {
			$menu_id = (int) $menu->term_id;
			$existing_items = wp_get_nav_menu_items( $menu_id );

			if ( is_array( $existing_items ) ) {
				foreach ( $existing_items as $item ) {
					wp_delete_post( $item->ID, true );
				}
			}
		}

		if ( is_wp_error( $menu_id ) ) {
			continue;
		}

		foreach ( $config['items'] as $slug ) {
			if ( empty( $ids[ $slug ] ) ) {
				continue;
			}

			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => get_the_title( $ids[ $slug ] ),
					'menu-item-object-id' => $ids[ $slug ],
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}

		$locations[ $location ] = (int) $menu_id;
	}

	set_theme_mod( 'nav_menu_locations', $locations );
}
