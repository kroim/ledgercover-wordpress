<?php
class datePps {
	static public function _($time = NULL) {
		if(is_null($time)) {
			$time = time();
		}
		return date(PPS_DATE_FORMAT_HIS, $time);
	}
}