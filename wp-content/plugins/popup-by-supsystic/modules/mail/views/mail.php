<?php
class mailViewPps extends viewPps {
	public function getTabContent() {
		framePps::_()->getModule('templates')->loadJqueryUi();
		framePps::_()->addScript('admin.'. $this->getCode(), $this->getModule()->getModPath(). 'js/admin.'. $this->getCode(). '.js');
		
		$this->assign('options', framePps::_()->getModule('options')->getCatOpts( $this->getCode() ));
		$this->assign('testEmail', framePps::_()->getModule('options')->get('notify_email'));
		return parent::getContent('mailAdmin');
	}
}
