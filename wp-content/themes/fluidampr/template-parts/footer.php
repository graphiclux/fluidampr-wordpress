<?php
/**
 * Site footer matching the Figma layout.
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
<footer class="fluid-footer" id="fluid-site-footer">
	<div class="fluid-footer__grid">
		<div class="fluid-footer__brand">
			<a class="fluid-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img src="<?php echo esc_url( fluidampr_logo_url() ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="180" height="50">
			</a>
			<?php if ( $address ) : ?>
				<p class="fluid-footer__address"><?php echo nl2br( esc_html( $address ) ); ?></p>
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
			<h2 class="fluid-footer__heading"><?php esc_html_e( 'Products', 'fluidampr' ); ?></h2>
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
			<h2 class="fluid-footer__heading"><?php esc_html_e( 'Technology', 'fluidampr' ); ?></h2>
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
			<h2 class="fluid-footer__heading"><?php esc_html_e( 'Newsletter', 'fluidampr' ); ?></h2>
			<p><?php echo esc_html( fluidampr_get_option( 'newsletter_note' ) ); ?></p>
			<form class="fluid-footer__form" method="post" action="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
				<label class="screen-reader-text" for="fluid-newsletter-email"><?php esc_html_e( 'Email address', 'fluidampr' ); ?></label>
				<input id="fluid-newsletter-email" type="email" name="newsletter_email" required placeholder="<?php esc_attr_e( 'Email address', 'fluidampr' ); ?>" autocomplete="email">
				<button type="submit" class="fluid-button"><?php esc_html_e( 'Sign Up', 'fluidampr' ); ?></button>
			</form>
			<div class="fluid-footer__usa">
				<img src="<?php echo esc_url( FLUIDAMPR_THEME_URI . '/assets/images/made-in-usa.png' ); ?>" alt="<?php esc_attr_e( 'Made in the USA', 'fluidampr' ); ?>" width="60" height="45" loading="lazy" decoding="async">
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
</footer>
