<?php
/* MailWizz integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_mailwizz_class {
	var $default_popup_options = array(
		'mailwizz_enable' => 'off',
		'mailwizz_api_url' => '',
		'mailwizz_public_key' => '',
		'mailwizz_private_key' => '',
		'mailwizz_list' => '',
		'mailwizz_list_id' => '',
		'mailwizz_fields' => array(
			'EMAIL' => '{subscription-email}',
			'FNAME' => '{subscription-name}'
		)
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
			add_action('wp_ajax_ulp-mailwizz-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-mailwizz-fields', array(&$this, "show_fields"));
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
				<h3>'.__('MailWizz Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable MailWizz', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mailwizz_enable" name="ulp_mailwizz_enable" '.($popup_options['mailwizz_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to MailWizz', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to MailWizz.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API URL', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mailwizz_api_url" name="ulp_mailwizz_api_url" value="'.esc_html($popup_options['mailwizz_api_url']).'" class="widefat">
							<br /><em>'.__('Enter your MailWizz API URL. If the MailWizz powered website does not use clean urls, make sure your API URL has the index.php part of url included.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Public Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mailwizz_public_key" name="ulp_mailwizz_public_key" value="'.esc_html($popup_options['mailwizz_public_key']).'" class="widefat">
							<br /><em>'.__('Enter your MailWizz API Public Key. You can generate it in MailWizz customer area.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Private Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mailwizz_private_key" name="ulp_mailwizz_private_key" value="'.esc_html($popup_options['mailwizz_private_key']).'" class="widefat">
							<br /><em>'.__('Enter your MailWizz API Private Key. You can generate it in MailWizz customer area.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-mailwizz-list" name="ulp_mailwizz_list" value="'.esc_html($popup_options['mailwizz_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_mailwizz_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-mailwizz-list-id" name="ulp_mailwizz_list_id" value="'.esc_html($popup_options['mailwizz_list_id']).'" />
							<div id="ulp-mailwizz-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_mailwizz_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-mailwizz-lists", "ulp_api_url": jQuery("#ulp_mailwizz_api_url").val(), "ulp_public_key": jQuery("#ulp_mailwizz_public_key").val(), "ulp_private_key": jQuery("#ulp_mailwizz_private_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-mailwizz-fields-html">';
		if (!empty($popup_options['mailwizz_api_url']) && !empty($popup_options['mailwizz_public_key']) && !empty($popup_options['mailwizz_private_key']) && !empty($popup_options['mailwizz_list_id'])) {
			$fields = $this->get_fields_html($popup_options['mailwizz_api_url'], $popup_options['mailwizz_public_key'], $popup_options['mailwizz_private_key'], $popup_options['mailwizz_list_id'], $popup_options['mailwizz_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a class="ulp-button ulp-button-small" onclick="return ulp_mailwizz_loadfields(this);"><i class="fas fa-check"></i><label>Load Fields</label></a>
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_mailwizz_loadfields(_object) {
									jQuery(_object).addClass("ulp-button-disabled");
									jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
									jQuery(".ulp-mailwizz-fields-html").slideUp(350);
									var data = {action: "ulp-mailwizz-fields", ulp_api_url: jQuery("#ulp_mailwizz_api_url").val(), ulp_public_key: jQuery("#ulp_mailwizz_public_key").val(), ulp_private_key: jQuery("#ulp_mailwizz_private_key").val(), ulp_list: jQuery("#ulp-mailwizz-list-id").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery(_object).removeClass("ulp-button-disabled");
										jQuery(_object).find("i").attr("class", "fas fa-check");
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-mailwizz-fields-html").html(data.html);
												jQuery(".ulp-mailwizz-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-mailwizz-fields-html").html("<div class=\'ulp-mailwizz-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to MailWizz server.', 'ulp').'</strong></div>");
												jQuery(".ulp-mailwizz-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-mailwizz-fields-html").html("<div class=\'ulp-mailwizz-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to MailWizz server.', 'ulp').'</strong></div>");
											jQuery(".ulp-mailwizz-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_mailwizz_enable"])) $popup_options['mailwizz_enable'] = "on";
		else $popup_options['mailwizz_enable'] = "off";
		if ($popup_options['mailwizz_enable'] == 'on') {
			if (empty($popup_options['mailwizz_api_url']) || !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $popup_options['mailwizz_api_url'])) $errors[] = __('Invalid MailWizz API URL.', 'ulp');
			if (empty($popup_options['mailwizz_public_key'])) $errors[] = __('Invalid MailWizz API Public Key.', 'ulp');
			if (empty($popup_options['mailwizz_private_key'])) $errors[] = __('Invalid MailWizz API Private Key.', 'ulp');
			if (empty($popup_options['mailwizz_list_id'])) $errors[] = __('Invalid MailWizz List ID.', 'ulp');
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
		$popup_options['mailwizz_fields'] = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_mailwizz_field_')) == 'ulp_mailwizz_field_') {
				$field = substr($key, strlen('ulp_mailwizz_field_'));
				$popup_options['mailwizz_fields'][$field] = stripslashes(trim($value));
			}
		}
		if (isset($ulp->postdata["ulp_mailwizz_enable"])) $popup_options['mailwizz_enable'] = "on";
		else $popup_options['mailwizz_enable'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['mailwizz_enable'] == 'on') {
			$result = $this->_connect($popup_options['mailwizz_api_url'], $popup_options['mailwizz_public_key'], $popup_options['mailwizz_private_key'], 'lists/'.$popup_options['mailwizz_list_id'].'/subscribers/search-by-email?EMAIL='.urlencode($_subscriber['{subscription-email}']));
			if(!$result || !is_array($result) || !array_key_exists('status', $result)) return;
			$post_data = array(
				'EMAIL' => $_subscriber['{subscription-email}'], 
				'details' => array('ip_address' => $_SERVER['REMOTE_ADDR'])
			);
			foreach ($popup_options['mailwizz_fields'] as $key => $value) {
				if (!empty($value) && $key != 'EMAIL') {
					$post_data[$key] = strtr($value, $_subscriber);
				}
			}
			if ($result['status'] != 'success') {
				$result = $this->_connect($popup_options['mailwizz_api_url'], $popup_options['mailwizz_public_key'], $popup_options['mailwizz_private_key'], 'lists/'.$popup_options['mailwizz_list_id'].'/subscribers', 'POST', $post_data);
			} else {
				$result = $this->_connect($popup_options['mailwizz_api_url'], $popup_options['mailwizz_public_key'], $popup_options['mailwizz_private_key'], 'lists/'.$popup_options['mailwizz_list_id'].'/subscribers/'.$result['data']['subscriber_uid'], 'PUT', $post_data);
			}
		}
	}
	function show_lists() {
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_public_key']) || empty($_POST['ulp_public_key']) || !isset($_POST['ulp_private_key']) || empty($_POST['ulp_private_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Credentails!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$api_url = trim(stripslashes($_POST['ulp_api_url']));
			$public_key = trim(stripslashes($_POST['ulp_public_key']));
			$private_key = trim(stripslashes($_POST['ulp_private_key']));
		
			$result = $this->_connect($api_url, $public_key, $private_key, 'lists?page=1&per_page=9999');
		
			if(!$result || !is_array($result)) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			if (!array_key_exists('status', $result) || $result['status'] != 'success') {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.esc_html($result['error']).'</div>';
				echo json_encode($return_object);
				exit;
			}
			if (!array_key_exists('data', $result) || !array_key_exists('count', $result['data']) || $result['data']['count'] == 0) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No lists found.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$lists = array();
			foreach ($result['data']['records'] as $key => $value) {
				$lists[$value['general']['list_uid']] = $value['general']['name'];
			}
			$list_html = '';
			if (!empty($result['data']['records'])) {
				foreach ($result['data']['records'] as $key => $value) {
					$list_html .= '<a href="#" data-id="'.esc_html($value['general']['list_uid']).'" data-title="'.esc_html($value['general']['list_uid']).(!empty($value['general']['name']) ? ' | '.esc_html($value['general']['name']) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($value['general']['list_uid']).(!empty($value['general']['name']) ? ' | '.esc_html($value['general']['name']) : '').'</a>';
				}
			} else $list_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No data found!', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $list_html;
			$return_object['items'] = sizeof($result['data']['records']);
			echo json_encode($return_object);
		}
		exit;
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_public_key']) || empty($_POST['ulp_public_key']) || !isset($_POST['ulp_private_key']) || empty($_POST['ulp_private_key']) || !isset($_POST['ulp_list']) || empty($_POST['ulp_list'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-mailwizz-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Credentials or List ID.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$api_url = trim(stripslashes($_POST['ulp_api_url']));
			$public_key = trim(stripslashes($_POST['ulp_public_key']));
			$private_key = trim(stripslashes($_POST['ulp_private_key']));
			$list = trim(stripslashes($_POST['ulp_list']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($api_url, $public_key, $private_key, $list, $this->default_popup_options['mailwizz_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_api_url, $_public_key, $_private_key, $_list, $_fields) {
		$result = $this->_connect($_api_url, $_public_key, $_private_key, 'lists/'.$_list.'/fields?page=1&per_page=9999');
		
		if(!$result || !is_array($result)) {
			return '<div class="ulp-mailwizz-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		if (!array_key_exists('status', $result) || $result['status'] != 'success') {
			return '<div class="ulp-mailwizz-grouping" style="margin-bottom: 10px;"><strong>'.esc_html($result['error']).'</strong></div>';
		}
		if (!array_key_exists('data', $result) || !array_key_exists('records', $result['data']) || sizeof($result['data']['records']) == 0) {
			return '<div class="ulp-mailwizz-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
		}

		$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate MailWizz fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
		foreach ($result['data']['records'] as $field) {
			if (is_array($field)) {
				if (array_key_exists('tag', $field) && array_key_exists('label', $field)) {
					$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['label']).':</strong></td>
					<td>
						<input type="text" id="ulp_mailwizz_field_'.esc_html($field['tag']).'" name="ulp_mailwizz_field_'.esc_html($field['tag']).'" value="'.esc_html(array_key_exists($field['tag'], $_fields) ? $_fields[$field['tag']] : '').'" class="widefat"'.($field['tag'] == 'EMAIL' ? ' readonly="readonly"' : '').' />
						<br /><em>'.esc_html($field['label'].' ('.$field['tag'].')').'</em>
					</td>
				</tr>';
				}
			}
		}
		$fields .= '
			</table>';
		return $fields;
	}
	
	function _connect($_api_url, $_public_key, $_private_key, $_path, $_method = 'GET', $_data = null) {
		try {
			$url = rtrim($_api_url, '/').'/'.rtrim($_path, '/');
			$timestamp = time();
			$headers = array(
				'X-MW-PUBLIC-KEY' => $_public_key,
				'X-MW-REMOTE-ADDR' => $_SERVER['REMOTE_ADDR'],
				'X-MW-TIMESTAMP' => $timestamp
			);
			if (is_array($_data) && !empty($_data)) $signature_data = array_merge($headers, $_data);
			else $signature_data = $headers;
			ksort($signature_data, SORT_STRING);
			$signature_string = strtoupper($_method).' '.$url.(strpos($url, '?') === false ? '?' : '&').http_build_query($signature_data, '', '&');
			$signature = hash_hmac('sha1', $signature_string, $_private_key, false);
			$headers['X-MW-SIGNATURE'] = $signature;
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_USERAGENT, 'MailWizzApi Client version 1.0');
			curl_setopt($ch, CURLOPT_AUTOREFERER , true);
			//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$curl_headers = array();
			$headers['X-HTTP-Method-Override'] = strtoupper($_method);
			foreach($headers as $name => $value) {
				$curl_headers[] = $name.': '.$value;
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
			if (is_array($_data) && !empty($_data)) {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($_method));
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_data, '', '&'));
			}
			$response = curl_exec($ch);
			curl_close($ch);
			$result = json_decode($response, true);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
}
$ulp_mailwizz = new ulp_mailwizz_class();
?>