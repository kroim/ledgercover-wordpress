<?php
/* KIRIM.EMAIL integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_kirimemail_class {
	var $default_popup_options = array(
		"kirimemail_enable" => "off",
		"kirimemail_username" => "",
		"kirimemail_token" => "",
		"kirimemail_list" => "",
		"kirimemail_list_id" => "",
		"kirimemail_name" => "{subscription-name}",
		"kirimemail_field_values" => array(),
		"kirimemail_field_names" => array(),
		"kirimemail_double" => "off"
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-kirimemail-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-kirimemail-fields', array(&$this, "show_fields"));
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
				<h3>'.__('KIRIM.EMAIL Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable KIRIM.EMAIL', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_kirimemail_enable" name="ulp_kirimemail_enable" '.($popup_options['kirimemail_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to KIRIM.EMAIL', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to KIRIM.EMAIL.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Username', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_kirimemail_username" name="ulp_kirimemail_username" value="'.esc_html($popup_options['kirimemail_username']).'" class="widefat">
							<br /><em>'.__('Enter your KIRIM.EMAIL username.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Token', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_kirimemail_token" name="ulp_kirimemail_token" value="'.esc_html($popup_options['kirimemail_token']).'" class="widefat">
							<br /><em>'.__('Enter your KIRIM.EMAIL API Token. You can get it <a href="https://aplikasi.kirim.email/users/tokenconfig/" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-kirimemail-list" name="ulp_kirimemail_list" value="'.esc_html($popup_options['kirimemail_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_kirimemail_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-kirimemail-list-id" name="ulp_kirimemail_list_id" value="'.esc_html($popup_options['kirimemail_list_id']).'" />
							<div id="ulp-kirimemail-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_kirimemail_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-kirimemail-lists", "ulp_username": jQuery("#ulp_kirimemail_username").val(), "ulp_token": jQuery("#ulp_kirimemail_token").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							'.__('Please adjust the attributes below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate KIRIM.EMAIL fields with the popup fields.', 'ulp').'
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
										<input type="text" id="ulp_kirimemail_name" name="ulp_kirimemail_name" value="'.esc_html($popup_options['kirimemail_name']).'" class="widefat" />
										<br /><em>'.__('Name of the contact.', 'ulp').'</em>
									</td>
								</tr>
							</table>
							<div class="ulp-kirimemail-fields-html">';
		if (!empty($popup_options['kirimemail_username']) && !empty($popup_options['kirimemail_list_id'])) {
			$fields = $this->get_fields_html($popup_options['kirimemail_username'], $popup_options['kirimemail_token'], $popup_options['kirimemail_field_values']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_kirimemail_fields_button" class="ulp_button button-secondary" onclick="return ulp_kirimemail_loadfields();">'.__('Load Custom Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-kirimemail-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_kirimemail_loadfields() {
									jQuery("#ulp-kirimemail-fields-loading").fadeIn(350);
									jQuery(".ulp-kirimemail-fields-html").slideUp(350);
									var data = {action: "ulp-kirimemail-fields", ulp_username: jQuery("#ulp_kirimemail_username").val(), ulp_token: jQuery("#ulp_kirimemail_token").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-kirimemail-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-kirimemail-fields-html").html(data.html);
												jQuery(".ulp-kirimemail-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-kirimemail-fields-html").html("<div class=\'ulp-kirimemail-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to KIRIM.EMAIL server.', 'ulp').'</strong></div>");
												jQuery(".ulp-kirimemail-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-kirimemail-fields-html").html("<div class=\'ulp-kirimemail-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to KIRIM.EMAIL server.', 'ulp').'</strong></div>");
											jQuery(".ulp-kirimemail-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_kirimemail_enable"])) $popup_options['kirimemail_enable'] = "on";
		else $popup_options['kirimemail_enable'] = "off";
		if ($popup_options['kirimemail_enable'] == 'on') {
			if (empty($popup_options['kirimemail_username'])) $errors[] = __('Invalid KIRIM.EMAIL Username.', 'ulp');
			if (empty($popup_options['kirimemail_token'])) $errors[] = __('Invalid KIRIM.EMAIL API Token.', 'ulp');
			if (empty($popup_options['kirimemail_list_id'])) $errors[] = __('Invalid KIRIM.EMAIL List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_kirimemail_enable"])) $popup_options['kirimemail_enable'] = "on";
		else $popup_options['kirimemail_enable'] = "off";
		
		$field_values = array();
		$field_names = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_kirimemail_fieldvalue_')) == 'ulp_kirimemail_fieldvalue_') {
				$field = substr($key, strlen('ulp_kirimemail_fieldvalue_'));
				$field_values[$field] = stripslashes(trim($value));
			} else if (substr($key, 0, strlen('ulp_kirimemail_fieldname_')) == 'ulp_kirimemail_fieldname_') {
				$field = substr($key, strlen('ulp_kirimemail_fieldname_'));
				$field_names[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['kirimemail_field_values'] = $field_values;
		$popup_options['kirimemail_field_names'] = $field_names;
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['kirimemail_enable'] == 'on') {
			$data = array(
				'email' => $_subscriber['{subscription-email}'],
				'full_name' => strtr($popup_options['kirimemail_name'], $_subscriber),
				'lists' => array($popup_options['kirimemail_list_id'])
			);
			foreach ($popup_options['kirimemail_field_values'] as $key => $value) {
				if (!empty($value)) {
					$data['fields'][$popup_options['kirimemail_field_names'][$key]] = strtr($value, $_subscriber);
				}
			}
			$result = $this->connect($popup_options['kirimemail_username'], $popup_options['kirimemail_token'], 'subscriber', $data);
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_username']) || empty($_POST['ulp_username']) || !isset($_POST['ulp_token']) || empty($_POST['ulp_token'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API credentials!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$username = trim(stripslashes($_POST['ulp_username']));
			$token = trim(stripslashes($_POST['ulp_token']));
			
			$result = $this->connect($username, $token, 'list');
			if (is_array($result) && array_key_exists('status', $result)) {
				if ($result['status'] == 'success') {
					if (intval($result['total']) > 0) {
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
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No Lists found!', 'ulp').'</div>';
						echo json_encode($return_object);
						exit;
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.(array_key_exists('message', $result) ? $result['message'] : __('Invalid API credentials!', 'ulp')).'</div>';
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
			if (!isset($_POST['ulp_username']) || !isset($_POST['ulp_token']) || empty($_POST['ulp_username']) || empty($_POST['ulp_token'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-kirimemail-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API credentials.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$username = trim(stripslashes($_POST['ulp_username']));
			$token = trim(stripslashes($_POST['ulp_token']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($username, $token, $this->default_popup_options['kirimemail_field_values']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_username, $_token, $_fields) {
		$fields = '';
		$result = $this->connect($_username, $_token, 'subscriber_field');
		if (is_array($result) && array_key_exists('status', $result)) {
			if ($result['status'] == 'success') {
				if (array_key_exists('total', $result) && $result['total'] > 0) {
					$fields = '
			<table style="min-width: 280px; width: 50%;">';
					foreach ($result['data'] as $field) {
						if (is_array($field)) {
							if (array_key_exists('id', $field) && array_key_exists('personalization_tag', $field)) {
								$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['name']).':</strong></td>
					<td>
						<input type="text" id="ulp_kirimemail_fieldvalue_'.esc_html($field['id']).'" name="ulp_kirimemail_fieldvalue_'.esc_html($field['id']).'" value="'.esc_html(array_key_exists($field['id'], $_fields) ? $_fields[$field['id']] : '').'" class="widefat" />
						<input type="hidden" id="ulp_kirimemail_fieldname_'.esc_html($field['id']).'" name="ulp_kirimemail_fieldname_'.esc_html($field['id']).'" value="'.esc_html($field['personalization_tag']).'" />
						<br /><em>'.(array_key_exists('name', $field) && !empty($field['name']) ? esc_html($field['name']) :  esc_html($field['personalization_tag'])).'</em>
					</td>
				</tr>';
							}
						}
					}
					$fields .= '
			</table>';
				} else {
					$fields = '<div class="ulp-kirimemail-grouping" style="margin-bottom: 10px;"><strong>'.__('No custom fields found.', 'ulp').'</strong></div>';
				}
			} else {
				$fields = '<div class="ulp-kirimemail-grouping" style="margin-bottom: 10px;"><strong>'.(array_key_exists('message', $result) ? $result['message'] : __('Invalid API credentials!', 'ulp')).'</strong></div>';
			}
		} else {
			$fields = '<div class="ulp-kirimemail-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_username, $_token, $_path, $_data = array(), $_method = '') {
		$time = time();
		$headers = array(
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json',
			'Auth-Id: '.$_username,
			'Auth-Token: '.hash_hmac('sha256', $_username.'::'.$_token."::".$time, $_token),
			'Timestamp: '.$time
		);
		try {
			$url = 'https://aplikasi.kirim.email/api/v3/'.ltrim($_path, '/');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 20);
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
$ulp_kirimemail = new ulp_kirimemail_class();
?>