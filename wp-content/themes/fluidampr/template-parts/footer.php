<?php
/**
 * Site footer markup. Rendered by the Enfold footer page via [fluid_site_footer].
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$phone   = fluidampr_get_option( 'phone' );
$email   = fluidampr_get_option( 'email' );
$address = fluidampr_get_option( 'address' );
$year    = gmdate( 'Y' );
?>
<div class="fluid-footer">
	<div class="fluid-footer__grid">
		<div class="fluid-footer__brand">
			<a class="fluid-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img src="<?php echo esc_url( fluidampr_logo_url() ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="270" height="95">
			</a>
			<?php if ( $address ) : ?>
				<p class="fluid-footer__address">
					<a href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( str_replace( array( "\r", "\n" ), ', ', $address ) ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo nl2br( esc_html( $address ) ); ?></a>
				</p>
			<?php endif; ?>
			<ul class="fluid-footer__contact">
				<?php if ( $phone ) : ?>
					<li>
						<?php echo fluidampr_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
					</li>
				<?php endif; ?>
				<?php if ( $email ) : ?>
					<li>
						<?php echo fluidampr_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
					</li>
				<?php endif; ?>
			</ul>
		</div>

		<div class="fluid-footer__col">
			<p class="fluid-footer__heading"><?php esc_html_e( 'Products', 'fluidampr' ); ?></p>
			<?php
			if ( has_nav_menu( 'fluidampr_footer_1' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'fluidampr_footer_1',
						'container'      => false,
						'menu_class'     => 'fluid-footer__links',
						'depth'          => 1,
					)
				);
			} else {
				echo '<ul class="fluid-footer__links">';
				echo '<li><a href="' . esc_url( fluidampr_page_url( 'finder_page' ) ) . '">' . esc_html__( 'Find Your Damper', 'fluidampr' ) . '</a></li>';
				echo '<li><a href="' . esc_url( home_url( '/instructions/' ) ) . '">' . esc_html__( 'Instructions', 'fluidampr' ) . '</a></li>';
				echo '<li><a href="' . esc_url( fluidampr_page_url( 'buy_page' ) ) . '">' . esc_html__( 'Dealers', 'fluidampr' ) . '</a></li>';
				echo '</ul>';
			}
			?>
		</div>

		<div class="fluid-footer__col">
			<p class="fluid-footer__heading"><?php esc_html_e( 'Technology', 'fluidampr' ); ?></p>
			<?php
			if ( has_nav_menu( 'fluidampr_footer_2' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'fluidampr_footer_2',
						'container'      => false,
						'menu_class'     => 'fluid-footer__links',
						'depth'          => 1,
					)
				);
			} else {
				echo '<ul class="fluid-footer__links">';
				echo '<li><a href="' . esc_url( home_url( '/support/' ) ) . '">' . esc_html__( 'Support / FAQ', 'fluidampr' ) . '</a></li>';
				echo '<li><a href="' . esc_url( home_url( '/news/' ) ) . '">' . esc_html__( 'News', 'fluidampr' ) . '</a></li>';
				echo '<li><a href="' . esc_url( home_url( '/contact/' ) ) . '">' . esc_html__( 'Contact', 'fluidampr' ) . '</a></li>';
				echo '</ul>';
			}
			?>
		</div>

		<div class="fluid-footer__newsletter">
			<p class="fluid-footer__heading fluid-footer__heading--accent"><?php esc_html_e( 'Newsletter', 'fluidampr' ); ?></p>
			<p class="fluid-footer__note"><?php echo esc_html( fluidampr_get_option( 'newsletter_note' ) ); ?></p>
			<form class="fluid-footer__form" data-fluid-newsletter method="post" action="<?php echo esc_url( rest_url( 'fluidampr/v1/newsletter' ) ); ?>" novalidate>
				<label class="screen-reader-text" for="fluid-newsletter-email"><?php esc_html_e( 'Email address', 'fluidampr' ); ?></label>
				<input class="fluid-hp" type="text" name="company" value="" tabindex="-1" autocomplete="off" aria-hidden="true">
				<input id="fluid-newsletter-email" type="email" name="email" required placeholder="<?php esc_attr_e( 'Email address', 'fluidampr' ); ?>" autocomplete="email">
				<button type="submit" class="fluid-button"><?php esc_html_e( 'Sign Up', 'fluidampr' ); ?></button>
				<p class="fluid-footer__form-status" role="status" hidden></p>
			</form>
			<div class="fluid-footer__usa">
				<img src="<?php echo esc_url( FLUIDAMPR_THEME_URI . '/assets/images/made-in-usa.png' ); ?>" alt="<?php esc_attr_e( 'Made in the USA', 'fluidampr' ); ?>" width="60" height="31" loading="lazy" decoding="async">
				<span><?php esc_html_e( 'Made in the USA', 'fluidampr' ); ?></span>
			</div>
		</div>
	</div>

	<div class="fluid-footer__socket">
		<p class="fluid-footer__copy"><?php echo esc_html( sprintf( /* translators: %s: year */ __( '© %s Fluidampr. All rights reserved.', 'fluidampr' ), $year ) ); ?></p>
		<nav class="fluid-footer__legal" aria-label="<?php esc_attr_e( 'Legal', 'fluidampr' ); ?>">
			<?php
			if ( has_nav_menu( 'fluidampr_legal' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'fluidampr_legal',
						'container'      => false,
						'menu_class'     => 'fluid-footer__legal-list',
						'depth'          => 1,
					)
				);
			} else {
				echo '<ul class="fluid-footer__legal-list">';
				echo '<li><a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '">' . esc_html__( 'Privacy policy', 'fluidampr' ) . '</a></li>';
				echo '<li><a href="' . esc_url( home_url( '/terms/' ) ) . '">' . esc_html__( 'Terms of service', 'fluidampr' ) . '</a></li>';
				echo '</ul>';
			}
			?>
		</nav>
		<ul class="fluid-footer__social">
			<?php foreach ( array( 'facebook', 'instagram', 'linkedin', 'youtube' ) as $network ) : ?>
				<?php $url = fluidampr_get_option( $network ); ?>
				<?php if ( $url ) : ?>
					<li>
						<a href="<?php echo esc_url( $url ); ?>" rel="noopener noreferrer" target="_blank">
							<span class="screen-reader-text"><?php echo esc_html( ucfirst( $network ) ); ?></span>
							<?php echo fluidampr_icon( $network ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					</li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
	</div>
</div>
