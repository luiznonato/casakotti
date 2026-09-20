<?php
/**
 * Casa Kotti theme setup and Customizer settings.
 *
 * @package Casa_Kotti
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CASA_KOTTI_THEME_VERSION', '1.5.22' );
define( 'CASA_KOTTI_META_PIXEL_ID', '2121663005134221' );

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
 * Meta Pixel (Facebook) — site-wide PageView.
 */
function casa_kotti_meta_pixel() {
	$pixel_id = CASA_KOTTI_META_PIXEL_ID;
	?>
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '<?php echo esc_js( $pixel_id ); ?>');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=<?php echo rawurlencode( $pixel_id ); ?>&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
	<?php
}
add_action( 'wp_head', 'casa_kotti_meta_pixel', 20 );

function casa_kotti_body_class( $classes ) {
	if ( is_front_page() ) {
		$classes[] = 'ck-screen';
	}
	return $classes;
}
add_filter( 'body_class', 'casa_kotti_body_class' );

/**
 * Compact header logo used on inner pages.
 */
function casa_kotti_header_logo() {
	if ( function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	printf(
		'<a class="custom-logo-link" href="%1$s"><img class="ck-brand-logo" src="%2$s" alt="%3$s" width="1237" height="752" decoding="async"></a>',
		esc_url( home_url( '/' ) ),
		esc_url( get_theme_file_uri( 'assets/images/logo-casa-kotti.png' ) ),
		esc_attr__( 'Casa Kotti', 'casa-kotti' )
	);
}

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
		'headline'            => array( 'Título principal', 'Algo novo está prestes a ocupar o ar.', 'sanitize_text_field' ),
		'support_text'        => array( 'Texto de apoio', "A primeira coleção Casa Kotti está chegando.\nCadastre-se para ser um dos primeiros a conhecer", 'sanitize_textarea_field' ),
		'email_label'         => array( 'Rótulo do campo', 'Seu e-mail', 'sanitize_text_field' ),
		'button_text'         => array( 'Texto do botão', 'Quero conhecer primeiro', 'sanitize_text_field' ),
		'consent_text'        => array( 'Texto de consentimento', 'Aceito receber novidades sobre o lançamento da Casa Kotti por e-mail.', 'sanitize_textarea_field' ),
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
		'form_enabled' => array( 'Exibir formulário', true ),
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

function casa_kotti_headline_html( $text ) {
	$escaped = esc_html( (string) $text );
	$marked  = preg_replace( '/\bnovo\b/iu', '<span class="ck-accent">$0</span>', $escaped, 1 );
	return $marked ? $marked : $escaped;
}

function casa_kotti_meta_description() {
	if ( ! is_front_page() || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) ) {
		return;
	}

	$description = casa_kotti_setting( 'support_text', "A primeira coleção Casa Kotti está chegando.\nCadastre-se para ser um dos primeiros a conhecer" );
	printf( '<meta name="description" content="%s">' . "\n", esc_attr( wp_strip_all_tags( $description ) ) );
}
add_action( 'wp_head', 'casa_kotti_meta_description', 1 );
