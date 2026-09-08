/**
 * Fluidampr site JavaScript
 * Lightweight, dependency-free header, overlay, and compact finder behavior.
 */
(function () {
	'use strict';

	var settings = window.fluidamprTheme || {};

	function onReady(fn) {
		if ('loading' !== document.readyState) {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	function qs(sel, ctx) {
		return (ctx || document).querySelector(sel);
	}

	function qsa(sel, ctx) {
		return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
	}

	function toggleOverlay(el, open) {
		if (!el) {
			return;
		}

		if (open) {
			el.hidden = false;
			document.body.classList.add('fluid-overlay-open');
		} else {
			el.hidden = true;
			if (!qs('.fluid-nav:not([hidden]), .fluid-search:not([hidden])')) {
				document.body.classList.remove('fluid-overlay-open');
			}
		}
	}

	function bindOverlay(openSel, closeSel, panelSel, focusSel) {
		var panel = qs(panelSel);

		qsa(openSel).forEach(function (btn) {
			btn.addEventListener('click', function () {
				toggleOverlay(panel, true);
				btn.setAttribute('aria-expanded', 'true');
				var focusEl = focusSel ? qs(focusSel, panel) : qs(closeSel, panel);
				if (focusEl) {
					focusEl.focus();
				}
			});
		});

		qsa(closeSel).forEach(function (btn) {
			btn.addEventListener('click', function () {
				toggleOverlay(panel, false);
				qsa(openSel).forEach(function (openBtn) {
					openBtn.setAttribute('aria-expanded', 'false');
				});
			});
		});

		if (panel) {
			panel.addEventListener('click', function (event) {
				if (event.target === panel) {
					toggleOverlay(panel, false);
				}
			});
		}
	}

	function bindFinderTabs(root) {
		qsa('[data-finder-tab]', root).forEach(function (tab) {
			tab.addEventListener('click', function () {
				var name = tab.getAttribute('data-finder-tab');

				qsa('[data-finder-tab]', root).forEach(function (other) {
					var active = other === tab;
					other.classList.toggle('is-active', active);
					other.setAttribute('aria-selected', active ? 'true' : 'false');
				});

				qsa('[data-finder-panel]', root).forEach(function (panel) {
					var active = panel.getAttribute('data-finder-panel') === name;
					panel.classList.toggle('is-active', active);
					panel.hidden = !active;
				});
			});
		});
	}

	function fillSelect(select, items, placeholder) {
		if (!select) {
			return;
		}

		var current = select.value;
		select.innerHTML = '';
		var first = document.createElement('option');
		first.value = '';
		first.textContent = placeholder;
		select.appendChild(first);

		(items || []).forEach(function (item) {
			var option = document.createElement('option');
			option.value = item;
			option.textContent = item;
			select.appendChild(option);
		});

		select.disabled = !items || !items.length;
		if (current && items && items.indexOf(current) !== -1) {
			select.value = current;
		}
	}

	function getJson(url) {
		return fetch(url, { credentials: 'same-origin' }).then(function (res) {
			if (!res.ok) {
				throw new Error('Finder request failed');
			}
			return res.json();
		});
	}

	function restUrl(path, params) {
		var base = (settings.finderRest || '').replace(/\/$/, '');
		var url = base + '/' + path.replace(/^\//, '');
		var query = [];

		Object.keys(params || {}).forEach(function (key) {
			if (params[key]) {
				query.push(encodeURIComponent(key) + '=' + encodeURIComponent(params[key]));
			}
		});

		return query.length ? url + '?' + query.join('&') : url;
	}

	function bindCompactFinder(root) {
		if (!settings.finderRest) {
			return;
		}

		var year = qs('[data-finder-field="year"]', root);
		var make = qs('[data-finder-field="make"]', root);
		var model = qs('[data-finder-field="model"]', root);
		var submodel = qs('[data-finder-field="submodel"]', root);

		if (!year) {
			return;
		}

		getJson(restUrl('years')).then(function (payload) {
			var years = (payload && payload.data) || payload || [];
			fillSelect(year, years, 'Year');
		}).catch(function () {
			/* Plugin REST is optional on marketing pages. */
		});

		year.addEventListener('change', function () {
			fillSelect(make, [], 'Make');
			fillSelect(model, [], 'Model');
			fillSelect(submodel, [], 'Submodel');
			if (!year.value) {
				return;
			}
			getJson(restUrl('makes', { year: year.value })).then(function (payload) {
				fillSelect(make, (payload && payload.data) || [], 'Make');
			});
		});

		make.addEventListener('change', function () {
			fillSelect(model, [], 'Model');
			fillSelect(submodel, [], 'Submodel');
			if (!make.value) {
				return;
			}
			getJson(restUrl('models', { year: year.value, make: make.value })).then(function (payload) {
				fillSelect(model, (payload && payload.data) || [], 'Model');
			});
		});

		model.addEventListener('change', function () {
			fillSelect(submodel, [], 'Submodel');
			if (!model.value) {
				return;
			}
			getJson(restUrl('submodels', { year: year.value, make: make.value, model: model.value })).then(function (payload) {
				fillSelect(submodel, (payload && payload.data) || [], 'Submodel');
			});
		});
	}

	onReady(function () {
		bindOverlay('[data-fluid-nav-open]', '[data-fluid-nav-close]', '#fluid-nav');
		bindOverlay('[data-fluid-search-open]', '[data-fluid-search-close]', '#fluid-search', '#fluid-search-field');

		document.addEventListener('keydown', function (event) {
			if ('Escape' === event.key) {
				toggleOverlay(qs('#fluid-nav'), false);
				toggleOverlay(qs('#fluid-search'), false);
			}
		});

		qsa('[data-fluid-finder]').forEach(function (root) {
			bindFinderTabs(root);
			if (root.getAttribute('data-mode') !== 'full') {
				bindCompactFinder(root);
			}
		});
	});
})();
