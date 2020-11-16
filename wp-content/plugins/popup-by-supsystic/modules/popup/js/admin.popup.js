var g_ppsPromoTplSelected = false;
jQuery(document).ready(function(){
	var hideForLoggedIn = jQuery('[name="params[main][hide_for_logged_in]"]')
	,	showForLoggedIn = jQuery('[name="params[main][show_for_logged_in]"]');

	hideForLoggedIn.on('change', function() {
		if(jQuery(this).is(':checked')) {
			showForLoggedIn.prop('checked', false);
			showForLoggedIn.attr('disabled', true);
		} else {
			showForLoggedIn.attr('disabled', false);
		}
		ppsCheckUpdate(showForLoggedIn);
	}).trigger('change');
	showForLoggedIn.on('change', function() {
		if(jQuery(this).is(':checked')) {
			hideForLoggedIn.attr('disabled', true);
		} else {
			hideForLoggedIn.attr('disabled', false);
		}
		ppsCheckUpdate(hideForLoggedIn);
	}).trigger('change');
	if(typeof(ppsOriginalPopup) !== 'undefined') {	// Just changing template - for existing popup
		ppsInitChangePopupDialog();
	} else {			// Creating new popup
		ppsInitCreatePopupDialog();
	}
	if(jQuery('.ppsTplPrevImg').length) {	// If on creation page
		ppsAdjustPreviewSize();
		jQuery(window).resize(function(){
			ppsAdjustPreviewSize();
		});
	}
	_ppsInitTypesFilter();
});

function ppsAdjustPreviewSize() {
	var shellWidth = parseInt(jQuery('.popup-list').width())
	,	initialMaxWidth = 400
	,	startFrom = 860
	,	endFrom = 500;
	if(shellWidth < startFrom && shellWidth > endFrom) {
		jQuery('.ppsTplPrevImg').css('max-width', initialMaxWidth - Math.floor((startFrom - shellWidth) / 2));
	} else if(shellWidth < endFrom || shellWidth > startFrom) {
		jQuery('.ppsTplPrevImg').css('max-width', initialMaxWidth);
	}
}
function ppsInitChangePopupDialog() {
	// Pre-select current PopUp template
	jQuery('.popup-list-item[data-id="'+ ppsOriginalPopup.original_id+ '"]').addClass('active');
	var $container = jQuery('#ppsChangeTplWnd').dialog({
		modal:    true
	,	autoOpen: false
	,	width: 460
	,	height: 180
	,	buttons:  {
			OK: function() {
				jQuery('#ppsChangeTplForm').submit();
			}
		,	Cancel: function() {
				$container.dialog('close');
			}
		}
	});
	jQuery('.popup-list-item').click(function(){
		var id = parseInt(jQuery(this).data('id'));
		if(!id) {
			g_ppsPromoTplSelected = true;
			_ppsShowPromoPopupForTpl( this );
			return;
		}
		g_ppsPromoTplSelected = false;
		if(ppsOriginalPopup.original_id == id) {
			var dialog = jQuery('<div />').html(toeLangPps('This is the same template that was used for Pop-Up before.')).dialog({
				modal:    true
			,	width: 480
			,	height: 180
			,	buttons: {
					OK: function() {
						dialog.dialog('close');
					}
				}
			,	close: function() {
					dialog.remove();
				}
			});
			return false;
		}
		jQuery('#ppsChangeTplForm').find('[name=id]').val( ppsOriginalPopup.id );
		jQuery('#ppsChangeTplForm').find('[name=new_tpl_id]').val( id );
		jQuery('#ppsChangeTplNewLabel').html( jQuery(this).find('.ppsTplLabel').html() )
		jQuery('#ppsChangeTplMsg').html('');
		$container.dialog('open');
		return false;
	});
	jQuery('#ppsChangeTplForm').submit(function(){
		jQuery(this).sendFormPps({
			msgElID: 'ppsChangeTplMsg'
		,	onSuccess: function(res) {
				if(!res.error && res.data.edit_link) {
					toeRedirect( res.data.edit_link );
				}
			} 
		});
		return false;
	});
}
function ppsInitCreatePopupDialog() {
	jQuery('.popup-list-item').click(function(){
		var id = parseInt(jQuery(this).data('id'));
		jQuery('.popup-list-item').removeClass('active');
		jQuery(this).addClass('active');
		if(id) {
			jQuery('#ppsCreatePopupForm').find('[name=original_id]').val( jQuery(this).data('id') );
		}
		if(id) {
			g_ppsPromoTplSelected = false;
			return false;
		} else {
			g_ppsPromoTplSelected = true;
		}
	});
	jQuery('#ppsCreatePopupForm').submit(function(){
		if(g_ppsPromoTplSelected) {
			_ppsShowPromoPopupForTpl();
			return false;
		}
		jQuery(this).sendFormPps({
			btn: jQuery(this).find('button')
		,	msgElID: 'ppsCreatePopupMsg'
		,	onSuccess: function(res) {
				if(!res.error && res.data.edit_link) {
					toeRedirect( res.data.edit_link );
				}
			} 
		});
		return false;
	});
}
function _ppsShowPromoPopupForTpl( $tplItem ) {
	var $proOptWnd = ppsGetMainPromoPopup()
	,	selectedTplHref = $tplItem 
			? jQuery($tplItem).find('a.ppsPromoTplBtn').attr('href') 
			: jQuery('.popup-list-item.active a.ppsPromoTplBtn').attr('href');
	jQuery('#ppsOptInProWnd a').attr('href', selectedTplHref);
	$proOptWnd.dialog('open');
	jQuery('#ppsOptWndTemplateTxt').show();
	jQuery('#ppsOptWndOptionTxt').hide();
}
function ppsPopupRemoveRow(id, link) {
	var tblId = jQuery(link).parents('table.ui-jqgrid-btable:first').attr('id');
	if(confirm(toeLangPps('Are you sure want to remove "'+ ppsGetGridColDataById(id, 'label', tblId)+ '" Pop-Up?'))) {
		jQuery.sendFormPps({
			btn: link
		,	data: {mod: 'popup', action: 'remove', id: id}
		,	onSuccess: function(res) {
				if(!res.error) {
					jQuery('#'+ tblId).trigger( 'reloadGrid' );
				}
			}
		});
	}
}
var g_ppsCurrTypeId = 0;	// All by default
function _ppsInitTypesFilter() {
	jQuery('.ppsTypeFilterBtn').click(function(){
		var typeId = parseInt(jQuery(this).data('id'));
		if(g_ppsCurrTypeId == typeId) {
			return false;
		}
		g_ppsCurrTypeId = typeId;
		var fullList = jQuery('.popup-list .popup-list-item');
		jQuery('.ppsTypeFilterBtn').removeClass('active focus');
		jQuery(this).addClass('active focus');
		if(typeId) {
			var fective = jQuery(this).data('fective')
			,	$select = null
			,	$selectNot = null;
			if( fective ) {
				var fectiveArr = fective.split(',')
				,	idSelector = [];
				for(var i = 0; i < fectiveArr.length; i++) {
					idSelector.push('[data-id='+ fectiveArr[ i ]+ ']');
				}
				$select = fullList.filter(idSelector.join(','));
				$selectNot = fullList.not(idSelector.join(','));
			} else {
				$select = fullList.filter('[data-type-id='+ typeId+ ']');
				$selectNot = fullList.filter(':not([data-type-id='+ typeId+ '])');
			}
			$selectNot.animate({
				'opacity': 0
			}, g_ppsAnimationSpeed, function(){
				jQuery(this).hide();
			});
			$select.animate({
				'opacity': 1
			}, g_ppsAnimationSpeed, function(){
				jQuery(this).show();
			});
		} else {
			fullList.show().animate({
				'opacity': 1
			}, g_ppsAnimationSpeed);
		}
		return false;
	});
}