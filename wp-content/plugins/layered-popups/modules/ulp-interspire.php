<?php
/* Interspire integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_interspire_class {
	var $default_popup_options = array(
		'interspire_enable' => 'off',
		'interspire_url' => '',
		'interspire_username' => '',
		'interspire_token' => '',
		'interspire_listid' => '',
		'interspire_nameid' => ''
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
				<h3>'.__('Interspire Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Interspire', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_interspire_enable" name="ulp_interspire_enable" '.($popup_options['interspire_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Interspire', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Interspire.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('XML Path', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_interspire_url" name="ulp_interspire_url" value="'.esc_html($popup_options['interspire_url']).'" class="widefat" onchange="ulp_interspire_lists(); ulp_interspire_fields();">
							<br /><em>'.__('Enter your Interspire XML Path. You can find it in Advanced User Settings.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('XML Username', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_interspire_username" name="ulp_interspire_username" value="'.esc_html($popup_options['interspire_username']).'" class="widefat" onchange="ulp_interspire_lists(); ulp_interspire_fields();">
							<br /><em>'.__('Enter your Interspire XML Username. You can find it in Advanced User Settings.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('XML Token', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_interspire_token" name="ulp_interspire_token" value="'.esc_html($popup_options['interspire_token']).'" class="widefat" onchange="ulp_interspire_lists(); ulp_interspire_fields();">
							<br /><em>'.__('Enter your Interspire XML Token. You can find it in Advanced User Settings.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_interspire_listid" name="ulp_interspire_listid" value="'.esc_html($popup_options['interspire_listid']).'" class="ic_input_number" onchange="ulp_interspire_fields();">
							<br /><em>'.__('Enter your List ID. You can get List ID from', 'ulp').' <a href="'.admin_url('admin.php').'?action=ulp-interspire-lists&url='.base64_encode($popup_options['interspire_url']).'&username='.base64_encode($popup_options['interspire_username']).'&token='.base64_encode($popup_options['interspire_token']).'" target="_blank" id="ulp_interspire_lists" title="'.__('Available Lists', 'ulp').'">'.__('this table', 'ulp').'</a>.</em>
							<script>
								function ulp_interspire_lists() {
									jQuery("#ulp_interspire_lists").attr("href", "'.admin_url('admin.php').'?action=ulp-interspire-lists&url="+ulp_encode64(jQuery("#ulp_interspire_url").val())+"&username="+ulp_encode64(jQuery("#ulp_interspire_username").val())+"&token="+ulp_encode64(jQuery("#ulp_interspire_token").val()));
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('"Name" field ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_interspire_nameid" name="ulp_interspire_nameid" value="'.esc_html($popup_options['interspire_nameid']).'" class="ic_input_number">
							<br /><em>'.__('Enter your "Name" field ID. You can get Name field ID from', 'ulp').' <a href="'.admin_url('admin.php').'?action=ulp-interspire-fields&url='.base64_encode($popup_options['interspire_url']).'&username='.base64_encode($popup_options['interspire_username']).'&token='.base64_encode($popup_options['interspire_token']).'&list='.base64_encode($popup_options['interspire_listid']).'" target="_blank" id="ulp_interspire_fields" title="'.__('Available Fields', 'ulp').'">'.__('this table', 'ulp').'</a>.</em>
							<script>
								function ulp_interspire_fields() {
									jQuery("#ulp_interspire_fields").attr("href", "'.admin_url('admin.php').'?action=ulp-interspire-fields&url="+ulp_encode64(jQuery("#ulp_interspire_url").val())+"&username="+ulp_encode64(jQuery("#ulp_interspire_username").val())+"&token="+ulp_encode64(jQuery("#ulp_interspire_token").val())+"&list="+ulp_encode64(jQuery("#ulp_interspire_listid").val()));
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
		if (isset($ulp->postdata["ulp_interspire_enable"])) $popup_options['interspire_enable'] = "on";
		else $popup_options['interspire_enable'] = "off";
		if ($popup_options['interspire_enable'] == 'on') {
			if (strlen($popup_options['interspire_url']) == 0 || !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $popup_options['interspire_url'])) $errors[] = __('Interspire XMP Path must be a valid URL.', 'ulp');
			if (empty($popup_options['interspire_username'])) $errors[] = __('Invalid Interspire Username', 'ulp');
			if (empty($popup_options['interspire_token'])) $errors[] = __('Invalid Interspire Token', 'ulp');
			if (empty($popup_options['interspire_listid'])) $errors[] = __('Invalid Interspire list ID', 'ulp');
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
		if (isset($ulp->postdata["ulp_interspire_enable"])) $popup_options['interspire_enable'] = "on";
		else $popup_options['interspire_enable'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function admin_request_handler() {
		global $wpdb;
		if (!empty($_GET['action'])) {
			switch($_GET['action']) {
				case 'ulp-interspire-lists':
					if (isset($_GET["url"]) && isset($_GET["username"]) && isset($_GET["token"])) {
						$url = base64_decode($_GET["url"]);
						$username = base64_decode($_GET["username"]);
						$token = base64_decode($_GET["token"]);
						$lists = array();
						try {
							$xml = '
<xmlrequest>
	<username>'.$username.'</username>
	<usertoken>'.$token.'</usertoken>
	<requesttype>lists</requesttype>
	<requestmethod>GetLists</requestmethod>
	<details>
	</details>
</xmlrequest>';
							$curl = curl_init($url);
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
							curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
							curl_setopt($curl, CURLOPT_HEADER, 0);
							curl_setopt($curl, CURLOPT_POST, 1);
							curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
							$response = curl_exec($curl);
							curl_close($curl);
							$p = xml_parser_create();
							xml_parse_into_struct($p, $response, $values, $index);
							xml_parser_free($p);
							if (isset($index['STATUS']) && $values[$index['STATUS'][0]]['value'] == 'SUCCESS') {
								$i = 0;
								foreach ($index['LISTID'] as $idx) {
									$lists[$values[$idx]['value']] = $values[$index['NAME'][$i]]['value'];
									$i++;
								}
							}
							ksort($lists);
						} catch (Exception $e) {
						}
						if (!empty($lists)) {
							echo '
<html>
<head>
	<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
	<title>'.__('Interspire Lists', 'ulp').'</title>
</head>
<body>
	<table style="width: 100%;">
		<tr>
			<td style="width: 170px; font-weight: bold;">'.__('List ID', 'ulp').'</td>
			<td style="font-weight: bold;">'.__('List Name', 'ulp').'</td>
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
				case 'ulp-interspire-fields':
					if (isset($_GET["url"]) && isset($_GET["username"]) && isset($_GET["token"]) && isset($_GET["list"])) {
						$url = base64_decode($_GET["url"]);
						$username = base64_decode($_GET["username"]);
						$token = base64_decode($_GET["token"]);
						$list = base64_decode($_GET["list"]);
						$fields = array();
						try {
							$xml = '
<xmlrequest>
	<username>'.$username.'</username>
	<usertoken>'.$token.'</usertoken>
	<requesttype>lists</requesttype>
	<requestmethod>GetCustomFields</requestmethod>
	<details>
		<listids>'.$list.'</listids>
	</details>
</xmlrequest>';
							$curl = curl_init($url);
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
							curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
							curl_setopt($curl, CURLOPT_HEADER, 0);
							curl_setopt($curl, CURLOPT_POST, 1);
							curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
							$response = curl_exec($curl);
							curl_close($curl);
							$p = xml_parser_create();
							xml_parse_into_struct($p, $response, $values, $index);
							xml_parser_free($p);
							if (isset($index['STATUS']) && $values[$index['STATUS'][0]]['value'] == 'SUCCESS') {
								$i = 0;
								foreach ($index['FIELDID'] as $idx) {
									$fields[$values[$idx]['value']] = $values[$index['NAME'][$i]]['value'];
									$i++;
								}
							}
							ksort($fields);
						} catch (Exception $e) {
						}
						if (!empty($fields)) {
							echo '
<html>
<head>
	<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
	<title>'.__('Interspire Fields', 'ulp').'</title>
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
		if ($popup_options['interspire_enable'] == 'on') {
			try {
				$xml = '
<xmlrequest>
	<username>'.$popup_options['interspire_username'].'</username>
	<usertoken>'.$popup_options['interspire_token'].'</usertoken>
	<requesttype>subscribers</requesttype>
	<requestmethod>AddSubscriberToList</requestmethod>
	<details>
		<emailaddress>'.$_subscriber['{subscription-email}'].'</emailaddress>
		<mailinglist>'.$popup_options['interspire_listid'].'</mailinglist>
		<format>html</format>
		<confirmed>yes</confirmed>';
				if (!empty($popup_options['interspire_nameid'])) {
					$xml .= '
		<customfields>;
			<item>
				<fieldid>'.$popup_options['interspire_nameid'].'</fieldid>
				<value>'.$_subscriber['{subscription-name}'].'</value>
			</item>
		</customfields>';
				}
				$xml .= '
	</details>
</xmlrequest>';
				$curl = curl_init($popup_options['interspire_url']);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
				$response = curl_exec($curl);
				curl_close($curl);
			} catch (Exception $e) {
			}
		}
	}
}
$ulp_interspire = new ulp_interspire_class();
?>