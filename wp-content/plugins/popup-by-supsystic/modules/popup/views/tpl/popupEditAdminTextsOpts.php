<?php if(in_array($this->popup['type'], array(PPS_COMMON, PPS_VIDEO, PPS_AGE_VERIFY, PPS_FULL_SCREEN, PPS_BAR))) {?>
	<div class="ppsPopupOptRow">
		<?php echo htmlPps::checkbox('params[tpl][enb_label]', array('checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'enb_label')))?>
		<?php echo htmlPps::text('params[tpl][label]', array(
			'value' => isset($this->popup['params']['tpl']['label']) ? esc_html($this->popup['params']['tpl']['label']) : '',
			'attrs' => 'class="ppsOptTxtCheck"',
		))?>
	</div>
<?php }?>
<?php for($i = 0; $i < $this->popup['params']['opts_attrs']['txt_block_number']; $i++) { ?>
	<fieldset>
		<legend>
			<label>
				<?php $switchBlock = 'txtBlock_'. $i;?>
				<?php echo htmlPps::checkbox('params[tpl][enb_txt_'. $i. ']', array(
					'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'enb_txt_'. $i),
					'attrs' => 'data-switch-block="'. $switchBlock. '"',
				))?>
				<?php $this->popup['params']['opts_attrs']['txt_block_number'] == 1 ? _e('Text block', PPS_LANG_CODE) : printf(__('Text block %d', PPS_LANG_CODE), $i + 1)?>
			</label>
		</legend>
		<span data-block-to-switch="<?php echo $switchBlock?>">
			<?php wp_editor((isset($this->popup['params']['tpl']['txt_'. $i]) ? $this->popup['params']['tpl']['txt_'. $i] : ''), 
				'params_tpl_txt_'. $i, array(
					'drag_drop_upload' => true,
			))?>
		</span>
	</fieldset>
<?php }?>
<?php if(isset($this->popup['params']['opts_attrs']['btns_number']) && !empty($this->popup['params']['opts_attrs']['btns_number'])) {?>
	<?php for($i = 0; $i < $this->popup['params']['opts_attrs']['btns_number']; $i++) { ?>
		<fieldset>
			<legend>
				<label>
					<?php $switchBlock = 'btnBlock_'. $i;?>
					<?php echo htmlPps::checkbox('params[tpl][enb_btn_'. $i. ']', array(
						'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'enb_btn_'. $i),
						'attrs' => 'data-switch-block="'. $switchBlock. '"',
					))?>
					<?php $this->popup['params']['opts_attrs']['btns_number'] == 1 ? _e('Button block', PPS_LANG_CODE) : printf(__('Button block %d', PPS_LANG_CODE), $i + 1)?>
				</label>
			</legend>
			<span data-block-to-switch="<?php echo $switchBlock?>">
				<table>
					<tr>
						<?php $btnId = 'ppsParamBtnNameTxt_'. $i; ?>
						<td><label for="<?php echo $btnId;?>"><?php _e('Button Name', PPS_LANG_CODE)?>:</label></td>
						<td><?php echo htmlPps::text('params[tpl][btn_txt_'. $i. ']', array(
							'value' => isset($this->popup['params']['tpl']['btn_txt_'. $i]) ? $this->popup['params']['tpl']['btn_txt_'. $i] : '',
							'attrs' => 'id="'. $btnId. '"',
						))?></td>
					</tr>
					<tr>
						<?php $btnId = 'ppsParamBtnUrlTxt_'. $i; ?>
						<td><label for="<?php echo $btnId;?>"><?php _e('Button URL', PPS_LANG_CODE)?>:</label></td>
						<td>
							<?php echo htmlPps::text('params[tpl][btn_url_'. $i. ']', array(
								'value' => isset($this->popup['params']['tpl']['btn_url_'. $i]) ? $this->popup['params']['tpl']['btn_url_'. $i] : '',
								'attrs' => 'id="'. $btnId. '"',
							))?>
							<label>
								<?php _e('or close PopUp on click', PPS_LANG_CODE)?> - 
								<?php echo htmlPps::checkbox('params[tpl][is_close_btn_'. $i. ']', array(
									'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'is_close_btn_'. $i),
								))?>
							</label>
						</td>
					</tr>
					<tr class="supsystic-tooltip" title="<?php _e('If it is primary - then it will be used when we check "Whom to Show" -> "Until user makes an action" condition.', PPS_LANG_CODE)?>">
						<td><?php _e('Use as Primary Action', PPS_LANG_CODE)?>:</td>
						<td>
							<?php echo htmlPps::checkbox('params[tpl][is_btn_primary_'. $i. ']', array(
								'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'is_btn_primary_'. $i),
							))?>
						</td>
					</tr>
				</table>
			</span>
		</fieldset>
	<?php }?>
<?php }?>
<?php if(!in_array($this->popup['type'], array(PPS_SIMPLE_HTML, PPS_FULL_SCREEN, PPS_LOGIN_REGISTER))) {?>
<fieldset>
	<legend>
		<label>
			<?php echo htmlPps::checkbox('params[tpl][enb_foot_note]', array(
				'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'enb_foot_note'),
				'attrs' => 'data-switch-block="txtFooter"',
			))?>
			<?php _e('Foot note', PPS_LANG_CODE)?>
		</label>
	</legend>
	<span data-block-to-switch="txtFooter">
		<?php echo htmlPps::textarea('params[tpl][foot_note]', array(
			'value' => isset($this->popup['params']['tpl']['foot_note']) ? $this->popup['params']['tpl']['foot_note'] : '',
		))?>
	</span>
</fieldset>
<?php }?>