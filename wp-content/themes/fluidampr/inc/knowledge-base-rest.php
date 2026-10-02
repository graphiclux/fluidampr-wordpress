<?php
/**
 * Read-only Knowledge Base search REST API.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register GET /fluidampr/v1/knowledge-base.
 *
 * @return void
 */
function fluidampr_kb_register_rest() {
	$search_arg = array(
		'required'          => false,
		'sanitize_callback' => 'sanitize_text_field',
	);

	register_rest_route(
		'fluidampr/v1',
		'/knowledge-base',
		array(
			'methods'             => 'GET',
			'callback'            => 'fluidampr_kb_rest_search',
			'permission_callback' => '__return_true',
			'args'                => array(
				'q'           => $search_arg,
				'sku'         => $search_arg,
				'part_number' => $search_arg,
				'pn'          => $search_arg,
				'category'    => $search_arg,
				'featured'    => array(
					'required'          => false,
					'sanitize_callback' => static function ( $value ) {
						return rest_sanitize_boolean( $value ) ? '1' : '';
					},
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'fluidampr_kb_register_rest' );

/**
 * GET /knowledge-base — search by keyword, topic, and/or part number.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response
 */
function fluidampr_kb_rest_search( WP_REST_Request $request ) {
	$query    = trim( (string) $request->get_param( 'q' ) );
	$sku      = '';
	$category = sanitize_title( (string) $request->get_param( 'category' ) );
	$featured = (bool) $request->get_param( 'featured' );

	foreach ( array( 'sku', 'part_number', 'pn' ) as $key ) {
		$value = fluidampr_kb_normalize_sku( (string) $request->get_param( $key ) );

		if ( '' !== $value ) {
			$sku = $value;
			break;
		}
	}

	$results = fluidampr_kb_search(
		array(
			's'              => $query,
			'sku'            => $sku,
			'category'       => $category,
			'featured'       => $featured,
			'posts_per_page' => 20,
		)
	);

	$message = $results ? '' : __( 'No matching articles were found.', 'fluidampr' );

	return new WP_REST_Response(
		array(
			'success' => true,
			'message' => $message,
			'data'    => array(
				'query'    => $query,
				'sku'      => $sku,
				'category' => $category,
				'featured' => $featured,
				'results'  => $results,
			),
		),
		200
	);
}
