<table class="form-table" style="width: auto;">
	<tr>
		<th scope="row" style="min-width: 250px;">
			<?php _e('Enable Google Analytics', PPS_LANG_CODE)?>
			<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(__('Will enable Google Analytics for your PopUp - and you will be able to check PopUp statistics from your Google Analytics account', PPS_LANG_CODE))?>"></i>
			<?php if(!$this->isPro) {?>
				<span class="ppsProOptMiniLabel"><a target="_blank" href="<?php echo framePps::_()->getModule('supsystic_promo')->generateMainLink('utm_source=plugin&utm_medium=google_analytics&utm_campaign=popup');?>"><?php _e('PRO option', PPS_LANG_CODE)?></a></span>
			<?php }?>
		</th>
		<td>
			<?php echo htmlPps::text('params[tpl][stat_ga_code]', array(
				'value' => (isset($this->popup['params']['tpl']['stat_ga_code']) ? $this->popup['params']['tpl']['stat_ga_code'] : ''),
				'attrs' => 'placeholder="'. __('Your Google Analytics Tracking ID').'" class="ppsProOpt" style="width: 250px;"'
			))?>
		</td>
	</tr>
</table>
<?php if($this->haveData) { ?>
	<?php if(!$this->isPro) { ?>
		<p style="font-size: 15px;">
			<?php printf(__('Want to increase conversion, subscribers and social share? <a target="_blank" href="%s" class="button">Get know - how!</a>', PPS_LANG_CODE), 'http://supsystic.com/what-is-ab-testing/?utm_source=plugin&utm_medium=abtesting&utm_campaign=popup')?>
		</p>
	<?php }?>
	<span class="ppsOptLabel" style="min-height: 30px;">
		<?php _e('Main PopUp Usage Statistics', PPS_LANG_CODE)?>
		<div style="float: right;">
			<a id="ppsPopupStatClearDateBtn" href="#" class="button" style="display: none;"><?php _e('Clear selection')?></a>
			<?php echo htmlPps::text('stat_from_txt', array('placeholder' => __('From', PPS_LANG_CODE), 'attrs' => 'style="font-weight: normal;"'))?>
			<?php echo htmlPps::text('stat_to_txt', array('placeholder' => __('To', PPS_LANG_CODE), 'attrs' => 'style="font-weight: normal;"'))?>
		</div>
	</span>
	<hr>
	<div style="clear: both;"></div>
	<div style="float: left;">
		<a href="#" class="button ppsPopupStatChartTypeBtn" data-type="line">
			<i class="fa fa-line-chart"></i>
		</a>
		<a href="#" class="button ppsPopupStatChartTypeBtn" data-type="bar">
			<i class="fa fa-bar-chart"></i>
		</a>
		<a href="#" class="button ppsPopupStatGraphZoomReset" style="display: none;">
			<i class="fa fa-undo"></i>
			<?php _e('Reset Zoom', PPS_LANG_CODE)?>
		</a>
	</div>
	<div style="float: right;">
		<span style="line-height: 30px;">
			<?php _e('Group by', PPS_LANG_CODE)?>:
			<a href="#" class="button" data-stat-group="hour"><?php _e('Hour', PPS_LANG_CODE)?></a>
			<a href="#" class="button" data-stat-group="day"><?php _e('Day', PPS_LANG_CODE)?></a>
			<a href="#" class="button" data-stat-group="week"><?php _e('Week', PPS_LANG_CODE)?></a>
			<a href="#" class="button" data-stat-group="month"><?php _e('Month', PPS_LANG_CODE)?></a>
			|
			<a href="<?php echo uriPps::mod('statistics', 'getCsv', array('id' => $this->popup['id']))?>" target="_blank" class="button" id="ppsPopupStatExportCsv"><?php _e('Export to CSV', PPS_LANG_CODE)?></a>
			|
		</span>
		<a href="#" id="ppsPopupStatClear" data-id="<?php echo $this->popup['id']?>" class="button">
			<i class="fa fa-trash"></i>
			<?php _e('Clear data', PPS_LANG_CODE)?>
		</a>
	</div>
	<div style="clear: both;"></div>
	<div id="ppsPopupStatGraph"></div>
	<div style="clear: both;"></div>
	<div class="description"><?php _e('You can Zoom In by allocating mouse on Graph area.', PPS_LANG_CODE)?></div>
	<div style="clear: both; padding-bottom: 20px;"></div>
	<div class="supsistic-half-side-box">
		<span class="ppsOptLabel"><?php _e('Ratio of All Actions', PPS_LANG_CODE)?></span>
		<hr>
		<div style="clear: both;"></div>
		<div id="ppsPopupStatAllActionsPie"></div>
		<div id="ppsPopupStatAllActionsNoData" style="display: none;" class="description">
			<?php _e('Once you will have enough different statistics - like shares, subscribes, likes - you will be able to see here which action is used more or less frequently.', PPS_LANG_CODE)?>
		</div>
	</div>
	<div class="supsistic-half-side-box">
		<span class="ppsOptLabel"><?php _e('Ratio of All Social Share', PPS_LANG_CODE)?></span>
		<hr>
		<div style="clear: both;"></div>
		<div id="ppsPopupStatAllSharePie"></div>
		<div id="ppsPopupStatAllShareNoData" style="display: none;" class="description">
			<?php _e('Once you will have enough different statistics about share from PopUp on social media, you will be able to see here which social media is used more or less frequently.', PPS_LANG_CODE)?>
		</div>
	</div>
	<div style="clear: both;"></div>
	<table id="ppsPopupStatTbl"></table>
	<div id="ppsPopupStatTblNav"></div>
<?php } else { ?>
	<h4><?php printf(__('You have no statistics for "%s" PopUp for now. Setup its options and wait until users will view it on your site.', PPS_LANG_CODE), $this->popup['label'])?></h4>
<?php }?>
<table class="form-table" style="width: auto;">
	<tr>
		<th scope="row" style="min-width: 250px;">
			<?php _e('Disable Statistics', PPS_LANG_CODE)?>
			<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(__('You can disable collecting all statistics at all. This is not recomended, but if you need this - you can do it.', PPS_LANG_CODE))?>"></i>
		</th>
		<td>
			<?php echo htmlPps::checkbox('params[tpl][dsbl_stats]', array(
				'checked' => (isset($this->popup['params']['tpl']['dsbl_stats']) ? $this->popup['params']['tpl']['dsbl_stats'] : false),
			))?>
		</td>
	</tr>
</table>