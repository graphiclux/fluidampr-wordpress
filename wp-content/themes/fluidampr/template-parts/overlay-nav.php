<?php
/**
 * Overlay navigation.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="fluid-nav" class="fluid-nav" hidden>
	<div class="fluid-nav__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Site menu', 'fluidampr' ); ?>">
		<div class="fluid-nav__top">
			<div class="fluid-header__logo">
				<?php echo fluidampr_enfold_logo_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Enfold logo HTML. ?>
			</div>
			<button type="button" class="fluid-header__icon" data-fluid-nav-close>
				<span class="screen-reader-text"><?php esc_html_e( 'Close menu', 'fluidampr' ); ?></span>
				<?php echo fluidampr_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>
		<nav class="fluid-nav__menu">
			<?php
			if ( has_nav_menu( 'fluidampr_primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'fluidampr_primary',
						'container'      => false,
						'menu_class'     => 'fluid-nav__list',
						'depth'          => 2,
					)
				);
			} else {
				echo '<ul class="fluid-nav__list">';
				echo '<li><a href="' . esc_url( fluidampr_page_url( 'finder_page' ) ) . '">' . esc_html__( 'Find your Damper', 'fluidampr' ) . '</a></li>';
				echo '<li><a href="' . esc_url( home_url( '/products/' ) ) . '">' . esc_html__( 'Products', 'fluidampr' ) . '</a></li>';
				echo '<li><a href="' . esc_url( fluidampr_page_url( 'buy_page' ) ) . '">' . esc_html__( 'Where to Buy', 'fluidampr' ) . '</a></li>';
				echo '<li><a href="' . esc_url( home_url( '/knowledge-center/' ) ) . '">' . esc_html__( 'Knowledge Center', 'fluidampr' ) . '</a></li>';
				echo '<li><a href="' . esc_url( home_url( '/technology/' ) ) . '">' . esc_html__( 'Technology', 'fluidampr' ) . '</a></li>';
				echo '<li><a href="' . esc_url( home_url( '/community/' ) ) . '">' . esc_html__( 'Community', 'fluidampr' ) . '</a></li>';
				echo '<li><a href="' . esc_url( home_url( '/contact/' ) ) . '">' . esc_html__( 'Contact', 'fluidampr' ) . '</a></li>';
				echo '</ul>';
			}
			?>
		</nav>
	</div>
</div>
