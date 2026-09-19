<?php
/**
 * Plugin Name: Casa Kotti — Avaliações
 * Description: Questionário de experiência do cliente, dashboard e fragrâncias da Casa Kotti.
 * Version: 1.2.1
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Casa Kotti
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: casa-kotti-feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CKF_VERSION', '1.2.1' );
define( 'CKF_FILE', __FILE__ );
define( 'CKF_DIR', plugin_dir_path( __FILE__ ) );
define( 'CKF_URL', plugin_dir_url( __FILE__ ) );

require_once CKF_DIR . 'includes/class-feedback-database.php';
require_once CKF_DIR . 'includes/class-feedback-security.php';
require_once CKF_DIR . 'includes/class-feedback-questions.php';
require_once CKF_DIR . 'includes/class-feedback-question-options.php';
require_once CKF_DIR . 'includes/class-feedback-answers.php';
require_once CKF_DIR . 'includes/class-feedback-conditions.php';
require_once CKF_DIR . 'includes/class-feedback-steps.php';
require_once CKF_DIR . 'includes/class-feedback-validate.php';
require_once CKF_DIR . 'includes/class-feedback-renderer.php';
require_once CKF_DIR . 'includes/class-feedback-api.php';
require_once CKF_DIR . 'includes/class-feedback-shortcode.php';
require_once CKF_DIR . 'includes/class-feedback-admin-questions.php';
require_once CKF_DIR . 'includes/class-feedback-admin.php';

register_activation_hook( __FILE__, array( 'CKF_Database', 'activate' ) );

add_action( 'plugins_loaded', 'ckf_boot' );

/**
 * Load plugin services.
 */
function ckf_boot() {
	CKF_Database::maybe_upgrade();
	CKF_API::init();
	CKF_Shortcode::init();
	if ( is_admin() ) {
		CKF_Admin::init();
	}
}

/**
 * Product option map.
 *
 * @return array<string, string>
 */
function ckf_products() {
	return array(
		'home-spray'    => __( 'Home Spray', 'casa-kotti-feedback' ),
		'difusor'       => __( 'Difusor', 'casa-kotti-feedback' ),
		'automotivo'    => __( 'Automotivo', 'casa-kotti-feedback' ),
		'refil'         => __( 'Refil', 'casa-kotti-feedback' ),
		'mais-de-um'    => __( 'Mais de um produto', 'casa-kotti-feedback' ),
	);
}

/**
 * Intensity option map.
 *
 * @return array<string, string>
 */
function ckf_intensity_options() {
	return array(
		'muito-suave'      => __( 'Muito suave', 'casa-kotti-feedback' ),
		'um-pouco-suave'   => __( 'Um pouco suave', 'casa-kotti-feedback' ),
		'na-medida-certa'  => __( 'Na medida certa', 'casa-kotti-feedback' ),
		'um-pouco-intensa' => __( 'Um pouco intensa', 'casa-kotti-feedback' ),
		'muito-intensa'    => __( 'Muito intensa', 'casa-kotti-feedback' ),
	);
}

/**
 * Performance option map.
 *
 * @return array<string, string>
 */
function ckf_performance_options() {
	return array(
		'abaixo'              => __( 'Abaixo do que eu esperava', 'casa-kotti-feedback' ),
		'poderia-durar-mais'  => __( 'Poderia durar mais', 'casa-kotti-feedback' ),
		'dentro-esperado'     => __( 'Dentro do esperado', 'casa-kotti-feedback' ),
		'melhor'              => __( 'Melhor do que eu esperava', 'casa-kotti-feedback' ),
		'ainda-nao-usei'      => __( 'Ainda não usei tempo suficiente para avaliar', 'casa-kotti-feedback' ),
	);
}

/**
 * Repurchase option map.
 *
 * @return array<string, string>
 */
function ckf_repurchase_options() {
	return array(
		'com-certeza'     => __( 'Com certeza', 'casa-kotti-feedback' ),
		'provavelmente-sim' => __( 'Provavelmente sim', 'casa-kotti-feedback' ),
		'talvez'          => __( 'Talvez', 'casa-kotti-feedback' ),
		'provavelmente-nao' => __( 'Provavelmente não', 'casa-kotti-feedback' ),
		'nao'             => __( 'Não', 'casa-kotti-feedback' ),
	);
}

/**
 * Product-specific questions and answers.
 *
 * @return array<string, array{question:string, options:array<string,string>}>
 */
function ckf_product_specific() {
	return array(
		'home-spray' => array(
			'question' => __( 'Depois da aplicação, como você percebe a permanência da fragrância no ambiente?', 'casa-kotti-feedback' ),
			'options'  => array(
				'desaparece-muito-rapido' => __( 'Desaparece muito rápido', 'casa-kotti-feedback' ),
				'poderia-permanecer-mais' => __( 'Poderia permanecer mais', 'casa-kotti-feedback' ),
				'permanece-esperado'      => __( 'Permanece pelo tempo que eu esperava', 'casa-kotti-feedback' ),
				'permanece-mais'          => __( 'Permanece mais do que eu esperava', 'casa-kotti-feedback' ),
				'ainda-nao-consigo'       => __( 'Ainda não consigo avaliar', 'casa-kotti-feedback' ),
			),
		),
		'difusor'    => array(
			'question' => __( 'Ao entrar no ambiente, você consegue perceber a fragrância do difusor?', 'casa-kotti-feedback' ),
			'options'  => array(
				'quase-nunca'          => __( 'Quase nunca', 'casa-kotti-feedback' ),
				'apenas-proximo'       => __( 'Apenas quando estou próximo', 'casa-kotti-feedback' ),
				'suave'                => __( 'Sim, de forma suave', 'casa-kotti-feedback' ),
				'facilmente'           => __( 'Sim, facilmente', 'casa-kotti-feedback' ),
				'bastante-intensidade' => __( 'Sim, com bastante intensidade', 'casa-kotti-feedback' ),
			),
		),
		'automotivo' => array(
			'question' => __( 'Como você percebe a fragrância dentro do carro?', 'casa-kotti-feedback' ),
			'options'  => array(
				'muito-discreta'       => __( 'Muito discreta', 'casa-kotti-feedback' ),
				'um-pouco-discreta'    => __( 'Um pouco discreta', 'casa-kotti-feedback' ),
				'na-medida-certa'      => __( 'Na medida certa', 'casa-kotti-feedback' ),
				'um-pouco-intensa'     => __( 'Um pouco intensa', 'casa-kotti-feedback' ),
				'muito-intensa'        => __( 'Muito intensa', 'casa-kotti-feedback' ),
			),
		),
	);
}
