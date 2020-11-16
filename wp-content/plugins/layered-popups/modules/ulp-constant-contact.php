<?php
/* Constant Contact integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
define('ULP_CONSTANT_CONTACT_DEFAULT_API_KEY', 'byk44ey5gc6nkha7vrxmdg8s');
class ulp_constantcontact_class {
	var $default_popup_options = array(
		'constantcontact_enable' => "off",
		'constantcontact_api_key' => ULP_CONSTANT_CONTACT_DEFAULT_API_KEY,
		'constantcontact_token' => '',
		'constantcontact_list' => '',
		'constantcontact_list_id' => '',
		'constantcontact_fields' => ''
	);
	var $fields = array(
		'first_name' => '{subscription-name}',
		'last_name' => '',
		'prefix_name' => '',
		'cell_phone' => '',
		'home_phone' => '{subscription-phone}',
		'company_name' => '',
		'job_title' => '',
		'work_phone' => ''
	);
	var $field_labels = array(
		'first_name' => array('title' => 'First name', 'description' => 'First name of the contact.'),
		'last_name' => array('title' => 'Last name', 'description' => 'Last name of the contact.'),
		'cell_phone' => array('title' => 'Cell phone #', 'description' => 'Cell phone number of the contact.'),
		'company_name' => array('title' => 'Organization', 'description' => 'Organization name the contact works for.'),
		'home_phone' => array('title' => 'Home phone #', 'description' => 'Home phone number of the contact.'),
		'work_phone' => array('title' => 'Work phone #', 'description' => 'Work phone number of the contact.'),
		'job_title' => array('title' => 'Job title', 'description' => 'Job title of the contact.'),
		'prefix_name' => array('title' => 'Name prefix', 'description' => 'Salutation (Mr., Ms., Sir, Mrs., Dr., etc).')
	);
	function __construct() {
		$this->default_popup_options['constantcontact_fields'] = serialize($this->fields);
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
			add_action('wp_ajax_ulp-constantcontact-lists', array(&$this, "show_lists"));

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
				<h3>'.__('Constant Contact Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Constant Contact', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_constantcontact_enable" name="ulp_constantcontact_enable" '.($popup_options['constantcontact_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Constant Contact', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Constant Contact.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_constantcontact_api_key" name="ulp_constantcontact_api_key" value="'.esc_html($popup_options['constantcontact_api_key']).'" class="widefat" onchange="ulp_constantcontact_key_changed();">
							<br /><em>'.__('Enter your API Key. You can use <a href="#" onclick="jQuery(\'#ulp_constantcontact_api_key\').val(\''.ULP_CONSTANT_CONTACT_DEFAULT_API_KEY.'\'); ulp_constantcontact_key_changed(); return false;">Default API Key</a> or get your own API Key <a href="https://constantcontact.mashery.com/apps/mykeys" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Access Token', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_constantcontact_token" name="ulp_constantcontact_token" value="'.esc_html($popup_options['constantcontact_token']).'" class="widefat" onchange="ulp_constantcontact_token_changed();">
							<br /><em>'.__('Enter your Access Token. You can get it <a id="ulp_constantcontact_token_link" href="https://oauth2.constantcontact.com/oauth2/password.htm?client_id='.esc_html($popup_options['constantcontact_api_key']).'" target="_blank">here</a>.', 'ulp').'</em>
							<script>
								function ulp_constantcontact_key_changed() {
									jQuery("#ulp_constantcontact_token_link").attr("href", "https://oauth2.constantcontact.com/oauth2/password.htm?client_id="+jQuery("#ulp_constantcontact_api_key").val());
									ulp_constantcontact_token_changed();
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID:', 'ulp').'</th>
						<td>
							<input type="text" id="ulp-constantcontact-list" name="ulp_constantcontact_list" value="'.esc_html($popup_options['constantcontact_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_constantcontact_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-constantcontact-list-id" name="ulp_constantcontact_list_id" value="'.esc_html($popup_options['constantcontact_list_id']).'" />
							<div id="ulp-constantcontact-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_constantcontact_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-constantcontact-lists", "ulp_api_key": jQuery("#ulp_constantcontact_api_key").val(), "ulp_token": jQuery("#ulp_constantcontact_token").val()});
								}
							</script>
						</td>
					</tr>';
		if ($ulp->ext_options['enable_customfields'] == 'on') {
			$fields = unserialize($popup_options['constantcontact_fields']);
			if (is_array($fields)) $fields = array_merge($this->fields, $fields);
			else $fields = $this->fields;
			echo '
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-constantcontact-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Constant Contact fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
			foreach ($this->fields as $key => $value) {
				echo '
									<tr>
										<td style="width: 100px;"><strong>'.esc_html($this->field_labels[$key]['title']).':</strong></td>
										<td>
											<input type="text" id="ulp_constantcontact_field_'.esc_html($key).'" name="ulp_constantcontact_field_'.esc_html($key).'" value="'.esc_html($fields[$key]).'" class="widefat" />
											<br /><em>'.esc_html($this->field_labels[$key]['description']).'</em>
										</td>
									</tr>';
			}
			echo '
								</table>
							</div>
						</td>
					</tr>';
		}
		echo '
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
		if (isset($ulp->postdata["ulp_constantcontact_enable"])) $popup_options['constantcontact_enable'] = "on";
		else $popup_options['constantcontact_enable'] = "off";
		if ($popup_options['constantcontact_enable'] == 'on') {
			if (empty($popup_options['constantcontact_api_key'])) $errors[] = __('Invalid Constant Contact API Key.', 'ulp');
			if (empty($popup_options['constantcontact_token'])) $errors[] = __('Invalid Constant Contact Access Token.', 'ulp');
			if (empty($popup_options['constantcontact_list_id'])) $errors[] = __('Invalid Constant Contact List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_constantcontact_enable"])) $popup_options['constantcontact_enable'] = "on";
		else $popup_options['constantcontact_enable'] = "off";
		if ($ulp->ext_options['enable_customfields'] == 'on') {
			$fields = array();
			foreach($this->fields as $key => $value) {
				if (isset($ulp->postdata['ulp_constantcontact_field_'.$key])) {
					$fields[$key] = stripslashes(trim($ulp->postdata['ulp_constantcontact_field_'.$key]));
				}
			}
			$popup_options['constantcontact_fields'] = serialize($fields);
		}
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['constantcontact_enable'] == 'on') {
			$curl = curl_init('https://api.constantcontact.com/v2/contacts?api_key='.rawurlencode($popup_options['constantcontact_api_key']).'&email='.$_subscriber['{subscription-email}']);
			$header = array(
				'Authorization: Bearer '.$popup_options['constantcontact_token']
			);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			//curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
			//curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
			$response = curl_exec($curl);
			curl_close($curl);
			$result = json_decode($response, true);
			if($result && is_array($result)) {
				if (!empty($result['results'])) {
					$contact = $result['results'][0];
					$contact_original = $contact;
					$update = true;
					foreach ($contact['lists'] as $list) {
						if ($list['id'] == $popup_options['constantcontact_list_id']) {
							$update = false;
							break;
						}
					}
					if ($update) {
						$contact['lists'][] = array('id' => $popup_options['constantcontact_list_id']);
					}
					if ($ulp->ext_options['enable_customfields'] == 'on') {
						$fields = unserialize($_popup_options['constantcontact_fields']);
						if (is_array($fields)) $fields = array_merge($this->fields, $fields);
						else $fields = $this->fields;
						foreach ($fields as $key => $value) {
							if (!empty($value)) {
								$contact[$key] = strtr($value, $_subscriber);
							}
						}
					} else {
						if (!empty($_subscriber['{subscription-name}'])) {
							$contact['first_name'] = $_subscriber['{subscription-name}'];
						}
						if (!empty($_subscriber['{subscription-phone}'])) {
							$contact['home_phone'] = $_subscriber['{subscription-phone}'];
						}
					}
					if ($contact != $contact_original) {
						$curl = curl_init('https://api.constantcontact.com/v2/contacts/'.$contact['id'].'?api_key='.rawurlencode($popup_options['constantcontact_api_key']).'&action_by=ACTION_BY_VISITOR');
						$header = array(
							'Content-Type: application/json',
							'Authorization: Bearer '.$popup_options['constantcontact_token']
						);
						curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
						curl_setopt($curl, CURLOPT_TIMEOUT, 10);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
						//curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
						//curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
						curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
						curl_setopt($curl, CURLOPT_POST, 1);
						curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($contact));
						curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
						curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
						$response = curl_exec($curl);
						curl_close($curl);
					}
				} else {
					$contact = array(
						'email_addresses' => array(
							array('email_address' => $_subscriber['{subscription-email}'])
						),
						'lists' => array(
							array(
								'id' => $popup_options['constantcontact_list_id']
							)
						)
					);
					if ($ulp->ext_options['enable_customfields'] == 'on') {
						$fields = unserialize($_popup_options['constantcontact_fields']);
						if (is_array($fields)) $fields = array_merge($this->fields, $fields);
						else $fields = $this->fields;
						foreach ($fields as $key => $value) {
							if (!empty($value)) {
								$contact[$key] = strtr($value, $_subscriber);
							}
						}
					} else {
						if (!empty($_subscriber['{subscription-name}'])) {
							$contact['first_name'] = $_subscriber['{subscription-name}'];
							$contact['last_name'] = '';
						}
						if (!empty($_subscriber['{subscription-phone}'])) {
							$contact['home_phone'] = $_subscriber['{subscription-phone}'];
						}
					}
					$curl = curl_init('https://api.constantcontact.com/v2/contacts?api_key='.rawurlencode($popup_options['constantcontact_api_key']).'&action_by=ACTION_BY_VISITOR');
					$header = array(
						'Content-Type: application/json',
						'Authorization: Bearer '.$popup_options['constantcontact_token']
					);
					curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
					curl_setopt($curl, CURLOPT_TIMEOUT, 10);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
					//curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
					//curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
					curl_setopt($curl, CURLOPT_POST, 1);
					curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($contact));
					curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
					$response = curl_exec($curl);
					curl_close($curl);
				}
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_api_key']) || empty($_POST['ulp_api_key']) || !isset($_POST['ulp_token']) || empty($_POST['ulp_token'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key or Token!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$token = trim(stripslashes($_POST['ulp_token']));
			$key = trim(stripslashes($_POST['ulp_api_key']));
			$lists = array();
			try {
				$curl = curl_init('https://api.constantcontact.com/v2/lists?api_key='.rawurlencode($key));
				$header = array(
					'Authorization: Bearer '.$token
				);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				//curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				//curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				$response = curl_exec($curl);
				curl_close($curl);
							
				$result = json_decode($response, true);
				if($result && is_array($result)) {
					foreach ($result as $list) {
						if (is_array($list)) {
							if (array_key_exists('id', $list) && array_key_exists('name', $list)) {
								$lists[$list['id']] = $list['name'];
							}
						}
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key or Token!', 'ulp').'</div>';
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
$ulp_constantcontact = new ulp_constantcontact_class();
?>