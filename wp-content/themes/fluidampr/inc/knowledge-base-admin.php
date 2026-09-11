<?php
/**
 * Knowledge Base article metabox: featured flag and related part numbers.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the article details metabox and list-table columns.
 *
 * @return void
 */
function fluidampr_kb_admin_register() {
	add_action( 'add_meta_boxes', 'fluidampr_kb_add_metabox' );
	add_action( 'save_post_' . fluidampr_kb_post_type(), 'fluidampr_kb_save_metabox' );
	add_action( 'admin_notices', 'fluidampr_kb_unmatched_sku_notice' );
	add_filter( 'manage_' . fluidampr_kb_post_type() . '_posts_columns', 'fluidampr_kb_columns' );
	add_action( 'manage_' . fluidampr_kb_post_type() . '_posts_custom_column', 'fluidampr_kb_column_content', 10, 2 );
}
add_action( 'admin_init', 'fluidampr_kb_admin_register' );

/**
 * Add the side metabox on Knowledge Base articles.
 *
 * @return void
 */
function fluidampr_kb_add_metabox() {
	add_meta_box(
		'fluidampr-kb-details',
		__( 'Article details', 'fluidampr' ),
		'fluidampr_kb_render_metabox',
		fluidampr_kb_post_type(),
		'side',
		'high'
	);
}

/**
 * Render featured + related SKU fields.
 *
 * @param WP_Post $post Article.
 * @return void
 */
function fluidampr_kb_render_metabox( $post ) {
	wp_nonce_field( 'fluidampr_kb_details', 'fluidampr_kb_details_nonce' );

	$featured = fluidampr_kb_is_featured( $post->ID );
	$skus     = fluidampr_kb_get_part_numbers( $post->ID );
	$ids      = fluidampr_kb_get_product_ids( $post->ID );

	echo '<p><label><input type="checkbox" name="fluidampr_kb_featured" value="1" ' . checked( $featured, true, false ) . '> ';
	echo esc_html__( 'Featured / common question', 'fluidampr' ) . '</label></p>';
	echo '<p class="description">' . esc_html__( 'Featured articles can appear on Support / FAQ and in the Knowledge Base lookup.', 'fluidampr' ) . '</p>';

	echo '<p><label for="fluidampr_kb_part_numbers"><strong>' . esc_html__( 'Related part numbers', 'fluidampr' ) . '</strong></label></p>';
	echo '<textarea class="widefat" rows="5" id="fluidampr_kb_part_numbers" name="fluidampr_kb_part_numbers" placeholder="920321">';
	echo esc_textarea( implode( "\n", $skus ) );
	echo '</textarea>';
	echo '<p class="description">' . esc_html__( 'One Fluidampr SKU per line. Matching catalog products are linked automatically.', 'fluidampr' ) . '</p>';

	if ( $ids ) {
		echo '<p><strong>' . esc_html__( 'Linked products', 'fluidampr' ) . '</strong></p><ul>';

		foreach ( $ids as $product_id ) {
			$title = get_the_title( $product_id );
			$sku   = fluidampr_kb_sku_from_product( $product_id );
			$label = $title ? $title : '#' . $product_id;

			if ( '' !== $sku ) {
				$label .= ' (' . $sku . ')';
			}

			$edit = get_edit_post_link( $product_id );

			echo '<li>';
			if ( $edit ) {
				echo '<a href="' . esc_url( $edit ) . '">' . esc_html( $label ) . '</a>';
			} else {
				echo esc_html( $label );
			}
			echo '</li>';
		}

		echo '</ul>';
	}
}

/**
 * Save featured flag and related SKUs. Unmatched SKUs are reported once.
 *
 * @param int $post_id Article ID.
 * @return void
 */
function fluidampr_kb_save_metabox( $post_id ) {
	if ( ! isset( $_POST['fluidampr_kb_details_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fluidampr_kb_details_nonce'] ) ), 'fluidampr_kb_details' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$featured = isset( $_POST['fluidampr_kb_featured'] ) ? '1' : '';

	if ( '1' === $featured ) {
		update_post_meta( $post_id, '_fluidampr_kb_featured', '1' );
	} else {
		delete_post_meta( $post_id, '_fluidampr_kb_featured' );
	}

	$raw  = isset( $_POST['fluidampr_kb_part_numbers'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fluidampr_kb_part_numbers'] ) ) : '';
	$skus = preg_split( '/[\s,;]+/', $raw );
	$skus = is_array( $skus ) ? $skus : array();

	$normalized = array();
	$unmatched  = array();
	$product_ids = array();

	foreach ( $skus as $sku ) {
		$sku = fluidampr_kb_normalize_sku( $sku );

		if ( '' === $sku || isset( $normalized[ $sku ] ) ) {
			continue;
		}

		$normalized[ $sku ] = true;
		$product_id         = fluidampr_kb_product_id_from_sku( $sku );

		if ( $product_id > 0 ) {
			$product_ids[ $product_id ] = true;
		} else {
			$unmatched[] = $sku;
		}
	}

	delete_post_meta( $post_id, '_fluidampr_kb_part_number' );
	delete_post_meta( $post_id, '_fluidampr_kb_product_id' );

	foreach ( array_keys( $normalized ) as $sku ) {
		add_post_meta( $post_id, '_fluidampr_kb_part_number', $sku, false );
	}

	foreach ( array_keys( $product_ids ) as $product_id ) {
		add_post_meta( $post_id, '_fluidampr_kb_product_id', (int) $product_id, false );
	}

	if ( '' === get_post_meta( $post_id, 'layout', true ) ) {
		update_post_meta( $post_id, 'layout', 'fullsize' );
		update_post_meta( $post_id, 'sidebar', 'hidden' );
	}

	if ( $unmatched ) {
		set_transient(
			'fluidampr_kb_unmatched_skus_' . get_current_user_id(),
			$unmatched,
			MINUTE_IN_SECONDS * 5
		);
	}
}

/**
 * Admin notice when saved SKUs did not match catalog products.
 *
 * @return void
 */
function fluidampr_kb_unmatched_sku_notice() {
	$key  = 'fluidampr_kb_unmatched_skus_' . get_current_user_id();
	$skus = get_transient( $key );

	if ( ! is_array( $skus ) || ! $skus ) {
		return;
	}

	delete_transient( $key );

	echo '<div class="notice notice-warning is-dismissible"><p>';
	echo esc_html__( 'These part numbers were saved but did not match a catalog product:', 'fluidampr' );
	echo ' ' . esc_html( implode( ', ', $skus ) );
	echo '</p></div>';
}

/**
 * Add Featured and Part numbers columns.
 *
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function fluidampr_kb_columns( $columns ) {
	$out = array();

	foreach ( $columns as $key => $label ) {
		$out[ $key ] = $label;

		if ( 'title' === $key ) {
			$out['fluidampr_kb_featured'] = __( 'Featured', 'fluidampr' );
			$out['fluidampr_kb_skus']     = __( 'Part numbers', 'fluidampr' );
		}
	}

	return $out;
}

/**
 * Render custom list-table columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Article ID.
 * @return void
 */
function fluidampr_kb_column_content( $column, $post_id ) {
	if ( 'fluidampr_kb_featured' === $column ) {
		echo fluidampr_kb_is_featured( $post_id ) ? esc_html__( 'Yes', 'fluidampr' ) : '—';
		return;
	}

	if ( 'fluidampr_kb_skus' === $column ) {
		$skus = fluidampr_kb_get_part_numbers( $post_id );
		echo $skus ? esc_html( implode( ', ', $skus ) ) : '—';
	}
}
