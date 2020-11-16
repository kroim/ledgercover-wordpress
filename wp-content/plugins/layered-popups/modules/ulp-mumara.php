<?php
/* Mumara integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_mumara_class {
	var $default_popup_options = array(
		'mumara_enable' => 'off',
		'mumara_api_url' => '',
		'mumara_api_token' => '',
		'mumara_list' => '',
		'mumara_list_id' => '',
		'mumara_fields' => array(
			'email' => '{subscription-email}',
			'first_name' => '{subscription-name}'
		)
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
			add_action('wp_ajax_ulp-mumara-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-mumara-fields', array(&$this, "show_fields"));
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
				<h3>'.__('Mumara Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Mumara', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mumara_enable" name="ulp_mumara_enable" '.($popup_options['mumara_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Mumara', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Mumara.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('URL', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mumara_api_url" name="ulp_mumara_api_url" value="'.esc_html($popup_options['mumara_api_url']).'" class="widefat">
							<br /><em>'.__('Enter your Mumara URL.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Token', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mumara_api_token" name="ulp_mumara_api_token" value="'.esc_html($popup_options['mumara_api_token']).'" class="widefat">
							<br /><em>'.__('Enter your Mumara API Token. You can generate it in Mumara account (Settings >> API Key).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-mumara-list" name="ulp_mumara_list" value="'.esc_html($popup_options['mumara_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_mumara_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-mumara-list-id" name="ulp_mumara_list_id" value="'.esc_html($popup_options['mumara_list_id']).'" />
							<div id="ulp-mumara-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_mumara_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-mumara-lists", "ulp_api_url": jQuery("#ulp_mumara_api_url").val(), "ulp_api_token": jQuery("#ulp_mumara_api_token").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-mumara-fields-html">';
		if (!empty($popup_options['mumara_api_url']) && !empty($popup_options['mumara_api_token'])) {
			$fields = $this->get_fields_html($popup_options['mumara_api_url'], $popup_options['mumara_api_token'], $popup_options['mumara_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a class="ulp-button ulp-button-small" onclick="return ulp_mumara_loadfields(this);"><i class="fas fa-check"></i><label>Load Fields</label></a>
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_mumara_loadfields(_object) {
									jQuery(_object).addClass("ulp-button-disabled");
									jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
									jQuery(".ulp-mumara-fields-html").slideUp(350);
									var data = {action: "ulp-mumara-fields", ulp_api_url: jQuery("#ulp_mumara_api_url").val(), ulp_api_token: jQuery("#ulp_mumara_api_token").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery(_object).removeClass("ulp-button-disabled");
										jQuery(_object).find("i").attr("class", "fas fa-check");
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-mumara-fields-html").html(data.html);
												jQuery(".ulp-mumara-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-mumara-fields-html").html("<div class=\'ulp-mumara-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Mumara server.', 'ulp').'</strong></div>");
												jQuery(".ulp-mumara-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-mumara-fields-html").html("<div class=\'ulp-mumara-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Mumara server.', 'ulp').'</strong></div>");
											jQuery(".ulp-mumara-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_mumara_enable"])) $popup_options['mumara_enable'] = "on";
		else $popup_options['mumara_enable'] = "off";
		if ($popup_options['mumara_enable'] == 'on') {
			if (empty($popup_options['mumara_api_url']) || !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $popup_options['mumara_api_url'])) $errors[] = __('Invalid Mumara API URL.', 'ulp');
			if (empty($popup_options['mumara_api_token'])) $errors[] = __('Invalid Mumara API Private Key.', 'ulp');
			if (empty($popup_options['mumara_list_id'])) $errors[] = __('Invalid Mumara List ID.', 'ulp');
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
		$popup_options['mumara_fields'] = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_mumara_field_')) == 'ulp_mumara_field_') {
				$field = substr($key, strlen('ulp_mumara_field_'));
				$popup_options['mumara_fields'][$field] = stripslashes(trim($value));
			}
		}
		if (isset($ulp->postdata["ulp_mumara_enable"])) $popup_options['mumara_enable'] = "on";
		else $popup_options['mumara_enable'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['mumara_enable'] == 'on') {
			$post_data = array(
				'email' => $_subscriber['{subscription-email}'], 
				'list_id' => $popup_options['mumara_list_id'],
				'confirmation_status' => 1
			);
			foreach ($popup_options['mumara_fields'] as $key => $value) {
				if (!empty($value)) {
					$post_data[$key] = strtr($value, $_subscriber);
				}
			}
			$result = $this->_connect($popup_options['mumara_api_url'], $popup_options['mumara_api_token'], 'add-subscriber', 'POST', $post_data);
		}
	}
	function show_lists() {
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_api_url']) || empty($_POST['ulp_api_url']) || !isset($_POST['ulp_api_token']) || empty($_POST['ulp_api_token'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Credentails!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$api_url = trim(stripslashes($_POST['ulp_api_url']));
			$api_token = trim(stripslashes($_POST['ulp_api_token']));
		
			$result = $this->_connect($api_url, $api_token, 'getLists');
			if(!$result || !is_array($result) || !array_key_exists('status', $result) || $result['status'] != 'success' || !array_key_exists('response', $result)) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API credentials.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			if (sizeof($result['response']) == 0) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No lists found.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$list_html = '';
			foreach ($result['response'] as $key => $value) {
				$list_html .= '<a href="#" data-id="'.esc_html($value['id']).'" data-title="'.esc_html($value['id']).(!empty($value['name']) ? ' | '.esc_html($value['name']) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($value['id']).(!empty($value['name']) ? ' | '.esc_html($value['name']) : '').'</a>';
			}
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $list_html;
			$return_object['items'] = sizeof($result['response']);
			echo json_encode($return_object);
		}
		exit;
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_api_url']) || empty($_POST['ulp_api_url']) || !isset($_POST['ulp_api_token']) || empty($_POST['ulp_api_token'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-mumara-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API credentials.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$api_url = trim(stripslashes($_POST['ulp_api_url']));
			$api_token = trim(stripslashes($_POST['ulp_api_token']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($api_url, $api_token, $this->default_popup_options['mumara_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_api_url, $_api_token, $_fields) {
		$result = $this->_connect($_api_url, $_api_token, 'getCustomFields');
		if(!$result || !is_array($result) || !array_key_exists('status', $result) || $result['status'] != 'success' || !array_key_exists('response', $result)) {
			return '<div class="ulp-mumara-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid API credentials.', 'ulp').'</strong></div>';
		}
		if (sizeof($result['response']) == 0) {
			return '<div class="ulp-mumara-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
		}

		$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Mumara fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">
				<tr>
					<td style="width: 100px;"><strong>'.__('Email', 'ulp').':</strong></td>
					<td>
						<input type="text" value="{subscription-email}" class="widefat" readonly="readonly" />
						<br /><em>'.__('Email', 'ulp').' (email)</em>
					</td>
				</tr>';
		foreach ($result['response'] as $field) {
			if (is_array($field)) {
				if (array_key_exists('tag', $field) && array_key_exists('name', $field)) {
					$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['name']).':</strong></td>
					<td>
						<input type="text" id="ulp_mumara_field_'.esc_html($field['tag']).'" name="ulp_mumara_field_'.esc_html($field['tag']).'" value="'.esc_html(array_key_exists($field['tag'], $_fields) ? $_fields[$field['tag']] : '').'" class="widefat" />
						<br /><em>'.esc_html($field['name'].' ('.$field['tag'].')').'</em>
					</td>
				</tr>';
				}
			}
		}
		$fields .= '
			</table>';
		return $fields;
	}
	
	function _connect($_api_url, $_api_token, $_path, $_method = 'GET', $_data = null) {
		try {
			$url = rtrim($_api_url, '/').'/api/'.trim($_path, '/');
			
			if ($_method == 'GET') {
				$url .= (strpos($url, '?') === false ? '?' : '&').'api_token='.$_api_token;
			} else {
				$_data['api_token'] = $_api_token;
			}
			
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_USERAGENT, 'MumaraApi Client version 1.0');
			curl_setopt($ch, CURLOPT_AUTOREFERER , true);
			//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			if (is_array($_data) && !empty($_data)) {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($_method));
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $_data);
			}
			$response = curl_exec($ch);
			curl_close($ch);
			$result = json_decode($response, true);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
}
$ulp_mumara = new ulp_mumara_class();
?>