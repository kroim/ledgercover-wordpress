<?php
/* Mailleader integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_mailleader_class {
	var $default_popup_options = array(
		"mailleader_enable" => "off",
		"mailleader_public_key" => "",
		"mailleader_private_key" => "",
		"mailleader_list" => "",
		"mailleader_list_id" => "",
		"mailleader_fields" => array(
			'EMAIL' => '{subscription-email}',
			'FNAME' => '{subscription-name}'
		)
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-mailleader-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-mailleader-fields', array(&$this, "show_fields"));
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
				<h3>'.__('Mailleader Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Mailleader', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mailleader_enable" name="ulp_mailleader_enable" '.($popup_options['mailleader_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Mailleader', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Mailleader.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Public Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mailleader_public_key" name="ulp_mailleader_public_key" value="'.esc_html($popup_options['mailleader_public_key']).'" class="widefat">
							<br /><em>'.__('Enter your Mailleader Public API Key. You can get it <a href="https://apps.mailleader.in/customer/api-keys/index" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Private Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mailleader_private_key" name="ulp_mailleader_private_key" value="'.esc_html($popup_options['mailleader_private_key']).'" class="widefat">
							<br /><em>'.__('Enter your Mailleader Private API Key. You can get it <a href="https://apps.mailleader.in/customer/api-keys/index" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-mailleader-list" name="ulp_mailleader_list" value="'.esc_html($popup_options['mailleader_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_mailleader_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-mailleader-list-id" name="ulp_mailleader_list_id" value="'.esc_html($popup_options['mailleader_list_id']).'" />
							<div id="ulp-mailleader-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_mailleader_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-mailleader-lists", "ulp_public_key": jQuery("#ulp_mailleader_public_key").val(), "ulp_private_key": jQuery("#ulp_mailleader_private_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-mailleader-fields-html">';
		if (!empty($popup_options['mailleader_public_key']) && !empty($popup_options['mailleader_private_key']) && !empty($popup_options['mailleader_list_id'])) {
			$fields = $this->get_fields_html($popup_options['mailleader_public_key'], $popup_options['mailleader_private_key'], $popup_options['mailleader_list_id'], $popup_options['mailleader_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_mailleader_fields_button" class="ulp_button button-secondary" onclick="return ulp_mailleader_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-mailleader-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_mailleader_loadfields() {
									jQuery("#ulp-mailleader-fields-loading").fadeIn(350);
									jQuery(".ulp-mailleader-fields-html").slideUp(350);
									var data = {action: "ulp-mailleader-fields", ulp_public_key: jQuery("#ulp_mailleader_public_key").val(), ulp_private_key: jQuery("#ulp_mailleader_private_key").val(), ulp_list: jQuery("#ulp-mailleader-list-id").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-mailleader-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-mailleader-fields-html").html(data.html);
												jQuery(".ulp-mailleader-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-mailleader-fields-html").html("<div class=\'ulp-mailleader-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Mailleader server.', 'ulp').'</strong></div>");
												jQuery(".ulp-mailleader-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-mailleader-fields-html").html("<div class=\'ulp-mailleader-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Mailleader server.', 'ulp').'</strong></div>");
											jQuery(".ulp-mailleader-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_mailleader_enable"])) $popup_options['mailleader_enable'] = "on";
		else $popup_options['mailleader_enable'] = "off";
		if ($popup_options['mailleader_enable'] == 'on') {
			if (empty($popup_options['mailleader_public_key'])) $errors[] = __('Invalid Mailleader Public API Key.', 'ulp');
			if (empty($popup_options['mailleader_private_key'])) $errors[] = __('Invalid Mailleader Private API Key.', 'ulp');
			if (empty($popup_options['mailleader_list_id'])) $errors[] = __('Invalid Mailleader List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_mailleader_enable"])) $popup_options['mailleader_enable'] = "on";
		else $popup_options['mailleader_enable'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_mailleader_field_')) == 'ulp_mailleader_field_') {
				$field = substr($key, strlen('ulp_mailleader_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['mailleader_fields'] = $fields;
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['mailleader_enable'] == 'on') {
			$result = $this->connect($popup_options['mailleader_public_key'], $popup_options['mailleader_private_key'], 'lists/'.$popup_options['mailleader_list_id'].'/subscribers/search-by-email?EMAIL='.urlencode($_subscriber['{subscription-email}']));
			if(!$result) return;
			$data = array(
				'EMAIL' => $_subscriber['{subscription-email}']
			);
			foreach($popup_options['mailleader_fields'] as $key => $value) {
				if ($key != 'EMAIL') {
					if (!empty($value)) $data[$key] = strtr($value, $_subscriber);
				}
			}
			if ($result['status'] != 'success') {
				$result = $this->connect($popup_options['mailleader_public_key'], $popup_options['mailleader_private_key'], 'lists/'.$popup_options['mailleader_list_id'].'/subscribers', $data);
			} else {
				$result = $this->connect($popup_options['mailleader_public_key'], $popup_options['mailleader_private_key'], 'lists/'.$popup_options['mailleader_list_id'].'/subscribers/'.$result['data']['subscriber_uid'], $data, 'PUT');
			}
			$merge_fields = array();
			$interests = array();
			if (array_key_exists('status', $result) && $result['status'] == 'pending') {
				$this->connect($popup_options['mailleader_public_key'], 'lists/'.urlencode($popup_options['mailleader_list_id']).'/members/'.md5(strtolower($_subscriber['{subscription-email}'])), array(), 'DELETE');
			} else {
				if (array_key_exists('merge_fields', $result)) $merge_fields = $result['merge_fields'];
				if (array_key_exists('interests', $result)) $interests = $result['interests'];
			}
			
			$fields = array();
			if (!empty($popup_options['mailleader_fields'])) $fields = unserialize($popup_options['mailleader_fields']);
			if (!empty($fields) && is_array($fields)) {
				foreach ($fields as $key => $value) {
					if (!empty($value)) {
						$merge_fields[$key] = strtr($value, $_subscriber);
					}
				}
			}
			
			$interests_marked = explode(':', $popup_options['mailleader_groups']);
			if (!empty($interests_marked) && is_array($interests_marked)) {
				foreach ($interests_marked as $interest_marked) {
					if (!empty($interest_marked) && strpos($interest_marked, '-') !== false) {
						$key = null;
						list($tmp, $key) = explode("-", $interest_marked, 2);
						if (!empty($key)) $interests[$key] = true;
					}
				}
			}
			
			$data = array(
				'ip_signup' => $_SERVER['REMOTE_ADDR'],
				'email_address' => $_subscriber['{subscription-email}'],
				'status' => $popup_options['mailleader_double'] == 'on' ? 'pending' : 'subscribed',
				'status_if_new' => $popup_options['mailleader_double'] == 'on' ? 'pending' : 'subscribed'
			);
			if (!empty($merge_fields)) {
				$data['merge_fields'] = $merge_fields;
			}
			if (!empty($interests)) {
				$data['interests'] = $interests;
			}
			$result = $this->connect($popup_options['mailleader_public_key'], 'lists/'.urlencode($popup_options['mailleader_list_id']).'/members/'.md5(strtolower($_subscriber['{subscription-email}'])), $data, 'PUT');
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_public_key']) || empty($_POST['ulp_public_key']) || !isset($_POST['ulp_private_key']) || empty($_POST['ulp_private_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Keys!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$public_key = trim(stripslashes($_POST['ulp_public_key']));
			$private_key = trim(stripslashes($_POST['ulp_private_key']));
			
			$result = $this->connect($public_key, $private_key, 'lists?page=1&per_page=9999');
			
			if (is_array($result) && array_key_exists('status', $result)) {
				if ($result['status'] != 'success') {
					$return_object = array();
					$return_object['status'] = 'OK';
					if (array_key_exists('error', $result)) $return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.$result['error'].'</div>';
					else $return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Keys!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				} else {
					if (intval($result['data']['count']) > 0) {
						foreach ($result['data']['records'] as $list) {
							if (is_array($list)) {
								if (array_key_exists('general', $list)) {
									$lists[$list['general']['list_uid']] = $list['general']['name'];
								}
							}
						}
					} else {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No Lists found!', 'ulp').'</div>';
						echo json_encode($return_object);
						exit;
					}
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$list_html = '';
			if (!empty($lists)) {
				foreach ($lists as $id => $name) {
					$list_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
				}
			} else $list_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No data found!', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $list_html;
			$return_object['items'] = sizeof($lists);
			echo json_encode($return_object);
		}
		exit;
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_public_key']) || !isset($_POST['ulp_private_key']) || !isset($_POST['ulp_list']) || empty($_POST['ulp_public_key']) || empty($_POST['ulp_private_key']) || empty($_POST['ulp_list'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-mailleader-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Keys or List ID.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$public_key = trim(stripslashes($_POST['ulp_public_key']));
			$private_key = trim(stripslashes($_POST['ulp_private_key']));
			$list = trim(stripslashes($_POST['ulp_list']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($public_key, $private_key, $list, $this->default_popup_options['mailleader_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_public_key, $_private_key, $_list, $_fields) {
		$result = $this->connect($_public_key, $_private_key, 'lists/'.$_list.'/fields?page=1&per_page=9999');
		$fields = '';
		if (is_array($result) && array_key_exists('status', $result)) {
			if ($result['status'] != 'success') {
				if (array_key_exists('error', $result)) $fields = '<div class="ulp-mailleader-grouping" style="margin-bottom: 10px;"><strong>'.$result['error'].'</strong></div>';
				else $fields = '<div class="ulp-mailleader-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Keys!', 'ulp').'</strong></div>';
			} else {
				if (sizeof($result['data']['records']) > 0) {
					$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Mailleader fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
					foreach ($result['data']['records'] as $field) {
						if (is_array($field)) {
							if (array_key_exists('tag', $field) && array_key_exists('label', $field)) {
								$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['tag']).':</strong></td>
					<td>
						<input type="text" id="ulp_mailleader_field_'.esc_html($field['tag']).'" name="ulp_mailleader_field_'.esc_html($field['tag']).'" value="'.esc_html(array_key_exists($field['tag'], $_fields) ? $_fields[$field['tag']] : '').'" class="widefat"'.($field['tag'] == 'EMAIL' ? ' readonly="readonly"' : '').' />
						<br /><em>'.esc_html($field['label']).'</em>
					</td>
				</tr>';
							}
						}
					}
					$fields .= '
			</table>';
				} else {
					$fields = '<div class="ulp-mailleader-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
				}
			}
		} else {
			$fields = '<div class="ulp-mailleader-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_public_key, $_private_key, $_path, $_data = array(), $_method = '') {
		$url = 'https://apps.mailleader.in/api/'.ltrim($_path, '/');
		$timestamp = time();
		$headers = array(
			'X-MW-PUBLIC-KEY' => $_public_key,
			'X-MW-REMOTE-ADDR' => $_SERVER['REMOTE_ADDR'],
			'X-MW-TIMESTAMP' => $timestamp
		);
		if (!empty($_data)) $signature_data = array_merge($headers, $_data);
		else $signature_data = $headers;
		ksort($signature_data, SORT_STRING);
		$signature_string = (empty($_data) ? 'GET' : (empty($_method) ? 'POST' : strtoupper($_method))).' '.$url.(strpos($url, '?') === false ? '?' : '&').http_build_query($signature_data, '', '&');
		$signature = hash_hmac('sha1', $signature_string, $_private_key, false);
		$headers['X-MW-SIGNATURE'] = $signature;
		
		try {
			$curl = curl_init($url);
			$curl_headers = array();
			foreach($headers as $name => $value) {
				$curl_headers[] = $name.': '.$value;
			}
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_headers);
			curl_setopt($curl, CURLOPT_USERAGENT, 'MailWizzApi Client version 1.0');
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data, '', '&'));
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 120);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
			//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($curl);
			
			curl_close($curl);
			$result = json_decode($response, true);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
}
$ulp_mailleader = new ulp_mailleader_class();
?>