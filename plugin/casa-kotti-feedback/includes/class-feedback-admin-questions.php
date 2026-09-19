<?php
/**
 * Question builder admin.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CKF_Admin_Questions {
	public static function init() {
		add_action( 'admin_post_ckf_save_question', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_ckf_toggle_question', array( __CLASS__, 'toggle' ) );
		add_action( 'admin_post_ckf_reorder_question', array( __CLASS__, 'reorder' ) );
		add_action( 'admin_post_ckf_duplicate_question', array( __CLASS__, 'duplicate' ) );
		add_action( 'admin_post_ckf_delete_question', array( __CLASS__, 'delete' ) );
		add_action( 'admin_post_ckf_save_copy', array( __CLASS__, 'save_copy' ) );
		add_action( 'admin_post_ckf_save_step', array( __CLASS__, 'save_step' ) );
		add_action( 'admin_post_ckf_toggle_step', array( __CLASS__, 'toggle_step' ) );
		add_action( 'admin_post_ckf_reorder_step', array( __CLASS__, 'reorder_step' ) );
		add_action( 'admin_post_ckf_duplicate_step', array( __CLASS__, 'duplicate_step' ) );
		add_action( 'admin_post_ckf_move_question', array( __CLASS__, 'move_question' ) );
		add_action( 'admin_post_ckf_delete_step', array( __CLASS__, 'delete_step' ) );
		add_action( 'admin_post_ckf_save_order', array( __CLASS__, 'save_order' ) );
		add_action( 'wp_ajax_ckf_preview_question', array( __CLASS__, 'ajax_preview' ) );
	}

	private static function survey_id() {
		return CKF_Surveys::current_id();
	}

	private static function redirect_builder( $extra = array() ) {
		wp_safe_redirect( CKF_Surveys::builder_url( $extra ) );
		exit;
	}

	public static function page_list() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$survey_id = self::survey_id();
		$survey    = CKF_Surveys::get( $survey_id );
		if ( ! $survey ) {
			wp_safe_redirect( admin_url( 'admin.php?page=casa-kotti-surveys' ) );
			exit;
		}
		$rows  = CKF_Questions::all( $survey_id );
		$steps = CKF_Steps::all( $survey_id );
		include CKF_DIR . 'admin/admin-questions.php';
	}

	public static function page_edit() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$id       = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$question = $id ? CKF_Questions::get( $id ) : null;
		$survey_id = $question && isset( $question->survey_id ) ? (int) $question->survey_id : self::survey_id();
		$options  = $question ? CKF_Question_Options::all_for_question( (int) $question->id ) : array();
		$settings = $question ? CKF_Questions::settings( $question ) : array();
		include CKF_DIR . 'admin/admin-question-edit.php';
	}

	public static function page_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$survey_id = self::survey_id();
		$survey    = CKF_Surveys::get( $survey_id );
		$copy      = CKF_Surveys::copy( $survey_id );
		include CKF_DIR . 'admin/admin-settings.php';
	}

	public static function save_copy() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'ckf_save_copy' );
		$survey_id = isset( $_POST['survey_id'] ) ? absint( $_POST['survey_id'] ) : self::survey_id();
		CKF_Surveys::save_copy( $survey_id, wp_unslash( $_POST ) );
		wp_safe_redirect( admin_url( 'admin.php?page=casa-kotti-feedback-settings&updated=1&survey_id=' . $survey_id ) );
		exit;
	}

	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'ckf_save_question' );

		$id       = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$existing = $id ? CKF_Questions::get( $id ) : null;
		$title    = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$type     = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'text';
		$type = CKF_Questions::canonical_type( $type );
		if ( '' === $title ) {
			wp_safe_redirect( admin_url( 'admin.php?page=casa-kotti-question-edit&error=1&id=' . $id ) );
			exit;
		}

		$slug_in = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		if ( $existing && CKF_Questions::slug_locked( $existing ) ) {
			$slug = $existing->slug;
		} else {
			$slug = CKF_Questions::unique_slug( $slug_in ? $slug_in : $title, $id );
		}

		$settings = self::collect_settings( $type, $existing );
		$step_id  = isset( $_POST['step_id'] ) ? absint( $_POST['step_id'] ) : 0;
		$survey_id = isset( $_POST['survey_id'] ) ? absint( $_POST['survey_id'] ) : self::survey_id();
		if ( $step_id ) {
			$step = CKF_Steps::get( $step_id );
			if ( ! $step || (int) $step->survey_id !== $survey_id ) {
				$step_id = 0;
			}
		}
		$data     = array(
			'title'         => $title,
			'description'   => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			'type'          => $existing && (int) $existing->is_system ? $existing->type : $type,
			'required'      => ! empty( $_POST['required'] ) ? 1 : 0,
			'status'        => ( isset( $_POST['status'] ) && 'inactive' === $_POST['status'] ) ? 'inactive' : 'active',
			'step_id'       => $step_id,
			'survey_id'     => $survey_id,
			'settings_json' => wp_json_encode( $settings ),
		);

		if ( $existing ) {
			if ( ! CKF_Questions::slug_locked( $existing ) ) {
				$data['slug'] = $slug;
			}
			unset( $data['survey_id'] );
			CKF_Questions::update( $id, $data );
		} else {
			$id = CKF_Questions::insert(
				array_merge(
					$data,
					array(
						'slug'      => $slug,
						'is_system' => 0,
						'step_id'   => $step_id,
						'survey_id' => $survey_id,
					)
				)
			);
		}

		if ( in_array( $data['type'], array( 'single_choice', 'radio', 'multi_choice', 'select' ), true ) ) {
			$source = isset( $settings['source'] ) ? $settings['source'] : '';
			if ( 'fragrances' !== $source ) {
				CKF_Question_Options::replace( $id, self::collect_options() );
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=casa-kotti-question-edit&id=' . $id . '&updated=1&survey_id=' . self::survey_id() ) );
		exit;
	}

	private static function posted_show_label() {
		if ( ! isset( $_POST['show_label'] ) ) {
			return true;
		}
		$raw = wp_unslash( $_POST['show_label'] );
		if ( is_array( $raw ) ) {
			$raw = end( $raw );
		}
		return '1' === (string) $raw;
	}

	private static function collect_settings( $type, $existing ) {
		$settings = $existing ? CKF_Questions::settings( $existing ) : array();
		$settings['placeholder'] = isset( $_POST['placeholder'] ) ? sanitize_text_field( wp_unslash( $_POST['placeholder'] ) ) : '';
		$settings['show_label']  = self::posted_show_label();
		$settings['max_length']  = isset( $_POST['max_length'] ) ? absint( $_POST['max_length'] ) : 0;
		$settings['min']         = isset( $_POST['min'] ) ? (int) wp_unslash( $_POST['min'] ) : ( 'scale' === $type ? 0 : 1 );
		$settings['max']         = isset( $_POST['max'] ) ? (int) wp_unslash( $_POST['max'] ) : ( 'scale' === $type ? 10 : 5 );
		$settings['min_label']   = isset( $_POST['min_label'] ) ? sanitize_text_field( wp_unslash( $_POST['min_label'] ) ) : '';
		$settings['max_label']   = isset( $_POST['max_label'] ) ? sanitize_text_field( wp_unslash( $_POST['max_label'] ) ) : '';
		$settings['step']           = isset( $_POST['step'] ) ? sanitize_text_field( wp_unslash( $_POST['step'] ) ) : '';
		$settings['min_length']     = isset( $_POST['min_length'] ) ? absint( $_POST['min_length'] ) : 0;
		$settings['min_selections'] = isset( $_POST['min_selections'] ) ? absint( $_POST['min_selections'] ) : 0;
		$settings['max_selections'] = isset( $_POST['max_selections'] ) ? absint( $_POST['max_selections'] ) : 0;
		$settings['checkbox_label'] = isset( $_POST['checkbox_label'] ) ? sanitize_text_field( wp_unslash( $_POST['checkbox_label'] ) ) : '';
		$settings['help_text']      = isset( $_POST['help_text'] ) ? sanitize_text_field( wp_unslash( $_POST['help_text'] ) ) : '';
		$settings['default_value']  = isset( $_POST['default_value'] ) ? sanitize_text_field( wp_unslash( $_POST['default_value'] ) ) : '';
		$settings['error_message']  = isset( $_POST['error_message'] ) ? sanitize_text_field( wp_unslash( $_POST['error_message'] ) ) : '';
		$settings['width']          = isset( $_POST['width'] ) && in_array( $_POST['width'], array( '100', '50', '33' ), true ) ? $_POST['width'] : '100';
		$settings['cond_action'] = 'show';
		if ( isset( $_POST['cond_action'] ) ) {
			$action = sanitize_key( wp_unslash( $_POST['cond_action'] ) );
			if ( in_array( $action, CKF_Conditions::ACTIONS, true ) ) {
				$settings['cond_action'] = $action;
			}
		}
		$settings['cond_goto'] = isset( $_POST['cond_goto'] ) ? sanitize_title( wp_unslash( $_POST['cond_goto'] ) ) : '';
		if ( isset( $_POST['ui'] ) ) {
			$settings['ui'] = sanitize_key( wp_unslash( $_POST['ui'] ) );
		}
		if ( $existing && 'fragrance' === $existing->slug ) {
			$settings['source'] = 'fragrances';
		}

		$cond_q   = isset( $_POST['cond_question'] ) ? sanitize_title( wp_unslash( $_POST['cond_question'] ) ) : '';
		$cond_o   = isset( $_POST['cond_operator'] ) ? sanitize_key( wp_unslash( $_POST['cond_operator'] ) ) : '';
		$cond_v   = isset( $_POST['cond_value'] ) ? sanitize_text_field( wp_unslash( $_POST['cond_value'] ) ) : '';
		$existing = isset( $settings['conditions'] ) ? $settings['conditions'] : null;
		$complex  = is_array( $existing ) && ( ( isset( $existing['logic'] ) && 'or' === $existing['logic'] ) || ( ! empty( $existing['rules'] ) && count( $existing['rules'] ) > 1 ) );
		if ( $complex ) {
			$settings['conditions'] = $existing;
		} elseif ( $cond_q && $cond_o ) {
			$settings['conditions'] = array(
				'logic' => 'and',
				'rules' => array(
					array(
						'question' => $cond_q,
						'operator' => $cond_o,
						'value'    => $cond_v,
					),
				),
			);
		} else {
			unset( $settings['conditions'] );
		}
		return $settings;
	}

	private static function collect_options() {
		$labels = isset( $_POST['opt_label'] ) ? wp_unslash( $_POST['opt_label'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$values = isset( $_POST['opt_value'] ) ? wp_unslash( $_POST['opt_value'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$ids    = isset( $_POST['opt_id'] ) ? wp_unslash( $_POST['opt_id'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$out    = array();
		foreach ( (array) $labels as $i => $label ) {
			$out[] = array(
				'id'    => isset( $ids[ $i ] ) ? absint( $ids[ $i ] ) : 0,
				'label' => sanitize_text_field( $label ),
				'value' => isset( $values[ $i ] ) ? sanitize_title( $values[ $i ] ) : '',
			);
		}
		return $out;
	}

	public static function toggle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'ckf_toggle_question_' . $id );
		$row = CKF_Questions::get( $id );
		if ( $row ) {
			$next = 'active' === $row->status ? 'inactive' : 'active';
			CKF_Questions::update( $id, array( 'status' => $next ) );
		}
		wp_safe_redirect( CKF_Surveys::builder_url() );
		exit;
	}

	public static function reorder() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$id  = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$dir = isset( $_GET['dir'] ) && 'up' === $_GET['dir'] ? -10 : 10;
		check_admin_referer( 'ckf_reorder_question_' . $id );
		$row = CKF_Questions::get( $id );
		if ( $row ) {
			CKF_Questions::update( $id, array( 'sort_order' => (int) $row->sort_order + $dir ) );
		}
		wp_safe_redirect( CKF_Surveys::builder_url() );
		exit;
	}

	public static function duplicate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'ckf_duplicate_question_' . $id );
		$row = CKF_Questions::get( $id );
		if ( $row ) {
			$new = CKF_Questions::insert(
				array(
					'slug'          => CKF_Questions::unique_slug( $row->slug . '-copia' ),
					'title'         => $row->title,
					'description'   => $row->description,
					'type'          => $row->type,
					'required'      => $row->required,
					'status'        => 'inactive',
					'is_system'     => 0,
					'step_id'       => (int) $row->step_id,
					'survey_id'     => isset( $row->survey_id ) ? (int) $row->survey_id : 0,
					'settings_json' => $row->settings_json,
				)
			);
			$opts = array();
			foreach ( CKF_Question_Options::all_for_question( $id ) as $opt ) {
				$opts[] = array(
					'value' => $opt->value,
					'label' => $opt->label,
				);
			}
			if ( $opts ) {
				CKF_Question_Options::replace( $new, $opts );
			}
		}
		wp_safe_redirect( CKF_Surveys::builder_url() );
		exit;
	}

	public static function delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'ckf_delete_question_' . $id );
		$row = CKF_Questions::get( $id );
		if ( $row && ! (int) $row->is_system && ! CKF_Answers::question_has_answers( $id ) ) {
			global $wpdb;
			$wpdb->delete( CKF_Database::options_table(), array( 'question_id' => $id ), array( '%d' ) );
			$wpdb->delete( CKF_Database::questions_table(), array( 'id' => $id ), array( '%d' ) );
			CKF_Questions::bust_cache();
		}
		wp_safe_redirect( CKF_Surveys::builder_url() );
		exit;
	}

	public static function ajax_preview() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(), 403 );
		}
		check_ajax_referer( 'ckf_preview_question', 'nonce' );
		$fake = array(
			'id'          => 0,
			'slug'        => 'preview',
			'title'       => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
			'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			'type'        => isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'text',
			'settings'    => array(
				'placeholder'    => isset( $_POST['placeholder'] ) ? sanitize_text_field( wp_unslash( $_POST['placeholder'] ) ) : '',
				'min'            => isset( $_POST['min'] ) ? (int) $_POST['min'] : 1,
				'max'            => isset( $_POST['max'] ) ? (int) $_POST['max'] : 5,
				'min_label'      => isset( $_POST['min_label'] ) ? sanitize_text_field( wp_unslash( $_POST['min_label'] ) ) : '',
				'max_label'      => isset( $_POST['max_label'] ) ? sanitize_text_field( wp_unslash( $_POST['max_label'] ) ) : '',
				'help_text'      => isset( $_POST['help_text'] ) ? sanitize_text_field( wp_unslash( $_POST['help_text'] ) ) : '',
				'ui'             => isset( $_POST['ui'] ) ? sanitize_key( wp_unslash( $_POST['ui'] ) ) : '',
				'checkbox_label' => isset( $_POST['checkbox_label'] ) ? sanitize_text_field( wp_unslash( $_POST['checkbox_label'] ) ) : '',
				'show_label'     => self::posted_show_label(),
			),
			'options'     => array(),
		);
		$fake['type'] = CKF_Questions::canonical_type( $fake['type'] );
		$labels = isset( $_POST['opt_label'] ) ? wp_unslash( $_POST['opt_label'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$values = isset( $_POST['opt_value'] ) ? wp_unslash( $_POST['opt_value'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		foreach ( (array) $labels as $i => $label ) {
			$label = sanitize_text_field( $label );
			if ( ! $label ) {
				continue;
			}
			$value = isset( $values[ $i ] ) ? sanitize_title( $values[ $i ] ) : sanitize_title( $label );
			$fake['options'][] = array(
				'value' => $value,
				'label' => $label,
			);
		}
		if ( 'fragrance' === ( isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '' ) ) {
			$fake['settings']['source'] = 'fragrances';
			$fake = CKF_Questions::hydrate_dynamic_options( array( $fake ) )[0];
		}
		wp_send_json_success( array( 'html' => CKF_Renderer::preview( $fake ) ) );
	}

	public static function save_step() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'ckf_save_step' );
		$id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		if ( '' === $title ) {
			wp_safe_redirect( CKF_Surveys::builder_url( array( 'error' => 1 ) ) );
			exit;
		}
		$slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$survey_id = isset( $_POST['survey_id'] ) ? absint( $_POST['survey_id'] ) : self::survey_id();
		$data = array(
			'title'       => $title,
			'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			'status'      => ( isset( $_POST['status'] ) && 'inactive' === $_POST['status'] ) ? 'inactive' : 'active',
			'slug'        => CKF_Steps::unique_slug( $slug ? $slug : $title, $id ),
			'survey_id'   => $survey_id,
		);
		if ( $id ) {
			unset( $data['survey_id'] );
			CKF_Steps::update( $id, $data );
		} else {
			CKF_Steps::insert( $data );
		}
		wp_safe_redirect( CKF_Surveys::builder_url( array( 'updated' => 1 ) ) );
		exit;
	}

	public static function toggle_step() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'ckf_toggle_step_' . $id );
		CKF_Steps::toggle( $id );
		wp_safe_redirect( CKF_Surveys::builder_url() );
		exit;
	}

	public static function reorder_step() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$id  = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$dir = isset( $_GET['dir'] ) && 'up' === $_GET['dir'] ? -10 : 10;
		check_admin_referer( 'ckf_reorder_step_' . $id );
		CKF_Steps::reorder( $id, $dir );
		wp_safe_redirect( CKF_Surveys::builder_url() );
		exit;
	}

	public static function duplicate_step() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'ckf_duplicate_step_' . $id );
		CKF_Steps::duplicate( $id );
		wp_safe_redirect( CKF_Surveys::builder_url() );
		exit;
	}

	public static function move_question() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'ckf_move_question' );
		$id        = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$step_id   = isset( $_POST['step_id'] ) ? absint( $_POST['step_id'] ) : 0;
		$survey_id = isset( $_POST['survey_id'] ) ? absint( $_POST['survey_id'] ) : self::survey_id();
		$question  = $id ? CKF_Questions::get( $id ) : null;
		$step      = $step_id ? CKF_Steps::get( $step_id ) : null;
		if ( $question && $step && (int) $question->survey_id === $survey_id && (int) $step->survey_id === $survey_id ) {
			CKF_Questions::update( $id, array( 'step_id' => $step_id ) );
		}
		wp_safe_redirect( CKF_Surveys::builder_url() );
		exit;
	}

	public static function delete_step() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'ckf_delete_step_' . $id );
		CKF_Steps::delete( $id );
		wp_safe_redirect( CKF_Surveys::builder_url( array( 'deleted' => 1 ) ) );
		exit;
	}

	public static function save_order() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'ckf_save_order' );
		$pages = isset( $_POST['page_order'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['page_order'] ) ) : array();
		$order = 10;
		foreach ( $pages as $id ) {
			if ( $id ) {
				CKF_Steps::update( $id, array( 'sort_order' => $order ) );
				$order += 10;
			}
		}
		$questions = isset( $_POST['question_order'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['question_order'] ) ) : array();
		$qorder    = 10;
		foreach ( $questions as $id ) {
			if ( $id ) {
				CKF_Questions::update( $id, array( 'sort_order' => $qorder ) );
				$qorder += 10;
			}
		}
		wp_safe_redirect( CKF_Surveys::builder_url( array( 'updated' => 1 ) ) );
		exit;
	}

	public static function page_preview() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'casa-kotti-feedback' ), '', array( 'response' => 403 ) );
		}
		$survey_id   = self::survey_id();
		$ckf_survey  = CKF_Surveys::get( $survey_id );
		$ckf_preview = true;
		include CKF_DIR . 'admin/admin-preview.php';
	}
}
