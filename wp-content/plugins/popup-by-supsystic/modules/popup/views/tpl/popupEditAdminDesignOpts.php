<table class="form-table" style="width: auto;">
	<?php if(in_array($this->popup['type'], array(PPS_VIDEO))) {?>
	<tr>
		<th scope="row" class="col-w-1perc">
			<?php _e('Video URL', PPS_LANG_CODE)?>&nbsp;
			<i class="fa fa-question supsystic-tooltip" title="<?php _e('Copy and paste here URL of your video source', PPS_LANG_CODE)?>"></i>
		</th>
		<td class="col-w-1perc">
			<?php echo htmlPps::text('params[tpl][video_url]', array('value' => $this->popup['params']['tpl']['video_url'], 'attrs' => 'style="width: 100%;"'))?>
		</td>
	</tr>
	<tr class="ppsVideoVimeoExtraShell">
		<th scope="row" class="col-w-1perc">
			<?php _e('Vimeo Extra Fullscreen', PPS_LANG_CODE)?>&nbsp;
			<i class="fa fa-question supsystic-tooltip" title="<?php _e('This will open Vimeo videos in extra full screen: user desctop panel and browser navigations will be still visible, but video will be still in fullscreen.', PPS_LANG_CODE)?>"></i>
			<?php if(!$this->isPro) {?>
				<span class="ppsProOptMiniLabel"><a target="_blank" href="<?php echo $this->mainLink. '?utm_source=plugin&utm_medium=vimeo_extra_fullscreen&utm_campaign=popup';?>"><?php _e('PRO option', PPS_LANG_CODE)?></a></span>
			<?php }?>
		</th>
		<td class="col-w-1perc">
			<?php echo htmlPps::checkbox('params[tpl][video_extra_full_screen]', array(
				'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'video_extra_full_screen'),
				'attrs' => 'class="ppsProOpt"',
			))?>
		</td>
	</tr>
	<?php }?>
	<?php if(in_array($this->popup['type'], array(PPS_IFRAME))) {?>
	<tr>
		<th scope="row" class="col-w-1perc">
			<?php _e('iFrame URL', PPS_LANG_CODE)?>&nbsp;
			<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(sprintf(__('Copy and paste here URL of site, that you need to display in PopUp as iFrame. Please note that some sites can block such possibility, you can read more about this <a target="_blank" href="%s">for example here</a>', PPS_LANG_CODE), 'https://developer.mozilla.org/en-US/docs/Web/HTTP/X-Frame-Options'))?>"></i>
		</th>
		<td class="col-w-1perc">
			<?php echo htmlPps::text('params[tpl][iframe_url]', array('value' => $this->popup['params']['tpl']['iframe_url'], 'attrs' => 'style="width: 100%;"'))?>
		</td>
	</tr>
	<?php }?>
	<?php if(in_array($this->popup['type'], array(PPS_PDF))) {?>
	<tr>
		<th scope="row" class="col-w-1perc">
			<?php _e('PDF URL', PPS_LANG_CODE)?>&nbsp;
			<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(__('Copy and paste here URL to your PDF file. You can simply upload it on your server (using FTP for example), then insert URL in this field - and you will see how it work. Enjoy!', PPS_LANG_CODE))?>"></i>
		</th>
		<td class="col-w-1perc">
			<?php echo htmlPps::text('params[tpl][pdf_url]', array('value' => $this->popup['params']['tpl']['pdf_url'], 'attrs' => 'style="width: 100%;"'))?>
		</td>
	</tr>
	<?php }?>
	<tr>
		<th scope="row" class="col-w-1perc">
			<?php _e('Width', PPS_LANG_CODE)?>
		</th>
		<td class="col-w-1perc">
			<?php echo htmlPps::text('params[tpl][width]', array('value' => $this->popup['params']['tpl']['width']))?>
		</td>
		<td class="col-w-1perc" colspan="3">
			<?php if(in_array($this->popup['type'], array(PPS_COMMON, PPS_BAR))) {?>
			<label style="margin-right: 10px;" class="supsystic-tooltip" title="<?php _e('Max width for percentage - is 100', PPS_LANG_CODE)?>">
				<?php echo htmlPps::radiobutton('params[tpl][width_measure]', array('value' => '%', 'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'width_measure', '%')))?>
				<?php _e('Percents', PPS_LANG_CODE)?>
			</label>
			<label>
				<?php echo htmlPps::radiobutton('params[tpl][width_measure]', array('value' => 'px', 'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'width_measure', 'px')))?>
				<?php _e('Pixels', PPS_LANG_CODE)?>
			</label>
			<?php } else {
				echo htmlPps::hidden('params[tpl][width_measure]', array('value' => 'px'));
			}?>
		</td>
	</tr>
	<?php if(in_array($this->popup['type'], array(PPS_FB_LIKE, PPS_VIDEO, PPS_IFRAME, PPS_PDF))) {?>
	<tr>
		<th scope="row" class="col-w-1perc">
			<?php _e('Height', PPS_LANG_CODE)?>
		</th>
		<td class="col-w-1perc">
			<?php echo htmlPps::text('params[tpl][height]', array('value' => $this->popup['params']['tpl']['height']))?>
		</td>
		<td class="col-w-1perc" colspan="3">
			<?php echo htmlPps::hidden('params[tpl][height_measure]', array('value' => 'px')); ?>
		</td>
	</tr>
	<?php if(in_array($this->popup['type'], array(PPS_VIDEO))) {?>
	<tr>
		<th scope="row" class="col-w-1perc">
			<?php _e('Video Autoplay', PPS_LANG_CODE)?>&nbsp;
			<i class="fa fa-question supsystic-tooltip" title="<?php _e('Play video - right after PopUp show', PPS_LANG_CODE)?>"></i>
		</th>
		<td class="col-w-1perc">
			<?php echo htmlPps::checkbox('params[tpl][video_autoplay]', array(
				'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'video_autoplay')
			))?>
		</td>
	</tr>
	<tr>
		<th scope="row" class="col-w-1perc">
			<?php _e('Hide controls', PPS_LANG_CODE)?>&nbsp;
			<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(sprintf(__('Hide standard video player controls. For vimeo videos - you need to change this in your account settings, check <a href="%s" target="_blank">this FAQ</a>.', PPS_LANG_CODE), 'https://vimeo.com/help/faq/sharing-videos/embedding-videos'))?>"></i>
		</th>
		<td class="col-w-1perc">
			<?php echo htmlPps::checkbox('params[tpl][vide_hide_controls]', array(
				'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'vide_hide_controls')
			))?>
		</td>
	</tr>
	<?php /*<tr>
		<th scope="row" class="col-w-1perc">
			<?php _e('Ignore related videos', PPS_LANG_CODE)?>&nbsp;
			<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(sprintf(__('In the end there are list of related videos by default, but you can disable this feature using this option. For vimeo videos - you need to change this in your account settings, check <a href="%s" target="_blank">this FAQ</a>.', PPS_LANG_CODE), 'https://vimeo.com/help/faq/sharing-videos/embedding-videos'))?>"></i>
		</th>
		<td class="col-w-1perc">
			<?php echo htmlPps::checkbox('params[tpl][video_hide_rel]', array(
				'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'video_hide_rel')
			))?>
		</td>
	</tr>*/ ?>
	<?php }?>
		<?php if(in_array($this->popup['type'], array(PPS_FB_LIKE))) {?>
			<?php foreach($this->fbLikeOpts as $fKey => $fData) { ?>
				<?php 
					$html = $fData['html'];
					$htmlParams = array();
					if(in_array($html, array('selectbox', 'selectlist'))) {
						$htmlParams['options'] = $fData['options'];
					}
					if($html == 'checkbox') {
						$htmlParams['checked'] = htmlPps::checkedOpt($this->popup['params']['tpl']['fb_like_opts'], $fKey);
					} else {
						$htmlParams['value'] = isset($this->popup['params']['tpl']['fb_like_opts'][ $fKey ]) 
							? $this->popup['params']['tpl']['fb_like_opts'][ $fKey ] 
							: (isset($fData['def']) ? $fData['def'] : '');
					}
					$htmlParams['attrs'] = '';
					if($fKey == 'href') {
						$htmlParams['attrs'] = 'style="width: 100%"';
					}
					if(in_array($html, array('selectlist'))) {
						$htmlParams['attrs'] = 'class="chosen" data-placeholder="'. __('Select Tabs to Render', PPS_LANG_CODE). '"';
					}
				?>
				<tr>
					<th scope="row" class="col-w-1perc">
						<?php echo $fData['label']?>&nbsp;
						<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html($fData['desc'])?>"></i>
					</th>
					<td class="col-w-1perc" colspan="4">
						<?php echo htmlPps::$html('params[tpl][fb_like_opts]['. $fKey. ']', $htmlParams)?>
					</td>
				</tr>
			<?php }?>
			<tr>
				<th scope="row" class="col-w-1perc">
					<?php _e('Enable Label', PPS_LANG_CODE)?>&nbsp;
					<i class="fa fa-question supsystic-tooltip" title="<?php _e('Will add label at the top of your PopUp', PPS_LANG_CODE)?>"></i>
				</th>
				<td class="col-w-1perc" colspan="4">
					<?php echo htmlPps::checkbox('params[tpl][fb_enb_label]', array(
						'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'fb_enb_label'),
					))?>
				</td>
			</tr>
		<?php }?>
	<?php }?>
	<tr>
		<th scope="row" class="col-w-1perc">
			<?php _e('Background overlay opacity', PPS_LANG_CODE)?>
		</th>
		<td class="col-w-1perc">
			<?php echo htmlPps::slider('params[tpl][bg_overlay_opacity]', array('value' => $this->popup['params']['tpl']['bg_overlay_opacity'], 'min' => 0, 'max' => 1, 'step' => 0.1))?>
		</td>
		<td class="col-w-1perc">
			<?php echo htmlPps::selectbox('params[tpl][bg_overlay_type]', array(
				'options' => array('color' => __('Color', PPS_LANG_CODE), 'img' => __('Image', PPS_LANG_CODE), 'snow' => __('Snow', PPS_LANG_CODE)),
				'value' => (isset($this->popup['params']['tpl']['bg_overlay_type']) ? $this->popup['params']['tpl']['bg_overlay_type'] : 'color'),
				'attrs' => 'id="ppsPopupOverlayTypeSel"',
			));?>
		</td>
		<td class="col-w-1perc">
			<div class="ppsBgOverlaySets ppsBgOverlaySets_color">
				<?php echo htmlPps::colorpicker('params[tpl][bg_overlay_color]', array(
					'value' => (isset($this->popup['params']['tpl']['bg_overlay_color']) ? $this->popup['params']['tpl']['bg_overlay_color'] : '#000'),
				));?>
			</div>
			<div class="ppsBgOverlaySets ppsBgOverlaySets_img">
				<?php echo htmlPps::imgGalleryBtn('params[tpl][bg_overlay_img]', array(
					'onChange' => 'ppsSetBgOverlayImgPrev', 
					'attrs' => 'class="button button-sup-small"', 
					'value' => (isset($this->popup['params']['tpl']['bg_overlay_img']) ? $this->popup['params']['tpl']['bg_overlay_img'] : ''),
				));?>
				<label>
					<?php _e('Position', PPS_LANG_CODE)?>
					<?php echo htmlPps::selectbox('params[tpl][bg_overlay_img_pos]', array(
						'options' => array('stretch' => __('Stretch', PPS_LANG_CODE), 'center' => __('Center', PPS_LANG_CODE), 'tile' => __('Tile', PPS_LANG_CODE)),
						'value' => (isset($this->popup['params']['tpl']['bg_overlay_img_pos']) ? $this->popup['params']['tpl']['bg_overlay_img_pos'] : 'stretch'),
					));?>
				</label>
			</div>
			<div class="ppsBgOverlaySets ppsBgOverlaySets_snow">
				<p style="font-weight: bold; font-style: italic;"><?php _e('Snow effect for your Overlay PopUp. Merry christmas and Happy New Year!')?></p>
			</div>
		</td>
		<td class="ppsBgOverlaySets ppsBgOverlaySets_img col-w-1perc">
			<img src="" style="max-width: 200px;" id="ppsBgOverlayPrev" />
		</td>
	</tr>
	<tr>
		<th scope="row" class="col-w-1perc">
			<?php _e('Disable window scroll', PPS_LANG_CODE)?>
			<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(__('Disable browser window scrolling while PopUp is opened. Good way to disallow viewing your site content before PopUp will be closed.', PPS_LANG_CODE))?>"></i>
		</th>
		<td class="col-w-1perc">
			<?php echo htmlPps::checkbox('params[tpl][dsbl_wnd_scroll]', array(
				'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'dsbl_wnd_scroll'),
			))?>
		</td>
		<td colspan="3"></td>
	</tr>
<?php for($i = 0; $i < $this->popup['params']['opts_attrs']['bg_number']; $i++) { ?>
	<tr class="ppsBgRowShell">
		<th scope="row" class="col-w-1perc">
			<?php 
				$bgNumTitle = $this->popup['params']['opts_attrs']['bg_number'] == 1 ? __('Background', PPS_LANG_CODE) : sprintf(__('Background %d', PPS_LANG_CODE), $i + 1);
				if($this->bgNames && isset($this->bgNames[ $i ]) && !empty($this->bgNames[ $i ])) {
					echo $this->bgNames[ $i ]. '<div class="description">'. $bgNumTitle. '</div>';
				} else {
					echo $bgNumTitle;
				}
			?>
		</th>
		<td class="col-w-1perc">
			<?php echo htmlPps::selectbox('params[tpl][bg_type_'. $i. ']', array('options' => $this->bgTypes, 'value' => $this->popup['params']['tpl']['bg_type_'. $i], 'attrs' => 'data-iter="'. $i. '" class="ppsBgTypeSelect"'))?>
		</td>
		<td class="col-w-1perc ppsBgTypeShell ppsBgTypeShell_<?php echo $i?> ppsBgTypeImgShell_<?php echo $i?>">
			<?php echo htmlPps::imgGalleryBtn('params[tpl][bg_img_'. $i. ']', array('onChange' => 'ppsShowImgPrev', 'attrs' => 'data-iter="'. $i. '" class="button button-sup-small"', 'value' => $this->popup['params']['tpl']['bg_img_'. $i]))?>
		</td>
		<td class="col-w-1perc ppsBgTypeShell ppsBgTypeShell_<?php echo $i?> ppsBgTypeImgShell_<?php echo $i?>" style="padding-top: 10px; min-width: 100px;">
			<img src="" style="max-width: 300px;" class="ppsBgImgPrev_<?php echo $i?>" />
		</td>
		<td class="col-w-1perc ppsBgTypeShell ppsBgTypeShell_<?php echo $i?> ppsBgTypeColorShell_<?php echo $i?>" style="line-height: 40px;">
			<?php echo htmlPps::colorpicker('params[tpl][bg_color_'. $i. ']', array('value' => $this->popup['params']['tpl']['bg_color_'. $i]))?>
		</td>
	</tr>
<?php }?>
	<?php if(!in_array($this->popup['type'], array(PPS_FB_LIKE, PPS_IFRAME, PPS_SIMPLE_HTML, PPS_PDF))) {?>
		<tr>
			<th scope="row" class="col-w-1perc">
				<?php _e('Label Font style', PPS_LANG_CODE)?>
				<?php if(!$this->isPro) {?>
					<span class="ppsProOptMiniLabel"><a target="_blank" href="<?php echo $this->mainLink. '?utm_source=plugin&utm_medium=font_label&utm_campaign=popup';?>"><?php _e('PRO option', PPS_LANG_CODE)?></a></span>
				<?php }?>
			</th>
			<td class="col-w-1perc">
				<?php echo htmlPps::fontsList('params[tpl][font_label]', array(
					'attrs' => 'class="ppsProOpt"',
					'value' => isset($this->popup['params']['tpl']['font_label']) ? $this->popup['params']['tpl']['font_label'] : PPS_DEFAULT,
					'default' => __('Default', PPS_LANG_CODE),
				))?>
			</td>
			<td class="col-w-1perc" colspan="3">
				<?php echo htmlPps::colorpicker('params[tpl][label_font_color]', array(
					'attrs' => 'class="ppsProOpt"',
					'value' => isset($this->popup['params']['tpl']['label_font_color']) ? $this->popup['params']['tpl']['label_font_color'] : '#000000',
				))?>
			</td>
		</tr>
			<?php if($this->popup['params']['opts_attrs']['txt_block_number'] != 0) {?>
				<?php for($i = 0; $i < $this->popup['params']['opts_attrs']['txt_block_number']; $i++) { ?>
					<tr>
						<th scope="row" class="col-w-1perc">
							<?php $this->popup['params']['opts_attrs']['txt_block_number'] == 1 ? _e('Text Font style', PPS_LANG_CODE) : printf(__('Text Font style %d', PPS_LANG_CODE), $i + 1)?>
							<?php if(!$this->isPro) {?>
								<span class="ppsProOptMiniLabel"><a target="_blank" href="<?php echo $this->mainLink. '?utm_source=plugin&utm_medium=font_txt&utm_campaign=popup';?>"><?php _e('PRO option', PPS_LANG_CODE)?></a></span>
							<?php }?>
						</th>
						<td class="col-w-1perc">
							<?php echo htmlPps::fontsList('params[tpl][font_txt_'. $i. ']', array(
								'attrs' => 'class="ppsProOpt"',
								'value' => isset($this->popup['params']['tpl']['font_txt_'. $i]) ? $this->popup['params']['tpl']['font_txt_'. $i] : PPS_DEFAULT,
								'default' => __('Default', PPS_LANG_CODE),
							))?>
						</td>
						<td class="col-w-1perc" colspan="3">
							<?php echo htmlPps::colorpicker('params[tpl][text_font_color_'. $i. ']', array(
								'attrs' => 'class="ppsProOpt"',
								'value' => isset($this->popup['params']['tpl']['text_font_color_'. $i]) ? $this->popup['params']['tpl']['text_font_color_'. $i] : '#000000',
							))?>
						</td>
					</tr>
				<?php }?>
			<?php }?>
		<?php if(!in_array($this->popup['type'], array(PPS_SIMPLE_HTML, PPS_PDF))) {?>
		<tr>
			<th scope="row" class="col-w-1perc">
				<?php _e('Footer Font style', PPS_LANG_CODE)?>
				<?php if(!$this->isPro) {?>
					<span class="ppsProOptMiniLabel"><a target="_blank" href="<?php echo $this->mainLink. '?utm_source=plugin&utm_medium=font_footer&utm_campaign=popup';?>"><?php _e('PRO option', PPS_LANG_CODE)?></a></span>
				<?php }?>
			</th>
			<td class="col-w-1perc">
				<?php echo htmlPps::fontsList('params[tpl][font_footer]', array(
					'attrs' => 'class="ppsProOpt"',
					'value' => isset($this->popup['params']['tpl']['font_footer']) ? $this->popup['params']['tpl']['font_footer'] : PPS_DEFAULT,
					'default' => __('Default', PPS_LANG_CODE),
				))?>
			</td>
			<td class="col-w-1perc" colspan="3">
				<?php echo htmlPps::colorpicker('params[tpl][footer_font_color]', array(
					'attrs' => 'class="ppsProOpt"',
					'value' => isset($this->popup['params']['tpl']['footer_font_color']) ? $this->popup['params']['tpl']['footer_font_color'] : '#000000',
				))?>
			</td>
		</tr>
		<?php }?>
	<?php }?>
<tr>
	<th scope="row" class="col-w-1perc">
		<?php _e('Responsive mode', PPS_LANG_CODE)?>
		<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(__('You can choose PopUp behavior for responsive mode from one of the following. If you don\'t know - what to select - you can just try both and leave most suitable for you.', PPS_LANG_CODE))?>"></i>
	</th>
	<td class="col-w-1perc">
		<label class="supsystic-tooltip" title="<?php _e('PopUp will be fully zoomed for smaller screens', PPS_LANG_CODE)?>">
			<?php echo htmlPps::radiobutton('params[tpl][responsive_mode]', array(
				'value' => 'def',
				'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'responsive_mode', 'def', true),
			))?>
			<?php _e('Full resize', PPS_LANG_CODE)?>
		</label>
		<label class="supsystic-tooltip" title="<?php _e('PopUp will not be zoomed like with prev. mode, it will adapt only Width for users screen size', PPS_LANG_CODE)?>">
			<?php echo htmlPps::radiobutton('params[tpl][responsive_mode]', array(
				'value' => 'width_only',
				'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'responsive_mode', 'width_only'),
			))?>
			<?php _e('Width only', PPS_LANG_CODE)?>
		</label>
	</td>
	<td colspan="3"></td>
</tr>
<tr>
	<th scope="row" class="col-w-1perc">
		<?php _e('Redirect after close', PPS_LANG_CODE)?>
		<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(__('If you want - you can redirect user after PopUp will be closed. Just enter required Redirect URL here - and each time after PopUp will be closed - user will be redirected to that URL. Just leave this field empty - if you don\'t need this functionality in your PopUp.', PPS_LANG_CODE))?>"></i>
	</th>
	<td colspan="4">
		<?php echo htmlPps::text('params[tpl][reidrect_on_close]', array(
			'value' => (isset($this->popup['params']['tpl']['reidrect_on_close']) ? esc_url( $this->popup['params']['tpl']['reidrect_on_close'] ) : ''),
			'attrs' => 'placeholder="http://example.com" style="width: 100%;"',
		))?><br />
		<label>
			<?php echo htmlPps::checkbox('params[tpl][reidrect_on_close_new_wnd]', array(
				'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'reidrect_on_close_new_wnd')))?>
			<?php _e('Open in a new window (tab)', PPS_LANG_CODE)?>
		</label>
		<label class="supsystic-tooltip" title="<?php _e('If you set PopUp to Show On -> Click on certain link, and this link have href parameter - you can redirect your users there after PopUp close.', PPS_LANG_CODE)?>">
			<?php echo htmlPps::checkbox('params[tpl][close_redirect_to_btn_url]', array(
				'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'close_redirect_to_btn_url')))?>
			<?php _e('Redirect to button URL', PPS_LANG_CODE)?>
		</label>
	</td>
</tr>
<?php if(in_array($this->popup['type'], array(PPS_IFRAME))) {?>
<tr>
	<th scope="row" class="col-w-1perc">
		<?php _e('Display only selector', PPS_LANG_CODE)?>
		<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(__('You can leave only required part of your iFrame PopUp by entering here CSS selector of this part. Do not use this option if you don\'t understad basics of CSS and HTML. Also please note that you can use this only if your iframe url have same domain that your - due to permissions of html iframe.', PPS_LANG_CODE))?>"></i>
		<?php if(!$this->isPro) {?>
			<span class="ppsProOptMiniLabel"><a target="_blank" href="<?php echo $this->mainLink. '?utm_source=plugin&utm_medium=iframe_display_only&utm_campaign=popup';?>"><?php _e('PRO option', PPS_LANG_CODE)?></a></span>
		<?php }?>
	</th>
	<td colspan="4">
		<?php echo htmlPps::text('params[tpl][iframe_display_only]', array(
			'value' => (isset($this->popup['params']['tpl']['iframe_display_only']) ? $this->popup['params']['tpl']['iframe_display_only'] : ''),
			'attrs' => 'placeholder="#some-content-element" style="width: 100%;" class="ppsProOpt"',
		))?>
	</td>
</tr>
<?php }?>
<tr>
	<th scope="row" class="col-w-1perc">
		<?php _e('Close button', PPS_LANG_CODE)?>
	</th>
	<td colspan="4">
		<ul id="ppsPopupCloseBtnList" class="ppsListItems">
			<?php foreach($this->closeBtns as $key => $data) { ?>
				<?php if($this->popup['original_id'] == 52 && $this->popup['type'] == 'age_verify' && $key != 'none') continue; ?>
				<li data-key="<?php echo $key?>">
					<?php if(isset($data['img_url'])) {?>
						<img src="<?php echo $data['img_url']?>" />
					<?php } elseif(isset($data['label'])) {
						echo $data['label'];
					}?>
				</li>
			<?php }?>
		</ul>
		<?php echo htmlPps::hidden('params[tpl][close_btn]')?>
	</td>
</tr>
<?php if(in_array($this->popup['type'], array(PPS_COMMON, PPS_BAR))) {?>
<tr>
	<th scope="row" class="col-w-1perc">
		<?php _e('Bullets', PPS_LANG_CODE)?>
	</th>
	<td colspan="4">
		<ul id="ppsPopupBulletsList" class="ppsListItems">
			<?php foreach($this->bullets as $key => $data) { ?>
				<li data-key="<?php echo $key?>">
					<?php if(isset($data['img_url'])) {?>
						<img src="<?php echo $data['img_url']?>" />
					<?php } elseif(isset($data['label'])) {
						echo $data['label'];
					}?>
				</li>
			<?php }?>
		</ul>
		<?php echo htmlPps::hidden('params[tpl][bullets]')?>
	</td>
</tr>
<?php }?>
</table>