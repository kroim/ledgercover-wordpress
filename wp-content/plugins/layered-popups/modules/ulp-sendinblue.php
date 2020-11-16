<?php
/* SendinBlue integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_sendinblue_class {
	var $default_popup_options = array(
		"sendinblue_enable" => "off",
		"sendinblue_api_key" => "",
		"sendinblue_list" => "",
		"sendinblue_list_id" => "",
		"sendinblue_attributes" => ""
	);
	function __construct() {
		$this->default_popup_options['sendinblue_attributes'] = serialize(array('NAME' => '{subscription-name}'));
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-sendinblue-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-sendinblue-attributes', array(&$this, "show_attributes"));
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
				<h3>'.__('SendinBlue Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable SendinBlue', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_sendinblue_enable" name="ulp_sendinblue_enable" '.($popup_options['sendinblue_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to SendinBlue', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to SendinBlue.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('SendinBlue API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_sendinblue_api_key" name="ulp_sendinblue_api_key" value="'.esc_html($popup_options['sendinblue_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your SendinBlue API Key (version 2.0). You can get it <a href="https://my.sendinblue.com/advanced/apikey" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID:', 'ulp').'</th>
						<td>
							<input type="text" id="ulp-sendinblue-list" name="ulp_sendinblue_list" value="'.esc_html($popup_options['sendinblue_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_sendinblue_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-sendinblue-list-id" name="ulp_sendinblue_list_id" value="'.esc_html($popup_options['sendinblue_list_id']).'" />
							<div id="ulp-sendinblue-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_sendinblue_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-sendinblue-lists", "ulp_api_key": jQuery("#ulp_sendinblue_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Attributes', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-sendinblue-attributes-html">';
		if (!empty($popup_options['sendinblue_api_key'])) {
			$attributes = $this->get_attributes_html($popup_options['sendinblue_api_key'], $popup_options['sendinblue_attributes']);
			echo $attributes;
		}
		echo '
							</div>
							<a id="ulp_sendinblue_attributes_button" class="ulp_button button-secondary" onclick="return ulp_sendinblue_loadattributes();">'.__('Load Attributes', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-sendinblue-attributes-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load attributes list. Ignore if you do not need specify attributes values.', 'ulp').'</em>
							<script>
								function ulp_sendinblue_loadattributes() {
									jQuery("#ulp-sendinblue-attributes-loading").fadeIn(350);
									jQuery(".ulp-sendinblue-attributes-html").slideUp(350);
									var data = {action: "ulp-sendinblue-attributes", ulp_key: jQuery("#ulp_sendinblue_api_key").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-sendinblue-attributes-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-sendinblue-attributes-html").html(data.html);
												jQuery(".ulp-sendinblue-attributes-html").slideDown(350);
											} else {
												jQuery(".ulp-sendinblue-attributes-html").html("<div class=\'ulp-sendinblue-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to SendinBlue server.', 'ulp').'</strong></div>");
												jQuery(".ulp-sendinblue-attributes-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-sendinblue-attributes-html").html("<div class=\'ulp-sendinblue-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to SendinBlue server.', 'ulp').'</strong></div>");
											jQuery(".ulp-sendinblue-attributes-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_sendinblue_enable"])) $popup_options['sendinblue_enable'] = "on";
		else $popup_options['sendinblue_enable'] = "off";
		if ($popup_options['sendinblue_enable'] == 'on') {
			if (empty($popup_options['sendinblue_api_key'])) $errors[] = __('Invalid SendinBlue API Key.', 'ulp');
			if (empty($popup_options['sendinblue_list_id'])) $errors[] = __('Invalid SendinBlue List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_sendinblue_enable"])) $popup_options['sendinblue_enable'] = "on";
		else $popup_options['sendinblue_enable'] = "off";
		
		$attributes = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_sendinblue_field_')) == 'ulp_sendinblue_field_') {
				$field = substr($key, strlen('ulp_sendinblue_field_'));
				$attributes[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['sendinblue_attributes'] = serialize($attributes);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['sendinblue_enable'] == 'on') {
			$headers = array(
				'api-key: '.$popup_options['sendinblue_api_key'],
				'Content-Type: application/json'
			);
			$data = array(
				'listid' => array($popup_options['sendinblue_list_id']),
				'email' => $_subscriber['{subscription-email}'],
				'blacklisted' => 0
			);
			$attributes = array();
			if (!empty($popup_options['sendinblue_attributes'])) $attributes = unserialize($popup_options['sendinblue_attributes']);
			if (!empty($attributes) && is_array($attributes)) {
				foreach ($attributes as $key => $value) {
					if (!empty($value)) {
						$data['attributes'][$key] = strtr($value, $_subscriber);
					}
				}
			}

			try {
				$curl = curl_init('https://api.sendinblue.com/v2.0/user/createdituser');
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				$response = curl_exec($curl);
				curl_close($curl);
			} catch (Exception $e) {
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_api_key']) || empty($_POST['ulp_api_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_api_key']));
			$headers = array(
				'api-key: '.$key,
				'Content-Type: application/json'
			);
			$data = array(
				'page' => 1,
				'page_limit' => 50
			);
			$lists = array();
			$watchdog = 0;
			try {
				do {
					$curl = curl_init('https://api.sendinblue.com/v2.0/list');
					curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
					curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
					curl_setopt($curl, CURLOPT_TIMEOUT, 10);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
					curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
					curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
					curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

					$response = curl_exec($curl);
					curl_close($curl);
					$result = json_decode($response, true);
					if ($result && array_key_exists('code', $result) && $result['code'] == 'success') {
						foreach ($result['data']['lists'] as $list) {
							if (is_array($list)) {
								if (array_key_exists('id', $list) && array_key_exists('name', $list)) {
									$lists[$list['id']] = $list['name'];
								}
							}
						}
						$total_list_records = intval($result['data']['total_list_records']);
						$data['page'] = intval($result['data']['page'])+1;
						$data['page_limit'] = intval($result['data']['page_limit']);
					} else {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Key!', 'ulp').'</div>';
						echo json_encode($return_object);
						exit;
					}
					$watchdog++;
				} while (($data['page']-1)*$data['page_limit'] < $total_list_records && $watchdog < 20);
			} catch (Exception $e) {
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
	function show_attributes() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_key']) || empty($_POST['ulp_key'])) exit;
			$key = trim(stripslashes($_POST['ulp_key']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_attributes_html($key, $this->default_popup_options['sendinblue_attributes']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_attributes_html($_key, $_attributes) {
		$result = array();
		$headers = array(
			'api-key: '.$_key,
			'Content-Type: application/json'
		);
		try {
			$curl = curl_init('https://api.sendinblue.com/v2.0/attribute');
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			$response = curl_exec($curl);
			curl_close($curl);
			$result = json_decode($response, true);
		} catch (Exception $e) {
		}
		$attributes = '';
		$values = unserialize($_attributes);
		if (!is_array($values)) $values = array();
		if (!empty($result)) {
			if (array_key_exists('code', $result) && $result['code'] != 'success') {
				$attributes = '<div class="ulp-sendinblue-grouping" style="margin-bottom: 10px;"><strong>'.$result['message'].'</strong></div>';
			} else {
				if (array_key_exists('normal_attributes', $result['data']) && !empty($result['data']['normal_attributes'])) {
					$attributes = '
			'.__('Please adjust the attributes below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate SendinBlue attributes with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
					foreach ($result['data']['normal_attributes'] as $field) {
						if (is_array($field)) {
							if (array_key_exists('name', $field) && array_key_exists('type', $field)) {
								$attributes .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['name']).':</strong></td>
					<td>
						<input type="text" id="ulp_sendinblue_field_'.esc_html($field['name']).'" name="ulp_sendinblue_field_'.esc_html($field['name']).'" value="'.esc_html(array_key_exists($field['name'], $values) ? $values[$field['name']] : '').'" class="widefat" />
						<br /><em>'.esc_html($field['name'].', '.$field['type']).'</em>
					</td>
				</tr>';
							}
						}
					}
					$attributes .= '
			</table>';
				} else {
					$attributes = '<div class="ulp-sendinblue-grouping" style="margin-bottom: 10px;"><strong>'.__('No attributes found.', 'ulp').'</strong></div>';
				}
			}
		} else {
			$attributes = '<div class="ulp-sendinblue-grouping" style="margin-bottom: 10px;"><strong>'.__('No attributes found.', 'ulp').'</strong></div>';
		}
		return $attributes;
	}
}
$ulp_sendinblue = new ulp_sendinblue_class();
?>