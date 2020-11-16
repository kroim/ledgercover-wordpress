<?php
/* SendFox integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_sendfox_class {
	var $default_popup_options = array(
		"sendfox_enable" => "off",
		"sendfox_api_token" => "",
		"sendfox_list" => "",
		"sendfox_list_id" => "",
		"sendfox_fields" => array(
			"email" => "{subscription-email}",
			"first_name" => "{subscription-name}",
			"last_name" => ""
		)
	);
	var $fields_meta;
	function __construct() {
		$this->fields_meta = array(
			'email' => __('Email address', 'ulp'),
			'first_name' => __('First name', 'ulp'),
			'last_name' => __('Last name', 'ulp')
		);
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-sendfox-lists', array(&$this, "show_lists"));
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
				<h3>'.__('SendFox Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable SendFox', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_sendfox_enable" name="ulp_sendfox_enable" '.($popup_options['sendfox_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to SendFox', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to SendFox.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Personal Access Token', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_sendfox_api_token" name="ulp_sendfox_api_token" value="'.esc_html($popup_options['sendfox_api_token']).'" class="widefat">
							<br /><em>'.__('Enter your SendFox Personal Access Token. You can get it <a href="https://sendfox.com/account/oauth" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-sendfox-list" name="ulp_sendfox_list" value="'.esc_html($popup_options['sendfox_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_sendfox_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-sendfox-list-id" name="ulp_sendfox_list_id" value="'.esc_html($popup_options['sendfox_list_id']).'" />
							<div id="ulp-sendfox-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_sendfox_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-sendfox-lists", "ulp_api_token": jQuery("#ulp_sendfox_api_token").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-sendfox-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate SendFox fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
		foreach ($this->default_popup_options['sendfox_fields'] as $key => $value) {
			echo '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($this->fields_meta[$key]).':</strong></td>
					<td>
						<input type="text" id="ulp_sendfox_field_'.esc_html($key).'" name="ulp_sendfox_field_'.esc_html($key).'" value="'.esc_html(array_key_exists($key, $popup_options['sendfox_fields']) ? $popup_options['sendfox_fields'][$key] : $value).'" class="widefat"'.($key == 'email' ? ' readonly="readonly"' : '').' />
						<br /><em>'.esc_html($this->fields_meta[$key].' ('.$key.')').'</em>
					</td>
				</tr>';
		}
		echo '
								</table>
							</div>
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
		if (isset($ulp->postdata["ulp_sendfox_enable"])) $popup_options['sendfox_enable'] = "on";
		else $popup_options['sendfox_enable'] = "off";
		if ($popup_options['sendfox_enable'] == 'on') {
			if (empty($popup_options['sendfox_api_token'])) $errors[] = __('Invalid SendFox Personal Access Token.', 'ulp');
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
		if (isset($ulp->postdata["ulp_sendfox_enable"])) $popup_options['sendfox_enable'] = "on";
		else $popup_options['sendfox_enable'] = "off";
		
		$popup_options['sendfox_fields'] = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_sendfox_field_')) == 'ulp_sendfox_field_') {
				$key = substr($key, strlen('ulp_sendfox_field_'));
				$popup_options['sendfox_fields'][$key] = stripslashes(trim($value));
			}
		}
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['sendfox_enable'] == 'on') {
			$post_data = array(
				'ip_address' => $_SERVER['REMOTE_ADDR'],
				'email' => $_subscriber['{subscription-email}']
			);
			foreach($popup_options['sendfox_fields'] as $key => $value) {
				if (!empty($value) && $key != 'email') $post_data[$key] = strtr($value, $_subscriber);
			}
			if (!empty($popup_options['sendfox_list_id']) && $popup_options['sendfox_list_id'] != 0) $post_data['lists'] = array($popup_options['sendfox_list_id']);
			$result = $this->connect($popup_options['sendfox_api_token'], 'contacts', $post_data);
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array('0' => 'None');
			if (!isset($_POST['ulp_api_token']) || empty($_POST['ulp_api_token'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Personal Access Token.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$token = trim(stripslashes($_POST['ulp_api_token']));
			
			$result = $this->connect($token, 'lists');

			if (is_array($result)) {
				if (array_key_exists('data', $result)) {
					if (sizeof($result['data']) > 0) {
						foreach ($result['data'] as $list) {
							if (is_array($list)) {
								if (array_key_exists('id', $list) && array_key_exists('name', $list)) {
									$lists[$list['id']] = $list['name'];
								}
							}
						}
					}
				} else {
					$return_object = array('status' => 'OK', 'html' => '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Personal Access Token.', 'ulp').'</div>');
					echo json_encode($return_object);
					exit;
				}
			} else {
				$return_object = array('status' => 'OK', 'html' => '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response.', 'ulp').'</div>');
				echo json_encode($return_object);
				exit;
			}
			$list_html = '';
			if (!empty($lists)) {
				foreach ($lists as $id => $name) {
					$list_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
				}
			} else $list_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No lists found.', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $list_html;
			$return_object['items'] = sizeof($lists);
			echo json_encode($return_object);
		}
		exit;
	}
	function connect($_access_token, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Authorization: Bearer '.$_access_token,
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		try {
			$url = 'https://api.sendfox.com/'.ltrim($_path, '/');
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
$ulp_sendfox = new ulp_sendfox_class();
?>