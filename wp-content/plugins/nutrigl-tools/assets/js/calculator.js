/* NutriGL Tools — Calculator (vanilla JS). */
(function () {
	'use strict';

	var foods = window.NUTRIGL_FOODS || [];
	if ( !foods.length ) return;

	var $food  = document.getElementById('glc-food');
	var $grams = document.getElementById('glc-grams');
	var $outGI = document.getElementById('glc-out-gi');
	var $outC  = document.getElementById('glc-out-carbs');
	var $outGL = document.getElementById('glc-out-gl');
	var $hint  = document.getElementById('glc-hint');

	if ( !$food || !$grams ) return;

	function classify(gl) {
		if (gl < 10)  return { cls: 'low',  label: 'Low GL', tip: 'Minimal blood-sugar impact — a solid choice.' };
		if (gl < 20)  return { cls: 'med',  label: 'Medium GL', tip: 'Moderate impact — fine paired with protein or fiber.' };
		return { cls: 'high', label: 'High GL', tip: 'This will spike your blood sugar. Reduce portion or add fiber, protein, or fat.' };
	}

	function calc() {
		var idx = $food.value;
		var g = parseFloat($grams.value);
		if ( idx === '' || isNaN(g) || g <= 0 ) {
			$outGI.textContent = '—';
			$outC.textContent  = '—';
			$outGL.textContent = '—';
			$outGL.className   = 'gl-result__value';
			$hint.textContent  = 'Select a food and enter a serving size to see the result.';
			return;
		}
		var f = foods[parseInt(idx, 10)];
		if (!f) return;

		var gi    = parseFloat(f.gi);
		var carbs = (parseFloat(f.carbs_per_100g) * g) / 100;
		var gl    = (gi * carbs) / 100;

		$outGI.textContent = Math.round(gi);
		$outC.textContent  = carbs.toFixed(1) + ' g';
		$outGL.textContent = gl.toFixed(1);

		var c = classify(gl);
		$outGL.className = 'gl-result__value gl-result__value--' + c.cls;
		$hint.textContent = c.label + ' — ' + c.tip + '  Formula: GL = GI × carbs ÷ 100.';
	}

	$food.addEventListener('change', calc);
	$grams.addEventListener('input', calc);
	calc();
})();
