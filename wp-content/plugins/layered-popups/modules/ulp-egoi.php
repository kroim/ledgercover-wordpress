<?php
/* E-goi integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_egoi_class {
	var $default_popup_options = array(
		'egoi_enable' => 'off',
		'egoi_api_key' => '',
		'egoi_list_id' => '',
		'egoi_double' => 'off'
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
				<h3>'.__('E-goi Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable E-goi', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_egoi_enable" name="ulp_egoi_enable" '.($popup_options['egoi_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to E-goi', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to E-goi.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_egoi_api_key" name="ulp_egoi_api_key" value="'.esc_html($popup_options['egoi_api_key']).'" class="widefat" onchange="ulp_egoi_handler();">
							<br /><em>'.__('Enter your E-goi API Key. You can get your API Key on Apps page in your account.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_egoi_list_id" name="ulp_egoi_list_id" value="'.esc_html($popup_options['egoi_list_id']).'" class="ic_input_number">
							<br /><em>'.__('Enter your List ID. You can get List ID from', 'ulp').' <a href="'.admin_url('admin.php').'?action=ulp-egoi-lists&key='.base64_encode($popup_options['egoi_api_key']).'" target="_blank" id="ulp_egoi_lists" title="'.__('Available Lists', 'ulp').'">'.__('this table', 'ulp').'</a>.</em>
							<script>
								function ulp_egoi_handler() {
									jQuery("#ulp_egoi_lists").attr("href", "'.admin_url('admin.php').'?action=ulp-egoi-lists&key="+ulp_encode64(jQuery("#ulp_egoi_api_key").val()));
								}
							</script>
						</td>
					<tr>
						<th>'.__('Double opt-in', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_egoi_double" name="ulp_egoi_double" '.($popup_options['egoi_double'] == "on" ? 'checked="checked"' : '').'"> '.__('Ask users to confirm their subscription', 'ulp').'
							<br /><em>'.__('Control whether a double opt-in confirmation message is sent.', 'ulp').'</em>
						</td>
					</tr>
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
		if (isset($ulp->postdata["ulp_egoi_enable"])) $popup_options['egoi_enable'] = "on";
		else $popup_options['egoi_enable'] = "off";
		if ($popup_options['egoi_enable'] == 'on') {
			if (empty($popup_options['egoi_api_key'])) $errors[] = __('Invalid E-goi API key', 'ulp');
			if (empty($popup_options['egoi_list_id'])) $errors[] = __('Invalid E-goi list ID', 'ulp');
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
		if (isset($ulp->postdata["ulp_egoi_enable"])) $popup_options['egoi_enable'] = "on";
		else $popup_options['egoi_enable'] = "off";
		if (isset($ulp->postdata["ulp_egoi_double"])) $popup_options['egoi_double'] = "on";
		else $popup_options['egoi_double'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function admin_request_handler() {
		global $wpdb;
		if (!empty($_GET['action'])) {
			switch($_GET['action']) {
				case 'ulp-egoi-lists':
					if (isset($_GET["key"])) {
						$key = base64_decode($_GET["key"]);
						
						$lists = $this->egoi_getlists($key);
						if (!empty($lists)) {
							echo '
<html>
<head>
	<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
	<title>'.__('E-goi Lists', 'ulp').'</title>
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
		if ($popup_options['egoi_enable'] == 'on') {
			$data = array(
				"method" => 'subscriberData',
				"functionOptions" => array(
					'apikey' => $popup_options['egoi_api_key'],
					'listID' => $popup_options['egoi_list_id'],
					'subscriber' => $_subscriber['{subscription-email}']
				),
				"type" => "json"
			);
			$request = http_build_query($data);
			$curl = curl_init('http://api.e-goi.com/v2/rest.php');
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
															
			$response = curl_exec($curl);
												
			if (curl_error($curl)) return;
			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			if ($httpCode != '200') return;
			curl_close($curl);
												
			$result = json_decode($response, true);
			
			if(!$result) return;
			if (!array_key_exists('Egoi_Api', $result)) return;
			if (!array_key_exists('subscriberData', $result['Egoi_Api'])) return;
			
			$create = false;
			$create_edit = false;
			if (array_key_exists('ERROR', $result['Egoi_Api']['subscriberData'])) $create = true;
			if (array_key_exists('subscriber', $result['Egoi_Api']['subscriberData']) && array_key_exists('REMOVE_METHOD', $result['Egoi_Api']['subscriberData']['subscriber'])) {
				$create = true;
				$create_edit = true;
			}

			if ($create) {
				$data = array(
					"method" => 'addSubscriber',
					"functionOptions" => array(
						'apikey' => $popup_options['egoi_api_key'],
						'listID' => $popup_options['egoi_list_id'],
						'status' => ($popup_options['egoi_double'] == 'on' ? '0' : '1'),
						'email' => $_subscriber['{subscription-email}'],
						'first_name' => $_subscriber['{subscription-name}'],
					),
					"type" => "json"
				);
				if (!empty($_subscriber['{subscription-phone}'])) $data['functionOptions']['cellphone'] = $_subscriber['{subscription-phone}'];
				if (!empty($_subscriber['{subscription-phone}'])) $data['functionOptions']['telephone'] = $_subscriber['{subscription-phone}'];
				$request = http_build_query($data);
				$curl = curl_init('http://api.e-goi.com/v2/rest.php');
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				$response = curl_exec($curl);
				curl_close($curl);
			}
			if (!$create || $create_edit) {
				$data = array(
					"method" => 'editSubscriber',
					"functionOptions" => array(
						'apikey' => $popup_options['egoi_api_key'],
						'listID' => $popup_options['egoi_list_id'],
						'subscriber' => $_subscriber['{subscription-email}'],
						'first_name' => $_subscriber['{subscription-name}'],
					),
					"type" => "json"
				);
				if (!empty($_subscriber['{subscription-phone}'])) $data['functionOptions']['cellphone'] = $_subscriber['{subscription-phone}'];
				if (!empty($_subscriber['{subscription-phone}'])) $data['functionOptions']['telephone'] = $_subscriber['{subscription-phone}'];
				$request = http_build_query($data);
				$curl = curl_init('http://api.e-goi.com/v2/rest.php');
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				$response = curl_exec($curl);
				curl_close($curl);
			}
		}
	}
	function egoi_getlists($_key) {
		$data = array(
			"method" => 'getLists',
			"functionOptions" => array(
				'apikey' => $_key
			),
			"type" => "json"
		);
		$request = http_build_query($data);
		$curl = curl_init('http://api.e-goi.com/v2/rest.php');
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
														
		$response = curl_exec($curl);
											
		if (curl_error($curl)) return array();
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($httpCode != '200') return array();
		curl_close($curl);
											
		$result = json_decode($response, true);
		
		if(!$result) return array();
		if (!array_key_exists('Egoi_Api', $result)) return array();
		if (!array_key_exists('getLists', $result['Egoi_Api'])) return array();
		if (array_key_exists('ERROR', $result['Egoi_Api']['getLists'])) return array();
		if (array_key_exists('response', $result['Egoi_Api']['getLists']) && $result['Egoi_Api']['getLists']['response'] == 'INVALID') return array();
		
		$lists = array();
		foreach ($result['Egoi_Api']['getLists'] as $key => $value) {
			if (is_array($value)) {
				$lists[$value['listnum']] = $value['title'];
			}
		}
		return $lists;
	}
}
$ulp_egoi = new ulp_egoi_class();
?>