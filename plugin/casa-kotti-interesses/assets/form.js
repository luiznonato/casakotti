(function () {
	'use strict';

	function isValidEmail(value) {
		var email = String(value || '').trim();
		if (!email) {
			return false;
		}
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
	}

	document.querySelectorAll('.ck-form').forEach(function (form) {
		var email = form.querySelector('input[name="email"]');
		var consent = form.querySelector('input[name="consent"]');
		var consentBox = form.querySelector('.ck-form__consent');
		var consentError = form.querySelector('.ck-form__consent-error');
		var button = form.querySelector('button[type="submit"]');
		var status = form.querySelector('.ck-form__status');
		var originalButtonText = button ? button.textContent : '';

		function announce(message, state) {
			if (!status) {
				return;
			}
			status.textContent = message;
			status.dataset.state = state || '';
		}

		function setConsentError(show) {
			if (consentBox) {
				consentBox.classList.toggle('is-invalid', !!show);
			}
			if (consentError) {
				consentError.hidden = !show;
			}
		}

		if (consent) {
			consent.addEventListener('change', function () {
				if (consent.checked) {
					setConsentError(false);
				}
			});
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			event.stopImmediatePropagation();
			announce('', '');
			setConsentError(false);

			if (!email) {
				return;
			}

			email.value = String(email.value || '').trim();

			if (!isValidEmail(email.value)) {
				announce((window.ckiForm && ckiForm.invalidEmail) || 'Digite um e-mail válido.', 'error');
				if (email) {
					email.focus();
				}
				return;
			}

			if (!consent || !consent.checked) {
				setConsentError(true);
				if (!consentError) {
					announce((window.ckiForm && ckiForm.consentNeeded) || 'É necessário aceitar para receber novidades.', 'error');
				}
				if (consent) {
					consent.focus();
				}
				return;
			}

			if (!button) {
				return;
			}

			var endpoint = form.getAttribute('action');
			var payload = new FormData(form);
			var fallback = (window.ckiForm && ckiForm.genericError) || 'Não foi possível cadastrar agora. Tente novamente.';

			button.disabled = true;
			button.textContent = (window.ckiForm && ckiForm.sending) || 'Enviando…';
			form.setAttribute('aria-busy', 'true');

			fetch(endpoint, {
				method: 'POST',
				body: payload,
				credentials: 'same-origin',
				headers: { 'X-Requested-With': 'XMLHttpRequest' }
			})
				.then(function (response) {
					return response.text().then(function (text) {
						var data;
						try {
							data = JSON.parse(text);
						} catch (parseError) {
							throw new Error(fallback);
						}
						if (!response.ok || !data.success) {
							var message = data && data.data && data.data.message;
							throw new Error(message || fallback);
						}
						return data;
					});
				})
				.then(function () {
					form.reset();
					setConsentError(false);
					announce(form.dataset.success, 'success');
				})
				.catch(function (error) {
					var message = error && error.message;
					if (!message || /pattern|JSON|fetch|Network|Load failed|unexpected/i.test(message)) {
						message = fallback;
					}
					announce(message, 'error');
				})
				.finally(function () {
					button.disabled = false;
					button.textContent = originalButtonText;
					form.removeAttribute('aria-busy');
				});
		});
	});
}());
