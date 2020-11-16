<?php
if (!defined('ABSPATH')) exit;
if (!class_exists('BVProtect')) :

require_once dirname( __FILE__ ) . '/../base.php';
require_once dirname( __FILE__ ) . '/logger.php';
require_once dirname( __FILE__ ) . '/ipstore.php';
require_once dirname( __FILE__ ) . '/../fw/fw.php';
require_once dirname( __FILE__ ) . '/../fw/config.php';
require_once dirname( __FILE__ ) . '/../fw/request.php';
require_once dirname( __FILE__ ) . '/lp/lp.php';
require_once dirname( __FILE__ ) . '/lp/config.php';

class BVProtect {
	public $db;
	public $settings;
	
	function __construct($db, $settings) {
		$this->settings = $settings;
		$this->db = $db;
	}

	public function run() {
		$bvipstore = new BVIPStore($this->db);
		$bvipstore->init();
		$bvinfo = new MCInfo($this->settings);
		
		$config = $this->settings->getOption('bvptconf');
		if (!$config) {
			$config = array();
		}

		$ipHeader = array_key_exists('ipheader', $config) ? $config['ipheader'] : false;
		$ip = BVProtectBase::getIP($ipHeader);
		
		$fwLogger = new BVLogger($this->db, BVFWConfig::$requests_table);

		$fwConfHash = array_key_exists('fw', $config) ? $config['fw'] : array();
		$fw = new BVFW($fwLogger, $fwConfHash, $ip, $bvinfo, $bvipstore);

		if ($fw->isActive()) {

			if ($fw->canSetAdminCookie()) {
				add_action('init', array($fw, 'setBypassCookie'));
			}

			if (!defined('MCFWLOADED') && $fw->canSetIPCookie()) {
				$fw->setIPCookie();
			}

			if (!defined('MCFWLOADED')) {
				register_shutdown_function(array($fw, 'log'));

				$fw->execute();
			}
		}

		add_action('clear_pt_config', array($this, 'uninstall'));

		$lpConfHash = array_key_exists('lp', $config) ? $config['lp'] : array();
		$lp = new BVWPLP($this->db, $this->settings, $ip, $bvipstore, $lpConfHash);
		if ($lp->isActive()) {
			$lp->init();
		}
	}

	public function uninstall() {
		$this->settings->deleteOption('bvptconf');
		$this->db->dropBVTable(BVFWConfig::$requests_table);
		$this->db->dropBVTable(BVWPLPConfig::$requests_table);
		$this->settings->deleteOption('bvptplug');
		return true;
	}
}
endif;