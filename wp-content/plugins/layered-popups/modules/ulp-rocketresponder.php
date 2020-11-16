<?php
/* RocketResponder integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_rocketresponder_class {
	var $default_popup_options = array(
		"rocketresponder_enable" => "off",
		"rocketresponder_api_public" => "",
		"rocketresponder_api_private" => "",
		"rocketresponder_list" => "",
		"rocketresponder_list_id" => ""
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-rocketresponder-lists', array(&$this, "show_lists"));
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
				<h3>'.__('RocketResponder Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable RocketResponder', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_rocketresponder_enable" name="ulp_rocketresponder_enable" '.($popup_options['rocketresponder_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to RocketResponder', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to RocketResponder.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Public API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_rocketresponder_api_public" name="ulp_rocketresponder_api_public" value="'.esc_html($popup_options['rocketresponder_api_public']).'" class="widefat">
							<br /><em>'.__('Enter your RocketResponder Public API Key. You can get it <a href="https://www.rocketresponder.com/launchpad/settings.php" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Private API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_rocketresponder_api_private" name="ulp_rocketresponder_api_private" value="'.esc_html($popup_options['rocketresponder_api_private']).'" class="widefat">
							<br /><em>'.__('Enter your RocketResponder Private API Key. You can get it <a href="https://www.rocketresponder.com/launchpad/settings.php" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID:', 'ulp').'</th>
						<td>
							<input type="text" id="ulp-rocketresponder-list" name="ulp_rocketresponder_list" value="'.esc_html($popup_options['rocketresponder_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_rocketresponder_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-rocketresponder-list-id" name="ulp_rocketresponder_list_id" value="'.esc_html($popup_options['rocketresponder_list_id']).'" />
							<div id="ulp-rocketresponder-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_rocketresponder_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-rocketresponder-lists", "ulp_api_public": jQuery("#ulp_rocketresponder_api_public").val(), "ulp_api_private": jQuery("#ulp_rocketresponder_api_private").val()});
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
		if (isset($ulp->postdata["ulp_rocketresponder_enable"])) $popup_options['rocketresponder_enable'] = "on";
		else $popup_options['rocketresponder_enable'] = "off";
		if ($popup_options['rocketresponder_enable'] == 'on') {
			if (empty($popup_options['rocketresponder_api_public'])) $errors[] = __('Invalid RocketResponder API Username.', 'ulp');
			if (empty($popup_options['rocketresponder_api_private'])) $errors[] = __('Invalid RocketResponder API Password.', 'ulp');
			if (empty($popup_options['rocketresponder_list_id'])) $errors[] = __('Invalid RocketResponder List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_rocketresponder_enable"])) $popup_options['rocketresponder_enable'] = "on";
		else $popup_options['rocketresponder_enable'] = "off";
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['rocketresponder_enable'] == 'on') {
			$data = array(
				'LID' => $popup_options['rocketresponder_list_id'],
				'email' => $_subscriber['{subscription-email}']
			);
			$result = $this->connect($popup_options['rocketresponder_api_public'], $popup_options['rocketresponder_api_private'], '/subscriber/lookup', $data);
			if ($result && !array_key_exists("error", $result)) {
				$data['name'] = $_subscriber['{subscription-name}'];
				if (empty($result['subscriber']['ID'])) {
					$result = $this->connect($popup_options['rocketresponder_api_public'], $popup_options['rocketresponder_api_private'], '/subscriber/subscribe', $data);
				} else {
					$data['newemail'] = $_subscriber['{subscription-email}'];
					$result = $this->connect($popup_options['rocketresponder_api_public'], $popup_options['rocketresponder_api_private'], '/subscriber/modify', $data);
				}
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_api_public']) || !isset($_POST['ulp_api_private']) || empty($_POST['ulp_api_public']) || empty($_POST['ulp_api_private'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Public or Private API Key!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$api_public = trim(stripslashes($_POST['ulp_api_public']));
			$api_private = trim(stripslashes($_POST['ulp_api_private']));

			$lists = array();
			$result = $this->connect($api_public, $api_private, '/list/all');
			if ($result) {
				if (array_key_exists("error", $result)) {
					$message = explode('|', $result['error']);
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.esc_html($message[0]).'</div>';
					echo json_encode($return_object);
					exit;
				}
				
				if (!array_key_exists("list", $result) || !is_array($result['list']) || sizeof($result['list']) == 0) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No lists found!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
				foreach($result['list'] as $list) {
					if (is_array($list)) {
						if (array_key_exists('LID', $list) && array_key_exists('Name', $list)) {
							$lists[$list['LID']] = $list['Name'];
						}
					}
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to RocketResponder server!', 'ulp').'</div>';
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
	function connect($_api_public, $_api_private, $_path, $_data = array(), $_method = '') {
		try {
			$url = 'https://www.rocketresponder.com/api/'.ltrim($_path, '/');
			
			$_data["Time"] = time();
			$_data = array_map('strval', $_data);
			array_multisort($_data, SORT_ASC, SORT_STRING);
			$hash = md5(json_encode($_data));
			$signature = md5($_api_private.$url.$hash);

			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_USERPWD, $_api_public.':'.$signature);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
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
			$result = json_decode($response, true);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
	
}
$ulp_rocketresponder = new ulp_rocketresponder_class();
?>