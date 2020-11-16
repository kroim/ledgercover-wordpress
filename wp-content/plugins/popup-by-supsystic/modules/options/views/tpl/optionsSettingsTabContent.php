<section class="supsystic-bar">
	<ul class="supsystic-bar-controls">
		<li title="<?php _e('Save all options')?>">
			<button class="button button-primary" id="ppsSettingsSaveBtn" data-toolbar-button>
				<i class="fa fa-fw fa-save"></i>
				<?php _e('Save', PPS_LANG_CODE)?>
			</button>
		</li>
	</ul>
	<div style="clear: both;"></div>
	<hr />
</section>
<section>
	<form id="ppsSettingsForm" class="ppsInputsWithDescrForm">
		<div class="supsystic-item supsystic-panel">
			<div id="containerWrapper">
				<table class="form-table">
					<?php foreach($this->options as $optCatKey => $optCatData) { ?>
						<?php if(isset($optCatData['opts']) && !empty($optCatData['opts'])) { ?>
							<?php foreach($optCatData['opts'] as $optKey => $opt) { ?>
								<?php
									$htmlType = isset($opt['html']) ? $opt['html'] : false;
									if(empty($htmlType)) continue;
									$htmlOpts = array('value' => $opt['value'], 'attrs' => 'data-optkey="'. $optKey. '"');
									if(in_array($htmlType, array('selectbox', 'selectlist')) && isset($opt['options'])) {
										if(is_callable($opt['options'])) {
											$htmlOpts['options'] = call_user_func( $opt['options'] );
										} elseif(is_array($opt['options'])) {
											$htmlOpts['options'] = $opt['options'];
										}
									}
									if(isset($opt['pro']) && !empty($opt['pro'])) {
										$htmlOpts['attrs'] .= ' class="ppsProOpt"';
									}
								?>
								<tr
									<?php if(isset($opt['connect']) && $opt['connect']) { ?>
										data-connect="<?php echo $opt['connect'];?>" style="display: none;"
									<?php }?>
								>
									<th scope="row" class="col-w-30perc">
										<?php echo $opt['label']?>
										<?php if(!empty($opt['changed_on'])) {?>
											<br />
											<span class="description">
												<?php
												$opt['value']
													? printf(__('Turned On %s', PPS_LANG_CODE), datePps::_($opt['changed_on']))
													: printf(__('Turned Off %s', PPS_LANG_CODE), datePps::_($opt['changed_on']))
												?>
											</span>
										<?php }?>
										<?php if(isset($opt['pro']) && !empty($opt['pro'])) { ?>
											<span class="ppsProOptMiniLabel">
												<a href="<?php echo $opt['pro']?>" target="_blank">
													<?php _e('PRO option', PPS_LANG_CODE)?>
												</a>
											</span>
										<?php }?>
									</th>
									<td class="col-w-1perc">
										<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html($opt['desc'])?>"></i>
									</td>
									<td class="col-w-1perc">
										<?php echo htmlPps::$htmlType('opt_values['. $optKey. ']', $htmlOpts)?>
									</td>
									<td class="col-w-60perc">
										<div id="ppsFormOptDetails_<?php echo $optKey?>" class="ppsOptDetailsShell">
										<?php switch($optKey) {

										}?>
										<?php
											if(isset($opt['add_sub_opts']) && !empty($opt['add_sub_opts'])) {
												if(is_string($opt['add_sub_opts'])) {
													echo $opt['add_sub_opts'];
												} elseif(is_callable($opt['add_sub_opts'])) {
													echo call_user_func_array($opt['add_sub_opts'], array($this->options));
												}
											}
										?>
										</div>
									</td>
								</tr>
							<?php }?>
						<?php }?>
					<?php }?>
					<tr>
						<th scope="row" class="col-w-30perc"><?php _e('Popup Groups', PPS_LANG_CODE)?></th>
									<td class="col-w-1perc">
										<i class="fa fa-question supsystic-tooltip tooltipstered" title="Here you can create popup groups."></i>
									</td>
									<td class="col-w-60perc">
											<input type="text" class="newPopupGroupInput" id="newPopupGroupInput" placeholder="Select new group title">
											<div class="newPopupGroupAddBtn button button-primary"><?php _e('Add', PPS_LANG_CODE)?></div>
											<div class="popupGroupDivWrapper" style="margin-top:15px;">
												<div class="popupGroupDivExample popupGroupDiv button button-primary" title="Remove group" style="margin-right:15px;">
													<input type="hidden" disabled class="popupGroupDivTitleInput" name="opt_values[groups][]" value="Group">
													<span class="popupGroupDivRemove"> <i class="fa fa-close"></i></span>
													<span class="popupGroupDivTitle"> Group</span>
												</div>
												<?php if (!empty($this->options['general']['opts']['groups']['value'])) {?>
													<?php foreach ($this->options['general']['opts']['groups']['value'] as $group) {?>
														<div class="popupGroupDiv button button-primary" title="Remove group" style="margin-right:15px;">
															<input type="hidden" class="popupGroupDivTitleInput" name="opt_values[groups][]" value="<?php echo $group;?>">
															<span class="popupGroupDivRemove"> <i class="fa fa-close"></i></span>
															<span class="popupGroupDivTitle"> <?php echo $group;?></span>
														</div>
													<?php }?>
												<?php }?>
											</div>
									</td>
					</tr>
				</table>
				<div style="clear: both;"></div>
			</div>
		</div>
		<?php echo htmlPps::hidden('mod', array('value' => 'options'))?>
		<?php echo htmlPps::hidden('action', array('value' => 'saveGroup'))?>
	</form>
	<br />
	<a href="<?php echo $this->exportAllSubscribersUrl;?>" class="button"><?php _e('Export all Subscribers', PPS_LANG_CODE)?></a>
</section>
