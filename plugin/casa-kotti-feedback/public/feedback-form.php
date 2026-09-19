<?php
/**
 * Public questionnaire — rendered from the question builder.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$copy       = CKF_Questions::copy();
$questions  = CKF_Questions::public_definition();
$privacy_id = absint( get_option( 'wp_page_for_privacy_policy', 0 ) );
$privacy_url = $privacy_id && 'publish' === get_post_status( $privacy_id ) ? get_permalink( $privacy_id ) : '';
$thanks_url = $copy['thanks_url'] ? $copy['thanks_url'] : home_url( '/' );
?>
<div class="ck-feedback" data-ck-feedback>
	<section class="ck-feedback__panel is-active" data-panel="intro">
		<p class="ck-feedback__kicker"><?php esc_html_e( 'Experiência', 'casa-kotti-feedback' ); ?></p>
		<h2 class="ck-feedback__title"><?php echo esc_html( $copy['intro_title'] ); ?></h2>
		<?php if ( $copy['intro_lead'] ) : ?><p class="ck-feedback__lead"><?php echo esc_html( $copy['intro_lead'] ); ?></p><?php endif; ?>
		<?php if ( $copy['intro_body'] ) : ?><p class="ck-feedback__helper"><?php echo esc_html( $copy['intro_body'] ); ?></p><?php endif; ?>
		<?php if ( $copy['intro_note'] ) : ?><p class="ck-feedback__helper"><?php echo esc_html( $copy['intro_note'] ); ?></p><?php endif; ?>
		<button type="button" class="ck-feedback__btn" data-start><?php echo esc_html( $copy['intro_button'] ); ?></button>
	</section>

	<form class="ck-feedback__form" data-form hidden novalidate>
		<div class="ck-feedback__progress" data-progress hidden>
			<p class="ck-feedback__counter" data-counter>01 / 01</p>
			<div class="ck-feedback__track" aria-hidden="true"><span class="ck-feedback__fill" data-fill></span></div>
		</div>
		<p class="ck-feedback__prefill" data-prefill hidden></p>
		<?php foreach ( $questions as $question ) : ?>
			<?php echo CKF_Renderer::step( $question ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in renderer. ?>
		<?php endforeach; ?>
		<div class="ck-honeypot" aria-hidden="true">
			<label><?php esc_html_e( 'Não preencha este campo', 'casa-kotti-feedback' ); ?>
				<input type="text" name="website" tabindex="-1" autocomplete="off">
			</label>
		</div>
		<input type="hidden" name="source" value="">
		<input type="hidden" name="campaign" value="">
		<input type="hidden" name="batch" value="">
		<input type="hidden" name="product_code" value="">
		<div class="ck-feedback__nav" data-nav hidden>
			<button type="button" class="ck-feedback__btn ck-feedback__btn--ghost" data-back><?php esc_html_e( 'Voltar', 'casa-kotti-feedback' ); ?></button>
			<button type="button" class="ck-feedback__btn" data-next><?php esc_html_e( 'Continuar', 'casa-kotti-feedback' ); ?> <span aria-hidden="true">→</span></button>
			<button type="submit" class="ck-feedback__btn" data-submit hidden><?php esc_html_e( 'Enviar avaliação', 'casa-kotti-feedback' ); ?></button>
		</div>
	</form>

	<section class="ck-feedback__panel" data-panel="thanks" hidden>
		<h2 class="ck-feedback__title"><?php echo esc_html( $copy['thanks_title'] ); ?></h2>
		<p class="ck-feedback__helper"><?php echo esc_html( $copy['thanks_body'] ); ?></p>
		<?php if ( $privacy_url ) : ?>
			<p class="ck-feedback__privacy"><a href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Política de privacidade', 'casa-kotti-feedback' ); ?></a></p>
		<?php endif; ?>
		<a class="ck-feedback__btn" href="<?php echo esc_url( $thanks_url ); ?>"><?php echo esc_html( $copy['thanks_button'] ); ?></a>
	</section>
</div>
