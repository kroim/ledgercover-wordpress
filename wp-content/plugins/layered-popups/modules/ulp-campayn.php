<?php
/* Campayn integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_campayn_class {
	var $default_popup_options = array(
		'campayn_enable' => 'off',
		'campayn_url' => '',
		'campayn_api_key' => '',
		'campayn_list' => '',
		'campayn_list_id' => '',
		'campayn_fields' => ''
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
		'birthday' => '',
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
		'birthday' => array('title' => 'Date of birth', 'description' => 'Date of birth of the contact.'),
		'phone' => array('title' => 'Phone #', 'description' => 'Phone number of the contact.'),
		'website' => array('title' => 'Website URL', 'description' => 'Website URL of the contact.')
	);
	function __construct() {
		$this->default_popup_options['campayn_fields'] = serialize($this->fields);
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-campayn-lists', array(&$this, "show_lists"));
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
				<h3>'.__('Campayn Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Campayn', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_campayn_enable" name="ulp_campayn_enable" '.($popup_options['campayn_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Campayn', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Campayn.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Site URL', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_campayn_url" name="ulp_campayn_url" value="'.esc_html($popup_options['campayn_url']).'" class="widefat">
							<br /><em>'.__('Enter unique website address of your account. Usually it looks like SITE-NAME.campayn.com', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_campayn_api_key" name="ulp_campayn_api_key" value="'.esc_html($popup_options['campayn_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Campayn API Key. Go to your Campayn account, click "Settings" and "API Key".', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID:', 'ulp').'</th>
						<td>
							<input type="text" id="ulp-campayn-list" name="ulp_campayn_list" value="'.esc_html($popup_options['campayn_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_campayn_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-campayn-list-id" name="ulp_campayn_list_id" value="'.esc_html($popup_options['campayn_list_id']).'" />
							<div id="ulp-campayn-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_campayn_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-campayn-lists", "ulp_url": jQuery("#ulp_campayn_url").val(), "ulp_api_key": jQuery("#ulp_campayn_api_key").val()});
								}
							</script>
						</td>
					</tr>';
		if ($ulp->ext_options['enable_customfields'] == 'on') {
			$fields = unserialize($popup_options['campayn_fields']);
			if (is_array($fields)) $fields = array_merge($this->fields, $fields);
			else $fields = $this->fields;
			echo '
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-campayn-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Campayn fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
			foreach ($this->fields as $key => $value) {
				echo '
									<tr>
										<td style="width: 100px;"><strong>'.esc_html($this->field_labels[$key]['title']).':</strong></td>
										<td>
											<input type="text" id="ulp_campayn_field_'.esc_html($key).'" name="ulp_campayn_field_'.esc_html($key).'" value="'.esc_html($fields[$key]).'" class="widefat"'.($key == 'email' ? ' readonly="readonly"' : '').' />
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
		if (isset($ulp->postdata["ulp_campayn_enable"])) $popup_options['campayn_enable'] = "on";
		else $popup_options['campayn_enable'] = "off";
		if ($popup_options['campayn_enable'] == 'on') {
			if (empty($popup_options['campayn_url'])) $errors[] = __('Invalid Campayn Site URL', 'ulp');
			if (empty($popup_options['campayn_api_key'])) $errors[] = __('Invalid Campayn API key', 'ulp');
			if (empty($popup_options['campayn_list_id'])) $errors[] = __('Invalid Campayn list ID', 'ulp');
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
		if (isset($ulp->postdata["ulp_campayn_enable"])) $popup_options['campayn_enable'] = "on";
		else $popup_options['campayn_enable'] = "off";
		if ($ulp->ext_options['enable_customfields'] == 'on') {
			$fields = array();
			foreach($this->fields as $key => $value) {
				if (isset($ulp->postdata['ulp_campayn_field_'.$key])) {
					$fields[$key] = stripslashes(trim($ulp->postdata['ulp_campayn_field_'.$key]));
				}
			}
			$popup_options['campayn_fields'] = serialize($fields);
		}
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['campayn_enable'] == 'on') {
			if ($ulp->ext_options['enable_customfields'] == 'on') {
				$fields = unserialize($_popup_options['campayn_fields']);
				if (is_array($fields)) $fields = array_merge($this->fields, $fields);
				else $fields = $this->fields;
				$data = array(
					'email' => $_subscriber['{subscription-email}']
				);
				foreach ($fields as $key => $value) {
					if (!empty($value) && $key != 'email') {
						if ($key == 'phone') $data['phones'] = array(array('value' => strtr($value, $_subscriber), 'type' => 'home'));
						else if ($key == 'website') $data['sites'] = array(array('value' => strtr($value, $_subscriber), 'type' => 'personal'));
						else $data[$key] = strtr($value, $_subscriber);
					}
				}
			} else {
				$data = array(
					'email' => $_subscriber['{subscription-email}'],
					'first_name' => $_subscriber['{subscription-name}']
				);
				if (!empty($_subscriber['{subscription-phone}'])) $data['phones'] = array(array('value' => $_subscriber['{subscription-phone}'], 'type' => 'home'));
			}

			$result = $this->connect($popup_options['campayn_api_key'], rtrim($popup_options['campayn_url'], '/').'/api/v1/lists/'.$popup_options['campayn_list_id'].'/contacts.json?filter[contact]='.rawurlencode($_subscriber['{subscription-email}']));
			if (empty($result)) {
				$result = $this->connect($popup_options['campayn_api_key'], rtrim($popup_options['campayn_url'], '/').'/api/v1/lists/'.$popup_options['campayn_list_id'].'/contacts.json', $data);
			} else {
				$contact_id = $result[0]['id'];
				$contact_data = $this->connect($popup_options['campayn_api_key'], rtrim($popup_options['campayn_url'], '/').'/api/v1/contacts/'.$contact_id.'.json');
				if (is_array($contact_data)) $data = array_merge($contact_data, $data);
				$result = $this->connect($popup_options['campayn_api_key'], rtrim($popup_options['campayn_url'], '/').'/api/v1/contacts/'.$contact_id.'.json', $data, 'PUT');
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_url']) || !isset($_POST['ulp_api_key']) || empty($_POST['ulp_url']) || empty($_POST['ulp_api_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Site URL or API Key!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$campayn_url = trim(stripslashes($_POST['ulp_url']));
			$api_key = trim(stripslashes($_POST['ulp_api_key']));
			
			if (!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $campayn_url)) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Site URL!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$lists = array();
			$result = $this->connect($api_key, rtrim($campayn_url, '/').'/api/v1/lists.json');
			
			if ($result) {
				if (array_key_exists("msg", $result)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.esc_html($result['msg']).'</div>';
					echo json_encode($return_object);
					exit;
				}
				foreach($result as $list) {
					if (is_array($list)) {
						if (array_key_exists('id', $list) && array_key_exists('list_name', $list)) {
							$lists[$list['id']] = $list['list_name'];
						}
					}
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to Campayn Site URL!', 'ulp').'</div>';
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
	function connect($_api_key, $_url, $_data = array(), $_method = '') {
		$headers = array(
			'Authorization: TRUEREST apikey='.$_api_key,
			'Content-Type: application/json'
		);
		try {
			$curl = curl_init($_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
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
$ulp_campayn = new ulp_campayn_class();
?>