(function (root) {
	'use strict';

	var TEXT_MAX = 190;
	var TEXTAREA_MAX = 4000;

	function messages(i18n) {
		return i18n || {};
	}

	function fmt(tpl, a, b) {
		if (!tpl) {
			return '';
		}
		return String(tpl).replace('%s', a).replace('%1$s', a).replace('%2$s', b);
	}

	function asList(raw) {
		if (raw == null || raw === '') {
			return [];
		}
		return Array.isArray(raw) ? raw.slice() : [raw];
	}

	function isEmail(value) {
		var v = String(value).trim();
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
	}

	function allowedValues(question) {
		return (question.options || []).map(function (opt) {
			return String(opt.value);
		});
	}

	function normalizeAnswer(question, raw) {
		var type = question.type;
		if (type === 'info') {
			return { empty: true, value: null, values: [] };
		}
		if (type === 'multi_choice') {
			var allowed = allowedValues(question);
			var clean = asList(raw).map(String).filter(function (v) {
				return allowed.indexOf(v) !== -1;
			});
			return { empty: !clean.length, value: clean, values: clean };
		}
		if (type === 'yes_no' && question.settings && question.settings.ui === 'checkbox') {
			var on = asList(raw).some(function (v) {
				return ['1', 'sim', 'yes', 'true'].indexOf(String(v)) !== -1;
			});
			return { empty: !on, value: on, values: on ? ['1'] : [] };
		}
		if (type === 'text' || type === 'textarea' || type === 'email') {
			var text = String(raw == null ? '' : (Array.isArray(raw) ? raw[0] : raw)).replace(/^\s+|\s+$/g, '');
			return { empty: text === '', value: text, values: text ? [text] : [] };
		}
		if (type === 'number' || type === 'stars' || type === 'scale') {
			var nraw = Array.isArray(raw) ? raw[0] : raw;
			if (nraw === '' || nraw == null) {
				return { empty: true, value: null, values: [] };
			}
			var num = type === 'number' ? parseFloat(nraw) : parseInt(nraw, 10);
			return { empty: false, value: num, values: [String(num)] };
		}
		var one = Array.isArray(raw) ? String(raw[0] || '') : String(raw == null ? '' : raw);
		return { empty: one === '', value: one, values: one ? [one] : [] };
	}

	function validateAnswer(question, raw, i18n) {
		var msg = messages(i18n);
		var type = question.type;
		var settings = question.settings || {};
		var required = !!question.required;
		var allowed = allowedValues(question);

		if (type === 'info') {
			return { ok: true, values: [], error: '' };
		}

		if (type === 'multi_choice') {
			var values = asList(raw).map(String);
			var clean = [];
			var i;
			for (i = 0; i < values.length; i++) {
				if (allowed.indexOf(values[i]) !== -1) {
					clean.push(values[i]);
				} else if (values[i]) {
					return { ok: false, values: [], error: msg.selectOption };
				}
			}
			var min = settings.min_selections != null ? parseInt(settings.min_selections, 10) : (required ? 1 : 0);
			var max = settings.max_selections != null ? parseInt(settings.max_selections, 10) : allowed.length;
			if (required && !clean.length) {
				return { ok: false, values: [], error: msg.selectOption };
			}
			if (clean.length && min && clean.length < min) {
				return { ok: false, values: clean, error: fmt(msg.minSelect, min) };
			}
			if (max && clean.length > max) {
				return { ok: false, values: clean, error: fmt(msg.maxSelect, max) };
			}
			return { ok: true, values: clean, error: '' };
		}

		if (type === 'single_choice' || type === 'radio' || type === 'select') {
			var value = String(Array.isArray(raw) ? (raw[0] || '') : (raw == null ? '' : raw));
			if (value === '') {
				return required ? { ok: false, values: [], error: msg.selectOption } : { ok: true, values: [], error: '' };
			}
			if (allowed.indexOf(value) === -1) {
				return { ok: false, values: [], error: msg.selectOption };
			}
			return { ok: true, values: [value], error: '' };
		}

		if (type === 'yes_no') {
			var yv = Array.isArray(raw) ? String(raw[0] || '') : String(raw == null ? '' : raw);
			if (settings.ui === 'checkbox') {
				var on = ['1', 'sim', 'yes', 'true'].indexOf(yv) !== -1;
				if (required && !on) {
					return { ok: false, values: [], error: msg.required };
				}
				return { ok: true, values: on ? ['1'] : [], error: '' };
			}
			if (yv === '') {
				return required ? { ok: false, values: [], error: msg.selectOption } : { ok: true, values: [], error: '' };
			}
			if (['sim', 'nao', 'yes', 'no', '1', '0'].indexOf(yv) === -1) {
				return { ok: false, values: [], error: msg.selectOption };
			}
			return { ok: true, values: [yv], error: '' };
		}

		if (type === 'stars' || type === 'scale' || type === 'number') {
			var rawn = Array.isArray(raw) ? raw[0] : raw;
			if (rawn === '' || rawn == null) {
				var emptyErr = type === 'stars' ? msg.selectStars : (type === 'number' ? msg.required : msg.selectOption);
				return required ? { ok: false, values: [], error: emptyErr } : { ok: true, values: [], error: '' };
			}
			if (typeof rawn === 'boolean' || isNaN(Number(rawn))) {
				return { ok: false, values: [], error: msg.invalidNumber };
			}
			var num = type === 'number' ? parseFloat(rawn) : parseInt(rawn, 10);
			var minn = settings.min != null && settings.min !== '' ? Number(settings.min) : (type === 'scale' ? 0 : 1);
			var maxn = settings.max != null && settings.max !== '' ? Number(settings.max) : (type === 'scale' ? 10 : 5);
			if (num < minn || num > maxn) {
				return { ok: false, values: [], error: fmt(msg.starsRange, String(minn), String(maxn)) };
			}
			if (type === 'number' && settings.step && !isNaN(Number(settings.step))) {
				var step = Number(settings.step);
				if (step > 0) {
					var mod = Math.abs(((num - minn) / step) % 1);
					if (mod > 0.0001 && Math.abs(1 - mod) > 0.0001) {
						return { ok: false, values: [], error: msg.invalidNumber };
					}
				}
			}
			return { ok: true, values: [String(num)], error: '' };
		}

		if (type === 'email') {
			var email = String(raw == null ? '' : (Array.isArray(raw) ? raw[0] : raw)).replace(/^\s+|\s+$/g, '');
			if (email === '') {
				return required ? { ok: false, values: [], error: msg.required } : { ok: true, values: [], error: '' };
			}
			if (!isEmail(email)) {
				return { ok: false, values: [], error: msg.invalidEmail };
			}
			return { ok: true, values: [email], error: '' };
		}

		var text = String(raw == null ? '' : (Array.isArray(raw) ? raw[0] : raw)).replace(/^\s+|\s+$/g, '');
		var cap = type === 'textarea' ? TEXTAREA_MAX : TEXT_MAX;
		if (settings.max_length) {
			cap = Math.min(cap, parseInt(settings.max_length, 10));
		}
		var minl = settings.min_length ? parseInt(settings.min_length, 10) : 0;
		if (text === '') {
			return required ? { ok: false, values: [], error: msg.required } : { ok: true, values: [], error: '' };
		}
		if (minl && text.length < minl) {
			return { ok: false, values: [text], error: fmt(msg.minLength, minl) };
		}
		if (text.length > cap) {
			return { ok: false, values: [text], error: fmt(msg.maxLength, cap) };
		}
		return { ok: true, values: [text], error: '' };
	}

	var api = {
		validateAnswer: validateAnswer,
		normalizeAnswer: normalizeAnswer,
		isEmail: isEmail
	};

	if (typeof module !== 'undefined' && module.exports) {
		module.exports = api;
	}
	root.CKFValidate = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
