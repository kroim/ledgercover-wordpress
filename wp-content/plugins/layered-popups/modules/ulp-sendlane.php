<?php
/* Sendlane integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_sendlane_class {
	var $default_popup_options = array(
		"sendlane_enable" => "off",
		"sendlane_url" => "",
		"sendlane_api_key" => "",
		"sendlane_hash" => "",
		"sendlane_list" => "",
		"sendlane_list_id" => ""
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-sendlane-lists', array(&$this, "show_lists"));
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
				<h3>'.__('Sendlane Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Sendlane', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_sendlane_enable" name="ulp_sendlane_enable" '.($popup_options['sendlane_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Sendlane', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Sendlane.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Site URL', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_sendlane_url" name="ulp_sendlane_url" value="'.esc_html($popup_options['sendlane_url']).'" class="widefat">
							<br /><em>'.__('Enter unique website address of your account. Usually it looks like SITE-NAME.sendlane.com', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_sendlane_api_key" name="ulp_sendlane_api_key" value="'.esc_html($popup_options['sendlane_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Sendlane API Key. Go to your Sendlane account, click your account settings, scroll down and you will see a section titled "Security Credentials".', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Hash Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_sendlane_hash" name="ulp_sendlane_hash" value="'.esc_html($popup_options['sendlane_hash']).'" class="widefat">
							<br /><em>'.__('Enter your Sendlane Hash Key. Go to your Sendlane account, click your account settings, scroll down and you will see a section titled "Security Credentials".', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID:', 'ulp').'</th>
						<td>
							<input type="text" id="ulp-sendlane-list" name="ulp_sendlane_list" value="'.esc_html($popup_options['sendlane_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_sendlane_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-sendlane-list-id" name="ulp_sendlane_list_id" value="'.esc_html($popup_options['sendlane_list_id']).'" />
							<div id="ulp-sendlane-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_sendlane_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-sendlane-lists", "ulp_url": jQuery("#ulp_sendlane_url").val(), "ulp_api_key": jQuery("#ulp_sendlane_api_key").val(), "ulp_hash": jQuery("#ulp_sendlane_hash").val()});
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
		if (isset($ulp->postdata["ulp_sendlane_enable"])) $popup_options['sendlane_enable'] = "on";
		else $popup_options['sendlane_enable'] = "off";
		if ($popup_options['sendlane_enable'] == 'on') {
			if (strlen($popup_options['sendlane_url']) == 0 || !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $popup_options['sendlane_url'])) $errors[] = __('Sendlane Site URL must be a valid URL.', 'ulp');
			if (empty($popup_options['sendlane_api_key'])) $errors[] = __('Invalid Sendlane API Key.', 'ulp');
			if (empty($popup_options['sendlane_hash'])) $errors[] = __('Invalid Sendlane Hash Key.', 'ulp');
			if (empty($popup_options['sendlane_list_id'])) $errors[] = __('Invalid Sendlane List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_sendlane_enable"])) $popup_options['sendlane_enable'] = "on";
		else $popup_options['sendlane_enable'] = "off";
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['sendlane_enable'] == 'on') {
			$sendlane_url = rtrim($popup_options['sendlane_url'], '/').'/api/v1/list-subscribers-add';
			$data = array(
				'api' => $popup_options['sendlane_api_key'],
				'hash' => $popup_options['sendlane_hash'],
				'list_id' => $popup_options['sendlane_list_id'],
				'email' => (!empty($_subscriber['{subscription-name}']) ? str_replace(array('<', '>'), array('', ''), $_subscriber['{subscription-name}']) : '').'<'.$_subscriber['{subscription-email}'].'>'
			);
			try {
				$curl = curl_init($sendlane_url);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
				//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				$response = curl_exec($curl);
				curl_close($curl);
			} catch (Exception $e) {
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_url']) || !isset($_POST['ulp_api_key']) || !isset($_POST['ulp_hash']) || empty($_POST['ulp_url']) || empty($_POST['ulp_api_key']) || empty($_POST['ulp_hash'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Site URL, API Key or Hash Key!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$sendlane_url = trim(stripslashes($_POST['ulp_url']));
			$api_key = trim(stripslashes($_POST['ulp_api_key']));
			$hash = trim(stripslashes($_POST['ulp_hash']));
			
			if (!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $sendlane_url)) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Site URL!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$sendlane_url = rtrim($sendlane_url, '/').'/api/v1/lists';
			$data = array(
				'api' => $api_key,
				'hash' => $hash,
				'limit' => 100
			);
			$lists = array();
			try {
				$curl = curl_init($sendlane_url);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
				//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				$response = curl_exec($curl);
				$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				curl_close($curl);
				if ($http_code != 200) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to Sendlane Site URL!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}

				$result = json_decode($response, true);
				if ($result) {
					if (array_key_exists("info", $result)) {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.$result['info']['messages'].'</div>';
						echo json_encode($return_object);
						exit;
					} else if (array_key_exists("error", $result)) {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.reset($result['error']).'</div>';
						echo json_encode($return_object);
						exit;
					}
					foreach($result as $list) {
						if (is_array($list)) {
							if (array_key_exists('list_id', $list) && array_key_exists('list_name', $list)) {
								$lists[$list['list_id']] = $list['list_name'];
							}
						}
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to Sendlane Site URL!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} catch (Exception $e) {
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
}
$ulp_sendlane = new ulp_sendlane_class();
?>