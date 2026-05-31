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

	// ----- Bot Profiles -----
	var $profileList  = $('#eaic-profiles-list');
	var $profileJson  = $('#eaic-profiles-json');
	var $addBtn       = $('#eaic-add-profile');
	var profileData   = [];

	if ($profileList.length) {
		try { profileData = JSON.parse($profileJson.val() || '[]'); } catch(e) { profileData = []; }
		renderProfiles();
	}

	function saveProfiles() {
		$profileJson.val(JSON.stringify(profileData));
	}

	function renderProfiles() {
		$profileList.empty();
		if (!profileData.length) {
			$profileList.html('<p style="color:#9ca3af;font-size:13px">' + (window.eaicL10n && eaicL10n.no_profiles || 'No profiles yet.') + '</p>');
			return;
		}
		$.each(profileData, function(idx, p) {
			var $row = $('<div class="eaic-profile-row" style="border:1px solid #e5e7eb;border-radius:8px;padding:14px;margin-bottom:10px;background:#fafafa"></div>');
			$row.append('<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px"><strong style="font-size:13px">' + $('<span>').text(p.name || p.slug).html() + '</strong><button type="button" class="eaic-del-profile button button-link-delete" data-idx="' + idx + '" style="color:#ef4444">Remove</button></div>');
			var providers = {ollama:'Ollama',openai:'OpenAI',anthropic:'Anthropic',deepseek:'DeepSeek',gemini:'Gemini'};
			var grid = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">';
			grid += '<div><label style="font-size:12px;font-weight:600;display:block;margin-bottom:3px">Slug</label><input type="text" class="eap-slug regular-text" style="width:100%" data-idx="'+idx+'" value="'+esc(p.slug)+'"></div>';
			grid += '<div><label style="font-size:12px;font-weight:600;display:block;margin-bottom:3px">Name</label><input type="text" class="eap-name regular-text" style="width:100%" data-idx="'+idx+'" value="'+esc(p.name)+'"></div>';
			grid += '<div><label style="font-size:12px;font-weight:600;display:block;margin-bottom:3px">Provider</label><select class="eap-provider" data-idx="'+idx+'" style="width:100%">';
			$.each(providers, function(slug, label) { grid += '<option value="'+slug+'"'+(p.provider===slug?' selected':'')+'>'+label+'</option>'; });
			grid += '</select></div>';
			grid += '<div><label style="font-size:12px;font-weight:600;display:block;margin-bottom:3px">Title</label><input type="text" class="eap-title regular-text" style="width:100%" data-idx="'+idx+'" value="'+esc(p.title)+'"></div>';
			grid += '</div>';
			grid += '<div style="margin-top:8px"><label style="font-size:12px;font-weight:600;display:block;margin-bottom:3px">System Prompt</label><textarea class="eap-system regular-text" rows="2" style="width:100%" data-idx="'+idx+'">'+esc(p.system_prompt)+'</textarea></div>';
			$row.append(grid);
			$profileList.append($row);
		});
	}

	function esc(str) { return $('<span>').text(str||'').html(); }

	$profileList.on('input change', '.eap-slug,.eap-name,.eap-provider,.eap-title,.eap-system', function() {
		var idx = parseInt($(this).data('idx'), 10);
		var cls = $(this).attr('class').split(' ')[0];
		var map = { 'eap-slug':'slug','eap-name':'name','eap-provider':'provider','eap-title':'title','eap-system':'system_prompt' };
		if (profileData[idx] && map[cls]) {
			profileData[idx][map[cls]] = $(this).val();
			saveProfiles();
		}
	});

	$profileList.on('click', '.eaic-del-profile', function() {
		var idx = parseInt($(this).data('idx'), 10);
		profileData.splice(idx, 1);
		saveProfiles();
		renderProfiles();
	});

	$addBtn.on('click', function() {
		var n = profileData.length + 1;
		profileData.push({ slug:'profile-'+n, name:'Profile '+n, provider:$('[name$="[default_provider]"]').val()||'ollama', title:'', placeholder:'', system_prompt:'' });
		saveProfiles();
		renderProfiles();
	});
	// ----- End Bot Profiles -----

}(jQuery));
