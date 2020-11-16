<?php
/* Perfit integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_perfit_class {
	var $default_popup_options = array(
		"perfit_enable" => "off",
		"perfit_username" => "",
		"perfit_password" => "",
		"perfit_list" => "",
		"perfit_list_id" => "",
		"perfit_firstname" => "{subscription-name}",
		"perfit_lastname" => "",
		"perfit_fields" => array()
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-perfit-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-perfit-groups', array(&$this, "show_groups"));
			add_action('wp_ajax_ulp-perfit-fields', array(&$this, "show_fields"));
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
				<h3>'.__('Perfit Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Perfit', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_perfit_enable" name="ulp_perfit_enable" '.($popup_options['perfit_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Perfit', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Perfit.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Email', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_perfit_username" name="ulp_perfit_username" value="'.esc_html($popup_options['perfit_username']).'" class="widefat">
							<br /><em>'.__('Enter email address that you use to login Perfit account', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Password', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_perfit_password" name="ulp_perfit_password" value="'.esc_html($popup_options['perfit_password']).'" class="widefat">
							<br /><em>'.__('Enter password that you use to login Perfit account', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-perfit-list" name="ulp_perfit_list" value="'.esc_html($popup_options['perfit_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_perfit_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-perfit-list-id" name="ulp_perfit_list_id" value="'.esc_html($popup_options['perfit_list_id']).'" />
							<div id="ulp-perfit-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_perfit_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-perfit-lists", "ulp_username": jQuery("#ulp_perfit_username").val(), "ulp_password": jQuery("#ulp_perfit_password").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Perfit fields with the popup fields.', 'ulp').'
							<table style="min-width: 280px; width: 50%;">
								<tr>
									<td style="width: 100px;"><strong>'.__('Email', 'ulp').':</strong></td>
									<td>
										<input type="text" id="ulp_perfit_email" name="ulp_perfit_email" value="{subscription-email}" class="widefat" readonly="readonly" />
										<br /><em>'.__('Email address', 'ulp').'</em>
									</td>
								</tr>
								<tr>
									<td><strong>'.__('First Name', 'ulp').':</strong></td>
									<td>
										<input type="text" id="ulp_perfit_firstname" name="ulp_perfit_firstname" value="'.esc_html($popup_options['perfit_firstname']).'" class="widefat" />
										<br /><em>'.__('First name', 'ulp').'</em>
									</td>
								</tr>
								<tr>
									<td><strong>'.__('Last Name', 'ulp').':</strong></td>
									<td>
										<input type="text" id="ulp_perfit_lastname" name="ulp_perfit_lastname" value="'.esc_html($popup_options['perfit_lastname']).'" class="widefat" />
										<br /><em>'.__('Last name', 'ulp').'</em>
									</td>
								</tr>
							</table>
							<div class="ulp-perfit-fields-html">';
		if (!empty($popup_options['perfit_username']) && !empty($popup_options['perfit_password'])) {
			$fields = $this->get_fields_html($popup_options['perfit_username'], $popup_options['perfit_password'], $popup_options['perfit_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_perfit_fields_button" class="ulp_button button-secondary" onclick="return ulp_perfit_loadfields();">'.__('Load Custom Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-perfit-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load custom fields list. Ignore if you do not need specify custom fields values.', 'ulp').'</em>
							<script>
								function ulp_perfit_loadfields() {
									jQuery("#ulp-perfit-fields-loading").fadeIn(350);
									jQuery(".ulp-perfit-fields-html").slideUp(350);
									var data = {action: "ulp-perfit-fields", ulp_username: jQuery("#ulp_perfit_username").val(), ulp_password: jQuery("#ulp_perfit_password").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-perfit-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-perfit-fields-html").html(data.html);
												jQuery(".ulp-perfit-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-perfit-fields-html").html("<div class=\'ulp-perfit-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Perfit server.', 'ulp').'</strong></div>");
												jQuery(".ulp-perfit-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-perfit-fields-html").html("<div class=\'ulp-perfit-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Perfit server.', 'ulp').'</strong></div>");
											jQuery(".ulp-perfit-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_perfit_enable"])) $popup_options['perfit_enable'] = "on";
		else $popup_options['perfit_enable'] = "off";
		if ($popup_options['perfit_enable'] == 'on') {
			if (empty($popup_options['perfit_username'])) $errors[] = __('Invalid Perfit Email.', 'ulp');
			if (empty($popup_options['perfit_password'])) $errors[] = __('Invalid Perfit Password.', 'ulp');
			if (empty($popup_options['perfit_list_id'])) $errors[] = __('Invalid Perfit List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_perfit_enable"])) $popup_options['perfit_enable'] = "on";
		else $popup_options['perfit_enable'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_perfit_field_')) == 'ulp_perfit_field_') {
				$field = substr($key, strlen('ulp_perfit_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['perfit_fields'] = $fields;
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['perfit_enable'] == 'on') {
			$data = array(
				'email' => $_subscriber['{subscription-email}']
			);
			if (!empty($popup_options['perfit_firstname'])) $data['firstName'] = strtr($popup_options['perfit_firstname'], $_subscriber);
			if (!empty($popup_options['perfit_lastname'])) $data['lastName'] = strtr($popup_options['perfit_lastname'], $_subscriber);
			foreach ($popup_options['perfit_fields'] as $key => $value) {
				if (!empty($value)) {
					$data['customFields'][] = array('id' => $key, 'value' => strtr($value, $_subscriber));
				}
			}
			$result = $this->connect($popup_options['perfit_username'], $popup_options['perfit_password'], 'contacts', $data);
			if (is_array($result) && array_key_exists('data', $result) && array_key_exists('id', $result['data'])) {
				$contact_id = $result['data']['id'];
				if (!$result['success']) {
					unset($data['email']);
					$result = $this->connect($popup_options['perfit_username'], $popup_options['perfit_password'], 'contacts/'.$contact_id, $data, 'PUT');
				}
				$result = $this->connect($popup_options['perfit_username'], $popup_options['perfit_password'], 'contacts/'.$contact_id.'/lists/'.$popup_options['perfit_list_id'], array(), 'PUT');
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_username']) || empty($_POST['ulp_username']) || !isset($_POST['ulp_password']) || empty($_POST['ulp_password'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Perfit Credentials!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$username = trim(stripslashes($_POST['ulp_username']));
			$password = trim(stripslashes($_POST['ulp_password']));
			
			$result = $this->connect($username, $password, 'lists?limit=1000');
			if (is_array($result) && array_key_exists('success', $result) && $result['success'] == true) {
				if (sizeof($result['data']) > 0) {
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
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Perfit Credentials!', 'ulp').'</div>';
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
			if (!isset($_POST['ulp_username']) || !isset($_POST['ulp_password']) || empty($_POST['ulp_username']) || empty($_POST['ulp_password'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-perfit-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid Perfit Credentials.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$username = trim(stripslashes($_POST['ulp_username']));
			$password = trim(stripslashes($_POST['ulp_password']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($username, $password, $this->default_popup_options['perfit_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_username, $_password, $_fields) {
		$result = $this->connect($_username, $_password, 'fields?limit=1000');
		$fields_html = '';
		$fields = array();
		if (is_array($result) && array_key_exists('success', $result) && $result['success'] == true) {
			foreach ($result['data'] as $field) {
				if ($field['custom']) $fields[$field['id']] = $field['name'];
			}
			if (sizeof($fields) > 0) {
				$fields_html = '
			<table style="min-width: 280px; width: 50%;">';
				foreach ($fields as $id => $field) {
					$fields_html .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field).':</strong></td>
					<td>
						<input type="text" id="ulp_perfit_field_'.esc_html($id).'" name="ulp_perfit_field_'.esc_html($id).'" value="'.esc_html(array_key_exists($id, $_fields) ? $_fields[$id] : '').'" class="widefat" />
						<br /><em>'.esc_html($field).'</em>
					</td>
				</tr>';
				}
				$fields_html .= '
			</table>';
				} else {
					$fields_html = '<div class="ulp-perfit-grouping" style="margin-bottom: 10px;"><strong>'.__('No custom fields found.', 'ulp').'</strong></div>';
				}
		} else {
			$fields_html = '<div class="ulp-perfit-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid Perfit Credentials.', 'ulp').'</strong></div>';
		}
		return $fields_html;
	}
	function connect($_username, $_password, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: application/json'
		);
		$auth_data = array(
			'user' => $_username,
			'password' => $_password
		);
		try {
			$url = 'https://api.myperfit.com/v2/login';
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($auth_data));
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
			if ($result['success'] == true) {
				$headers = array(
					'Content-Type: application/x-www-form-urlencoded',
					'Accept: application/json',
					'X-Auth-Token: '.$result['data']['token']
				);
				$url = 'https://api.myperfit.com/v2/'.$result['data']['account'].'/'.ltrim($_path, '/');
				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
				if (!empty($_data)) {
					curl_setopt($curl, CURLOPT_POST, true);
					curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
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
				$result['http_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				curl_close($curl);
				$result = json_decode($response, true);
			}
		} catch (Exception $e) {
		}
		return $result;
	}
}
$ulp_perfit = new ulp_perfit_class();
?>