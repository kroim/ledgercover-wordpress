<div class="ppsPopupOptRow">
	<label>
		<a target="_blank" href="<?php echo $this->promoLink?>" class="sup-promolink-input">
			<?php echo htmlPps::checkbox('layered_style_promo', array(
				'checked' => 1,
				//'attrs' => 'disabled="disabled"',
			))?>
			<?php _e('Enable Layered PopUp Style', PPS_LANG_CODE)?>
		</a>
		<a target="_blank" class="button" style="margin-top: -8px;" href="<?php echo $this->promoLink?>"><?php _e('Available in PRO', PPS_LANG_CODE)?></a>
	</label>
	<div class="description"><?php _e('By default all PopUps have modal style: it appears on user screen over the whole site. Layered style allows you to show your PopUp - on selected position: top, bottom, etc. and not over your site - but right near your content.', PPS_LANG_CODE)?><br /><br /><?php _e('Notice: If you enable Layered style - background overlay for popup will not be displayed on frontend.', PPS_LANG_CODE)?></div>
</div>
<span>
	<div class="ppsPopupOptRow">
		<span class="ppsOptLabel"><?php _e('Select position for your PopUp', PPS_LANG_CODE)?></span>
		<br style="clear: both;" />
		<div id="ppsLayeredSelectPosShell">
			<div class="ppsLayeredPosCell" style="width: 30%;" data-pos="top_left"><span class="ppsLayeredPosCellContent"><?php _e('Top Left', PPS_LANG_CODE)?></span></div>
			<div class="ppsLayeredPosCell" style="width: 40%;" data-pos="top"><span class="ppsLayeredPosCellContent"><?php _e('Top', PPS_LANG_CODE)?></span></div>
			<div class="ppsLayeredPosCell" style="width: 30%;" data-pos="top_right"><span class="ppsLayeredPosCellContent"><?php _e('Top Right', PPS_LANG_CODE)?></span></div>
			<br style="clear: both;"/>
			<div class="ppsLayeredPosCell" style="width: 30%;" data-pos="center_left"><span class="ppsLayeredPosCellContent"><?php _e('Center Left', PPS_LANG_CODE)?></span></div>
			<div class="ppsLayeredPosCell" style="width: 40%;" data-pos="center"><span class="ppsLayeredPosCellContent"><?php _e('Center', PPS_LANG_CODE)?></span></div>
			<div class="ppsLayeredPosCell" style="width: 30%;" data-pos="center_right"><span class="ppsLayeredPosCellContent"><?php _e('Center Right', PPS_LANG_CODE)?></span></div>
			<br style="clear: both;"/>
			<div class="ppsLayeredPosCell" style="width: 30%;" data-pos="bottom_left"><span class="ppsLayeredPosCellContent"><?php _e('Bottom Left', PPS_LANG_CODE)?></span></div>
			<div class="ppsLayeredPosCell" style="width: 40%;" data-pos="bottom"><span class="ppsLayeredPosCellContent"><?php _e('Bottom', PPS_LANG_CODE)?></span></div>
			<div class="ppsLayeredPosCell" style="width: 30%;" data-pos="bottom_right"><span class="ppsLayeredPosCellContent"><?php _e('Bottom Right', PPS_LANG_CODE)?></span></div>
			<br style="clear: both;"/>
		</div>
		<?php echo htmlPps::hidden('params[tpl][layered_pos]')?>
	</div>
</span>
<style type="text/css">
	#ppsLayeredSelectPosShell {
		max-width: 560px;
		height: 380px;
	}
	.ppsLayeredPosCell {
		float: left;
		cursor: pointer;
		height: 33.33%;
		text-align: center;
		vertical-align: middle;
		line-height: 110px;
	}
	.ppsLayeredPosCellContent {
		border: 1px solid #a5b6b2;
		margin: 5px;
		display: block;
		font-weight: bold;
		box-shadow: -3px -3px 6px #a5b6b2 inset;
		color: #739b92;
	}
	.ppsLayeredPosCellContent:hover, .ppsLayeredPosCell.active .ppsLayeredPosCellContent {
		background-color: #e7f5f6; /*rgba(165, 182, 178, 0.3);*/
		color: #00575d;
	}
</style>
<script type="text/javascript">
	jQuery(document).ready(function(){
		var proExplainContent = jQuery('#ppsLayeredProExplainWnd').dialog({
			modal:    true
		,	autoOpen: false
		,	width: 460
		,	height: 180
		});
		jQuery('.ppsLayeredPosCell').click(function(){
			proExplainContent.dialog('open');
		});
	});
</script>
<!--PRO explanation Wnd-->
<div id="ppsLayeredProExplainWnd" style="display: none;" title="<?php _e('Improve Free version', PPS_LANG_CODE)?>">
	<p>
		<?php printf(__('This functionality and more - is available in PRO version. <a class="button button-primary" target="_blank" href="%s">Get it</a> today for 29$', PPS_LANG_CODE), $this->promoLink)?>
	</p>
</div>