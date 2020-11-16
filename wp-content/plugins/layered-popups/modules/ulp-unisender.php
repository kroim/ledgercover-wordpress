<?php
/* UniSender integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_unisender_class {
	var $default_popup_options = array(
		"unisender_enable" => "off",
		"unisender_api_key" => "",
		"unisender_list" => "",
		"unisender_list_id" => "",
		"unisender_field_values" => array(),
		"unisender_field_names" => array(),
		"unisender_tags" => "",
		"unisender_double" => "off"
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-unisender-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-unisender-fields', array(&$this, "show_fields"));
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
				<h3>'.__('UniSender Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable UniSender', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_unisender_enable" name="ulp_unisender_enable" '.($popup_options['unisender_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to UniSender', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to UniSender.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('UniSender API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_unisender_api_key" name="ulp_unisender_api_key" value="'.esc_html($popup_options['unisender_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your UniSender API Key. You can get it <a href="https://cp.unisender.com/en/v5/user/info/api" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-unisender-list" name="ulp_unisender_list" value="'.esc_html($popup_options['unisender_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_unisender_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-unisender-list-id" name="ulp_unisender_list_id" value="'.esc_html($popup_options['unisender_list_id']).'" />
							<div id="ulp-unisender-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_unisender_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-unisender-lists", "ulp_api_key": jQuery("#ulp_unisender_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate UniSender fields with the popup fields.', 'ulp').'
							<table style="min-width: 280px; width: 50%;">
								<tr>
									<td style="width: 100px;"><strong>'.__('Email', 'ulp').':</strong></td>
									<td>
										<input type="text" class="widefat" readonly="readonly" value="{subscription-email}" />
										<br /><em>'.__('Email address of the contact.', 'ulp').'</em>
									</td>
								</tr>
								<tr>
									<td style="width: 100px;"><strong>'.__('Phone #', 'ulp').':</strong></td>
									<td>
										<input type="text" class="widefat" readonly="readonly" value="{subscription-phone}" />
										<br /><em>'.__('Phone number of the contact.', 'ulp').'</em>
									</td>
								</tr>
							</table>
							<div class="ulp-unisender-fields-html">';
		if (!empty($popup_options['unisender_api_key']) && !empty($popup_options['unisender_list_id'])) {
			$fields = $this->get_fields_html($popup_options['unisender_api_key'], $popup_options['unisender_field_values']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_unisender_fields_button" class="ulp_button button-secondary" onclick="return ulp_unisender_loadfields();">'.__('Load Custom Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-unisender-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load custom fields list. Ignore if you do not need specify custom fields values.', 'ulp').'</em>
							<script>
								function ulp_unisender_loadfields() {
									jQuery("#ulp-unisender-fields-loading").fadeIn(350);
									jQuery(".ulp-unisender-fields-html").slideUp(350);
									var data = {action: "ulp-unisender-fields", ulp_key: jQuery("#ulp_unisender_api_key").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-unisender-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-unisender-fields-html").html(data.html);
												jQuery(".ulp-unisender-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-unisender-fields-html").html("<div class=\'ulp-unisender-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to UniSender server.', 'ulp').'</strong></div>");
												jQuery(".ulp-unisender-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-unisender-fields-html").html("<div class=\'ulp-unisender-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to UniSender server.', 'ulp').'</strong></div>");
											jQuery(".ulp-unisender-fields-html").slideDown(350);
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Tags', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_unisender_tags" name="ulp_unisender_tags" value="'.esc_html($popup_options['unisender_tags']).'" class="widefat">
							<br /><em>'.__('If you want to tag contact with tags, drop them here (comma-separated string).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Double opt-in', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_unisender_double" name="ulp_unisender_double" '.($popup_options['unisender_double'] == "on" ? 'checked="checked"' : '').'"> '.__('Ask users to confirm their subscription', 'ulp').'
							<br /><em>'.__('Control whether a double opt-in confirmation message is sent.', 'ulp').'</em>
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
		if (isset($ulp->postdata["ulp_unisender_enable"])) $popup_options['unisender_enable'] = "on";
		else $popup_options['unisender_enable'] = "off";
		if ($popup_options['unisender_enable'] == 'on') {
			if (empty($popup_options['unisender_api_key'])) $errors[] = __('Invalid UniSender API Key.', 'ulp');
			if (empty($popup_options['unisender_list_id'])) $errors[] = __('Invalid UniSender List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_unisender_double"])) $popup_options['unisender_double'] = "on";
		else $popup_options['unisender_double'] = "off";
		if (isset($ulp->postdata["ulp_unisender_enable"])) $popup_options['unisender_enable'] = "on";
		else $popup_options['unisender_enable'] = "off";
		
		$field_values = array();
		$field_names = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_unisender_fieldvalue_')) == 'ulp_unisender_fieldvalue_') {
				$field = substr($key, strlen('ulp_unisender_fieldvalue_'));
				$field_values[$field] = stripslashes(trim($value));
			} else if (substr($key, 0, strlen('ulp_unisender_fieldname_')) == 'ulp_unisender_fieldname_') {
				$field = substr($key, strlen('ulp_unisender_fieldname_'));
				$field_names[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['unisender_field_values'] = $field_values;
		$popup_options['unisender_field_names'] = $field_names;

		$tags = explode(',', $ulp->postdata['ulp_unisender_tags']);
		foreach($tags as $key => $value) {
			$tags[$key] = trim($value);
			if (empty($tags[$key])) unset($tags[$key]);
		}
		$popup_options['unisender_tags'] = implode(', ', $tags);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}']) && empty($_subscriber['{subscription-phone}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['unisender_enable'] == 'on') {
			$data = array(
				'list_ids' => $popup_options['unisender_list_id'],
				'request_ip' => $_SERVER['REMOTE_ADDR'],
				'overwrite' => 2,
				'double_optin' => $popup_options['unisender_double'] == 'on' ? 0 : 3
			);
			foreach ($popup_options['unisender_field_values'] as $key => $value) {
				if (!empty($value)) {
					$data['fields['.$popup_options['unisender_field_names'][$key].']'] = strtr($value, $_subscriber);
				}
			}
			if (!empty($_subscriber['{subscription-email}'])) $data['fields[email]'] = $_subscriber['{subscription-email}'];
			if (!empty($_subscriber['{subscription-phone}'])) $data['fields[phone]'] = $_subscriber['{subscription-phone}'];
			if (!empty($popup_options['unisender_tags'])) $data['tags'] = str_replace(' ', '', $popup_options['unisender_tags']);
			$result = $this->connect($popup_options['unisender_api_key'], 'subscribe', $data);
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_api_key']) || empty($_POST['ulp_api_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_api_key']));
			
			$result = $this->connect($key, 'getLists');
			if (is_array($result)) {
				if (array_key_exists('error', $result)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.$result['error'].'</div>';
					echo json_encode($return_object);
					exit;
				} else if (array_key_exists('result', $result) && is_array($result['result'])) {
					if (sizeof($result['result']) > 0) {
						foreach ($result['result'] as $list) {
							if (is_array($list)) {
								if (array_key_exists('id', $list) && array_key_exists('title', $list)) {
									$lists[$list['id']] = $list['title'];
								}
							}
						}
					} else {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No Lists found!', 'ulp').'</div>';
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
			if (!isset($_POST['ulp_key']) || empty($_POST['ulp_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-unisender-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Key.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_key']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($key, $this->default_popup_options['unisender_field_values']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_key, $_fields) {
		$result = $this->connect($_key, 'getFields');
		if (!empty($result) && is_array($result)) {
			if (array_key_exists('error', $result)) {
				$fields = '<div class="ulp-unisender-grouping" style="margin-bottom: 10px;"><strong>'.$result['error'].'</strong></div>';
			} else if (array_key_exists('result', $result) && is_array($result['result'])) {
				if (sizeof($result['result']) > 0) {
					$fields = '
			<table style="min-width: 280px; width: 50%;">';
					foreach ($result['result'] as $field) {
						if (is_array($field)) {
							if (array_key_exists('id', $field) && array_key_exists('name', $field)) {
								$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['name']).':</strong></td>
					<td>
						<input type="text" id="ulp_unisender_fieldvalue_'.esc_html($field['id']).'" name="ulp_unisender_fieldvalue_'.esc_html($field['id']).'" value="'.esc_html(array_key_exists($field['id'], $_fields) ? $_fields[$field['id']] : '').'" class="widefat" />
						<input type="hidden" id="ulp_unisender_fieldname_'.esc_html($field['id']).'" name="ulp_unisender_fieldname_'.esc_html($field['id']).'" value="'.esc_html($field['name']).'" />
						<br /><em>'.(array_key_exists('public_name', $field) && !empty($field['public_name']) ? esc_html($field['public_name']) :  esc_html($field['name'])).'</em>
					</td>
				</tr>';
							}
						}
					}
					$fields .= '
			</table>';
				} else {
					$fields = '<div class="ulp-unisender-grouping" style="margin-bottom: 10px;"><strong>'.__('No custom fields found.', 'ulp').'</strong></div>';
				}
			} else {
				$fields = '<div class="ulp-unisender-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
			}
		} else {
			$fields = '<div class="ulp-unisender-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_api_key, $_path, $_data = array()) {
		try {
			$url = 'https://api.unisender.com/en/api/'.ltrim($_path, '/').'?format=json&api_key='.urlencode($_api_key);
			if (is_array($_data)) {
				foreach ($_data as $key => $value) {
					$url .= '&'.urlencode($key).'='.urlencode($value);
				}
			}
			$curl = curl_init($url);
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
$ulp_unisender = new ulp_unisender_class();
?>