(function($){
	'use strict';

	// ── Tab switching (fallback for settings page pure-JS tabs) ──────────
	$(document).on('click', '.eai-tab-btn', function(){
		var tab = $(this).data('tab');
		$('.eai-tab-btn').removeClass('active');
		$(this).addClass('active');
		$('.eai-panel').removeClass('active');
		$('#eai-panel-' + tab).addClass('active');
		if (history.replaceState) history.replaceState(null,'','#'+tab);
	});
	var hash = location.hash.replace('#','');
	if (hash) {
		var $btn = $('.eai-tab-btn[data-tab="'+hash+'"]');
		if ($btn.length) $btn.trigger('click');
	}

	// ── Test connection ──────────────────────────────────────────────────
	$(document).on('click', '.eai-test-btn', function(){
		var $btn     = $(this);
		var provider = $btn.data('provider');
		var $result  = $('#eai-test-' + provider);
		$btn.prop('disabled', true).text('Testing…');
		$result.text('').css('color','#888');

		$.post(EasyITAIChatAdmin.ajax_url, {
			action:   'easyit_ai_chat_health',
			nonce:    EasyITAIChatAdmin.nonce,
			provider: provider,
		}, function(res){
			if (res.success) {
				$result.text(res.data.message).css('color','#16a34a');
			} else {
				$result.text(res.data.message).css('color','#ef4444');
			}
		}).fail(function(){
			$result.text('❌ ' + EasyITAIChatAdmin.i18n.error).css('color','#ef4444');
		}).always(function(){
			$btn.prop('disabled', false).html('🔌 Test Connection');
		});
	});

}(jQuery));
