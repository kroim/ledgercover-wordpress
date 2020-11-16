<?php
/* NeverBounce integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_neverbounce_class {
	var $options = array(
		"email_neverbounce_enable" => "off",
		"email_neverbounce_user" => "",
		"email_neverbounce_key" => ""
	);
	function __construct() {
		$this->get_options();
		if (is_admin()) {
			add_action('ulp_options_email_verification_show', array(&$this, 'options_show'));
			add_filter('ulp_options_check', array(&$this, 'options_check'));
			add_action('ulp_options_update', array(&$this, 'options_update'));
		}
		add_filter('ulp_front_fields_check', array(&$this, 'front_fields_check'), 10, 2);
	}
	function get_options() {
		foreach ($this->options as $key => $value) {
			$this->options[$key] = get_option('ulp_'.$key, $this->options[$key]);
		}
	}
	function update_options() {
		if (current_user_can('manage_options')) {
			foreach ($this->options as $key => $value) {
				update_option('ulp_'.$key, $value);
			}
		}
	}
	function populate_options() {
		foreach ($this->options as $key => $value) {
			if (isset($_POST['ulp_'.$key])) {
				$this->options[$key] = trim(stripslashes($_POST['ulp_'.$key]));
			}
		}
	}
	function options_show() {
		echo '
					<tr>
						<th></th>
						<td>
							<input type="checkbox" class="ulp-email-verifier" data-id="ulp-neverbounce-data" name="ulp_email_neverbounce_enable" '.($this->options['email_neverbounce_enable'] == "on" ? 'checked="checked"' : '').' onclick="ulp_toggle_verifier(this);"> '.__('Verify deliverability using <a href="https://neverbounce.com/" target="_blank">NeverBounce</a>', 'ulp').'
							<br /><em>'.__('Verify deliverability using NeverBounce.', 'ulp').'</em>
							<div id="ulp-neverbounce-data"'.($this->options['email_neverbounce_enable'] == "on" ? '' : ' style="display: none;"').'>
								<strong>'.__('NeverBounce API Username', 'ulp').':</strong>
								<br /><input type="text" id="ulp_email_neverbounce_user" name="ulp_email_neverbounce_user" value="'.esc_html($this->options['email_neverbounce_user']).'" class="ulp-input-30">
								<br /><em>'.__('Please enter NeverBounce API Username. You can find it <a href="https://app.neverbounce.com/settings/api">here</a>.', 'ulp').'</em>
								<br /><strong>'.__('NeverBounce API Secret Key', 'ulp').':</strong>
								<br /><input type="text" id="ulp_email_neverbounce_key" name="ulp_email_neverbounce_key" value="'.esc_html($this->options['email_neverbounce_key']).'" class="ulp-input-30">
								<br /><em>'.__('Please enter NeverBounce API Secret Key. You can find it <a href="https://app.neverbounce.com/settings/api">here</a>.', 'ulp').'</em>
							</div>
						</td>
					</tr>';
	}
	function options_check($_errors) {
		$this->populate_options();
		if (isset($_POST['ulp_email_neverbounce_enable'])) $this->options['email_neverbounce_enable'] = 'on';
		else $this->options['email_neverbounce_enable'] = 'off';
		if ($this->options['email_neverbounce_enable'] == 'on') {
			if (strlen($this->options['email_neverbounce_user']) == 0) $_errors[] = __('Invalid NeverBounce Username.', 'ulp');
			if (strlen($this->options['email_neverbounce_key']) == 0) $_errors[] = __('Invalid NeverBounce API Secret Key.', 'ulp');
		}
		return $_errors;
	}
	function options_update() {
		$this->populate_options();
		if (isset($_POST['ulp_email_neverbounce_enable'])) $this->options['email_neverbounce_enable'] = 'on';
		else $this->options['email_neverbounce_enable'] = 'off';
		$this->update_options();
	}
	function front_fields_check($_field_errors, $_popup_options) {
		if (isset($_REQUEST['encoded']) && $_REQUEST['encoded'] == true) {
			$request_data = json_decode(base64_decode(trim(stripslashes($_REQUEST['data']))), true);
		} else $request_data = $_REQUEST;
		if (isset($request_data['ulp-email'])) $email = trim(stripslashes($request_data['ulp-email']));
		else $email = '';
		if ($this->options['email_neverbounce_enable'] == 'on') {
			$result = $this->verify_neverbounce($this->options['email_neverbounce_user'], $this->options['email_neverbounce_key'], $email);
			if (is_array($result) && array_key_exists('result', $result) && $result['result'] !== 0) $_field_errors['ulp-email'] = 'ERROR';
		}
		return $_field_errors;
	}
	function verify_neverbounce($_user_id, $_api_key, $_email) {
		try {
			$data = array(
				'grant_type' => 'client_credentials',
				'scope' => 'basic user'
			);
			$url = 'https://api.neverbounce.com/v3/access_token';
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_USERPWD, $_user_id.":".$_api_key);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($curl, CURLOPT_TIMEOUT, 20);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
			//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($curl);
			curl_close($curl);
			$result = json_decode($response, true);
			if (is_array($result) && array_key_exists('access_token', $result)) {
				$data = array(
					'access_token' => $result['access_token'],
					'email' => $_email
				);
				$url = 'https://api.neverbounce.com/v3/single';
				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
				curl_setopt($curl, CURLOPT_TIMEOUT, 20);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
				//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				$response = curl_exec($curl);
				curl_close($curl);
				$result = json_decode($response, true);
			} else $result = false;
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
}
$ulp_neverbounce = new ulp_neverbounce_class();
?>