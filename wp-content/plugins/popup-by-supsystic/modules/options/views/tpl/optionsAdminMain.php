<style type="text/css">
	.ppsAdminMainLeftSide {
		width: 56%;
		float: left;
	}
	.ppsAdminMainRightSide {
		width: <?php echo (empty($this->optsDisplayOnMainPage) ? 100 : 40)?>%;
		float: left;
		text-align: center;
	}
	#ppsMainOccupancy {
		box-shadow: none !important;
	}
</style>
<section>
	<div class="supsystic-item supsystic-panel">
		<div id="containerWrapper">
			<?php _e('Main page Go here!!!!', PPS_LANG_CODE)?>
		</div>
		<div style="clear: both;"></div>
	</div>
</section>