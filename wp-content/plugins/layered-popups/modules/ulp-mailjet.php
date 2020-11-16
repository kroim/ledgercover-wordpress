<?php
/* Mailjet integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_mailjet_class {
	var $default_popup_options = array(
		"mailjet_enable" => "off",
		"mailjet_api_key" => "",
		"mailjet_secret_key" => "",
		"mailjet_list" => "",
		"mailjet_list_id" => "",
		"mailjet_fields" => ""
	);
	function __construct() {
		$this->default_popup_options['mailjet_fields'] = serialize(array('name' => '{subscription-name}'));
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-mailjet-fields', array(&$this, "show_fields"));
			add_action('wp_ajax_ulp-mailjet-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-mailjet-tags', array(&$this, "show_tags"));
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
				<h3>'.__('Mailjet Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Mailjet', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mailjet_enable" name="ulp_mailjet_enable" '.($popup_options['mailjet_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Mailjet', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Mailjet.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Mailjet API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mailjet_api_key" name="ulp_mailjet_api_key" value="'.esc_html($popup_options['mailjet_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Mailjet API Key. You can get it <a href="https://app.mailjet.com/account/api_keys" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Mailjet Secret Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mailjet_secret_key" name="ulp_mailjet_secret_key" value="'.esc_html($popup_options['mailjet_secret_key']).'" class="widefat">
							<br /><em>'.__('Enter your Mailjet Secret Key. You can get it <a href="https://app.mailjet.com/account/api_keys" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-mailjet-list" name="ulp_mailjet_list" value="'.esc_html($popup_options['mailjet_list']).'" class="ulp-input-options" readonly="readonly" onfocus="ulp_mailjet_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-mailjet-list-id" name="ulp_mailjet_list_id" value="'.esc_html($popup_options['mailjet_list_id']).'" />
							<div id="ulp-mailjet-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_mailjet_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-mailjet-lists", "ulp_api_key": jQuery("#ulp_mailjet_api_key").val(), "ulp_secret_key": jQuery("#ulp_mailjet_secret_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Properties', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-mailjet-fields-html">';
		if (!empty($popup_options['mailjet_api_key']) && !empty($popup_options['mailjet_secret_key'])) {
			$fields = $this->get_fields_html($popup_options['mailjet_api_key'], $popup_options['mailjet_secret_key'], $popup_options['mailjet_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_mailjet_fields_button" class="ulp_button button-secondary" onclick="return ulp_mailjet_loadfields();">'.__('Load Properties', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-mailjet-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load properties list. Ignore if you do not need specify properties values.', 'ulp').'</em>
							<script>
								function ulp_mailjet_loadfields() {
									jQuery("#ulp-mailjet-fields-loading").fadeIn(350);
									jQuery(".ulp-mailjet-fields-html").slideUp(350);
									var data = {action: "ulp-mailjet-fields", ulp_api_key: jQuery("#ulp_mailjet_api_key").val(), ulp_secret_key: jQuery("#ulp_mailjet_secret_key").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-mailjet-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-mailjet-fields-html").html(data.html);
												jQuery(".ulp-mailjet-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-mailjet-fields-html").html("<div class=\'ulp-mailjet-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Mailjet server.', 'ulp').'</strong></div>");
												jQuery(".ulp-mailjet-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-mailjet-fields-html").html("<div class=\'ulp-mailjet-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Mailjet server.', 'ulp').'</strong></div>");
											jQuery(".ulp-mailjet-fields-html").slideDown(350);
										}
									});
									return false;
								}
							</script>
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
		if (isset($ulp->postdata["ulp_mailjet_enable"])) $popup_options['mailjet_enable'] = "on";
		else $popup_options['mailjet_enable'] = "off";
		if ($popup_options['mailjet_enable'] == 'on') {
			if (empty($popup_options['mailjet_api_key'])) $errors[] = __('Invalid Mailjet Username.', 'ulp');
			if (empty($popup_options['mailjet_secret_key'])) $errors[] = __('Invalid Mailjet Password.', 'ulp');
			if (empty($popup_options['mailjet_list_id'])) $errors[] = __('Invalid Mailjet List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_mailjet_enable"])) $popup_options['mailjet_enable'] = "on";
		else $popup_options['mailjet_enable'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_mailjet_field_')) == 'ulp_mailjet_field_') {
				$field = substr($key, strlen('ulp_mailjet_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['mailjet_fields'] = serialize($fields);
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['mailjet_enable'] == 'on') {
			$data = array(
				'Email' => $_subscriber['{subscription-email}'],
				'Action' => 'addforce'
			);
			$fields = array();
			if (!empty($_subscriber['{subscription-name}'])) $data['Name'] = $_subscriber['{subscription-name}'];
			$properties = array();
			if (!empty($popup_options['mailjet_fields'])) $fields = unserialize($popup_options['mailjet_fields']);
			if (!empty($fields) && is_array($fields)) {
				foreach ($fields as $key => $value) {
					if (!empty($value)) {
						$properties[] = array('Name' => $key, 'Value' => strtr($value, $_subscriber));
					}
				}
			}
			try {
				$curl = curl_init('https://api.mailjet.com/v3/REST/contactslist/'.$popup_options['mailjet_list_id'].'/managecontact');
				curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
				curl_setopt($curl, CURLOPT_USERPWD, $popup_options['mailjet_api_key'].':'.$popup_options['mailjet_secret_key']);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				$response = curl_exec($curl);
				curl_close($curl);

				$result = json_decode($response, true);
				if (!$result || !is_array($result)) return;
				if (!array_key_exists('Count', $result) || !array_key_exists('Data', $result) || $result['Count'] < 1) return;
				$data = array(
					'Data' => $properties
				);
				$header = array(
					'Content-Type: application/json'
				);
				$curl = curl_init('https://api.mailjet.com/v3/REST/contactdata/'.$result['Data'][0]['ContactID']);
				curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
				curl_setopt($curl, CURLOPT_USERPWD, $popup_options['mailjet_api_key'].':'.$popup_options['mailjet_secret_key']);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
				$response = curl_exec($curl);
				curl_close($curl);
			} catch (Exception $e) {
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_api_key']) || !isset($_POST['ulp_secret_key']) || empty($_POST['ulp_api_key']) || empty($_POST['ulp_secret_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key or Secret Key!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$api_key = trim(stripslashes($_POST['ulp_api_key']));
			$secret_key = trim(stripslashes($_POST['ulp_secret_key']));
			$list_html = '';
			$lists = array();
			try {
				$curl = curl_init('https://api.mailjet.com/v3/REST/contactslist');
				curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
				curl_setopt($curl, CURLOPT_USERPWD, $api_key.':'.$secret_key);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($curl, CURLOPT_POST, 0);
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				$response = curl_exec($curl);

				if (curl_error($curl)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key or Secret Key!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
				$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				if ($httpCode != '200') {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key or Secret Key!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
				curl_close($curl);
				
				$result = json_decode($response, true);
				
				if (!$result || !is_array($result)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
				if (!array_key_exists('Count', $result) || !array_key_exists('Data', $result) || $result['Count'] < 1) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No data found!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
				foreach($result['Data'] as $list) {
					if (!$list['IsDeleted']) {
						$lists[$list['ID']] = $list['Name'];
					}
				}
			} catch (Exception $e) {
			}
			if (!empty($lists)) {
				foreach ($lists as $key => $value) {
					$list_html .= '<a href="#" data-id="'.esc_html($key).'" data-title="'.esc_html($key).(!empty($value) ? ' | '.esc_html($value) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($key).(!empty($value) ? ' | '.esc_html($value) : '').'</a>';
				}
			} else $list_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No data found!', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $list_html;
			$return_object['items'] = sizeof($lists);
			echo json_encode($return_object);
		}
		exit;
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_api_key']) || !isset($_POST['ulp_secret_key']) || empty($_POST['ulp_api_key']) || empty($_POST['ulp_secret_key'])) exit;
			$api_key = trim(stripslashes($_POST['ulp_api_key']));
			$secret_key = trim(stripslashes($_POST['ulp_secret_key']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($api_key, $secret_key, $this->default_popup_options['mailjet_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_api_key, $_secret_key, $_fields) {
		$result = $this->get_fields($_api_key, $_secret_key);
		$fields = '';
		$values = unserialize($_fields);
		if (!is_array($values)) $values = array();
		if (!empty($result)) {
			$fields = '
			'.__('Please adjust the properties below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Mailjet contact properties with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
					foreach ($result as $key => $field) {
						$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($key).':</strong></td>
					<td>
						<input type="text" id="ulp_mailjet_field_'.esc_html($key).'" name="ulp_mailjet_field_'.esc_html($key).'" value="'.esc_html(array_key_exists($key, $values) ? $values[$key] : '').'" class="widefat" />
						<br /><em>'.esc_html($field).'</em>
					</td>
				</tr>';
					}
					$fields .= '
			</table>';
		} else {
			$fields = '<div class="ulp-mailjet-grouping" style="margin-bottom: 10px;"><strong>'.__('No properties found.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function get_fields($_api_key, $_secret_key) {
		$fields = array();
		try {
			$curl = curl_init('https://api.mailjet.com/v3/REST/contactmetadata?Limit=1000');
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_USERPWD, $_api_key.':'.$_secret_key);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_POST, 0);
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			$response = curl_exec($curl);

			if (curl_error($curl)) return array();
			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			if ($httpCode != '200') return array();
				
			$result = json_decode($response, true);
				
			if (!$result || !is_array($result)) return array();
			if (!array_key_exists('Count', $result) || !array_key_exists('Data', $result) || $result['Count'] < 1) return array();
			foreach($result['Data'] as $field) {
				$fields[$field['Name']] = $field['Name'];
			}
		} catch (Exception $e) {
		}
		return $fields;
	}
}
$ulp_mailjet = new ulp_mailjet_class();
?>