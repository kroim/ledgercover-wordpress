<?php
/* SimplyCast integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_simplycast_class {
	var $default_popup_options = array(
		"simplycast_enable" => "off",
		"simplycast_api_public" => "",
		"simplycast_api_secret" => "",
		"simplycast_list" => "",
		"simplycast_list_id" => "",
		"simplycast_fields" => ""
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-simplycast-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-simplycast-fields', array(&$this, "show_fields"));
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
				<h3>'.__('SimplyCast Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable SimplyCast', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_simplycast_enable" name="ulp_simplycast_enable" '.($popup_options['simplycast_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to SimplyCast', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to SimplyCast.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Public API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_simplycast_api_public" name="ulp_simplycast_api_public" value="'.esc_html($popup_options['simplycast_api_public']).'" class="widefat">
							<br /><em>'.__('Enter your SimplyCast Public API Key. You can get it <a href="https://app.simplycast.com/?q=account/info/api" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Secret API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_simplycast_api_secret" name="ulp_simplycast_api_secret" value="'.esc_html($popup_options['simplycast_api_secret']).'" class="widefat">
							<br /><em>'.__('Enter your SimplyCast Secret API Key. You can get it <a href="https://app.simplycast.com/?q=account/info/api" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID:', 'ulp').'</th>
						<td>
							<input type="text" id="ulp-simplycast-list" name="ulp_simplycast_list" value="'.esc_html($popup_options['simplycast_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_simplycast_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-simplycast-list-id" name="ulp_simplycast_list_id" value="'.esc_html($popup_options['simplycast_list_id']).'" />
							<div id="ulp-simplycast-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_simplycast_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-simplycast-lists", "ulp_api_public": jQuery("#ulp_simplycast_api_public").val(), "ulp_api_secret": jQuery("#ulp_simplycast_api_secret").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Columns', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-simplycast-fields-html">';
		if (!empty($popup_options['simplycast_api_public']) && !empty($popup_options['simplycast_api_secret'])) {
		//echo $popup_options['simplycast_fields'];
			$fields = $this->get_fields_html($popup_options['simplycast_api_public'], $popup_options['simplycast_api_secret'], $popup_options['simplycast_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_simplycast_fields_button" class="ulp_button button-secondary" onclick="return ulp_simplycast_loadfields();">'.__('Load Columns', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-simplycast-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load columns list. Ignore if you do not need specify columns values.', 'ulp').'</em>
							<script>
								function ulp_simplycast_loadfields() {
									jQuery("#ulp-simplycast-fields-loading").fadeIn(350);
									jQuery(".ulp-simplycast-fields-html").slideUp(350);
									var data = {action: "ulp-simplycast-fields", ulp_api_public: jQuery("#ulp_simplycast_api_public").val(), ulp_api_secret: jQuery("#ulp_simplycast_api_secret").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-simplycast-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-simplycast-fields-html").html(data.html);
												jQuery(".ulp-simplycast-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-simplycast-fields-html").html("<div class=\'ulp-simplycast-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to SimplyCast server.', 'ulp').'</strong></div>");
												jQuery(".ulp-simplycast-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-simplycast-fields-html").html("<div class=\'ulp-simplycast-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to SimplyCast server.', 'ulp').'</strong></div>");
											jQuery(".ulp-simplycast-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_simplycast_enable"])) $popup_options['simplycast_enable'] = "on";
		else $popup_options['simplycast_enable'] = "off";
		if ($popup_options['simplycast_enable'] == 'on') {
			if (empty($popup_options['simplycast_api_public'])) $errors[] = __('Invalid SimplyCast API Username.', 'ulp');
			if (empty($popup_options['simplycast_api_secret'])) $errors[] = __('Invalid SimplyCast API Password.', 'ulp');
			if (empty($popup_options['simplycast_list_id'])) $errors[] = __('Invalid SimplyCast List ID.', 'ulp');
			$fields = array();
			foreach($ulp->postdata as $key => $value) {
				if (substr($key, 0, strlen('ulp_simplycast_field_')) == 'ulp_simplycast_field_') {
					$field = substr($key, strlen('ulp_simplycast_field_'));
					if (!empty($value)) {
						$fields[$field] = stripslashes(trim($value));
					}
				}
			}
			if (empty($fields)) $errors[] = __('Set at least one SimplyCast Column value.', 'ulp');
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
		if (isset($ulp->postdata["ulp_simplycast_enable"])) $popup_options['simplycast_enable'] = "on";
		else $popup_options['simplycast_enable'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_simplycast_field_')) == 'ulp_simplycast_field_') {
				$field = substr($key, strlen('ulp_simplycast_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['simplycast_fields'] = serialize($fields);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['simplycast_enable'] == 'on') {
			$data = array('contact' => array(
				'lists' => array($popup_options['simplycast_list_id'])
			));
			$fields = array();
			if (!empty($popup_options['simplycast_fields'])) $fields = unserialize($popup_options['simplycast_fields']);
			if (!empty($fields) && is_array($fields)) {
				foreach ($fields as $key => $value) {
					if (!empty($value)) {
						$data['contact']['fields'][] = array('id' => $key, 'value' => strtr($value, $_subscriber));
					}
				}
			}
			$result = $this->connect($popup_options['simplycast_api_public'], $popup_options['simplycast_api_secret'], '/contactmanager/contacts?query='.rawurlencode('`email`="'.$_subscriber['{subscription-email}'].'"'));
			if ($result && is_array($result) && array_key_exists("responseCount", $result) && $result['responseCount'] > 0) {
				$contact_id = $result['contacts'][0]['id'];
				$result = $this->connect($popup_options['simplycast_api_public'], $popup_options['simplycast_api_secret'], '/contactmanager/contacts/'.$contact_id, $data);
				$result = $this->connect($popup_options['simplycast_api_public'], $popup_options['simplycast_api_secret'], '/contactmanager/lists/'.$popup_options['simplycast_list_id'].'/contacts', array('contacts' => array($contact_id)));
			} else {
				$result = $this->connect($popup_options['simplycast_api_public'], $popup_options['simplycast_api_secret'], '/contactmanager/contacts', $data);
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_api_public']) || !isset($_POST['ulp_api_secret']) || empty($_POST['ulp_api_public']) || empty($_POST['ulp_api_secret'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Public or Secret API Key!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$api_public = trim(stripslashes($_POST['ulp_api_public']));
			$api_secret = trim(stripslashes($_POST['ulp_api_secret']));

			$lists = array();
			$result = $this->connect($api_public, $api_secret, '/contactmanager/lists');
			if ($result) {
				if (array_key_exists("error", $result)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.esc_html($result['error']).'</div>';
					echo json_encode($return_object);
					exit;
				}
				if (!array_key_exists("lists", $result) || !is_array($result['lists']) || sizeof($result['lists']) == 0) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No lists found!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
				foreach($result['lists'] as $list) {
					if (is_array($list)) {
						if (array_key_exists('id', $list) && array_key_exists('name', $list)) {
							$lists[$list['id']] = $list['name'];
						}
					}
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to SimplyCast server!', 'ulp').'</div>';
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
			if (!isset($_POST['ulp_api_public']) || !isset($_POST['ulp_api_secret']) || empty($_POST['ulp_api_public']) || empty($_POST['ulp_api_secret'])) {
				$return_object['html'] = '<div class="ulp-simplycast-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid Public or Secret API Key!', 'ulp').'</strong></div>';
			} else {
				$api_public = trim(stripslashes($_POST['ulp_api_public']));
				$api_secret = trim(stripslashes($_POST['ulp_api_secret']));
				$return_object['html'] = $this->get_fields_html($api_public, $api_secret, $this->default_popup_options['simplycast_fields']);
			}
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_api_public, $_api_secret, $_fields) {
		$fields = '';
		$result = $this->connect($_api_public, $_api_secret, '/contactmanager/columns');
		if ($result) {
			if (array_key_exists("error", $result)) {
				return '<div class="ulp-simplycast-grouping" style="margin-bottom: 10px;"><strong>'.esc_html($result['error']).'</strong></div>';
			} else if (!array_key_exists("columns", $result) || !is_array($result['columns']) || sizeof($result['columns']) == 0) {
				return '<div class="ulp-simplycast-grouping" style="margin-bottom: 10px;"><strong>'.__('No columns found!', 'ulp').'</strong></div>';
			}
			$values = unserialize($_fields);
			if (!is_array($values)) $values = array();
			$fields = '
			'.__('Please adjust the columns below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate SimplyCast columns with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
			foreach ($result['columns'] as $field) {
				if (is_array($field)) {
					if (array_key_exists('id', $field) && array_key_exists('name', $field)) {
						if ($field['editable']) {
							$value = array_key_exists($field['id'], $values) ? $values[$field['id']] : '';
							if ($field['name'] == 'email') $value = '{subscription-email}';
							$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['name']).':</strong></td>
					<td>
						<input type="text" id="ulp_simplycast_field_'.esc_html($field['id']).'" name="ulp_simplycast_field_'.esc_html($field['id']).'" value="'.esc_html($value).'" class="widefat"'.($field['name'] == 'email' ? ' readonly="readonly"' : '').' />
						<br /><em>'.esc_html($field['name']).'</em>
					</td>
				</tr>';
						}
					}
				}
			}
			$fields .= '
			</table>';
		} else {
			return '<div class="ulp-simplycast-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not connect to SimplyCast server!', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_api_public, $_api_secret, $_path, $_data = array(), $_method = '') {
		try {
			$url = 'https://api.simplycast.com/'.ltrim($_path, '/');
			$headers = array(
				'Authorization: Basic '.base64_encode($_api_public.':'.$_api_secret),
				'Content-Type: application/json',
				'Accept: application/json'
			);
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
			//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($curl);
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			if ($http_code == 204) return array('error' => 'No data found!');
			$result = json_decode($response, true);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
	
}
$ulp_simplycast = new ulp_simplycast_class();
?>