<?php
/* Pipedrive integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_pipedrive_class {
	var $default_popup_options = array(
		"pipedrive_enable" => "off",
		"pipedrive_api_key" => "",
		"pipedrive_list" => "",
		"pipedrive_list_id" => "",
		"pipedrive_name" => "{subscription-name}",
		"pipedrive_email" => "{subscription-email}",
		"pipedrive_phone" => "{subscription-phone}",
		"pipedrive_fields" => "",
		"pipedrive_visible" => "1"
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-pipedrive-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-pipedrive-fields', array(&$this, "show_fields"));
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
				<h3>'.__('Pipedrive Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Pipedrive', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_pipedrive_enable" name="ulp_pipedrive_enable" '.($popup_options['pipedrive_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Pipedrive', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Pipedrive.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Token', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_pipedrive_api_key" name="ulp_pipedrive_api_key" value="'.esc_html($popup_options['pipedrive_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Pipedrive API Token. You can find it in your account on Settings page.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Organization ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-pipedrive-list" name="ulp_pipedrive_list" value="'.esc_html($popup_options['pipedrive_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_pipedrive_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-pipedrive-list-id" name="ulp_pipedrive_list_id" value="'.esc_html($popup_options['pipedrive_list_id']).'" />
							<div id="ulp-pipedrive-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter the ID of the organization the contact will belong to.', 'ulp').'</em>
							<script>
								function ulp_pipedrive_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-pipedrive-lists", "ulp_api_key": jQuery("#ulp_pipedrive_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Pipedrive fields with the popup fields.', 'ulp').'
							<table style="min-width: 280px; width: 50%;">
								<tr>
									<td style="width: 100px;"><strong>'.__('Name', 'ulp').':</strong></td>
									<td>
										<input type="text" id="ulp_pipedrive_name" name="ulp_pipedrive_name" value="'.esc_html($popup_options['pipedrive_name']).'" class="widefat" />
										<br /><em>'.__('Name of the contact.', 'ulp').'</em>
									</td>
								</tr>
								<tr>
									<td><strong>'.__('E-mail', 'ulp').':</strong></td>
									<td>
										<input type="text" id="ulp_pipedrive_email" name="ulp_pipedrive_email" value="'.esc_html($popup_options['pipedrive_email']).'" class="widefat" />
										<br /><em>'.__('Email address associated with the contact.', 'ulp').'</em>
									</td>
								</tr>
								<tr>
									<td><strong>'.__('Phone', 'ulp').':</strong></td>
									<td>
										<input type="text" id="ulp_pipedrive_phone" name="ulp_pipedrive_phone" value="'.esc_html($popup_options['pipedrive_phone']).'" class="widefat" />
										<br /><em>'.__('Phone number associated with the contact.', 'ulp').'</em>
									</td>
								</tr>
							</table>
							<div class="ulp-pipedrive-fields-html">';
		if (!empty($popup_options['pipedrive_api_key'])) {
			$fields = $this->get_fields_html($popup_options['pipedrive_api_key'], $popup_options['pipedrive_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_pipedrive_fields_button" class="ulp_button button-secondary" onclick="return ulp_pipedrive_loadfields();">'.__('Load Custom Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-pipedrive-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load custom fields list. Ignore, if you do not need specify custom fields values.', 'ulp').'</em>
							<script>
								function ulp_pipedrive_loadfields() {
									jQuery("#ulp-pipedrive-fields-loading").fadeIn(350);
									jQuery(".ulp-pipedrive-fields-html").slideUp(350);
									var data = {action: "ulp-pipedrive-fields", ulp_key: jQuery("#ulp_pipedrive_api_key").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-pipedrive-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-pipedrive-fields-html").html(data.html);
												jQuery(".ulp-pipedrive-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-pipedrive-fields-html").html("<div class=\'ulp-pipedrive-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Pipedrive server.', 'ulp').'</strong></div>");
												jQuery(".ulp-pipedrive-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-pipedrive-fields-html").html("<div class=\'ulp-pipedrive-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Pipedrive server.', 'ulp').'</strong></div>");
											jQuery(".ulp-pipedrive-fields-html").slideDown(350);
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Visibility', 'ulp').':</th>
						<td>
							<select id="ulp_pipedrive_visible" name="ulp_pipedrive_visible">
								<option value="1"'.($popup_options['pipedrive_visible'] == 1 ? ' selected="selected"' : '').'>'.__('Owner & followers (private)', 'ulp').'</option>
								<option value="3"'.($popup_options['pipedrive_visible'] != 1 ? ' selected="selected"' : '').'>'.__('Entire company (shared)', 'ulp').'</option>
							</select>
							<br /><em>'.__('Visibility of the contact.', 'ulp').'</em>
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
		if (isset($ulp->postdata["ulp_pipedrive_enable"])) $popup_options['pipedrive_enable'] = "on";
		else $popup_options['pipedrive_enable'] = "off";
		if ($popup_options['pipedrive_enable'] == 'on') {
			if (empty($popup_options['pipedrive_api_key'])) $errors[] = __('Invalid Pipedrive API Token.', 'ulp');
			if (empty($popup_options['pipedrive_name'])) $errors[] = __('Pipedrive Name field can not be empty.', 'ulp');
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

		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_pipedrive_field_')) == 'ulp_pipedrive_field_') {
				$field = substr($key, strlen('ulp_pipedrive_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['pipedrive_fields'] = serialize($fields);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-name}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['pipedrive_enable'] == 'on') {
			$data = array(
				'name' => strtr($popup_options['pipedrive_name'], $_subscriber),
				'email' => strtr($popup_options['pipedrive_email'], $_subscriber),
				'phone' => strtr($popup_options['pipedrive_phone'], $_subscriber),
				'org_id' => $popup_options['pipedrive_list_id'],
				'visible_to' => $popup_options['pipedrive_visible']
			);
			$fields = array();
			if (!empty($popup_options['pipedrive_fields'])) $fields = unserialize($popup_options['pipedrive_fields']);
			if (!empty($fields) && is_array($fields)) {
				foreach ($fields as $key => $value) {
					if (!empty($value)) {
						$data[$key] = strtr($value, $_subscriber);
					}
				}
			}
			$method = 'POST';
			$person_id = null;
			if (!empty($_subscriber['{subscription-email}'])) {
				$result = $this->connect($popup_options['pipedrive_api_key'], 'persons/find?term='.urlencode($_subscriber['{subscription-email}']).'&start=0&limit=1'.(!empty($popup_options['pipedrive_list_id']) ? '&org_id='.$popup_options['pipedrive_list_id'] : '').'&search_by_email=1');
				if (array_key_exists('success', $result) && $result['success']) {
					if (!empty($result['data'])) {
						$person_id = $result['data'][0]['id'];
						$method = 'PUT';
					}
				}
			}
			$result = $this->connect($popup_options['pipedrive_api_key'], 'persons'.(!empty($person_id) ? '/'.$person_id : ''), $data, $method);
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_api_key']) || empty($_POST['ulp_api_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Token!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_api_key']));
			
			$result = $this->connect($key, 'organizations?start=0&limit=1000');
			
			if (is_array($result) && array_key_exists('success', $result)) {
				if ($result['success']) {
					if (!empty($result['data']) && is_array($result['data'])) {
						foreach ($result['data'] as $list) {
							if (is_array($list)) {
								if (array_key_exists('id', $list) && array_key_exists('name', $list)) {
									$lists[$list['id']] = $list['name'];
								}
							}
						}
					} else {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No organizations found!', 'ulp').'</div>';
						echo json_encode($return_object);
						exit;
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.(array_key_exists('error', $result) ? esc_html($result['error']) : __('Invalid server response!', 'ulp')).'</div>';
					echo json_encode($return_object);
					exit;
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
				$list_html .= '<a href="#" data-id="" data-title="None" onclick="return ulp_input_options_selected(this);">None</a>';
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
			if (!isset($_POST['ulp_key']) || empty($_POST['ulp_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-pipedrive-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Token.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_key']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($key, $this->default_popup_options['pipedrive_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_key, $_fields) {
		$result = $this->connect($_key, 'personFields');
		$fields = '';
		$values = unserialize($_fields);
		if (!is_array($values)) $values = array();
		if (is_array($result) && array_key_exists('success', $result)) {
			if ($result['success']) {
				if (!empty($result['data']) && is_array($result['data'])) {			
					$fields = '
			<table style="min-width: 280px; width: 50%;">';
					$found = false;
					foreach ($result['data'] as $field) {
						if (is_array($field)) {
							if (array_key_exists('key', $field) && array_key_exists('name', $field) && $field['edit_flag']) {
								$found = true;
								$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['name']).':</strong></td>
					<td>
						<input type="text" id="ulp_pipedrive_field_'.esc_html($field['key']).'" name="ulp_pipedrive_field_'.esc_html($field['key']).'" value="'.esc_html(array_key_exists($field['key'], $values) ? $values[$field['key']] : '').'" class="widefat" />
						<br /><em>'.esc_html($field['name']).'</em>
					</td>
				</tr>';
							}
						}
					}
					$fields .= '
			</table>';
					if (!$found) $fields = '<div class="ulp-pipedrive-grouping" style="margin-bottom: 10px;"><strong>'.__('No custom fields found.', 'ulp').'</strong></div>';
				} else {
					$fields = '<div class="ulp-pipedrive-grouping" style="margin-bottom: 10px;"><strong>'.__('No custom fields found.', 'ulp').'</strong></div>';
				}
			} else {
				$fields = '<div class="ulp-pipedrive-grouping" style="margin-bottom: 10px;"><strong>'.(array_key_exists('error', $result) ? esc_html($result['error']) : __('Invalid server response!', 'ulp')).'</strong></div>';
			}
		} else {
			$fields = '<div class="ulp-pipedrive-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_api_key, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		try {
			$url = 'https://api.pipedrive.com/v1/'.ltrim($_path, '/');
			if (strpos($_path, '?') !== false) $url .= '&api_token='.$_api_key;
			else $url .= '?api_token='.$_api_key;
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
$ulp_pipedrive = new ulp_pipedrive_class();
?>