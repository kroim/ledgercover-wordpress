<?php
/* SqualoMail integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_squalomail_class {
	var $default_popup_options = array(
		"squalomail_enable" => "off",
		"squalomail_api_user" => "",
		"squalomail_api_key" => "",
		"squalomail_list" => "",
		"squalomail_list_id" => "",
		"squalomail_name" => "{subscription-name}",
		"squalomail_fields" => array()
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-squalomail-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-squalomail-groups', array(&$this, "show_groups"));
			add_action('wp_ajax_ulp-squalomail-fields', array(&$this, "show_fields"));
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
				<h3>'.__('SqualoMail Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable SqualoMail', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_squalomail_enable" name="ulp_squalomail_enable" '.($popup_options['squalomail_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to SqualoMail', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to SqualoMail.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API User', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_squalomail_api_user" name="ulp_squalomail_api_user" value="'.esc_html($popup_options['squalomail_api_user']).'" class="widefat">
							<br /><em>'.__('Enter your SqualoMail API User. You can request it by sending message to <a href="mailto:podpora@squalomail.com">podpora@squalomail.com</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_squalomail_api_key" name="ulp_squalomail_api_key" value="'.esc_html($popup_options['squalomail_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your SqualoMail API Key. You can request it by sending message to <a href="mailto:podpora@squalomail.com">podpora@squalomail.com</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-squalomail-list" name="ulp_squalomail_list" value="'.esc_html($popup_options['squalomail_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_squalomail_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-squalomail-list-id" name="ulp_squalomail_list_id" value="'.esc_html($popup_options['squalomail_list_id']).'" />
							<div id="ulp-squalomail-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_squalomail_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-squalomail-lists", "ulp_api_user": jQuery("#ulp_squalomail_api_user").val(), "ulp_api_key": jQuery("#ulp_squalomail_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Attributes', 'ulp').':</th>
						<td style="vertical-align: middle;">
							'.__('Please adjust the attributes below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate SqualoMail attributes with the popup fields.', 'ulp').'
							<table style="min-width: 280px; width: 50%;">
								<tr>
									<td style="width: 100px;"><strong>'.__('Email', 'ulp').':</strong></td>
									<td>
										<input type="text" value="{subscription-email}" class="widefat" readonly="readonly" />
										<br /><em>'.__('Email address of the contact.', 'ulp').'</em>
									</td>
								</tr>
								<tr>
									<td style="width: 100px;"><strong>'.__('Name', 'ulp').':</strong></td>
									<td>
										<input type="text" id="ulp_squalomail_name" name="ulp_squalomail_name" value="'.esc_html($popup_options['squalomail_name']).'" class="widefat" />
										<br /><em>'.__('Name of the contact.', 'ulp').'</em>
									</td>
								</tr>
							</table>
							<div class="ulp-squalomail-fields-html">';
		if (!empty($popup_options['squalomail_api_key']) && !empty($popup_options['squalomail_list_id'])) {
			$fields = $this->get_fields_html($popup_options['squalomail_api_user'], $popup_options['squalomail_api_key'], $popup_options['squalomail_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_squalomail_fields_button" class="ulp_button button-secondary" onclick="return ulp_squalomail_loadfields();">'.__('Load Custom Attributes', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-squalomail-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load custom attributes list. Ignore if you do not need specify custom attributes values.', 'ulp').'</em>
							<script>
								function ulp_squalomail_loadfields() {
									jQuery("#ulp-squalomail-fields-loading").fadeIn(350);
									jQuery(".ulp-squalomail-fields-html").slideUp(350);
									var data = {action: "ulp-squalomail-fields", ulp_user: jQuery("#ulp_squalomail_api_user").val(), ulp_key: jQuery("#ulp_squalomail_api_key").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-squalomail-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-squalomail-fields-html").html(data.html);
												jQuery(".ulp-squalomail-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-squalomail-fields-html").html("<div class=\'ulp-squalomail-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to SqualoMail server.', 'ulp').'</strong></div>");
												jQuery(".ulp-squalomail-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-squalomail-fields-html").html("<div class=\'ulp-squalomail-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to SqualoMail server.', 'ulp').'</strong></div>");
											jQuery(".ulp-squalomail-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_squalomail_enable"])) $popup_options['squalomail_enable'] = "on";
		else $popup_options['squalomail_enable'] = "off";
		if ($popup_options['squalomail_enable'] == 'on') {
			if (empty($popup_options['squalomail_api_user'])) $errors[] = __('Invalid SqualoMail API User.', 'ulp');
			if (empty($popup_options['squalomail_api_key'])) $errors[] = __('Invalid SqualoMail API Key.', 'ulp');
			if (empty($popup_options['squalomail_list_id'])) $errors[] = __('Invalid SqualoMail List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_squalomail_enable"])) $popup_options['squalomail_enable'] = "on";
		else $popup_options['squalomail_enable'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_squalomail_field_')) == 'ulp_squalomail_field_') {
				$field = substr($key, strlen('ulp_squalomail_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['squalomail_fields'] = $fields;
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['squalomail_enable'] == 'on') {
			$data = array(
				'accept' => true,
				'email' => $_subscriber['{subscription-email}'],
				'name' => strtr($popup_options['squalomail_name'], $_subscriber),
				'html' => true,
				'listIds' => array($popup_options['squalomail_list_id']),
				'confirmed' => true,
				'enabled' => true
			);
			foreach ($popup_options['squalomail_fields'] as $key => $value) {
				if (!empty($value)) {
					$data['customAttributes'][] = array('name' => $key, 'value' => strtr($value, $_subscriber));
				}
			}
			$result = $this->connect($popup_options['squalomail_api_user'], $popup_options['squalomail_api_key'], 'get-data?entity=recipient&filter='.urlencode('email=="'.$_subscriber['{subscription-email}'].'"'));
			if (empty($result)) {
				$result = $this->connect($popup_options['squalomail_api_user'], $popup_options['squalomail_api_key'], 'create-recipient', $data);
			} else {
				$result = $this->connect($popup_options['squalomail_api_user'], $popup_options['squalomail_api_key'], 'update-recipient', $data);
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_api_user']) || empty($_POST['ulp_api_user']) || !isset($_POST['ulp_api_key']) || empty($_POST['ulp_api_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API credentials!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$user = trim(stripslashes($_POST['ulp_api_user']));
			$key = trim(stripslashes($_POST['ulp_api_key']));
			
			$result = $this->connect($user, $key, 'get-data?entity=List');
			if (is_array($result)) {
				if (sizeof($result) > 0) {
					foreach ($result as $list) {
						if (is_array($list)) {
							if (array_key_exists('listid', $list) && array_key_exists('name', $list)) {
								$lists[$list['listid']] = $list['name'];
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
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API credentials!', 'ulp').'</div>';
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
			if (!isset($_POST['ulp_key']) || !isset($_POST['ulp_user']) || empty($_POST['ulp_key']) || empty($_POST['ulp_user'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-squalomail-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API credentials.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_key']));
			$user = trim(stripslashes($_POST['ulp_user']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($user, $key, $this->default_popup_options['squalomail_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_user, $_key, $_fields) {
		$result = $this->connect($_user, $_key, 'get-data?entity=Field');
		if (is_array($result)) {
			if (sizeof($result) > 0) {
				$fields_found = false;
				$fields = '
			<table style="min-width: 280px; width: 50%;">';
				foreach ($result as $field) {
					if (is_array($field)) {
						if (array_key_exists('namekey', $field) && array_key_exists('fieldname', $field) && array_key_exists('core', $field)) {
							if (!$field['core']) {
								$fields_found = true;
								$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['fieldname']).':</strong></td>
					<td>
						<input type="text" id="ulp_squalomail_field_'.esc_html($field['namekey']).'" name="ulp_squalomail_field_'.esc_html($field['namekey']).'" value="'.esc_html(array_key_exists($field['namekey'], $_fields) ? $_fields[$field['namekey']] : '').'" class="widefat"'.($field['namekey'] == 'email' ? ' readonly="readonly"' : '').' />
						<br /><em>'.esc_html($field['fieldname']).'</em>
					</td>
				</tr>';
							}
						}
					}
				}
				$fields .= '
			</table>';
				if (!$fields_found) {
					$fields = '<div class="ulp-squalomail-grouping" style="margin-bottom: 10px;"><strong>'.__('No custom attributes found.', 'ulp').'</strong></div>';
				}
			} else {
				$fields = '<div class="ulp-squalomail-grouping" style="margin-bottom: 10px;"><strong>'.__('No custom attributes found.', 'ulp').'</strong></div>';
			}
		} else {
			$fields = '<div class="ulp-squalomail-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid API credentials or server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_api_user, $_api_key, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		try {
			$url = 'https://api.squalomail.com/v1/'.ltrim($_path, '/');
			if (empty($_data)) {
				if (strpos($url, '?') !== false) $url .= '&';
				else  $url .= '?';
				$url .= 'apiUser='.urlencode($_api_user).'&apiKey='.urlencode($_api_key);
			} else {
				if (strpos($url, '?') !== false) $url .= '&';
				else  $url .= '?';
				$url .= 'apiUser='.urlencode($_api_user).'&apiKey='.urlencode($_api_key);
				$_data['apiUser'] = $_api_user;
				$_data['apiKey'] = $_api_key;
			}
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
//				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
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
$ulp_squalomail = new ulp_squalomail_class();
?>