<?php
class popupControllerPps extends controllerPps {
	private $_prevPopupId = 0;
	public function createFromTpl() {
		$res = new responsePps();
		if(($id = $this->getModel()->createFromTpl(reqPps::get('post'))) != false) {
			$res->addMessage(__('Done', PPS_LANG_CODE));
			$res->addData('edit_link', $this->getModule()->getEditLink( $id ));
		} else
			$res->pushError ($this->getModel()->getErrors());
		return $res->ajaxExec();
	}
	protected function _prepareListForTbl($data) {
		if(!empty($data)) {
			foreach($data as $i => $v) {
				$data[ $i ]['label'] = '<a class="" href="'. $this->getModule()->getEditLink($data[ $i ]['id']). '">'. $data[ $i ]['label']. '&nbsp;<i class="fa fa-fw fa-pencil" style="margin-top: 2px;"></i></a>';
				$conversion = 0;
				if(!empty($data[ $i ]['unique_views']) && !empty($data[ $i ]['actions'])) {
					$conversion = number_format( ((int) $data[ $i ]['actions'] / (int) $data[ $i ]['unique_views']), 3);
				}
				$data[ $i ]['conversion'] = $conversion;
				$data[ $i ]['active'] = $data[ $i ]['active'] ? '<span class="alert alert-success">'. __('Yes', PPS_LANG_CODE). '</span>' : '<span class="alert alert-danger">'. __('No', PPS_LANG_CODE). '</span>';
				
				//$data[ $i ]['action'] = '<a class="button" style="margin-right: 10px;" href="'. $this->getModule()->getEditLink($data[ $i ]['id']). '"><i class="fa fa-fw fa-2x fa-pencil" style="margin-top: 2px;"></i></a>';
				//$data[ $i ]['action'] .= '<button href="#" onclick="ppsPopupRemoveRow('. $data[ $i ]['id']. ', this); return false;" title="'. __('Remove', PPS_LANG_CODE). '" class="button"><i class="fa fa-fw fa-2x fa-trash-o" style="margin-top: 5px;"></i></button>';
			}
		}
		return $data;
	}
	protected function _prepareTextLikeSearch($val) {
		$query = '(label LIKE "%'. $val. '%"';
		if(is_numeric($val)) {
			$query .= ' OR id LIKE "%'. (int) $val. '%"';
		}
		$query .= ')';
		return $query;
	}
	protected function _prepareModelBeforeListSelect($model) {
		$this->getModel()->recalculateStatsForPopups();	// This was done for old users - from version 1.0.9, can be removed in future
		$where = 'original_id != 0';
		if($this->getModel()->abDeactivated()) {
			$where .= ' AND ab_id = 0';
		}
		$model->addWhere( $where );
		dispatcherPps::doAction('popupModelBeforeGetList', $model);
		return $model;
	}
	protected function _prepareSortOrder($sortOrder) {
		if($sortOrder == 'conversion') {
			$sortOrder = '(actions / unique_views)';	// Conversion in real-time calculation
		}
		return $sortOrder;
	}
	public function remove() {
		$res = new responsePps();
		if($this->getModel()->remove(reqPps::getVar('id', 'post'))) {
			$res->addMessage(__('Done', PPS_LANG_CODE));
		} else
			$res->pushError($this->getModel()->getErrors());
		$res->ajaxExec();
	}
	public function save() {
		$res = new responsePps();
		if($this->getModel()->save( reqPps::get('post') )) {
			$res->addMessage(__('Done', PPS_LANG_CODE));
		} else
			$res->pushError($this->getModel()->getErrors());
		$res->ajaxExec();
	}
	public function getPreviewHtml() {
		$this->_prevPopupId = (int) reqPps::getVar('id', 'get');
		$this->outPreviewHtml();
		//add_action('init', array($this, 'outPreviewHtml'));
	}
	public function outPreviewHtml() {
		if($this->_prevPopupId) {
			$this->_prepareGoogleMapAssetsForPreview( $this->_prevPopupId );
			$popup = $this->getModel()->getById( $this->_prevPopupId );
			$assetsStr = '<link rel="stylesheet" href="'. $this->getModule()->getModPath(). 'css/frontend.popup.css" type="text/css" media="all" />';
			if((isset($popup['params']['tpl']['enb_contact_form']) 
				&& !empty($popup['params']['tpl']['enb_contact_form']))
				&& $this->getModule()->contactFormsSupported()
			) {
				$form = frameCfs::_()->getModule('forms')->getModel()->getById($popup['params']['tpl']['contact_form']);
				$assetsStr .= frameCfs::_()->getModule('forms')->getAssetsforPrevStr($form);
			}
			$popupContent = $this->getView()->generateHtml( $popup );
			echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
			<html dir="'. (function_exists('is_rtl') && is_rtl() ? 'rtl' : 'ltr'). '"><head>'
			. '<meta content="'. get_option('html_type'). '; charset='. get_option('blog_charset'). '" http-equiv="Content-Type">'
			. '<script type="text/javascript" src="'. includes_url('js/jquery/jquery.js'). '"></script>'
			. $this->_generateSocSharingAssetsForPreview( $this->_prevPopupId )
			. $this->_generateGoogleMapAssetsForPreview( $this->_prevPopupId )
			. $assetsStr
			. '<style type="text/css"> 
				html { overflow: visible !important; } 
				.ppsPopupPreloadImg {
					width: 1px !important;
					height: 1px !important;
					position: absolute !important;
					top: -9999px !important;
					left: -9999px !important;
					opacity: 0 !important;
				}
				.ppsPopupShell {
					display: block;
					visibility: visible;
					position: static;
				}
				</style>'
			. '</head>';
			//wp_head();
			echo '<body>';
			echo $popupContent;
			//wp_footer();
			echo '<body></html>';
		}
		exit();
	}
	private function _generateSocSharingAssetsForPreview($popupId) {
		$res = '';
		if(class_exists('SupsysticSocialSharing')) {
			global $supsysticSocialSharing;
			if(isset($supsysticSocialSharing) 
				&& !empty($supsysticSocialSharing) 
				&& method_exists($supsysticSocialSharing, 'getEnvironment')
				&& ($uiMod = $supsysticSocialSharing->getEnvironment()->getModule('Ui'))
			) {
				$assetsForSocSharePlug = $uiMod->getAssets();
				if(!empty($assetsForSocSharePlug)) {
					$frontedHookNames = array('wp_enqueue_scripts', $supsysticSocialSharing->getEnvironment()->getConfig()->get('hooks_prefix'). 'before_html_build');
					foreach($assetsForSocSharePlug as $asset) {
						if(in_array($asset->getHookName(), $frontedHookNames)) {
							$source = $asset->getSource();
							if(empty($source)) continue;
							switch(get_class($asset)) {
								case 'SocialSharing_Ui_Script':
									$res .= '<script type="text/javascript" src="'. $asset->getSource(). '"></script>';
									break;
								case 'SocialSharing_Ui_Style':
									$res .= '<link rel="stylesheet" type="text/css" href="'. $asset->getSource(). '" />';
									break;
							}
						}
					}
					if(!empty($res)) {
						$res = $this->_connectMainJsLibsForPrev()
							. '<script type="text/javascript"> var sssIgnoreSaveStatistics = true; </script>'
							. $res;
					}
				}
			}
		}
		return $res;
	}
	private function _generateGoogleMapAssetsForPreview($popupId) {
		$res = '';
		if(class_exists('frameGmp') && defined('GMP_VERSION_PLUGIN')) {
			$scripts = frameGmp::_()->getScripts();
			if(!empty($scripts)) {
				frameGmp::_()->getModule('gmap')->getView()->addMapDataToJs();
				$setAssets = array();
				$res .= $this->_connectMainJsLibsForPrev();
				$scVars = frameGmp::_()->getJSVars();
				foreach($scripts as $s) {
					if(isset($s['src']) && !empty($s['src']) && !in_array($s['handle'], $setAssets)) {
						if($scVars && isset($scVars[ $s['handle'] ]) && !empty($scVars[ $s['handle'] ])) {
							$res .= "<script type='text/javascript'>"; // CDATA and type='text/javascript' is not needed for HTML 5
							$res .= "/* <![CDATA[ */";
							foreach($scVars[ $s['handle'] ] as $name => $value) {
								if($name == 'dataNoJson' && !is_array($value)) {
									$res .= $value;
								} else {
									$res .= "var $name = ". utilsGmp::jsonEncode($value). ";";
								}
							}
							$res .= "/* ]]> */";
							$res .= "</script>";
						}
						$res .= '<script type="text/javascript" src="'. $s['src']. '"></script>';
						$setAssets[] = $s['handle'];
					}
				}
			}
			$styles = frameGmp::_()->getStyles();
			if(!empty($styles)) {
				$setAssets = array();
				foreach($styles as $s) {
					if(isset($s['src']) && !empty($s['src']) && !in_array($s['handle'], $setAssets)) {
						$res .= '<link rel="stylesheet" type="text/css" href="'. $s['src']. '" />';
						$setAssets[] = $s['handle'];
					}
				}
			}
		}
		return $res;
	}
	private function _prepareGoogleMapAssetsForPreview($popupId) {
		if(class_exists('frameGmp') && defined('GMP_VERSION_PLUGIN')) {
			frameGmp::_()->setScriptsInitialized( false );
			frameGmp::_()->setStylesInitialized( false );
		}
	}
	private function _connectMainJsLibsForPrev() {
		static $connected = false;
		if(!$connected) {
			return '<script type="text/javascript" src="'. includes_url('js/jquery/jquery.js'). '"></script>';
			$connected = true;
		}
		return '';
	}
	public function changeTpl() {
		$res = new responsePps();
		if($this->getModel()->changeTpl(reqPps::get('post'))) {
			$res->addMessage(__('Done', PPS_LANG_CODE));
			$id = (int) reqPps::getVar('id', 'post');
			// Redirect after change template - to Design tab, as change tpl btn is located there - so, user was at this tab before changing tpl
			$res->addData('edit_link', $this->getModule()->getEditLink( $id, 'ppsPopupTpl' ));
		} else
			$res->pushError ($this->getModel()->getErrors());
		return $res->ajaxExec();
	}
	public function exportForDb() {
		global $wpdb;
		$eol = "\r\n";
		$forPro = (int) reqPps::getVar('for_pro');
		$forPromo = (int) reqPps::getVar('for_promo');
		$forWix = (int) reqPps::getVar('for_wix');
		$selectColumns = array('id','label','active','original_id','params','html','css','img_preview','show_on','show_to','show_pages','type_id','date_created','sort_order');
		$link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		if($forWix) {
			$contentKeys = array('popup_id', 'html', 'css');
			/*foreach($contentKeys as $k) {
				if(isset($selectColumns[array_search($k, $selectColumns)]))
					unset($selectColumns[array_search($k, $selectColumns)]);
			}*/
		}
		$popupList = dbPps::get('SELECT '. implode(',', $selectColumns). ' FROM @__popup WHERE original_id = 0 AND '. ($forPro ? 'id >= 50' : 'id < 50'));
		$valuesArr = array();
		if($forPromo) {
			$str = "\$promoList = array(";
			$keys = array('label', 'img_preview', 'sort_order', 'type_id');
			foreach($popupList as $popup) {
				$addArr = array();
				foreach($keys as $k) {
					if(in_array($k, array('sort_order', 'type_id'))) {	// Numeric values
						$addArr[] = "'$k' => ". $popup[ $k ]. "";
					} else {
						$addArr[] = "'$k' => '". $popup[ $k ]. "'";
					}
				}
				$str .= $eol. "array(". implode(', ', $addArr). "),";
			}
			$str .= $eol. ");";
			echo $str;
			exit();
		}
		$allKeys = array();
		foreach($popupList as $popup) {
			$arr = array();
			$addToKeys = empty($allKeys);
			foreach($popup as $k => $v) {
				if(!in_array($k, $selectColumns)) continue;
				if($addToKeys && (!$forWix || ($forWix && !in_array($k, $contentKeys)))) {
					$allKeys[] = $k;
				}
				//$arr[] = '"'. $wpdb->_real_escape($v). '"';
				$arr[] = '"'. mysqli_real_escape_string($link, $v). '"';
			}
			$valuesArr[] = '('. implode(',', $arr). ')';
		}
		$query = 'INSERT INTO @__popup ('. implode(',', $allKeys). ') VALUES '. $eol. implode(','. $eol, $valuesArr);
		echo $query;
		if($forWix) {
			$allKeys = array();
			$valuesArr = array();
			foreach($popupList as $popup) {
				$arr = array();
				$addToKeys = empty($allKeys);
				foreach($contentKeys as $k) {
					$v = $k == 'popup_id' ? $popup['id'] : $popup[ $k ];
					$arr[] = '"'. $wpdb->_real_escape($v). '"';
				}
				/*foreach($popup as $k => $v) {
					if(!in_array($k, $selectColumns)) continue;
					if($addToKeys && (!$forWix || ($forWix && !in_array($k, $contentKeys)))) {
						$allKeys[] = $k;
					}
					$arr[] = '"'. $wpdb->_real_escape($v). '"';
				}*/
				$valuesArr[] = '('. implode(',', $arr). ')';
			}
			$query = 'INSERT INTO @__popup_content ('. implode(',', $contentKeys). ') VALUES '. $eol. implode(','. $eol, $valuesArr);
			echo $eol. $query;
		}
		exit();
	}
	public function saveAsCopy() {
		$res = new responsePps();
		if(($id = $this->getModel()->saveAsCopy(reqPps::get('post'))) != false) {
			$res->addMessage(__('Done, redirecting to new PopUp...', PPS_LANG_CODE));
			$res->addData('edit_link', $this->getModule()->getEditLink( $id ));
		} else
			$res->pushError ($this->getModel()->getErrors());
		return $res->ajaxExec();
	}
	public function switchActive() {
		$res = new responsePps();
		if($this->getModel()->switchActive(reqPps::get('post'))) {
			$res->addMessage(__('Done', PPS_LANG_CODE));
		} else
			$res->pushError ($this->getModel()->getErrors());
		return $res->ajaxExec();
	}
	public function updateLabel() {
		$res = new responsePps();
		if($this->getModel()->updateLabel(reqPps::get('post'))) {
			$res->addMessage(__('Done', PPS_LANG_CODE));
		} else
			$res->pushError ($this->getModel()->getErrors());
		return $res->ajaxExec();
	}
	public function updateNonce() {
		$res = new responsePps();
		$getFor = reqPps::getVar('get_for', 'post');
		$id = (int) reqPps::getVar('id', 'post');
		$updateFor = array();
		if(!empty($getFor) && !empty($id)) {
			$generateKeys = array(
				'ppsSubscribeForm' => 'subscribe-'. $id,
				'ppsLoginForm' => 'login-'. $id,
				'ppsRegForm' => 'register-'. $id,
			);
			foreach($getFor as $gf) {
				if(isset($generateKeys[ $gf ])) {
					$updateFor[ $gf ] = wp_create_nonce( $generateKeys[ $gf ] );
				}
			}
		}
		if(!empty($updateFor)) {
			$res->addData('update_for', $updateFor);
		}
		return $res->ajaxExec();
	}
	public function getPermissions() {
		return array(
			PPS_USERLEVELS => array(
				PPS_ADMIN => array('createFromTpl', 'getListForTbl', 'remove', 'removeGroup', 'clear', 
					'save', 'getPreviewHtml', 'exportForDb', 'changeTpl', 'saveAsCopy', 'switchActive', 
					'outPreviewHtml', 'updateLabel')
			),
		);
	}
	public function getNoncedMethods() {
		return array('save');
	}
}

