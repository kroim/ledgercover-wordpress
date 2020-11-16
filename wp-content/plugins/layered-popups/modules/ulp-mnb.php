<?php
/* MyNewsletterBuilder integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_mnb_class {
	var $default_popup_options = array(
		'mnb_enable' => 'off',
		'mnb_api_key' => '',
		'mnb_list' => '',
		'mnb_list_id' => '',
		'mnb_fields' => array(),
		'mnb_double' => 'off'
	);
	var $fields = array(
		'email' => '{subscription-email}',
		'first_name' => '{subscription-name}',
		'middle_name' => '',
		'last_name' => '',
		'full_name' => '{subscription-name}',
		'company_name' => '',
		'job_title' => '',
		'phone_work' => '',
		'phone_home' => '{subscription-phone}',
		'address_1' => '',
		'address_2' => '',
		'address_3' => '',
		'city' => '',
		'state' => '',
		'zip' => '',
		'country' => '',
		'custom_1' => '',
		'custom_2' => '',
		'custom_3' => '',
		'custom_4' => '',
		'custom_5' => '',
		'custom_6' => '',
		'custom_7' => '',
		'custom_8' => '',
		'custom_9' => '',
		'custom_10' => ''
	);
	var $field_labels = array(
		'email' => array('title' => 'E-mail', 'description' => 'Email address of the contact.'),
		'first_name' => array('title' => 'First name', 'description' => 'First name of the contact.'),
		'middle_name' => array('title' => 'Middle name', 'description' => 'Middle name of the contact.'),
		'last_name' => array('title' => 'Last name', 'description' => 'Last name of the contact.'),
		'full_name' => array('title' => 'Full name', 'description' => 'Full name of the contact.'),
		'company_name' => array('title' => 'Company', 'description' => 'Company name of the contact.'),
		'job_title' => array('title' => 'Job title', 'description' => 'Job title of the contact.'),
		'phone_work' => array('title' => 'Work phone', 'description' => 'Work phone number.'),
		'phone_home' => array('title' => 'Home phone', 'description' => 'Home phone number.'),
		'address_1' => array('title' => 'Address', 'description' => 'Address of the contact.'),
		'address_2' => array('title' => 'Address 2', 'description' => 'Address of the contact.'),
		'address_3' => array('title' => 'Address 3', 'description' => 'Address of the contact.'),
		'city' => array('title' => 'City', 'description' => 'City of the contact.'),
		'state' => array('title' => 'State', 'description' => 'State of the contact.'),
		'zip' => array('title' => 'Postal code', 'description' => 'ZIP / Postal code of the contact.'),
		'country' => array('title' => 'Country', 'description' => 'Country of the contact.')
	);
	function __construct() {
		$this->default_popup_options['mnb_fields'] = $this->fields;
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
			add_action('wp_ajax_ulp-mnb-lists', array(&$this, "show_lists"));
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
				<h3>'.__('MyNewsletterBuilder Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable MyNewsletterBuilder', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mnb_enable" name="ulp_mnb_enable" '.($popup_options['mnb_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to MyNewsletterBuilder', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to MyNewsletterBuilder.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mnb_api_key" name="ulp_mnb_api_key" value="'.esc_html($popup_options['mnb_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your MyNewsletterBuilder API Key. You can request it <a href="https://www.mynewsletterbuilder.com/contact" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-mnb-list" name="ulp_mnb_list" value="'.esc_html($popup_options['mnb_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_mnb_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-mnb-list-id" name="ulp_mnb_list_id" value="'.esc_html($popup_options['mnb_list_id']).'" />
							<div id="ulp-mnb-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_mnb_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-mnb-lists", "ulp_api_key": jQuery("#ulp_mnb_api_key").val()});
								}
							</script>
						</td>
					</tr>';
		$fields = array_merge($this->fields, $popup_options['mnb_fields']);
		echo '
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-mnb-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate MyNewsletterBuilder fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
		foreach ($this->fields as $key => $value) {
			if (strpos($key, 'custom_') !== false) {
				$number = substr($key, strlen('custom_'));
				$title = 'Custom '.$number;
				$description = 'Custom field '.$number.'.';
			} else {
				$title = $this->field_labels[$key]['title'];
				$description = $this->field_labels[$key]['description'];
			}
			echo '
									<tr>
										<td style="width: 100px;"><strong>'.esc_html($title).':</strong></td>
										<td>
											<input type="text" id="ulp_mnb_field_'.esc_html($key).'" name="ulp_mnb_field_'.esc_html($key).'" value="'.esc_html($fields[$key]).'" class="widefat"'.($key == 'email' ? ' readonly="readonly"' : '').' />
											<br /><em>'.esc_html($description).'</em>
										</td>
									</tr>';
		}
		echo '
								</table>
							</div>
						</td>
					</tr>
					<tr>
						<th>'.__('Double opt-in', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mnb_double" name="ulp_mnb_double" '.($popup_options['mnb_double'] == "on" ? 'checked="checked"' : '').'"> '.__('Ask users to confirm their subscription', 'ulp').'
							<br /><em>'.__('Control whether a double opt-in confirmation message is sent.', 'ulp').'</em>
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
		if (isset($ulp->postdata["ulp_mnb_enable"])) $popup_options['mnb_enable'] = "on";
		else $popup_options['mnb_enable'] = "off";
		if ($popup_options['mnb_enable'] == 'on') {
			if (empty($popup_options['mnb_api_key'])) $errors[] = __('Invalid MyNewsletterBuilder API Key', 'ulp');
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
		if (isset($ulp->postdata["ulp_mnb_enable"])) $popup_options['mnb_enable'] = "on";
		else $popup_options['mnb_enable'] = "off";
		if (isset($ulp->postdata["ulp_mnb_double"])) $popup_options['mnb_double'] = "on";
		else $popup_options['mnb_double'] = "off";
		
		$popup_options['mnb_fields'] = array();
		foreach($this->fields as $key => $value) {
			if (isset($ulp->postdata['ulp_mnb_field_'.$key])) {
				$popup_options['mnb_fields'][$key] = stripslashes(trim($ulp->postdata['ulp_mnb_field_'.$key]));
			}
		}
			
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['mnb_enable'] == 'on') {
			$data = array(
				'details' => array('email' => $_subscriber['{subscription-email}']),
				'skip_opt_in' => $popup_options['mnb_double'] == 'on' ? false : true,
				'lists' => array($popup_options['mnb_list_id']),
				'update_existing' => true
			);
			$fields = array_merge($this->fields, $popup_options['mnb_fields']);
			foreach ($fields as $key => $value) {
				if (!empty($value) && $key != 'email') {
					$data['details'][$key] = strtr($value, $_subscriber);
				}
			}
			$result = $this->connect($popup_options['mnb_api_key'], 'Subscribe', $data);
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
			$api_key = trim(stripslashes($_POST['ulp_api_key']));
			
			$result = $this->connect($api_key, 'Lists');
			if (is_array($result)) {
				if (!empty($result) && array_key_exists(0, $result) && array_key_exists('id', $result[0])) {
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
					if (array_key_exists('errstr', $result)) {
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.esc_html($result['errstr']).'</div>';
					} else {
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response!', 'ulp').'</div>';
					}
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
	function connect($_api_key, $_path, $_data = array()) {
		$_data['api_key'] = $_api_key;
		try {
			$url = 'http://api.mynewsletterbuilder.com/1.0.2/'.trim($_path, '/').'/json';
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
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
$ulp_mnb = new ulp_mnb_class();
?>