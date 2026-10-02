<?php
/**
 * Single Knowledge Base article.
 *
 * @package Fluidampr
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

global $avia_config;

get_header();

if ( get_post_meta( get_the_ID(), 'header', true ) !== 'no' ) {
	echo avia_title();
}

do_action( 'ava_after_main_title' );

$main_class = apply_filters( 'avf_custom_main_classes', 'av-main-single-fluidampr-kb', 'single-fluidampr_kb.php' );
?>
		<div class="container_wrap container_wrap_first main_color <?php avia_layout_class( 'main' ); ?>">
			<div class="container">
				<main class="template-page content <?php avia_layout_class( 'content' ); ?> units <?php echo esc_attr( $main_class ); ?>" <?php avia_markup_helper( array( 'context' => 'content', 'post_type' => fluidampr_kb_post_type() ) ); ?>>
					<?php
					$avia_config['size'] = avia_layout_class( 'main', false ) === 'fullsize' ? 'entry_without_sidebar' : 'entry_with_sidebar';
					get_template_part( 'includes/loop', 'page' );
					?>
				</main>
				<?php
				$avia_config['currently_viewing'] = 'page';
				get_sidebar();
				?>
			</div>
		</div>
<?php
get_footer();
