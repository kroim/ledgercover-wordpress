<?php
/* Conversio integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_conversio_class {
	var $default_popup_options = array(
		"conversio_enable" => "off",
		"conversio_api_key" => "",
		"conversio_list" => "",
		"conversio_list_id" => "",
		"conversio_name" => "{subscription-name}",
		"conversio_fields" => array(),
		"conversio_optintext" => "Subscribe to receive newsletters",
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-conversio-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-conversio-fields', array(&$this, "show_fields"));
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
				<h3>'.__('Conversio Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Conversio', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_conversio_enable" name="ulp_conversio_enable" '.($popup_options['conversio_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Conversio', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Conversio.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_conversio_api_key" name="ulp_conversio_api_key" value="'.esc_html($popup_options['conversio_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Conversio API Key. You can get it on <a href="https://app.conversio.com/profile" target="_blank">Profile</a> page.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-conversio-list" name="ulp_conversio_list" value="'.esc_html($popup_options['conversio_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_conversio_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-conversio-list-id" name="ulp_conversio_list_id" value="'.esc_html($popup_options['conversio_list_id']).'" />
							<div id="ulp-conversio-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_conversio_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-conversio-lists", "ulp_api_key": jQuery("#ulp_conversio_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							'.__('Please adjust the attributes below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Conversio fields with the popup fields.', 'ulp').'
							<table style="min-width: 280px; width: 70%;">
								<tr>
									<td style="width: 200px;"><strong>'.__('Name', 'ulp').'</strong></td>
									<td><strong>'.__('Value', 'ulp').'</strong></td>
								</tr>
								<tr>
									<td><strong>'.__('Email', 'ulp').':</strong></td>
									<td>
										<input type="text" value="{subscription-email}" class="widefat" readonly="readonly" />
										<br /><em>'.__('Email address of the subscriber.', 'ulp').'</em>
									</td>
								</tr>
								<tr>
									<td><strong>'.__('Name', 'ulp').':</strong></td>
									<td>
										<input type="text" id="ulp_conversio_name" name="ulp_conversio_name" value="'.esc_html($popup_options['conversio_name']).'" class="widefat" />
										<br /><em>'.__('Name of the subscriber.', 'ulp').'</em>
									</td>
								</tr>';
		if (is_array($popup_options['conversio_fields'])) {
			foreach ($popup_options['conversio_fields'] as $key => $value) {
				echo '									
								<tr>
									<td>
										<input type="text" name="ulp_conversio_fields_name[]" value="'.esc_html($key).'" class="widefat">
										<br /><em><a href="#" onclick="return ulp_conversio_remove_fields(this);">'.__('Remove Field', 'ulp').'</a></em>
									</td>
									<td>
										<input type="text" name="ulp_conversio_fields_value[]" value="'.esc_html($value).'" class="widefat">
									</td>
								</tr>';
			}
		}
		echo '
								<tr style="display: none;" id="conversio-fields-template">
									<td>
										<input type="text" name="ulp_conversio_fields_name[]" value="" class="widefat">
										<br /><em><a href="#" onclick="return ulp_conversio_remove_fields(this);">'.__('Remove Field', 'ulp').'</a></em>
									</td>
									<td>
										<input type="text" name="ulp_conversio_fields_value[]" value="" class="widefat">
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<a class="ulp-button ulp-button-small" onclick="return ulp_conversio_add_fields(this);"><i class="fas fa-plus"></i><label>Add Field</label></a>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<th>'.__('Opt-In Text', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_conversio_optintext" name="ulp_conversio_optintext" value="'.esc_html($popup_options['conversio_optintext']).'" class="widefat">
							<br /><em>'.__('What opt-in text was shown to the subscriber. This is required for GDPR compliance.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<script>
					function ulp_conversio_add_fields(object) {
						jQuery("#conversio-fields-template").before("<tr>"+jQuery("#conversio-fields-template").html()+"</tr>");
						return false;
					}
					function ulp_conversio_remove_fields(object) {
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
		if (isset($ulp->postdata["ulp_conversio_enable"])) $popup_options['conversio_enable'] = "on";
		else $popup_options['conversio_enable'] = "off";
		if ($popup_options['conversio_enable'] == 'on') {
			if (empty($popup_options['conversio_api_key'])) $errors[] = __('Invalid Conversio API Key.', 'ulp');
			if (empty($popup_options['conversio_list_id'])) $errors[] = __('Invalid Conversio List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_conversio_enable"])) $popup_options['conversio_enable'] = "on";
		else $popup_options['conversio_enable'] = "off";

		$popup_options['conversio_fields'] = array();
		if (is_array($ulp->postdata["ulp_conversio_fields_name"]) && is_array($ulp->postdata["ulp_conversio_fields_value"])) {
			for($i=0; $i<sizeof($ulp->postdata["ulp_conversio_fields_name"]); $i++) {
				$key = stripslashes(trim($ulp->postdata['ulp_conversio_fields_name'][$i]));
				$value = stripslashes(trim($ulp->postdata['ulp_conversio_fields_value'][$i]));
				if (!empty($key)) $popup_options['conversio_fields'][$key] = $value;
			}
		}
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['conversio_enable'] == 'on') {

			$fields = array();
			if (!empty($popup_options['conversio_fields']) && is_array($popup_options['conversio_fields'])) {
				foreach ($popup_options['conversio_fields'] as $key => $value) {
					if (!empty($value) && !empty($key)) {
						$fields[$key] = strtr($value, $_subscriber);
					}
				}
			}
			
			$data = array(
				'email' => $_subscriber['{subscription-email}'],
				'name' => strtr($popup_options['conversio_name'], $_subscriber),
				'source' => $_subscriber['{popup}'],
				'sourceType' => 'Layered Popups',
				'sourceId' => $_subscriber['{popup-id}'],
				'optInText' => strtr($popup_options['conversio_optintext'], $_subscriber)
			);
			if (!empty($fields)) {
				$data['properties'] = $fields;
			}
			$result = $this->connect($popup_options['conversio_api_key'], 'customer-lists/'.urlencode($popup_options['conversio_list_id']).'/subscriptions', $data, 'PUT');
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
			
			$result = $this->connect($key, 'customer-lists');

			if (is_array($result)) {
				if (array_key_exists('errors', $result)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.$result['errors'][0].'</div>';
					echo json_encode($return_object);
					exit;
				}
				if (sizeof($result) > 0) {
					foreach ($result as $list) {
						if (is_array($list)) {
							if (array_key_exists('id', $list) && array_key_exists('title', $list)) {
								$lists[$list['id']] = $list['title'];
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
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response!', 'ulp').'</div>';
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
			'X-ApiKey: '.$_api_key,
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		try {
			$url = 'https://app.conversio.com/api/v1/'.ltrim($_path, '/');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 120);
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
$ulp_conversio = new ulp_conversio_class();
?>