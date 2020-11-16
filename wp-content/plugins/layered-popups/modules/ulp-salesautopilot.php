<?php
/* SalesAutoPilot integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_salesautopilot_class {
	var $default_popup_options = array(
		"salesautopilot_enable" => "off",
		"salesautopilot_username" => "",
		"salesautopilot_password" => "",
		"salesautopilot_list" => "",
		"salesautopilot_list_id" => "",
		"salesautopilot_form" => "",
		"salesautopilot_form_id" => "",
		"salesautopilot_fields" => array(
			'email' => '{subscription-email}',
			'mssys_firstname' => '{subscription-name}'
		)
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-salesautopilot-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-salesautopilot-forms', array(&$this, "show_forms"));
			add_action('wp_ajax_ulp-salesautopilot-fields', array(&$this, "show_fields"));
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
				<h3>'.__('SalesAutoPilot Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable SalesAutoPilot', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_salesautopilot_enable" name="ulp_salesautopilot_enable" '.($popup_options['salesautopilot_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to SalesAutoPilot', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to SalesAutoPilot.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Username', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_salesautopilot_username" name="ulp_salesautopilot_username" value="'.esc_html($popup_options['salesautopilot_username']).'" class="widefat">
							<br /><em>'.__('Enter your SalesAutoPilot API Username. You can get your API Username from the SalesAutoPilot account: Settings >> Integration >> API Keys.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Password', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_salesautopilot_password" name="ulp_salesautopilot_password" value="'.esc_html($popup_options['salesautopilot_password']).'" class="widefat">
							<br /><em>'.__('Enter your SalesAutoPilot API Password. You can get your API Password from the SalesAutoPilot account: Settings >> Integration >> API Keys.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-salesautopilot-list" name="ulp_salesautopilot_list" value="'.esc_html($popup_options['salesautopilot_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_salesautopilot_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-salesautopilot-list-id" name="ulp_salesautopilot_list_id" value="'.esc_html($popup_options['salesautopilot_list_id']).'" />
							<div id="ulp-salesautopilot-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_salesautopilot_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-salesautopilot-lists", "ulp_username": jQuery("#ulp_salesautopilot_username").val(), "ulp_password": jQuery("#ulp_salesautopilot_password").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Form ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-salesautopilot-form" name="ulp_salesautopilot_form" value="'.esc_html($popup_options['salesautopilot_form']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_salesautopilot_forms_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-salesautopilot-form-id" name="ulp_salesautopilot_form_id" value="'.esc_html($popup_options['salesautopilot_form_id']).'" />
							<div id="ulp-salesautopilot-form-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your Form ID.', 'ulp').'</em>
							<script>
								function ulp_salesautopilot_forms_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-salesautopilot-forms", "ulp_username": jQuery("#ulp_salesautopilot_username").val(), "ulp_password": jQuery("#ulp_salesautopilot_password").val(), "ulp_list": jQuery("#ulp-salesautopilot-list-id").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-salesautopilot-fields-html">';
		if (!empty($popup_options['salesautopilot_username']) && !empty($popup_options['salesautopilot_password']) && !empty($popup_options['salesautopilot_list_id'])) {
			$fields_data = $this->get_fields_html($popup_options['salesautopilot_username'], $popup_options['salesautopilot_password'], $popup_options['salesautopilot_list_id'], $popup_options['salesautopilot_fields']);
			if ($fields_data['status'] == 'OK') echo $fields_data['html'];
		}
		echo '
							</div>
							<a class="ulp-button ulp-button-small" onclick="return ulp_salesautopilot_loadfields(this);"><i class="fas fa-check"></i><label>'.__('Load Fields', 'ulp').'</label></a>
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_salesautopilot_loadfields(_object) {
									if (ulp_saving) return;
									ulp_saving = true;
									jQuery(_object).addClass("ulp-button-disabled");
									jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
									jQuery(".ulp-salesautopilot-fields-html").slideUp(350);
									var post_data = {action: "ulp-salesautopilot-fields", ulp_username: jQuery("#ulp_salesautopilot_username").val(), ulp_password: jQuery("#ulp_salesautopilot_password").val(), ulp_list: jQuery("#ulp-salesautopilot-list-id").val()};
									jQuery.ajax({
										type	: "POST",
										url		: "'.admin_url('admin-ajax.php').'", 
										data	: post_data,
										success	: function(return_data) {
											jQuery(_object).removeClass("ulp-button-disabled");
											jQuery(_object).find("i").attr("class", "fas fa-check");
											var data;
											try {
												if (typeof return_data == "object") data = return_data;
												else data = jQuery.parseJSON(return_data);
												if (data.status == "OK") {
													jQuery(".ulp-salesautopilot-fields-html").html(data.html);
													jQuery(".ulp-salesautopilot-fields-html").slideDown(350);
												} else if (data.status == "ERROR") {
													ulp_global_message_show("danger", data.message);
												} else {
													ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
												}
											} catch(error) {
												ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
											}
											ulp_saving = false;
										},
										error	: function(XMLHttpRequest, textStatus, errorThrown) {
											jQuery(_object).removeClass("ulp-button-disabled");
											jQuery(_object).find("i").attr("class", "fas fa-check");
											ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
											ulp_saving = false;
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
		if (isset($ulp->postdata["ulp_salesautopilot_enable"])) $popup_options['salesautopilot_enable'] = "on";
		else $popup_options['salesautopilot_enable'] = "off";
		if ($popup_options['salesautopilot_enable'] == 'on') {
			if (empty($popup_options['salesautopilot_username']) || empty($popup_options['salesautopilot_password'])) $errors[] = __('Invalid SalesAutoPilot API credentials.', 'ulp');
			if (empty($popup_options['salesautopilot_list_id'])) $errors[] = __('Invalid SalesAutoPilot List ID.', 'ulp');
			if (empty($popup_options['salesautopilot_form_id'])) $errors[] = __('Invalid SalesAutoPilot Form ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_salesautopilot_enable"])) $popup_options['salesautopilot_enable'] = "on";
		else $popup_options['salesautopilot_enable'] = "off";
		
		$popup_options['salesautopilot_fields'] = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_salesautopilot_field_')) == 'ulp_salesautopilot_field_') {
				$field = substr($key, strlen('ulp_salesautopilot_field_'));
				$popup_options['salesautopilot_fields'][$field] = stripslashes(trim($value));
			}
		}
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['salesautopilot_enable'] == 'on') {
			$post_data = array(
				'email' => $_subscriber['{subscription-email}'],
				'mssys_ipaddress' => $_SERVER['REMOTE_ADDR']
			);
			if (!empty($popup_options['salesautopilot_fields']) && is_array($popup_options['salesautopilot_fields'])) {
				foreach ($popup_options['salesautopilot_fields'] as $key => $value) {
					if (!empty($value) && $key != 'email') {
						$post_data[$key] = strtr($value, $_subscriber);
					}
				}
			}
			$result = $this->connect($popup_options['salesautopilot_username'], $popup_options['salesautopilot_password'], 'subscribe/'.urlencode(trim($popup_options['salesautopilot_list_id'])).'/form/'.urlencode(trim($popup_options['salesautopilot_form_id'])), $post_data);
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_username']) || empty($_POST['ulp_username']) || !isset($_POST['ulp_password']) || empty($_POST['ulp_password'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API credentials.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$username = trim(stripslashes($_POST['ulp_username']));
			$password = trim(stripslashes($_POST['ulp_password']));
			
			$result = $this->connect($username, $password, 'getlists');
			if (is_array($result)) {
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
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No lists found.', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API credentials.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$list_html = '';
			if (!empty($lists)) {
				foreach ($lists as $id => $name) {
					$list_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
				}
			} else $list_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No data found.', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $list_html;
			$return_object['items'] = sizeof($lists);
			echo json_encode($return_object);
		}
		exit;
	}
	function show_forms() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$forms = array();
			if (!isset($_POST['ulp_username']) || empty($_POST['ulp_username']) || !isset($_POST['ulp_password']) || empty($_POST['ulp_password']) || !isset($_POST['ulp_list']) || empty($_POST['ulp_list'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API credentials or list ID.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$username = trim(stripslashes($_POST['ulp_username']));
			$password = trim(stripslashes($_POST['ulp_password']));
			$list_id = trim(stripslashes($_POST['ulp_list']));
			
			$result = $this->connect($username, $password, 'getforms/'.urlencode($list_id));
			if (is_array($result)) {
				if (sizeof($result) > 0) {
					foreach ($result as $form) {
						if (is_array($form)) {
							if (array_key_exists('id', $form) && array_key_exists('name', $form)) {
								$forms[$form['id']] = $form['name'];
							}
						}
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No forms found.', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API credentials.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$form_html = '';
			if (!empty($forms)) {
				foreach ($forms as $id => $name) {
					$form_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
				}
			} else $form_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No data found.', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $form_html;
			$return_object['items'] = sizeof($forms);
			echo json_encode($return_object);
		}
		exit;
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_username']) || !isset($_POST['ulp_password']) || !isset($_POST['ulp_list']) || empty($_POST['ulp_username']) || empty($_POST['ulp_password']) || empty($_POST['ulp_list'])) {
				$return_object = array('status' => 'ERROR', 'message' => __('Invalid API credentials or list ID.', 'ulp'));
				echo json_encode($return_object);
				exit;
			}
			$username = trim(stripslashes($_POST['ulp_username']));
			$password = trim(stripslashes($_POST['ulp_password']));
			$list_id = trim(stripslashes($_POST['ulp_list']));
			$return_object = $this->get_fields_html($username, $password, $list_id, $this->default_popup_options['salesautopilot_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_username, $_password, $_list, $_fields) {
		$result = $this->connect($_username, $_password, 'listfields/'.urlencode($_list));
		$fields = '';
		if (!empty($result) && is_array($result)) {
			if (sizeof($result)) {
				$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate SalesAutoPilot fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
				foreach ($result as $field) {
						$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field).':</strong></td>
					<td>
						<input type="text" id="ulp_salesautopilot_field_'.esc_html($field).'" name="ulp_salesautopilot_field_'.esc_html($field).'" value="'.esc_html(array_key_exists($field, $_fields) ? $_fields[$field] : '').'" class="widefat"'.($field == 'email' ? ' readonly="readonly"' : '').' />
					</td>
				</tr>';
				}
				$fields .= '
			</table>';
			} else {
				return array('status' => 'ERROR', 'message' => __('No fields found.', 'ulp'));
			}
		} else {
			return array('status' => 'ERROR', 'message' => __('Inavlid server response.', 'ulp'));
		}
		return array('status' => 'OK', 'html' => $fields);
	}
	function connect($_username, $_password, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		try {
			$url = 'https://api.salesautopilot.com/'.ltrim($_path, '/');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_USERPWD, $_username.':'.$_password);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
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
$ulp_salesautopilot = new ulp_salesautopilot_class();
?>