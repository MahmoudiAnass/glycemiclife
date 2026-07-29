/* NutriGL Tools — Calculator v2 (calls WP proxy → Heroku API). */
(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') fn();
		else document.addEventListener('DOMContentLoaded', fn);
	}

	function tierFromCategory(cat) {
		var c = (cat || '').toLowerCase();
		if (c === 'low') return { cls: 'low', label: 'Low GL', tip: 'Minimal blood-sugar impact — a solid choice.' };
		if (c === 'high') return { cls: 'high', label: 'High GL', tip: 'Big spike expected. Cut the portion or add fiber, protein, or fat.' };
		return { cls: 'med', label: 'Medium GL', tip: 'Moderate impact — fine paired with protein or fiber.' };
	}

	ready(function () {
		var $wrap  = document.getElementById('gl-calculator');
		if (!$wrap) return;

		var $food  = document.getElementById('glc-food');
		var $grams = document.getElementById('glc-grams');
		var $btn   = document.getElementById('glc-run');
		var $outGI = document.getElementById('glc-out-gi');
		var $outC  = document.getElementById('glc-out-carbs');
		var $outGL = document.getElementById('glc-out-gl');
		var $hint  = document.getElementById('glc-hint');

		var $qBar  = document.getElementById('glc-quota-fill');
		var $qTxt  = document.getElementById('glc-quota-text');
		var $qCTA  = document.getElementById('glc-quota-cta');

		if (!$food || !$grams || !$btn) return;

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
			$outGI.textContent = r.gi == null ? '—' : Math.round(r.gi);
			$outC.textContent  = r.carbs_for_serving == null ? '—' : (Math.round(r.carbs_for_serving * 10) / 10) + ' g';
			$outGL.textContent = r.glycemic_load == null ? '—' : (Math.round(r.glycemic_load * 10) / 10);
			var tier = tierFromCategory(r.category);
			$outGL.className = 'gl-result__value gl-result__value--' + tier.cls;
			$hint.textContent = tier.label + ' — ' + tier.tip + '   Formula: GL = GI × carbs ÷ 100.';
		}

		function paintError(msg) {
			$outGI.textContent = '—';
			$outC.textContent  = '—';
			$outGL.textContent = '—';
			$outGL.className   = 'gl-result__value';
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

			var origLabel = $btn.textContent;
			$btn.disabled = true;
			$btn.textContent = 'Calculating…';
			$hint.textContent = 'Calculating…';

			window.NutriGLAuth.api('gl-check', {
				method: 'POST',
				body: { food: food, grams: g, fingerprint: window.NutriGLAuth.fingerprint() }
			}).then(function (r) {
				$btn.textContent = origLabel;
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
				$btn.textContent = origLabel;
				paintError('Network error. Try again.');
			});
		}

		$btn.addEventListener('click', calc);
		$food.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); calc(); } });
		$grams.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); calc(); } });

		if (window.NutriGLAuth) {
			window.NutriGLAuth.subscribe(paintQuota);
		} else {
			setTimeout(function () {
				if (window.NutriGLAuth) window.NutriGLAuth.subscribe(paintQuota);
			}, 250);
		}
	});
})();
