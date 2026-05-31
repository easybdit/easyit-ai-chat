/* global jQuery, EAICAdmin */
(function ($) {
	'use strict';

	// Tab switching
	$(document).on('click', '.eaic-tab-btn', function () {
		var tab = $(this).data('tab');
		$('.eaic-tab-btn').removeClass('active');
		$(this).addClass('active');
		$('.eaic-panel').removeClass('active');
		$('#eaic-panel-' + tab).addClass('active');
		if (window.history && history.replaceState) {
			history.replaceState(null, '', '#' + tab);
		}
	});

	// Restore tab from hash
	var hash = location.hash.replace('#', '');
	if (hash) {
		var $btn = $('.eaic-tab-btn[data-tab="' + hash + '"]');
		if ($btn.length) { $btn.trigger('click'); }
	}

	// Shortcode click-to-copy
	$(document).on('click', '.eaic-sc-item', function () {
		var $el = $(this);
		var text = $.trim($el.text());
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(function () {
				var orig = $el.text();
				$el.text('✅ Copied!');
				setTimeout(function () { $el.text(orig); }, 1400);
			});
		}
	});

	// Welcome message textarea toggle
	$(document).on('change', '#eaic-welcome-enabled', function () {
		$('#eaic-welcome-text-wrap').toggle($(this).is(':checked'));
	});

	// Suggested questions textarea toggle
	$(document).on('change', '#eaic-sq-enabled', function () {
		$('#eaic-sq-wrap').toggle($(this).is(':checked'));
	});

	// Test connection
	$(document).on('click', '.eaic-test-btn', function () {
		var $btn     = $(this);
		var provider = $btn.data('provider');
		var $result  = $('#eaic-test-' + provider);

		$btn.prop('disabled', true).text((EAICAdmin.i18n && EAICAdmin.i18n.testing) || 'Testing…');
		$result.text('').css('color', '#888');

		$.post(EAICAdmin.ajax_url, {
			action:   'eaic_health',
			nonce:    EAICAdmin.nonce,
			provider: provider
		}, function (res) {
			if (res && res.success) {
				$result.html(res.data.message).css('color', '#16a34a');
			} else {
				$result.html((res && res.data && res.data.message) || '').css('color', '#ef4444');
			}
		}).fail(function () {
			$result.text('❌ ' + ((EAICAdmin.i18n && EAICAdmin.i18n.error) || 'Request failed.')).css('color', '#ef4444');
		}).always(function () {
			$btn.prop('disabled', false).html('🔌 Test Connection');
		});
	});

}(jQuery));
