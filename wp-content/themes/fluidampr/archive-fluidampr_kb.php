<?php
/**
 * Knowledge Base archive and topic listing.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

global $avia_config;

get_header();

$title = post_type_archive_title( '', false );

if ( is_tax( fluidampr_kb_taxonomy() ) ) {
	$title = single_term_title( '', false );
}

echo avia_title( array( 'title' => $title ) );

do_action( 'ava_after_main_title' );

$main_class = apply_filters( 'avf_custom_main_classes', 'av-main-archive-fluidampr-kb', 'archive-fluidampr_kb.php' );
$topics     = get_terms(
	array(
		'taxonomy'   => fluidampr_kb_taxonomy(),
		'hide_empty' => true,
	)
);
?>
		<div class="container_wrap container_wrap_first main_color <?php avia_layout_class( 'main' ); ?>">
			<div class="container">
				<main class="template-page content <?php avia_layout_class( 'content' ); ?> units <?php echo esc_attr( $main_class ); ?>" <?php avia_markup_helper( array( 'context' => 'content', 'post_type' => fluidampr_kb_post_type() ) ); ?>>
					<div class="fluid-kb fluid-kb--archive">
						<?php if ( ! is_wp_error( $topics ) && $topics ) : ?>
							<nav class="fluid-kb__topics" aria-label="<?php esc_attr_e( 'Knowledge Base topics', 'fluidampr' ); ?>">
								<a href="<?php echo esc_url( get_post_type_archive_link( fluidampr_kb_post_type() ) ); ?>"<?php echo is_post_type_archive( fluidampr_kb_post_type() ) ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'All topics', 'fluidampr' ); ?></a>
								<?php foreach ( $topics as $topic ) : ?>
									<a href="<?php echo esc_url( get_term_link( $topic ) ); ?>"<?php echo is_tax( fluidampr_kb_taxonomy(), $topic->slug ) ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $topic->name ); ?></a>
								<?php endforeach; ?>
							</nav>
						<?php endif; ?>

						<?php if ( is_tax() && term_description() ) : ?>
							<div class="fluid-kb__intro"><?php echo wp_kses_post( term_description() ); ?></div>
						<?php endif; ?>

						<?php
						if ( have_posts() ) :
							$articles = array();

							while ( have_posts() ) :
								the_post();
								$articles[] = fluidampr_kb_format_article( get_post() );
							endwhile;

							echo fluidampr_kb_articles_list_html( $articles ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

							if ( function_exists( 'avia_pagination' ) ) {
								echo avia_pagination( '', 'nav' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							} else {
								the_posts_pagination();
							}
							?>
						<?php else : ?>
							<p><?php esc_html_e( 'No articles in this topic yet.', 'fluidampr' ); ?></p>
						<?php endif; ?>
					</div>
				</main>
				<?php
				$avia_config['currently_viewing'] = 'page';
				get_sidebar();
				?>
			</div>
		</div>
<?php
get_footer();
