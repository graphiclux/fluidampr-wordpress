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
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Pages, menus, and homepage layout were installed. You can edit each page in the Enfold Advanced Layout Builder.', 'fluidampr' ) . '</p></div>';
	}

	echo '<p>' . esc_html__( 'Creates the Figma homepage and interior pages as Enfold Layout Builder content. Running this again updates seeded pages without duplicating them.', 'fluidampr' ) . '</p>';
	echo '<form method="post">';
	wp_nonce_field( 'fluidampr_seed_pages' );
	submit_button( __( 'Install / refresh site layout', 'fluidampr' ), 'primary', 'fluidampr_seed' );
	echo '</form></div>';
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
	update_option( 'fluidampr_seeded', 1 );
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
	update_post_meta( $post_id, 'header_title_bar', 'hidden_title_bar' );
	update_post_meta( $post_id, 'sidebar', 'hidden' );

	return (int) $post_id;
}

/**
 * Wrap shortcodes in a full-width Enfold color section.
 *
 * @param string $inner Inner shortcodes/HTML.
 * @param string $class Extra section class.
 * @return string
 */
function fluidampr_alb_section( $inner, $class = 'fluid-section' ) {
	return '[av_section padding=\'no-padding\' custom_class=\'' . esc_attr( $class ) . '\' color=\'main_color\' custom_bg=\'#ffffff\'][av_one_full first][av_textblock]' . $inner . '[/av_textblock][/av_one_full][/av_section]';
}

/**
 * Page content map.
 *
 * @return array<string, array<string, string>>
 */
function fluidampr_seed_page_definitions() {
	$home = '[fluid_home_hero]'
		. '[fluid_card_grid]'
		. '[fluid_feature_card icon="pin" title="Where to Buy" link="/where-to-buy/" label="Find a dealer"]Find authorized Fluidampr dealers and trusted retailers near you.[/fluid_feature_card]'
		. '[fluid_feature_card icon="book" title="Knowledge Center" link="/knowledge-center/" label="Get support"]Guides, tech articles, and install resources to build with confidence.[/fluid_feature_card]'
		. '[fluid_feature_card icon="shield" title="Built for Performance" link="/technology/" label="Learn how it works"]Precision-balanced dampers that protect critical engine components.[/fluid_feature_card]'
		. '[fluid_feature_card icon="car" title="Browse the Lineup" link="/products/" label="Browse all dampers"]Diesel, Domestic Performance, Import Performance, and more — shop by product line.[/fluid_feature_card]'
		. '[/fluid_card_grid]'
		. '[fluid_heading_group eyebrow="The Fluidampr community" title="Real builders. Real results." align="left"]Champion engine builders trust viscous damping when the horsepower climbs.[/fluid_heading_group]'
		. '[fluid_community_grid]'
		. '[fluid_community_card title="2,800HP Gen III HEMI" handle="@boostedstbrad"]'
		. '[fluid_community_card title="Street-driven LS build" handle="@midnightmachine"]'
		. '[fluid_community_card title="Compound-turbo diesel" handle="@blacksmokebench"]'
		. '[/fluid_community_grid]'
		. '<p class="fluid-explore"><a href="/community/">Explore the Community ›</a></p>'
		. '[fluid_cta_banner title="Why choose Fluidampr" button="Learn more about Fluidampr" url="/technology/"]US-made viscous dampers engineered for broad-RPM protection, SFI 18.1 certification, and engines that will keep getting faster.[/fluid_cta_banner]';

	$finder = '[fluid_heading_group eyebrow="Product finder" title="Find your damper"]Search by vehicle, engine family, or part number. Fitment is powered by the Fluidampr catalog — not hardcoded into the theme.[/fluid_heading_group][fluid_finder_panel mode="full"]';

	$buy = '[fluid_heading_group eyebrow="Dealers" title="Where to buy"]Fluidampr performance dampers are sold through authorized performance distributors, retailers, and engine builders.[/fluid_heading_group]<div class="fluid-prose"><p>Use the dealer tools on this page as they come online. MAP/public pricing will display from catalog data; ecommerce can be added later without rebuilding these templates.</p></div>';

	$knowledge = '[fluid_heading_group eyebrow="Support" title="Knowledge center"]Install resources, application notes, and technical articles so you can build with confidence.[/fluid_heading_group][fluid_card_grid][fluid_feature_card icon="book" title="Instructions" link="/instructions/" label="View instructions"]QR-code-ready instruction URLs by part number.[/fluid_feature_card][fluid_feature_card icon="shield" title="Support / FAQ" link="/support/" label="Read FAQs"]Common fitment and installation questions.[/fluid_feature_card][fluid_feature_card icon="car" title="News" link="/news/" label="See news"]New applications, case studies, and product releases.[/fluid_feature_card][fluid_feature_card icon="pin" title="Contact" link="/contact/" label="Contact us"]Talk to Fluidampr technical support.[/fluid_feature_card][/fluid_card_grid]';

	$tech = '[fluid_heading_group eyebrow="Technology" title="Why choose Fluidampr"]Viscous damping protects rotating assemblies across the RPM range without tuning or rebuilds.[/fluid_heading_group]<div class="fluid-prose"><p>A stock elastomer damper is sized for an unmodified engine. Power adders, rotating-assembly changes, and higher RPM shift harmonics. Fluidampr uses a validated viscous design, precision-manufactured in the USA, to control torsional vibration as the build evolves.</p><ul><li>Broad protection across the entire RPM range</li><li>No tuning, no rebuilds, no elastomer aging</li><li>SFI 18.1 certified applications where specified</li><li>Domestic, import, and diesel coverage</li></ul></div>';

	$products = '[fluid_heading_group eyebrow="Catalog" title="Browse the lineup"]Diesel, domestic performance, import performance, and accessories. Individual product records stay in the SEMA integration layer.[/fluid_heading_group]<p><a class="fluid-button" href="/find-your-damper/">Find your damper</a></p>';

	$community = '[fluid_heading_group eyebrow="The Fluidampr community" title="Real builders. Real results."][fluid_community_grid][fluid_community_card title="2,800HP Gen III HEMI" handle="@boostedstbrad"][fluid_community_card title="Street-driven LS build" handle="@midnightmachine"][fluid_community_card title="Compound-turbo diesel" handle="@blacksmokebench"][/fluid_community_grid]';

	$instructions = '[fluid_heading_group eyebrow="Install" title="Instructions"]Look up installation instructions by part number. QR-code URLs can point at these same routes later.[/fluid_heading_group][fluid_finder_panel mode="compact" browse_label="Browse all dampers"]';

	$support = '[fluid_heading_group eyebrow="Help" title="Support / FAQ"]<div class="fluid-prose"><h2>Will a Fluidampr damper work with my power adder?</h2><p>Viscous damping is designed for broad-RPM protection as the combination changes. Always confirm the application in the finder.</p><h2>Can I paint or coat the damper?</h2><p>Follow Fluidampr technical guidance before coating. Improper coatings can affect balance and heat dissipation.</p><h2>Where do I find install instructions?</h2><p>Use the instructions page and search by part number. Those URLs are intended to stay QR-code compatible.</p></div>';

	$contact = '[fluid_heading_group eyebrow="Company" title="Contact"]<div class="fluid-prose"><p>11980 Walden Ave, Springville, NY 14141<br>(716) 592-1000<br>info@fluidampr.com</p></div><form class="fluid-form" method="post" action=""><label>Name<input type="text" name="name" required></label><label>Email<input type="email" name="email" required></label><label>Message<textarea name="message" required></textarea></label><button class="fluid-button" type="submit">Send</button></form>';

	$news = '[fluid_heading_group eyebrow="Updates" title="News"]Product releases, case studies, and application news.';

	$privacy = '[fluid_heading_group title="Privacy policy"]<div class="fluid-prose"><p>Replace this seeded copy with the approved Fluidampr privacy policy.</p></div>';
	$terms   = '[fluid_heading_group title="Terms of service"]<div class="fluid-prose"><p>Replace this seeded copy with the approved Fluidampr terms of service.</p></div>';

	$map = array(
		'home'              => array( 'title' => 'Home', 'content' => $home ),
		'find-your-damper'  => array( 'title' => 'Find Your Damper', 'content' => $finder ),
		'where-to-buy'      => array( 'title' => 'Where to Buy', 'content' => $buy ),
		'knowledge-center'  => array( 'title' => 'Knowledge Center', 'content' => $knowledge ),
		'technology'        => array( 'title' => 'Technology', 'content' => $tech ),
		'products'          => array( 'title' => 'Products', 'content' => $products ),
		'community'         => array( 'title' => 'Community', 'content' => $community ),
		'instructions'      => array( 'title' => 'Instructions', 'content' => $instructions ),
		'support'           => array( 'title' => 'Support / FAQ', 'content' => $support ),
		'contact'           => array( 'title' => 'Contact', 'content' => $contact ),
		'news'              => array( 'title' => 'News', 'content' => $news ),
		'privacy-policy'    => array( 'title' => 'Privacy policy', 'content' => $privacy ),
		'terms'             => array( 'title' => 'Terms of service', 'content' => $terms ),
	);

	foreach ( $map as $slug => $item ) {
		$map[ $slug ]['content'] = fluidampr_alb_section( $item['content'], 'home' === $slug ? 'fluid-section fluid-home' : 'fluid-section fluid-interior' );
	}

	return $map;
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
