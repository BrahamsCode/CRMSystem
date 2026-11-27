// jQuery UI 1.13 does part of the dialog layout differently from the copies master loaded. This puts back what each of
// them did, going by the file name the page loads (the name stayed, the content is 1.13 since step 20e):
// - 1.7.2 positioned the box, sized the overlay, left the border out of the height and had no .ui-button class;
// - 1.8rc3 did the same except the position;
// - 1.11 put each button's text in span.ui-button-text, which its stylesheet pads (only matters where it is linked).
// Pages that loaded 1.10, or 1.11 without its stylesheet, are left as they were.
// Events on document, because many pages load jQuery UI again after this file and replace the widget.
(function ($) {
	if (!$) {
		return;
	}

	var maxZ = 1000;

	function loadedVersion() {
		var src = $('script[src*="jquery-ui-1"]').last().attr('src') || '';
		if (src.indexOf('jquery-ui-1.7.2') !== -1) {
			return '1.7';
		}
		if (src.indexOf('jquery-ui-1.8') !== -1) {
			return '1.8';
		}
		if (src.indexOf('jquery-ui-1.11') !== -1 && $('link[href*="jquery-ui-1.11"]').length) {
			return '1.11';
		}
		return '';
	}

	// The buttons have a 0.5s transition in mainStyle.css: drop the class without animating it.
	function plainButtons(widget) {
		var buttons = widget.find('.ui-dialog-buttonpane button.ui-button');
		if (!buttons.length) {
			return false;
		}
		buttons.css('transition', 'none').removeClass('ui-button');
		buttons.each(function () { return this.offsetHeight; });
		buttons.css('transition', '');
		return true;
	}

	function textButtons(widget) {
		var buttons = widget.find('.ui-dialog-buttonpane button').filter(function () {
			return !$(this).children('.ui-button-text').length;
		});
		if (!buttons.length) {
			return false;
		}
		buttons.addClass('ui-state-default ui-button-text-only').each(function () {
			$(this).wrapInner('<span class="ui-button-text"></span>');
		});
		return true;
	}

	$(document).on('dialogcreate', function (event) {
		var version = loadedVersion();
		if (!version) {
			return;
		}
		var widget = $(event.target).dialog('widget');
		if (version === '1.11') {
			textButtons(widget);
			return;
		}
		if (version === '1.7') {
			widget.css({ position: 'absolute', overflow: 'hidden' });
		}
		plainButtons(widget);
		var height = $(event.target).dialog('option', 'height');
		if (typeof height === 'number') {
			var border = (parseFloat(widget.css('border-top-width')) || 0) + (parseFloat(widget.css('border-bottom-width')) || 0);
			$(event.target).dialog('option', 'height', height + border);
		}
	});

	$(document).on('dialogopen', function (event) {
		var version = loadedVersion();
		if (!version) {
			return;
		}
		var instance = $(event.target).dialog('instance');
		// Buttons set after creation arrive in the 1.13 form: resize before the page's own open callback runs.
		if (version === '1.11') {
			if (textButtons(instance.uiDialog)) {
				instance._size();
				instance._position();
			}
			return;
		}
		if (instance.overlay) {
			instance.overlay.css({ position: 'fixed', top: 0, left: 0, width: '100%', height: '100%', zIndex: ++maxZ });
		}
		instance.uiDialog.css('zIndex', ++maxZ);
		if (plainButtons(instance.uiDialog)) {
			instance._size();
			instance._position();
		}
	});
})(window.jQuery);
