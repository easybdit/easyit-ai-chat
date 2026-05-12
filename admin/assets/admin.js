(function ($) {
	'use strict';

	// Tab switching
	$(document).on('click', '.weai-tab-btn', function () {
		var tab = $(this).data('tab');
		$('.weai-tab-btn').removeClass('active');
		$(this).addClass('active');
		$('.weai-panel').removeClass('active');
		$('#weai-panel-' + tab).addClass('active');
		if (history.replaceState) history.replaceState(null, '', '#' + tab);
	});
	var hash = location.hash.replace('#', '');
	if (hash) {
		var $btn = $('.weai-tab-btn[data-tab="' + hash + '"]');
		if ($btn.length) $btn.trigger('click');
	}

	// Test connection
	$(document).on('click', '.weai-test-btn', function () {
		var $btn     = $(this);
		var provider = $btn.data('provider');
		var $result  = $('#weai-test-' + provider);
		$btn.prop('disabled', true).text(WPEasyAIAdmin.i18n.testing || 'Testing\u2026');
		$result.text('').css('color', '#888');

		$.post(WPEasyAIAdmin.ajax_url, {
			action:   'wpeasyai_health',
			nonce:    WPEasyAIAdmin.nonce,
			provider: provider,
		}, function (res) {
			if (res.success) {
				$result.html(res.data.message).css('color', '#16a34a');
			} else {
				$result.html(res.data.message).css('color', '#ef4444');
			}
		}).fail(function () {
			$result.text('\u274C ' + (WPEasyAIAdmin.i18n.error || 'Request failed.')).css('color', '#ef4444');
		}).always(function () {
			$btn.prop('disabled', false).html('🔌 Test Connection');
		});
	});

}(jQuery));
