<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$types = CKF_Questions::types();
?>
<div class="wrap ckf-admin">
	<h1><?php esc_html_e( 'Perguntas', 'casa-kotti-feedback' ); ?></h1>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-question-edit' ) ); ?>"><?php esc_html_e( 'Nova pergunta', 'casa-kotti-feedback' ); ?></a>
	</p>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Ordem', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Pergunta', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Tipo', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Obrigatória', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Condição', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Sistema', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Status', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Ações', 'casa-kotti-feedback' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( ! $rows ) : ?>
			<tr><td colspan="8"><?php esc_html_e( 'Nenhuma pergunta cadastrada.', 'casa-kotti-feedback' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $rows as $i => $row ) : ?>
				<?php $settings = CKF_Questions::settings( $row ); ?>
				<tr>
					<td>
						<?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_reorder_question&id=' . absint( $row->id ) . '&dir=up' ), 'ckf_reorder_question_' . absint( $row->id ) ) ); ?>">↑</a>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_reorder_question&id=' . absint( $row->id ) . '&dir=down' ), 'ckf_reorder_question_' . absint( $row->id ) ) ); ?>">↓</a>
					</td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-question-edit&id=' . absint( $row->id ) ) ); ?>"><?php echo esc_html( $row->title ); ?></a>
						<div><code><?php echo esc_html( $row->slug ); ?></code></div>
					</td>
					<td><?php echo esc_html( isset( $types[ $row->type ] ) ? $types[ $row->type ] : $row->type ); ?></td>
					<td><?php echo esc_html( $row->required ? __( 'Sim', 'casa-kotti-feedback' ) : __( 'Não', 'casa-kotti-feedback' ) ); ?></td>
					<td><?php echo esc_html( CKF_Conditions::summarize( $settings ) ); ?></td>
					<td><?php echo esc_html( $row->is_system ? __( 'Sim', 'casa-kotti-feedback' ) : __( 'Não', 'casa-kotti-feedback' ) ); ?></td>
					<td><?php echo esc_html( 'active' === $row->status ? __( 'Ativa', 'casa-kotti-feedback' ) : __( 'Inativa', 'casa-kotti-feedback' ) ); ?></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-question-edit&id=' . absint( $row->id ) ) ); ?>"><?php esc_html_e( 'Editar', 'casa-kotti-feedback' ); ?></a>
						|
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_duplicate_question&id=' . absint( $row->id ) ), 'ckf_duplicate_question_' . absint( $row->id ) ) ); ?>"><?php esc_html_e( 'Duplicar', 'casa-kotti-feedback' ); ?></a>
						|
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_toggle_question&id=' . absint( $row->id ) ), 'ckf_toggle_question_' . absint( $row->id ) ) ); ?>"><?php echo esc_html( 'active' === $row->status ? __( 'Desativar', 'casa-kotti-feedback' ) : __( 'Ativar', 'casa-kotti-feedback' ) ); ?></a>
						<?php if ( ! $row->is_system && ! CKF_Answers::question_has_answers( (int) $row->id ) ) : ?>
							| <a class="button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_delete_question&id=' . absint( $row->id ) ), 'ckf_delete_question_' . absint( $row->id ) ) ); ?>"><?php esc_html_e( 'Excluir', 'casa-kotti-feedback' ); ?></a>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
