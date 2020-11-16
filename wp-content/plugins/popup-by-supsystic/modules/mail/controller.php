<?php
class mailControllerPps extends controllerPps {
	public function testEmail() {
		$res = new responsePps();
		$email = reqPps::getVar('test_email', 'post');
		if($this->getModel()->testEmail($email)) {
			$res->addMessage(__('Now check your email inbox / spam folders for test mail.'));
		} else 
			$res->pushError ($this->getModel()->getErrors());
		$res->ajaxExec();
	}
	public function saveMailTestRes() {
		$res = new responsePps();
		$result = (int) reqPps::getVar('result', 'post');
		framePps::_()->getModule('options')->getModel()->save('mail_function_work', $result);
		$res->ajaxExec();
	}
	public function saveOptions() {
		$res = new responsePps();
		$optsModel = framePps::_()->getModule('options')->getModel();
		$submitData = reqPps::get('post');
		if($optsModel->saveGroup($submitData)) {
			$res->addMessage(__('Done', PPS_LANG_CODE));
		} else
			$res->pushError ($optsModel->getErrors());
		$res->ajaxExec();
	}
	public function getPermissions() {
		return array(
			PPS_USERLEVELS => array(
				PPS_ADMIN => array('testEmail', 'saveMailTestRes', 'saveOptions')
			),
		);
	}
}
