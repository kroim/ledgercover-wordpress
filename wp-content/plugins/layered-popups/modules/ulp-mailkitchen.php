<?php
/* MailKitchen integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_mailkitchen_class {
	var $default_popup_options = array(
		"mailkitchen_enable" => "off",
		"mailkitchen_login" => "",
		"mailkitchen_password" => "",
		"mailkitchen_list" => "",
		"mailkitchen_list_id" => "",
		"mailkitchen_fields" => array(
			'firstname' => '{subscription-name}',
			'first_name' => '{subscription-name}',
			'name' => '{subscription-name}')
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-mailkitchen-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-mailkitchen-fields', array(&$this, "show_fields"));
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
				<h3>'.__('MailKitchen Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable MailKitchen', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mailkitchen_enable" name="ulp_mailkitchen_enable" '.($popup_options['mailkitchen_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to MailKitchen', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to MailKitchen.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Login', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mailkitchen_login" name="ulp_mailkitchen_login" value="'.esc_html($popup_options['mailkitchen_login']).'" class="widefat">
							<br /><em>'.__('Enter your MailKitchen login/email.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Password', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mailkitchen_password" name="ulp_mailkitchen_password" value="'.esc_html($popup_options['mailkitchen_password']).'" class="widefat">
							<br /><em>'.__('Enter your MailKitchen password.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID:', 'ulp').'</th>
						<td>
							<input type="text" id="ulp-mailkitchen-list" name="ulp_mailkitchen_list" value="'.esc_html($popup_options['mailkitchen_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_mailkitchen_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-mailkitchen-list-id" name="ulp_mailkitchen_list_id" value="'.esc_html($popup_options['mailkitchen_list_id']).'" />
							<div id="ulp-mailkitchen-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_mailkitchen_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-mailkitchen-lists", "ulp_login": jQuery("#ulp_mailkitchen_login").val(), "ulp_password": jQuery("#ulp_mailkitchen_password").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-mailkitchen-fields-html">';
		if (!empty($popup_options['mailkitchen_login']) && !empty($popup_options['mailkitchen_password']) && !empty($popup_options['mailkitchen_list_id'])) {
			$fields = $this->get_fields_html($popup_options['mailkitchen_login'], $popup_options['mailkitchen_password'], $popup_options['mailkitchen_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_mailkitchen_fields_button" class="ulp_button button-secondary" onclick="return ulp_mailkitchen_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-mailkitchen-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_mailkitchen_loadfields() {
									jQuery("#ulp-mailkitchen-fields-loading").fadeIn(350);
									jQuery(".ulp-mailkitchen-fields-html").slideUp(350);
									var data = {action: "ulp-mailkitchen-fields", ulp_login: jQuery("#ulp_mailkitchen_login").val(), ulp_password: jQuery("#ulp_mailkitchen_password").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-mailkitchen-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-mailkitchen-fields-html").html(data.html);
												jQuery(".ulp-mailkitchen-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-mailkitchen-fields-html").html("<div class=\'ulp-mailkitchen-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to MailKitchen server.', 'ulp').'</strong></div>");
												jQuery(".ulp-mailkitchen-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-mailkitchen-fields-html").html("<div class=\'ulp-mailkitchen-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to MailKitchen server.', 'ulp').'</strong></div>");
											jQuery(".ulp-mailkitchen-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_mailkitchen_enable"])) $popup_options['mailkitchen_enable'] = "on";
		else $popup_options['mailkitchen_enable'] = "off";
		if ($popup_options['mailkitchen_enable'] == 'on') {
			if (empty($popup_options['mailkitchen_login'])) $errors[] = __('Invalid MailKitchen login/email.', 'ulp');
			if (empty($popup_options['mailkitchen_password'])) $errors[] = __('Invalid MailKitchen password.', 'ulp');
			if (empty($popup_options['mailkitchen_list_id'])) $errors[] = __('Invalid MailKitchen List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_mailkitchen_enable"])) $popup_options['mailkitchen_enable'] = "on";
		else $popup_options['mailkitchen_enable'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_mailkitchen_field_')) == 'ulp_mailkitchen_field_') {
				$field = substr($key, strlen('ulp_mailkitchen_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['mailkitchen_fields'] = $fields;
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['mailkitchen_enable'] == 'on') {
			$data = array(
				'header' => array('email'),
				'datas' => array(array($_subscriber['{subscription-email}']))
			);
			foreach ($popup_options['mailkitchen_fields'] as $key => $value) {
				if (!empty($value)) {
					$data['header'][] = $key;
					$data['datas'][0][] = strtr($value, $_subscriber);
				}
			}
			$wsdl_url = 'http://webservices.mailkitchen.com/server.wsdl';
			try {
				$api = new SoapClient($wsdl_url);
				$token = $api->Authenticate($popup_options['mailkitchen_login'], $popup_options['mailkitchen_password']);
				$result = $api->ImportMember(array($popup_options['mailkitchen_list_id']), $data, $token);
			} catch (Exception $e) {
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_login']) || empty($_POST['ulp_login']) && !isset($_POST['ulp_password']) || empty($_POST['ulp_password'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid credentials!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$login = trim(stripslashes($_POST['ulp_login']));
			$password = trim(stripslashes($_POST['ulp_password']));
			$wsdl_url = 'http://webservices.mailkitchen.com/server.wsdl';

			$lists = array();
			try {
				$api = new SoapClient($wsdl_url);
				$token = $api->Authenticate($login, $password);
				$result = $api->GetSubscriberLists($token);			
				if (is_array($result)) {
					if (sizeof($result) > 0) {
						foreach($result as $list) {
							if (is_array($list)) {
								if (array_key_exists('id', $list) && array_key_exists('name', $list)) {
									$lists[$list['id']] = $list['name'];
								}
							}
						}
					} else {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Lists not found!', 'ulp').'</div>';
						echo json_encode($return_object);
						exit;
					}

				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to MailKitchen webservices!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} catch (Exception $e) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to MailKitchen webservices!', 'ulp').'</div>';
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
			$return_object = array();
			$return_object['status'] = 'OK';
			if (!isset($_POST['ulp_login']) || !isset($_POST['ulp_password']) || empty($_POST['ulp_login']) || empty($_POST['ulp_password'])) {
				$return_object['html'] = '<div class="ulp-mailkitchen-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid credentials!', 'ulp').'</strong></div>';
			} else {
				$login = trim(stripslashes($_POST['ulp_login']));
				$password = trim(stripslashes($_POST['ulp_password']));
				$return_object['html'] = $this->get_fields_html($login, $password, $this->default_popup_options['mailkitchen_fields']);
			}
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_login, $_password, $_fields) {
		$wsdl_url = 'http://webservices.mailkitchen.com/server.wsdl';
		$fields = '';
		try {
			$api = new SoapClient($wsdl_url);
			$token = $api->Authenticate($_login, $_password);
			$result = $api->GetFieldList($token);			
			if (is_array($result)) {
				if (sizeof($result) > 0) {
					$fields = '
					'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate MailKitchen fields with the popup fields.', 'ulp').'
					<table style="min-width: 280px; width: 50%;">';
					foreach ($result as $field => $value) {
						$fields .= '
						<tr>
							<td style="width: 100px;"><strong>'.esc_html($field).':</strong></td>
							<td>
								<input type="text" id="ulp_mailkitchen_field_'.esc_html($field).'" name="ulp_mailkitchen_field_'.esc_html($field).'" value="'.esc_html(array_key_exists($field, $_fields) ? $_fields[$field] : '').'" class="widefat" />
								<br /><em>'.esc_html($field).'</em>
							</td>
						</tr>';
					}
							$fields .= '
					</table>';
				} else {
					$fields = '<div class="ulp-mailkitchen-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
				}
			} else {
				$fields = '<div class="ulp-mailkitchen-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not connect to MailKitchen webservices!', 'ulp').'</strong></div>';
			}
		} catch (Exception $e) {
			$fields = '<div class="ulp-mailkitchen-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not connect to MailKitchen webservices!', 'ulp').'</strong></div>';
		}
		return $fields;
	}
}
$ulp_mailkitchen = new ulp_mailkitchen_class();
?>