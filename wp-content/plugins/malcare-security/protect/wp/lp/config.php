<?php

if (!defined('ABSPATH')) exit;
if (!class_exists('BVWPLPConfig')) :
class BVWPLPConfig {
	public $mode;
	public $captchaLimit;
	public $tempBlockLimit;
	public $blockAllLimit;
	
	public static $requests_table = 'lp_requests';
	
	#mode
	const DISABLED = 1;
	const AUDIT    = 2;
	const PROTECT  = 3;

	public function __construct($confHash) {
		$this->mode = array_key_exists('mode', $confHash) ? intval($confHash['mode']) : BVWPLPConfig::DISABLED;
		$this->captchaLimit = array_key_exists('captchalimit', $confHash) ? intval($confHash['captchalimit']) : 3;
		$this->tempBlockLimit = array_key_exists('tempblocklimit', $confHash) ? intval($confHash['tempblocklimit']) : 10;
		$this->blockAllLimit = array_key_exists('blockalllimit', $confHash) ? intval($confHash['blockalllimit']) : 100;
	}
}
endif;