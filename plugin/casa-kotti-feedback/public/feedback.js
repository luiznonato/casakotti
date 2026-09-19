(function () {
	'use strict';

	var root = document.querySelector('[data-ck-feedback]');
	if (!root || typeof ckfForm === 'undefined') {
		return;
	}

	var intro = root.querySelector('[data-panel="intro"]');
	var thanks = root.querySelector('[data-panel="thanks"]');
	var form = root.querySelector('[data-form]');
	var progress = root.querySelector('[data-progress]');
	var counter = root.querySelector('[data-counter]');
	var fill = root.querySelector('[data-fill]');
	var nav = root.querySelector('[data-nav]');
	var nextBtn = root.querySelector('[data-next]');
	var backBtn = root.querySelector('[data-back]');
	var submitBtn = root.querySelector('[data-submit]');
	var prefillEl = root.querySelector('[data-prefill]');
	var questions = ckfForm.questions || [];
	var bySlug = {};
	questions.forEach(function (q) {
		bySlug[q.slug] = q;
	});

	var sequence = [];
	var index = 0;
	var lock = false;
	var productLocked = false;

	function pad(n) {
		return n < 10 ? '0' + n : String(n);
	}

	function valuesOf(slug) {
		var nodes = form.querySelectorAll('[name="' + slug + '"], [name="' + slug + '[]"]');
		var out = [];
		nodes.forEach(function (node) {
			if (node.type === 'checkbox' || node.type === 'radio') {
				if (node.checked) {
					out.push(node.value);
				}
			} else if (node.value) {
				out.push(node.value);
			}
		});
		return out;
	}

	function answersMap() {
		var map = {};
		questions.forEach(function (q) {
			var vals = valuesOf(q.slug);
			map[q.slug] = vals.length > 1 ? vals : (vals[0] || '');
		});
		return map;
	}

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

	function applies(question, answers) {
		if (!question.settings || !question.settings.conditions) {
			return true;
		}
		return evalGroup(question.settings.conditions, answers);
	}

	function visibleSequence() {
		var answers = answersMap();
		return questions.filter(function (q) {
			if (productLocked && q.slug === 'product' && answers.product) {
				return false;
			}
			return applies(q, answers);
		}).map(function (q) {
			return q.slug;
		});
	}

	function currentQuestion() {
		return bySlug[sequence[index]];
	}

	function showStep() {
		sequence = visibleSequence();
		if (index >= sequence.length) {
			index = Math.max(0, sequence.length - 1);
		}
		form.querySelectorAll('[data-step]').forEach(function (step) {
			var on = step.getAttribute('data-step') === sequence[index];
			step.hidden = !on;
			step.classList.toggle('is-active', on);
		});
		var last = index === sequence.length - 1;
		var q = currentQuestion();
		nextBtn.hidden = last;
		submitBtn.hidden = !last;
		backBtn.hidden = index === 0;
		progress.hidden = false;
		nav.hidden = false;
		counter.textContent = pad(index + 1) + ' / ' + pad(sequence.length);
		fill.style.width = sequence.length ? ((index + 1) / sequence.length) * 100 + '%' : '0';
		var first = form.querySelector('[data-step]:not([hidden]) input, [data-step]:not([hidden]) textarea, [data-step]:not([hidden]) select');
		if (first) {
			first.focus();
		}
		if (q && q.type === 'info') {
			nextBtn.hidden = last;
		}
	}

	function currentError() {
		var step = form.querySelector('[data-step]:not([hidden])');
		return step ? step.querySelector('[data-error]') : null;
	}

	function setError(message) {
		var node = currentError();
		if (!node) {
			return;
		}
		node.hidden = !message;
		node.textContent = message || '';
	}

	function validateStep() {
		var q = currentQuestion();
		if (!q || q.type === 'info' || !q.required) {
			if (q && q.type === 'email') {
				var email = valuesOf(q.slug)[0];
				if (email) {
					var input = form.querySelector('[name="' + q.slug + '"]');
					if (input && !input.checkValidity()) {
						setError(ckfForm.i18n.invalidEmail);
						input.focus();
						return false;
					}
				}
			}
			return true;
		}
		if (q.type === 'email') {
			var field = form.querySelector('[name="' + q.slug + '"]');
			var val = valuesOf(q.slug)[0];
			if (val && field && !field.checkValidity()) {
				setError(ckfForm.i18n.invalidEmail);
				field.focus();
				return false;
			}
		}
		if (!valuesOf(q.slug).length) {
			setError(ckfForm.i18n.selectOption);
			return false;
		}
		return true;
	}

	function startForm() {
		intro.hidden = true;
		intro.classList.remove('is-active');
		form.hidden = false;
		index = 0;
		showStep();
	}

	function applyPrefill() {
		var pre = ckfForm.prefill || {};
		['source', 'campaign', 'batch'].forEach(function (name) {
			var hidden = form.querySelector('[name="' + name + '"]');
			if (hidden) {
				hidden.value = pre[name] || '';
			}
		});
		if (pre.product) {
			var radio = form.querySelector('[name="product"][value="' + pre.product + '"]');
			if (radio) {
				radio.checked = true;
				productLocked = true;
				prefillEl.hidden = false;
				prefillEl.textContent = '';
				prefillEl.appendChild(document.createTextNode((ckfForm.i18n.evaluating || '') + ' '));
				var strong = document.createElement('strong');
				strong.textContent = (ckfForm.products && ckfForm.products[pre.product]) || pre.product;
				prefillEl.appendChild(strong);
				var change = document.createElement('button');
				change.type = 'button';
				change.textContent = ckfForm.i18n.change;
				change.addEventListener('click', function () {
					productLocked = false;
					prefillEl.hidden = true;
					index = 0;
					showStep();
				});
				prefillEl.appendChild(change);
			}
		}
		if (pre.fragrance) {
			var frag = form.querySelector('[name="fragrance"][value="' + pre.fragrance + '"]');
			if (frag) {
				frag.checked = true;
			}
		}
	}

	function collectPayload() {
		var answers = {};
		var visible = visibleSequence();
		visible.forEach(function (slug) {
			var q = bySlug[slug];
			if (!q || q.type === 'info') {
				return;
			}
			var vals = valuesOf(slug);
			if (q.type === 'multi_choice') {
				answers[slug] = vals;
			} else if (q.type === 'yes_no' && q.settings && q.settings.ui === 'checkbox') {
				answers[slug] = vals.length ? '1' : '';
			} else {
				answers[slug] = vals[0] || '';
			}
		});
		return {
			answers: answers,
			website: form.querySelector('[name="website"]').value,
			source: form.querySelector('[name="source"]').value,
			campaign: form.querySelector('[name="campaign"]').value,
			batch: form.querySelector('[name="batch"]').value,
			product_code: form.querySelector('[name="product_code"]').value,
			nonce: ckfForm.nonce
		};
	}

	root.querySelector('[data-start]').addEventListener('click', startForm);

	nextBtn.addEventListener('click', function () {
		setError('');
		if (!validateStep()) {
			return;
		}
		index += 1;
		showStep();
	});

	backBtn.addEventListener('click', function () {
		setError('');
		index = Math.max(0, index - 1);
		showStep();
	});

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		setError('');
		if (lock || !validateStep()) {
			return;
		}
		lock = true;
		submitBtn.disabled = true;
		submitBtn.textContent = ckfForm.i18n.sending;
		form.setAttribute('aria-busy', 'true');

		fetch(ckfForm.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': ckfForm.nonce
			},
			body: JSON.stringify(collectPayload())
		})
			.then(function (response) {
				return response.json().then(function (body) {
					if (!response.ok || body.success === false) {
						throw new Error(body.message || (body.data && body.data.message) || ckfForm.i18n.serverError);
					}
					return body;
				});
			})
			.then(function () {
				form.hidden = true;
				thanks.hidden = false;
				thanks.classList.add('is-active');
				var focusable = thanks.querySelector('.ck-feedback__btn');
				if (focusable) {
					focusable.focus();
				}
			})
			.catch(function (error) {
				setError(error.message || ckfForm.i18n.serverError);
				lock = false;
				submitBtn.disabled = false;
				submitBtn.textContent = 'Enviar avaliação';
				form.removeAttribute('aria-busy');
			});
	});

	applyPrefill();
}());
