<p class="alert alert-danger">
	<?php printf(__('Edit this ONLY if you know basics of HTML, CSS and have been acquainted with the rules of template editing described <a target="_blank" href="%s">here</a>', PPS_LANG_CODE), 'http://supsystic.com/edit-popup-html-css-code/')?>
</p>
<fieldset>
	<legend><?php _e('CSS code')?></legend>
	<?php echo htmlPps::textarea('css', array('value' => $this->popup['css'], 'attrs' => 'id="ppsPopupCssEditor"'))?>
</fieldset>
<fieldset>
	<legend><?php _e('HTML code')?></legend>
	<?php echo htmlPps::textarea('html', array('value' => $this->popup['html'], 'attrs' => 'id="ppsPopupHtmlEditor"'))?>
</fieldset>