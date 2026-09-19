<?php
/**
 * Conditional engine: show / hide / goto page / end.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CKF_Conditions {
	const ACTIONS = array( 'show', 'hide', 'goto', 'end' );

	/**
	 * Whether a question should appear given current answers.
	 *
	 * goto/end never hide the question; they change routing after the page.
	 *
	 * @param array $question Normalized question.
	 * @param array $answers  slug => scalar|array.
	 * @param array $opts     Optional {productLocked}.
	 * @return bool
	 */
	public static function applies( $question, $answers, $opts = array() ) {
		if ( isset( $question['type'] ) && 'hidden' === $question['type'] ) {
			return false;
		}
		if ( ! empty( $opts['productLocked'] ) && ! empty( $answers['product'] ) && isset( $question['slug'] ) && 'product' === $question['slug'] ) {
			return false;
		}
		$settings = isset( $question['settings'] ) ? $question['settings'] : array();
		$action   = self::action( $settings );
		if ( empty( $settings['conditions'] ) || empty( $settings['conditions']['rules'] ) ) {
			return true;
		}
		$match = self::eval_group( $settings['conditions'], $answers );
		if ( 'hide' === $action ) {
			return ! $match;
		}
		if ( 'goto' === $action || 'end' === $action ) {
			return true;
		}
		return $match;
	}

	public static function action( $settings ) {
		$action = isset( $settings['cond_action'] ) ? $settings['cond_action'] : 'show';
		return in_array( $action, self::ACTIONS, true ) ? $action : 'show';
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

	public static function jump_from( $questions, $answers ) {
		foreach ( $questions as $question ) {
			$settings = isset( $question['settings'] ) ? $question['settings'] : array();
			$action   = self::action( $settings );
			if ( 'goto' !== $action && 'end' !== $action ) {
				continue;
			}
			if ( empty( $settings['conditions']['rules'] ) ) {
				continue;
			}
			if ( ! self::eval_group( $settings['conditions'], $answers ) ) {
				continue;
			}
			if ( 'end' === $action ) {
				return array( 'type' => 'end' );
			}
			$goto = isset( $settings['cond_goto'] ) ? sanitize_title( $settings['cond_goto'] ) : '';
			if ( $goto ) {
				return array(
					'type' => 'goto',
					'slug' => $goto,
				);
			}
		}
		return null;
	}

	public static function questions_for_step( $step, $questions ) {
		$out = array();
		$sid = isset( $step['id'] ) ? (int) $step['id'] : 0;
		foreach ( $questions as $question ) {
			$qid = isset( $question['step_id'] ) ? (int) $question['step_id'] : 0;
			if ( $sid ) {
				if ( $qid === $sid ) {
					$out[] = $question;
				}
			} elseif ( ! $qid && isset( $question['slug'], $step['slug'] ) && $question['slug'] === $step['slug'] ) {
				$out[] = $question;
			}
		}
		return $out;
	}

	/**
	 * Pages the respondent should visit for these answers.
	 *
	 * @param array $steps     Wizard steps.
	 * @param array $questions Questions.
	 * @param array $answers   Answers.
	 * @param array $opts      Optional.
	 * @return array
	 */
	public static function route( $steps, $questions, $answers, $opts = array() ) {
		$ordered = is_array( $steps ) ? $steps : array();
		foreach ( $questions as $question ) {
			if ( empty( $question['step_id'] ) ) {
				$ordered[] = array(
					'id'          => 0,
					'slug'        => $question['slug'],
					'title'       => $question['title'],
					'description' => '',
				);
			}
		}
		$visited = array();
		$seen    = array();
		$index   = 0;
		$guard   = 0;
		$count   = count( $ordered );
		while ( $index < $count && $guard < 200 ) {
			$guard++;
			$step = $ordered[ $index ];
			$key  = ( isset( $step['id'] ) ? (int) $step['id'] : 0 ) . ':' . ( isset( $step['slug'] ) ? $step['slug'] : '' );
			if ( isset( $seen[ $key ] ) ) {
				break;
			}
			$visible = array();
			foreach ( self::questions_for_step( $step, $questions ) as $question ) {
				if ( self::applies( $question, $answers, $opts ) ) {
					$visible[] = $question;
				}
			}
			if ( ! $visible ) {
				$index++;
				continue;
			}
			$seen[ $key ] = true;
			$visited[]    = $step;
			$jump         = self::jump_from( $visible, $answers );
			if ( $jump && 'end' === $jump['type'] ) {
				break;
			}
			if ( $jump && 'goto' === $jump['type'] ) {
				$next_index = -1;
				foreach ( $ordered as $i => $candidate ) {
					if ( isset( $candidate['slug'] ) && $candidate['slug'] === $jump['slug'] ) {
						$next_index = $i;
					}
				}
				if ( $next_index >= 0 && $next_index !== $index ) {
					$index = $next_index;
					continue;
				}
			}
			$index++;
		}
		return $visited;
	}

	/**
	 * Questions that must be validated/stored for this route.
	 *
	 * @return array<string,array> slug => question
	 */
	public static function applicable_on_route( $steps, $questions, $answers, $opts = array() ) {
		$out = array();
		foreach ( self::route( $steps, $questions, $answers, $opts ) as $step ) {
			foreach ( self::questions_for_step( $step, $questions ) as $question ) {
				if ( ! self::applies( $question, $answers, $opts ) ) {
					continue;
				}
				if ( in_array( $question['type'], array( 'info', 'hidden' ), true ) ) {
					continue;
				}
				$out[ $question['slug'] ] = $question;
			}
		}
		return $out;
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
		$ops    = self::operators();
		$op     = isset( $ops[ $rule['operator'] ] ) ? $ops[ $rule['operator'] ] : $rule['operator'];
		$val    = isset( $rule['value'] ) ? $rule['value'] : '';
		$action = self::action( $settings );
		$label  = $rule['question'] . ' ' . $op . ( '' !== $val ? ' ' . $val : '' );
		if ( 'goto' === $action && ! empty( $settings['cond_goto'] ) ) {
			$label .= ' → ' . $settings['cond_goto'];
		}
		if ( 'end' === $action ) {
			$label .= ' → fim';
		}
		if ( 'hide' === $action ) {
			$label .= ' → ocultar';
		}
		return $label;
	}
}
