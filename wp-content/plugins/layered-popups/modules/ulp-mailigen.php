<?php
/* Mailigen integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_mailigen_class {
	var $default_popup_options = array(
		"mailigen_enable" => "off",
		"mailigen_api_key" => "",
		"mailigen_list" => "",
		"mailigen_list_id" => "",
		"mailigen_fields" => array('EMAIL' => '{subscription-email}', 'FNAME' => '{subscription-name}'),
		"mailigen_double" => "off",
		"mailigen_welcome" => "off"
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-mailigen-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-mailigen-fields', array(&$this, "show_fields"));
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
				<h3>'.__('Mailigen Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Mailigen', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mailigen_enable" name="ulp_mailigen_enable" '.($popup_options['mailigen_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Mailigen', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Mailigen.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mailigen_api_key" name="ulp_mailigen_api_key" value="'.esc_html($popup_options['mailigen_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Mailigen API Key. You can get it <a href="https://admin.mailigen.com/settings/api" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-mailigen-list" name="ulp_mailigen_list" value="'.esc_html($popup_options['mailigen_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_mailigen_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-mailigen-list-id" name="ulp_mailigen_list_id" value="'.esc_html($popup_options['mailigen_list_id']).'" />
							<div id="ulp-mailigen-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_mailigen_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-mailigen-lists", "ulp_api_key": jQuery("#ulp_mailigen_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-mailigen-fields-html">';
		if (!empty($popup_options['mailigen_api_key']) && !empty($popup_options['mailigen_list_id'])) {
			$fields = $this->get_fields_html($popup_options['mailigen_api_key'], $popup_options['mailigen_list_id'], $popup_options['mailigen_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_mailigen_fields_button" class="ulp_button button-secondary" onclick="return ulp_mailigen_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-mailigen-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_mailigen_loadfields() {
									jQuery("#ulp-mailigen-fields-loading").fadeIn(350);
									jQuery(".ulp-mailigen-fields-html").slideUp(350);
									var data = {action: "ulp-mailigen-fields", ulp_key: jQuery("#ulp_mailigen_api_key").val(), ulp_list: jQuery("#ulp-mailigen-list-id").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-mailigen-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-mailigen-fields-html").html(data.html);
												jQuery(".ulp-mailigen-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-mailigen-fields-html").html("<div class=\'ulp-mailigen-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Mailigen server.', 'ulp').'</strong></div>");
												jQuery(".ulp-mailigen-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-mailigen-fields-html").html("<div class=\'ulp-mailigen-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Mailigen server.', 'ulp').'</strong></div>");
											jQuery(".ulp-mailigen-fields-html").slideDown(350);
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Double opt-in', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mailigen_double" name="ulp_mailigen_double" '.($popup_options['mailigen_double'] == "on" ? 'checked="checked"' : '').'"> '.__('Enable double opt-in', 'ulp').'
							<br /><em>'.__('Enable/disable double opt-in.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Welcome message', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mailigen_welcome" name="ulp_mailigen_welcome" '.($popup_options['mailigen_welcome'] == "on" ? 'checked="checked"' : '').'"> '.__('Send welcome message', 'ulp').'
							<br /><em>'.__('Send or not welcome (thank you) message.', 'ulp').'</em>
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
		if (isset($ulp->postdata["ulp_mailigen_enable"])) $popup_options['mailigen_enable'] = "on";
		else $popup_options['mailigen_enable'] = "off";
		if ($popup_options['mailigen_enable'] == 'on') {
			if (empty($popup_options['mailigen_api_key'])) $errors[] = __('Invalid Mailigen API Key.', 'ulp');
			if (empty($popup_options['mailigen_list_id'])) $errors[] = __('Invalid Mailigen List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_mailigen_enable"])) $popup_options['mailigen_enable'] = "on";
		else $popup_options['mailigen_enable'] = "off";
		if (isset($ulp->postdata["ulp_mailigen_double"])) $popup_options['mailigen_double'] = "on";
		else $popup_options['mailigen_double'] = "off";
		if (isset($ulp->postdata["ulp_mailigen_welcome"])) $popup_options['mailigen_welcome'] = "on";
		else $popup_options['mailigen_welcome'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_mailigen_field_')) == 'ulp_mailigen_field_') {
				$field = substr($key, strlen('ulp_mailigen_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['mailigen_fields'] = $fields;
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['mailigen_enable'] == 'on') {
			$merge_fields = array();
//			$result = $this->connect($popup_options['mailigen_api_key'], 'listMemberInfo', array('id' => $popup_options['mailigen_list_id'], 'email_address' => $_subscriber['{subscription-email}']));
//			if (is_array($result) && array_key_exists('status', $result)) {
//				if ($result['status'] != 'subscribed') {
//					$result = $this->connect($popup_options['mailigen_api_key'], 'listUnsubscribe', array('id' => $popup_options['mailigen_list_id'], 'email_address' => $_subscriber['{subscription-email}'], 'delete_member' => true, 'send_goodbye' => false, 'send_notify' => false));
//				} else $merge_fields = $result['merges'];
//			}
			foreach ($popup_options['mailigen_fields'] as $key => $value) {
				if (!empty($value)) {
					$merge_fields[$key] = strtr($value, $_subscriber);
				}
			}
			$data = array(
				'id' => $popup_options['mailigen_list_id'],
				'ip_signup' => $_SERVER['REMOTE_ADDR'],
				'email_address' => $_subscriber['{subscription-email}'],
				'update_existing' => true,
				'double_optin' => $popup_options['mailigen_double'] == 'on' ? true : false,
				'send_welcome' => $popup_options['mailigen_welcome'] == 'on' ? true : false
			);
			if (!empty($merge_fields)) {
				$data['merge_vars'] = $merge_fields;
			}
			$result = $this->connect($popup_options['mailigen_api_key'], 'listSubscribe', $data);
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_api_key']) || empty($_POST['ulp_api_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_api_key']));
			
			$result = $this->connect($key, 'lists', array('start' => 0, 'limit' => 1000));
			if (is_array($result) && !array_key_exists('error', $result)) {
				if (sizeof($result) > 0) {
					foreach ($result as $list) {
						if (is_array($list)) {
							if (array_key_exists('id', $list) && array_key_exists('name', $list)) {
								$lists[$list['id']] = $list['name'];
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
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key!', 'ulp').'</div>';
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
			if (!isset($_POST['ulp_key']) || !isset($_POST['ulp_list']) || empty($_POST['ulp_key']) || empty($_POST['ulp_list'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-mailigen-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Key or List ID.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_key']));
			$list = trim(stripslashes($_POST['ulp_list']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($key, $list, $this->default_popup_options['mailigen_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_key, $_list, $_fields) {
		$result = $this->connect($_key, 'listMergeVars', array('id' => $_list));
		if (is_array($result) && !array_key_exists('error', $result)) {
			if (sizeof($result) > 0) {
				$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Mailigen fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
					foreach ($result as $field) {
						if (is_array($field)) {
							if (array_key_exists('tag', $field) && array_key_exists('name', $field)) {
								$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['tag']).':</strong></td>
					<td>
						<input type="text" id="ulp_mailigen_field_'.esc_html($field['tag']).'" name="ulp_mailigen_field_'.esc_html($field['tag']).'" value="'.esc_html(array_key_exists($field['tag'], $_fields) ? $_fields[$field['tag']] : '').'" class="widefat"'.($field['tag'] == 'EMAIL' ? ' readonly="readonly"' : '').' />
						<br /><em>'.esc_html($field['name']).'</em>
					</td>
				</tr>';
							}
						}
					}
					$fields .= '
			</table>';
			} else {
				$fields = '<div class="ulp-mailigen-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
			}
		} else {
			$fields = '<div class="ulp-mailigen-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid API Key or List ID.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_api_key, $_method, $_data = array()) {
		$headers = array(
			'Content-Type: application/x-www-form-urlencoded',
			'User-Agent: MGAPI/1.5'
		);
		$_data['apikey'] = $_api_key;
		try {
			$url = 'https://api.mailigen.com/1.5/?output=json&method='.$_method;
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
			curl_setopt($curl, CURLOPT_TIMEOUT, 20);
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
$ulp_mailigen = new ulp_mailigen_class();
?>