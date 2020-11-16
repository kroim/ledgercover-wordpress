<div id="ppsPopupEditTabs">
	<section class="supsystic-bar supsystic-sticky sticky-padd-next sticky-save-width sticky-base-width-auto" data-prev-height="#supsystic-breadcrumbs" data-next-padding-add="15">
		<h3 class="nav-tab-wrapper ppsMainTabsNav" style="margin-bottom: 0px; margin-top: 12px;">
			<?php $i = 0;?>
			<?php foreach($this->tabs as $tKey => $tData) { ?>
				<?php
					$iconClass = 'pps-edit-icon';
					if(isset($tData['avoid_hide_icon']) && $tData['avoid_hide_icon']) {
						$iconClass .= '-not-hide';	// We will just exclude it from selector to hide, jQuery.not() - make browser slow down in this case - so better don't use it
					}
				?>
				<a class="nav-tab <?php if($i == 0) { echo 'nav-tab-active'; }?>" href="#<?php echo $tKey?>">
					<?php if(isset($tData['fa_icon'])) { ?>
						<i class="<?php echo $iconClass?> fa <?php echo $tData['fa_icon']?>"></i>
					<?php } elseif(isset($tData['icon_content'])) { ?>
						<i class="<?php echo $iconClass?> fa"><?php echo $tData['icon_content']?></i>
					<?php }?>
					<span class="ppsPopupTabTitle"><?php echo $tData['title']?></span>
				</a>
			<?php $i++; }?>
		</h3>
	</section>
	<section>
		<div class="supsystic-item supsystic-panel" style="padding-left: 10px;">
			<div id="containerWrapper">
				<form id="ppsPopupEditForm">
					<?php foreach($this->tabs as $tKey => $tData) { ?>
						<div id="<?php echo $tKey?>" class="ppsTabContent">
							<?php echo $tData['content']?>
						</div>
					<?php }?>
					<?php if(isset($this->popup['params']['opts_attrs'])) {?>
						<?php foreach($this->popup['params']['opts_attrs'] as $optKey => $attr) {
							echo htmlPps::hidden('params[opts_attrs]['. $optKey. ']', array('value' => $attr));
						}?>
					<?php }?>
					<?php echo htmlPps::hidden('mod', array('value' => 'popup'))?>
					<?php echo htmlPps::hidden('action', array('value' => 'save'))?>
					<?php echo htmlPps::hidden('id', array('value' => $this->popup['id']))?>
					<?php echo htmlPps::nonceForAction('save')?>
				</form>
				<div style="clear: both;"></div>
				<div id="ppsPopupPreview" style="">
					<iframe id="ppsPopupPreviewFrame" width="" height="" frameborder="0" src="" style=""></iframe>
					<script type="text/javascript">
					jQuery('#ppsPopupPreviewFrame').load(function(){
						if(typeof(ppsHidePreviewUpdating) === 'function')
							ppsHidePreviewUpdating();
						var contentDoc = jQuery(this).contents()
						,	popupShell = contentDoc.find('.ppsPopupShell')
						,	paddingSize = 40
						,	newWidth = (jQuery(this).get(0).contentWindow.document.body.scrollWidth + paddingSize)
						,	newHeight = (jQuery(this).get(0).contentWindow.document.body.scrollHeight + paddingSize)
						,	parentWidth = jQuery('#ppsPopupPreview').width()
						,	widthMeasure = jQuery('#ppsPopupEditForm').find('[name="params[tpl][width_measure]"]:checked').val();

						if(widthMeasure == '%') {
							newWidth = parentWidth;
						} else {
							if(newWidth > parentWidth) {
								newWidth = parentWidth;
							}
						}
						jQuery(this).width( newWidth+ 'px' );
						jQuery(this).height( newHeight+ 'px' );
						<?php if(in_array($this->popup['type'], array(PPS_FB_LIKE))) {?>
							jQuery(this).height( '500px' );
						<?php }?>
						var top = 15
						,	left = 0;
						if(typeof(ppsPopup) !== 'undefined') {
							var addMovePopUps = [				// Additional preview PopUps movements
								{id: 11, top: 30}				// START popup
							,	{id: 16, left: 45}				// Pink popup
							,	{id: 20, left: 40, top: 30}		// Discount popup
							,	{id: 54, top: 50}				// Bump! popup
							,	{id: 55, top: 70}				// Subscribe Me Bar popup
							,	{id: 57, top: 20}				// Pyramid popup
							];
							for(var i = 0; i < addMovePopUps.length; i++) {
								if(ppsPopup.id == addMovePopUps[i].id
									|| ppsPopup.original_id == addMovePopUps[i].id
								) {
									if(addMovePopUps[i].top) {
										top = addMovePopUps[i].top;
									}
									if(addMovePopUps[i].left) {
										left = addMovePopUps[i].left;
									}
								}
							}
						}
						popupShell.css({
							'position': 'fixed'
						,	'top': top+ 'px'
						,	'left': left+ 'px'
						});
						contentDoc.click(function(){
							return false;
						});
					}).attr('src', '<?php echo $this->previewUrl?>');
					</script>
				</div>
			</div>
		</div>
	</section>
</div>
<div id="ppsPopupPreviewUpdatingMsg">
	<?php _e('Loading preview...', PPS_LANG_CODE)?>
</div>
<div id="ppsPopupGoToTop">
	<a id="ppsPopupGoToTopBtn" href="#">
		<img src="<?php echo uriPps::_(PPS_IMG_PATH)?>pointer-up.png" /><br />
		<?php _e('Back to top', PPS_LANG_CODE)?>
	</a>
</div>
<?php dispatcherPps::doAction('afterPopupEdit', $this->popup);?>