<?php
/* Zoho Campaigns integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_zohocampaigns_class {
	var $default_popup_options = array(
		"zohocampaigns_enable" => "off",
		"zohocampaigns_api_key" => "",
		"zohocampaigns_domain" => "zoho.eu",
		"zohocampaigns_list" => "",
		"zohocampaigns_list_id" => "",
		"zohocampaigns_fields" => array(
			'contact_email' => '{subscription-email}'
		),
		"zohocampaigns_fieldnames" => array()
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-zohocampaigns-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-zohocampaigns-fields', array(&$this, "show_fields"));
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
				<h3>'.__('Zoho Campaigns Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Zoho Campaigns', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_zohocampaigns_enable" name="ulp_zohocampaigns_enable" '.($popup_options['zohocampaigns_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Zoho Campaigns', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Zoho Campaigns.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Zoho Domain', 'ulp').':</th>
						<td>
							<select id="ulp_zohocampaigns_domain" name="ulp_zohocampaigns_domain">
								<option value="zoho.com"'.($popup_options['zohocampaigns_domain'] == 'zoho.com' ? ' selected="selected"' : '').'>zoho.com</option>
								<option value="zoho.eu"'.($popup_options['zohocampaigns_domain'] == 'zoho.eu' ? ' selected="selected"' : '').'>zoho.eu</option>
							</select>
							<br /><em>'.__('Select your Zoho Domain.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_zohocampaigns_api_key" name="ulp_zohocampaigns_api_key" value="'.esc_html($popup_options['zohocampaigns_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Zoho Campaigns API Key (Authentication Token). You can get it <a href="https://campaigns.zoho.eu/campaigns/home.do#settings/api" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-zohocampaigns-list" name="ulp_zohocampaigns_list" value="'.esc_html($popup_options['zohocampaigns_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_zohocampaigns_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-zohocampaigns-list-id" name="ulp_zohocampaigns_list_id" value="'.esc_html($popup_options['zohocampaigns_list_id']).'" />
							<div id="ulp-zohocampaigns-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_zohocampaigns_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-zohocampaigns-lists", "ulp_api_key": jQuery("#ulp_zohocampaigns_api_key").val(), "ulp_domain": jQuery("#ulp_zohocampaigns_domain").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-zohocampaigns-fields-html">';
		if (!empty($popup_options['zohocampaigns_api_key'])) {
			$fields = $this->get_fields_html($popup_options['zohocampaigns_api_key'], $popup_options['zohocampaigns_domain'], $popup_options['zohocampaigns_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_zohocampaigns_fields_button" class="ulp_button button-secondary" onclick="return ulp_zohocampaigns_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-zohocampaigns-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_zohocampaigns_loadfields() {
									jQuery("#ulp-zohocampaigns-fields-loading").fadeIn(350);
									jQuery(".ulp-zohocampaigns-fields-html").slideUp(350);
									var data = {action: "ulp-zohocampaigns-fields", ulp_key: jQuery("#ulp_zohocampaigns_api_key").val(), ulp_domain: jQuery("#ulp_zohocampaigns_domain").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-zohocampaigns-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-zohocampaigns-fields-html").html(data.html);
												jQuery(".ulp-zohocampaigns-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-zohocampaigns-fields-html").html("<div class=\'ulp-zohocampaigns-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Zoho Campaigns server.', 'ulp').'</strong></div>");
												jQuery(".ulp-zohocampaigns-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-zohocampaigns-fields-html").html("<div class=\'ulp-zohocampaigns-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Zoho Campaigns server.', 'ulp').'</strong></div>");
											jQuery(".ulp-zohocampaigns-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_zohocampaigns_enable"])) $popup_options['zohocampaigns_enable'] = "on";
		else $popup_options['zohocampaigns_enable'] = "off";
		if ($popup_options['zohocampaigns_enable'] == 'on') {
			if (empty($popup_options['zohocampaigns_api_key'])) $errors[] = __('Invalid Zoho Campaigns API Key.', 'ulp');
			if (empty($popup_options['zohocampaigns_list_id'])) $errors[] = __('Invalid Zoho Campaigns List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_zohocampaigns_enable"])) $popup_options['zohocampaigns_enable'] = "on";
		else $popup_options['zohocampaigns_enable'] = "off";
		
		$fields = array();
		$fieldnames = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_zohocampaigns_field_')) == 'ulp_zohocampaigns_field_') {
				$field = substr($key, strlen('ulp_zohocampaigns_field_'));
				$fields[$field] = stripslashes(trim($value));
				$fieldnames[$field] = stripslashes(trim($ulp->postdata['ulp_zohocampaigns_fieldname_'.$field]));
			}
		}
		$popup_options['zohocampaigns_fields'] = $fields;
		$popup_options['zohocampaigns_fieldnames'] = $fieldnames;
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['zohocampaigns_enable'] == 'on') {
			$fields = array();
			if (!empty($popup_options['zohocampaigns_fields']) && is_array($popup_options['zohocampaigns_fields'])) {
				foreach ($popup_options['zohocampaigns_fields'] as $key => $value) {
					if (!empty($value)) {
						$fields[$popup_options['zohocampaigns_fieldnames'][$key]] = strtr($value, $_subscriber);
					}
				}
			}
			$data = array(
				'listkey' => $popup_options['zohocampaigns_list_id'],
				'resfmt' => 'JSON',
				'contactinfo' => json_encode($fields)
			);
			$result = $this->connect($popup_options['zohocampaigns_api_key'], $popup_options['zohocampaigns_domain'], 'json/listsubscribe', $data);
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
			$domain = trim(stripslashes($_POST['ulp_domain']));
			
			$result = $this->connect($key, $domain, 'getmailinglists?resfmt=JSON&fromindex=0&range=100&sort=asc');

			if (is_array($result) && array_key_exists('status', $result) && $result['status'] == 'success') {
				if (array_key_exists('list_of_details', $result) && intval($result['list_of_details']) > 0) {
					foreach ($result['list_of_details'] as $list) {
						if (is_array($list)) {
							if (array_key_exists('listkey', $list) && array_key_exists('listname', $list)) {
								$lists[$list['listkey']] = $list['listname'];
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
			} else if (is_array($result) && array_key_exists('status', $result) && $result['status'] == 'error') {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.$result['message'].'</div>';
				echo json_encode($return_object);
				exit;
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to Zoho server!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$list_html = '';
			if (!empty($lists)) {
				foreach ($lists as $id => $name) {
					$list_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.(!empty($name) ? esc_html($name) : esc_html($id)).'" onclick="return ulp_input_options_selected(this);">'.(!empty($name) ? esc_html($name) : esc_html($id)).'</a>';
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
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_key']) || empty($_POST['ulp_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-zohocampaigns-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Key.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_key']));
			$domain = trim(stripslashes($_POST['ulp_domain']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($key, $domain, $this->default_popup_options['zohocampaigns_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_key, $_domain, $_fields) {
		$result = $this->connect($_key, $_domain, 'contact/allfields?type=json');
		$fields = '';
		if (is_array($result) && array_key_exists('response', $result) && array_key_exists('fieldnames', $result['response']) && array_key_exists('fieldname', $result['response']['fieldnames'])) {
			if (sizeof($result['response']['fieldnames']['fieldname']) > 0) {
				$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Zoho Campaigns fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
				foreach ($result['response']['fieldnames']['fieldname'] as $field) {
					if (is_array($field)) {
						if (array_key_exists('FIELD_NAME', $field) && array_key_exists('DISPLAY_NAME', $field)) {
							$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['DISPLAY_NAME']).':</strong></td>
					<td>
						<input type="text" id="ulp_zohocampaigns_field_'.esc_html($field['FIELD_NAME']).'" name="ulp_zohocampaigns_field_'.esc_html($field['FIELD_NAME']).'" value="'.esc_html(array_key_exists($field['FIELD_NAME'], $_fields) ? $_fields[$field['FIELD_NAME']] : '').'" class="widefat"'.($field['FIELD_NAME'] == 'contact_email' ? ' readonly="readonly"' : '').' />
						<input type="hidden" id="ulp_zohocampaigns_fieldname_'.esc_html($field['FIELD_NAME']).'" name="ulp_zohocampaigns_fieldname_'.esc_html($field['FIELD_NAME']).'" value="'.esc_html($field['DISPLAY_NAME']).'" />
						<br /><em>'.esc_html($field['DISPLAY_NAME']).'</em>
					</td>
				</tr>';
						}
					}
				}
				$fields .= '
			</table>';
			} else {
				$fields = '<div class="ulp-zohocampaigns-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
			}
		} else if (is_array($result) && array_key_exists('status', $result) && $result['status'] == 'error') {
			$fields = '<div class="ulp-zohocampaigns-grouping" style="margin-bottom: 10px;"><strong>'.$result['message'].'</strong></div>';
		} else {
			$fields = '<div class="ulp-zohocampaigns-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_api_key, $_domain, $_path, $_data = array(), $_method = '') {
		try {
			if (!in_array($_domain, array('zoho.eu', 'zoho.com'))) $_domain = 'zoho.com';
			$url = 'https://campaigns.'.$_domain.'/api/'.ltrim($_path, '/');
			if (!empty($_data)) $_data = array_merge($_data, array('authtoken' => $_api_key, 'scope' => 'CampaignsAPI'));
			else $url .= '&scope=CampaignsAPI&authtoken='.$_api_key;
			$curl = curl_init($url);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
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
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
}
$ulp_zohocampaigns = new ulp_zohocampaigns_class();
?>