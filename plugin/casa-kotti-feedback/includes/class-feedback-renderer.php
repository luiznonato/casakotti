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
	public static function page( $step, $questions, $copy = array() ) {
		ob_start();
		echo '<fieldset class="ck-feedback__step" data-step="' . esc_attr( $step['slug'] ) . '" data-step-id="' . esc_attr( (string) $step['id'] ) . '" hidden>';
		echo '<legend class="ck-feedback__question">' . esc_html( $step['title'] ) . '</legend>';
		if ( ! empty( $step['description'] ) ) {
			echo '<p class="ck-feedback__helper">' . esc_html( $step['description'] ) . '</p>';
		}
		echo '<div class="ck-feedback__grid">';
		foreach ( $questions as $question ) {
			echo self::field( $question ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';
		$show_privacy = false;
		foreach ( $questions as $question ) {
			if ( in_array( $question['slug'], array( 'customer_name', 'customer_email', 'marketing_consent' ), true ) ) {
				$show_privacy = true;
			}
		}
		if ( $show_privacy && ! empty( $copy['privacy_note'] ) ) {
			$privacy_id  = absint( get_option( 'wp_page_for_privacy_policy', 0 ) );
			$privacy_url = $privacy_id && function_exists( 'get_post_status' ) && 'publish' === get_post_status( $privacy_id ) ? get_permalink( $privacy_id ) : '';
			echo '<p class="ck-feedback__privacy">';
			echo esc_html( $copy['privacy_note'] );
			if ( $privacy_url ) {
				echo ' <a href="' . esc_url( $privacy_url ) . '">' . esc_html__( 'Política de Privacidade', 'casa-kotti-feedback' ) . '</a>';
			}
			echo '</p>';
		}
		echo '</fieldset>';
		return ob_get_clean();
	}

	public static function field( $question ) {
		$type  = CKF_Questions::canonical_type( $question['type'] );
		$slug  = $question['slug'];
		$error = 'ckf-err-' . $slug;
		$width = isset( $question['settings']['width'] ) ? $question['settings']['width'] : '100';
		if ( ! in_array( (string) $width, array( '100', '50', '33' ), true ) ) {
			$width = '100';
		}
		ob_start();
		echo '<div class="ck-feedback__block ck-feedback__block--w' . esc_attr( $width ) . '" data-field="' . esc_attr( $slug ) . '" data-type="' . esc_attr( $type ) . '"' . ( 'hidden' === $type ? ' hidden' : '' ) . '>';
		if ( 'info' === $type ) {
			echo '<p class="ck-feedback__question ck-feedback__question--sub">' . esc_html( $question['title'] ) . '</p>';
			if ( $question['description'] ) {
				echo '<p class="ck-feedback__helper">' . esc_html( $question['description'] ) . '</p>';
			}
		} elseif ( 'hidden' === $type ) {
			$val = isset( $question['settings']['default_value'] ) ? $question['settings']['default_value'] : '';
			echo '<input type="hidden" name="' . esc_attr( $slug ) . '" value="' . esc_attr( $val ) . '">';
		} else {
			$ui_checkbox = in_array( $type, array( 'checkbox', 'consent' ), true ) || ( 'yes_no' === $type && ! empty( $question['settings']['ui'] ) && 'checkbox' === $question['settings']['ui'] );
			if ( ! $ui_checkbox ) {
				echo '<label class="ck-feedback__question ck-feedback__question--sub" id="ckf-lbl-' . esc_attr( $slug ) . '" for="ckf-in-' . esc_attr( $slug ) . '">' . esc_html( $question['title'] ) . '</label>';
				if ( $question['description'] ) {
					echo '<p class="ck-feedback__helper">' . esc_html( $question['description'] ) . '</p>';
				}
				if ( ! empty( $question['settings']['help_text'] ) ) {
					echo '<p class="ck-feedback__helper">' . esc_html( $question['settings']['help_text'] ) . '</p>';
				}
			}
			self::control( $question, $error );
			echo '<p class="ck-feedback__error" id="' . esc_attr( $error ) . '" data-error hidden></p>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	public static function preview( $question ) {
		$question['slug'] = $question['slug'] ? $question['slug'] : 'preview';
		ob_start();
		echo '<div class="ck-feedback ck-feedback--preview">';
		echo self::field( $question ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
		return ob_get_clean();
	}

	private static function described( $error ) {
		return ' aria-describedby="' . esc_attr( $error ) . '"';
	}

	private static function control( $question, $error ) {
		$settings = isset( $question['settings'] ) ? $question['settings'] : array();
		$slug     = $question['slug'];
		$options  = isset( $question['options'] ) ? $question['options'] : array();

		switch ( CKF_Questions::canonical_type( $question['type'] ) ) {
			case 'textarea':
				self::textarea( $slug, $settings, $error );
				break;
			case 'email':
				self::input( $slug, 'email', $settings, $error );
				break;
			case 'tel':
				self::input( $slug, 'tel', $settings, $error );
				break;
			case 'date':
				self::input( $slug, 'date', $settings, $error );
				break;
			case 'number':
				self::input( $slug, 'number', $settings, $error );
				break;
			case 'text':
				self::input( $slug, 'text', $settings, $error );
				break;
			case 'stars':
				self::stars( $slug, $settings, $error );
				break;
			case 'scale':
				self::scale( $slug, $settings, $error );
				break;
			case 'checkbox':
			case 'consent':
				self::consent( $slug, $settings, $error );
				break;
			case 'yes_no':
				if ( ! empty( $settings['ui'] ) && 'checkbox' === $settings['ui'] ) {
					self::consent( $slug, $settings, $error );
				} else {
					self::choices(
						$slug,
						array(
							array( 'value' => 'sim', 'label' => __( 'Sim', 'casa-kotti-feedback' ) ),
							array( 'value' => 'nao', 'label' => __( 'Não', 'casa-kotti-feedback' ) ),
						),
						'radio',
						'ck-feedback__choice',
						$error
					);
				}
				break;
			case 'select':
				self::select( $slug, $options, $error );
				break;
			case 'multi_choice':
				self::choices( $slug, $options, 'checkbox', 'ck-feedback__choice', $error );
				break;
			case 'radio':
				self::choices( $slug, $options, 'radio', 'ck-feedback__choice ck-feedback__choice--radio', $error );
				break;
			case 'single_choice':
			default:
				self::choices( $slug, $options, 'radio', 'ck-feedback__choice', $error );
				break;
		}
	}

	private static function input( $slug, $type, $settings, $error ) {
		$placeholder = isset( $settings['placeholder'] ) ? $settings['placeholder'] : '';
		$max         = isset( $settings['max_length'] ) ? absint( $settings['max_length'] ) : 190;
		$min         = isset( $settings['min'] ) ? $settings['min'] : '';
		$maxn        = isset( $settings['max'] ) ? $settings['max'] : '';
		$step        = isset( $settings['step'] ) ? $settings['step'] : '';
		echo '<label class="ck-feedback__field">';
		echo '<span class="ck-feedback__sr">' . esc_html( $slug ) . '</span>';
		echo '<input class="ck-feedback__input" id="ckf-in-' . esc_attr( $slug ) . '" type="' . esc_attr( $type ) . '" name="' . esc_attr( $slug ) . '"' . self::described( $error ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( 'email' === $type ) {
			echo ' inputmode="email" autocomplete="email"';
		}
		if ( 'text' === $type && 'customer_name' === $slug ) {
			echo ' autocomplete="name"';
		}
		if ( $placeholder ) {
			echo ' placeholder="' . esc_attr( $placeholder ) . '"';
		}
		if ( ! empty( $settings['default_value'] ) ) {
			echo ' value="' . esc_attr( $settings['default_value'] ) . '"';
		}
		if ( 'tel' === $type ) {
			echo ' inputmode="tel" autocomplete="tel"';
		}
		if ( $max && ! in_array( $type, array( 'number', 'date', 'tel' ), true ) ) {
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

	private static function textarea( $slug, $settings, $error ) {
		$placeholder = isset( $settings['placeholder'] ) ? $settings['placeholder'] : '';
		$max         = isset( $settings['max_length'] ) ? absint( $settings['max_length'] ) : 4000;
		echo '<label class="ck-feedback__field">';
		echo '<span class="ck-feedback__sr">' . esc_html( $slug ) . '</span>';
		$default = isset( $settings['default_value'] ) ? $settings['default_value'] : '';
		echo '<textarea class="ck-feedback__input" id="ckf-in-' . esc_attr( $slug ) . '" name="' . esc_attr( $slug ) . '" rows="5" maxlength="' . esc_attr( (string) $max ) . '" placeholder="' . esc_attr( $placeholder ) . '"' . self::described( $error ) . '>' . esc_textarea( $default ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<span class="ck-feedback__count" data-count-for="' . esc_attr( $slug ) . '" data-max="' . esc_attr( (string) $max ) . '">0 / ' . esc_html( (string) $max ) . '</span>';
		echo '</label>';
	}

	private static function stars( $slug, $settings, $error ) {
		$min = isset( $settings['min'] ) ? (int) $settings['min'] : 1;
		$max = isset( $settings['max'] ) ? (int) $settings['max'] : 5;
		if ( $min < 1 ) {
			$min = 1;
		}
		if ( $max < $min ) {
			$max = $min;
		}
		echo '<div class="ck-feedback__stars" role="radiogroup" aria-labelledby="ckf-lbl-' . esc_attr( $slug ) . '"' . self::described( $error ) . ' data-stars>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		for ( $i = $min; $i <= $max; $i++ ) {
			echo '<label class="ck-feedback__star">';
			echo '<input type="radio" name="' . esc_attr( $slug ) . '" value="' . esc_attr( (string) $i ) . '">';
			echo '<span class="ck-feedback__star-icon" aria-hidden="true">★</span>';
			echo '<span class="ck-feedback__star-num" aria-hidden="true">' . esc_html( (string) $i ) . '</span>';
			echo '<span class="ck-feedback__sr">' . esc_html( sprintf( __( '%d de %d', 'casa-kotti-feedback' ), $i, $max ) ) . '</span>';
			echo '</label>';
		}
		echo '</div>';
	}

	private static function scale( $slug, $settings, $error ) {
		$min = isset( $settings['min'] ) ? (int) $settings['min'] : 0;
		$max = isset( $settings['max'] ) ? (int) $settings['max'] : 10;
		if ( $max < $min ) {
			$max = $min;
		}
		$min_label = isset( $settings['min_label'] ) ? $settings['min_label'] : '';
		$max_label = isset( $settings['max_label'] ) ? $settings['max_label'] : '';
		echo '<div class="ck-feedback__scale" role="radiogroup" aria-labelledby="ckf-lbl-' . esc_attr( $slug ) . '"' . self::described( $error ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

	private static function select( $slug, $options, $error ) {
		echo '<label class="ck-feedback__field"><span class="ck-feedback__sr">' . esc_html( $slug ) . '</span>';
		echo '<select class="ck-feedback__input" id="ckf-in-' . esc_attr( $slug ) . '" name="' . esc_attr( $slug ) . '"' . self::described( $error ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<option value="">' . esc_html__( 'Selecione...', 'casa-kotti-feedback' ) . '</option>';
		foreach ( $options as $option ) {
			echo '<option value="' . esc_attr( $option['value'] ) . '">' . esc_html( $option['label'] ) . '</option>';
		}
		echo '</select></label>';
	}

	private static function consent( $slug, $settings, $error ) {
		$label = isset( $settings['checkbox_label'] ) ? $settings['checkbox_label'] : __( 'Quero receber novidades, lançamentos e conteúdos da Casa Kotti.', 'casa-kotti-feedback' );
		echo '<label class="ck-feedback__consent">';
		echo '<input type="checkbox" name="' . esc_attr( $slug ) . '" value="1"' . self::described( $error ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<span>' . esc_html( $label ) . '</span></label>';
	}

	private static function choices( $slug, $options, $input, $class, $error ) {
		$name = 'checkbox' === $input ? $slug . '[]' : $slug;
		echo '<div class="ck-feedback__choices" role="group" aria-labelledby="ckf-lbl-' . esc_attr( $slug ) . '"' . self::described( $error ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
