<?php
/* Mailgun integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_mailgun_class {
	var $default_popup_options = array(
		"mailgun_enable" => "off",
		"mailgun_api_key" => "",
		"mailgun_region" => "us",
		"mailgun_list" => "",
		"mailgun_list_id" => "",
		"mailgun_custom_fields" => array()
	);
	var $fields_meta;
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-mailgun-lists', array(&$this, "show_lists"));
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
				<h3>'.__('Mailgun Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Mailgun', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mailgun_enable" name="ulp_mailgun_enable" '.($popup_options['mailgun_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Mailgun', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Mailgun.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Private API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mailgun_api_key" name="ulp_mailgun_api_key" value="'.esc_html($popup_options['mailgun_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Mailgun Private API Key. You can get it <a href="https://app.mailgun.com/app/account/security/api_keys" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Region', 'ulp').':</th>
						<td>
							<select id="ulp_mailgun_region" name="ulp_mailgun_region" class="ic_input_m">
								<option value="us"'.($popup_options['mailgun_region'] == 'us' ? ' selected="selected"' : '').'>US</option>
								<option value="eu"'.($popup_options['mailgun_region'] == 'eu' ? ' selected="selected"' : '').'>EU</option>
							</select>
							<br /><em>'.__('Select your region.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-mailgun-list" name="ulp_mailgun_list" value="'.esc_html($popup_options['mailgun_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_mailgun_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-mailgun-list-id" name="ulp_mailgun_list_id" value="'.esc_html($popup_options['mailgun_list_id']).'" />
							<div id="ulp-mailgun-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Select List ID.', 'ulp').'</em>
							<script>
								function ulp_mailgun_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-mailgun-lists", "ulp_api_key": jQuery("#ulp_mailgun_api_key").val(), "ulp_region": jQuery("#ulp_mailgun_region").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Custom Parameters', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-mailgun-fields-html">
								'.__('Please adjust the custom parameters below. Parameter name must contain only these characters: <code>A-Z</code>, <code>a-z</code>, <code>0-9</code> and <code>_</code> (parameter name is case sensitive). You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Mailgun parameters with the popup fields.', 'ulp').'
								<table style="width: 100%;">
									<tr>
										<td style="width: 200px; padding-bottom: 5px;"><strong>'.__('Name', 'ulp').'</strong></td>
										<td style="padding-bottom: 5px;"><strong>'.__('Value', 'ulp').'</strong></td>
										<td style="width: 32px; padding-bottom: 5px;"></td>
									</tr>';
		$i = 0;
		foreach ($popup_options['mailgun_custom_fields'] as $key => $value) {
			echo '									
									<tr>
										<td>
											<input type="text" name="ulp_mailgun_custom_fields_name[]" value="'.esc_html($key).'" class="widefat">
										</td>
										<td>
											<input type="text" name="ulp_mailgun_custom_fields_value[]" value="'.esc_html($value).'" class="widefat">
										</td>
										<td style="vertical-align: middle;">
											'.($i > 0 ? '<a class="ulp-integration-row-remove" href="#" onclick="return ulp_mailgun_remove_fields(this);"><i class="fas fa-trash-alt"></i></a>' : '').'
										</td>
									</tr>';
			$i++;
		}
		if ($i == 0) {
			echo '									
									<tr>
										<td>
											<input type="text" name="ulp_mailgun_custom_fields_name[]" value="" class="widefat">
										</td>
										<td>
											<input type="text" name="ulp_mailgun_custom_fields_value[]" value="" class="widefat">
										</td>
										<td></td>
									</tr>';
		}
		echo '
									<tr style="display: none;" id="mailgun-fields-template">
										<td>
											<input type="text" name="ulp_mailgun_custom_fields_name[]" value="" class="widefat">
										</td>
										<td>
											<input type="text" name="ulp_mailgun_custom_fields_value[]" value="" class="widefat">
										</td>
										<td style="vertical-align: middle;">
											<a class="ulp-integration-row-remove" href="#" onclick="return ulp_mailgun_remove_fields(this);"><i class="fas fa-trash-alt"></i></a>
										</td>
									</tr>
									<tr>
										<td colspan="3">
											<a class="ulp-button ulp-button-small" onclick="return ulp_mailgun_add_fields(this);"><i class="fas fa-plus"></i><label>'.__('Add Custom Field', 'ulp').'</label></a>
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
				</table>
				<script>
					function ulp_mailgun_add_fields(object) {
						jQuery("#mailgun-fields-template").before("<tr>"+jQuery("#mailgun-fields-template").html()+"</tr>");
						return false;
					}
					function ulp_mailgun_remove_fields(object) {
						var row = jQuery(object).closest("tr");
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
		if (isset($ulp->postdata["ulp_mailgun_enable"])) $popup_options['mailgun_enable'] = "on";
		else $popup_options['mailgun_enable'] = "off";
		if ($popup_options['mailgun_enable'] == 'on') {
			if (empty($popup_options['mailgun_api_key'])) $errors[] = __('Invalid Mailgun API Key.', 'ulp');
			if (empty($popup_options['mailgun_list_id'])) $errors[] = __('Invalid Mailgun List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_mailgun_enable"])) $popup_options['mailgun_enable'] = "on";
		else $popup_options['mailgun_enable'] = "off";
		
		$popup_options['mailgun_fields'] = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_mailgun_field_')) == 'ulp_mailgun_field_') {
				$key = substr($key, strlen('ulp_mailgun_field_'));
				$popup_options['mailgun_fields'][$key] = stripslashes(trim($value));
			}
		}
		$popup_options['mailgun_custom_fields'] = array();
		if (is_array($ulp->postdata["ulp_mailgun_custom_fields_name"]) && is_array($ulp->postdata["ulp_mailgun_custom_fields_value"])) {
			$fields = array();
			for($i=0; $i<sizeof($ulp->postdata["ulp_mailgun_custom_fields_name"]); $i++) {
				$key = preg_replace('/[^a-zA-Z0-9_]/', '', $ulp->postdata['ulp_mailgun_custom_fields_name'][$i]);
				$value = stripslashes(trim($ulp->postdata['ulp_mailgun_custom_fields_value'][$i]));
				if (!empty($key)) $popup_options['mailgun_custom_fields'][$key] = $value;
			}
		}
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['mailgun_enable'] == 'on') {
			$post_data = array(
				'address' => $_subscriber['{subscription-email}'],
				'name' => $_subscriber['{subscription-name}'],
				'upsert' => 'yes',
				'subscribed' => 'yes'
			);
			$vars = array();
			if (!empty($popup_options['mailgun_custom_fields'])) {
				foreach($popup_options['mailgun_custom_fields'] as $key => $value) {
					if (!empty($value)) $vars[$key] = strtr($value, $_subscriber);
				}
			}
			if (!empty($vars)) $post_data['vars'] = json_encode($vars);
			$result = $this->connect($popup_options['mailgun_region'], $popup_options['mailgun_api_key'], 'lists/'.$popup_options['mailgun_list_id'].'/members', $post_data);
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_api_key']) || empty($_POST['ulp_api_key']) || !isset($_POST['ulp_region']) || empty($_POST['ulp_region'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API credentials.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_api_key']));
			$region = trim(stripslashes($_POST['ulp_region']));
			
			$result = $this->connect($region, $key, 'lists/pages?limit=100');
			
			if (is_array($result) && !empty($result)) {
				if (array_key_exists('message', $result)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.esc_html($result['message']).'</div>';
					echo json_encode($return_object);
					exit;
				} else if (array_key_exists('items', $result) && is_array($result['items']) && sizeof($result['items']) > 0) {
					foreach ($result['items'] as $list) {
						if (is_array($list)) {
							if (array_key_exists('address', $list) && array_key_exists('name', $list)) {
								$lists[$list['address']] = $list['name'];
							}
						}
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No lists found.', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response.', 'ulp').'</div>';
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
	
	
	function connect($_region, $_api_key, $_path, $_data = array(), $_method = '') {
		if ($_region == 'eu') $region = '.eu';
		else $region = '';
		$headers = array(
			'Accept: application/json'
		);
		try {
			$url = 'https://api'.$region.'.mailgun.net/v3/'.ltrim($_path, '/');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_USERPWD, 'api:'.$_api_key);
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
			curl_close($curl);
			$result = json_decode($response, true);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
}
$ulp_mailgun = new ulp_mailgun_class();
?>