<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ckf-admin">
	<h1><?php esc_html_e( 'Questionários', 'casa-kotti-feedback' ); ?></h1>
	<p><?php esc_html_e( 'Cada questionário tem páginas, perguntas e textos próprios. Use o shortcode com o slug correspondente.', 'casa-kotti-feedback' ); ?></p>

	<h2><?php echo esc_html( $edit ? __( 'Editar questionário', 'casa-kotti-feedback' ) : __( 'Novo questionário', 'casa-kotti-feedback' ) ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ckf_save_survey' ); ?>
		<input type="hidden" name="action" value="ckf_save_survey">
		<input type="hidden" name="id" value="<?php echo esc_attr( $edit ? (string) $edit->id : '0' ); ?>">
		<p>
			<label><?php esc_html_e( 'Nome', 'casa-kotti-feedback' ); ?>
				<input class="regular-text" name="title" required value="<?php echo esc_attr( $edit ? $edit->title : '' ); ?>">
			</label>
			<label><?php esc_html_e( 'Slug', 'casa-kotti-feedback' ); ?>
				<input class="regular-text" name="slug" value="<?php echo esc_attr( $edit ? $edit->slug : '' ); ?>" <?php disabled( $edit && (int) $edit->is_default ); ?>>
			</label>
			<label><?php esc_html_e( 'Status', 'casa-kotti-feedback' ); ?>
				<select name="status">
					<option value="active" <?php selected( $edit ? $edit->status : 'active', 'active' ); ?>><?php esc_html_e( 'Ativo', 'casa-kotti-feedback' ); ?></option>
					<option value="inactive" <?php selected( $edit ? $edit->status : '', 'inactive' ); ?>><?php esc_html_e( 'Inativo', 'casa-kotti-feedback' ); ?></option>
				</select>
			</label>
		</p>
		<p>
			<label><?php esc_html_e( 'Descrição', 'casa-kotti-feedback' ); ?><br>
				<textarea class="large-text" name="description" rows="2"><?php echo esc_textarea( $edit ? $edit->description : '' ); ?></textarea>
			</label>
		</p>
		<?php submit_button( $edit ? __( 'Salvar questionário', 'casa-kotti-feedback' ) : __( 'Criar questionário', 'casa-kotti-feedback' ), 'secondary', 'submit', false ); ?>
	</form>

	<table class="widefat striped" style="max-width:960px;margin-top:24px">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Nome', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Shortcode', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Status', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Ações', 'casa-kotti-feedback' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $surveys as $row ) : ?>
			<tr>
				<td>
					<strong><?php echo esc_html( $row->title ); ?></strong>
					<?php if ( (int) $row->is_default ) : ?>
						— <?php esc_html_e( 'padrão', 'casa-kotti-feedback' ); ?>
					<?php endif; ?>
				</td>
				<td><code>[casa_kotti_feedback<?php echo (int) $row->is_default ? '' : ' slug="' . esc_attr( $row->slug ) . '"'; ?>]</code></td>
				<td><?php echo esc_html( 'active' === $row->status ? __( 'Ativo', 'casa-kotti-feedback' ) : __( 'Inativo', 'casa-kotti-feedback' ) ); ?></td>
				<td>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-questions&survey_id=' . absint( $row->id ) ) ); ?>"><?php esc_html_e( 'Páginas', 'casa-kotti-feedback' ); ?></a>
					|
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-preview&survey_id=' . absint( $row->id ) ) ); ?>"><?php esc_html_e( 'Pré-visualizar', 'casa-kotti-feedback' ); ?></a>
					|
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-feedback-settings&survey_id=' . absint( $row->id ) ) ); ?>"><?php esc_html_e( 'Textos', 'casa-kotti-feedback' ); ?></a>
					|
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-surveys&id=' . absint( $row->id ) ) ); ?>"><?php esc_html_e( 'Editar', 'casa-kotti-feedback' ); ?></a>
					|
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_duplicate_survey&id=' . absint( $row->id ) ), 'ckf_duplicate_survey_' . absint( $row->id ) ) ); ?>"><?php esc_html_e( 'Duplicar', 'casa-kotti-feedback' ); ?></a>
					<?php if ( ! (int) $row->is_default ) : ?>
						|
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_toggle_survey&id=' . absint( $row->id ) ), 'ckf_toggle_survey_' . absint( $row->id ) ) ); ?>"><?php echo esc_html( 'active' === $row->status ? __( 'Desativar', 'casa-kotti-feedback' ) : __( 'Ativar', 'casa-kotti-feedback' ) ); ?></a>
						|
						<a class="button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_delete_survey&id=' . absint( $row->id ) ), 'ckf_delete_survey_' . absint( $row->id ) ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Excluir este questionário e suas páginas?', 'casa-kotti-feedback' ) ); ?>');"><?php esc_html_e( 'Excluir', 'casa-kotti-feedback' ); ?></a>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
