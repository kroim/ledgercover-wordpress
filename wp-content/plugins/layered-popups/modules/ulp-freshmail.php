<?php
/* FreshMail integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_freshmail_class {
	var $default_popup_options = array(
		'freshmail_enable' => 'off',
		'freshmail_key' => '',
		'freshmail_secret' => '',
		'freshmail_listid' => '',
		'freshmail_nameid' => ''
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
				<h3>'.__('FreshMail Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable FreshMail', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_freshmail_enable" name="ulp_freshmail_enable" '.($popup_options['freshmail_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to FreshMail', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to FreshMail.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_freshmail_key" name="ulp_freshmail_key" value="'.esc_html($popup_options['freshmail_key']).'" class="widefat" onchange="ulp_freshmail_lists(); ulp_freshmail_fields();">
							<br /><em>'.__('Enter your FreshMail API Key. You can find it <a href="https://app.freshmail.com/en/settings/integration/" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Secret', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_freshmail_secret" name="ulp_freshmail_secret" value="'.esc_html($popup_options['freshmail_secret']).'" class="widefat" onchange="ulp_freshmail_lists(); ulp_freshmail_fields();">
							<br /><em>'.__('Enter your FreshMail API Secret. You can find it <a href="https://app.freshmail.com/en/settings/integration/" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_freshmail_listid" name="ulp_freshmail_listid" value="'.esc_html($popup_options['freshmail_listid']).'" class="widefat" onchange="ulp_freshmail_fields();">
							<br /><em>'.__('Enter your List ID. You can get List ID from', 'ulp').' <a href="'.admin_url('admin.php').'?action=ulp-freshmail-lists&key='.base64_encode($popup_options['freshmail_key']).'&secret='.base64_encode($popup_options['freshmail_secret']).'" target="_blank" id="ulp_freshmail_lists" title="'.__('Available Lists', 'ulp').'">'.__('this table', 'ulp').'</a>.</em>
							<script>
								function ulp_freshmail_lists() {
									jQuery("#ulp_freshmail_lists").attr("href", "'.admin_url('admin.php').'?action=ulp-freshmail-lists&key="+ulp_encode64(jQuery("#ulp_freshmail_key").val())+"&secret="+ulp_encode64(jQuery("#ulp_freshmail_secret").val()));
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('"Name" field ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_freshmail_nameid" name="ulp_freshmail_nameid" value="'.esc_html($popup_options['freshmail_nameid']).'" class="widefat">
							<br /><em>'.__('Enter your "Name" field ID (or leave it blank). You can get Name field ID from', 'ulp').' <a href="'.admin_url('admin.php').'?action=ulp-freshmail-fields&key='.base64_encode($popup_options['freshmail_key']).'&secret='.base64_encode($popup_options['freshmail_secret']).'&list='.base64_encode($popup_options['freshmail_listid']).'" target="_blank" id="ulp_freshmail_fields" title="'.__('Available Fields', 'ulp').'">'.__('this table', 'ulp').'</a>.</em>
							<script>
								function ulp_freshmail_fields() {
									jQuery("#ulp_freshmail_fields").attr("href", "'.admin_url('admin.php').'?action=ulp-freshmail-fields&key="+ulp_encode64(jQuery("#ulp_freshmail_key").val())+"&secret="+ulp_encode64(jQuery("#ulp_freshmail_secret").val())+"&list="+ulp_encode64(jQuery("#ulp_freshmail_listid").val()));
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
		if (isset($ulp->postdata["ulp_freshmail_enable"])) $popup_options['freshmail_enable'] = "on";
		else $popup_options['freshmail_enable'] = "off";
		if ($popup_options['freshmail_enable'] == 'on') {
			if (empty($popup_options['freshmail_secret'])) $errors[] = __('Invalid FreshMail API Secret', 'ulp');
			if (empty($popup_options['freshmail_key'])) $errors[] = __('Invalid FreshMail API Key', 'ulp');
			if (empty($popup_options['freshmail_listid'])) $errors[] = __('Invalid FreshMail list ID', 'ulp');
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
		if (isset($ulp->postdata["ulp_freshmail_enable"])) $popup_options['freshmail_enable'] = "on";
		else $popup_options['freshmail_enable'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function admin_request_handler() {
		global $wpdb;
		if (!empty($_GET['action'])) {
			switch($_GET['action']) {
				case 'ulp-freshmail-lists':
					if (isset($_GET["key"]) && isset($_GET["secret"])) {
						$key = base64_decode($_GET["key"]);
						$secret = base64_decode($_GET["secret"]);
						$lists = array();
						try {
							$sign = sha1($key.'/rest/subscribers_list/lists'.$secret);

							$curl = curl_init('https://app.freshmail.pl/rest/subscribers_list/lists');
							curl_setopt($curl, CURLOPT_POST, 0);
							curl_setopt($curl, CURLOPT_TIMEOUT, 10);
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
							curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
							curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
							curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
							curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
							curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'X-Rest-ApiKey: '.$key, 'X-Rest-ApiSign: '.$sign));
												
							$response = curl_exec($curl);
							curl_close($curl);
							
							$result = json_decode($response, true);
							if($result && $result['status'] == 'OK') {
								foreach ($result['lists'] as $list) {
									if (is_array($list)) {
										if (array_key_exists('subscriberListHash', $list) && array_key_exists('name', $list)) {
											$lists[$list['subscriberListHash']] = $list['name'];
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
	<title>'.__('FreshMail Lists', 'ulp').'</title>
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
				case 'ulp-freshmail-fields':
					if (isset($_GET["key"]) && isset($_GET["secret"])) {
						$key = base64_decode($_GET["key"]);
						$secret = base64_decode($_GET["secret"]);
						$list = base64_decode($_GET["list"]);
						$fields = array();
						try {
							$data = array(
								'hash' => $list
							);
							$request = http_build_query($data);
							$sign = sha1($key.'/rest/subscribers_list/getFields'.$request.$secret);

							$curl = curl_init('https://app.freshmail.pl/rest/subscribers_list/getFields');
							curl_setopt($curl, CURLOPT_POST, 1);
							curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
							curl_setopt($curl, CURLOPT_TIMEOUT, 10);
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
							curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
							curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
							curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
							curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
							curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Rest-ApiKey: '.$key, 'X-Rest-ApiSign: '.$sign));
												
							$response = curl_exec($curl);
							curl_close($curl);
							$result = json_decode($response, true);
							if($result && $result['status'] == 'OK') {
								foreach ($result['fields'] as $field) {
									if (is_array($field)) {
										if (array_key_exists('tag', $field) && array_key_exists('name', $field)) {
											$fields[$field['tag']] = $field['name'];
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
	<title>'.__('FreshMail Fields', 'ulp').'</title>
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
		if ($popup_options['freshmail_enable'] == 'on') {
			try {
				$data = array(
					'email' => $_subscriber['{subscription-email}'],
					'list' => $popup_options['freshmail_listid'],
				);
				if (!empty($popup_options['freshmail_nameid'])) {
					$data['custom_fields'] = array($popup_options['freshmail_nameid'] => $_subscriber['{subscription-name}']);
				}
				$request = http_build_query($data);
				
				$sign = sha1($popup_options['freshmail_key'].'/rest/subscriber/add'.$request.$popup_options['freshmail_secret']);
				
				$curl = curl_init('https://app.freshmail.pl/rest/subscriber/add');
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Rest-ApiKey: '.$popup_options['freshmail_key'], 'X-Rest-ApiSign: '.$sign));
											
				$response = curl_exec($curl);
				curl_close($curl);
			} catch (Exception $e) {
			}
		}
	}
}
$ulp_freshmail = new ulp_freshmail_class();
?>