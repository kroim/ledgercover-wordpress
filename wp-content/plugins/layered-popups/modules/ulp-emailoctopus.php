<?php
/* EmailOctopus integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_emailoctopus_class {
	var $default_popup_options = array(
		"emailoctopus_enable" => "off",
		"emailoctopus_api_key" => "",
		"emailoctopus_list" => "",
		"emailoctopus_list_id" => "",
		"emailoctopus_first_name" => "{subscription-name}",
		"emailoctopus_last_name" => ""
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-emailoctopus-lists', array(&$this, "show_lists"));
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
				<h3>'.__('EmailOctopus Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable EmailOctopus', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_emailoctopus_enable" name="ulp_emailoctopus_enable" '.($popup_options['emailoctopus_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to EmailOctopus', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to EmailOctopus.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_emailoctopus_api_key" name="ulp_emailoctopus_api_key" value="'.esc_html($popup_options['emailoctopus_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your EmailOctopus API Key. You can get it <a href="https://emailoctopus.com/api-documentation/" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-emailoctopus-list" name="ulp_emailoctopus_list" value="'.esc_html($popup_options['emailoctopus_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_emailoctopus_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-emailoctopus-list-id" name="ulp_emailoctopus_list_id" value="'.esc_html($popup_options['emailoctopus_list_id']).'" />
							<div id="ulp-emailoctopus-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_emailoctopus_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-emailoctopus-lists", "ulp_api_key": jQuery("#ulp_emailoctopus_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-emailoctopus-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate EmailOctopus fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">
									<tr>
										<td style="width: 100px;"><strong>'.__('First name', 'ulp').':</strong></td>
										<td>
											<input type="text" id="ulp_emailoctopus_first_name" name="ulp_emailoctopus_first_name" value="'.esc_html($popup_options['emailoctopus_first_name']).'" class="widefat">
											<br /><em>'.__('Enter first name of the contact.', 'ulp').'</em>
										</td>
									</tr>
									<tr>
										<td style="width: 100px;"><strong>'.__('Last name', 'ulp').':</strong></td>
										<td>
											<input type="text" id="ulp_emailoctopus_last_name" name="ulp_emailoctopus_last_name" value="'.esc_html($popup_options['emailoctopus_last_name']).'" class="widefat">
											<br /><em>'.__('Enter last name of the contact.', 'ulp').'</em>
										</td>
									</tr>
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
		if (isset($ulp->postdata["ulp_emailoctopus_enable"])) $popup_options['emailoctopus_enable'] = "on";
		else $popup_options['emailoctopus_enable'] = "off";
		if ($popup_options['emailoctopus_enable'] == 'on') {
			if (empty($popup_options['emailoctopus_api_key'])) $errors[] = __('Invalid EmailOctopus API Key.', 'ulp');
			if (empty($popup_options['emailoctopus_list_id'])) $errors[] = __('Invalid EmailOctopus List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_emailoctopus_enable"])) $popup_options['emailoctopus_enable'] = "on";
		else $popup_options['emailoctopus_enable'] = "off";
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['emailoctopus_enable'] == 'on') {
			$data = array(
				'email_address' => $_subscriber['{subscription-email}'],
				'first_name' => strtr($popup_options['emailoctopus_first_name'], $_subscriber),
				'last_name' => strtr($popup_options['emailoctopus_last_name'], $_subscriber),
				'subscribed' => true
			);
			$result = $this->connect($popup_options['emailoctopus_api_key'], 'lists/'.urlencode($popup_options['emailoctopus_list_id']).'/contacts', $data);
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
			
			$result = $this->connect($key, 'lists');
			
			if (is_array($result) && array_key_exists('data', $result)) {
				if (intval($result['data']) > 0) {
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
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key!', 'ulp').'</div>';
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
	function connect($_api_key, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		if (empty($_data)) {
			if (strpos($_path, '?') === false) $_path .= '?';
			else $_path .= '&';
			$_path .= 'api_key='.urlencode($_api_key);
		} else {
			$_data['api_key'] = $_api_key;
		}
		try {
			$url = 'https://emailoctopus.com/api/1.1/'.ltrim($_path, '/');
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
$ulp_emailoctopus = new ulp_emailoctopus_class();
?>