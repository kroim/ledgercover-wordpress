<?php
class smPps extends modulePps {	//sm == socialmedia
	private $_availableLinks = array();
	private $_availableDesigns = array();
	public function generateHtml($popup) {
		$socialSharingHtml = apply_filters('supsystic_popup_sm_html', '', $popup);
		if(!empty($socialSharingHtml))
			return $socialSharingHtml;
		$res = '';
		$this->getAvailableLinks();
		$this->getAvailableDesigns();
		$currFullUrl = uriPps::getFullUrl();
		$designKey = isset($popup['params']['tpl']['sm_design']) && isset($this->_availableDesigns[ $popup['params']['tpl']['sm_design'] ])
				? $popup['params']['tpl']['sm_design']
				: 'boxy';
		$res .= '<div class="ppsSmLinksShell ppsSmLinksShell_'. $designKey. '">';
		foreach($this->_availableLinks as $lKey => $lData) {
			if(isset($popup['params']['tpl']['enb_sm_'. $lKey]) && !empty($popup['params']['tpl']['enb_sm_'. $lKey])) {
				$res .= '<a target="_blank" class="ppsSmLink '. $lKey. ' '. $designKey. '" data-type="'. $lKey. '" href="'. $lData['share_link']. urlencode($currFullUrl). '"></a>';
			}
		}
		$res .= '<div style="clear: both;"></div>';
		$res .= '</div>';

		return $res;
	}
	public function getAvailableLinks() {
		if(empty($this->_availableLinks)) {
			$this->_availableLinks = array(
				'facebook' => array('label' => __('Facebook', PPS_LANG_CODE), 'share_link' => 'https://www.facebook.com/sharer/sharer.php?u=', 'id' => 1),
				'googleplus' => array('label' => __('Google+', PPS_LANG_CODE), 'share_link' => 'https://plus.google.com/share?url=', 'id' => 2),
				'twitter' => array('label' => __('Twitter', PPS_LANG_CODE), 'share_link' => 'https://twitter.com/home?status=', 'id' => 3),
			);
		}
		return $this->_availableLinks;
	}
	public function getTypeIdByCode($code) {
		$this->getAvailableLinks();
		return isset($this->_availableLinks[ $code ]) ? $this->_availableLinks[ $code ]['id'] : 0;
	}
	public function getTypeById($id) {
		$this->getAvailableLinks();
		$res = array();
		foreach($this->_availableLinks as $code => $type) {
			if($type['id'] == $id) {
				$res = $type;
				$res['code'] = $code;
				return $res;
			}
		}
		return false;
	}
	public function getAvailableDesigns() {
		if(empty($this->_availableDesigns)) {
			$this->_availableDesigns = array(
				'simple' => array('label' => __('Simple', PPS_LANG_CODE)),
				'boxy' => array('label' => __('Boxy', PPS_LANG_CODE)),
			);
		}
		return $this->_availableDesigns;
	}
	public function generateCss($popup) {
		return str_replace('[PPS_MOD_PATH]', $this->getModPath(), file_get_contents( $this->getModDir(). 'sm.css' ));
	}
}

