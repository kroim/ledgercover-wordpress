<?php
/* StreamSend integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_streamsend_class {
	var $default_popup_options = array(
		'streamsend_enable' => 'off',
		'streamsend_login_id' => '',
		'streamsend_key' => '',
		'streamsend_audience' => '',
		'streamsend_audience_id' => '',
		'streamsend_list' => '',
		'streamsend_list_id' => '',
		'streamsend_fields' => array()
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-streamsend-audiences', array(&$this, "show_audiences"));
			add_action('wp_ajax_ulp-streamsend-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-streamsend-fields', array(&$this, "show_fields"));
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
		}
		add_action('ulp_subscribe', array(&$this, 'subscribe'), 10, 2);
	}
	function popup_options_tabs($_tabs) {
		if (!array_key_exists("integration", $_tabs)) $_tabs["integration"] = __('Integration', 'ulp');
		return $_tabs;
	}
	function popup_options_show($_popup_options) {
		global $ulp;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('StreamSend Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable StreamSend', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_streamsend_enable" name="ulp_streamsend_enable" '.($popup_options['streamsend_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to StreamSend', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to StreamSend.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Login ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_streamsend_login_id" name="ulp_streamsend_login_id" value="'.esc_html($popup_options['streamsend_login_id']).'" class="widefat">
							<br /><em>'.__('Enter your StreamSend API Login ID. You can find it <a href="https://app.streamsend.com/account/settings" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_streamsend_key" name="ulp_streamsend_key" value="'.esc_html($popup_options['streamsend_key']).'" class="widefat">
							<br /><em>'.__('Enter your StreamSend API Key. You can find it <a href="https://app.streamsend.com/account/settings" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Audience', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-streamsend-audience" name="ulp_streamsend_audience" value="'.esc_html($popup_options['streamsend_audience']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_streamsend_audiences_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-streamsend-audience-id" name="ulp_streamsend_audience_id" value="'.esc_html($popup_options['streamsend_audience_id']).'" />
							<div id="ulp-streamsend-audience-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your Audience ID.', 'ulp').'</em>
							<script>
								function ulp_streamsend_audiences_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-streamsend-audiences", "ulp_login_id": jQuery("#ulp_streamsend_login_id").val(), "ulp_key": jQuery("#ulp_streamsend_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('List', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-streamsend-list" name="ulp_streamsend_list" value="'.esc_html($popup_options['streamsend_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_streamsend_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-streamsend-list-id" name="ulp_streamsend_list_id" value="'.esc_html($popup_options['streamsend_list_id']).'" />
							<div id="ulp-streamsend-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_streamsend_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-streamsend-lists", "ulp_login_id": jQuery("#ulp_streamsend_login_id").val(), "ulp_key": jQuery("#ulp_streamsend_key").val(), "ulp_audience": jQuery("#ulp-streamsend-audience-id").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-streamsend-fields-html">';
		if (!empty($popup_options['streamsend_login_id']) &&!empty($popup_options['streamsend_key']) && !empty($popup_options['streamsend_audience_id'])) {
			$fields = $this->get_fields_html($popup_options['streamsend_login_id'], $popup_options['streamsend_key'], $popup_options['streamsend_audience_id'], $popup_options['streamsend_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_streamsend_fields_button" class="ulp_button button-secondary" onclick="return ulp_streamsend_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-streamsend-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_streamsend_loadfields() {
									jQuery("#ulp-streamsend-fields-loading").fadeIn(350);
									jQuery(".ulp-streamsend-fields-html").slideUp(350);
									var data = {action: "ulp-streamsend-fields", ulp_login_id: jQuery("#ulp_streamsend_login_id").val(), ulp_key: jQuery("#ulp_streamsend_key").val(), ulp_audience: jQuery("#ulp-streamsend-audience-id").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-streamsend-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-streamsend-fields-html").html(data.html);
												jQuery(".ulp-streamsend-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-streamsend-fields-html").html("<div class=\'ulp-streamsend-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to StreamSend server.', 'ulp').'</strong></div>");
												jQuery(".ulp-streamsend-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-streamsend-fields-html").html("<div class=\'ulp-streamsend-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to StreamSend server.', 'ulp').'</strong></div>");
											jQuery(".ulp-streamsend-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_streamsend_enable"])) $popup_options['streamsend_enable'] = "on";
		else $popup_options['streamsend_enable'] = "off";
		if ($popup_options['streamsend_enable'] == 'on') {
			if (empty($popup_options['streamsend_login_id'])) $errors[] = __('Invalid StreamSend API Login ID', 'ulp');
			if (empty($popup_options['streamsend_key'])) $errors[] = __('Invalid StreamSend API Key', 'ulp');
			if (empty($popup_options['streamsend_audience_id'])) $errors[] = __('Invalid StreamSend Audience ID', 'ulp');
			if (empty($popup_options['streamsend_list_id'])) $errors[] = __('Invalid StreamSend List ID', 'ulp');
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
		if (isset($ulp->postdata["ulp_streamsend_enable"])) $popup_options['streamsend_enable'] = "on";
		else $popup_options['streamsend_enable'] = "off";
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_streamsend_field_')) == 'ulp_streamsend_field_') {
				$field = substr($key, strlen('ulp_streamsend_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['streamsend_fields'] = $fields;
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['streamsend_enable'] == 'on') {
			$data = '<person>
	<email-address>'.esc_html($_subscriber['{subscription-email}']).'</email-address>';
			$fields = $popup_options['streamsend_fields'];
			if (is_array($fields)) $fields = array_merge($this->default_popup_options['streamsend_fields'], $fields);
			else $fields = $this->default_popup_options['streamsend_fields'];
			foreach ($fields as $key => $value) {
				if (!empty($value)) {
					$key = str_replace('_', '-', $key);
					$data .= '
	<'.$key.'>'.esc_html(strtr($value, $_subscriber)).'</'.$key.'>';
				}
			}
			$data .= '
</person>';
			$result = $this->connect($popup_options['streamsend_login_id'], $popup_options['streamsend_key'], 'audiences/'.intval($popup_options['streamsend_audience_id']).'/people.xml?email_address='.$_subscriber['{subscription-email}']);
			if ($result) {
				$p = xml_parser_create();
				if (xml_parse_into_struct($p, $result, $values, $index)) {
					if (array_key_exists('PERSON', $index)) {
						$person_id = $values[$index['ID'][0]]['value'];
						$result = $this->connect($popup_options['streamsend_login_id'], $popup_options['streamsend_key'], 'audiences/'.intval($popup_options['streamsend_audience_id']).'/people/'.$person_id.'.xml', $data, 'PUT');
					} else {
						$result = $this->connect($popup_options['streamsend_login_id'], $popup_options['streamsend_key'], 'audiences/'.intval($popup_options['streamsend_audience_id']).'/people.xml', $data);
					}
					$data = '<membership>
	<list-id>'.esc_html($popup_options['streamsend_list_id']).'</list-id>
	<email-address>'.esc_html($_subscriber['{subscription-email}']).'</email-address>
</membership>';
					$result = $this->connect($popup_options['streamsend_login_id'], $popup_options['streamsend_key'], 'audiences/'.intval($popup_options['streamsend_audience_id']).'/memberships.xml', $data);
				}
				xml_parser_free($p);
			}
		}
	}
	function show_audiences() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_login_id']) || !isset($_POST['ulp_key']) || empty($_POST['ulp_login_id']) || empty($_POST['ulp_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Login ID or Key!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$login_id = trim(stripslashes($_POST['ulp_login_id']));
			$key = trim(stripslashes($_POST['ulp_key']));

			$audiences = array();
			$result = $this->connect($login_id, $key, 'audiences.xml');
			if ($result) {
				$p = xml_parser_create();
				if (xml_parse_into_struct($p, $result, $values, $index)) {
					if (array_key_exists('ERROR', $index)) {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.ucfirst($values[$index['ERROR'][0]]['value']).'</div>';
						echo json_encode($return_object);
						exit;
					} else if (array_key_exists('AUDIENCES', $index)) {
						if (array_key_exists('ID', $index) && sizeof($index['ID']) > 0) {
							foreach ($index['ID'] as $idx => $value_idx) {
								$audiences[$values[$value_idx]['value']] = $values[$index['NAME'][$idx]]['value'];
							}
						} else {
							$return_object = array();
							$return_object['status'] = 'OK';
							$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No audiences found!', 'ulp').'</div>';
							echo json_encode($return_object);
							exit;
						}
					} else {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response!', 'ulp').'</div>';
						echo json_encode($return_object);
						exit;
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
				xml_parser_free($p);
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to StreamSend Server!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$audiences_html = '';
			if (!empty($audiences)) {
				foreach ($audiences as $id => $name) {
					$audiences_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
				}
			} else $audiences_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No data found!', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $audiences_html;
			$return_object['items'] = sizeof($audiences);
			echo json_encode($return_object);
		}
		exit;
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_login_id']) || !isset($_POST['ulp_key']) || !isset($_POST['ulp_audience']) || empty($_POST['ulp_login_id']) || empty($_POST['ulp_key']) || empty($_POST['ulp_audience'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API Credentials or Audience!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$login_id = trim(stripslashes($_POST['ulp_login_id']));
			$key = trim(stripslashes($_POST['ulp_key']));
			$audience = trim(stripslashes($_POST['ulp_audience']));

			$lists = array();
			$result = $this->connect($login_id, $key, 'audiences/'.intval($audience).'/lists.xml');
			if ($result) {
				$p = xml_parser_create();
				if (xml_parse_into_struct($p, $result, $values, $index)) {
					if (array_key_exists('ERROR', $index)) {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.ucfirst($values[$index['ERROR'][0]]['value']).'</div>';
						echo json_encode($return_object);
						exit;
					} else if (array_key_exists('LISTS', $index)) {
						if (array_key_exists('ID', $index) && sizeof($index['ID']) > 0) {
							foreach ($index['ID'] as $idx => $value_idx) {
								$lists[$values[$value_idx]['value']] = $values[$index['NAME'][$idx]]['value'];
							}
						} else {
							$return_object = array();
							$return_object['status'] = 'OK';
							$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No lists found!', 'ulp').'</div>';
							echo json_encode($return_object);
							exit;
						}
					} else {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response!', 'ulp').'</div>';
						echo json_encode($return_object);
						exit;
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
				xml_parser_free($p);
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to StreamSend Server!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$lists_html = '';
			if (!empty($lists)) {
				foreach ($lists as $id => $name) {
					$lists_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
				}
			} else $lists_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No data found!', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $lists_html;
			$return_object['items'] = sizeof($lists);
			echo json_encode($return_object);
		}
		exit;
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_login_id']) || !isset($_POST['ulp_key']) || !isset($_POST['ulp_audience']) || empty($_POST['ulp_login_id']) || empty($_POST['ulp_key']) || empty($_POST['ulp_audience'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-streamsend-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Credentials or Audience!', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$login_id = trim(stripslashes($_POST['ulp_login_id']));
			$key = trim(stripslashes($_POST['ulp_key']));
			$audience = trim(stripslashes($_POST['ulp_audience']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($login_id, $key, $audience, $this->default_popup_options['streamsend_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_login_id, $_key, $_audience, $_fields) {
		$fields = '';
		$result = $this->connect($_login_id, $_key, 'audiences/'.intval($_audience).'/fields.xml');
		if ($result) {
			$p = xml_parser_create();
			if (xml_parse_into_struct($p, $result, $values, $index)) {
				if (array_key_exists('ERROR', $index)) {
					$fields = '<div class="ulp-streamsend-grouping" style="margin-bottom: 10px;"><strong>'.ucfirst($values[$index['ERROR'][0]]['value']).'</strong></div>';
				} else if (array_key_exists('FIELDS', $index)) {
					if (array_key_exists('SLUG', $index) && sizeof($index['SLUG']) > 0) {
						$fields .= '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate StreamSend fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
						foreach ($index['SLUG'] as $idx => $value_idx) {
							$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($values[$index['NAME'][$idx]]['value']).':</strong></td>
					<td>
						<input type="text" id="ulp_streamsend_field_'.esc_html($values[$value_idx]['value']).'" name="ulp_streamsend_field_'.esc_html($values[$value_idx]['value']).'" value="'.esc_html(array_key_exists($values[$value_idx]['value'], $_fields) ? $_fields[$values[$value_idx]['value']] : '').'" class="widefat" />
						<br /><em>'.esc_html($values[$index['NAME'][$idx]]['value']).'</em>
					</td>
				</tr>';
						}
						$fields .= '
			</table>';
					} else {
						$fields = '<div class="ulp-streamsend-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found!', 'ulp').'</strong></div>';
					}
				} else {
					$firlds = '<div class="ulp-streamsend-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid server response!', 'ulp').'</strong></div>';
				}
			} else {
				$fields = '<div class="ulp-streamsend-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid server response!', 'ulp').'</strong></div>';
			}
			xml_parser_free($p);
		} else {
			$fields = '<div class="ulp-streamsend-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	
	function connect($_login_id, $_key, $_path, $_data = array(), $_method = '') {
		$url = 'https://app.streamsend.com/'.ltrim($_path, '/');
		$headers = array(
			'Authorization: Basic '.base64_encode($_login_id.':'.$_key),
			'Content-Type: application/xml',
			'Accept: application/xml'
		);
		try {
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $_data);
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 30);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
			//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($curl);
			curl_close($curl);
		} catch (Exception $e) {
			$response = false;
		}
		return $response;
	}
}
$ulp_streamsend = new ulp_streamsend_class();
?>