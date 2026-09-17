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
	var submitBtn = root.querySelector('[data-submit]');
	var prefillEl = root.querySelector('[data-prefill]');
	var specificLegend = root.querySelector('[data-specific-legend]');
	var specificOptions = root.querySelector('[data-specific-options]');

	var sequence = [];
	var index = 0;
	var lock = false;
	var productLocked = false;

	function pad(n) {
		return n < 10 ? '0' + n : String(n);
	}

	function selected(name) {
		var el = form.querySelector('[name="' + name + '"]:checked');
		return el ? el.value : '';
	}

	function product() {
		return selected('product');
	}

	function needsRefil() {
		return product() === 'refil';
	}

	function specificKey() {
		if (product() === 'mais-de-um') {
			return '';
		}
		if (product() === 'refil') {
			return selected('refil_target') === 'difusor' ? 'difusor' : '';
		}
		return ckfForm.specific[product()] ? product() : '';
	}

	function buildSequence() {
		sequence = ['product', 'fragrance', 'overall', 'intensity'];
		if (needsRefil()) {
			sequence.push('refil-target');
		}
		if (specificKey()) {
			sequence.push('product-specific');
		}
		sequence.push('performance', 'presentation', 'repurchase', 'nps', 'improvement', 'positive', 'contact');
	}

	function renderSpecific() {
		var key = specificKey();
		specificOptions.innerHTML = '';
		if (!key || !ckfForm.specific[key]) {
			return;
		}
		specificLegend.textContent = ckfForm.specific[key].question;
		Object.keys(ckfForm.specific[key].options).forEach(function (value) {
			var label = document.createElement('label');
			label.className = 'ck-feedback__option';
			label.innerHTML = '<input type="radio" name="product_specific_answer" value="' + value.replace(/"/g, '') + '"><span></span>';
			label.querySelector('span').textContent = ckfForm.specific[key].options[value];
			specificOptions.appendChild(label);
		});
	}

	function showStep() {
		form.querySelectorAll('[data-step]').forEach(function (step) {
			step.hidden = step.getAttribute('data-step') !== sequence[index];
			step.classList.toggle('is-active', !step.hidden);
		});
		var last = index === sequence.length - 1;
		nextBtn.hidden = last;
		submitBtn.hidden = !last;
		progress.hidden = false;
		nav.hidden = false;
		counter.textContent = pad(index + 1) + ' / ' + pad(sequence.length);
		fill.style.width = ((index + 1) / sequence.length) * 100 + '%';
		var first = form.querySelector('[data-step]:not([hidden]) input, [data-step]:not([hidden]) textarea');
		if (first) {
			first.focus();
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
		var step = sequence[index];
		if (step === 'improvement' || step === 'positive') {
			return true;
		}
		if (step === 'contact') {
			var email = form.querySelector('[name="customer_email"]');
			if (email.value && !email.checkValidity()) {
				setError(ckfForm.i18n.invalidEmail);
				email.focus();
				return false;
			}
			return true;
		}
		var map = {
			product: 'product',
			fragrance: 'fragrance',
			overall: 'overall_rating',
			intensity: 'intensity',
			'refil-target': 'refil_target',
			'product-specific': 'product_specific_answer',
			performance: 'performance',
			presentation: 'presentation_rating',
			repurchase: 'repurchase_intent',
			nps: 'nps_score'
		};
		if (map[step] && !selected(map[step])) {
			setError(ckfForm.i18n.selectOption);
			return false;
		}
		return true;
	}

	function startForm() {
		intro.hidden = true;
		intro.classList.remove('is-active');
		form.hidden = false;
		buildSequence();
		if (productLocked && product()) {
			sequence = sequence.filter(function (id) {
				return id !== 'product';
			});
		}
		index = 0;
		showStep();
	}

	function applyPrefill() {
		var pre = ckfForm.prefill || {};
		form.querySelector('[name="source"]').value = pre.source || '';
		form.querySelector('[name="campaign"]').value = pre.campaign || '';
		form.querySelector('[name="batch"]').value = pre.batch || '';
		if (pre.product && ckfForm.products[pre.product]) {
			var radio = form.querySelector('[name="product"][value="' + pre.product + '"]');
			if (radio) {
				radio.checked = true;
				productLocked = true;
				prefillEl.hidden = false;
				prefillEl.innerHTML = ckfForm.i18n.evaluating + ' <strong></strong> ';
				prefillEl.querySelector('strong').textContent = ckfForm.products[pre.product];
				var change = document.createElement('button');
				change.type = 'button';
				change.textContent = ckfForm.i18n.change;
				change.addEventListener('click', function () {
					productLocked = false;
					prefillEl.hidden = true;
					buildSequence();
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

	root.querySelector('[data-start]').addEventListener('click', startForm);

	nextBtn.addEventListener('click', function () {
		setError('');
		if (!validateStep()) {
			return;
		}
		if (sequence[index] === 'product' || sequence[index] === 'refil-target') {
			var current = sequence[index];
			buildSequence();
			if (productLocked) {
				sequence = sequence.filter(function (id) {
					return id !== 'product';
				});
			}
			index = sequence.indexOf(current);
			if (specificKey()) {
				renderSpecific();
			}
		}
		index += 1;
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

		var payload = {
			product: selected('product'),
			fragrance: selected('fragrance'),
			overall_rating: selected('overall_rating'),
			intensity: selected('intensity'),
			refil_target: selected('refil_target'),
			product_specific_answer: selected('product_specific_answer'),
			performance: selected('performance'),
			presentation_rating: selected('presentation_rating'),
			repurchase_intent: selected('repurchase_intent'),
			nps_score: selected('nps_score'),
			improvement_comment: form.querySelector('[name="improvement_comment"]').value,
			positive_comment: form.querySelector('[name="positive_comment"]').value,
			customer_name: form.querySelector('[name="customer_name"]').value,
			customer_email: form.querySelector('[name="customer_email"]').value,
			marketing_consent: form.querySelector('[name="marketing_consent"]').checked ? 1 : 0,
			website: form.querySelector('[name="website"]').value,
			source: form.querySelector('[name="source"]').value,
			campaign: form.querySelector('[name="campaign"]').value,
			batch: form.querySelector('[name="batch"]').value,
			product_code: form.querySelector('[name="product_code"]').value,
			nonce: ckfForm.nonce
		};

		fetch(ckfForm.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': ckfForm.nonce
			},
			body: JSON.stringify(payload)
		})
			.then(function (response) {
				return response.json().then(function (body) {
					if (!response.ok || body.success === false) {
						var message = (body.message) || (body.data && body.data.message) || ckfForm.i18n.serverError;
						throw new Error(message);
					}
					return body;
				});
			})
			.then(function () {
				form.hidden = true;
				thanks.hidden = false;
				thanks.classList.add('is-active');
				thanks.querySelector('.ck-feedback__btn').focus();
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
