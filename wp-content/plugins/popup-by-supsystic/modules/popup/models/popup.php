<?php
class popupModelPps extends modelPps {
	private $_showToList = array();
	private $_showPagesList = array();
	private $_showOnList = array();
	private $_types = array();
	private $_linksReplacement = array();
	public function __construct() {
		$this->_setTbl('popup');
	}
	public function abDeactivated() {
		if(framePps::_()->licenseDeactivated()) {
			return (bool) dbPps::exist('@__'. $this->_tbl, 'ab_id');
		}
		return false;
	}
	/**
	 * Exclude some data from list - to avoid memory overload
	 */
	public function getSimpleList($where = array(), $params = array()) {
		if($where)
			$this->setWhere ($where);
		return $this->setSelectFields('id, label, original_id, img_preview, type_id')->getFromTbl( $params );
	}
	protected function _prepareParamsAfterDb($params) {
		if(is_array($params)) {
			foreach($params as $k => $v) {
				$params[ $k ] = $this->_prepareParamsAfterDb( $v ); 
			}
		} else
			$params = stripslashes ($params);
		return $params;
	}
	private function _getLinksReplacement() {
		if(empty($this->_linksReplacement)) {
			$this->_linksReplacement = array(
				'modUrl' => array('url' => $this->getModule()->getModPath(), 'key' => 'PPS_MOD_URL'),
				'siteUrl' => array('url' => PPS_SITE_URL, 'key' => 'PPS_SITE_URL'),
				'assetsUrl' => array('url' => $this->getModule()->getAssetsUrl(), 'key' => 'PPS_ASSETS_URL'),
				'oldAssets' => array('url' => $this->getModule()->getOldAssetsUrl(), 'key' => 'PPS_OLD_ASSETS_URL'),
			);
		}
		return $this->_linksReplacement;
	}
	protected function _beforeDbReplace($data) {
		static $replaceFrom, $replaceTo;
		if(is_array($data)) {
			foreach($data as $k => $v) {
				$data[ $k ] = $this->_beforeDbReplace($v);
			}
		} else {
			if(!$replaceFrom) {
				$this->_getLinksReplacement();
				foreach($this->_linksReplacement as $k => $rData) {
					if($k == 'oldAssets') {	// Replace old assets urls - to new one
						$replaceFrom[] = $rData['url'];
						$replaceTo[] = '['. $this->_linksReplacement['assetsUrl']['key']. ']';
					} else {
						$replaceFrom[] = $rData['url'];
						$replaceTo[] = '['. $rData['key']. ']';
					}
				}
			}
			$data = str_replace($replaceFrom, $replaceTo, $data);
		}
		return $data;
	}
	protected function _afterDbReplace($data) {
		static $replaceFrom, $replaceTo;
		if(is_array($data)) {
			foreach($data as $k => $v) {
				$data[ $k ] = $this->_afterDbReplace($v);
			}
		} else {
			if(!$replaceFrom) {
				$this->_getLinksReplacement();
				/*Tmp fix - for quick replace all mode URL to assets URL*/
				$replaceFrom[] = '['. $this->_linksReplacement['modUrl']['key']. ']';
				$replaceTo[] = '['. $this->_linksReplacement['assetsUrl']['key']. ']';
				$replaceFrom[] = $this->_linksReplacement['oldAssets']['url'];
				$replaceTo[] = $this->_linksReplacement['assetsUrl']['url'];
				/*****/
				foreach($this->_linksReplacement as $k => $rData) {
					$replaceFrom[] = '['. $rData['key']. ']';
					$replaceTo[] = $rData['url'];
				}
			}
			$data = str_replace($replaceFrom, $replaceTo, $data);
		}
		return $data;
	}
	protected function _afterGetFromTbl($row) {
		if(isset($row['params'])) {
			$row['params'] = $this->_prepareParamsAfterDb( utilsPps::unserialize( base64_decode($row['params']) ) );
			if(!empty($row['params']) && isset($row['params']['tpl']) && !empty($row['params']['tpl'])) {
				$keysCheckEmail = array('sub_txt_subscriber_mail_from', 'sub_new_email', 'sub_txt_confirm_mail_from',
					'reg_txt_subscriber_mail_from', 'reg_txt_confirm_mail_from', 'reg_new_email');
				foreach($keysCheckEmail as $ek) {
					if(isset($row['params']['tpl'][$ek])) {
						$row['params']['tpl'][$ek] = utilsPps::toAdminEmail($row['params']['tpl'][$ek]);
					}
				}
			}
		}
		if(empty($row['img_preview'])) {
			$row['img_preview'] = str_replace(' ', '-', strtolower( trim($row['label']) )). '.jpg';
		}
		$row['img_preview_url'] = uriPps::_($this->getModule()->getAssetsUrl(). 'img/preview/'. $row['img_preview']);
		$row['view_id'] = $row['id']. '_'. mt_rand(1, 999999);
		$row = $this->_afterDbReplace($row);
		$this->getTypes();
		$row['type'] = isset($row['type_id']) && isset($this->_types[ $row['type_id'] ]) ? $this->_types[ $row['type_id'] ]['code'] : 'common';
		if(!isset($row['params']['tpl']['sub_fields'])) {
			$row['params']['tpl']['sub_fields'] = array(
				'email' => array('label' => __('E-Mail', PPS_LANG_CODE), 'html' => 'text', 'enb' => true, 'mandatory' => true, 'name' => 'email'),
				'name' => array('label' => __('Name', PPS_LANG_CODE), 'html' => 'text', 'enb' => (isset($row['params']['tpl']['enb_sub_name']) && $row['params']['tpl']['enb_sub_name']), 'name' => 'name'),
			);
		} else {
			// Saving Enabling name field for old field database sctructure - to not lose this field for old popups
			if(isset($row['params']['tpl']['sub_fields']) 
				&& isset($row['params']['tpl']['sub_fields']['name'])
				&& isset($row['params']['tpl']['sub_fields']['name']['enb'])
				&& $row['params']['tpl']['sub_fields']['name']['enb']
			) {
				$row['params']['tpl']['enb_sub_name'] = 1;
			}
		}
		// For PRO only, but let it be here for now
		if(!isset($row['params']['tpl']['reg_fields'])) {
			$row['params']['tpl']['reg_fields'] = array(
				'name' => array('label' => __('Name', PPS_LANG_CODE), 'html' => 'text', 'enb' => 1, 'name' => 'name'),
				'email' => array('label' => __('E-Mail', PPS_LANG_CODE), 'html' => 'text', 'enb' => true, 'mandatory' => true, 'name' => 'email'),
			);
		}
		if($row['type'] == PPS_VIDEO && !isset($row['params']['tpl']['video_type'])) {
			$videoTypes = array(PPS_VIDEO_YOUTUBE, PPS_VIDEO_VIMEO);
			$row['params']['tpl']['video_type'] = PPS_VIDEO_OTHER;
			foreach($videoTypes as $t) {
				if(isset($row['params']['tpl']['video_url'])
					&& strpos($row['params']['tpl']['video_url'], $t) !== false
				) {
					$row['params']['tpl']['video_type'] = $t;
					break;
				}
			}
		}
		return $row;
	}
	protected function _dataSave($data, $update = false) {
		$data = $this->_beforeDbReplace($data);
		if(isset($data['params']))
			$data['params'] = base64_encode(utilsPps::serialize( $data['params'] ));
		return $data;
	}
	protected function _escTplData($data) {
		$data['label'] = dbPps::prepareHtmlIn($data['label']);
		$data['html'] = dbPps::escape($data['html']);
		$data['css'] = dbPps::escape($data['css']);
		return $data;
	}
	public function createFromTpl($d = array()) {
		$d['label'] = isset($d['label']) ? trim($d['label']) : '';
		$d['original_id'] = isset($d['original_id']) ? (int) $d['original_id'] : 0;
		if(!empty($d['label'])) {
			if(!empty($d['original_id'])) {
				$original = $this->getById($d['original_id']);
				framePps::_()->getModule('supsystic_promo')->getModel()->saveUsageStat('create_from_tpl.'. strtolower(str_replace(' ', '-', $original['label'])));
				framePps::_()->getModule('supsystic_promo')->getModel()->bigStatAddCheck('Used Template', array('Selected Template' => $original['label']));
				unset($original['id']);
				$original['label'] = $d['label'];
				$original['original_id'] = $d['original_id'];
				if(in_array($original['type'], array(PPS_VIDEO))) {
					// They all have video from youtube by default
					$original['params']['tpl']['video_type'] = PPS_VIDEO_YOUTUBE;
				}
				return $this->insertFromOriginal( $original );
			} else
				$this->pushError (__('Please select PopUp template from list below', PPS_LANG_CODE));
		} else
			$this->pushError (__('Please enter Name', PPS_LANG_CODE), 'label');
		return false;
	}
	public function insertFromOriginal($original) {
		$original = $this->_escTplData( $original );
		return $this->insert( $original );
	}
	public function remove($id) {
		$id = (int) $id;
		if($id) {
			if(framePps::_()->getTable( $this->_tbl )->delete(array('id' => $id))) {
				return true;
			} else
				$this->pushError (__('Database error detected', PPS_LANG_CODE));
		} else
			$this->pushError(__('Invalid ID', PPS_LANG_CODE));
		return false;
	}
	/**
	 * Do not remove pre-set templates
	 */
	public function clear() {
		if(framePps::_()->getTable( $this->_tbl )->delete(array('additionalCondition' => 'original_id != 0'))) {
			return true;
		} else 
			$this->pushError (__('Database error detected', PPS_LANG_CODE));
		return false;
	}
	public function save($d = array()) {
		$popup = $this->getById($d['id']);
		if(in_array($popup['type'], array(PPS_FB_LIKE))) {
			$d['params']['tpl']['fb_like_opts']['href'] = trim( $d['params']['tpl']['fb_like_opts']['href'] );
			if(empty($d['params']['tpl']['fb_like_opts']['href'])) {
				$this->pushError(__('Enter your Facebook page URL', PPS_LANG_CODE), 'params[tpl][fb_like_opts][href]');
				return false;
			}
		}
		if(in_array($popup['type'], array(PPS_VIDEO))) {
			$d['params']['tpl']['video_url'] = trim( $d['params']['tpl']['video_url'] );
			if(empty($d['params']['tpl']['video_url'])) {
				$this->pushError(__('Enter your video URL', PPS_LANG_CODE), 'params[tpl][video_url]');
				return false;
			}
			$videoTypes = array(PPS_VIDEO_YOUTUBE, PPS_VIDEO_VIMEO);
			$d['params']['tpl']['video_type'] = PPS_VIDEO_OTHER;
			foreach($videoTypes as $t) {
				if(strpos($d['params']['tpl']['video_url'], $t) !== false) {
					$d['params']['tpl']['video_type'] = $t;
					break;
				}
			}
		}
		if(isset($d['params']['opts_attrs']['txt_block_number']) && !empty($d['params']['opts_attrs']['txt_block_number'])) {
			for($i = 0; $i < (int) $d['params']['opts_attrs']['txt_block_number']; $i++) {
				$sendValKey = 'params_tpl_txt_val_'. $i;
				if(isset($d[ $sendValKey ])) {
					$d['params']['tpl']['txt_'. $i] = urldecode( $d[ $sendValKey ] );
				}
			}
		}
		if(isset($d['params']['tpl']['use_sss_prj_id'])) {
			$oldSssProjId = isset($popup['params']['tpl']['use_sss_prj_id']) ? (int) $popup['params']['tpl']['use_sss_prj_id'] : 0;
			$newSssProjId = (int) $d['params']['tpl']['use_sss_prj_id'];
			if($oldSssProjId != $newSssProjId) {
				if(!$this->_updateSocSharingProject( $newSssProjId, $d['id'])) {	// For just changed Proj ID - set it, if it was set to 0 - clear prev. selected
					return false;	// Something wrong go there - let's try to detect thos issues for now
				}
			}
		}
		$this->getShowOnList();
		$this->getShowToList();
		$this->getShowPagesList();
		
		$d['show_on'] = isset($d['params']['main']['show_on']) ? $this->_showOnList[ $d['params']['main']['show_on'] ]['id'] : 0;
		$d['show_to'] = isset($d['params']['main']['show_to']) ? $this->_showToList[ $d['params']['main']['show_to'] ]['id'] : 0;
		$d['show_pages'] = isset($d['params']['main']['show_pages']) ? $this->_showPagesList[ $d['params']['main']['show_pages'] ]['id'] : 0;
		$d['show_in_admin_area'] = isset($d['params']['main']['show_in_admin_area']) && $d['params']['main']['show_in_admin_area'] ? 1 : 0;
		
		if(isset($d['css']) && empty($d['css'])) {
			unset($d['css']);
		}
		if(isset($d['html']) && empty($d['html'])) {
			unset($d['html']);
		}
		$res = $this->updateById($d);
		if($res) {
			/*$currentPopup = $this->getById($d['id']);
			$difs = $this->getDifferences($popup, $currentPopup);
			if(!empty($difs)) {
				foreach($difs as $dif) {
					framePps::_()->getModule('supsystic_promo')->getModel()->saveUsageStat('save_popup.'. $dif);
				}
			}*/
			$this->_bindShowToPages( $d );
			dispatcherPps::doAction('afterPopUpUpdate', $d);
		}
		return $res;
	}
	public function updateParamsById($d) {
		foreach($d as $k => $v) {
			if(!in_array($k, array('id', 'params')))
				unset($d[ $k ]);
		}
		return $this->updateById($d);
	}
	/**
	 * Copied from SocialSharing_Popup_Module::getSearchString() as it is protected there
	 */
	protected function _socShareGetSearchString($id) {
        // Need to be a string
        $id = (string)$id;

        // Popup ID saved in the database with the other serialized settings.
        // So we need to serialize value.
        $serialized = serialize(array('popup_id' => $id));
        // Take part like s:8:"popup_id";s:2:"54";
        $search = substr($serialized, 5, strlen($serialized) - 6);

        return '%' . $search . '%';
    }
	private function _updateSocSharingProject($projId, $popupId) {
		if(class_exists('SupsysticSocialSharing')) {
			global $supsysticSocialSharing;
			if(isset($supsysticSocialSharing) && !empty($supsysticSocialSharing) && method_exists($supsysticSocialSharing, 'getEnvironment')) {
				$socShareProjMod = $supsysticSocialSharing->getEnvironment()->getModule('Projects');
				$socSharePopupMod = $supsysticSocialSharing->getEnvironment()->getModule('Popup');
				if(!empty($socShareProjMod) && !empty($socSharePopupMod)) {
					try {
						// Clear all prev. projects used this PopUp
						$searchPrjcts = $this->_socShareGetSearchString($popupId);
						$projects = $socShareProjMod->getModelsFactory()->get('projects');
						$project = $projects->searchByElementId($searchPrjcts);
						if(!empty($project)) {
							$project->settings['popup_id'] = 0;
							$socShareProjMod->getController()->getModelsFactory()->get('projects')->save($project->id, $project->settings);
						}
						// Update current
						if(!empty($projId)) {
							$socShareProj = $socShareProjMod->getController()->getModelsFactory()->get('projects')->get($projId);
							if(!empty($socShareProj)) {
								$socShareProj->settings['where_to_show'] = 'popup';
								$socShareProj->settings['popup_id'] = $popupId;
								$socShareProjMod->getController()->getModelsFactory()->get('projects')->save($projId, $socShareProj->settings);
							}
						}
					} catch (Exception $e) {
						$this->pushError($e->getMessage());
						return false;
					}
				}
			}
		}
		return true;
	}
	private function _bindShowToPages( $d ) {
		$id = (int) $d['id'];
		if($id) {
			// Re-Bind show/hide for single posts/pages
			framePps::_()->getTable('popup_show_pages')->delete(array('popup_id' => $id));
			$insertArr = array();
			if(isset($d['show_pages_list']) && !empty($d['show_pages_list'])) {
				foreach($d['show_pages_list'] as $postId) {
					$insertArr[] = "($id, $postId, 0)";
				}
			}
			if(isset($d['not_show_pages_list']) && !empty($d['not_show_pages_list'])) {
				foreach($d['not_show_pages_list'] as $postId) {
					$insertArr[] = "($id, $postId, 1)";
				}
			}
			if(!empty($insertArr)) {
				dbPps::query('INSERT INTO @__popup_show_pages (popup_id, post_id, not_show) VALUES '. implode(',', $insertArr));
			}
			// Re-Bind show/hide for categories (terms)
			framePps::_()->getTable('popup_show_categories')->delete(array('popup_id' => $id));
			$insertArr = array();
			if(isset($d['show_categories_list']) && !empty($d['show_categories_list'])) {
				foreach($d['show_categories_list'] as $termId) {
					$insertArr[] = "($id, $termId, 0)";
				}
			}
			if(isset($d['not_show_categories_list']) && !empty($d['not_show_categories_list'])) {
				foreach($d['not_show_categories_list'] as $termId) {
					$insertArr[] = "($id, $termId, 1)";
				}
			}
			if(!empty($insertArr)) {
				dbPps::query('INSERT INTO @__popup_show_categories (popup_id, term_id, not_show) VALUES '. implode(',', $insertArr));
			}
		}
	}
	public function getShowToList() {
		if(empty($this->_showToList)) {
			$this->_showToList = array(
				'everyone' => array('id' => 1),
				'first_time_visit' => array('id' => 2),
				'for_countries' => array('id' => 3),
				'until_make_action' => array('id' => 4),
				'count_times' => array('id' => 5),
				'until_email_confirm' => array('id' => 6),
			);
		}
		return $this->_showToList;
	}
	public function getShowPagesList() {
		if(empty($this->_showPagesList)) {
			$this->_showPagesList = array(
				'all' => array('id' => 1),
				'show_on_pages' => array('id' => 2),
				'not_show_on_pages' => array('id' => 3),
				'show_on_categories' => array('id' => 4),
				'not_show_on_categories' => array('id' => 5),
			);
		}
		return $this->_showPagesList;
	}
	public function getShowOnList() {
		if(empty($this->_showOnList)) {
			$this->_showOnList = dispatcherPps::applyFilters('popupShowOnList', array(
				'page_load' => array('id' => 1),
				'click_on_page' => array('id' => 2),
				'click_on_element' => array('id' => 3),
				'scroll_window' => array('id' => 4),
				'on_exit' => array('id' => 5),
				'page_bottom' => array('id' => 6),
				'after_inactive' => array('id' => 7),
				'after_comment' => array('id' => 8),
				'after_checkout' => array('id' => 9),
				'link_follow' => array('id' => 10),
				'build_in_page' => array('id' => 11),
				'adblock_detected' => array('id' => 12),
			));
		}
		return $this->_showOnList;
	}
	public function getShowOnIdByKey($key) {
		$this->getShowOnList();
		return isset($this->_showOnList[ $key ]) ? $this->_showOnList[ $key ]['id'] : false;
	}
	public function getById($id) {
		$data = parent::getById($id);
		if($data) {
			$data['show_pages_list'] = framePps::_()->getTable('popup_show_pages')->get('*', array('popup_id' => $id));
			$data['show_categories_list'] = framePps::_()->getTable('popup_show_categories')->get('*', array('popup_id' => $id));
		}
		return $data;
	}
	public function getPopupOnClickById($id) {
		$data = $this->addWhere('id = '. $id. ' AND original_id != 0 AND active = 1')->getFromTbl();
		$data = empty($data) ? false : array_shift($data);
		if($data) {
			$data['show_pages_list'] = framePps::_()->getTable('popup_show_pages')->get('*', array('popup_id' => $id));
			$data['show_categories_list'] = framePps::_()->getTable('popup_show_categories')->get('*', array('popup_id' => $id));
		}
		return $data;
	}
	public function getTypes() {
		if(empty($this->_types)) {
			$this->_types =  dispatcherPps::applyFilters('popupTypesList', array(
				1 => array('code' => PPS_COMMON, 'label' => __('Common', PPS_LANG_CODE)),
				2 => array('code' => PPS_FB_LIKE, 'label' => __('Facebook Like', PPS_LANG_CODE)),
				3 => array('code' => PPS_VIDEO, 'label' => __('Video', PPS_LANG_CODE)),
				4 => array('code' => PPS_IFRAME, 'label' => __('iFrame', PPS_LANG_CODE)),
				5 => array('code' => PPS_SIMPLE_HTML, 'label' => __('Simple HTML', PPS_LANG_CODE)),
				6 => array('code' => PPS_PDF, 'label' => __('PDF', PPS_LANG_CODE)),
				7 => array('code' => PPS_AGE_VERIFY, 'label' => __('Age Verification', PPS_LANG_CODE)),
				8 => array('code' => PPS_FULL_SCREEN, 'label' => __('Full Screen', PPS_LANG_CODE)),
				9 => array('code' => PPS_LOGIN_REGISTER, 'label' => __('Login / Registration', PPS_LANG_CODE)),
				10 => array('code' => PPS_BAR, 'label' => __('Notification Bar', PPS_LANG_CODE)),
				11 => array('code' => PPS_COMMON, 'label' => __('Christmas', PPS_LANG_CODE)),
			));
		}
		return $this->_types;
	}
	public function changeTpl($d = array()) {
		$d['id'] = isset($d['id']) ? (int) $d['id'] : 0;
		$d['new_tpl_id'] = isset($d['new_tpl_id']) ? (int) $d['new_tpl_id'] : 0;
		if($d['id'] && $d['new_tpl_id']) {
			$currentPopup = $this->getById( $d['id'] );
			$newTpl = $this->getById( $d['new_tpl_id'] );
			$originalPopup = $this->getById( $currentPopup['original_id'] );
			$diffFromOriginal = $this->getDifferences($currentPopup, $originalPopup);
			if(!empty($diffFromOriginal)) {
				if(isset($newTpl['params'])) {
					$keysForMove = array('params.tpl.label', 'params.tpl.anim_key', 'params.tpl.enb_foot_note', 'params.tpl.foot_note',
						'params.tpl.enb_sm',
						'params.tpl.enb_subscribe');
					foreach($diffFromOriginal as $k) {
						if(in_array($k, $keysForMove)
							|| strpos($k, 'params.tpl.enb_sm_') === 0 
							|| strpos($k, 'params.tpl.sm_') === 0 
							|| strpos($k, 'params.tpl.enb_sub_') === 0 
							|| strpos($k, 'params.tpl.sub_') === 0
							|| strpos($k, 'params.tpl.enb_txt_') === 0
							|| strpos($k, 'params.tpl.txt_') === 0
						) {
							$this->_assignKeyArr($currentPopup, $newTpl, $k);
						}
					}
				}
			}
			// Save main settings - as they should not influence for display settings
			$this->_assignKeyArr($currentPopup, $newTpl, 'params.main');
			framePps::_()->getModule('supsystic_promo')->getModel()->saveUsageStat('change_to_tpl.'. strtolower(str_replace(' ', '-', $newTpl['label'])));
			framePps::_()->getModule('supsystic_promo')->getModel()->bigStatAddCheck('Used Template', array('Changed to Template' => $newTpl['label']));
			$newTpl['original_id'] = $newTpl['id'];	// It will be our new original
			$newTpl['id'] = $currentPopup['id'];
			$newTpl['label'] = $currentPopup['label'];
			$newTpl = dispatcherPps::applyFilters('popupChangeTpl', $newTpl, $currentPopup);
			$newTpl = $this->_escTplData( $newTpl );
			return $this->update( $newTpl, array('id' => $newTpl['id']) );
		} else
			$this->pushError (__('Provided data was corrupted', PPS_LANG_CODE));
		return false;
	}
	private function _assignKeyArr($from, &$to, $key) {
		$subKeys = explode('.', $key);	
		// Yeah, hardcode, I know.............
		switch(count($subKeys)) {
			case 4:
				if(isset( $from[ $subKeys[0] ][ $subKeys[1] ][ $subKeys[2] ][ $subKeys[3] ] ))
					$to[ $subKeys[0] ][ $subKeys[1] ][ $subKeys[2] ][ $subKeys[3] ] = $from[ $subKeys[0] ][ $subKeys[1] ][ $subKeys[2] ][ $subKeys[3] ];
				else
					unset($to[ $subKeys[0] ][ $subKeys[1] ][ $subKeys[2] ][ $subKeys[3] ]);
				break;
			case 3:
				if(isset( $from[ $subKeys[0] ][ $subKeys[1] ][ $subKeys[2] ] ))
					$to[ $subKeys[0] ][ $subKeys[1] ][ $subKeys[2] ] = $from[ $subKeys[0] ][ $subKeys[1] ][ $subKeys[2] ];
				else
					unset($to[ $subKeys[0] ][ $subKeys[1] ][ $subKeys[2] ]);
				break;
			case 2:
				if(isset( $from[ $subKeys[0] ][ $subKeys[1] ] ))
					$to[ $subKeys[0] ][ $subKeys[1] ] = $from[ $subKeys[0] ][ $subKeys[1] ];
				else
					unset($to[ $subKeys[0] ][ $subKeys[1] ]);
				break;
			case 1:
				if(isset( $from[ $subKeys[0] ] ))
					$to[ $subKeys[0] ] = $from[ $subKeys[0] ];
				else
					unset( $to[ $subKeys[0] ] );
				break;
		}
	}
	public function getDifferences($popup, $original) {
		$difsFromOriginal = $this->_computeDifferences($popup, $original);
		$difsOfOriginal = $this->_computeDifferences($original, $popup);	// Some options may be present in original, but not present in current popup
		if(!empty($difsFromOriginal) && empty($difsOfOriginal)) {
			return $difsFromOriginal;
		} elseif(empty($difsFromOriginal) && !empty($difsOfOriginal)) {
			return $difsOfOriginal;
		} else {
			$difs = array_merge($difsFromOriginal, $difsOfOriginal);
			return array_unique($difs);
		}
	}
	private function _computeDifferences($popup, $original, $key = '', $keysImplode = array()) {
		$difs = array();
		if(is_array($popup)) {
			$excludeKey = array('id', 'label', 'active', 'original_id', 'img_preview', 'type_id', 
				'date_created', 'view_id', 'img_preview_url', 'show_on', 'show_to', 'show_pages');
			if(!empty($key))
				$keysImplode[] = $key;
			foreach($popup as $k => $v) {
				if(in_array($k, $excludeKey) && empty($key)) continue;
				if(!isset($original[ $k ])) {
					$difs[] = $this->_prepareDiffKeys($k, $keysImplode);
					continue;
				}
				$currDifs = $this->_computeDifferences($popup[ $k ], $original[ $k ], $k, $keysImplode);
				if(!empty($currDifs)) {
					$difs = array_merge($difs, $currDifs);
				}
			}
		} else {
			if($popup != $original) {
				$difs[] = $this->_prepareDiffKeys($key, $keysImplode);
			}
		}
		return $difs;
	}
	private function _prepareDiffKeys($key, $keysImplode) {
		return empty($keysImplode) ? $key : implode('.', $keysImplode). '.'. $key;
	}
	public function clearCachedStats($id) {
		$tbl = $this->getTbl();
		$id = (int) $id;
		return dbPps::query("UPDATE @__$tbl SET `views` = 0, `unique_views` = 0, `actions` = 0 WHERE `id` = $id");
	}
	public function addCachedStat($id, $statColumn) {
		$tbl = $this->getTbl();
		$id = (int) $id;
		return dbPps::query("UPDATE @__$tbl SET `$statColumn` = `$statColumn` + 1 WHERE `id` = $id");
	}
	public function addViewed($id) {
		return $this->addCachedStat($id, 'views');
	}
	public function addUniqueViewed($id) {
		return $this->addCachedStat($id, 'unique_views');
	}
	public function addActionDone($id) {
		return $this->addCachedStat($id, 'actions');
	}
	public function recalculateStatsForPopups() {
		$recalculated = (int)get_option('pps_stats_recalculated');
		if(!$recalculated) {
			update_option('pps_stats_recalculated', 1);
			$allPopups = $this->getSimpleList();
			if(!empty($allPopups)) {
				$statsModel = framePps::_()->getModule('statistics')->getModel();
				foreach($allPopups as $p) {
					if(empty($p['original_id'])) continue;
					$stats = $statsModel->getPreparedStats(array('id' => $p['id']));
					if(!empty($stats)) {
						$total = array_shift($stats);
						foreach($stats as $s) {
							foreach($s as $statKey => $statData) {
								if(is_numeric($statData)) {
									$total[ $statKey ] += $statData;
								}
							}
						}
						$tbl = $this->getTbl();
						framePps::_()->getTable($tbl)->update(array(
							'views' => $total['views'],
							'unique_views' => $total['unique_requests'],
							'actions' => $total['actions'],
						), array(
							'id' => $p['id']
						));
					}
				}
			}
		}
	}
	public function saveAsCopy($d = array()) {
		$d['copy_label'] = isset($d['copy_label']) ? trim($d['copy_label']) : '';
		$d['id'] = isset($d['id']) ? (int) $d['id'] : 0;
		if(!empty($d['copy_label'])) {
			if(!empty($d['id'])) {
				$original = $this->getById($d['id']);
				unset($original['id']);
				unset($original['date_created']);
				$original['label'] = $d['copy_label'];
				$original['views'] = $original['unique_views'] = $original['actions'] = 0;
				//framePps::_()->getModule('supsystic_promo')->getModel()->saveUsageStat('save_as_copy');
				return $this->insertFromOriginal( $original );
			} else
				$this->pushError (__('Invalid ID', PPS_LANG_CODE));
		} else
			$this->pushError (__('Please enter Name', PPS_LANG_CODE), 'copy_label');
		return false;
	}
	public function switchActive($d = array()) {
		$d['active'] = isset($d['active']) ? (int)$d['active'] : 0;
		$d['id'] = isset($d['id']) ? (int) $d['id'] : 0;
		if(!empty($d['id'])) {
			$tbl = $this->getTbl();
			return framePps::_()->getTable($tbl)->update(array(
				'active' => $d['active'],
			), array(
				'id' => $d['id'],
			));
		} else
			$this->pushError (__('Invalid ID', PPS_LANG_CODE));
		return false;
	}
	public function updateLabel($d = array()) {
		$d['id'] = isset($d['id']) ? (int) $d['id'] : 0;
		if(!empty($d['id'])) {
			$d['label'] = isset($d['label']) ? trim($d['label']) : '';
			if(!empty($d['label'])) {
				return $this->updateById(array(
					'label' => $d['label']
				), $d['id']);
			} else
				$this->pushError (__('Name can not be empty', PPS_LANG_CODE));
		} else
			$this->pushError (__('Invalid ID', PPS_LANG_CODE));
		return false;
	}
	public function setSimpleGetFields() {
		$this->setSelectFields('id, label, active, views, unique_views, actions, date_created, sort_order');
		return parent::setSimpleGetFields();
	}
	/**
	 * Names of Background for each PopUp template - to not display standard "Background 1" etc. labels there
	 */
	public function getBgNames() {
		return array(
			1 => array(
				__('Left side background', PPS_LANG_CODE),
				__('Right side background', PPS_LANG_CODE),
				__('Subscribe button background', PPS_LANG_CODE),
			),
			2 => array(
				__('Background', PPS_LANG_CODE),
				__('Sign up button background', PPS_LANG_CODE),
			),
			3 => array(
				__('Background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
				__('Right side background', PPS_LANG_CODE),
			),
			5 => array(
				__('Left side background', PPS_LANG_CODE),
				__('Right side background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			6 => array(
				__('Background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			7 => array(
				__('Main Background', PPS_LANG_CODE),
				__('Down background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			9 => array(
				__('Background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			10 => array(
				__('Background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			11 => array(
				__('Left Side Background', PPS_LANG_CODE),
				__('Right Side background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			12 => array(
				__('Background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			13 => array(
				__('Background', PPS_LANG_CODE),
				__('Sign-up button background', PPS_LANG_CODE),
				__('Close button background', PPS_LANG_CODE),
			),
			14 => array(
				__('Main background', PPS_LANG_CODE),
				__('Middle side background', PPS_LANG_CODE),
				__('Down side background', PPS_LANG_CODE),
			),
			15 => array(
				__('Main background', PPS_LANG_CODE),
				__('Fields background', PPS_LANG_CODE),
				__('Right side background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			16 => array(
				__('Main background', PPS_LANG_CODE),
				__('Left picture background', PPS_LANG_CODE),
				__('Background image', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
				__('Additional background', PPS_LANG_CODE),
			),
			17 => array(
				__('Main background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
				__('Frame background', PPS_LANG_CODE),
			),
			18 => array(
				__('Main background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
				__('Middle side background', PPS_LANG_CODE),
			),
			19 => array(
				__('Main background', PPS_LANG_CODE),
				__('Additional background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			20 => array(
				__('Main background', PPS_LANG_CODE),
				__('Exit button background', PPS_LANG_CODE),
				__('Left image background', PPS_LANG_CODE),
				__('Submit button background', PPS_LANG_CODE),
				__('Additional background', PPS_LANG_CODE),
				__('Right image background', PPS_LANG_CODE),
			),
			21 => array(
				__('Main background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
				__('Left side background', PPS_LANG_CODE),
			),
			22 => array(
				__('Left side background', PPS_LANG_CODE),
				__('Right side background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
				__('Image background', PPS_LANG_CODE),
			),
			23 => array(
				__('Main background', PPS_LANG_CODE),
				__('Field background', PPS_LANG_CODE),
				__('Image background', PPS_LANG_CODE),
				__('Go button background', PPS_LANG_CODE),
			),
			24 => array(
				__('Main background', PPS_LANG_CODE),
				__('Close button background', PPS_LANG_CODE),
				__('Middle side background', PPS_LANG_CODE),
				__('Submit button background', PPS_LANG_CODE),
			),
			25 => array(
				__('Main background', PPS_LANG_CODE),
				__('Submit button background', PPS_LANG_CODE),
				__('Right Image background', PPS_LANG_CODE),
				__('Right background', PPS_LANG_CODE),
			),
			26 => array(
				__('Main background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
				__('Image background', PPS_LANG_CODE),
			),
			27 => array(
				__('Left side background', PPS_LANG_CODE),
				__('Right side background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			28 => array(
				__('Main background', PPS_LANG_CODE),
				__('Field background', PPS_LANG_CODE),
				__('Left image background', PPS_LANG_CODE),
				__('Get it now button background', PPS_LANG_CODE),
			),
			29 => array(
				__('Main background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			30 => array(
				__('Main background', PPS_LANG_CODE),
				__('Upper background', PPS_LANG_CODE),
				__('Right image background', PPS_LANG_CODE),
				__('Bottom background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			31 => array(
				__('Main background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			33 => array(
				__('Main background', PPS_LANG_CODE),
				__('Frame background', PPS_LANG_CODE),
			),
			34 => array(
				__('Main background', PPS_LANG_CODE),
				__('Frame background', PPS_LANG_CODE),
			),
			43 => array(
				__('Main background', PPS_LANG_CODE),
				__('Field background', PPS_LANG_CODE),
				__('Image', PPS_LANG_CODE),
				__('Button Background', PPS_LANG_CODE),
				__('Button text color', PPS_LANG_CODE),
				__('Email text color', PPS_LANG_CODE),
				__('Header text color 2', PPS_LANG_CODE),
			),
			44 => array(
				__('Main background', PPS_LANG_CODE),
				__('Image 1', PPS_LANG_CODE),
				__('Image 2', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
				__('Email background', PPS_LANG_CODE),
			),
			45 => array(
				__('Main background', PPS_LANG_CODE),
				__('Image 1', PPS_LANG_CODE),
				__('Top background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
				__('Email background', PPS_LANG_CODE),
			),
			46 => array(
				__('Main background', PPS_LANG_CODE),
				__('Image 1', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			47 => array(
				__('Main background', PPS_LANG_CODE),
				__('Top Image', PPS_LANG_CODE),
				__('Top overlay', PPS_LANG_CODE),
				__('Bottom background', PPS_LANG_CODE),
			),
			48 => array(
				__('Main background', PPS_LANG_CODE),
				__('Image 1', PPS_LANG_CODE),
				__('Email background', PPS_LANG_CODE),
				__('Border color', PPS_LANG_CODE),
			),
			49 => array(
				__('Main background', PPS_LANG_CODE),
				__('Top background', PPS_LANG_CODE),
				__('Email background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			50 => array(
				__('Additional background', PPS_LANG_CODE),
				__('Main background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			52 => array(
				__('Upper background', PPS_LANG_CODE),
				__('Main background', PPS_LANG_CODE),
				__('Yes button background', PPS_LANG_CODE),
				__('No button background', PPS_LANG_CODE),
			),
			53 => array(
				__('Main background', PPS_LANG_CODE),
				__('Image background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			54 => array(
				__('Main background', PPS_LANG_CODE),
				__('Image background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			55 => array(
				__('Main background', PPS_LANG_CODE),
				__('Image background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			56 => array(
				__('Main background', PPS_LANG_CODE),
				__('Frame background', PPS_LANG_CODE),
			),
			57 => array(
				__('Main background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			58 => array(
				__('Main background', PPS_LANG_CODE),
				__('Field background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
				__('Left image background', PPS_LANG_CODE),
			),
			60 => array(
				__('Top background', PPS_LANG_CODE),
				__('Top overlap background', PPS_LANG_CODE),
				__('Image 1', PPS_LANG_CODE),
				__('Image 2', PPS_LANG_CODE),
				__('Main background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
				__('Field background', PPS_LANG_CODE),
			),
			61 => array(
				__('Main background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
				__('Email background', PPS_LANG_CODE),
			),
			62 => array(
				__('Main background', PPS_LANG_CODE),
				__('Image 1', PPS_LANG_CODE),
				__('Left background', PPS_LANG_CODE),
				__('E-mail background', PPS_LANG_CODE),
			),
			63 => array(
				__('Main background', PPS_LANG_CODE),
				__('Overlay background', PPS_LANG_CODE),
				__('E-mail background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
				__('Image', PPS_LANG_CODE),
			),
			64 => array(
				__('Main background', PPS_LANG_CODE),
				__('Left background', PPS_LANG_CODE),
				__('Right background', PPS_LANG_CODE),
				__('Women button background', PPS_LANG_CODE),
				__('Men button background', PPS_LANG_CODE),
			),
			65 => array(
				__('Background 1', PPS_LANG_CODE),
				__('Background 2', PPS_LANG_CODE),
				__('Left image', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			66 => array(
				__('Main background', PPS_LANG_CODE),
				__('Field background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			67 => array(
				__('Main background', PPS_LANG_CODE),
				__('Left image', PPS_LANG_CODE),
				__('Right image', PPS_LANG_CODE),
				__('Top image', PPS_LANG_CODE),
				__('Bottom image', PPS_LANG_CODE),
				__('Text image', PPS_LANG_CODE),
				__('Form top image', PPS_LANG_CODE),
				__('Form background', PPS_LANG_CODE),
				__('Form border', PPS_LANG_CODE),
				__('Field background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			68 => array(
				__('Background 1', PPS_LANG_CODE),
				__('Background 2', PPS_LANG_CODE),
				__('Field background', PPS_LANG_CODE),
				__('Button background', PPS_LANG_CODE),
			),
			69 => array(
				__('Main background', PPS_LANG_CODE),
				__('Image', PPS_LANG_CODE),
				__('Image background', PPS_LANG_CODE),
				__('Field background', PPS_LANG_CODE),
				__('Submit button background', PPS_LANG_CODE),
				__('Exit button background', PPS_LANG_CODE),
			),
			70 => array(
				__('Main background', PPS_LANG_CODE),
				__('Field background', PPS_LANG_CODE),
				__('Submit button background', PPS_LANG_CODE),
				__('Exit button background', PPS_LANG_CODE),
			),
			71 => array(
				__('Main background', PPS_LANG_CODE),
				__('Image', PPS_LANG_CODE),
				__('Field background', PPS_LANG_CODE),
				__('Submit button background', PPS_LANG_CODE),
				__('Name icon', PPS_LANG_CODE),
				__('Email icon', PPS_LANG_CODE),
				__('Exit button background', PPS_LANG_CODE),
			),
			72 => array(
				__('Main background', PPS_LANG_CODE),
				__('Field background', PPS_LANG_CODE),
				__('Submit button background', PPS_LANG_CODE),
				__('Name icon', PPS_LANG_CODE),
				__('Email icon', PPS_LANG_CODE),
				__('Right Image', PPS_LANG_CODE),
			),
		);
	}
	public function getBgNamesForPopup( $id ) {
		$bgNames = $this->getBgNames();
		return isset($bgNames[ $id ]) ? $bgNames[ $id ] : false;
	}
}
