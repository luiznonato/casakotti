<?php
/**
 * Conditional visibility. Supports nested AND/OR groups.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CKF_Conditions {
	/**
	 * Whether a question should appear given current answers.
	 *
	 * @param array $question Normalized question.
	 * @param array $answers  slug => scalar|array.
	 * @return bool
	 */
	public static function applies( $question, $answers ) {
		$settings = isset( $question['settings'] ) ? $question['settings'] : array();
		if ( empty( $settings['conditions'] ) || empty( $settings['conditions']['rules'] ) ) {
			return true;
		}
		return self::eval_group( $settings['conditions'], $answers );
	}

	public static function eval_group( $group, $answers ) {
		$logic = isset( $group['logic'] ) && 'or' === $group['logic'] ? 'or' : 'and';
		$rules = isset( $group['rules'] ) && is_array( $group['rules'] ) ? $group['rules'] : array();
		if ( ! $rules ) {
			return true;
		}
		$results = array();
		foreach ( $rules as $rule ) {
			if ( isset( $rule['rules'] ) ) {
				$results[] = self::eval_group( $rule, $answers );
			} else {
				$results[] = self::eval_rule( $rule, $answers );
			}
		}
		if ( 'or' === $logic ) {
			return in_array( true, $results, true );
		}
		return ! in_array( false, $results, true );
	}

	public static function eval_rule( $rule, $answers ) {
		$slug     = isset( $rule['question'] ) ? $rule['question'] : '';
		$operator = isset( $rule['operator'] ) ? $rule['operator'] : 'equals';
		$expected = isset( $rule['value'] ) ? $rule['value'] : '';
		$actual   = isset( $answers[ $slug ] ) ? $answers[ $slug ] : '';
		$values   = is_array( $actual ) ? $actual : ( '' === $actual || null === $actual ? array() : array( (string) $actual ) );
		$flat     = array_map( 'strval', $values );

		switch ( $operator ) {
			case 'not_equals':
				return ! in_array( (string) $expected, $flat, true );
			case 'contains':
				foreach ( $flat as $item ) {
					if ( false !== stripos( $item, (string) $expected ) ) {
						return true;
					}
				}
				return false;
			case 'not_contains':
				foreach ( $flat as $item ) {
					if ( false !== stripos( $item, (string) $expected ) ) {
						return false;
					}
				}
				return true;
			case 'contains_any':
				$needles = is_array( $expected ) ? $expected : preg_split( '/\s*,\s*/', (string) $expected );
				return (bool) array_intersect( array_map( 'strval', $needles ), $flat );
			case 'filled':
				return count( $flat ) > 0;
			case 'empty':
				return 0 === count( $flat );
			case 'gt':
			case 'lt':
			case 'gte':
			case 'lte':
				$num = isset( $flat[0] ) ? (float) $flat[0] : 0;
				$exp = (float) $expected;
				if ( 'gt' === $operator ) {
					return $num > $exp;
				}
				if ( 'lt' === $operator ) {
					return $num < $exp;
				}
				if ( 'gte' === $operator ) {
					return $num >= $exp;
				}
				return $num <= $exp;
			case 'equals':
			default:
				if ( is_array( $expected ) ) {
					return (bool) array_intersect( array_map( 'strval', $expected ), $flat );
				}
				return in_array( (string) $expected, $flat, true );
		}
	}

	public static function operators() {
		return array(
			'equals'       => __( 'é igual a', 'casa-kotti-feedback' ),
			'not_equals'   => __( 'é diferente de', 'casa-kotti-feedback' ),
			'contains'     => __( 'contém', 'casa-kotti-feedback' ),
			'not_contains' => __( 'não contém', 'casa-kotti-feedback' ),
			'contains_any' => __( 'contém qualquer um', 'casa-kotti-feedback' ),
			'filled'       => __( 'está preenchido', 'casa-kotti-feedback' ),
			'empty'        => __( 'não está preenchido', 'casa-kotti-feedback' ),
			'gt'           => __( 'maior que', 'casa-kotti-feedback' ),
			'lt'           => __( 'menor que', 'casa-kotti-feedback' ),
			'gte'          => __( 'maior ou igual', 'casa-kotti-feedback' ),
			'lte'          => __( 'menor ou igual', 'casa-kotti-feedback' ),
		);
	}

	public static function summarize( $settings ) {
		if ( empty( $settings['conditions']['rules'][0] ) ) {
			return '—';
		}
		$rule = $settings['conditions']['rules'][0];
		if ( empty( $rule['question'] ) ) {
			return '—';
		}
		$ops = self::operators();
		$op  = isset( $ops[ $rule['operator'] ] ) ? $ops[ $rule['operator'] ] : $rule['operator'];
		$val = isset( $rule['value'] ) ? $rule['value'] : '';
		return $rule['question'] . ' ' . $op . ( '' !== $val ? ' ' . $val : '' );
	}
}
