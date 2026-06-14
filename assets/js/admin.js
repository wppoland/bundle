/**
 * Bundle — admin settings help affordances.
 *
 * Progressive enhancement only. The "?" buttons are real, focusable controls and
 * each is wired to a help bubble via aria-describedby, so screen-reader and
 * keyboard users always get the help text even with JS disabled (the bubble is a
 * native [popover], which stays hidden until invoked but remains discoverable).
 *
 * Where the Popover API is supported we position the bubble next to its trigger
 * and toggle it on hover/focus/click; otherwise we fall back to toggling a class.
 * No dependencies, no jQuery.
 */
(function () {
	'use strict';

	var supportsPopover =
		typeof HTMLElement !== 'undefined' &&
		Object.prototype.hasOwnProperty.call(HTMLElement.prototype, 'popover');

	function place(trigger, pop) {
		if (!supportsPopover) {
			return;
		}
		var r = trigger.getBoundingClientRect();
		var top = r.bottom + 8;
		var left = Math.max(8, r.left);
		// Keep the bubble inside the viewport on the inline axis.
		var maxLeft = window.innerWidth - pop.offsetWidth - 8;
		if (left > maxLeft) {
			left = Math.max(8, maxLeft);
		}
		pop.style.top = top + 'px';
		pop.style.left = left + 'px';
	}

	function wire(trigger) {
		var id = trigger.getAttribute('aria-describedby');
		if (!id) {
			return;
		}
		var pop = document.getElementById(id);
		if (!pop) {
			return;
		}

		function open() {
			place(trigger, pop);
			if (supportsPopover && typeof pop.showPopover === 'function') {
				try {
					pop.showPopover();
				} catch (e) {
					pop.classList.add('is-open');
				}
			} else {
				pop.classList.add('is-open');
			}
		}

		function close() {
			if (supportsPopover && typeof pop.hidePopover === 'function') {
				try {
					pop.hidePopover();
				} catch (e) {
					pop.classList.remove('is-open');
				}
			} else {
				pop.classList.remove('is-open');
			}
		}

		trigger.addEventListener('mouseenter', open);
		trigger.addEventListener('mouseleave', close);
		trigger.addEventListener('focus', open);
		trigger.addEventListener('blur', close);
		trigger.addEventListener('click', function (ev) {
			ev.preventDefault();
			var isOpen = supportsPopover
				? pop.matches(':popover-open')
				: pop.classList.contains('is-open');
			if (isOpen) {
				close();
			} else {
				open();
			}
		});
		trigger.addEventListener('keydown', function (ev) {
			if (ev.key === 'Escape') {
				close();
			}
		});
	}

	function init() {
		var triggers = document.querySelectorAll('.bundle-help[aria-describedby]');
		Array.prototype.forEach.call(triggers, wire);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
