<?php
/**
 * Plugin Name: Casa Kotti — Interesses
 * Description: Captação e gestão dos interessados no lançamento da Casa Kotti.
 * Version: 1.0.5
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Casa Kotti
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: casa-kotti-interesses
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CKI_VERSION', '1.0.5' );
define( 'CKI_FILE', __FILE__ );

/**
 * Create/update the dedicated subscriber table.
 */
function cki_activate() {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table   = $wpdb->prefix . 'casa_kotti_interests';
	$charset = $wpdb->get_charset_collate();
	$sql     = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		email varchar(190) NOT NULL,
		consent_text text NOT NULL,
		consent_version varchar(64) NOT NULL,
		source varchar(255) NOT NULL,
		device varchar(32) NOT NULL DEFAULT '',
		client varchar(80) NOT NULL DEFAULT '',
		locale varchar(32) NOT NULL DEFAULT '',
		viewport varchar(20) NOT NULL DEFAULT '',
		campaign varchar(120) NOT NULL DEFAULT '',
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY email (email)
	) {$charset};";

	dbDelta( $sql );
	update_option( 'cki_db_version', CKI_VERSION, false );
}
register_activation_hook( __FILE__, 'cki_activate' );

function cki_maybe_upgrade() {
	if ( get_option( 'cki_db_version' ) === CKI_VERSION ) {
		return;
	}
	cki_activate();
}
add_action( 'plugins_loaded', 'cki_maybe_upgrade' );

function cki_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'casa_kotti_interests';
}

function cki_has_valid_privacy_page() {
	$page_id = absint( get_option( 'wp_page_for_privacy_policy', 0 ) );
	return $page_id > 0 && 'publish' === get_post_status( $page_id );
}

/**
 * Derive a coarse device/browser label from the request User-Agent.
 *
 * @param string $ua User-Agent string.
 * @return array{device:string,client:string}
 */
function cki_parse_client( $ua ) {
	$ua = (string) $ua;

	$device = 'computador';
	if ( preg_match( '/iPad|Tablet|PlayBook/i', $ua ) ) {
		$device = 'tablet';
	} elseif ( preg_match( '/Mobi|iPhone|Android.+Mobile|iPod/i', $ua ) ) {
		$device = 'celular';
	} elseif ( preg_match( '/Android/i', $ua ) ) {
		$device = 'tablet';
	}

	$os = '';
	if ( preg_match( '/iPhone|iPad|iPod/i', $ua ) ) {
		$os = 'iOS';
	} elseif ( preg_match( '/Android/i', $ua ) ) {
		$os = 'Android';
	} elseif ( preg_match( '/Mac OS X/i', $ua ) ) {
		$os = 'macOS';
	} elseif ( preg_match( '/Windows/i', $ua ) ) {
		$os = 'Windows';
	} elseif ( preg_match( '/Linux/i', $ua ) ) {
		$os = 'Linux';
	}

	$browser = '';
	if ( preg_match( '/Edg\//i', $ua ) || preg_match( '/EdgiOS/i', $ua ) ) {
		$browser = 'Edge';
	} elseif ( preg_match( '/OPR\/|Opera/i', $ua ) ) {
		$browser = 'Opera';
	} elseif ( preg_match( '/CriOS|Chrome\//i', $ua ) ) {
		$browser = 'Chrome';
	} elseif ( preg_match( '/FxiOS|Firefox\//i', $ua ) ) {
		$browser = 'Firefox';
	} elseif ( preg_match( '/Safari\//i', $ua ) ) {
		$browser = 'Safari';
	}

	$client = trim( $os . ( $os && $browser ? ' · ' : '' ) . $browser );
	return array(
		'device' => $device,
		'client' => substr( $client, 0, 80 ),
	);
}

function cki_request_locale() {
	if ( isset( $_POST['locale'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Validated after nonce in caller.
		$posted = sanitize_text_field( wp_unslash( $_POST['locale'] ) );
		if ( preg_match( '/^[A-Za-z]{2,3}(-[A-Za-z0-9]{2,8})?$/', $posted ) ) {
			return substr( $posted, 0, 32 );
		}
	}

	$accept = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) : '';
	if ( preg_match( '/^[A-Za-z]{2,3}(-[A-Za-z0-9]{2,8})?/', $accept, $match ) ) {
		return substr( $match[0], 0, 32 );
	}

	return '';
}

function cki_request_viewport() {
	if ( ! isset( $_POST['viewport'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Validated after nonce in caller.
		return '';
	}
	$value = sanitize_text_field( wp_unslash( $_POST['viewport'] ) );
	return preg_match( '/^\d{2,5}x\d{2,5}$/', $value ) ? $value : '';
}

function cki_request_campaign() {
	$raw = '';
	if ( isset( $_POST['utm'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Validated after nonce in caller.
		$raw = sanitize_text_field( wp_unslash( $_POST['utm'] ) );
	}
	$raw = ltrim( $raw, '?' );
	if ( '' === $raw ) {
		return '';
	}

	parse_str( $raw, $query );
	$parts = array();
	foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign' ) as $key ) {
		if ( ! empty( $query[ $key ] ) ) {
			$parts[] = $key . '=' . sanitize_text_field( (string) $query[ $key ] );
		}
	}

	return substr( implode( '&', $parts ), 0, 120 );
}

function cki_format_signup_datetime( $gmt ) {
	$local = get_date_from_gmt( (string) $gmt, 'Y-m-d H:i' );
	return $local ? $local : (string) $gmt;
}

/**
 * Render a pre-launch interest form.
 *
 * @param array $args Copy supplied by the theme/shortcode.
 * @return string
 */
function casa_kotti_render_interest_form( $args = array() ) {
	if ( ! cki_has_valid_privacy_page() ) {
		return '';
	}

	$args = wp_parse_args( $args, array(
		'label'   => 'Seu e-mail',
		'button'  => 'Quero conhecer primeiro',
		'consent' => 'Aceito receber novidades sobre o lançamento da Casa Kotti por e-mail.',
		'success' => 'Pronto! Vamos avisar você quando a Casa Kotti chegar.',
	) );
	$args = array_map( 'sanitize_text_field', $args );

	$form_id = wp_unique_id( 'ck-interest-form-' );
	$consent_signature = hash_hmac( 'sha256', $args['consent'], wp_salt( 'auth' ) );
	ob_start();
	?>
	<form class="ck-form" id="<?php echo esc_attr( $form_id ); ?>" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" data-success="<?php echo esc_attr( $args['success'] ); ?>" novalidate>
		<div class="ck-form__row">
			<label>
				<span class="ck-form__label"><?php echo esc_html( $args['label'] ); ?></span>
				<input type="text" name="email" inputmode="email" autocomplete="email" autocapitalize="none" autocorrect="off" spellcheck="false" aria-required="true" maxlength="190" placeholder="<?php echo esc_attr( $args['label'] ); ?>">
			</label>
			<button type="submit"><?php echo esc_html( $args['button'] ); ?></button>
		</div>
		<label class="ck-form__consent">
			<input type="checkbox" name="consent" value="1" aria-required="true">
			<span><?php echo esc_html( $args['consent'] ); ?></span>
		</label>
		<p class="ck-form__consent-error" hidden role="alert"><?php esc_html_e( 'É necessário aceitar para receber novidades.', 'casa-kotti-interesses' ); ?></p>
		<div class="ck-honeypot" aria-hidden="true">
			<label>Não preencha este campo <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
		</div>
		<input type="hidden" name="action" value="cki_register_interest">
		<input type="hidden" name="consent_text" value="<?php echo esc_attr( $args['consent'] ); ?>">
		<input type="hidden" name="consent_signature" value="<?php echo esc_attr( $consent_signature ); ?>">
		<?php wp_nonce_field( 'cki_register_interest', 'nonce' ); ?>
		<p class="ck-form__status" role="status" aria-live="polite" aria-atomic="true"></p>
	</form>
	<?php
	return ob_get_clean();
}

function cki_shortcode( $atts ) {
	return casa_kotti_render_interest_form( shortcode_atts( array(
		'label'   => 'Seu e-mail',
		'button'  => 'Quero conhecer primeiro',
		'consent' => 'Aceito receber novidades sobre o lançamento da Casa Kotti por e-mail.',
		'success' => 'Pronto! Vamos avisar você quando a Casa Kotti chegar.',
	), $atts, 'casa_kotti_interest_form' ) );
}
add_shortcode( 'casa_kotti_interest_form', 'cki_shortcode' );

function cki_enqueue_assets() {
	if ( ! is_front_page() ) {
		return;
	}

	wp_enqueue_script(
		'casa-kotti-interests',
		plugins_url( 'assets/form.js', __FILE__ ),
		array(),
		CKI_VERSION,
		true
	);
	wp_localize_script( 'casa-kotti-interests', 'ckiForm', array(
		'sending'        => __( 'Enviando…', 'casa-kotti-interesses' ),
		'invalidEmail'   => __( 'Digite um e-mail válido.', 'casa-kotti-interesses' ),
		'consentNeeded'  => __( 'É necessário aceitar para receber novidades.', 'casa-kotti-interesses' ),
		'genericError'   => __( 'Não foi possível cadastrar agora. Tente novamente.', 'casa-kotti-interesses' ),
	) );
}
add_action( 'wp_enqueue_scripts', 'cki_enqueue_assets' );

/**
 * Enforce a small rolling limit without retaining the visitor IP.
 *
 * @return bool True when the submission may continue.
 */
function cki_rate_limit_allows() {
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$key = 'cki_rate_' . hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) );
	$num = (int) get_transient( $key );

	if ( $num >= 5 ) {
		return false;
	}

	set_transient( $key, $num + 1, HOUR_IN_SECONDS );
	return true;
}

function cki_register_interest() {
	if ( ! check_ajax_referer( 'cki_register_interest', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Atualize a página e tente novamente.', 'casa-kotti-interesses' ) ), 403 );
	}

	if ( ! cki_has_valid_privacy_page() ) {
		wp_send_json_error( array( 'message' => __( 'O cadastro está temporariamente indisponível.', 'casa-kotti-interesses' ) ), 400 );
	}

	if ( ! empty( $_POST['website'] ) || ! cki_rate_limit_allows() ) {
		wp_send_json_error( array( 'message' => __( 'Não foi possível cadastrar agora. Tente novamente.', 'casa-kotti-interesses' ) ), 429 );
	}

	$email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$has_consent  = isset( $_POST['consent'] ) && '1' === $_POST['consent'];
	$consent_text = isset( $_POST['consent_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['consent_text'] ) ) : '';
	$signature    = isset( $_POST['consent_signature'] ) ? sanitize_text_field( wp_unslash( $_POST['consent_signature'] ) ) : '';
	$expected_signature = hash_hmac( 'sha256', $consent_text, wp_salt( 'auth' ) );

	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Digite um e-mail válido.', 'casa-kotti-interesses' ) ), 400 );
	}

	if ( ! $has_consent || '' === $consent_text || ! hash_equals( $expected_signature, $signature ) ) {
		wp_send_json_error( array( 'message' => __( 'Confirme o consentimento para continuar.', 'casa-kotti-interesses' ) ), 400 );
	}

	$referer = wp_get_referer();
	$source  = $referer ? wp_parse_url( $referer, PHP_URL_PATH ) : '/';
	$source  = substr( sanitize_text_field( (string) $source ), 0, 255 );
	$ua      = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	$client  = cki_parse_client( $ua );

	global $wpdb;
	$table    = cki_table_name();
	$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE email = %s LIMIT 1", $email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( $existing ) {
		wp_send_json_success();
	}

	$inserted = $wpdb->insert(
		$table,
		array(
			'email'           => $email,
			'consent_text'    => $consent_text,
			'consent_version' => hash( 'sha256', $consent_text ),
			'source'          => $source ? $source : '/',
			'device'          => $client['device'],
			'client'          => $client['client'],
			'locale'          => cki_request_locale(),
			'viewport'        => cki_request_viewport(),
			'campaign'        => cki_request_campaign(),
			'created_at'      => current_time( 'mysql', true ),
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);

	if ( false === $inserted ) {
		// A concurrent duplicate is still a confirmed persisted registration.
		$confirmed = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE email = %s LIMIT 1", $email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $confirmed ) {
			wp_send_json_error( array( 'message' => __( 'Não foi possível cadastrar agora. Tente novamente.', 'casa-kotti-interesses' ) ), 500 );
		}
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_cki_register_interest', 'cki_register_interest' );
add_action( 'wp_ajax_nopriv_cki_register_interest', 'cki_register_interest' );

function cki_admin_menu() {
	add_menu_page(
		__( 'Interesses Casa Kotti', 'casa-kotti-interesses' ),
		__( 'Interesses', 'casa-kotti-interesses' ),
		'manage_options',
		'casa-kotti-interesses',
		'cki_admin_page',
		'dashicons-email-alt',
		26
	);
}
add_action( 'admin_menu', 'cki_admin_menu' );

function cki_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'casa-kotti-interesses' ), '', array( 'response' => 403 ) );
	}

	global $wpdb;
	$table    = cki_table_name();
	$page     = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
	$per_page = 50;
	$offset   = ( $page - 1 ) * $per_page;
	$total    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$rows     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Interesses Casa Kotti', 'casa-kotti-interesses' ); ?></h1>
		<p><?php echo esc_html( sprintf( _n( '%s cadastro', '%s cadastros', $total, 'casa-kotti-interesses' ), number_format_i18n( $total ) ) ); ?></p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cki_export' ), 'cki_export' ) ); ?>"><?php esc_html_e( 'Exportar CSV', 'casa-kotti-interesses' ); ?></a>
		</p>
		<table class="widefat striped">
			<thead><tr>
				<th><?php esc_html_e( 'E-mail', 'casa-kotti-interesses' ); ?></th>
				<th><?php esc_html_e( 'Quando', 'casa-kotti-interesses' ); ?></th>
				<th><?php esc_html_e( 'Dispositivo', 'casa-kotti-interesses' ); ?></th>
				<th><?php esc_html_e( 'Sistema / navegador', 'casa-kotti-interesses' ); ?></th>
				<th><?php esc_html_e( 'Idioma', 'casa-kotti-interesses' ); ?></th>
				<th><?php esc_html_e( 'Tela', 'casa-kotti-interesses' ); ?></th>
				<th><?php esc_html_e( 'Campanha', 'casa-kotti-interesses' ); ?></th>
				<th><?php esc_html_e( 'Ações', 'casa-kotti-interesses' ); ?></th>
			</tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'Nenhum cadastro encontrado.', 'casa-kotti-interesses' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row->email ); ?></td>
						<td><?php echo esc_html( cki_format_signup_datetime( $row->created_at ) ); ?></td>
						<td><?php echo esc_html( isset( $row->device ) ? $row->device : '' ); ?></td>
						<td><?php echo esc_html( isset( $row->client ) ? $row->client : '' ); ?></td>
						<td><?php echo esc_html( isset( $row->locale ) ? $row->locale : '' ); ?></td>
						<td><?php echo esc_html( isset( $row->viewport ) ? $row->viewport : '' ); ?></td>
						<td><?php echo esc_html( isset( $row->campaign ) && $row->campaign ? $row->campaign : '—' ); ?></td>
						<td>
							<a class="button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cki_delete&id=' . absint( $row->id ) ), 'cki_delete_' . absint( $row->id ) ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Excluir este cadastro?', 'casa-kotti-interesses' ) ); ?>');"><?php esc_html_e( 'Excluir', 'casa-kotti-interesses' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
		echo wp_kses_post( paginate_links( array(
			'base'    => add_query_arg( 'paged', '%#%' ),
			'format'  => '',
			'current' => $page,
			'total'   => max( 1, (int) ceil( $total / $per_page ) ),
		) ) );
		?>
	</div>
	<?php
}

function cki_delete_interest() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-interesses' ), '', array( 'response' => 403 ) );
	}

	$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	check_admin_referer( 'cki_delete_' . $id );

	global $wpdb;
	$wpdb->delete( cki_table_name(), array( 'id' => $id ), array( '%d' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=casa-kotti-interesses' ) );
	exit;
}
add_action( 'admin_post_cki_delete', 'cki_delete_interest' );

function cki_csv_safe( $value ) {
	$value = (string) $value;
	if ( preg_match( '/^[\s]*[=+\-@]/u', $value ) ) {
		return "'" . $value;
	}
	return $value;
}

function cki_export_interests() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-interesses' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'cki_export' );

	global $wpdb;
	$rows = $wpdb->get_results( 'SELECT email, created_at, device, client, locale, viewport, campaign, source, consent_text FROM ' . cki_table_name() . ' ORDER BY created_at DESC', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="casa-kotti-interesses-' . gmdate( 'Y-m-d' ) . '.csv"' );
	$output = fopen( 'php://output', 'w' );
	fputs( $output, "\xEF\xBB\xBF" );
	fputcsv( $output, array( 'email', 'data_utc', 'dispositivo', 'sistema_navegador', 'idioma', 'tela', 'campanha', 'origem', 'consentimento' ) );
	foreach ( $rows as $row ) {
		fputcsv( $output, array_map( 'cki_csv_safe', $row ) );
	}
	fclose( $output );
	exit;
}
add_action( 'admin_post_cki_export', 'cki_export_interests' );

function cki_privacy_notice() {
	if ( ! current_user_can( 'manage_options' ) || cki_has_valid_privacy_page() ) {
		return;
	}
	?>
	<div class="notice notice-warning"><p>
		<?php esc_html_e( 'Casa Kotti: publique e selecione uma página em Configurações > Privacidade para habilitar os cadastros.', 'casa-kotti-interesses' ); ?>
	</p></div>
	<?php
}
add_action( 'admin_notices', 'cki_privacy_notice' );

function cki_register_privacy_policy() {
	if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
		wp_add_privacy_policy_content(
			__( 'Casa Kotti — Interesses', 'casa-kotti-interesses' ),
			wp_kses_post( '<p>' . __( 'Este site pode armazenar o e-mail, a data do cadastro, a origem, o tipo de dispositivo, o sistema/navegador, o idioma, o tamanho aproximado da tela, parâmetros de campanha da URL (utm) e o texto do consentimento aceito para avisar sobre o lançamento. O endereço IP não é gravado. Ajuste este texto à política de privacidade aprovada para o site.', 'casa-kotti-interesses' ) . '</p>' )
		);
	}
}
add_action( 'admin_init', 'cki_register_privacy_policy' );

function cki_register_privacy_tools( $exporters ) {
	$exporters['casa-kotti-interesses'] = array(
		'exporter_friendly_name' => __( 'Interesses Casa Kotti', 'casa-kotti-interesses' ),
		'callback'               => 'cki_personal_data_exporter',
	);
	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', 'cki_register_privacy_tools' );

function cki_personal_data_exporter( $email_address, $page = 1 ) {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . cki_table_name() . ' WHERE email = %s', sanitize_email( $email_address ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( ! $row || $page > 1 ) {
		return array( 'data' => array(), 'done' => true );
	}
	return array(
		'data' => array( array(
			'group_id'    => 'casa-kotti-interesses',
			'group_label' => __( 'Interesse no lançamento da Casa Kotti', 'casa-kotti-interesses' ),
			'item_id'     => 'cki-' . absint( $row->id ),
			'data'        => array(
				array( 'name' => __( 'E-mail', 'casa-kotti-interesses' ), 'value' => $row->email ),
				array( 'name' => __( 'Data (UTC)', 'casa-kotti-interesses' ), 'value' => $row->created_at ),
				array( 'name' => __( 'Dispositivo', 'casa-kotti-interesses' ), 'value' => isset( $row->device ) ? $row->device : '' ),
				array( 'name' => __( 'Sistema / navegador', 'casa-kotti-interesses' ), 'value' => isset( $row->client ) ? $row->client : '' ),
				array( 'name' => __( 'Idioma', 'casa-kotti-interesses' ), 'value' => isset( $row->locale ) ? $row->locale : '' ),
				array( 'name' => __( 'Tela', 'casa-kotti-interesses' ), 'value' => isset( $row->viewport ) ? $row->viewport : '' ),
				array( 'name' => __( 'Campanha', 'casa-kotti-interesses' ), 'value' => isset( $row->campaign ) ? $row->campaign : '' ),
				array( 'name' => __( 'Origem', 'casa-kotti-interesses' ), 'value' => $row->source ),
				array( 'name' => __( 'Consentimento', 'casa-kotti-interesses' ), 'value' => $row->consent_text ),
			),
		) ),
		'done' => true,
	);
}

function cki_register_eraser( $erasers ) {
	$erasers['casa-kotti-interesses'] = array(
		'eraser_friendly_name' => __( 'Interesses Casa Kotti', 'casa-kotti-interesses' ),
		'callback'             => 'cki_personal_data_eraser',
	);
	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', 'cki_register_eraser' );

function cki_personal_data_eraser( $email_address, $page = 1 ) {
	if ( $page > 1 ) {
		return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
	}
	global $wpdb;
	$deleted = $wpdb->delete( cki_table_name(), array( 'email' => sanitize_email( $email_address ) ), array( '%s' ) );
	return array(
		'items_removed'  => $deleted > 0,
		'items_retained' => false,
		'messages'       => array(),
		'done'           => true,
	);
}
