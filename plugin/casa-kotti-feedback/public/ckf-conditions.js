/**
 * Conditional engine: show / hide / goto page / end.
 * Browser + Node.
 */
(function (root, factory) {
	if (typeof module === 'object' && module.exports) {
		module.exports = factory();
	} else {
		root.CKFConditions = factory();
	}
})(typeof self !== 'undefined' ? self : this, function () {
	'use strict';

	function evalRule(rule, answers) {
		var actual = answers[rule.question];
		var flat = Array.isArray(actual) ? actual.map(String) : (actual === '' || actual == null ? [] : [String(actual)]);
		var expected = rule.value;
		var op = rule.operator || 'equals';
		if (op === 'filled') {
			return flat.length > 0;
		}
		if (op === 'empty') {
			return flat.length === 0;
		}
		if (op === 'not_equals') {
			return flat.indexOf(String(expected)) === -1;
		}
		if (op === 'contains') {
			return flat.some(function (item) {
				return item.toLowerCase().indexOf(String(expected).toLowerCase()) !== -1;
			});
		}
		if (op === 'not_contains') {
			return flat.every(function (item) {
				return item.toLowerCase().indexOf(String(expected).toLowerCase()) === -1;
			});
		}
		if (op === 'contains_any') {
			var needles = Array.isArray(expected) ? expected : String(expected).split(/\s*,\s*/);
			return needles.some(function (n) {
				return flat.indexOf(String(n)) !== -1;
			});
		}
		if (op === 'gt' || op === 'lt' || op === 'gte' || op === 'lte') {
			var num = flat.length ? parseFloat(flat[0]) : 0;
			var exp = parseFloat(expected);
			if (op === 'gt') {
				return num > exp;
			}
			if (op === 'lt') {
				return num < exp;
			}
			if (op === 'gte') {
				return num >= exp;
			}
			return num <= exp;
		}
		if (Array.isArray(expected)) {
			return expected.some(function (n) {
				return flat.indexOf(String(n)) !== -1;
			});
		}
		return flat.indexOf(String(expected)) !== -1;
	}

	function evalGroup(group, answers) {
		if (!group || !group.rules || !group.rules.length) {
			return true;
		}
		var logic = group.logic === 'or' ? 'or' : 'and';
		var results = group.rules.map(function (rule) {
			return rule.rules ? evalGroup(rule, answers) : evalRule(rule, answers);
		});
		return logic === 'or' ? results.indexOf(true) !== -1 : results.indexOf(false) === -1;
	}

	function actionOf(question) {
		var settings = question.settings || {};
		var action = settings.cond_action || 'show';
		if (action !== 'hide' && action !== 'goto' && action !== 'end') {
			return 'show';
		}
		return action;
	}

	function isVisible(question, answers, opts) {
		opts = opts || {};
		if (question.type === 'hidden') {
			return false;
		}
		if (opts.productLocked && question.slug === 'product' && answers.product) {
			return false;
		}
		var settings = question.settings || {};
		var action = actionOf(question);
		if (!settings.conditions || !settings.conditions.rules || !settings.conditions.rules.length) {
			return true;
		}
		var match = evalGroup(settings.conditions, answers);
		if (action === 'hide') {
			return !match;
		}
		if (action === 'goto' || action === 'end') {
			return true;
		}
		return match;
	}

	function jumpFrom(questionsOnStep, answers) {
		var i;
		for (i = 0; i < questionsOnStep.length; i++) {
			var q = questionsOnStep[i];
			var settings = q.settings || {};
			var action = actionOf(q);
			if (action !== 'goto' && action !== 'end') {
				continue;
			}
			if (!settings.conditions || !settings.conditions.rules || !settings.conditions.rules.length) {
				continue;
			}
			if (!evalGroup(settings.conditions, answers)) {
				continue;
			}
			if (action === 'end') {
				return { type: 'end' };
			}
			if (settings.cond_goto) {
				return { type: 'goto', slug: String(settings.cond_goto) };
			}
		}
		return null;
	}

	function questionsForStep(step, questions) {
		return questions.filter(function (q) {
			if (step.id) {
				return Number(q.step_id) === Number(step.id);
			}
			return !q.step_id && q.slug === step.slug;
		});
	}

	function orderedSteps(steps, questions) {
		var out = (steps || []).slice();
		questions.forEach(function (q) {
			if (q.step_id) {
				return;
			}
			out.push({ id: 0, slug: q.slug, title: q.title, description: '' });
		});
		return out;
	}

	function route(steps, questions, answers, opts) {
		opts = opts || {};
		answers = answers || {};
		var ordered = orderedSteps(steps, questions);
		var visited = [];
		var seen = {};
		var index = 0;
		var guard = 0;
		while (index < ordered.length && guard < 200) {
			guard += 1;
			var step = ordered[index];
			var key = String(step.id || 0) + ':' + step.slug;
			if (seen[key]) {
				break;
			}
			var visible = questionsForStep(step, questions).filter(function (q) {
				return isVisible(q, answers, opts);
			});
			if (!visible.length) {
				index += 1;
				continue;
			}
			seen[key] = true;
			visited.push(step);
			var jump = jumpFrom(visible, answers);
			if (jump && jump.type === 'end') {
				break;
			}
			if (jump && jump.type === 'goto') {
				var nextIndex = -1;
				ordered.forEach(function (s, i) {
					if (s.slug === jump.slug) {
						nextIndex = i;
					}
				});
				if (nextIndex >= 0 && nextIndex !== index) {
					index = nextIndex;
					continue;
				}
			}
			index += 1;
		}
		return visited;
	}

	function applicableOnRoute(steps, questions, answers, opts) {
		var seen = {};
		route(steps, questions, answers, opts).forEach(function (step) {
			questionsForStep(step, questions).forEach(function (q) {
				if (isVisible(q, answers, opts) && q.type !== 'info' && q.type !== 'hidden') {
					seen[q.slug] = q;
				}
			});
		});
		return seen;
	}

	return {
		evalRule: evalRule,
		evalGroup: evalGroup,
		isVisible: isVisible,
		route: route,
		jumpFrom: jumpFrom,
		questionsForStep: questionsForStep,
		applicableOnRoute: applicableOnRoute
	};
});
