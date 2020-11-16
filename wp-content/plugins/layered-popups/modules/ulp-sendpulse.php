<?php
/* SendPulse integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_sendpulse_class {
	var $default_popup_options = array(
		"sendpulse_enable" => "off",
		"sendpulse_client_id" => "",
		"sendpulse_client_secret" => "",
		"sendpulse_list" => "",
		"sendpulse_list_id" => "",
		"sendpulse_fields" => ""
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-sendpulse-lists', array(&$this, "show_lists"));
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
		$fields = unserialize($popup_options['sendpulse_fields']);
		echo '
				<h3>'.__('SendPulse Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable SendPulse', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_sendpulse_enable" name="ulp_sendpulse_enable" '.($popup_options['sendpulse_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to SendPulse', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to SendPulse using REST API.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('SendPulse ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_sendpulse_client_id" name="ulp_sendpulse_client_id" value="'.esc_html($popup_options['sendpulse_client_id']).'" class="widefat">
							<br /><em>'.__('Enter your SendPulse ID (for REST API). You can get it <a href="https://login.sendpulse.com/settings/" target="_blank">here</a> (API tab).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('SendPulse Secret ', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_sendpulse_client_secret" name="ulp_sendpulse_client_secret" value="'.esc_html($popup_options['sendpulse_client_secret']).'" class="widefat">
							<br /><em>'.__('Enter your SendPulse Secret (for REST API). You can get it <a href="https://login.sendpulse.com/settings/" target="_blank">here</a> (API tab).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Address Book', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-sendpulse-list" name="ulp_sendpulse_list" value="'.esc_html($popup_options['sendpulse_list']).'" class="ulp-input-options" readonly="readonly" onfocus="ulp_sendpulse_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-sendpulse-list-id" name="ulp_sendpulse_list_id" value="'.esc_html($popup_options['sendpulse_list_id']).'" />
							<div id="ulp-sendpulse-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your Address Book.', 'ulp').'</em>
							<script>
								function ulp_sendpulse_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-sendpulse-lists", "ulp_client_id": jQuery("#ulp_sendpulse_client_id").val(), "ulp_client_secret": jQuery("#ulp_sendpulse_client_secret").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields (Columns)', 'ulp').':</th>
						<td>
							'.__('Please adjust the fields/columns below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate SendPulse fields with the popup fields.', 'ulp').'
							<table style="width: 100%;">
								<tr>
									<td style="width: 200px;"><strong>'.__('Name', 'ulp').'</strong></td>
									<td><strong>'.__('Value', 'ulp').'</strong></td>
								</tr>';
		$i = 0;
		if (is_array($fields)) {
			foreach ($fields as $key => $value) {
				echo '									
								<tr>
									<td>
										<input type="text" name="ulp_sendpulse_fields_name[]" value="'.esc_html($key).'" class="widefat">
										<br /><em>'.($i > 0 ? '<a href="#" onclick="return ulp_sendpulse_remove_field(this);">'.__('Remove Field', 'ulp').'</a>' : '').'</em>
									</td>
									<td>
										<input type="text" name="ulp_sendpulse_fields_value[]" value="'.esc_html($value).'" class="widefat">
									</td>
								</tr>';
				$i++;
			}
		}
		if ($i == 0) {
			echo '									
								<tr>
									<td>
										<input type="text" name="ulp_sendpulse_fields_name[]" value="" class="widefat">
									</td>
									<td>
										<input type="text" name="ulp_sendpulse_fields_value[]" value="" class="widefat">
									</td>
								</tr>';
		}
		echo '
								<tr style="display: none;" id="sendpulse-field-template">
									<td>
										<input type="text" name="ulp_sendpulse_fields_name[]" value="" class="widefat">
										<br /><em><a href="#" onclick="return ulp_sendpulse_remove_field(this);">'.__('Remove Field', 'ulp').'</a></em>
									</td>
									<td>
										<input type="text" name="ulp_sendpulse_fields_value[]" value="" class="widefat">
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<a href="#" class="button-secondary" onclick="return ulp_sendpulse_add_field(this);">'.__('Add Field', 'ulp').'</a>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
				<script>
					function ulp_sendpulse_add_field(object) {
						jQuery("#sendpulse-field-template").before("<tr>"+jQuery("#sendpulse-field-template").html()+"</tr>");
						return false;
					}
					function ulp_sendpulse_remove_field(object) {
						var row = jQuery(object).parentsUntil("tr").parent();
						jQuery(row).fadeOut(300, function() {
							jQuery(row).remove();
						});
						return false;
					}
				</script>';
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
		if (isset($ulp->postdata["ulp_sendpulse_enable"])) $popup_options['sendpulse_enable'] = "on";
		else $popup_options['sendpulse_enable'] = "off";
		if ($popup_options['sendpulse_enable'] == 'on') {
			if (empty($popup_options['sendpulse_client_id'])) $errors[] = __('Invalid SendPulse ID.', 'ulp');
			if (empty($popup_options['sendpulse_client_secret'])) $errors[] = __('Invalid SendPulse Secret.', 'ulp');
			if (empty($popup_options['sendpulse_list_id'])) $errors[] = __('Invalid SendPulse Address Book.', 'ulp');
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
		if (isset($ulp->postdata["ulp_sendpulse_enable"])) $popup_options['sendpulse_enable'] = "on";
		else $popup_options['sendpulse_enable'] = "off";
		
		if (is_array($ulp->postdata["ulp_sendpulse_fields_name"]) && is_array($ulp->postdata["ulp_sendpulse_fields_value"])) {
			$fields = array();
			for($i=0; $i<sizeof($ulp->postdata["ulp_sendpulse_fields_name"]); $i++) {
				$key = stripslashes(trim($ulp->postdata['ulp_sendpulse_fields_name'][$i]));
				$value = stripslashes(trim($ulp->postdata['ulp_sendpulse_fields_value'][$i]));
				if (!empty($key)) $fields[$key] = $value;
			}
			if (!empty($fields)) $popup_options['sendpulse_fields'] = serialize($fields);
			else $popup_options['sendpulse_fields'] = '';
		} else $popup_options['sendpulse_fields'] = '';
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['sendpulse_enable'] == 'on') {
			$data = array(
				'client_id' => $popup_options['sendpulse_client_id'],
				'client_secret' => $popup_options['sendpulse_client_secret'],
				'grant_type' => 'client_credentials'
			);
			try {
				$curl = curl_init('https://api.sendpulse.com/oauth/access_token');
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
				curl_setopt($curl, CURLOPT_TIMEOUT, 30);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
				//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				$response = curl_exec($curl);
				curl_close($curl);
				$json = json_decode($response, true);
				if ($json && is_array($json) && array_key_exists('access_token', $json)){
					$header = array(
						'Authorization: Bearer '.$json['access_token']
					);
					$contact_details = array(
						'email' => $_subscriber['{subscription-email}']
					);
					$fields = unserialize($popup_options['sendpulse_fields']);
					if (is_array($fields)) {
						foreach ($fields as $key => $value) {
							$contact_details['variables'][$key] = strtr($value, $_subscriber);
						}
					}
					$data = array(
						'emails' => serialize(array($contact_details))
					);
					$curl = curl_init('https://api.sendpulse.com/addressbooks/'.$popup_options['sendpulse_list_id'].'/emails');
					curl_setopt($curl, CURLOPT_POST, true);
					curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
					curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
					curl_setopt($curl, CURLOPT_TIMEOUT, 30);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
					curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
					//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
					curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
					$response = curl_exec($curl);
					curl_close($curl);
				}
			} catch (Exception $e) {
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_client_id']) || !isset($_POST['ulp_client_secret']) || empty($_POST['ulp_client_id']) || empty($_POST['ulp_client_secret'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid ID or Secret!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$client_id = trim(stripslashes($_POST['ulp_client_id']));
			$client_secret = trim(stripslashes($_POST['ulp_client_secret']));
			$data = array(
				'client_id' => $client_id,
				'client_secret' => $client_secret,
				'grant_type' => 'client_credentials'
			);
			$list_html = '';
			$lists = array();
			try {
				$curl = curl_init('https://api.sendpulse.com/oauth/access_token');
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
				curl_setopt($curl, CURLOPT_TIMEOUT, 30);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
				//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				$response = curl_exec($curl);
				curl_close($curl);
				$json = json_decode($response, true);
				if ($json && is_array($json) && array_key_exists('access_token', $json)){
					$header = array(
						'Authorization: Bearer '.$json['access_token']
					);
					$curl = curl_init('https://api.sendpulse.com/addressbooks');
					curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
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
					if ($result && is_array($result)) $lists = $result;
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid ID or Secret!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} catch (Exception $e) {
			}
			if (!empty($lists)) {
				foreach ($lists as $list) {
					$list_html .= '<a href="#" data-id="'.esc_html($list['id']).'" data-title="'.esc_html($list['id']).(!empty($list['name']) ? ' | '.esc_html($list['name']) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($list['id']).(!empty($list['name']) ? ' | '.esc_html($list['name']) : '').'</a>';
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
$ulp_sendpulse = new ulp_sendpulse_class();
?>