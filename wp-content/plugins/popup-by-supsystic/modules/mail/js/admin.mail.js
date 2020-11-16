jQuery(document).ready(function(){
	jQuery('#ppsMailTestForm').submit(function(){
		jQuery(this).sendFormPps({
			btn: jQuery(this).find('button:first')
		,	onSuccess: function(res) {
				if(!res.error) {
					jQuery('#ppsMailTestForm').slideUp( 300 );
					jQuery('#ppsMailTestResShell').slideDown( 300 );
				}
			}
		});
		return false;
	});
	jQuery('.ppsMailTestResBtn').click(function(){
		var result = parseInt(jQuery(this).data('res'));
		jQuery.sendFormPps({
			btn: this
		,	data: {mod: 'mail', action: 'saveMailTestRes', result: result}
		,	onSuccess: function(res) {
				if(!res.error) {
					jQuery('#ppsMailTestResShell').slideUp( 300 );
					jQuery('#'+ (result ? 'ppsMailTestResSuccess' : 'ppsMailTestResFail')).slideDown( 300 );
				}
			}
		});
		return false;
	});
	jQuery('#ppsMailSettingsForm').submit(function(){
		jQuery(this).sendFormPps({
			btn: jQuery(this).find('button:first')
		});
		return false; 
	});
});