<?php
class subscribeViewPps extends viewPps {
	public function generateFormStart_wordpress($popup) {
		return $this->_generateFormStartCommon($popup, 'wordpress');
	}
	public function generateFormEnd_wordpress($popup) {
		return $this->_generateFormEndCommon($popup);
	}
	public function generateFormStart_aweber($popup) {
		return '<form class="ppsSubscribeForm ppsSubscribeForm_aweber" method="post" action="https://www.aweber.com/scripts/addlead.pl">';
	}
	public function generateFormEnd_aweber($popup) {
		$redirectUrl = isset($popup['params']['tpl']['sub_redirect_url']) && !empty($popup['params']['tpl']['sub_redirect_url'])
			? $popup['params']['tpl']['sub_redirect_url']
			: false;
		if(!empty($redirectUrl)) {
			$redirectUrl = trim($redirectUrl);
			if(strpos($redirectUrl, 'http') !== 0) {
				$redirectUrl = 'http://'. $redirectUrl;
			}
		}
		if(empty($redirectUrl)) {
			$redirectUrl = uriPps::getFullUrl();
		}
		$res = '';
		$res .= htmlPps::hidden('listname', array('value' => $popup['params']['tpl']['sub_aweber_listname']));
		$res .= htmlPps::hidden('meta_message', array('value' => '1'));
		$res .= htmlPps::hidden('meta_required', array('value' => 'email'));
		$res .= htmlPps::hidden('redirect', array('value' => $redirectUrl));
		if(isset($popup['params']['tpl']['sub_aweber_adtracking']) && !empty($popup['params']['tpl']['sub_aweber_adtracking'])) {
			$res .= htmlPps::hidden('meta_adtracking', array('value' => $popup['params']['tpl']['sub_aweber_adtracking']));
		}
		$res .= '</form>';
		return $res;
	}
	public function generateFormStart_mailchimp($popup) {
		return $this->_generateFormStartCommon($popup, 'mailchimp');
	}
	public function generateFormEnd_mailchimp($popup) {
		return $this->_generateFormEndCommon($popup);
	}
	public function generateFormStart_mailpoet($popup) {
		return $this->_generateFormStartCommon($popup, 'mailpoet');
	}
	public function generateFormEnd_mailpoet($popup) {
		return $this->_generateFormEndCommon($popup);
	}
	public function generateFormStart_newsletter($popup) {
		return $this->_generateFormStartCommon($popup, 'newsletter');
	}
	public function generateFormEnd_newsletter($popup) {
		return $this->_generateFormEndCommon($popup);
	}
	private function _generateFormStartCommon($popup, $key = '') {
		$res = '<form class="ppsSubscribeForm'. (empty($key) ? '' : ' ppsSubscribeForm_'. $key).'" action="'. PPS_SITE_URL. '" method="post">';
		if(in_array($popup['original_id'], array(31))) {	// For those templates - put message up to the form
			$res .= '<div class="ppsSubMsg"></div>';
		}
		return $res;
	}
	private function _generateFormEndCommon($popup) {
		$res = '';
		$res .= htmlPps::hidden('mod', array('value' => 'subscribe'));
		$res .= htmlPps::hidden('action', array('value' => 'subscribe'));
		$res .= htmlPps::hidden('id', array('value' => $popup['id']));
		//$res .= htmlPps::hidden('from_url', array('value' => uriPps::getFullUrl()));
		$res .= htmlPps::hidden('_wpnonce', array('value' => wp_create_nonce('subscribe-'. $popup['id'])));
		if(!in_array($popup['original_id'], array(31))) {	// For those templates - put message up to the form
			$res .= '<div class="ppsSubMsg"></div>';
		}
		$res .= '</form>';
		return $res;
	}
	/**
	 * Public alias for _generateFormStartCommon() method
	 */
	public function generateFormStartCommon($popup, $key = '') {
		return $this->_generateFormStartCommon($popup, $key);
	}
	/**
	 * Public alias for _generateFormEndCommon() method
	 */
	public function generateFormEndCommon($popup) {
		return $this->_generateFormEndCommon($popup);
	}
	public function displaySuccessPage($popup, $res, $forReg = false, $subscribedUrl = '') {
		$this->assign('popup', $popup);
		$this->assign('res', $res);
		$this->assign('forReg', $forReg);
		$this->assign('redirectUrl', $subscribedUrl);
		parent::display('subSuccessPage');
	}
}
