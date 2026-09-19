<?php
/**
 * Static pages (experiência, privacidade, etc.).
 *
 * @package Casa_Kotti
 */

get_header();
?>
<div class="ck-page">
	<?php get_template_part( 'template-parts/site-header' ); ?>
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
