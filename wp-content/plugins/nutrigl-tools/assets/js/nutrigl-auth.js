/* NutriGL Tools — Auth + Fingerprint + Modal (vanilla JS). */
(function () {
	'use strict';

	var CFG = window.NUTRIGL_CFG || {};
	var REST = CFG.rest || '/wp-json/nutrigl/v1/';

	// --------------------------------------------------------------
	// Fingerprint: stable per browser+device. Combined server-side
	// with the client IP, so wiping localStorage alone does NOT
	// give you a new quota bucket.
	// --------------------------------------------------------------
	function randomId() {
		if (window.crypto && crypto.getRandomValues) {
			var a = new Uint8Array(16);
			crypto.getRandomValues(a);
			return Array.prototype.map.call(a, function (b) {
				return b.toString(16).padStart(2, '0');
			}).join('');
		}
		return String(Math.random()).slice(2) + String(Date.now());
	}

	function stableId() {
		var k = 'nutrigl_fp_id';
		var v = null;
		try { v = localStorage.getItem(k); } catch (e) {}
		if (!v) {
			v = randomId();
			try { localStorage.setItem(k, v); } catch (e) {}
		}
		return v;
	}

	function canvasHash() {
		try {
			var c = document.createElement('canvas');
			c.width = 220; c.height = 40;
			var ctx = c.getContext('2d');
			ctx.textBaseline = 'top';
			ctx.font = '14px "Arial"';
			ctx.fillStyle = '#f60';
			ctx.fillRect(0, 0, 220, 40);
			ctx.fillStyle = '#069';
			ctx.fillText('nutriglinsight-fp', 2, 15);
			ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
			ctx.fillText('nutriglinsight-fp', 4, 17);
			return c.toDataURL();
		} catch (e) { return 'x'; }
	}

	function shortHash(str) {
		// FNV-like — good enough as one component of the composite key.
		var h = 2166136261;
		for (var i = 0; i < str.length; i++) {
			h ^= str.charCodeAt(i);
			h = (h + ((h << 1) + (h << 4) + (h << 7) + (h << 8) + (h << 24))) >>> 0;
		}
		return h.toString(16);
	}

	function fingerprint() {
		var parts = [
			stableId(),
			navigator.userAgent || '',
			navigator.language || '',
			(navigator.languages || []).join(','),
			screen.width + 'x' + screen.height + 'x' + (screen.colorDepth || ''),
			new Date().getTimezoneOffset(),
			navigator.hardwareConcurrency || '',
			navigator.platform || '',
			canvasHash()
		].join('|');
		// Return hex string, ~32 chars.
		return shortHash(parts) + shortHash(parts.split('').reverse().join(''));
	}

	var FP = fingerprint();

	// --------------------------------------------------------------
	// Small REST helper.
	// --------------------------------------------------------------
	function api(path, opts) {
		opts = opts || {};
		var init = {
			method: opts.method || 'GET',
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json' }
		};
		if (opts.body) {
			init.headers['Content-Type'] = 'application/json';
			init.body = JSON.stringify(opts.body);
		}
		return fetch(REST + path, init).then(function (r) {
			return r.json().then(function (j) {
				return { status: r.status, ok: r.ok, body: j };
			});
		});
	}

	// --------------------------------------------------------------
	// State + subscribers.
	// --------------------------------------------------------------
	var state = { user: null, quota: null };
	var subs  = [];

	function set(next) {
		state = Object.assign({}, state, next);
		subs.forEach(function (fn) { try { fn(state); } catch (e) {} });
	}

	function subscribe(fn) { subs.push(fn); fn(state); }

	function refresh() {
		return api('me?fingerprint=' + encodeURIComponent(FP)).then(function (r) {
			set({ user: r.body.user || null, quota: r.body.quota || null });
			return state;
		});
	}

	// --------------------------------------------------------------
	// Modal UI.
	// --------------------------------------------------------------
	var modal = null;

	function openModal(tab) {
		if (!modal) return;
		showTab(tab || 'signup');
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('nutrigl-modal-open');
		var first = modal.querySelector('input[name="email"]');
		if (first) setTimeout(function(){ first.focus(); }, 30);
	}

	function closeModal() {
		if (!modal) return;
		modal.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('nutrigl-modal-open');
	}

	function showTab(tab) {
		modal.querySelectorAll('.nutrigl-tab').forEach(function (b) {
			b.classList.toggle('is-active', b.getAttribute('data-tab') === tab);
		});
		modal.querySelectorAll('.nutrigl-form').forEach(function (f) {
			f.style.display = (f.getAttribute('data-form') === tab) ? '' : 'none';
			var msg = f.querySelector('.nutrigl-form__msg');
			if (msg) msg.textContent = '';
		});
		var titles = {
			signup: 'Get 2 more free checks',
			login: 'Welcome back'
		};
		var subs2 = {
			signup: '1 free daily calculation · sign up and get 3. No spam, ever.',
			login: 'Log in to unlock your 3 daily calculations.'
		};
		var t = modal.querySelector('.nutrigl-modal__title');
		var s = modal.querySelector('.nutrigl-modal__sub');
		if (t) t.textContent = titles[tab];
		if (s) s.textContent = subs2[tab];
	}

	function bindForm(kind) {
		var form = modal.querySelector('form[data-form="' + kind + '"]');
		if (!form) return;
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var btn = form.querySelector('button[type="submit"]');
			var msg = form.querySelector('.nutrigl-form__msg');
			var email = form.querySelector('input[name="email"]').value.trim();
			var pass  = form.querySelector('input[name="password"]').value;
			msg.textContent = '';
			msg.className   = 'nutrigl-form__msg';
			btn.disabled = true;
			var origLabel = btn.textContent;
			btn.textContent = kind === 'signup' ? 'Creating…' : 'Logging in…';

			api(kind, {
				method: 'POST',
				body: { email: email, password: pass, fingerprint: FP }
			}).then(function (r) {
				btn.disabled = false;
				btn.textContent = origLabel;
				if (!r.ok) {
					msg.className = 'nutrigl-form__msg is-err';
					msg.textContent = (r.body && r.body.error) || 'Something went wrong.';
					return;
				}
				set({ user: r.body.user, quota: r.body.quota });
				closeModal();
			}).catch(function () {
				btn.disabled = false;
				btn.textContent = origLabel;
				msg.className = 'nutrigl-form__msg is-err';
				msg.textContent = 'Network error. Try again.';
			});
		});
	}

	function initModal() {
		modal = document.getElementById('nutrigl-modal');
		if (!modal) return;
		modal.addEventListener('click', function (e) {
			var t = e.target;
			if (t.matches('[data-close]')) closeModal();
			if (t.matches('.nutrigl-tab')) showTab(t.getAttribute('data-tab'));
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') closeModal();
		});
		bindForm('signup');
		bindForm('login');
	}

	// --------------------------------------------------------------
	// Header account widget.
	// --------------------------------------------------------------
	function renderAccountBadge() {
		var el = document.getElementById('nutrigl-account');
		if (!el) return;

		function paint(st) {
			if (st.user) {
				el.innerHTML =
					'<span class="nutrigl-badge__user" title="' + st.user.email + '">' +
					'<span class="nutrigl-badge__dot"></span>' + st.user.email.split('@')[0] +
					'</span>' +
					'<button type="button" class="nutrigl-badge__btn" data-nutrigl-logout>Log out</button>';
			} else {
				el.innerHTML =
					'<button type="button" class="nutrigl-badge__btn nutrigl-badge__btn--ghost" data-nutrigl-open="login">Log in</button>' +
					'<button type="button" class="nutrigl-badge__btn" data-nutrigl-open="signup">Sign up free</button>';
			}
		}
		subscribe(paint);
	}

	// --------------------------------------------------------------
	// Global click handlers.
	// --------------------------------------------------------------
	function bindTriggers() {
		document.addEventListener('click', function (e) {
			var t = e.target.closest('[data-nutrigl-open]');
			if (t) {
				e.preventDefault();
				openModal(t.getAttribute('data-nutrigl-open') || 'signup');
				return;
			}
			var l = e.target.closest('[data-nutrigl-logout]');
			if (l) {
				e.preventDefault();
				api('logout', { method: 'POST', body: { fingerprint: FP } }).then(function (r) {
					if (r.body && r.body.quota) {
						set({ user: null, quota: r.body.quota });
					} else {
						set({ user: null });
					}
				});
			}
		});
	}

	// --------------------------------------------------------------
	// Public API.
	// --------------------------------------------------------------
	window.NutriGLAuth = {
		fingerprint: function () { return FP; },
		api: api,
		subscribe: subscribe,
		refresh: refresh,
		open: openModal,
		close: closeModal,
		state: function () { return state; },
		set: set
	};

	document.addEventListener('DOMContentLoaded', function () {
		initModal();
		renderAccountBadge();
		bindTriggers();
		refresh();
	});
})();
