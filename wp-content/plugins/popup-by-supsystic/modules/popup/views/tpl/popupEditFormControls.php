&laquo;<span id="ppsPopupEditableLabelShell" title="<?php _e('Click to Edit', PPS_LANG_CODE)?>">
	<span id="ppsPopupEditableLabel"><?php echo $this->popup['label']?></span>
	<?php echo htmlPps::text('popup_label', array(
		'attrs' => 'id="ppsPopupEditableLabelTxt"'
	))?>
	<i id="ppsPopupLabelEditMsg" class="fa fa-fw fa-pencil"></i>
</span>&raquo;&nbsp;
<span id="ppsPopupMainControllsShell" style="float: right; padding-right: 95px;">
	<button class="button button-primary ppsPopupSaveBtn" title="<?php _e('Save all changes', PPS_LANG_CODE)?>">
		<i class="fa fa-fw fa-save"></i>
		<?php _e('Save', PPS_LANG_CODE)?>
	</button>
	<button class="button button-primary ppsPopupCloneBtn" title="<?php _e('Clone to New PopUp', PPS_LANG_CODE)?>">
		<i class="fa fa-fw fa-files-o"></i>
		<?php _e('Clone', PPS_LANG_CODE)?>
	</button>
	<button class="button button-primary ppsPopupPreviewBtn">
		<i class="fa fa-fw fa-eye"></i>
		<?php _e('Preview', PPS_LANG_CODE)?>
	</button>
	<button class="button button-primary ppsPopupSwitchActive" data-txt-off="<?php _e('Turn Off', PPS_LANG_CODE)?>" data-txt-on="<?php _e('Turn On', PPS_LANG_CODE)?>">
		<i class="fa fa-fw"></i>
		<span></span>
	</button>
	<button class="button button-primary ppsPopupRemoveBtn">
		<i class="fa fa-fw fa-trash-o"></i>
		<?php _e('Delete', PPS_LANG_CODE)?>
	</button>
</span>
<div style="clear: both; height: 0;"></div>
<div id="ppsPopupSaveAsCopyWnd" style="display: none;">
	<form id="ppsPopupSaveAsCopyForm">
		<label>
			<?php _e('New Name', PPS_LANG_CODE)?>:
			<?php echo htmlPps::text('copy_label', array('value' => $this->popup['label']. ' '. __('Copy', PPS_LANG_CODE), 'required' => true))?>
		</label>
		<div id="ppsPopupSaveAsCopyMsg"></div>
		<?php echo htmlPps::hidden('mod', array('value' => 'popup'))?>
		<?php echo htmlPps::hidden('action', array('value' => 'saveAsCopy'))?>
		<?php echo htmlPps::hidden('id', array('value' => $this->popup['id']))?>
	</form>
</div>
