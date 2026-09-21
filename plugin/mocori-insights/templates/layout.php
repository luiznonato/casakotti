<?php
/**
 * Admin shell.
 *
 * @package Mocori_Insights
 *
 * @var string $view
 * @var array  $range
 * @var array  $totals
 * @var array  $series
 * @var array  $acq
 * @var array  $conv_break
 * @var array  $live
 * @var array  $settings
 * @var string $project
 * @var string $accent
 * @var string $export_base
 * @var Mocori_Insights_Reports $reports
 * @var Mocori_Insights_Funnel $funnels
 * @var Mocori_Insights_Plugin $this_plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nav = array(
	'mocori-insights'             => __( 'Dashboard', 'mocori-insights' ),
	'mocori-insights-visitors'    => __( 'Visitantes', 'mocori-insights' ),
	'mocori-insights-acquisition' => __( 'Aquisição', 'mocori-insights' ),
	'mocori-insights-pages'       => __( 'Páginas', 'mocori-insights' ),
	'mocori-insights-events'      => __( 'Eventos', 'mocori-insights' ),
	'mocori-insights-conversions' => __( 'Conversões', 'mocori-insights' ),
	'mocori-insights-forms'       => __( 'Formulários', 'mocori-insights' ),
	'mocori-insights-campaigns'   => __( 'Campanhas', 'mocori-insights' ),
	'mocori-insights-reports'     => __( 'Relatórios', 'mocori-insights' ),
	'mocori-insights-settings'    => __( 'Configurações', 'mocori-insights' ),
);
$current = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'mocori-insights';
$labels  = array(
	'direct'         => 'Direct',
	'organic_search' => 'Organic Search',
	'paid_search'    => 'Paid Search',
	'organic_social' => 'Organic Social',
	'paid_social'    => 'Paid Social',
	'referral'       => 'Referral',
	'email'          => 'Email',
	'whatsapp'       => 'WhatsApp',
	'other'          => 'Other',
);
?>
<div class="mi-app" style="--mi-accent: <?php echo esc_attr( $accent ); ?>">
	<aside class="mi-side">
		<div class="mi-brand">
			<span class="mi-mark">M</span>
			<div>
				<strong>Mocori Insights</strong>
				<small><?php echo esc_html( $project ? $project : __( 'Projeto', 'mocori-insights' ) ); ?></small>
			</div>
		</div>
		<nav>
			<?php foreach ( $nav as $slug => $label ) : ?>
				<a class="<?php echo $current === $slug ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug . '&range=' . rawurlencode( $range['preset'] ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
		<p class="mi-foot"><?php esc_html_e( 'Powered by Mocori', 'mocori-insights' ); ?></p>
	</aside>
	<main class="mi-main">
		<header class="mi-top">
			<div>
				<p class="mi-kicker">Mocori Insights</p>
				<h1><?php echo esc_html( $nav[ $current ] ); ?></h1>
			</div>
			<?php if ( 'settings' !== $view ) : ?>
			<form class="mi-range" method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( $current ); ?>">
				<?php
				$presets = array(
					'today'     => __( 'Hoje', 'mocori-insights' ),
					'yesterday' => __( 'Ontem', 'mocori-insights' ),
					'7d'        => __( '7 dias', 'mocori-insights' ),
					'30d'       => __( '30 dias', 'mocori-insights' ),
					'90d'       => __( '90 dias', 'mocori-insights' ),
					'custom'    => __( 'Personalizado', 'mocori-insights' ),
				);
				foreach ( $presets as $key => $label ) :
					?>
					<button class="<?php echo $range['preset'] === $key ? 'is-on' : ''; ?>" name="range" value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
				<?php if ( 'custom' === $range['preset'] ) : ?>
					<input type="date" name="from" value="<?php echo esc_attr( $range['from'] ); ?>">
					<input type="date" name="to" value="<?php echo esc_attr( $range['to'] ); ?>">
				<?php endif; ?>
			</form>
			<?php endif; ?>
		</header>

		<?php if ( 'dashboard' === $view ) : ?>
			<section class="mi-cards">
				<?php
				$cards = array(
					__( 'Visitantes', 'mocori-insights' ) => $totals['visitors'],
					__( 'Sessões', 'mocori-insights' ) => $totals['sessions'],
					__( 'Visualizações', 'mocori-insights' ) => $totals['pageviews'],
					__( 'Conversões', 'mocori-insights' ) => $totals['conversions'],
					__( 'Taxa de conversão', 'mocori-insights' ) => $totals['conversion_rate'] . '%',
					__( 'Novos visitantes', 'mocori-insights' ) => $totals['new_visitors'],
					__( 'Visitantes recorrentes', 'mocori-insights' ) => $totals['returning_visitors'],
				);
				foreach ( $cards as $label => $value ) :
					?>
					<article>
						<span><?php echo esc_html( $label ); ?></span>
						<strong><?php echo esc_html( is_numeric( $value ) ? Mocori_Insights_Admin::n( $value ) : $value ); ?></strong>
					</article>
				<?php endforeach; ?>
			</section>
			<section class="mi-grid">
				<article class="mi-panel">
					<h2><?php esc_html_e( 'Visitantes ao longo do tempo', 'mocori-insights' ); ?></h2>
					<canvas id="mi-chart" height="160" data-series="<?php echo esc_attr( wp_json_encode( $series ) ); ?>"></canvas>
				</article>
				<article class="mi-panel">
					<h2><?php esc_html_e( 'Tempo real', 'mocori-insights' ); ?></h2>
					<p class="mi-live"><strong id="mi-active"><?php echo esc_html( Mocori_Insights_Admin::n( $live['active'] ) ); ?></strong> <?php esc_html_e( 'visitantes agora', 'mocori-insights' ); ?></p>
					<table class="mi-table" id="mi-live-pages">
						<thead><tr><th><?php esc_html_e( 'Página', 'mocori-insights' ); ?></th><th><?php esc_html_e( 'Visitantes', 'mocori-insights' ); ?></th></tr></thead>
						<tbody>
						<?php foreach ( $live['pages'] as $row ) : ?>
							<tr><td><?php echo esc_html( $row['page_url'] ); ?></td><td><?php echo esc_html( $row['visitors'] ); ?></td></tr>
						<?php endforeach; ?>
						<?php if ( ! $live['pages'] ) : ?>
							<tr><td colspan="2"><?php esc_html_e( 'Nenhum visitante ativo nos últimos 5 minutos.', 'mocori-insights' ); ?></td></tr>
						<?php endif; ?>
						</tbody>
					</table>
				</article>
				<article class="mi-panel">
					<h2><?php esc_html_e( 'Aquisição', 'mocori-insights' ); ?></h2>
					<ul class="mi-bars">
						<?php
						$max = 0;
						foreach ( $acq as $row ) {
							$max = max( $max, (int) $row['sessions'] );
						}
						foreach ( $acq as $row ) :
							$w = $max ? round( ( (int) $row['sessions'] / $max ) * 100 ) : 0;
							$lab = isset( $labels[ $row['channel'] ] ) ? $labels[ $row['channel'] ] : $row['channel'];
							?>
							<li>
								<span><?php echo esc_html( $lab ); ?></span>
								<i style="width:<?php echo esc_attr( $w ); ?>%"></i>
								<em><?php echo esc_html( $row['sessions'] ); ?></em>
							</li>
						<?php endforeach; ?>
						<?php if ( ! $acq ) : ?>
							<li><?php esc_html_e( 'Sem dados no período.', 'mocori-insights' ); ?></li>
						<?php endif; ?>
					</ul>
				</article>
				<article class="mi-panel">
					<h2><?php esc_html_e( 'Conversões', 'mocori-insights' ); ?></h2>
					<table class="mi-table">
						<tbody>
						<?php foreach ( $conv_break as $row ) : ?>
							<tr><td><?php echo esc_html( $row['conversion_name'] ); ?></td><td><?php echo esc_html( $row['total'] ); ?></td></tr>
						<?php endforeach; ?>
						<?php if ( ! $conv_break ) : ?>
							<tr><td><?php esc_html_e( 'Nenhuma conversão no período.', 'mocori-insights' ); ?></td></tr>
						<?php endif; ?>
						</tbody>
					</table>
				</article>
			</section>
		<?php endif; ?>

		<?php
		if ( in_array( $view, array( 'visitors', 'events', 'conversions', 'pages', 'campaigns', 'forms', 'acquisition' ), true ) ) {
			$table_rows = array();
			$headers    = array();
			if ( 'visitors' === $view ) {
				$headers    = array( 'visitor_uuid', 'first_seen', 'last_seen', 'first_channel', 'last_channel', 'first_landing_page', 'device_type', 'os', 'browser' );
				$table_rows = $reports->visitors( $range, 200 );
			} elseif ( 'events' === $view ) {
				$headers    = array( 'created_at', 'event_name', 'page_url', 'metadata' );
				$table_rows = $reports->events( $range, 300 );
			} elseif ( 'conversions' === $view ) {
				$headers    = array( 'created_at', 'conversion_name', 'conversion_type', 'first_channel', 'last_channel', 'reference_id', 'value' );
				$table_rows = $reports->conversions( $range, 300 );
			} elseif ( 'pages' === $view ) {
				$headers    = array( 'page_url', 'pageviews', 'visitors', 'entries', 'exits', 'conversions' );
				$table_rows = $reports->pages( $range );
			} elseif ( 'campaigns' === $view ) {
				$headers    = array( 'campaign', 'source', 'medium', 'visitors', 'sessions', 'conversions', 'conversion_rate' );
				$table_rows = $reports->campaigns( $range );
			} elseif ( 'forms' === $view ) {
				$headers    = array( 'form_id', 'form_name', 'views', 'starts', 'submits' );
				$table_rows = $reports->forms( $range );
			} elseif ( 'acquisition' === $view ) {
				$headers    = array( 'channel', 'visitors', 'sessions' );
				$table_rows = $acq;
			}
			$export_type = 'acquisition' === $view ? 'campaigns' : $view;
			?>
			<p class="mi-export"><a class="mi-btn" href="<?php echo esc_url( $export_base . '&type=' . rawurlencode( $export_type ) ); ?>"><?php esc_html_e( 'Exportar CSV', 'mocori-insights' ); ?></a></p>
			<table class="mi-table mi-wide">
				<thead><tr><?php foreach ( $headers as $h ) : ?><th><?php echo esc_html( $h ); ?></th><?php endforeach; ?></tr></thead>
				<tbody>
				<?php if ( ! $table_rows ) : ?>
					<tr><td colspan="<?php echo esc_attr( count( $headers ) ); ?>"><?php esc_html_e( 'Sem dados no período.', 'mocori-insights' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $table_rows as $row ) : ?>
						<tr>
							<?php foreach ( $headers as $h ) : ?>
								<td><?php echo esc_html( isset( $row[ $h ] ) ? ( is_array( $row[ $h ] ) ? wp_json_encode( $row[ $h ] ) : $row[ $h ] ) : '' ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
			<?php
		}
		?>

		<?php if ( 'reports' === $view ) : ?>
			<section class="mi-grid">
				<article class="mi-panel">
					<h2><?php esc_html_e( 'Exportações', 'mocori-insights' ); ?></h2>
					<ul class="mi-links">
						<?php foreach ( array( 'visitors', 'sessions', 'events', 'conversions', 'campaigns', 'pages' ) as $type ) : ?>
							<li><a href="<?php echo esc_url( $export_base . '&type=' . $type ); ?>"><?php echo esc_html( ucfirst( $type ) ); ?> CSV</a></li>
						<?php endforeach; ?>
					</ul>
				</article>
				<article class="mi-panel">
					<h2><?php esc_html_e( 'Funis', 'mocori-insights' ); ?></h2>
					<p><?php esc_html_e( 'Arquitetura genérica pronta. Defina etapas por evento; nenhum funil de cliente vem pré-carregado.', 'mocori-insights' ); ?></p>
					<?php
					$all_funnels = $funnels->all();
					if ( ! $all_funnels ) :
						?>
						<p><?php esc_html_e( 'Nenhum funil configurado ainda.', 'mocori-insights' ); ?></p>
					<?php else : ?>
						<?php foreach ( $all_funnels as $funnel ) : ?>
							<h3><?php echo esc_html( $funnel['funnel_name'] ); ?></h3>
							<ol>
							<?php foreach ( $funnels->compute( $funnel, $range ) as $step ) : ?>
								<li><?php echo esc_html( $step['label'] ); ?> — <?php echo esc_html( $step['visitors'] ); ?></li>
							<?php endforeach; ?>
							</ol>
						<?php endforeach; ?>
					<?php endif; ?>
				</article>
			</section>
		<?php endif; ?>

		<?php if ( 'settings' === $view ) : ?>
			<?php if ( ! empty( $_GET['updated'] ) ) : ?>
				<p class="mi-ok"><?php esc_html_e( 'Configurações salvas.', 'mocori-insights' ); ?></p>
			<?php endif; ?>
			<form method="post" class="mi-form">
				<?php wp_nonce_field( 'mocori_insights_settings', 'mocori_insights_settings_nonce' ); ?>
				<fieldset>
					<legend><?php esc_html_e( 'Projeto', 'mocori-insights' ); ?></legend>
					<label><?php esc_html_e( 'Nome do projeto', 'mocori-insights' ); ?><input name="project_name" value="<?php echo esc_attr( $settings['project_name'] ); ?>"></label>
					<label><?php esc_html_e( 'Domínio', 'mocori-insights' ); ?><input name="domain" value="<?php echo esc_attr( $settings['domain'] ); ?>"></label>
					<label><?php esc_html_e( 'Timezone', 'mocori-insights' ); ?><input name="timezone" value="<?php echo esc_attr( $settings['timezone'] ); ?>"></label>
					<label><?php esc_html_e( 'Moeda', 'mocori-insights' ); ?><input name="currency" value="<?php echo esc_attr( $settings['currency'] ); ?>"></label>
					<label><?php esc_html_e( 'Logo (URL)', 'mocori-insights' ); ?><input name="logo" value="<?php echo esc_attr( $settings['logo'] ); ?>"></label>
					<label><?php esc_html_e( 'Cor de destaque', 'mocori-insights' ); ?><input name="accent_color" type="color" value="<?php echo esc_attr( $settings['accent_color'] ); ?>"></label>
					<label><?php esc_html_e( 'Retenção (dias)', 'mocori-insights' ); ?>
						<select name="retention_days">
							<?php foreach ( array( '30', '90', '180', '365' ) as $d ) : ?>
								<option value="<?php echo esc_attr( $d ); ?>" <?php selected( (string) $settings['retention_days'], $d ); ?>><?php echo esc_html( $d ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</fieldset>
				<fieldset>
					<legend><?php esc_html_e( 'Privacidade', 'mocori-insights' ); ?></legend>
					<label><input type="checkbox" name="tracking_enabled" value="1" <?php checked( $settings['tracking_enabled'], '1' ); ?>> <?php esc_html_e( 'Coleta ativa', 'mocori-insights' ); ?></label>
					<label><?php esc_html_e( 'Modo de analytics', 'mocori-insights' ); ?>
						<select name="consent_mode">
							<option value="cookies" <?php selected( $settings['consent_mode'], 'cookies' ); ?>><?php esc_html_e( 'Executar com cookies', 'mocori-insights' ); ?></option>
							<option value="cookieless" <?php selected( $settings['consent_mode'], 'cookieless' ); ?>><?php esc_html_e( 'Executar analytics sem cookies', 'mocori-insights' ); ?></option>
							<option value="after_consent" <?php selected( $settings['consent_mode'], 'after_consent' ); ?>><?php esc_html_e( 'Executar somente após consentimento', 'mocori-insights' ); ?></option>
						</select>
					</label>
					<label><input type="checkbox" name="require_consent" value="1" <?php checked( $settings['require_consent'], '1' ); ?>> <?php esc_html_e( 'Exigir MocoriInsights.consent(true)', 'mocori-insights' ); ?></label>
					<label><input type="checkbox" name="cmp_integration" value="1" <?php checked( $settings['cmp_integration'], '1' ); ?>> <?php esc_html_e( 'Integrar com sistema de consentimento (CMP)', 'mocori-insights' ); ?></label>
				</fieldset>
				<fieldset>
					<legend><?php esc_html_e( 'Licença (futuro)', 'mocori-insights' ); ?></legend>
					<label><?php esc_html_e( 'License key', 'mocori-insights' ); ?><input name="license_key" value="<?php echo esc_attr( $settings['license_key'] ); ?>"></label>
					<p><?php echo esc_html( sprintf( __( 'Status: %1$s · site_uuid: %2$s · installation_id: %3$s', 'mocori-insights' ), $settings['license_status'], $settings['site_uuid'], $settings['installation_id'] ) ); ?></p>
				</fieldset>
				<fieldset>
					<legend><?php esc_html_e( 'Nova conversão', 'mocori-insights' ); ?></legend>
					<label><?php esc_html_e( 'Nome', 'mocori-insights' ); ?><input name="mi_conversion_name" placeholder="<?php esc_attr_e( 'Solicitação de contato', 'mocori-insights' ); ?>"></label>
					<label><?php esc_html_e( 'Evento', 'mocori-insights' ); ?><input name="mi_event_name" value="form_submit"></label>
					<label><?php esc_html_e( 'Match key', 'mocori-insights' ); ?><input name="mi_match_key" value="form_id"></label>
					<label><?php esc_html_e( 'Match value / Form ID', 'mocori-insights' ); ?><input name="mi_match_value"></label>
					<label><?php esc_html_e( 'Tipo', 'mocori-insights' ); ?><input name="mi_conversion_type" value="lead"></label>
				</fieldset>
				<button class="mi-btn" type="submit"><?php esc_html_e( 'Salvar', 'mocori-insights' ); ?></button>
			</form>
			<h2><?php esc_html_e( 'Conversões ativas', 'mocori-insights' ); ?></h2>
			<table class="mi-table">
				<thead><tr><th>Nome</th><th>Evento</th><th>Match</th><th>Tipo</th></tr></thead>
				<tbody>
				<?php foreach ( mocori_insights()->settings->conversions() as $def ) : ?>
					<tr>
						<td><?php echo esc_html( $def['conversion_name'] ); ?></td>
						<td><?php echo esc_html( $def['event_name'] ); ?></td>
						<td><?php echo esc_html( $def['match_key'] . '=' . $def['match_value'] ); ?></td>
						<td><?php echo esc_html( $def['conversion_type'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p>
				<a class="mi-danger" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mocori_insights_wipe' ), 'mocori_insights_wipe' ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Apagar todos os dados de analytics desta instalação?', 'mocori-insights' ) ); ?>');"><?php esc_html_e( 'Apagar dados de analytics', 'mocori-insights' ); ?></a>
			</p>
		<?php endif; ?>
	</main>
</div>
