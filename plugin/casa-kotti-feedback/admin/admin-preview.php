<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ckf-admin">
	<h1><?php esc_html_e( 'Pré-visualizar questionário', 'casa-kotti-feedback' ); ?></h1>
	<p><?php esc_html_e( 'Mesmo renderer do site público. O envio nesta tela não grava respostas.', 'casa-kotti-feedback' ); ?></p>
	<?php if ( ! empty( $ckf_survey ) ) : ?>
		<p><code>[casa_kotti_feedback slug="<?php echo esc_attr( $ckf_survey->slug ); ?>"]</code></p>
	<?php endif; ?>
	<div class="ckf-preview-frame">
		<?php
		if ( empty( $ckf_survey ) ) {
			echo '<p>' . esc_html__( 'Questionário não encontrado.', 'casa-kotti-feedback' ) . '</p>';
		} else {
			echo CKF_Shortcode::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				array(
					'slug'    => $ckf_survey->slug,
					'preview' => '1',
				)
			);
		}
		?>
	</div>
</div>
