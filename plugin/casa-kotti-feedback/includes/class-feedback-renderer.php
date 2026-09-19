<?php
/**
 * Shared public field renderer (form + admin preview).
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CKF_Renderer {
	public static function step( $question ) {
		$type = $question['type'];
		ob_start();
		echo '<fieldset class="ck-feedback__step" data-step="' . esc_attr( $question['slug'] ) . '" data-type="' . esc_attr( $type ) . '" hidden>';
		if ( 'info' === $type ) {
			echo '<legend class="ck-feedback__question">' . esc_html( $question['title'] ) . '</legend>';
			if ( $question['description'] ) {
				echo '<p class="ck-feedback__helper">' . esc_html( $question['description'] ) . '</p>';
			}
		} else {
			echo '<legend class="ck-feedback__question">' . esc_html( $question['title'] ) . '</legend>';
			if ( $question['description'] ) {
				echo '<p class="ck-feedback__helper">' . esc_html( $question['description'] ) . '</p>';
			}
			self::control( $question );
			echo '<p class="ck-feedback__error" data-error hidden></p>';
		}
		echo '</fieldset>';
		return ob_get_clean();
	}

	public static function preview( $question ) {
		$question['slug'] = $question['slug'] ? $question['slug'] : 'preview';
		ob_start();
		echo '<div class="ck-feedback ck-feedback--preview">';
		echo '<fieldset class="ck-feedback__step is-active" data-type="' . esc_attr( $question['type'] ) . '">';
		echo '<legend class="ck-feedback__question">' . esc_html( $question['title'] ? $question['title'] : __( 'Pergunta', 'casa-kotti-feedback' ) ) . '</legend>';
		if ( ! empty( $question['description'] ) ) {
			echo '<p class="ck-feedback__helper">' . esc_html( $question['description'] ) . '</p>';
		}
		if ( 'info' !== $question['type'] ) {
			self::control( $question );
		}
		echo '</fieldset></div>';
		return ob_get_clean();
	}

	private static function control( $question ) {
		$settings = isset( $question['settings'] ) ? $question['settings'] : array();
		$slug     = $question['slug'];
		$options  = isset( $question['options'] ) ? $question['options'] : array();

		switch ( $question['type'] ) {
			case 'textarea':
				self::textarea( $slug, $settings );
				break;
			case 'email':
				self::input( $slug, 'email', $settings );
				break;
			case 'number':
				self::input( $slug, 'number', $settings );
				break;
			case 'text':
				self::input( $slug, 'text', $settings );
				break;
			case 'stars':
				self::stars( $slug, $settings );
				break;
			case 'scale':
				self::scale( $slug, $settings );
				break;
			case 'yes_no':
				if ( ! empty( $settings['ui'] ) && 'checkbox' === $settings['ui'] ) {
					self::consent( $slug, $settings );
				} else {
					self::choices( $slug, array(
						array( 'value' => 'sim', 'label' => __( 'Sim', 'casa-kotti-feedback' ) ),
						array( 'value' => 'nao', 'label' => __( 'Não', 'casa-kotti-feedback' ) ),
					), 'radio' );
				}
				break;
			case 'select':
				self::select( $slug, $options );
				break;
			case 'multi_choice':
				self::choices( $slug, $options, 'checkbox' );
				break;
			case 'radio':
				self::choices( $slug, $options, 'radio', 'ck-feedback__choice ck-feedback__choice--radio' );
				break;
			case 'single_choice':
			default:
				self::choices( $slug, $options, 'radio' );
				break;
		}
	}

	private static function input( $slug, $type, $settings ) {
		$placeholder = isset( $settings['placeholder'] ) ? $settings['placeholder'] : '';
		$max         = isset( $settings['max_length'] ) ? absint( $settings['max_length'] ) : ( 'email' === $type ? 190 : 190 );
		$min         = isset( $settings['min'] ) ? $settings['min'] : '';
		$maxn        = isset( $settings['max'] ) ? $settings['max'] : '';
		$step        = isset( $settings['step'] ) ? $settings['step'] : '';
		echo '<label class="ck-feedback__field">';
		echo '<span class="ck-feedback__sr">' . esc_html( $slug ) . '</span>';
		echo '<input class="ck-feedback__input" type="' . esc_attr( $type ) . '" name="' . esc_attr( $slug ) . '"';
		if ( 'email' === $type ) {
			echo ' inputmode="email" autocomplete="email"';
		}
		if ( $placeholder ) {
			echo ' placeholder="' . esc_attr( $placeholder ) . '"';
		}
		if ( $max ) {
			echo ' maxlength="' . esc_attr( (string) $max ) . '"';
		}
		if ( 'number' === $type ) {
			if ( '' !== $min ) {
				echo ' min="' . esc_attr( (string) $min ) . '"';
			}
			if ( '' !== $maxn ) {
				echo ' max="' . esc_attr( (string) $maxn ) . '"';
			}
			if ( '' !== $step ) {
				echo ' step="' . esc_attr( (string) $step ) . '"';
			}
		}
		echo '></label>';
	}

	private static function textarea( $slug, $settings ) {
		$placeholder = isset( $settings['placeholder'] ) ? $settings['placeholder'] : '';
		$max         = isset( $settings['max_length'] ) ? absint( $settings['max_length'] ) : 4000;
		echo '<label class="ck-feedback__field">';
		echo '<span class="ck-feedback__sr">' . esc_html( $slug ) . '</span>';
		echo '<textarea class="ck-feedback__input" name="' . esc_attr( $slug ) . '" rows="5" maxlength="' . esc_attr( (string) $max ) . '" placeholder="' . esc_attr( $placeholder ) . '"></textarea>';
		echo '</label>';
	}

	private static function stars( $slug, $settings ) {
		$min = isset( $settings['min'] ) ? (int) $settings['min'] : 1;
		$max = isset( $settings['max'] ) ? (int) $settings['max'] : 5;
		if ( $min < 1 ) {
			$min = 1;
		}
		if ( $max < $min ) {
			$max = $min;
		}
		echo '<div class="ck-feedback__stars" role="radiogroup" data-stars>';
		for ( $i = $min; $i <= $max; $i++ ) {
			echo '<label class="ck-feedback__star">';
			echo '<input type="radio" name="' . esc_attr( $slug ) . '" value="' . esc_attr( (string) $i ) . '">';
			echo '<span aria-hidden="true">★</span>';
			echo '<span class="ck-feedback__sr">' . esc_html( sprintf( __( '%d de %d', 'casa-kotti-feedback' ), $i, $max ) ) . '</span>';
			echo '</label>';
		}
		echo '</div>';
		echo '<p class="ck-feedback__hint"><span>' . esc_html( (string) $min ) . '</span><span>' . esc_html( (string) $max ) . '</span></p>';
	}

	private static function scale( $slug, $settings ) {
		$min = isset( $settings['min'] ) ? (int) $settings['min'] : 0;
		$max = isset( $settings['max'] ) ? (int) $settings['max'] : 10;
		if ( $max < $min ) {
			$max = $min;
		}
		$min_label = isset( $settings['min_label'] ) ? $settings['min_label'] : '';
		$max_label = isset( $settings['max_label'] ) ? $settings['max_label'] : '';
		echo '<div class="ck-feedback__scale" role="radiogroup">';
		for ( $i = $min; $i <= $max; $i++ ) {
			echo '<label class="ck-feedback__scale-item">';
			echo '<input type="radio" name="' . esc_attr( $slug ) . '" value="' . esc_attr( (string) $i ) . '">';
			echo '<span>' . esc_html( (string) $i ) . '</span>';
			echo '</label>';
		}
		echo '</div>';
		if ( $min_label || $max_label ) {
			echo '<p class="ck-feedback__hint"><span>' . esc_html( $min_label ) . '</span><span>' . esc_html( $max_label ) . '</span></p>';
		}
	}

	private static function select( $slug, $options ) {
		echo '<label class="ck-feedback__field"><span class="ck-feedback__sr">' . esc_html( $slug ) . '</span>';
		echo '<select class="ck-feedback__input" name="' . esc_attr( $slug ) . '">';
		echo '<option value="">' . esc_html__( 'Selecione', 'casa-kotti-feedback' ) . '</option>';
		foreach ( $options as $option ) {
			echo '<option value="' . esc_attr( $option['value'] ) . '">' . esc_html( $option['label'] ) . '</option>';
		}
		echo '</select></label>';
	}

	private static function consent( $slug, $settings ) {
		$label = isset( $settings['checkbox_label'] ) ? $settings['checkbox_label'] : __( 'Quero receber novidades, lançamentos e conteúdos da Casa Kotti por e-mail.', 'casa-kotti-feedback' );
		echo '<label class="ck-feedback__consent">';
		echo '<input type="checkbox" name="' . esc_attr( $slug ) . '" value="1">';
		echo '<span>' . esc_html( $label ) . '</span></label>';
	}

	private static function choices( $slug, $options, $input, $class = 'ck-feedback__choice' ) {
		$name = 'checkbox' === $input ? $slug . '[]' : $slug;
		echo '<div class="ck-feedback__choices">';
		foreach ( $options as $option ) {
			echo '<label class="' . esc_attr( $class ) . '">';
			echo '<input type="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $option['value'] ) . '">';
			echo '<span class="ck-feedback__choice-mark" aria-hidden="true"></span>';
			echo '<span class="ck-feedback__choice-label">' . esc_html( $option['label'] ) . '</span>';
			echo '</label>';
		}
		echo '</div>';
	}
}
