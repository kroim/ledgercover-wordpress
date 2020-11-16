<?php
/* TotalSend integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_totalsend_class {
	var $default_popup_options = array(
		"totalsend_enable" => "off",
		"totalsend_api_username" => "",
		"totalsend_api_password" => "",
		"totalsend_list" => "",
		"totalsend_list_id" => "",
		"totalsend_fields" => ""
	);
	function __construct() {
		$this->default_popup_options['totalsend_fields'] = serialize(array('email' => '{subscription-email}', 'firstname' => '{subscription-name}'));
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-totalsend-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-totalsend-fields', array(&$this, "show_fields"));
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
				<h3>'.__('TotalSend Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable TotalSend', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_totalsend_enable" name="ulp_totalsend_enable" '.($popup_options['totalsend_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to TotalSend', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to TotalSend.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Username', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_totalsend_api_username" name="ulp_totalsend_api_username" value="'.esc_html($popup_options['totalsend_api_username']).'" class="widefat">
							<br /><em>'.__('Enter your TotalSend API Username. You can get it <a href="https://app.totalsend.com/app/user/integration/api/" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Password', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_totalsend_api_password" name="ulp_totalsend_api_password" value="'.esc_html($popup_options['totalsend_api_password']).'" class="widefat">
							<br /><em>'.__('Enter your TotalSend API Password. You can get it <a href="https://app.totalsend.com/app/user/integration/api/" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID:', 'ulp').'</th>
						<td>
							<input type="text" id="ulp-totalsend-list" name="ulp_totalsend_list" value="'.esc_html($popup_options['totalsend_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_totalsend_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-totalsend-list-id" name="ulp_totalsend_list_id" value="'.esc_html($popup_options['totalsend_list_id']).'" />
							<div id="ulp-totalsend-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_totalsend_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-totalsend-lists", "ulp_api_username": jQuery("#ulp_totalsend_api_username").val(), "ulp_api_password": jQuery("#ulp_totalsend_api_password").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-totalsend-fields-html">';
		if (!empty($popup_options['totalsend_api_username']) && !empty($popup_options['totalsend_list_id'])) {
			$fields = $this->get_fields_html($popup_options['totalsend_api_username'], $popup_options['totalsend_api_password'], $popup_options['totalsend_list_id'], $popup_options['totalsend_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_totalsend_fields_button" class="ulp_button button-secondary" onclick="return ulp_totalsend_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-totalsend-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_totalsend_loadfields() {
									jQuery("#ulp-totalsend-fields-loading").fadeIn(350);
									jQuery(".ulp-totalsend-fields-html").slideUp(350);
									var data = {action: "ulp-totalsend-fields", ulp_username: jQuery("#ulp_totalsend_api_username").val(), ulp_password: jQuery("#ulp_totalsend_api_password").val(), ulp_list: jQuery("#ulp-totalsend-list-id").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-totalsend-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-totalsend-fields-html").html(data.html);
												jQuery(".ulp-totalsend-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-totalsend-fields-html").html("<div class=\'ulp-totalsend-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to TotalSend server.', 'ulp').'</strong></div>");
												jQuery(".ulp-totalsend-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-totalsend-fields-html").html("<div class=\'ulp-totalsend-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to TotalSend server.', 'ulp').'</strong></div>");
											jQuery(".ulp-totalsend-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_totalsend_enable"])) $popup_options['totalsend_enable'] = "on";
		else $popup_options['totalsend_enable'] = "off";
		if ($popup_options['totalsend_enable'] == 'on') {
			if (empty($popup_options['totalsend_api_username'])) $errors[] = __('Invalid TotalSend API Username.', 'ulp');
			if (empty($popup_options['totalsend_api_password'])) $errors[] = __('Invalid TotalSend API Password.', 'ulp');
			if (empty($popup_options['totalsend_list_id'])) $errors[] = __('Invalid TotalSend List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_totalsend_enable"])) $popup_options['totalsend_enable'] = "on";
		else $popup_options['totalsend_enable'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_totalsend_field_')) == 'ulp_totalsend_field_') {
				$field = substr($key, strlen('ulp_totalsend_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['totalsend_fields'] = serialize($fields);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['totalsend_enable'] == 'on') {
			$data = array(
				'Command' => 'User.Login',
				'Username' => $popup_options['totalsend_api_username'],
				'Password' => $popup_options['totalsend_api_password']
			);
			$result = $this->connect($data);
			if ($result) {
				if (array_key_exists("Success", $result) && !$result['Success']) return;
				else if (!array_key_exists("SessionID", $result)) return;
				$session_id = $result['SessionID'];
				$data = array(
					'SessionID' => $session_id,
					'Command' => 'Subscriber.Get',
					'ListID' => $popup_options['totalsend_list_id'],
					'EmailAddress' => $_subscriber['{subscription-email}']
				);
				$result = $this->connect($data);
				if (array_key_exists("Success", $result) && $result['Success']) {
					$data = array(
						'SessionID' => $session_id,
						'Command' => 'Subscriber.Update',
						'SubscriberListID' => $popup_options['totalsend_list_id'],
						'EmailAddress' => $_subscriber['{subscription-email}'],
						'SubscriberID' => $result['SubscriberInformation']['SubscriberID']
					);
					$fields = array();
					if (!empty($popup_options['totalsend_fields'])) $fields = unserialize($popup_options['totalsend_fields']);
					if (!empty($fields) && is_array($fields)) {
						foreach ($fields as $key => $value) {
							if (!empty($value)) {
								$data['Fields']['CustomField'.$key] = strtr($value, $_subscriber);
							}
						}
					}
					$result = $this->connect($data);
				} else {
					$data = array(
						'SessionID' => $session_id,
						'Command' => 'Subscriber.Subscribe',
						'ListID' => $popup_options['totalsend_list_id'],
						'EmailAddress' => $_subscriber['{subscription-email}'],
						'IPAddress' => $_SERVER['REMOTE_ADDR']
					);
					$fields = array();
					if (!empty($popup_options['totalsend_fields'])) $fields = unserialize($popup_options['totalsend_fields']);
					if (!empty($fields) && is_array($fields)) {
						foreach ($fields as $key => $value) {
							if (!empty($value)) {
								$data['CustomField'.$key] = strtr($value, $_subscriber);
							}
						}
					}
					$result = $this->connect($data);
				}
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_api_username']) || !isset($_POST['ulp_api_password']) || empty($_POST['ulp_api_username']) || empty($_POST['ulp_api_password'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Username or Password!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$api_username = trim(stripslashes($_POST['ulp_api_username']));
			$api_password = trim(stripslashes($_POST['ulp_api_password']));

			$lists = array();
			$data = array(
				'Command' => 'User.Login',
				'Username' => $api_username,
				'Password' => $api_password
			);
			$result = $this->connect($data);
			if ($result) {
				if (array_key_exists("Success", $result) && !$result['Success']) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.esc_html($result['ErrorText'][0]).'</div>';
					echo json_encode($return_object);
					exit;
				} else if (!array_key_exists("SessionID", $result)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not get TotalSend Session ID!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
				$data = array(
					'SessionID' => $result['SessionID'],
					'Command' => 'Lists.Get',
					'OrderType' => 'ASC'
				);
				$result = $this->connect($data);
				if ($result) {
					if (array_key_exists("Success", $result) && !$result['Success']) {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.esc_html($result['ErrorText'][0]).'</div>';
						echo json_encode($return_object);
						exit;
					} else if (!array_key_exists("Lists", $result) || !is_array($result['Lists']) || sizeof($result['Lists']) == 0) {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No lists found!', 'ulp').'</div>';
						echo json_encode($return_object);
						exit;
					}
					foreach($result['Lists'] as $list) {
						if (is_array($list)) {
							if (array_key_exists('ListID', $list) && array_key_exists('Name', $list)) {
								$lists[$list['ListID']] = $list['Name'];
							}
						}
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to TotalSend server!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to TotalSend server!', 'ulp').'</div>';
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
			if (!isset($_POST['ulp_username']) || !isset($_POST['ulp_password']) || !isset($_POST['ulp_list']) || empty($_POST['ulp_username']) || empty($_POST['ulp_password']) || empty($_POST['ulp_list'])) exit;
			$api_username = trim(stripslashes($_POST['ulp_username']));
			$api_password = trim(stripslashes($_POST['ulp_password']));
			$list = trim(stripslashes($_POST['ulp_list']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($api_username, $api_password, $list, $this->default_popup_options['totalsend_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_api_username, $_api_password, $_list, $_fields) {
		$fields = '';
		$data = array(
			'Command' => 'User.Login',
			'Username' => $_api_username,
			'Password' => $_api_password
		);
		$result = $this->connect($data);
		if ($result) {
			if (array_key_exists("Success", $result) && !$result['Success']) {
				return '<div class="ulp-totalsend-grouping" style="margin-bottom: 10px;"><strong>'.esc_html($result['ErrorText'][0]).'</strong></div>';
			} else if (!array_key_exists("SessionID", $result)) {
				return '<div class="ulp-totalsend-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not get TotalSend Session ID!', 'ulp').'</strong></div>';
			}
			$data = array(
				'SessionID' => $result['SessionID'],
				'Command' => 'CustomFields.Get',
				'SubscriberListID' => $_list
			);
			$result = $this->connect($data);
			if ($result) {
				if (array_key_exists("Success", $result) && !$result['Success']) {
					return '<div class="ulp-totalsend-grouping" style="margin-bottom: 10px;"><strong>'.esc_html($result['ErrorText'][0]).'</strong></div>';
				} else if (!array_key_exists("CustomFields", $result) || !is_array($result['CustomFields']) || sizeof($result['CustomFields']) == 0) {
					return '<div class="ulp-totalsend-grouping" style="margin-bottom: 10px;"><strong>'.__('No custom fields found!', 'ulp').'</strong></div>';
				}
				$values = unserialize($_fields);
				if (!is_array($values)) $values = array();
				$fields = '
				'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate TotalSend fields with the popup fields.', 'ulp').'
				<table style="min-width: 280px; width: 50%;">';
				foreach ($result['CustomFields'] as $field) {
					if (is_array($field)) {
						if (array_key_exists('CustomFieldID', $field) && array_key_exists('FieldName', $field)) {
							$fields .= '
					<tr>
						<td style="width: 100px;"><strong>'.esc_html($field['FieldName']).':</strong></td>
						<td>
							<input type="text" id="ulp_totalsend_field_'.esc_html($field['CustomFieldID']).'" name="ulp_totalsend_field_'.esc_html($field['CustomFieldID']).'" value="'.esc_html(array_key_exists($field['CustomFieldID'], $values) ? $values[$field['CustomFieldID']] : '').'" class="widefat" />
							<br /><em>'.esc_html($field['FieldName']).'</em>
						</td>
					</tr>';
						}
					}
				}
				$fields .= '
				</table>';
			} else {
				return '<div class="ulp-totalsend-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not connect to TotalSend server!', 'ulp').'</strong></div>';
			}
		} else {
			return '<div class="ulp-totalsend-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not connect to TotalSend server!', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_data) {
		try {
			$_data['ResponseFormat'] = 'JSON';
			$totalsend_url = 'http://app.totalsend.com/api.php';
			$curl = curl_init($totalsend_url);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
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
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
	
}
$ulp_totalsend = new ulp_totalsend_class();
?>