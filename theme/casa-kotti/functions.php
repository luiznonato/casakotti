<?php
/**
 * Casa Kotti theme setup and Customizer settings.
 *
 * @package Casa_Kotti
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CASA_KOTTI_THEME_VERSION', '1.3.0' );

function casa_kotti_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'custom-logo', array(
		'height'      => 240,
		'width'       => 640,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array( 'style', 'script' ) );
}
add_action( 'after_setup_theme', 'casa_kotti_setup' );

function casa_kotti_enqueue_assets() {
	wp_enqueue_style(
		'casa-kotti-main',
		get_theme_file_uri( 'assets/css/main.css' ),
		array(),
		CASA_KOTTI_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'casa_kotti_enqueue_assets' );

/**
 * Return a sanitized checkbox value.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function casa_kotti_sanitize_checkbox( $value ) {
	return (bool) $value;
}

/**
 * Register the focused set of pre-launch settings.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function casa_kotti_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'casa_kotti_prelaunch', array(
		'title'    => __( 'Casa Kotti — Pré-lançamento', 'casa-kotti' ),
		'priority' => 30,
	) );

	$text_settings = array(
		'tagline'             => array( 'Complemento da marca', 'Perfumaria para ambientes', 'sanitize_text_field' ),
		'headline'            => array( 'Título principal', 'Em breve, um novo aroma para o seu cotidiano.', 'sanitize_text_field' ),
		'support_text'        => array( 'Texto de apoio', 'Estamos preparando nossa primeira coleção. Deixe seu e-mail para saber quando ela chegar.', 'sanitize_textarea_field' ),
		'email_label'         => array( 'Rótulo do campo', 'Seu e-mail', 'sanitize_text_field' ),
		'button_text'         => array( 'Texto do botão', 'Quero saber do lançamento', 'sanitize_text_field' ),
		'consent_text'        => array( 'Texto de consentimento', 'Quero receber novidades sobre o lançamento da Casa Kotti por e-mail.', 'sanitize_textarea_field' ),
		'success_text'        => array( 'Mensagem de sucesso', 'Pronto! Vamos avisar você quando a Casa Kotti chegar.', 'sanitize_textarea_field' ),
		'instagram_label'     => array( 'Rótulo do Instagram', 'Instagram', 'sanitize_text_field' ),
		'contact_label'       => array( 'Rótulo do contato', 'Fale com a Casa Kotti', 'sanitize_text_field' ),
		'privacy_label'       => array( 'Rótulo da privacidade', 'Política de privacidade', 'sanitize_text_field' ),
	);

	foreach ( $text_settings as $key => $config ) {
		$setting_id = 'casa_kotti_' . $key;
		$wp_customize->add_setting( $setting_id, array(
			'default'           => $config[1],
			'sanitize_callback' => $config[2],
		) );
		$wp_customize->add_control( $setting_id, array(
			'label'   => $config[0],
			'section' => 'casa_kotti_prelaunch',
			'type'    => 'sanitize_textarea_field' === $config[2] ? 'textarea' : 'text',
		) );
	}

	$wp_customize->add_setting( 'casa_kotti_contact_email', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_email',
	) );
	$wp_customize->add_control( 'casa_kotti_contact_email', array(
		'label'   => __( 'E-mail de contato', 'casa-kotti' ),
		'section' => 'casa_kotti_prelaunch',
		'type'    => 'email',
	) );

	$wp_customize->add_setting( 'casa_kotti_instagram_url', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'casa_kotti_instagram_url', array(
		'label'       => __( 'URL do Instagram', 'casa-kotti' ),
		'description' => __( 'O link fica oculto enquanto este campo estiver vazio.', 'casa-kotti' ),
		'section'     => 'casa_kotti_prelaunch',
		'type'        => 'url',
	) );

	$wp_customize->add_setting( 'casa_kotti_privacy_page', array(
		'default'           => 0,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'casa_kotti_privacy_page', array(
		'label'       => __( 'Página de privacidade', 'casa-kotti' ),
		'description' => __( 'Também deve ser definida em Configurações > Privacidade para liberar o formulário.', 'casa-kotti' ),
		'section'     => 'casa_kotti_prelaunch',
		'type'        => 'dropdown-pages',
	) );

	foreach ( array(
		'form_enabled'      => array( 'Exibir formulário', true ),
		'animations_enabled' => array( 'Ativar animações das folhagens', true ),
	) as $key => $config ) {
		$setting_id = 'casa_kotti_' . $key;
		$wp_customize->add_setting( $setting_id, array(
			'default'           => $config[1],
			'sanitize_callback' => 'casa_kotti_sanitize_checkbox',
		) );
		$wp_customize->add_control( $setting_id, array(
			'label'   => $config[0],
			'section' => 'casa_kotti_prelaunch',
			'type'    => 'checkbox',
		) );
	}
}
add_action( 'customize_register', 'casa_kotti_customize_register' );

/**
 * Theme setting helper with an explicit fallback.
 *
 * @param string $key Setting suffix.
 * @param mixed  $default Default value.
 * @return mixed
 */
function casa_kotti_setting( $key, $default = '' ) {
	return get_theme_mod( 'casa_kotti_' . $key, $default );
}

/**
 * Determine whether the selected privacy page is public and is also the
 * WordPress privacy policy page.
 *
 * @return bool
 */
function casa_kotti_has_valid_privacy_page() {
	$theme_page = absint( casa_kotti_setting( 'privacy_page', 0 ) );
	$core_page  = absint( get_option( 'wp_page_for_privacy_policy', 0 ) );

	return $theme_page > 0
		&& $theme_page === $core_page
		&& 'publish' === get_post_status( $theme_page );
}

function casa_kotti_meta_description() {
	if ( ! is_front_page() || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) ) {
		return;
	}

	$description = casa_kotti_setting( 'support_text', 'Estamos preparando nossa primeira coleção. Deixe seu e-mail para saber quando ela chegar.' );
	printf( '<meta name="description" content="%s">' . "\n", esc_attr( wp_strip_all_tags( $description ) ) );
}
add_action( 'wp_head', 'casa_kotti_meta_description', 1 );
