<?php
/* Bitrix24 integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_bitrix24_class {
	var $default_popup_options = array(
		"bitrix24_enable" => "off",
		"bitrix24_url" => "",
		"bitrix24_fields" => array(
			'NAME' => '{subscription-name}',
			'EMAIL' => '{subscription-email}',
			'PHONE' => '{subscription-phone}'
		)
	);
	var $multiple = array('PHONE', 'EMAIL', 'WEB', 'IM');
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-bitrix24-fields', array(&$this, "show_fields"));
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
				<h3>'.__('Bitrix24 Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Bitrix24', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_bitrix24_enable" name="ulp_bitrix24_enable" '.($popup_options['bitrix24_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Bitrix24', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Bitrix24.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Bitrix24 REST call example URL', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_bitrix24_url" name="ulp_bitrix24_url" value="'.esc_html($popup_options['bitrix24_url']).'" class="widefat">
							<br /><em>'.__('Enter your Bitrix24 REST call example URL. In your Bitrix24 account go to Applications >> Webhooks and create Inbound webhook with CRM access permissions. Paste provided REST call example URL here.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-bitrix24-fields-html">';
		if (!empty($popup_options['bitrix24_url'])) {
			$fields_data = $this->get_fields_html($popup_options['bitrix24_url'], $popup_options['bitrix24_fields']);
			if ($fields_data['status'] == 'OK') echo $fields_data['html'];
		}
		echo '
							</div>
							<a class="ulp-button ulp-button-small" onclick="return ulp_bitrix24_loadfields(this);"><i class="fas fa-check"></i><label>'.__('Load Fields', 'ulp').'</label></a>
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_bitrix24_loadfields(_object) {
									if (ulp_saving) return;
									ulp_saving = true;
									jQuery(_object).addClass("ulp-button-disabled");
									jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
									jQuery(".ulp-bitrix24-fields-html").slideUp(350);
									var post_data = {action: "ulp-bitrix24-fields", ulp_url: jQuery("#ulp_bitrix24_url").val()};
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
													jQuery(".ulp-bitrix24-fields-html").html(data.html);
													jQuery(".ulp-bitrix24-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_bitrix24_enable"])) $popup_options['bitrix24_enable'] = "on";
		else $popup_options['bitrix24_enable'] = "off";
		if ($popup_options['bitrix24_enable'] == 'on') {
			if (empty($popup_options['bitrix24_url']) || !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $popup_options['bitrix24_url'])) $errors[] = __('Invalid Bitrix24 REST call example URL.', 'ulp');
			else {
				$url_path = parse_url($popup_options['bitrix24_url'], PHP_URL_PATH);
				if ($url_path) {
					$url_path_parts = explode('/', trim($url_path, '/'));
					if (sizeof($url_path_parts) != 4 || $url_path_parts[0] != 'rest' || $url_path_parts[3] != 'profile' || !is_numeric($url_path_parts[1])) $errors[] = __('Bitrix24 REST call example URL must look like "https://&lt;xxxxxxx&gt;.bitrix24.ru/rest/&lt;n&gt;/&lt;xxxxxxxxxxxxx&gt;/profile/".', 'ulp');
				} else $errors[] = __('Invalid Bitrix24 REST call example URL.', 'ulp');
				
			}
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
		if (isset($ulp->postdata["ulp_bitrix24_enable"])) $popup_options['bitrix24_enable'] = "on";
		else $popup_options['bitrix24_enable'] = "off";
		
		$popup_options['bitrix24_fields'] = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_bitrix24_field_')) == 'ulp_bitrix24_field_') {
				$field = substr($key, strlen('ulp_bitrix24_field_'));
				$popup_options['bitrix24_fields'][$field] = stripslashes(trim($value));
			}
		}
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		//if (empty($_subscriber['{subscription-email}']) && empty($_subscriber['{subscription-phone}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['bitrix24_enable'] == 'on') {
			$post_data = array(
				'fields' => array(),
				'params' => array("REGISTER_SONET_EVENT" => "Y")
			);
			if (!empty($popup_options['bitrix24_fields']) && is_array($popup_options['bitrix24_fields'])) {
				foreach($popup_options['bitrix24_fields'] as $id => $value) {
					if (!empty($value)) {
						if (in_array($id, $this->multiple)) $post_data['fields'][$id] = array(array("VALUE" => strtr($value, $_subscriber), "VALUE_TYPE" => "HOME"));
						else $post_data['fields'][$id] = strtr($value, $_subscriber);
					}
				}
			}
			if (array_key_exists('EMAIL', $post_data['fields']) && !empty($post_data['fields']['EMAIL'][0]['VALUE'])) $result = $this->connect($popup_options['bitrix24_url'], 'crm.lead.list/?filter[EMAIL]='.strtolower($post_data['fields']['EMAIL'][0]['VALUE']));
			else if (array_key_exists('PHONE', $post_data['fields']) && !empty($post_data['fields']['PHONE'][0]['VALUE'])) $result = $this->connect($popup_options['bitrix24_url'], 'crm.lead.list/?filter[PHONE]='.urlencode($post_data['fields']['PHONE'][0]['VALUE']));
			else return;
			if (!empty($result) && is_array($result) && array_key_exists('result', $result) && sizeof($result['result']) > 0) {
				$post_data['id'] = $result['result'][0]['ID'];
				$result = $this->connect($popup_options['bitrix24_url'], 'crm.lead.update', $post_data);
			} else {
				$result = $this->connect($popup_options['bitrix24_url'], 'crm.lead.add', $post_data);
			}
		}
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_url']) || empty($_POST['ulp_url'])) {
				$return_object = array('status' => 'ERROR', 'message' => __('Invalid REST call example URL.', 'ulp'));
				echo json_encode($return_object);
				exit;
			}
			$url = trim(stripslashes($_POST['ulp_url']));
			$return_object = $this->get_fields_html($url, $this->default_popup_options['bitrix24_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_url, $_fields) {
		$result = $this->connect($_url, 'crm.lead.fields');
		$fields = '';
		if (!empty($result) && is_array($result)) {
			if (array_key_exists('error_description', $result)) {
				$fields = '<div class="ulp-bitrix24-grouping" style="margin-bottom: 10px;"><strong>'.$result['error_description'].'</strong></div>';
			} else {
				if (array_key_exists('result', $result) && sizeof($result['result']) > 0) {
					$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Bitrix24 fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
					foreach ($result['result'] as $id => $field) {
						if (is_array($field)) {
							if ((array_key_exists('title', $field) || array_key_exists('listLabel', $field)) && array_key_exists('isReadOnly', $field) && $field['isReadOnly'] === false) {
								$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.(array_key_exists('listLabel', $field) && !empty($field['listLabel']) ? esc_html($field['listLabel']) : esc_html($field['title'])).':</strong></td>
					<td>
						<input type="text" id="ulp_bitrix24_field_'.esc_html($id).'" name="ulp_bitrix24_field_'.esc_html($id).'" value="'.($id == 'EMAIL' ? '{subscription-email}' : esc_html(array_key_exists($id, $_fields) ? $_fields[$id] : '')).'" class="widefat"'.($id == 'EMAIL' ? ' readonly="readonly"' : '').' />
						<br /><em>'.esc_html((array_key_exists('listLabel', $field) && !empty($field['listLabel']) ? $field['listLabel'] : $field['title']).' ('.$id.')').'</em>
					</td>
				</tr>';
							}
						}
					}
					$fields .= '
			</table>';
				} else {
					return array('status' => 'ERROR', 'message' => __('No fields found.', 'ulp'));
				}
			}
		} else {
			return array('status' => 'ERROR', 'message' => __('Inavlid server response.', 'ulp'));
		}
		return array('status' => 'OK', 'html' => $fields);
	}
	function connect($_url, $_path, $_data = array(), $_method = '') {
		$url = rtrim(str_replace('/profile', '/', $_url), '/').'/'.ltrim($_path, '/');
		try {
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
$ulp_bitrix24 = new ulp_bitrix24_class();
?>