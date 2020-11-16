<?php
/* HTML Form integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_htmlform_class {
	var $default_popup_options = array(
		"htmlform_enable" => "off",
		"htmlform_html" => "",
		"htmlform_parsed" => "",
		"htmlform_clientside" => "off",
		"htmlform_target" => "iframe"
	);
	var $targets = array(
		'iframe' => 'Hidden iframe',
		'blank' => 'New browser tab',
		'top' => 'Same browser tab'
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-htmlform-connect', array(&$this, "connect"));
			add_action('wp_ajax_ulp-htmlform-disconnect', array(&$this, "disconnect"));
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
		}
		add_filter('ulp_subscribed_data', array(&$this, 'return_data'), 10, 3);
		add_action('ulp_subscribe', array(&$this, 'subscribe'), 10, 2);
	}
	function popup_options_tabs($_tabs) {
		if (!array_key_exists("integration", $_tabs)) $_tabs["integration"] = __('Integration', 'ulp');
		return $_tabs;
	}
	function popup_options_show($_popup_options) {
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
			<h3>'.__('HTML Form Parameters', 'ulp').'</h3>
			<table class="ulp_useroptions">
				<tr>
					<th>'.__('Enable HTML Form', 'ulp').':</th>
					<td>
						<input type="checkbox" id="ulp_htmlform_enable" name="ulp_htmlform_enable" '.($popup_options['htmlform_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Re-submit contact details to 3rd party HTML Form', 'ulp').'
						<br /><em>'.__('Please tick checkbox if you want to re-submit contact details to 3rd party HTML Form.', 'ulp').'</em>
					</td>
				</tr>
			</table>
			<div id="ulp-htmlform-form">
				'.$this->get_form_options($popup_options['htmlform_html'], $popup_options['htmlform_parsed']).'
			</div>
			<div id="ulp-htmlform-message" class="ulp-message"></div>
			<table class="ulp_useroptions">
				<tr>
					<th>'.__('Client side', 'ulp').':</th>
					<td>
						<input type="checkbox" id="ulp-htmlform-clientside" name="ulp_htmlform_clientside" '.($popup_options['htmlform_clientside'] == "on" ? 'checked="checked"' : '').'" onclick="ulp_htmlform_toggle_target();"> '.__('Submit form from client side', 'ulp').'
						<br /><em>'.__('Tick checkbox if you want form to be submitted from client side. Otherwise, it is submitted from server side.', 'ulp').'</em>
					</td>
				</tr>
				<tr id="ulp-htmlform-targets">
					<th>'.__('Form target', 'ulp').':</th>
					<td>
						<select name="ulp_htmlform_target" id="ulp-htmlform-target">';
		foreach ($this->targets as $key => $value) {
			echo '
							<option value="'.esc_html($key).'"'.($popup_options['htmlform_target'] == $key ? ' selected="selected"' : '').'>'.esc_html($value).'</option>';
		}
		echo '
						</select>
						<br /><em>'.__('Select where to display the response that is received after submitting the form.', 'ulp').'</em>
					</td>
				</tr>
			</table>
			
			<script>
				function ulp_htmlform_connect() {
					jQuery("#ulp-htmlform-connecting").fadeIn(350);
					var data = {action: "ulp-htmlform-connect", ulp_form: jQuery("#ulp_htmlform_html").val()};
					jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
						jQuery("#ulp-htmlform-connecting").fadeOut(350);
						try {
							var data = jQuery.parseJSON(return_data);
							var status = data.status;
							if (status == "OK") {
								jQuery("#ulp-htmlform-form").fadeOut(300, function() {
									jQuery("#ulp-htmlform-form").html(data.html);
									jQuery("#ulp-htmlform-form").fadeIn(300);
								});
							} else if (status == "ERROR") {
								jQuery("#ulp-htmlform-message").html(data.message);
								jQuery("#ulp-htmlform-message").slideDown(350);
							} else {
								jQuery("#ulp-htmlform-message").html("Service is not available.");
								jQuery("#ulp-htmlform-message").slideDown(350);
							}
						} catch(error) {
							jQuery("#ulp-htmlform-message").html("Service is not available.");
							jQuery("#ulp-htmlform-message").slideDown(350);
						}
					});
					return false;
				}
				function ulp_htmlform_disconnect() {
					jQuery("#ulp-htmlform-disconnecting").fadeIn(350);
					var data = {action: "ulp-htmlform-disconnect", ulp_form: jQuery("#ulp_htmlform_html").val()};
					jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
						jQuery("#ulp-htmlform-disconnecting").fadeOut(350);
						try {
							var data = jQuery.parseJSON(return_data);
							var status = data.status;
							if (status == "OK") {
								jQuery("#ulp-htmlform-form").fadeOut(300, function() {
									jQuery("#ulp-htmlform-form").html(data.html);
									jQuery("#ulp-htmlform-form").fadeIn(300);
								});
							} else if (status == "ERROR") {
								jQuery("#ulp-htmlform-message").html(data.message);
								jQuery("#ulp-htmlform-message").slideDown(350);
							} else {
								jQuery("#ulp-htmlform-message").html("Service is not available.");
								jQuery("#ulp-htmlform-message").slideDown(350);
							}
						} catch(error) {
							jQuery("#ulp-htmlform-message").html("Service is not available.");
							jQuery("#ulp-htmlform-message").slideDown(350);
						}
					});
					return false;
				}
				function ulp_htmlform_toggle_target() {
					if (jQuery("#ulp-htmlform-clientside").is(":checked")) {
						jQuery("#ulp-htmlform-targets").show();
					} else {
						jQuery("#ulp-htmlform-targets").hide();
					}
				}
				ulp_htmlform_toggle_target();
			</script>';
	}
	function popup_options_check($_errors) {
		global $ulp;
		$errors = array();
		if (isset($ulp->postdata["ulp_htmlform_enable"])) {
			if (empty($ulp->postdata["ulp_htmlform_action"])) $errors[] = __('Third party HTML-form is not connected.', 'ulp');
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
		if (isset($ulp->postdata["ulp_htmlform_enable"])) $popup_options['htmlform_enable'] = "on";
		else $popup_options['htmlform_enable'] = "off";
		if (isset($ulp->postdata["ulp_htmlform_clientside"])) $popup_options['htmlform_clientside'] = "on";
		else $popup_options['htmlform_clientside'] = "off";
		if (empty($ulp->postdata["ulp_htmlform_action"])) {
			$popup_options['htmlform_html'] = stripslashes(trim($ulp->postdata['ulp_htmlform_html']));
			$popup_options['htmlform_parsed'] = array();
		} else {
			$popup_options['htmlform_html'] = base64_decode(stripslashes(trim($ulp->postdata['ulp_htmlform_html'])));
			$popup_options['htmlform_parsed']['action'] = stripslashes(trim($ulp->postdata['ulp_htmlform_action']));
			$popup_options['htmlform_parsed']['method'] = stripslashes(trim($ulp->postdata['ulp_htmlform_method']));
			$fields = array();
			foreach ($ulp->postdata as $key => $value) {
				if (substr($key, 0, strlen('ulp_htmlform_field_')) == 'ulp_htmlform_field_') {
					$key = substr($key, strlen('ulp_htmlform_field_'));
					if (!empty($key)) {
						if (is_array($value)) {
							foreach($value as $subkey => $subvalue) {
								$fields[$key.'['.$subkey.']'] = array(
									'name' => stripslashes(trim($ulp->postdata['ulp_htmlform_fieldname_'.$key][$subkey])),
									'type' => stripslashes(trim($ulp->postdata['ulp_htmlform_fieldtype_'.$key][$subkey])),
									'value' => stripslashes(trim($subvalue))
								);
							}
						} else {
							$fields[$key] = array(
								'name' => stripslashes(trim($ulp->postdata['ulp_htmlform_fieldname_'.$key])),
								'type' => stripslashes(trim($ulp->postdata['ulp_htmlform_fieldtype_'.$key])),
								'value' => stripslashes(trim($value))
							);
						}
					}
				}
			}
			$popup_options['htmlform_parsed']['fields'] = $fields;
		}
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['htmlform_enable'] == 'on' && ($popup_options['htmlform_clientside'] == 'off' || $popup_options['doubleoptin_enable'] == 'on')) {
			$request = array();
			foreach ($popup_options['htmlform_parsed']['fields'] as $name => $field) {
				$request[$field['name']] = strtr($field['value'], $_subscriber);
			}
			$action = $popup_options['htmlform_parsed']['action'];
			if ($popup_options['htmlform_parsed']['method'] == 'get') {
				if (strpos($action, '?') === false) $action .= '?'.http_build_query($request);
				else $action .= '&'.http_build_query($request);
			}
			if (substr($action, 0, 2) == '//') $action = 'http:'.$action;
			try {
				$curl = curl_init($action);
				if ($popup_options['htmlform_parsed']['method'] != 'get') {
					curl_setopt($curl, CURLOPT_POST, 1);
					curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($request));
				}
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2049.0 Safari/537.36');
				if (strtolower(substr($action, 0, 8)) == 'https://') {
					curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				}
				$response = curl_exec($curl);
				//$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				//if ($httpCode == '301' || $httpCode == '302') {
				//	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
				//	$response = curl_exec($curl);
				//}
				curl_close($curl);
			} catch (Exception $e) {
			}
		}
	}
	
	function return_data($_return_data, $_popup_options, $_subscriber) {
		global $ulp;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		$form = '';
		if (is_array($_subscriber) && !empty($_subscriber)) {
			if ($popup_options['htmlform_enable'] == 'on' && $popup_options['htmlform_clientside'] == 'on' && $popup_options['doubleoptin_enable'] == 'off') {
				$id = 'f'.$ulp->random_string(16);
				if ($popup_options['htmlform_target'] == 'iframe') {
					$target = $id;
					$form = '<iframe style="display: none !important;" name="'.$id.'"></iframe>';
				} else if ($popup_options['htmlform_target'] == 'blank') $target = '_blank';
				else $target = '_top';
				$form .= '<form style="display: none !important;" method="'.$popup_options['htmlform_parsed']['method'].'" action="'.$popup_options['htmlform_parsed']['action'].'" target="'.$target.'">';
				foreach ($popup_options['htmlform_parsed']['fields'] as $name => $field) {
					$value = strtr($field['value'], $_subscriber);
					$form .= '<input type="hidden" name="'.esc_html($field['name']).'" value="'.esc_html($value).'">';
				}
				$form .= '<input id="submit-'.$id.'" type="submit" value="Submit"></form>';
				$_return_data['forms'][$id] = $form;
			}
		}
		return $_return_data;
	}
	
	function connect() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_form'])) {
				$return_data = array();
				$return_data['status'] = 'ERROR';
				$return_data['message'] = __('Hey dude, you have done an invalid request.', 'ulp');
				echo json_encode($return_data);
				exit;
			}
			$form_html = trim(stripslashes($_POST['ulp_form']));
			if (empty($form_html)) {
				$return_data = array();
				$return_data['status'] = 'ERROR';
				$return_data['message'] = __('Please copy-paste your HTML-form.', 'ulp');
				echo json_encode($return_data);
				exit;
			}
			
			if (function_exists('libxml_use_internal_errors')) libxml_use_internal_errors(true);
			
			$dom = new DOMDocument();
			$dom->loadHTML($form_html);
			if (!$dom) {
				$return_data = array();
				$return_data['status'] = 'ERROR';
				$return_data['message'] = __('Can not parse provided HTML-code.', 'ulp');
				echo json_encode($return_data);
				exit;
			}
			$xpath = new DOMXPath($dom);
			$dom_forms = $xpath->query('//form');
			if (!$dom_forms) {
				$return_data = array();
				$return_data['status'] = 'ERROR';
				$return_data['message'] = __('Can not parse provided HTML-code.', 'ulp');
				echo json_encode($return_data);
				exit;
			}
			if ($dom_forms->length == 0) {
				$return_data = array();
				$return_data['status'] = 'ERROR';
				$return_data['message'] = __('Can not find any form in provided HTML-code.', 'ulp');
				echo json_encode($return_data);
				exit;
			}
			if ($dom_forms->length > 1) {
				$return_data = array();
				$return_data['status'] = 'ERROR';
				$return_data['message'] = __('Too many forms found in provided HTML-code.', 'ulp');
				echo json_encode($return_data);
				exit;
			}
			$popup_options['htmlform_parsed']['action'] = $dom_forms->item(0)->getAttribute('action');
			if (empty($popup_options['htmlform_parsed']['action'])) {
				$return_data = array();
				$return_data['status'] = 'ERROR';
				$return_data['message'] = __('No <code>action</code> attribute found in provided HTML-form.', 'ulp');
				echo json_encode($return_data);
				exit;
			}
			if (substr($popup_options['htmlform_parsed']['action'], 0, 2) != '//' && substr(strtolower($popup_options['htmlform_parsed']['action']), 0, 7) != 'http://' && substr(strtolower($popup_options['htmlform_parsed']['action']), 0, 8) != 'https://') {
				$return_data = array();
				$return_data['status'] = 'ERROR';
				$return_data['message'] = __('Form <code>action</code> attribute must be a full URL.', 'ulp');
				echo json_encode($return_data);
				exit;
			}
			
			$popup_options['htmlform_parsed']['method'] = strtolower($dom_forms->item(0)->getAttribute('method'));
			if (empty($popup_options['htmlform_parsed']['method'])) $popup_options['htmlform_parsed']['method'] = 'get';
			
			$inputs = array();
			$dom_inputs = $xpath->query('//input', $dom_forms->item(0));
			foreach ($dom_inputs as $input) {
				$name = $input->getAttribute('name');
				if (!empty($name)) {
					if (!array_key_exists($name, $inputs)) {
						$type = $input->getAttribute('type');
						if (empty($type)) $type = 'text';
						$inputs[$name] = array(
							'name' => $name,
							'type' => $type,
							'value' => $input->getAttribute('value')
						);
					}
				}
			}
			$dom_inputs = $xpath->query('//textarea', $dom_forms->item(0));
			foreach ($dom_inputs as $input) {
				$name = $input->getAttribute('name');
				if (!empty($name)) {
					if (!array_key_exists($name, $inputs)) {
						$inputs[$name] = array(
							'name' => $name,
							'type' => 'textarea',
							'value' => $input->textContent
						);
					}
				}
			}
			$dom_inputs = $xpath->query('//select', $dom_forms->item(0));
			foreach ($dom_inputs as $input) {
				$name = $input->getAttribute('name');
				if (!empty($name)) {
					if (!array_key_exists($name, $inputs)) {
						$dom_options = $xpath->query('//option', $dom_inputs->item(0));
						if ($dom_options->length > 0) {
							$inputs[$name] = array(
								'name' => $name,
								'type' => 'select',
								'value' => $dom_options->item(0)->getAttribute('value')
							);
						}
					}
				}
			}
			if (function_exists('libxml_clear_errors')) libxml_clear_errors();
			$popup_options['htmlform_parsed']['fields'] = $inputs;
			
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_form_options($form_html, $popup_options['htmlform_parsed']);
			echo json_encode($return_object);
		}
		exit;
	}
	function disconnect() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (isset($_POST['ulp_form'])) {
				$form_html = base64_decode(trim(stripslashes($_POST['ulp_form'])));
				if (!$form_html) {
					$return_data = array();
					$return_data['status'] = 'ERROR';
					$return_data['message'] = __('Hey dude, you have done an invalid request.', 'ulp');
					echo json_encode($return_data);
					exit;
				}
			} else $form_html = '';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_form_options($form_html, array());
			echo json_encode($return_object);
		}
		exit;
	}
	function get_form_options($htmlform = '', $parsed = array()) {
		$html = '';
		if (empty($parsed)) {
			$html .= '
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('HTML Form', 'ulp').':</th>
						<td>
							<textarea id="ulp_htmlform_html" name="ulp_htmlform_html" class="widefat" style="height: 120px;">'.esc_html($htmlform).'</textarea>
							<input type="hidden" name="ulp_htmlform_action" value="">
							<a class="ulp_button button-secondary" onclick="return ulp_htmlform_connect();">'.__('Connect Form', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-htmlform-connecting" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Copy-paste your 3rd party HTML form and connect it.', 'ulp').'</em><br />
						</td>
					</tr>
				</table>';
		} else {
			$html .= '
				<table class="ulp_useroptions">
					<tr>
						<td colspan="2">'.__('Please adjust the form fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate the form fields with the popup fields.', 'ulp').'</td>
					</tr>
					<tr>
						<th>'.__('Action URL', 'ulp').':</th>
						<td>
							<strong>'.esc_html($parsed['action']).'</strong>
							<input type="hidden" name="ulp_htmlform_action" value="'.esc_html($parsed['action']).'">
							<input type="hidden" id="ulp_htmlform_html" name="ulp_htmlform_html" value="'.base64_encode($htmlform).'">
							<br /><em>'.__('Specifies where to send the form-data when a form is submitted.', 'ulp').'</em><br />
						</td>
					</tr>
					<tr>
					<th>'.__('Method', 'ulp').':</th>
						<td>
							<strong>'.strtoupper(esc_html($parsed['method'])).'</strong>
							<input type="hidden" name="ulp_htmlform_method" value="'.esc_html($parsed['method']).'">
							<br /><em>'.__('Specifies how to send form-data.', 'ulp').'</em><br />
						</td>
					</tr>';
			if (is_array($parsed['fields'])) {
				foreach($parsed['fields'] as $name => $field) {
					$html .= '
					<tr>
						<th>'.(!empty($field['name']) ? esc_html($field['name']) : esc_html($name)).':</th>
						<td>';
					switch ($field['type']) {
						case 'textarea':
							$html .= '
							<textarea id="ulp_htmlform_field_'.esc_html($name).'" name="ulp_htmlform_field_'.esc_html($name).'" class="widefat" style="height: 120px;">'.esc_html($field['value']).'</textarea>';
							break;
						default:
							$html .= '
							<input type="text" id="ulp_htmlform_field_'.esc_html($name).'" name="ulp_htmlform_field_'.esc_html($name).'" value="'.esc_html($field['value']).'" class="widefat">';
							break;
							break;
					}
					$html .= '
						<input type="hidden" id="ulp_htmlform_fieldname_'.esc_html($name).'" name="ulp_htmlform_fieldname_'.esc_html($name).'" value="'.(!empty($field['name']) ? esc_html($field['name']) : esc_html($name)).'">
						<input type="hidden" id="ulp_htmlform_fieldtype_'.esc_html($name).'" name="ulp_htmlform_fieldtype_'.esc_html($name).'" value="'.esc_html($field['type']).'">
						<br /><em>'.ucfirst($field['type']).' input field.</em>
						</td>
					</tr>';
				}
			}		
			$html .= '
					<tr>
						<th></th>
						<td>
							<a class="ulp_button button-secondary" onclick="return ulp_htmlform_disconnect();">'.__('Disconnect Form', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-htmlform-disconnecting" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to disconnect your current HTML form.', 'ulp').'</em><br />
						</td>
					</tr>
				</table>';
		}
		return $html;
	}
}
$ulp_htmlform = new ulp_htmlform_class();
?>