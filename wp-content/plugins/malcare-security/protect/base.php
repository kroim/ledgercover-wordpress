<?php
if (! (defined('ABSPATH') || defined('MCDATAPATH')) ) exit;
if (!class_exists('BVProtectBase')) :

class BVProtectBase {
	public static function getIP($ipHeader) {
		$ip = '127.0.0.1';
		if ($ipHeader && is_array($ipHeader)) {
			if (array_key_exists($ipHeader['hdr'], $_SERVER)) {
				$_ips = preg_split("/(,| |\t)/", $_SERVER[$ipHeader['hdr']]);
				if (array_key_exists(intval($ipHeader['pos']), $_ips)) {
					$ip = $_ips[intval($ipHeader['pos'])];
				}
			}
		} else if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		$ip = trim($ip);
		if (preg_match('/^\[([0-9a-fA-F:]+)\](:[0-9]+)$/', $ip, $matches)) {
			$ip = $matches[1];
		} elseif (preg_match('/^([0-9.]+)(:[0-9]+)$/', $ip, $matches)) {
			$ip = $matches[1];
		}

		return $ip;
	}
}
endif;