<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ckf-admin">
	<h1><?php esc_html_e( 'Pré-visualizar questionário', 'casa-kotti-feedback' ); ?></h1>
	<p><?php esc_html_e( 'Mesmo renderer do site público. O envio nesta tela usa a API real se o nonce estiver válido.', 'casa-kotti-feedback' ); ?></p>
	<div class="ckf-preview-frame" style="max-width:560px;padding:32px;background:#252726;border-radius:20px;">
		<?php echo CKF_Shortcode::render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</div>
