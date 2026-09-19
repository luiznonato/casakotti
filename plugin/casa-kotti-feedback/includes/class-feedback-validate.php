<?php
/**
 * Shared answer validation (backend authority; frontend mirrors messages).
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CKF_Validate {
	const TEXT_MAX     = 190;
	const TEXTAREA_MAX = 4000;

	public static function messages() {
		return array(
			'required'      => __( 'Este campo é obrigatório.', 'casa-kotti-feedback' ),
			'selectOption'  => __( 'Selecione uma opção para continuar.', 'casa-kotti-feedback' ),
			'selectStars'   => __( 'Selecione uma nota.', 'casa-kotti-feedback' ),
			'starsRange'    => __( 'Escolha uma nota de %1$s a %2$s.', 'casa-kotti-feedback' ),
			'invalidEmail'  => __( 'Digite um e-mail válido.', 'casa-kotti-feedback' ),
			'minLength'     => __( 'Digite pelo menos %s caracteres.', 'casa-kotti-feedback' ),
			'maxLength'     => __( 'Digite no máximo %s caracteres.', 'casa-kotti-feedback' ),
			'minSelect'     => __( 'Selecione pelo menos %s opções.', 'casa-kotti-feedback' ),
			'maxSelect'     => __( 'Selecione no máximo %s opções.', 'casa-kotti-feedback' ),
			'invalidNumber' => __( 'Digite um número válido.', 'casa-kotti-feedback' ),
			'invalidPhone'  => __( 'Digite um telefone válido.', 'casa-kotti-feedback' ),
			'invalidDate'   => __( 'Digite uma data válida.', 'casa-kotti-feedback' ),
			'serverError'   => __( 'Não foi possível enviar sua avaliação. Tente novamente.', 'casa-kotti-feedback' ),
			'sending'       => __( 'Enviando...', 'casa-kotti-feedback' ),
			'continue'      => __( 'Continuar', 'casa-kotti-feedback' ),
			'change'        => __( 'Alterar produto', 'casa-kotti-feedback' ),
			'evaluating'    => __( 'Você está avaliando:', 'casa-kotti-feedback' ),
		);
	}

	/**
	 * @param array $question Definition.
	 * @param mixed $raw      Incoming value.
	 * @return array{ok:bool,values:array,error:string}
	 */
	public static function answer( $question, $raw ) {
		$type     = CKF_Questions::canonical_type( isset( $question['type'] ) ? $question['type'] : 'text' );
		$settings = isset( $question['settings'] ) ? $question['settings'] : array();
		$options  = isset( $question['options'] ) ? $question['options'] : array();
		$allowed  = wp_list_pluck( $options, 'value' );
		$required = ! empty( $question['required'] );
		$msg      = self::messages();

		if ( 'info' === $type ) {
			return array( 'ok' => true, 'values' => array(), 'error' => '' );
		}

		if ( 'hidden' === $type ) {
			$text = sanitize_text_field( is_array( $raw ) ? (string) reset( $raw ) : (string) $raw );
			return array( 'ok' => true, 'values' => '' === $text ? array() : array( $text ), 'error' => '' );
		}

		if ( in_array( $type, array( 'checkbox', 'consent' ), true ) ) {
			$question['type']           = 'yes_no';
			$settings['ui']             = 'checkbox';
			$question['settings']       = $settings;
			$result                     = self::answer( $question, $raw );
			return $result;
		}

		if ( 'multi_choice' === $type ) {
			$values = is_array( $raw ) ? $raw : ( ( '' === $raw || null === $raw ) ? array() : array( $raw ) );
			$clean  = array();
			foreach ( $values as $value ) {
				$value = sanitize_title( (string) $value );
				if ( $value && in_array( $value, $allowed, true ) ) {
					$clean[] = $value;
				} elseif ( $value ) {
					return array( 'ok' => false, 'values' => array(), 'error' => $msg['selectOption'] );
				}
			}
			$min = isset( $settings['min_selections'] ) ? absint( $settings['min_selections'] ) : ( $required ? 1 : 0 );
			$max = isset( $settings['max_selections'] ) ? absint( $settings['max_selections'] ) : count( $allowed );
			if ( $required && ! $clean ) {
				return array( 'ok' => false, 'values' => array(), 'error' => $msg['selectOption'] );
			}
			if ( $clean && $min && count( $clean ) < $min ) {
				return array( 'ok' => false, 'values' => $clean, 'error' => sprintf( $msg['minSelect'], $min ) );
			}
			if ( $max && count( $clean ) > $max ) {
				return array( 'ok' => false, 'values' => $clean, 'error' => sprintf( $msg['maxSelect'], $max ) );
			}
			return array( 'ok' => true, 'values' => $clean, 'error' => '' );
		}

		if ( in_array( $type, array( 'single_choice', 'radio', 'select' ), true ) ) {
			$value = sanitize_title( is_array( $raw ) ? (string) reset( $raw ) : (string) $raw );
			if ( '' === $value ) {
				return $required
					? array( 'ok' => false, 'values' => array(), 'error' => $msg['selectOption'] )
					: array( 'ok' => true, 'values' => array(), 'error' => '' );
			}
			if ( ! in_array( $value, $allowed, true ) ) {
				return array( 'ok' => false, 'values' => array(), 'error' => $msg['selectOption'] );
			}
			return array( 'ok' => true, 'values' => array( $value ), 'error' => '' );
		}

		if ( 'yes_no' === $type ) {
			$value = is_array( $raw ) ? (string) reset( $raw ) : (string) $raw;
			if ( ! empty( $settings['ui'] ) && 'checkbox' === $settings['ui'] ) {
				$on = in_array( (string) $value, array( '1', 'sim', 'yes', 'true' ), true );
				if ( $required && ! $on ) {
					return array( 'ok' => false, 'values' => array(), 'error' => $msg['required'] );
				}
				return array( 'ok' => true, 'values' => $on ? array( '1' ) : array(), 'error' => '' );
			}
			$value = sanitize_title( $value );
			if ( '' === $value ) {
				return $required
					? array( 'ok' => false, 'values' => array(), 'error' => $msg['selectOption'] )
					: array( 'ok' => true, 'values' => array(), 'error' => '' );
			}
			if ( ! in_array( $value, array( 'sim', 'nao', 'yes', 'no', '1', '0' ), true ) ) {
				return array( 'ok' => false, 'values' => array(), 'error' => $msg['selectOption'] );
			}
			return array( 'ok' => true, 'values' => array( $value ), 'error' => '' );
		}

		if ( in_array( $type, array( 'stars', 'scale', 'number' ), true ) ) {
			if ( '' === $raw || null === $raw ) {
				$error = 'stars' === $type ? $msg['selectStars'] : ( 'number' === $type ? $msg['required'] : $msg['selectOption'] );
				return $required
					? array( 'ok' => false, 'values' => array(), 'error' => $error )
					: array( 'ok' => true, 'values' => array(), 'error' => '' );
			}
			if ( ! is_numeric( $raw ) ) {
				return array( 'ok' => false, 'values' => array(), 'error' => $msg['invalidNumber'] );
			}
			$num = 'number' === $type ? (float) $raw : (int) $raw;
			$min = isset( $settings['min'] ) ? (float) $settings['min'] : ( 'scale' === $type ? 0 : 1 );
			$max = isset( $settings['max'] ) ? (float) $settings['max'] : ( 'scale' === $type ? 10 : 5 );
			if ( $num < $min || $num > $max ) {
				return array( 'ok' => false, 'values' => array(), 'error' => sprintf( $msg['starsRange'], (string) $min, (string) $max ) );
			}
			if ( 'number' === $type && ! empty( $settings['step'] ) && is_numeric( $settings['step'] ) ) {
				$step = (float) $settings['step'];
				if ( $step > 0 ) {
					$mod = fmod( ( $num - $min ), $step );
					if ( $mod > 0.0001 && ( $step - $mod ) > 0.0001 ) {
						return array( 'ok' => false, 'values' => array(), 'error' => $msg['invalidNumber'] );
					}
				}
			}
			return array( 'ok' => true, 'values' => array( (string) $num ), 'error' => '' );
		}

		if ( 'tel' === $type ) {
			$text = trim( (string) $raw );
			if ( '' === $text ) {
				return $required
					? array( 'ok' => false, 'values' => array(), 'error' => $msg['required'] )
					: array( 'ok' => true, 'values' => array(), 'error' => '' );
			}
			$digits = preg_replace( '/\D+/', '', $text );
			if ( strlen( $digits ) < 8 || strlen( $digits ) > 15 ) {
				return array( 'ok' => false, 'values' => array(), 'error' => $msg['invalidPhone'] );
			}
			return array( 'ok' => true, 'values' => array( sanitize_text_field( $text ) ), 'error' => '' );
		}

		if ( 'date' === $type ) {
			$text = trim( (string) $raw );
			if ( '' === $text ) {
				return $required
					? array( 'ok' => false, 'values' => array(), 'error' => $msg['required'] )
					: array( 'ok' => true, 'values' => array(), 'error' => '' );
			}
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $text ) || ! strtotime( $text ) ) {
				return array( 'ok' => false, 'values' => array(), 'error' => $msg['invalidDate'] );
			}
			return array( 'ok' => true, 'values' => array( $text ), 'error' => '' );
		}

		if ( 'email' === $type ) {
			$email = trim( sanitize_email( (string) $raw ) );
			if ( '' === trim( (string) $raw ) ) {
				return $required
					? array( 'ok' => false, 'values' => array(), 'error' => $msg['required'] )
					: array( 'ok' => true, 'values' => array(), 'error' => '' );
			}
			if ( ! is_email( $email ) ) {
				return array( 'ok' => false, 'values' => array(), 'error' => $msg['invalidEmail'] );
			}
			return array( 'ok' => true, 'values' => array( $email ), 'error' => '' );
		}

		$text = 'textarea' === $type ? sanitize_textarea_field( (string) $raw ) : sanitize_text_field( (string) $raw );
		$text = trim( $text );
		$cap  = 'textarea' === $type ? self::TEXTAREA_MAX : self::TEXT_MAX;
		if ( ! empty( $settings['max_length'] ) ) {
			$cap = min( $cap, absint( $settings['max_length'] ) );
		}
		$minl = ! empty( $settings['min_length'] ) ? absint( $settings['min_length'] ) : 0;
		if ( '' === $text ) {
			return $required
				? array( 'ok' => false, 'values' => array(), 'error' => $msg['required'] )
				: array( 'ok' => true, 'values' => array(), 'error' => '' );
		}
		if ( $minl && strlen( $text ) < $minl ) {
			return array( 'ok' => false, 'values' => array( $text ), 'error' => sprintf( $msg['minLength'], $minl ) );
		}
		if ( strlen( $text ) > $cap ) {
			return array( 'ok' => false, 'values' => array( $text ), 'error' => sprintf( $msg['maxLength'], $cap ) );
		}
		return array( 'ok' => true, 'values' => array( $text ), 'error' => '' );
	}
}
