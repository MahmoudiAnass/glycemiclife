/* NutriGL Tools — Database (checkboxes + search + sort). */
(function () {
	'use strict';

	var $search = document.getElementById('gldb-search');
	var $sort   = document.getElementById('gldb-sort');
	var $grid   = document.getElementById('gldb-grid');
	var $stats  = document.getElementById('gldb-stats');
	var $empty  = document.getElementById('gldb-empty');
	var $clear  = document.getElementById('gldb-clear');
	var $filters = document.querySelectorAll('input[type="checkbox"][data-filter]');
	if (!$grid) return;

	var cards = Array.prototype.slice.call($grid.querySelectorAll('.food-card'));
	var total = cards.length;

	function activeSet(name) {
		var out = {};
		document.querySelectorAll('input[type="checkbox"][data-filter="' + name + '"]:checked').forEach(function (el) {
			out[el.value] = true;
		});
		return out;
	}

	function apply() {
		var q     = ($search && $search.value || '').trim().toLowerCase();
		var gis   = activeSet('gi');
		var gls   = activeSet('gl');
		var cats  = activeSet('cat');
		var anyGi = Object.keys(gis).length > 0;
		var anyGl = Object.keys(gls).length > 0;
		var anyC  = Object.keys(cats).length > 0;
		var shown = 0;

		cards.forEach(function (c) {
			var okName = !q || c.dataset.name.indexOf(q) !== -1;
			var okGi   = !anyGi || gis[c.dataset.giTier];
			var okGl   = !anyGl || gls[c.dataset.glTier];
			var okCat  = !anyC  || cats[c.dataset.cat];
			var show   = okName && okGi && okGl && okCat;
			c.style.display = show ? '' : 'none';
			if (show) shown++;
		});

		if ($stats) {
			$stats.textContent = shown === total
				? total + ' foods.'
				: 'Showing ' + shown + ' of ' + total + ' foods.';
		}
		if ($empty) $empty.style.display = shown === 0 ? '' : 'none';
	}

	function sort() {
		var val = $sort ? $sort.value : 'name-asc';
		var parts = val.split('-');
		var key = parts[0]; // name | gi | gl
		var dir = parts[1] === 'asc' ? 1 : -1;

		cards.sort(function (a, b) {
			var va, vb;
			if (key === 'gi') { va = parseFloat(a.dataset.gi); vb = parseFloat(b.dataset.gi); }
			else if (key === 'gl') { va = parseFloat(a.dataset.gl); vb = parseFloat(b.dataset.gl); }
			else { va = a.dataset.name; vb = b.dataset.name; }
			if (va < vb) return -1 * dir;
			if (va > vb) return  1 * dir;
			return 0;
		});
		cards.forEach(function (c) { $grid.appendChild(c); });
	}

	if ($search) $search.addEventListener('input', apply);
	if ($sort)   $sort.addEventListener('change', function () { sort(); apply(); });
	$filters.forEach(function (f) { f.addEventListener('change', apply); });
	if ($clear) {
		$clear.addEventListener('click', function () {
			$filters.forEach(function (f) { f.checked = false; });
			if ($search) $search.value = '';
			if ($sort)   $sort.value = 'name-asc';
			sort();
			apply();
		});
	}
})();
