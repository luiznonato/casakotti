<?php
/**
 * Public questionnaire — rendered from wizard pages.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$copy        = CKF_Questions::copy();
$wizard      = CKF_Questions::public_wizard();
$steps       = $wizard['steps'];
$questions   = $wizard['questions'];
$by_step     = array();
$orphans     = array();
foreach ( $questions as $question ) {
	$sid = isset( $question['step_id'] ) ? (int) $question['step_id'] : 0;
	if ( $sid ) {
		if ( ! isset( $by_step[ $sid ] ) ) {
			$by_step[ $sid ] = array();
		}
		$by_step[ $sid ][] = $question;
	} else {
		$orphans[] = $question;
	}
}
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
		<p class="ck-feedback__error ck-feedback__error--form" data-form-error hidden></p>
		<?php foreach ( $steps as $step ) : ?>
			<?php
			$sid   = (int) $step['id'];
			$group = isset( $by_step[ $sid ] ) ? $by_step[ $sid ] : array();
			if ( ! $group ) {
				continue;
			}
			echo CKF_Renderer::page( $step, $group, $copy ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		<?php endforeach; ?>
		<?php foreach ( $orphans as $question ) : ?>
			<?php
			$fake = array(
				'id'          => 0,
				'slug'        => $question['slug'],
				'title'       => $question['title'],
				'description' => '',
			);
			echo CKF_Renderer::page( $fake, array( $question ), $copy ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
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
			<button type="button" class="ck-feedback__btn" data-next><?php esc_html_e( 'Próximo', 'casa-kotti-feedback' ); ?> <span aria-hidden="true">→</span></button>
			<button type="submit" class="ck-feedback__btn" data-submit hidden><?php esc_html_e( 'Enviar', 'casa-kotti-feedback' ); ?></button>
		</div>
	</form>

	<section class="ck-feedback__panel" data-panel="thanks" hidden>
		<h2 class="ck-feedback__title"><?php echo esc_html( $copy['thanks_title'] ); ?></h2>
		<p class="ck-feedback__helper"><?php echo esc_html( $copy['thanks_body'] ); ?></p>
		<a class="ck-feedback__btn" href="<?php echo esc_url( $thanks_url ); ?>"><?php echo esc_html( $copy['thanks_button'] ); ?></a>
	</section>
</div>
