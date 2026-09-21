'use strict';

const fs = require('fs');
const path = require('path');
const vm = require('vm');

let fails = 0;
function assert(cond, label) {
	if (cond) {
		console.log('PASS  ' + label);
		return;
	}
	fails++;
	console.log('FAIL  ' + label);
}

const cookies = {};
const beacons = [];
const store = {};

const sandbox = {
	window: {},
	document: {
		cookie: '',
		referrer: 'https://l.instagram.com/',
		title: 'Home',
		readyState: 'complete',
		querySelectorAll: () => [],
		addEventListener: () => {},
	},
	navigator: {
		sendBeacon: (url, blob) => {
			beacons.push({ url, blob });
			return true;
		}
	},
	console,
	Blob: class {
		constructor(parts, opts) {
			this.parts = parts;
			this.type = opts && opts.type;
		}
	}
};
sandbox.window = sandbox;
sandbox.document.cookie = '';
Object.defineProperty(sandbox.document, 'cookie', {
	get() {
		return Object.keys(cookies).map((k) => k + '=' + cookies[k]).join('; ');
	},
	set(v) {
		const [pair] = String(v).split(';');
		const i = pair.indexOf('=');
		cookies[pair.slice(0, i)] = decodeURIComponent(pair.slice(i + 1));
	}
});
sandbox.window.location = {
	href: 'https://example.test/?utm_source=ig',
	search: '?utm_source=ig',
	host: 'example.test'
};
sandbox.window.sessionStorage = {
	getItem: (k) => store[k] || null,
	setItem: (k, v) => { store[k] = String(v); }
};
sandbox.window.MocoriInsightsConfig = {
	endpoint: 'https://example.test/wp-json/mocori-insights/v1/collect',
	token: 'test-token',
	consent: { requireConsent: false, cookieless: false, cookieVisitor: 'mocori_insights_visitor', cookieSession: 'mocori_insights_session', sessionMinutes: 30 }
};
sandbox.window.crypto = { randomUUID: () => '11111111-1111-4111-8111-111111111111' };
sandbox.window.URLSearchParams = URLSearchParams;
sandbox.window.URL = URL;

const code = fs.readFileSync(path.join(__dirname, '../assets/js/tracker.js'), 'utf8');
vm.runInNewContext(code, sandbox);

assert(typeof sandbox.window.MocoriInsights.track === 'function', 'JS API track');
assert(typeof sandbox.window.MocoriInsights.consent === 'function', 'JS API consent');
assert(typeof sandbox.window.MocoriInsights.convert === 'function', 'JS API convert');
assert(cookies.mocori_insights_visitor, 'visitor cookie set');
assert(cookies.mocori_insights_session, 'session cookie set');
assert(beacons.length >= 1, 'page_view sent via sendBeacon');

sandbox.window.MocoriInsights.consent(false);
const n = beacons.length;
sandbox.window.MocoriInsights.track('page_view', {});
assert(beacons.length === n, 'consent(false) stops tracking');

sandbox.window.MocoriInsights.consent(true);
sandbox.window.MocoriInsights.track('form_submit', { form_id: 'contact-form', email: 'x' });
assert(beacons.length === n + 1, 'consent(true) resumes tracking');

assert(!/casa.?kotti|ck_/i.test(code), 'tracker has no client identifiers');

console.log('\n' + (fails ? fails + ' failed' : 'js ok'));
process.exit(fails ? 1 : 0);
