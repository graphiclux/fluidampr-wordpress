<?php
/**
 * Lightweight Customizer fields for contact, social, and newsletter copy.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Fluidampr Customizer settings.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 * @return void
 */
function fluidampr_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'fluidampr_brand',
		array(
			'title'    => __( 'Fluidampr content', 'fluidampr' ),
			'priority' => 35,
		)
	);

	$fields = array(
		'phone'                    => __( 'Phone', 'fluidampr' ),
		'email'                    => __( 'Email', 'fluidampr' ),
		'address'                  => __( 'Address', 'fluidampr' ),
		'facebook'                 => __( 'Facebook URL', 'fluidampr' ),
		'instagram'                => __( 'Instagram URL', 'fluidampr' ),
		'youtube'                  => __( 'YouTube URL', 'fluidampr' ),
		'linkedin'                 => __( 'LinkedIn URL', 'fluidampr' ),
		'newsletter_note'        => __( 'Newsletter description', 'fluidampr' ),
		'instagram_access_token' => __( 'Instagram access token', 'fluidampr' ),
	);

	foreach ( $fields as $key => $label ) {
		$wp_customize->add_setting(
			'fluidampr_' . $key,
			array(
				'default'           => fluidampr_default_options()[ $key ] ?? '',
				'sanitize_callback' => in_array( $key, array( 'address', 'newsletter_note' ), true ) ? 'sanitize_textarea_field' : 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$type = 'text';

		if ( in_array( $key, array( 'address', 'newsletter_note' ), true ) ) {
			$type = 'textarea';
		} elseif ( 'instagram_access_token' === $key ) {
			$type = 'password';
		}

		$control = array(
			'label'   => $label,
			'section' => 'fluidampr_brand',
			'type'    => $type,
		);

		if ( 'instagram_access_token' === $key ) {
			$control['description'] = __( 'Long-lived token from Instagram API with Instagram Login (Business or Creator account). The homepage caches the latest 3 posts. Leave blank to keep the curated fallback tiles.', 'fluidampr' );
		}

		$wp_customize->add_control( 'fluidampr_' . $key, $control );
	}
}
add_action( 'customize_register', 'fluidampr_customize_register' );
