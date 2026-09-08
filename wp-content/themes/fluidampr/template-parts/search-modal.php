<?php
/**
 * Header search overlay.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="fluid-search" class="fluid-search" hidden>
	<div class="fluid-search__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'fluidampr' ); ?>">
		<form class="fluid-search__form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search">
			<label class="screen-reader-text" for="fluid-search-field"><?php esc_html_e( 'Search', 'fluidampr' ); ?></label>
			<input id="fluid-search-field" type="search" name="s" placeholder="<?php esc_attr_e( 'Search Fluidampr…', 'fluidampr' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>">
			<button type="submit" class="fluid-button"><?php esc_html_e( 'Search', 'fluidampr' ); ?></button>
			<button type="button" class="fluid-header__icon" data-fluid-search-close>
				<span class="screen-reader-text"><?php esc_html_e( 'Close search', 'fluidampr' ); ?></span>
				<?php echo fluidampr_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</form>
	</div>
</div>
