<?php
/* AgileCRM integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_agilecrm_class {
	var $default_popup_options = array(
		'agilecrm_enable' => 'off',
		'agilecrm_url' => '',
		'agilecrm_email' => '',
		'agilecrm_api_key' => '',
		'agilecrm_list' => 0,
		'agilecrm_list_id' => '',
		'agilecrm_fields' => array(),
		'agilecrm_custom_fields' => array(),
		'agilecrm_tags' => ''
	);
	var $fields = array(
		'email' => '{subscription-email}',
		'first_name' => '{subscription-name}',
		'last_name' => '',
		'title' => '',
		'company' => '',
		'address' => '',
		'city' => '',
		'country' => '',
		'state' => '',
		'zip' => '',
		'phone' => '{subscription-phone}',
		'website' => ''
	);
	var $field_labels = array(
		'email' => array('title' => 'E-mail', 'description' => 'E-mail address of contact/recipient.'),
		'first_name' => array('title' => 'First name', 'description' => 'First name of the contact.'),
		'last_name' => array('title' => 'Last name', 'description' => 'Last name of the contact.'),
		'title' => array('title' => 'Title', 'description' => 'Title of the contact (Mr., Mrs, Miss, etc.).'),
		'company' => array('title' => 'Company', 'description' => 'Organization name the contact works for.'),
		'address' => array('title' => 'Address', 'description' => 'Address of the contact.'),
		'city' => array('title' => 'City', 'description' => 'City of the contact.'),
		'country' => array('title' => 'Country', 'description' => 'Country of the contact.'),
		'state' => array('title' => 'State', 'description' => 'State or province of the contact.'),
		'zip' => array('title' => 'Postal code', 'description' => 'ZIP or postal code of the contact.'),
		'phone' => array('title' => 'Phone #', 'description' => 'Phone number of the contact.'),
		'website' => array('title' => 'Website URL', 'description' => 'Website URL of the contact.')
	);
	function __construct() {
		$this->default_popup_options['agilecrm_list'] = '0 | '.__('Do not add contact to campaign', 'ulp');
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-agilecrm-lists', array(&$this, "show_lists"));
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
				<h3>'.__('AgileCRM Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable AgileCRM', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_agilecrm_enable" name="ulp_agilecrm_enable" '.($popup_options['agilecrm_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to AgileCRM', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to AgileCRM.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Site URL', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_agilecrm_url" name="ulp_agilecrm_url" value="'.esc_html($popup_options['agilecrm_url']).'" class="widefat">
							<br /><em>'.__('Enter unique website address of your account. Usually it looks like https://SITE-NAME.agilecrm.com', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('E-mail', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_agilecrm_email" name="ulp_agilecrm_email" value="'.esc_html($popup_options['agilecrm_email']).'" class="widefat">
							<br /><em>'.__('Enter e-mail address of your AgileCRM account (i.e. e-mail address that you used to create AgileCRM account).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_agilecrm_api_key" name="ulp_agilecrm_api_key" value="'.esc_html($popup_options['agilecrm_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your AgileCRM REST API Key. Go to your AgileCRM account, click "Admin Settings" and "Developers & API".', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Campaign ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-agilecrm-list" name="ulp_agilecrm_list" value="'.esc_html($popup_options['agilecrm_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_agilecrm_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-agilecrm-list-id" name="ulp_agilecrm_list_id" value="'.esc_html($popup_options['agilecrm_list_id']).'" />
							<div id="ulp-agilecrm-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your Campaign ID.', 'ulp').'</em>
							<script>
								function ulp_agilecrm_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-agilecrm-lists", "ulp_url": jQuery("#ulp_agilecrm_url").val(), "ulp_email": jQuery("#ulp_agilecrm_email").val(), "ulp_api_key": jQuery("#ulp_agilecrm_api_key").val()});
								}
							</script>
						</td>
					</tr>';
		if (is_array($popup_options['agilecrm_fields'])) $popup_options['agilecrm_fields'] = array_merge($this->fields, (array)$popup_options['agilecrm_fields']);
		else $popup_options['agilecrm_fields'] = $this->fields;
		echo '
					<tr>
						<th>'.__('System Properties', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-agilecrm-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate AgileCRM properties with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
		foreach ($this->fields as $key => $value) {
			echo '
									<tr>
										<td style="width: 200px;"><strong>'.esc_html($this->field_labels[$key]['title']).':</strong></td>
										<td>
											<input type="text" id="ulp_agilecrm_field_'.esc_html($key).'" name="ulp_agilecrm_field_'.esc_html($key).'" value="'.esc_html($popup_options['agilecrm_fields'][$key]).'" class="widefat"'.($key == 'email' ? ' readonly="readonly"' : '').' />
											<br /><em>'.esc_html($this->field_labels[$key]['description']).'</em>
										</td>
									</tr>';
		}
		echo '
								</table>
							</div>
						</td>
					</tr>
					<tr>
						<th>'.__('Custom Properties', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-agilecrm-fields-html">
								'.__('Please adjust the custom properties below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate AgileCRM custom properties with the popup fields.', 'ulp').'
								<table style="width: 100%;">
									<tr>
										<td style="width: 200px; padding-bottom: 5px;"><strong>'.__('Name', 'ulp').'</strong></td>
										<td style="padding-bottom: 5px;"><strong>'.__('Value', 'ulp').'</strong></td>
										<td style="width: 32px; padding-bottom: 5px;"></td>
									</tr>';
		$i = 0;
		foreach ($popup_options['agilecrm_custom_fields'] as $key => $value) {
			echo '									
									<tr>
										<td>
											<input type="text" name="ulp_agilecrm_custom_fields_name[]" value="'.esc_html($key).'" class="widefat">
										</td>
										<td>
											<input type="text" name="ulp_agilecrm_custom_fields_value[]" value="'.esc_html($value).'" class="widefat">
										</td>
										<td style="vertical-align: middle;">
											'.($i > 0 ? '<a class="ulp-integration-row-remove" href="#" onclick="return ulp_agilecrm_remove_fields(this);"><i class="fas fa-trash-alt"></i></a>' : '').'
										</td>
									</tr>';
			$i++;
		}
		if ($i == 0) {
			echo '									
									<tr>
										<td>
											<input type="text" name="ulp_agilecrm_custom_fields_name[]" value="" class="widefat">
										</td>
										<td>
											<input type="text" name="ulp_agilecrm_custom_fields_value[]" value="" class="widefat">
										</td>
										<td></td>
									</tr>';
		}
		echo '
									<tr style="display: none;" id="agilecrm-fields-template">
										<td>
											<input type="text" name="ulp_agilecrm_custom_fields_name[]" value="" class="widefat">
										</td>
										<td>
											<input type="text" name="ulp_agilecrm_custom_fields_value[]" value="" class="widefat">
										</td>
										<td style="vertical-align: middle;">
											<a class="ulp-integration-row-remove" href="#" onclick="return ulp_agilecrm_remove_fields(this);"><i class="fas fa-trash-alt"></i></a>
										</td>
									</tr>
									<tr>
										<td colspan="3">
											<a class="ulp-button ulp-button-small" onclick="return ulp_agilecrm_add_fields(this);"><i class="fas fa-plus"></i><label>'.__('Add Custom Property', 'ulp').'</label></a>
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
					<tr>
						<th>'.__('Tags', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_agilecrm_tags" name="ulp_agilecrm_tags" value="'.esc_html($popup_options['agilecrm_tags']).'" class="widefat">
							<br /><em>'.__('If you want to tag contact with tags, drop them here (comma-separated string).', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<script>
					function ulp_agilecrm_add_fields(object) {
						jQuery("#agilecrm-fields-template").before("<tr>"+jQuery("#agilecrm-fields-template").html()+"</tr>");
						return false;
					}
					function ulp_agilecrm_remove_fields(object) {
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
		if (isset($ulp->postdata["ulp_agilecrm_enable"])) $popup_options['agilecrm_enable'] = "on";
		else $popup_options['agilecrm_enable'] = "off";
		if ($popup_options['agilecrm_enable'] == 'on') {
			if (empty($popup_options['agilecrm_url']) || !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $popup_options['agilecrm_url'])) $errors[] = __('Invalid AgileCRM Site URL', 'ulp');
			if (empty($popup_options['agilecrm_email']) || !preg_match("/^[_a-z0-9-+]+(\.[_a-z0-9-+]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,19})$/i", $popup_options['agilecrm_email'])) $errors[] = __('Invalid AgileCRM E-mail', 'ulp');
			if (empty($popup_options['agilecrm_api_key'])) $errors[] = __('Invalid AgileCRM API key', 'ulp');
			//if (empty($popup_options['agilecrm_list_id'])) $errors[] = __('Invalid AgileCRM list ID', 'ulp');
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
		if (isset($ulp->postdata["ulp_agilecrm_enable"])) $popup_options['agilecrm_enable'] = "on";
		else $popup_options['agilecrm_enable'] = "off";
		
		$popup_options['agilecrm_fields'] = array();
		foreach($this->fields as $key => $value) {
			if (isset($ulp->postdata['ulp_agilecrm_field_'.$key])) {
				$popup_options['agilecrm_fields'][$key] = stripslashes(trim($ulp->postdata['ulp_agilecrm_field_'.$key]));
			}
		}

		$popup_options['agilecrm_custom_fields'] = array();
		if (is_array($ulp->postdata["ulp_agilecrm_custom_fields_name"]) && is_array($ulp->postdata["ulp_agilecrm_custom_fields_value"])) {
			for($i=0; $i<sizeof($ulp->postdata["ulp_agilecrm_custom_fields_name"]); $i++) {
				$key = stripslashes(trim($ulp->postdata['ulp_agilecrm_custom_fields_name'][$i]));
				$value = stripslashes(trim($ulp->postdata['ulp_agilecrm_custom_fields_value'][$i]));
				if (!empty($key)) $popup_options['agilecrm_custom_fields'][$key] = $value;
			}
		}

		$tags = explode(',', $ulp->postdata['ulp_agilecrm_tags']);
		foreach($tags as $key => $value) {
			$tags[$key] = trim($value);
			if (empty($tags[$key])) unset($tags[$key]);
		}
		$popup_options['agilecrm_tags'] = implode(', ', $tags);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['agilecrm_enable'] == 'on') {
			if (is_array($popup_options['agilecrm_fields'])) $fields = array_merge($this->fields, $popup_options['agilecrm_fields']);
			else $fields = $this->fields;
			$data = array(
				'properties' => array(array(
					'type' => 'SYSTEM',
					'name' => 'email',
					'value' => $_subscriber['{subscription-email}']
				))
			);
			$address = array();
			foreach ($fields as $key => $value) {
				if (!empty($value) && $key != 'email') {
					if ($key == 'address' || $key == 'city' || $key == 'state' || $key == 'country' || $key == 'zip') {
						$address[$key] = strtr($value, $_subscriber);
					} else {
						$data['properties'][] = array(
							'type' => 'SYSTEM',
							'name' => $key,
							'value' => strtr($value, $_subscriber)
						);
					}
				}
			}
			if (!empty($address)) {
				$data['properties'][] = array(
					'type' => 'SYSTEM',
					'name' => 'address',
					'value' => json_encode($address)
				);
			}
			
			if (!empty($popup_options['agilecrm_custom_fields'])) {
				foreach($popup_options['agilecrm_custom_fields'] as $key => $value) {
					if (!empty($value)) {
						$data['properties'][] = array(
							'type' => 'CUSTOM',
							'name' => $key,
							'value' => strtr($value, $_subscriber)
						);
					}
				}
			}
			
			$result = $this->connect($popup_options['agilecrm_email'], $popup_options['agilecrm_api_key'], rtrim($popup_options['agilecrm_url'], '/').'/dev/api/contacts/search/email/'.$_subscriber['{subscription-email}']);
			if ($result['http_code'] == 200) {
				$contact_id = $result['result']['id'];
				$data['id'] = $contact_id;
				$result = $this->connect($popup_options['agilecrm_email'], $popup_options['agilecrm_api_key'], rtrim($popup_options['agilecrm_url'], '/').'/dev/api/contacts/edit-properties', $data, 'PUT');
				$tags_data = array(
					'id' => $contact_id,
					'tags' => explode(', ', $popup_options['agilecrm_tags'])
				);
				$result = $this->connect($popup_options['agilecrm_email'], $popup_options['agilecrm_api_key'], rtrim($popup_options['agilecrm_url'], '/').'/dev/api/contacts/edit/tags', $tags_data, 'PUT');
			} else {
				$data['tags'] = explode(', ', $popup_options['agilecrm_tags']);
				$result = $this->connect($popup_options['agilecrm_email'], $popup_options['agilecrm_api_key'], rtrim($popup_options['agilecrm_url'], '/').'/dev/api/contacts', $data);
			}
			
			if (!empty($popup_options['agilecrm_list_id'])) {
				$data = array(
					'email' => $_subscriber['{subscription-email}'],
					'workflow-id' => $popup_options['agilecrm_list_id']
				);
				$result = $this->connect($popup_options['agilecrm_email'], $popup_options['agilecrm_api_key'], rtrim($popup_options['agilecrm_url'], '/').'/dev/api/campaigns/enroll/email', $data, 'POST', 'application/x-www-form-urlencoded');
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_url']) || !isset($_POST['ulp_email']) || !isset($_POST['ulp_api_key']) || empty($_POST['ulp_url']) || empty($_POST['ulp_email']) || empty($_POST['ulp_api_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Credentials!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$agilecrm_url = trim(stripslashes($_POST['ulp_url']));
			$api_key = trim(stripslashes($_POST['ulp_api_key']));
			$email = trim(stripslashes($_POST['ulp_email']));
			
			if (!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $agilecrm_url)) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Site URL!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$lists = array(__('Do not add contact to campaign', 'ulp'));
			
			$result = $this->connect($email, $api_key, rtrim($agilecrm_url, '/').'/dev/api/workflows');
			
			if ($result) {
				if ($result['http_code'] != 200) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to AgileCRM Site URL!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
				foreach($result['result'] as $list) {
					if (is_array($list)) {
						if (array_key_exists('id', $list) && array_key_exists('name', $list)) {
							$lists[$list['id']] = $list['name'];
						}
					}
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to AgileCRM Site URL!', 'ulp').'</div>';
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
	function connect($_email, $_api_key, $_url, $_data = array(), $_method = '', $_content_type = 'application/json') {
		$headers = array(
			'Accept: application/json',
			'Content-Type: '.$_content_type
		);
		if ($_content_type == 'application/json') $post_data = json_encode($_data);
		else $post_data = http_build_query($_data);
		
		try {
			$curl = curl_init($_url);
			curl_setopt($curl, CURLOPT_USERPWD, $_email.':'.$_api_key);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_UNRESTRICTED_AUTH, true);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 20);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($curl);
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			$response = preg_replace('/"id":(\d+)/', '"id":"$1"', $response);
			$result = json_decode($response, true);
		} catch (Exception $e) {
			return false;
		}
		return array('http_code' => $http_code, 'result' => $result);
	}
}
$ulp_agilecrm = new ulp_agilecrm_class();
?>