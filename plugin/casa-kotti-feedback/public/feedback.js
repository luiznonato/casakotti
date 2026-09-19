(function () {
	'use strict';

	if (typeof CKFValidate === 'undefined' || typeof CKFConditions === 'undefined') {
		return;
	}

	function mergeConfig(root) {
		var base = typeof ckfForm !== 'undefined' ? ckfForm : {};
		var el = root.querySelector('[data-ck-config]');
		if (!el) {
			return base;
		}
		try {
			var extra = JSON.parse(el.textContent);
			var out = {};
			var key;
			for (key in base) {
				if (Object.prototype.hasOwnProperty.call(base, key)) {
					out[key] = base[key];
				}
			}
			for (key in extra) {
				if (Object.prototype.hasOwnProperty.call(extra, key)) {
					out[key] = extra[key];
				}
			}
			return out;
		} catch (e) {
			return base;
		}
	}

	function boot(root, cfg) {
	if (!root || !cfg) {
		return;
	}
	var intro = root.querySelector('[data-panel="intro"]');
	var thanks = root.querySelector('[data-panel="thanks"]');
	var form = root.querySelector('[data-form]');
	if (!form || !intro) {
		return;
	}
	var progress = root.querySelector('[data-progress]');
	var counter = root.querySelector('[data-counter]');
	var fill = root.querySelector('[data-fill]');
	var nav = root.querySelector('[data-nav]');
	var footer = root.querySelector('[data-footer]');
	var nextBtn = root.querySelector('[data-next]');
	var backBtn = root.querySelector('[data-back]');
	var submitBtn = root.querySelector('[data-submit]');
	var prefillEl = root.querySelector('[data-prefill]');
	var formError = root.querySelector('[data-form-error]');
	var questions = cfg.questions || [];
	var steps = cfg.steps || [];
	var i18n = cfg.i18n || {};
	var isPreview = root.getAttribute('data-preview') === '1' || cfg.preview === true;
	var bySlug = {};
	questions.forEach(function (q) {
		bySlug[q.slug] = q;
	});

	var state = {
		answers: {},
		currentStep: 0,
		visibleSteps: [],
		errors: {},
		isSubmitting: false,
		attempted: {},
		productLocked: false
	};

	function pad(n) {
		return n < 10 ? '0' + n : String(n);
	}

	function readDomValue(slug) {
		var q = bySlug[slug];
		var nodes = form.querySelectorAll('[name="' + slug + '"], [name="' + slug + '[]"]');
		var out = [];
		nodes.forEach(function (node) {
			if (node.type === 'checkbox' || node.type === 'radio') {
				if (node.checked) {
					out.push(node.value);
				}
			} else {
				out.push(node.value);
			}
		});
		if (!q) {
			return out.length > 1 ? out : (out[0] || '');
		}
		if (q.type === 'multi_choice') {
			return out;
		}
		if (q.type === 'yes_no' && q.settings && q.settings.ui === 'checkbox') {
			return out.length ? '1' : '';
		}
		if (q.type === 'checkbox' || q.type === 'consent') {
			return out.length ? '1' : '';
		}
		return out[0] || '';
	}

	function syncAnswersFromDom() {
		questions.forEach(function (q) {
			if (q.type === 'info') {
				return;
			}
			state.answers[q.slug] = readDomValue(q.slug);
		});
	}

	function applies(question, answers) {
		return CKFConditions.isVisible(question, answers, { productLocked: state.productLocked });
	}

	function isIdentityQuestion(q) {
		return q.slug === 'customer_name' || q.slug === 'customer_email' || q.slug === 'marketing_consent';
	}

	function questionsForStep(step) {
		if (step && step.questions) {
			return step.questions;
		}
		return CKFConditions.questionsForStep(step, questions);
	}

	function applicableQuestions(step, answers) {
		return questionsForStep(step).filter(function (q) {
			if (state.productLocked && q.slug === 'product' && answers.product) {
				return false;
			}
			return applies(q, answers);
		});
	}

	function visibleSteps() {
		var routed = CKFConditions.route(steps, questions, state.answers, { productLocked: state.productLocked });
		var out = [];
		routed.forEach(function (step) {
			var qs = applicableQuestions(step, state.answers);
			var i = 0;
			while (i < qs.length) {
				if (isIdentityQuestion(qs[i])) {
					var group = [];
					while (i < qs.length && isIdentityQuestion(qs[i])) {
						group.push(qs[i]);
						i += 1;
					}
					out.push({
						id: step.id,
						slug: step.slug,
						title: step.title,
						description: step.description || '',
						questions: group
					});
				} else {
					out.push({
						id: step.id,
						slug: qs[i].slug,
						title: qs[i].title || step.title,
						description: qs[i].description || '',
						questions: [qs[i]]
					});
					i += 1;
				}
			}
		});
		return out;
	}

	function fieldEl(slug) {
		return form.querySelector('[data-field="' + slug + '"]');
	}

	function controlEl(slug) {
		return form.querySelector('[name="' + slug + '"], [name="' + slug + '[]"]');
	}

	function showFieldError(slug, message) {
		var block = fieldEl(slug);
		var err = block ? block.querySelector('[data-error]') : null;
		if (block) {
			block.classList.toggle('ck-feedback__field--error', !!message);
		}
		if (err) {
			err.hidden = !message;
			err.textContent = message || '';
		}
		form.querySelectorAll('[name="' + slug + '"], [name="' + slug + '[]"]').forEach(function (node) {
			if (message) {
				node.setAttribute('aria-invalid', 'true');
			} else {
				node.removeAttribute('aria-invalid');
			}
		});
		if (message) {
			state.errors[slug] = message;
		} else {
			delete state.errors[slug];
		}
	}

	function clearFieldError(slug) {
		showFieldError(slug, '');
	}

	function focusFirstInvalidField(slugs) {
		var i;
		for (i = 0; i < slugs.length; i++) {
			if (!state.errors[slugs[i]]) {
				continue;
			}
			var node = controlEl(slugs[i]);
			if (node) {
				if (node.scrollIntoView) {
					node.scrollIntoView({ behavior: 'smooth', block: 'center' });
				}
				node.focus();
				return;
			}
		}
	}

	function validateField(question, show) {
		if (question.type === 'info' || !applies(question, state.answers)) {
			clearFieldError(question.slug);
			return true;
		}
		var result = CKFValidate.validateAnswer(question, state.answers[question.slug], i18n);
		if (!result.ok && question.settings && question.settings.error_message) {
			result.error = question.settings.error_message;
		}
		if (show) {
			showFieldError(question.slug, result.ok ? '' : result.error);
		} else if (result.ok) {
			clearFieldError(question.slug);
		}
		return result.ok;
	}

	function validateStep(step, show) {
		var qs = step && step.questions ? step.questions : applicableQuestions(step, state.answers);
		var ok = true;
		var slugs = [];
		qs.forEach(function (q) {
			if (!applies(q, state.answers)) {
				return;
			}
			slugs.push(q.slug);
			if (!validateField(q, show)) {
				ok = false;
			}
		});
		if (show && !ok) {
			focusFirstInvalidField(slugs);
		}
		return ok;
	}

	function validateAll() {
		syncAnswersFromDom();
		recalculate();
		var ok = true;
		state.visibleSteps.forEach(function (step) {
			var qs = step.questions || applicableQuestions(step, state.answers);
			qs.forEach(function (q) {
				if (!validateField(q, true)) {
					ok = false;
				}
			});
		});
		if (!ok) {
			var found = false;
			state.visibleSteps.forEach(function (step, i) {
				if (found) {
					return;
				}
				var slugs = (step.questions || applicableQuestions(step, state.answers)).map(function (q) {
					return q.slug;
				});
				var has = slugs.some(function (s) {
					return state.errors[s];
				});
				if (has) {
					state.currentStep = i;
					renderStep();
					focusFirstInvalidField(slugs);
					found = true;
				}
			});
		}
		return ok;
	}

	function recalculate() {
		var current = state.visibleSteps[state.currentStep];
		state.visibleSteps = visibleSteps();
		if (current) {
			var nextIndex = -1;
			state.visibleSteps.forEach(function (step, i) {
				if (step.slug === current.slug) {
					nextIndex = i;
				}
			});
			state.currentStep = nextIndex === -1 ? Math.min(state.currentStep, Math.max(0, state.visibleSteps.length - 1)) : nextIndex;
		}
		if (state.currentStep >= state.visibleSteps.length) {
			state.currentStep = Math.max(0, state.visibleSteps.length - 1);
		}
	}

	function screenBelongsToFieldset(screen, el) {
		if (!screen) {
			return false;
		}
		var sid = Number(el.getAttribute('data-step-id') || 0);
		var page = el.getAttribute('data-step');
		if (screen.questions && screen.questions.length) {
			return screen.questions.some(function (q) {
				if (sid && Number(q.step_id) === sid) {
					return true;
				}
				return q.slug === page;
			});
		}
		if (sid && Number(screen.id) === sid) {
			return true;
		}
		return page === screen.slug;
	}

	function renderStep() {
		recalculate();
		var current = state.visibleSteps[state.currentStep];
		form.querySelectorAll('[data-step]').forEach(function (el) {
			var on = screenBelongsToFieldset(current, el);
			el.hidden = !on;
			el.classList.toggle('is-active', on);
			el.querySelectorAll(':scope > .ck-feedback__helper').forEach(function (helper) {
				helper.hidden = !(current && current.questions && current.questions.length > 1);
			});
		});
		form.querySelectorAll('[data-field]').forEach(function (block) {
			block.hidden = true;
		});
		if (current) {
			(current.questions || []).forEach(function (q) {
				var block = fieldEl(q.slug);
				if (!block) {
					return;
				}
				var vis = applies(q, state.answers) && !(state.productLocked && q.slug === 'product' && state.answers.product);
				block.hidden = !vis;
				var sub = block.querySelector('.ck-feedback__question--sub');
				if (sub) {
					sub.hidden = current.questions.length === 1 && (current.title === q.title || !q.title);
				}
			});
			var activePage = form.querySelector('[data-step].is-active legend');
			if (activePage && current.title) {
				activePage.textContent = current.title;
			}
		}
		var last = state.currentStep === state.visibleSteps.length - 1;
		nextBtn.hidden = last;
		nextBtn.setAttribute('aria-hidden', last ? 'true' : 'false');
		submitBtn.hidden = !last;
		backBtn.hidden = state.currentStep === 0;
		nav.classList.toggle('is-last', last);
		progress.hidden = false;
		nav.hidden = false;
		if (footer) {
			footer.hidden = false;
		}
		counter.textContent = pad(state.currentStep + 1) + ' / ' + pad(state.visibleSteps.length || 1);
		fill.style.width = state.visibleSteps.length ? ((state.currentStep + 1) / state.visibleSteps.length) * 100 + '%' : '0';
		var first = form.querySelector('[data-step]:not([hidden]) input:not([type="hidden"]):not([tabindex="-1"]), [data-step]:not([hidden]) textarea, [data-step]:not([hidden]) select');
		if (first && !state.isSubmitting) {
			first.focus();
		}
	}

	function setFormError(message) {
		if (!formError) {
			return;
		}
		formError.hidden = !message;
		formError.textContent = message || '';
	}

	function startForm() {
		intro.hidden = true;
		intro.classList.remove('is-active');
		form.hidden = false;
		state.currentStep = 0;
		syncAnswersFromDom();
		renderStep();
	}

	function applyPrefill() {
		var pre = cfg.prefill || {};
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
				state.productLocked = true;
				state.answers.product = pre.product;
				prefillEl.hidden = false;
				prefillEl.textContent = '';
				prefillEl.appendChild(document.createTextNode((i18n.evaluating || '') + ' '));
				var strong = document.createElement('strong');
				strong.textContent = (cfg.products && cfg.products[pre.product]) || pre.product;
				prefillEl.appendChild(strong);
				var change = document.createElement('button');
				change.type = 'button';
				change.textContent = i18n.change;
				change.addEventListener('click', function () {
					state.productLocked = false;
					prefillEl.hidden = true;
					state.currentStep = 0;
					syncAnswersFromDom();
					renderStep();
				});
				prefillEl.appendChild(change);
			}
		}
		if (pre.fragrance) {
			var frag = form.querySelector('[name="fragrance"][value="' + pre.fragrance + '"]');
			if (frag) {
				frag.checked = true;
				state.answers.fragrance = pre.fragrance;
			}
		}
	}

	function collectPayload() {
		syncAnswersFromDom();
		recalculate();
		var answers = {};
		questions.forEach(function (q) {
			if (q.type === 'hidden') {
				var hiddenVal = state.answers[q.slug] || (q.settings && q.settings.default_value) || '';
				if (hiddenVal) {
					answers[q.slug] = hiddenVal;
				}
			}
		});
		state.visibleSteps.forEach(function (step) {
			applicableQuestions(step, state.answers).forEach(function (q) {
				if (q.type === 'info') {
					return;
				}
				var result = CKFValidate.validateAnswer(q, state.answers[q.slug], i18n);
				if (!result.ok) {
					return;
				}
				if (q.type === 'multi_choice') {
					answers[q.slug] = result.values;
				} else if ((q.type === 'yes_no' && q.settings && q.settings.ui === 'checkbox') || q.type === 'checkbox' || q.type === 'consent') {
					answers[q.slug] = result.values.length ? '1' : '';
				} else {
					answers[q.slug] = result.values[0] || '';
				}
			});
		});
		var payload = {
			answers: answers,
			website: form.querySelector('[name="website"]').value,
			source: form.querySelector('[name="source"]').value,
			campaign: form.querySelector('[name="campaign"]').value,
			batch: form.querySelector('[name="batch"]').value,
			product_code: form.querySelector('[name="product_code"]').value,
			survey_id: cfg.surveyId || (form.querySelector('[name="survey_id"]') && form.querySelector('[name="survey_id"]').value) || 0,
			survey: cfg.survey || '',
			nonce: cfg.nonce
		};
		return payload;
	}

	function updateCount(el) {
		var slug = el.getAttribute('data-count-for');
		var max = el.getAttribute('data-max');
		var field = form.querySelector('[name="' + slug + '"]');
		if (!field) {
			return;
		}
		el.textContent = String(field.value.length) + ' / ' + max;
	}

	form.querySelectorAll('[data-count-for]').forEach(updateCount);

	form.addEventListener('input', function (event) {
		var name = event.target.name ? event.target.name.replace(/\[\]$/, '') : '';
		if (!name || !bySlug[name]) {
			return;
		}
		if (bySlug[name].type === 'multi_choice') {
			var q = bySlug[name];
			var max = q.settings && q.settings.max_selections != null ? parseInt(q.settings.max_selections, 10) : 0;
			if (max && event.target.type === 'checkbox' && event.target.checked) {
				var selected = readDomValue(name);
				if (selected.length > max) {
					event.target.checked = false;
					if (state.attempted[state.visibleSteps[state.currentStep] && state.visibleSteps[state.currentStep].slug]) {
						showFieldError(name, (i18n.maxSelect || '').replace('%s', max));
					}
					return;
				}
			}
		}
		syncAnswersFromDom();
		recalculate();
		if (state.attempted[name] || state.errors[name]) {
			validateField(bySlug[name], true);
		}
		var counterEl = form.querySelector('[data-count-for="' + name + '"]');
		if (counterEl) {
			updateCount(counterEl);
		}
		renderProgressOnly();
	});

	form.addEventListener('change', function (event) {
		var name = event.target.name ? event.target.name.replace(/\[\]$/, '') : '';
		if (!name || !bySlug[name]) {
			return;
		}
		syncAnswersFromDom();
		recalculate();
		if (state.attempted[name] || state.errors[name]) {
			validateField(bySlug[name], true);
		}
		renderStepKeepFocus();
	});

	function renderProgressOnly() {
		var current = state.visibleSteps[state.currentStep];
		counter.textContent = pad(state.currentStep + 1) + ' / ' + pad(state.visibleSteps.length || 1);
		fill.style.width = state.visibleSteps.length ? ((state.currentStep + 1) / state.visibleSteps.length) * 100 + '%' : '0';
		if (current && current.questions) {
			form.querySelectorAll('[data-field]').forEach(function (block) {
				var slug = block.getAttribute('data-field');
				block.hidden = !current.questions.some(function (q) {
					return q.slug === slug && applies(q, state.answers);
				});
			});
		}
	}

	function renderStepKeepFocus() {
		var active = document.activeElement;
		var slug = active && active.name ? active.name.replace(/\[\]$/, '') : '';
		renderStep();
		if (slug) {
			var again = controlEl(slug);
			if (again && again === active) {
				return;
			}
			if (again && again.type !== 'radio' && again.type !== 'checkbox') {
				again.focus();
			}
		}
	}

	root.querySelector('[data-start]').addEventListener('click', startForm);

	nextBtn.addEventListener('click', function () {
		syncAnswersFromDom();
		recalculate();
		var step = state.visibleSteps[state.currentStep];
		if (step) {
			state.attempted[step.slug] = true;
			applicableQuestions(step, state.answers).forEach(function (q) {
				state.attempted[q.slug] = true;
			});
			if (!validateStep(step, true)) {
				return;
			}
		}
		state.currentStep += 1;
		renderStep();
	});

	backBtn.addEventListener('click', function () {
		syncAnswersFromDom();
		state.currentStep = Math.max(0, state.currentStep - 1);
		renderStep();
	});

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		setFormError('');
		if (state.isSubmitting) {
			return;
		}
		syncAnswersFromDom();
		var step = state.visibleSteps[state.currentStep];
		if (step) {
			state.attempted[step.slug] = true;
			applicableQuestions(step, state.answers).forEach(function (q) {
				state.attempted[q.slug] = true;
			});
		}
		if (!validateAll()) {
			return;
		}
		if (isPreview) {
			form.hidden = true;
			thanks.hidden = false;
			thanks.classList.add('is-active');
			return;
		}
		state.isSubmitting = true;
		submitBtn.disabled = true;
		submitBtn.textContent = i18n.sending || 'Enviando...';
		form.setAttribute('aria-busy', 'true');

		fetch(cfg.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce
			},
			body: JSON.stringify(collectPayload())
		})
			.then(function (response) {
				return response.json().then(function (body) {
					if (!response.ok || body.success === false) {
						var err = new Error(body.message || (body.data && body.data.message) || i18n.serverError);
						err.slug = body.data && body.data.slug;
						err.status = response.status;
						throw err;
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
				if (error.slug) {
					showFieldError(error.slug, error.message);
					var node = controlEl(error.slug);
					if (node) {
						node.focus();
					}
				} else {
					setFormError(error.message || i18n.serverError);
				}
				state.isSubmitting = false;
				submitBtn.disabled = false;
				submitBtn.textContent = i18n.submit || 'Enviar';
				form.removeAttribute('aria-busy');
			});
	});

	applyPrefill();
	syncAnswersFromDom();
	state.visibleSteps = visibleSteps();
	}

	document.querySelectorAll('[data-ck-feedback]').forEach(function (root) {
		boot(root, mergeConfig(root));
	});
}());
