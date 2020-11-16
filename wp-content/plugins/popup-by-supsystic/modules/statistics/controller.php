<?php
class statisticsControllerPps extends controllerPps {
	public function add() {
		$res = new responsePps();
		$connectHash = reqPps::getVar('connect_hash', 'post');
		$id = reqPps::getVar('id', 'post');
		if(md5(date('m-d-Y'). $id. NONCE_KEY) != $connectHash) {
			$res->pushError('Some undefined for now.....');
			$res->ajaxExec( true );
		}
		if($this->getModel()->add( reqPps::get('post') )) {
			// Do nothing for now
		} else
			$res->pushError ($this->getModel()->getErrors());
		$res->ajaxExec();
	}
	public function clearForPopUp() {
		$res = new responsePps();
		if($this->getModel()->clearForPopUp( reqPps::get('post') )) {
			$res->addMessage(__('Done', PPS_LANG_CODE));
		} else
			$res->pushError ($this->getModel()->getErrors());
		$res->ajaxExec();
	}
	public function getUpdatedStats() {
		$res = new responsePps();
		if(($stats = $this->getModel()->getUpdatedStats( reqPps::get('post') )) !== false) {
			$res->addData('stats', $stats);
			$res->addMessage(__('Done', PPS_LANG_CODE));
		} else
			$res->pushError ($this->getModel()->getErrors());
		$res->ajaxExec();
	}
	public function getCsv() {
		if(($stats = $this->getModel()->getPreparedStats( reqPps::get('get') )) !== false) {
			$id = (int) reqPps::getVar('id');
			$popup = framePps::_()->getModule('popup')->getModel()->getById( $id );
			importClassPps('filegeneratorPps');
			importClassPps('csvgeneratorPps');
			$csvGenerator = new csvgeneratorPps(sprintf(__('Statistics for %s', PPS_LANG_CODE), htmlspecialchars( $popup['label'] )));
			$labels = array(
				'date' => __('Date', PPS_LANG_CODE),
				'views' => __('Views', PPS_LANG_CODE),
				'unique_requests' => __('Unique Views', PPS_LANG_CODE),
				'actions' => __('Actions', PPS_LANG_CODE),
				'conversion' => __('Conversion', PPS_LANG_CODE),
			);
			$row = $cell = 0;
			foreach($labels as $l) {
				$csvGenerator->addCell($row, $cell, $l);
				$cell++;
			}
			$row = 1;
			foreach($stats as $s) {
				$cell = 0;
				foreach($labels as $k => $l) {
					$csvGenerator->addCell($row, $cell, $s[ $k ]);
					$cell++;
				}
				$row++;
			}
			$csvGenerator->generate();
		} else {
			echo implode('<br />', $this->getModel()->getErrors());
		}
		exit();
	}
	public function getPermissions() {
		return array(
			PPS_USERLEVELS => array(
				PPS_ADMIN => array('clearForPopUp', 'getUpdatedStats', 'getCsv')
			),
		);
	}
}
