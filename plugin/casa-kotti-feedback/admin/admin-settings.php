<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ckf-admin">
	<h1><?php esc_html_e( 'Configurações do questionário', 'casa-kotti-feedback' ); ?></h1>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ckf_save_copy' ); ?>
		<input type="hidden" name="action" value="ckf_save_copy">
		<h2><?php esc_html_e( 'Introdução', 'casa-kotti-feedback' ); ?></h2>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Título', 'casa-kotti-feedback' ); ?></th><td><input class="large-text" name="intro_title" value="<?php echo esc_attr( $copy['intro_title'] ); ?>"></td></tr>
			<tr><th><?php esc_html_e( 'Texto 1', 'casa-kotti-feedback' ); ?></th><td><textarea class="large-text" name="intro_lead" rows="3"><?php echo esc_textarea( $copy['intro_lead'] ); ?></textarea></td></tr>
			<tr><th><?php esc_html_e( 'Texto 2', 'casa-kotti-feedback' ); ?></th><td><textarea class="large-text" name="intro_body" rows="3"><?php echo esc_textarea( $copy['intro_body'] ); ?></textarea></td></tr>
			<tr><th><?php esc_html_e( 'Texto 3', 'casa-kotti-feedback' ); ?></th><td><input class="large-text" name="intro_note" value="<?php echo esc_attr( $copy['intro_note'] ); ?>"></td></tr>
			<tr><th><?php esc_html_e( 'Botão', 'casa-kotti-feedback' ); ?></th><td><input class="regular-text" name="intro_button" value="<?php echo esc_attr( $copy['intro_button'] ); ?>"></td></tr>
		</table>
		<h2><?php esc_html_e( 'Tela final', 'casa-kotti-feedback' ); ?></h2>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Título', 'casa-kotti-feedback' ); ?></th><td><input class="large-text" name="thanks_title" value="<?php echo esc_attr( $copy['thanks_title'] ); ?>"></td></tr>
			<tr><th><?php esc_html_e( 'Mensagem', 'casa-kotti-feedback' ); ?></th><td><textarea class="large-text" name="thanks_body" rows="3"><?php echo esc_textarea( $copy['thanks_body'] ); ?></textarea></td></tr>
			<tr><th><?php esc_html_e( 'Texto do botão', 'casa-kotti-feedback' ); ?></th><td><input class="regular-text" name="thanks_button" value="<?php echo esc_attr( $copy['thanks_button'] ); ?>"></td></tr>
			<tr><th><?php esc_html_e( 'Link', 'casa-kotti-feedback' ); ?></th><td><input class="regular-text" type="url" name="thanks_url" value="<?php echo esc_attr( $copy['thanks_url'] ); ?>" placeholder="<?php esc_attr_e( 'Vazio usa a homepage', 'casa-kotti-feedback' ); ?>"></td></tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
