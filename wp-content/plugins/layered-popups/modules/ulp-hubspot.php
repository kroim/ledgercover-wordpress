<?php
/* HubSpot integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_hubspot_class {
	var $default_popup_options = array(
		"hubspot_enable" => "off",
		"hubspot_api_key" => "",
		"hubspot_list" => "",
		"hubspot_list_id" => "",
		"hubspot_fields" => ""
	);
	function __construct() {
		$this->default_popup_options['hubspot_fields'] = serialize(array('email' => '{subscription-email}', 'firstname' => '{subscription-name}'));
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-hubspot-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-hubspot-fields', array(&$this, "show_fields"));
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
				<h3>'.__('HubSpot Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable HubSpot', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_hubspot_enable" name="ulp_hubspot_enable" '.($popup_options['hubspot_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to HubSpot', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to HubSpot.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_hubspot_api_key" name="ulp_hubspot_api_key" value="'.esc_html($popup_options['hubspot_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your HubSpot API Key. Go to "Integrations" page in your HubSpot account and click "Get your HubSpot API Key".', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID:', 'ulp').'</th>
						<td>
							<input type="text" id="ulp-hubspot-list" name="ulp_hubspot_list" value="'.esc_html($popup_options['hubspot_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_hubspot_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-hubspot-list-id" name="ulp_hubspot_list_id" value="'.esc_html($popup_options['hubspot_list_id']).'" />
							<div id="ulp-hubspot-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_hubspot_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-hubspot-lists", "ulp_api_key": jQuery("#ulp_hubspot_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-hubspot-fields-html">';
		if (!empty($popup_options['hubspot_api_key']) && !empty($popup_options['hubspot_list_id'])) {
			$fields = $this->get_fields_html($popup_options['hubspot_api_key'], $popup_options['hubspot_list_id'], $popup_options['hubspot_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_hubspot_fields_button" class="ulp_button button-secondary" onclick="return ulp_hubspot_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-hubspot-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_hubspot_loadfields() {
									jQuery("#ulp-hubspot-fields-loading").fadeIn(350);
									jQuery(".ulp-hubspot-fields-html").slideUp(350);
									var data = {action: "ulp-hubspot-fields", ulp_key: jQuery("#ulp_hubspot_api_key").val(), ulp_list: jQuery("#ulp-hubspot-list-id").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-hubspot-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-hubspot-fields-html").html(data.html);
												jQuery(".ulp-hubspot-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-hubspot-fields-html").html("<div class=\'ulp-hubspot-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to HubSpot server.', 'ulp').'</strong></div>");
												jQuery(".ulp-hubspot-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-hubspot-fields-html").html("<div class=\'ulp-hubspot-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to HubSpot server.', 'ulp').'</strong></div>");
											jQuery(".ulp-hubspot-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_hubspot_enable"])) $popup_options['hubspot_enable'] = "on";
		else $popup_options['hubspot_enable'] = "off";
		if ($popup_options['hubspot_enable'] == 'on') {
			if (empty($popup_options['hubspot_api_key'])) $errors[] = __('Invalid HubSpot API Key.', 'ulp');
			if (empty($popup_options['hubspot_list_id'])) $errors[] = __('Invalid HubSpot List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_hubspot_enable"])) $popup_options['hubspot_enable'] = "on";
		else $popup_options['hubspot_enable'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_hubspot_field_')) == 'ulp_hubspot_field_') {
				$field = substr($key, strlen('ulp_hubspot_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['hubspot_fields'] = serialize($fields);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['hubspot_enable'] == 'on') {
			$data = array('properties' => array());
			$fields = array();
			if (!empty($popup_options['hubspot_fields'])) $fields = unserialize($popup_options['hubspot_fields']);
			if (!empty($fields) && is_array($fields)) {
				foreach ($fields as $key => $value) {
					if (!empty($value)) {
						$data['properties'][] = array('property' => $key, 'value' => strtr($value, $_subscriber));
					}
				}
			}
			//$data['properities'][] = array('property' => 'ipaddress', 'value' => $_SERVER['REMOTE_ADDR']);

			try {
				$hubspot_url = 'https://api.hubapi.com/contacts/v1/contact/createOrUpdate/email/'.$_subscriber['{subscription-email}'].'/?hapikey='.rawurlencode($popup_options['hubspot_api_key']);
				$curl = curl_init($hubspot_url);
				curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
				//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				$response = curl_exec($curl);
				$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				curl_close($curl);

				$hubspot_url = 'https://api.hubapi.com/contacts/v1/lists/'.$popup_options['hubspot_list_id'].'/add/?hapikey='.rawurlencode($popup_options['hubspot_api_key']);
				$data = array('emails' => array($_subscriber['{subscription-email}']));
				$curl = curl_init($hubspot_url);
				curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
				//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				$response = curl_exec($curl);
				$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				curl_close($curl);
			} catch (Exception $e) {
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_api_key']) || empty($_POST['ulp_api_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_api_key']));
			$hubspot_url = 'https://api.hubapi.com/contacts/v1/lists?hapikey='.rawurlencode($key).'&count=221&offset=0';

			$lists = array();
			try {
				$curl = curl_init($hubspot_url);
				curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
				//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				$response = curl_exec($curl);
				$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				curl_close($curl);
				$result = json_decode($response, true);
				if ($result) {
					if (array_key_exists("status", $result) && $result['status'] == 'error') {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.esc_html($result['message']).'</div>';
						echo json_encode($return_object);
						exit;
					} else if (!array_key_exists("lists", $result) || !is_array($result['lists']) || sizeof($result['lists']) == 0) {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Lists not found!', 'ulp').'</div>';
						echo json_encode($return_object);
						exit;
					}
					foreach($result['lists'] as $list) {
						if (is_array($list)) {
							if (array_key_exists('listId', $list) && array_key_exists('name', $list)) {
								$lists[$list['listId']] = $list['name'];
							}
						}
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to HubSpot API server!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} catch (Exception $e) {
			}
			$list_html = '';
			if (!empty($lists)) {
				foreach ($lists as $id => $name) {
					$list_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
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
			if (!isset($_POST['ulp_key']) || !isset($_POST['ulp_list']) || empty($_POST['ulp_key']) || empty($_POST['ulp_list'])) exit;
			$key = trim(stripslashes($_POST['ulp_key']));
			$list = trim(stripslashes($_POST['ulp_list']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($key, $list, $this->default_popup_options['hubspot_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_key, $_list, $_fields) {
		$hubspot_url = 'https://api.hubapi.com/contacts/v2/properties/?hapikey='.rawurlencode($_key);
		$fields = '';
		try {
			$curl = curl_init($hubspot_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
			//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($curl);
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			$result = json_decode($response, true);
			if ($result && is_array($result)) {
				if (array_key_exists("status", $result) && $result['status'] == 'error') {
					return '<div class="ulp-hubspot-grouping" style="margin-bottom: 10px;"><strong>'.esc_html($result['message']).'</strong></div>';
				}
				$values = unserialize($_fields);
				if (!is_array($values)) $values = array();
				if (!empty($result)) {
					$fields = '
					'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate HubSpot fields with the popup fields.', 'ulp').'
					<table style="min-width: 280px; width: 50%;">';
					foreach ($result as $field) {
						if (is_array($field)) {
							if (array_key_exists('name', $field) && array_key_exists('label', $field)) {
								if (array_key_exists('hidden', $field) && !$field['hidden'] && array_key_exists('readOnlyValue', $field) && !$field['readOnlyValue']) {
									$fields .= '
						<tr>
							<td style="width: 100px;"><strong>'.esc_html($field['label']).':</strong></td>
							<td>
								<input type="text" id="ulp_hubspot_field_'.esc_html($field['name']).'" name="ulp_hubspot_field_'.esc_html($field['name']).'" value="'.esc_html(array_key_exists($field['name'], $values) ? $values[$field['name']] : '').'" class="widefat"'.($field['name'] == 'email' ? ' readonly="readonly"' : '').' />
								<br /><em>'.esc_html($field['label']).(!empty($field['description']) ? '. '.esc_html(rtrim($field['description'], '.').'.') : '').'</em>
							</td>
						</tr>';
								}
							}
						}
					}
							$fields .= '
					</table>';
				} else {
					$fields = '<div class="ulp-hubspot-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
				}
			} else {
				return '<div class="ulp-hubspot-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not connect to HubSpot API server!', 'ulp').'</strong></div>';
			}
		} catch (Exception $e) {
		}
		return $fields;
	}
}
$ulp_hubspot = new ulp_hubspot_class();
?>