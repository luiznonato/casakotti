<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$types      = CKF_Questions::types();
$locked     = $question && CKF_Questions::slug_locked( $question );
$cond       = isset( $settings['conditions']['rules'][0] ) ? $settings['conditions']['rules'][0] : array();
$all        = CKF_Questions::all();
$option_src = isset( $settings['source'] ) ? $settings['source'] : '';
$steps      = CKF_Steps::all();
$step_id    = $question && isset( $question->step_id ) ? (int) $question->step_id : ( isset( $_GET['step_id'] ) ? absint( $_GET['step_id'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap ckf-admin">
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-questions' ) ); ?>"><?php esc_html_e( '← Questionário', 'casa-kotti-feedback' ); ?></a></p>
	<h1><?php echo esc_html( $question ? __( 'Editar pergunta', 'casa-kotti-feedback' ) : __( 'Nova pergunta', 'casa-kotti-feedback' ) ); ?></h1>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ckf-question-form" id="ckf-question-form">
		<?php wp_nonce_field( 'ckf_save_question' ); ?>
		<input type="hidden" name="action" value="ckf_save_question">
		<input type="hidden" name="id" value="<?php echo esc_attr( $question ? (string) $question->id : '0' ); ?>">
		<table class="form-table">
			<tr>
				<th><label for="ckf-title"><?php esc_html_e( 'Pergunta', 'casa-kotti-feedback' ); ?></label></th>
				<td><input class="large-text" id="ckf-title" name="title" required value="<?php echo esc_attr( $question ? $question->title : '' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="ckf-description"><?php esc_html_e( 'Texto auxiliar', 'casa-kotti-feedback' ); ?></label></th>
				<td><textarea class="large-text" id="ckf-description" name="description" rows="3"><?php echo esc_textarea( $question ? $question->description : '' ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="ckf-step-id"><?php esc_html_e( 'Página', 'casa-kotti-feedback' ); ?></label></th>
				<td>
					<select id="ckf-step-id" name="step_id">
						<option value="0"><?php esc_html_e( 'Sem página', 'casa-kotti-feedback' ); ?></option>
						<?php foreach ( $steps as $step ) : ?>
							<option value="<?php echo esc_attr( (string) $step->id ); ?>" <?php selected( $step_id, (int) $step->id ); ?>><?php echo esc_html( $step->title ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="ckf-type"><?php esc_html_e( 'Tipo', 'casa-kotti-feedback' ); ?></label></th>
				<td>
					<select id="ckf-type" name="type" <?php disabled( $question && $question->is_system ); ?>>
						<?php foreach ( $types as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $question ? $question->type : 'single_choice', $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="ckf-slug"><?php esc_html_e( 'Slug', 'casa-kotti-feedback' ); ?></label></th>
				<td>
					<input class="regular-text" id="ckf-slug" name="slug" value="<?php echo esc_attr( $question ? $question->slug : '' ); ?>" <?php disabled( $locked ); ?>>
					<?php if ( $question && $question->is_system ) : ?>
						<p class="description"><?php esc_html_e( 'Identificador de sistema protegido. Alimenta métricas internas e não pode ser alterado.', 'casa-kotti-feedback' ); ?></p>
					<?php elseif ( $locked ) : ?>
						<p class="description"><?php esc_html_e( 'Este slug já possui respostas e não pode ser alterado.', 'casa-kotti-feedback' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Obrigatória', 'casa-kotti-feedback' ); ?></th>
				<td><label><input type="checkbox" name="required" value="1" <?php checked( $question ? $question->required : 0, 1 ); ?>> <?php esc_html_e( 'Resposta obrigatória', 'casa-kotti-feedback' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Status', 'casa-kotti-feedback' ); ?></th>
				<td>
					<select name="status">
						<option value="active" <?php selected( $question ? $question->status : 'active', 'active' ); ?>><?php esc_html_e( 'Ativa', 'casa-kotti-feedback' ); ?></option>
						<option value="inactive" <?php selected( $question ? $question->status : '', 'inactive' ); ?>><?php esc_html_e( 'Inativa', 'casa-kotti-feedback' ); ?></option>
					</select>
				</td>
			</tr>
			<tr class="ckf-type-field" data-types="text,textarea,email">
				<th><label for="ckf-placeholder"><?php esc_html_e( 'Placeholder', 'casa-kotti-feedback' ); ?></label></th>
				<td><input class="regular-text" id="ckf-placeholder" name="placeholder" value="<?php echo esc_attr( isset( $settings['placeholder'] ) ? $settings['placeholder'] : '' ); ?>"></td>
			</tr>
			<tr class="ckf-type-field" data-types="text,textarea">
				<th><label for="ckf-min-length"><?php esc_html_e( 'Mínimo de caracteres', 'casa-kotti-feedback' ); ?></label></th>
				<td><input type="number" id="ckf-min-length" name="min_length" value="<?php echo esc_attr( isset( $settings['min_length'] ) ? (string) $settings['min_length'] : '' ); ?>"></td>
			</tr>
			<tr class="ckf-type-field" data-types="text,textarea">
				<th><label for="ckf-max-length"><?php esc_html_e( 'Máximo de caracteres', 'casa-kotti-feedback' ); ?></label></th>
				<td><input type="number" id="ckf-max-length" name="max_length" value="<?php echo esc_attr( isset( $settings['max_length'] ) ? (string) $settings['max_length'] : '' ); ?>"></td>
			</tr>
			<tr class="ckf-type-field" data-types="multi_choice">
				<th><?php esc_html_e( 'Seleções mín / máx', 'casa-kotti-feedback' ); ?></th>
				<td>
					<input type="number" name="min_selections" value="<?php echo esc_attr( isset( $settings['min_selections'] ) ? (string) $settings['min_selections'] : '' ); ?>">
					<input type="number" name="max_selections" value="<?php echo esc_attr( isset( $settings['max_selections'] ) ? (string) $settings['max_selections'] : '' ); ?>">
				</td>
			</tr>
			<tr class="ckf-type-field" data-types="yes_no">
				<th><?php esc_html_e( 'Checkbox (opcional)', 'casa-kotti-feedback' ); ?></th>
				<td>
					<label><input type="checkbox" name="ui" value="checkbox" <?php checked( isset( $settings['ui'] ) ? $settings['ui'] : '', 'checkbox' ); ?>> <?php esc_html_e( 'Exibir como checkbox simples', 'casa-kotti-feedback' ); ?></label>
					<p><input class="large-text" name="checkbox_label" value="<?php echo esc_attr( isset( $settings['checkbox_label'] ) ? $settings['checkbox_label'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Texto do checkbox', 'casa-kotti-feedback' ); ?>"></p>
				</td>
			</tr>
			<tr class="ckf-type-field" data-types="stars,scale,number">
				<th><?php esc_html_e( 'Mínimo / Máximo', 'casa-kotti-feedback' ); ?></th>
				<td>
					<input type="number" name="min" value="<?php echo esc_attr( isset( $settings['min'] ) ? (string) $settings['min'] : '' ); ?>">
					<input type="number" name="max" value="<?php echo esc_attr( isset( $settings['max'] ) ? (string) $settings['max'] : '' ); ?>">
				</td>
			</tr>
			<tr class="ckf-type-field" data-types="scale">
				<th><?php esc_html_e( 'Textos da escala', 'casa-kotti-feedback' ); ?></th>
				<td>
					<input class="regular-text" name="min_label" placeholder="<?php esc_attr_e( 'Esquerda', 'casa-kotti-feedback' ); ?>" value="<?php echo esc_attr( isset( $settings['min_label'] ) ? $settings['min_label'] : '' ); ?>">
					<input class="regular-text" name="max_label" placeholder="<?php esc_attr_e( 'Direita', 'casa-kotti-feedback' ); ?>" value="<?php echo esc_attr( isset( $settings['max_label'] ) ? $settings['max_label'] : '' ); ?>">
				</td>
			</tr>
			<tr class="ckf-type-field" data-types="number">
				<th><label for="ckf-step"><?php esc_html_e( 'Step', 'casa-kotti-feedback' ); ?></label></th>
				<td><input id="ckf-step" name="step" value="<?php echo esc_attr( isset( $settings['step'] ) ? $settings['step'] : '' ); ?>"></td>
			</tr>
			<?php if ( $question && 'fragrance' === $question->slug ) : ?>
				<tr>
					<th><?php esc_html_e( 'Fonte das opções', 'casa-kotti-feedback' ); ?></th>
					<td><?php esc_html_e( 'Fragrâncias ativas (cadastro Casa Kotti → Fragrâncias).', 'casa-kotti-feedback' ); ?></td>
				</tr>
			<?php endif; ?>
			<tr class="ckf-type-field" data-types="single_choice,radio,multi_choice,select" <?php echo 'fragrances' === $option_src ? 'hidden' : ''; ?>>
				<th><?php esc_html_e( 'Alternativas', 'casa-kotti-feedback' ); ?></th>
				<td>
					<table class="widefat" id="ckf-options">
						<thead><tr><th><?php esc_html_e( 'Label', 'casa-kotti-feedback' ); ?></th><th><?php esc_html_e( 'Value', 'casa-kotti-feedback' ); ?></th></tr></thead>
						<tbody>
						<?php
						$opts = $options ? $options : array();
						if ( ! $opts ) {
							$opts = array( (object) array( 'id' => 0, 'label' => '', 'value' => '' ) );
						}
						foreach ( $opts as $opt ) :
							?>
							<tr>
								<td><input class="regular-text" name="opt_label[]" value="<?php echo esc_attr( $opt->label ); ?>"></td>
								<td>
									<input class="regular-text" name="opt_value[]" value="<?php echo esc_attr( $opt->value ); ?>">
									<input type="hidden" name="opt_id[]" value="<?php echo esc_attr( (string) (int) $opt->id ); ?>">
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<p><button type="button" class="button" id="ckf-add-option"><?php esc_html_e( '+ Adicionar alternativa', 'casa-kotti-feedback' ); ?></button></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Condição de exibição', 'casa-kotti-feedback' ); ?></th>
				<td>
					<select name="cond_question">
						<option value=""><?php esc_html_e( 'Sempre visível', 'casa-kotti-feedback' ); ?></option>
						<?php foreach ( $all as $other ) : ?>
							<?php if ( $question && (int) $other->id === (int) $question->id ) { continue; } ?>
							<option value="<?php echo esc_attr( $other->slug ); ?>" <?php selected( isset( $cond['question'] ) ? $cond['question'] : '', $other->slug ); ?>><?php echo esc_html( $other->title ); ?></option>
						<?php endforeach; ?>
					</select>
					<select name="cond_operator">
						<?php foreach ( CKF_Conditions::operators() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $cond['operator'] ) ? $cond['operator'] : 'equals', $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<input class="regular-text" name="cond_value" value="<?php echo esc_attr( isset( $cond['value'] ) ? $cond['value'] : '' ); ?>" placeholder="value">
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Salvar pergunta', 'casa-kotti-feedback' ) ); ?>
		<button type="button" class="button" id="ckf-preview-btn"><?php esc_html_e( 'Pré-visualizar', 'casa-kotti-feedback' ); ?></button>
	</form>
	<div class="ckf-preview-frame" id="ckf-preview" hidden></div>
</div>
