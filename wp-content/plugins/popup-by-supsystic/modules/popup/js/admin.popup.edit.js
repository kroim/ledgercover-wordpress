var ppsPopupSaveTimeout = null
,	ppsPopupIsSaving = false
,	ppsTinyMceEditorUpdateBinded = false
,	ppsSaveWithoutPreviewUpdate = false;
jQuery(document).ready(function(){
	jQuery('#ppsPopupEditTabs').wpTabs({
		uniqId: 'ppsPopupEditTabs'
	,	change: function(selector) {
			if(selector == '#ppsPopupEditors') {
				jQuery(selector).find('textarea').each(function(i, el){
					if(typeof(this.CodeMirrorEditor) !== 'undefined') {
						this.CodeMirrorEditor.refresh();
					}
				});
			} else if(selector == '#ppsPopupStatistics' && typeof(ppsDrawPopupCharts) === 'function') {
				ppsDrawPopupCharts();
			}
			if(selector == '#ppsPopupStatistics') {	// Hide preview for statistics tab
				jQuery('#ppsPopupPreview').hide();
			} else {
				jQuery('#ppsPopupPreview').show();
			}
			var tabChangeEvt = str_replace(selector, '#', '')+ '_tabSwitch';
			jQuery(document).trigger( tabChangeEvt, selector );
		}
	});
	jQuery('#ppsPopupEditDesignTabs').wpTabs({
		uniqId: 'ppsPopupEditDesignTabs'
	});
	jQuery('.ppsPopupSaveBtn').click(function(){
		jQuery('#ppsPopupEditForm').submit();
		return false;
	});
	setTimeout(function(){
		jQuery('select.chosen-group').attr('multiple',false).prop('multiple',false);
	}, 500);
	jQuery('#ppsPopupEditForm').submit(function(){
		// Don't save if form isalready submitted
		if(ppsPopupIsSaving) {
			ppsMakeAutoUpdate();
			return false;
		}
		if(!ppsSaveWithoutPreviewUpdate)
			ppsShowPreviewUpdating();
		ppsPopupIsSaving = true;
		var addData = {};
		if(ppsPopup.params.opts_attrs.txt_block_number) {
			for(var i = 0; i < ppsPopup.params.opts_attrs.txt_block_number; i++) {
				var textId = 'params_tpl_txt_'+ i
				,	sendValKey = 'params_tpl_txt_val_'+ i;
				addData[ sendValKey ] = encodeURIComponent( ppsGetTxtEditorVal( textId ) );
			}
		}
		var cssEditor = jQuery('#ppsPopupCssEditor').get(0).CodeMirrorEditor
		,	htmlEditor = jQuery('#ppsPopupHtmlEditor').get(0).CodeMirrorEditor
		,	cssSet = false
		,	htmlSet = false;

		if(cssEditor) {
			if(cssEditor._ppsChanged) {
				jQuery('#ppsPopupCssEditor').val( cssEditor.getValue() );
				cssEditor._ppsChanged = false;
			} else {
				jQuery('#ppsPopupCssEditor').val('');
				cssSet = cssEditor.getValue();
			}
		}
		if(htmlEditor) {
			if(htmlEditor._ppsChanged) {
				jQuery('#ppsPopupHtmlEditor').val( htmlEditor.getValue() );
				htmlEditor._ppsChanged = false;
			} else {
				jQuery('#ppsPopupHtmlEditor').val('');
				htmlSet = htmlEditor.getValue();
			}
		}
		jQuery(this).sendFormPps({
			btn: jQuery('.ppsPopupSaveBtn')
		,	appendData: addData
		,	onSuccess: function(res) {
				ppsPopupIsSaving = false;
				if(!res.error) {
					if(!ppsSaveWithoutPreviewUpdate)
						ppsRefreshPreview();
				}
				ppsSaveWithoutPreviewUpdate = false;
				if(cssSet && cssEditor) {
					jQuery('#ppsPopupCssEditor').val( cssEditor.getValue() );
				}
				if(htmlSet && htmlEditor) {
					jQuery('#ppsPopupHtmlEditor').val( htmlEditor.getValue() );
				}
			}
		});
		return false;
	});

	jQuery('.ppsBgTypeSelect').change(function(){
		var iter = jQuery(this).data('iter');
		jQuery('.ppsBgTypeShell_'+ iter).hide();
		switch(jQuery(this).val()) {
			case 'img':
				jQuery('.ppsBgTypeImgShell_'+ iter).show();
				break;
			case 'color':
				jQuery('.ppsBgTypeColorShell_'+ iter).show();
				break;
		}
	}).change();
	// Fallback for case if library was not loaded
	if(typeof(CodeMirror) !== 'undefined') {
		var cssEditor = CodeMirror.fromTextArea(jQuery('#ppsPopupCssEditor').get(0), {
			mode: 'css'
		,	lineWrapping: true
		,	lineNumbers: true
		,	matchBrackets: true
		,	autoCloseBrackets: true
		});
		jQuery('#ppsPopupCssEditor').get(0).CodeMirrorEditor = cssEditor;
		if(cssEditor.on && typeof(cssEditor.on) == 'function') {
			cssEditor.on('change', function( editor ){
				editor._ppsChanged = true;
				ppsMakeAutoUpdate( 3000 );
			});
		}
		var htmlEditor = CodeMirror.fromTextArea(jQuery('#ppsPopupHtmlEditor').get(0), {
			mode: 'text/html'
		,	lineWrapping: true
		,	lineNumbers: true
		,	matchBrackets: true
		,	autoCloseBrackets: true
		});
		jQuery('#ppsPopupHtmlEditor').get(0).CodeMirrorEditor = htmlEditor;
		if(htmlEditor.on && typeof(htmlEditor.on) == 'function') {
			htmlEditor.on('change', function( editor ){
				editor._ppsChanged = true;
				ppsMakeAutoUpdate( 3000 );
			});
		}
	}
	setTimeout(function(){
		ppsBindTinyMceUpdate();
		if(!ppsTinyMceEditorUpdateBinded) {
			jQuery('.wp-switch-editor.switch-tmce').click(function(){
				setTimeout(ppsBindTinyMceUpdate, 500);
			});
		}
	}, 500);
	// Close btn selection
	jQuery('#ppsPopupCloseBtnList li').click(function(){
		jQuery('#ppsPopupCloseBtnList li').removeClass('active');
		jQuery(this).addClass('active');
		jQuery('#ppsPopupEditForm').find('[name="params[tpl][close_btn]"]').val( jQuery(this).data('key') ).trigger('change');
	});
	if(ppsPopup.params.tpl && ppsPopup.params.tpl.close_btn) {
		jQuery('#ppsPopupCloseBtnList li[data-key="'+ ppsPopup.params.tpl.close_btn+ '"]').addClass('active');
		jQuery('#ppsPopupEditForm').find('[name="params[tpl][close_btn]"]').val( ppsPopup.params.tpl.close_btn );
	}
	// Bullets selection
	jQuery('#ppsPopupBulletsList li').click(function(){
		jQuery('#ppsPopupBulletsList li').removeClass('active');
		jQuery(this).addClass('active');
		jQuery('#ppsPopupEditForm').find('[name="params[tpl][bullets]"]').val( jQuery(this).data('key') ).trigger('change');
	});
	if(ppsPopup.params.tpl && ppsPopup.params.tpl.bullets) {
		jQuery('#ppsPopupBulletsList li[data-key="'+ ppsPopup.params.tpl.bullets+ '"]').addClass('active');
		jQuery('#ppsPopupEditForm').find('[name="params[tpl][bullets]"]').val( ppsPopup.params.tpl.bullets );
	}
	// Some main options can have additional sub-options - "descriptions" - that need to be visible if option is checked
	jQuery('#ppsPopupEditForm').find('input[name="params[main][show_on]"],input[name="params[main][close_on]"],input[name="params[main][show_to]"],input[name="params[main][show_pages]"]').change(function(){
		var name = jQuery(this).attr('name')
		,	value = jQuery(this).val()
		,	nameReplaced = pps_str_replace( pps_str_replace( pps_str_replace(name, '][', '_'), '[', '_'), ']', '_' )
		,	nameValueReplaced = nameReplaced+ value
		,	descShell = jQuery('#ppsOptDesc_'+ nameValueReplaced);
		if(descShell.length) {
			jQuery(this).attr('checked') ? descShell.slideDown( g_ppsAnimationSpeed ) : descShell.slideUp( g_ppsAnimationSpeed );
		}
	}).change();
	// Fallback for case if library was not loaded
	if(!jQuery.fn.chosen) {
		jQuery.fn.chosen = function() {

		};
	}
	jQuery('.chosen').chosen({
		disable_search_threshold: 10
	});
	jQuery('.chosen.chosen-responsive').each(function(){
		jQuery(this).next('.chosen-container').addClass('chosen-responsive');
	});
	jQuery('.chosen[data-chosen-width]').each(function(){
		jQuery(this).next('.chosen-container').css({
			'width': jQuery(this).data('chosen-width')
		});
	});
	// Animation effect change
	jQuery('.ppsPopupAnimEffLabel').each(function(){
		var key = jQuery(this).data('key');
		if(key != 'none') {
			// magictime was old animation lib
			//jQuery(this).addClass('magictime');
			jQuery(this).addClass('animated');
			jQuery(this).mouseover(function(){
				if(!jQuery(this).data('anim-started')) {
					jQuery(this).data('anim-started', 1);
					ppsHideEndlessAnim(jQuery(this), jQuery(this).data('show-class'), jQuery(this).data('hide-class'));
				}
			});
			jQuery(this).mouseout(function(){
				jQuery(this).data('anim-started', 0);
			});
		}
	});
	var animSelector = {
		_forClose: false
	,	init: function() {
			// Events init
			var self = this;
			jQuery('.ppsPopupAnimEff').click(function(){
				var animElement = jQuery(this).find('.ppsPopupAnimEffLabel:first');
				self._setAnim( animElement.data('key') );
				return false;
			});
			jQuery('#ppsOpenCloseAnimSwitchBtn').click(function(){
				self._switchClose();
				return false;
			});
			// First data init
			this._setCloseSwitchBtn();
			var activeAnimKey = ppsPopup.params.tpl && ppsPopup.params.tpl.anim_key ? ppsPopup.params.tpl.anim_key : 'none';
			if(activeAnimKey) {
				this._setAnim(activeAnimKey);
			}
		}
	,	_setAnim: function(key) {
			jQuery('.ppsPopupAnimEff').removeClass('active');
			var animElement = jQuery('.ppsPopupAnimEffLabel[data-key="'+ key+ '"]')
			animElement.parents('.ppsPopupAnimEff:first').addClass('active');
			jQuery('#ppsPopupEditForm').find(this._forClose
					? '[name="params[tpl][anim_close_key]"]'
					: '[name="params[tpl][anim_key]"]').val( key );
			jQuery('#ppsPopupAnimCurrStyle').html( animElement.data('label') );
		}
	,	_switchClose: function() {
			this._forClose = !this._forClose;
			var animKey = jQuery('#ppsPopupEditForm').find(this._forClose
					? '[name="params[tpl][anim_close_key]"]'
					: '[name="params[tpl][anim_key]"]').val();
			if(!animKey || animKey == '')
				animKey = 'none';
			this._setAnim(animKey);
			this._setCloseSwitchBtn();
		}
	,	_setCloseSwitchBtn: function() {
			var $btn = jQuery('#ppsOpenCloseAnimSwitchBtn');
			$btn.html( $btn.data(this._forClose ? 'txt-open' : 'txt-close') );
			jQuery((this._forClose ? '.ppsAnimOpenRow' : '.ppsAnimCloseRow')).hide( g_ppsAnimationSpeed );
			jQuery((this._forClose ? '.ppsAnimCloseRow' : '.ppsAnimOpenRow')).show( g_ppsAnimationSpeed );
		}
	};
	animSelector.init();
	/*jQuery('.ppsPopupAnimEff').click(function(){
		jQuery('.ppsPopupAnimEff').removeClass('active');
		jQuery(this).addClass('active');
		var animElement = jQuery(this).find('.ppsPopupAnimEffLabel:first');
		var key = animElement.data('key');
		jQuery('#ppsPopupEditForm').find('[name="params[tpl][anim_key]"]').val( key ).trigger('change');
		jQuery('#ppsPopupAnimCurrStyle').html( animElement.data('label') );
		return false;
	});*/
	/*var activeAnimKey = ppsPopup.params.tpl && ppsPopup.params.tpl.anim_key ? ppsPopup.params.tpl.anim_key : 'none';
	if(activeAnimKey) {
		var animElement = jQuery('.ppsPopupAnimEffLabel[data-key="'+ activeAnimKey+ '"]')
		animElement.parents('.ppsPopupAnimEff:first').addClass('active');
		jQuery('#ppsPopupEditForm').find('[name="params[tpl][anim_key]"]').val( activeAnimKey );
		jQuery('#ppsPopupAnimCurrStyle').html( animElement.data('label') );
	}*/
	/*var g_ppsAnimForClose = true;
	jQuery('#ppsOpenCloseAnimSwitchBtn').click(function(){
		g_ppsAnimForClose = !g_ppsAnimForClose;

		return false;
	}).trigger('click');*/
	jQuery('.ppsPopupPreviewBtn').click(function(){
		jQuery('html, body').animate({
			scrollTop: jQuery("#ppsPopupPreview").offset().top
		}, 1000);
		return false;
	});
	// Delete btn init
	jQuery('.ppsPopupRemoveBtn').click(function(){
		if(confirm(toeLangPps('Are you sure want to remove this Pop-Up?'))) {
			jQuery.sendFormPps({
				btn: this
			,	data: {mod: 'popup', action: 'remove', id: ppsPopup.id}
			,	onSuccess: function(res) {
					if(!res.error) {
						toeRedirect( ppsAddNewUrl );
					}
				}
			});
		}
		return false;
	});
	// Don't allow users to set more then 100% width
	jQuery('#ppsPopupEditForm').find('[name="params[tpl][width]"]').keyup(function(){
		var measureType = jQuery('#ppsPopupEditForm').find('[name="params[tpl][width_measure]"]:checked').val();
		if(measureType == '%') {
			var currentValue = parseInt( jQuery(this).val() );
			if(currentValue > 100) {
				jQuery(this).val( 100 );
			}
		}
	});
	jQuery('#ppsPopupEditForm').find('[name="params[tpl][width_measure]"]').change(function(){
		if(!jQuery(this).attr('checked'))
			return;
		var widthInput = jQuery('#ppsPopupEditForm').find('[name="params[tpl][width]"]');
		if(jQuery(this).val() == '%') {
			var currentWidth = parseInt(widthInput.val());
			if(currentWidth > 100) {
				widthInput.data('prev-width', currentWidth);
				widthInput.val(100);
			}
		} else if(widthInput.data('prev-width')) {
			widthInput.val( widthInput.data('prev-width') );
		}
	});
	// Show/hide whole blocks after it's enable/disable by special attribute - data-switch-block
	jQuery('input[type=checkbox][data-switch-block]').change(function(){
		var blockToSwitch = jQuery(this).data('switch-block');
		if(jQuery(this).prop('checked')) {
			jQuery('[data-block-to-switch='+ blockToSwitch+ ']').slideDown( g_ppsAnimationSpeed );
		} else {
			jQuery('[data-block-to-switch='+ blockToSwitch+ ']').slideUp( g_ppsAnimationSpeed );
		}
	}).change();
	// Init Save as Copy function
	ppsPopupInitSaveAsCopyDlg();
	// Init Hide IP Dlg
	_ppsPopupHideIpMoveFromText(true);
	ppsPopupInitHideIpDlg();
	// Auto update bind, timeout - to make sure that all options is already setup and triggered required load changes
	setTimeout(function(){
		var autoUpdateBoxes = ['#ppsPopupTpl', '#ppsPopupTexts', '#ppsPopupSubscribe', '#ppsPopupSm']
		,	ignoreInputs = ['#toeSliderInput_paramstplbg_overlay_opacity'].join(',');
		for(var i = 0; i < autoUpdateBoxes.length; i++) {
			jQuery( autoUpdateBoxes[i] ).find('input[type=checkbox],input[type=radio],input[type=hidden],select').not( ignoreInputs ).change(function(){
				ppsMakeAutoUpdate( 1 );
			});
			jQuery( autoUpdateBoxes[i] ).find('input[type=text],textarea').not( ignoreInputs ).keyup(function(){
				ppsMakeAutoUpdate();
			});
		}
	}, 1000);
	jQuery(window).resize(function(){
		ppsAdjustPopupsEditTabs();
	});
	// Switch Off/Onn button
	ppsPopupCheckSwitchActiveBtn();
	jQuery('.ppsPopupSwitchActive').click(function(){
		var newActive = parseInt(ppsPopup.active) ? 0 : 1;
		jQuery.sendFormPps({
			btn: this
		,	data: {mod: 'popup', action: 'switchActive', id: ppsPopup.id, active: newActive}
		,	onSuccess: function(res) {
				if(!res.error) {
					ppsPopup.active = newActive;
					ppsPopupCheckSwitchActiveBtn();
				}
			}
		});
		return false;
	});
	jQuery('#supsystic-breadcrumbs').bind('startSticky', function(){
		var currentPadding = parseInt(jQuery('#ppsPopupMainControllsShell').css('padding-right'));
		jQuery('#ppsPopupMainControllsShell').css('padding-right', currentPadding + 200).attr('data-padding-changed', 'padding is changed in admin.popup.edit.js');
	});
	jQuery('#supsystic-breadcrumbs').bind('stopSticky', function(){
		var currentPadding = parseInt(jQuery('#ppsPopupMainControllsShell').css('padding-right'));
		jQuery('#ppsPopupMainControllsShell').css('padding-right', currentPadding - 200);
	});
	// Change show/hide parameters
	jQuery('.ppsSwitchShowHideOptLink').click(function(e){
		e.stopPropagation();
		jQuery(this).parents('.ppsPopupMainOptLbl:first').find('.ppsSwitchShowHideOptLink').removeClass('active');
		jQuery(this).addClass('active');
		var inputName = jQuery(this).data('input-name')
		,	inputVal = jQuery(this).data('input-value');
		if(inputName) {
			jQuery('#ppsPopupEditForm [name="'+ inputName+ '"]').val( inputVal );
			ppsSavePopupChanges( true );
		}
		return false;
	});
	var parsedShowHideInputNames = [];
	jQuery('.ppsSwitchShowHideOptLink').each(function(){
		var inputName = jQuery(this).data('input-name');
		if(toeInArray(inputName, parsedShowHideInputNames) === -1) {
			var inputVal = jQuery('#ppsPopupEditForm [name="'+ inputName+ '"]').val();
			jQuery(this).parents('.ppsPopupMainOptLbl:first').find('.ppsSwitchShowHideOptLink[data-input-value="'+ inputVal+ '"]').addClass('active');
			parsedShowHideInputNames.push( inputName );
		}
	});
	// Editable PopUp title
	jQuery('#ppsPopupEditableLabelShell').click(function(){
		var isEdit = jQuery(this).data('edit-on');
		if(!isEdit) {
			var $labelHtml = jQuery('#ppsPopupEditableLabel')
			,	$labelTxt = jQuery('#ppsPopupEditableLabelTxt');
			$labelTxt.val( $labelHtml.text() );
			$labelHtml.hide( g_ppsAnimationSpeed );
			$labelTxt.show( g_ppsAnimationSpeed, function(){
				jQuery(this).data('ready', 1);
			});
			jQuery(this).data('edit-on', 1);
		}
	});
	// Time display settings manipulations
	jQuery('#ppsPopupEditForm [name="params[main][enb_show_time]"]').change(function(){
		if(jQuery(this).prop('checked')) {
			jQuery('.ppsTimeDisplayOptsShell').slideDown( g_ppsAnimationSpeed );
		} else {
			jQuery('.ppsTimeDisplayOptsShell').slideUp( g_ppsAnimationSpeed );
		}
	}).change();
	jQuery('.time-choosen').chosen({width: '90px'});
	// Date display settings manipulations
	jQuery('#ppsPopupEditForm [name="params[main][enb_show_date]"]').change(function(){
		if(jQuery(this).prop('checked')) {
			jQuery('.ppsDateDisplayOptsShell').slideDown( g_ppsAnimationSpeed );
		} else {
			jQuery('.ppsDateDisplayOptsShell').slideUp( g_ppsAnimationSpeed );
		}
	}).change();
	// Days display settings manipulations
	jQuery('#ppsPopupEditForm [name="params[main][enb_show_days]"]').change(function(){
		if(jQuery(this).prop('checked')) {
			jQuery('.ppsDaysDisplayOptsShell').slideDown( g_ppsAnimationSpeed );
		} else {
			jQuery('.ppsDaysDisplayOptsShell').slideUp( g_ppsAnimationSpeed );
		}
	}).change();
	jQuery('#ppsPopupEditForm').find('[name="params[main][show_date_from]"],[name="params[main][show_date_to]"]').datepicker();
	// Edit PopUp Label
	jQuery('#ppsPopupEditableLabelTxt').blur(function(){
		ppsFinishEditPopupLabel( jQuery(this).val() );
	}).keydown(function(e){
		if(e.keyCode == 13) {	// Enter pressed
			ppsFinishEditPopupLabel( jQuery(this).val() );
		}
	});
	// Email attach settings
	jQuery('.ppsPopupAddEmailAttachBtn').click(function(){
		ppsAddEmailAttach({
			$parentShell: jQuery(this).parents('.ppsPopupAttachFilesShell:first')
		});
		return false;
	});
	jQuery('.ppsPopupAttachFilesShell').each(function(){
		var $this = jQuery(this)
		,	key = $this.data('key')
		,	filesKey = 'sub_attach_'+ key;
		if(ppsPopup.params
			&& ppsPopup.params.tpl
			&& ppsPopup.params.tpl[ filesKey ]
		) {
			for(var i in ppsPopup.params.tpl[ filesKey ]) {
				if(ppsPopup.params.tpl[ filesKey ][ i ] && ppsPopup.params.tpl[ filesKey ][ i ] != '') {
					ppsAddEmailAttach({
						$parentShell: $this
					,	file: ppsPopup.params.tpl[ filesKey ][ i ]
					});
				}
			}
		}
	});
	// Submit main form by Enter key
	jQuery('#ppsPopupEditForm input[type=text]').keypress(function(e){
		if (e.which == 13) {
			e.preventDefault();
			jQuery('#ppsPopupEditForm').submit();
		}
	});
	// Option, that is depended on Show On Delay option
	jQuery('#ppsPopupEditForm input[name="params[main][show_on_page_load_enb_delay]"]').change(function(){
		jQuery(this).prop('checked')
			? jQuery('.ppsPageGlobalDelayShell').slideDown( g_ppsAnimationSpeed )
			: jQuery('.ppsPageGlobalDelayShell').slideUp( g_ppsAnimationSpeed );
	}).change();
	// Bg overlay types change
	jQuery('#ppsPopupEditForm [name="params[tpl][bg_overlay_type]"]').change(function(){
		var type = jQuery(this).val();
		var $setShells = jQuery('.ppsBgOverlaySets')
			.slideUp( g_ppsAnimationSpeed )
			.filter('.ppsBgOverlaySets_'+ type)
			.slideDown( g_ppsAnimationSpeed );
		if(type == 'snow') {
			$setShells.addClass('ppsSnow');
		}
	}).change();
	// Vimeo extra fuscreen option manpulations
	var $videoUrlInp = jQuery('#ppsPopupEditForm').find('[name="params[tpl][video_url]"]');
	if($videoUrlInp.length) {
		$videoUrlInp.change(function(){
			var videoUrl = jQuery(this).val();
			if(videoUrl.indexOf('vimeo') === -1) {
				jQuery('.ppsVideoVimeoExtraShell').slideUp( g_ppsAnimationSpeed );
			} else {
				jQuery('.ppsVideoVimeoExtraShell').slideDown( g_ppsAnimationSpeed );
			}
		}).change();
	}
	jQuery("#ppsPopupEditTabs .tooltipstered").removeAttr("title");
});
function ppsAddEmailAttach(params) {
	var $parent = params.$parentShell
	,	$newShell = jQuery('#ppsPopupAttachShell').clone().removeAttr('id')
	,	$input = $newShell.find('[name="params[tpl][sub_attach][]"]').removeAttr('disabled')
	,	$fileName = $newShell.find('.ppsPopupAttachFile')
	,	key = $parent.data('key');
	$parent.append( $newShell );
	$input.attr('name', 'params[tpl][sub_attach_'+ key+ '][]');
	var _setFileClb = function( url ) {
		$input.val( url );
		$fileName.html( url );
	};
	$newShell.find('.ppsPopupAttachBtn').click(function(){
		var button = jQuery(this);
		_custom_media = true;
		wp.media.editor.send.attachment = function(props, attachment){
			if ( _custom_media ) {
				_setFileClb( attachment.url );
			} else {
				return _orig_send_attachment.apply( this, [props, attachment] );
			};
		};
		wp.media.editor.open(button);
		return false;
	});
	$newShell.find('.ppsPopupAttachRemoveBtn').click(function(){
		$newShell.remove();
		return false;
	});
	if(params.file) {
		_setFileClb( params.file );
	}
}
jQuery(window).load(function(){
	ppsAdjustPopupsEditTabs();
});
function ppsFinishEditPopupLabel(label) {
	if(jQuery('#ppsPopupEditableLabelShell').data('sending')) return;
	if(!jQuery('#ppsPopupEditableLabelTxt').data('ready')) return;
	jQuery('#ppsPopupEditableLabelShell').data('sending', 1);
	jQuery.sendFormPps({
		btn: jQuery('#ppsPopupEditableLabelShell')
	,	data: {mod: 'popup', action: 'updateLabel', label: label, id: ppsPopup.id}
	,	onSuccess: function(res) {
			if(!res.error) {
				var $labelHtml = jQuery('#ppsPopupEditableLabel')
				,	$labelTxt = jQuery('#ppsPopupEditableLabelTxt');
				$labelHtml.html( jQuery.trim($labelTxt.val()) );
				$labelTxt.hide( g_ppsAnimationSpeed ).data('ready', 0);
				$labelHtml.show( g_ppsAnimationSpeed );
				jQuery('#ppsPopupEditableLabelShell').data('edit-on', 0);
			}
			jQuery('#ppsPopupEditableLabelShell').data('sending', 0);
		}
	});
}
/**
 * Make popup edit tabs - responsive
 * @param {bool} requring is function - called in requring way
 */
function ppsAdjustPopupsEditTabs(requring) {
	jQuery('#ppsPopupEditTabs .supsystic-always-top')
			.outerWidth( jQuery('#ppsPopupEditTabs').width() )
			.attr('data-code-tip', 'Width was set in admin.popup.edit.js - ppsAdjustPopupsEditTabs()');

	var checkTabsNavs = ['#ppsPopupEditTabs .nav-tab-wrapper:first'];
	for(var i = 0; i < checkTabsNavs.length; i++) {
		var tabs = jQuery(checkTabsNavs[i])
		,	delta = 10
		,	lineWidth = tabs.width() + delta
		,	fullCurrentWidth = 0
		,	currentState = '';	//full, text, icons

		if(!tabs.find('.pps-edit-icon').is(':visible')) {
			currentState = 'text';
		} else if(!tabs.find('.ppsPopupTabTitle').is(':visible')) {
			currentState = 'icons';
		} else {
			currentState = 'full';
		}

		tabs.find('.nav-tab').each(function(){
			fullCurrentWidth += jQuery(this).outerWidth();
		});

		if(fullCurrentWidth > lineWidth) {
			switch(currentState) {
				case 'full':
					tabs.find('.pps-edit-icon').hide();
					ppsAdjustPopupsEditTabs(true);	// Maybe we will require to make it more smaller
					break;
				case 'text':
					tabs.find('.pps-edit-icon').show().end().find('.ppsPopupTabTitle').hide();
					break;
				default:
					// Nothing can do - all that can be hidden - is already hidden
					break;
			}
		} else if(fullCurrentWidth < lineWidth && (lineWidth - fullCurrentWidth > 400) && !requring) {
			switch(currentState) {
				case 'icons':
					tabs.find('.pps-edit-icon').hide().end().find('.ppsPopupTabTitle').show();
					break;
				case 'text':
					tabs.find('.pps-edit-icon').show().end().find('.ppsPopupTabTitle').show();
					break;
				default:
					// Nothing can do - all that can be hidden - is already hidden
					break;
			}
		}
	}
}
function ppsShowImgPrev(url, attach, buttonId) {
	var iter = jQuery('#'+ buttonId).data('iter');
	jQuery('.ppsBgImgPrev_'+ iter).attr('src', url);
}
function ppsSetBgOverlayImgPrev( url, attach ) {
	jQuery('#ppsBgOverlayPrev').attr('src', url);
}
function ppsSavePopupChanges(withoutPreviewUpdate) {
	// Triger save
	if(withoutPreviewUpdate)
		ppsSaveWithoutPreviewUpdate = true;
	jQuery('.ppsPopupSaveBtn').click();
}
function ppsRefreshPreview() {
	document.getElementById('ppsPopupPreviewFrame').contentWindow.location.reload();
}
function ppsMakeAutoUpdate(delay) {
	if(parseInt(toeOptionPps('disable_autosave'))) {
		return;	// Autosave disabled in admin area
	}
	delay = delay ? delay : 1500;
	if(ppsPopupSaveTimeout)
		clearTimeout( ppsPopupSaveTimeout );
	ppsPopupSaveTimeout = setTimeout(ppsSavePopupChanges, delay);
}
function ppsBindTinyMceUpdate() {
	if(!ppsTinyMceEditorUpdateBinded && typeof(tinyMCE) !== 'undefined' && tinyMCE.editors && tinyMCE.editors.length) {
		for (var edId in tinyMCE.editors) {
			tinyMCE.editors[edId].onKeyUp.add(function(){
				ppsMakeAutoUpdate();
			});
		}
		ppsTinyMceEditorUpdateBinded = true;
	}
}
function ppsShowPreviewUpdating() {
	this._posSet;
	if(!this._posSet) {
		this._posSet = true;
		jQuery('#ppsPopupPreviewUpdatingMsg').css({
			'left': 'calc(50% - '+ (jQuery('#ppsPopupPreviewUpdatingMsg').width() / 2)+ 'px)'
		});
	}
	jQuery('#ppsPopupPreviewFrame').css({
		'opacity': 0.5
	});
	jQuery('#ppsPopupPreviewUpdatingMsg').slideDown( g_ppsAnimationSpeed );
}
function ppsHidePreviewUpdating() {
	jQuery('#ppsPopupPreviewFrame').show().css({
		'opacity': 1
	});
	jQuery('#ppsPopupPreviewUpdatingMsg').slideUp( 100 );
}
function ppsShowEndlessAnim(element, showClass, hideClass) {
	if(!jQuery(element).data('anim-started')) {
		element.removeClass(showClass).removeClass(hideClass);
		return;
	}
	var animationDuration = parseFloat(jQuery('#ppsPopupEditForm').find('[name="params[tpl][anim_duration]"]').val());
	if(animationDuration && animationDuration > 10) {
		jQuery(element).animationDuration( animationDuration, true );
	} else {
		jQuery(element).animationDuration( 1 );
		animationDuration = 1000;
		jQuery('#ppsPopupEditForm').find('[name="params[tpl][anim_duration]"]').val( animationDuration );
	}
	element.removeClass(hideClass).addClass(showClass);
	setTimeout(function(){
		ppsHideEndlessAnim( element, showClass, hideClass );
	}, animationDuration);
}
function ppsHideEndlessAnim(element, showClass, hideClass) {
	if(!jQuery(element).data('anim-started')) {
		element.removeClass(showClass).removeClass(hideClass);
		return;
	}
	var animationDuration = parseFloat(jQuery('#ppsPopupEditForm').find('[name="params[tpl][anim_duration]"]').val());
	if(animationDuration) {
		jQuery(element).animationDuration( animationDuration, true );
	} else {
		jQuery(element).animationDuration( 1 );
		animationDuration = 1000;
	}
	element.removeClass(showClass).addClass(hideClass);
	setTimeout(function(){
		ppsShowEndlessAnim( element, showClass, hideClass );
	}, animationDuration);
}
function ppsShowTipScreenPopUp(link) {
	var $container = jQuery('<div style="display: none;" title="'+ toeLangPps('How make PopUp appear after click on your content link')+'" />')
	,	$img = jQuery('<img src="'+ jQuery(link).attr('href')+ '" />').load(function(){
		// Show popup after image was loaded - to make it's size according to image size
			var dialog = $container.dialog({
				modal: true
			,	width: this.width + 40
			,	height: this.height + 120
			,	buttons: {
					OK: function() {
						dialog.dialog('close');
					}
				}
			,	close: function() {
					dialog.remove();
				}
			});
	});
	$container.append( $img ).appendTo('body');
}
function ppsPopupInitSaveAsCopyDlg() {
	var $container = jQuery('#ppsPopupSaveAsCopyWnd').dialog({
		modal:    true
	,	autoOpen: false
	,	width: 460
	,	height: 180
	,	buttons:  {
			OK: function() {
				jQuery('#ppsPopupSaveAsCopyForm').submit();
			}
		,	Cancel: function() {
				$container.dialog('close');
			}
		}
	});
	jQuery('#ppsPopupSaveAsCopyForm').submit(function(){
		jQuery(this).sendFormPps({
			msgElID: 'ppsPopupSaveAsCopyMsg'
		,	onSuccess: function(res) {
				if(!res.error && res.data.edit_link) {
					toeRedirect( res.data.edit_link );
				}
			}
		});
		return false;
	});
	jQuery('.ppsPopupCloneBtn').click(function(){
		$container.dialog('open');
		return false;
	});
}
function ppsPopupInitHideIpDlg() {
	var $container = jQuery('#ppsHideForIpWnd').dialog({
		modal:    true
	,	autoOpen: false
	,	width: 400
	,	height: 460
	,	buttons:  {
			OK: function() {
				_ppsPopupHideIpMoveFromText();
				ppsMakeAutoUpdate( 1 );
				$container.dialog('close');
			}
		,	Cancel: function() {
				$container.dialog('close');
			}
		}
	});
	jQuery('#ppsHideForIpBtn').click(function(){
		_ppsPopupHideIpMoveToText();
		$container.dialog('open');
		return false;
	});
}
function _ppsPopupHideIpMoveFromText(notUserOpen) {
	var ips = notUserOpen ? jQuery('#ppsPopupEditForm').find('[name="params[main][hide_for_ips]"]').val() : jQuery('#ppsHideForIpTxt').val()
	var ipsArr = [];
	if(ips) {
		ipsArr = ips.split(notUserOpen ? "," : "\n");
	}
	if(!ipsArr || !ipsArr.length)
		ipsArr = false;
	if(ipsArr) {
		jQuery.map(ipsArr, jQuery.trim);
	}
	if(!notUserOpen)
		jQuery('#ppsPopupEditForm').find('[name="params[main][hide_for_ips]"]').val( ipsArr ? ipsArr.join(',') : '' ).trigger('change');
	jQuery('#ppsHiddenIpStaticList').html(ipsArr
		? ipsArr.length+ ' '+ toeLangPps('IPs are blocked')
		: toeLangPps('No IPs are currently in block list'));
}
function _ppsPopupHideIpMoveToText() {
	var ips = jQuery('#ppsPopupEditForm').find('[name="params[main][hide_for_ips]"]').val()
	,	ipsArr = ips ? ips.split(",") : false;
	jQuery('#ppsHideForIpTxt').val(ipsArr ? ipsArr.join("\n") : '');
}
function ppsPopupCheckSwitchActiveBtn() {
	if(parseInt(ppsPopup.active)) {
		jQuery('.ppsPopupSwitchActive .fa').removeClass('fa-toggle-on').addClass('fa-toggle-off');
		jQuery('.ppsPopupSwitchActive span').html( jQuery('.ppsPopupSwitchActive').data('txt-off') )
	} else {
		jQuery('.ppsPopupSwitchActive .fa').removeClass('fa-toggle-off').addClass('fa-toggle-on');
		jQuery('.ppsPopupSwitchActive span').html( jQuery('.ppsPopupSwitchActive').data('txt-on') );
	}
}
function wpColorPicker_paramstplbg_color_0_change(event, ui) {
	ppsMakeAutoUpdate();
}
function wpColorPicker_paramstplbg_color_1_change(event, ui) {
	ppsMakeAutoUpdate();
}
function wpColorPicker_paramstplbg_color_2_change(event, ui) {
	ppsMakeAutoUpdate();
}
function wpColorPicker_paramstplbg_color_3_change(event, ui) {
	ppsMakeAutoUpdate();
}
function wpColorPicker_paramstplbg_color_4_change(event, ui) {
	ppsMakeAutoUpdate();
}
function wpColorPicker_paramstpllabel_font_color_change(event, ui) {
	if(PPS_DATA.isPro) {
		ppsMakeAutoUpdate();
	} else {
		jQuery('#ppsPopupEditForm [name="params[tpl][label_font_color]"]').trigger('change');
	}
}
function wpColorPicker_paramstpltext_font_color_0_change(event, ui) {
	if(PPS_DATA.isPro) {
		ppsMakeAutoUpdate();
	} else {
		jQuery('#ppsPopupEditForm [name="params[tpl][text_font_color_0]"]').trigger('change');
	}
}
function wpColorPicker_paramstpltext_font_color_1_change(event, ui) {
	if(PPS_DATA.isPro) {
		ppsMakeAutoUpdate();
	} else {
		jQuery('#ppsPopupEditForm [name="params[tpl][text_font_color_1]"]').trigger('change');
	}
}
function wpColorPicker_paramstplfooter_font_color_change(event, ui) {
	if(PPS_DATA.isPro) {
		ppsMakeAutoUpdate();
	} else {
		jQuery('#ppsPopupEditForm [name="params[tpl][footer_font_color]"]').trigger('change');
	}
}
