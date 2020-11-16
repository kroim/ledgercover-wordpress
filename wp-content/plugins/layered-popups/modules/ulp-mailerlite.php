<?php
/* MailerLite integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_mailerlite_class {
	var $default_popup_options = array(
		'mailerlite_enable' => 'off',
		'mailerlite_api_key' => '',
		'mailerlite_list_id' => ''
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
				<h3>'.__('MailerLite Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable MailerLite', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mailerlite_enable" name="ulp_mailerlite_enable" '.($popup_options['mailerlite_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to MailerLite', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to MailerLite.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mailerlite_api_key" name="ulp_mailerlite_api_key" value="'.esc_html($popup_options['mailerlite_api_key']).'" class="widefat" onchange="ulp_mailerlite_handler();">
							<br /><em>'.__('Enter your MailerLite API Key. You can get your API Key <a href="https://app.mailerlite.com/subscribe/api/" target="_blank">Developer API page</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mailerlite_list_id" name="ulp_mailerlite_list_id" value="'.esc_html($popup_options['mailerlite_list_id']).'" class="widefat">
							<br /><em>'.__('Enter your List ID. You can get List ID from', 'ulp').' <a href="'.admin_url('admin.php').'?action=ulp-mailerlite-lists&key='.base64_encode($popup_options['mailerlite_api_key']).'" target="_blank" id="ulp_mailerlite_lists" title="'.__('Available Lists', 'ulp').'">'.__('this table', 'ulp').'</a>.</em>
							<script>
								function ulp_mailerlite_handler() {
									jQuery("#ulp_mailerlite_lists").attr("href", "'.admin_url('admin.php').'?action=ulp-mailerlite-lists&key="+ulp_encode64(jQuery("#ulp_mailerlite_api_key").val()));
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
		if (isset($ulp->postdata["ulp_mailerlite_enable"])) $popup_options['mailerlite_enable'] = "on";
		else $popup_options['mailerlite_enable'] = "off";
		if ($popup_options['mailerlite_enable'] == 'on') {
			if (empty($popup_options['mailerlite_api_key'])) $errors[] = __('Invalid MailerLite API key', 'ulp');
			if (empty($popup_options['mailerlite_list_id'])) $errors[] = __('Invalid MailerLite list ID', 'ulp');
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
		if (isset($ulp->postdata["ulp_mailerlite_enable"])) $popup_options['mailerlite_enable'] = "on";
		else $popup_options['mailerlite_enable'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function admin_request_handler() {
		global $wpdb;
		if (!empty($_GET['action'])) {
			switch($_GET['action']) {
				case 'ulp-mailerlite-lists':
					if (isset($_GET["key"])) {
						$key = base64_decode($_GET["key"]);
						
						$lists = $this->mailerlite_getlists($key);
						if (!empty($lists)) {
							echo '
<html>
<head>
	<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
	<title>'.__('MailerLite Lists', 'ulp').'</title>
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
		if ($popup_options['mailerlite_enable'] == 'on') {
			$data = array(
				'apiKey' => $popup_options['mailerlite_api_key'],
				'id' => $popup_options['mailerlite_list_id'],
				'email' => $_subscriber['{subscription-email}'],
				'name' => $_subscriber['{subscription-name}'],
				'resubscribe' => '1'
			);
			if (!empty($_subscriber['{subscription-phone}'])) $data['fields'][] = array('name' => 'phone', 'value' => $_subscriber['{subscription-phone}']);
			$request = http_build_query($data);

			$curl = curl_init('https://app.mailerlite.com/api/v1/subscribers/'.$popup_options['mailerlite_list_id'].'/');
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
			//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
								
			$response = curl_exec($curl);
			curl_close($curl);
		}
	}
	function mailerlite_getlists($_key) {
		$curl = curl_init('https://app.mailerlite.com/api/v1/lists/?apiKey='.$_key);
		curl_setopt($curl, CURLOPT_POST, 0);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		
		$response = curl_exec($curl);
		
		if (curl_error($curl)) return array();
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($httpCode != '200') return array();
		curl_close($curl);

		
		$result = json_decode($response, true);
		if(!$result) return array();
		$lists = array();
		foreach ($result['Results'] as $key => $value) {
			$lists[$value['id']] = $value['name'];
		}
		return $lists;
	}
}
$ulp_mailerlite = new ulp_mailerlite_class();
?>