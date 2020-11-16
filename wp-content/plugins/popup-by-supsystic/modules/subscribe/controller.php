<?php
class subscribeControllerPps extends controllerPps {
	public function subscribe() {
		$res = new responsePps();
		$data = reqPps::get('post');
		$id = isset($data['id']) ? (int) $data['id'] : 0;
		if((int)framePps::_()->getModule('options')->get('enable_spam_filter')) {
			$nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : reqPps::getVar('_wpnonce');
			if(!wp_verify_nonce($nonce, 'subscribe-'. $id)) {
				die('Some error with your request.........');
			}
		}
		if($this->getModel()->subscribe(reqPps::get('post'), true)) {
			$dest = $this->getModel()->getDest();
			$destData = $this->getModule()->getDestByKey( $dest );
			$lastPopup = $this->getModel()->getLastPopup();
			$withoutConfirm = (isset($lastPopup['params']['tpl']['sub_ignore_confirm']) && $lastPopup['params']['tpl']['sub_ignore_confirm'])
				|| (isset($lastPopup['params']['tpl']['sub_dsbl_dbl_opt_id']) && $lastPopup['params']['tpl']['sub_dsbl_dbl_opt_id'] && $dest == 'mailchimp');
			if(isset($lastPopup['params']['tpl']['sub_dest']) 
				&& $lastPopup['params']['tpl']['sub_dest'] == 'mailpoet' 
				&& $this->getModel()->getMailPoetVer()
			) {
				$withoutConfirm = !$this->getModel()->mailPoetRequireConfirm();
			}
			// User was subscribed with success before - so just ignore this here
			if(!$withoutConfirm && $this->getModel()->alreadySubscribedSuccess()) {
				$withoutConfirm = true;
			}
			$isSubInternal = $this->getModel()->isSubscribedInternal();
			$forceRequireConfirm = false;
			if(!$isSubInternal && framePps::_()->getModule($dest)) {	// Confirm can be required by other subscribe engines
				$forceRequireConfirm = framePps::_()->getModule($dest)->getModel()->requireConfirm();
			}
			if(($destData && isset($destData['require_confirm']) && $destData['require_confirm'] && !$withoutConfirm) || $forceRequireConfirm)
				$res->addMessage(isset($lastPopup['params']['tpl']['sub_txt_confirm_sent']) 
						? $lastPopup['params']['tpl']['sub_txt_confirm_sent'] : 
						__('Confirmation link was sent to your email address. Check your email!', PPS_LANG_CODE));
			else
				$res->addMessage(isset($lastPopup['params']['tpl']['sub_txt_success'])
						? $lastPopup['params']['tpl']['sub_txt_success']
						: __('Thank you for subscribing!', PPS_LANG_CODE));
			$redirectUrl = isset($lastPopup['params']['tpl']['sub_redirect_url']) && !empty($lastPopup['params']['tpl']['sub_redirect_url'])
					? $lastPopup['params']['tpl']['sub_redirect_url']
					: false;
			if(!empty($redirectUrl)) {
				$res->addData('redirect', uriPps::normal($redirectUrl));
			}
		} else {
			$lastPopup = $this->getModel()->getLastPopup();
			if($lastPopup 
				&& isset($lastPopup['params']['tpl']['sub_redirect_email_exists']) 
				&& !empty($lastPopup['params']['tpl']['sub_redirect_email_exists'])
				&& $this->getModel()->getEmailExists()
			) {
				$this->_setConfirmedCookie($lastPopup['id']);
				$res->addData('emailExistsRedirect', $lastPopup['params']['tpl']['sub_redirect_email_exists']);
			}
			$res->pushError ($this->getModel()->getErrors());
		}
		if(!$res->isAjax()) {
			if(!$res->error()) {
				$popupActions = reqPps::getVar('pps_actions_'. $id, 'cookie');
				if(empty($popupActions)) {
					$popupActions = array();
				}
				$popupActions['subscribe'] = date('m-d-Y H:i:s');
				reqPps::setVar('pps_actions_'. $id, $popupActions, 'cookie', array('expire' => 7 * 24 * 3600));
				framePps::_()->getModule('statistics')->getModel()->add(array(
					'id' => $id,
					'type' => 'subscribe',
				));
			}
			$res->mainRedirect(isset($redirectUrl) && $redirectUrl ? $redirectUrl : '');
		}
		return $res->ajaxExec();
	}
	private function _setConfirmedCookie($popupId) {
		reqPps::setVar('pps_email_confirmed_'. $popupId, '1', 'cookie', array('expire' => 999 * 24 * 60 * 60));
	}
	public function confirm() {
		$res = new responsePps();
		$forReg = (int) reqPps::getVar('for_reg', 'get');
		$haveErrors = false;
		if(($userId = $this->getModel()->confirm(reqPps::get('get'), $forReg)) === false) {
			$res->pushError ($this->getModel()->getErrors());
			$haveErrors = true;
		}
		
		$lastPopup = $this->getModel()->getLastPopup();
		$subscribedUrl = '';
		if(!$haveErrors) {
			//reqPps::setVar('pps_email_confirmed_'. $lastPopup['id'], '1', 'cookie', array('expire' => 999 * 24 * 60 * 60));
			$this->_setConfirmedCookie($lastPopup['id']);
			$subscribedUrl = get_user_meta($userId, '_subscribe_url', true);
			if(strpos($subscribedUrl, $_SERVER['HTTP_HOST']) === false) {
				$subscribedUrl = '';
			}
		}
		$this->getView()->displaySuccessPage($lastPopup, $res, $forReg, $subscribedUrl);
		exit();
		// Just simple redirect for now
		//$siteUrl = get_bloginfo('wpurl');
		//redirectPps($siteUrl);
	}
	public function getMailchimpLists() {
		$res = new responsePps();
		if(($lists = $this->getModel()->getMailchimpLists(reqPps::get('post'))) !== false) {
			$res->addData('lists', $lists);
		} else
			$res->pushError ($this->getModel()->getErrors());
		return $res->ajaxExec();
	}
	public function getMailchimpGroups() {
		$res = new responsePps();
		if(($groups = $this->getModel()->getMailchimpGroups(reqPps::get('post'))) !== false) {
			$res->addData('groups', $groups);
		} else
			$res->pushError ($this->getModel()->getErrors());
		return $res->ajaxExec();
	}
	public function getWpCsvList() {
		$id = (int) reqPps::getVar('id');
		$forReg = (int) reqPps::getVar('for_reg');
		$popup = $id ? framePps::_()->getModule('popup')->getModel()->getById( $id ) : NULL;
		$label = $popup ? $popup['label'] : get_bloginfo('name');
		
		importClassPps('filegeneratorPps');
		importClassPps('csvgeneratorPps');

		$fileTitle = $forReg 
			? sprintf(__('Registered from %s', PPS_LANG_CODE), htmlspecialchars( $label )) 
			: sprintf(__('Subscribed to %s', PPS_LANG_CODE), htmlspecialchars( $label ));

		$csvGenerator = new csvgeneratorPps( $fileTitle );
		$labels = array(
			'username' => __('Username', PPS_LANG_CODE),
			'email' => __('Email', PPS_LANG_CODE),
		);
		if($popup) {
			// Add additional subscribe fields
			if(isset($popup['params']['tpl']['sub_fields']) && !empty($popup['params']['tpl']['sub_fields'])) {
				foreach($popup['params']['tpl']['sub_fields'] as $k => $f) {
					if(in_array($k, array('name', 'email'))) continue;	// Ignore standard fields
					$labels[ 'sub_field_'. $k ] = $f['label'];
				}
			}
		}
		$labels = array_merge($labels, array(
			'activated' => __('Activated', PPS_LANG_CODE),
			'popup_id' => __('PopUp ID', PPS_LANG_CODE),
			'date_created' => __('Date Created', PPS_LANG_CODE),
		));
		$selectFields = array('all_data');
		foreach($labels as $lKey => $lName) {
			if(strpos($lKey, 'sub_field_') === 0) continue;
			$selectFields[] = $lKey;
		}
		$model = $forReg ? framePps::_()->getModule('login')->getModel() : $this->getModel();
		$model->setSelectFields( $selectFields );
		if($popup) {
			$model->setWhere(array('popup_id' => $popup['id']));
		}
		$list = $model->getFromTbl();
		if(!empty($list) && !$popup) {
			// Clear duplicated emails
			$oldList = $list;
			$list = $emails = array();
			foreach($oldList as $s) {
				if(!in_array($s['email'], $emails)) {
					$list[] = $s;
					$emails[] = $s['email'];
				}
			}
			// Check all fields in all PopUps
			$popupIds = array();
			foreach($list as $s) {
				$popupIds[ $s['popup_id'] ] = 1;
			}
			$popups = framePps::_()->getModule('popup')->getModel()->setWhere('id IN ('. implode(',', array_keys($popupIds)). ')')->getFromTbl();
			if(!empty($popups)) {
				foreach($popups as $p) {
					if(isset($p['params']['tpl']['sub_fields']) && !empty($p['params']['tpl']['sub_fields'])) {
						foreach($p['params']['tpl']['sub_fields'] as $k => $f) {
							if(in_array($k, array('name', 'email'))) continue;	// Ignore standard fields
							$labels[ 'sub_field_'. $k ] = $f['label'];
						}
					}
				}
			}
		}
		$row = $cell = 0;
		foreach($labels as $l) {
			$csvGenerator->addCell($row, $cell, $l);
			$cell++;
		}
		$row = 1;
		if(!empty($list)) {
			foreach($list as $s) {
				$cell = 0;
				foreach($labels as $k => $l) {
					$getKey = $k;
					if(strpos($getKey, 'sub_field_') === 0) {
						$getKey = str_replace('sub_field_', '', $getKey);
						$allData = empty($s['all_data']) ? array() : utilsPps::unserialize($s['all_data']);
						$value = isset($allData[ $getKey ]) ? $allData[ $getKey ] : '';
					} else {
						$value = $s[ $getKey ];
					}
					$csvGenerator->addCell($row, $cell, $value);
					$cell++;
				}
				$row++;
			}
		} else {
			$cell = 0;
			$noUsersMsg = $forReg 
				? __('There are no members for now', PPS_LANG_CODE) 
				: __('There are no subscribers for now', PPS_LANG_CODE);
			$csvGenerator->addCell($row, $cell, $noUsersMsg);
		}
		$csvGenerator->generate();
		
	}
	public function getPermissions() {
		return array(
			PPS_USERLEVELS => array(
				PPS_ADMIN => array('getMailchimpLists', 'getWpCsvList')
			),
		);
	}
}

