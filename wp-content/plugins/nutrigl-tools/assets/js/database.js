/* NutriGL Tools — Database (vanilla JS, filter + sort). */
(function () {
	'use strict';

	var $search = document.getElementById('gldb-search');
	var $cat    = document.getElementById('gldb-cat');
	var $body   = document.getElementById('gldb-body');
	var $stats  = document.getElementById('gldb-stats');
	var $ths    = document.querySelectorAll('.gl-db__table thead th[data-sort]');
	if (!$body) return;

	var rows = Array.prototype.slice.call($body.querySelectorAll('tr'));
	var totalCount = rows.length;
	var sortState = { key: 'name', dir: 'asc' };

	function apply() {
		var q  = ($search && $search.value || '').trim().toLowerCase();
		var c  = ($cat && $cat.value || '');
		var shown = 0;

		rows.forEach(function (r) {
			var okName = !q || r.dataset.name.indexOf(q) !== -1;
			var okCat  = !c || r.dataset.cat === c;
			if (okName && okCat) {
				r.style.display = '';
				shown++;
			} else {
				r.style.display = 'none';
			}
		});

		if ($stats) {
			$stats.textContent = shown === totalCount
				? totalCount + ' foods listed.'
				: shown + ' of ' + totalCount + ' foods match.';
		}
	}

	function sortBy(key) {
		if (sortState.key === key) {
			sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
		} else {
			sortState.key = key;
			sortState.dir = (key === 'gi' || key === 'gl') ? 'desc' : 'asc';
		}
		var dir = sortState.dir === 'asc' ? 1 : -1;

		rows.sort(function (a, b) {
			var va, vb;
			switch (key) {
				case 'gi': va = parseFloat(a.dataset.gi); vb = parseFloat(b.dataset.gi); break;
				case 'gl': va = parseFloat(a.dataset.gl); vb = parseFloat(b.dataset.gl); break;
				case 'cat': va = a.dataset.cat; vb = b.dataset.cat; break;
				case 'serving': va = parseFloat(a.dataset.servingG); vb = parseFloat(b.dataset.servingG); break;
				default: va = a.dataset.name; vb = b.dataset.name;
			}
			if (va < vb) return -1 * dir;
			if (va > vb) return  1 * dir;
			return 0;
		});

		rows.forEach(function (r) { $body.appendChild(r); });

		$ths.forEach(function (th) {
			th.setAttribute('aria-sort',
				th.dataset.sort === key
					? (sortState.dir === 'asc' ? 'ascending' : 'descending')
					: 'none'
			);
		});
	}

	if ($search) $search.addEventListener('input', apply);
	if ($cat)    $cat.addEventListener('change', apply);
	$ths.forEach(function (th) {
		th.addEventListener('click', function () { sortBy(th.dataset.sort); });
		th.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); sortBy(th.dataset.sort); }
		});
		th.setAttribute('tabindex', '0');
	});
})();
