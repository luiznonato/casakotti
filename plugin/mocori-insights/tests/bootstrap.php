<?php
/**
 * WordPress function stubs for running Insights tests without WordPress.
 *
 * @package Mocori_Insights
 */

define( 'ABSPATH', sys_get_temp_dir() . '/mi-wp/' );
if ( ! is_dir( ABSPATH . 'wp-admin/includes' ) ) {
	mkdir( ABSPATH . 'wp-admin/includes', 0777, true );
}
file_put_contents( ABSPATH . 'wp-admin/includes/upgrade.php', "<?php\nfunction dbDelta( \$sql ) {\n\tglobal \$wpdb;\n\t\$wpdb->dbDelta( \$sql );\n}\n" );

define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'MOCORI_INSIGHTS_VERSION', '1.0.0' );
define( 'MOCORI_INSIGHTS_DB_VERSION', '1.0.0' );
define( 'MOCORI_INSIGHTS_DIR', dirname( __DIR__ ) . '/' );
define( 'MOCORI_INSIGHTS_URL', 'http://example.test/wp-content/plugins/mocori-insights/' );
define( 'MOCORI_INSIGHTS_SLUG', 'mocori-insights' );

$GLOBALS['mi_filters']  = array();
$GLOBALS['mi_actions']  = array();
$GLOBALS['mi_options']  = array();
$GLOBALS['mi_trans']    = array();
$GLOBALS['mi_caps']     = array( 'manage_options' => true, 'manage_mocori_insights' => true, 'view_mocori_insights' => true );
$GLOBALS['mi_now']      = gmdate( 'Y-m-d H:i:s' );
$GLOBALS['mi_timezone'] = 'UTC';

function add_filter( $tag, $fn, $priority = 10, $args = 1 ) {
	$GLOBALS['mi_filters'][ $tag ][ $priority ][] = array( $fn, $args );
	unset( $args );
}
function add_action( $tag, $fn, $priority = 10, $args = 1 ) {
	add_filter( $tag, $fn, $priority, $args );
}
function apply_filters( $tag, $value ) {
	$args = func_get_args();
	array_shift( $args );
	if ( empty( $GLOBALS['mi_filters'][ $tag ] ) ) {
		return $value;
	}
	ksort( $GLOBALS['mi_filters'][ $tag ] );
	foreach ( $GLOBALS['mi_filters'][ $tag ] as $cbs ) {
		foreach ( $cbs as $cb ) {
			$value = call_user_func_array( $cb[0], $args );
			$args[0] = $value;
		}
	}
	return $value;
}
function do_action( $tag ) {
	$args = func_get_args();
	array_shift( $args );
	if ( empty( $GLOBALS['mi_filters'][ $tag ] ) ) {
		return;
	}
	ksort( $GLOBALS['mi_filters'][ $tag ] );
	foreach ( $GLOBALS['mi_filters'][ $tag ] as $cbs ) {
		foreach ( $cbs as $cb ) {
			call_user_func_array( $cb[0], $args );
		}
	}
}
function __return_true() { return true; }

function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_key( $v ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $v ) ); }
function sanitize_email( $v ) { return filter_var( $v, FILTER_SANITIZE_EMAIL ); }
function wp_strip_all_tags( $v ) { return strip_tags( (string) $v ); }
function wp_json_encode( $v ) { return json_encode( $v ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function wp_parse_args( $a, $b ) { return array_merge( $b, $a ); }
function wp_generate_password( $len = 12, $special = true, $extra = false ) {
	unset( $special, $extra );
	return substr( bin2hex( random_bytes( 16 ) ), 0, $len );
}
function wp_salt( $scheme = 'auth' ) { return 'test-salt-' . $scheme; }
function wp_unslash( $v ) { return is_string( $v ) ? stripslashes( $v ) : $v; }
function esc_url_raw( $v ) { return $v; }
function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function __ ( $t, $d = '' ) { unset( $d ); return $t; }
function esc_html__( $t, $d = '' ) { return esc_html( __( $t, $d ) ); }
function home_url() { return 'https://example.test'; }
function get_bloginfo( $show = '' ) { unset( $show ); return 'Example Site'; }
function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
function plugin_dir_url( $file ) { unset( $file ); return MOCORI_INSIGHTS_URL; }
function plugins_url( $path, $file ) { unset( $file ); return MOCORI_INSIGHTS_URL . ltrim( $path, '/' ); }
function rest_url( $path ) { return 'https://example.test/wp-json/' . $path; }
function admin_url( $path ) { return 'https://example.test/wp-admin/' . $path; }
function wp_timezone_string() { return 'UTC'; }
function current_time( $type, $gmt = 0 ) {
	unset( $gmt );
	if ( 'mysql' === $type ) {
		return $GLOBALS['mi_now'];
	}
	if ( 'Y-m-d' === $type ) {
		return substr( $GLOBALS['mi_now'], 0, 10 );
	}
	return $GLOBALS['mi_now'];
}
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['mi_options'] ) ? $GLOBALS['mi_options'][ $k ] : $d; }
function update_option( $k, $v, $autoload = true ) { unset( $autoload ); $GLOBALS['mi_options'][ $k ] = $v; return true; }
function delete_option( $k ) { unset( $GLOBALS['mi_options'][ $k ] ); }
function get_transient( $k ) { return isset( $GLOBALS['mi_trans'][ $k ] ) ? $GLOBALS['mi_trans'][ $k ] : false; }
function set_transient( $k, $v, $e = 0 ) { unset( $e ); $GLOBALS['mi_trans'][ $k ] = $v; return true; }
function current_user_can( $cap ) { return ! empty( $GLOBALS['mi_caps'][ $cap ] ); }
function get_role( $role ) {
	unset( $role );
	return new class {
		public function add_cap( $c ) { $GLOBALS['mi_caps'][ $c ] = true; }
		public function remove_cap( $c ) { unset( $GLOBALS['mi_caps'][ $c ] ); }
	};
}
function wp_next_scheduled( $h ) { unset( $h ); return false; }
function wp_schedule_event( $t, $r, $h ) { unset( $t, $r, $h ); return true; }
function wp_clear_scheduled_hook( $h ) { unset( $h ); }
function flush_rewrite_rules( $hard = true ) { unset( $hard ); }
function is_admin() { return false; }
function absint( $v ) { return abs( (int) $v ); }
function selected( $a, $b, $echo = true ) { $r = ( (string) $a === (string) $b ) ? ' selected="selected"' : ''; if ( $echo ) { echo $r; } return $r; }
function checked( $a, $b, $echo = true ) { $r = ( (string) $a === (string) $b ) ? ' checked="checked"' : ''; if ( $echo ) { echo $r; } return $r; }
function number_format_i18n( $n ) { return number_format( (int) $n, 0, ',', '.' ); }

class MI_Fake_Wpdb {
	public $prefix = 'wp_';
	public $insert_id = 0;
	public $last_error = '';
	private $pdo;

	public function get_charset_collate() {
		return '';
	}

	public function __construct() {
		$this->pdo = new PDO( 'sqlite::memory:' );
		$this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$this->install_sqlite();
	}

	public function dbDelta( $sql ) {
		unset( $sql );
	}

	private function install_sqlite() {
		$p = $this->prefix;
		$stmts = array(
			"CREATE TABLE {$p}mocori_insights_settings (setting_key TEXT PRIMARY KEY, setting_value TEXT NOT NULL, updated_at TEXT NOT NULL)",
			"CREATE TABLE {$p}mocori_insights_visitors (id INTEGER PRIMARY KEY AUTOINCREMENT, site_uuid TEXT, visitor_uuid TEXT UNIQUE, first_seen TEXT, last_seen TEXT, first_source TEXT, first_medium TEXT, first_campaign TEXT, first_content TEXT, first_term TEXT, first_channel TEXT, first_landing_page TEXT, last_source TEXT, last_medium TEXT, last_campaign TEXT, last_channel TEXT, device_type TEXT, os TEXT, browser TEXT, created_at TEXT, updated_at TEXT)",
			"CREATE TABLE {$p}mocori_insights_sessions (id INTEGER PRIMARY KEY AUTOINCREMENT, site_uuid TEXT, session_uuid TEXT UNIQUE, visitor_id INTEGER, started_at TEXT, last_activity_at TEXT, source TEXT, medium TEXT, campaign TEXT, content TEXT, term TEXT, channel TEXT, referrer TEXT, landing_page TEXT, exit_page TEXT, pageviews INTEGER DEFAULT 0, created_at TEXT, updated_at TEXT)",
			"CREATE TABLE {$p}mocori_insights_events (id INTEGER PRIMARY KEY AUTOINCREMENT, site_uuid TEXT, visitor_id INTEGER, session_id INTEGER, event_name TEXT, page_url TEXT, metadata TEXT, created_at TEXT)",
			"CREATE TABLE {$p}mocori_insights_conversions (id INTEGER PRIMARY KEY AUTOINCREMENT, site_uuid TEXT, visitor_id INTEGER, session_id INTEGER, event_id INTEGER, conversion_type TEXT, conversion_name TEXT, reference_id TEXT, value REAL, currency TEXT, first_channel TEXT, last_channel TEXT, created_at TEXT)",
			"CREATE TABLE {$p}mocori_insights_daily (id INTEGER PRIMARY KEY AUTOINCREMENT, site_uuid TEXT, stat_date TEXT, visitors INTEGER, new_visitors INTEGER, returning_visitors INTEGER, sessions INTEGER, pageviews INTEGER, conversions INTEGER, updated_at TEXT, UNIQUE(site_uuid, stat_date))",
			"CREATE TABLE {$p}mocori_insights_conversion_defs (id INTEGER PRIMARY KEY AUTOINCREMENT, site_uuid TEXT, event_name TEXT, match_key TEXT, match_value TEXT, conversion_name TEXT, conversion_type TEXT, is_active INTEGER, created_at TEXT, updated_at TEXT)",
			"CREATE TABLE {$p}mocori_insights_identities (id INTEGER PRIMARY KEY AUTOINCREMENT, site_uuid TEXT, visitor_id INTEGER, session_id INTEGER, lead_id TEXT, lead_source TEXT, identified_at TEXT)",
			"CREATE TABLE {$p}mocori_insights_funnels (id INTEGER PRIMARY KEY AUTOINCREMENT, site_uuid TEXT, funnel_name TEXT, steps TEXT, is_active INTEGER, created_at TEXT, updated_at TEXT)",
		);
		foreach ( $stmts as $sql ) {
			$this->pdo->exec( $sql );
		}
	}

	public function prepare( $query, ...$args ) {
		if ( count( $args ) === 1 && is_array( $args[0] ) ) {
			$args = $args[0];
		}
		foreach ( $args as $arg ) {
			if ( is_int( $arg ) || is_float( $arg ) ) {
				$repl = $arg;
			} else {
				$repl = $this->pdo->quote( (string) $arg );
			}
			$query = preg_replace( '/%[sdf]/', $repl, $query, 1 );
		}
		return $query;
	}

	public function query( $sql ) {
		return $this->pdo->exec( $sql );
	}

	public function get_var( $sql ) {
		$stmt = $this->pdo->query( $sql );
		$val  = $stmt ? $stmt->fetchColumn() : null;
		return false === $val ? null : $val;
	}

	public function get_row( $sql, $output = OBJECT ) {
		$stmt = $this->pdo->query( $sql );
		if ( ! $stmt ) {
			return null;
		}
		$row = ARRAY_A === $output ? $stmt->fetch( PDO::FETCH_ASSOC ) : $stmt->fetch( PDO::FETCH_OBJ );
		return $row ? $row : null;
	}

	public function get_results( $sql, $output = OBJECT ) {
		$stmt = $this->pdo->query( $sql );
		if ( ! $stmt ) {
			return array();
		}
		return ARRAY_A === $output ? $stmt->fetchAll( PDO::FETCH_ASSOC ) : $stmt->fetchAll( PDO::FETCH_OBJ );
	}

	public function insert( $table, $data, $format = null ) {
		unset( $format );
		$cols = implode( ',', array_keys( $data ) );
		$vals = implode( ',', array_map( array( $this, 'quote_val' ), array_values( $data ) ) );
		$this->pdo->exec( "INSERT INTO {$table} ({$cols}) VALUES ({$vals})" );
		$this->insert_id = (int) $this->pdo->lastInsertId();
		return 1;
	}

	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		unset( $format, $where_format );
		$set = array();
		foreach ( $data as $k => $v ) {
			$set[] = $k . '=' . $this->quote_val( $v );
		}
		$w = array();
		foreach ( $where as $k => $v ) {
			$w[] = $k . '=' . $this->quote_val( $v );
		}
		return $this->pdo->exec( 'UPDATE ' . $table . ' SET ' . implode( ',', $set ) . ' WHERE ' . implode( ' AND ', $w ) );
	}

	public function replace( $table, $data, $format = null ) {
		unset( $format );
		$cols = implode( ',', array_keys( $data ) );
		$vals = implode( ',', array_map( array( $this, 'quote_val' ), array_values( $data ) ) );
		$this->pdo->exec( "INSERT OR REPLACE INTO {$table} ({$cols}) VALUES ({$vals})" );
		return 1;
	}

	public function delete( $table, $where, $where_format = null ) {
		unset( $where_format );
		$w = array();
		foreach ( $where as $k => $v ) {
			$w[] = $k . '=' . $this->quote_val( $v );
		}
		return $this->pdo->exec( 'DELETE FROM ' . $table . ' WHERE ' . implode( ' AND ', $w ) );
	}

	private function quote_val( $v ) {
		if ( is_int( $v ) || is_float( $v ) ) {
			return $v;
		}
		return $this->pdo->quote( (string) $v );
	}
}

if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

$GLOBALS['wpdb'] = new MI_Fake_Wpdb();
