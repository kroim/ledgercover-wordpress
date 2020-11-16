<?php if(!$this->contactFormSupported) {
	printf(__('To start using this feature - you need to have Contact Form by Supsystic plugin installed on your site. It\'s free! You can find it <a href="%s" target="_blank" class="button" style="margin-top: -10px;">here</a>, install and active it, then reload this page - and you will be able to select your Contact Form.', PPS_LANG_CODE), admin_url('plugin-install.php?tab=search&s=Contact+Form+by+Supsystic'));
} else { ?>
	<div class="ppsPopupOptRow">
		<label>
			<?php echo htmlPps::checkbox('params[tpl][enb_contact_form]', array(
				'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'enb_contact_form'),
				'attrs' => 'data-switch-block="contactFormShell"',
			))?>
			<?php  _e('Enable Contact Form', PPS_LANG_CODE)?>
		</label>
		<div class="description"><?php _e('Please be advised that this feature will replace your current PopUp forms (Subscribe, Login and Registration) with Contact Form', PPS_LANG_CODE)?></div>
	</div>
	<span data-block-to-switch="contactFormShell">
		<table class="form-table ppsSubShellMainTbl" style="width: auto;">
			<tr>
				<th scope="row">
					<?php _e('Select Contact Form', PPS_LANG_CODE)?>
					<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(__('Contact Form that will handle your contacts.', PPS_LANG_CODE))?>"></i>
				</th>
				<td>
					<?php if(empty($this->contactFormsListForSelect)) {
						printf(__('You have no Contact Forms for now. Create your first form <a href="%s" target="_blank" class="button">here</a>, reload this page - and you will be able to select your Contact Form.', PPS_LANG_CODE), $this->contactFormCreateUrl);
					} else {
						echo htmlPps::selectbox('params[tpl][contact_form]', array(
							'options' => $this->contactFormsListForSelect, 
							'value' => (isset($this->popup['params']['tpl']['contact_form']) ? $this->popup['params']['tpl']['contact_form'] : '')));
					}?>
				</td>
			</tr>

		</table>
	</span>
<?php }
