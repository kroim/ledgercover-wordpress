<?php
class mailPoetV3Pps {
	public function getLists() {
		return \MailPoet\API\API::MP('v1')->getLists();
	}

	public function signupConfirm() {
		//return \MailPoet\Models\Setting::getValue('signup_confirmation');
      return '';
	}

	public function subscribe($userData, $lists, $options) {
		return \MailPoet\API\API::MP('v1')->addSubscriber($userData, $lists);
	}
}
