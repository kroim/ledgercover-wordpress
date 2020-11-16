<?php
class statisticsPps extends modulePps {
	private $_types = array();
	public function init() {
		parent::init();
		dispatcherPps::addFilter('popupEditTabs', array($this, 'addPopupEditTab'), 10, 2);
	}
	public function getTypes() {
		if(empty($this->_types)) {
			$this->_types = array(
				'show' => array('id' => 1, 'label' => __('Displayed', PPS_LANG_CODE)),
				'subscribe' => array('id' => 2, 'label' => __('Subscribed', PPS_LANG_CODE)),
				'share' => array('id' => 3, 'label' => __('Shared', PPS_LANG_CODE)),
				'fb_like' => array('id' => 4, 'label' => __('Facebook Liked', PPS_LANG_CODE)),
				'login' => array('id' => 5, 'label' => __('Login', PPS_LANG_CODE)),
				'registration' => array('id' => 6, 'label' => __('Registration', PPS_LANG_CODE)),
				'age_verify' => array('id' => 7, 'label' => __('Verification', PPS_LANG_CODE)),
				'close' => array('id' => 8, 'label' => __('Closed', PPS_LANG_CODE)),
				'subscribe_error' => array('id' => 9, 'label' => __('Subscribe Error', PPS_LANG_CODE)),
			);
		}
		return $this->_types;
	}
	public function getTypeIdByCode($code) {
		$this->getTypes();
		return isset($this->_types[ $code ]) ? $this->_types[ $code ]['id'] : false;
	}
	public function addPopupEditTab($tabs, $popup) {
		$tabs['ppsPopupStatistics'] = array(
			'title' => __('Statistics', PPS_LANG_CODE), 
			'content' => $this->getView()->getPopupEditTab($popup),
			'fa_icon' => 'fa-line-chart',
			'sort_order' => 60,
		);
		return $tabs;
	}
}