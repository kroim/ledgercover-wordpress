<?php
/* Direct Mail integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_directmail_class {
	var $default_popup_options = array(
		'directmail_enable' => 'off',
		'directmail_api_key' => '',
		'directmail_api_secret' => '',
		'directmail_form_id' => ''
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
		}
		add_action('ulp_subscribe', array(&$this, 'subscribe'), 10, 2);
	}
	function popup_options_tabs($_tabs) {
		if (!array_key_exists("integration", $_tabs)) $_tabs["integration"] = __('Integration', 'ulp');
		return $_tabs;
	}
	function popup_options_show($_popup_options) {
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('Direct Mail Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Direct Mail ', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_directmail_enable" name="ulp_directmail_enable" '.($popup_options['directmail_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Direct Mail ', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Direct Mail .', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_directmail_api_key" name="ulp_directmail_api_key" value="'.esc_html($popup_options['directmail_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Direct Mail API Key. You can generate your API credentials as described <a href="http://directmailmac.com/support/article/349-authentication-and-authorization" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Secret', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_directmail_api_secret" name="ulp_directmail_api_secret" value="'.esc_html($popup_options['directmail_api_secret']).'" class="widefat">
							<br /><em>'.__('Enter your Direct Mail API Secret. You can generate your API credentials as described <a href="http://directmailmac.com/support/article/349-authentication-and-authorization" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Form ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_directmail_form_id" name="ulp_directmail_form_id" value="'.esc_html($popup_options['directmail_form_id']).'" class="widefat">
							<br /><em>'.__('Enter your Form ID. Please do not forget to enable API access to this form as described ', 'ulp').' <a href="http://directmailmac.com/support/article/349-authentication-and-authorization" target="_blank">here</a> (step #2).</em>
						</td>
					</tr>
				</table>';
	}
	function popup_options_check($_errors) {
		global $ulp;
		$errors = array();
		$popup_options = array();
		foreach ($this->default_popup_options as $key => $value) {
			if (isset($ulp->postdata['ulp_'.$key])) {
				$popup_options[$key] = stripslashes(trim($ulp->postdata['ulp_'.$key]));
			}
		}
		if (isset($ulp->postdata["ulp_directmail_enable"])) $popup_options['directmail_enable'] = "on";
		else $popup_options['directmail_enable'] = "off";
		if ($popup_options['directmail_enable'] == 'on') {
			if (empty($popup_options['directmail_api_key'])) $errors[] = __('Invalid Direct Mail API Key', 'ulp');
			if (empty($popup_options['directmail_api_secret'])) $errors[] = __('Invalid Direct Mail API Secret', 'ulp');
			if (empty($popup_options['directmail_form_id'])) $errors[] = __('Invalid Direct Mail Form ID', 'ulp');
		}
		return array_merge($_errors, $errors);
	}
	function popup_options_populate($_popup_options) {
		global $ulp;
		$popup_options = array();
		foreach ($this->default_popup_options as $key => $value) {
			if (isset($ulp->postdata['ulp_'.$key])) {
				$popup_options[$key] = stripslashes(trim($ulp->postdata['ulp_'.$key]));
			}
		}
		if (isset($ulp->postdata["ulp_directmail_enable"])) $popup_options['directmail_enable'] = "on";
		else $popup_options['directmail_enable'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['directmail_enable'] == 'on') {
			$request = json_encode(array(
				"create_if_necessary" => true,
				"address" => array( 
					"first_name" => $_subscriber['{subscription-name}']
				)
			));

			$ch = curl_init('https://secure.directmailmac.com/api/v1/forms/'.urlencode($popup_options['directmail_form_id']).'/addresses/'.urlencode($_subscriber['{subscription-email}']));
			
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, $popup_options['directmail_api_key'].':'.$popup_options['directmail_api_secret']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
								
			$response = curl_exec($ch);
			curl_close($ch);
		}
	}
}
$ulp_directmail = new ulp_directmail_class();
?>