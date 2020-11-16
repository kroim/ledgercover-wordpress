<?php
/* StampReady integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_stampready_class {
	var $default_popup_options = array(
		"stampready_enable" => "off",
		"stampready_api_key" => "",
		"stampready_list" => "",
		"stampready_list_id" => ""
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-stampready-lists', array(&$this, "show_lists"));
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
				<h3>'.__('StampReady Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable StampReady', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_stampready_enable" name="ulp_stampready_enable" '.($popup_options['stampready_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to StampReady', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to StampReady.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Private API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_stampready_api_key" name="ulp_stampready_api_key" value="'.esc_html($popup_options['stampready_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your StampReady Private API Key. You can get it <a href="https://www.stampready.net/dashboard/account/settings/index.php" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-stampready-list" name="ulp_stampready_list" value="'.esc_html($popup_options['stampready_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_stampready_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-stampready-list-id" name="ulp_stampready_list_id" value="'.esc_html($popup_options['stampready_list_id']).'" />
							<div id="ulp-stampready-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List.', 'ulp').'</em>
							<script>
								function ulp_stampready_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-stampready-lists", "ulp_api_key": jQuery("#ulp_stampready_api_key").val()});
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
		if (isset($ulp->postdata["ulp_stampready_enable"])) $popup_options['stampready_enable'] = "on";
		else $popup_options['stampready_enable'] = "off";
		if ($popup_options['stampready_enable'] == 'on') {
			if (empty($popup_options['stampready_api_key'])) $errors[] = __('Invalid StampReady API Key.', 'ulp');
			if (empty($popup_options['stampready_list_id'])) $errors[] = __('Invalid StampReady List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_stampready_enable"])) $popup_options['stampready_enable'] = "on";
		else $popup_options['stampready_enable'] = "off";
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['stampready_enable'] == 'on') {
			$data = array('array' => array(array(
				$_subscriber['{subscription-email}'],
				$_subscriber['{subscription-name}'],
				'',
				$_SERVER['HTTP_REFERER'],
				'',
				$_SERVER['HTTP_USER_AGENT'],
				1
			)));
			$result = $this->connect($popup_options['stampready_api_key'], 'api.php?function=addSubscribers&list='.urlencode($popup_options['stampready_list']), $data);
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_api_key']) || empty($_POST['ulp_api_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_api_key']));
			
			$result = $this->connect($key, 'api.php?function=getLists');
			
			if (is_array($result)) {
				if (sizeof($result) > 0) {
					foreach ($result as $list) {
						if (is_array($list)) {
							if (array_key_exists('list id', $list) && array_key_exists('name', $list)) {
								$lists[$list['list id']] = $list['name'];
							}
						}
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No Lists found.', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key.', 'ulp').'</div>';
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
	function connect($_api_key, $_path, $_data = array(), $_method = '') {
		$url = 'https://www.stampready.net/api/3.0/'.$_path;
		if (strpos($url, '?') === false) $url .= '?';
		else $url .= '&';
		$url .= 'private_key='.$_api_key;
		try {
			$curl = curl_init($url);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
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
$ulp_stampready = new ulp_stampready_class();
?>