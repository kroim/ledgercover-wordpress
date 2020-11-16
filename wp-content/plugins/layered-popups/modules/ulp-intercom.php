<?php
/* Intercom integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_intercom_class {
	var $default_popup_options = array(
		"intercom_enable" => "off",
		"intercom_access_token" => "",
		"intercom_tags" => "",
		"intercom_attributes" => ""
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
		$fields = unserialize($popup_options['intercom_attributes']);
		echo '
				<h3>'.__('Intercom Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Intercom', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_intercom_enable" name="ulp_intercom_enable" '.($popup_options['intercom_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Intercom', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Intercom (submitted as lead).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Personal Access Token', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_intercom_access_token" name="ulp_intercom_access_token" value="'.esc_html($popup_options['intercom_access_token']).'" class="widefat">
							<br /><em>'.__('Enter your Intercom Personal Access Token. You can get it on App Settings page in your account. Make sure it has extended scopes.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Attributes', 'ulp').':</th>
						<td>
							'.__('Please adjust the custom attributes below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Intercom attributes with the popup fields.', 'ulp').'
							<table style="width: 100%;">
								<tr>
									<td style="width: 200px;"><strong>'.__('Name', 'ulp').'</strong></td>
									<td><strong>'.__('Value', 'ulp').'</strong></td>
								</tr>';
		$i = 0;
		if (is_array($fields)) {
			foreach ($fields as $key => $value) {
				echo '									
								<tr>
									<td>
										<input type="text" name="ulp_intercom_attributes_name[]" value="'.esc_html($key).'" class="widefat">
										<br /><em>'.($i > 0 ? '<a href="#" onclick="return ulp_intercom_remove_field(this);">'.__('Remove Field', 'ulp').'</a>' : '').'</em>
									</td>
									<td>
										<input type="text" name="ulp_intercom_attributes_value[]" value="'.esc_html($value).'" class="widefat">
									</td>
								</tr>';
				$i++;
			}
		}
		if ($i == 0) {
			echo '									
								<tr>
									<td>
										<input type="text" name="ulp_intercom_attributes_name[]" value="" class="widefat">
									</td>
									<td>
										<input type="text" name="ulp_intercom_attributes_value[]" value="" class="widefat">
									</td>
								</tr>';
		}
		echo '
								<tr style="display: none;" id="intercom-field-template">
									<td>
										<input type="text" name="ulp_intercom_attributes_name[]" value="" class="widefat">
										<br /><em><a href="#" onclick="return ulp_intercom_remove_field(this);">'.__('Remove Field', 'ulp').'</a></em>
									</td>
									<td>
										<input type="text" name="ulp_intercom_attributes_value[]" value="" class="widefat">
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<a href="#" class="button-secondary" onclick="return ulp_intercom_add_field(this);">'.__('Add Field', 'ulp').'</a>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<th>'.__('Tags', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_intercom_tags" name="ulp_intercom_tags" value="'.esc_html($popup_options['intercom_tags']).'" class="widefat">
							<br /><em>'.__('Enter comma-separated list of tags for the lead/contact.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<script>
					function ulp_intercom_add_field(object) {
						jQuery("#intercom-field-template").before("<tr>"+jQuery("#intercom-field-template").html()+"</tr>");
						return false;
					}
					function ulp_intercom_remove_field(object) {
						var row = jQuery(object).parentsUntil("tr").parent();
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
		if (isset($ulp->postdata["ulp_intercom_enable"])) $popup_options['intercom_enable'] = "on";
		else $popup_options['intercom_enable'] = "off";
		if ($popup_options['intercom_enable'] == 'on') {
			if (empty($popup_options['intercom_access_token'])) $errors[] = __('Invalid Intercom API Key.', 'ulp');
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
		if (isset($ulp->postdata["ulp_intercom_enable"])) $popup_options['intercom_enable'] = "on";
		else $popup_options['intercom_enable'] = "off";
		
		$tags = explode(',', $popup_options['intercom_tags']);
		$tags_ready = array();
		foreach($tags as $tag) {
			$tag = trim($tag);
			if (strlen($tag) > 0) $tags_ready[] = $tag;
		}
		$popup_options['intercom_tags'] = implode(', ', $tags_ready);

		if (is_array($ulp->postdata["ulp_intercom_attributes_name"]) && is_array($ulp->postdata["ulp_intercom_attributes_value"])) {
			$fields = array();
			for($i=0; $i<sizeof($ulp->postdata["ulp_intercom_attributes_name"]); $i++) {
				$key = stripslashes(trim($ulp->postdata['ulp_intercom_attributes_name'][$i]));
				$value = stripslashes(trim($ulp->postdata['ulp_intercom_attributes_value'][$i]));
				if (!empty($key)) $fields[$key] = $value;
			}
			if (!empty($fields)) $popup_options['intercom_attributes'] = serialize($fields);
			else $popup_options['intercom_attributes'] = '';
		} else $popup_options['intercom_attributes'] = '';

		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		$attributes = unserialize($popup_options['intercom_attributes']);
		if ($popup_options['intercom_enable'] == 'on') {
			$data = array(
				'last_seen_ip' => $_SERVER['REMOTE_ADDR'],
				'email' => $_subscriber['{subscription-email}'],
				'name' => $_subscriber['{subscription-name}'],
				'phone' => $_subscriber['{subscription-phone}'],
				'custom_attributes' => $attributes
			);
			$result = $this->connect($popup_options['intercom_access_token'], 'contacts?email='.$_subscriber['{subscription-email}']);
			if (array_key_exists('contacts', $result) && sizeof($result['contacts'])) {
				$data['id'] = $result['contacts'][0]['id'];
			} else {
				
			}
			$result = $this->connect($popup_options['intercom_access_token'], 'contacts', $data);
			
			$tag_data = array(
				'name' => '',
				'users' => array(array('id' => $result['id']))
			);
			$tags = explode(',', $popup_options['intercom_tags']);
			foreach($tags as $tag) {
				$tag = trim($tag);
				if (strlen($tag) > 0) {
					$tag_data['name'] = $tag;
					$result = $this->connect($popup_options['intercom_access_token'], 'tags', $tag_data);
				}
			}
		}
	}
	function connect($_access_token, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Authorization: Bearer '.$_access_token,
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		try {
			$url = 'https://api.intercom.io/'.ltrim($_path, '/');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 30);
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
$ulp_intercom = new ulp_intercom_class();
?>