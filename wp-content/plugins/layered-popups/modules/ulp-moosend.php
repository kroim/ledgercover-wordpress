<?php
/* Moosend integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_moosend_class {
	var $default_popup_options = array(
		"moosend_enable" => "off",
		"moosend_api_key" => "",
		"moosend_list" => "",
		"moosend_list_id" => "",
		"moosend_name" => "{subscription-name}",
		"moosend_fields" => ""
	);
	function __construct() {
		$this->default_popup_options['moosend_fields'] = serialize(array('EMAIL' => '{subscription-email}', 'FNAME' => '{subscription-name}', 'NAME' => '{subscription-name}'));
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-moosend-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-moosend-fields', array(&$this, "show_fields"));
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
				<h3>'.__('Moosend Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Moosend', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_moosend_enable" name="ulp_moosend_enable" '.($popup_options['moosend_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Moosend', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Moosend.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Moosend API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_moosend_api_key" name="ulp_moosend_api_key" value="'.esc_html($popup_options['moosend_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Moosend API Key. You can get your API key from the settings page in your account.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-moosend-list" name="ulp_moosend_list" value="'.esc_html($popup_options['moosend_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_moosend_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-moosend-list-id" name="ulp_moosend_list_id" value="'.esc_html($popup_options['moosend_list_id']).'" />
							<div id="ulp-moosend-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Select desired List.', 'ulp').'</em>
							<script>
								function ulp_moosend_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-moosend-lists", "ulp_api_key": jQuery("#ulp_moosend_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-moosend-fields-html">';
		if (!empty($popup_options['moosend_api_key']) && !empty($popup_options['moosend_list_id'])) {
			$fields = $this->get_fields_html($popup_options['moosend_api_key'], $popup_options['moosend_list_id'], $popup_options['moosend_name'], $popup_options['moosend_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_moosend_fields_button" class="ulp_button button-secondary" onclick="return ulp_moosend_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-moosend-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_moosend_loadfields() {
									jQuery("#ulp-moosend-fields-loading").fadeIn(350);
									jQuery(".ulp-moosend-fields-html").slideUp(350);
									var data = {action: "ulp-moosend-fields", ulp_key: jQuery("#ulp_moosend_api_key").val(), ulp_list: jQuery("#ulp-moosend-list-id").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-moosend-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-moosend-fields-html").html(data.html);
												jQuery(".ulp-moosend-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-moosend-fields-html").html("<div class=\'ulp-moosend-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Moosend server.', 'ulp').'</strong></div>");
												jQuery(".ulp-moosend-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-moosend-fields-html").html("<div class=\'ulp-moosend-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Moosend server.', 'ulp').'</strong></div>");
											jQuery(".ulp-moosend-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_moosend_enable"])) $popup_options['moosend_enable'] = "on";
		else $popup_options['moosend_enable'] = "off";
		if ($popup_options['moosend_enable'] == 'on') {
			if (empty($popup_options['moosend_api_key'])) $errors[] = __('Invalid Moosend API Key.', 'ulp');
			if (empty($popup_options['moosend_list_id'])) $errors[] = __('Invalid Moosend List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_moosend_double"])) $popup_options['moosend_double'] = "on";
		else $popup_options['moosend_double'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_moosend_field_')) == 'ulp_moosend_field_') {
				$field = substr($key, strlen('ulp_moosend_field_'));
				$fields[$field] = array(
					'value' => stripslashes(trim($value)),
					'name' => stripslashes(trim($ulp->postdata['ulp_moosend_fieldname_'.$field]))
				);
			}
		}
		$popup_options['moosend_fields'] = $fields;
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['moosend_enable'] == 'on') {
			$data = array(
				'Email' => $_subscriber['{subscription-email}'],
				'Name' => strtr($popup_options['moosend_name'], $_subscriber)
			);
			foreach ($popup_options['moosend_fields'] as $key => $field) {
				if (!empty($field['value'])) {
					$data['CustomFields'][] = $field['name'].'='.strtr($field['value'], $_subscriber);
				}
			}
			$result = $this->connect($popup_options['moosend_api_key'], 'subscribers/'.urlencode($popup_options['moosend_list_id']).'/view.json?Email='.urlencode($_subscriber['{subscription-email}']));
			if (is_array($result) && array_key_exists('Context', $result) && is_array($result['Context']) && array_key_exists('ID', $result['Context'])) {
				$result = $this->connect($popup_options['moosend_api_key'], 'subscribers/'.urlencode($popup_options['moosend_list_id']).'/update/'.urlencode($result['Context']['ID']).'.json', $data);
			} else {
				$result = $this->connect($popup_options['moosend_api_key'], 'subscribers/'.urlencode($popup_options['moosend_list_id']).'/subscribe.json', $data);
			}
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
			$result = $this->connect($key, 'lists/1/100.json');
			if (is_array($result) && array_key_exists('Error', $result) && is_null($result['Error']) && array_key_exists('Context', $result)) {
				if (is_array($result['Context']) && array_key_exists('MailingLists', $result['Context']) && sizeof($result['Context']['MailingLists'])) {
					foreach ($result['Context']['MailingLists'] as $list) {
						if (is_array($list)) {
							if (array_key_exists('ID', $list) && array_key_exists('Name', $list)) {
								$lists[$list['ID']] = $list['Name'];
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
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$list_html = '';
			if (!empty($lists)) {
				foreach ($lists as $id => $name) {
					$list_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($name).'" onclick="return ulp_input_options_selected(this);">'.esc_html($name).'</a>';
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
				$return_object['html'] = '<div class="ulp-moosend-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Key or List ID.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_key']));
			$list = trim(stripslashes($_POST['ulp_list']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($key, $list, $this->default_popup_options['moosend_name'], $this->default_popup_options['moosend_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_key, $_list, $_name, $_fields) {
		$result = $this->connect($_key, 'lists/'.urlencode($_list).'/details.json');
		$fields = '';
		if (is_array($result) && array_key_exists('Error', $result) && is_null($result['Error']) && array_key_exists('Context', $result)) {
			$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Moosend fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">
				<tr>
					<td style="width: 100px;"><strong>'.__('Email', 'ulp').':</strong></td>
					<td>
						<input type="text" readonly="readonly" value="{subscription-email}" class="widefat" />
						<br /><em>'.__('Email of the contact.', 'ulp').'</em>
					</td>
				</tr>
				<tr>
					<td style="width: 100px;"><strong>'.__('Name', 'ulp').':</strong></td>
					<td>
						<input type="text" id="ulp_moosend_name" name="ulp_moosend_name" value="'.esc_html($_name).'" class="widefat" />
						<br /><em>'.__('Name of the contact.', 'ulp').'</em>
					</td>
				</tr>';
			if (array_key_exists('CustomFieldsDefinition', $result['Context'])) {
				foreach ($result['Context']['CustomFieldsDefinition'] as $field) {
					if (is_array($field)) {
						if (array_key_exists('ID', $field) && array_key_exists('Name', $field)) {
							$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['Name']).':</strong></td>
					<td>
						<input type="text" id="ulp_moosend_field_'.esc_html($field['ID']).'" name="ulp_moosend_field_'.esc_html($field['ID']).'" value="'.esc_html(array_key_exists($field['ID'], $_fields) ? $_fields[$field['ID']]['value'] : '').'" class="widefat" />
						<input type="hidden" id="ulp_moosend_fieldname_'.esc_html($field['ID']).'" name="ulp_moosend_fieldname_'.esc_html($field['ID']).'" value="'.esc_html($field['Name']).'" />
						<br /><em>'.esc_html($field['Name']).' ('.$field['ID'].')</em>
					</td>
				</tr>';
						}
					}
				}
			}
			$fields .= '
			</table>';
		} else {
			$fields = '<div class="ulp-moosend-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Key (or server response)!', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_api_key, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		try {
			$url = 'https://api.moosend.com/v3/'.ltrim($_path, '/');
			if (strpos($url, '?') === false) $url .= '?apikey='.urlencode($_api_key);
			else $url .= '&apikey='.urlencode($_api_key);
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
$ulp_moosend = new ulp_moosend_class();
?>