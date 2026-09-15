<?php
/**
 * Fallback template.
 *
 * @package Casa_Kotti
 */

get_header();
?>
<main class="ck-content">
	<?php
	while ( have_posts() ) {
		the_post();
		the_title( '<h1>', '</h1>' );
		the_content();
	}
	?>
</main>
<?php
get_footer();
