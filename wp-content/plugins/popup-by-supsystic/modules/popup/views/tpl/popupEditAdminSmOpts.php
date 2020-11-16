<div class="ppsPopupOptRow">
	<label>
		<?php echo htmlPps::checkbox('params[tpl][enb_sm]', array(
			'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'enb_sm'),
			'attrs' => 'data-switch-block="smShell"',
		))?>
		<?php  _e('Enable Social Buttons', PPS_LANG_CODE)?>
	</label>
</div>
<span data-block-to-switch="smShell">
	<div class="ppsPopupOptRow">
	<?php foreach($this->smLinks as $smKey => $smData) { ?>
		<label>
			<?php echo htmlPps::checkbox('params[tpl][enb_sm_'. $smKey. ']', array('checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'enb_sm_'. $smKey)));?>
			<?php echo $smData['label']?>
		</label>
	<?php }?>
	</div>
	<div class="ppsPopupOptRow">
		<fieldset class="ppoPopupSubFields" style="padding: 10px;">
			<legend><?php _e('Social links design', PPS_LANG_CODE)?></legend>
			<?php foreach($this->smDesigns as $smKey => $smData) { ?>
				<label>
					<?php echo htmlPps::radiobutton('params[tpl][sm_design]', array('value' => $smKey, 'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'sm_design', $smKey)));?>
					<?php echo $smData['label']?>
				</label>
			<?php }?>
		</fieldset>
	</div>
	<div class="ppsPopupOptRow">
		<h4 style="margin-bottom: 0;"><?php _e('OR', PPS_LANG_CODE)?></h4>
		<table class="form-table" style="width: auto;">
			<tr>
				<td style="padding-left: 0;" colspan="2"><?php _e('Connect <b>around 20 social networks</b> to your PopUp, with various lists of design settings, using our plugin <b>Social Share Buttons by Supsystic</b>', PPS_LANG_CODE)?></td>
			</tr>
			<?php if($this->sssPlugAvailable && isset($this->sssProjectsForSelect) && !empty($this->sssProjectsForSelect)) { ?>
				<tr>
					<th scope="row"><?php _e('Select Social Button Project', PPS_LANG_CODE)?></th>
					<td>
						<?php echo htmlPps::selectbox('params[tpl][use_sss_prj_id]', array(
							'value' => (isset($this->popup['params']['tpl']['use_sss_prj_id']) ? $this->popup['params']['tpl']['use_sss_prj_id'] : ''),
							'options' => $this->sssProjectsForSelect,
						));?>
					</td>
				</tr>
			<?php } elseif($this->sssPlugAvailable && (!isset($this->sssProjectsForSelect) || empty($this->sssProjectsForSelect))) { ?>
				<tr>
					<td style="padding-left: 0;" colspan="2">
						<p style="white-space: normal;"><?php echo (sprintf(__('You have no Social Sharing projects for now. <a href="%s" target="_blank" class="button button-primary">Create your first project</a> - then just reload page with your PopUp settings, and you will see list with available Social Projects for your PopUp.', PPS_LANG_CODE), $this->addProjectUrl))?></p>
					</td>
				</tr>
			<?php } else { ?>
				<tr>
					<td style="padding-left: 0;" colspan="2">
						<p style="white-space: normal;"><?php echo (sprintf(__('You need to install Social Share Buttons by Supsystic to use this feature. <a href="%s" target="_blank" class="button">Install plugin</a> from your admin area, or visit it\'s official page on Wordpress.org <a href="%s" target="_blank">here.</a>', PPS_LANG_CODE), admin_url('plugin-install.php?tab=search&s=Social+Share+Buttons+by+Supsystic'), 'https://wordpress.org/plugins/social-share-buttons-by-supsystic/'))?></p>
					</td>
				</tr>
			<?php }?>
			
		</table>
	</div>
</span>