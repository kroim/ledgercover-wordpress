<?php
/* Emma integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_emma_class {
	var $default_popup_options = array(
		"emma_enable" => "off",
		"emma_public_api_key" => "",
		"emma_private_api_key" => "",
		"emma_account_id" => "",
		"emma_groups" => "",
		"emma_fields" => ""
	);
	function __construct() {
		$this->default_popup_options['emma_fields'] = serialize(array('first_name' => '{subscription-name}'));
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-emma-groups', array(&$this, "show_groups"));
			add_action('wp_ajax_ulp-emma-fields', array(&$this, "show_fields"));
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
				<h3>'.__('Emma Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Emma', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_emma_enable" name="ulp_emma_enable" '.($popup_options['emma_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Emma', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Emma.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Public API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_emma_public_api_key" name="ulp_emma_public_api_key" value="'.esc_html($popup_options['emma_public_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Emma Public API Key. You can find Public API key inside your Emma account in the "Account Settings" section.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Private API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_emma_private_api_key" name="ulp_emma_private_api_key" value="'.esc_html($popup_options['emma_private_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Emma Private API Key. You can find Private API key inside your Emma account in the "Account Settings" section.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Account ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_emma_account_id" name="ulp_emma_account_id" value="'.esc_html($popup_options['emma_account_id']).'" class="widefat">
							<br /><em>'.__('Enter your Emma Account ID. You can find Account ID inside your Emma account in the "Account Settings" section.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-emma-fields-html">';
		if (!empty($popup_options['emma_public_api_key']) && !empty($popup_options['emma_private_api_key']) && !empty($popup_options['emma_account_id'])) {
			$fields = $this->get_fields_html($popup_options['emma_public_api_key'], $popup_options['emma_private_api_key'], $popup_options['emma_account_id'], $popup_options['emma_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_emma_fields_button" class="ulp_button button-secondary" onclick="return ulp_emma_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-emma-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_emma_loadfields() {
									jQuery("#ulp-emma-fields-loading").fadeIn(350);
									jQuery(".ulp-emma-fields-html").slideUp(350);
									var data = {action: "ulp-emma-fields", ulp_public_key: jQuery("#ulp_emma_public_api_key").val(), ulp_private_key: jQuery("#ulp_emma_private_api_key").val(), ulp_account: jQuery("#ulp_emma_account_id").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-emma-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-emma-fields-html").html(data.html);
												jQuery(".ulp-emma-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-emma-fields-html").html("<div class=\'ulp-emma-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Emma server.', 'ulp').'</strong></div>");
												jQuery(".ulp-emma-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-emma-fields-html").html("<div class=\'ulp-emma-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Emma server.', 'ulp').'</strong></div>");
											jQuery(".ulp-emma-fields-html").slideDown(350);
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Groups', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-emma-groups-html">';
		if (!empty($popup_options['emma_public_api_key']) && !empty($popup_options['emma_private_api_key']) && !empty($popup_options['emma_account_id'])) {
			$groups = $this->get_groups_html($popup_options['emma_public_api_key'], $popup_options['emma_private_api_key'], $popup_options['emma_account_id'], $popup_options['emma_groups']);
			echo $groups;
		}
		echo '
							</div>
							<a id="ulp_emma_groups_button" class="ulp_button button-secondary" onclick="return ulp_emma_loadgroups();">'.__('Load Groups', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-emma-groups-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load groups.', 'ulp').'</em>
							<script>
								function ulp_emma_loadgroups() {
									jQuery("#ulp-emma-groups-loading").fadeIn(350);
									jQuery(".ulp-emma-groups-html").slideUp(350);
									var data = {action: "ulp-emma-groups", ulp_public_key: jQuery("#ulp_emma_public_api_key").val(), ulp_private_key: jQuery("#ulp_emma_private_api_key").val(), ulp_account: jQuery("#ulp_emma_account_id").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-emma-groups-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-emma-groups-html").html(data.html);
												jQuery(".ulp-emma-groups-html").slideDown(350);
											} else {
												jQuery(".ulp-emma-groups-html").html("<div class=\'ulp-emma-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Emma server.', 'ulp').'</strong></div>");
												jQuery(".ulp-emma-groups-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-emma-groups-html").html("<div class=\'ulp-emma-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Emma server.', 'ulp').'</strong></div>");
											jQuery(".ulp-emma-groups-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_emma_enable"])) $popup_options['emma_enable'] = "on";
		else $popup_options['emma_enable'] = "off";
		if ($popup_options['emma_enable'] == 'on') {
			if (empty($popup_options['emma_public_api_key'])) $errors[] = __('Invalid Emma Public API Key.', 'ulp');
			if (empty($popup_options['emma_private_api_key'])) $errors[] = __('Invalid Emma Private API Key.', 'ulp');
			if (empty($popup_options['emma_account_id'])) $errors[] = __('Invalid Emma Account ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_emma_enable"])) $popup_options['emma_enable'] = "on";
		else $popup_options['emma_enable'] = "off";
		
		$groups = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_emma_group_')) == 'ulp_emma_group_') {
				$groups[] = substr($key, strlen('ulp_emma_group_'));
			}
		}
		$popup_options['emma_groups'] = implode(':', $groups);

		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_emma_field_')) == 'ulp_emma_field_') {
				$field = substr($key, strlen('ulp_emma_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['emma_fields'] = serialize($fields);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['emma_enable'] == 'on') {
			$emma_url = 'https://api.e2ma.net/'.$popup_options['emma_account_id'].'/members/add';
		
			$groups = array();
			if (!empty($popup_options['emma_groups'])) {
				$groups = explode(':', $popup_options['emma_groups']);
			}
			$data = array(
				'email' => $_subscriber['{subscription-email}'],
				'group_ids' => $groups
			);
			
			$fields = array();
			if (!empty($popup_options['emma_fields'])) $fields = unserialize($popup_options['emma_fields']);
			if (!empty($fields) && is_array($fields)) {
				foreach ($fields as $key => $value) {
					if (!empty($value)) {
						$data['fields'][$key] = strtr($value, $_subscriber);
					}
				}
			}
			try {
				$curl = curl_init($emma_url);
				curl_setopt($curl, CURLOPT_USERPWD, $popup_options['emma_public_api_key'].":".$popup_options['emma_private_api_key']);
				curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
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
	function show_groups() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_public_key']) || !isset($_POST['ulp_private_key']) || !isset($_POST['ulp_account']) || empty($_POST['ulp_public_key']) || empty($_POST['ulp_private_key']) || empty($_POST['ulp_account'])) exit;
			$public_key = trim(stripslashes($_POST['ulp_public_key']));
			$private_key = trim(stripslashes($_POST['ulp_private_key']));
			$account = trim(stripslashes($_POST['ulp_account']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_groups_html($public_key, $private_key, $account, '');
			echo json_encode($return_object);
		}
		exit;
	}
	function get_groups_html($_public_key, $_private_key, $_account, $_groups) {
		$result = $this->get_groups($_public_key, $_private_key, $_account);
		$groups = '';
		$groups_marked = explode(':', $_groups);
		if (!empty($result)) {
			foreach ($result as $group) {
				$groups .= '<div class="ulp-emma-group" style="margin: 1px 0;"><input type="checkbox" name="ulp_emma_group_'.$group['member_group_id'].'"'.(in_array($group['member_group_id'], $groups_marked) ? ' checked="checked"' : '').' /> '.esc_html($group['group_name']).'</div>';
			}
		} else {
			$groups = '<div class="ulp-emma-grouping" style="margin-bottom: 10px;"><strong>'.__('No groups found.', 'ulp').'</strong></div>';
		}
		return $groups;
	}
	function get_groups($_public_key, $_private_key, $_account) {
		$result = array();
		$emma_url = 'https://api.e2ma.net/'.$_account.'/groups?group_types=all';
		try {
			$curl = curl_init($emma_url);
			curl_setopt($curl, CURLOPT_USERPWD, $_public_key.":".$_private_key);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
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
			if ($http_code == 200) $result = json_decode($response, true);
		} catch (Exception $e) {
		}
		return $result;
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_public_key']) || !isset($_POST['ulp_private_key']) || !isset($_POST['ulp_account']) || empty($_POST['ulp_public_key']) || empty($_POST['ulp_private_key']) || empty($_POST['ulp_account'])) exit;
			$public_key = trim(stripslashes($_POST['ulp_public_key']));
			$private_key = trim(stripslashes($_POST['ulp_private_key']));
			$account = trim(stripslashes($_POST['ulp_account']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($public_key, $private_key, $account, $this->default_popup_options['emma_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_public_key, $_private_key, $_account, $_fields) {
		$result = $this->get_fields($_public_key, $_private_key, $_account);
		$fields = '';
		$values = unserialize($_fields);
		if (!is_array($values)) $values = array();
		if (!empty($result)) {
			$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Emma fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
			foreach ($result as $field) {
				if (is_array($field)) {
					if (array_key_exists('shortcut_name', $field) && array_key_exists('display_name', $field)) {
						$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['shortcut_name']).':</strong></td>
					<td>
						<input type="text" id="ulp_emma_field_'.esc_html($field['shortcut_name']).'" name="ulp_emma_field_'.esc_html($field['shortcut_name']).'" value="'.esc_html(array_key_exists($field['shortcut_name'], $values) ? $values[$field['shortcut_name']] : '').'" class="widefat" />
						<br /><em>'.esc_html($field['display_name']).'</em>
					</td>
				</tr>';
					}
				}
			}
			$fields .= '
			</table>';
		} else {
			$fields = '<div class="ulp-emma-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function get_fields($_public_key, $_private_key, $_account) {
		$result = array();
		$emma_url = 'https://api.e2ma.net/'.$_account.'/fields';
		try {
			$curl = curl_init($emma_url);
			curl_setopt($curl, CURLOPT_USERPWD, $_public_key.":".$_private_key);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
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
			if ($http_code == 200) $result = json_decode($response, true);
		} catch (Exception $e) {
		}
		return $result;
	}
}
$ulp_emma = new ulp_emma_class();
?>