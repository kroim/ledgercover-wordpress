<style type="text/css">
	.ppsDeactivateDescShell {
		display: none;
		margin-left: 25px;
		margin-top: 5px;
	}
	.ppsDeactivateReasonShell {
		display: block;
		margin-bottom: 10px;
	}
	#ppsDeactivateWnd input[type="text"],
	#ppsDeactivateWnd textarea {
		width: 100%;
	}
	#ppsDeactivateWnd h4 {
		line-height: 1.53em;
	}
	#ppsDeactivateWnd + .ui-dialog-buttonpane .ui-dialog-buttonset {
		float: none;
	}
	.ppsDeactivateSkipDataBtn {
		float: right;
		margin-top: 15px;
		text-decoration: none;
		color: #777 !important;
	}
</style>
<div id="ppsDeactivateWnd" style="display: none;" title="<?php _e('Your Feedback', PPS_LANG_CODE)?>">
	<h4><?php printf(__('If you have a moment, please share why you are deactivating %s', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></h4>
	<form id="ppsDeactivateForm">
		<label class="ppsDeactivateReasonShell">
			<?php echo htmlPps::radiobutton('deactivate_reason', array(
				'value' => 'not_working',
			))?>
			<?php _e('Couldn\'t get the plugin to work', PPS_LANG_CODE)?>
			<div class="ppsDeactivateDescShell">
				<?php printf(__('If you have a question, <a href="%s" target="_blank">contact us</a> and will do our best to help you'), 'https://supsystic.com/contact-us/?utm_source=plugin&utm_medium=deactivated_contact&utm_campaign=popup')?>
			</div>
		</label>
		<label class="ppsDeactivateReasonShell">
			<?php echo htmlPps::radiobutton('deactivate_reason', array(
				'value' => 'found_better',
			))?>
			<?php _e('I found a better plugin', PPS_LANG_CODE)?>
			<div class="ppsDeactivateDescShell">
				<?php echo htmlPps::text('better_plugin', array(
					'placeholder' => __('If it\'s possible, specify plugin name', PPS_LANG_CODE),
				))?>
			</div>
		</label>
		<label class="ppsDeactivateReasonShell">
			<?php echo htmlPps::radiobutton('deactivate_reason', array(
				'value' => 'not_need',
			))?>
			<?php _e('I no longer need the plugin', PPS_LANG_CODE)?>
		</label>
		<label class="ppsDeactivateReasonShell">
			<?php echo htmlPps::radiobutton('deactivate_reason', array(
				'value' => 'temporary',
			))?>
			<?php _e('It\'s a temporary deactivation', PPS_LANG_CODE)?>
		</label>
		<label class="ppsDeactivateReasonShell">
			<?php echo htmlPps::radiobutton('deactivate_reason', array(
				'value' => 'other',
			))?>
			<?php _e('Other', PPS_LANG_CODE)?>
			<div class="ppsDeactivateDescShell">
				<?php echo htmlPps::text('other', array(
					'placeholder' => __('What is the reason?', PPS_LANG_CODE),
				))?>
			</div>
		</label>
		<?php echo htmlPps::hidden('mod', array('value' => 'supsystic_promo'))?>
		<?php echo htmlPps::hidden('action', array('value' => 'saveDeactivateData'))?>
	</form>
	<a href="" class="ppsDeactivateSkipDataBtn"><?php _e('Skip & Deactivate', PPS_LANG_CODE)?></a>
</div>