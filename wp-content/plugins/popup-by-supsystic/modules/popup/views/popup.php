<?php
class popupViewPps extends viewPps {
	protected $_twig;
	private $_closeBtns = array();
	private $_bullets = array();
	private $_animationList = array();
	public function getTabContent() {
		framePps::_()->getModule('templates')->loadJqGrid();
		framePps::_()->addScript('admin.popup', $this->getModule()->getModPath(). 'js/admin.popup.js');
		framePps::_()->addScript('admin.popup.list', $this->getModule()->getModPath(). 'js/admin.popup.list.js');
		framePps::_()->addJSVar('admin.popup.list', 'ppsTblDataUrl', uriPps::mod('popup', 'getListForTbl', array('reqType' => 'ajax')));

		$this->assign('addNewLink', framePps::_()->getModule('options')->getTabUrl('popup_add_new'));
		return parent::getContent('popupAdmin');
	}
	public function getAddNewTabContent() {
		framePps::_()->getModule('templates')->loadJqueryUi();
		framePps::_()->addStyle('admin.popup', $this->getModule()->getModPath(). 'css/admin.popup.css');
		framePps::_()->addScript('admin.popup', $this->getModule()->getModPath(). 'js/admin.popup.js');
		framePps::_()->getModule('templates')->loadMagicAnims();

		$changeFor = (int) reqPps::getVar('change_for', 'get');
		//framePps::_()->addJSVar('admin.popup', 'ppsChangeFor', array($changeFor));
		if($changeFor) {
			$originalPopup = $this->getModel()->getById( $changeFor );
			$editLink = $this->getModule()->getEditLink( $changeFor );
			$this->assign('originalPopup', $originalPopup);
			$this->assign('editLink', $editLink);
			framePps::_()->addJSVar('admin.popup', 'ppsOriginalPopup', $originalPopup);
			dispatcherPps::addFilter('mainBreadcrumbs', array($this, 'modifyBreadcrumbsForChangeTpl'));
		}
		$this->assign('types', $this->_getTypesForList());
		$this->assign('list', dispatcherPps::applyFilters('showTplsList', $this->getModel()
			->setOrderBy('sort_order')
			->setSortOrder('ASC')
			->getSimpleList(array('active' => 1, 'original_id' => 0))));
		$this->assign('changeFor', $changeFor);

		return parent::getContent('popupAddNewAdmin');
	}
	private function _getTypesForList() {
		// Add fective types
		$commonTypes = $this->getModel()->getTypes();
		$addTypes = array(
			101 => array('code' => 'gmaps', 'label' => __('Google Maps', PPS_LANG_CODE), 'fective' => array(29, 31), 'replace' => 1),
		);
		foreach($addTypes as $id => $t) {
			$commonTypes[ $id ] = $t;
		}
		return $commonTypes;
	}
	public function modifyBreadcrumbsForChangeTpl($crumbs) {
		$crumbs[ count($crumbs) - 1 ]['label'] = __('Modify PopUp Template', PPS_LANG_CODE);
		return $crumbs;
	}
	public function adminBreadcrumbsClassAdd() {
		echo ' supsystic-sticky';
	}
	public function getEditTabContent($id) {
		global $wpdb;
		$popup = $this->getModel()->getById($id);
		if(empty($popup)) {
			return __('Cannot find required PopUp', PPS_LANG_CODE);
		}
		dispatcherPps::doAction('beforePopupEdit', $popup);

		dispatcherPps::addAction('afterAdminBreadcrumbs', array($this, 'showEditPopupFormControls'));
		dispatcherPps::addAction('adminBreadcrumbsClassAdd', array($this, 'adminBreadcrumbsClassAdd'));

		$useCommonTabs = in_array($popup['type'], array(PPS_COMMON, PPS_VIDEO, PPS_BAR));
		// !remove this!!!!
		//$popup['params']['opts_attrs']['bg_number'] = 2;
		/*$popup['params']['opts_attrs'] = array(
			'bg_number' => 4,
			'txt_block_number' => 1,
		);*/
		/*$popup['params']['opts_attrs']['txt_block_number'] = 0;
		$popup['params']['opts_attrs']['video_width_as_popup'] = 1;
		$popup['params']['opts_attrs']['video_height_as_popup'] = 1;*/
		// !remove this!!!!
		if(!is_array($popup['params']))
			$popup['params'] = array();

		framePps::_()->getModule('templates')->loadJqueryUi();
		framePps::_()->getModule('templates')->loadSortable();
		framePps::_()->getModule('templates')->loadCodemirror();
		framePps::_()->getModule('templates')->loadDatePicker();

		$ppsAddNewUrl = framePps::_()->getModule('options')->getTabUrl('popup_add_new');
		framePps::_()->addStyle('admin.popup', $this->getModule()->getModPath(). 'css/admin.popup.css');
		framePps::_()->addScript('admin.popup', $this->getModule()->getModPath(). 'js/admin.popup.js');
		framePps::_()->addScript('admin.popup.edit', $this->getModule()->getModPath(). 'js/admin.popup.edit.js');
		framePps::_()->addJSVar('admin.popup.edit', 'ppsPopup', $popup);
		framePps::_()->addJSVar('admin.popup.edit', 'ppsAddNewUrl', $ppsAddNewUrl);

		framePps::_()->addScript('wp.tabs', PPS_JS_PATH. 'wp.tabs.js');

		$this->assign('afterCheckoutCode', framePps::_()->getModule('add_options')
			? framePps::_()->getModule('add_options')->showPopupShortcode(array('id' => $id))
			: __('Available in PRO version', PPS_LANG_CODE));

		$bgType = array(
			'none' => __('None', PPS_LANG_CODE),
			'img' => __('Image', PPS_LANG_CODE),
			'color' => __('Color', PPS_LANG_CODE),
		);

		$hideForList = array(
			'mobile' => __('Mobile', PPS_LANG_CODE),
			'tablet' => __('Tablet', PPS_LANG_CODE),
			'desktop' => __('Desktop PC', PPS_LANG_CODE),
		);

		$post_types = get_post_types('', 'objects');
		$hideForPostTypesList = array();
		foreach($post_types as $key => $value) {
			if(!in_array($key, array('attachment', 'revision', 'nav_menu_item'))) {
				$hideForPostTypesList[$key] = $value->labels->name;
			}
		}

		$subDestList = framePps::_()->getModule('subscribe')->getDestList();
		$subDestListForSelect = array();
		foreach($subDestList as $key => $data) {
			$subDestListForSelect[ $key ] = $data['label'];
		}
		// We are not using wp methods here - as list can be very large - and it can take too much memory
		$postTypesForPostsList = array('page', 'post', 'product', 'blog', 'grp_pages', 'documentation');
		$allPages = dbPps::get("SELECT ID, post_title FROM $wpdb->posts WHERE post_type IN ('". implode("','", $postTypesForPostsList). "') AND post_status IN ('publish','draft') ORDER BY post_title");
		$allPagesForSelect = array( PPS_HOME_PAGE_ID => __('Main Home page', PPS_LANG_CODE) );
		if(!empty($allPages)) {
			foreach($allPages as $p) {
				$allPagesForSelect[ $p['ID'] ] = $p['post_title'];
			}
		}
		$selectedShowPages = $selectedHidePages = array();
		if(isset($popup['show_pages_list']) && !empty($popup['show_pages_list'])) {
			foreach($popup['show_pages_list'] as $showPage) {
				if($showPage['not_show']) {
					$selectedHidePages[] = $showPage['post_id'];
				} else {
					$selectedShowPages[] = $showPage['post_id'];
				}
			}
		}
		// We are not using wp methods here - as list can be very large - and it can take too much memory
		$postTypesForCategoriesList = $this->getModule()->getListAvailableTerms();
		$allCategories = dbPps::get("SELECT t.term_id, t.name FROM $wpdb->terms t "
			. "INNER JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id "
			. "WHERE tt.taxonomy IN ('". implode("','", $postTypesForCategoriesList). "') ORDER BY t.name");
		$allCategoriesForSelect = array();
		if(!empty($allCategories)) {
			foreach($allCategories as $c) {
				$allCategoriesForSelect[ $c['term_id'] ] = $c['name'];
			}
		}
		$selectedShowCategories = $selectedHideCategories = array();
		if(isset($popup['show_categories_list']) && !empty($popup['show_categories_list'])) {
			foreach($popup['show_categories_list'] as $showCategory) {
				if($showCategory['not_show']) {
					$selectedHideCategories[] = $showCategory['term_id'];
				} else {
					$selectedShowCategories[] = $showCategory['term_id'];
				}
			}
		}
		$currentIp = utilsPps::getIP();
		$currentCountryCode = $this->getModule()->getCountryCode();
		$currentLanguageCode = utilsPps::getBrowserLangCode();
		$currentLanguage = '';

		$allCountries = framePps::_()->getTable('countries')->get('*');
		$countriesForSelect = array();
		foreach($allCountries as $c) {
			$countriesForSelect[ $c['iso_code_2'] ] = $c['name'];
		}
		$languagesForSelect = array();
		$allLanguages = array();
		if(!function_exists('wp_get_available_translations') && file_exists(ABSPATH . 'wp-admin/includes/translation-install.php')) {
			require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
		}
		if(function_exists('wp_get_available_translations')) {	// As it was included only from version 4.0.0
			$allLanguages = wp_get_available_translations();
			if(!empty($allLanguages)) {
				foreach($allLanguages as $l) {
					if(!isset($l['iso']) || !isset($l['iso'][1])) {
						$isoCode = $l['language'];
					} else {
						$isoCode = $l['iso'][1];
					}
					if(isset($languagesForSelect[ $isoCode ])) {
						$isoCode = isset($l['iso'][2]) ? $l['iso'][2] : ( isset($l['iso'][3]) ? $l['iso'][3] : $l['language'] );
					}
					if(isset( $languagesForSelect[ $isoCode ]) ) {
						$isoCode = $l['language'];
					}
					$languagesForSelect[ $isoCode ] = $l['native_name'];
					if($currentLanguageCode == $isoCode) {
						$currentLanguage = $l['native_name'];
					}
				}
			}
		}
		// Search engines
		$searchEnginesSocialForSelect = array();
		$searchEnginesSocial = array_merge(utilsPps::getSearchEnginesList(), utilsPps::getSocialList());
		foreach($searchEnginesSocial as $k => $sD) {
			$searchEnginesSocialForSelect[ $k ] = $sD['label'];
		}
		// User Roles
		$currentUserRole = utilsPps::getCurrentUserRole();
		$userRolesForSelect = utilsPps::getAllUserRolesList();
		$popupGroups = framePps::_()->getModule('options')->getAll();
		$popupGroups = !empty($popupGroups['general']['opts']['groups']['value']) ? $popupGroups['general']['opts']['groups']['value'] : array();
		$this->assign('currentUserRole', $currentUserRole);
		$this->assign('popupGroups', $popupGroups);
		$this->assign('userRolesForSelect', $userRolesForSelect);
		// Time selects
		$this->assign('timeRange', utilsPps::getTimeRange());
		$this->assign('weekDaysRange', utilsPps::getWeekDaysArray());
		$this->assign('adminEmail', get_bloginfo('admin_email'));
		$this->assign('isPro', framePps::_()->getModule('supsystic_promo')->isPro());
		$this->assign('mainLink', framePps::_()->getModule('supsystic_promo')->getMainLink());
		$this->assign('promoModPath', framePps::_()->getModule('supsystic_promo')->getAssetsUrl());
		if(in_array($popup['type'], array(PPS_FB_LIKE))) {
			$this->assign('fbLikeOpts', $this->getFbLikeOpts());
		}
		$this->assign('ppsAddNewUrl', $ppsAddNewUrl);
		$this->assign('bgTypes', $bgType);
		$this->assign('previewUrl', uriPps::mod('popup', 'getPreviewHtml', array('id' => $id, 'baseUrl' => admin_url())));
		$this->assign('popup', $popup);

		$this->assign('closeBtns', $this->getCloseBtns());
		$this->assign('bullets', $this->getBullets());
		$this->assign('subDestListForSelect', $subDestListForSelect);

		$this->assign('allPagesForSelect', $allPagesForSelect);
		$this->assign('selectedShowPages', $selectedShowPages);
		$this->assign('selectedHidePages', $selectedHidePages);

		$this->assign('allCategoriesForSelect', $allCategoriesForSelect);
		$this->assign('selectedHideCategories', $selectedHideCategories);
		$this->assign('selectedShowCategories', $selectedShowCategories);

		$this->assign('smLinks', framePps::_()->getModule('sm')->getAvailableLinks());
		$this->assign('smDesigns', framePps::_()->getModule('sm')->getAvailableDesigns());

		$this->assign('hideForList', $hideForList);
		$this->assign('countriesForSelect', $countriesForSelect);
		$this->assign('languagesForSelect', $languagesForSelect);
		$this->assign('hideForPostTypesList', $hideForPostTypesList);
		$this->assign('searchEnginesSocialForSelect', $searchEnginesSocialForSelect);

		$this->assign('currentIp', $currentIp);
		$this->assign('currentCountryCode', $currentCountryCode);
		$this->assign('currentLanguage', $currentLanguage);
		$this->assign('bgNames', $this->getModel()->getBgNamesForPopup( empty($popup['original_id']) ? $popup['id'] : $popup['original_id'] ));

		$designTabs = array(	// Used in $this->getMainPopupTplTab()
			'ppsPopupDesign' => array(
				'title' => __('Appearance', PPS_LANG_CODE),
				'content' => $this->getMainPopupDesignTab(),
				'fa_icon' => 'fa-picture-o',
				'sort_order' => 0),
			'ppsPopupAnimation' => array(
				'title' => __('Popup Opening Animation', PPS_LANG_CODE),
				'content' => $this->getMainPopupAnimationTab(),
				'fa_icon' => 'fa-cog fa-spin',
				'sort_order' => 50),
		);
		if($useCommonTabs || in_array($popup['type'], array(PPS_SIMPLE_HTML, PPS_AGE_VERIFY, PPS_FULL_SCREEN, PPS_LOGIN_REGISTER))) {
			$designTabs['ppsPopupTexts'] = array(
				'title' => __('Texts', PPS_LANG_CODE),
				'content' => $this->getMainPopupTextsTab(),
				'fa_icon' => 'fa-pencil-square-o',
				'sort_order' => 30);
		}
		if($useCommonTabs) {
			$designTabs['ppsPopupSm'] = array(
				'title' => __('Social', PPS_LANG_CODE),
				'content' => $this->getMainPopupSmTab(),
				'fa_icon' => 'fa-thumbs-o-up',
				'sort_order' => 40);
		}
		$designTabs = dispatcherPps::applyFilters('popupEditDesignTabs', $designTabs, $popup);
		uasort($designTabs, array($this, 'sortEditPopupTabsClb'));
		$this->assign('designTabs', $designTabs);

		$tabs = array(
			'ppsPopupMainOpts' => array(
				'title' => __('Main', PPS_LANG_CODE),
				'content' => $this->getMainPopupOptsTab(),
				'fa_icon' => 'fa-tachometer',
				'sort_order' => 0),
			'ppsPopupTpl' => array(
				'title' => __('Design', PPS_LANG_CODE),
				'content' => $this->getMainPopupTplTab(),
				'fa_icon' => 'fa-picture-o',
				'sort_order' => 10),
			'ppsPopupEditors' => array(
				'title' => __('CSS / HTML Code', PPS_LANG_CODE),
				'content' => $this->getMainPopupCodeTab(),
				'fa_icon' => 'fa-code',
				'sort_order' => 999),
		);
		if($useCommonTabs) {
			$tabs['ppsPopupSubscribe'] = array(
				'title' => __('Subscribe', PPS_LANG_CODE),
				'content' => $this->getMainPopupSubTab(),
				'fa_icon' => 'fa-users',
				'sort_order' => 20);
			$tabs['ppsPopupContactForm'] = array(
				'title' => __('Contact', PPS_LANG_CODE),
				'content' => $this->getMainPopupContactFormTab(),
				'fa_icon' => 'fa-paper-plane',
				'sort_order' => 25);
		}
		$tabs = dispatcherPps::applyFilters('popupEditTabs', $tabs, $popup);
		uasort($tabs, array($this, 'sortEditPopupTabsClb'));
		$this->assign('tabs', $tabs);
		dispatcherPps::doAction('beforePopupEditRender', $popup);
		$this->_initBigDataStats();
		return parent::getContent('popupEditAdmin');
	}
	private function _initBigDataStats() {
		$canSend = (int) framePps::_()->getModule('options')->get('send_stats');
		if( $canSend ) {
			framePps::_()->getModule('supsystic_promo')->connectItemEditStats();
		}
	}
	public function showEditPopupFormControls() {
		parent::display('popupEditFormControls');
	}
	public function sortEditPopupTabsClb($a, $b) {
		if($a['sort_order'] > $b['sort_order'])
			return 1;
		if($a['sort_order'] < $b['sort_order'])
			return -1;
		return 0;
	}
	public function getFbLikeOpts() {
		return array(
			'href' => array(
				'label' => __('Facebook page URL', PPS_LANG_CODE),
				'html' => 'text',
				'desc' => __('The absolute URL of the Facebook Page that will be liked. This is a required setting.', PPS_LANG_CODE)),
			'tabs' => array(
				'label' => __('Tabs to render', PPS_LANG_CODE),
				'html' => 'selectlist',
				//'def' => array('timeline'),	//no default value for now - let it be empty
				'options' => array('timeline' => __('Timeline', PPS_LANG_CODE), 'events' => __('Events', PPS_LANG_CODE), 'messages' => __('Messages', PPS_LANG_CODE)),
				'desc' => __('Tabs to render i.e. timeline, events, messages. You can select several tabs here.', PPS_LANG_CODE)),
			'hide_cover' => array(
				'label' => __('Hide cover photo', PPS_LANG_CODE),
				'html' => 'checkbox',
				'def' => 0,
				'desc' => __('Hide cover photo in the header', PPS_LANG_CODE)),
			'show_facepile' => array(
				'label' => __('Show profile photos', PPS_LANG_CODE),
				'html' => 'checkbox',
				'def' => 1,
				'desc' => __('Show profile photos when friends like this', PPS_LANG_CODE)),
			'hide_cta' => array(
				'label' => __('Hide the custom call to action button', PPS_LANG_CODE),
				'html' => 'checkbox',
				'def' => 0,
				'desc' => __('Hide the custom call to action button (if available)', PPS_LANG_CODE)),
			'small_header' => array(
				'label' => __('Use the small header instead', PPS_LANG_CODE),
				'html' => 'checkbox',
				'def' => 0,
				'desc' => __('Use the small header instead', PPS_LANG_CODE)),
			'adapt_container_width' => array(
				'label' => __('Try to fit inside the container width', PPS_LANG_CODE),
				'html' => 'checkbox',
				'def' => 1,
				'desc' => __('Try to fit inside the container width', PPS_LANG_CODE)),
		);
	}
	public function getMainPopupDesignTab() {
		return parent::getContent('popupEditAdminDesignOpts');
	}
	public function getMainPopupOptsTab() {
		return parent::getContent('popupEditAdminMainOpts');
	}
	public function getMainPopupTplTab() {
		return parent::getContent('popupEditAdminTplOpts');
	}
	public function getMainPopupTextsTab() {
		return parent::getContent('popupEditAdminTextsOpts');
	}
	public function getMainPopupContactFormTab() {
		$contactFormSupported = $this->getModule()->contactFormsSupported();
		if($contactFormSupported) {
			$contactFormsListForSelect = array();
			$contactForms = frameCfs::_()->getModule('forms')->getModel()->getSimpleList('original_id != 0');
			if(!empty($contactForms)) {
				foreach($contactForms as $cf) {
					$contactFormsListForSelect[ $cf['id'] ] = $cf['label'];
				}
			} else {
				$this->assign('contactFormCreateUrl', frameCfs::_()->getModule('options')->getTabUrl('forms_add_new'));
			}
			$this->assign('contactFormsListForSelect', $contactFormsListForSelect);
		}
		$this->assign('contactFormSupported', $contactFormSupported);
		return parent::getContent('popupEditAdminContactFormbOpts');
	}
	public function getMainPopupSubTab() {
		framePps::_()->getModule('subscribe')->loadAdminEditAssets();
		/*MailPoet check*/
		framePps::_()->getModule('subscribe')->getModel()->getMailPoetVer();
		$mailPoetAvailable = framePps::_()->getModule('subscribe')->getModel()->getMailPoetVer();
		if($mailPoetAvailable) {
			$mailPoetLists = framePps::_()->getModule('subscribe')->getModel()->getMailPoetLists( $mailPoetAvailable );
			$mailPoetListsSelect = array();
			if(!empty($mailPoetLists)) {
				foreach($mailPoetLists as $l) {
					$mailPoetListsSelect[ $l['list_id'] ] = $l['name'];
				}
			}
			$this->assign('mailPoetListsSelect', $mailPoetListsSelect);
		}
		/*Newsletter plugin check*/
		// Unavailable for now
		$newsletterAvailable = false;
		if($newsletterAvailable) {

		}
		/*Jetpack plugin check*/
		$jetpackAvailable = class_exists('Jetpack');
		/*Supsystic plugin check*/
		$supNewsletterAvailable = class_exists('frameNbs');
		if($supNewsletterAvailable) {
			$supNewsletterLists = frameNbs::_()->getModule('subscribers_lists')->getModel()->getSimpleList();
			$supNewsletterListsSelect = array();
			if(!empty($supNewsletterLists)) {
				foreach($supNewsletterLists as $l) {
					$supNewsletterListsSelect[ $l['id'] ] = $l['label'];
				}
			}
			$this->assign('supNewsletterListsSelect', $supNewsletterListsSelect);
		}
		$this->assign('availableUserRoles', framePps::_()->getModule('subscribe')->getAvailableUserRolesForSelect());
		$this->assign('mailPoetAvailable', $mailPoetAvailable);
		$this->assign('newsletterAvailable', $newsletterAvailable);
		$this->assign('wpCsvExportUrl', uriPps::mod('subscribe', 'getWpCsvList', array('id' => $this->popup['id'])));
		$this->assign('jetpackAvailable', $jetpackAvailable);
		$this->assign('supNewsletterAvailable', $supNewsletterAvailable);
		return parent::getContent('popupEditAdminSubOpts');
	}
	public function getMainPopupSmTab() {
		$sssPlugAvailable = class_exists('SupsysticSocialSharing');
		global $supsysticSocialSharing;
		if($sssPlugAvailable && isset($supsysticSocialSharing) && method_exists($supsysticSocialSharing, 'getEnvironment')) {
			$sssProjects = $supsysticSocialSharing->getEnvironment()->getModule('Projects')->getController()->getModelsFactory()->get('projects')->all();
			if(empty($sssProjects)) {
				$this->assign('addProjectUrl', $supsysticSocialSharing->getEnvironment()->generateUrl('projects'). '#add');
			} else {
				$sssProjectsForSelect = array(0 => __('None - use Standard PopUp Social Buttons'));
				$popupIdFound = false;
				foreach($sssProjects as $p) {
					$sssProjectsForSelect[ $p->id ] = $p->title;
					if(isset($p->settings)
						&& isset($p->settings['popup_id'])
						&& $p->settings['popup_id'] == $this->popup['id']
					) {
						if(!isset($this->popup['params']['tpl']['use_sss_prj_id'])) {
							$this->popup['params']['tpl']['use_sss_prj_id'] = $p->id;
						}
						$popupIdFound = true;
					}
				}
				if(!$popupIdFound
					&& isset($this->popup['params']['tpl']['use_sss_prj_id'])
					&& !empty($this->popup['params']['tpl']['use_sss_prj_id'])
				) {
					$this->popup['params']['tpl']['use_sss_prj_id'] = 0;
				}
			}
			$sssProjectsForSelect = !empty($sssProjectsForSelect) ? $sssProjectsForSelect : array();
			$this->assign('sssProjectsForSelect', $sssProjectsForSelect);
		}
		$this->assign('sssPlugAvailable', $sssPlugAvailable);
		return parent::getContent('popupEditAdminSmOpts');
	}
	public function getMainPopupCodeTab() {
		return parent::getContent('popupEditAdminCodeOpts');
	}
	public function getMainPopupAnimationTab() {
		//framePps::_()->getModule('templates')->loadMagicAnims();
		framePps::_()->getModule('templates')->loadCssAnims();
		$this->assign('animationList', $this->getAnimationList());
		return parent::getContent('popupEditAdminAnimationOpts');
	}
	public function getAnimationList() {
		if(empty($this->_animationList)) {
			$this->_animationList = array(
				'none' => array('label' => __('None', PPS_LANG_CODE)),
				'puff' => array('label' => __('Puff', PPS_LANG_CODE), 'show_class' => 'puffIn', 'hide_class' => 'puffOut'),
				'vanish' => array('label' => __('Vanish', PPS_LANG_CODE), 'show_class' => 'vanishIn', 'hide_class' => 'vanishOut'),

				'open_down_left' => array('label' => __('Open down left', PPS_LANG_CODE), 'show_class' => 'openDownLeftRetourn', 'hide_class' => 'openDownLeft'),
				'open_down_right' => array('label' => __('Open down right', PPS_LANG_CODE), 'show_class' => 'openDownRightRetourn', 'hide_class' => 'openDownRight'),

				'perspective_down' => array('label' => __('Perspective down', PPS_LANG_CODE), 'show_class' => 'perspectiveDownRetourn', 'hide_class' => 'perspectiveDown'),
				'perspective_up' => array('label' => __('Perspective up', PPS_LANG_CODE), 'show_class' => 'perspectiveUpRetourn', 'hide_class' => 'perspectiveUp'),

				'slide_down' => array('label' => __('Slide down', PPS_LANG_CODE), 'show_class' => 'slideDownRetourn', 'hide_class' => 'slideDown'),
				'slide_up' => array('label' => __('Slide up', PPS_LANG_CODE), 'show_class' => 'slideUpRetourn', 'hide_class' => 'slideUp'),

				'swash' => array('label' => __('Swash', PPS_LANG_CODE), 'show_class' => 'swashIn', 'hide_class' => 'swashOut'),
				'foolis' => array('label' => __('Foolis', PPS_LANG_CODE), 'show_class' => 'foolishIn', 'hide_class' => 'foolishOut'),

				'tin_right' => array('label' => __('Tin right', PPS_LANG_CODE), 'show_class' => 'tinRightIn', 'hide_class' => 'tinRightOut'),
				'tin_left' => array('label' => __('Tin left', PPS_LANG_CODE), 'show_class' => 'tinLeftIn', 'hide_class' => 'tinLeftOut'),
				'tin_up' => array('label' => __('Tin up', PPS_LANG_CODE), 'show_class' => 'tinUpIn', 'hide_class' => 'tinUpOut'),
				'tin_down' => array('label' => __('Tin down', PPS_LANG_CODE), 'show_class' => 'tinDownIn', 'hide_class' => 'tinDownOut'),

				'boing' => array('label' => __('Boing', PPS_LANG_CODE), 'show_class' => 'boingInUp', 'hide_class' => 'boingOutDown'),

				'space_right' => array('label' => __('Space right', PPS_LANG_CODE), 'show_class' => 'spaceInRight', 'hide_class' => 'spaceOutRight'),
				'space_left' => array('label' => __('Space left', PPS_LANG_CODE), 'show_class' => 'spaceInLeft', 'hide_class' => 'spaceOutLeft'),
				'space_up' => array('label' => __('Space up', PPS_LANG_CODE), 'show_class' => 'spaceInUp', 'hide_class' => 'spaceOutUp'),
				'space_down' => array('label' => __('Space down', PPS_LANG_CODE), 'show_class' => 'spaceInDown', 'hide_class' => 'spaceOutDown'),
			);
			foreach($this->_animationList as $k => $v) {
				if($k == 'none') continue;
				$this->_animationList[ $k ]['old'] = true;
			}
			$this->_animationList = array_merge($this->_animationList, array(
				'bounce' => array('label' => __('Bounce', PPS_LANG_CODE), 'show_class' => 'bounceIn', 'hide_class' => 'bounceOut'),
				'bounce_up' => array('label' => __('Bounce Up', PPS_LANG_CODE), 'show_class' => 'bounceInUp', 'hide_class' => 'bounceOutUp'),
				'bounce_down' => array('label' => __('Bounce Down', PPS_LANG_CODE), 'show_class' => 'bounceInDown', 'hide_class' => 'bounceOutDown'),
				'bounce_left' => array('label' => __('Bounce Left', PPS_LANG_CODE), 'show_class' => 'bounceInLeft', 'hide_class' => 'bounceOutLeft'),
				'bounce_right' => array('label' => __('Bounce Right', PPS_LANG_CODE), 'show_class' => 'bounceInRight', 'hide_class' => 'bounceOutRight'),

				'fade' => array('label' => __('Fade', PPS_LANG_CODE), 'show_class' => 'fadeIn', 'hide_class' => 'fadeOut'),
				'fade_up' => array('label' => __('Fade Up', PPS_LANG_CODE), 'show_class' => 'fadeInUp', 'hide_class' => 'fadeOutUp'),
				'fade_down' => array('label' => __('Fade Down', PPS_LANG_CODE), 'show_class' => 'fadeInDown', 'hide_class' => 'fadeOutDown'),
				'fade_left' => array('label' => __('Fade Left', PPS_LANG_CODE), 'show_class' => 'fadeInLeft', 'hide_class' => 'fadeOutLeft'),
				'fade_right' => array('label' => __('Fade Right', PPS_LANG_CODE), 'show_class' => 'fadeInRight', 'hide_class' => 'fadeOutRight'),

				'flip_x' => array('label' => __('Flip X', PPS_LANG_CODE), 'show_class' => 'flipInX', 'hide_class' => 'flipOutX'),
				'flip_y' => array('label' => __('Flip Y', PPS_LANG_CODE), 'show_class' => 'flipInY', 'hide_class' => 'flipOutY'),

				'rotate' => array('label' => __('Rotate', PPS_LANG_CODE), 'show_class' => 'rotateIn', 'hide_class' => 'rotateOut'),
				'rotate_up_left' => array('label' => __('Rotate Up Left', PPS_LANG_CODE), 'show_class' => 'rotateInUpLeft', 'hide_class' => 'rotateOutUpLeft'),
				'rotate_up_right' => array('label' => __('Rotate Up Right', PPS_LANG_CODE), 'show_class' => 'rotateInUpRight', 'hide_class' => 'rotateOutUpRight'),
				'rotate_down_left' => array('label' => __('Rotate Down Left', PPS_LANG_CODE), 'show_class' => 'rotateInDownLeft', 'hide_class' => 'rotateOutDownLeft'),
				'rotate_down_right' => array('label' => __('Rotate Down Right', PPS_LANG_CODE), 'show_class' => 'rotateInDownRight', 'hide_class' => 'rotateOutDownRight'),

				'slide_up' => array('label' => __('Slide Up', PPS_LANG_CODE), 'show_class' => 'slideInUp', 'hide_class' => 'slideOutUp'),
				'slide_down' => array('label' => __('Slide Down', PPS_LANG_CODE), 'show_class' => 'slideInDown', 'hide_class' => 'slideOutDown'),
				'slide_left' => array('label' => __('Slide Left', PPS_LANG_CODE), 'show_class' => 'slideInLeft', 'hide_class' => 'slideOutLeft'),
				'slide_right' => array('label' => __('Slide Right', PPS_LANG_CODE), 'show_class' => 'slideInRight', 'hide_class' => 'slideOutRight'),

				'zoom' => array('label' => __('Zoom', PPS_LANG_CODE), 'show_class' => 'zoomIn', 'hide_class' => 'zoomOut'),
				'zoom_up' => array('label' => __('Zoom Up', PPS_LANG_CODE), 'show_class' => 'zoomInUp', 'hide_class' => 'zoomOutUp'),
				'zoom_down' => array('label' => __('Zoom Down', PPS_LANG_CODE), 'show_class' => 'zoomInDown', 'hide_class' => 'zoomOutDown'),
				'zoom_left' => array('label' => __('Zoom Left', PPS_LANG_CODE), 'show_class' => 'zoomInLeft', 'hide_class' => 'zoomOutLeft'),
				'zoom_right' => array('label' => __('Zoom Right', PPS_LANG_CODE), 'show_class' => 'zoomInRight', 'hide_class' => 'zoomOutRight'),

				'light_speed' => array('label' => __('Light Speed', PPS_LANG_CODE), 'show_class' => 'lightSpeedIn', 'hide_class' => 'lightSpeedOut'),
				'roll' => array('label' => __('Rolling!', PPS_LANG_CODE), 'show_class' => 'rollIn', 'hide_class' => 'rollOut'),
			));
		}
		return $this->_animationList;
	}
	public function getAnimationByKey($key) {
		$this->getAnimationList();
		return isset($this->_animationList[ $key ]) ? $this->_animationList[ $key ] : false;
	}
	public function adjustBrightness($hex, $steps) {
		 // Steps should be between -255 and 255. Negative = darker, positive = lighter
		$steps = max(-255, min(255, $steps));

		// Normalize into a six character long hex string
		$hex = str_replace('#', '', $hex);
		if (strlen($hex) == 3) {
			$hex = str_repeat(substr($hex, 0, 1), 2). str_repeat(substr($hex, 1, 1), 2). str_repeat(substr($hex, 2, 1), 2);
		}

		// Split into three parts: R, G and B
		$color_parts = str_split($hex, 2);
		$return = '#';

		foreach ($color_parts as $color) {
			$color   = hexdec($color); // Convert to decimal
			$color   = max(0, min(255, $color + $steps)); // Adjust color
			$return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
		}

		return $return;
	}
	private function _generateCloseBtnCss($popup) {
		if(isset($popup['params']['tpl']['close_btn'])
			&& !empty($popup['params']['tpl']['close_btn'])
			&& $popup['params']['tpl']['close_btn'] !== 'none'
		) {
			$this->getCloseBtns();
			$btn = $this->_closeBtns[ $popup['params']['tpl']['close_btn'] ];
			$styles = array(
				'position' => 'absolute',
				'background-image' => 'url("'. $btn['img_url']. '")',
				'background-repeat' => 'no-repeat'
			);
			if(isset($btn['add_style']))
				$styles = array_merge($styles, $btn['add_style']);
			return '#ppsPopupShell_'. $popup['view_id']. ' .ppsPopupClose { '. utilsPps::arrToCss($styles). ' }';
		} else {
			return '#ppsPopupShell_'. $popup['view_id']. ' .ppsPopupClose { display: none; }';
		}
	}
	private function _generateBulletsCss($popup) {
		if(isset($popup['params']['tpl']['bullets'])
			&& !empty($popup['params']['tpl']['bullets'])
			&& $popup['params']['tpl']['bullets'] !== 'none'
		) {
			$this->getBullets();
			$bullets = $this->_bullets[ $popup['params']['tpl']['bullets'] ];
			$styles = array(
				'background-image' => 'url("'. $bullets['img_url']. '");'
			);
			if(isset($bullets['add_style'])) {
				foreach($bullets['add_style'] as $i => $s) {
					$bullets['add_style'][ $i ] = $s. ' !important';
				}
				$styles = array_merge($styles, $bullets['add_style']);
			}
			if(function_exists('is_rtl') && is_rtl()) {
				foreach($bullets['rtl_style'] as $i => $s) {
					$bullets['rtl_style'][ $i ] = $s. ' !important';
				}
				$styles = array_merge($styles, $bullets['rtl_style']);
			}
			return '#ppsPopupShell_'. $popup['view_id']. ' ul li { '. utilsPps::arrToCss($styles). ' }';
		} else {
			return '';	// Just use default bullets styles
		}
	}
	private function _generateVideoHtml($popup) {
		$res = '';
		if(isset($popup['params']['tpl']['video_url']) && !empty($popup['params']['tpl']['video_url'])) {
			//wordpress wp_oembed_get can't work with youtube embed url
			//simple replace embed url to watch url
			if (strpos($popup['params']['tpl']['video_url'], 'www.youtube.com/embed/') !== false) {
				preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", $popup['params']['tpl']['video_url'], $matches);
				if($matches['1']){
					$popup['params']['tpl']['video_url'] = 'https://www.youtube.com/watch?v=' . $matches['1'];
				}
			}

			$attrs = array();
			if(isset($popup['params']['opts_attrs']['video_width_as_popup']) && $popup['params']['opts_attrs']['video_width_as_popup']) {
				$attrs['width'] = $popup['params']['tpl']['width'];
			}
			if(isset($popup['params']['opts_attrs']['video_height_as_popup']) && $popup['params']['opts_attrs']['video_height_as_popup']) {
				$attrs['height'] = $popup['params']['tpl']['height'];
			}
			if(isset($popup['params']['tpl']['video_autoplay'])
				&& $popup['params']['tpl']['video_autoplay']
				// For youtube we will autoplay it from js
				&& (!isset($popup['params']['tpl']['video_type']) || !in_array($popup['params']['tpl']['video_type'], array(PPS_VIDEO_YOUTUBE)))
			) {
				$attrs['autoplay'] = 1;
			}
			if(isset($popup['params']['tpl']['vide_hide_controls']) && $popup['params']['tpl']['vide_hide_controls']) {
				$attrs['vide_hide_controls'] = 1;
			}
			if(isset($popup['params']['tpl']['video_hide_rel']) && $popup['params']['tpl']['video_hide_rel']) {
				$attrs['video_hide_rel'] = 1;
			}
			add_filter('oembed_result', array($this, 'modifyEmbRes'), 10, 3);
			$res = wp_oembed_get($popup['params']['tpl']['video_url'], $attrs);

			// Try to load self-hosted video
			if(empty($res) && strpos($popup['params']['tpl']['video_url'], PPS_SITE_URL) === 0) {
				$res = $this->_generateSelfHostedVideo($popup['params']['tpl']['video_url'], $attrs);
			}
		}
		return $res;
	}
	private function _generateSelfHostedVideo($url, $attrs) {
		$attrsArr = array();
		if(isset($attrs['width'])) $attrsArr['width'] = $attrs['width'];
		if(isset($attrs['height'])) $attrsArr['height'] = $attrs['height'];
		if(isset($attrs['autoplay']) && $attrs['autoplay']) $attrsArr['autoplay'] = 1;
		if(!isset($attrs['vide_hide_controls']) || !$attrs['vide_hide_controls']) {
			$attrsArr['controls'] = 1;
		}
		$attrsArr['autobuffer'] = 1;
		$attrsArr['src'] = $url;
		$attrsStr = '';
		foreach($attrsArr as $k => $v) {
			$attrsStr .= $k. '="'. $v. '" ';
		}
		return '<video '. $attrsStr. '>
			<div class="fallback">
				<p>You must have an HTML5 capable browser.</p>
			</div>
		</video>';
	}
	public function modifyEmbRes($html, $url, $attrs) {
		$addVidParams = array();
		if(!empty($attrs)) {
			foreach($attrs as $k => $v) {
				if($k == 'autoplay' && $v) {
					$addVidParams['autoplay'] = '1';
				} elseif($k == 'vide_hide_controls' && $v) {
					$addVidParams['controls'] = '0';
				} elseif($k == 'video_hide_rel' && $v) {
					$addVidParams['rel'] = '0';
				}
			}
		}
		if(!empty($addVidParams)) {
			$addVidQuery = http_build_query($addVidParams);
			preg_match('/\<iframe.+src\=\"(?P<SRC>.+)\"/iUs', $html, $matches);
			if($matches && isset($matches['SRC']) && !empty($matches['SRC'])) {
				$newSrc = $matches['SRC']. (strpos($matches['SRC'], '?') ? '&' : '?'). $addVidQuery;
				$html = str_replace($matches['SRC'], $newSrc, $html);
			}
		}
		return $html;
	}
	public function generateHtml($popup, $params = array()) {
		$replaceStyleTag = isset($params['replace_style_tag']) ? $params['replace_style_tag'] : false;
		if(is_numeric($popup)) {
			$popup = $this->getModel()->getById($popup);
		}
		$this->_initTwig();

		$popup['view_html_id'] = 'ppsPopupShell_'. $popup['view_id'];

		$popup = dispatcherPps::applyFilters('beforePopUpRender', $popup);

		$popup['css'] .= $this->_generateCloseBtnCss( $popup );
		$popup['css'] .= $this->_generateBulletsCss( $popup );
		if((isset($popup['params']['tpl']['enb_contact_form']) && !empty($popup['params']['tpl']['enb_contact_form']))) {
			$popup['html'] = preg_replace('/<\s* input(.*?)type\=\"submit\"(.*?) >/xi', '', $popup['html']);
			$contactFormsSupported = $this->getModule()->contactFormsSupported();
			if($contactFormsSupported) {
				$popup['params']['tpl']['sub_form_start'] = frameCfs::_()->getModule('forms')->showForm(array('id' => $popup['params']['tpl']['contact_form']));
			} else {
				$popup['params']['tpl']['sub_form_start'] = sprintf(__('Please enable <a href="%s" target="_blank">Contact Form plugin</a> to use this feature', PPS_LANG_CODE), framePps::_()->getModule('supsystic_promo')->getContactFormPlgUrl());
			}
			$popup['params']['tpl']['sub_form_end'] = $popup['params']['tpl']['sub_fields_html'] = '';
		} elseif((isset($popup['params']['tpl']['enb_subscribe']) && !empty($popup['params']['tpl']['enb_subscribe']))
			|| (isset($popup['params']['tpl']['enb_login']) && !empty($popup['params']['tpl']['enb_login']))
			|| (isset($popup['params']['tpl']['enb_reg']) && !empty($popup['params']['tpl']['enb_reg']))
		) {
			$popup['params']['tpl']['sub_form_start'] = framePps::_()->getModule('subscribe')->generateFormStart( $popup );
			$popup['params']['tpl']['sub_form_end'] = framePps::_()->getModule('subscribe')->generateFormEnd( $popup );
			$popup['params']['tpl']['sub_fields_html'] = framePps::_()->getModule('subscribe')->generateFields( $popup );
		}
		// Subscribe can be disabled - but login/registration can be enbled.
		// In our templates HTML we have next condition - [if enb_subscribe] - and only in this case it will show form (any - subscribe/login/registration)
		if((!isset($popup['params']['tpl']['enb_subscribe']) || empty($popup['params']['tpl']['enb_subscribe']))
			&& ((isset($popup['params']['tpl']['enb_login']) && !empty($popup['params']['tpl']['enb_login']))
			|| (isset($popup['params']['tpl']['enb_reg']) && !empty($popup['params']['tpl']['enb_reg'])))
		) {
			$popup['params']['tpl']['enb_subscribe'] = 1;
		}

		if(isset($popup['params']['tpl']['enb_sm']) && !empty($popup['params']['tpl']['enb_sm'])) {
			$popup['params']['tpl']['sm_html'] = framePps::_()->getModule('sm')->generateHtml( $popup );
			$popup['css'] .= framePps::_()->getModule('sm')->generateCss( $popup );
		}
		if(in_array($popup['type'], array(PPS_FB_LIKE))) {
			$popup['params']['tpl']['fb_like_widget_html'] = $this->_generateFbLikeWidget( $popup );
		}
		if(in_array($popup['type'], array(PPS_VIDEO))) {
			$popup['params']['tpl']['video_html'] = $this->_generateVideoHtml( $popup );
		}
		$popup['css'] = $this->_replaceTagsWithTwig( $popup['css'], $popup );
		$popup['html'] = $this->_replaceTagsWithTwig( $popup['html'], $popup );

		$popup['html'] .= $this->_generateImgsPreload( $popup );

		$popup['css'] = dispatcherPps::applyFilters('popupCss', $popup['css'], $popup);
		$popup['html'] = dispatcherPps::applyFilters('popupHtml', $popup['html'], $popup);
		// $replaceStyleTag can be used for compability with other plugins minify functionality:
		// it will not recognize css in js data as style whye rendering on server side,
		// but will be replaced back to normal <style> tag in JS, @see js/frontend.popup.js
		return $this->_twig->render(
				($replaceStyleTag ? '<span style="display: none;" id="ppsPopupStylesHidden_'. $popup['view_id']. '">' : '<style type="text/css">')
					. $popup['css']
				. ($replaceStyleTag ? '</span>' : '</style>')
				. $popup['html'],
			array('popup' => $popup)
		);
	}
	private function _generateImgsPreload( $popup ) {
		$res = '';
		if(isset($popup['params']['opts_attrs']['bg_number']) && !empty($popup['params']['opts_attrs']['bg_number'])) {
			for($i = 0; $i < $popup['params']['opts_attrs']['bg_number']; $i++) {
				if($popup['params']['tpl']['bg_type_'. $i] == 'img' && !empty($popup['params']['tpl']['bg_img_'. $i])) {
					$res .= '<img class="ppsPopupPreloadImg ppsPopupPreloadImg_'. $popup['view_id']. '" src="'. $popup['params']['tpl']['bg_img_'. $i]. '" />';
				}
			}
		}
		return $res;
	}
	private function _generateFbLikeWidget($popup) {
		$res = '';
		if(isset($popup['params']['tpl']['fb_enb_label']) && $popup['params']['tpl']['fb_enb_label']) {
			$res .= '<div>'. $popup['label']. '</div>';
		}
		$res .= '<div id="fb-root"></div>
		<script>(function(d, s, id) {
		  var js, fjs = d.getElementsByTagName(s)[0];
		  if (d.getElementById(id)) return;
		  js = d.createElement(s); js.id = id;
		  js.src = "//connect.facebook.net/'. utilsPps::getLangCode(). '/sdk.js#xfbml=1&version=v2.5&appId=1612081092370131";
		  fjs.parentNode.insertBefore(js, fjs);
		}(document, \'script\', \'facebook-jssdk\'));</script>';
		$res .= '<div class="fb-page fb_iframe_widget"';
		$fbLikeOpts = $this->getFbLikeOpts();
		foreach($fbLikeOpts as $fKey => $fData) {
			$dataKey = 'data-'. str_replace('_', '-', $fKey);
			$value = '';
			if($fData['html'] == 'checkbox') {
				$value = isset($popup['params']['tpl']['fb_like_opts'][ $fKey ]) && $popup['params']['tpl']['fb_like_opts'][ $fKey ]
					? 'true'
					: 'false';
			} else {
				$value = isset($popup['params']['tpl']['fb_like_opts'][ $fKey ])
					? $popup['params']['tpl']['fb_like_opts'][ $fKey ]
					: (isset($fData['def']) ? $fData['def'] : '');
			}
			if(is_array($value)) {
				$value = implode(',', $value);
			}
			$res .= ' '. $dataKey.'="'. $value. '"';
		}
		if(isset($popup['params']['tpl']['width']) && !empty($popup['params']['tpl']['width'])) {
			$res .= ' data-width="'. $popup['params']['tpl']['width']. '"';
		}
		if(isset($popup['params']['tpl']['height']) && !empty($popup['params']['tpl']['height'])) {
			$res .= ' data-height="'. $popup['params']['tpl']['height']. '"';
		}
		$res .= '></div>';
		return $res;
	}
	private function _replaceTagsWithTwig($string, &$popup) {
		$string = preg_replace('/\[if (.+)\]/iU', '{% if popup.params.tpl.$1 %}', $string);
		$string = preg_replace('/\[elseif (.+)\]/iU', '{% elseif popup.params.tpl.$1 %}', $string);

		$replaceFrom = array('ID', 'endif', 'else', 'wp_logout_url');
		$replaceTo = array($popup['view_id'], '{% endif %}', '{% else %}', wp_logout_url());
		// Standard shortcode processor didn't worked for us here - as it is developed for posts,
		// not for direct "do_shortcode" call, so we created own embed shortcode processor
		add_shortcode('embed', array($this, 'processEmbedCode'));
		if(isset($popup['params']) && isset($popup['params']['tpl'])) {
			foreach($popup['params']['tpl'] as $key => $val) {
				if(is_array($val)) {
					foreach($val as $key2 => $val2) {
						if(is_array($val2)) {
							foreach($val2 as $key3 => $val3) {
								// Here should be some recursive and not 3 circles, but have not time for this right now, maybe you will do this?:)
								if(is_array($val3)) continue;
								$replaceFrom[] = $key. '_'. $key2. '_'. $key3;
								$replaceTo[] = $val3;
							}
						} else {
							$replaceFrom[] = $key. '_'. $key2;
							$replaceTo[] = $val2;
						}
					}
				} else {
					// Do shortcodes for all text type data in popup
					if(!isset($popup['params_replaced']) && (strpos($key, 'txt_') === 0 || strpos($key, 'label') === 0 || strpos($key, 'foot_note'))) {
						$val = $popup['params']['tpl'][ $key ] = do_shortcode( $val );
					}
					$replaceFrom[] = $key;
					$replaceTo[] = $val;
				}
			}
			$popup['params_replaced'] = true;
		}
		remove_shortcode('embed', array($this, 'processEmbedCode'));
		foreach($replaceFrom as $i => $v) {
			$replaceFrom[ $i ] = '['. $v. ']';
		}
		return str_replace($replaceFrom, $replaceTo, $string);
	}
	public function processEmbedCode($attrs, $url = '') {
		if ( empty( $url ) && ! empty( $attrs['src'] ) ) {
			$url = trim($attrs['src']);
		}
		if(empty($url)) return false;
		return wp_oembed_get($url, $attrs);
	}
	public function getCloseBtns() {
		if(empty($this->_closeBtns)) {
			$this->_closeBtns = array(
				'none' => array('label' => __('None', PPS_LANG_CODE)),
				'classy_grey' => array('img' => 'classy_grey.png', 'add_style' => array('top' => '-16px', 'right' => '-16px', 'width' => '42px', 'height' => '42px')),
				'close-orange' => array('img' => 'close-orange.png', 'add_style' => array('top' => '-16px', 'right' => '-16px', 'width' => '42px', 'height' => '42px')),
				'close-red-in-circle' => array('img' => 'close-red-in-circle.png', 'add_style' => array('top' => '-16px', 'right' => '-16px', 'width' => '42px', 'height' => '42px')),
				'exclusive_close' => array('img' => 'exclusive_close.png', 'add_style' => array('top' => '-10px', 'right' => '-35px', 'width' => '31px', 'height' => '31px')),
				'lists_black' => array('img' => 'lists_black.png', 'add_style' => array('top' => '-10px', 'right' => '-10px', 'width' => '25px', 'height' => '25px')),
				'while_close' => array('img' => 'while_close.png', 'add_style' => array('top' => '15px', 'right' => '15px', 'width' => '20px', 'height' => '19px')),
				'red_close' => array('img' => 'close-red.png', 'add_style' => array('top' => '15px', 'right' => '20px', 'width' => '25px', 'height' => '25px')),
				'yellow_close' => array('img' => 'close-yellow.png', 'add_style' => array('top' => '-16px', 'right' => '-16px', 'width' => '42px', 'height' => '42px')),
				'sqr_close' => array('img' => 'sqr-close.png', 'add_style' => array('top' => '25px', 'right' => '20px', 'width' => '25px', 'height' => '25px')),
				'close-black-in-white-circle' => array('img' => 'close-black-in-white-circle.png', 'add_style' => array('top' => '16px', 'right' => '16px', 'width' => '32px', 'height' => '32px')),
				'circle_big_new_close' => array('img' => 'circle_big_new_close.png', 'add_style' => array('top' => '0', 'right' => '0', 'width' => '31px', 'height' => '31px')),
			);
			foreach($this->_closeBtns as $key => $data) {
				if(isset($data['img'])) {
					if(!isset($data['img_url']))
						$this->_closeBtns[ $key ]['img_url'] = $this->getModule()->getModPath(). 'img/assets/close_btns/'. $data['img'];
				}
			}
		}
		return $this->_closeBtns;
	}
	public function getBullets() {
		if(empty($this->_bullets)) {
			$this->_bullets = array(
				'none' => array('label' => __('None (standard)', PPS_LANG_CODE)),
				'classy_blue' => array('img' => 'classy_blue.png', 'add_style' => array('list-style' => 'outside none none !important', 'background-repeat' => 'no-repeat', 'padding-left' => '30px', 'line-height' => '100%', 'margin-bottom' => '10px', 'min-height' => '18px')),
				'circle_green' => array('img' => 'circle_green.png', 'add_style' => array('list-style' => 'outside none none !important', 'background-repeat' => 'no-repeat', 'padding-left' => '30px', 'min-height' => '18px')),
				'lists_green' => array('img' => 'lists_green.png', 'add_style' => array('list-style' => 'outside none none !important', 'background-repeat' => 'no-repeat', 'padding-left' => '30px', 'margin-bottom' => '10px', 'min-height' => '25px')),
				'tick' => array('img' => 'tick.png', 'add_style' => array('list-style' => 'outside none none !important', 'background-repeat' => 'no-repeat', 'padding-left' => '30px', 'line-height' => '100%', 'margin-bottom' => '10px', 'min-height' => '18px')),
				'tick_blue' => array('img' => 'tick_blue.png', 'add_style' => array('list-style' => 'outside none none !important', 'background-repeat' => 'no-repeat', 'padding-left' => '30px', 'line-height' => '100%', 'margin-bottom' => '10px', 'min-height' => '18px')),
				'ticks' => array('img' => 'ticks.png', 'add_style' => array('list-style' => 'outside none none !important', 'background-repeat' => 'no-repeat', 'padding-left' => '30px', 'line-height' => '100%', 'margin-bottom' => '10px', 'min-height' => '18px')),
				'pop_icon' => array('img' => 'pop_icon.jpg', 'add_style' => array('list-style' => 'outside none none !important', 'background-repeat' => 'no-repeat', 'padding-left' => '30px', 'line-height' => '100%', 'background-position' => 'left center', 'margin-bottom' => '15px')),
				'circle_big_new' => array('img' => 'circle_big_new.png', 'add_style' => array('list-style' => 'outside none none !important', 'background-repeat' => 'no-repeat', 'padding-left' => '40px', 'line-height' => '100%', 'background-position' => 'left center', 'margin-bottom' => '5px', 'line-height' => '30px')),
			);
			foreach($this->_bullets as $key => $data) {
				if(isset($data['img']) && !isset($data['img_url'])) {
					$this->_bullets[ $key ]['img_url'] = $this->getModule()->getModPath(). 'img/assets/bullets/'. $data['img'];
				}
				// it's just lucky that all bulets - have same padding-left and bg-size - so we can set same rtl styles for all of them
				$this->_bullets[ $key ]['rtl_style'] = array('padding-left' => '0', 'padding-right' => '30px', 'background-position' => 'top right');
			}
		}
		return $this->_bullets;
	}
	protected function _initTwig() {
		if(!$this->_twig) {
			if(!class_exists('Twig_Autoloader')) {
				require_once(PPS_CLASSES_DIR. 'Twig'. DS. 'Autoloader.php');
			}
			Twig_Autoloader::register();
			$this->_twig = new Twig_Environment(new Twig_Loader_String(), array('debug' => 0));
			$this->_twig->addFunction(
				new Twig_SimpleFunction('adjust_brightness', array(
						$this,
						'adjustBrightness'
					)
				)
			);
			$this->_twig->addFunction(
				new Twig_SimpleFunction('hex_to_rgba_str', 'utilsPps::hexToRgbaStr')
			);
		}
	}
}
