<?php
/**
 * Custom Fluidampr header.
 *
 * Loaded by Enfold via get_template_part( 'includes/helper', 'main-menu' ).
 * Parent header.php is not copied. This file only replaces the menu include.
 *
 * Header Behavior (sticky, shrinking, stretch) comes from Theme Options via
 * avia_header_setting() and Enfold's sticky-header script.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$header_s = function_exists( 'avia_header_setting' ) ? avia_header_setting() : array();
$finder   = fluidampr_page_url( 'finder_page' );
$buy      = fluidampr_page_url( 'buy_page' );

if ( ! empty( $header_s['disabled'] ) ) {
	return;
}

$shrink_factor = function_exists( 'avia_get_option' ) ? avia_get_option( 'header_shrinking_factor' ) : '';
if ( empty( $shrink_factor ) ) {
	$shrink_factor = '50';
}
$shrink_factor = apply_filters( 'avf_header_shrink_factor', $shrink_factor, $header_s );

$header_classes = array( 'all_colors', 'header_color', 'fluid-header' );
if ( ! empty( $header_s['header_class'] ) ) {
	$header_classes[] = trim( (string) $header_s['header_class'] );
}
if ( function_exists( 'avia_is_dark_bg' ) ) {
	$header_classes[] = avia_is_dark_bg( 'header_color', true );
}

$aria_label = function_exists( 'avia_get_option' ) ? avia_get_option( 'header_aria_label', '' ) : '';
if ( '' === (string) $aria_label ) {
	$aria_label = __( 'Site header', 'fluidampr' );
}

$markup = function_exists( 'avia_markup_helper' ) ? avia_markup_helper( array( 'context' => 'header', 'echo' => false ) ) : '';
?>
<header id="header" class="<?php echo esc_attr( implode( ' ', array_filter( $header_classes ) ) ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>" data-av_shrink_factor="<?php echo esc_attr( (string) $shrink_factor ); ?>" <?php echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Enfold schema markup. ?>>
	<div id="header_main" class="container_wrap container_wrap_logo">
		<div class="container av-logo-container fluid-header__inner">
			<div class="inner-container fluid-header__bar">
				<div class="fluid-header__logo">
					<?php echo fluidampr_enfold_logo_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Enfold logo HTML. ?>
				</div>

				<div class="fluid-header__actions">
					<div class="fluid-header__ctas">
						<a class="fluid-button fluid-header__cta" href="<?php echo esc_url( $finder ); ?>"><?php esc_html_e( 'Find your Damper', 'fluidampr' ); ?></a>
						<a class="fluid-button fluid-button--outline fluid-header__cta" href="<?php echo esc_url( $buy ); ?>"><?php esc_html_e( 'Where to Buy', 'fluidampr' ); ?></a>
					</div>

					<div class="fluid-header__tools">
						<button type="button" class="fluid-header__icon" data-fluid-search-open aria-expanded="false" aria-controls="fluid-search">
							<span class="screen-reader-text"><?php esc_html_e( 'Search', 'fluidampr' ); ?></span>
							<?php echo fluidampr_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</button>

						<button type="button" class="fluid-header__icon fluid-header__menu-btn" data-fluid-nav-open aria-expanded="false" aria-controls="fluid-nav">
							<span class="screen-reader-text"><?php esc_html_e( 'Open menu', 'fluidampr' ); ?></span>
							<?php echo fluidampr_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</header>
<?php
get_template_part( 'template-parts/overlay-nav' );
get_template_part( 'template-parts/search-modal' );
