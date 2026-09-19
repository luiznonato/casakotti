<?php
/**
 * Wizard pages (steps).
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CKF_Steps {
	const MIGRATE_KEY = 'ckf_steps_migrated';

	public static function all() {
		global $wpdb;
		$table = CKF_Database::steps_table();
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY sort_order ASC, id ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function get( $id ) {
		global $wpdb;
		$table = CKF_Database::steps_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function insert( $data ) {
		global $wpdb;
		$now  = current_time( 'mysql', true );
		$data = wp_parse_args(
			$data,
			array(
				'uuid'        => wp_generate_uuid4(),
				'description' => '',
				'status'      => 'active',
				'sort_order'  => self::next_order(),
			)
		);
		$ok = $wpdb->insert(
			CKF_Database::steps_table(),
			array(
				'uuid'        => $data['uuid'],
				'slug'        => $data['slug'],
				'title'       => $data['title'],
				'description' => $data['description'],
				'sort_order'  => (int) $data['sort_order'],
				'status'      => $data['status'],
				'created_at'  => $now,
				'updated_at'  => $now,
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		);
		CKF_Questions::bust_cache();
		return $ok ? (int) $wpdb->insert_id : 0;
	}

	public static function update( $id, $data ) {
		global $wpdb;
		$data['updated_at'] = current_time( 'mysql', true );
		$wpdb->update( CKF_Database::steps_table(), $data, array( 'id' => absint( $id ) ) );
		CKF_Questions::bust_cache();
	}

	public static function next_order() {
		global $wpdb;
		$table = CKF_Database::steps_table();
		$max   = (int) $wpdb->get_var( "SELECT MAX(sort_order) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $max + 10;
	}

	public static function unique_slug( $base, $ignore = 0 ) {
		$slug = sanitize_title( $base );
		if ( '' === $slug ) {
			$slug = 'pagina';
		}
		$try = $slug;
		$i   = 2;
		global $wpdb;
		$table = CKF_Database::steps_table();
		while ( true ) {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s", $try ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ! $row || (int) $row->id === (int) $ignore ) {
				return $try;
			}
			$try = $slug . '-' . $i;
			$i++;
		}
	}

	/**
	 * One-time: one page per question, then merge name/email/consent.
	 */
	public static function migrate_from_questions() {
		if ( get_option( self::MIGRATE_KEY ) ) {
			return;
		}
		global $wpdb;
		$steps_table = CKF_Database::steps_table();
		$q_table     = CKF_Database::questions_table();
		$existing    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$steps_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$questions   = $wpdb->get_results( "SELECT * FROM {$q_table} ORDER BY sort_order ASC, id ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $questions ) {
			update_option( self::MIGRATE_KEY, '1', false );
			return;
		}

		$identity = array( 'customer_name', 'customer_email', 'marketing_consent' );
		$ident_id = 0;
		$order    = 10;

		if ( $existing ) {
			foreach ( self::all() as $step ) {
				if ( 'identificacao' === $step->slug ) {
					$ident_id = (int) $step->id;
				}
			}
		}

		foreach ( $questions as $question ) {
			$is_ident = in_array( $question->slug, $identity, true );
			if ( $is_ident ) {
				if ( ! $ident_id ) {
					$ident_id = self::insert(
						array(
							'slug'        => 'identificacao',
							'title'       => 'Quase lá',
							'description' => 'Se quiser, conte para a gente quem você é.',
							'sort_order'  => 900,
						)
					);
				}
				$wpdb->update(
					$q_table,
					array(
						'step_id'    => $ident_id,
						'updated_at' => current_time( 'mysql', true ),
					),
					array( 'id' => (int) $question->id ),
					array( '%d', '%s' ),
					array( '%d' )
				);
				if ( 'customer_name' === $question->slug ) {
					$wpdb->update( $q_table, array( 'title' => 'Nome', 'description' => '' ), array( 'id' => (int) $question->id ) );
				}
				if ( 'customer_email' === $question->slug ) {
					$wpdb->update( $q_table, array( 'title' => 'E-mail', 'description' => '' ), array( 'id' => (int) $question->id ) );
				}
				if ( 'marketing_consent' === $question->slug ) {
					$wpdb->update(
						$q_table,
						array(
							'title'         => 'Novidades',
							'description'   => '',
							'settings_json' => wp_json_encode(
								array_merge(
									CKF_Questions::settings( $question ),
									array(
										'ui'             => 'checkbox',
										'checkbox_label' => 'Quero receber novidades, lançamentos e conteúdos da Casa Kotti.',
									)
								)
							),
						),
						array( 'id' => (int) $question->id )
					);
				}
				continue;
			}

			$step_id = (int) $question->step_id;
			if ( $step_id && self::get( $step_id ) ) {
				continue;
			}
			$step_id = self::insert(
				array(
					'slug'        => self::unique_slug( $question->slug ),
					'title'       => $question->title,
					'description' => '',
					'sort_order'  => $order,
				)
			);
			$wpdb->update( $q_table, array( 'step_id' => $step_id ), array( 'id' => (int) $question->id ), array( '%d' ), array( '%d' ) );
			$order += 10;
		}

		$copy = CKF_Questions::copy();
		if ( empty( $copy['privacy_note'] ) ) {
			$copy['privacy_note'] = 'Ao enviar, você concorda com o tratamento das informações conforme nossa Política de Privacidade.';
			update_option( 'ckf_copy', $copy, false );
		}

		update_option( self::MIGRATE_KEY, '1', false );
		CKF_Questions::bust_cache();
	}

	public static function delete( $id ) {
		global $wpdb;
		$id = absint( $id );
		if ( ! $id ) {
			return;
		}
		$wpdb->update( CKF_Database::questions_table(), array( 'step_id' => 0 ), array( 'step_id' => $id ), array( '%d' ), array( '%d' ) );
		$wpdb->delete( CKF_Database::steps_table(), array( 'id' => $id ), array( '%d' ) );
		CKF_Questions::bust_cache();
	}

	public static function toggle( $id ) {
		$row = self::get( $id );
		if ( ! $row ) {
			return;
		}
		$next = 'active' === $row->status ? 'inactive' : 'active';
		self::update( $id, array( 'status' => $next ) );
	}

	public static function reorder( $id, $delta ) {
		$row = self::get( $id );
		if ( $row ) {
			self::update( $id, array( 'sort_order' => (int) $row->sort_order + (int) $delta ) );
		}
	}

	public static function duplicate( $id ) {
		$row = self::get( $id );
		if ( ! $row ) {
			return 0;
		}
		$new = self::insert(
			array(
				'slug'        => self::unique_slug( $row->slug . '-copia' ),
				'title'       => $row->title,
				'description' => $row->description,
				'status'      => 'inactive',
			)
		);
		if ( ! $new ) {
			return 0;
		}
		foreach ( CKF_Questions::all() as $question ) {
			if ( (int) $question->step_id !== (int) $id ) {
				continue;
			}
			$copy_id = CKF_Questions::insert(
				array(
					'slug'          => CKF_Questions::unique_slug( $question->slug . '-copia' ),
					'title'         => $question->title,
					'description'   => $question->description,
					'type'          => $question->type,
					'required'      => $question->required,
					'status'        => 'inactive',
					'step_id'       => $new,
					'is_system'     => 0,
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
		return $new;
	}
}
