(function () {
	'use strict';

	var type = document.getElementById('ckf-type');
	if (type) {
		var rows = document.querySelectorAll('.ckf-type-field');
		function sync() {
			rows.forEach(function (row) {
				var list = (row.getAttribute('data-types') || '').split(',');
				row.hidden = list.indexOf(type.value) === -1;
			});
		}
		type.addEventListener('change', sync);
		sync();
	}

	var add = document.getElementById('ckf-add-option');
	var table = document.getElementById('ckf-options');
	if (add && table) {
		add.addEventListener('click', function () {
			var body = table.querySelector('tbody');
			var row = document.createElement('tr');
			row.innerHTML = '<td><input class="regular-text" name="opt_label[]"></td><td><input class="regular-text" name="opt_value[]"><input type="hidden" name="opt_id[]" value="0"></td>';
			body.appendChild(row);
		});
	}

	var previewBtn = document.getElementById('ckf-preview-btn');
	var preview = document.getElementById('ckf-preview');
	var form = document.getElementById('ckf-question-form');
	if (previewBtn && preview && form && typeof ckfAdmin !== 'undefined') {
		previewBtn.addEventListener('click', function () {
			var data = new FormData(form);
			data.set('action', 'ckf_preview_question');
			data.set('nonce', ckfAdmin.previewNonce);
			fetch(ckfAdmin.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (payload) {
					if (!payload.success) {
						return;
					}
					preview.hidden = false;
					preview.innerHTML = payload.data.html;
				});
		});
	}
}());
