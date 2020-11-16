<?php
/* Your Mailing List Provider integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_ymlp_class {
	var $default_popup_options = array(
		'ymlp_enable' => 'off',
		'ymlp_key' => '',
		'ymlp_username' => '',
		'ymlp_listid' => '',
		'ymlp_nameid' => ''
	);
	function __construct() {
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
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('Your Mailing List Provider Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable YMLP', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_ymlp_enable" name="ulp_ymlp_enable" '.($popup_options['ymlp_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Your Mailing List Provider', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Your Mailing List Provider.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_ymlp_key" name="ulp_ymlp_key" value="'.esc_html($popup_options['ymlp_key']).'" class="widefat" onchange="ulp_ymlp_lists(); ulp_ymlp_fields();">
							<br /><em>'.__('Enter your Your Mailing List Provider API Key. You can find it <a href="http://www.ymlp.com/app/api.php" target="_blank">here</a>. Do not forget enable API access on the same page.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Username', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_ymlp_username" name="ulp_ymlp_username" value="'.esc_html($popup_options['ymlp_username']).'" class="widefat" onchange="ulp_ymlp_lists(); ulp_ymlp_fields();">
							<br /><em>'.__('Enter your Your Mailing List Provider Username. You can find it in Advanced User Settings.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Group ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_ymlp_listid" name="ulp_ymlp_listid" value="'.esc_html($popup_options['ymlp_listid']).'" class="ic_input_number">
							<br /><em>'.__('Enter comma-separated list of Group IDs. You can get Group IDs from', 'ulp').' <a href="'.admin_url('admin.php').'?action=ulp-ymlp-lists&key='.base64_encode($popup_options['ymlp_key']).'&username='.base64_encode($popup_options['ymlp_username']).'" target="_blank" id="ulp_ymlp_lists" title="'.__('Available Groups', 'ulp').'">'.__('this table', 'ulp').'</a>.</em>
							<script>
								function ulp_ymlp_lists() {
									jQuery("#ulp_ymlp_lists").attr("href", "'.admin_url('admin.php').'?action=ulp-ymlp-lists&key="+ulp_encode64(jQuery("#ulp_ymlp_key").val())+"&username="+ulp_encode64(jQuery("#ulp_ymlp_username").val()));
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('"Name" field ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_ymlp_nameid" name="ulp_ymlp_nameid" value="'.esc_html($popup_options['ymlp_nameid']).'" class="ic_input_number">
							<br /><em>'.__('Enter your "Name" field ID (or leave it blank). You can get Name field ID from', 'ulp').' <a href="'.admin_url('admin.php').'?action=ulp-ymlp-fields&key='.base64_encode($popup_options['ymlp_key']).'&username='.base64_encode($popup_options['ymlp_username']).'" target="_blank" id="ulp_ymlp_fields" title="'.__('Available Fields', 'ulp').'">'.__('this table', 'ulp').'</a>.</em>
							<script>
								function ulp_ymlp_fields() {
									jQuery("#ulp_ymlp_fields").attr("href", "'.admin_url('admin.php').'?action=ulp-ymlp-fields&key="+ulp_encode64(jQuery("#ulp_ymlp_key").val())+"&username="+ulp_encode64(jQuery("#ulp_ymlp_username").val()));
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
		if (isset($ulp->postdata["ulp_ymlp_enable"])) $popup_options['ymlp_enable'] = "on";
		else $popup_options['ymlp_enable'] = "off";
		if ($popup_options['ymlp_enable'] == 'on') {
			if (empty($popup_options['ymlp_username'])) $errors[] = __('Invalid Your Mailing List Provider Username', 'ulp');
			if (empty($popup_options['ymlp_key'])) $errors[] = __('Invalid Your Mailing List Provider API Key', 'ulp');
			if (empty($popup_options['ymlp_listid'])) $errors[] = __('Invalid Your Mailing List Provider list ID', 'ulp');
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
		if (isset($ulp->postdata["ulp_ymlp_enable"])) $popup_options['ymlp_enable'] = "on";
		else $popup_options['ymlp_enable'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function admin_request_handler() {
		global $wpdb;
		if (!empty($_GET['action'])) {
			switch($_GET['action']) {
				case 'ulp-ymlp-lists':
					if (isset($_GET["key"]) && isset($_GET["username"])) {
						$key = base64_decode($_GET["key"]);
						$username = base64_decode($_GET["username"]);
						$lists = array();
						try {
							$data = array(
								'Key' => $key,
								'Username' => $username,
								'Output' => 'JSON'
							);
							$request = http_build_query($data);

							$curl = curl_init('https://www.ymlp.com/api/Groups.GetList');
							curl_setopt($curl, CURLOPT_POST, 1);
							curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
							curl_setopt($curl, CURLOPT_TIMEOUT, 10);
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
							curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
							curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
							curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
							curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
												
							$response = curl_exec($curl);
							curl_close($curl);

							$result = json_decode($response, true);
							if($result) {
								foreach ($result as $list) {
									if (is_array($list)) {
										if (array_key_exists('ID', $list) && array_key_exists('GroupName', $list)) {
											$lists[$list['ID']] = $list['GroupName'];
										}
									}
								}
							}
						} catch (Exception $e) {
						}
						if (!empty($lists)) {
							echo '
<html>
<head>
	<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
	<title>'.__('Your Mailing List Provider Groups', 'ulp').'</title>
</head>
<body>
	<table style="width: 100%;">
		<tr>
			<td style="width: 170px; font-weight: bold;">'.__('Group ID', 'ulp').'</td>
			<td style="font-weight: bold;">'.__('Group Name', 'ulp').'</td>
		</tr>';
							foreach ($lists as $key => $value) {
								echo '
		<tr>
			<td>'.esc_html($key).'</td>
			<td>'.esc_html(esc_html($value)).'</td>
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
				case 'ulp-ymlp-fields':
					if (isset($_GET["key"]) && isset($_GET["username"])) {
						$key = base64_decode($_GET["key"]);
						$username = base64_decode($_GET["username"]);
						$fields = array();
						try {
							$data = array(
								'Key' => $key,
								'Username' => $username,
								'Output' => 'JSON'
							);
							$request = http_build_query($data);

							$curl = curl_init('https://www.ymlp.com/api/Fields.GetList');
							curl_setopt($curl, CURLOPT_POST, 1);
							curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
							curl_setopt($curl, CURLOPT_TIMEOUT, 10);
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
							curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
							curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
							curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
							curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
												
							$response = curl_exec($curl);
							curl_close($curl);

							$result = json_decode($response, true);
							if($result) {
								foreach ($result as $field) {
									if (is_array($field)) {
										if (array_key_exists('ID', $field) && array_key_exists('FieldName', $field)) {
											$fields[$field['ID']] = $field['FieldName'];
										}
									}
								}
							}
						} catch (Exception $e) {
						}
						if (!empty($fields)) {
							echo '
<html>
<head>
	<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
	<title>'.__('Your Mailing List Provider Fields', 'ulp').'</title>
</head>
<body>
	<table style="width: 100%;">
		<tr>
			<td style="width: 170px; font-weight: bold;">'.__('Field ID', 'ulp').'</td>
			<td style="font-weight: bold;">'.__('Field Name', 'ulp').'</td>
		</tr>';
							foreach ($fields as $key => $value) {
								echo '
		<tr>
			<td>'.esc_html($key).'</td>
			<td>'.esc_html(esc_html($value)).'</td>
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
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['ymlp_enable'] == 'on') {
			try {
				$groups = str_replace(';', ',', preg_replace('/[^a-zA-Z0-9,;]/', '', $popup_options['ymlp_listid']));
				$data = array(
					'Key' => $popup_options['ymlp_key'],
					'Username' => $popup_options['ymlp_username'],
					'Output' => 'JSON',
					'Email' => $_subscriber['{subscription-email}'],
					'GroupID' => $groups,
					'OverruleUnsubscribedBounced' => '1'
				);
				if (!empty($popup_options['ymlp_nameid'])) {
					$data['Field'.$popup_options['ymlp_nameid']] = $_subscriber['{subscription-name}'];
				}
				$request = http_build_query($data);
				
				$curl = curl_init('https://www.ymlp.com/api/Contacts.Add');
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
											
				$response = curl_exec($curl);
				curl_close($curl);
			} catch (Exception $e) {
			}
		}
	}
}
$ulp_ymlp = new ulp_ymlp_class();
?>