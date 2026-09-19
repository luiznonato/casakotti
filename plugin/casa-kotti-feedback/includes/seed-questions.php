<?php
/**
 * One-time seed of the current Casa Kotti questionnaire.
 *
 * @package Casa_Kotti_Feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Insert system questions matching the v1 flow.
 */
function ckf_seed_default_questions() {
	$order = 10;

	$product = CKF_Questions::insert(
		array(
			'slug'          => 'product',
			'title'         => 'Qual produto você experimentou?',
			'type'          => 'single_choice',
			'required'      => 1,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode( array() ),
		)
	);
	CKF_Question_Options::insert_many( $product, ckf_products() );
	$order += 10;

	CKF_Questions::insert(
		array(
			'slug'          => 'fragrance',
			'title'         => 'Qual fragrância você escolheu?',
			'type'          => 'single_choice',
			'required'      => 1,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode( array( 'source' => 'fragrances' ) ),
		)
	);
	$order += 10;

	CKF_Questions::insert(
		array(
			'slug'          => 'overall_rating',
			'title'         => 'Como você avalia sua experiência geral com o produto?',
			'type'          => 'stars',
			'required'      => 1,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode( array( 'min' => 1, 'max' => 5 ) ),
		)
	);
	$order += 10;

	$intensity = CKF_Questions::insert(
		array(
			'slug'          => 'intensity',
			'title'         => 'Como você avalia a intensidade da fragrância?',
			'type'          => 'single_choice',
			'required'      => 1,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode( array() ),
		)
	);
	CKF_Question_Options::insert_many( $intensity, ckf_intensity_options() );
	$order += 10;

	$refil = CKF_Questions::insert(
		array(
			'slug'          => 'refil_target',
			'title'         => 'Para qual produto o refil foi utilizado?',
			'type'          => 'single_choice',
			'required'      => 1,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode(
				array(
					'conditions' => array(
						'logic' => 'and',
						'rules' => array(
							array( 'question' => 'product', 'operator' => 'equals', 'value' => 'refil' ),
						),
					),
				)
			),
		)
	);
	CKF_Question_Options::insert_many(
		$refil,
		array(
			'difusor' => 'Difusor',
			'outro'   => 'Outro',
		)
	);
	$order += 10;

	$specific = ckf_product_specific();

	$hs = CKF_Questions::insert(
		array(
			'slug'          => 'product_specific_home_spray',
			'title'         => $specific['home-spray']['question'],
			'type'          => 'single_choice',
			'required'      => 1,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode(
				array(
					'conditions' => array(
						'logic' => 'and',
						'rules' => array(
							array( 'question' => 'product', 'operator' => 'equals', 'value' => 'home-spray' ),
						),
					),
				)
			),
		)
	);
	CKF_Question_Options::insert_many( $hs, $specific['home-spray']['options'] );
	$order += 10;

	$dif = CKF_Questions::insert(
		array(
			'slug'          => 'product_specific_difusor',
			'title'         => $specific['difusor']['question'],
			'type'          => 'single_choice',
			'required'      => 1,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode(
				array(
					'conditions' => array(
						'logic' => 'or',
						'rules' => array(
							array( 'question' => 'product', 'operator' => 'equals', 'value' => 'difusor' ),
							array( 'question' => 'refil_target', 'operator' => 'equals', 'value' => 'difusor' ),
						),
					),
				)
			),
		)
	);
	CKF_Question_Options::insert_many( $dif, $specific['difusor']['options'] );
	$order += 10;

	$auto = CKF_Questions::insert(
		array(
			'slug'          => 'product_specific_automotivo',
			'title'         => $specific['automotivo']['question'],
			'type'          => 'single_choice',
			'required'      => 1,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode(
				array(
					'conditions' => array(
						'logic' => 'and',
						'rules' => array(
							array( 'question' => 'product', 'operator' => 'equals', 'value' => 'automotivo' ),
						),
					),
				)
			),
		)
	);
	CKF_Question_Options::insert_many( $auto, $specific['automotivo']['options'] );
	$order += 10;

	$perf = CKF_Questions::insert(
		array(
			'slug'          => 'performance',
			'title'         => 'E sobre a duração/performance da fragrância?',
			'type'          => 'single_choice',
			'required'      => 1,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode( array() ),
		)
	);
	CKF_Question_Options::insert_many( $perf, ckf_performance_options() );
	$order += 10;

	CKF_Questions::insert(
		array(
			'slug'          => 'presentation_rating',
			'title'         => 'Como você avalia a apresentação do produto?',
			'description'   => 'Considere embalagem, rótulo e experiência ao receber.',
			'type'          => 'stars',
			'required'      => 1,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode( array( 'min' => 1, 'max' => 5 ) ),
		)
	);
	$order += 10;

	$rep = CKF_Questions::insert(
		array(
			'slug'          => 'repurchase_intent',
			'title'         => 'Você compraria novamente um produto Casa Kotti?',
			'type'          => 'single_choice',
			'required'      => 1,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode( array() ),
		)
	);
	CKF_Question_Options::insert_many( $rep, ckf_repurchase_options() );
	$order += 10;

	CKF_Questions::insert(
		array(
			'slug'          => 'nps_score',
			'title'         => 'Você indicaria a Casa Kotti para alguém?',
			'type'          => 'scale',
			'required'      => 1,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode(
				array(
					'min'       => 0,
					'max'       => 10,
					'min_label' => 'Não indicaria',
					'max_label' => 'Com certeza indicaria',
				)
			),
		)
	);
	$order += 10;

	CKF_Questions::insert(
		array(
			'slug'          => 'improvement_comment',
			'title'         => 'Tem alguma coisa que você gostaria que a gente melhorasse?',
			'type'          => 'textarea',
			'required'      => 0,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode(
				array(
					'placeholder' => 'Conte pra gente. Toda sugestão é bem-vinda.',
					'max_length'  => 4000,
				)
			),
		)
	);
	$order += 10;

	CKF_Questions::insert(
		array(
			'slug'          => 'positive_comment',
			'title'         => 'Teve alguma coisa que você gostou especialmente?',
			'type'          => 'textarea',
			'required'      => 0,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode(
				array(
					'placeholder' => 'Pode ser a fragrância, a embalagem ou qualquer detalhe da experiência.',
					'max_length'  => 4000,
				)
			),
		)
	);
	$order += 10;

	CKF_Questions::insert(
		array(
			'slug'          => 'customer_name',
			'title'         => 'Quer ficar mais perto da Casa Kotti?',
			'description'   => 'Se quiser, deixe seus dados para que a gente possa continuar essa conversa.',
			'type'          => 'text',
			'required'      => 0,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode(
				array(
					'placeholder'            => 'Nome',
					'contains_personal_data' => true,
					'max_length'             => 190,
					'show_label'             => false,
				)
			),
		)
	);
	$order += 10;

	CKF_Questions::insert(
		array(
			'slug'          => 'customer_email',
			'title'         => 'E-mail',
			'type'          => 'email',
			'required'      => 0,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode(
				array(
					'placeholder'            => 'E-mail',
					'contains_personal_data' => true,
					'max_length'             => 190,
					'show_label'             => false,
				)
			),
		)
	);
	$order += 10;

	CKF_Questions::insert(
		array(
			'slug'          => 'marketing_consent',
			'title'         => 'Novidades por e-mail',
			'description'   => 'Seus dados serão utilizados apenas conforme as escolhas acima.',
			'type'          => 'yes_no',
			'required'      => 0,
			'is_system'     => 1,
			'sort_order'    => $order,
			'settings_json' => wp_json_encode(
				array(
					'ui'                     => 'checkbox',
					'checkbox_label'         => 'Quero receber novidades, lançamentos e conteúdos da Casa Kotti por e-mail.',
					'contains_personal_data' => true,
				)
			),
		)
	);
}
