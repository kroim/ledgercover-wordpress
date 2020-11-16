jQuery(document).ready(function(){
	jQuery('#ppsSettingsSaveBtn').click(function(){
		jQuery('#ppsSettingsForm').submit();
		return false;
	});
	jQuery('body').on('click', '.popupGroupDiv', function(){
		jQuery(this).remove();
	});
	jQuery('.popupGroupDivExample').hide();
	jQuery('.newPopupGroupAddBtn').on('click', function(){
		if (jQuery('#newPopupGroupInput').val() != '' && !jQuery('.popupGroupDivTitleInput[value="'+jQuery('#newPopupGroupInput').val()+'"]').length > 0) {
			jQuery('#newPopupGroupInput').css({'border':''});
			var example = jQuery('.popupGroupDivExample').clone();
			var title = jQuery('.newPopupGroupInput').val();
			example.removeClass('popupGroupDivExample');
			example.find('input').attr('disabled',false).prop('disabled',false);
			example.find('.popupGroupDivTitle').html(title);
			example.find('.popupGroupDivTitleInput').val(title);
			jQuery('.popupGroupDivWrapper').append(example);
			example.show();
		} else {
			jQuery('#newPopupGroupInput').css({'border':'1px solid red'});
		}
	});
	jQuery('#ppsSettingsForm').submit(function(){
		jQuery(this).sendFormPps({
			btn: jQuery('#ppsSettingsSaveBtn')
		});
		return false;
	});
	/*Connected options: some options need to be visible  only if in other options selected special value (e.g. if send engine SMTP - show SMTP options)*/
	var $connectOpts = jQuery('#ppsSettingsForm').find('[data-connect]');
	if($connectOpts && $connectOpts.length) {
		var $connectedTo = {};
		$connectOpts.each(function(){
			var connectToData = jQuery(this).data('connect').split(':')
			,	$connectTo = jQuery('#ppsSettingsForm').find('[name="opt_values['+ connectToData[ 0 ]+ ']"]')
			,	connected = $connectTo.data('connected');
			if(!connected) connected = {};
			if(!connected[ connectToData[1] ]) connected[ connectToData[1] ] = [];
			connected[ connectToData[1] ].push( this );
			$connectTo.data('connected', connected);
			if(!$connectTo.data('binded')) {
				$connectTo.change(function(){
					var connected = jQuery(this).data('connected')
					,	value = jQuery(this).val();
					if(connected) {
						for(var connectVal in connected) {
							if(connected[ connectVal ] && connected[ connectVal ].length) {
								var show = connectVal == value;
								for(var i = 0; i < connected[ connectVal ].length; i++) {
									show
									? jQuery(connected[ connectVal ][ i ]).show()
									: jQuery(connected[ connectVal ][ i ]).hide();
								}
							}
						}
					}
				});
				$connectTo.data('binded', 1);
			}
			$connectedTo[ connectToData[ 0 ] ] = $connectTo;
		});
		for(var connectedName in $connectedTo) {
			$connectedTo[ connectedName ].change();
		}
	}
});
