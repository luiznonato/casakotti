<?php
/**
 * Dynamic answer rows.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CKF_Answers {
	public static function question_has_answers( $question_id ) {
		global $wpdb;
		$table = CKF_Database::answers_table();
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE question_id = %d", absint( $question_id ) ) ) > 0; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function option_has_answers( $question_id, $value ) {
		global $wpdb;
		$table = CKF_Database::answers_table();
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE question_id = %d AND answer_value = %s", absint( $question_id ), $value ) ) > 0; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function slug_has_answers( $slug ) {
		global $wpdb;
		$table = CKF_Database::answers_table();
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE question_slug = %s", $slug ) ) > 0; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Persist one feedback's answers. Multi-choice = one row per value.
	 *
	 * @param int   $feedback_id Parent row.
	 * @param array $rows        Normalized answer rows.
	 */
	public static function insert_many( $feedback_id, $rows ) {
		global $wpdb;
		$table = CKF_Database::answers_table();
		$now   = current_time( 'mysql', true );
		foreach ( $rows as $row ) {
			$wpdb->insert(
				$table,
				array(
					'feedback_id'   => $feedback_id,
					'question_id'   => (int) $row['question_id'],
					'question_slug' => $row['question_slug'],
					'answer_value'  => substr( (string) $row['answer_value'], 0, 190 ),
					'answer_text'   => $row['answer_text'],
					'created_at'    => $now,
				),
				array( '%d', '%d', '%s', '%s', '%s', '%s' )
			);
		}
	}

	public static function for_feedback( $feedback_id ) {
		global $wpdb;
		$table = CKF_Database::answers_table();
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE feedback_id = %d ORDER BY id ASC", absint( $feedback_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function custom_slugs() {
		$slugs = array();
		foreach ( CKF_Questions::all() as $row ) {
			if ( ! (int) $row->is_system && 'info' !== $row->type ) {
				$slugs[] = $row->slug;
			}
		}
		return $slugs;
	}

	public static function values_for_feedback_slugs( $feedback_id, $slugs ) {
		if ( ! $slugs ) {
			return array();
		}
		global $wpdb;
		$table = CKF_Database::answers_table();
		$placeholders = implode( ',', array_fill( 0, count( $slugs ), '%s' ) );
		$args = array_merge( array( absint( $feedback_id ) ), $slugs );
		$sql  = "SELECT question_slug, answer_value, answer_text FROM {$table} WHERE feedback_id = %d AND question_slug IN ({$placeholders})";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$out  = array();
		foreach ( $rows as $row ) {
			if ( ! isset( $out[ $row->question_slug ] ) ) {
				$out[ $row->question_slug ] = array();
			}
			$out[ $row->question_slug ][] = $row->answer_text ? $row->answer_text : $row->answer_value;
		}
		return $out;
	}
}
