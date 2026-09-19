<?php
/**
 * Casa Kotti admin: dashboard, list, CSV, fragrances.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CKF_Admin {
	/**
	 * Admin hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_ckf_export', array( __CLASS__, 'export_csv' ) );
		add_action( 'admin_post_ckf_save_fragrance', array( __CLASS__, 'save_fragrance' ) );
		add_action( 'admin_post_ckf_toggle_fragrance', array( __CLASS__, 'toggle_fragrance' ) );
		add_action( 'admin_post_ckf_reorder_fragrance', array( __CLASS__, 'reorder_fragrance' ) );
		CKF_Admin_Questions::init();
		add_action( 'admin_init', array( __CLASS__, 'privacy_policy' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
	}

	/**
	 * Menus under Casa Kotti.
	 */
	public static function menu() {
		add_menu_page(
			__( 'Casa Kotti', 'casa-kotti-feedback' ),
			__( 'Casa Kotti', 'casa-kotti-feedback' ),
			'manage_options',
			'casa-kotti-feedback',
			array( __CLASS__, 'page_reviews' ),
			'dashicons-star-filled',
			58
		);
		add_submenu_page(
			'casa-kotti-feedback',
			__( 'Avaliações', 'casa-kotti-feedback' ),
			__( 'Avaliações', 'casa-kotti-feedback' ),
			'manage_options',
			'casa-kotti-feedback',
			array( __CLASS__, 'page_reviews' )
		);
		add_submenu_page(
			'casa-kotti-feedback',
			__( 'Fragrâncias', 'casa-kotti-feedback' ),
			__( 'Fragrâncias', 'casa-kotti-feedback' ),
			'manage_options',
			'casa-kotti-fragrances',
			array( __CLASS__, 'page_fragrances' )
		);
		add_submenu_page(
			'casa-kotti-feedback',
			__( 'Perguntas', 'casa-kotti-feedback' ),
			__( 'Perguntas', 'casa-kotti-feedback' ),
			'manage_options',
			'casa-kotti-questions',
			array( 'CKF_Admin_Questions', 'page_list' )
		);
		add_submenu_page(
			'casa-kotti-feedback',
			__( 'Editar pergunta', 'casa-kotti-feedback' ),
			__( 'Nova pergunta', 'casa-kotti-feedback' ),
			'manage_options',
			'casa-kotti-question-edit',
			array( 'CKF_Admin_Questions', 'page_edit' )
		);
		add_submenu_page(
			'casa-kotti-feedback',
			__( 'Configurações', 'casa-kotti-feedback' ),
			__( 'Configurações', 'casa-kotti-feedback' ),
			'manage_options',
			'casa-kotti-feedback-settings',
			array( 'CKF_Admin_Questions', 'page_settings' )
		);
	}

	/**
	 * Admin CSS/JS only on plugin screens.
	 *
	 * @param string $hook Hook suffix.
	 */
	public static function assets( $hook ) {
		if ( false === strpos( $hook, 'casa-kotti' ) ) {
			return;
		}
		wp_enqueue_style( 'casa-kotti-feedback', CKF_URL . 'public/feedback.css', array(), CKF_VERSION );
		wp_enqueue_style( 'casa-kotti-feedback-admin', CKF_URL . 'admin/admin.css', array( 'casa-kotti-feedback' ), CKF_VERSION );
		wp_enqueue_script( 'casa-kotti-feedback-admin', CKF_URL . 'admin/admin.js', array(), CKF_VERSION, true );
		wp_localize_script(
			'casa-kotti-feedback-admin',
			'ckfAdmin',
			array(
				'previewNonce' => wp_create_nonce( 'ckf_preview_question' ),
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Current GET filters.
	 *
	 * @return array
	 */
	public static function filters() {
		return array(
			'product'    => isset( $_GET['product'] ) ? sanitize_key( wp_unslash( $_GET['product'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'fragrance'  => isset( $_GET['fragrance'] ) ? sanitize_title( wp_unslash( $_GET['fragrance'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'rating'     => isset( $_GET['rating'] ) ? absint( $_GET['rating'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'nps'        => isset( $_GET['nps'] ) ? sanitize_key( wp_unslash( $_GET['nps'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'from'       => isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'to'         => isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			's'          => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);
	}

	/**
	 * SQL WHERE from filters.
	 *
	 * @param array $filters Filters.
	 * @return array{sql:string, args:array}
	 */
	public static function where( $filters ) {
		global $wpdb;
		$table = CKF_Database::feedback_table();
		$where = array( '1=1' );
		$args  = array();

		if ( $filters['product'] && isset( ckf_products()[ $filters['product'] ] ) ) {
			$where[] = 'product = %s';
			$args[]  = $filters['product'];
		}
		if ( $filters['fragrance'] ) {
			$where[] = 'fragrance_slug = %s';
			$args[]  = $filters['fragrance'];
		}
		if ( $filters['rating'] >= 1 && $filters['rating'] <= 5 ) {
			$where[] = 'overall_rating = %d';
			$args[]  = $filters['rating'];
		}
		if ( 'promoters' === $filters['nps'] ) {
			$where[] = 'nps_score >= 9';
		} elseif ( 'passives' === $filters['nps'] ) {
			$where[] = 'nps_score IN (7,8)';
		} elseif ( 'detractors' === $filters['nps'] ) {
			$where[] = 'nps_score <= 6';
		}
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $filters['from'] ) ) {
			$where[] = 'created_at >= %s';
			$args[]  = $filters['from'] . ' 00:00:00';
		}
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $filters['to'] ) ) {
			$where[] = 'created_at <= %s';
			$args[]  = $filters['to'] . ' 23:59:59';
		}
		if ( $filters['s'] ) {
			$like    = '%' . $wpdb->esc_like( $filters['s'] ) . '%';
			$where[] = '(customer_name LIKE %s OR customer_email LIKE %s)';
			$args[]  = $like;
			$args[]  = $like;
		}

		return array(
			'sql'  => ' FROM ' . $table . ' WHERE ' . implode( ' AND ', $where ),
			'args' => $args,
		);
	}

	/**
	 * Dashboard metrics.
	 *
	 * @param array $filters Filters.
	 * @return object
	 */
	public static function metrics( $filters ) {
		global $wpdb;
		$part = self::where( $filters );
		$sql  = 'SELECT COUNT(*) AS total,
			AVG(overall_rating) AS avg_overall,
			AVG(presentation_rating) AS avg_presentation,
			SUM(CASE WHEN nps_score >= 9 THEN 1 ELSE 0 END) AS promoters,
			SUM(CASE WHEN nps_score BETWEEN 7 AND 8 THEN 1 ELSE 0 END) AS passives,
			SUM(CASE WHEN nps_score <= 6 THEN 1 ELSE 0 END) AS detractors,
			SUM(CASE WHEN repurchase_intent IN ("com-certeza","provavelmente-sim") THEN 1 ELSE 0 END) AS repurchase
			' . $part['sql'];
		if ( $part['args'] ) {
			$row = $wpdb->get_row( $wpdb->prepare( $sql, $part['args'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$row = $wpdb->get_row( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$total = (int) $row->total;
		$nps   = 0;
		if ( $total ) {
			$nps = ( ( (int) $row->promoters / $total ) - ( (int) $row->detractors / $total ) ) * 100;
		}
		$row->nps = $nps;
		$row->repurchase_pct = $total ? ( (int) $row->repurchase / $total ) * 100 : 0;
		return $row;
	}

	/**
	 * Reviews screen.
	 */
	public static function page_reviews() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}

		$view = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $view ) {
			self::render_detail( $view );
			return;
		}

		global $wpdb;
		$filters  = self::filters();
		$metrics  = self::metrics( $filters );
		$part     = self::where( $filters );
		$page     = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page = 20;
		$offset   = ( $page - 1 ) * $per_page;
		$count_sql = 'SELECT COUNT(*)' . $part['sql'];
		$list_sql  = 'SELECT *' . $part['sql'] . ' ORDER BY created_at DESC LIMIT %d OFFSET %d';
		if ( $part['args'] ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $part['args'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rows  = $wpdb->get_results( $wpdb->prepare( $list_sql, array_merge( $part['args'], array( $per_page, $offset ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rows  = $wpdb->get_results( $wpdb->prepare( $list_sql, $per_page, $offset ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$by_product = array();
		foreach ( array_keys( ckf_products() ) as $slug ) {
			if ( 'mais-de-um' === $slug ) {
				continue;
			}
			$scoped            = $filters;
			$scoped['product'] = $slug;
			$by_product[ $slug ] = self::metrics( $scoped );
		}

		$export = wp_nonce_url(
			add_query_arg( array_merge( array( 'action' => 'ckf_export' ), array_filter( $filters ) ), admin_url( 'admin-post.php' ) ),
			'ckf_export'
		);

		include CKF_DIR . 'admin/admin-page.php';
	}

	/**
	 * Single review.
	 *
	 * @param int $id Row ID.
	 */
	private static function render_detail( $id ) {
		global $wpdb;
		$table = CKF_Database::feedback_table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $row ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Avaliação não encontrada.', 'casa-kotti-feedback' ) . '</p></div>';
			return;
		}
		$products    = ckf_products();
		$intensity   = ckf_intensity_options();
		$performance = ckf_performance_options();
		$repurchase  = ckf_repurchase_options();
		include CKF_DIR . 'admin/admin-detail.php';
	}

	/**
	 * Fragrance CRUD screen.
	 */
	public static function page_fragrances() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$edit = null;
		if ( isset( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			global $wpdb;
			$edit = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . CKF_Database::fragrance_table() . ' WHERE id = %d', absint( $_GET['edit'] ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.Security.NonceVerification.Recommended
		}
		$rows = CKF_Database::all_fragrances();
		include CKF_DIR . 'admin/admin-fragrances.php';
	}

	/**
	 * Create or update fragrance.
	 */
	public static function save_fragrance() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'ckf_save_fragrance' );

		global $wpdb;
		$id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$slug   = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$status = isset( $_POST['status'] ) && 'inactive' === $_POST['status'] ? 'inactive' : 'active';
		$order  = isset( $_POST['sort_order'] ) ? (int) $_POST['sort_order'] : 0;

		if ( '' === $name ) {
			wp_safe_redirect( admin_url( 'admin.php?page=casa-kotti-fragrances&error=1' ) );
			exit;
		}
		if ( '' === $slug ) {
			$slug = sanitize_title( $name );
		}

		$table = CKF_Database::fragrance_table();
		$now   = current_time( 'mysql', true );
		if ( $id ) {
			$wpdb->update(
				$table,
				array(
					'name'       => $name,
					'slug'       => $slug,
					'status'     => $status,
					'sort_order' => $order,
					'updated_at' => $now,
				),
				array( 'id' => $id ),
				array( '%s', '%s', '%s', '%d', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'name'       => $name,
					'slug'       => $slug,
					'status'     => $status,
					'sort_order' => $order,
					'created_at' => $now,
					'updated_at' => $now,
				),
				array( '%s', '%s', '%s', '%d', '%s', '%s' )
			);
		}

		wp_safe_redirect( admin_url( 'admin.php?page=casa-kotti-fragrances&updated=1' ) );
		exit;
	}

	/**
	 * Activate or deactivate without deleting responses.
	 */
	public static function toggle_fragrance() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'ckf_toggle_fragrance_' . $id );

		global $wpdb;
		$table = CKF_Database::fragrance_table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT status FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $row ) {
			$next = 'active' === $row->status ? 'inactive' : 'active';
			$wpdb->update( $table, array( 'status' => $next, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $id ), array( '%s', '%s' ), array( '%d' ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=casa-kotti-fragrances' ) );
		exit;
	}

	/**
	 * Move fragrance order.
	 */
	public static function reorder_fragrance() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$dir  = isset( $_GET['dir'] ) && 'up' === $_GET['dir'] ? -1 : 1;
		check_admin_referer( 'ckf_reorder_fragrance_' . $id );

		global $wpdb;
		$table = CKF_Database::fragrance_table();
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET sort_order = sort_order + %d, updated_at = %s WHERE id = %d", $dir, current_time( 'mysql', true ), $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		wp_safe_redirect( admin_url( 'admin.php?page=casa-kotti-fragrances' ) );
		exit;
	}

	/**
	 * CSV export honoring filters.
	 */
	public static function export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'ckf_export' );

		global $wpdb;
		$filters = self::filters();
		$part    = self::where( $filters );
		$sql     = 'SELECT *' . $part['sql'] . ' ORDER BY created_at DESC';
		if ( $part['args'] ) {
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $part['args'] ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="casa-kotti-avaliacoes-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$output = fopen( 'php://output', 'w' );
		fputs( $output, "\xEF\xBB\xBF" );
		$custom = CKF_Answers::custom_slugs();
		$headers = array_merge(
			array( 'id', 'uuid', 'data_utc', 'produto', 'fragrancia', 'nota', 'intensidade', 'resposta_especifica', 'performance', 'apresentacao', 'recompra', 'nps', 'melhoria', 'positivo', 'nome', 'email', 'marketing', 'origem', 'campanha', 'lote' ),
			$custom
		);
		fputcsv( $output, $headers );

		$by_id = array();
		if ( $rows && $custom ) {
			$ids = array_map( 'intval', wp_list_pluck( $rows, 'id' ) );
			$ans_table = CKF_Database::answers_table();
			$in = implode( ',', $ids );
			$placeholders = implode( ',', array_fill( 0, count( $custom ), '%s' ) );
			$sql_ans = "SELECT feedback_id, question_slug, answer_value, answer_text FROM {$ans_table} WHERE feedback_id IN ({$in}) AND question_slug IN ({$placeholders})";
			$answers = $wpdb->get_results( $wpdb->prepare( $sql_ans, $custom ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			foreach ( $answers as $ans ) {
				$val = $ans->answer_text ? $ans->answer_text : $ans->answer_value;
				if ( ! isset( $by_id[ $ans->feedback_id ][ $ans->question_slug ] ) ) {
					$by_id[ $ans->feedback_id ][ $ans->question_slug ] = array();
				}
				$by_id[ $ans->feedback_id ][ $ans->question_slug ][] = $val;
			}
		}

		foreach ( $rows as $row ) {
			$cells = array(
				$row['id'],
				$row['uuid'],
				$row['created_at'],
				$row['product'],
				$row['fragrance'],
				$row['overall_rating'],
				$row['intensity'],
				$row['product_specific_answer'],
				$row['performance'],
				$row['presentation_rating'],
				$row['repurchase_intent'],
				$row['nps_score'],
				$row['improvement_comment'],
				$row['positive_comment'],
				$row['customer_name'],
				$row['customer_email'],
				$row['marketing_consent'],
				$row['source'],
				$row['campaign'],
				$row['batch'],
			);
			foreach ( $custom as $slug ) {
				$vals = isset( $by_id[ $row['id'] ][ $slug ] ) ? $by_id[ $row['id'] ][ $slug ] : array();
				$cells[] = implode( '; ', $vals );
			}
			fputcsv( $output, array_map( array( 'CKF_Security', 'csv_safe' ), $cells ) );
		}
		fclose( $output );
		exit;
	}

	/**
	 * Suggested privacy copy.
	 */
	public static function privacy_policy() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content(
				__( 'Casa Kotti — Avaliações', 'casa-kotti-feedback' ),
				wp_kses_post( '<p>' . __( 'Este site pode armazenar respostas da avaliação de experiência, e opcionalmente nome, e-mail e a escolha de receber novidades. Um identificador irreversível pode ser usado para limitar envios repetidos. Ajuste este texto à política de privacidade aprovada.', 'casa-kotti-feedback' ) . '</p>' )
			);
		}
	}

	/**
	 * Personal data exporter.
	 *
	 * @param array $exporters Exporters.
	 * @return array
	 */
	public static function register_exporter( $exporters ) {
		$exporters['casa-kotti-feedback'] = array(
			'exporter_friendly_name' => __( 'Avaliações Casa Kotti', 'casa-kotti-feedback' ),
			'callback'               => array( __CLASS__, 'export_personal_data' ),
		);
		return $exporters;
	}

	/**
	 * Personal data eraser.
	 *
	 * @param array $erasers Erasers.
	 * @return array
	 */
	public static function register_eraser( $erasers ) {
		$erasers['casa-kotti-feedback'] = array(
			'eraser_friendly_name' => __( 'Avaliações Casa Kotti', 'casa-kotti-feedback' ),
			'callback'             => array( __CLASS__, 'erase_personal_data' ),
		);
		return $erasers;
	}

	/**
	 * Export rows by email.
	 *
	 * @param string $email_address Email.
	 * @param int    $page Page.
	 * @return array
	 */
	public static function export_personal_data( $email_address, $page = 1 ) {
		global $wpdb;
		$email = sanitize_email( $email_address );
		if ( ! $email || $page > 1 ) {
			return array( 'data' => array(), 'done' => true );
		}
		$table = CKF_Database::feedback_table();
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE customer_email = %s", $email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$data  = array();
		foreach ( $rows as $row ) {
			$data[] = array(
				'group_id'    => 'casa-kotti-feedback',
				'group_label' => __( 'Avaliação Casa Kotti', 'casa-kotti-feedback' ),
				'item_id'     => 'ckf-' . absint( $row->id ),
				'data'        => array(
					array( 'name' => __( 'E-mail', 'casa-kotti-feedback' ), 'value' => $row->customer_email ),
					array( 'name' => __( 'Nome', 'casa-kotti-feedback' ), 'value' => $row->customer_name ),
					array( 'name' => __( 'Produto', 'casa-kotti-feedback' ), 'value' => $row->product ),
					array( 'name' => __( 'Data (UTC)', 'casa-kotti-feedback' ), 'value' => $row->created_at ),
				),
			);
		}
		return array( 'data' => $data, 'done' => true );
	}

	/**
	 * Anonymize identity fields; keep product metrics.
	 *
	 * @param string $email_address Email.
	 * @param int    $page Page.
	 * @return array
	 */
	public static function erase_personal_data( $email_address, $page = 1 ) {
		if ( $page > 1 ) {
			return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		}
		global $wpdb;
		$email   = sanitize_email( $email_address );
		$updated = $wpdb->update(
			CKF_Database::feedback_table(),
			array(
				'customer_name'     => '',
				'customer_email'    => '',
				'marketing_consent' => 0,
				'ip_hash'           => '',
				'user_agent'        => '',
			),
			array( 'customer_email' => $email ),
			array( '%s', '%s', '%d', '%s', '%s' ),
			array( '%s' )
		);
		return array(
			'items_removed'  => $updated > 0,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
