<?php
/**
 * Reviews dashboard and table.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$products = ckf_products();
$fmt = function ( $n, $dec = 1 ) {
	return number_format_i18n( (float) $n, $dec );
};
?>
<div class="wrap ckf-admin">
	<h1><?php esc_html_e( 'Avaliações', 'casa-kotti-feedback' ); ?></h1>
	<?php if ( isset( $_GET['deleted'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Avaliação excluída.', 'casa-kotti-feedback' ); ?></p></div>
	<?php endif; ?>

	<div class="ckf-cards">
		<div class="ckf-card">
			<p><?php esc_html_e( 'Avaliações', 'casa-kotti-feedback' ); ?></p>
			<strong><?php echo esc_html( number_format_i18n( (int) $metrics->total ) ); ?></strong>
		</div>
		<div class="ckf-card">
			<p><?php esc_html_e( 'Nota média', 'casa-kotti-feedback' ); ?></p>
			<strong><?php echo esc_html( $metrics->total ? $fmt( $metrics->avg_overall ) . ' / 5' : '—' ); ?></strong>
		</div>
		<div class="ckf-card">
			<p><?php esc_html_e( 'NPS', 'casa-kotti-feedback' ); ?></p>
			<strong><?php echo esc_html( $metrics->total ? (string) (int) round( $metrics->nps ) : '—' ); ?></strong>
		</div>
		<div class="ckf-card">
			<p><?php esc_html_e( 'Recompra', 'casa-kotti-feedback' ); ?></p>
			<strong><?php echo esc_html( $metrics->total ? (int) round( $metrics->repurchase_pct ) . '%' : '—' ); ?></strong>
		</div>
	</div>

	<h2><?php esc_html_e( 'Por produto', 'casa-kotti-feedback' ); ?></h2>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Produto', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Avaliações', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Nota', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'NPS', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Recompra', 'casa-kotti-feedback' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $by_product as $slug => $m ) : ?>
			<tr>
				<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-feedback&product=' . rawurlencode( $slug ) ) ); ?>"><?php echo esc_html( $products[ $slug ] ); ?></a></td>
				<td><?php echo esc_html( number_format_i18n( (int) $m->total ) ); ?></td>
				<td><?php echo esc_html( $m->total ? $fmt( $m->avg_overall ) : '—' ); ?></td>
				<td><?php echo esc_html( $m->total ? (string) (int) round( $m->nps ) : '—' ); ?></td>
				<td><?php echo esc_html( $m->total ? (int) round( $m->repurchase_pct ) . '%' : '—' ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<form method="get" class="ckf-filters">
		<input type="hidden" name="page" value="casa-kotti-feedback">
		<label>
			<span><?php esc_html_e( 'Questionário', 'casa-kotti-feedback' ); ?></span>
			<select name="survey_id">
				<option value=""><?php esc_html_e( 'Todos', 'casa-kotti-feedback' ); ?></option>
				<?php foreach ( CKF_Surveys::all() as $survey_row ) : ?>
					<option value="<?php echo esc_attr( (string) (int) $survey_row->id ); ?>" <?php selected( (int) $filters['survey_id'], (int) $survey_row->id ); ?>><?php echo esc_html( $survey_row->title ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<span><?php esc_html_e( 'Produto', 'casa-kotti-feedback' ); ?></span>
			<select name="product">
				<option value=""><?php esc_html_e( 'Todos', 'casa-kotti-feedback' ); ?></option>
				<?php foreach ( $products as $slug => $label ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $filters['product'], $slug ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<span><?php esc_html_e( 'Fragrância', 'casa-kotti-feedback' ); ?></span>
			<select name="fragrance">
				<option value=""><?php esc_html_e( 'Todas', 'casa-kotti-feedback' ); ?></option>
				<?php foreach ( CKF_Database::all_fragrances() as $frag ) : ?>
					<option value="<?php echo esc_attr( $frag->slug ); ?>" <?php selected( $filters['fragrance'], $frag->slug ); ?>><?php echo esc_html( $frag->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label>
			<span><?php esc_html_e( 'Nota', 'casa-kotti-feedback' ); ?></span>
			<select name="rating">
				<option value="0"><?php esc_html_e( 'Todas', 'casa-kotti-feedback' ); ?></option>
				<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
					<option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( $filters['rating'], $i ); ?>><?php echo esc_html( (string) $i ); ?></option>
				<?php endfor; ?>
			</select>
		</label>
		<label>
			<span><?php esc_html_e( 'NPS', 'casa-kotti-feedback' ); ?></span>
			<select name="nps">
				<option value=""><?php esc_html_e( 'Todos', 'casa-kotti-feedback' ); ?></option>
				<option value="promoters" <?php selected( $filters['nps'], 'promoters' ); ?>><?php esc_html_e( 'Promotores', 'casa-kotti-feedback' ); ?></option>
				<option value="passives" <?php selected( $filters['nps'], 'passives' ); ?>><?php esc_html_e( 'Neutros', 'casa-kotti-feedback' ); ?></option>
				<option value="detractors" <?php selected( $filters['nps'], 'detractors' ); ?>><?php esc_html_e( 'Detratores', 'casa-kotti-feedback' ); ?></option>
			</select>
		</label>
		<label>
			<span><?php esc_html_e( 'De', 'casa-kotti-feedback' ); ?></span>
			<input type="date" name="from" value="<?php echo esc_attr( $filters['from'] ); ?>">
		</label>
		<label>
			<span><?php esc_html_e( 'Até', 'casa-kotti-feedback' ); ?></span>
			<input type="date" name="to" value="<?php echo esc_attr( $filters['to'] ); ?>">
		</label>
		<label>
			<span><?php esc_html_e( 'Busca', 'casa-kotti-feedback' ); ?></span>
			<input type="search" name="s" value="<?php echo esc_attr( $filters['s'] ); ?>" placeholder="<?php esc_attr_e( 'Nome ou e-mail', 'casa-kotti-feedback' ); ?>">
		</label>
		<?php submit_button( __( 'Filtrar', 'casa-kotti-feedback' ), 'secondary', '', false ); ?>
		<a class="button button-primary" href="<?php echo esc_url( $export ); ?>"><?php esc_html_e( 'Exportar CSV', 'casa-kotti-feedback' ); ?></a>
	</form>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Data', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Produto', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Fragrância', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Nota', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Intensidade', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'NPS', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Recompra', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Cliente', 'casa-kotti-feedback' ); ?></th>
				<th><?php esc_html_e( 'Ações', 'casa-kotti-feedback' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( ! $rows ) : ?>
			<tr><td colspan="9"><?php esc_html_e( 'Nenhuma avaliação encontrada.', 'casa-kotti-feedback' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $rows as $row ) : ?>
				<tr>
					<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-feedback&view=' . absint( $row->id ) ) ); ?>"><?php echo esc_html( $row->created_at ); ?></a></td>
					<td><?php echo esc_html( isset( $products[ $row->product ] ) ? $products[ $row->product ] : $row->product ); ?></td>
					<td><?php echo esc_html( $row->fragrance ); ?></td>
					<td><?php echo esc_html( (string) (int) $row->overall_rating ); ?></td>
					<td><?php echo esc_html( isset( ckf_intensity_options()[ $row->intensity ] ) ? ckf_intensity_options()[ $row->intensity ] : $row->intensity ); ?></td>
					<td><?php echo esc_html( (string) (int) $row->nps_score ); ?></td>
					<td><?php echo esc_html( isset( ckf_repurchase_options()[ $row->repurchase_intent ] ) ? ckf_repurchase_options()[ $row->repurchase_intent ] : $row->repurchase_intent ); ?></td>
					<td><?php echo esc_html( $row->customer_name ? $row->customer_name : ( $row->customer_email ? $row->customer_email : '—' ) ); ?></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-feedback&view=' . absint( $row->id ) ) ); ?>"><?php esc_html_e( 'Ver', 'casa-kotti-feedback' ); ?></a>
						|
						<a class="button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ckf_delete_feedback&id=' . absint( $row->id ) ), 'ckf_delete_feedback_' . absint( $row->id ) ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Excluir esta avaliação de forma permanente?', 'casa-kotti-feedback' ) ); ?>');"><?php esc_html_e( 'Excluir', 'casa-kotti-feedback' ); ?></a>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
	<?php
	echo wp_kses_post(
		paginate_links(
			array(
				'base'    => add_query_arg( 'paged', '%#%' ),
				'format'  => '',
				'current' => $page,
				'total'   => max( 1, (int) ceil( $total / $per_page ) ),
			)
		)
	);
	?>
</div>
