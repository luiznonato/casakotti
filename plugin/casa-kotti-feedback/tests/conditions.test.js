const CKFConditions = require('../public/ckf-conditions.js');

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

const steps = [
	{ id: 1, slug: 'a', title: 'A' },
	{ id: 2, slug: 'b', title: 'B' },
	{ id: 3, slug: 'c', title: 'C' }
];

const questions = [
	{ slug: 'q1', type: 'single_choice', step_id: 1, settings: {}, options: [] },
	{
		slug: 'q2',
		type: 'single_choice',
		step_id: 2,
		settings: {
			conditions: { logic: 'and', rules: [{ question: 'q1', operator: 'equals', value: 'yes' }] },
			cond_action: 'show'
		},
		options: []
	},
	{
		slug: 'skipper',
		type: 'single_choice',
		step_id: 1,
		settings: {
			conditions: { logic: 'and', rules: [{ question: 'q1', operator: 'equals', value: 'jump' }] },
			cond_action: 'goto',
			cond_goto: 'c'
		},
		options: []
	},
	{ slug: 'q3', type: 'text', step_id: 3, settings: {}, options: [] },
	{
		slug: 'ender',
		type: 'single_choice',
		step_id: 1,
		settings: {
			conditions: { logic: 'and', rules: [{ question: 'q1', operator: 'equals', value: 'stop' }] },
			cond_action: 'end'
		},
		options: []
	}
];

check('show hides unmatched', function () {
	assert(CKFConditions.isVisible(questions[1], { q1: 'no' }) === false, 'hidden');
	assert(CKFConditions.isVisible(questions[1], { q1: 'yes' }) === true, 'shown');
});

check('goto skips middle page', function () {
	const route = CKFConditions.route(steps, questions, { q1: 'jump' });
	assert(route.map((s) => s.slug).join(',') === 'a,c', route.map((s) => s.slug).join(','));
});

check('end stops after current', function () {
	const route = CKFConditions.route(steps, questions, { q1: 'stop' });
	assert(route.map((s) => s.slug).join(',') === 'a', route.map((s) => s.slug).join(','));
});

check('default sequential', function () {
	const route = CKFConditions.route(steps, questions, { q1: 'yes' });
	assert(route.map((s) => s.slug).join(',') === 'a,b,c', route.map((s) => s.slug).join(','));
});

check('applicable ignores skipped', function () {
	const apps = CKFConditions.applicableOnRoute(steps, questions, { q1: 'jump' });
	assert(!apps.q2, 'no q2');
	assert(apps.q3, 'has q3');
});

console.log(n + ' tests');
