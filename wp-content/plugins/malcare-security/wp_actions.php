<?php

if (!defined('ABSPATH')) exit;
if (!class_exists('MCWPAction')) :
	class MCWPAction {
		public $settings;
		public $siteinfo;
		public $bvinfo;
		public $bvapi;

		public function __construct($settings, $siteinfo, $bvapi) {
			$this->settings = $settings;
			$this->siteinfo = $siteinfo;
			$this->bvapi = $bvapi;
			$this->bvinfo = new MCInfo($settings);
		}
	
		public function activate() {
			if (!isset($_REQUEST['blogvaultkey'])) {
				##BVKEYSLOCATE##
			}
			if (MCAccount::isConfigured($this->settings)) {
				/* This informs the server about the activation */
				$info = array();
				$this->siteinfo->basic($info);
				$this->bvapi->pingbv('/bvapi/activate', $info);
			} else {
				MCAccount::setup($this->settings);
			}
		}

		public function deactivate() {
			$info = array();
			$this->siteinfo->basic($info);
			$this->bvapi->pingbv('/bvapi/deactivate', $info);
		}

		public static function uninstall() {
			do_action('clear_pt_config');
			do_action('clear_ip_store');
		 	##CLEARDYNSYNCCONFIG##
		}

		public function footerHandler() {
			$bvfooter = $this->settings->getOption($this->bvinfo->badgeinfo);
			if ($bvfooter) {
				echo '<div style="max-width:150px;min-height:70px;margin:0 auto;text-align:center;position:relative;">
					<a href='.$bvfooter['badgeurl'].' target="_blank" ><img src="'.plugins_url($bvfooter['badgeimg'], __FILE__).'" alt="'.$bvfooter['badgealt'].'" /></a></div>';
			}
		}
	}
endif;