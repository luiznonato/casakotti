(function () {
	'use strict';

	var cfg = window.MocoriInsightsAdmin || {};
	document.documentElement.style.setProperty('--mi-accent', cfg.accent || '#1f3d34');

	var canvas = document.getElementById('mi-chart');
	if (canvas && canvas.getContext) {
		var series = [];
		try {
			series = JSON.parse(canvas.getAttribute('data-series') || '[]');
		} catch (e) {
			series = [];
		}
		var ctx = canvas.getContext('2d');
		var w = canvas.width = canvas.clientWidth || 640;
		var h = canvas.height;
		ctx.clearRect(0, 0, w, h);
		if (series.length) {
			var max = 1;
			series.forEach(function (row) {
				max = Math.max(max, Number(row.visitors) || 0);
			});
			ctx.beginPath();
			series.forEach(function (row, i) {
				var x = series.length === 1 ? w / 2 : (i / (series.length - 1)) * (w - 16) + 8;
				var y = h - 12 - ((Number(row.visitors) || 0) / max) * (h - 28);
				if (i === 0) {
					ctx.moveTo(x, y);
				} else {
					ctx.lineTo(x, y);
				}
			});
			ctx.strokeStyle = cfg.accent || '#1f3d34';
			ctx.lineWidth = 2;
			ctx.stroke();
		}
	}

	function refreshLive() {
		if (!cfg.realtime) {
			return;
		}
		fetch(cfg.realtime, { credentials: 'same-origin', headers: { 'X-WP-Nonce': cfg.nonce || '' } })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				var active = document.getElementById('mi-active');
				if (active && data && typeof data.active !== 'undefined') {
					active.textContent = data.active;
				}
				var body = document.querySelector('#mi-live-pages tbody');
				if (body && data && data.pages) {
					body.innerHTML = data.pages.length ? data.pages.map(function (row) {
						return '<tr><td>' + String(row.page_url || '').replace(/</g, '') + '</td><td>' + String(row.visitors) + '</td></tr>';
					}).join('') : '<tr><td colspan="2">Nenhum visitante ativo nos últimos 5 minutos.</td></tr>';
				}
			})
			.catch(function () {});
	}

	if (document.getElementById('mi-active')) {
		setInterval(refreshLive, 30000);
	}
}());
