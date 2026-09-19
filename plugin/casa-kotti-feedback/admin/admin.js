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
	function enableDrag(listSelector, itemSelector) {
		var lists = document.querySelectorAll(listSelector);
		lists.forEach(function (list) {
			var dragging = null;
			list.addEventListener('dragstart', function (event) {
				dragging = event.target.closest(itemSelector);
				if (dragging) {
					event.dataTransfer.effectAllowed = 'move';
				}
			});
			list.addEventListener('dragover', function (event) {
				event.preventDefault();
				var over = event.target.closest(itemSelector);
				if (!dragging || !over || over === dragging) {
					return;
				}
				var rect = over.getBoundingClientRect();
				var before = (event.clientY - rect.top) < rect.height / 2;
				list.insertBefore(dragging, before ? over : over.nextSibling);
			});
		});
	}
	enableDrag('#ckf-pages', '.ckf-page-card');
	enableDrag('.ckf-q-list', 'li');

	document.querySelectorAll('.ckf-jump-step').forEach(function (sel) {
		sel.addEventListener('change', function () {
			var form = document.createElement('form');
			form.method = 'post';
			form.action = (typeof ckfAdmin !== 'undefined' && ckfAdmin.adminPost) ? ckfAdmin.adminPost : 'admin-post.php';
			form.innerHTML = '<input type="hidden" name="action" value="ckf_move_question">'
				+ '<input type="hidden" name="id" value="' + sel.getAttribute('data-id') + '">'
				+ '<input type="hidden" name="step_id" value="' + sel.value + '">'
				+ '<input type="hidden" name="survey_id" value="' + (sel.getAttribute('data-survey') || '') + '">'
				+ '<input type="hidden" name="_wpnonce" value="' + (ckfAdmin && ckfAdmin.moveNonce ? ckfAdmin.moveNonce : '') + '">';
			document.body.appendChild(form);
			form.submit();
		});
	});

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
