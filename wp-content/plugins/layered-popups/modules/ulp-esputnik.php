<?php
/* eSputnik integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_esputnik_class {
	var $default_popup_options = array(
		"esputnik_enable" => "off",
		"esputnik_login" => "",
		"esputnik_password" => "",
		"esputnik_addressbook" => "",
		"esputnik_addressbook_id" => "",
		"esputnik_static_fields" => array(
			"firstName" => "{subscription-name}",
			"lastName" => "",
			"address_region" => "",
			"address_town" => "",
			"address_address" => "",
			"address_postcode" => ""
		),
		"esputnik_fields" => array(),
		"esputnik_groups" => array()
	);
	var $field_labels = array(
		'firstName' => array('title' => 'First name', 'description' => 'First name of the contact.'),
		'lastName' => array('title' => 'Last name', 'description' => 'Last name of the contact.'),
		'address_town' => array('title' => 'Town', 'description' => 'Town of the contact.'),
		'address_region' => array('title' => 'Region', 'description' => 'Region of the contact.'),
		'address_address' => array('title' => 'Address', 'description' => 'Address of the contact.'),
		'address_postcode' => array('title' => 'Postal code', 'description' => 'ZIP or postal code of the contact.')
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-esputnik-addressbooks', array(&$this, "show_addressbooks"));
			add_action('wp_ajax_ulp-esputnik-groups', array(&$this, "show_groups"));
			add_action('wp_ajax_ulp-esputnik-fields', array(&$this, "show_fields"));
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
				<h3>'.__('eSputnik Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable eSputnik', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_esputnik_enable" name="ulp_esputnik_enable" '.($popup_options['esputnik_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to eSputnik', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to eSputnik.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Login', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_esputnik_login" name="ulp_esputnik_login" value="'.esc_html($popup_options['esputnik_login']).'" class="widefat">
							<br /><em>'.__('Enter your eSputnik login. This is an email address that you use to enter eSputnik account.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Password', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_esputnik_password" name="ulp_esputnik_password" value="'.esc_html($popup_options['esputnik_password']).'" class="widefat">
							<br /><em>'.__('Enter your eSputnik password. This is a password that you use to enter eSputnik account.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Address Book', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-esputnik-list" name="ulp_esputnik_addressbook" value="'.esc_html($popup_options['esputnik_addressbook']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_esputnik_addressbooks_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-esputnik-list-id" name="ulp_esputnik_addressbook_id" value="'.esc_html($popup_options['esputnik_addressbook_id']).'" />
							<div id="ulp-esputnik-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your Address Book ID.', 'ulp').'</em>
							<script>
								function ulp_esputnik_addressbooks_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-esputnik-addressbooks", "ulp_login": jQuery("#ulp_esputnik_login").val(), "ulp_password": jQuery("#ulp_esputnik_password").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-esputnik-fields-html">';
		if (!empty($popup_options['esputnik_login']) && !empty($popup_options['esputnik_password'])) {
			$fields = $this->get_fields_html($popup_options['esputnik_login'], $popup_options['esputnik_password'], $popup_options['esputnik_static_fields'], $popup_options['esputnik_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_esputnik_fields_button" class="ulp_button button-secondary" onclick="return ulp_esputnik_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-esputnik-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_esputnik_loadfields() {
									jQuery("#ulp-esputnik-fields-loading").fadeIn(350);
									jQuery(".ulp-esputnik-fields-html").slideUp(350);
									var data = {action: "ulp-esputnik-fields", ulp_login: jQuery("#ulp_esputnik_login").val(), ulp_password: jQuery("#ulp_esputnik_password").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-esputnik-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-esputnik-fields-html").html(data.html);
												jQuery(".ulp-esputnik-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-esputnik-fields-html").html("<div class=\'ulp-esputnik-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to eSputnik server.', 'ulp').'</strong></div>");
												jQuery(".ulp-esputnik-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-esputnik-fields-html").html("<div class=\'ulp-esputnik-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to eSputnik server.', 'ulp').'</strong></div>");
											jQuery(".ulp-esputnik-fields-html").slideDown(350);
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Groups', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-esputnik-groups-html">';
		if (!empty($popup_options['esputnik_login']) && !empty($popup_options['esputnik_password'])) {
			$groups = $this->get_groups_html($popup_options['esputnik_login'], $popup_options['esputnik_password'], $popup_options['esputnik_groups']);
			echo $groups;
		}
		echo '
							</div>
							<a id="ulp_esputnik_groups_button" class="ulp_button button-secondary" onclick="return ulp_esputnik_loadgroups();">'.__('Load Groups', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-esputnik-groups-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load groups. Ignore if you do not use groups.', 'ulp').'</em>
							<script>
								function ulp_esputnik_loadgroups() {
									jQuery("#ulp-esputnik-groups-loading").fadeIn(350);
									jQuery(".ulp-esputnik-groups-html").slideUp(350);
									var data = {action: "ulp-esputnik-groups", ulp_login: jQuery("#ulp_esputnik_login").val(), ulp_password: jQuery("#ulp_esputnik_password").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-esputnik-groups-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-esputnik-groups-html").html(data.html);
												jQuery(".ulp-esputnik-groups-html").slideDown(350);
											} else {
												jQuery(".ulp-esputnik-groups-html").html("<div class=\'ulp-esputnik-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to eSputnik server.', 'ulp').'</strong></div>");
												jQuery(".ulp-esputnik-groups-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-esputnik-groups-html").html("<div class=\'ulp-esputnik-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to eSputnik server.', 'ulp').'</strong></div>");
											jQuery(".ulp-esputnik-groups-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_esputnik_enable"])) $popup_options['esputnik_enable'] = "on";
		else $popup_options['esputnik_enable'] = "off";
		if ($popup_options['esputnik_enable'] == 'on') {
			if (empty($popup_options['esputnik_login'])) $errors[] = __('Invalid eSputnik login.', 'ulp');
			if (empty($popup_options['esputnik_password'])) $errors[] = __('Invalid eSputnik password.', 'ulp');
			if (empty($popup_options['esputnik_addressbook_id'])) $errors[] = __('Invalid eSputnik Address Book ID.', 'ulp');
		}
		return array_merge($_errors, $errors);
	}
	function popup_options_populate($_popup_options) {
		global $ulp;
		$popup_options = $this->default_popup_options;
		foreach ($this->default_popup_options as $key => $value) {
			if (isset($ulp->postdata['ulp_'.$key])) {
				$popup_options[$key] = stripslashes(trim($ulp->postdata['ulp_'.$key]));
			}
		}
		if (isset($ulp->postdata["ulp_esputnik_enable"])) $popup_options['esputnik_enable'] = "on";
		else $popup_options['esputnik_enable'] = "off";
		
		$groups = array();
		$fields = array();
		$static_fields = $this->default_popup_options['esputnik_static_fields'];
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_esputnik_group_')) == 'ulp_esputnik_group_') {
				$groups[] = substr($key, strlen('ulp_esputnik_group_'));
			}
			if (substr($key, 0, strlen('ulp_esputnik_field_')) == 'ulp_esputnik_field_') {
				$field = substr($key, strlen('ulp_esputnik_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
			if (substr($key, 0, strlen('ulp_esputnik_static_field_')) == 'ulp_esputnik_static_field_') {
				$field = substr($key, strlen('ulp_esputnik_static_field_'));
				$static_fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['esputnik_groups'] = $groups;
		$popup_options['esputnik_fields'] = $fields;
		$popup_options['esputnik_static_fields'] = $static_fields;
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}']) && empty($_subscriber['{subscription-phone}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['esputnik_enable'] == 'on') {
			$data = array();
			$data['contact'] = array(
				'channels' => array(),
				'addressBookId' => $popup_options['esputnik_addressbook_id']
			);
			if (!empty($_subscriber['{subscription-phone}'])) {
				$data['contact']['channels'][] = array('type' => 'sms', 'value' => $_subscriber['{subscription-phone}']);
				$dedupeon = 'sms';
			}
			if (!empty($_subscriber['{subscription-email}'])) {
				$data['contact']['channels'][] = array('type' => 'email', 'value' => $_subscriber['{subscription-email}']);
				$dedupeon = 'email';
			}
			$data['dedupeOn'] = $dedupeon;
			$data['groups'] = $popup_options['esputnik_groups'];
			foreach ($popup_options['esputnik_static_fields'] as $key => $value) {
				if (!empty($value)) {
					if (substr($key, 0, strlen('address_')) == 'address_') {
						$key = substr($key, strlen('address_'));
						$data['contact']['address'][$key] = strtr($value, $_subscriber);
					} else {
						$data['contact'][$key] = strtr($value, $_subscriber);
					}
				}
			}
			foreach ($popup_options['esputnik_fields'] as $key => $value) {
				if (!empty($value)) {
					$data['contact']['fields'][] = array('id' => $key, 'value' => strtr($value, $_subscriber));
				}
			}
			$result = $this->connect($popup_options['esputnik_login'], $popup_options['esputnik_password'], '/v1/contact/subscribe', $data);
		}
	}
	function show_addressbooks() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_login']) || !isset($_POST['ulp_password']) || empty($_POST['ulp_login']) || empty($_POST['ulp_password'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Login or Password!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$login = trim(stripslashes($_POST['ulp_login']));
			$password = trim(stripslashes($_POST['ulp_password']));
			
			$result = $this->connect($login, $password, '/v1/addressbooks');
			if ($result['http_code'] >= 400) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to your eSputnik account!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			if (!array_key_exists('addressBook', $result['result']) || empty($result['result']['addressBook'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No Address Books found!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$addressbooks = array();
			foreach ($result['result'] as $addressbook) {
				if (is_array($addressbook)) {
					if (array_key_exists('addressBookId', $addressbook) && array_key_exists('name', $addressbook)) {
						$addressbooks[$addressbook['addressBookId']] = $addressbook['name'];
					}
				}
			}
			$addressbook_html = '';
			if (!empty($addressbooks)) {
				foreach ($addressbooks as $id => $name) {
					$addressbook_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
				}
			} else $addressbook_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No data found!', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $addressbook_html;
			$return_object['items'] = sizeof($addressbooks);
			echo json_encode($return_object);
		}
		exit;
	}
	function show_groups() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$return_object = array();
			if (!isset($_POST['ulp_login']) || !isset($_POST['ulp_password']) || empty($_POST['ulp_login']) || empty($_POST['ulp_password'])) {
				$return_object['html'] = '<div class="ulp-esputnik-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid Login or Password!', 'ulp').'</strong></div>';
			} else {
				$login = trim(stripslashes($_POST['ulp_login']));
				$password = trim(stripslashes($_POST['ulp_password']));
				$return_object['html'] = $this->get_groups_html($login, $password, array());
			}
			$return_object['status'] = 'OK';
			echo json_encode($return_object);
		}
		exit;
	}
	function get_groups_html($_login, $_password, $_groups) {
		$result = $this->connect($_login, $_password, '/v1/groups');
		if ($result['http_code'] >= 400) {
			return '<div class="ulp-esputnik-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not connect to your eSputnik account!', 'ulp').'</strong></div>';
		}
		if (!is_array($result['result'])) {
			return '<div class="ulp-esputnik-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not find any groups!', 'ulp').'</strong></div>';
		}
		$groups = '';
		foreach ($result['result'] as $group) {
			if ($group['type'] == 'Static') {
				$groups .= '<div class="ulp-esputnik-group" style="margin: 1px 0 1px 10px;"><input type="checkbox" name="ulp_esputnik_group_'.$group['id'].'"'.(in_array($group['id'], $_groups) ? ' checked="checked"' : '').' /> '.$group['name'].'</div>';
			}
		}
		if (empty($groups)) {
			$groups = '<div class="ulp-esputnik-grouping" style="margin-bottom: 10px;"><strong>'.__('No groups found.', 'ulp').'</strong></div>';
		} else {
			$groups = '<div class="ulp-mailchimp-grouping" style="margin-bottom: 10px;">'.$groups.'</div>';
		}
		return $groups;
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$return_object = array();
			if (!isset($_POST['ulp_login']) || !isset($_POST['ulp_password']) || empty($_POST['ulp_login']) || empty($_POST['ulp_password'])) {
				$return_object['html'] = '<div class="ulp-esputnik-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid Login or Password!', 'ulp').'</strong></div>';
			} else {
				$login = trim(stripslashes($_POST['ulp_login']));
				$password = trim(stripslashes($_POST['ulp_password']));
				$return_object['html'] = $this->get_fields_html($login, $password, $this->default_popup_options['esputnik_static_fields'], $this->default_popup_options['esputnik_fields']);
			}
			$return_object['status'] = 'OK';
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_login, $_password, $_static_fields, $_fields) {
		$result = $this->connect($_login, $_password, '/v1/addressbooks');
		if ($result['http_code'] >= 400) {
			return '<div class="ulp-esputnik-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not connect to your eSputnik account!', 'ulp').'</strong></div>';
		}
		if (!array_key_exists('addressBook', $result['result']) || empty($result['result']['addressBook'])) {
			return '<div class="ulp-esputnik-grouping" style="margin-bottom: 10px;"><strong>'.__('No Address Books found!', 'ulp').'</strong></div>';
		}
		$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate eSputnik fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
		foreach ($_static_fields as $key => $value) {
			$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($this->field_labels[$key]['title']).':</strong></td>
					<td>
						<input type="text" id="ulp_esputnik_static_field_'.esc_html($key).'" name="ulp_esputnik_static_field_'.esc_html($key).'" value="'.esc_html($value).'" class="widefat" />
						<br /><em>'.esc_html($this->field_labels[$key]['description']).'</em>
					</td>
				</tr>';
		
		}
		if (array_key_exists('name', $result['result']['addressBook']['fieldGroups'])) {
			foreach ($result['result']['addressBook']['fieldGroups']['fields'] as $field) {
				if (is_array($field)) {
					if (array_key_exists('id', $field) && array_key_exists('name', $field)) {
						$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['name']).':</strong></td>
					<td>
						<input type="text" id="ulp_esputnik_field_'.esc_html($field['id']).'" name="ulp_esputnik_field_'.esc_html($field['id']).'" value="'.esc_html(array_key_exists($field['id'], $_fields) ? $_fields[$field['id']] : '').'" class="widefat" />
						<br /><em>'.esc_html($field['name']).'</em>
					</td>
				</tr>';
					}
				}
			}
		} else if (array_key_exists(0, $result['result']['addressBook']['fieldGroups'])) {
			foreach ($result['result']['addressBook']['fieldGroups'] as $fieldgroup) {
				foreach ($fieldgroup['fields'] as $field) {
					if (is_array($field)) {
						if (array_key_exists('id', $field) && array_key_exists('name', $field)) {
							$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['name']).':</strong></td>
					<td>
						<input type="text" id="ulp_esputnik_field_'.esc_html($field['id']).'" name="ulp_esputnik_field_'.esc_html($field['id']).'" value="'.esc_html(array_key_exists($field['id'], $_fields) ? $_fields[$field['id']] : '').'" class="widefat" />
						<br /><em>'.esc_html($field['name']).'</em>
					</td>
				</tr>';
						}
					}
				}
			}
		}
		$fields .= '
			</table>';
		return $fields;
	}
	function connect($_login, $_password, $_path, $_data = array(), $_method = '') {
		try {
			$url = 'https://esputnik.com.ua/api/'.ltrim($_path, '/');
			$curl = curl_init($url);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_USERPWD, $_login.':'.$_password);
			//curl_setopt($curl, CURLOPT_HEADER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
			//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($curl);
			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			$result = json_decode($response, true);
		} catch (Exception $e) {
			$result = false;
		}
		return array('http_code' => $httpCode, 'result' => $result);
	}
}
$ulp_esputnik = new ulp_esputnik_class();
?>