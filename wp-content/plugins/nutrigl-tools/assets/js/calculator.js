/* NutriGL Tools — Calculator v3 (quick chips + SVG gauge, calls WP proxy). */
(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') fn();
		else document.addEventListener('DOMContentLoaded', fn);
	}

	var GAUGE_CIRCUMFERENCE = 326.7256; // 2 * PI * 52
	var GAUGE_MAX_GL = 40; // GL value that fills the ring 100%.

	var TIERS = {
		low:  { color: '#86efac', label: 'Low GL',    tip: 'Minimal blood-sugar impact — a solid choice.' },
		med:  { color: '#fcd34d', label: 'Medium GL', tip: 'Moderate impact — fine paired with protein or fiber.' },
		high: { color: '#fca5a5', label: 'High GL',   tip: 'Big spike expected. Cut the portion or add fiber, protein, or fat.' }
	};

	function tierFromCategory(cat) {
		var c = (cat || '').toLowerCase();
		if (c === 'low') return TIERS.low;
		if (c === 'high') return TIERS.high;
		return TIERS.med;
	}

	ready(function () {
		var $wrap  = document.getElementById('gl-calculator');
		if (!$wrap) return;

		var $food     = document.getElementById('glc-food');
		var $grams    = document.getElementById('glc-grams');
		var $btn      = document.getElementById('glc-run');
		var $btnLabel = document.getElementById('glc-run-label');
		var $outGI    = document.getElementById('glc-out-gi');
		var $outC     = document.getElementById('glc-out-carbs');
		var $outGL    = document.getElementById('glc-out-gl');
		var $gaugeFill = document.getElementById('glc-gauge-fill');
		var $hint     = document.getElementById('glc-hint');

		var $qBar  = document.getElementById('glc-quota-fill');
		var $qTxt  = document.getElementById('glc-quota-text');
		var $qCTA  = document.getElementById('glc-quota-cta');

		if (!$food || !$grams || !$btn) return;

		function setGauge(gl, color) {
			var pct = gl == null ? 0 : Math.max(0, Math.min(1, gl / GAUGE_MAX_GL));
			var offset = GAUGE_CIRCUMFERENCE * (1 - pct);
			$gaugeFill.style.strokeDashoffset = String(offset);
			$gaugeFill.style.stroke = color || '#86efac';
		}

		function paintQuota(state) {
			var q    = state && state.quota;
			var user = state && state.user;
			if (!q) {
				$qTxt.textContent = 'Checking your daily allowance…';
				return;
			}
			var pct = q.limit ? Math.max(0, Math.min(100, (q.remaining / q.limit) * 100)) : 0;
			$qBar.style.width = pct + '%';
			$qBar.className = 'gl-quota__fill' + (q.remaining === 0 ? ' is-empty' : (q.remaining === 1 ? ' is-low' : ''));

			if (user) {
				$qTxt.innerHTML = '<strong>' + q.remaining + '</strong> of ' + q.limit + ' checks left today · signed in as ' + user.email;
				$qCTA.innerHTML = q.remaining === 0
					? '<span class="gl-quota__note">Daily limit reached. Come back tomorrow.</span>'
					: '';
			} else {
				$qTxt.innerHTML = '<strong>' + q.remaining + '</strong> of ' + q.limit + ' free check left today';
				$qCTA.innerHTML = q.remaining === 0
					? '<button type="button" class="btn btn--primary btn--sm" data-nutrigl-open="signup">Sign up free for 2 more</button>'
					: '<button type="button" class="btn btn--ghost btn--sm" data-nutrigl-open="signup">Sign up free · get 2 more per day</button>';
			}

			$btn.disabled = q.remaining === 0;
		}

		function paintResult(r) {
			var tier = tierFromCategory(r.category);
			$outGI.textContent = r.gi == null ? '—' : Math.round(r.gi);
			$outC.textContent  = r.carbs_for_serving == null ? '—' : (Math.round(r.carbs_for_serving * 10) / 10) + ' g';
			var gl = r.glycemic_load == null ? null : Math.round(r.glycemic_load * 10) / 10;
			$outGL.textContent = gl == null ? '—' : gl;
			setGauge(gl, tier.color);
			$hint.textContent = tier.label + ' — ' + tier.tip + '  Formula: GL = GI × carbs ÷ 100.';
		}

		function paintError(msg) {
			$outGI.textContent = '—';
			$outC.textContent  = '—';
			$outGL.textContent = '—';
			setGauge(null, '#86efac');
			$hint.textContent  = msg;
		}

		function calc() {
			if (!window.NutriGLAuth) return;
			var food = ($food.value || '').trim();
			var g    = parseFloat($grams.value);

			if (food === '') {
				paintError('Type a food name to calculate its glycemic load.');
				$food.focus();
				return;
			}
			if (isNaN(g) || g < 1 || g > 2000) {
				paintError('Enter a serving between 1 and 2000 grams.');
				$grams.focus();
				return;
			}

			$btn.disabled = true;
			$btnLabel.textContent = 'Calculating…';
			$hint.textContent = 'Calculating…';

			window.NutriGLAuth.api('gl-check', {
				method: 'POST',
				body: { food: food, grams: g, fingerprint: window.NutriGLAuth.fingerprint() }
			}).then(function (r) {
				$btnLabel.textContent = 'Calculate';
				if (!r.ok) {
					var q = r.body && r.body.quota;
					if (q) window.NutriGLAuth.set({ quota: q });
					var msg = (r.body && r.body.error) || 'Could not calculate. Try again.';
					paintError(msg);
					if (r.status === 429 && r.body && r.body.code === 'quota_daily_anon') {
						window.NutriGLAuth.open('signup');
					}
					return;
				}
				if (r.body.quota) window.NutriGLAuth.set({ quota: r.body.quota });
				if (r.body.user)  window.NutriGLAuth.set({ user: r.body.user });
				paintResult(r.body.result);
			}).catch(function () {
				$btn.disabled = false;
				$btnLabel.textContent = 'Calculate';
				paintError('Network error. Try again.');
			});
		}

		$btn.addEventListener('click', calc);
		$food.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); calc(); } });
		$grams.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); calc(); } });

		$wrap.querySelectorAll('.gl-chip').forEach(function (chip) {
			chip.addEventListener('click', function () {
				$food.value = chip.getAttribute('data-food') || '';
				calc();
			});
		});

		if (window.NutriGLAuth) {
			window.NutriGLAuth.subscribe(paintQuota);
		} else {
			setTimeout(function () {
				if (window.NutriGLAuth) window.NutriGLAuth.subscribe(paintQuota);
			}, 250);
		}
	});
})();
