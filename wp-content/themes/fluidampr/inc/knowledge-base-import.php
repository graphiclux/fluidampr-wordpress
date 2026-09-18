<?php
/**
 * Idempotent, draft-only Knowledge Base importer.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function fluidampr_kb_import_records() {
	$file = ABSPATH . 'kb-import/initial-batch.json';
	if ( ! file_exists( $file ) ) {
		return new WP_Error( 'missing_batch', 'KB import batch JSON is missing.' );
	}
	$data = json_decode( file_get_contents( $file ), true );
	return is_array( $data ) ? $data : new WP_Error( 'invalid_batch', 'KB import batch JSON is invalid.' );
}

function fluidampr_kb_import_find_by_key( $key ) {
	$posts = get_posts( array(
		'post_type'      => fluidampr_kb_post_type(),
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_fluidampr_kb_import_key',
		'meta_value'     => $key,
	) );
	return $posts ? (int) $posts[0] : 0;
}

function fluidampr_kb_import_apply_meta( $post_id, $record ) {
	$source = (array) ( $record['source'] ?? array() );
	$parts  = array_values( array_unique( array_filter( array_map( 'fluidampr_kb_normalize_sku', (array) ( $record['part_numbers'] ?? array() ) ) ) ) );
	if ( in_array( '300010', $parts, true ) && empty( $record['review_status'] ) ) {
		$record['review_status'] = 'Technical Review Required';
		$record['notes'] = 'Instruction revision conflict for 300010. Preserve current live attachment until reviewed.';
	}
	$hash   = (string) ( $source['hash'] ?? '' );
	if ( '' === $hash ) {
		$hash = hash( 'sha256', wp_json_encode( $source ) . '|' . (string) ( $record['title'] ?? '' ) );
	}
	$meta = array(
		'_fluidampr_kb_import_key'  => (string) $record['key'],
		'_fluidampr_kb_source_type' => (string) ( $source['type'] ?? '' ),
		'_fluidampr_kb_source_filename' => (string) ( $source['filename'] ?? '' ),
		'_fluidampr_kb_source_section' => (string) ( $source['section'] ?? '' ),
		'_fluidampr_kb_source_revision' => (string) ( $source['revision'] ?? '' ),
		'_fluidampr_kb_source_hash' => $hash,
		'_fluidampr_kb_applicable_parts' => implode( ', ', $parts ),
		'_fluidampr_kb_import_date' => gmdate( 'Y-m-d' ),
		'_fluidampr_kb_review_status' => (string) ( $record['review_status'] ?? 'Draft / Unreviewed' ),
		'_fluidampr_kb_internal_notes' => (string) ( $record['notes'] ?? '' ),
	);
	foreach ( $meta as $key => $value ) {
		update_post_meta( $post_id, $key, $value );
	}
	delete_post_meta( $post_id, '_fluidampr_kb_part_number' );
	delete_post_meta( $post_id, '_fluidampr_kb_product_id' );
	delete_post_meta( $post_id, '_fluidampr_kb_instruction_attachment_id' );
	foreach ( $parts as $sku ) {
		add_post_meta( $post_id, '_fluidampr_kb_part_number', $sku, false );
		$product_id = fluidampr_kb_product_id_from_sku( $sku );
		if ( $product_id ) {
			add_post_meta( $post_id, '_fluidampr_kb_product_id', $product_id, false );
			$attachment_id = (int) get_post_meta( $product_id, '_fluidampr_instruction_attachment_id', true );
			if ( $attachment_id ) {
				add_post_meta( $post_id, '_fluidampr_kb_instruction_attachment_id', $attachment_id, false );
			}
		}
	}
}

function fluidampr_kb_stage_existing_reviews() {
	$staged = array(
		5667 => array(
			'key' => 'review-stage-power-adder',
			'append' => '<h2>Review note</h2><p>This draft is staged for editorial review against the supplied supercharger FAQ. Confirm the exact application and retain the existing product relationship until a broader source-supported relationship is approved.</p>',
		),
		5668 => array(
			'key' => 'review-stage-instructions-lookup',
			'append' => '<h2>Review note</h2><p>This draft is staged for editorial review against the supplied instruction package. Confirm revision conflicts before changing any live instruction attachment.</p>',
		),
	);
	$ids = array();
	foreach ( $staged as $source_id => $data ) {
		$existing = fluidampr_kb_import_find_by_key( $data['key'] );
		$source = get_post( $source_id );
		if ( ! $source ) { continue; }
		$args = array(
			'post_type' => fluidampr_kb_post_type(),
			'post_status' => 'draft',
			'post_title' => '[Review draft] ' . $source->post_title,
			'post_content' => $source->post_content . $data['append'],
			'post_excerpt' => $source->post_excerpt,
		);
		if ( $existing ) { $args['ID'] = $existing; $id = wp_update_post( $args, true ); }
		else { $id = wp_insert_post( $args, true ); }
		if ( is_wp_error( $id ) ) { continue; }
		wp_set_object_terms( $id, wp_get_object_terms( $source_id, fluidampr_kb_taxonomy(), array( 'fields' => 'slugs' ) ), fluidampr_kb_taxonomy(), false );
		$parts = fluidampr_kb_get_part_numbers( $source_id );
		fluidampr_kb_import_apply_meta( $id, array(
			'key' => $data['key'], 'title' => $args['post_title'], 'part_numbers' => $parts,
			'review_status' => 'Technical Review Required',
			'notes' => 'Staged copy of published article #' . $source_id . '. Review before publication.',
			'source' => array( 'type' => 'Existing KB article + client source', 'filename' => 'Published article #' . $source_id, 'section' => $source->post_title ),
		) );
		$ids[ $source_id ] = (int) $id;
	}
	return $ids;
}

function fluidampr_kb_import_one( $record ) {
	$key = sanitize_key( (string) ( $record['key'] ?? '' ) );
	if ( '' === $key || empty( $record['title'] ) ) {
		return 0;
	}
	$post_id = fluidampr_kb_import_find_by_key( $key );
	$args = array(
		'post_type'    => fluidampr_kb_post_type(),
		'post_status'  => 'draft',
		'post_title'   => (string) $record['title'],
		'post_name'    => sanitize_title( (string) $record['slug'] ),
		'post_content' => (string) $record['content'],
		'post_excerpt' => (string) ( $record['excerpt'] ?? '' ),
	);
	if ( $post_id ) {
		$args['ID'] = $post_id;
		wp_update_post( $args );
	} else {
		$post_id = wp_insert_post( $args, true );
		if ( is_wp_error( $post_id ) ) {
			return 0;
		}
	}
	wp_set_object_terms( $post_id, (array) ( $record['topics'] ?? array() ), fluidampr_kb_taxonomy(), false );
	fluidampr_kb_import_apply_meta( $post_id, $record );
	return (int) $post_id;
}

function fluidampr_kb_import_batch( $echo = true ) {
	$records = fluidampr_kb_import_records();
	if ( is_wp_error( $records ) ) {
		if ( $echo ) { WP_CLI::error( $records->get_error_message() ); }
		return $records;
	}
	$ids = array();
	foreach ( $records as $record ) {
		$id = fluidampr_kb_import_one( $record );
		if ( $id ) { $ids[ (string) $record['key'] ] = $id; }
	}
	$staged = fluidampr_kb_stage_existing_reviews();
	fluidampr_kb_apply_qa_cleanup();
	update_option( 'fluidampr_kb_import_report', array(
		'updated_at' => current_time( 'mysql' ),
		'conflicts' => array( '300010: 4NFZ02 vs 4NFZ01', '300002: 4NFG11 vs 4NFG10', '300008: 4NFX02 vs 4NFX01' ),
		'unmatched_parts' => array( '600701', '743301', '651201', '763301', '843331', '80251E', '80252E', '80253E', '80254E', '450809' ),
		'support_page_overlaps' => array( 'Will a Fluidampr damper work with a power adder?', 'Can I paint or coat the damper?', 'Where do I find install instructions?' ),
		'staged_existing_reviews' => $staged,
		'classification_note' => 'The completed QA headline reported 10 Ready drafts, but its article-level table identifies 9 Ready, 13 Technical Review, and 3 Blocked. No tenth article was inferred; the report preserves this discrepancy for client confirmation.',
	), false );
	if ( $echo ) { WP_CLI\Utils\format_items( 'table', array_map( static function ( $key, $id ) { return array( 'key' => $key, 'id' => $id ); }, array_keys( $ids ), $ids ), array( 'key', 'id' ) ); }
	return $ids;
}

function fluidampr_kb_apply_qa_cleanup() {
	$groups = array(
		'ready' => array( 'faq-torsional-vibration', 'faq-balance-correction', 'faq-internal-external-balance', 'faq-match-balance', 'faq-press-fit', 'faq-temperature-warmup', 'faq-stock-replacement', 'faq-timing-marks', 'selection-finder' ),
		'technical' => array( 'faq-early-sbc-bolt', 'faq-stored-damper', 'faq-coating', 'faq-silicone-life', 'faq-street-to-fluidampr', 'faq-new-application', 'policy-warranty', 'policy-warranty-claim', 'policy-returns', 'policy-international', 'selection-ls-offset', 'review-stage-power-adder', 'review-stage-instructions-lookup' ),
		'blocked' => array( 'install-diesel-bolts', 'install-cummins-kits', 'install-duramax-retention' ),
	);
	foreach ( $groups as $group => $keys ) {
		foreach ( $keys as $key ) {
			$id = fluidampr_kb_import_find_by_key( $key );
			if ( $id ) { update_post_meta( $id, '_fluidampr_kb_review_group', $group ); }
		}
	}
	$source_updates = array(
		'review-stage-power-adder' => 'Current Website FAQs_09142026.docx; Published article #5667',
		'review-stage-instructions-lookup' => '4NFE19_Performance-Diesel-Install_-3-25.pdf; Published article #5668',
	);
	foreach ( $source_updates as $key => $filename ) {
		$id = fluidampr_kb_import_find_by_key( $key );
		if ( $id ) { update_post_meta( $id, '_fluidampr_kb_source_filename', $filename ); }
	}
	$revisions = array(
		'install-diesel-bolts' => '4NFE19 3-25; 4NFZ02 5-22; 4NFAG01 9-21; 4NFAH01 9-21; 4NFAJ01 9-21',
		'install-cummins-kits' => '4NFG11 8-20; 4NFX02 8-20',
		'install-duramax-retention' => '4NFZ02 5-22; 4NFE19 3-25',
	);
	foreach ( $revisions as $key => $revision ) {
		$id = fluidampr_kb_import_find_by_key( $key );
		if ( $id ) { update_post_meta( $id, '_fluidampr_kb_source_revision', $revision ); }
	}
	$term_cleanup = array(
		'faq-balance-correction' => array( 'harmonic-damper-basics' ),
		'faq-coating' => array( 'installation' ),
	);
	foreach ( $term_cleanup as $key => $terms ) {
		$id = fluidampr_kb_import_find_by_key( $key );
		if ( $id ) { wp_set_object_terms( $id, $terms, fluidampr_kb_taxonomy(), false ); }
	}
	$review_notes = array(
		'faq-early-sbc-bolt' => 'Confirm the source-specific 70 ft-lb guidance and machining warning.',
		'faq-stored-damper' => 'Confirm storage wording and escalation for questionable physical damage.',
		'faq-coating' => 'Confirm the 280°F coating-temperature guidance.',
		'faq-silicone-life' => 'Confirm service-life wording remains distinct from warranty coverage.',
		'faq-street-to-fluidampr' => 'Confirm SFI-related wording for race-tech requirements.',
		'faq-new-application' => 'Confirm the process and contact path for unlisted/custom applications.',
		'policy-warranty' => 'Confirm policy wording and final formal policy-page destination.',
		'policy-warranty-claim' => 'Confirm claim routing, RMA requirements, and final formal policy-page destination.',
		'policy-returns' => 'Confirm return-window, packaging, freight, and RMA language against the final policy.',
		'policy-international' => 'Confirm international-payment, carrier, customs, and final policy-page language.',
		'selection-ls-offset' => 'Confirm LS offset-group wording and qualified truck/application language.',
		'review-stage-power-adder' => 'Approve staged enrichment of the existing power-adder article.',
		'review-stage-instructions-lookup' => 'Approve staged enrichment of the existing instructions article.',
	);
	foreach ( $review_notes as $key => $note ) {
		$id = fluidampr_kb_import_find_by_key( $key );
		if ( $id ) { update_post_meta( $id, '_fluidampr_kb_internal_notes', $note ); }
	}
}

function fluidampr_kb_import_report_notice() {
	if ( ! is_admin() || empty( $_GET['post_type'] ) || fluidampr_kb_post_type() !== sanitize_key( $_GET['post_type'] ) ) { return; }
	$report = get_option( 'fluidampr_kb_import_report', array() );
	if ( empty( $report['conflicts'] ) ) { return; }
	echo '<div class="notice notice-warning"><p><strong>KB source review holds:</strong> ' . esc_html( implode( '; ', (array) $report['conflicts'] ) ) . '. Unmatched parts: ' . esc_html( implode( ', ', (array) $report['unmatched_parts'] ) ) . '.</p></div>';
}
add_action( 'admin_notices', 'fluidampr_kb_import_report_notice' );

function fluidampr_kb_review_report_menu() {
	add_submenu_page( 'edit.php?post_type=' . fluidampr_kb_post_type(), __( 'Client Review Report', 'fluidampr' ), __( 'Client Review Report', 'fluidampr' ), 'edit_posts', 'fluidampr-kb-review-report', 'fluidampr_kb_render_review_report' );
}
add_action( 'admin_menu', 'fluidampr_kb_review_report_menu' );

function fluidampr_kb_render_review_report() {
	if ( ! current_user_can( 'edit_posts' ) ) { return; }
	$groups = array(
		'ready' => 'Ready for Review',
		'technical' => 'Needs Technical Review',
		'blocked' => 'Blocked / Awaiting Source Confirmation',
	);
	echo '<div class="wrap"><h1>Fluidampr Knowledge Base Client Review</h1>';
	$report = get_option( 'fluidampr_kb_import_report', array() );
	if ( ! empty( $report['classification_note'] ) ) { echo '<div class="notice notice-warning"><p>' . esc_html( $report['classification_note'] ) . '</p></div>'; }
	foreach ( $groups as $group => $heading ) {
		$posts = get_posts( array( 'post_type' => fluidampr_kb_post_type(), 'post_status' => 'draft', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'meta_key' => '_fluidampr_kb_review_group', 'meta_value' => $group ) );
		echo '<h2>' . esc_html( $heading ) . ' (' . count( $posts ) . ')</h2><table class="widefat striped"><thead><tr><th>ID</th><th>Article</th><th>Category</th><th>Parts</th><th>Source</th><th>Review note</th><th>Edit</th></tr></thead><tbody>';
		foreach ( $posts as $post ) {
			$terms = wp_get_object_terms( $post->ID, fluidampr_kb_taxonomy(), array( 'fields' => 'names' ) );
			$parts = fluidampr_kb_get_part_numbers( $post->ID );
			$note = get_post_meta( $post->ID, '_fluidampr_kb_internal_notes', true );
			$source = get_post_meta( $post->ID, '_fluidampr_kb_source_filename', true );
			echo '<tr><td>' . (int) $post->ID . '</td><td>' . esc_html( $post->post_title ) . '</td><td>' . esc_html( implode( '; ', $terms ) ) . '</td><td>' . esc_html( implode( ', ', $parts ) ) . '</td><td>' . esc_html( $source ) . '</td><td>' . esc_html( $note ) . '</td><td><a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">Edit</a></td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '<h2>Policy follow-up</h2><p>The warranty, returns, and international-order drafts require the approved formal policy page. The current <code>/terms/</code> page remains placeholder content.</p>';
	echo '<h2>Support-page overlap</h2><p>After approval, replace or link the hard-coded Support questions for the power-adder, coating, and installation-instructions topics.</p></div>';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'fluidampr kb-import', 'fluidampr_kb_import_batch' );
}
