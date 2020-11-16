<?php
class subscribePps extends modulePps {
	private $_destList = array();
	public function init() {
		dispatcherPps::addFilter('popupCss', array($this, 'modifyPopupCss'), 10, 2);
	}
	public function modifyPopupCss($css, $popup) {
		if($popup['original_id'] == 54) {	// Bump! template
			$css .= '#ppsPopupShell_'. $popup['view_id']. ' .g-recaptcha { background-color: transparent; height: auto; }';
		}
		return $css;
	}
	public function getDestList() {
		if(empty($this->_destList)) {
			$this->_destList = dispatcherPps::applyFilters('subDestList', array(
				'wordpress' => array('label' => __('WordPress', PPS_LANG_CODE), 'require_confirm' => true),
				'supsystic' => array('label' => __('Newsletter by Supsystic', PPS_LANG_CODE), 'require_confirm' => false),
				'aweber' => array('label' => __('Aweber', PPS_LANG_CODE)),
				'mailchimp' => array('label' => __('MailChimp', PPS_LANG_CODE), 'require_confirm' => true),
				'mailpoet' => array('label' => __('MailPoet', PPS_LANG_CODE), 'require_confirm' => true),
				//'newsletter' => array('label' => __('Newsletter', PPS_LANG_CODE), 'require_confirm' => true),
				'jetpack' => array('label' => __('Jetpack', PPS_LANG_CODE), 'require_confirm' => true),
			));
		}
		return $this->_destList;
	}
	public function getDestByKey($key) {
		$this->getDestList();
		return isset($this->_destList[ $key ]) ? $this->_destList[ $key ] : false;
	}
	public function generateFormStart($popup, $onlyForceSub = false) {
		$res = '';
		$enbLogin = (isset($popup['params']['tpl']['enb_login']) && !empty($popup['params']['tpl']['enb_login']));
		$enbReg = (isset($popup['params']['tpl']['enb_reg']) && !empty($popup['params']['tpl']['enb_reg']));
		$enbSub = (isset($popup['params']['tpl']['enb_subscribe']) && !empty($popup['params']['tpl']['enb_subscribe']));
		if(($enbLogin || $enbReg)
			&& framePps::_()->getModule('login') && !$onlyForceSub
		) {
			if($enbLogin) {
				$res .= framePps::_()->getModule('login')->generateLoginFormStart( $popup );
			}
			if($enbReg && !$enbLogin) {
				$res .= framePps::_()->getModule('login')->generateRegFormStart( $popup );
			}
		}
		if($enbSub && isset($popup['params']['tpl']['sub_dest']) && !empty($popup['params']['tpl']['sub_dest'])
			&& ($onlyForceSub || (!$enbLogin && !$enbReg))
		) {
			$subDest = $popup['params']['tpl']['sub_dest'];
			$view = $this->getView();
			$generateMethod = 'generateFormStart_'. $subDest;
			if(method_exists($view, $generateMethod)) {
				$res = $view->$generateMethod( $popup );
			} elseif(framePps::_()->getModule( $subDest ) && method_exists(framePps::_()->getModule( $subDest ), 'generateFormStart')) {
				$res = framePps::_()->getModule( $subDest )->generateFormStart( $popup, $subDest );
			} else {
				$res = $view->generateFormStartCommon( $popup, $subDest );
			}
			$res = dispatcherPps::applyFilters('subFormStart', $res, $popup);
		}
		return $res;
	}
	/**
	 * PopUps have only one submit button - so we wil duplicate it here for both LOgi nand Registration forms
	 * @param array $popup Popup object
	 * @return string Script, that will make duplication of submit button - from Login form to Regstration - if both is enabled
	 */
	private function _addDuplicateRegSubmitBtns( $popup, $forSubscribe = false, $isAlreadyLoggedIn = false ) {
		$pref = $forSubscribe ? 'sub' : 'reg';
		$formClass = $forSubscribe ? 'ppsSubscribeForm' : 'ppsRegForm';
		$btnLabel = '';
		if($forSubscribe && isset($popup['params']['tpl']['original_sub_label'])) {
			$btnLabel = $popup['params']['tpl']['original_sub_label'];
		} elseif(isset($popup['params']['tpl'][$pref. '_btn_label']) && !empty($popup['params']['tpl'][$pref. '_btn_label'])) {
			$btnLabel = $popup['params']['tpl'][$pref. '_btn_label'];
		}
		return '<script type="text/javascript">'
		. 'jQuery(function(){ '
			. 'var $originalBtns;'
			. 'var needClone;'
			. ($isAlreadyLoggedIn
			? ('$originalBtns = jQuery("#'. $popup['view_html_id']. '").find(".ppsSubscribeShell input[type=submit]:not(.ppsPopupClose)");'
			. 'needClone = false;')
			: ('$originalBtns = jQuery("#'. $popup['view_html_id']. '").find(".ppsLoginForm input[type=submit]:not(.ppsPopupClose)");'
			. 'if(!$originalBtns || !$originalBtns.length) {'
			. '$originalBtns = jQuery("#'. $popup['view_html_id']. '").find(".ppsRegForm input[type=submit]:not(.ppsPopupClose)");'
			. '}'
			. 'needClone = true;'))
			. 'var $btns = needClone ? $originalBtns.clone() : $originalBtns;'
			. (!empty($btnLabel) 
				? '$btns.attr("value", "'. $btnLabel. '");' 
				: '')
			. 'jQuery("#'. $popup['view_html_id']. '").find(".'. $formClass. '").append( $btns );'
			. ' });'
		. '</script>';
	}
	public function generateFormEnd($popup) {
		$res = '';
		$enbLogin = (isset($popup['params']['tpl']['enb_login']) && !empty($popup['params']['tpl']['enb_login']));
		$enbReg = (isset($popup['params']['tpl']['enb_reg']) && !empty($popup['params']['tpl']['enb_reg']));
		$enbSub = (isset($popup['params']['tpl']['enb_subscribe']) && !empty($popup['params']['tpl']['enb_subscribe']));
		$logRegFormShown = false;
		if(($enbLogin || $enbReg)
			&& framePps::_()->getModule('login')
		) {
			if($enbLogin) {
				$res .= framePps::_()->getModule('login')->generateLoginFormEnd( $popup );
			}
			if($enbReg) {
				if($enbLogin) {
					$res .= framePps::_()->getModule('login')->generateRegFormStart( $popup );
					$res .= framePps::_()->getModule('login')->generateRegFields( $popup );
					$res .= $this->_addDuplicateRegSubmitBtns( $popup );
				}
				$res .= framePps::_()->getModule('login')->generateRegFormEnd( $popup );
			}
			$logRegFormShown = true;
		}
		if($enbSub && isset($popup['params']['tpl']['sub_dest']) && !empty($popup['params']['tpl']['sub_dest'])) {
			if($logRegFormShown) {
				$res .= $this->generateFormStart( $popup, true );
				$res .= $this->generateFields( $popup, true );
				$res .= $this->_addDuplicateRegSubmitBtns( $popup, true, framePps::_()->getModule('login')->isUserLoggedIn() );
			}
			$subDest = $popup['params']['tpl']['sub_dest'];
			$view = $this->getView();
			$generateMethod = 'generateFormEnd_'. $subDest;
			if(method_exists($view, $generateMethod)) {
				$res .= $view->$generateMethod( $popup );
			} elseif(framePps::_()->getModule( $subDest ) && method_exists(framePps::_()->getModule( $subDest ), 'generateFormEnd')) {
				$res .= framePps::_()->getModule( $subDest )->generateFormEnd( $popup );
			} else {
				$res .= $view->generateFormEndCommon( $popup );
			}
			$res = dispatcherPps::applyFilters('subFormEnd', $res, $popup);
		}
		return $res;
	}
	public function loadAdminEditAssets() {
		framePps::_()->addScript('admin.subscribe', $this->getModPath(). 'js/admin.subscribe.js');
	}
	public function getAvailableUserRolesForSelect() {
		global $wp_roles;
		$res = array();
		$allRoles = $wp_roles->roles;
		$editableRoles = apply_filters('editable_roles', $allRoles);
		
		if(!empty($editableRoles)) {
			foreach($editableRoles as $role => $data) {
				if(in_array($role, array('administrator', 'editor'))) continue;
				if($role == 'subscriber') {	// Subscriber - at the begining of array
					$res = array($role => $data['name']) + $res;
				} else {
					$res[ $role ] = $data['name'];
				}
			}
		}
		return $res;
	}
	public function generateFields($popup, $onlyForceSub = false) {
		$resHtml = '';
		$enbLogin = (isset($popup['params']['tpl']['enb_login']) && !empty($popup['params']['tpl']['enb_login']));
		$enbReg = (isset($popup['params']['tpl']['enb_reg']) && !empty($popup['params']['tpl']['enb_reg']));
		$enbSub = (isset($popup['params']['tpl']['enb_subscribe']) && !empty($popup['params']['tpl']['enb_subscribe']));
		$enbRecaptcha = (isset($popup['params']['tpl']['enb_captcha']) && !empty($popup['params']['tpl']['enb_captcha']));
		if(($enbLogin || $enbReg)
			&& framePps::_()->getModule('login') && !$onlyForceSub
		) {
			if($enbLogin) {
				$resHtml .= framePps::_()->getModule('login')->generateLoginFields( $popup );
			}
			if($enbReg && !$enbLogin) {
				$resHtml .= framePps::_()->getModule('login')->generateRegFields( $popup );
			}
		} else {
			if($enbSub) {
				foreach($popup['params']['tpl']['sub_fields'] as $k => $f) {
					if(isset($f['enb']) && $f['enb']) {
						$htmlType = $f['html'];
						$name = $k;
						// Will not work for now - almost all templates detect it in CSS as [type="text"], and there are no styles for [type="email"]
						if($k == 'email') {
							$htmlType = 'email';
						}
						if($popup && isset($popup['params']) 
							&& isset($popup['params']['tpl']['sub_dest'])
							&& $popup['params']['tpl']['sub_dest'] == 'aweber'
							&& !in_array($name, array('name', 'email'))
							&& strpos($name, 'custom ') !== 0
						) {
							$name = 'custom '. $name;	// This need for aweber to identify custom fields
						}
						if($popup && isset($popup['params']) 
							&& isset($popup['params']['tpl']['sub_dest'])
							&& $popup['params']['tpl']['sub_dest'] == 'arpreach'
							&& in_array($name, array('email'))
						) {
							$name .= '_address';	// name for field email for arpreach should be email_address
						}
						$htmlParams = array(
							'placeholder' => $f['label'],
						);
						if($htmlType == 'selectbox' && isset($f['options']) && !empty($f['options'])) {
							$htmlParams['options'] = array();
							foreach($f['options'] as $opt) {
								$htmlParams['options'][ $opt['name'] ] = isset($opt['label']) ? $opt['label'] : $opt['name'];
							}
						}
						if($htmlType == 'mailchimp_lists') {
							$htmlType = 'selectbox';
							$htmlParams['options'] = $this->getModel()->getMailchimpLists(array('key' => $popup['params']['tpl']['sub_mailchimp_api_key']));
						}
						if($htmlType == 'mailchimp_groups_list') {
							$htmlType = 'selectbox';
							$htmlParams['options'] = array();
							if($popup && isset($popup['params']) 
								&& isset($popup['params']['tpl']['sub_mailchimp_groups_full'])
								&& !empty($popup['params']['tpl']['sub_mailchimp_groups_full'])
							) {
								$mcGoups = explode(';', $popup['params']['tpl']['sub_mailchimp_groups_full']);
								foreach($mcGoups as $g) {
									$gIdLabel = explode(':', $g);
									$htmlParams['options'][ $gIdLabel[0] ] = $gIdLabel[1];
								}
							}
						}
						if(isset($f['value']) && !empty($f['value'])) {
							if(isset($f['set_preset']) && !empty($f['set_preset']) && framePps::_()->getModule('sub_fields')) {
								$htmlParams['value'] = framePps::_()->getModule('sub_fields')->generateValuePreset( $f['set_preset'] );
							} else {
								$htmlParams['value'] = do_shortcode( $f['value'] );
							}
						}
						if(isset($f['mandatory']) && !empty($f['mandatory']) && (int)$f['mandatory']) {
							$htmlParams['required'] = true;
						}
						if(in_array($htmlType, array('checkbox'))) {
							$htmlParams['attrs'] = 'style="height: auto; width: auto; margin: 0; padding: 0;"';
						}
						$inputHtml = htmlPps::$htmlType($name, $htmlParams);
						if($htmlType == 'selectbox') {
							$inputHtml = '<label class="ppsSubSelect"><span class="ppsSubSelectLabel">'. $f['label']. ': </span>'. $inputHtml. '</label>';
						} elseif(in_array($htmlType, array('checkbox'))) {
							$inputHtml = '<label class="ppsSubCheck" style="cursor: pointer;">'. $inputHtml. '&nbsp;'. $f['label']. '</label>';
						}
						$resHtml .= $inputHtml;
					}
				}
			}
		}
		if(!empty($resHtml) && $enbRecaptcha && framePps::_()->getModule('sub_fields')) {
			$resHtml .= htmlPps::recaptcha('recap', array(
				'sitekey' => $popup['params']['tpl']['capt_site_key'],
			));
		}
		return $resHtml;
	}
}

