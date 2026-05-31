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

	// Color reset buttons
	$(document).on('click', '.eaic-color-reset', function () {
		var target = $(this).data('target');
		var def    = $(this).data('default');
		$('#' + target).val(def);
	});

	// AI Avatar — WordPress media uploader
	$(document).on('click', '#eaic-avatar-upload-btn', function (e) {
		e.preventDefault();
		var frame = wp.media({
			title:    'Select AI Avatar Image',
			button:   { text: 'Use this image' },
			multiple: false
		});
		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			$('#eaic-avatar-url').val(attachment.url);
			$('#eaic-avatar-preview').attr('src', attachment.url).show();
			$('#eaic-avatar-remove-btn').show();
		});
		frame.open();
	});
	$(document).on('click', '#eaic-avatar-remove-btn', function () {
		$('#eaic-avatar-url').val('');
		$('#eaic-avatar-preview').attr('src', '').hide();
		$(this).hide();
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
