var g_ppsSfCurrEditStandardCell = null;
jQuery(document).ready(function(){
	// Show/hide additonal subscribe options
	jQuery('#ppsPopupEditForm').find('[name="params[tpl][sub_dest]"]').change(function(){
		var selectedSubMethod = jQuery(this).val();
		jQuery('.ppsPopupSubDestOpts:visible').slideUp( g_ppsAnimationSpeed );
		var selectedShell = jQuery('.ppsPopupSubDestOpts_'+ selectedSubMethod);
		if(selectedShell && selectedShell.length) {
			selectedShell.slideDown( g_ppsAnimationSpeed );
		}
		if(toeInArray(selectedSubMethod, ['aweber']) === -1) {	// For aweber we use simple post send to aweber side method - so all messages is generated by aweber
			jQuery('.ppsPopupSubTxtsAndRedirect').slideDown( g_ppsAnimationSpeed );
		} else {
			jQuery('.ppsPopupSubTxtsAndRedirect').slideUp( g_ppsAnimationSpeed );
		}
		if(toeInArray(selectedSubMethod, ['wordpress']) !== -1) {	// Only for wordpress - we will handle emails about subscribe confirm
			jQuery('.ppsPopupSubEmailTxt').slideDown( g_ppsAnimationSpeed );
		} else {
			jQuery('.ppsPopupSubEmailTxt').slideUp( g_ppsAnimationSpeed );
		}
		if(toeInArray(selectedSubMethod, ['wordpress', 'aweber']) !== -1) {	// Create WP subscriber with usual subscription methods
			jQuery('.ppsPopupSubCreateWpUser').slideUp( g_ppsAnimationSpeed );
		} else {
			jQuery('.ppsPopupSubCreateWpUser').slideDown( g_ppsAnimationSpeed );
		}
		if(!PPS_DATA.isPro
			&& typeof(g_ppsProSubMethods) !== 'undefined' 
			&& typeof(g_ppsProSubMethods[ selectedSubMethod ]) !== 'undefined'
		) {
			var proDialog = ppsGetMainPromoPopup();
			var promoLink = jQuery('.ppsPopupSubDestOpts_'+ selectedSubMethod).find('.ppsProOptMiniLabel a').attr('href');
			proDialog.find('a').attr('href', promoLink);
			proDialog.dialog('open');
		}
	}).change();
	// MailChimp subscribe data manipulations
	_ppsUpdateMailchimpLists();
	
	jQuery('#ppsPopupEditForm').find('[name="params[tpl][sub_mailchimp_api_key]"]').change(function(){
		_ppsUpdateMailchimpLists();
	});
	jQuery('#ppsPopupEditForm').find('[name="params[tpl][sub_mailchimp_lists][]"]').change(function(){
		_ppsUpdateMailchimpGroups( jQuery(this).val() );
	});
	jQuery('#ppsMailchimpGroups').change(function(){
		var groups = jQuery(this).val();
		if(groups && groups != '' && groups.length) {
			var $groupsFull = jQuery('#ppsPopupEditForm').find('[name="params[tpl][sub_mailchimp_groups_full]"]')
			,	fullArr = [];
			for(var i = 0; i < groups.length; i++) {
				fullArr.push(groups[ i ]+ ':'+ jQuery(this).find('option[value="'+ groups[ i ]+ '"]').html());
			}
			$groupsFull.val(fullArr.join(';'));
		}
	});
	// Test email functionality
	jQuery('.ppsTestEmailFuncBtn').click(function(){
		jQuery.sendFormPps({
			btn: this
		,	data: {mod: 'mail', action: 'testEmail', test_email: jQuery('input[name=test_email]').val()}
		,	onSuccess: function(res) {
				if(!res.error) {
					jQuery('.ppsTestEmailWasSent').slideDown( g_ppsAnimationSpeed );
				}
			}
		});
		return false;
	});
	jQuery('#ppoPopupSubFields').sortable({
		revert: true
	,	placeholder: 'ui-state-highlight-sub-fields'
	,	axis: 'x'
	,	items: '.ppsSubFieldShell'
	,	update: function() {
			ppsSavePopupChanges();
		}
    });
	ppsInitSubFieldsPromoPopup();
	// Standard subscribe fields edit dialog
	ppsInitStandardSubFieldsPopup()
	// Standard subscribe fields toolbar
	jQuery('#ppoPopupSubFields .ppsSubFieldShell').each(function(){
		_ppsSfInitStandardFieldToolbar(jQuery(this));
	});
	
	jQuery('#ppsPopupEditForm [name="params[tpl][sub_dsbl_dbl_opt_id]"]').change(function(){
		jQuery(this).prop('checked')
			? jQuery('#ppsSubMcSendWelcome').slideDown( g_ppsAnimationSpeed )
			: jQuery('#ppsSubMcSendWelcome').slideUp( g_ppsAnimationSpeed );
	}).change();
});
function _ppsGetMailchimpKey() {
	return jQuery.trim( jQuery('#ppsPopupEditForm').find('[name="params[tpl][sub_mailchimp_api_key]"]').val() );
}
function _ppsUpdateMailchimpLists() {
	if(jQuery('#ppsPopupEditForm').find('[name="params[tpl][sub_dest]"]').val() == 'mailchimp') {
		var key = _ppsGetMailchimpKey();
		if(key && key != '') {
			jQuery('#ppsMailchimpListsShell').hide();
			jQuery('#ppsMailchimpNoApiKey').hide();
			jQuery.sendFormPps({
				msgElID: 'ppsMailchimpMsg'
			,	data: {mod: 'subscribe', action: 'getMailchimpLists', key: key}
			,	onSuccess: function(res) {
					if(!res.error) {
						jQuery('#ppsMailchimpLists').html('');
						var selectedListsIds = ppsPopup && ppsPopup.params.tpl && ppsPopup.params.tpl.sub_mailchimp_lists ? ppsPopup.params.tpl.sub_mailchimp_lists : [];
						//var allListsForSave = [];
						for(var listId in res.data.lists) {
							var selected = toeInArrayPps(listId, selectedListsIds) ? 'selected="selected"' : '';
							jQuery('#ppsMailchimpLists').append('<option '+ selected+ ' value="'+ listId+ '">'+ res.data.lists[ listId ]+ '</option>');
						}
						jQuery('#ppsMailchimpListsShell').show();
						jQuery('#ppsMailchimpLists').chosen().trigger('chosen:updated');
						if(selectedListsIds && selectedListsIds.length)
							_ppsUpdateMailchimpGroups( selectedListsIds );
					}
				}
			});
		} else {
			jQuery('#ppsMailchimpNoApiKey').show();
			jQuery('#ppsMailchimpListsShell').hide();
		}
	}
}
function _ppsUpdateMailchimpGroups(listIds) {
	if(jQuery('#ppsPopupEditForm').find('[name="params[tpl][sub_dest]"]').val() == 'mailchimp') {
		var key = _ppsGetMailchimpKey();
		if(key && key != '') {
			jQuery('#ppsMailchimpGroupsShell').hide();
			jQuery('#ppsMailchimpGroupsNoApiKey').hide();
			jQuery.sendFormPps({
				msgElID: 'ppsMailchimpGroupsMsg'
			,	data: {mod: 'subscribe', action: 'getMailchimpGroups', key: key, listIds: listIds}
			,	onSuccess: function(res) {
					if(!res.error) {
						jQuery('#ppsMailchimpGroups').html('');
						var selectedGroupsIds = ppsPopup && ppsPopup.params.tpl && ppsPopup.params.tpl.sub_mailchimp_groups ? ppsPopup.params.tpl.sub_mailchimp_groups : [];
						if(res.data.groups && res.data.groups != {}) {
							for(var groupId in res.data.groups) {
								var selected = toeInArrayPps(groupId, selectedGroupsIds) ? 'selected="selected"' : '';
								jQuery('#ppsMailchimpGroups').append('<option '+ selected+ ' value="'+ groupId+ '">'+ res.data.groups[ groupId ]+ '</option>');
							}
						}
						jQuery('#ppsMailchimpGroupsShell').show();
						jQuery('#ppsMailchimpGroups').chosen().trigger('chosen:updated').trigger('change');
					}
				}
			});
		} else {
			jQuery('#ppsMailchimpGroupsNoApiKey').show();
			jQuery('#ppsMailchimpGroupsShell').hide();
		}
	}
}
function ppsInitSubFieldsPromoPopup() {
	if(!PPS_DATA.isPro) {
		var $proOptWnd = jQuery('#ppsSubAddFieldWnd').dialog({
			modal:    true
		,	autoOpen: false
		,	width: 580
		,	height: 380
		,	buttons: {
				'Get It': function() {
					window.open( $proOptWnd.find('.ppsPromoImgUrl').attr('href') );
					$proOptWnd.dialog('close');
				}
			,	'Cancel': function() {
					$proOptWnd.dialog('close');
				}
			}
		});
		jQuery('#ppsSubAddFieldBtn').click(function(){
			$proOptWnd.dialog('open');
			return false;
		});
	}
}
function ppsInitStandardSubFieldsPopup() {
	var $wnd = jQuery('#ppsSfEditFieldsStandardWnd').dialog({
		modal:    true
	,	autoOpen: false
	,	width: 540
	,	height: jQuery(window).height() - 60
	,	buttons: {
			'Ok': function() {
				if(_ppsSfSaveStandrdSubField($wnd.serializeAnythingPps(false, true), $wnd)) {
					$wnd.dialog('close');
				}
			}
		,	'Cancel': function() {
				$wnd.dialog('close');
			}
		}
	});
}
function _ppsSfSaveStandrdSubField(data, $wnd) {
	var errors = {};

	if(data.label) {
		var cell = g_ppsSfCurrEditStandardCell ? g_ppsSfCurrEditStandardCell : null;
		_ppsSfFillInSubFieldStandardCell(cell, data);
		ppsSavePopupChanges();
		return true;
	} else
		errors['label'] = toeLangPps('Please enter Label');

	if($wnd && errors) {
		toeProcessAjaxResponsePps({error: true, errors: errors}, false, $wnd, true, {btn: $wnd.find('.ui-button:first')});
	}
	return false;
}
function _ppsSfFillInSubFieldStandardCell(cell, data) {
	cell.find('[name="params[tpl][sub_fields]['+ data.name+ '][label]"]').val( data.label );
	if(data.name != 'email') {
		cell.find('[name="params[tpl][sub_fields]['+ data.name+ '][mandatory]"]').val( data.mandatory ? 1 : 0 );
	}
	cell.find('.ppsSubFieldLabel').html( data.label );
}
function _ppsSfClearStandardEditForm() {
	jQuery('#ppsSfEditFieldsStandardWnd').find('input:not([type="checkbox"])').val('');
	ppsCheckUpdate(jQuery('#ppsSfEditFieldsStandardWnd').find('input[type=checkbox]').removeAttr('checked'));
	jQuery('#ppsSfEditFieldsStandardWnd').find('.ppsSfFieldSelectOptShell:not(#ppsSfFieldSelectOptShellExl)').remove();
}
function _ppsSfInitStandardFieldToolbar(cell) {
	if(parseInt(cell.find('input[name*="custom"]').val()) === 0) {
		var toolbarHtml = jQuery('#ppsSfFieldToolbarStandardExl').clone().removeAttr('id');
		cell.append( toolbarHtml );
		toolbarHtml.find('.ppsSfFieldSettingsBtn').click(function(){
			_ppsSfClearStandardEditForm();
			var $wnd = jQuery('#ppsSfEditFieldsStandardWnd')
			,	name = cell.attr('data-name');	// cell.data('name') didn't worked correctly here

			$wnd.find('[name="name"]').val( name );
			$wnd.find('[name="label"]').val( cell.find('input[name*="label"]').val() );
			parseInt(cell.find('input[name*="mandatory"]').val())
				? $wnd.find('[name="mandatory"]').attr('checked', 'checked')
				: $wnd.find('[name="mandatory"]').removeAttr('checked');
				
			ppsCheckUpdateArea( $wnd );
			g_ppsSfCurrEditStandardCell = cell;
			name == 'email'
				 ? $wnd.find('.ppsSfMandatoryStandardRow').hide()
				 : $wnd.find('.ppsSfMandatoryStandardRow').show();
			$wnd.dialog('open');
			return false;
		});
	}
}
