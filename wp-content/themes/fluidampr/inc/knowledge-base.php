<?php
/**
 * Knowledge Base custom post type, topics, and article helpers.
 *
 * Editorial content lives in the child theme. Related WooCommerce products
 * are linked by SKU; this file never calls the SEMA API.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Knowledge Base post type slug.
 *
 * @return string
 */
function fluidampr_kb_post_type() {
	return 'fluidampr_kb';
}

/**
 * Knowledge Base topic taxonomy slug.
 *
 * @return string
 */
function fluidampr_kb_taxonomy() {
	return 'fluidampr_kb_topic';
}

/**
 * Default Knowledge Base topics from the development brief.
 *
 * @return array<string, string> slug => label
 */
function fluidampr_kb_default_topics() {
	return array(
		'general-fluidampr-questions' => __( 'General Fluidampr Questions', 'fluidampr' ),
		'harmonic-damper-basics'      => __( 'Harmonic Damper Basics', 'fluidampr' ),
		'fitment'                     => __( 'Fitment', 'fluidampr' ),
		'installation'                => __( 'Installation', 'fluidampr' ),
		'torque-specifications'       => __( 'Torque / Specifications', 'fluidampr' ),
		'diesel'                      => __( 'Diesel', 'fluidampr' ),
		'import'                      => __( 'Import', 'fluidampr' ),
		'domestic'                    => __( 'Domestic', 'fluidampr' ),
		'racing-performance'          => __( 'Racing / Performance', 'fluidampr' ),
		'troubleshooting'             => __( 'Troubleshooting', 'fluidampr' ),
		'warranty-product-care'       => __( 'Warranty / Product Care', 'fluidampr' ),
	);
}

/**
 * Register the Knowledge Base post type and topic taxonomy.
 *
 * @return void
 */
function fluidampr_kb_register() {
	register_post_type(
		fluidampr_kb_post_type(),
		array(
			'labels'              => array(
				'name'               => __( 'Knowledge Base', 'fluidampr' ),
				'singular_name'      => __( 'Article', 'fluidampr' ),
				'add_new'            => __( 'Add Article', 'fluidampr' ),
				'add_new_item'       => __( 'Add Knowledge Base Article', 'fluidampr' ),
				'edit_item'          => __( 'Edit Article', 'fluidampr' ),
				'new_item'           => __( 'New Article', 'fluidampr' ),
				'view_item'          => __( 'View Article', 'fluidampr' ),
				'search_items'       => __( 'Search Articles', 'fluidampr' ),
				'not_found'          => __( 'No articles found.', 'fluidampr' ),
				'not_found_in_trash' => __( 'No articles found in Trash.', 'fluidampr' ),
				'all_items'          => __( 'All Articles', 'fluidampr' ),
				'menu_name'          => __( 'Knowledge Base', 'fluidampr' ),
			),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'exclude_from_search' => false,
			'has_archive'         => true,
			'rewrite'             => array(
				'slug'       => 'knowledge-base',
				'with_front' => false,
			),
			'menu_icon'           => 'dashicons-book-alt',
			'menu_position'       => 26,
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		)
	);

	register_taxonomy(
		fluidampr_kb_taxonomy(),
		fluidampr_kb_post_type(),
		array(
			'labels'            => array(
				'name'          => __( 'Topics', 'fluidampr' ),
				'singular_name' => __( 'Topic', 'fluidampr' ),
				'search_items'  => __( 'Search Topics', 'fluidampr' ),
				'all_items'     => __( 'All Topics', 'fluidampr' ),
				'edit_item'     => __( 'Edit Topic', 'fluidampr' ),
				'update_item'   => __( 'Update Topic', 'fluidampr' ),
				'add_new_item'  => __( 'Add New Topic', 'fluidampr' ),
				'new_item_name' => __( 'New Topic Name', 'fluidampr' ),
				'menu_name'     => __( 'Topics', 'fluidampr' ),
			),
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'hierarchical'      => true,
			'rewrite'           => array(
				'slug'       => 'knowledge-topic',
				'with_front' => false,
			),
		)
	);
}
add_action( 'init', 'fluidampr_kb_register' );

/**
 * Insert the default topics once.
 *
 * @return void
 */
function fluidampr_kb_seed_topics() {
	if ( '1' === get_option( 'fluidampr_kb_topics_seeded' ) ) {
		return;
	}

	if ( ! taxonomy_exists( fluidampr_kb_taxonomy() ) ) {
		return;
	}

	foreach ( fluidampr_kb_default_topics() as $slug => $label ) {
		if ( ! term_exists( $slug, fluidampr_kb_taxonomy() ) ) {
			wp_insert_term( $label, fluidampr_kb_taxonomy(), array( 'slug' => $slug ) );
		}
	}

	update_option( 'fluidampr_kb_topics_seeded', '1', false );
}
add_action( 'init', 'fluidampr_kb_seed_topics', 20 );

/**
 * Flush rewrite rules once after the CPT is registered.
 *
 * @return void
 */
function fluidampr_kb_maybe_flush_rewrites() {
	if ( '1.0.0' === get_option( 'fluidampr_kb_rewrite_version' ) ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( 'fluidampr_kb_rewrite_version', '1.0.0', false );
}
add_action( 'init', 'fluidampr_kb_maybe_flush_rewrites', 30 );

/**
 * Flush rewrite rules when the child theme is activated.
 *
 * @return void
 */
function fluidampr_kb_flush_on_switch() {
	delete_option( 'fluidampr_kb_rewrite_version' );
}
add_action( 'after_switch_theme', 'fluidampr_kb_flush_on_switch' );

/**
 * Let Enfold’s Layout Builder edit Knowledge Base articles.
 *
 * @param array<int, string> $types Post types.
 * @return array<int, string>
 */
function fluidampr_kb_alb_post_types( $types ) {
	$types[] = fluidampr_kb_post_type();

	return array_values( array_unique( array_filter( $types ) ) );
}
add_filter( 'avf_alb_supported_post_types', 'fluidampr_kb_alb_post_types' );

/**
 * Keep Knowledge Base articles in site search when another query sets post_type.
 *
 * @param WP_Query $query Main query.
 * @return void
 */
function fluidampr_kb_include_in_search( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}

	$types = $query->get( 'post_type' );

	if ( empty( $types ) || 'any' === $types ) {
		return;
	}

	if ( is_string( $types ) ) {
		$types = array( $types );
	}

	if ( ! is_array( $types ) || in_array( fluidampr_kb_post_type(), $types, true ) ) {
		return;
	}

	$types[] = fluidampr_kb_post_type();
	$query->set( 'post_type', $types );
}
add_action( 'pre_get_posts', 'fluidampr_kb_include_in_search' );

/**
 * Normalize a part number for storage and lookup.
 *
 * @param string $sku Raw SKU.
 * @return string
 */
function fluidampr_kb_normalize_sku( $sku ) {
	$sku = strtoupper( trim( (string) $sku ) );
	$sku = preg_replace( '/[^A-Z0-9._-]/', '', $sku );

	return is_string( $sku ) ? $sku : '';
}

/**
 * Whether a search string looks like a Fluidampr part number.
 *
 * @param string $value Search string.
 * @return bool
 */
function fluidampr_kb_looks_like_sku( $value ) {
	$value = trim( (string) $value );

	return (bool) preg_match( '/^[A-Za-z0-9._-]{3,32}$/', $value );
}

/**
 * WooCommerce product ID for a SKU, or 0.
 *
 * @param string $sku Part number.
 * @return int
 */
function fluidampr_kb_product_id_from_sku( $sku ) {
	$sku = fluidampr_kb_normalize_sku( $sku );

	if ( '' === $sku ) {
		return 0;
	}

	if ( function_exists( 'wc_get_product_id_by_sku' ) ) {
		return (int) wc_get_product_id_by_sku( $sku );
	}

	return 0;
}

/**
 * Product SKU for a WooCommerce product ID.
 *
 * @param int $product_id Product ID.
 * @return string
 */
function fluidampr_kb_sku_from_product( $product_id ) {
	$product_id = (int) $product_id;

	if ( $product_id <= 0 ) {
		return '';
	}

	if ( function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( $product_id );

		if ( $product ) {
			return fluidampr_kb_normalize_sku( $product->get_sku() );
		}
	}

	return fluidampr_kb_normalize_sku( (string) get_post_meta( $product_id, '_sku', true ) );
}

/**
 * Related product IDs stored on an article.
 *
 * @param int $post_id Article ID.
 * @return array<int, int>
 */
function fluidampr_kb_get_product_ids( $post_id ) {
	$ids = get_post_meta( (int) $post_id, '_fluidampr_kb_product_id' );

	if ( ! is_array( $ids ) ) {
		return array();
	}

	$ids = array_map( 'intval', $ids );
	$ids = array_filter( $ids );

	return array_values( array_unique( $ids ) );
}

/**
 * Related part numbers stored on an article.
 *
 * @param int $post_id Article ID.
 * @return array<int, string>
 */
function fluidampr_kb_get_part_numbers( $post_id ) {
	$skus = get_post_meta( (int) $post_id, '_fluidampr_kb_part_number' );

	if ( ! is_array( $skus ) ) {
		return array();
	}

	$out = array();

	foreach ( $skus as $sku ) {
		$normalized = fluidampr_kb_normalize_sku( (string) $sku );

		if ( '' !== $normalized ) {
			$out[] = $normalized;
		}
	}

	return array_values( array_unique( $out ) );
}

/**
 * Whether an article is marked as a featured / common question.
 *
 * @param int $post_id Article ID.
 * @return bool
 */
function fluidampr_kb_is_featured( $post_id ) {
	return '1' === (string) get_post_meta( (int) $post_id, '_fluidampr_kb_featured', true );
}

/**
 * Format an article for REST and frontend lists.
 *
 * @param WP_Post|int $post Article.
 * @return array<string, mixed>
 */
function fluidampr_kb_format_article( $post ) {
	$post = get_post( $post );

	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	$terms = get_the_terms( $post, fluidampr_kb_taxonomy() );
	$cats  = array();

	if ( is_array( $terms ) ) {
		foreach ( $terms as $term ) {
			$link = get_term_link( $term );

			$cats[] = array(
				'name' => $term->name,
				'slug' => $term->slug,
				'url'  => is_wp_error( $link ) ? '' : $link,
			);
		}
	}

	$excerpt = has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 36, '…' );
	$excerpt = html_entity_decode( wp_strip_all_tags( (string) $excerpt ), ENT_QUOTES, 'UTF-8' );

	return array(
		'id'                 => (int) $post->ID,
		'title'              => get_the_title( $post ),
		'url'                => get_permalink( $post ),
		'excerpt'            => $excerpt,
		'featured'           => fluidampr_kb_is_featured( $post->ID ),
		'categories'         => $cats,
		'related_skus'       => fluidampr_kb_get_part_numbers( $post->ID ),
		'related_product_ids'=> fluidampr_kb_get_product_ids( $post->ID ),
	);
}

/**
 * Query published Knowledge Base articles.
 *
 * @param array<string, mixed> $args Query args.
 * @return array<int, array<string, mixed>>
 */
function fluidampr_kb_query_articles( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			's'              => '',
			'category'       => '',
			'sku'            => '',
			'product_id'     => 0,
			'featured'       => false,
			'posts_per_page' => 20,
		)
	);

	$query_args = array(
		'post_type'           => fluidampr_kb_post_type(),
		'post_status'         => 'publish',
		'posts_per_page'      => min( 50, max( 1, (int) $args['posts_per_page'] ) ),
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'orderby'             => 'title',
		'order'               => 'ASC',
	);

	$search = trim( (string) $args['s'] );

	if ( '' !== $search ) {
		$query_args['s']       = $search;
		$query_args['orderby'] = 'relevance';
	}

	if ( '' !== (string) $args['category'] ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => fluidampr_kb_taxonomy(),
				'field'    => 'slug',
				'terms'    => sanitize_title( (string) $args['category'] ),
			),
		);
	}

	$meta       = array();
	$sku        = fluidampr_kb_normalize_sku( (string) $args['sku'] );
	$product_id = (int) $args['product_id'];

	if ( ! empty( $args['featured'] ) ) {
		$meta[] = array(
			'key'   => '_fluidampr_kb_featured',
			'value' => '1',
		);
	}

	if ( '' !== $sku && $product_id <= 0 ) {
		$product_id = fluidampr_kb_product_id_from_sku( $sku );
	}

	if ( '' !== $sku || $product_id > 0 ) {
		$sku_query = array( 'relation' => 'OR' );

		if ( '' !== $sku ) {
			$sku_query[] = array(
				'key'   => '_fluidampr_kb_part_number',
				'value' => $sku,
			);
		}

		if ( $product_id > 0 ) {
			$sku_query[] = array(
				'key'   => '_fluidampr_kb_product_id',
				'value' => $product_id,
			);
		}

		$meta[] = $sku_query;
	}

	if ( count( $meta ) > 1 ) {
		$query_args['meta_query'] = array_merge( array( 'relation' => 'AND' ), $meta );
	} elseif ( $meta ) {
		$query_args['meta_query'] = $meta;
	}

	$query   = new WP_Query( $query_args );
	$results = array();

	foreach ( $query->posts as $post ) {
		$formatted = fluidampr_kb_format_article( $post );

		if ( $formatted ) {
			$results[] = $formatted;
		}
	}

	return $results;
}

/**
 * Search articles by keyword, topic, and/or part number. SKU hits sort first.
 *
 * @param array<string, mixed> $args Search args.
 * @return array<int, array<string, mixed>>
 */
function fluidampr_kb_search( $args = array() ) {
	$args     = wp_parse_args(
		$args,
		array(
			's'              => '',
			'category'       => '',
			'sku'            => '',
			'featured'       => false,
			'posts_per_page' => 20,
		)
	);
	$search   = trim( (string) $args['s'] );
	$sku      = fluidampr_kb_normalize_sku( (string) $args['sku'] );
	$featured = ! empty( $args['featured'] );

	if ( '' === $sku && fluidampr_kb_looks_like_sku( $search ) ) {
		$sku = fluidampr_kb_normalize_sku( $search );
	}

	$shared = array(
		'category'       => (string) $args['category'],
		'featured'       => $featured,
		'posts_per_page' => (int) $args['posts_per_page'],
	);

	if ( '' === $search && '' === $sku ) {
		return fluidampr_kb_query_articles( $shared );
	}

	$by_sku     = array();
	$by_keyword = array();

	if ( '' !== $sku ) {
		$by_sku = fluidampr_kb_query_articles(
			array_merge(
				$shared,
				array(
					's'   => '',
					'sku' => $sku,
				)
			)
		);
	}

	if ( '' !== $search ) {
		$by_keyword = fluidampr_kb_query_articles(
			array_merge(
				$shared,
				array(
					's'   => $search,
					'sku' => '',
				)
			)
		);
	}

	$merged = array();
	$seen   = array();

	foreach ( array_merge( $by_sku, $by_keyword ) as $article ) {
		$id = (int) ( $article['id'] ?? 0 );

		if ( $id <= 0 || isset( $seen[ $id ] ) ) {
			continue;
		}

		$seen[ $id ] = true;
		$merged[]    = $article;
	}

	return array_slice( $merged, 0, (int) $shared['posts_per_page'] );
}

/**
 * Related-product list markup for a Knowledge Base article.
 *
 * @param int $post_id Article ID.
 * @return string
 */
function fluidampr_kb_related_products_html( $post_id ) {
	$ids = fluidampr_kb_get_product_ids( $post_id );

	if ( ! $ids ) {
		$skus = fluidampr_kb_get_part_numbers( $post_id );

		foreach ( $skus as $sku ) {
			$product_id = fluidampr_kb_product_id_from_sku( $sku );

			if ( $product_id > 0 ) {
				$ids[] = $product_id;
			}
		}

		$ids = array_values( array_unique( $ids ) );
	}

	if ( ! $ids ) {
		return '';
	}

	$items = array();

	foreach ( $ids as $product_id ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;

		if ( ! $product || ! $product->is_visible() ) {
			continue;
		}

		$sku   = fluidampr_kb_sku_from_product( $product_id );
		$label = $product->get_name();

		if ( '' !== $sku ) {
			$label .= ' (' . $sku . ')';
		}

		$items[] = '<li><a href="' . esc_url( get_permalink( $product_id ) ) . '">' . esc_html( $label ) . '</a></li>';
	}

	if ( ! $items ) {
		return '';
	}

	return '<aside class="fluid-kb-related fluid-kb-related--products"><h2>' . esc_html__( 'Related products', 'fluidampr' ) . '</h2><ul>' . implode( '', $items ) . '</ul></aside>';
}

/**
 * Related-article list markup for a WooCommerce product.
 *
 * @param int $product_id Product ID.
 * @return string
 */
function fluidampr_kb_related_articles_html( $product_id ) {
	$product_id = (int) $product_id;
	$sku        = fluidampr_kb_sku_from_product( $product_id );

	if ( $product_id <= 0 ) {
		return '';
	}

	$articles = fluidampr_kb_query_articles(
		array(
			'sku'            => $sku,
			'product_id'     => $product_id,
			'posts_per_page' => 10,
		)
	);

	if ( ! $articles ) {
		return '';
	}

	$items = array();

	foreach ( $articles as $article ) {
		$items[] = '<li><a href="' . esc_url( $article['url'] ) . '">' . esc_html( $article['title'] ) . '</a></li>';
	}

	return '<aside class="fluid-kb-related fluid-kb-related--articles"><h2>' . esc_html__( 'Technical articles', 'fluidampr' ) . '</h2><ul>' . implode( '', $items ) . '</ul></aside>';
}

/**
 * Append related products to article content (classic and ALB).
 *
 * @param string $content Post content.
 * @return string
 */
function fluidampr_kb_append_related_products( $content ) {
	static $appended = false;

	if ( $appended || is_admin() || ! is_singular( fluidampr_kb_post_type() ) || ! in_the_loop() ) {
		return $content;
	}

	$appended = true;

	return $content . fluidampr_kb_related_products_html( get_the_ID() );
}
add_filter( 'the_content', 'fluidampr_kb_append_related_products', 20 );

/**
 * List related Knowledge Base articles on single product pages.
 *
 * @return void
 */
function fluidampr_kb_render_product_articles() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	echo fluidampr_kb_related_articles_html( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'woocommerce_after_single_product_summary', 'fluidampr_kb_render_product_articles', 12 );

/**
 * Semantic article list markup used by the shortcode and archive.
 *
 * @param array<int, array<string, mixed>> $articles Formatted articles.
 * @return string
 */
function fluidampr_kb_articles_list_html( $articles ) {
	if ( ! $articles ) {
		return '';
	}

	$html = '<ul class="fluid-kb__list">';

	foreach ( $articles as $article ) {
		$html .= '<li class="fluid-kb__item">';
		$html .= '<a class="fluid-kb__link" href="' . esc_url( (string) ( $article['url'] ?? '' ) ) . '">' . esc_html( (string) ( $article['title'] ?? '' ) ) . '</a>';

		if ( ! empty( $article['excerpt'] ) ) {
			$html .= '<p class="fluid-kb__excerpt">' . esc_html( (string) $article['excerpt'] ) . '</p>';
		}

		$meta = array();

		if ( ! empty( $article['featured'] ) ) {
			$meta[] = esc_html__( 'Common question', 'fluidampr' );
		}

		if ( ! empty( $article['categories'] ) && is_array( $article['categories'] ) ) {
			foreach ( $article['categories'] as $category ) {
				if ( ! empty( $category['name'] ) ) {
					$meta[] = esc_html( (string) $category['name'] );
				}
			}
		}

		if ( ! empty( $article['related_skus'] ) && is_array( $article['related_skus'] ) ) {
			$meta[] = esc_html( implode( ', ', $article['related_skus'] ) );
		}

		if ( $meta ) {
			$html .= '<p class="fluid-kb__meta">' . implode( ' · ', $meta ) . '</p>';
		}

		$html .= '</li>';
	}

	$html .= '</ul>';

	return $html;
}

/**
 * [fluid_knowledge_base] lookup: search, topic filter, and featured questions.
 *
 * @param array<string, string>|string $atts Shortcode attributes.
 * @return string
 */
function fluidampr_kb_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'featured' => '',
			'category' => '',
		),
		$atts,
		'fluid_knowledge_base'
	);

	$featured_only = in_array( strtolower( (string) $atts['featured'] ), array( '1', 'true', 'yes' ), true );
	$category      = sanitize_title( (string) $atts['category'] );
	$topics        = get_terms(
		array(
			'taxonomy'   => fluidampr_kb_taxonomy(),
			'hide_empty' => false,
		)
	);

	$featured = fluidampr_kb_query_articles(
		array(
			'featured'       => true,
			'category'       => $category,
			'posts_per_page' => 10,
		)
	);

	$initial = $featured_only
		? $featured
		: fluidampr_kb_query_articles(
			array(
				'category'       => $category,
				'posts_per_page' => 10,
			)
		);

	ob_start();
	?>
	<div class="fluid-kb" data-fluid-kb <?php echo $featured_only ? 'data-featured="1"' : ''; ?> <?php echo $category ? 'data-category="' . esc_attr( $category ) . '"' : ''; ?>>
		<?php if ( ! $featured_only ) : ?>
			<form class="fluid-kb__form" data-fluid-kb-form>
				<label class="fluid-kb__field">
					<span class="screen-reader-text"><?php esc_html_e( 'Search articles', 'fluidampr' ); ?></span>
					<input type="search" data-fluid-kb-q placeholder="<?php esc_attr_e( 'Search by topic, keyword, or part number', 'fluidampr' ); ?>" autocomplete="off">
				</label>
				<?php if ( ! is_wp_error( $topics ) && $topics ) : ?>
					<label class="fluid-kb__field">
						<span class="screen-reader-text"><?php esc_html_e( 'Topic', 'fluidampr' ); ?></span>
						<select data-fluid-kb-category>
							<option value=""><?php esc_html_e( 'All topics', 'fluidampr' ); ?></option>
							<?php foreach ( $topics as $topic ) : ?>
								<option value="<?php echo esc_attr( $topic->slug ); ?>" <?php selected( $category, $topic->slug ); ?>><?php echo esc_html( $topic->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>
				<button type="submit" class="fluid-button"><?php esc_html_e( 'Search', 'fluidampr' ); ?></button>
			</form>
		<?php endif; ?>

		<p class="fluid-kb__status" data-fluid-kb-status aria-live="polite"></p>

		<?php if ( ! $featured_only && $featured ) : ?>
			<section class="fluid-kb__featured">
				<h2><?php esc_html_e( 'Common questions', 'fluidampr' ); ?></h2>
				<?php echo fluidampr_kb_articles_list_html( $featured ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</section>
		<?php endif; ?>

		<section class="fluid-kb__results" data-fluid-kb-results>
			<?php if ( $featured_only ) : ?>
				<h2><?php esc_html_e( 'Common questions', 'fluidampr' ); ?></h2>
			<?php endif; ?>
			<?php if ( $initial ) : ?>
				<?php echo fluidampr_kb_articles_list_html( $initial ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php elseif ( $featured_only ) : ?>
				<p><?php esc_html_e( 'No featured questions have been published yet.', 'fluidampr' ); ?></p>
			<?php else : ?>
				<p><?php esc_html_e( 'No articles have been published yet.', 'fluidampr' ); ?></p>
			<?php endif; ?>
		</section>
	</div>
	<?php
	return (string) ob_get_clean();
}

require_once __DIR__ . '/knowledge-base-admin.php';
require_once __DIR__ . '/knowledge-base-rest.php';
