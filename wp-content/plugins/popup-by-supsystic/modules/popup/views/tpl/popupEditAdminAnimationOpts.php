<span class="ppsOptLabel" style="display: inline; line-height: 30px;">
	<?php _e('Choose PopUp animation style', PPS_LANG_CODE)?>,
	<?php _e('current style', PPS_LANG_CODE)?>: 
	<span id="ppsPopupAnimCurrStyle"></span>
</span>
<a href="#" class="button" id="ppsOpenCloseAnimSwitchBtn" 
	   data-txt-close="<?php _e('Select Close Animation')?>"
	   data-txt-open="<?php _e('Select Open Animation')?>"></a>
<hr /><div style="clear: both;"></div>
<div class="ppsPopupOptRow">
	<div id="ppsPopupAnimOptsShell">
		<?php foreach($this->animationList as $aKey => $aData) { ?>
		<?php if(isset($aData['old']) && $aData['old']) continue;?>
		<div class="ppsPopupAnimEff">
			<div class="ppsPopupAnimEffLabel" 
				data-label="<?php echo $aData['label']?>"
				data-key="<?php echo $aKey?>" 
				<?php if($aKey != 'none') {?>
				data-show-class="<?php echo $aData['show_class']?>"
				data-hide-class="<?php echo $aData['hide_class']?>"
				<?php }?>
			 >
				 <?php echo $aData['label']?>
			</div>
		</div>
		<?php }?>
		<div style="clear: both;"></div>
	</div>
	<?php echo htmlPps::hidden('params[tpl][anim_key]', array('value' => $this->popup['params']['tpl']['anim_key']))?>
	<?php echo htmlPps::hidden('params[tpl][anim_close_key]', array('value' => isset($this->popup['params']['tpl']['anim_close_key']) 
		? $this->popup['params']['tpl']['anim_close_key']
		: ''))?>
</div>
<div class="ppsPopupOptRow ppsAnimOpenRow">
	<label>
		<?php _e('Animation Duration', PPS_LANG_CODE)?>:
		<?php echo htmlPps::text('params[tpl][anim_duration]', array('value' => $this->popup['params']['tpl']['anim_duration']))?>
		<?php _e('miliseconds', PPS_LANG_CODE)?>
	</label>
</div>
<div class="ppsPopupOptRow ppsAnimCloseRow">
	<label>
		<?php _e('Close Animation Duration', PPS_LANG_CODE)?>:
		<?php echo htmlPps::text('params[tpl][anim_close_duration]', array('value' => isset($this->popup['params']['tpl']['anim_close_duration'])
			? $this->popup['params']['tpl']['anim_close_duration'] 
			: ''))?>
		<?php _e('miliseconds', PPS_LANG_CODE)?>
	</label>
</div>