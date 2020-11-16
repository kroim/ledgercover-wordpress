<?php
class statisticsViewPps extends viewPps {
	public function getPopupEditTab($popup) {
		$group = isset($_COOKIE['pps_stat_group']) ? $_COOKIE['pps_stat_group'] : 'day';
		$allStats = $this->getModel()->getAllForPopupId($popup['id'], array('group' => $group, 'popup' => $popup));
		$allStats = dispatcherPps::applyFilters('popupStatsAdminData', $allStats, $popup);
		$haveData = $allStats ? true : false;
		if($haveData) {
			framePps::_()->getModule('templates')->loadJqplot();
			framePps::_()->getModule('templates')->loadJqGrid();
			framePps::_()->getModule('templates')->loadDatePicker();
			framePps::_()->addScript('admin.statistics.popup.edit', $this->getModule()->getModPath(). 'js/admin.statistics.popup.edit.js');
			framePps::_()->addJSVar('admin.statistics.popup.edit', 'ppsPopupAllStats', $allStats);
			$allSmAction = $this->getModel()->getSmActionForPopup( $popup['id'] );
			$allSmAction = dispatcherPps::applyFilters('popupShareStatsAdminData', $allSmAction, $popup);
			if(!empty($allSmAction)) {
				framePps::_()->addJSVar('admin.statistics.popup.edit', 'ppsPopupAllShareStats', $allSmAction);
			}
		}
		
		$this->assign('haveData', $haveData);
		$this->assign('popup', $popup);
		$this->assign('isPro', framePps::_()->getModule('supsystic_promo')->isPro());
		return parent::getContent('statPopupEditTab');
	}
}
