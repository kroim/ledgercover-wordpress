<?php
/* MailFit integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_mailfit_class {
	var $default_popup_options = array(
		"mailfit_enable" => "off",
		"mailfit_api_url" => "",
		"mailfit_api_key" => "",
		"mailfit_list" => "",
		"mailfit_list_id" => "",
		"mailfit_fields" => array(
			'EMAIL' => '{subscription-email}',
			'FIRST_NAME' => '{subscription-name}'
		)
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-mailfit-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-mailfit-fields', array(&$this, "show_fields"));
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
				<h3>'.__('MailFit Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable MailFit', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mailfit_enable" name="ulp_mailfit_enable" '.($popup_options['mailfit_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to MailFit', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to MailFit.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Endpoint', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mailfit_api_url" name="ulp_mailfit_api_url" value="'.esc_html($popup_options['mailfit_api_url']).'" class="widefat">
							<br /><em>'.__('Enter your MailFit API Endpoint. You can get it on API page in your MailFit account.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Token', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mailfit_api_key" name="ulp_mailfit_api_key" value="'.esc_html($popup_options['mailfit_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your MailFit API Token. You can get it on API page in your MailFit account.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-mailfit-list" name="ulp_mailfit_list" value="'.esc_html($popup_options['mailfit_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_mailfit_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-mailfit-list-id" name="ulp_mailfit_list_id" value="'.esc_html($popup_options['mailfit_list_id']).'" />
							<div id="ulp-mailfit-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_mailfit_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-mailfit-lists", "ulp_api_url": jQuery("#ulp_mailfit_api_url").val(), "ulp_api_key": jQuery("#ulp_mailfit_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-mailfit-fields-html">';
		if (!empty($popup_options['mailfit_api_url']) && !empty($popup_options['mailfit_api_key']) && !empty($popup_options['mailfit_list_id'])) {
			$fields = $this->get_fields_html($popup_options['mailfit_api_url'], $popup_options['mailfit_api_key'], $popup_options['mailfit_list_id'], $popup_options['mailfit_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_mailfit_fields_button" class="ulp_button button-secondary" onclick="return ulp_mailfit_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-mailfit-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_mailfit_loadfields() {
									jQuery("#ulp-mailfit-fields-loading").fadeIn(350);
									jQuery(".ulp-mailfit-fields-html").slideUp(350);
									var data = {action: "ulp-mailfit-fields", ulp_url: jQuery("#ulp_mailfit_api_url").val(), ulp_key: jQuery("#ulp_mailfit_api_key").val(), ulp_list: jQuery("#ulp-mailfit-list-id").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-mailfit-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-mailfit-fields-html").html(data.html);
												jQuery(".ulp-mailfit-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-mailfit-fields-html").html("<div class=\'ulp-mailfit-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to MailFit server.', 'ulp').'</strong></div>");
												jQuery(".ulp-mailfit-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-mailfit-fields-html").html("<div class=\'ulp-mailfit-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to MailFit server.', 'ulp').'</strong></div>");
											jQuery(".ulp-mailfit-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_mailfit_enable"])) $popup_options['mailfit_enable'] = "on";
		else $popup_options['mailfit_enable'] = "off";
		if ($popup_options['mailfit_enable'] == 'on') {
			if (strlen($popup_options['mailfit_api_url']) == 0 || !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $popup_options['mailfit_api_url'])) $errors[] = __('Invalid MailFit API Endpoint.', 'ulp');
			if (empty($popup_options['mailfit_api_key'])) $errors[] = __('Invalid MailFit API Token.', 'ulp');
			if (empty($popup_options['mailfit_list_id'])) $errors[] = __('Invalid MailFit List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_mailfit_enable"])) $popup_options['mailfit_enable'] = "on";
		else $popup_options['mailfit_enable'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_mailfit_field_')) == 'ulp_mailfit_field_') {
				$field = substr($key, strlen('ulp_mailfit_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['mailfit_fields'] = $fields;
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['mailfit_enable'] == 'on') {
			$data = array(
				'EMAIL' => $_subscriber['{subscription-email}'],
				'ip_address' => $_SERVER['REMOTE_ADDR']
			);
			if (!empty($popup_options['mailfit_fields'])) {
				foreach ($popup_options['mailfit_fields'] as $key => $value) {
					if (!empty($value) && $key != 'EMAIL') {
						$data[$key] = strtr($value, $_subscriber);
					}
				}
			}
			
			$result = $this->connect($popup_options['mailfit_api_url'], $popup_options['mailfit_api_key'], 'lists/'.$popup_options['mailfit_list_id'].'/subscribers/'.strtolower($_subscriber['{subscription-email}']));
			if (!empty($result) && array_key_exists('subscriber', $result) && !empty($result['subscriber'])) {
				$subscriber_id = $result['subscriber']['uid'];
				// Add API call to update subscriber details.
			} else {
				$result = $this->connect($popup_options['mailfit_api_url'], $popup_options['mailfit_api_key'], 'lists/'.$popup_options['mailfit_list_id'].'/subscribers/store', $data);
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_api_url']) || empty($_POST['ulp_api_url']) || !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $_POST['ulp_api_url']) || !isset($_POST['ulp_api_key']) || empty($_POST['ulp_api_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Endpoint or Token!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$url = trim(stripslashes($_POST['ulp_api_url']));
			$key = trim(stripslashes($_POST['ulp_api_key']));
			
			$result = $this->connect($url, $key, 'lists');
			
			if (is_array($result)) {
				if (sizeof($result) > 0) {
					foreach ($result as $list) {
						if (is_array($list)) {
							if (array_key_exists('uid', $list) && array_key_exists('name', $list)) {
								$lists[$list['uid']] = $list['name'];
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
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Token!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$list_html = '';
			if (!empty($lists)) {
				foreach ($lists as $id => $name) {
					$list_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
				}
			} else $list_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No Lists found!', 'ulp').'</div>';
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
			if (!isset($_POST['ulp_url']) || empty($_POST['ulp_url']) || !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $_POST['ulp_url']) || !isset($_POST['ulp_key']) || !isset($_POST['ulp_list']) || empty($_POST['ulp_key']) || empty($_POST['ulp_list'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-mailfit-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Credentials or List ID.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$url = trim(stripslashes($_POST['ulp_url']));
			$key = trim(stripslashes($_POST['ulp_key']));
			$list = trim(stripslashes($_POST['ulp_list']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($url, $key, $list, $this->default_popup_options['mailfit_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_url, $_key, $_list, $_fields) {
		$result = $this->connect($_url, $_key, 'lists/'.urlencode($_list));
		$fields = '';
		if (!empty($result)) {
			if (!array_key_exists('list', $result)) {
				$fields = '<div class="ulp-mailfit-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
			} else {
				if (array_key_exists('fields', $result['list']) && $result['list']['fields'] > 0) {
					$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate MailFit fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
					foreach ($result['list']['fields'] as $field) {
						if (is_array($field)) {
							if (array_key_exists('tag', $field) && array_key_exists('label', $field)) {
								$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['tag']).':</strong></td>
					<td>
						<input type="text" id="ulp_mailfit_field_'.esc_html($field['tag']).'" name="ulp_mailfit_field_'.esc_html($field['tag']).'" value="'.esc_html(array_key_exists($field['tag'], $_fields) ? $_fields[$field['tag']] : '').'" class="widefat"'.($field['tag'] == 'EMAIL' ? ' readonly="readonly"' : '').' />
						<br /><em>'.esc_html(ucfirst($field['label'])).'.</em>
					</td>
				</tr>';
							}
						}
					}
					$fields .= '
			</table>';
				} else {
					$fields = '<div class="ulp-mailfit-grouping" style="margin-bottom: 10px;"><strong>'.__('Make sure that you use latest version of MailFit application.', 'ulp').'</strong></div>';
				}
			}
		} else {
			$fields = '<div class="ulp-mailfit-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_api_url, $_api_key, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Accept: application/json'
		);
		try {
			$url = rtrim($_api_url, '/').'/'.ltrim($_path, '/');
			if (strpos($url, '?') === false) $url .= '?api_token='.$_api_key;
			else $url .= '&api_token='.$_api_key;
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $_data);
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
$ulp_mailfit = new ulp_mailfit_class();
?>