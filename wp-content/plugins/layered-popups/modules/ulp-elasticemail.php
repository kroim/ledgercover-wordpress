<?php
/* Elastic Email integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_elasticemail_class {
	var $default_popup_options = array(
		'elasticemail_enable' => 'off',
		'elasticemail_api_key' => '',
		'elasticemail_list_id' => '',
		'elasticemail_fields' => ''
	);
	var $fields = array(
		'email' => '{subscription-email}',
		'firstname' => '{subscription-name}',
		'lastname' => '',
		'birthdate' => '',
		'city' => '',
		'country' => '',
		'gender' => '',
		'organizationname' => '',
		'phone' => '{subscription-phone}',
		'title' => '',
		'state' => '',
		'postalcode' => ''
	);
	var $field_labels = array(
		'email' => array('title' => 'E-mail', 'description' => 'E-mail address of contact/recipient.'),
		'firstname' => array('title' => 'First name', 'description' => 'First name of the contact.'),
		'lastname' => array('title' => 'Last name', 'description' => 'Last name of the contact.'),
		'birthdate' => array('title' => 'Date of birth', 'description' => 'Date of birth of the contact.'),
		'city' => array('title' => 'City', 'description' => 'City of the contact.'),
		'country' => array('title' => 'Country', 'description' => 'Country of the contact.'),
		'gender' => array('title' => 'Gender', 'description' => 'Gender of the contact.'),
		'organizationname' => array('title' => 'Organization', 'description' => 'Organization name the contact works for.'),
		'phone' => array('title' => 'Phone #', 'description' => 'Phone number of the contact.'),
		'title' => array('title' => 'Title', 'description' => 'Title of the contact (Mr., Mrs, Miss, etc.).'),
		'state' => array('title' => 'State', 'description' => 'State or province of the contact.'),
		'postalcode' => array('title' => 'Postal code', 'description' => 'ZIP or postal code of the contact.')
	);
	function __construct() {
		$this->default_popup_options['elasticemail_fields'] = serialize($this->fields);
		if (is_admin()) {
			add_action('admin_init', array(&$this, 'admin_request_handler'));
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
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
				<h3>'.__('Elastic Email Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Elastic Email', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_elasticemail_enable" name="ulp_elasticemail_enable" '.($popup_options['elasticemail_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Elastic Email', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Elastic Email.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_elasticemail_api_key" name="ulp_elasticemail_api_key" value="'.esc_html($popup_options['elasticemail_api_key']).'" class="widefat" onchange="ulp_elasticemail_handler();">
							<br /><em>'.__('Enter your Elastic Email API Key. You can get your API Key <a href="https://elasticemail.com/account#/settings" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List name', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_elasticemail_list_id" name="ulp_elasticemail_list_id" value="'.esc_html($popup_options['elasticemail_list_id']).'" class="widefat">
							<br /><em>'.__('Enter your List Name. You can get List Name from', 'ulp').' <a href="'.admin_url('admin.php').'?action=ulp-elasticemail-lists&key='.base64_encode($popup_options['elasticemail_api_key']).'" target="_blank" id="ulp_elasticemail_lists" title="'.__('Available Lists', 'ulp').'">'.__('this table', 'ulp').'</a>.</em>
							<script>
								function ulp_elasticemail_handler() {
									jQuery("#ulp_elasticemail_lists").attr("href", "'.admin_url('admin.php').'?action=ulp-elasticemail-lists&key="+ulp_encode64(jQuery("#ulp_elasticemail_api_key").val()));
								}
							</script>
						</td>
					</tr>';
		if ($ulp->ext_options['enable_customfields'] == 'on') {
			$fields = unserialize($popup_options['elasticemail_fields']);
			if (is_array($fields)) $fields = array_merge($this->fields, $fields);
			else $fields = $this->fields;
			echo '
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-elasticemail-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Elastic Email fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
			foreach ($this->fields as $key => $value) {
				echo '
									<tr>
										<td style="width: 100px;"><strong>'.esc_html($this->field_labels[$key]['title']).':</strong></td>
										<td>
											<input type="text" id="ulp_elasticemail_field_'.esc_html($key).'" name="ulp_elasticemail_field_'.esc_html($key).'" value="'.esc_html($fields[$key]).'" class="widefat"'.($key == 'email' ? ' readonly="readonly"' : '').' />
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
		if (isset($ulp->postdata["ulp_elasticemail_enable"])) $popup_options['elasticemail_enable'] = "on";
		else $popup_options['elasticemail_enable'] = "off";
		if ($popup_options['elasticemail_enable'] == 'on') {
			if (empty($popup_options['elasticemail_api_key'])) $errors[] = __('Invalid Elastic Email API key', 'ulp');
			if (empty($popup_options['elasticemail_list_id'])) $errors[] = __('Invalid Elastic Email list ID', 'ulp');
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
		if (isset($ulp->postdata["ulp_elasticemail_enable"])) $popup_options['elasticemail_enable'] = "on";
		else $popup_options['elasticemail_enable'] = "off";
		if ($ulp->ext_options['enable_customfields'] == 'on') {
			$fields = array();
			foreach($this->fields as $key => $value) {
				if (isset($ulp->postdata['ulp_elasticemail_field_'.$key])) {
					$fields[$key] = stripslashes(trim($ulp->postdata['ulp_elasticemail_field_'.$key]));
				}
			}
			$popup_options['elasticemail_fields'] = serialize($fields);
		}
		return array_merge($_popup_options, $popup_options);
	}
	function admin_request_handler() {
		global $wpdb;
		if (!empty($_GET['action'])) {
			switch($_GET['action']) {
				case 'ulp-elasticemail-lists':
					if (isset($_GET["key"])) {
						$key = base64_decode($_GET["key"]);
						$curl = curl_init('http://api.elasticemail.com/lists/get?'.http_build_query(array('api_key' => $key)));
						curl_setopt($curl, CURLOPT_POST, 0);
						curl_setopt($curl, CURLOPT_TIMEOUT, 10);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
						curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
						curl_setopt($curl, CURLOPT_HEADER, 0);
																		
						$response = curl_exec($curl);
															
						if (curl_error($curl)) return array();
						$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
						if ($httpCode != '200') return array();
						curl_close($curl);
						$p = xml_parser_create();
						xml_parse_into_struct($p, $response, $values, $index);
						xml_parser_free($p);
						$lists = array();
						if (isset($index['LIST'])) {
							foreach ($index['LIST'] as $idx) {
								$lists[] = $values[$idx]['attributes']['NAME'];
							}
						}
						if (!empty($lists)) {
							echo '
<html>
<head>
	<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
	<title>'.__('Elastic Email Lists', 'ulp').'</title>
</head>
<body>
	<table style="width: 100%;">';
							foreach ($lists as $list) {
								echo '
		<tr>
			<td>'.esc_html($list).'</td>
		</tr>';
							}
							echo '
	</table>						
</body>
</html>';
						} else echo '<div style="text-align: center; margin: 20px 0px;">'.__('No data found!', 'ulp').'</div>';
					} else echo '<div style="text-align: center; margin: 20px 0px;">'.__('No data found!', 'ulp').'</div>';
					die();
					break;
				default:
					break;
			}
		}
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['elasticemail_enable'] == 'on') {
			if ($ulp->ext_options['enable_customfields'] == 'on') {
				$fields = unserialize($_popup_options['elasticemail_fields']);
				if (is_array($fields)) $fields = array_merge($this->fields, $fields);
				else $fields = $this->fields;
				$data = array(
					'email' => $_subscriber['{subscription-email}'],
					'api_key' => $popup_options['elasticemail_api_key'],
					'listname' => $popup_options['elasticemail_list_id']
				);
				foreach ($fields as $key => $value) {
					if (!empty($value) && $key != 'email') {
						$data[$key] = strtr($value, $_subscriber);
					}
				}
			} else {
				$data = array(
					'email' => $_subscriber['{subscription-email}'],
					'firstname' => $_subscriber['{subscription-name}'],
					'api_key' => $popup_options['elasticemail_api_key'],
					'listname' => $popup_options['elasticemail_list_id']
				);
				if (!empty($_subscriber['{subscription-phone}'])) $data['phone'] = $_subscriber['{subscription-phone}'];
			}

			$curl = curl_init('http://api.elasticemail.com/lists/create-contact?'.http_build_query($data));
			curl_setopt($curl, CURLOPT_POST, 0);
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
			curl_setopt($curl, CURLOPT_HEADER, 0);
																		
			$response = curl_exec($curl);
			curl_close($curl);
		}
	}
}
$ulp_elasticemail = new ulp_elasticemail_class();
?>