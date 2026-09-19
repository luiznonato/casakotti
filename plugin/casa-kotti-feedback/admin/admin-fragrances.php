<?php
/**
 * Fragrance admin.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ckf-admin">
	<h1><?php esc_html_e( 'Fragrâncias', 'casa-kotti-feedback' ); ?></h1>
	<p><?php esc_html_e( 'Somente fragrâncias ativas aparecem no questionário. Desativar não apaga avaliações já recebidas.', 'casa-kotti-feedback' ); ?></p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ckf-frag-form">
		<?php wp_nonce_field( 'ckf_save_fragrance' ); ?>
		<input type="hidden" name="action" value="ckf_save_fragrance">
		<input type="hidden" name="id" value="<?php echo esc_attr( $edit ? (string) $edit->id : '0' ); ?>">
		<table class="form-table">
			<tr>
				<th><label for="ckf-name"><?php esc_html_e( 'Nome', 'casa-kotti-feedback' ); ?></label></th>
				<td><input class="regular-text" id="ckf-name" name="name" required value="<?php echo esc_attr( $edit ? $edit->name : '' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="ckf-slug"><?php esc_html_e( 'Slug', 'casa-kotti-feedback' ); ?></label></th>
				<td><input class="regular-text" id="ckf-slug" name="slug" value="<?php echo esc_attr( $edit ? $edit->slug : '' ); ?>"><p class="description"><?php esc_html_e( 'Usado em ?fragrancia= no QR Code. Vazio gera a partir do nome.', 'casa-kotti-feedback' ); ?></p></td>
			</tr>
			<tr>
				<th><label for="ckf-status"><?php esc_html_e( 'Status', 'casa-kotti-feedback' ); ?></label></th>
				<td>
					<select id="ckf-status" name="status">
						<option value="active" <?php selected( $edit ? $edit->status : 'active', 'active' ); ?>><?php esc_html_e( 'Ativa', 'casa-kotti-feedback' ); ?></option>
						<option value="inactive" <?php selected( $edit ? $edit->status : '', 'inactive' ); ?>><?php esc_html_e( 'Inativa', 'casa-kotti-feedback' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="ckf-order"><?php esc_html_e( 'Ordem', 'casa-kotti-feedback' ); ?></label></th>
				<td><input type="number" id="ckf-order" name="sort_order" value="<?php echo esc_attr( $edit ? (string) (int) $edit->sort_order : '0' ); ?>"></td>
			</tr>
		</table>
		<?php submit_button( $edit ? __( 'Atualizar fragrância', 'casa-kotti-feedback' ) : __( 'Cadastrar fragrância', 'casa-kotti-feedback' ) ); ?>
	</form>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Ordem', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Nome', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Slug', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Status', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Ações', 'casa-kotti-feedback' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( ! $rows ) : ?>
			<tr><td colspan="5"><?php esc_html_e( 'Nenhuma fragrância cadastrada.', 'casa-kotti-feedback' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $rows as $row ) : ?>
				<tr>
					<td>
						<?php echo esc_html( (string) (int) $row->sort_order ); ?>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_reorder_fragrance&id=' . absint( $row->id ) . '&dir=up' ), 'ckf_reorder_fragrance_' . absint( $row->id ) ) ); ?>">↑</a>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_reorder_fragrance&id=' . absint( $row->id ) . '&dir=down' ), 'ckf_reorder_fragrance_' . absint( $row->id ) ) ); ?>">↓</a>
					</td>
					<td><?php echo esc_html( $row->name ); ?></td>
					<td><code><?php echo esc_html( $row->slug ); ?></code></td>
					<td><?php echo esc_html( 'active' === $row->status ? __( 'Ativa', 'casa-kotti-feedback' ) : __( 'Inativa', 'casa-kotti-feedback' ) ); ?></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-fragrances&edit=' . absint( $row->id ) ) ); ?>"><?php esc_html_e( 'Editar', 'casa-kotti-feedback' ); ?></a>
						|
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_toggle_fragrance&id=' . absint( $row->id ) ), 'ckf_toggle_fragrance_' . absint( $row->id ) ) ); ?>">
							<?php echo esc_html( 'active' === $row->status ? __( 'Desativar', 'casa-kotti-feedback' ) : __( 'Ativar', 'casa-kotti-feedback' ) ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
