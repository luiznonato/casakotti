<?php
/**
 * Single evaluation.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ckf-admin">
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=casa-kotti-feedback' ) ); ?>"><?php esc_html_e( '← Avaliações', 'casa-kotti-feedback' ); ?></a></p>
	<h1><?php esc_html_e( 'Detalhe da avaliação', 'casa-kotti-feedback' ); ?></h1>
	<table class="widefat striped">
		<tbody>
			<tr><th><?php esc_html_e( 'Data (UTC)', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( $row->created_at ); ?></td></tr>
			<tr><th><?php esc_html_e( 'UUID', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( $row->uuid ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Produto', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( isset( $products[ $row->product ] ) ? $products[ $row->product ] : $row->product ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Fragrância', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( $row->fragrance ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Nota geral', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( (string) (int) $row->overall_rating ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Intensidade', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( isset( $intensity[ $row->intensity ] ) ? $intensity[ $row->intensity ] : $row->intensity ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Pergunta específica', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( $row->product_specific_answer ? $row->product_specific_answer : '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Performance', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( isset( $performance[ $row->performance ] ) ? $performance[ $row->performance ] : $row->performance ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Apresentação', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( (string) (int) $row->presentation_rating ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Recompra', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( isset( $repurchase[ $row->repurchase_intent ] ) ? $repurchase[ $row->repurchase_intent ] : $row->repurchase_intent ); ?></td></tr>
			<tr><th><?php esc_html_e( 'NPS', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( (string) (int) $row->nps_score ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Melhoria', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( $row->improvement_comment ? $row->improvement_comment : '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Positivo', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( $row->positive_comment ? $row->positive_comment : '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Nome', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( $row->customer_name ? $row->customer_name : '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'E-mail', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( $row->customer_email ? $row->customer_email : '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Marketing', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( $row->marketing_consent ? __( 'Sim', 'casa-kotti-feedback' ) : __( 'Não', 'casa-kotti-feedback' ) ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Origem', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( $row->source ? $row->source : '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Campanha', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( $row->campaign ? $row->campaign : '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Lote', 'casa-kotti-feedback' ); ?></th><td><?php echo esc_html( $row->batch ? $row->batch : '—' ); ?></td></tr>
		</tbody>
	</table>
</div>
