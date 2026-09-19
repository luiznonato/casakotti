<?php
/**
 * Fallback template.
 *
 * @package Casa_Kotti
 */

get_header();

$motion_class = casa_kotti_setting( 'animations_enabled', true ) ? ' has-motion' : '';
?>
<div class="ck-page<?php echo esc_attr( $motion_class ); ?>">
	<?php get_template_part( 'template-parts/foliage' ); ?>
	<main class="ck-content" id="conteudo">
		<?php
		while ( have_posts() ) {
			the_post();
			the_title( '<h1>', '</h1>' );
			the_content();
		}
		?>
	</main>
</div>
<?php
get_footer();
