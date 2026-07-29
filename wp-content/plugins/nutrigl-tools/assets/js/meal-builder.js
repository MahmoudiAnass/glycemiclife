/* NutriGL Tools — Meal Builder (members-only, local dataset, vanilla JS). */
(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') fn();
		else document.addEventListener('DOMContentLoaded', fn);
	}

	var GAUGE_CIRCUMFERENCE = 326.7256; // 2 * PI * 52
	var GAUGE_MAX_GL = 60; // meal-level scale (whole meals run higher than single foods)

	var TIERS = {
		low:  { color: '#86efac', label: 'Low' },
		med:  { color: '#fcd34d', label: 'Medium' },
		high: { color: '#fca5a5', label: 'High' }
	};

	function mealTier(gl) {
		if (gl < 20) return 'low';
		if (gl < 40) return 'med';
		return 'high';
	}

	function escapeHtml(str) {
		return String(str).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}

	ready(function () {
		var $root = document.getElementById('meal-builder');
		if (!$root) return;

		var foods = window.NUTRIGL_FOODS || [];
		var byName = {};
		foods.forEach(function (f) { byName[String(f.name).toLowerCase()] = f; });

		var $gate = document.getElementById('meal-gate');
		var $app  = document.getElementById('meal-app');

		var $food  = document.getElementById('meal-food');
		var $grams = document.getElementById('meal-grams');
		var $add   = document.getElementById('meal-add');
		var $msg   = document.getElementById('meal-msg');
		var $list  = document.getElementById('meal-items');

		var $gaugeFill  = document.getElementById('meal-gauge-fill');
		var $totalGL    = document.getElementById('meal-total-gl');
		var $totalItems = document.getElementById('meal-total-items');
		var $totalCarbs = document.getElementById('meal-total-carbs');

		var $name = document.getElementById('meal-name');
		var $save = document.getElementById('meal-save');

		var $savedList = document.getElementById('meal-saved-list');

		var items = []; // { food, grams, gi, carbs, gl }

		function setGauge(gl) {
			var pct = Math.max(0, Math.min(1, gl / GAUGE_MAX_GL));
			var offset = GAUGE_CIRCUMFERENCE * (1 - pct);
			$gaugeFill.style.strokeDashoffset = String(offset);
			$gaugeFill.style.stroke = TIERS[mealTier(gl)].color;
		}

		function renderItems() {
			$list.innerHTML = '';
			items.forEach(function (it, idx) {
				var li = document.createElement('li');
				li.className = 'meal-item';
				li.innerHTML =
					'<span class="meal-item__name">' + escapeHtml(it.food) + '</span>' +
					'<span class="meal-item__grams">' + it.grams + ' g</span>' +
					'<span class="meal-item__gl">GL ' + (Math.round(it.gl * 10) / 10) + '</span>' +
					'<button type="button" class="meal-item__remove" data-idx="' + idx + '" aria-label="Remove">&times;</button>';
				$list.appendChild(li);
			});

			var totalGL = 0, totalCarbs = 0;
			items.forEach(function (it) { totalGL += it.gl; totalCarbs += it.carbs; });
			totalGL = Math.round(totalGL * 10) / 10;
			totalCarbs = Math.round(totalCarbs * 10) / 10;

			$totalGL.textContent = totalGL;
			$totalItems.textContent = items.length;
			$totalCarbs.textContent = totalCarbs + ' g';
			setGauge(totalGL);
		}

		function addItem() {
			var name = ($food.value || '').trim();
			var grams = parseFloat($grams.value);
			$msg.textContent = '';

			if (!name) { $msg.textContent = 'Pick a food from the list first.'; $food.focus(); return; }
			var food = byName[name.toLowerCase()];
			if (!food) { $msg.textContent = 'Unknown food — choose one from the suggestions.'; $food.focus(); return; }
			if (isNaN(grams) || grams < 1 || grams > 2000) { $msg.textContent = 'Enter grams between 1 and 2000.'; $grams.focus(); return; }
			if (items.length >= 20) { $msg.textContent = 'A meal can have at most 20 items.'; return; }

			var carbs = (parseFloat(food.carbs_per_100g) * grams) / 100;
			var gl    = (parseFloat(food.gi) * carbs) / 100;

			items.push({ food: food.name, grams: grams, gi: parseFloat(food.gi), carbs: carbs, gl: gl });
			renderItems();

			$food.value = '';
			$food.focus();
		}

		$add.addEventListener('click', addItem);
		$food.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); addItem(); } });
		$grams.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); addItem(); } });

		$list.addEventListener('click', function (e) {
			var btn = e.target.closest('.meal-item__remove');
			if (!btn) return;
			var idx = parseInt(btn.getAttribute('data-idx'), 10);
			items.splice(idx, 1);
			renderItems();
		});

		function renderSavedMeals(meals) {
			if (!meals || !meals.length) {
				$savedList.innerHTML = '<p class="meal-saved__empty">No saved meals yet — build one above.</p>';
				return;
			}
			$savedList.innerHTML = meals.map(function (m) {
				var tierLabel = TIERS[m.tier] ? TIERS[m.tier].label : 'Medium';
				return '' +
					'<div class="meal-card" data-id="' + m.id + '">' +
						'<div class="meal-card__head">' +
							'<h4>' + escapeHtml(m.name) + '</h4>' +
							'<span class="meal-card__pill meal-card__pill--' + m.tier + '">' + tierLabel + ' GL</span>' +
						'</div>' +
						'<p class="meal-card__meta">' + m.item_count + ' item' + (m.item_count === 1 ? '' : 's') + ' &middot; Total GL ' + m.total_gl + ' &middot; ' + m.total_carbs + 'g carbs</p>' +
						'<p class="meal-card__items">' + m.items.map(function (it) { return escapeHtml(it.food) + ' (' + it.grams + 'g)'; }).join(', ') + '</p>' +
						'<button type="button" class="meal-card__delete" data-id="' + m.id + '">Delete</button>' +
					'</div>';
			}).join('');
		}

		function loadSavedMeals() {
			if (!window.NutriGLAuth) return;
			window.NutriGLAuth.api('meals').then(function (r) {
				if (r.ok && r.body && r.body.meals) renderSavedMeals(r.body.meals);
			});
		}

		$savedList.addEventListener('click', function (e) {
			var btn = e.target.closest('.meal-card__delete');
			if (!btn || !window.NutriGLAuth) return;
			var id = btn.getAttribute('data-id');
			btn.disabled = true;
			btn.textContent = 'Deleting…';
			window.NutriGLAuth.api('meals/' + id, { method: 'DELETE' }).then(function () {
				loadSavedMeals();
			});
		});

		$save.addEventListener('click', function () {
			if (!window.NutriGLAuth) return;
			if (!items.length) { $msg.textContent = 'Add at least one food before saving.'; return; }

			var name = ($name.value || '').trim() || 'My meal';
			var origLabel = $save.textContent;
			$save.disabled = true;
			$save.textContent = 'Saving…';

			window.NutriGLAuth.api('meals', {
				method: 'POST',
				body: {
					name: name,
					items: items.map(function (it) { return { food: it.food, grams: it.grams }; })
				}
			}).then(function (r) {
				$save.disabled = false;
				$save.textContent = origLabel;
				if (!r.ok) {
					$msg.textContent = (r.body && r.body.error) || 'Could not save the meal.';
					return;
				}
				items = [];
				renderItems();
				$name.value = '';
				$msg.textContent = 'Meal saved!';
				loadSavedMeals();
			}).catch(function () {
				$save.disabled = false;
				$save.textContent = origLabel;
				$msg.textContent = 'Network error. Try again.';
			});
		});

		renderItems();

		function paintGate(state) {
			var loggedIn = !!(state && state.user);
			$gate.hidden = loggedIn;
			$app.hidden  = !loggedIn;
			if (loggedIn) loadSavedMeals();
		}

		if (window.NutriGLAuth) {
			window.NutriGLAuth.subscribe(paintGate);
		} else {
			setTimeout(function () {
				if (window.NutriGLAuth) window.NutriGLAuth.subscribe(paintGate);
			}, 250);
		}
	});
})();
