jQuery(document).ready(function(){
	var $deactivateLnk = jQuery('#the-list tr[data-slug="'+ ppsPluginsData.plugSlug+ '"] .row-actions .deactivate a');
	if($deactivateLnk && $deactivateLnk.length) {
		var $deactivateForm = jQuery('#ppsDeactivateForm');
		var $deactivateWnd = jQuery('#ppsDeactivateWnd').dialog({
			modal:    true
		,	autoOpen: false
		,	width: 500
		,	height: 390
		,	buttons:  {
				'Submit & Deactivate': function() {
					$deactivateForm.submit();
				}
			}
		});
		var $wndButtonset = $deactivateWnd.parents('.ui-dialog:first')
			.find('.ui-dialog-buttonpane .ui-dialog-buttonset')
		,	$deactivateDlgBtn = $deactivateWnd.find('.ppsDeactivateSkipDataBtn')
		,	deactivateUrl = $deactivateLnk.attr('href');
		$deactivateDlgBtn.attr('href', deactivateUrl);
		$wndButtonset.append( $deactivateDlgBtn );
		$deactivateLnk.click(function(){
			$deactivateWnd.dialog('open');
			return false;
		});
		
		$deactivateForm.submit(function(){
			var $btn = $wndButtonset.find('button:first');
			$btn.width( $btn.width() );	// Ha:)
			$btn.showLoaderPps();
			jQuery(this).sendFormPps({
				btn: $btn
			,	onSuccess: function(res) {
					toeRedirect( deactivateUrl );
				}
			});
			return false;
		});
		$deactivateForm.find('[name="deactivate_reason"]').change(function(){
			jQuery('.ppsDeactivateDescShell').slideUp( g_ppsAnimationSpeed );
			if(jQuery(this).prop('checked')) {
				var $descShell = jQuery(this).parents('.ppsDeactivateReasonShell:first').find('.ppsDeactivateDescShell');
				if($descShell && $descShell.length) {
					$descShell.slideDown( g_ppsAnimationSpeed );
				}
			}
		});
	}
});