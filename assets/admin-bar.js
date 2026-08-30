(function () {
	'use strict';

	var cfg = window.pinswitchPanel || {};
	var state = {
		tab: 'pinned',
		search: '',
		page: 1,
		hasMore: false,
		loading: false,
		open: false,
	};
	var requestSeq = 0;
	var closeTimer = null;
	var debouncedFetchUsers = debounce(function () {
		fetchUsers(true);
	}, 250);

	function el(tag, attrs, children) {
		var node = document.createElement(tag);
		attrs = attrs || {};
		Object.keys(attrs).forEach(function (key) {
			if (key === 'className') {
				node.className = attrs[key];
			} else if (key === 'text') {
				node.textContent = attrs[key];
			} else if (key === 'html') {
				node.innerHTML = attrs[key];
			} else if (key.slice(0, 2) === 'on') {
				node.addEventListener(key.slice(2).toLowerCase(), attrs[key]);
			} else {
				node.setAttribute(key, attrs[key]);
			}
		});
		(children || []).forEach(function (child) {
			if (child) {
				node.appendChild(child);
			}
		});
		return node;
	}

	function debounce(fn, wait) {
		var t;
		return function () {
			var args = arguments;
			var ctx = this;
			clearTimeout(t);
			t = setTimeout(function () {
				fn.apply(ctx, args);
			}, wait);
		};
	}

	function panelRoot() {
		return document.getElementById('pinswitch-panel');
	}

	function listRoot() {
		return document.getElementById('pinswitch-panel-list');
	}

	function loadingNode() {
		return el('div', { className: 'pinswitch-panel__loading', text: cfg.i18n.loading || 'Loading…' });
	}

	function replaceListWithLoading() {
		var list = listRoot();
		if (!list) {
			return;
		}
		list.innerHTML = '';
		list.classList.add('is-loading-only');
		list.appendChild(loadingNode());
		state.loading = true;
	}

	function removeLoadingIndicator() {
		var list = listRoot();
		if (!list) {
			return;
		}
		var loading = list.querySelector('.pinswitch-panel__loading');
		if (loading) {
			loading.remove();
		}
		state.loading = false;
	}

	function appendLoadingIndicator() {
		var list = listRoot();
		if (!list || list.querySelector('.pinswitch-panel__loading')) {
			return;
		}
		list.appendChild(loadingNode());
	}

	function renderUser(user) {
		var switchUrl = user.switch_url || '#';
		if (switchUrl.indexOf('redirect_to=') === -1) {
			switchUrl +=
				(switchUrl.indexOf('?') === -1 ? '?' : '&') +
				'redirect_to=' +
				encodeURIComponent(window.location.href);
		}

		var profileUrl = user.profile_url || '#';
		var label = user.display_name || user.user_login;

		var nameChildren = [];
		if (user.pinned) {
			nameChildren.push(el('span', { className: 'pinswitch-user__pin', text: '★ ' }));
		}
		nameChildren.push(document.createTextNode(label));

		var nameRowChildren = [
			el('span', { className: 'pinswitch-user__name', title: label }, nameChildren),
		];
		if (user.role) {
			nameRowChildren.push(el('span', { className: 'pinswitch-user__role', text: user.role }));
		}

		var metaChildren = [
			el('div', { className: 'pinswitch-user__name-row' }, nameRowChildren),
		];

		if (user.user_login) {
			metaChildren.push(
				el('div', { className: 'pinswitch-user__login', text: '@' + user.user_login })
			);
		}

		if (user.user_email) {
			metaChildren.push(
				el('div', { className: 'pinswitch-user__email', text: user.user_email, title: user.user_email })
			);
		}

		var showPin = state.tab === 'all' && !user.pinned && user.pin_url;
		var showUnpin = state.tab === 'pinned' && user.pinned && user.unpin_url;
		var actionChildren = [];

		if (showPin) {
			actionChildren.push(
				el('a', {
					className: 'pinswitch-user__pin-link',
					href: user.pin_url,
					title: cfg.i18n.pinUser || 'Pin user',
					text: cfg.i18n.pin || 'Pin',
				})
			);
		}

		if (showUnpin) {
			actionChildren.push(
				el('a', {
					className: 'pinswitch-user__unpin-link',
					href: user.unpin_url,
					title: cfg.i18n.unpinUser || 'Unpin user',
					text: cfg.i18n.unpin || 'Unpin',
				})
			);
		}

		actionChildren.push(
			el('a', {
				className: 'pinswitch-user__switch-link',
				href: switchUrl,
				title: (cfg.i18n.switchTo || 'Switch to') + ' ' + label,
				text: cfg.i18n.switchTo || 'Switch To',
			})
		);

		return el('div', { className: 'pinswitch-user-row' }, [
			el(
				'a',
				{
					className: 'pinswitch-user__main',
					href: profileUrl,
					title: label,
				},
				[
					el('div', { className: 'pinswitch-user__avatar' }, [
						el('img', {
							src: user.avatar || '',
							alt: '',
							width: '28',
							height: '28',
							loading: 'lazy',
						}),
					]),
					el('div', { className: 'pinswitch-user__meta' }, metaChildren),
				]
			),
			el('div', { className: 'pinswitch-user__actions' }, actionChildren),
		]);
	}

	function renderEmpty() {
		var msg =
			state.tab === 'pinned'
				? cfg.i18n.emptyPinned || 'No pinned users yet.'
				: cfg.i18n.emptySearch || 'No users found.';
		return el('div', { className: 'pinswitch-panel__empty', text: msg });
	}

	function renderUsers(users, reset) {
		var list = listRoot();
		if (!list) {
			return;
		}

		if (reset) {
			list.innerHTML = '';
			list.classList.remove('is-loading-only');
		} else {
			removeLoadingIndicator();
		}

		var end = list.querySelector('.pinswitch-panel__end');
		if (end) {
			end.remove();
		}

		if (reset && (!users || !users.length)) {
			list.appendChild(renderEmpty());
			state.loading = false;
			return;
		}

		(users || []).forEach(function (user) {
			list.appendChild(renderUser(user));
		});

		if (!state.hasMore && list.querySelector('.pinswitch-user-row, .pinswitch-panel__empty')) {
			list.appendChild(
				el('div', { className: 'pinswitch-panel__end', text: cfg.i18n.end || 'End of list' })
			);
		}

		state.loading = false;
	}

	function fetchUsers(reset) {
		var seq = ++requestSeq;

		if (reset) {
			state.page = 1;
			state.hasMore = false;
			if (!listRoot() || !listRoot().querySelector('.pinswitch-panel__loading')) {
				replaceListWithLoading();
			}
		} else {
			if (state.loading) {
				return;
			}
			state.loading = true;
			appendLoadingIndicator();
		}

		var body = new FormData();
		body.append('action', 'pinswitch_search_users');
		body.append('nonce', cfg.nonce || '');
		body.append('search', state.search);
		body.append('page', String(state.page));
		body.append('pinned', state.tab === 'pinned' ? '1' : '0');

		fetch(cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		})
			.then(function (res) {
				return res.json();
			})
			.then(function (json) {
				if (seq !== requestSeq) {
					return;
				}

				if (!json || !json.success || !json.data) {
					if (reset) {
						renderUsers([], true);
					} else {
						removeLoadingIndicator();
						state.loading = false;
					}
					return;
				}

				state.hasMore = !!json.data.has_more;
				renderUsers(json.data.users || [], reset);
			})
			.catch(function () {
				if (seq !== requestSeq) {
					return;
				}

				if (reset) {
					renderUsers([], true);
				} else {
					removeLoadingIndicator();
					state.loading = false;
				}
			});
	}

	function setTab(tab) {
		if (state.tab === tab) {
			return;
		}

		state.tab = tab;
		var panel = panelRoot();
		if (!panel) {
			return;
		}

		panel.querySelectorAll('.pinswitch-panel__tab').forEach(function (btn) {
			btn.classList.toggle('is-active', btn.getAttribute('data-tab') === tab);
		});

		var searchWrap = panel.querySelector('.pinswitch-panel__search');
		var input = panel.querySelector('.pinswitch-panel__search input');

		if (tab === 'all') {
			if (searchWrap) {
				searchWrap.style.display = '';
			}
		} else {
			state.search = '';
			if (input) {
				input.value = '';
			}
			if (searchWrap) {
				searchWrap.style.display = 'none';
			}
		}

		fetchUsers(true);
	}

	function openPanel() {
		var panel = panelRoot();
		var item = document.getElementById('wp-admin-bar-pinswitch-menu');
		if (!panel || !item) {
			return;
		}
		cancelClose();
		state.open = true;
		panel.classList.add('is-open');
		item.classList.add('hover');
		fetchUsers(true);
	}

	function closePanel() {
		var panel = panelRoot();
		var item = document.getElementById('wp-admin-bar-pinswitch-menu');
		if (!panel) {
			return;
		}
		state.open = false;
		panel.classList.remove('is-open');
		if (item) {
			item.classList.remove('hover');
		}
	}

	function cancelClose() {
		clearTimeout(closeTimer);
		closeTimer = null;
	}

	function scheduleClose() {
		cancelClose();
		closeTimer = setTimeout(function () {
			closePanel();
		}, 200);
	}

	function buildPanel() {
		var item = document.getElementById('wp-admin-bar-pinswitch-menu');
		if (!item || document.getElementById('pinswitch-panel')) {
			return;
		}

		var panel = el('div', { id: 'pinswitch-panel', className: 'pinswitch-panel' }, [
			el('div', { className: 'pinswitch-panel__tabs' }, [
				el('button', {
					type: 'button',
					className: 'pinswitch-panel__tab is-active',
					'data-tab': 'pinned',
					text: cfg.i18n.pinned || 'Pinned',
					onClick: function (e) {
						e.preventDefault();
						e.stopPropagation();
						setTab('pinned');
					},
				}),
				el('button', {
					type: 'button',
					className: 'pinswitch-panel__tab',
					'data-tab': 'all',
					text: cfg.i18n.all || 'All users',
					onClick: function (e) {
						e.preventDefault();
						e.stopPropagation();
						setTab('all');
					},
				}),
			]),
			el('div', { className: 'pinswitch-panel__search', style: 'display:none' }, [
				el('input', {
					type: 'search',
					placeholder: cfg.i18n.searchPlaceholder || 'Search username or email…',
					autocomplete: 'off',
					onInput: function (e) {
						state.search = e.target.value.trim();
						replaceListWithLoading();
						debouncedFetchUsers();
					},
					onClick: function (e) {
						e.stopPropagation();
					},
					onKeydown: function (e) {
						e.stopPropagation();
					},
				}),
			]),
			el('div', { id: 'pinswitch-panel-list', className: 'pinswitch-panel__list' }),
		]);

		item.style.position = 'relative';
		item.appendChild(panel);

		var link = item.querySelector('.ab-item');
		if (link) {
			link.setAttribute('href', '#');
			link.addEventListener('click', function (e) {
				e.preventDefault();
			});
		}

		item.addEventListener('mouseenter', function () {
			openPanel();
		});

		item.addEventListener('mouseleave', function () {
			scheduleClose();
		});

		panel.addEventListener('mouseenter', function () {
			cancelClose();
		});

		panel.addEventListener('mouseleave', function () {
			scheduleClose();
		});

		var list = panel.querySelector('#pinswitch-panel-list');
		list.addEventListener('scroll', function () {
			if (state.loading || !state.hasMore) {
				return;
			}
			if (list.scrollTop + list.clientHeight >= list.scrollHeight - 48) {
				state.page += 1;
				fetchUsers(false);
			}
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && state.open) {
				closePanel();
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', buildPanel);
	} else {
		buildPanel();
	}
})();
