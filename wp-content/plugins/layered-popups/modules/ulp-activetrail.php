<?php
/* ActiveTrail integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_activetrail_class {
	var $default_popup_options = array(
		"activetrail_enable" => "off",
		"activetrail_api_key" => "",
		"activetrail_groups" => "",
		"activetrail_fields" => "",
		"activetrail_double" => "off"
	);
	function __construct() {
		$this->default_popup_options['activetrail_fields'] = serialize(array('email' => '{subscription-email}', 'first_name' => '{subscription-name}', 'sms' => '{subscription-phone}'));
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-activetrail-groups', array(&$this, "show_groups"));
			add_action('wp_ajax_ulp-activetrail-fields', array(&$this, "show_fields"));
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
				<h3>'.__('ActiveTrail Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable ActiveTrail', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_activetrail_enable" name="ulp_activetrail_enable" '.($popup_options['activetrail_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to ActiveTrail', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to ActiveTrail.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Access Token', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_activetrail_api_key" name="ulp_activetrail_api_key" value="'.esc_html($popup_options['activetrail_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your ActiveTrail Access Token. You can get it <a href="https://app.activetrail.com/Members/Settings/ApiApps.aspx" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-activetrail-fields-html">';
		if (!empty($popup_options['activetrail_api_key'])) {
			$fields = $this->get_fields_html($popup_options['activetrail_api_key'], $popup_options['activetrail_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_activetrail_fields_button" class="ulp_button button-secondary" onclick="return ulp_activetrail_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-activetrail-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_activetrail_loadfields() {
									jQuery("#ulp-activetrail-fields-loading").fadeIn(350);
									jQuery(".ulp-activetrail-fields-html").slideUp(350);
									var data = {action: "ulp-activetrail-fields", ulp_key: jQuery("#ulp_activetrail_api_key").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-activetrail-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-activetrail-fields-html").html(data.html);
												jQuery(".ulp-activetrail-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-activetrail-fields-html").html("<div class=\'ulp-activetrail-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to ActiveTrail server.', 'ulp').'</strong></div>");
												jQuery(".ulp-activetrail-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-activetrail-fields-html").html("<div class=\'ulp-activetrail-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to ActiveTrail server.', 'ulp').'</strong></div>");
											jQuery(".ulp-activetrail-fields-html").slideDown(350);
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Groups', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-activetrail-groups-html">';
		if (!empty($popup_options['activetrail_api_key'])) {
			$groups = $this->get_groups_html($popup_options['activetrail_api_key'], $popup_options['activetrail_groups']);
			echo $groups;
		}
		echo '
							</div>
							<a id="ulp_activetrail_groups_button" class="ulp_button button-secondary" onclick="return ulp_activetrail_loadgroups();">'.__('Load Groups', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-activetrail-groups-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load groups of the list. Ignore if you do not use groups.', 'ulp').'</em>
							<script>
								function ulp_activetrail_loadgroups() {
									jQuery("#ulp-activetrail-groups-loading").fadeIn(350);
									jQuery(".ulp-activetrail-groups-html").slideUp(350);
									var data = {action: "ulp-activetrail-groups", ulp_key: jQuery("#ulp_activetrail_api_key").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-activetrail-groups-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-activetrail-groups-html").html(data.html);
												jQuery(".ulp-activetrail-groups-html").slideDown(350);
											} else {
												jQuery(".ulp-activetrail-groups-html").html("<div class=\'ulp-activetrail-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to ActiveTrail server.', 'ulp').'</strong></div>");
												jQuery(".ulp-activetrail-groups-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-activetrail-groups-html").html("<div class=\'ulp-activetrail-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to ActiveTrail server.', 'ulp').'</strong></div>");
											jQuery(".ulp-activetrail-groups-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_activetrail_enable"])) $popup_options['activetrail_enable'] = "on";
		else $popup_options['activetrail_enable'] = "off";
		if ($popup_options['activetrail_enable'] == 'on') {
			if (empty($popup_options['activetrail_api_key'])) $errors[] = __('Invalid ActiveTrail Access Token.', 'ulp');
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
		if (isset($ulp->postdata["ulp_activetrail_enable"])) $popup_options['activetrail_enable'] = "on";
		else $popup_options['activetrail_enable'] = "off";
		
		$groups = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_activetrail_group_')) == 'ulp_activetrail_group_') {
				$groups[] = substr($key, strlen('ulp_activetrail_group_'));
			}
		}
		$popup_options['activetrail_groups'] = implode(':', $groups);

		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_activetrail_field_')) == 'ulp_activetrail_field_') {
				$field = substr($key, strlen('ulp_activetrail_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['activetrail_fields'] = serialize($fields);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['activetrail_enable'] == 'on') {
			$data = array(
				'subscribe_ip' => $_SERVER['REMOTE_ADDR'],
				'email' => $_subscriber['{subscription-email}'],
				'status' => 1
			);
			$fields = array();
			if (!empty($popup_options['activetrail_fields'])) $fields = unserialize($popup_options['activetrail_fields']);
			if (!empty($fields) && is_array($fields)) {
				foreach ($fields as $key => $value) {
					if (!empty($value)) {
						$data[$key] = strtr($value, $_subscriber);
					}
				}
			}
			$result = $this->connect($popup_options['activetrail_api_key'], 'contacts?SearchTerm='.$_subscriber['{subscription-email}']);
			if (is_array($result)) {
				if (array_key_exists('Message', $result) && !array_key_exists('id', $result[0])) {
					$result = $this->connect($popup_options['activetrail_api_key'], 'contacts', $data);
					$contact_id = $result['id'];
				} else {
					$contact_id = $result[0]['id'];
					$result = $this->connect($popup_options['activetrail_api_key'], 'contacts/'.$contact_id, $data, 'PUT');
				}
			}
			$groups = explode(':', $popup_options['activetrail_groups']);
			foreach ($groups as $group_id) {
				if (!empty($group_id)) {
					$result = $this->connect($popup_options['activetrail_api_key'], 'groups/'.$group_id.'/members', $data);
				}
			}
		}
	}
	function show_groups() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_key']) || empty($_POST['ulp_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-activetrail-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid Access Token.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_key']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_groups_html($key, '');
			echo json_encode($return_object);
		}
		exit;
	}
	function get_groups_html($_key, $_groups) {
		$result = $this->connect($_key, 'groups?Page=1&Limit=100');
		$groups = '';
		$groups_marked = explode(':', $_groups);
		if (is_array($result)) {
			if (array_key_exists('Message', $result)) {
				$groups = '<div class="ulp-activetrail-grouping" style="margin-bottom: 10px;"><strong>'.$result['Message'].'</strong></div>';
			} else {
				if (sizeof($result) > 0) {
					$groups = '<div class="ulp-activetrail-grouping" style="margin-bottom: 10px;">';
					foreach ($result as $category) {
						$groups .= '<div class="ulp-activetrail-group" style="margin: 1px 0 1px 10px;"><input type="checkbox" name="ulp_activetrail_group_'.$category['id'].'"'.(in_array($category['id'], $groups_marked) ? ' checked="checked"' : '').' /> '.$category['name'].'</div>';
					}
					$groups .= '</div>';
				} else {
					$groups = '<div class="ulp-activetrail-grouping" style="margin-bottom: 10px;"><strong>'.__('No groups found.', 'ulp').'</strong></div>';
				}
			}
		} else {
			$groups = '<div class="ulp-activetrail-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $groups;
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_key']) || empty($_POST['ulp_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-activetrail-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid Access Token.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_key']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($key, $this->default_popup_options['activetrail_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_key, $_fields) {
		$result = $this->connect($_key, 'account/contactFields');
		$fields = '';
		$values = unserialize($_fields);
		if (!is_array($values)) $values = array();
		if (!empty($result) && is_array($result)) {
			if (array_key_exists('Message', $result)) {
				$fields = '<div class="ulp-activetrail-grouping" style="margin-bottom: 10px;"><strong>'.$result['Message'].'</strong></div>';
			} else {
				if (sizeof($result)) {
					$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate ActiveTrail fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">
				<tr>
					<td style="width: 100px;"><strong>'.__('Email', 'ulp').':</strong></td>
					<td>
						<input type="text" id="ulp_activetrail_field_email" name="ulp_activetrail_field_email" value="{subscription-email}" class="widefat" readonly="readonly" disabled="disabled" />
						<br /><em>'.__('Email', 'ulp').'</em>
					</td>
				</tr>';
				foreach ($result as $field) {
						if (is_array($field)) {
							if (array_key_exists('field_name', $field) && array_key_exists('field_display_name', $field)) {
								$field_name = strtolower($field['field_name']);
								$field_name = str_replace(array('firstname', 'lastname', 'zipcode'), array('first_name', 'last_name', 'zip_code'), $field_name);
								$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['field_display_name']).':</strong></td>
					<td>
						<input type="text" id="ulp_activetrail_field_'.esc_html($field_name).'" name="ulp_activetrail_field_'.esc_html($field_name).'" value="'.esc_html(array_key_exists($field_name, $values) ? $values[$field_name] : '').'" class="widefat"'.($field_name == 'email' ? ' readonly="readonly"' : '').' />
						<br /><em>'.esc_html($field['field_display_name']).'</em>
					</td>
				</tr>';
							}
						}
					}
					$fields .= '
			</table>';
				} else {
					$fields = '<div class="ulp-activetrail-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
				}
			}
		} else {
			$fields = '<div class="ulp-activetrail-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_api_key, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Authorization: '.$_api_key,
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		try {
			$url = 'https://webapi.mymarketing.co.il/api/'.ltrim($_path, '/');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
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
$ulp_activetrail = new ulp_activetrail_class();
?>