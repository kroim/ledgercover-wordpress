<?php
/* Clearout integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_clearout_class {
	var $options = array(
		"email_clearout_enable" => "off",
		"email_clearout_key" => ""
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
							<input type="checkbox" class="ulp-email-verifier" data-id="ulp-clearout-data" name="ulp_email_clearout_enable" '.($this->options['email_clearout_enable'] == "on" ? 'checked="checked"' : '').' onclick="ulp_toggle_verifier(this);"> '.__('Verify deliverability using <a href="https://clearout.io/" target="_blank">Clearout</a>', 'ulp').'
							<br /><em>'.__('Verify deliverability using Clearout.', 'ulp').'</em>
							<div id="ulp-clearout-data"'.($this->options['email_clearout_enable'] == "on" ? '' : ' style="display: none;"').'>
								<strong>'.__('Clearout API Token', 'ulp').':</strong>
								<br /><input type="text" id="ulp_email_clearout_key" name="ulp_email_clearout_key" value="'.esc_html($this->options['email_clearout_key']).'" class="ulp-input-30">
								<br /><em>'.sprintf(esc_html__('Please enter Clearout API Token. You can find it in the %sProfile%s.', 'ulp'), '<a href="https://app.clearout.io/dashboard/account?tab=profile" target="_blank">', '</a>').'</em>
							</div>
						</td>
					</tr>';
	}
	function options_check($_errors) {
		$this->populate_options();
		if (isset($_POST['ulp_email_clearout_enable'])) $this->options['email_clearout_enable'] = 'on';
		else $this->options['email_clearout_enable'] = 'off';
		if ($this->options['email_clearout_enable'] == 'on') {
			if (strlen($this->options['email_clearout_key']) == 0) $_errors[] = __('Invalid Clearout API Token.', 'ulp');
		}
		return $_errors;
	}
	function options_update() {
		$this->populate_options();
		if (isset($_POST['ulp_email_clearout_enable'])) $this->options['email_clearout_enable'] = 'on';
		else $this->options['email_clearout_enable'] = 'off';
		$this->update_options();
	}
	function front_fields_check($_field_errors, $_popup_options) {
		if (isset($_REQUEST['encoded']) && $_REQUEST['encoded'] == true) {
			$request_data = json_decode(base64_decode(trim(stripslashes($_REQUEST['data']))), true);
		} else $request_data = $_REQUEST;
		if (isset($request_data['ulp-email'])) $email = trim(stripslashes($request_data['ulp-email']));
		else $email = '';
		if ($this->options['email_clearout_enable'] == 'on') {
			$result = $this->verify_clearout($this->options['email_clearout_key'], $email);
			if (is_array($result) && array_key_exists('data', $result) && array_key_exists('results', $result['data']) && !in_array($result['data']['results'][0]['status'], array('valid'))) $_field_errors['ulp-email'] = 'ERROR';
		}
		return $_field_errors;
	}
	function verify_clearout($_api_key, $_email) {
		$headers = array(
			'Content-Type: application/json;charset=UTF-8',
			'Authorization: Bearer:'.$_api_key
		);
		try {
			$url = 'https://api.clearout.io/v1/email_verify/instant';
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array('email' => $_email)));
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
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
}
$ulp_clearout = new ulp_clearout_class();
?>