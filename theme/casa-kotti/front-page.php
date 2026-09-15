<?php
/**
 * Casa Kotti pre-launch composition.
 *
 * @package Casa_Kotti
 */

get_header();

$instagram_url = casa_kotti_setting( 'instagram_url' );
$contact_email = casa_kotti_setting( 'contact_email' );
$privacy_page  = absint( casa_kotti_setting( 'privacy_page', 0 ) );
$privacy_url   = $privacy_page && 'publish' === get_post_status( $privacy_page ) ? get_permalink( $privacy_page ) : '';
$has_form      = casa_kotti_setting( 'form_enabled', true )
	&& casa_kotti_has_valid_privacy_page()
	&& function_exists( 'casa_kotti_render_interest_form' );
$foliage_left  = get_theme_file_path( 'assets/images/foliage-left.svg' );
$foliage_right = get_theme_file_path( 'assets/images/foliage-right.svg' );
$motion_class  = casa_kotti_setting( 'animations_enabled', true ) ? ' has-motion' : '';
?>
<div class="ck-page<?php echo esc_attr( $motion_class ); ?>">
	<?php if ( file_exists( $foliage_left ) ) : ?>
		<img class="ck-foliage ck-foliage--left" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/foliage-left.svg' ) ); ?>" alt="" aria-hidden="true" width="560" height="980">
	<?php endif; ?>
	<?php if ( file_exists( $foliage_right ) ) : ?>
		<img class="ck-foliage ck-foliage--right" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/foliage-right.svg' ) ); ?>" alt="" aria-hidden="true" width="560" height="980">
	<?php endif; ?>

	<main class="ck-main" id="conteudo">
		<div class="ck-brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<p class="ck-wordmark" aria-label="<?php esc_attr_e( 'Casa Kotti', 'casa-kotti' ); ?>">Casa Kotti</p>
			<?php endif; ?>
			<p class="ck-tagline"><?php echo esc_html( casa_kotti_setting( 'tagline', 'Perfumaria para ambientes' ) ); ?></p>
		</div>

		<div class="ck-message">
			<h1><?php echo esc_html( casa_kotti_setting( 'headline', 'Em breve, um novo aroma para o seu cotidiano.' ) ); ?></h1>
			<p><?php echo esc_html( casa_kotti_setting( 'support_text', 'Estamos preparando nossa primeira coleção. Deixe seu e-mail para saber quando ela chegar.' ) ); ?></p>
		</div>

		<div class="ck-interest">
			<?php if ( $has_form ) : ?>
				<?php
				echo casa_kotti_render_interest_form( array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the plugin renderer.
					'label'   => casa_kotti_setting( 'email_label', 'Seu e-mail' ),
					'button'  => casa_kotti_setting( 'button_text', 'Quero saber do lançamento' ),
					'consent' => casa_kotti_setting( 'consent_text', 'Quero receber novidades sobre o lançamento da Casa Kotti por e-mail.' ),
					'success' => casa_kotti_setting( 'success_text', 'Pronto! Vamos avisar você quando a Casa Kotti chegar.' ),
				) );
				?>
			<?php elseif ( $contact_email ) : ?>
				<a class="ck-contact-fallback" href="<?php echo esc_url( 'mailto:' . antispambot( $contact_email ) ); ?>">
					<?php echo esc_html( casa_kotti_setting( 'contact_label', 'Fale com a Casa Kotti' ) ); ?>
				</a>
			<?php endif; ?>
		</div>
	</main>

	<footer class="ck-footer">
		<nav aria-label="<?php esc_attr_e( 'Links institucionais', 'casa-kotti' ); ?>">
			<?php if ( $instagram_url ) : ?>
				<a href="<?php echo esc_url( $instagram_url ); ?>" rel="noopener noreferrer"><?php echo esc_html( casa_kotti_setting( 'instagram_label', 'Instagram' ) ); ?></a>
			<?php endif; ?>
			<?php if ( $contact_email ) : ?>
				<a href="<?php echo esc_url( 'mailto:' . antispambot( $contact_email ) ); ?>"><?php echo esc_html( casa_kotti_setting( 'contact_label', 'Fale com a Casa Kotti' ) ); ?></a>
			<?php endif; ?>
			<?php if ( $privacy_url ) : ?>
				<a href="<?php echo esc_url( $privacy_url ); ?>"><?php echo esc_html( casa_kotti_setting( 'privacy_label', 'Política de privacidade' ) ); ?></a>
			<?php endif; ?>
		</nav>
		<p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> Casa Kotti</p>
	</footer>
</div>
<?php
get_footer();
