<?php
class statisticsModelPps extends modelPps {
	public function __construct() {
		$this->_setTbl('statistics');
	}
	public function add($d = array()) {
		$d['id'] = isset($d['id']) ? (int) $d['id'] : 0;
		$d['type'] = isset($d['type']) ? $d['type'] : '';
		if(!empty($d['id']) && !empty($d['type'])) {
			$typeId = $this->getModule()->getTypeIdByCode( $d['type'] );
			$smId = 0;
			if(isset($d['sm_type']) && !empty($d['sm_type'])) {
				if($d['type'] == 'share') {
					$smId = (int) framePps::_()->getModule('sm')->getTypeIdByCode( $d['sm_type'] );
				} elseif(is_numeric($d['sm_type']) ) {
					$smId = (int) $d['sm_type'];
				}
			}
			
			$isUnique = 0;
			if(isset($d['is_unique']) && !empty($d['is_unique'])) {
				$isUnique = (int) 1;	// This is realy cool :)
			}
			$popupModel = framePps::_()->getModule('popup')->getModel();
			if(in_array($d['type'], array('show'))) {
				$popupModel->addViewed( $d['id'] );
				if($isUnique) {
					$popupModel->addUniqueViewed( $d['id'] );
				}
			} elseif(!in_array($d['type'], array('close', 'subscribe_error'))) {	// Any action count here
				$popupModel->addActionDone( $d['id'] );
			}
			return $this->insert(array(
				'popup_id' => $d['id'],
				'type' => $typeId,
				'sm_id' => $smId,
				'is_unique' => $isUnique,
			));
		} else
			$this->pushError(__('Send me some info, pls', PPS_LANG_CODE));
		return false;
	}
	/**
	 * Get list for popup
	 * @param numeric $pid PopUp ID
	 * @param array $params Additional selection params, $params = array('type' => '')
	 * @return array List of statistics data
	 */
	public function getForPopup($popupId, $params = array()) {
		$where = array('popup_id' => $popupId);
		$typeId = isset($params['type']) ? $params['type'] : 0;
		if($typeId && !is_numeric($typeId)) {
			$typeId = $this->getModule()->getTypeIdByCode( $typeId );
		}
		if($typeId) {
			$where['type'] = $typeId;
		}
		if(isset($params['sm_id'])) {
			$where['sm_id'] = $params['sm_id'];
		}
		$group = isset($params['group']) ? $params['group'] : 'day';
		$sqlDateFormat = '';
		switch($group) {
			case 'hour':
				$sqlDateFormat = 'DATE_FORMAT(date_created, "%m-%d-%Y %H:00")';
				break;
			case 'week':
				$sqlDateFormat = 'DATE_FORMAT(DATE_SUB(date_created, INTERVAL DAYOFWEEK(date_created)-1 DAY), "%m-%d-%Y")';
				break;
			case 'month':
				$sqlDateFormat = 'DATE_FORMAT(date_created, "%m-01-%Y")';
				break;
			case 'day':
			default:
				$sqlDateFormat = 'DATE_FORMAT(date_created, "%m-%d-%Y")';
				break;
		}
		return $this->setSelectFields('COUNT(*) AS total_requests, SUM(is_unique) AS unique_requests, '. $sqlDateFormat. ' AS date')
				->groupBy('date')
				->setOrderBy('date')
				->setSortOrder('DESC')
				->setWhere($where)
				->getFromTbl();
	}
	public function getSmActionForPopup($popupId) {
		// type != 7 - for age verify types - that used same column in this table
		$where = array('popup_id' => $popupId, 'additionalCondition' => ' sm_id != 0 AND type != 7 ');
		$data = $this->setSelectFields('COUNT(*) AS total_requests, sm_id')
				->groupBy('sm_id')
				->setWhere($where)
				->getFromTbl();
		if(!empty($data)) {
			foreach($data as $i => $row) {
				$data[ $i ]['sm_type'] = framePps::_()->getModule('sm')->getTypeById( $row['sm_id'] );
			}
		}
		return $data;
	}
	public function clearForPopUp($d = array()) {
		$d['id'] = isset($d['id']) ? (int) $d['id'] : 0;
		if($d['id']) {
			framePps::_()->getModule('popup')->getModel()->clearCachedStats( $d['id'] );
			return $this->delete(array('popup_id' => $d['id']));
		} else
			$this->pushError(__('Invalid ID', PPS_LANG_CODE));
		return false;
	}
	public function getAllForPopupId($id, $params = array()) {
		$allTypes = $this->getModule()->getTypes();
		$allStats = array();
		$haveData = false;
		$i = 0;
		$popup = null;
		foreach($allTypes as $typeCode => $type) {
			$params['type'] = $type['id'];
			$allStats[ $i ] = $type;
			$allStats[ $i ]['code'] = $typeCode;
			$allStats[ $i ]['points'] = $this->getForPopup($id, $params);
			if($typeCode == 'age_verify' && !empty($allStats[ $i ]['points'])) {
				if(empty($popup)) {
					$popup = isset($params['popup']) ? $params['popup'] : framePps::_()->getModule('popup')->getModel()->getById( $id );
				}
				if(!empty($popup) 
					&& isset($popup['params']['opts_attrs']['btns_number'])
					&& !empty($popup['params']['opts_attrs']['btns_number'])
				) {
					for($j = 0; $j < (int) $popup['params']['opts_attrs']['btns_number']; $j++) {
						if(isset($popup['params']['tpl']['btn_txt_'. $j])) {
							$i++;

							$allStats[ $i ] = $type;
							$allStats[ $i ]['code'] = $typeCode. '_'. $j;
							$allStats[ $i ]['label'] .= ' '. $popup['params']['tpl']['btn_txt_'. $j];
							$allStats[ $i ]['points'] = $this->getForPopup($id, array_merge($params, array('sm_id' => $j)));
						}
					}
				}
			}
			if(!empty($allStats[ $i ]['points'])) {
				$haveData = true;
			}
			$i++;
		}
		return $haveData ? $allStats : false;
	}
	public function getUpdatedStats($d = array()) {
		$id = isset($d['id']) ? (int) $d['id'] : 0;
		if($id) {
			$popup = framePps::_()->getModule('popup')->getModel()->getById( $id );
			$params = array();
			if(isset($d['group']))
				$params['group'] = $d['group'];
			$allStats = $this->getAllForPopupId($id, $params);
			$allStats = dispatcherPps::applyFilters('popupStatsAdminData', $allStats, $popup);
			return $allStats;
		} else
			$this->pushError (__('Invalid ID', PPS_LANG_CODE));
		return false;
	}
	public function getPreparedStats($d = array()) {
		$stats = $this->getUpdatedStats( $d );
		if($stats) {
			$dataToDate = array();
			foreach($stats as $i => $stat) {
				if(isset($stat['points']) && !empty($stat['points'])) {
					foreach($stat['points'] as $j => $point) {
						$date = $point['date'];
						$currentData = array(
							'date' => $date,
							'views' =>  0,
							'unique_requests' => 0,
							'actions' => 0,
							'conversion' => 0,
						);
						if(in_array($stat['code'], array('show'))) {
							$currentData['views'] = (int)( $point['total_requests'] );
						} else {
							$currentData['actions'] = (int)( $point['total_requests'] );
						}
						$uniqueRequests = (int)( $point['unique_requests'] );
						if($uniqueRequests) {
							$currentData['unique_requests'] = $uniqueRequests;
						}
						if(isset($dataToDate[ $date ])) {
							$currentData['views'] += $dataToDate[ $date ]['views'];
							$currentData['actions'] += $dataToDate[ $date ]['actions'];
							$currentData['unique_requests'] += $dataToDate[ $date ]['unique_requests'];
						}
						$dataToDate[ $date ] = $currentData;
					}
				}
			}
			return $dataToDate;
		} else
			$this->pushError (__('No data found', PPS_LANG_CODE));
		return false;
	}
}