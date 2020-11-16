<?php
/* Klick Tipp integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_klicktipp_class {
	var $default_popup_options = array(
		"klicktipp_enable" => "off",
		"klicktipp_username" => "",
		"klicktipp_password" => "",
		"klicktipp_list" => "",
		"klicktipp_list_id" => "",
		"klicktipp_tag" => "",
		"klicktipp_tag_id" => "",
		"klicktipp_fields" => "",
		"klicktipp_double" => "off",
		"klicktipp_welcome" => "off"
	);
	function __construct() {
		$this->default_popup_options['klicktipp_fields'] = serialize(array('fieldFirstName' => '{subscription-name}'));
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-klicktipp-fields', array(&$this, "show_fields"));
			add_action('wp_ajax_ulp-klicktipp-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-klicktipp-tags', array(&$this, "show_tags"));
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
				<h3>'.__('Klick Tipp Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Klick Tipp', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_klicktipp_enable" name="ulp_klicktipp_enable" '.($popup_options['klicktipp_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Klick Tipp', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Klick Tipp.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Klick Tipp Username', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_klicktipp_username" name="ulp_klicktipp_username" value="'.esc_html($popup_options['klicktipp_username']).'" class="widefat">
							<br /><em>'.__('Enter your Klick Tipp Username.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Klick Tipp Password', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_klicktipp_password" name="ulp_klicktipp_password" value="'.esc_html($popup_options['klicktipp_password']).'" class="widefat">
							<br /><em>'.__('Enter your Klick Tipp Password.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-klicktipp-list" name="ulp_klicktipp_list" value="'.esc_html($popup_options['klicktipp_list']).'" class="ulp-input-options" readonly="readonly" onfocus="ulp_klicktipp_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-klicktipp-list-id" name="ulp_klicktipp_list_id" value="'.esc_html($popup_options['klicktipp_list_id']).'" />
							<div id="ulp-klicktipp-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_klicktipp_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-klicktipp-lists", "ulp_username": jQuery("#ulp_klicktipp_username").val(), "ulp_password": jQuery("#ulp_klicktipp_password").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Tag ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-klicktipp-tag" name="ulp_klicktipp_tag" value="'.esc_html($popup_options['klicktipp_tag']).'" class="ulp-input-options" readonly="readonly" onfocus="ulp_klicktipp_tags_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-klicktipp-tag-id" name="ulp_klicktipp_tag_id" value="'.esc_html($popup_options['klicktipp_tag_id']).'" />
							<div id="ulp-klicktipp-tag-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your Tag ID.', 'ulp').'</em>
							<script>
								function ulp_klicktipp_tags_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-klicktipp-tags", "ulp_username": jQuery("#ulp_klicktipp_username").val(), "ulp_password": jQuery("#ulp_klicktipp_password").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-klicktipp-fields-html">';
		if (!empty($popup_options['klicktipp_username']) && !empty($popup_options['klicktipp_password'])) {
			$fields = $this->get_fields_html($popup_options['klicktipp_username'], $popup_options['klicktipp_password'], $popup_options['klicktipp_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_klicktipp_fields_button" class="ulp_button button-secondary" onclick="return ulp_klicktipp_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-klicktipp-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_klicktipp_loadfields() {
									jQuery("#ulp-klicktipp-fields-loading").fadeIn(350);
									jQuery(".ulp-klicktipp-fields-html").slideUp(350);
									var data = {action: "ulp-klicktipp-fields", ulp_username: jQuery("#ulp_klicktipp_username").val(), ulp_password: jQuery("#ulp_klicktipp_password").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-klicktipp-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-klicktipp-fields-html").html(data.html);
												jQuery(".ulp-klicktipp-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-klicktipp-fields-html").html("<div class=\'ulp-klicktipp-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Klick Tipp server.', 'ulp').'</strong></div>");
												jQuery(".ulp-klicktipp-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-klicktipp-fields-html").html("<div class=\'ulp-klicktipp-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Klick Tipp server.', 'ulp').'</strong></div>");
											jQuery(".ulp-klicktipp-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_klicktipp_enable"])) $popup_options['klicktipp_enable'] = "on";
		else $popup_options['klicktipp_enable'] = "off";
		if ($popup_options['klicktipp_enable'] == 'on') {
			if (empty($popup_options['klicktipp_username'])) $errors[] = __('Invalid Klick Tipp Username.', 'ulp');
			if (empty($popup_options['klicktipp_password'])) $errors[] = __('Invalid Klick Tipp Password.', 'ulp');
			if (empty($popup_options['klicktipp_list_id'])) $errors[] = __('Invalid Klick Tipp List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_klicktipp_enable"])) $popup_options['klicktipp_enable'] = "on";
		else $popup_options['klicktipp_enable'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_klicktipp_field_')) == 'ulp_klicktipp_field_') {
				$field = substr($key, strlen('ulp_klicktipp_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['klicktipp_fields'] = serialize($fields);
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['klicktipp_enable'] == 'on') {
			$data = array(
				'username' => $popup_options['klicktipp_username'],
				'password' => $popup_options['klicktipp_password']
			);
			$header = array(
				'Content-Type: application/x-www-form-urlencoded',
				'Accept: application/vnd.php.serialized'
			);
			try {
				$curl = curl_init('http://api.klick-tipp.com/account/login');
				curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
				$response = curl_exec($curl);
				curl_close($curl);
				$result = unserialize($response);
				if ($result && is_object($result) && isset($result->sessid) && isset($result->session_name)) {
					$session_name = $result->session_name;
					$session_id = $result->sessid;
					$data = array(
						'email' => $_subscriber['{subscription-email}']
					);
					if (!empty($popup_options['klicktipp_list_id'])) $data['listid'] = $popup_options['klicktipp_list_id'];
					if (!empty($popup_options['klicktipp_tag_id'])) $data['tagid'] = $popup_options['klicktipp_tag_id'];
					$fields = array();
					if (!empty($popup_options['klicktipp_fields'])) $fields = unserialize($popup_options['klicktipp_fields']);
					if (!empty($fields) && is_array($fields)) {
						foreach ($fields as $key => $value) {
							if (!empty($value)) {
								$data['fields'][$key] = strtr($value, $_subscriber);
							}
						}
					}
					$curl = curl_init('http://api.klick-tipp.com/subscriber');
					curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
					curl_setopt($curl, CURLOPT_COOKIE, $session_name.'='.$session_id);
					curl_setopt($curl, CURLOPT_POST, 1);
					curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					$response = curl_exec($curl);
					curl_close($curl);
				}
			} catch (Exception $e) {
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_username']) || !isset($_POST['ulp_password']) || empty($_POST['ulp_username']) || empty($_POST['ulp_password'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid username or password!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$username = trim(stripslashes($_POST['ulp_username']));
			$password = trim(stripslashes($_POST['ulp_password']));
			$data = array(
				'username' => $username,
				'password' => $password
			);
			$header = array(
				'Content-Type: application/x-www-form-urlencoded',
				'Accept: application/vnd.php.serialized'
			);
			$list_html = '';
			$lists = array();
			try {
				$curl = curl_init('http://api.klick-tipp.com/account/login');
				curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
				$response = curl_exec($curl);
				curl_close($curl);
				$result = unserialize($response);
				if ($result && is_object($result) && isset($result->sessid) && isset($result->session_name)) {
					$session_name = $result->session_name;
					$session_id = $result->sessid;
					$curl = curl_init('http://api.klick-tipp.com/list');
					curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
					curl_setopt($curl, CURLOPT_COOKIE, $session_name.'='.$session_id);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					$response = curl_exec($curl);
					curl_close($curl);
					$result = unserialize($response);
					if (is_array($result)) $lists = $result;
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid username or password!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} catch (Exception $e) {
			}
			if (!empty($lists)) {
				foreach ($lists as $key => $value) {
					$list_html .= '<a href="#" data-id="'.esc_html($key).'" data-title="'.esc_html($key).(!empty($value) ? ' | '.esc_html($value) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($key).(!empty($value) ? ' | '.esc_html($value) : '').'</a>';
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
	function show_tags() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_username']) || !isset($_POST['ulp_password']) || empty($_POST['ulp_username']) || empty($_POST['ulp_password'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid username or password!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$username = trim(stripslashes($_POST['ulp_username']));
			$password = trim(stripslashes($_POST['ulp_password']));
			$data = array(
				'username' => $username,
				'password' => $password
			);
			$header = array(
				'Content-Type: application/x-www-form-urlencoded',
				'Accept: application/vnd.php.serialized'
			);
			$tag_html = '';
			$tags = array();
			try {
				$curl = curl_init('http://api.klick-tipp.com/account/login');
				curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
				$response = curl_exec($curl);
				curl_close($curl);
				$result = unserialize($response);
				if ($result && is_object($result) && isset($result->sessid) && isset($result->session_name)) {
					$session_name = $result->session_name;
					$session_id = $result->sessid;
					$curl = curl_init('http://api.klick-tipp.com/tag');
					curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
					curl_setopt($curl, CURLOPT_COOKIE, $session_name.'='.$session_id);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					$response = curl_exec($curl);
					curl_close($curl);
					$result = unserialize($response);
					if (is_array($result)) $tags = $result;
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid username or password!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} catch (Exception $e) {
			}
			if (!empty($tags)) {
				foreach ($tags as $key => $value) {
					$tag_html .= '<a href="#" data-id="'.esc_html($key).'" data-title="'.esc_html($key).(!empty($value) ? ' | '.esc_html($value) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($key).(!empty($value) ? ' | '.esc_html($value) : '').'</a>';
				}
			} else $tag_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No data found!', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $tag_html;
			$return_object['items'] = sizeof($tags);
			echo json_encode($return_object);
		}
		exit;
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_username']) || !isset($_POST['ulp_password']) || empty($_POST['ulp_username']) || empty($_POST['ulp_password'])) exit;
			$username = trim(stripslashes($_POST['ulp_username']));
			$password = trim(stripslashes($_POST['ulp_password']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($username, $password, $this->default_popup_options['klicktipp_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_username, $_password, $_fields) {
		$result = $this->get_fields($_username, $_password);
		$fields = '';
		$values = unserialize($_fields);
		if (!is_array($values)) $values = array();
		if (!empty($result)) {
			$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Klick Tipp fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
					foreach ($result as $key => $field) {
						$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($key).':</strong></td>
					<td>
						<input type="text" id="ulp_klicktipp_field_'.esc_html($key).'" name="ulp_klicktipp_field_'.esc_html($key).'" value="'.esc_html(array_key_exists($key, $values) ? $values[$key] : '').'" class="widefat" />
						<br /><em>'.esc_html($field).'</em>
					</td>
				</tr>';
					}
					$fields .= '
			</table>';
		} else {
			$fields = '<div class="ulp-klicktipp-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function get_fields($_username, $_password) {
		$data = array(
			'username' => $_username,
			'password' => $_password,
		);
		$header = array(
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: application/vnd.php.serialized'
		);
		$fields = array();
		try {
			$curl = curl_init('http://api.klick-tipp.com/account/login');
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
			$response = curl_exec($curl);
			curl_close($curl);
			$result = unserialize($response);
			if ($result && is_object($result) && isset($result->sessid) && isset($result->session_name)) {
				$session_name = $result->session_name;
				$session_id = $result->sessid;
				$curl = curl_init('http://api.klick-tipp.com/field');
				curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
				curl_setopt($curl, CURLOPT_COOKIE, $session_name.'='.$session_id);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($curl);
				curl_close($curl);
				$result = unserialize($response);
				if (is_array($result)) $fields = $result;
			}
		} catch (Exception $e) {
		}
		return $fields;
	}
}
$ulp_klicktipp = new ulp_klicktipp_class();
?>