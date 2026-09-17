<?php
/**
 * Static pages (experiência, privacidade, etc.).
 *
 * @package Casa_Kotti
 */

get_header();

$foliage_left  = get_theme_file_path( 'assets/images/foliage-left.svg' );
$foliage_right = get_theme_file_path( 'assets/images/foliage-right.svg' );
$motion_class  = casa_kotti_setting( 'animations_enabled', true ) ? ' has-motion' : '';
?>
<div class="ck-page<?php echo esc_attr( $motion_class ); ?>">
	<div class="ck-foliage-layer" aria-hidden="true">
		<?php if ( file_exists( $foliage_left ) ) : ?>
			<div class="ck-foliage ck-foliage--left">
				<div class="ck-foliage__motion">
					<?php echo file_get_contents( $foliage_left ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized, trusted theme asset. ?>
				</div>
			</div>
		<?php endif; ?>
		<?php if ( file_exists( $foliage_right ) ) : ?>
			<div class="ck-foliage ck-foliage--right">
				<div class="ck-foliage__motion">
					<?php echo file_get_contents( $foliage_right ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized, trusted theme asset. ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
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
