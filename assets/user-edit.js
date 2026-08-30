(function () {
	'use strict';

	var cfg = window.pinswitchUserEdit || {};

	if (document.getElementById('pinswitch-switch-to-user')) {
		return;
	}

	var wrap = document.getElementById('profile-page');
	if (!wrap) {
		return;
	}

	var addUser = wrap.querySelector('a.page-title-action[href*="user-new.php"]');
	var anchor = addUser || wrap.querySelector('h1.wp-heading-inline');

	if (!anchor || !cfg.url) {
		return;
	}

	var link = document.createElement('a');
	link.id = 'pinswitch-switch-to-user';
	link.className = 'page-title-action';
	link.href = cfg.url;
	link.textContent = cfg.label || 'Switch To';
	anchor.insertAdjacentElement('afterend', link);
})();
