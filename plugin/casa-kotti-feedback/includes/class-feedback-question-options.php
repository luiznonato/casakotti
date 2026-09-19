<?php
/**
 * Question option rows.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CKF_Question_Options {
	/**
	 * Options for many questions (one query).
	 *
	 * @param array $ids Question IDs.
	 * @return array<int, array<int, array{value:string,label:string,id:int}>>
	 */
	public static function for_questions( $ids ) {
		$ids = array_filter( array_map( 'absint', (array) $ids ) );
		if ( ! $ids ) {
			return array();
		}
		global $wpdb;
		$table  = CKF_Database::options_table();
		$in     = implode( ',', $ids );
		$rows   = $wpdb->get_results( "SELECT * FROM {$table} WHERE question_id IN ({$in}) AND status = 'active' ORDER BY sort_order ASC, id ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$grouped = array();
		foreach ( $rows as $row ) {
			$grouped[ (int) $row->question_id ][] = array(
				'id'    => (int) $row->id,
				'value' => $row->value,
				'label' => $row->label,
			);
		}
		return $grouped;
	}

	public static function for_question( $question_id ) {
		$all = self::for_questions( array( $question_id ) );
		return isset( $all[ $question_id ] ) ? $all[ $question_id ] : array();
	}

	public static function all_for_question( $question_id ) {
		global $wpdb;
		$table = CKF_Database::options_table();
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE question_id = %d ORDER BY sort_order ASC, id ASC", absint( $question_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function replace( $question_id, $options ) {
		global $wpdb;
		$table = CKF_Database::options_table();
		$now   = current_time( 'mysql', true );
		$existing = self::all_for_question( $question_id );
		$keep     = array();
		$order    = 10;
		foreach ( (array) $options as $option ) {
			$label = isset( $option['label'] ) ? sanitize_text_field( $option['label'] ) : '';
			$value = isset( $option['value'] ) ? sanitize_title( $option['value'] ) : '';
			$id    = isset( $option['id'] ) ? absint( $option['id'] ) : 0;
			if ( '' === $label ) {
				continue;
			}
			if ( '' === $value ) {
				$value = sanitize_title( $label );
			}
			if ( $id ) {
				$locked = CKF_Answers::option_has_answers( $question_id, $value );
				$row    = null;
				foreach ( $existing as $ex ) {
					if ( (int) $ex->id === $id ) {
						$row = $ex;
						break;
					}
				}
				$saved_value = ( $row && ( $locked || CKF_Answers::option_has_answers( $question_id, $row->value ) ) ) ? $row->value : $value;
				$wpdb->update(
					$table,
					array(
						'label'      => $label,
						'value'      => $saved_value,
						'sort_order' => $order,
						'status'     => 'active',
						'updated_at' => $now,
					),
					array( 'id' => $id, 'question_id' => $question_id ),
					array( '%s', '%s', '%d', '%s', '%s' ),
					array( '%d', '%d' )
				);
				$keep[] = $id;
			} else {
				$wpdb->insert(
					$table,
					array(
						'question_id' => $question_id,
						'value'       => $value,
						'label'       => $label,
						'sort_order'  => $order,
						'status'      => 'active',
						'created_at'  => $now,
						'updated_at'  => $now,
					),
					array( '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
				);
				$keep[] = (int) $wpdb->insert_id;
			}
			$order += 10;
		}

		foreach ( $existing as $ex ) {
			if ( ! in_array( (int) $ex->id, $keep, true ) ) {
				if ( CKF_Answers::option_has_answers( $question_id, $ex->value ) ) {
					$wpdb->update( $table, array( 'status' => 'inactive', 'updated_at' => $now ), array( 'id' => (int) $ex->id ), array( '%s', '%s' ), array( '%d' ) );
				} else {
					$wpdb->delete( $table, array( 'id' => (int) $ex->id ), array( '%d' ) );
				}
			}
		}
		CKF_Questions::bust_cache();
	}

	public static function insert_many( $question_id, $map ) {
		$order = 10;
		$rows  = array();
		foreach ( $map as $value => $label ) {
			$rows[] = array(
				'value' => $value,
				'label' => $label,
				'id'    => 0,
			);
			$order += 10;
		}
		self::replace( $question_id, $rows );
	}
}
