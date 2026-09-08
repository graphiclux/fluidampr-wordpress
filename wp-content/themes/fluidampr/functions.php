<?php
/**
 * Fluidampr Enfold child theme bootstrap.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FLUIDAMPR_THEME_VERSION', '1.0.0' );
define( 'FLUIDAMPR_THEME_PATH', get_stylesheet_directory() );
define( 'FLUIDAMPR_THEME_URI', get_stylesheet_directory_uri() );

$fluidampr_modules = array(
	'inc/setup.php',
	'inc/assets.php',
	'inc/performance.php',
	'inc/enfold-cleanup.php',
	'inc/theme-options.php',
	'inc/instagram.php',
	'inc/constant-contact.php',
	'inc/header-footer.php',
	'inc/shortcodes.php',
	'inc/alb-content.php',
	'inc/layout-seed.php',
);

foreach ( $fluidampr_modules as $fluidampr_module ) {
	$fluidampr_module_path = FLUIDAMPR_THEME_PATH . '/' . $fluidampr_module;

	if ( file_exists( $fluidampr_module_path ) ) {
		require_once $fluidampr_module_path;
	}
}
