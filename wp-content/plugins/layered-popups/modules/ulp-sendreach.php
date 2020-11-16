<?php
/* SendReach integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_sendreach_class {
	var $default_popup_options = array(
		'sendreach_enable' => 'off',
		'sendreach_key' => '',
		'sendreach_secret' => '',
		'sendreach_user' => '',
		'sendreach_listid' => ''
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
				<h3>'.__('SendReach Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable SendReach', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_sendreach_enable" name="ulp_sendreach_enable" '.($popup_options['sendreach_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to SendReach', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to SendReach.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_sendreach_key" name="ulp_sendreach_key" value="'.esc_html($popup_options['sendreach_key']).'" class="widefat" onchange="ulp_sendreach_lists();">
							<br /><em>'.__('Enter your SendReach API Key. You can find it <a href="http://portal.sendreach.com/" target="_blank">here</a> (go to Account Settings, click My App and create new app).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Secret', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_sendreach_secret" name="ulp_sendreach_secret" value="'.esc_html($popup_options['sendreach_secret']).'" class="widefat" onchange="ulp_sendreach_lists();">
							<br /><em>'.__('Enter your SendReach API Secret. You can take it on the same page with API Key.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('User ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_sendreach_user" name="ulp_sendreach_user" value="'.esc_html($popup_options['sendreach_user']).'" class="ic_input_number" onchange="ulp_sendreach_lists();">
							<br /><em>'.__('Enter your User ID. You can take it at top-right corner on <a href="http://portal.sendreach.com/" target="_blank">this page</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_sendreach_listid" name="ulp_sendreach_listid" value="'.esc_html($popup_options['sendreach_listid']).'" class="ic_input_number">
							<br /><em>'.__('Enter your List ID. You can get List ID from', 'ulp').' <a href="'.admin_url('admin.php').'?action=ulp-sendreach-lists&key='.base64_encode($popup_options['sendreach_key']).'&secret='.base64_encode($popup_options['sendreach_secret']).'&user='.base64_encode($popup_options['sendreach_user']).'" target="_blank" id="ulp_sendreach_lists" title="'.__('Available Lists', 'ulp').'">'.__('this table', 'ulp').'</a>.</em>
							<script>
								function ulp_sendreach_lists() {
									jQuery("#ulp_sendreach_lists").attr("href", "'.admin_url('admin.php').'?action=ulp-sendreach-lists&key="+ulp_encode64(jQuery("#ulp_sendreach_key").val())+"&secret="+ulp_encode64(jQuery("#ulp_sendreach_secret").val())+"&user="+ulp_encode64(jQuery("#ulp_sendreach_user").val()));
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
		if (isset($ulp->postdata["ulp_sendreach_enable"])) $popup_options['sendreach_enable'] = "on";
		else $popup_options['sendreach_enable'] = "off";
		if ($popup_options['sendreach_enable'] == 'on') {
			if (empty($popup_options['sendreach_secret'])) $errors[] = __('Invalid SendReach API Secret', 'ulp');
			if (empty($popup_options['sendreach_key'])) $errors[] = __('Invalid SendReach API Key', 'ulp');
			if (empty($popup_options['sendreach_user'])) $errors[] = __('Invalid SendReach User ID', 'ulp');
			if (empty($popup_options['sendreach_listid'])) $errors[] = __('Invalid SendReach list ID', 'ulp');
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
		if (isset($ulp->postdata["ulp_sendreach_enable"])) $popup_options['sendreach_enable'] = "on";
		else $popup_options['sendreach_enable'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function admin_request_handler() {
		global $wpdb;
		if (!empty($_GET['action'])) {
			switch($_GET['action']) {
				case 'ulp-sendreach-lists':
					if (isset($_GET["key"]) && isset($_GET["secret"]) && isset($_GET["user"])) {
						$key = trim(base64_decode($_GET["key"]));
						$secret = trim(base64_decode($_GET["secret"]));
						$user = base64_decode($_GET["user"]);
						$lists = array();
						try {
							$sendreach_url = 'http://api.sendreach.com/index.php?key='.urlencode($key).'&secret='.urlencode($secret).'&action=lists_view';

							$curl = curl_init($sendreach_url);
							curl_setopt($curl, CURLOPT_POST, 0);
							curl_setopt($curl, CURLOPT_TIMEOUT, 10);
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
							curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
							curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
												
							$response = curl_exec($curl);
							curl_close($curl);
							
							$result = json_decode($response, true);
							if($result) {
								foreach ($result as $list) {
									if (is_array($list)) {
										if (array_key_exists('id', $list) && array_key_exists('list_name', $list)) {
											$lists[$list['id']] = $list['list_name'];
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
	<title>'.__('SendReach Lists', 'ulp').'</title>
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
				default:
					break;
			}
		}
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['sendreach_enable'] == 'on') {
			try {
				$sendreach_url = 'http://api.sendreach.com/index.php?key='.urlencode($popup_options['sendreach_key']).'&secret='.urlencode($popup_options['sendreach_secret']).'&action=subscriber_add&user_id='.urlencode($popup_options['sendreach_user']).'&list_id='.urlencode($popup_options['sendreach_listid']).'&first_name='.urlencode($_subscriber['{subscription-name}']).'&last_name=&email='.urlencode($_subscriber['{subscription-email}']).'&client_ip='.urlencode($_SERVER['REMOTE_ADDR']);

				$curl = curl_init($sendreach_url);
				curl_setopt($curl, CURLOPT_POST, 0);
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
												
				$response = curl_exec($curl);
				curl_close($curl);
			} catch (Exception $e) {
			}
		}
	}
}
$ulp_sendreach = new ulp_sendreach_class();
?>