<?php
/* Sendloop integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_sendloop_class {
	var $default_popup_options = array(
		"sendloop_enable" => "off",
		"sendloop_api_key" => "",
		"sendloop_list" => "",
		"sendloop_list_id" => "",
		"sendloop_fields" => array()
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-sendloop-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-sendloop-fields', array(&$this, "show_fields"));
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
				<h3>'.__('Sendloop Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Sendloop', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_sendloop_enable" name="ulp_sendloop_enable" '.($popup_options['sendloop_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Sendloop', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Sendloop.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_sendloop_api_key" name="ulp_sendloop_api_key" value="'.esc_html($popup_options['sendloop_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Sendloop API Key. You can get it on Settings page in Sendloop dashboard.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-sendloop-list" name="ulp_sendloop_list" value="'.esc_html($popup_options['sendloop_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_sendloop_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-sendloop-list-id" name="ulp_sendloop_list_id" value="'.esc_html($popup_options['sendloop_list_id']).'" />
							<div id="ulp-sendloop-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_sendloop_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-sendloop-lists", "ulp_api_key": jQuery("#ulp_sendloop_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-sendloop-fields-html">';
		if (!empty($popup_options['sendloop_api_key']) && !empty($popup_options['sendloop_list_id'])) {
			$fields = $this->get_fields_html($popup_options['sendloop_api_key'], $popup_options['sendloop_list_id'], $popup_options['sendloop_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_sendloop_fields_button" class="ulp_button button-secondary" onclick="return ulp_sendloop_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-sendloop-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_sendloop_loadfields() {
									jQuery("#ulp-sendloop-fields-loading").fadeIn(350);
									jQuery(".ulp-sendloop-fields-html").slideUp(350);
									var data = {action: "ulp-sendloop-fields", ulp_api_key: jQuery("#ulp_sendloop_api_key").val(), ulp_list: jQuery("#ulp-sendloop-list-id").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-sendloop-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-sendloop-fields-html").html(data.html);
												jQuery(".ulp-sendloop-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-sendloop-fields-html").html("<div class=\'ulp-sendloop-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Sendloop server.', 'ulp').'</strong></div>");
												jQuery(".ulp-sendloop-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-sendloop-fields-html").html("<div class=\'ulp-sendloop-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Sendloop server.', 'ulp').'</strong></div>");
											jQuery(".ulp-sendloop-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_sendloop_enable"])) $popup_options['sendloop_enable'] = "on";
		else $popup_options['sendloop_enable'] = "off";
		if ($popup_options['sendloop_enable'] == 'on') {
			if (empty($popup_options['sendloop_api_key'])) $errors[] = __('Invalid Sendloop API Key.', 'ulp');
			if (empty($popup_options['sendloop_list_id'])) $errors[] = __('Invalid Sendloop List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_sendloop_enable"])) $popup_options['sendloop_enable'] = "on";
		else $popup_options['sendloop_enable'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_sendloop_field_')) == 'ulp_sendloop_field_') {
				$field = substr($key, strlen('ulp_sendloop_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['sendloop_fields'] = $fields;
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['sendloop_enable'] == 'on') {
			$data = array(
				'ListID' => $popup_options['sendloop_list_id'], 
				'EmailAddress' => $_subscriber['{subscription-email}'],
				'SubscriptionIP' => $_SERVER['REMOTE_ADDR']
			);
			foreach ($popup_options['sendloop_fields'] as $key => $value) {
				if (!empty($value)) {
					$data['Fields[CustomField'.$key.']'] = strtr($value, $_subscriber);
				}
			}
			$result = $this->connect($popup_options['sendloop_api_key'], 'subscriber.subscribe', $data);
//			$result = $this->connect($popup_options['sendloop_api_key'], 'subscriber.get', array('ListID' => $popup_options['sendloop_list_id'], 'EmailAddress' => $_subscriber['{subscription-email}']));
//			if (is_array($result) && array_key_exists('Success', $result) && $result['Success']) {
//				$result = $this->connect($popup_options['sendloop_api_key'], 'subscriber.subscribe', $data);
//				$result = $this->connect($popup_options['sendloop_api_key'], 'subscriber.update', $data);
//			} else {
//				$result = $this->connect($popup_options['sendloop_api_key'], 'subscriber.subscribe', $data);
//			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			
			if (!isset($_POST['ulp_api_key']) || empty($_POST['ulp_api_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API credentials!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_api_key']));
			
			$result = $this->connect($key, 'list.getlist');
			if (is_array($result) && array_key_exists('Success', $result)) {
				if ($result['Success']) {
					if (sizeof($result['Lists']) > 0) {
						foreach ($result['Lists'] as $list) {
							if (is_array($list)) {
								if (array_key_exists('ListID', $list) && array_key_exists('Name', $list)) {
									$lists[$list['ListID']] = $list['Name'];
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
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.(array_key_exists('ErrorMessage', $result) ? $result['ErrorMessage'] : __('No Lists found!', 'ulp')).'</div>';
					echo json_encode($return_object);
					exit;
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response.', 'ulp').'</div>';
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
			if (!isset($_POST['ulp_api_key']) || !isset($_POST['ulp_list']) || empty($_POST['ulp_api_key']) || empty($_POST['ulp_list'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-sendloop-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Key or List ID.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_api_key']));
			$list = trim(stripslashes($_POST['ulp_list']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($key, $list, $this->default_popup_options['sendloop_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_key, $_list, $_fields) {
		$result = $this->connect($_key, 'list.get', array('ListID' => $_list, 'GetCustomFields' => 1));
		$fields = '';
		if (is_array($result) && array_key_exists('Success', $result)) {
			if ($result['Success']) {
				if (array_key_exists('CustomFields', $result) && sizeof($result['CustomFields']) > 0) {
					$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Sendloop fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
					foreach ($result['CustomFields'] as $field) {
						if (is_array($field)) {
							if (array_key_exists('CustomFieldID', $field) && array_key_exists('FieldName', $field)) {
								$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['FieldName']).':</strong></td>
					<td>
						<input type="text" id="ulp_sendloop_field_'.esc_html($field['CustomFieldID']).'" name="ulp_sendloop_field_'.esc_html($field['CustomFieldID']).'" value="'.esc_html(array_key_exists($field['CustomFieldID'], $_fields) ? $_fields[$field['CustomFieldID']] : '').'" class="widefat" />
						<br /><em>'.esc_html($field['FieldName']).'</em>
					</td>
				</tr>';
							}
						}
					}
					$fields .= '
			</table>';
				} else {
					$fields = '<div class="ulp-sendloop-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
				}
			} else {
				$fields = '<div class="ulp-sendloop-grouping" style="margin-bottom: 10px;"><strong>'.(array_key_exists('ErrorMessage', $result) ? $result['ErrorMessage'] : __('No fields found!', 'ulp')).'</strong></div>';
			}
		} else {
			$fields = '<div class="ulp-sendloop-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_api_key, $_path, $_data = array(), $_method = '') {
		$_data['APIKey'] = $_api_key;
		try {
			$url = 'http://app.sendloop.com/api/v3/'.trim($_path, '/').'/json';
			$curl = curl_init($url);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $_data);
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
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
$ulp_sendloop = new ulp_sendloop_class();
?>