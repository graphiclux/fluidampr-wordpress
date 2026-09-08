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
		'phone'           => __( 'Phone', 'fluidampr' ),
		'email'           => __( 'Email', 'fluidampr' ),
		'address'         => __( 'Address', 'fluidampr' ),
		'facebook'        => __( 'Facebook URL', 'fluidampr' ),
		'instagram'       => __( 'Instagram URL', 'fluidampr' ),
		'youtube'         => __( 'YouTube URL', 'fluidampr' ),
		'linkedin'        => __( 'LinkedIn URL', 'fluidampr' ),
		'newsletter_note' => __( 'Newsletter description', 'fluidampr' ),
	);

	foreach ( $fields as $key => $label ) {
		$wp_customize->add_setting(
			'fluidampr_' . $key,
			array(
				'default'           => fluidampr_default_options()[ $key ] ?? '',
				'sanitize_callback' => 'address' === $key || 'newsletter_note' === $key ? 'sanitize_textarea_field' : 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$type = ( 'address' === $key || 'newsletter_note' === $key ) ? 'textarea' : 'text';

		$wp_customize->add_control(
			'fluidampr_' . $key,
			array(
				'label'   => $label,
				'section' => 'fluidampr_brand',
				'type'    => $type,
			)
		);
	}
}
add_action( 'customize_register', 'fluidampr_customize_register' );
