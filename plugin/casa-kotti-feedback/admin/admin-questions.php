<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$types     = CKF_Questions::types();
$by_step   = array();
$unassigned = array();
foreach ( $rows as $row ) {
	$sid = isset( $row->step_id ) ? (int) $row->step_id : 0;
	if ( $sid ) {
		if ( ! isset( $by_step[ $sid ] ) ) {
			$by_step[ $sid ] = array();
		}
		$by_step[ $sid ][] = $row;
	} else {
		$unassigned[] = $row;
	}
}
$edit_step = null;
if ( isset( $_GET['step'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$edit_step = CKF_Steps::get( absint( $_GET['step'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}
?>
<div class="wrap ckf-admin">
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-surveys' ) ); ?>"><?php esc_html_e( '← Questionários', 'casa-kotti-feedback' ); ?></a></p>
	<h1><?php echo esc_html( $survey->title ); ?></h1>
	<p><?php esc_html_e( 'Cada página pode conter uma ou várias perguntas. O progresso do cliente conta páginas, não perguntas.', 'casa-kotti-feedback' ); ?></p>
	<p><code>[casa_kotti_feedback slug="<?php echo esc_attr( $survey->slug ); ?>"]</code></p>

	<h2><?php echo esc_html( $edit_step ? __( 'Editar página', 'casa-kotti-feedback' ) : __( 'Nova página', 'casa-kotti-feedback' ) ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ckf-step-form">
		<?php wp_nonce_field( 'ckf_save_step' ); ?>
		<input type="hidden" name="action" value="ckf_save_step">
		<input type="hidden" name="survey_id" value="<?php echo esc_attr( (string) (int) $survey->id ); ?>">
		<input type="hidden" name="id" value="<?php echo esc_attr( $edit_step ? (string) $edit_step->id : '0' ); ?>">
		<p>
			<label><?php esc_html_e( 'Título', 'casa-kotti-feedback' ); ?>
				<input class="regular-text" name="title" required value="<?php echo esc_attr( $edit_step ? $edit_step->title : '' ); ?>">
			</label>
			<label><?php esc_html_e( 'Slug', 'casa-kotti-feedback' ); ?>
				<input class="regular-text" name="slug" value="<?php echo esc_attr( $edit_step ? $edit_step->slug : '' ); ?>">
			</label>
			<label><?php esc_html_e( 'Status', 'casa-kotti-feedback' ); ?>
				<select name="status">
					<option value="active" <?php selected( $edit_step ? $edit_step->status : 'active', 'active' ); ?>><?php esc_html_e( 'Ativa', 'casa-kotti-feedback' ); ?></option>
					<option value="inactive" <?php selected( $edit_step ? $edit_step->status : '', 'inactive' ); ?>><?php esc_html_e( 'Inativa', 'casa-kotti-feedback' ); ?></option>
				</select>
			</label>
		</p>
		<p>
			<label><?php esc_html_e( 'Descrição', 'casa-kotti-feedback' ); ?><br>
				<textarea class="large-text" name="description" rows="2"><?php echo esc_textarea( $edit_step ? $edit_step->description : '' ); ?></textarea>
			</label>
		</p>
		<?php submit_button( $edit_step ? __( 'Salvar página', 'casa-kotti-feedback' ) : __( 'Criar página', 'casa-kotti-feedback' ), 'secondary', 'submit', false ); ?>
	</form>

	<form id="ckf-order-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ckf_save_order' ); ?>
		<input type="hidden" name="action" value="ckf_save_order">
		<input type="hidden" name="survey_id" value="<?php echo esc_attr( (string) (int) $survey->id ); ?>">
		<p><button class="button"><?php esc_html_e( 'Salvar ordem (após arrastar)', 'casa-kotti-feedback' ); ?></button>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-preview&survey_id=' . absint( $survey->id ) ) ); ?>"><?php esc_html_e( 'Pré-visualizar', 'casa-kotti-feedback' ); ?></a>
		</p>
	<div id="ckf-pages">
	<?php
	$index = 0;
	foreach ( $steps as $step ) :
		$index++;
		$qs = isset( $by_step[ (int) $step->id ] ) ? $by_step[ (int) $step->id ] : array();
		?>
		<section class="ckf-page-card" draggable="true" data-step-id="<?php echo esc_attr( (string) $step->id ); ?>">
			<input type="hidden" name="page_order[]" value="<?php echo esc_attr( (string) $step->id ); ?>">
			<header>
				<strong><?php echo esc_html( sprintf( __( 'PÁGINA %s', 'casa-kotti-feedback' ), str_pad( (string) $index, 2, '0', STR_PAD_LEFT ) ) ); ?></strong>
				<h2><?php echo esc_html( $step->title ); ?></h2>
				<p><code><?php echo esc_html( $step->slug ); ?></code> · <?php echo esc_html( 'active' === $step->status ? __( 'Ativa', 'casa-kotti-feedback' ) : __( 'Inativa', 'casa-kotti-feedback' ) ); ?></p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-questions&survey_id=' . absint( $survey->id ) . '&step=' . absint( $step->id ) ) ); ?>"><?php esc_html_e( 'Editar página', 'casa-kotti-feedback' ); ?></a>
					|
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_reorder_step&id=' . absint( $step->id ) . '&dir=up&survey_id=' . absint( $survey->id ) ), 'ckf_reorder_step_' . absint( $step->id ) ) ); ?>">↑</a>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_reorder_step&id=' . absint( $step->id ) . '&dir=down&survey_id=' . absint( $survey->id ) ), 'ckf_reorder_step_' . absint( $step->id ) ) ); ?>">↓</a>
					|
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_duplicate_step&id=' . absint( $step->id ) . '&survey_id=' . absint( $survey->id ) ), 'ckf_duplicate_step_' . absint( $step->id ) ) ); ?>"><?php esc_html_e( 'Duplicar página', 'casa-kotti-feedback' ); ?></a>
					|
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_toggle_step&id=' . absint( $step->id ) . '&survey_id=' . absint( $survey->id ) ), 'ckf_toggle_step_' . absint( $step->id ) ) ); ?>"><?php echo esc_html( 'active' === $step->status ? __( 'Desativar', 'casa-kotti-feedback' ) : __( 'Ativar', 'casa-kotti-feedback' ) ); ?></a>
					|
					<a class="button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_delete_step&id=' . absint( $step->id ) . '&survey_id=' . absint( $survey->id ) ), 'ckf_delete_step_' . absint( $step->id ) ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Excluir esta página? As perguntas ficarão sem página.', 'casa-kotti-feedback' ) ); ?>');"><?php esc_html_e( 'Excluir página', 'casa-kotti-feedback' ); ?></a>
					|
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-question-edit&survey_id=' . absint( $survey->id ) . '&step_id=' . absint( $step->id ) ) ); ?>"><?php esc_html_e( 'Adicionar pergunta', 'casa-kotti-feedback' ); ?></a>
				</p>
			</header>
			<?php if ( ! $qs ) : ?>
				<p><?php esc_html_e( 'Nenhuma pergunta nesta página.', 'casa-kotti-feedback' ); ?></p>
			<?php else : ?>
				<ul class="ckf-q-list">
					<?php foreach ( $qs as $row ) : ?>
						<li draggable="true" data-question-id="<?php echo esc_attr( (string) $row->id ); ?>">
							<input type="hidden" name="question_order[]" value="<?php echo esc_attr( (string) $row->id ); ?>">
							<div>
								<strong><?php echo esc_html( $row->title ); ?></strong>
								<div><?php echo esc_html( isset( $types[ $row->type ] ) ? $types[ $row->type ] : $row->type ); ?> · <code><?php echo esc_html( $row->slug ); ?></code></div>
							</div>
							<div>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-question-edit&survey_id=' . absint( $survey->id ) . '&id=' . absint( $row->id ) ) ); ?>"><?php esc_html_e( 'Editar', 'casa-kotti-feedback' ); ?></a>
								|
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_reorder_question&id=' . absint( $row->id ) . '&dir=up&survey_id=' . absint( $survey->id ) ), 'ckf_reorder_question_' . absint( $row->id ) ) ); ?>">↑</a>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_reorder_question&id=' . absint( $row->id ) . '&dir=down&survey_id=' . absint( $survey->id ) ), 'ckf_reorder_question_' . absint( $row->id ) ) ); ?>">↓</a>
								|
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_duplicate_question&id=' . absint( $row->id ) . '&survey_id=' . absint( $survey->id ) ), 'ckf_duplicate_question_' . absint( $row->id ) ) ); ?>"><?php esc_html_e( 'Duplicar', 'casa-kotti-feedback' ); ?></a>
								<?php if ( ! $row->is_system && ! CKF_Answers::question_has_answers( (int) $row->id ) ) : ?>
									|
									<a class="button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_delete_question&id=' . absint( $row->id ) . '&survey_id=' . absint( $survey->id ) ), 'ckf_delete_question_' . absint( $row->id ) ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Excluir esta pergunta?', 'casa-kotti-feedback' ) ); ?>');"><?php esc_html_e( 'Excluir', 'casa-kotti-feedback' ); ?></a>
								<?php endif; ?>
								<label class="ckf-move">
									<?php esc_html_e( 'Mover para', 'casa-kotti-feedback' ); ?>
									<select class="ckf-jump-step" data-id="<?php echo esc_attr( (string) $row->id ); ?>" data-survey="<?php echo esc_attr( (string) (int) $survey->id ); ?>">
										<?php foreach ( $steps as $opt_step ) : ?>
											<option value="<?php echo esc_attr( (string) $opt_step->id ); ?>" <?php selected( (int) $row->step_id, (int) $opt_step->id ); ?>><?php echo esc_html( $opt_step->title ); ?></option>
										<?php endforeach; ?>
									</select>
								</label>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	<?php endforeach; ?>
	</div>
	</form>

	<?php if ( $unassigned ) : ?>
		<section class="ckf-page-card">
			<header><h2><?php esc_html_e( 'Sem página', 'casa-kotti-feedback' ); ?></h2></header>
			<ul class="ckf-q-list">
				<?php foreach ( $unassigned as $row ) : ?>
					<li>
						<div>
							<strong><?php echo esc_html( $row->title ); ?></strong>
							<div><?php echo esc_html( isset( $types[ $row->type ] ) ? $types[ $row->type ] : $row->type ); ?></div>
						</div>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'ckf_move_question' ); ?>
							<input type="hidden" name="action" value="ckf_move_question">
							<input type="hidden" name="survey_id" value="<?php echo esc_attr( (string) (int) $survey->id ); ?>">
							<input type="hidden" name="id" value="<?php echo esc_attr( (string) $row->id ); ?>">
							<select name="step_id">
								<?php foreach ( $steps as $opt_step ) : ?>
									<option value="<?php echo esc_attr( (string) $opt_step->id ); ?>"><?php echo esc_html( $opt_step->title ); ?></option>
								<?php endforeach; ?>
							</select>
							<button class="button"><?php esc_html_e( 'Mover', 'casa-kotti-feedback' ); ?></button>
						</form>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
</div>
