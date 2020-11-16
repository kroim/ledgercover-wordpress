<?php
/* Omnisend integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_omnisend_class {
	var $default_popup_options = array(
		"omnisend_enable" => "off",
		"omnisend_api_key" => "",
		"omnisend_fields" => array(
			"email" => "{subscription-email}",
			"firstName" => "{subscription-name}",
			"lastName" => "",
			"gender" => "",
			"phone" => "",
			"birthdate" => "",
			"country" => "",
			"state" => "",
			"city" => "",
			"address" => "",
			"postalCode" => ""
		),
		"omnisend_custom_fields" => array()
	);
	var $fields_meta;
	function __construct() {
		$this->fields_meta = array(
			'email' => __('Email address', 'ulp'),
			'firstName' => __('First name', 'ulp'),
			'lastName' => __('Last name', 'ulp'),
			'gender' => __('Gender', 'ulp'),
			'phone' => __('Phone #', 'ulp'),
			'birthdate' => __('Birtdate', 'ulp'),
			'country' => __('Country', 'ulp'),
			'state' => __('State', 'ulp'),
			'city' => __('City', 'ulp'),
			'address' => __('Address', 'ulp'),
			'postalCode' => __('Postal Code', 'ulp')
		);
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
				<h3>'.__('Omnisend Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Omnisend', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_omnisend_enable" name="ulp_omnisend_enable" '.($popup_options['omnisend_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Omnisend', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Omnisend.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_omnisend_api_key" name="ulp_omnisend_api_key" value="'.esc_html($popup_options['omnisend_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Omnisend API Key. You can get it <a href="https://app.omnisend.com/#/my-account/integrations/api-keys" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-omnisend-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Omnisend fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
		foreach ($this->default_popup_options['omnisend_fields'] as $key => $value) {
			echo '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($this->fields_meta[$key]).':</strong></td>
					<td>
						<input type="text" id="ulp_omnisend_field_'.esc_html($key).'" name="ulp_omnisend_field_'.esc_html($key).'" value="'.esc_html(array_key_exists($key, $popup_options['omnisend_fields']) ? $popup_options['omnisend_fields'][$key] : $value).'" class="widefat"'.($key == 'email' ? ' readonly="readonly"' : '').' />
						<br /><em>'.esc_html($this->fields_meta[$key].' ('.$key.')').'</em>
					</td>
				</tr>';
		}
		echo '
								</table>
							</div>
						</td>
					</tr>
					<tr>
						<th>'.__('Custom Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-omnisend-fields-html">
								'.__('Please adjust the custom fields below. Field name must contain only these characters: <code>A-Z</code>, <code>a-z</code>, <code>0-9</code> and <code>_</code> (field name is case sensitive). You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Omnisend custom fields with the popup fields.', 'ulp').'
								<table style="width: 100%;">
									<tr>
										<td style="width: 200px; padding-bottom: 5px;"><strong>'.__('Name', 'ulp').'</strong></td>
										<td style="padding-bottom: 5px;"><strong>'.__('Value', 'ulp').'</strong></td>
										<td style="width: 32px; padding-bottom: 5px;"></td>
									</tr>';
		$i = 0;
		foreach ($popup_options['omnisend_custom_fields'] as $key => $value) {
			echo '									
									<tr>
										<td>
											<input type="text" name="ulp_omnisend_custom_fields_name[]" value="'.esc_html($key).'" class="widefat">
										</td>
										<td>
											<input type="text" name="ulp_omnisend_custom_fields_value[]" value="'.esc_html($value).'" class="widefat">
										</td>
										<td style="vertical-align: middle;">
											'.($i > 0 ? '<a class="ulp-integration-row-remove" href="#" onclick="return ulp_omnisend_remove_fields(this);"><i class="fas fa-trash-alt"></i></a>' : '').'
										</td>
									</tr>';
			$i++;
		}
		if ($i == 0) {
			echo '									
									<tr>
										<td>
											<input type="text" name="ulp_omnisend_custom_fields_name[]" value="" class="widefat">
										</td>
										<td>
											<input type="text" name="ulp_omnisend_custom_fields_value[]" value="" class="widefat">
										</td>
										<td></td>
									</tr>';
		}
		echo '
									<tr style="display: none;" id="omnisend-fields-template">
										<td>
											<input type="text" name="ulp_omnisend_custom_fields_name[]" value="" class="widefat">
										</td>
										<td>
											<input type="text" name="ulp_omnisend_custom_fields_value[]" value="" class="widefat">
										</td>
										<td style="vertical-align: middle;">
											<a class="ulp-integration-row-remove" href="#" onclick="return ulp_omnisend_remove_fields(this);"><i class="fas fa-trash-alt"></i></a>
										</td>
									</tr>
									<tr>
										<td colspan="3">
											<a class="ulp-button ulp-button-small" onclick="return ulp_omnisend_add_fields(this);"><i class="fas fa-plus"></i><label>'.__('Add Custom Field', 'ulp').'</label></a>
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
				</table>
				<script>
					function ulp_omnisend_add_fields(object) {
						jQuery("#omnisend-fields-template").before("<tr>"+jQuery("#omnisend-fields-template").html()+"</tr>");
						return false;
					}
					function ulp_omnisend_remove_fields(object) {
						var row = jQuery(object).closest("tr");
						jQuery(row).fadeOut(300, function() {
							jQuery(row).remove();
						});
						return false;
					}
				</script>';
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
		if (isset($ulp->postdata["ulp_omnisend_enable"])) $popup_options['omnisend_enable'] = "on";
		else $popup_options['omnisend_enable'] = "off";
		if ($popup_options['omnisend_enable'] == 'on') {
			if (empty($popup_options['omnisend_api_key'])) $errors[] = __('Invalid Omnisend API Key.', 'ulp');
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
		if (isset($ulp->postdata["ulp_omnisend_enable"])) $popup_options['omnisend_enable'] = "on";
		else $popup_options['omnisend_enable'] = "off";
		
		$popup_options['omnisend_fields'] = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_omnisend_field_')) == 'ulp_omnisend_field_') {
				$key = substr($key, strlen('ulp_omnisend_field_'));
				$popup_options['omnisend_fields'][$key] = stripslashes(trim($value));
			}
		}
		$popup_options['omnisend_custom_fields'] = array();
		if (is_array($ulp->postdata["ulp_omnisend_custom_fields_name"]) && is_array($ulp->postdata["ulp_omnisend_custom_fields_value"])) {
			$fields = array();
			for($i=0; $i<sizeof($ulp->postdata["ulp_omnisend_custom_fields_name"]); $i++) {
				$key = preg_replace('/[^a-zA-Z0-9_]/', '', $ulp->postdata['ulp_omnisend_custom_fields_name'][$i]);
				$value = stripslashes(trim($ulp->postdata['ulp_omnisend_custom_fields_value'][$i]));
				if (!empty($key)) $popup_options['omnisend_custom_fields'][$key] = $value;
			}
		}
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['omnisend_enable'] == 'on') {
			$post_data = array(
				'optInIP' => $_SERVER['REMOTE_ADDR'],
				'optInDate' => date(DATE_ATOM),
				'status' => 'subscribed',
				'statusDate' => date(DATE_ATOM),
				'sendWelcomeEmail' => true,
				'email' => $_subscriber['{subscription-email}']
			);
			foreach($popup_options['omnisend_fields'] as $key => $value) {
				if ($key != 'email' && !empty($value)) $post_data[$key] = strtr($value, $_subscriber);
			}
			if (!empty($popup_options['omnisend_custom_fields'])) {
				$post_data['customProperties'] = array();
				foreach($popup_options['omnisend_custom_fields'] as $key => $value) {
					if (!empty($value)) $post_data['customProperties'][$key] = strtr($value, $_subscriber);
				}
			}
			$result = $this->connect($popup_options['omnisend_api_key'], 'contacts', $post_data);
		}
	}
	function connect($_api_key, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json',
			'X-API-KEY: '.$_api_key
		);
		try {
			$url = 'https://api.omnisend.com/v3/'.ltrim($_path, '/');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 120);
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
$ulp_omnisend = new ulp_omnisend_class();
?>