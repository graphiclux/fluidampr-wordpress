<?php
/**
 * Enfold Advanced Layout Builder page content.
 *
 * Seeded pages are built from native Enfold elements so editors can change
 * copy, images, buttons, and layout in the Avia builder. Custom shortcodes
 * in Text Blocks are the product finder, the Instagram feed, and the footer.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build an Enfold shortcode.
 *
 * @param string               $tag     Shortcode tag.
 * @param array<string, mixed> $atts    Attributes.
 * @param string|false         $content Inner content, or false for a self-closing tag.
 * @return string
 */
function fluidampr_sc( $tag, $atts = array(), $content = false ) {
	$attr = '';

	foreach ( $atts as $key => $value ) {
		if ( null === $value || false === $value || '' === $value ) {
			continue;
		}

		$attr .= ' ' . $key . "='" . esc_attr( (string) $value ) . "'";
	}

	if ( false === $content ) {
		return '[' . $tag . $attr . ']';
	}

	return '[' . $tag . $attr . ']' . $content . '[/' . $tag . ']';
}

/**
 * Color Section wrapper.
 *
 * @param string               $inner Inner shortcodes.
 * @param array<string, mixed> $atts  Section attributes.
 * @return string
 */
function fluidampr_alb_section( $inner, $atts = array() ) {
	$atts = wp_parse_args(
		$atts,
		array(
			'padding'      => 'large',
			'color'        => 'main_color',
			'background'   => 'bg_color',
			'custom_bg'    => '#ffffff',
			'custom_class' => 'fluid-section',
		)
	);

	return fluidampr_sc( 'av_section', $atts, $inner );
}

/**
 * Column wrapper.
 *
 * @param string $span  one_full, one_half, one_third, one_fourth.
 * @param bool   $first Whether this is the first column in the row.
 * @param string $inner Inner shortcodes.
 * @param string $class Extra class.
 * @return string
 */
function fluidampr_alb_col( $span, $first, $inner, $class = '' ) {
	$attr = '';

	if ( $first ) {
		$attr .= ' first';
	}

	if ( $class ) {
		$attr .= " custom_class='" . esc_attr( $class ) . "'";
	}

	return '[av_' . $span . $attr . ']' . $inner . '[/av_' . $span . ']';
}

/**
 * Special Heading with optional eyebrow subheading.
 *
 * @param string $title   Heading text.
 * @param string $eyebrow Optional eyebrow (subheading above).
 * @param string $tag     Heading tag.
 * @param string $class   Extra class.
 * @return string
 */
function fluidampr_alb_heading( $title, $eyebrow = '', $tag = 'h2', $class = 'fluid-heading' ) {
	$atts = array(
		'tag'           => $tag,
		'heading'       => $title,
		'custom_class'  => $class,
		'color'         => 'custom-color-heading',
		'custom_font'   => '#000013',
	);

	if ( $eyebrow ) {
		$atts['style']              = 'blockquote modern-quote';
		$atts['subheading_active']  = 'subheading_above';
		$atts['subheading_color']   = '#265ca4';
		return fluidampr_sc( 'av_heading', $atts, $eyebrow );
	}

	return fluidampr_sc( 'av_heading', $atts, '' );
}

/**
 * Text Block.
 *
 * @param string $html  Inner HTML.
 * @param string $class Extra class.
 * @return string
 */
function fluidampr_alb_text( $html, $class = '' ) {
	$atts = array();

	if ( $class ) {
		$atts['custom_class'] = $class;
	}

	return fluidampr_sc( 'av_textblock', $atts, $html );
}

/**
 * Button.
 *
 * @param string $label Label.
 * @param string $url   URL.
 * @param string $class Extra class.
 * @return string
 */
function fluidampr_alb_button( $label, $url, $class = 'fluid-button' ) {
	$atts = array(
		'label'       => $label,
		'link'        => 'manually,' . $url,
		'size'        => 'large',
		'position'    => 'left',
		'color'       => 'theme-color',
		'icon_select' => 'no',
	);

	if ( $class ) {
		$atts['custom_class'] = $class;
	}

	return fluidampr_sc( 'av_button', $atts );
}

/**
 * Icon Box, with either a text link (Figma cards) or a Button.
 *
 * @param string $icon    Entypo icon unicode.
 * @param string $title   Title.
 * @param string $text    Body copy.
 * @param string $url     Link URL.
 * @param string $label   Link/button label.
 * @param string $variant 'link' or 'button'.
 * @return string
 */
function fluidampr_alb_iconbox( $icon, $title, $text, $url, $label, $variant = 'button' ) {
	$atts = array(
		'icon'         => $icon,
		'font'         => 'entypo-fontello',
		'title'        => $title,
		'position'     => 'top',
		'boxed'        => 'av-no-box',
		'custom_class' => 'fluid-card-iconbox',
	);

	if ( 'link' === $variant ) {
		$content = $text . '<p><a class="fluid-card__link" href="' . esc_url( $url ) . '">' . esc_html( $label ) . ' <span aria-hidden="true">›</span></a></p>';
		return fluidampr_sc( 'av_icon_box', $atts, $content );
	}

	return fluidampr_sc( 'av_icon_box', $atts, $text ) . fluidampr_alb_button( $label, $url, 'fluid-button fluid-card-button' );
}

/**
 * Theme image URL.
 *
 * @param string $file Filename in assets/images.
 * @return string
 */
function fluidampr_theme_img( $file ) {
	return set_url_scheme( FLUIDAMPR_THEME_URI . '/assets/images/' . ltrim( $file, '/' ), 'https' );
}

/**
 * Image element.
 *
 * @param string $src   Image URL.
 * @param string $alt   Alt text.
 * @param string $align Alignment.
 * @param string $link  Optional URL.
 * @return string
 */
function fluidampr_alb_image( $src, $alt = '', $align = 'center', $link = '' ) {
	$atts = array(
		'src'   => $src,
		'align' => $align,
		'alt'   => $alt,
		'styling' => '',
		'caption' => '',
	);

	if ( $link ) {
		$atts['link']   = 'manually,' . $link;
		$atts['target'] = '_blank';
	}

	return fluidampr_sc( 'av_image', $atts, '' );
}

/**
 * Manually linked URL for Enfold button/image elements.
 *
 * @param string $path Path or URL.
 * @return string
 */
function fluidampr_url( $path ) {
	if ( 0 === strpos( $path, 'http' ) ) {
		return $path;
	}

	return home_url( $path );
}

/**
 * Page content map built from Enfold ALB elements.
 *
 * @return array<string, array<string, mixed>>
 */
function fluidampr_seed_page_definitions() {
	$hero = fluidampr_theme_img( 'hero-damper.jpg' );
	$ig   = 'https://www.instagram.com/theoriginalfluidampr/';
	$shop = fluidampr_theme_img( 'cta-engine.jpg' );

	$home = fluidampr_alb_section(
		fluidampr_alb_col(
			'one_half',
			true,
			fluidampr_alb_text(
				'<h1 class="fluid-hero__heading"><span class="fluid-hero__heading-lead">Find the right damper.</span><span class="fluid-hero__heading-sub">Build it right. <span class="fluid-hero__accent">With confidence.</span></span></h1>',
				'fluid-hero-heading-block'
			)
			. fluidampr_alb_text( '[fluid_finder_panel mode="compact"]', 'fluid-finder-embed' )
		)
		. fluidampr_alb_col(
			'one_half',
			false,
			fluidampr_alb_image( $hero, 'Fluidampr performance damper', 'center' )
		),
		array(
			'padding'      => 'small',
			'custom_class' => 'fluid-section fluid-home-hero',
		)
	);

	$home .= fluidampr_alb_section(
		fluidampr_alb_col( 'one_fourth', true, fluidampr_alb_iconbox( 'ue842', 'Where to Buy', 'Find authorized Fluidampr dealers and trusted retailers near you.', fluidampr_url( '/where-to-buy/' ), 'Find a dealer', 'link' ) )
		. fluidampr_alb_col( 'one_fourth', false, fluidampr_alb_iconbox( 'ue84e', 'Knowledge Center', 'Guides, tech articles, and install resources to build with confidence.', fluidampr_url( '/knowledge-center/' ), 'Get support', 'link' ) )
		. fluidampr_alb_col( 'one_fourth', false, fluidampr_alb_iconbox( 'ue8de', 'Built for Performance', 'Precision-balanced dampers that protect critical engine components.', fluidampr_url( '/technology/' ), 'Learn how it works', 'link' ) )
		. fluidampr_alb_col( 'one_fourth', false, fluidampr_alb_iconbox( 'ue810', 'Browse the Lineup', 'Diesel, Domestic Performance, Import Performance, and more — shop by product line.', fluidampr_url( '/products/' ), 'Browse all dampers', 'link' ) ),
		array(
			'padding'      => 'default',
			'custom_class' => 'fluid-section fluid-home-cards',
		)
	);

	$home .= fluidampr_alb_section(
		fluidampr_alb_col(
			'one_full',
			true,
			fluidampr_alb_heading( 'Real builders. Real results.', 'The Fluidampr community' )
			. fluidampr_alb_text( '<p class="fluid-community-intro">From daily drivers to race day, see how builders are putting Fluidampr to work.</p>' )
			. fluidampr_alb_text( '[fluid_instagram_feed]', 'fluid-instagram-embed' )
			. fluidampr_alb_text( '<p class="fluid-explore"><a href="' . esc_url( fluidampr_url( '/community/' ) ) . '">Explore the Community <span aria-hidden="true">›</span></a></p>' )
		),
		array(
			'padding'      => 'default',
			'custom_class' => 'fluid-section fluid-home-community',
		)
	);

	$home .= fluidampr_alb_section(
		fluidampr_alb_col(
			'one_half',
			true,
			fluidampr_sc(
				'av_heading',
				array(
					'tag'          => 'h2',
					'heading'      => 'Why choose Fluidampr',
					'color'        => 'custom-color-heading',
					'custom_font'  => '#ffffff',
					'custom_class' => 'fluid-heading fluid-cta-heading',
				),
				''
			)
			. fluidampr_alb_text( '<p>Precision-engineered in the USA for broad-RPM protection, SFI 18.1 certification, and engines that will keep getting faster.</p>' )
			. fluidampr_alb_button( 'Learn more about Fluidampr', fluidampr_url( '/technology/' ) ),
			'fluid-cta-copy'
		)
		. fluidampr_alb_col(
			'one_half',
			false,
			fluidampr_alb_image( $shop, 'Fluidampr performance damper on the engine', 'center' ),
			'fluid-cta-media'
		),
		array(
			'padding'      => 'no-padding',
			'color'        => 'alternate_color',
			'custom_bg'    => '#000013',
			'custom_class' => 'fluid-section fluid-cta-section',
		)
	);

	$finder = fluidampr_alb_section(
		fluidampr_alb_col(
			'one_full',
			true,
			fluidampr_alb_heading( 'Find your damper', 'Product finder' )
			. fluidampr_alb_text( '<p>Search by vehicle, engine family, or part number. Fitment is powered by the Fluidampr catalog — not hardcoded into the theme.</p>' )
			. fluidampr_alb_text( '[fluid_finder_panel mode="full"]', 'fluid-finder-embed' )
		),
		array( 'custom_class' => 'fluid-section fluid-interior' )
	);

	$buy = fluidampr_alb_section(
		fluidampr_alb_col(
			'one_full',
			true,
			fluidampr_alb_heading( 'Where to buy', 'Dealers' )
			. fluidampr_alb_text( '<p>Fluidampr performance dampers are sold through authorized performance distributors, retailers, and engine builders.</p><p>Use the dealer tools on this page as they come online. MAP/public pricing will display from catalog data; ecommerce can be added later without rebuilding these templates.</p>' )
		),
		array( 'custom_class' => 'fluid-section fluid-interior' )
	);

	$knowledge = fluidampr_alb_section(
		fluidampr_alb_col(
			'one_full',
			true,
			fluidampr_alb_heading( 'Knowledge center', 'Support' )
			. fluidampr_alb_text( '<p>Install resources, application notes, and technical articles so you can build with confidence.</p>' )
		)
		. fluidampr_alb_col( 'one_fourth', true, fluidampr_alb_iconbox( 'ue836', 'Instructions', 'QR-code-ready instruction URLs by part number.', fluidampr_url( '/instructions/' ), 'View instructions' ) )
		. fluidampr_alb_col( 'one_fourth', false, fluidampr_alb_iconbox( 'ue8bd', 'Support / FAQ', 'Common fitment and installation questions.', fluidampr_url( '/support/' ), 'Read FAQs' ) )
		. fluidampr_alb_col( 'one_fourth', false, fluidampr_alb_iconbox( 'ue8cc', 'News', 'New applications, case studies, and product releases.', fluidampr_url( '/news/' ), 'See news' ) )
		. fluidampr_alb_col( 'one_fourth', false, fluidampr_alb_iconbox( 'ue809', 'Contact', 'Talk to Fluidampr technical support.', fluidampr_url( '/contact/' ), 'Contact us' ) ),
		array( 'custom_class' => 'fluid-section fluid-interior' )
	);

	$tech = fluidampr_alb_section(
		fluidampr_alb_col(
			'one_full',
			true,
			fluidampr_alb_heading( 'Why choose Fluidampr', 'Technology' )
			. fluidampr_alb_text( '<p>A stock elastomer damper is sized for an unmodified engine. Power adders, rotating-assembly changes, and higher RPM shift harmonics. Fluidampr uses a validated viscous design, precision-manufactured in the USA, to control torsional vibration as the build evolves.</p><ul><li>Broad protection across the entire RPM range</li><li>No tuning, no rebuilds, no elastomer aging</li><li>SFI 18.1 certified applications where specified</li><li>Domestic, import, and diesel coverage</li></ul>' )
		),
		array( 'custom_class' => 'fluid-section fluid-interior' )
	);

	$products = fluidampr_alb_section(
		fluidampr_alb_col(
			'one_full',
			true,
			fluidampr_alb_heading( 'Browse the lineup', 'Catalog' )
			. fluidampr_alb_text( '<p>Diesel, domestic performance, import performance, and accessories. Individual product records stay in the SEMA integration layer.</p>' )
			. fluidampr_alb_button( 'Find your damper', fluidampr_url( '/find-your-damper/' ) )
		),
		array( 'custom_class' => 'fluid-section fluid-interior' )
	);

	$community = fluidampr_alb_section(
		fluidampr_alb_col(
			'one_full',
			true,
			fluidampr_alb_heading( 'Real builders. Real results.', 'The Fluidampr community' )
			. fluidampr_alb_text( '<p>From daily drivers to race day, see how builders are putting Fluidampr to work. Follow along on Instagram at @theoriginalfluidampr.</p>' )
			. fluidampr_alb_text( '[fluid_instagram_feed]', 'fluid-instagram-embed' )
			. fluidampr_alb_button( 'Follow @theoriginalfluidampr', $ig )
		),
		array( 'custom_class' => 'fluid-section fluid-interior fluid-home-community' )
	);

	$instructions = fluidampr_alb_section(
		fluidampr_alb_col(
			'one_full',
			true,
			fluidampr_alb_heading( 'Instructions', 'Install' )
			. fluidampr_alb_text( '<p>Look up installation instructions by part number. QR-code URLs can point at these same routes later.</p>' )
			. fluidampr_alb_text( '[fluid_finder_panel mode="compact"]', 'fluid-finder-embed' )
		),
		array( 'custom_class' => 'fluid-section fluid-interior' )
	);

	$faq  = fluidampr_sc( 'av_toggle', array( 'title' => 'Will a Fluidampr damper work with my power adder?' ), 'Viscous damping is designed for broad-RPM protection as the combination changes. Always confirm the application in the finder.' );
	$faq .= fluidampr_sc( 'av_toggle', array( 'title' => 'Can I paint or coat the damper?' ), 'Follow Fluidampr technical guidance before coating. Improper coatings can affect balance and heat dissipation.' );
	$faq .= fluidampr_sc( 'av_toggle', array( 'title' => 'Where do I find install instructions?' ), 'Use the instructions page and search by part number. Those URLs are intended to stay QR-code compatible.' );

	$support = fluidampr_alb_section(
		fluidampr_alb_col(
			'one_full',
			true,
			fluidampr_alb_heading( 'Support / FAQ', 'Help' )
			. fluidampr_sc( 'av_toggle_container', array( 'styling' => '' ), $faq )
		),
		array( 'custom_class' => 'fluid-section fluid-interior' )
	);

	$contact_form  = fluidampr_sc( 'av_contact_field', array( 'label' => 'Name', 'type' => 'text', 'is_empty' => 'Yes' ), '' );
	$contact_form .= fluidampr_sc( 'av_contact_field', array( 'label' => 'E-Mail', 'type' => 'text', 'is_email' => 'Yes', 'is_empty' => 'Yes' ), '' );
	$contact_form .= fluidampr_sc( 'av_contact_field', array( 'label' => 'Message', 'type' => 'textarea', 'is_empty' => 'Yes' ), '' );

	$contact = fluidampr_alb_section(
		fluidampr_alb_col(
			'one_half',
			true,
			fluidampr_alb_heading( 'Contact', 'Company' )
			. fluidampr_alb_text( '<p>11980 Walden Ave<br>Springville, NY 14141<br>(716) 592-1000<br>info@fluidampr.com</p>' )
		)
		. fluidampr_alb_col(
			'one_half',
			false,
			fluidampr_sc(
				'av_contact',
				array(
					'email'  => 'info@fluidampr.com',
					'title'  => '',
					'button' => 'Send',
				),
				$contact_form
			)
		),
		array( 'custom_class' => 'fluid-section fluid-interior' )
	);

	$news = fluidampr_alb_section(
		fluidampr_alb_col(
			'one_full',
			true,
			fluidampr_alb_heading( 'News', 'Updates' )
			. fluidampr_alb_text( '<p>Product releases, case studies, and application news.</p>' )
			. fluidampr_sc(
				'av_blog',
				array(
					'blog_type' => 'posts',
					'items'     => '9',
					'columns'   => '3',
					'paginate'  => 'yes',
				)
			)
		),
		array( 'custom_class' => 'fluid-section fluid-interior' )
	);

	$privacy = fluidampr_alb_section(
		fluidampr_alb_col(
			'one_full',
			true,
			fluidampr_alb_heading( 'Privacy policy' )
			. fluidampr_alb_text( '<p>Replace this seeded copy with the approved Fluidampr privacy policy.</p>' )
		),
		array( 'custom_class' => 'fluid-section fluid-interior' )
	);

	$terms = fluidampr_alb_section(
		fluidampr_alb_col(
			'one_full',
			true,
			fluidampr_alb_heading( 'Terms of service' )
			. fluidampr_alb_text( '<p>Replace this seeded copy with the approved Fluidampr terms of service.</p>' )
		),
		array( 'custom_class' => 'fluid-section fluid-interior' )
	);

	$footer = fluidampr_alb_section(
		fluidampr_alb_col(
			'one_full',
			true,
			fluidampr_alb_text( '[fluid_site_footer]', 'fluid-footer-embed' )
		),
		array(
			'padding'      => 'no-padding',
			'custom_class' => 'fluid-section fluid-footer-section',
		)
	);

	return array(
		'home'             => array( 'title' => 'Home', 'content' => $home ),
		'find-your-damper' => array( 'title' => 'Find Your Damper', 'content' => $finder ),
		'where-to-buy'     => array( 'title' => 'Where to Buy', 'content' => $buy ),
		'knowledge-center' => array( 'title' => 'Knowledge Center', 'content' => $knowledge ),
		'technology'       => array( 'title' => 'Technology', 'content' => $tech ),
		'products'         => array( 'title' => 'Products', 'content' => $products ),
		'community'        => array( 'title' => 'Community', 'content' => $community ),
		'instructions'     => array( 'title' => 'Instructions', 'content' => $instructions ),
		'support'          => array( 'title' => 'Support / FAQ', 'content' => $support ),
		'contact'          => array( 'title' => 'Contact', 'content' => $contact ),
		'news'             => array( 'title' => 'News', 'content' => $news ),
		'privacy-policy'   => array( 'title' => 'Privacy policy', 'content' => $privacy ),
		'terms'            => array( 'title' => 'Terms of service', 'content' => $terms ),
		'site-footer'      => array(
			'title'     => 'Footer',
			'is_footer' => true,
			'content'   => $footer,
		),
	);
}
