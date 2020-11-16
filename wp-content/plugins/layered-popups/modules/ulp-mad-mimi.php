<?php
/* Mad Mimi integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_madmimi_class {
	var $default_popup_options = array(
		'madmimi_enable' => 'off',
		'madmimi_login' => '',
		'madmimi_api_key' => '',
		'madmimi_list_id' => ''
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
				<h3>'.__('Mad Mimi Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Mad Mimi', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_madmimi_enable" name="ulp_madmimi_enable" '.($popup_options['madmimi_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Mad Mimi', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Mad Mimi.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Username/E-mail', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_madmimi_login" name="ulp_madmimi_login" value="'.esc_html($popup_options['madmimi_login']).'" class="widefat" onchange="ulp_madmimi_handler();">
							<br /><em>'.__('Enter your Mad Mimi username/e-mail.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_madmimi_api_key" name="ulp_madmimi_api_key" value="'.esc_html($popup_options['madmimi_api_key']).'" class="widefat" onchange="ulp_madmimi_handler();">
							<br /><em>'.__('Enter your Mad Mimi API Key. You can get your API Key <a href="https://madmimi.com/user/edit?account_info_tabs=account_info_personal" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_madmimi_list_id" name="ulp_madmimi_list_id" value="'.esc_html($popup_options['madmimi_list_id']).'" class="widefat">
							<br /><em>'.__('Enter your List ID. You can get List ID from', 'ulp').' <a href="'.admin_url('admin.php').'?action=ulp-madmimi-lists&login='.base64_encode($popup_options['madmimi_login']).'&key='.base64_encode($popup_options['madmimi_api_key']).'" target="_blank" id="ulp_madmimi_lists" title="'.__('Available Lists', 'ulp').'">'.__('this table', 'ulp').'</a>.</em>
							<script>
								function ulp_madmimi_handler() {
									jQuery("#ulp_madmimi_lists").attr("href", "'.admin_url('admin.php').'?action=ulp-madmimi-lists&login="+ulp_encode64(jQuery("#ulp_madmimi_login").val())+"&key="+ulp_encode64(jQuery("#ulp_madmimi_api_key").val()));
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
		if (isset($ulp->postdata["ulp_madmimi_enable"])) $popup_options['madmimi_enable'] = "on";
		else $popup_options['madmimi_enable'] = "off";
		if ($popup_options['madmimi_enable'] == 'on') {
			if (empty($popup_options['madmimi_login'])) $errors[] = __('Invalid Mad Mimi login', 'ulp');
			if (empty($popup_options['madmimi_api_key'])) $errors[] = __('Invalid Mad Mimi API key', 'ulp');
			if (empty($popup_options['madmimi_list_id'])) $errors[] = __('Invalid Mad Mimi list ID', 'ulp');
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
		if (isset($ulp->postdata["ulp_madmimi_enable"])) $popup_options['madmimi_enable'] = "on";
		else $popup_options['madmimi_enable'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function admin_request_handler() {
		global $wpdb;
		if (!empty($_GET['action'])) {
			switch($_GET['action']) {
				case 'ulp-madmimi-lists':
					if (isset($_GET["login"]) && isset($_GET["key"])) {
						$login = base64_decode($_GET["login"]);
						$key = base64_decode($_GET["key"]);
						
						$lists = $this->madmimi_getlists($login, $key);
						if (!empty($lists)) {
							echo '
<html>
<head>
	<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
	<title>'.__('Mad Mimi Lists', 'ulp').'</title>
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
			<td>'.esc_html($value).'</td>
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
		if ($popup_options['madmimi_enable'] == 'on') {
			$request = http_build_query(array(
				'email' => $_subscriber['{subscription-email}'],
				'first_name' => $_subscriber['{subscription-name}'],
				'last_name' => '',
				'username' => $popup_options['madmimi_login'],
				'api_key' => $popup_options['madmimi_api_key']
			));

			$curl = curl_init('http://api.madmimi.com/audience_lists/'.$popup_options['madmimi_list_id'].'/add');
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $request);

			curl_setopt($curl, CURLOPT_TIMEOUT, 20);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
			curl_setopt($curl, CURLOPT_HEADER, 0);
								
			$response = curl_exec($curl);
			curl_close($curl);
		}
	}
	function madmimi_getlists($_login, $_key) {
		$curl = curl_init('http://api.madmimi.com/audience_lists/lists.json?'.http_build_query(array('username' => $_login, 'api_key' => $_key)));
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
											
		$result = json_decode($response, true);
		if(!$result) return array();
		$lists = array();
		foreach ($result as $key => $value) {
			$lists[$value['id']] = $value['name'];
		}
		return $lists;
	}
}
$ulp_madmimi = new ulp_madmimi_class();
?>