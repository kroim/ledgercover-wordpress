<?php
/* Vision6 integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_vision6_class {
	var $default_popup_options = array(
		"vision6_enable" => "off",
		"vision6_url" => "https://app.vision6.com/api/jsonrpcserver?version=3.0",
		"vision6_api_key" => "",
		"vision6_list" => "",
		"vision6_list_id" => "",
		"vision6_fields" => array()
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-vision6-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-vision6-fields', array(&$this, "show_fields"));
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
				<h3>'.__('Vision6 Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Vision6', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_vision6_enable" name="ulp_vision6_enable" '.($popup_options['vision6_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Vision6', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Vision6.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API URL', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_vision6_url" name="ulp_vision6_url" value="'.esc_html($popup_options['vision6_url']).'" class="widefat">
							<br /><em>'.__('Enter your Vision6 API URL. Leave it default if you do not have personal API URL.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_vision6_api_key" name="ulp_vision6_api_key" value="'.esc_html($popup_options['vision6_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Vision6 API Key. You can get it <a href="https://app.vision6.com/integration/api_keys/" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-vision6-list" name="ulp_vision6_list" value="'.esc_html($popup_options['vision6_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_vision6_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-vision6-list-id" name="ulp_vision6_list_id" value="'.esc_html($popup_options['vision6_list_id']).'" />
							<div id="ulp-vision6-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_vision6_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-vision6-lists", "ulp_url": jQuery("#ulp_vision6_url").val(), "ulp_api_key": jQuery("#ulp_vision6_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-vision6-fields-html">';
		if (!empty($popup_options['vision6_api_key']) && !empty($popup_options['vision6_list_id'])) {
			$fields = $this->get_fields_html($popup_options['vision6_url'], $popup_options['vision6_api_key'], $popup_options['vision6_list_id'], $popup_options['vision6_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_vision6_fields_button" class="ulp_button button-secondary" onclick="return ulp_vision6_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-vision6-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_vision6_loadfields() {
									jQuery("#ulp-vision6-fields-loading").fadeIn(350);
									jQuery(".ulp-vision6-fields-html").slideUp(350);
									var data = {action: "ulp-vision6-fields", ulp_key: jQuery("#ulp_vision6_api_key").val(), ulp_list: jQuery("#ulp-vision6-list-id").val(), ulp_url: jQuery("#ulp_vision6_url").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-vision6-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-vision6-fields-html").html(data.html);
												jQuery(".ulp-vision6-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-vision6-fields-html").html("<div class=\'ulp-vision6-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Vision6 server.', 'ulp').'</strong></div>");
												jQuery(".ulp-vision6-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-vision6-fields-html").html("<div class=\'ulp-vision6-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Vision6 server.', 'ulp').'</strong></div>");
											jQuery(".ulp-vision6-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_vision6_enable"])) $popup_options['vision6_enable'] = "on";
		else $popup_options['vision6_enable'] = "off";
		if ($popup_options['vision6_enable'] == 'on') {
			if (empty($popup_options['vision6_url']) || !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $popup_options['vision6_url'])) $errors[] = __('Invalid Vision6 API URL', 'ulp');
			if (empty($popup_options['vision6_api_key'])) $errors[] = __('Invalid Vision6 API Key.', 'ulp');
			if (empty($popup_options['vision6_list_id'])) $errors[] = __('Invalid Vision6 List ID.', 'ulp');
			$fields_found = false;
			foreach($ulp->postdata as $key => $value) {
				if (substr($key, 0, strlen('ulp_vision6_field_')) == 'ulp_vision6_field_') {
					$fields_found = true;
					break;
				}
			}
			if (!$fields_found) $errors[] = __('Configure Vision6 fields.', 'ulp');
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
		if (isset($ulp->postdata["ulp_vision6_enable"])) $popup_options['vision6_enable'] = "on";
		else $popup_options['vision6_enable'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_vision6_field_')) == 'ulp_vision6_field_') {
				$field = substr($key, strlen('ulp_vision6_field_'));
				$fields[$field] = array('name' => stripslashes(trim($ulp->postdata['ulp_vision6_fieldname_'.$field])), 'value' => stripslashes(trim($value)));
			}
		}
		$popup_options['vision6_fields'] = $fields;
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['vision6_enable'] == 'on') {

			$contact_details = array();
			if (!empty($popup_options['vision6_fields']) && is_array($popup_options['vision6_fields'])) {
				foreach ($popup_options['vision6_fields'] as $key => $field) {
					if (!empty($field['value'])) {
						$contact_details[$field['name']] = strtr($field['value'], $_subscriber);
					}
				}
			}
			if (!empty($contact_details)) {
				$data = array(
					'id' => 1,
					'method' => 'addContacts',
					'params' => array(
						$popup_options['vision6_api_key'],
						$popup_options['vision6_list_id'],
						array($contact_details),
						true,
						0
					)
				);
				$result = $this->connect($popup_options['vision6_url'], $data);
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_api_key']) || empty($_POST['ulp_api_key']) || !isset($_POST['ulp_url']) || empty($_POST['ulp_url'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API URL or Key!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_api_key']));
			$url = trim(stripslashes($_POST['ulp_url']));
			
			if (!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url)) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API URL!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			
			$data = array(
				'id' => 1,
				'method' => 'searchLists',
				'params' => array(
					$key,
					array(),
					0,
					0,
					'name',
					'ASC'
				)
			);
			
			$result = $this->connect($url, $data);
			if (is_array($result)) {
				if (empty($result['error'])) {
					if (!empty($result['result'])) {
						foreach ($result['result'] as $list) {
							if (is_array($list)) {
								if (array_key_exists('id', $list) && array_key_exists('name', $list)) {
									$lists[$list['id']] = $list['name'];
								}
							}
						}
					} else {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No lists found.', 'ulp').'</div>';
						echo json_encode($return_object);
						exit;
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.(!empty($result['error']['message']) ? $result['error']['message'] : __('Invalid server response.', 'ulp')).'</div>';
					echo json_encode($return_object);
					exit;
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response.', 'ulp').'</div>';
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
			if (!isset($_POST['ulp_key']) || !isset($_POST['ulp_list']) || empty($_POST['ulp_key']) || empty($_POST['ulp_list']) || !isset($_POST['ulp_url']) || empty($_POST['ulp_url'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-vision6-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API URL, Key or List ID.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_key']));
			$list = trim(stripslashes($_POST['ulp_list']));
			$url = trim(stripslashes($_POST['ulp_url']));
			
			if (!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url)) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-vision6-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API URL.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($url, $key, $list, $this->default_popup_options['vision6_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_url, $_key, $_list, $_fields) {
		$data = array(
			'id' => 1,
			'method' => 'searchFields',
			'params' => array(
				$_key,
				$_list,
				array()
			)
		);
		$result = $this->connect($_url, $data);
		$fields = '';
		$values = $_fields;
		if (!is_array($values)) $values = array();
		if (!empty($result)) {
			if (!empty($result['error'])) {
				$fields = '<div class="ulp-vision6-grouping" style="margin-bottom: 10px;"><strong>'.(!empty($result['error']['message']) ? $result['error']['message'] : __('Invalid server response.', 'ulp')).'</strong></div>';
			} else {
				$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Vision6 fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
				foreach ($result['result'] as $field) {
					if (is_array($field)) {
						if (array_key_exists('id', $field) && array_key_exists('name', $field)) {
							$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['name']).':</strong></td>
					<td>
						<input type="text" id="ulp_vision6_field_'.esc_html($field['id']).'" name="ulp_vision6_field_'.esc_html($field['id']).'" class="widefat"'.($field['address_type'] == 'email' ? ' value="{subscription-email}" readonly="readonly"' : ' value="'.esc_html(array_key_exists($field['id'], $values) ? $values[$field['id']]['value'] : '').'"').' />
						<input type="hidden" id="ulp_vision6_fieldname_'.esc_html($field['id']).'" name="ulp_vision6_fieldname_'.esc_html($field['id']).'" value="'.esc_html($field['name']).'" class="widefat" />
						<br /><em>'.esc_html($field['name']).'</em>
					</td>
				</tr>';
						}
					}
				}
				$fields .= '
			</table>';
			}
		} else {
			$fields = '<div class="ulp-vision6-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_url, $_data = array()) {
		$headers = array(
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		try {
			$curl = curl_init($_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
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
$ulp_vision6 = new ulp_vision6_class();
?>