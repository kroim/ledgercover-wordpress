<?php
/* Drip integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_drip_class {
	var $default_popup_options = array(
		"drip_enable" => "off",
		"drip_api_token" => "",
		"drip_account" => "",
		"drip_account_id" => "",
		"drip_campaign" => "",
		"drip_campaign_id" => "",
		"drip_tags" => array(),
		'drip_fields' => array(),
		"drip_custom_fields" => array(),
		"drip_eu_consent" => "on"
	);
	var $fields = array(
		'email' => '{subscription-email}',
		'first_name' => '{subscription-name}',
		'last_name' => '',
		'address1' => '',
		'address2' => '',
		'city' => '',
		'state' => '',
		'country' => '',
		'zip' => '',
		'phone' => ''
	);
	var $field_labels = array(
		'email' => array('title' => 'Email', 'description' => 'The subscriber\'s email address.'),
		'first_name' => array('title' => 'First name', 'description' => 'The subscriber\'s first name.'),
		'last_name' => array('title' => 'Last name', 'description' => 'The subscriber\'s first name.'),
		'address1' => array('title' => 'Address 1', 'description' => 'The subscriber\'s mailing address.'),
		'address2' => array('title' => 'Address 2', 'description' => 'An additional field for the subscriber\'s mailing address.'),
		'city' => array('title' => 'City', 'description' => 'The city, town, or village in which the subscriber resides.'),
		'state' => array('title' => 'Region', 'description' => 'The region in which the subscriber resides. Typically a province, a state, or a prefecture.'),
		'country' => array('title' => 'Country', 'description' => 'The country in which the subscriber resides.'),
		'zip' => array('title' => 'Postal code', 'description' => 'The postal code in which the subscriber resides, also known as zip, postcode, Eircode, etc.'),
		'phone' => array('title' => 'Phone #', 'description' => 'The subscriber\'s primary phone number.')
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
			add_action('wp_ajax_ulp-drip-accounts', array(&$this, "show_accounts"));
			add_action('wp_ajax_ulp-drip-campaigns', array(&$this, "show_campaigns"));
			add_action('wp_ajax_ulp-drip-fields', array(&$this, "show_fields"));
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
				<h3>'.__('Drip Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Drip', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_drip_enable" name="ulp_drip_enable" '.($popup_options['drip_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Drip', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Drip.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Token', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_drip_api_token" name="ulp_drip_api_token" value="'.esc_html($popup_options['drip_api_token']).'" class="widefat">
							<br /><em>'.__('Enter your Drip API Token. You can get it <a href="https://www.getdrip.com/user/edit" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Account ID:', 'ulp').'</th>
						<td>
							<input type="text" id="ulp-drip-account" name="ulp_drip_account" value="'.esc_html($popup_options['drip_account']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_drip_accounts_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-drip-account-id" name="ulp_drip_account_id" value="'.esc_html($popup_options['drip_account_id']).'" />
							<div id="ulp-drip-account-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Select Account ID.', 'ulp').'</em>
							<script>
								function ulp_drip_accounts_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-drip-accounts", "ulp_api_token": jQuery("#ulp_drip_api_token").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Campaign ID:', 'ulp').'</th>
						<td>
							<input type="text" id="ulp-drip-campaign" name="ulp_drip_campaign" value="'.esc_html($popup_options['drip_campaign']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_drip_campaigns_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-drip-campaign-id" name="ulp_drip_campaign_id" value="'.esc_html($popup_options['drip_campaign_id']).'" />
							<div id="ulp-drip-campaign-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Select Campaign ID.', 'ulp').'</em>
							<script>
								function ulp_drip_campaigns_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-drip-campaigns", "ulp_api_token": jQuery("#ulp_drip_api_token").val(), "ulp_account_id": jQuery("#ulp-drip-account-id").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-drip-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Drip fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
		if (is_array($popup_options['drip_fields'])) $fields = array_merge($this->fields, $popup_options['drip_fields']);
		else $fields = $this->fields;
		foreach ($this->fields as $key => $value) {
			echo '
									<tr>
										<td style="width: 200px;"><strong>'.esc_html($this->field_labels[$key]['title']).':</strong></td>
										<td>
											<input type="text" id="ulp_drip_field_'.esc_html($key).'" name="ulp_drip_field_'.esc_html($key).'" value="'.esc_html($fields[$key]).'" class="widefat"'.($key == 'email' ? ' readonly="readonly"' : '').' />
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
						<th>'.__('Custom Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-drip-custom-fields-html">';
		if (!empty($popup_options['drip_api_token']) && !empty($popup_options['drip_account_id'])) {
			$fields_data = $this->get_fields_html($popup_options['drip_api_token'], $popup_options['drip_account_id'], $popup_options['drip_custom_fields']);
			if ($fields_data['status'] == 'OK') echo $fields_data['html'];
		}
		echo '
							</div>
							<a class="ulp-button ulp-button-small" onclick="return ulp_drip_loadfields(this);"><i class="fas fa-check"></i><label>'.__('Load Custom Fields', 'ulp').'</label></a>
							<br /><em>'.__('Click the button to (re)load list of custom fields. Ignore if you do not need specify values of custom fields.', 'ulp').'</em>
							<script>
								function ulp_drip_loadfields(_object) {
									if (ulp_saving) return;
									ulp_saving = true;
									jQuery(_object).addClass("ulp-button-disabled");
									jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
									jQuery(".ulp-drip-custom-fields-html").slideUp(350);
									var post_data = {action: "ulp-drip-fields", ulp_api_token: jQuery("#ulp_drip_api_token").val(), ulp_account_id: jQuery("#ulp-drip-account-id").val()};
									jQuery.ajax({
										type	: "POST",
										url		: "'.admin_url('admin-ajax.php').'", 
										data	: post_data,
										success	: function(return_data) {
											jQuery(_object).removeClass("ulp-button-disabled");
											jQuery(_object).find("i").attr("class", "fas fa-check");
											var data;
											try {
												if (typeof return_data == "object") data = return_data;
												else data = jQuery.parseJSON(return_data);
												if (data.status == "OK") {
													jQuery(".ulp-drip-custom-fields-html").html(data.html);
													jQuery(".ulp-drip-custom-fields-html").slideDown(350);
												} else if (data.status == "ERROR") {
													ulp_global_message_show("danger", data.message);
												} else {
													ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
												}
											} catch(error) {
												ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
											}
											ulp_saving = false;
										},
										error	: function(XMLHttpRequest, textStatus, errorThrown) {
											jQuery(_object).removeClass("ulp-button-disabled");
											jQuery(_object).find("i").attr("class", "fas fa-check");
											ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
											ulp_saving = false;
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Tags', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_drip_tags_string" name="ulp_drip_tags_string" value="'.esc_html(implode(', ', $popup_options['drip_tags'])).'" class="widefat">
							<br /><em>'.__('Specify comma-separated list of tags that applies to subscribers.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('EU Consent', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_drip_eu_consent" name="ulp_drip_eu_consent" value="'.esc_html($popup_options['drip_eu_consent']).'" class="widefat">
							<br /><em>'.__('Specify whether the subscriber granted or denied GDPR consent. You can use field shortcode to associate EU Consent field with the popup field.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<script>
					function ulp_drip_add_fields(object) {
						jQuery("#drip-fields-template").before("<tr>"+jQuery("#drip-fields-template").html()+"</tr>");
						return false;
					}
					function ulp_drip_remove_fields(object) {
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
		if (isset($ulp->postdata["ulp_drip_enable"])) $popup_options['drip_enable'] = "on";
		else $popup_options['drip_enable'] = "off";
		if ($popup_options['drip_enable'] == 'on') {
			if (empty($popup_options['drip_account_id'])) $errors[] = __('Invalid Drip Account ID.', 'ulp');
			if (empty($popup_options['drip_api_token'])) $errors[] = __('Invalid Drip API Token.', 'ulp');
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
		if (isset($ulp->postdata["ulp_drip_enable"])) $popup_options['drip_enable'] = "on";
		else $popup_options['drip_enable'] = "off";

		$popup_options['drip_fields'] = array();
		foreach($this->fields as $key => $value) {
			if (isset($ulp->postdata['ulp_drip_field_'.$key])) {
				$popup_options['drip_fields'][$key] = stripslashes(trim($ulp->postdata['ulp_drip_field_'.$key]));
			}
		}
		
		$popup_options['drip_custom_fields'] = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_drip_custom_field_')) == 'ulp_drip_custom_field_') {
				$field = substr($key, strlen('ulp_drip_custom_field_'));
				$popup_options['drip_custom_fields'][$field] = stripslashes(trim($value));
			}
		}

		$popup_options['drip_tags'] = array();
		if (isset($ulp->postdata["ulp_drip_tags_string"])) {
			$items = explode(',', $ulp->postdata["ulp_drip_tags_string"]);
			$tags = array();
			foreach ($items as $item) {
				$item = trim($item);
				if (strlen($item) > 0) $popup_options['drip_tags'][] = $item;
			}
		}
		return array_merge($_popup_options, $popup_options);
	}
	function show_accounts() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_api_token']) || empty($_POST['ulp_api_token'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Token.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$accounts = array();
			$api_token = trim(stripslashes($_POST['ulp_api_token']));
			$result = $this->connect($api_token, 'accounts');			
			if ($result && array_key_exists('accounts', $result)) {
				if (is_array($result['accounts']) && sizeof($result['accounts']) > 0) {
					foreach ($result['accounts'] as $account) {
						if (is_array($account)) {
							if (array_key_exists('id', $account) && array_key_exists('name', $account)) {
								$accounts[$account['id']] = $account['name'];
							}
						}
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No accounts found!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Token!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$accounts_html = '';
			if (!empty($accounts)) {
				foreach ($accounts as $id => $name) {
					$accounts_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
				}
			} else $accounts_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No data found!', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $accounts_html;
			$return_object['items'] = sizeof($accounts);
			echo json_encode($return_object);
		}
		exit;
	}
	function show_campaigns() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_api_token']) || empty($_POST['ulp_api_token']) || !isset($_POST['ulp_account_id']) || empty($_POST['ulp_account_id'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Token or Account ID!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$campaigns = array();
			$api_token = trim(stripslashes($_POST['ulp_api_token']));
			$account_id = trim(stripslashes($_POST['ulp_account_id']));
			$result = $this->connect($api_token, urlencode($account_id).'/campaigns');
			if ($result && array_key_exists('campaigns', $result)) {
				if (is_array($result['campaigns']) && sizeof($result['campaigns']) > 0) {
					foreach ($result['campaigns'] as $campaign) {
						if (is_array($campaign)) {
							if (array_key_exists('id', $campaign) && array_key_exists('name', $campaign)) {
								$campaigns[$campaign['id']] = $campaign['name'];
							}
						}
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No campaigns found.', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Token or Account ID.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$list_html = '';
			if (!empty($campaigns)) {
				foreach ($campaigns as $id => $name) {
					$list_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
				}
			} else $list_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No data found!', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $list_html;
			$return_object['items'] = sizeof($campaigns);
			echo json_encode($return_object);
		}
		exit;
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_api_token']) || empty($_POST['ulp_api_token']) || !isset($_POST['ulp_account_id']) || empty($_POST['ulp_account_id'])) {
				$return_object = array('status' => 'ERROR', 'message' => __('Invalid API Token.', 'ulp'));
				echo json_encode($return_object);
				exit;
			}
			$api_token = trim(stripslashes($_POST['ulp_api_token']));
			$account_id = trim(stripslashes($_POST['ulp_account_id']));
			$return_object = $this->get_fields_html($api_token, $account_id, $this->default_popup_options['drip_custom_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_api_token, $_account_id, $_fields) {
		$result = $this->connect($_api_token, urlencode($_account_id).'/custom_field_identifiers');
		$fields = '';
		if (!empty($result) && is_array($result)) {
			if (array_key_exists('errors', $result)) {
				return array('status' => 'ERROR', 'message' => $result['errors'][0]['message']);
			} else if (!array_key_exists('custom_field_identifiers', $result)) {
				return array('status' => 'ERROR', 'message' => __('Inavlid server response.', 'ulp'));
			} else {
				if (is_array($result['custom_field_identifiers']) && sizeof($result['custom_field_identifiers']) > 0) {
					$unique_fields = array();
					foreach ($result['custom_field_identifiers'] as $field) {
						if (!array_key_exists($field, $this->fields)) {
							$unique_fields[] = $field;
						}
					}
					if (sizeof($unique_fields) > 0) {
						$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Drip fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
						foreach ($unique_fields as $field) {
							$fields .= '
				<tr>
					<td style="width: 200px;"><strong>'.esc_html($field).':</strong></td>
					<td>
						<input type="text" id="ulp_drip_custom_field_'.esc_html($field).'" name="ulp_drip_custom_field_'.esc_html($field).'" value="'.esc_html(array_key_exists($field, $_fields) ? $_fields[$field] : '').'" class="widefat" />
						<br /><em>'.esc_html($field).'</em>
					</td>
				</tr>';
						}
						$fields .= '
			</table>';
					} else {
						return array('status' => 'ERROR', 'message' => __('No custom fields found.', 'ulp'));
					}
				} else {
					return array('status' => 'ERROR', 'message' => __('No custom fields found.', 'ulp'));
				}
			}
		} else {
			return array('status' => 'ERROR', 'message' => __('Inavlid server response.', 'ulp'));
		}
		return array('status' => 'OK', 'html' => $fields);
	}
	
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['drip_enable'] == 'on') {
			$eu_consent = strtolower(strtr($popup_options['drip_eu_consent'], $_subscriber));
			if (in_array($eu_consent, array('on', 'true', 'yes', '1', 'granted'))) $eu_consent = 'granted';
			else if (in_array($eu_consent, array('off', 'false', 'no', '0', 'denied'))) $eu_consent = 'denied';
			else $eu_consent = 'unknown';
			$data = array(
				'email' => $_subscriber['{subscription-email}'], 
				'ip_address' => $_SERVER['REMOTE_ADDR'],
				'eu_consent' => $eu_consent
			);
			if (is_array($popup_options['drip_fields'])) {
				foreach ($popup_options['drip_fields'] as $key => $value) {
					if ($key != 'email' && !empty($value)) $data[$key] = strtr($value, $_subscriber);
				}
			}
			if (is_array($popup_options['drip_custom_fields'])) {
				foreach ($popup_options['drip_custom_fields'] as $key => $value) {
					if (!empty($value)) $data['custom_fields'][$key] = strtr($value, $_subscriber);
				}
			}
			if (!empty($popup_options['drip_tags'])) $data['tags'] = $popup_options['drip_tags'];
			$result = $this->connect($popup_options['drip_api_token'], urlencode($popup_options['drip_account_id']).'/subscribers', array("subscribers" => array($data)));
			$result = $this->connect($popup_options['drip_api_token'], urlencode($popup_options['drip_account_id']).'/campaigns/'.urlencode($popup_options['drip_campaign_id']).'/subscribers', array("subscribers" => array(array('email' => $_subscriber['{subscription-email}'], 'eu_consent' => $eu_consent))));
		}
	}
	function connect($_api_token, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Content-Type: application/vnd.api+json',
			'Accept: application/json'
		);
		try {
			$url = 'https://api.getdrip.com/v2/'.ltrim($_path, '/');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_USERPWD, $_api_token);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
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
$ulp_drip = new ulp_drip_class();
?>