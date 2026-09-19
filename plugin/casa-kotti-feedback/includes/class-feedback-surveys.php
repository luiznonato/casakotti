<?php
/**
 * Multiple questionnaires.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CKF_Surveys {
	const DEFAULT_SLUG = 'experiencia';
	const MIGRATE_KEY  = 'ckf_surveys_migrated';

	public static function init() {
		add_action( 'admin_post_ckf_save_survey', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_ckf_duplicate_survey', array( __CLASS__, 'duplicate' ) );
		add_action( 'admin_post_ckf_toggle_survey', array( __CLASS__, 'toggle' ) );
		add_action( 'admin_post_ckf_delete_survey', array( __CLASS__, 'delete' ) );
	}

	public static function table() {
		return CKF_Database::surveys_table();
	}

	public static function all() {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY is_default DESC, sort_order ASC, id ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function get( $id ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function by_slug( $slug ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s LIMIT 1", sanitize_title( $slug ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function default_row() {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row( "SELECT * FROM {$table} WHERE is_default = 1 ORDER BY id ASC LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $row ) {
			return $row;
		}
		return $wpdb->get_row( "SELECT * FROM {$table} ORDER BY id ASC LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function default_id() {
		$row = self::default_row();
		return $row ? (int) $row->id : 0;
	}

	public static function current_id() {
		$id = 0;
		if ( isset( $_REQUEST['survey_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$id = absint( wp_unslash( $_REQUEST['survey_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( $id && self::get( $id ) ) {
			return $id;
		}
		return self::default_id();
	}

	public static function current() {
		return self::get( self::current_id() );
	}

	public static function builder_url( $args = array() ) {
		$args = array_merge(
			array(
				'page'      => 'casa-kotti-questions',
				'survey_id' => self::current_id(),
			),
			$args
		);
		return admin_url( 'admin.php?' . http_build_query( $args ) );
	}

	public static function settings( $survey ) {
		$raw  = is_object( $survey ) ? $survey->settings_json : '';
		$data = json_decode( (string) $raw, true );
		return is_array( $data ) ? $data : array();
	}

	public static function copy( $survey = null ) {
		$defaults = CKF_Questions::default_copy();
		if ( null === $survey ) {
			$survey = self::default_row();
		} elseif ( is_numeric( $survey ) ) {
			$survey = self::get( $survey );
		}
		$from_survey = $survey ? self::settings( $survey ) : array();
		$copy        = isset( $from_survey['copy'] ) && is_array( $from_survey['copy'] ) ? $from_survey['copy'] : array();
		if ( ! $copy ) {
			$saved = get_option( 'ckf_copy', array() );
			$copy  = is_array( $saved ) ? $saved : array();
		}
		return wp_parse_args( $copy, $defaults );
	}

	public static function save_copy( $survey_id, $data ) {
		$survey = self::get( $survey_id );
		if ( ! $survey ) {
			return;
		}
		$settings         = self::settings( $survey );
		$settings['copy'] = CKF_Questions::sanitize_copy( $data );
		self::update(
			$survey_id,
			array(
				'settings_json' => wp_json_encode( $settings ),
			)
		);
		if ( (int) $survey->is_default ) {
			CKF_Questions::save_copy( $settings['copy'] );
		}
	}

	public static function unique_slug( $base, $ignore = 0 ) {
		$slug = sanitize_title( $base );
		if ( '' === $slug ) {
			$slug = 'questionario';
		}
		$try = $slug;
		$i   = 2;
		while ( true ) {
			$row = self::by_slug( $try );
			if ( ! $row || (int) $row->id === (int) $ignore ) {
				return $try;
			}
			$try = $slug . '-' . $i;
			$i++;
		}
	}

	public static function insert( $data ) {
		global $wpdb;
		$now  = current_time( 'mysql', true );
		$data = wp_parse_args(
			$data,
			array(
				'uuid'          => wp_generate_uuid4(),
				'description'   => '',
				'status'        => 'active',
				'is_default'    => 0,
				'sort_order'    => self::next_order(),
				'settings_json' => '{}',
			)
		);
		$ok = $wpdb->insert(
			self::table(),
			array(
				'uuid'          => $data['uuid'],
				'slug'          => $data['slug'],
				'title'         => $data['title'],
				'description'   => $data['description'],
				'status'        => $data['status'],
				'is_default'    => (int) $data['is_default'],
				'sort_order'    => (int) $data['sort_order'],
				'settings_json' => $data['settings_json'],
				'created_at'    => $now,
				'updated_at'    => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
		);
		CKF_Questions::bust_cache();
		return $ok ? (int) $wpdb->insert_id : 0;
	}

	public static function update( $id, $data ) {
		global $wpdb;
		$data['updated_at'] = current_time( 'mysql', true );
		$wpdb->update( self::table(), $data, array( 'id' => absint( $id ) ) );
		CKF_Questions::bust_cache();
	}

	public static function next_order() {
		global $wpdb;
		$table = self::table();
		$max   = (int) $wpdb->get_var( "SELECT MAX(sort_order) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $max + 10;
	}

	/**
	 * Create the default questionnaire and attach existing pages/questions.
	 */
	public static function migrate_default() {
		$existing = self::default_row();
		if ( ! $existing ) {
			$copy     = get_option( 'ckf_copy', array() );
			$settings = array(
				'copy' => is_array( $copy ) ? $copy : array(),
			);
			$id       = self::insert(
				array(
					'slug'          => self::DEFAULT_SLUG,
					'title'         => __( 'Experiência', 'casa-kotti-feedback' ),
					'description'   => __( 'Questionário principal de avaliação.', 'casa-kotti-feedback' ),
					'is_default'    => 1,
					'status'        => 'active',
					'settings_json' => wp_json_encode( $settings ),
				)
			);
			$existing = $id ? self::get( $id ) : null;
		}
		if ( ! $existing ) {
			return;
		}
		$sid = (int) $existing->id;
		global $wpdb;
		$q_table = CKF_Database::questions_table();
		$s_table = CKF_Database::steps_table();
		$wpdb->query( $wpdb->prepare( "UPDATE {$q_table} SET survey_id = %d WHERE survey_id = 0", $sid ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "UPDATE {$s_table} SET survey_id = %d WHERE survey_id = 0", $sid ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		update_option( self::MIGRATE_KEY, '1', false );
		CKF_Questions::bust_cache();
	}

	public static function resolve_public( $slug = '' ) {
		$slug = sanitize_title( (string) $slug );
		if ( $slug ) {
			$row = self::by_slug( $slug );
			if ( $row && 'active' === $row->status ) {
				return $row;
			}
			return null;
		}
		$row = self::default_row();
		if ( $row && 'active' === $row->status ) {
			return $row;
		}
		return null;
	}

	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'ckf_save_survey' );
		$id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		if ( '' === $title ) {
			wp_safe_redirect( admin_url( 'admin.php?page=casa-kotti-surveys&error=1' ) );
			exit;
		}
		$slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$data = array(
			'title'       => $title,
			'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			'status'      => ( isset( $_POST['status'] ) && 'inactive' === $_POST['status'] ) ? 'inactive' : 'active',
			'slug'        => self::unique_slug( $slug ? $slug : $title, $id ),
		);
		if ( $id ) {
			$existing = self::get( $id );
			if ( $existing && (int) $existing->is_default ) {
				$data['slug'] = $existing->slug;
			}
			self::update( $id, $data );
		} else {
			$id = self::insert( $data );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=casa-kotti-surveys&updated=1&survey_id=' . absint( $id ) ) );
		exit;
	}

	public static function toggle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'ckf_toggle_survey_' . $id );
		$row = self::get( $id );
		if ( $row && ! (int) $row->is_default ) {
			$next = 'active' === $row->status ? 'inactive' : 'active';
			self::update( $id, array( 'status' => $next ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=casa-kotti-surveys' ) );
		exit;
	}

	public static function delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'ckf_delete_survey_' . $id );
		$row = self::get( $id );
		if ( $row && ! (int) $row->is_default ) {
			global $wpdb;
			$q_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . CKF_Database::questions_table() . ' WHERE survey_id = %d', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			foreach ( $q_ids as $qid ) {
				if ( CKF_Answers::question_has_answers( (int) $qid ) ) {
					wp_safe_redirect( admin_url( 'admin.php?page=casa-kotti-surveys&error=has_answers' ) );
					exit;
				}
			}
			$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . CKF_Database::options_table() . ' WHERE question_id IN (SELECT id FROM ' . CKF_Database::questions_table() . ' WHERE survey_id = %d)', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->delete( CKF_Database::questions_table(), array( 'survey_id' => $id ), array( '%d' ) );
			$wpdb->delete( CKF_Database::steps_table(), array( 'survey_id' => $id ), array( '%d' ) );
			$wpdb->delete( self::table(), array( 'id' => $id ), array( '%d' ) );
			CKF_Questions::bust_cache();
		}
		wp_safe_redirect( admin_url( 'admin.php?page=casa-kotti-surveys&deleted=1' ) );
		exit;
	}

	public static function duplicate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'ckf_duplicate_survey_' . $id );
		$row = self::get( $id );
		if ( ! $row ) {
			wp_safe_redirect( admin_url( 'admin.php?page=casa-kotti-surveys' ) );
			exit;
		}
		$new = self::insert(
			array(
				'slug'          => self::unique_slug( $row->slug . '-copia' ),
				'title'         => $row->title . ' (cópia)',
				'description'   => $row->description,
				'status'        => 'inactive',
				'is_default'    => 0,
				'settings_json' => $row->settings_json,
			)
		);
		if ( $new ) {
			$map = array();
			foreach ( CKF_Steps::all( $id ) as $step ) {
				$map[ (int) $step->id ] = CKF_Steps::insert(
					array(
						'slug'        => CKF_Steps::unique_slug( $step->slug . '-copia' ),
						'title'       => $step->title,
						'description' => $step->description,
						'status'      => $step->status,
						'sort_order'  => (int) $step->sort_order,
						'survey_id'   => $new,
					)
				);
			}
			foreach ( CKF_Questions::all( $id ) as $question ) {
				$step_id = (int) $question->step_id;
				$copy_id = CKF_Questions::insert(
					array(
						'slug'          => CKF_Questions::unique_slug( $question->slug . '-copia' ),
						'title'         => $question->title,
						'description'   => $question->description,
						'type'          => $question->type,
						'required'      => $question->required,
						'status'        => $question->status,
						'step_id'       => isset( $map[ $step_id ] ) ? $map[ $step_id ] : 0,
						'survey_id'     => $new,
						'is_system'     => 0,
						'sort_order'    => (int) $question->sort_order,
						'settings_json' => $question->settings_json,
					)
				);
				$opts = array();
				foreach ( CKF_Question_Options::all_for_question( (int) $question->id ) as $opt ) {
					$opts[] = array(
						'value' => $opt->value,
						'label' => $opt->label,
					);
				}
				if ( $opts && $copy_id ) {
					CKF_Question_Options::replace( $copy_id, $opts );
				}
			}
		}
		wp_safe_redirect( admin_url( 'admin.php?page=casa-kotti-surveys&duplicated=1' ) );
		exit;
	}

	public static function page_list() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$surveys   = self::all();
		$edit      = isset( $_GET['id'] ) ? self::get( absint( $_GET['id'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		include CKF_DIR . 'admin/admin-surveys.php';
	}
}
