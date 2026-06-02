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
		try { sessionStorage.setItem('eaic_active_tab', tab); } catch(e) {}
	});

	// Restore tab: URL hash first, then sessionStorage (survives save-redirect)
	var hash = location.hash.replace('#', '');
	if (!hash) {
		try { hash = sessionStorage.getItem('eaic_active_tab') || ''; } catch(e) {}
	}
	if (hash) {
		var $btn = $('.eaic-tab-btn[data-tab="' + hash + '"]');
		if ($btn.length) { $btn.trigger('click'); }
	}

	// Ensure hidden JSON inputs are current before the settings form submits
	$('form').on('submit', function () {
		if (typeof saveCp === 'function') { saveCp(); }
		if (typeof saveProfiles === 'function') { saveProfiles(); }
	});

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

	// ----- Access restriction role toggle -----
	$('#eaic-access-restriction').on('change', function() {
		$('#eaic-roles-wrap').toggle($(this).val() === 'specific_roles');
	});
	// ----- End Access restriction -----

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
		var idx  = parseInt($(this).data('idx'), 10);
		var $el  = $(this);
		var map  = { 'eap-slug':'slug','eap-name':'name','eap-provider':'provider','eap-title':'title','eap-system':'system_prompt' };
		var key  = null;
		$.each(map, function(cls, field) { if ($el.hasClass(cls)) { key = field; return false; } });
		if (profileData[idx] && key) {
			profileData[idx][key] = $el.val();
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

	// ----- Custom Providers -----
	var $cpList    = $('#eaic-custom-providers-list');
	var $cpJson    = $('#eaic-custom-providers-json');
	var $cpAddBtn  = $('#eaic-add-custom-provider');
	var cpData     = [];

	if ($cpList.length) {
		try { cpData = JSON.parse($cpJson.val() || '[]'); } catch(e) { cpData = []; }
		renderCustomProviders();
	}

	function saveCp() {
		$cpJson.val(JSON.stringify(cpData));
	}

	function renderCustomProviders() {
		$cpList.empty();
		if (!cpData.length) {
			$cpList.html('<p style="color:#9ca3af;font-size:13px">No custom providers yet. Click "+ Add Provider" to add one.</p>');
			return;
		}
		$.each(cpData, function(idx, cp) {
			var slug = 'custom_' + (idx + 1);
			var $row = $('<div class="eaic-profile-row" style="border:1px solid #e5e7eb;border-radius:8px;padding:14px;margin-bottom:12px;background:#fafafa"></div>');

			// Header row
			$row.append(
				'<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">' +
				'<strong style="font-size:13px"><span class="ecp-header-name">' + esc(cp.name || slug) + '</span> <code style="font-size:11px;color:#6b7280">provider="' + slug + '"</code></strong>' +
				'<button type="button" class="ecp-del button button-link-delete" data-idx="' + idx + '" style="color:#ef4444">Remove</button>' +
				'</div>'
			);

			// Fields grid
			var grid = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">';
			grid += '<div><label style="font-size:12px;font-weight:600;display:block;margin-bottom:3px">Provider Name</label>' +
				'<input type="text" class="ecp-name regular-text" style="width:100%" data-idx="' + idx + '" value="' + esc(cp.name) + '" placeholder="My Custom Server"></div>';
			grid += '<div><label style="font-size:12px;font-weight:600;display:block;margin-bottom:3px">Base URL</label>' +
				'<input type="url" class="ecp-url regular-text" style="width:100%" data-idx="' + idx + '" value="' + esc(cp.url) + '" placeholder="http://aiapi.example.com/api/openai"></div>';
			grid += '<div><label style="font-size:12px;font-weight:600;display:block;margin-bottom:3px">API Key <span style="font-weight:400;color:#9ca3af">(optional)</span></label>' +
				'<input type="text" class="ecp-key regular-text" style="width:100%" data-idx="' + idx + '" value="' + esc(cp.api_key) + '" placeholder="sk-..."></div>';
			grid += '<div><label style="font-size:12px;font-weight:600;display:block;margin-bottom:3px">Default Model</label>' +
				'<input type="text" class="ecp-model regular-text" style="width:100%" data-idx="' + idx + '" value="' + esc(cp.model) + '" placeholder="gemma3:4b"></div>';
			grid += '<div><label style="font-size:12px;font-weight:600;display:block;margin-bottom:3px">Timeout (s)</label>' +
				'<input type="number" class="ecp-timeout" style="width:90px" min="10" max="300" data-idx="' + idx + '" value="' + (parseInt(cp.timeout, 10) || 30) + '"></div>';
			grid += '<div style="display:flex;align-items:flex-end;padding-bottom:2px"><label style="display:flex;align-items:center;gap:6px;cursor:pointer">' +
				'<input type="checkbox" class="ecp-enabled" data-idx="' + idx + '"' + (cp.enabled ? ' checked' : '') + '>' +
				'<span style="font-size:13px;font-weight:600">Enabled</span></label></div>';
			grid += '</div>';

			// Test row
			grid += '<div style="margin-top:10px;display:flex;align-items:center;gap:10px">' +
				'<button type="button" class="ecp-test eaic-test-btn-styled button" data-idx="' + idx + '">🔌 Test Connection</button>' +
				'<span class="ecp-result" id="ecp-result-' + idx + '" style="font-size:13px"></span>' +
				'</div>';

			$row.append(grid);
			$cpList.append($row);
		});
	}

	// Live field updates — update data + header text in-place (no full re-render)
	$cpList.on('input change', '.ecp-name,.ecp-url,.ecp-key,.ecp-model,.ecp-timeout,.ecp-enabled', function() {
		var idx = parseInt($(this).data('idx'), 10);
		var $el = $(this);
		if (!cpData[idx]) { return; }
		if ($el.hasClass('ecp-name'))    {
			cpData[idx].name = $el.val();
			// Update header text without re-rendering the full list
			$el.closest('.eaic-profile-row').find('.ecp-header-name').text(cpData[idx].name || 'custom_' + (idx + 1));
		}
		if ($el.hasClass('ecp-url'))     { cpData[idx].url     = $el.val(); }
		if ($el.hasClass('ecp-key'))     { cpData[idx].api_key = $el.val(); }
		if ($el.hasClass('ecp-model'))   { cpData[idx].model   = $el.val(); }
		if ($el.hasClass('ecp-timeout')) { cpData[idx].timeout = parseInt($el.val(), 10) || 30; }
		if ($el.hasClass('ecp-enabled')) { cpData[idx].enabled = $el.is(':checked'); }
		saveCp();
	});

	// Remove provider
	$cpList.on('click', '.ecp-del', function() {
		var idx = parseInt($(this).data('idx'), 10);
		cpData.splice(idx, 1);
		saveCp();
		renderCustomProviders();
	});

	// Test connection using current form values
	$cpList.on('click', '.ecp-test', function() {
		var idx    = parseInt($(this).data('idx'), 10);
		var cp     = cpData[idx];
		var $btn   = $(this);
		var $res   = $('#ecp-result-' + idx);

		if (!cp || !cp.url) {
			$res.html('❌ Enter a URL first.').css('color','#ef4444');
			return;
		}

		$btn.prop('disabled', true).text('Testing…');
		$res.text('').css('color','#888');

		$.post(EAICAdmin.ajax_url, {
			action:   'eaic_test_custom_provider',
			nonce:    EAICAdmin.nonce,
			url:      cp.url,
			api_key:  cp.api_key,
			model:    cp.model
		}, function(res) {
			if (res && res.success) {
				$res.html(res.data.message).css('color','#16a34a');
			} else {
				$res.html((res && res.data && res.data.message) || '❌ Failed.').css('color','#ef4444');
			}
		}).fail(function() {
			$res.text('❌ Request failed.').css('color','#ef4444');
		}).always(function() {
			$btn.prop('disabled', false).html('🔌 Test Connection');
		});
	});

	// Add new provider
	$cpAddBtn.on('click', function() {
		var n = cpData.length + 1;
		cpData.push({ name: 'Custom Provider ' + n, url: '', api_key: '', model: '', enabled: true, timeout: 30 });
		saveCp();
		renderCustomProviders();
	});
	// ----- End Custom Providers -----

}(jQuery));
