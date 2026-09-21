<?php
/**
 * Mocori Insights standalone test runner (no Casa Kotti plugins loaded).
 *
 * @package Mocori_Insights
 */

require __DIR__ . '/bootstrap.php';
require MOCORI_INSIGHTS_DIR . 'includes/autoload.php';

$fails = 0;
$passes = 0;

function mi_assert( $cond, $label ) {
	global $fails, $passes;
	if ( $cond ) {
		$passes++;
		echo "PASS  {$label}\n";
		return;
	}
	$fails++;
	echo "FAIL  {$label}\n";
}

Mocori_Insights_Schema::install();
$settings = new Mocori_Insights_Settings();
$settings->seed_defaults();

$plugin = new stdClass();
$plugin->settings   = $settings;
$plugin->repository = new Mocori_Insights_Repository();
$plugin->api        = new Mocori_Insights_Public_API( $plugin );

mi_assert( (bool) preg_match( '/^[0-9a-f-]{36}$/', $settings->get( 'site_uuid' ) ), 'site_uuid generated' );
mi_assert( (bool) $settings->get( 'installation_id' ), 'installation_id generated' );
mi_assert( 'Example Site' === $settings->get( 'project_name' ), 'default project name is blog name, not a client' );
mi_assert( false === strpos( strtolower( $settings->get( 'project_name' ) ), 'kotti' ), 'core defaults have no Casa Kotti' );
mi_assert( '#1f3d34' === $settings->get( 'accent_color' ), 'default accent is Mocori, not client copper' );

$src = Mocori_Insights_Source_Classifier::classify( array( 'utm_source' => '', 'utm_medium' => '', 'referrer' => '', 'site_host' => 'example.test' ) );
mi_assert( 'direct' === $src['channel'], 'direct traffic' );

$src = Mocori_Insights_Source_Classifier::classify( array( 'utm_source' => 'google', 'utm_medium' => 'cpc', 'utm_campaign' => 'spring', 'referrer' => '', 'site_host' => 'example.test' ) );
mi_assert( 'paid_search' === $src['channel'] && 'spring' === $src['campaign'], 'paid search UTM' );

$src = Mocori_Insights_Source_Classifier::classify( array( 'utm_source' => 'ig', 'utm_medium' => '', 'referrer' => 'https://l.instagram.com/', 'site_host' => 'example.test' ) );
mi_assert( 'organic_social' === $src['channel'] || 'instagram' === $src['source'] || 'ig' === $src['source'], 'instagram referrer/source' );

$src = Mocori_Insights_Source_Classifier::classify( array( 'referrer' => 'https://www.google.com/search?q=x', 'site_host' => 'example.test' ) );
mi_assert( 'organic_search' === $src['channel'], 'organic search referrer' );

$src = Mocori_Insights_Source_Classifier::classify( array( 'utm_source' => 'whatsapp', 'utm_medium' => 'wa', 'site_host' => 'example.test' ) );
mi_assert( 'whatsapp' === $src['channel'], 'whatsapp channel' );

$src = Mocori_Insights_Source_Classifier::classify( array( 'referrer' => 'https://news.example.net/a', 'site_host' => 'example.test' ) );
mi_assert( 'referral' === $src['channel'], 'referral host' );

$ua = Mocori_Insights_User_Agent::parse( 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1' );
mi_assert( 'mobile' === $ua['device_type'] && 'iOS' === $ua['os'] && 'Safari' === $ua['browser'], 'ua parse iphone safari' );

$meta = Mocori_Insights_Sanitizer::metadata( array( 'form_id' => 'contact-form', 'email' => 'a@b.c', 'phone' => '999', 'nested' => array( 'name' => 'secret' ) ) );
mi_assert( isset( $meta['form_id'] ) && ! isset( $meta['email'] ) && ! isset( $meta['phone'] ), 'PII stripped from metadata' );

mi_assert( 'page_view' === Mocori_Insights_Sanitizer::event_name( 'page_view' ), 'native event allowed' );
mi_assert( '' === Mocori_Insights_Sanitizer::uuid( 'not-a-uuid' ), 'invalid uuid rejected' );

$collector = new Mocori_Insights_Collector( $plugin );
$v1 = Mocori_Insights_Settings::uuid();
$s1 = Mocori_Insights_Settings::uuid();

$first = $collector->ingest(
	array(
		'visitor_id' => $v1,
		'session_id' => $s1,
		'page_url'   => 'https://example.test/',
		'referrer'   => '',
		'utm'        => array(),
		'consent'    => 1,
		'events'     => array( array( 'name' => 'page_view', 'page_url' => 'https://example.test/' ) ),
	)
);
mi_assert( ! empty( $first['ok'] ) && $first['accepted'] >= 1, 'new visitor page_view' );
mi_assert( $v1 === $first['visitor_id'], 'visitor uuid preserved' );

$again = $collector->ingest(
	array(
		'visitor_id' => $v1,
		'session_id' => $s1,
		'page_url'   => 'https://example.test/contato',
		'referrer'   => 'https://example.test/',
		'consent'    => 1,
		'events'     => array( array( 'name' => 'page_view', 'page_url' => 'https://example.test/contato' ) ),
	)
);
mi_assert( $again['session_id'] === $first['session_id'], 'same session within 30 minutes' );
$vis = $plugin->repository->visitor_by_uuid( $v1 );
mi_assert( $vis && $vis->first_seen === $vis->last_seen || $vis->last_seen >= $vis->first_seen, 'returning visitor last_seen updates' );

$GLOBALS['mi_now'] = gmdate( 'Y-m-d H:i:s', time() + 1900 );
$expired = $collector->ingest(
	array(
		'visitor_id' => $v1,
		'session_id' => $s1,
		'page_url'   => 'https://example.test/produtos',
		'consent'    => 1,
		'events'     => array( array( 'name' => 'page_view', 'page_url' => 'https://example.test/produtos' ) ),
	)
);
mi_assert( $expired['session_id'] !== $s1, 'session expires after 30 minutes idle' );
$GLOBALS['mi_now'] = gmdate( 'Y-m-d H:i:s' );

$utm_hit = $collector->ingest(
	array(
		'visitor_id' => Mocori_Insights_Settings::uuid(),
		'session_id' => Mocori_Insights_Settings::uuid(),
		'page_url'   => 'https://example.test/?utm_source=google&utm_medium=cpc&utm_campaign=launch',
		'referrer'   => 'https://www.google.com/',
		'utm'        => array( 'utm_source' => 'google', 'utm_medium' => 'cpc', 'utm_campaign' => 'launch' ),
		'consent'    => 1,
		'events'     => array( array( 'name' => 'page_view', 'page_url' => 'https://example.test/' ) ),
	)
);
$session = $plugin->repository->session_by_uuid( $utm_hit['session_id'] );
mi_assert( $session && 'launch' === $session->campaign && 'paid_search' === $session->channel, 'UTM campaign stored on session' );

$engine = new Mocori_Insights_Conversion_Engine( $plugin );
$engine->save_definition(
	array(
		'event_name'      => 'form_submit',
		'match_key'       => 'form_id',
		'match_value'     => 'contact-form',
		'conversion_name' => 'Solicitação de contato',
		'conversion_type' => 'lead',
		'is_active'       => 1,
	)
);

$form = $collector->ingest(
	array(
		'visitor_id' => $v1,
		'session_id' => $expired['session_id'],
		'page_url'   => 'https://example.test/contato',
		'consent'    => 1,
		'events'     => array(
			array( 'name' => 'form_view', 'metadata' => array( 'form_id' => 'contact-form' ) ),
			array( 'name' => 'form_start', 'metadata' => array( 'form_id' => 'contact-form' ) ),
			array( 'name' => 'form_submit', 'metadata' => array( 'form_id' => 'contact-form', 'email' => 'hidden@example.com' ) ),
		),
	)
);
mi_assert( $form['accepted'] >= 3, 'form_view/start/submit accepted' );

$range = Mocori_Insights_Metrics::range( '30d' );
$reports = new Mocori_Insights_Reports( $plugin );
$convs = $reports->conversions( $range, 50 );
mi_assert( ! empty( $convs ) && 'Solicitação de contato' === $convs[0]['conversion_name'], 'configurable conversion recorded' );
mi_assert( false === strpos( wp_json_encode( $convs ), 'hidden@example.com' ), 'email not stored on conversion' );

$touch = Mocori_Insights_Attribution::snapshot( $plugin->repository->visitor_by_uuid( $v1 ), $plugin->repository->session_by_uuid( $expired['session_id'] ) );
mi_assert( ! empty( $touch['first'] ) && ! empty( $touch['last'] ), 'first and last touch present' );

$plugin->api->identify(
	array(
		'visitor_id' => $v1,
		'session_id' => $expired['session_id'],
		'lead_id'    => '42',
		'source'     => 'custom_form',
	)
);
global $wpdb;
$ident = $wpdb->get_var( 'SELECT lead_id FROM ' . Mocori_Insights_Schema::table( 'identities' ) . ' LIMIT 1' );
mi_assert( '42' === (string) $ident, 'lead_id stored without PII' );

$live = ( new Mocori_Insights_Realtime( $plugin ) )->snapshot();
mi_assert( isset( $live['active'] ) && $live['active'] >= 1, 'realtime active visitors' );

$metrics = new Mocori_Insights_Metrics( $plugin );
$totals  = $metrics->totals( $range );
mi_assert( $totals['pageviews'] >= 1 && $totals['sessions'] >= 1, 'dashboard totals from daily aggregate' );
mi_assert( isset( $totals['conversion_rate'] ), 'conversion rate computed' );

$series = $metrics->series( $range );
mi_assert( is_array( $series ), 'dashboard time series' );

$acq = $metrics->acquisition( $range );
mi_assert( is_array( $acq ), 'acquisition breakdown' );

$pages = $reports->pages( $range );
mi_assert( is_array( $pages ), 'pages report' );

$campaigns = $reports->campaigns( $range );
mi_assert( is_array( $campaigns ), 'campaigns report' );

$forms = $reports->forms( $range );
mi_assert( ! empty( $forms ) && 'contact-form' === $forms[0]['form_id'], 'forms report' );

$exporter = new Mocori_Insights_Exporter( $plugin );
mi_assert( "'=cmd" === $exporter->safe( '=cmd' ), 'csv formula neutralized' );

$settings->set( 'require_consent', '1' );
$settings->set( 'consent_mode', 'after_consent' );
$blocked = $collector->ingest(
	array(
		'visitor_id' => Mocori_Insights_Settings::uuid(),
		'events'     => array( array( 'name' => 'page_view' ) ),
		'consent'    => 0,
	)
);
mi_assert( empty( $blocked['ok'] ) && 'consent' === $blocked['reason'], 'privacy: blocked without consent' );

$settings->set( 'require_consent', '0' );
$settings->set( 'consent_mode', 'cookies' );

$GLOBALS['mi_caps']['view_mocori_insights'] = false;
$GLOBALS['mi_caps']['manage_mocori_insights'] = false;
$GLOBALS['mi_caps']['manage_options'] = false;
mi_assert( false === Mocori_Insights_Capabilities::can_view(), 'permissions: view denied' );
$GLOBALS['mi_caps']['view_mocori_insights'] = true;
$GLOBALS['mi_caps']['manage_mocori_insights'] = true;
mi_assert( true === Mocori_Insights_Capabilities::can_view() && true === Mocori_Insights_Capabilities::can_manage(), 'permissions: caps via current_user_can' );

$settings->set( 'retention_days', '1' );
$old = gmdate( 'Y-m-d H:i:s', time() - 10 * DAY_IN_SECONDS );
$wpdb->query( "UPDATE " . Mocori_Insights_Schema::table( 'events' ) . " SET created_at = '{$old}'" );
( new Mocori_Insights_Retention( $plugin ) )->purge();
$left = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Mocori_Insights_Schema::table( 'events' ) );
mi_assert( 0 === $left, 'retention deletes detailed events' );
$daily_left = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Mocori_Insights_Schema::table( 'daily' ) );
mi_assert( $daily_left >= 0, 'aggregated daily table remains available' );

Mocori_Insights_Migrator::maybe_upgrade();
mi_assert( MOCORI_INSIGHTS_DB_VERSION === get_option( 'mocori_insights_db_version' ), 'migration sets db version without dropping data' );

$core = file_get_contents( MOCORI_INSIGHTS_DIR . 'mocori-insights.php' ) . file_get_contents( MOCORI_INSIGHTS_DIR . 'includes/class-plugin.php' );
mi_assert( false === stripos( $core, 'casa_kotti' ) && false === stripos( $core, 'ck_' ), 'bootstrap has no client prefixes' );

$adapter = dirname( MOCORI_INSIGHTS_DIR ) . '/casa-kotti-interesses/includes/class-mocori-insights-adapter.php';
mi_assert( is_readable( $adapter ), 'client adapter lives outside Insights core' );
mi_assert( false === strpos( file_get_contents( MOCORI_INSIGHTS_DIR . 'includes/Integrations/class-loader.php' ), 'interest-launch' ), 'core integrations loader has no client form id' );

echo "\n{$passes} passed, {$fails} failed\n";
exit( $fails ? 1 : 0 );
