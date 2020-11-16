<?php
/* Campaign Monitor integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_campaignmonitor_class {
	var $default_popup_options = array(
		'campaignmonitor_enable' => "off",
		'campaignmonitor_api_key' => '',
		'campaignmonitor_client' => '',
		'campaignmonitor_client_id' => '',
		'campaignmonitor_list' => '',
		'campaignmonitor_list_id' => '',
		'campaignmonitor_fields' => ''
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
			add_action('wp_ajax_ulp-campaignmonitor-clients', array(&$this, "show_clients"));
			add_action('wp_ajax_ulp-campaignmonitor-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-campaignmonitor-fields', array(&$this, "show_fields"));
			
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
				<h3>'.__('Campaign Monitor Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Campaign Monitor', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_campaignmonitor_enable" name="ulp_campaignmonitor_enable" '.($popup_options['campaignmonitor_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Campaign Monitor', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Campaign Monitor.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_campaignmonitor_api_key" name="ulp_campaignmonitor_api_key" value="'.esc_html($popup_options['campaignmonitor_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Campaign Monitor API Key. You can get your API Key from the Account Settings page when logged into your Campaign Monitor account.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Client ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-campaignmonitor-client" name="ulp_campaignmonitor_client" value="'.esc_html($popup_options['campaignmonitor_client']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_campaignmonitor_clients_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-campaignmonitor-client-id" name="ulp_campaignmonitor_client_id" value="'.esc_html($popup_options['campaignmonitor_client_id']).'" />
							<div id="ulp-campaignmonitor-client-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your Client ID.', 'ulp').'</em>
							<script>
								function ulp_campaignmonitor_clients_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-campaignmonitor-clients", "ulp_api_key": jQuery("#ulp_campaignmonitor_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-campaignmonitor-list" name="ulp_campaignmonitor_list" value="'.esc_html($popup_options['campaignmonitor_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_campaignmonitor_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-campaignmonitor-list-id" name="ulp_campaignmonitor_list_id" value="'.esc_html($popup_options['campaignmonitor_list_id']).'" />
							<div id="ulp-campaignmonitor-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_campaignmonitor_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-campaignmonitor-lists", "ulp_api_key": jQuery("#ulp_campaignmonitor_api_key").val(), "ulp_client_id": jQuery("#ulp-campaignmonitor-client-id").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-campaignmonitor-fields-html">';
		if (!empty($popup_options['campaignmonitor_api_key']) && !empty($popup_options['campaignmonitor_list_id'])) {
			$fields = $this->get_fields_html($popup_options['campaignmonitor_api_key'], $popup_options['campaignmonitor_list_id'], $popup_options['campaignmonitor_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_campaignmonitor_fields_button" class="ulp_button button-secondary" onclick="return ulp_campaignmonitor_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-campaignmonitor-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_campaignmonitor_loadfields() {
									jQuery("#ulp-campaignmonitor-fields-loading").fadeIn(350);
									jQuery(".ulp-campaignmonitor-fields-html").slideUp(350);
									var data = {action: "ulp-campaignmonitor-fields", ulp_key: jQuery("#ulp_campaignmonitor_api_key").val(), ulp_list: jQuery("#ulp-campaignmonitor-list-id").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-campaignmonitor-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-campaignmonitor-fields-html").html(data.html);
												jQuery(".ulp-campaignmonitor-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-campaignmonitor-fields-html").html("<div class=\'ulp-campaignmonitor-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Campaign Monitor server.', 'ulp').'</strong></div>");
												jQuery(".ulp-campaignmonitor-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-campaignmonitor-fields-html").html("<div class=\'ulp-campaignmonitor-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Campaign Monitor server.', 'ulp').'</strong></div>");
											jQuery(".ulp-campaignmonitor-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_campaignmonitor_enable"])) $popup_options['campaignmonitor_enable'] = "on";
		else $popup_options['campaignmonitor_enable'] = "off";
		if ($popup_options['campaignmonitor_enable'] == 'on') {
			if (empty($popup_options['campaignmonitor_api_key'])) $errors[] = __('Invalid Campaign Monitor API Key.', 'ulp');
			if (empty($popup_options['campaignmonitor_client_id'])) $errors[] = __('Invalid Campaign Monitor Client ID.', 'ulp');
			if (empty($popup_options['campaignmonitor_list_id'])) $errors[] = __('Invalid Campaign Monitor List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_campaignmonitor_enable"])) $popup_options['campaignmonitor_enable'] = "on";
		else $popup_options['campaignmonitor_enable'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_campaignmonitor_field_')) == 'ulp_campaignmonitor_field_') {
				$field = substr($key, strlen('ulp_campaignmonitor_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['campaignmonitor_fields'] = serialize($fields);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['campaignmonitor_enable'] == 'on') {
			$data['EmailAddress'] = $_subscriber['{subscription-email}'];
			$data['Name'] = $_subscriber['{subscription-name}'];
			$data['Resubscribe'] = 'true';
			$data['RestartSubscriptionBasedAutoresponders'] = 'true';

			$fields = array();
			if (!empty($popup_options['campaignmonitor_fields'])) $fields = unserialize($popup_options['campaignmonitor_fields']);
			if (!empty($fields) && is_array($fields)) {
				foreach ($fields as $key => $value) {
					if (!empty($value)) {
						$data['CustomFields'][] = array('Key' => $key, 'Value' => strtr($value, $_subscriber));
					}
				}
			}
			
			$result = $this->connect($popup_options['campaignmonitor_api_key'], 'subscribers/'.urlencode($popup_options['campaignmonitor_list_id']).'.json?email='.urlencode($_subscriber['{subscription-email}']));
			if (is_array($result) && array_key_exists('EmailAddress', $result)) {
				$result = $this->connect($popup_options['campaignmonitor_api_key'], 'subscribers/'.urlencode($popup_options['campaignmonitor_list_id']).'.json?email='.urlencode($_subscriber['{subscription-email}']), $data, 'PUT');
			} else {
				$result = $this->connect($popup_options['campaignmonitor_api_key'], 'subscribers/'.urlencode($popup_options['campaignmonitor_list_id']).'.json', $data);
			}
		}
	}
	
	function show_clients() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$clients = array();
			if (!isset($_POST['ulp_api_key']) || empty($_POST['ulp_api_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_api_key']));
			
			$result = $this->connect($key, 'clients.json');
			
			if (is_array($result)) {
				foreach ($result as $client) {
					if (is_array($client)) {
						if (array_key_exists('ClientID', $client) && array_key_exists('Name', $client)) {
							$clients[$client['ClientID']] = $client['Name'];
						}
					}
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$client_html = '';
			if (!empty($clients)) {
				foreach ($clients as $id => $name) {
					$client_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
				}
			} else $client_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No data found!', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $client_html;
			$return_object['items'] = sizeof($clients);
			echo json_encode($return_object);
		}
		exit;
	}

	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_api_key']) || empty($_POST['ulp_api_key']) || !isset($_POST['ulp_client_id']) || empty($_POST['ulp_client_id'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key or Client ID!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_api_key']));
			$client_id = trim(stripslashes($_POST['ulp_client_id']));
			
			$result = $this->connect($key, 'clients/'.$client_id.'/lists.json');
			
			if (is_array($result)) {
				foreach ($result as $list) {
					if (is_array($list)) {
						if (array_key_exists('ListID', $list) && array_key_exists('Name', $list)) {
							$lists[$list['ListID']] = $list['Name'];
						}
					}
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
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
			if (!isset($_POST['ulp_key']) || !isset($_POST['ulp_list']) || empty($_POST['ulp_key']) || empty($_POST['ulp_list'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-campaignmonitor-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Key or List ID.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_key']));
			$list = trim(stripslashes($_POST['ulp_list']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($key, $list, $this->default_popup_options['campaignmonitor_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_key, $_list, $_fields) {
		$result = $this->connect($_key, 'lists/'.urlencode($_list).'/customfields.json');
		$fields = '';
		$values = unserialize($_fields);
		if (!is_array($values)) $values = array();
		if (is_array($result)) {
			if (!empty($result)) {
				$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Campaign Monitor fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
				foreach ($result as $field) {
					if (is_array($field)) {
						if (array_key_exists('Key', $field) && array_key_exists('FieldName', $field)) {
							$field['Key'] = str_replace(array('[', ']'), array('', ''), $field['Key']);
							$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['Key']).':</strong></td>
					<td>
						<input type="text" id="ulp_campaignmonitor_field_'.esc_html($field['Key']).'" name="ulp_campaignmonitor_field_'.esc_html($field['Key']).'" value="'.esc_html(array_key_exists($field['Key'], $values) ? $values[$field['Key']] : '').'" class="widefat"'.($field['Key'] == 'EMAIL' ? ' readonly="readonly"' : '').' />
						<br /><em>'.esc_html($field['FieldName']).'</em>
					</td>
				</tr>';
						}
					}
				}
				$fields .= '
			</table>';
			} else {
				$fields = '<div class="ulp-campaignmonitor-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
			}
		} else {
			$fields = '<div class="ulp-campaignmonitor-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	
	function connect($_api_key, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json',
			'Authorization: Basic '.base64_encode($_api_key)
		);
		try {
			$url = 'https://api.createsend.com/api/v3.1/'.ltrim($_path, '/');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
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
$ulp_campaignmonitor = new ulp_campaignmonitor_class();
?>