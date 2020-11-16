<?php
/* GetResponse integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_getresponse_class {
	var $default_popup_options = array(
		"getresponse_enable" => "off",
		"getresponse_api_key" => "",
		"getresponse_campaign" => "",
		"getresponse_campaign_id" => "",
		"getresponse_fields" => ""
	);
	function __construct() {
		$this->default_popup_options['getresponse_fields'] = serialize(array());
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-getresponse-campaigns', array(&$this, "show_campaigns"));
			add_action('wp_ajax_ulp-getresponse-fields', array(&$this, "show_fields"));
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
				<h3>'.__('GetResponse Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable GetResponse', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_getresponse_enable" name="ulp_getresponse_enable" '.($popup_options['getresponse_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to GetResponse', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to GetResponse.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_getresponse_api_key" name="ulp_getresponse_api_key" value="'.esc_html($popup_options['getresponse_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your GetResponse API Key. You can get your API Key <a href="https://app.getresponse.com/my_api_key.html" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Campaign ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-getresponse-list" name="ulp_getresponse_campaign" value="'.esc_html($popup_options['getresponse_campaign']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_getresponse_campaigns_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-getresponse-list-id" name="ulp_getresponse_campaign_id" value="'.esc_html($popup_options['getresponse_campaign_id']).'" />
							<div id="ulp-getresponse-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your Campaign ID.', 'ulp').'</em>
							<script>
								function ulp_getresponse_campaigns_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-getresponse-campaigns", "ulp_api_key": jQuery("#ulp_getresponse_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Custom Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-getresponse-fields-html">';
		if (!empty($popup_options['getresponse_api_key'])) {
			$fields = $this->get_fields_html($popup_options['getresponse_api_key'], $popup_options['getresponse_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_getresponse_fields_button" class="ulp_button button-secondary" onclick="return ulp_getresponse_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-getresponse-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_getresponse_loadfields() {
									jQuery("#ulp-getresponse-fields-loading").fadeIn(350);
									jQuery(".ulp-getresponse-fields-html").slideUp(350);
									var data = {action: "ulp-getresponse-fields", ulp_key: jQuery("#ulp_getresponse_api_key").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-getresponse-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-getresponse-fields-html").html(data.html);
												jQuery(".ulp-getresponse-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-getresponse-fields-html").html("<div class=\'ulp-getresponse-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to GetResponse server.', 'ulp').'</strong></div>");
												jQuery(".ulp-getresponse-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-getresponse-fields-html").html("<div class=\'ulp-getresponse-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to GetResponse server.', 'ulp').'</strong></div>");
											jQuery(".ulp-getresponse-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_getresponse_enable"])) $popup_options['getresponse_enable'] = "on";
		else $popup_options['getresponse_enable'] = "off";
		if ($popup_options['getresponse_enable'] == 'on') {
			if (empty($popup_options['getresponse_api_key'])) $errors[] = __('Invalid GetResponse API Key.', 'ulp');
			if (empty($popup_options['getresponse_campaign_id'])) $errors[] = __('Invalid GetResponse Campaign ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_getresponse_enable"])) $popup_options['getresponse_enable'] = "on";
		else $popup_options['getresponse_enable'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_getresponse_field_')) == 'ulp_getresponse_field_') {
				$field = substr($key, strlen('ulp_getresponse_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['getresponse_fields'] = serialize($fields);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['getresponse_enable'] == 'on') {
			$data = array(
				'campaign' => array('campaignId' => $popup_options['getresponse_campaign_id']),
				'name' => strlen($_subscriber['{subscription-name}']) >= 2 ? $_subscriber['{subscription-name}'] : $_subscriber['{subscription-email}'],
				'email' => $_subscriber['{subscription-email}'],
				'dayOfCycle' => 0,
				'ipAddress' => $_SERVER['REMOTE_ADDR']
			);
			$fields = array();
			if (!empty($popup_options['getresponse_fields'])) $fields = unserialize($popup_options['getresponse_fields']);
			if (!empty($fields) && is_array($fields)) {
				foreach ($fields as $key => $value) {
					if (!empty($value)) {
						$data['customFieldValues'][] = array('customFieldId' => $key, 'value' => array(strtr($value, $_subscriber)));
					}
				}
			}
			$result = $this->connect($popup_options['getresponse_api_key'], 'contacts?query[email]='.$_subscriber['{subscription-email}']);
			if (empty($result)) {
				$result = $this->connect($popup_options['getresponse_api_key'], 'contacts', $data);
			} else {
				$contact_id = $result[0]['contactId'];
				$result = $this->connect($popup_options['getresponse_api_key'], 'contacts/'.$contact_id, $data);
			}
		}
	}
	function show_campaigns() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$campaigns = array();
			if (!isset($_POST['ulp_api_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_api_key']));
			
			$result = $this->connect($key, 'campaigns?page=1&perPage=100');
			if (is_array($result) && !empty($result)) {
				if (array_key_exists('code', $result)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.esc_html($result['codeDescription']).'</div>';
					echo json_encode($return_object);
					exit;
				} else {
					foreach ($result as $campaign) {
						if (is_array($campaign)) {
							if (array_key_exists('campaignId', $campaign) && array_key_exists('name', $campaign)) {
								$campaigns[$campaign['campaignId']] = $campaign['name'];
							}
						}
					}
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key!', 'ulp').'</div>';
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
			if (!isset($_POST['ulp_key']) || empty($_POST['ulp_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-getresponse-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Key!', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_key']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($key, $this->default_popup_options['getresponse_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_key, $_fields) {
		$result = $this->connect($_key, 'custom-fields?page=1&perPage=100');
		$fields = '';
		$values = unserialize($_fields);
		if (!is_array($values)) $values = array();
		if (is_array($result)) {
			if (array_key_exists('code', $result)) {
				$fields = '<div class="ulp-getresponse-grouping" style="margin-bottom: 10px;"><strong>'.esc_html($result['codeDescription']).'</strong></div>';
			} else {
				if (!empty($result)) {
					$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate GetResponse fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
					foreach ($result as $field) {
						if (is_array($field)) {
							if (array_key_exists('customFieldId', $field) && array_key_exists('name', $field)) {
								$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html(ucfirst($field['name'])).':</strong></td>
					<td>
						<input type="text" id="ulp_getresponse_field_'.esc_html($field['customFieldId']).'" name="ulp_getresponse_field_'.esc_html($field['customFieldId']).'" value="'.esc_html(array_key_exists($field['customFieldId'], $values) ? $values[$field['customFieldId']] : '').'" class="widefat" />
						<br /><em>'.esc_html($field['name']).'</em>
					</td>
				</tr>';
							}
						}
					}
					$fields .= '
			</table>';
				} else {
					$fields = '<div class="ulp-getresponse-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
				}
			}
		} else {
			$fields = '<div class="ulp-getresponse-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_api_key, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'X-Auth-Token: api-key '.$_api_key,
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		try {
			$url = 'https://api.getresponse.com/v3/'.ltrim($_path, '/');
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
$ulp_getresponse = new ulp_getresponse_class();
?>