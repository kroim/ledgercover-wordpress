<?php
/* Klaviyo integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_klaviyo_class {
	var $default_popup_options = array(
		'klaviyo_enable' => 'off',
		'klaviyo_api_key' => '',
		'klaviyo_list' => '',
		'klaviyo_list_id' => '',
		'klaviyo_fields' => array(),
		'klaviyo_properties' => array(),
		"klaviyo_double" => "off"
	);
	var $fields = array(
		'first_name' => '{subscription-name}',
		'last_name' => '',
		'phone_number' => '{subscription-phone}',
		'title' => '',
		'organization' => '',
		'city' => '',
		'region' => '',
		'country' => '',
		'zip' => ''
	);
	var $field_labels = array(
		'first_name' => array('title' => 'First name', 'description' => 'First name of the contact.'),
		'last_name' => array('title' => 'Last name', 'description' => 'Last name of the contact.'),
		'phone_number' => array('title' => 'Phone #', 'description' => 'Phone number of the contact.'),
		'title' => array('title' => 'Title', 'description' => 'Title at their business or organization.'),
		'organization' => array('title' => 'Organization', 'description' => 'Organization name the contact works for.'),
		'city' => array('title' => 'City', 'description' => 'City of the contact.'),
		'region' => array('title' => 'Region', 'description' => 'State or province of the contact.'),
		'country' => array('title' => 'Country', 'description' => 'Country of the contact.'),
		'zip' => array('title' => 'Postal code', 'description' => 'ZIP or postal code of the contact.')
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-klaviyo-lists', array(&$this, "show_lists"));
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
		}
		add_action('ulp_subscribe', array(&$this, 'subscribe'), 10, 2);
	}
	function popup_options_tabs($_tabs) {
		if (!array_key_exists("integration", $_tabs)) $_tabs["integration"] = __('Integration', 'ulp');
		return $_tabs;
	}
	function popup_options_show($_popup_options) {
		global $ulp;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('Klaviyo Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Klaviyo', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_klaviyo_enable" name="ulp_klaviyo_enable" '.($popup_options['klaviyo_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Klaviyo', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Klaviyo.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Private API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_klaviyo_api_key" name="ulp_klaviyo_api_key" value="'.esc_html($popup_options['klaviyo_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Klaviyo Private API Key. Go to your Klaviyo account, click "Account", "Settings" and "API Keys".', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-klaviyo-list" name="ulp_klaviyo_list" value="'.esc_html($popup_options['klaviyo_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_klaviyo_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-klaviyo-list-id" name="ulp_klaviyo_list_id" value="'.esc_html($popup_options['klaviyo_list_id']).'" />
							<div id="ulp-klaviyo-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_klaviyo_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-klaviyo-lists", "ulp_api_key": jQuery("#ulp_klaviyo_api_key").val()});
								}
							</script>
						</td>
					</tr>';
		if (is_array($popup_options['klaviyo_fields'])) $fields = array_merge($this->fields, $popup_options['klaviyo_fields']);
		else $fields = $this->fields;
		echo '
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-klaviyo-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Klaviyo fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
		foreach ($this->fields as $key => $value) {
			echo '
									<tr>
										<td style="width: 200px;"><strong>'.esc_html($this->field_labels[$key]['title']).':</strong></td>
										<td>
											<input type="text" id="ulp_klaviyo_field_'.esc_html($key).'" name="ulp_klaviyo_field_'.esc_html($key).'" value="'.esc_html($fields[$key]).'" class="widefat"'.($key == 'email' ? ' readonly="readonly"' : '').' />
											<br /><em>'.esc_html($this->field_labels[$key]['description']).'</em>
										</td>
									</tr>';
		}
		if (is_array($popup_options['klaviyo_properties'])) {
			foreach ($popup_options['klaviyo_properties'] as $key => $value) {
				echo '									
									<tr>
										<td>
											<input type="text" name="ulp_klaviyo_properties_name[]" value="'.esc_html($key).'" class="widefat">
											<br /><em><a href="#" onclick="return ulp_klaviyo_remove_properties(this);">'.__('Remove Field', 'ulp').'</a></em>
										</td>
										<td>
											<input type="text" name="ulp_klaviyo_properties_value[]" value="'.esc_html($value).'" class="widefat">
										</td>
									</tr>';
			}
		}
		echo '
									<tr style="display: none;" id="klaviyo-properties-template">
										<td>
											<input type="text" name="ulp_klaviyo_properties_name[]" value="" class="widefat">
											<br /><em><a href="#" onclick="return ulp_klaviyo_remove_properties(this);">'.__('Remove Property', 'ulp').'</a></em>
										</td>
										<td>
											<input type="text" name="ulp_klaviyo_properties_value[]" value="" class="widefat">
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<a class="ulp-button ulp-button-small" onclick="return ulp_klaviyo_add_properties(this);"><i class="fas fa-plus"></i><label>Add Property</label></a>
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>';
		echo '
					<tr>
						<th>'.__('Double opt-in', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_klaviyo_double" name="ulp_klaviyo_double" '.($popup_options['klaviyo_double'] == "on" ? 'checked="checked"' : '').'"> '.__('Ask users to confirm their subscription', 'ulp').'
							<br /><em>'.__('Control whether a double opt-in confirmation message is sent.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<script>
					function ulp_klaviyo_add_properties(object) {
						jQuery("#klaviyo-properties-template").before("<tr>"+jQuery("#klaviyo-properties-template").html()+"</tr>");
						return false;
					}
					function ulp_klaviyo_remove_properties(object) {
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
		if (isset($ulp->postdata["ulp_klaviyo_enable"])) $popup_options['klaviyo_enable'] = "on";
		else $popup_options['klaviyo_enable'] = "off";
		if ($popup_options['klaviyo_enable'] == 'on') {
			if (empty($popup_options['klaviyo_api_key'])) $errors[] = __('Invalid Klaviyo API key', 'ulp');
			if (empty($popup_options['klaviyo_list_id'])) $errors[] = __('Invalid Klaviyo List ID', 'ulp');
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
		if (isset($ulp->postdata["ulp_klaviyo_enable"])) $popup_options['klaviyo_enable'] = "on";
		else $popup_options['klaviyo_enable'] = "off";
		if (isset($ulp->postdata["ulp_klaviyo_double"])) $popup_options['klaviyo_double'] = "on";
		else $popup_options['klaviyo_double'] = "off";

		$popup_options['klaviyo_fields'] = array();
		foreach($this->fields as $key => $value) {
			if (isset($ulp->postdata['ulp_klaviyo_field_'.$key])) {
				$popup_options['klaviyo_fields'][$key] = stripslashes(trim($ulp->postdata['ulp_klaviyo_field_'.$key]));
			}
		}
		
		$popup_options['klaviyo_properties'] = array();
		if (is_array($ulp->postdata["ulp_klaviyo_properties_name"]) && is_array($ulp->postdata["ulp_klaviyo_properties_value"])) {
			for($i=0; $i<sizeof($ulp->postdata["ulp_klaviyo_properties_name"]); $i++) {
				$key = stripslashes(trim($ulp->postdata['ulp_klaviyo_properties_name'][$i]));
				$value = stripslashes(trim($ulp->postdata['ulp_klaviyo_properties_value'][$i]));
				if (!empty($key)) $popup_options['klaviyo_properties'][$key] = $value;
			}
		}
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['klaviyo_enable'] == 'on') {
			$data = array(
				'email' => $_subscriber['{subscription-email}'],
				'confirm_optin' => ($popup_options['klaviyo_double'] == 'on' ? "true" : "false")
			);
			if (is_array($_popup_options['klaviyo_fields'])) $fields = array_merge($this->fields, $_popup_options['klaviyo_fields']);
			else $fields = $this->fields;
			foreach ($fields as $key => $value) {
				if (!empty($value)) {
					$properties['$'.$key] = strtr($value, $_subscriber);
				}
			}
			if (is_array($_popup_options['klaviyo_properties']) && !empty($_popup_options['klaviyo_properties'])) {
				foreach ($_popup_options['klaviyo_properties'] as $key => $value) {
					if (!empty($value)) {
						$properties[$key] = strtr($value, $_subscriber);
					}
				}
			}
			$data['properties'] = json_encode($properties);

			$result = $this->connect($popup_options['klaviyo_api_key'], '/api/v1/list/'.$popup_options['klaviyo_list_id'].'/members?email='.rawurlencode($_subscriber['{subscription-email}']));
			if (empty($result) || $result['total'] == 0) {
				$result = $this->connect($popup_options['klaviyo_api_key'], '/api/v1/list/'.$popup_options['klaviyo_list_id'].'/members', $data);
			} else {
				$contact_id = $result['data'][0]['person']['id'];
				$result = $this->connect($popup_options['klaviyo_api_key'], '/api/v1/person/'.$contact_id, $properties, 'PUT');
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_api_key']) || empty($_POST['ulp_api_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Private API Key!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$api_key = trim(stripslashes($_POST['ulp_api_key']));
			
			$lists = array();
			$result = $this->connect($api_key, '/api/v1/lists?count=100');
			if ($result) {
				if (array_key_exists("message", $result)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.esc_html($result['message']).'</div>';
					echo json_encode($return_object);
					exit;
				}
				if (!array_key_exists("total", $result) || !array_key_exists("data", $result) || $result['total'] == 0) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No lists found!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
				foreach($result['data'] as $list) {
					if (is_array($list)) {
						if (array_key_exists('id', $list) && array_key_exists('name', $list)) {
							$lists[$list['id']] = $list['name'];
						}
					}
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to Klaviyo API Server!', 'ulp').'</div>';
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
		try {
			$url = 'https://a.klaviyo.com/'.ltrim($_path, '/');
			if (!empty($_data)) {
				$_data['api_key'] = $_api_key;
			} else {
				if (strstr($_path, '?') === false) $url .= '?';
				else $url .= '&';
				$url .= 'api_key='.$_api_key;
			}
			$curl = curl_init($url);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
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
$ulp_klaviyo = new ulp_klaviyo_class();
?>