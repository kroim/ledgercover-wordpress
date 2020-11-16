<?php
/* dotmailer integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_dotmailer_class {
	var $default_popup_options = array(
		"dotmailer_enable" => "off",
		"dotmailer_endpoint" => "",
		"dotmailer_api_user" => "",
		"dotmailer_api_password" => "",
		"dotmailer_list" => "",
		"dotmailer_list_id" => "",
		"dotmailer_fields" => array(),
		"dotmailer_double" => "off"
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-dotmailer-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-dotmailer-fields', array(&$this, "show_fields"));
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
				<h3>'.__('dotmailer Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable dotmailer', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_dotmailer_enable" name="ulp_dotmailer_enable" '.($popup_options['dotmailer_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to dotmailer', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to dotmailer.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Endpoint', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_dotmailer_endpoint" name="ulp_dotmailer_endpoint" value="'.esc_html($popup_options['dotmailer_endpoint']).'" class="widefat">
							<br /><em>'.__('Enter your dotmailer API Endpoint. You can get it <a href="https://app.dotmailer.com/access/api" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API User', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_dotmailer_api_user" name="ulp_dotmailer_api_user" value="'.esc_html($popup_options['dotmailer_api_user']).'" class="widefat">
							<br /><em>'.__('Enter your dotmailer API User. You can get it <a href="https://app.dotmailer.com/access/api" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Password', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_dotmailer_api_password" name="ulp_dotmailer_api_password" value="'.esc_html($popup_options['dotmailer_api_password']).'" class="widefat">
							<br /><em>'.__('Enter your dotmailer API User password.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Address Book', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-dotmailer-list" name="ulp_dotmailer_list" value="'.esc_html($popup_options['dotmailer_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_dotmailer_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-dotmailer-list-id" name="ulp_dotmailer_list_id" value="'.esc_html($popup_options['dotmailer_list_id']).'" />
							<div id="ulp-dotmailer-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Select Address Book.', 'ulp').'</em>
							<script>
								function ulp_dotmailer_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-dotmailer-lists", "ulp_endpoint": jQuery("#ulp_dotmailer_endpoint").val(), "ulp_api_user": jQuery("#ulp_dotmailer_api_user").val(), "ulp_api_password": jQuery("#ulp_dotmailer_api_password").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-dotmailer-fields-html">';
		if (!empty($popup_options['dotmailer_endpoint']) && !empty($popup_options['dotmailer_api_user']) && !empty($popup_options['dotmailer_api_password'])) {
			$fields = $this->get_fields_html($popup_options['dotmailer_endpoint'], $popup_options['dotmailer_api_user'], $popup_options['dotmailer_api_password'], $popup_options['dotmailer_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_dotmailer_fields_button" class="ulp_button button-secondary" onclick="return ulp_dotmailer_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-dotmailer-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_dotmailer_loadfields() {
									jQuery("#ulp-dotmailer-fields-loading").fadeIn(350);
									jQuery(".ulp-dotmailer-fields-html").slideUp(350);
									var data = {action: "ulp-dotmailer-fields", ulp_endpoint: jQuery("#ulp_dotmailer_endpoint").val(), ulp_api_user: jQuery("#ulp_dotmailer_api_user").val(), ulp_api_password: jQuery("#ulp_dotmailer_api_password").val(), ulp_list: jQuery("#ulp-dotmailer-list-id").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-dotmailer-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-dotmailer-fields-html").html(data.html);
												jQuery(".ulp-dotmailer-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-dotmailer-fields-html").html("<div class=\'ulp-dotmailer-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to dotmailer server.', 'ulp').'</strong></div>");
												jQuery(".ulp-dotmailer-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-dotmailer-fields-html").html("<div class=\'ulp-dotmailer-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to dotmailer server.', 'ulp').'</strong></div>");
											jQuery(".ulp-dotmailer-fields-html").slideDown(350);
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Double opt-in', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_dotmailer_double" name="ulp_dotmailer_double" '.($popup_options['dotmailer_double'] == "on" ? 'checked="checked"' : '').'"> '.__('Ask users to confirm their subscription', 'ulp').'
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
		if (isset($ulp->postdata["ulp_dotmailer_enable"])) $popup_options['dotmailer_enable'] = "on";
		else $popup_options['dotmailer_enable'] = "off";
		if ($popup_options['dotmailer_enable'] == 'on') {
			if (empty($popup_options['dotmailer_endpoint']) || !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $popup_options['dotmailer_endpoint'])) $errors[] = __('Invalid dotmailer Endpoint.', 'ulp');
			if (empty($popup_options['dotmailer_api_user'])) $errors[] = __('Invalid dotmailer API User.', 'ulp');
			if (empty($popup_options['dotmailer_api_password'])) $errors[] = __('Invalid dotmailer API User password.', 'ulp');
			if (empty($popup_options['dotmailer_list_id'])) $errors[] = __('Invalid dotmailer Address Book.', 'ulp');
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
		if (isset($ulp->postdata["ulp_dotmailer_double"])) $popup_options['dotmailer_double'] = "on";
		else $popup_options['dotmailer_double'] = "off";
		if (isset($ulp->postdata["ulp_dotmailer_enable"])) $popup_options['dotmailer_enable'] = "on";
		else $popup_options['dotmailer_enable'] = "off";
		
		$popup_options['dotmailer_fields'] = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_dotmailer_field_')) == 'ulp_dotmailer_field_') {
				$field = substr($key, strlen('ulp_dotmailer_field_'));
				$popup_options['dotmailer_fields'][$field] = stripslashes(trim($value));
			}
		}
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['dotmailer_enable'] == 'on') {
			$data = array(
				'email' => $_subscriber['{subscription-email}'],
				'optInType' => $popup_options['dotmailer_double'] == 'on' ? 'VerifiedDouble' : 'Single',
				'emailType' => 'Html'
			);
			if (!empty($popup_options['dotmailer_fields']) && is_array($popup_options['dotmailer_fields'])) {
				foreach ($popup_options['dotmailer_fields'] as $key => $value) {
					if (!empty($value)) {
						$data['dataFields'][] = array('key' => $key, 'value' => strtr($value, $_subscriber));
					}
				}
			}
			if ($popup_options['dotmailer_list_id'] == 0) $result = $this->connect($popup_options['dotmailer_endpoint'], $popup_options['dotmailer_api_user'], $popup_options['dotmailer_api_password'], 'contacts', $data);
			else $result = $this->connect($popup_options['dotmailer_endpoint'], $popup_options['dotmailer_api_user'], $popup_options['dotmailer_api_password'], 'address-books/'.$popup_options['dotmailer_list_id'].'/contacts', $data);
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array('0' => 'All Contacts');
			if (!isset($_POST['ulp_endpoint']) || empty($_POST['ulp_endpoint']) || !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $_POST['ulp_endpoint']) || !isset($_POST['ulp_api_user']) || empty($_POST['ulp_api_user']) || !isset($_POST['ulp_api_password']) || empty($_POST['ulp_api_password'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Credentials!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$endpoint = trim(stripslashes($_POST['ulp_endpoint']));
			$user = trim(stripslashes($_POST['ulp_api_user']));
			$password = trim(stripslashes($_POST['ulp_api_password']));
			
			$result = $this->connect($endpoint, $user, $password, 'address-books');
			if (is_array($result)) {
				if (array_key_exists('message', $result)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.esc_html($result['message']).'</div>';
					echo json_encode($return_object);
					exit;
				}
				if (sizeof($result) > 0) {
					foreach ($result as $list) {
						if (is_array($list)) {
							if (array_key_exists('id', $list) && array_key_exists('name', $list)) {
								$lists[$list['id']] = $list['name'];
							}
						}
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No Address Books found!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Credentials!', 'ulp').'</div>';
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
			if (!isset($_POST['ulp_endpoint']) || empty($_POST['ulp_endpoint']) || !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $_POST['ulp_endpoint']) || !isset($_POST['ulp_api_user']) || empty($_POST['ulp_api_user']) || !isset($_POST['ulp_api_password']) || empty($_POST['ulp_api_password'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-dotmailer-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Credentials.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$endpoint = trim(stripslashes($_POST['ulp_endpoint']));
			$user = trim(stripslashes($_POST['ulp_api_user']));
			$password = trim(stripslashes($_POST['ulp_api_password']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($endpoint, $user, $password, $this->default_popup_options['dotmailer_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_endpoint, $_user, $_password, $_fields) {
		$result = $this->connect($_endpoint, $_user, $_password, 'data-fields');
		$fields = '';
		if (!empty($result) && is_array($result)) {
			if (array_key_exists('message', $result)) {
				$fields = '<div class="ulp-dotmailer-grouping" style="margin-bottom: 10px;"><strong>'.esc_html($result['message']).'</strong></div>';
			} else {
				if (sizeof($result) > 0) {
					$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate dotmailer fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
					foreach ($result as $field) {
						if (is_array($field)) {
							if (array_key_exists('visibility', $field) && array_key_exists('name', $field) && strtolower($field['visibility']) == 'public') {
								$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['name']).':</strong></td>
					<td>
						<input type="text" id="ulp_dotmailer_field_'.esc_html($field['name']).'" name="ulp_dotmailer_field_'.esc_html($field['name']).'" value="'.esc_html(array_key_exists($field['name'], $_fields) ? $_fields[$field['name']] : '').'" class="widefat" />
						<br /><em>'.esc_html($field['name']).'</em>
					</td>
				</tr>';
							}
						}
					}
					$fields .= '
			</table>';
				} else {
					$fields = '<div class="ulp-dotmailer-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
				}
			}
		} else {
			$fields = '<div class="ulp-dotmailer-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_endpoint, $_user, $_password, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		try {
			$url = rtrim($_endpoint, '/').'/v2/'.ltrim($_path, '/');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLAUTH_BASIC, CURLAUTH_DIGEST);
			curl_setopt($curl, CURLOPT_USERPWD, $_user.':'.$_password);			
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
$ulp_dotmailer = new ulp_dotmailer_class();
?>