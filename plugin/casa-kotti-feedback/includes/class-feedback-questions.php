<?php
/**
 * Questions repository and form definition cache.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CKF_Questions {
	const CACHE_KEY = 'ckf_form_definition';
	const SEED_KEY  = 'ckf_questions_seeded';

	const TYPES = array(
		'text'          => 'Texto curto',
		'textarea'      => 'Texto longo',
		'email'         => 'E-mail',
		'tel'           => 'Telefone',
		'number'        => 'Número',
		'date'          => 'Data',
		'radio'         => 'Radio',
		'checkbox'      => 'Checkbox',
		'single_choice' => 'Seleção única',
		'multi_choice'  => 'Multiseleção',
		'select'        => 'Select',
		'scale'         => 'Escala numérica',
		'stars'         => 'Estrelas',
		'yes_no'        => 'Sim/Não',
		'consent'       => 'Consentimento / aceite',
		'info'          => 'Texto informativo',
		'hidden'        => 'Campo oculto',
	);

	const TYPE_ALIASES = array(
		'phone'    => 'tel',
		'telefone' => 'tel',
	);

	const SYSTEM_SLUGS = array(
		'product',
		'fragrance',
		'overall_rating',
		'intensity',
		'refil_target',
		'product_specific_home_spray',
		'product_specific_difusor',
		'product_specific_automotivo',
		'performance',
		'presentation_rating',
		'repurchase_intent',
		'nps_score',
		'improvement_comment',
		'positive_comment',
		'customer_name',
		'customer_email',
		'marketing_consent',
	);

	const SYSTEM_COLUMNS = array(
		'product'                      => 'product',
		'overall_rating'               => 'overall_rating',
		'intensity'                    => 'intensity',
		'product_specific_home_spray'  => 'product_specific_answer',
		'product_specific_difusor'     => 'product_specific_answer',
		'product_specific_automotivo'  => 'product_specific_answer',
		'performance'                  => 'performance',
		'presentation_rating'          => 'presentation_rating',
		'repurchase_intent'            => 'repurchase_intent',
		'nps_score'                    => 'nps_score',
		'improvement_comment'          => 'improvement_comment',
		'positive_comment'             => 'positive_comment',
		'customer_name'                => 'customer_name',
		'customer_email'               => 'customer_email',
		'marketing_consent'            => 'marketing_consent',
	);

	public static function bust_cache() {
		delete_transient( self::CACHE_KEY );
		if ( class_exists( 'CKF_Surveys' ) ) {
			foreach ( CKF_Surveys::all() as $survey ) {
				delete_transient( self::CACHE_KEY . '_' . (int) $survey->id );
			}
		}
	}

	public static function types() {
		return apply_filters( 'ckf_field_types', self::TYPES );
	}

	public static function canonical_type( $type ) {
		$type = sanitize_key( (string) $type );
		$aliases = apply_filters( 'ckf_field_type_aliases', self::TYPE_ALIASES );
		if ( isset( $aliases[ $type ] ) ) {
			$type = $aliases[ $type ];
		}
		$types = self::types();
		return isset( $types[ $type ] ) ? $type : 'text';
	}

	public static function all( $survey_id = null ) {
		global $wpdb;
		$table = CKF_Database::questions_table();
		if ( null === $survey_id ) {
			return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY sort_order ASC, id ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE survey_id = %d ORDER BY sort_order ASC, id ASC", absint( $survey_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function get( $id ) {
		global $wpdb;
		$table = CKF_Database::questions_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function by_slug( $slug ) {
		global $wpdb;
		$table = CKF_Database::questions_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", sanitize_title( $slug ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function settings( $question ) {
		$raw = is_object( $question ) ? $question->settings_json : '';
		$data = json_decode( (string) $raw, true );
		return is_array( $data ) ? $data : array();
	}

	public static function default_copy() {
		return array(
			'intro_title'   => __( 'Como foi sua experiência com a Casa Kotti?', 'casa-kotti-feedback' ),
			'intro_lead'    => __( 'Queremos saber como foi ter um pouco da Casa Kotti com você.', 'casa-kotti-feedback' ),
			'intro_body'    => __( 'Sua opinião nos ajuda a aperfeiçoar cada detalhe — da fragrância à experiência de receber o produto.', 'casa-kotti-feedback' ),
			'intro_note'    => __( 'Leva menos de 2 minutos.', 'casa-kotti-feedback' ),
			'intro_button'  => __( 'Começar', 'casa-kotti-feedback' ),
			'thanks_title'  => __( 'Obrigado por compartilhar.', 'casa-kotti-feedback' ),
			'thanks_body'   => __( 'Cada resposta ajuda a Casa Kotti a aperfeiçoar aquilo que fazemos e criar experiências cada vez melhores.', 'casa-kotti-feedback' ),
			'thanks_button' => __( 'Voltar para Casa Kotti', 'casa-kotti-feedback' ),
			'thanks_url'    => '',
			'privacy_note'  => __( 'Ao enviar, você concorda com o tratamento das informações conforme nossa Política de Privacidade.', 'casa-kotti-feedback' ),
		);
	}

	public static function sanitize_copy( $data ) {
		$current = self::default_copy();
		foreach ( $current as $key => $value ) {
			if ( isset( $data[ $key ] ) ) {
				$current[ $key ] = sanitize_textarea_field( $data[ $key ] );
			}
		}
		if ( ! empty( $current['thanks_url'] ) ) {
			$current['thanks_url'] = esc_url_raw( $current['thanks_url'] );
		}
		return $current;
	}

	public static function copy( $survey_id = 0 ) {
		if ( $survey_id && class_exists( 'CKF_Surveys' ) ) {
			return CKF_Surveys::copy( $survey_id );
		}
		$saved = get_option( 'ckf_copy', array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::default_copy() );
	}

	public static function save_copy( $data ) {
		$current = self::sanitize_copy( $data );
		update_option( 'ckf_copy', $current, false );
		self::bust_cache();
	}

	/**
	 * Public wizard definition: questions + resolved options, few queries.
	 *
	 * @return array
	 */
	public static function public_definition( $survey_id = 0 ) {
		$wizard = self::public_wizard( $survey_id );
		return $wizard['questions'];
	}

	/**
	 * Steps + questions for the public wizard.
	 *
	 * @param int $survey_id Survey.
	 * @return array{steps:array,questions:array,survey_id:int}
	 */
	public static function public_wizard( $survey_id = 0 ) {
		$survey_id = absint( $survey_id );
		if ( ! $survey_id && class_exists( 'CKF_Surveys' ) ) {
			$survey_id = CKF_Surveys::default_id();
		}
		$key    = $survey_id ? self::CACHE_KEY . '_' . $survey_id : self::CACHE_KEY;
		$cached = get_transient( $key );
		if ( is_array( $cached ) && isset( $cached['steps'], $cached['questions'] ) ) {
			$cached['questions'] = self::hydrate_dynamic_options( $cached['questions'] );
			$cached['survey_id'] = $survey_id;
			return $cached;
		}

		$questions = array();
		foreach ( self::all( $survey_id ? $survey_id : null ) as $row ) {
			if ( $survey_id && isset( $row->survey_id ) && (int) $row->survey_id !== $survey_id ) {
				continue;
			}
			if ( 'active' !== $row->status ) {
				continue;
			}
			$questions[] = self::normalize( $row );
		}
		$ids = wp_list_pluck( $questions, 'id' );
		$options_by_q = CKF_Question_Options::for_questions( $ids );
		foreach ( $questions as &$question ) {
			$question['options'] = isset( $options_by_q[ $question['id'] ] ) ? $options_by_q[ $question['id'] ] : array();
		}
		unset( $question );

		$steps = array();
		$step_rows = $survey_id ? CKF_Steps::all( $survey_id ) : CKF_Steps::all();
		foreach ( $step_rows as $step ) {
			if ( 'active' !== $step->status ) {
				continue;
			}
			$steps[] = array(
				'id'          => (int) $step->id,
				'slug'        => $step->slug,
				'title'       => $step->title,
				'description' => (string) $step->description,
				'sort_order'  => (int) $step->sort_order,
			);
		}

		$payload = array(
			'steps'      => $steps,
			'questions'  => $questions,
			'survey_id'  => $survey_id,
		);
		set_transient( $key, $payload, HOUR_IN_SECONDS );
		$payload['questions'] = self::hydrate_dynamic_options( $questions );
		return $payload;
	}

	/**
	 * Inject live fragrance options (never cached as static labels).
	 *
	 * @param array $questions Definition.
	 * @return array
	 */
	public static function hydrate_dynamic_options( $questions ) {
		$frags = array();
		foreach ( CKF_Database::active_fragrances() as $row ) {
			$frags[] = array(
				'value' => $row->slug,
				'label' => $row->name,
			);
		}
		foreach ( $questions as &$question ) {
			$source = isset( $question['settings']['source'] ) ? $question['settings']['source'] : '';
			if ( 'fragrances' === $source || 'fragrance' === $question['slug'] ) {
				$question['options'] = $frags;
				$question['settings']['source'] = 'fragrances';
			}
		}
		unset( $question );
		return $questions;
	}

	public static function normalize( $row ) {
		return array(
			'id'          => (int) $row->id,
			'uuid'        => $row->uuid,
			'slug'        => $row->slug,
			'title'       => $row->title,
			'description' => (string) $row->description,
			'type'        => $row->type,
			'required'    => (int) $row->required,
			'status'      => $row->status,
			'sort_order'  => (int) $row->sort_order,
			'step_id'     => isset( $row->step_id ) ? (int) $row->step_id : 0,
			'survey_id'   => isset( $row->survey_id ) ? (int) $row->survey_id : 0,
			'is_system'   => (int) $row->is_system,
			'settings'    => self::settings( $row ),
			'options'     => array(),
		);
	}

	public static function slug_locked( $question ) {
		if ( ! $question ) {
			return false;
		}
		if ( (int) $question->is_system ) {
			return true;
		}
		return CKF_Answers::question_has_answers( (int) $question->id );
	}

	public static function insert( $data ) {
		global $wpdb;
		$now  = current_time( 'mysql', true );
		$data = wp_parse_args(
			$data,
			array(
				'uuid'          => wp_generate_uuid4(),
				'required'      => 0,
				'status'        => 'active',
				'sort_order'    => self::next_order(),
				'step_id'       => 0,
				'survey_id'     => class_exists( 'CKF_Surveys' ) ? CKF_Surveys::default_id() : 0,
				'is_system'     => 0,
				'settings_json' => '{}',
				'description'   => '',
			)
		);
		$ok = $wpdb->insert(
			CKF_Database::questions_table(),
			array(
				'uuid'          => $data['uuid'],
				'slug'          => $data['slug'],
				'title'         => $data['title'],
				'description'   => $data['description'],
				'type'          => $data['type'],
				'required'      => (int) $data['required'],
				'status'        => $data['status'],
				'sort_order'    => (int) $data['sort_order'],
				'step_id'       => (int) $data['step_id'],
				'survey_id'     => (int) $data['survey_id'],
				'is_system'     => (int) $data['is_system'],
				'settings_json' => $data['settings_json'],
				'created_at'    => $now,
				'updated_at'    => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s' )
		);
		self::bust_cache();
		return $ok ? (int) $wpdb->insert_id : 0;
	}

	public static function update( $id, $data ) {
		global $wpdb;
		$data['updated_at'] = current_time( 'mysql', true );
		$wpdb->update( CKF_Database::questions_table(), $data, array( 'id' => absint( $id ) ) );
		self::bust_cache();
	}

	public static function next_order() {
		global $wpdb;
		$table = CKF_Database::questions_table();
		$max   = (int) $wpdb->get_var( "SELECT MAX(sort_order) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $max + 10;
	}

	public static function unique_slug( $base, $ignore_id = 0 ) {
		$slug = sanitize_title( $base );
		if ( '' === $slug ) {
			$slug = 'pergunta';
		}
		$try = $slug;
		$i   = 2;
		while ( true ) {
			$row = self::by_slug( $try );
			if ( ! $row || (int) $row->id === (int) $ignore_id ) {
				return $try;
			}
			$try = $slug . '-' . $i;
			$i++;
		}
	}

	/**
	 * Seed current questionnaire once.
	 */
	public static function seed_defaults() {
		if ( get_option( self::SEED_KEY ) ) {
			return;
		}
		global $wpdb;
		$table = CKF_Database::questions_table();
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $count > 0 ) {
			update_option( self::SEED_KEY, '1', false );
			return;
		}

		require CKF_DIR . 'includes/seed-questions.php';
		ckf_seed_default_questions();
		update_option( self::SEED_KEY, '1', false );
		self::bust_cache();
	}
}
