/* Node tests for CKFValidate — mirrors frontend/backend rules. */
const CKFValidate = require('../public/ckf-validate.js');

const i18n = {
	required: 'Este campo é obrigatório.',
	selectOption: 'Selecione uma opção para continuar.',
	selectStars: 'Selecione uma nota.',
	starsRange: 'Escolha uma nota de %1$s a %2$s.',
	invalidEmail: 'Digite um e-mail válido.',
	minLength: 'Digite pelo menos %s caracteres.',
	maxLength: 'Digite no máximo %s caracteres.',
	minSelect: 'Selecione pelo menos %s opções.',
	maxSelect: 'Selecione no máximo %s opções.',
	invalidNumber: 'Digite um número válido.',
	invalidPhone: 'Digite um telefone válido.',
	invalidDate: 'Digite uma data válida.'
};

function q(partial) {
	return Object.assign({ required: 0, settings: {}, options: [] }, partial);
}

function assert(cond, msg) {
	if (!cond) {
		throw new Error(msg);
	}
}

let n = 0;
function check(name, fn) {
	fn();
	n += 1;
	console.log('ok', name);
}

check('text required empty', function () {
	assert(!CKFValidate.validateAnswer(q({ type: 'text', required: 1 }), '   ', i18n).ok, 'spaces');
	assert(CKFValidate.validateAnswer(q({ type: 'text', required: 1 }), 'ok', i18n).ok, 'filled');
});

check('text max', function () {
	assert(!CKFValidate.validateAnswer(q({ type: 'text', settings: { max_length: 3 } }), 'abcd', i18n).ok, 'max');
});

check('textarea max', function () {
	assert(!CKFValidate.validateAnswer(q({ type: 'textarea', settings: { max_length: 5 } }), '123456', i18n).ok, 'ta');
	assert(CKFValidate.validateAnswer(q({ type: 'textarea' }), '', i18n).ok, 'optional empty');
});

check('email optional/invalid/valid', function () {
	assert(CKFValidate.validateAnswer(q({ type: 'email' }), '', i18n).ok, 'empty');
	assert(!CKFValidate.validateAnswer(q({ type: 'email' }), 'abc', i18n).ok, 'abc');
	assert(!CKFValidate.validateAnswer(q({ type: 'email' }), 'abc@', i18n).ok, 'abc@');
	assert(!CKFValidate.validateAnswer(q({ type: 'email' }), '@dominio.com', i18n).ok, '@dom');
	assert(!CKFValidate.validateAnswer(q({ type: 'email' }), 'abc@dominio', i18n).ok, 'no tld');
	assert(CKFValidate.validateAnswer(q({ type: 'email' }), ' maria@casa.com ', i18n).ok, 'valid');
});

const opts = [{ value: 'a', label: 'A' }, { value: 'b', label: 'B' }, { value: 'c', label: 'C' }];
check('single_choice', function () {
	assert(!CKFValidate.validateAnswer(q({ type: 'single_choice', required: 1, options: opts }), '', i18n).ok, 'none');
	assert(CKFValidate.validateAnswer(q({ type: 'single_choice', required: 1, options: opts }), 'a', i18n).ok, 'one');
	assert(!CKFValidate.validateAnswer(q({ type: 'single_choice', required: 1, options: opts }), 'zzz', i18n).ok, 'bad');
});

check('radio', function () {
	assert(!CKFValidate.validateAnswer(q({ type: 'radio', required: 1, options: opts }), '', i18n).ok, 'none');
	assert(CKFValidate.validateAnswer(q({ type: 'radio', required: 1, options: opts }), 'b', i18n).ok, 'sel');
});

check('multi_choice', function () {
	const m = q({ type: 'multi_choice', required: 1, options: opts, settings: { min_selections: 2, max_selections: 2 } });
	assert(!CKFValidate.validateAnswer(m, ['a'], i18n).ok, 'min');
	assert(CKFValidate.validateAnswer(m, ['a', 'b'], i18n).ok, 'ok');
	assert(!CKFValidate.validateAnswer(m, ['a', 'b', 'c'], i18n).ok, 'max');
});

check('checkbox optional/required', function () {
	const opt = q({ type: 'yes_no', settings: { ui: 'checkbox' } });
	assert(CKFValidate.validateAnswer(opt, '', i18n).ok, 'opt empty');
	assert(CKFValidate.validateAnswer(opt, '1', i18n).ok, 'opt on');
	const req = q({ type: 'yes_no', required: 1, settings: { ui: 'checkbox' } });
	assert(!CKFValidate.validateAnswer(req, '', i18n).ok, 'req off');
});

check('stars', function () {
	const s = q({ type: 'stars', required: 1, settings: { min: 1, max: 5 } });
	assert(!CKFValidate.validateAnswer(s, '', i18n).ok, 'empty');
	assert(CKFValidate.validateAnswer(s, 1, i18n).ok, 'min');
	assert(CKFValidate.validateAnswer(s, 5, i18n).ok, 'max');
	assert(!CKFValidate.validateAnswer(s, 0, i18n).ok, '0');
	assert(!CKFValidate.validateAnswer(s, 6, i18n).ok, '6');
});

check('scale', function () {
	const s = q({ type: 'scale', required: 1, settings: { min: 0, max: 10 } });
	assert(!CKFValidate.validateAnswer(s, '', i18n).ok, 'empty');
	assert(CKFValidate.validateAnswer(s, 0, i18n).ok, '0');
	assert(CKFValidate.validateAnswer(s, 10, i18n).ok, '10');
	assert(!CKFValidate.validateAnswer(s, -1, i18n).ok, '-1');
	assert(!CKFValidate.validateAnswer(s, 11, i18n).ok, '11');
});

check('contact page optional', function () {
	assert(CKFValidate.validateAnswer(q({ type: 'text', slug: 'customer_name' }), '', i18n).ok, 'name');
	assert(CKFValidate.validateAnswer(q({ type: 'email', slug: 'customer_email' }), '', i18n).ok, 'email');
	assert(CKFValidate.validateAnswer(q({ type: 'yes_no', slug: 'marketing_consent', settings: { ui: 'checkbox' } }), '', i18n).ok, 'consent');
	assert(!CKFValidate.validateAnswer(q({ type: 'email' }), 'maria@', i18n).ok, 'bad email');
});

check('tel and date', function () {
	assert(CKFValidate.validateAnswer(q({ type: 'tel' }), '', i18n).ok, 'tel empty');
	assert(!CKFValidate.validateAnswer(q({ type: 'tel', required: 1 }), '12', i18n).ok, 'tel short');
	assert(CKFValidate.validateAnswer(q({ type: 'tel' }), '11987654321', i18n).ok, 'tel ok');
	assert(CKFValidate.validateAnswer(q({ type: 'date' }), '', i18n).ok, 'date empty');
	assert(!CKFValidate.validateAnswer(q({ type: 'date', required: 1 }), '32/13/2020', i18n).ok, 'date bad');
	assert(CKFValidate.validateAnswer(q({ type: 'date' }), '2024-03-10', i18n).ok, 'date ok');
});

check('info skipped', function () {
	assert(CKFValidate.validateAnswer(q({ type: 'info' }), 'x', i18n).ok, 'info');
	assert(CKFValidate.validateAnswer(q({ type: 'info' }), 'x', i18n).values.length === 0, 'no value');
});

check('select placeholder', function () {
	assert(!CKFValidate.validateAnswer(q({ type: 'select', required: 1, options: opts }), '', i18n).ok, 'placeholder');
});

console.log('passed', n);
