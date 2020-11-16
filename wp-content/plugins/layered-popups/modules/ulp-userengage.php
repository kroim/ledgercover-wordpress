<?php
/* UserEngage integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_userengage_class {
	var $default_popup_options = array(
		"userengage_enable" => "off",
		"userengage_api_key" => "",
		"userengage_email" => "{subscription-email}",
		"userengage_first_name" => "{subscription-name}",
		"userengage_last_name" => "",
		"userengage_list" => "",
		"userengage_list_id" => "",
		"userengage_tags" => array(),
		"userengage_attributes" => ""
	);
	function __construct() {
		$this->default_popup_options['userengage_attributes'] = json_encode(array());
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-userengage-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-userengage-tags', array(&$this, "show_tags"));
			add_action('wp_ajax_ulp-userengage-attributes', array(&$this, "show_attributes"));
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
				<h3>'.__('UserEngage Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable UserEngage', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_userengage_enable" name="ulp_userengage_enable" '.($popup_options['userengage_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to UserEngage', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to UserEngage.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key/Token', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_userengage_api_key" name="ulp_userengage_api_key" value="'.esc_html($popup_options['userengage_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your UserEngage API Key. You can get it <a href="https://app.userengage.io/api-credentials/" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-userengage-list" name="ulp_userengage_list" value="'.esc_html($popup_options['userengage_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_userengage_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-userengage-list-id" name="ulp_userengage_list_id" value="'.esc_html($popup_options['userengage_list_id']).'" />
							<div id="ulp-userengage-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_userengage_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-userengage-lists", "ulp_api_key": jQuery("#ulp_userengage_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-userengage-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate UserEngage fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">
									<tr>
										<td style="width: 100px;"><strong>'.__('E-mail', 'ulp').':</strong></td>
										<td>
											<input type="text" id="ulp_userengage_email" name="ulp_userengage_email" value="'.esc_html($popup_options['userengage_email']).'" readonly="readonly" class="widefat" />
											<br /><em>'.__('Contact\'s e-mail address.', 'ulp').'</em>
										</td>
									</tr>
									<tr>
										<td><strong>'.__('First Name', 'ulp').':</strong></td>
										<td>
											<input type="text" id="ulp_userengage_first_name" name="ulp_userengage_first_name" value="'.esc_html($popup_options['userengage_first_name']).'" class="widefat" />
											<br /><em>'.__('Contact\'s first name.', 'ulp').'</em>
										</td>
									</tr>
									<tr>
										<td><strong>'.__('Last Name', 'ulp').':</strong></td>
										<td>
											<input type="text" id="ulp_userengage_last_name" name="ulp_userengage_last_name" value="'.esc_html($popup_options['userengage_last_name']).'" class="widefat" />
											<br /><em>'.__('Contact\'s last name.', 'ulp').'</em>
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
					<tr>
						<th>'.__('Attributes', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-userengage-attributes-html">';
		if (!empty($popup_options['userengage_api_key'])) {
			$attributes = $this->get_attributes_html($popup_options['userengage_api_key'], $popup_options['userengage_attributes']);
			echo $attributes;
		}
		echo '
							</div>
							<a id="ulp_userengage_attributes_button" class="ulp_button button-secondary" onclick="return ulp_userengage_loadattributes();">'.__('Load Attributes', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-userengage-attributes-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load attributes list. Ignore if you do not need specify attributes values.', 'ulp').'</em>
							<script>
								function ulp_userengage_loadattributes() {
									jQuery("#ulp-userengage-attributes-loading").fadeIn(350);
									jQuery(".ulp-userengage-attributes-html").slideUp(350);
									var data = {action: "ulp-userengage-attributes", ulp_key: jQuery("#ulp_userengage_api_key").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-userengage-attributes-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-userengage-attributes-html").html(data.html);
												jQuery(".ulp-userengage-attributes-html").slideDown(350);
											} else {
												jQuery(".ulp-userengage-attributes-html").html("<div class=\'ulp-userengage-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to UserEngage server.', 'ulp').'</strong></div>");
												jQuery(".ulp-userengage-attributes-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-userengage-attributes-html").html("<div class=\'ulp-userengage-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to UserEngage server.', 'ulp').'</strong></div>");
											jQuery(".ulp-userengage-attributes-html").slideDown(350);
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Tags', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_userengage_tags" name="ulp_userengage_tags" value="'.esc_html(implode(', ', $popup_options['userengage_tags'])).'" class="widefat">
							<br /><em>'.__('Comma-separated list of tags, associated with contact.', 'ulp').'</em>
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
		if (isset($ulp->postdata["ulp_userengage_enable"])) $popup_options['userengage_enable'] = "on";
		else $popup_options['userengage_enable'] = "off";
		if ($popup_options['userengage_enable'] == 'on') {
			if (empty($popup_options['userengage_api_key'])) $errors[] = __('Invalid UserEngage API Key/Token.', 'ulp');
			//if (empty($popup_options['userengage_list_id'])) $errors[] = __('Invalid UserEngage List ID.', 'ulp');
		}
		return array_merge($_errors, $errors);
	}
	function popup_options_populate($_popup_options) {
		global $ulp;
		$popup_options = array();
		foreach ($this->default_popup_options as $key => $value) {
			if (isset($ulp->postdata['ulp_'.$key]) && $key != 'userengage_tags') {
				$popup_options[$key] = stripslashes(trim($ulp->postdata['ulp_'.$key]));
			}
		}
		if (isset($ulp->postdata["ulp_userengage_enable"])) $popup_options['userengage_enable'] = "on";
		else $popup_options['userengage_enable'] = "off";
		
		$tags = explode(',', $ulp->postdata["ulp_userengage_tags"]);
		$popup_options['userengage_tags'] = array();
		foreach($tags as $tag) {
			$tag = trim($tag);
			if (!empty($tag)) $popup_options['userengage_tags'][] = $tag;
		}
		$popup_options['userengage_tags'] = array_unique($popup_options['userengage_tags']);

		$attributes = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_userengage_attribute_')) == 'ulp_userengage_attribute_') {
				$attribute = substr($key, strlen('ulp_userengage_attribute_'));
				$attributes[$attribute] = stripslashes(trim($value));
			}
		}
		$popup_options['userengage_attributes'] = json_encode($attributes);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['userengage_enable'] == 'on') {
			$data = array(
				'last_ip' => $_SERVER['REMOTE_ADDR'],
				'email' => $_subscriber['{subscription-email}'],
				'first_name' => strtr($popup_options['userengage_first_name'], $_subscriber),
				'last_name' => strtr($popup_options['userengage_last_name'], $_subscriber),
			);
			
			$result = $this->connect($popup_options['userengage_api_key'], 'users/search/?email='.rawurlencode($_subscriber['{subscription-email}']));
			
			if (is_array($result) && array_key_exists('id', $result)) {
				$contact_id = $result['id'];
				$data = array(
					'last_ip' => $_SERVER['REMOTE_ADDR'],
					'first_name' => strtr($popup_options['userengage_first_name'], $_subscriber),
					'last_name' => strtr($popup_options['userengage_last_name'], $_subscriber),
				);
				$result = $this->connect($popup_options['userengage_api_key'], 'users/'.$contact_id.'/', $data, 'PUT', true);
			} else {
				$result = $this->connect($popup_options['userengage_api_key'], 'users/', $data);
				if (is_array($result) && array_key_exists('id', $result)) $contact_id = $result['id'];
				else $contact_id = null;
			}
			
			if ($contact_id) {
				foreach ($popup_options['userengage_tags'] as $tag) {
					$result = $this->connect($popup_options['userengage_api_key'], 'users/'.$contact_id.'/add_tag/', array('name' => $tag));
				}
				$attributes = json_decode($popup_options['userengage_attributes'], true);
				if (is_array($attributes) && !empty($attributes)) {
					foreach ($attributes as $key => $value) {
						if (!empty($value)) {
							$result = $this->connect($popup_options['userengage_api_key'], 'users/'.$contact_id.'/set_attribute/', array('attribute' => $key, 'value' => strtr($value, $_subscriber)));
						}
					}
				}
				if (!empty($popup_options['userengage_list_id'])) {
					$result = $this->connect($popup_options['userengage_api_key'], 'users/'.$contact_id.'/add_to_list/', array('list' => $popup_options['userengage_list_id']));
				}
			}
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
			
			$result = $this->connect($key, 'lists/');
			
			if (is_array($result) && array_key_exists('count', $result)) {
				if (intval($result['count']) > 0) {
					foreach ($result['results'] as $list) {
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
	function show_attributes() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_key']) || empty($_POST['ulp_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-userengage-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Key!', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_key']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_attributes_html($key, $this->default_popup_options['userengage_attributes']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_attributes_html($_key, $_attributes) {
		$result = $this->connect($_key, 'attributes/');
		$attributes = '';
		$values = json_decode($_attributes, true);
		if (!is_array($values)) $values = array();
		if (!empty($result) && is_array($result)) {
			if (array_key_exists('count', $result) && $result['count'] > 0) {
				$attributes = '
			'.__('Please adjust the attributes below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate UserEngage attributes with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
				foreach ($result['results'] as $field) {
					if (is_array($field)) {
						if (array_key_exists('name', $field) && array_key_exists('name_std', $field)) {
							$attributes .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['name']).':</strong></td>
					<td>
						<input type="text" id="ulp_userengage_attribute_'.esc_html($field['name_std']).'" name="ulp_userengage_attribute_'.esc_html($field['name_std']).'" value="'.esc_html(array_key_exists($field['name_std'], $values) ? $values[$field['name_std']] : '').'" class="widefat" />
						<br /><em>'.esc_html($field['name_std']).'</em>
					</td>
				</tr>';
						}
					}
				}
				$attributes .= '
			</table>';
			} else {
				$attributes = '<div class="ulp-userengage-grouping" style="margin-bottom: 10px;"><strong>'.__('No attributes found.', 'ulp').'</strong></div>';
			}
		} else {
			$attributes = '<div class="ulp-userengage-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $attributes;
	}
	function connect($_api_key, $_path, $_data = array(), $_method = '', $_json = false) {
		$result = null;
		$headers = array(
			'Authorization: Token '.$_api_key,
			'Accept: application/json'
		);
		if ($_json) $headers[] = 'Content-Type: application/json';
		else $headers[] = 'Content-Type: application/x-www-form-urlencoded';
		try {
			$url = 'https://app.userengage.io/api/public/'.ltrim($_path, '/');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				if ($_json) curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
				else curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 120);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			
//print_r($curl);
			$response = curl_exec($curl);
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			if ($http_code == 404) $result = array();
			else $result = json_decode($response, true);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
}
$ulp_userengage = new ulp_userengage_class();
?>