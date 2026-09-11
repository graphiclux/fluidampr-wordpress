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

	function syncSelectValueClass(select) {
		if (!select) {
			return;
		}

		select.classList.toggle('has-value', !!select.value);
	}

	function bindSelectValueClass(select) {
		if (!select || select.dataset.fluidValueBound) {
			return;
		}

		select.dataset.fluidValueBound = '1';
		select.addEventListener('change', function () {
			syncSelectValueClass(select);
		});
		syncSelectValueClass(select);
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

		select.disabled = false;
		select.classList.toggle('is-waiting', !items || !items.length);
		select.setAttribute('aria-disabled', (!items || !items.length) ? 'true' : 'false');
		if (current && items && items.indexOf(current) !== -1) {
			select.value = current;
		}

		bindSelectValueClass(select);
		syncSelectValueClass(select);
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

		qsa('form[data-finder-form]', root).forEach(function (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var url = new URL(form.getAttribute('action'), window.location.origin);
				qsa('select, input', form).forEach(function (field) {
					if (field.name && field.value && !field.classList.contains('fluid-hp')) {
						url.searchParams.set(field.name, field.value);
					}
				});
				window.location.href = url.toString();
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
			qsa('select[data-finder-field]', root).forEach(function (select) {
				bindSelectValueClass(select);
				if (!select.value) {
					select.classList.add('is-waiting');
					select.setAttribute('aria-disabled', 'true');
				}
			});
			if (root.getAttribute('data-mode') !== 'full') {
				bindCompactFinder(root);
			}
		});

		prefillPluginFinder();

		qsa('[data-fluid-newsletter]').forEach(bindNewsletterForm);
		qsa('[data-fluid-kb]').forEach(bindKnowledgeBase);
	});

	function queryParam(name) {
		return new URLSearchParams(window.location.search).get(name) || '';
	}

	function canonicalOptionValue(select, wanted) {
		if (!select || !wanted) {
			return '';
		}

		var lower = String(wanted).toLowerCase();
		var i;

		for (i = 0; i < select.options.length; i += 1) {
			if (String(select.options[i].value).toLowerCase() === lower) {
				return select.options[i].value;
			}
		}

		return '';
	}

	function waitForOption(select, wanted, timeout) {
		return new Promise(function (resolve) {
			if (!select || !wanted) {
				resolve('');
				return;
			}

			var started = Date.now();
			var timer = window.setInterval(function () {
				var found = canonicalOptionValue(select, wanted);

				if (found) {
					window.clearInterval(timer);
					resolve(found);
					return;
				}

				if (Date.now() - started > (timeout || 12000)) {
					window.clearInterval(timer);
					resolve('');
				}
			}, 100);
		});
	}

	function setSelectValue(select, value) {
		var actual = canonicalOptionValue(select, value);

		if (!select || !actual) {
			return false;
		}

		select.disabled = false;
		select.value = actual;
		syncSelectValueClass(select);
		select.dispatchEvent(new Event('change', { bubbles: true }));
		return select.value === actual;
	}

	function prefillPluginFinder() {
		var year = queryParam('fy_year') || queryParam('year');
		var make = queryParam('fy_make') || queryParam('make');
		var model = queryParam('fy_model') || queryParam('model');
		var submodel = queryParam('fy_submodel') || queryParam('submodel');

		if (!year) {
			return;
		}

		var yearSelect = qs('[data-fluidampr-field="year"]');
		var makeSelect = qs('[data-fluidampr-field="make"]');
		var modelSelect = qs('[data-fluidampr-field="model"]');
		var submodelSelect = qs('[data-fluidampr-field="submodel"]');

		if (!yearSelect) {
			return;
		}

		waitForOption(yearSelect, year).then(function (yearValue) {
			if (!yearValue || !setSelectValue(yearSelect, yearValue) || !make) {
				return;
			}

			return waitForOption(makeSelect, make).then(function (makeValue) {
				if (!makeValue || !setSelectValue(makeSelect, makeValue) || !model) {
					return;
				}

				return waitForOption(modelSelect, model).then(function (modelValue) {
					if (!modelValue || !setSelectValue(modelSelect, modelValue) || !submodel) {
						return;
					}

					return waitForOption(submodelSelect, submodel, 4000).then(function (subValue) {
						if (subValue) {
							setSelectValue(submodelSelect, subValue);
						}
					});
				});
			});
		});
	}

	function kbEscape(text) {
		var div = document.createElement('div');
		div.textContent = text || '';
		return div.innerHTML;
	}

	function kbListHtml(articles) {
		if (!articles || !articles.length) {
			return '';
		}

		return '<ul class="fluid-kb__list">' + articles.map(function (article) {
			var meta = [];
			var cats;
			var html = '<li class="fluid-kb__item"><a class="fluid-kb__link" href="' + kbEscape(article.url || '') + '">' + kbEscape(article.title || '') + '</a>';

			if (article.excerpt) {
				html += '<p class="fluid-kb__excerpt">' + kbEscape(article.excerpt) + '</p>';
			}

			if (article.featured) {
				meta.push('Common question');
			}

			cats = article.categories || [];
			cats.forEach(function (category) {
				if (category && category.name) {
					meta.push(category.name);
				}
			});

			if (article.related_skus && article.related_skus.length) {
				meta.push(article.related_skus.join(', '));
			}

			if (meta.length) {
				html += '<p class="fluid-kb__meta">' + kbEscape(meta.join(' · ')) + '</p>';
			}

			return html + '</li>';
		}).join('') + '</ul>';
	}

	function bindKnowledgeBase(root) {
		var form = qs('[data-fluid-kb-form]', root);
		var input = qs('[data-fluid-kb-q]', root);
		var category = qs('[data-fluid-kb-category]', root);
		var status = qs('[data-fluid-kb-status]', root);
		var results = qs('[data-fluid-kb-results]', root);
		var restUrl = settings.knowledgeBaseRest || '';

		if (!form || !restUrl) {
			return;
		}

		function search(event) {
			if (event) {
				event.preventDefault();
			}

			var params = new URLSearchParams();
			var q = input ? input.value.trim() : '';
			var topic = category ? category.value : (root.getAttribute('data-category') || '');

			if (q) {
				params.set('q', q);
			}

			if (topic) {
				params.set('category', topic);
			}

			if (root.getAttribute('data-featured') === '1') {
				params.set('featured', '1');
			}

			if (status) {
				status.textContent = settings.i18n.kbLoading || 'Searching…';
			}

			fetch(restUrl + (params.toString() ? '?' + params.toString() : ''), {
				credentials: 'same-origin',
				headers: { Accept: 'application/json' }
			}).then(function (res) {
				return res.json();
			}).then(function (payload) {
				var articles = payload && payload.data && payload.data.results ? payload.data.results : [];

				if (status) {
					status.textContent = articles.length ? '' : (payload && payload.message) || settings.i18n.kbEmpty || 'No matching articles were found.';
				}

				if (results) {
					results.innerHTML = articles.length ? kbListHtml(articles) : '';
				}
			}).catch(function () {
				if (status) {
					status.textContent = settings.i18n.kbError || 'Could not search articles. Try again.';
				}
			});
		}

		form.addEventListener('submit', search);

		if (category) {
			category.addEventListener('change', search);
		}

		if (input) {
			['q', 'sku', 'part_number', 'pn'].some(function (key) {
				var value = queryParam(key);

				if (value) {
					input.value = value;
					search();
					return true;
				}

				return false;
			});
		}
	}

	function bindNewsletterForm(form) {
		var status = qs('.fluid-footer__form-status', form);
		var button = qs('button[type="submit"]', form);
		var endpoint = settings.newsletterRest || form.getAttribute('action');

		form.addEventListener('submit', function (event) {
			event.preventDefault();

			if (!endpoint) {
				return;
			}

			var email = qs('input[type="email"]', form);
			var company = qs('input[name="company"]', form);
			var audience = qs('input[name="audience"]:checked', form);
			var turnstileInput = qs('input[name="cf-turnstile-response"]', form);
			var turnstileToken = turnstileInput ? turnstileInput.value : '';

			if (!audience || !audience.value) {
				if (status) {
					status.hidden = false;
					status.className = 'fluid-footer__form-status is-error';
					status.textContent = settings.i18n.newsletterAudience || 'Choose Customer or Dealer.';
				}
				return;
			}

			if (settings.turnstileEnabled && !turnstileToken) {
				if (status) {
					status.hidden = false;
					status.className = 'fluid-footer__form-status is-error';
					status.textContent = settings.i18n.newsletterTurnstile || 'Please complete the security check and try again.';
				}
				return;
			}

			if (status) {
				status.hidden = false;
				status.className = 'fluid-footer__form-status';
				status.textContent = settings.i18n.newsletterSending || 'Signing up…';
			}

			if (button) {
				button.disabled = true;
			}

			fetch(endpoint, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': settings.restNonce || ''
				},
				body: JSON.stringify({
					email: email ? email.value : '',
					audience: audience.value,
					company: company ? company.value : '',
					turnstile_token: turnstileToken
				})
			}).then(function (res) {
				return res.json().then(function (payload) {
					return { ok: res.ok && payload && payload.success, payload: payload };
				});
			}).then(function (result) {
				var message = (result.payload && result.payload.message) || settings.i18n.newsletterError;

				if (status) {
					status.className = 'fluid-footer__form-status ' + (result.ok ? 'is-success' : 'is-error');
					status.textContent = message;
				}

				if (result.ok) {
					form.classList.add('is-complete');
					if (email) {
						email.value = '';
					}

					var customer = qs('input[name="audience"][value="customer"]', form);
					if (customer) {
						customer.checked = true;
					}
				}
			}).catch(function () {
				if (status) {
					status.className = 'fluid-footer__form-status is-error';
					status.textContent = settings.i18n.newsletterError || 'Could not complete signup. Try again.';
				}
			}).then(function () {
				if (button) {
					button.disabled = false;
				}

				if (window.turnstile && typeof window.turnstile.reset === 'function') {
					var widget = qs('.cf-turnstile', form);
					if (widget) {
						window.turnstile.reset(widget);
					} else {
						window.turnstile.reset();
					}
				}
			});
		});
	}
})();
