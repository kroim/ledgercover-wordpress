<?php
/* TrueMail integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_truemail_class {
	var $options = array(
		"email_truemail_enable" => "off",
		"email_truemail_key" => ""
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
							<input type="checkbox" class="ulp-email-verifier" data-id="ulp-truemail-data" name="ulp_email_truemail_enable" '.($this->options['email_truemail_enable'] == "on" ? 'checked="checked"' : '').' onclick="ulp_toggle_verifier(this);"> '.__('Verify deliverability using <a href="https://www.truemail.com/" target="_blank">TrueMail</a>', 'ulp').'
							<br /><em>'.__('Verify deliverability using TrueMail API.', 'ulp').'</em>
							<div id="ulp-truemail-data"'.($this->options['email_truemail_enable'] == "on" ? '' : ' style="display: none;"').'>
								<strong>'.__('TrueMail API Key', 'ulp').':</strong>
								<br /><input type="text" id="ulp_email_truemail_key" name="ulp_email_truemail_key" value="'.esc_html($this->options['email_truemail_key']).'" class="ulp-input-30">
								<br /><em>'.__('Please enter TrueMail API Key. You can find it <a href="https://truemail.io/app/api-keys" target="_blank">here</a>.', 'ulp').'</em>
							</div>
						</td>
					</tr>';
	}
	function options_check($_errors) {
		$this->populate_options();
		if (isset($_POST['ulp_email_truemail_enable'])) $this->options['email_truemail_enable'] = 'on';
		else $this->options['email_truemail_enable'] = 'off';
		if ($this->options['email_truemail_enable'] == 'on') {
			if (strlen($this->options['email_truemail_key']) == 0) $_errors[] = __('Invalid TrueMail API Key.', 'ulp');
		}
		return $_errors;
	}
	function options_update() {
		$this->populate_options();
		if (isset($_POST['ulp_email_truemail_enable'])) $this->options['email_truemail_enable'] = 'on';
		else $this->options['email_truemail_enable'] = 'off';
		$this->update_options();
	}
	function front_fields_check($_field_errors, $_popup_options) {
		if (isset($_REQUEST['encoded']) && $_REQUEST['encoded'] == true) {
			$request_data = json_decode(base64_decode(trim(stripslashes($_REQUEST['data']))), true);
		} else $request_data = $_REQUEST;
		if (isset($request_data['ulp-email'])) $email = trim(stripslashes($request_data['ulp-email']));
		else $email = '';

		if ($this->options['email_truemail_enable'] == 'on' && !empty($email)) {
			$result = $this->verify_truemail($this->options['email_truemail_key'], $email);
			if (is_array($result) && array_key_exists('status', $result) && $result['status'] == 'success' && array_key_exists('result', $result) && $result['result'] != 'valid' && $result['result'] != 'unverifiable' && $result['result'] != 'unknown') $_field_errors['ulp-email'] = 'ERROR';
		}
		return $_field_errors;
	}
	function verify_truemail($_api_key, $_email) {
		try {
			$url = 'https://truemail.io/api/v1/verify/single?access_token='.urlencode($_api_key).'&email='.urlencode($_email);
			$curl = curl_init($url);
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
$ulp_truemail = new ulp_truemail_class();
?>