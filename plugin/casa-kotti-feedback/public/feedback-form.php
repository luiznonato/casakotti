<?php
/**
 * Public questionnaire markup.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$products    = ckf_products();
$intensity   = ckf_intensity_options();
$performance = ckf_performance_options();
$repurchase  = ckf_repurchase_options();
$fragrances  = CKF_Database::active_fragrances();
$privacy_id  = absint( get_option( 'wp_page_for_privacy_policy', 0 ) );
$privacy_url = $privacy_id && 'publish' === get_post_status( $privacy_id ) ? get_permalink( $privacy_id ) : '';
?>
<div class="ck-feedback" data-ck-feedback>
	<section class="ck-feedback__panel is-active" data-panel="intro">
		<p class="ck-feedback__kicker"><?php esc_html_e( 'Experiência', 'casa-kotti-feedback' ); ?></p>
		<h2 class="ck-feedback__title"><?php esc_html_e( 'Como foi sua experiência com a Casa Kotti?', 'casa-kotti-feedback' ); ?></h2>
		<p class="ck-feedback__lead"><?php esc_html_e( 'Queremos saber como foi ter um pouco da Casa Kotti com você.', 'casa-kotti-feedback' ); ?></p>
		<p class="ck-feedback__copy"><?php esc_html_e( 'Sua opinião nos ajuda a aperfeiçoar cada detalhe — da fragrância à experiência de receber o produto.', 'casa-kotti-feedback' ); ?></p>
		<p class="ck-feedback__copy"><?php esc_html_e( 'Leva menos de 2 minutos.', 'casa-kotti-feedback' ); ?></p>
		<button type="button" class="ck-feedback__btn" data-start><?php esc_html_e( 'Começar', 'casa-kotti-feedback' ); ?></button>
	</section>

	<form class="ck-feedback__form" data-form hidden novalidate>
		<div class="ck-feedback__progress" data-progress hidden>
			<p class="ck-feedback__counter" data-counter>01 / 10</p>
			<div class="ck-feedback__track" aria-hidden="true"><span class="ck-feedback__fill" data-fill></span></div>
		</div>

		<p class="ck-feedback__prefill" data-prefill hidden></p>

		<fieldset class="ck-feedback__step" data-step="product">
			<legend class="ck-feedback__question"><?php esc_html_e( 'Qual produto você experimentou?', 'casa-kotti-feedback' ); ?></legend>
			<div class="ck-feedback__options" role="radiogroup" aria-labelledby="">
				<?php foreach ( $products as $value => $label ) : ?>
					<label class="ck-feedback__option">
						<input type="radio" name="product" value="<?php echo esc_attr( $value ); ?>">
						<span><?php echo esc_html( $label ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
			<p class="ck-feedback__error" data-error hidden></p>
		</fieldset>

		<fieldset class="ck-feedback__step" data-step="fragrance" hidden>
			<legend class="ck-feedback__question"><?php esc_html_e( 'Qual fragrância você escolheu?', 'casa-kotti-feedback' ); ?></legend>
			<div class="ck-feedback__options" data-fragrance-options>
				<?php if ( ! $fragrances ) : ?>
					<p class="ck-feedback__copy"><?php esc_html_e( 'Nenhuma fragrância está disponível no momento.', 'casa-kotti-feedback' ); ?></p>
				<?php else : ?>
					<?php foreach ( $fragrances as $row ) : ?>
						<label class="ck-feedback__option">
							<input type="radio" name="fragrance" value="<?php echo esc_attr( $row->slug ); ?>">
							<span><?php echo esc_html( $row->name ); ?></span>
						</label>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<p class="ck-feedback__error" data-error hidden></p>
		</fieldset>

		<fieldset class="ck-feedback__step" data-step="overall" hidden>
			<legend class="ck-feedback__question"><?php esc_html_e( 'Como você avalia sua experiência geral com o produto?', 'casa-kotti-feedback' ); ?></legend>
			<div class="ck-feedback__stars" role="radiogroup" aria-label="<?php esc_attr_e( 'Nota de 1 a 5', 'casa-kotti-feedback' ); ?>">
				<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
					<label class="ck-feedback__star">
						<input type="radio" name="overall_rating" value="<?php echo esc_attr( (string) $i ); ?>">
						<span aria-hidden="true">★</span>
						<span class="ck-feedback__sr"><?php echo esc_html( sprintf( /* translators: %d rating */ __( '%d de 5', 'casa-kotti-feedback' ), $i ) ); ?></span>
					</label>
				<?php endfor; ?>
			</div>
			<p class="ck-feedback__hint"><span><?php esc_html_e( '1 — Não gostei', 'casa-kotti-feedback' ); ?></span><span><?php esc_html_e( '5 — Amei', 'casa-kotti-feedback' ); ?></span></p>
			<p class="ck-feedback__error" data-error hidden></p>
		</fieldset>

		<fieldset class="ck-feedback__step" data-step="intensity" hidden>
			<legend class="ck-feedback__question"><?php esc_html_e( 'Como você avalia a intensidade da fragrância?', 'casa-kotti-feedback' ); ?></legend>
			<div class="ck-feedback__options">
				<?php foreach ( $intensity as $value => $label ) : ?>
					<label class="ck-feedback__option">
						<input type="radio" name="intensity" value="<?php echo esc_attr( $value ); ?>">
						<span><?php echo esc_html( $label ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
			<p class="ck-feedback__error" data-error hidden></p>
		</fieldset>

		<fieldset class="ck-feedback__step" data-step="refil-target" hidden>
			<legend class="ck-feedback__question"><?php esc_html_e( 'Para qual produto o refil foi utilizado?', 'casa-kotti-feedback' ); ?></legend>
			<div class="ck-feedback__options">
				<label class="ck-feedback__option"><input type="radio" name="refil_target" value="difusor"><span><?php esc_html_e( 'Difusor', 'casa-kotti-feedback' ); ?></span></label>
				<label class="ck-feedback__option"><input type="radio" name="refil_target" value="outro"><span><?php esc_html_e( 'Outro', 'casa-kotti-feedback' ); ?></span></label>
			</div>
			<p class="ck-feedback__error" data-error hidden></p>
		</fieldset>

		<fieldset class="ck-feedback__step" data-step="product-specific" hidden>
			<legend class="ck-feedback__question" data-specific-legend></legend>
			<div class="ck-feedback__options" data-specific-options></div>
			<p class="ck-feedback__error" data-error hidden></p>
		</fieldset>

		<fieldset class="ck-feedback__step" data-step="performance" hidden>
			<legend class="ck-feedback__question"><?php esc_html_e( 'E sobre a duração/performance da fragrância?', 'casa-kotti-feedback' ); ?></legend>
			<div class="ck-feedback__options">
				<?php foreach ( $performance as $value => $label ) : ?>
					<label class="ck-feedback__option">
						<input type="radio" name="performance" value="<?php echo esc_attr( $value ); ?>">
						<span><?php echo esc_html( $label ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
			<p class="ck-feedback__error" data-error hidden></p>
		</fieldset>

		<fieldset class="ck-feedback__step" data-step="presentation" hidden>
			<legend class="ck-feedback__question"><?php esc_html_e( 'Como você avalia a apresentação do produto?', 'casa-kotti-feedback' ); ?></legend>
			<p class="ck-feedback__copy"><?php esc_html_e( 'Considere embalagem, rótulo e experiência ao receber.', 'casa-kotti-feedback' ); ?></p>
			<div class="ck-feedback__stars" role="radiogroup" aria-label="<?php esc_attr_e( 'Nota da apresentação de 1 a 5', 'casa-kotti-feedback' ); ?>">
				<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
					<label class="ck-feedback__star">
						<input type="radio" name="presentation_rating" value="<?php echo esc_attr( (string) $i ); ?>">
						<span aria-hidden="true">★</span>
						<span class="ck-feedback__sr"><?php echo esc_html( sprintf( /* translators: %d rating */ __( '%d de 5', 'casa-kotti-feedback' ), $i ) ); ?></span>
					</label>
				<?php endfor; ?>
			</div>
			<p class="ck-feedback__hint"><span><?php esc_html_e( '1 — Não gostei', 'casa-kotti-feedback' ); ?></span><span><?php esc_html_e( '5 — Amei', 'casa-kotti-feedback' ); ?></span></p>
			<p class="ck-feedback__error" data-error hidden></p>
		</fieldset>

		<fieldset class="ck-feedback__step" data-step="repurchase" hidden>
			<legend class="ck-feedback__question"><?php esc_html_e( 'Você compraria novamente um produto Casa Kotti?', 'casa-kotti-feedback' ); ?></legend>
			<div class="ck-feedback__options">
				<?php foreach ( $repurchase as $value => $label ) : ?>
					<label class="ck-feedback__option">
						<input type="radio" name="repurchase_intent" value="<?php echo esc_attr( $value ); ?>">
						<span><?php echo esc_html( $label ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
			<p class="ck-feedback__error" data-error hidden></p>
		</fieldset>

		<fieldset class="ck-feedback__step" data-step="nps" hidden>
			<legend class="ck-feedback__question"><?php esc_html_e( 'Você indicaria a Casa Kotti para alguém?', 'casa-kotti-feedback' ); ?></legend>
			<div class="ck-feedback__nps" role="radiogroup" aria-label="<?php esc_attr_e( 'De 0 a 10', 'casa-kotti-feedback' ); ?>">
				<?php for ( $i = 0; $i <= 10; $i++ ) : ?>
					<label class="ck-feedback__nps-item">
						<input type="radio" name="nps_score" value="<?php echo esc_attr( (string) $i ); ?>">
						<span><?php echo esc_html( (string) $i ); ?></span>
					</label>
				<?php endfor; ?>
			</div>
			<p class="ck-feedback__hint"><span><?php esc_html_e( 'Não indicaria', 'casa-kotti-feedback' ); ?></span><span><?php esc_html_e( 'Com certeza indicaria', 'casa-kotti-feedback' ); ?></span></p>
			<p class="ck-feedback__error" data-error hidden></p>
		</fieldset>

		<fieldset class="ck-feedback__step" data-step="improvement" hidden>
			<legend class="ck-feedback__question"><?php esc_html_e( 'Tem alguma coisa que você gostaria que a gente melhorasse?', 'casa-kotti-feedback' ); ?></legend>
			<label class="ck-feedback__field">
				<span class="ck-feedback__sr"><?php esc_html_e( 'Sugestão de melhoria, opcional', 'casa-kotti-feedback' ); ?></span>
				<textarea name="improvement_comment" rows="5" maxlength="4000" placeholder="<?php esc_attr_e( 'Conte pra gente. Toda sugestão é bem-vinda.', 'casa-kotti-feedback' ); ?>"></textarea>
			</label>
		</fieldset>

		<fieldset class="ck-feedback__step" data-step="positive" hidden>
			<legend class="ck-feedback__question"><?php esc_html_e( 'Teve alguma coisa que você gostou especialmente?', 'casa-kotti-feedback' ); ?></legend>
			<label class="ck-feedback__field">
				<span class="ck-feedback__sr"><?php esc_html_e( 'Comentário positivo, opcional', 'casa-kotti-feedback' ); ?></span>
				<textarea name="positive_comment" rows="5" maxlength="4000" placeholder="<?php esc_attr_e( 'Pode ser a fragrância, a embalagem ou qualquer detalhe da experiência.', 'casa-kotti-feedback' ); ?>"></textarea>
			</label>
		</fieldset>

		<fieldset class="ck-feedback__step" data-step="contact" hidden>
			<legend class="ck-feedback__question"><?php esc_html_e( 'Quer ficar mais perto da Casa Kotti?', 'casa-kotti-feedback' ); ?></legend>
			<p class="ck-feedback__copy"><?php esc_html_e( 'Se quiser, deixe seus dados para que a gente possa continuar essa conversa.', 'casa-kotti-feedback' ); ?></p>
			<label class="ck-feedback__field">
				<span><?php esc_html_e( 'Nome', 'casa-kotti-feedback' ); ?></span>
				<input type="text" name="customer_name" autocomplete="name" maxlength="190">
			</label>
			<label class="ck-feedback__field">
				<span><?php esc_html_e( 'E-mail', 'casa-kotti-feedback' ); ?></span>
				<input type="email" name="customer_email" inputmode="email" autocomplete="email" maxlength="190">
			</label>
			<label class="ck-feedback__consent">
				<input type="checkbox" name="marketing_consent" value="1">
				<span><?php esc_html_e( 'Quero receber novidades, lançamentos e conteúdos da Casa Kotti por e-mail.', 'casa-kotti-feedback' ); ?></span>
			</label>
			<p class="ck-feedback__privacy">
				<?php esc_html_e( 'Seus dados serão utilizados apenas conforme as escolhas acima.', 'casa-kotti-feedback' ); ?>
				<?php if ( $privacy_url ) : ?>
					<a href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Política de privacidade', 'casa-kotti-feedback' ); ?></a>
				<?php endif; ?>
			</p>
			<p class="ck-feedback__error" data-error hidden></p>
		</fieldset>

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
			<button type="button" class="ck-feedback__btn" data-next><?php esc_html_e( 'Continuar', 'casa-kotti-feedback' ); ?> <span aria-hidden="true">→</span></button>
			<button type="submit" class="ck-feedback__btn" data-submit hidden><?php esc_html_e( 'Enviar avaliação', 'casa-kotti-feedback' ); ?></button>
		</div>
	</form>

	<section class="ck-feedback__panel" data-panel="thanks" hidden>
		<h2 class="ck-feedback__title"><?php esc_html_e( 'Obrigado por compartilhar.', 'casa-kotti-feedback' ); ?></h2>
		<p class="ck-feedback__copy"><?php esc_html_e( 'Cada resposta ajuda a Casa Kotti a aperfeiçoar aquilo que fazemos e criar experiências cada vez melhores.', 'casa-kotti-feedback' ); ?></p>
		<a class="ck-feedback__btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Voltar para Casa Kotti', 'casa-kotti-feedback' ); ?></a>
	</section>
</div>
