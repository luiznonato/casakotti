(function (window, document) {
	'use strict';

	var cfg = window.MocoriInsightsConfig || {};
	var consentCfg = cfg.consent || {};
	var queue = Array.isArray(window.MocoriInsights) ? window.MocoriInsights.slice() : [];
	var visitorId = '';
	var sessionId = '';
	var started = false;
	var consentGranted = !consentCfg.requireConsent;
	var COOKIE_VISITOR = consentCfg.cookieVisitor || 'mocori_insights_visitor';
	var COOKIE_SESSION = consentCfg.cookieSession || 'mocori_insights_session';
	var IDLE_MS = (consentCfg.sessionMinutes || 30) * 60 * 1000;

	function uuid() {
		if (window.crypto && crypto.randomUUID) {
			return crypto.randomUUID();
		}
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
			var r = Math.random() * 16 | 0;
			return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
		});
	}

	function readCookie(name) {
		var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[$()*+./?[\\\]^{|}]/g, '\\$&') + '=([^;]*)'));
		return m ? decodeURIComponent(m[1]) : '';
	}

	function writeCookie(name, value, maxAge) {
		if (consentCfg.cookieless) {
			return;
		}
		document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; SameSite=Lax; max-age=' + maxAge;
	}

	function storageGet(key) {
		try {
			return window.sessionStorage ? sessionStorage.getItem(key) || '' : '';
		} catch (e) {
			return '';
		}
	}

	function storageSet(key, value) {
		try {
			if (window.sessionStorage) {
				sessionStorage.setItem(key, value);
			}
		} catch (e) {}
	}

	function ids() {
		if (consentCfg.cookieless) {
			visitorId = visitorId || uuid();
			sessionId = sessionId || uuid();
			return;
		}
		visitorId = readCookie(COOKIE_VISITOR) || storageGet(COOKIE_VISITOR) || uuid();
		sessionId = readCookie(COOKIE_SESSION) || storageGet(COOKIE_SESSION) || uuid();
		writeCookie(COOKIE_VISITOR, visitorId, 60 * 60 * 24 * 365);
		writeCookie(COOKIE_SESSION, sessionId, 60 * 30);
		storageSet(COOKIE_VISITOR, visitorId);
		storageSet(COOKIE_SESSION, sessionId);
	}

	function utm() {
		var params = {};
		try {
			var search = new URLSearchParams(window.location.search);
			['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'].forEach(function (key) {
				var val = search.get(key);
				if (val) {
					params[key] = val.slice(0, 191);
				}
			});
		} catch (e) {}
		return params;
	}

	function send(events) {
		if (!cfg.endpoint || !consentGranted || !events.length) {
			return;
		}
		ids();
		var body = JSON.stringify({
			token: cfg.token || '',
			visitor_id: visitorId,
			session_id: sessionId,
			page_url: window.location.href,
			referrer: document.referrer || '',
			utm: utm(),
			consent: consentGranted ? 1 : 0,
			events: events
		});
		var blob = new Blob([body], { type: 'application/json' });
		if (navigator.sendBeacon) {
			navigator.sendBeacon(cfg.endpoint, blob);
			return;
		}
		fetch(cfg.endpoint, {
			method: 'POST',
			body: body,
			keepalive: true,
			headers: {
				'Content-Type': 'application/json',
				'X-Mocori-Insights-Token': cfg.token || ''
			}
		}).catch(function () {});
	}

	function track(name, metadata) {
		send([{ name: name, page_url: window.location.href, metadata: metadata || {} }]);
	}

	function convert(name, metadata) {
		var meta = metadata || {};
		if (!meta.conversion_name) {
			meta.conversion_name = name;
		}
		track('conversion', meta);
	}

	function identify(payload) {
		track('lead_created', payload || {});
	}

	function setConsent(granted) {
		consentGranted = !!granted;
		if (consentGranted && !started) {
			boot();
		}
	}

	function watchForms() {
		document.querySelectorAll('form[data-mocori-insights-form]').forEach(function (form) {
			if (form.__mocoriInsightsBound) {
				return;
			}
			form.__mocoriInsightsBound = true;
			var formId = form.getAttribute('data-mocori-insights-form') || form.id || 'form';
			var formName = form.getAttribute('data-mocori-insights-form-name') || '';
			var viewed = false;
			var startedForm = false;
			function meta() {
				return { form_id: formId, form_name: formName };
			}
			if ('IntersectionObserver' in window) {
				var io = new IntersectionObserver(function (entries) {
					entries.forEach(function (entry) {
						if (!viewed && entry.isIntersecting) {
							viewed = true;
							track('form_view', meta());
						}
					});
				}, { threshold: 0.4 });
				io.observe(form);
			} else {
				viewed = true;
				track('form_view', meta());
			}
			form.addEventListener('focusin', function () {
				if (!startedForm) {
					startedForm = true;
					track('form_start', meta());
				}
			});
			form.addEventListener('submit', function () {
				if (form.getAttribute('data-mocori-insights-skip-submit')) {
					return;
				}
				track('form_submit', meta());
			});
		});
	}

	function outbound() {
		document.addEventListener('click', function (event) {
			var a = event.target && event.target.closest ? event.target.closest('a[href]') : null;
			if (!a) {
				return;
			}
			try {
				var url = new URL(a.href, window.location.href);
				if (url.host && url.host !== window.location.host) {
					track('outbound_click', { href: url.origin + url.pathname });
				}
			} catch (e) {}
		});
	}

	function boot() {
		if (started || !consentGranted) {
			return;
		}
		started = true;
		ids();
		track('page_view', { title: document.title || '' });
		watchForms();
		outbound();
	}

	window.MocoriInsights = {
		track: track,
		identify: identify,
		convert: convert,
		setConsent: setConsent,
		consent: setConsent
	};

	queue.forEach(function (item) {
		if (Array.isArray(item) && typeof window.MocoriInsights[item[0]] === 'function') {
			window.MocoriInsights[item[0]].apply(null, item.slice(1));
		}
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
}(window, document));
