<?php
class templatesPps extends modulePps {
    protected $_styles = array();
	private $_cdnUrl = '';
	
	public function __construct($d) {
		parent::__construct($d);
		$this->getCdnUrl();	// Init CDN URL
	}
	public function getCdnUrl() {
		if(empty($this->_cdnUrl)) {
			if((int) framePps::_()->getModule('options')->get('use_local_cdn')) {
				$uploadsDir = wp_upload_dir( null, false );
				$this->_cdnUrl = $uploadsDir['baseurl']. '/'. PPS_CODE. '/';
				if(uriPps::isHttps()) {
					$this->_cdnUrl = str_replace('http://', 'https://', $this->_cdnUrl);
				}
				dispatcherPps::addFilter('externalCdnUrl', array($this, 'modifyExternalToLocalCdn'));
			} else {
				$this->_cdnUrl = (uriPps::isHttps() ? 'https' : 'http'). '://supsystic-42d7.kxcdn.com/';
			}
		}
		return $this->_cdnUrl;
	}
	public function modifyExternalToLocalCdn( $url ) {
		$url = str_replace(
			array('https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css'), 
			array($this->_cdnUrl. 'lib/font-awesome'), 
			$url);
		return $url;
	}
    public function init() {
        if (is_admin()) {
			if($isAdminPlugOptsPage = framePps::_()->isAdminPlugOptsPage()) {
				$this->loadCoreJs();
				$this->loadAdminCoreJs();
				$this->loadCoreCss();
				$this->loadChosenSelects();
				framePps::_()->addScript('adminOptionsPps', PPS_JS_PATH. 'admin.options.js', array(), false, true);
				add_action('admin_enqueue_scripts', array($this, 'loadMediaScripts'));
				add_action('init', array($this, 'connectAdditionalAdminAssets'));
			}
			// Some common styles - that need to be on all admin pages - be careful with them
			framePps::_()->addStyle('supsystic-for-all-admin-'. PPS_CODE, PPS_CSS_PATH. 'supsystic-for-all-admin.css');
		}
        parent::init();
    }
	public function connectAdditionalAdminAssets() {
		if(is_rtl()) {
			framePps::_()->addStyle('stylePps-rtl', PPS_CSS_PATH. 'style-rtl.css');
		}
	}
	public function loadMediaScripts() {
		if(function_exists('wp_enqueue_media')) {
			wp_enqueue_media();
		}
	}
	public function loadAdminCoreJs() {
		framePps::_()->addScript('jquery-ui-dialog');
		framePps::_()->addScript('jquery-ui-slider');
		framePps::_()->addScript('wp-color-picker');
		framePps::_()->addScript('icheck', PPS_JS_PATH. 'icheck.min.js');
		$this->loadTooltipster();
	}
	public function loadCoreJs() {
		static $loaded = false;
		if(!$loaded) {
			framePps::_()->addScript('jquery');
			$suf = PPS_MINIFY_ASSETS ? '.min' : '';
			framePps::_()->addScript('commonPps', PPS_JS_PATH. 'common'. $suf. '.js');
			framePps::_()->addScript('corePps', PPS_JS_PATH. 'core'. $suf. '.js');

			$ajaxurl = admin_url('admin-ajax.php');
			$jsData = array(
				'siteUrl'					=> PPS_SITE_URL,
				'imgPath'					=> PPS_IMG_PATH,
				'cssPath'					=> PPS_CSS_PATH,
				'loader'					=> PPS_LOADER_IMG, 
				'close'						=> PPS_IMG_PATH. 'cross.gif', 
				'ajaxurl'					=> $ajaxurl,
				'options'					=> framePps::_()->getModule('options')->getAllowedPublicOptions(),
				'PPS_CODE'					=> PPS_CODE,
				//'ball_loader'				=> PPS_IMG_PATH. 'ajax-loader-ball.gif',
				//'ok_icon'					=> PPS_IMG_PATH. 'ok-icon.png',
				'jsPath'					=> PPS_JS_PATH,
			);
			if(is_admin()) {
				$jsData['isPro'] = framePps::_()->getModule('supsystic_promo')->isPro();
				$jsData['mainLink'] = framePps::_()->getModule('supsystic_promo')->getMainLink();
			}
			$jsData = dispatcherPps::applyFilters('jsInitVariables', $jsData);
			framePps::_()->addJSVar('corePps', 'PPS_DATA', $jsData);
			$loaded = true;
		}
	}
	public function loadTooltipster() {
		framePps::_()->addScript('tooltipster', $this->_cdnUrl. 'lib/tooltipster/jquery.tooltipster.min.js');
		framePps::_()->addStyle('tooltipster', $this->_cdnUrl. 'lib/tooltipster/tooltipster.css');
	}
	public function loadSlimscroll() {
		framePps::_()->addScript('jquery.slimscroll', $this->_cdnUrl. 'js/jquery.slimscroll.js');
	}
	public function loadCodemirror() {
		framePps::_()->addStyle('ppsCodemirror', $this->_cdnUrl. 'lib/codemirror/codemirror.css');
		framePps::_()->addStyle('codemirror-addon-hint', $this->_cdnUrl. 'lib/codemirror/addon/hint/show-hint.css');
		framePps::_()->addScript('ppsCodemirror', $this->_cdnUrl. 'lib/codemirror/codemirror.js');
		framePps::_()->addScript('codemirror-addon-show-hint', $this->_cdnUrl. 'lib/codemirror/addon/hint/show-hint.js');
		framePps::_()->addScript('codemirror-addon-xml-hint', $this->_cdnUrl. 'lib/codemirror/addon/hint/xml-hint.js');
		framePps::_()->addScript('codemirror-addon-html-hint', $this->_cdnUrl. 'lib/codemirror/addon/hint/html-hint.js');
		framePps::_()->addScript('codemirror-mode-xml', $this->_cdnUrl. 'lib/codemirror/mode/xml/xml.js');
		framePps::_()->addScript('codemirror-mode-javascript', $this->_cdnUrl. 'lib/codemirror/mode/javascript/javascript.js');
		framePps::_()->addScript('codemirror-mode-css', $this->_cdnUrl. 'lib/codemirror/mode/css/css.js');
		framePps::_()->addScript('codemirror-mode-htmlmixed', $this->_cdnUrl. 'lib/codemirror/mode/htmlmixed/htmlmixed.js');
	}
	public function loadCoreCss() {
		$this->_styles = array(
			'stylePps'			=> array('path' => PPS_CSS_PATH. 'style.css', 'for' => 'admin'), 
			'supsystic-uiPps'	=> array('path' => PPS_CSS_PATH. 'supsystic-ui.css', 'for' => 'admin'), 
			'dashicons'			=> array('for' => 'admin'),
			'bootstrap-alerts'	=> array('path' => PPS_CSS_PATH. 'bootstrap-alerts.css', 'for' => 'admin'),
			'icheck'			=> array('path' => PPS_CSS_PATH. 'jquery.icheck.css', 'for' => 'admin'),
			//'uniform'			=> array('path' => PPS_CSS_PATH. 'uniform.default.css', 'for' => 'admin'),
			'wp-color-picker'	=> array('for' => 'admin'),
		);
		foreach($this->_styles as $s => $sInfo) {
			if(!empty($sInfo['path'])) {
				framePps::_()->addStyle($s, $sInfo['path']);
			} else {
				framePps::_()->addStyle($s);
			}
		}
		$this->loadFontAwesome();
	}
	public function loadJqueryUi() {
		static $loaded = false;
		if(!$loaded) {
			framePps::_()->addStyle('jquery-ui', PPS_CSS_PATH. 'jquery-ui.min.css');
			framePps::_()->addStyle('jquery-ui.structure', PPS_CSS_PATH. 'jquery-ui.structure.min.css');
			framePps::_()->addStyle('jquery-ui.theme', PPS_CSS_PATH. 'jquery-ui.theme.min.css');
			framePps::_()->addStyle('jquery-slider', PPS_CSS_PATH. 'jquery-slider.css');
			$loaded = true;
		}
	}
	public function loadJqGrid() {
		static $loaded = false;
		if(!$loaded) {
			$this->loadJqueryUi();
			framePps::_()->addScript('jq-grid', $this->_cdnUrl. 'lib/jqgrid/jquery.jqGrid.min.js');
			framePps::_()->addStyle('jq-grid', $this->_cdnUrl. 'lib/jqgrid/ui.jqgrid.css');
			$langToLoad = utilsPps::getLangCode2Letter();
			$availableLocales = array('ar','bg','bg1251','cat','cn','cs','da','de','dk','el','en','es','fa','fi','fr','gl','he','hr','hr1250','hu','id','is','it','ja','kr','lt','mne','nl','no','pl','pt','pt','ro','ru','sk','sr','sr','sv','th','tr','tw','ua','vi');
			if(!in_array($langToLoad, $availableLocales)) {
				$langToLoad = 'en';
			}
			framePps::_()->addScript('jq-grid-lang', $this->_cdnUrl. 'lib/jqgrid/i18n/grid.locale-'. $langToLoad. '.js');
			$loaded = true;
		}
	}
	public function loadFontAwesome() {
		framePps::_()->addStyle('font-awesomePps', dispatcherPps::applyFilters('externalCdnUrl', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css'));
	}
	public function loadChosenSelects() {
		framePps::_()->addStyle('jquery.chosen', $this->_cdnUrl. 'lib/chosen/chosen.min.css');
		framePps::_()->addScript('jquery.chosen', $this->_cdnUrl. 'lib/chosen/chosen.jquery.min.js');
	}
	public function loadDatePicker() {
		framePps::_()->addScript('jquery-ui-datepicker');
	}
	public function loadJqplot() {
		static $loaded = false;
		if(!$loaded) {
			$jqplotDir = $this->_cdnUrl. 'lib/jqplot/';

			framePps::_()->addStyle('jquery.jqplot', $jqplotDir. 'jquery.jqplot.min.css');

			framePps::_()->addScript('jplot', $jqplotDir. 'jquery.jqplot.min.js');
			framePps::_()->addScript('jqplot.canvasAxisLabelRenderer', $jqplotDir. 'jqplot.canvasAxisLabelRenderer.min.js');
			framePps::_()->addScript('jqplot.canvasTextRenderer', $jqplotDir. 'jqplot.canvasTextRenderer.min.js');
			framePps::_()->addScript('jqplot.dateAxisRenderer', $jqplotDir. 'jqplot.dateAxisRenderer.min.js');
			framePps::_()->addScript('jqplot.canvasAxisTickRenderer', $jqplotDir. 'jqplot.canvasAxisTickRenderer.min.js');
			framePps::_()->addScript('jqplot.highlighter', $jqplotDir. 'jqplot.highlighter.min.js');
			framePps::_()->addScript('jqplot.cursor', $jqplotDir. 'jqplot.cursor.min.js');
			framePps::_()->addScript('jqplot.barRenderer', $jqplotDir. 'jqplot.barRenderer.min.js');
			framePps::_()->addScript('jqplot.categoryAxisRenderer', $jqplotDir. 'jqplot.categoryAxisRenderer.min.js');
			framePps::_()->addScript('jqplot.pointLabels', $jqplotDir. 'jqplot.pointLabels.min.js');
			framePps::_()->addScript('jqplot.pieRenderer', $jqplotDir. 'jqplot.pieRenderer.min.js');
			$loaded = true;
		}
	}
	public function loadSortable() {
		static $loaded = false;
		if(!$loaded) {
			framePps::_()->addScript('jquery-ui-core');
			framePps::_()->addScript('jquery-ui-widget');
			framePps::_()->addScript('jquery-ui-mouse');

			framePps::_()->addScript('jquery-ui-draggable');
			framePps::_()->addScript('jquery-ui-sortable');
			$loaded = true;
		}
	}
	public function loadMagicAnims() {
		static $loaded = false;
		if(!$loaded) {
			framePps::_()->addStyle('magic.anim', $this->_cdnUrl. 'css/magic.min.css');
			$loaded = true;
		}
	}
	public function loadCssAnims() {
		static $loaded = false;
		if(!$loaded) {
			framePps::_()->addStyle('animate.styles', 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.4.0/animate.min.css');
			$loaded = true;
		}
	}
	public function loadBootstrapSimple() {
		static $loaded = false;
		if(!$loaded) {
			framePps::_()->addStyle('bootstrap-simple', PPS_CSS_PATH. 'bootstrap-simple.css');
			$loaded = true;
		}
	}
	public function loadGoogleFont( $font ) {
		static $loaded = array();
		if(!isset($loaded[ $font ])) {
			framePps::_()->addStyle('google.font.'. str_replace(array(' '), '-', $font), 'https://fonts.googleapis.com/css?family='. urlencode($font));
			$loaded[ $font ] = 1;
		}
	}
}
