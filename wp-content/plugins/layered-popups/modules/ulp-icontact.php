<?php
/* iContact integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_icontact_class {
	var $default_popup_options = array(
		"icontact_enable" => "off",
		"icontact_appid" => "",
		"icontact_apiusername" => "",
		"icontact_apipassword" => "",
		"icontact_listid" => "",
		"icontact_messageid" => ""
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
				<h3>'.__('iContact Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable iContact', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_icontact_enable" name="ulp_icontact_enable" '.($popup_options['icontact_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to iContact', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to iContact.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('AppID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_icontact_appid" name="ulp_icontact_appid" value="'.esc_html($popup_options['icontact_appid']).'" class="widefat" onchange="ulp_icontact_handler();">
							<br /><em>'.__('Obtained when you <a href="http://developer.icontact.com/documentation/register-your-app/" target="_blank">Register the API application</a>. This identifier is used to uniquely identify your application.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Username', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_icontact_apiusername" name="ulp_icontact_apiusername" value="'.esc_html($popup_options['icontact_apiusername']).'" class="widefat" onchange="ulp_icontact_handler();">
							<br /><em>'.__('The iContact username for logging into your iContact account.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Password', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_icontact_apipassword" name="ulp_icontact_apipassword" value="'.esc_html($popup_options['icontact_apipassword']).'" class="widefat" onchange="ulp_icontact_handler();">
							<br /><em>'.__('The API application password set when the application was registered. This API password is used as input when your application authenticates to the API. This password is not the same as the password you use to log in to iContact.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_icontact_listid" name="ulp_icontact_listid" value="'.esc_html($popup_options['icontact_listid']).'" class="widefat">
							<br /><em>'.__('Enter your List ID. You can get List ID from', 'ulp').' <a href="'.admin_url('admin.php').'?action=ulp-icontact-lists&appid='.base64_encode($popup_options['icontact_appid']).'&user='.base64_encode($popup_options['icontact_apiusername']).'&pass='.base64_encode($popup_options['icontact_apipassword']).'" target="_blank" id="ulp_icontact_lists" title="'.__('Available Lists', 'ulp').'">'.__('this table', 'ulp').'</a>.</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Confirmation Message ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_icontact_messageid" name="ulp_icontact_messageid" value="'.esc_html($popup_options['icontact_messageid']).'" class="widefat">
							<br /><em>'.__('Indicates which email should be used as the confirmation-request message. You can get Message ID from', 'ulp').' <a href="'.admin_url('admin.php').'?action=ulp-icontact-messages&appid='.base64_encode($popup_options['icontact_appid']).'&user='.base64_encode($popup_options['icontact_apiusername']).'&pass='.base64_encode($popup_options['icontact_apipassword']).'" target="_blank" id="ulp_icontact_messages" title="'.__('Available Messages', 'ulp').'">'.__('this table', 'ulp').'</a>. '.__('Leave the field empty, if you do not need confirmation functionality.', 'ulp').'</em>
							<script>
								function ulp_icontact_handler() {
									jQuery("#ulp_icontact_lists").attr("href", "'.admin_url('admin.php').'?action=ulp-icontact-lists&appid="+ulp_encode64(jQuery("#ulp_icontact_appid").val())+"&user="+ulp_encode64(jQuery("#ulp_icontact_apiusername").val())+"&pass="+ulp_encode64(jQuery("#ulp_icontact_apipassword").val()));
									jQuery("#ulp_icontact_messages").attr("href", "'.admin_url('admin.php').'?action=ulp-icontact-messages&appid="+ulp_encode64(jQuery("#ulp_icontact_appid").val())+"&user="+ulp_encode64(jQuery("#ulp_icontact_apiusername").val())+"&pass="+ulp_encode64(jQuery("#ulp_icontact_apipassword").val()));
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
		if (isset($ulp->postdata["ulp_icontact_enable"])) $popup_options['icontact_enable'] = "on";
		else $popup_options['icontact_enable'] = "off";
		if ($popup_options['icontact_enable'] == 'on') {
			if (empty($popup_options['icontact_appid'])) $errors[] = __('Invalid iContact App ID.', 'ulp');
			if (empty($popup_options['icontact_apiusername'])) $errors[] = __('Invalid iContact API Username.', 'ulp');
			if (empty($popup_options['icontact_apipassword'])) $errors[] = __('Invalid iContact API Password.', 'ulp');
			if (empty($popup_options['icontact_listid'])) $errors[] = __('Invalid iContact List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_icontact_enable"])) $popup_options['icontact_enable'] = "on";
		else $popup_options['icontact_enable'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function admin_request_handler() {
		global $wpdb;
		if (!empty($_GET['action'])) {
			switch($_GET['action']) {
				case 'ulp-icontact-lists':
					if (isset($_GET["appid"]) && isset($_GET["user"]) && isset($_GET["pass"])) {
						$appid = base64_decode($_GET["appid"]);
						$apiusername = base64_decode($_GET["user"]);
						$apipassword = base64_decode($_GET["pass"]);
						
						$lists = $this->icontact_getlists($appid, $apiusername, $apipassword);
						if (!empty($lists)) {
							echo '
<html>
<head>
	<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
	<title>'.__('iContact Lists', 'ulp').'</title>
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
					exit;
					break;
				case 'ulp-icontact-messages':
					if (isset($_GET["appid"]) && isset($_GET["user"]) && isset($_GET["pass"])) {
						$appid = base64_decode($_GET["appid"]);
						$apiusername = base64_decode($_GET["user"]);
						$apipassword = base64_decode($_GET["pass"]);
						
						$messages = $this->icontact_getmessages($appid, $apiusername, $apipassword);
						if (!empty($messages)) {
							echo '
<html>
<head>
	<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
	<title>'.__('iContact Messages', 'ulp').'</title>
</head>
<body>
	<table style="width: 100%;">
		<tr>
			<td style="width: 170px; font-weight: bold;">'.__('Message ID', 'ulp').'</td>
			<td style="font-weight: bold;">'.__('Message Name', 'ulp').'</td>
		</tr>';
							foreach ($messages as $key => $value) {
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
					exit;
					break;
				default:
					break;
			}
		}
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['icontact_enable'] == 'on') {
			$data = $this->icontact_makecall($popup_options['icontact_appid'], $popup_options['icontact_apiusername'], $popup_options['icontact_apipassword'], '/a/', null, 'accounts');
			if (!empty($data['errors'])) return;
			$account = $data['response'][0];
			if (empty($account) || intval($account->enabled != 1)) return;
			$data = $this->icontact_makecall($popup_options['icontact_appid'], $popup_options['icontact_apiusername'], $popup_options['icontact_apipassword'], '/a/'.$account->accountId.'/c/', null, 'clientfolders');
			if (!empty($data['errors'])) return;
			$client = $data['response'][0];
			if (empty($client)) return;
			$contact['email'] = $_subscriber['{subscription-email}'];
			$contact['firstName'] = $_subscriber['{subscription-name}'];
			$contact['status'] = 'normal';
			$data = $this->icontact_makecall($popup_options['icontact_appid'], $popup_options['icontact_apiusername'], $popup_options['icontact_apipassword'], '/a/'.$account->accountId.'/c/'.$client->clientFolderId.'/contacts', array($contact), 'contacts');
			if (!empty($data['errors'])) return;
			$contact = $data['response'][0];
			if (empty($contact)) return;
			$subscriber['contactId'] = $contact->contactId;
			$subscriber['listId'] = $popup_options['icontact_listid'];
			if (!empty($popup_options['icontact_messageid']) && $popup_options['icontact_messageid'] == preg_replace('/[^0-9]/', '', $popup_options['icontact_messageid'])) {
				$subscriber['status'] = 'pending';
				$subscriber['confirmationMessageId'] = $popup_options['icontact_messageid'];
			} else {
				$subscriber['status'] = 'normal';
			}
			$data = $this->icontact_makecall($popup_options['icontact_appid'], $popup_options['icontact_apiusername'], $popup_options['icontact_apipassword'], '/a/'.$account->accountId.'/c/'.$client->clientFolderId.'/subscriptions', array($subscriber), 'subscriptions');
		}
	}
	function icontact_getlists($appid, $apiusername, $apipassword) {
		$data = $this->icontact_makecall($appid, $apiusername, $apipassword, '/a/', null, 'accounts');
		if (!empty($data['errors'])) return array();
		$account = $data['response'][0];
		if (empty($account) || intval($account->enabled != 1)) return;
		$data = $this->icontact_makecall($appid, $apiusername, $apipassword, '/a/'.$account->accountId.'/c/', null, 'clientfolders');
		if (!empty($data['errors'])) return array();
		$client = $data['response'][0];
		if (empty($client)) return array();
		$data = $this->icontact_makecall($appid, $apiusername, $apipassword, '/a/'.$account->accountId.'/c/'.$client->clientFolderId.'/lists', array(), 'lists');
		if (!empty($data['errors'])) return array();
		if (!is_array($data['response'])) return array();
		$lists = array();
		foreach ($data['response'] as $list) {
			$lists[$list->listId] = $list->name;
		}
		return $lists;
	}
	function icontact_getmessages($appid, $apiusername, $apipassword) {
		$data = $this->icontact_makecall($appid, $apiusername, $apipassword, '/a/', null, 'accounts');
		if (!empty($data['errors'])) return array();
		$account = $data['response'][0];
		if (empty($account) || intval($account->enabled != 1)) return;
		$data = $this->icontact_makecall($appid, $apiusername, $apipassword, '/a/'.$account->accountId.'/c/', null, 'clientfolders');
		if (!empty($data['errors'])) return array();
		$client = $data['response'][0];
		if (empty($client)) return array();
		$data = $this->icontact_makecall($appid, $apiusername, $apipassword, '/a/'.$account->accountId.'/c/'.$client->clientFolderId.'/messages', array(), 'messages');
		if (!empty($data['errors'])) return array();
		if (!is_array($data['response'])) return array();
		$messages = array();
		foreach ($data['response'] as $message) {
			$messages[$message->messageId] = $message->messageName;
		}
		return $messages;
	}
	function icontact_makecall($appid, $apiusername, $apipassword, $resource, $postdata = null, $returnkey = null) {
		$return = array();
		$url = "https://app.icontact.com/icp".$resource;
		$headers = array(
			'Except:', 
			'Accept:  application/json', 
			'Content-type:  application/json', 
			'Api-Version:  2.2',
			'Api-AppId:  '.$appid, 
			'Api-Username:  '.$apiusername, 
			'Api-Password:  '.$apipassword
		);
		$handle = curl_init();
		curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		if (!empty($postdata)) {
			curl_setopt($handle, CURLOPT_POST, true);
			curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($postdata));
		}
		curl_setopt($handle, CURLOPT_URL, $url);
		if (!$response_json = curl_exec($handle)) {
			$return['errors'][] = __('Unable to execute the cURL handle.', 'ulp');
		}
		if (!$response = json_decode($response_json)) {
			$return['errors'][] = __('The iContact API did not return valid JSON.', 'ulp');
		}
		curl_close($handle);
		if (!empty($response->errors)) {
			foreach ($response->errors as $error) {
				$return['errors'][] = $error;
			}
		}
		if (!empty($return['errors'])) return $return;
		if (empty($returnkey)) {
			$return['response'] = $response;
		} else {
			$return['response'] = $response->$returnkey;
		}
		return $return;
	}
}
$ulp_icontact = new ulp_icontact_class();
?>