(function () {
	'use strict';

	document.querySelectorAll('.ck-form').forEach(function (form) {
		var email = form.querySelector('input[type="email"]');
		var consent = form.querySelector('input[name="consent"]');
		var button = form.querySelector('button[type="submit"]');
		var status = form.querySelector('.ck-form__status');
		var originalButtonText = button.textContent;

		function announce(message, state) {
			status.textContent = message;
			status.dataset.state = state || '';
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			announce('', '');

			if (!email.validity.valid) {
				announce(ckiForm.invalidEmail, 'error');
				email.focus();
				return;
			}

			if (!consent.checked) {
				announce(ckiForm.consentNeeded, 'error');
				consent.focus();
				return;
			}

			button.disabled = true;
			button.textContent = ckiForm.sending;
			form.setAttribute('aria-busy', 'true');

			fetch(form.action, {
				method: 'POST',
				body: new FormData(form),
				credentials: 'same-origin',
				headers: { 'X-Requested-With': 'XMLHttpRequest' }
			})
				.then(function (response) {
					return response.json().then(function (payload) {
						if (!response.ok || !payload.success) {
							var message = payload.data && payload.data.message;
							throw new Error(message || ckiForm.genericError);
						}
						return payload;
					});
				})
				.then(function () {
					form.reset();
					announce(form.dataset.success, 'success');
					email.focus();
				})
				.catch(function (error) {
					announce(error.message || ckiForm.genericError, 'error');
				})
				.finally(function () {
					button.disabled = false;
					button.textContent = originalButtonText;
					form.removeAttribute('aria-busy');
				});
		});
	});
}());
