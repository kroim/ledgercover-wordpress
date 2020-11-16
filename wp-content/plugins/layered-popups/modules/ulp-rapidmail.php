<?php
/* Rapidmail integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_rapidmail_class {
	var $default_popup_options = array(
		"rapidmail_enable" => "off",
		"rapidmail_api_username" => "",
		"rapidmail_api_password" => "",
		"rapidmail_list" => "",
		"rapidmail_list_id" => "",
		"rapidmail_fields" => array(
			"email" => "{subscription-email}",
			"firstname" => "{subscription-name}",
			"lastname" => "",
			"gender" => "",
			"title" => "",
			"zip" => "",
			"birthdate" => "",
			"extra1" => "",
			"extra2" => "",
			"extra3" => "",
			"extra4" => "",
			"extra5" => "",
			"extra6" => "",
			"extra7" => "",
			"extra8" => "",
			"extra9" => "",
			"extra10" => ""
		)
	);
	var $fields_meta;
	function __construct() {
		$this->fields_meta = array(
			'email' => __('Email address', 'ulp'),
			'firstname' => __('First name', 'ulp'),
			'lastname' => __('Last name', 'ulp'),
			'gender' => __('Gender', 'ulp'),
			'title' => __('Title', 'ulp'),
			'zip' => __('ZIP Code', 'ulp'),
			'birthdate' => __('Birtdate', 'ulp'),
			'extra1' => __('Extra 1', 'ulp'),
			'extra2' => __('Extra 2', 'ulp'),
			'extra3' => __('Extra 3', 'ulp'),
			'extra4' => __('Extra 4', 'ulp'),
			'extra5' => __('Extra 5', 'ulp'),
			'extra6' => __('Extra 6', 'ulp'),
			'extra7' => __('Extra 4', 'ulp'),
			'extra8' => __('Extra 8', 'ulp'),
			'extra9' => __('Extra 9', 'ulp'),
			'extra10' => __('Extra 10', 'ulp')
		);
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-rapidmail-lists', array(&$this, "show_lists"));
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
				<h3>'.__('Rapidmail Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Rapidmail', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_rapidmail_enable" name="ulp_rapidmail_enable" '.($popup_options['rapidmail_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Rapidmail', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Rapidmail.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Username', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_rapidmail_api_username" name="ulp_rapidmail_api_username" value="'.esc_html($popup_options['rapidmail_api_username']).'" class="widefat">
							<br /><em>'.__('Enter your Rapidmail API Username. You can get it <a href="https://my.rapidmail.de/api/v3/userlist.html" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Password', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_rapidmail_api_password" name="ulp_rapidmail_api_password" value="'.esc_html($popup_options['rapidmail_api_password']).'" class="widefat">
							<br /><em>'.__('Enter your Rapidmail API Password. You can get it <a href="https://my.rapidmail.de/api/v3/userlist.html" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-rapidmail-list" name="ulp_rapidmail_list" value="'.esc_html($popup_options['rapidmail_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_rapidmail_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-rapidmail-list-id" name="ulp_rapidmail_list_id" value="'.esc_html($popup_options['rapidmail_list_id']).'" />
							<div id="ulp-rapidmail-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_rapidmail_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-rapidmail-lists", "ulp_api_username": jQuery("#ulp_rapidmail_api_username").val(), "ulp_api_password": jQuery("#ulp_rapidmail_api_password").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-rapidmail-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Rapidmail fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
		foreach ($this->default_popup_options['rapidmail_fields'] as $key => $value) {
			echo '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($this->fields_meta[$key]).':</strong></td>
					<td>
						<input type="text" id="ulp_rapidmail_field_'.esc_html($key).'" name="ulp_rapidmail_field_'.esc_html($key).'" value="'.esc_html(array_key_exists($key, $popup_options['rapidmail_fields']) ? $popup_options['rapidmail_fields'][$key] : $value).'" class="widefat"'.($key == 'email' ? ' readonly="readonly"' : '').' />
						<br /><em>'.esc_html($this->fields_meta[$key].' ('.$key.')').'</em>
					</td>
				</tr>';
		}
		echo '
								</table>
							</div>
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
		if (isset($ulp->postdata["ulp_rapidmail_enable"])) $popup_options['rapidmail_enable'] = "on";
		else $popup_options['rapidmail_enable'] = "off";
		if ($popup_options['rapidmail_enable'] == 'on') {
			if (empty($popup_options['rapidmail_api_username'])) $errors[] = __('Invalid Rapidmail API Username.', 'ulp');
			if (empty($popup_options['rapidmail_api_password'])) $errors[] = __('Invalid Rapidmail API Password.', 'ulp');
			if (empty($popup_options['rapidmail_list_id'])) $errors[] = __('Invalid Rapidmail List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_rapidmail_enable"])) $popup_options['rapidmail_enable'] = "on";
		else $popup_options['rapidmail_enable'] = "off";
		
		$popup_options['rapidmail_fields'] = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_rapidmail_field_')) == 'ulp_rapidmail_field_') {
				$key = substr($key, strlen('ulp_rapidmail_field_'));
				$popup_options['rapidmail_fields'][$key] = stripslashes(trim($value));
			}
		}
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['rapidmail_enable'] == 'on') {
			$post_data = array(
				'created_ip' => $_SERVER['REMOTE_ADDR'],
				'status' => 'active',
				'email' => $_subscriber['{subscription-email}']
			);
			foreach($popup_options['rapidmail_fields'] as $key => $value) {
				if (!empty($value)) $post_data[$key] = strtr($value, $_subscriber);
			}
			$result = $this->connect($popup_options['rapidmail_api_username'], $popup_options['rapidmail_api_password'], 'recipients?recipientlist_id='.urlencode($popup_options['rapidmail_list_id']).'&email='.urlencode($_subscriber['{subscription-email}']));
			if (array_key_exists('_embedded', $result) && array_key_exists('recipients', $result['_embedded']) && sizeof($result['_embedded']['recipients']) > 0) {
				$result = $this->connect($popup_options['rapidmail_api_username'], $popup_options['rapidmail_api_password'], 'recipients/'.urlencode($result['_embedded']['recipients'][0]['id']), $post_data, 'PATCH');
			} else {
				$post_data['recipientlist_id'] = $popup_options['rapidmail_list_id'];
				$result = $this->connect($popup_options['rapidmail_api_username'], $popup_options['rapidmail_api_password'], 'recipients', $post_data);
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_api_username']) || empty($_POST['ulp_api_username']) || !isset($_POST['ulp_api_password']) || empty($_POST['ulp_api_password'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Credentials!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$username = trim(stripslashes($_POST['ulp_api_username']));
			$password = trim(stripslashes($_POST['ulp_api_password']));
			$do = true;
			$page = 1;
			do {
				$result = $this->connect($username, $password, 'recipientlists?page='.$page);
				if (is_array($result)) {
					if (array_key_exists('_embedded', $result)) {
						if (array_key_exists('recipientlists', $result['_embedded']) && sizeof($result['_embedded']['recipientlists']) > 0) {
							foreach ($result['_embedded']['recipientlists'] as $list) {
								if (is_array($list)) {
									if (array_key_exists('id', $list) && array_key_exists('name', $list)) {
										$lists[$list['id']] = $list['name'];
									}
								}
							}
							if ($result['page'] >= $result['page_count']) $do = false;
							$page++;
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
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response.', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} while ($do);
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
	function connect($_api_username, $_api_password, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		try {
			$url = 'https://apiv3.emailsys.net/v1/'.ltrim($_path, '/');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_USERPWD, $_api_username.':'.$_api_password);
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
$ulp_rapidmail = new ulp_rapidmail_class();
?>