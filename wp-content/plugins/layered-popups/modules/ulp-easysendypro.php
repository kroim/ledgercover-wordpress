<?php
/* EasySendy Pro integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_easysendypro_class {
	var $default_popup_options = array(
		'easysendypro_enable' => 'off',
		'easysendypro_api_key' => '',
		'easysendypro_list' => '',
		'easysendypro_list_id' => '',
		'easysendypro_fields' => ''
	);
	function __construct() {
		$this->default_popup_options['easysendypro_fields'] = serialize(array('FNAME' => '{subscription-name}', 'EMAIL' => '{subscription-email}'));
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-easysendypro-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-easysendypro-fields', array(&$this, "show_fields"));
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
				<h3>'.__('EasySendy Pro Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable EasySendy Pro', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_easysendypro_enable" name="ulp_easysendypro_enable" '.($popup_options['easysendypro_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to EasySendy Pro', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to EasySendy Pro.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Public API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_easysendypro_api_key" name="ulp_easysendypro_api_key" value="'.esc_html($popup_options['easysendypro_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your EasySendy Pro Public API Key. Go to your EasySendy Pro account, click "API Keys" in left side menu.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-easysendypro-list" name="ulp_easysendypro_list" value="'.esc_html($popup_options['easysendypro_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_easysendypro_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-easysendypro-list-id" name="ulp_easysendypro_list_id" value="'.esc_html($popup_options['easysendypro_list_id']).'" />
							<div id="ulp-easysendypro-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_easysendypro_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-easysendypro-lists", "ulp_api_key": jQuery("#ulp_easysendypro_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-easysendypro-fields-html">';
		if (!empty($popup_options['easysendypro_api_key']) && !empty($popup_options['easysendypro_list_id'])) {
			$fields = $this->get_fields_html($popup_options['easysendypro_api_key'], $popup_options['easysendypro_list_id'], $popup_options['easysendypro_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_easysendypro_fields_button" class="ulp_button button-secondary" onclick="return ulp_easysendypro_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-easysendypro-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_easysendypro_loadfields() {
									jQuery("#ulp-easysendypro-fields-loading").fadeIn(350);
									jQuery(".ulp-easysendypro-fields-html").slideUp(350);
									var data = {action: "ulp-easysendypro-fields", ulp_key: jQuery("#ulp_easysendypro_api_key").val(), ulp_list: jQuery("#ulp-easysendypro-list-id").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-easysendypro-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-easysendypro-fields-html").html(data.html);
												jQuery(".ulp-easysendypro-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-easysendypro-fields-html").html("<div class=\'ulp-easysendypro-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to EasySendy Pro server.', 'ulp').'</strong></div>");
												jQuery(".ulp-easysendypro-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-easysendypro-fields-html").html("<div class=\'ulp-easysendypro-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to EasySendy Pro server.', 'ulp').'</strong></div>");
											jQuery(".ulp-easysendypro-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_easysendypro_enable"])) $popup_options['easysendypro_enable'] = "on";
		else $popup_options['easysendypro_enable'] = "off";
		if ($popup_options['easysendypro_enable'] == 'on') {
			if (empty($popup_options['easysendypro_api_key'])) $errors[] = __('Invalid EasySendy Pro API key', 'ulp');
			if (empty($popup_options['easysendypro_list_id'])) $errors[] = __('Invalid EasySendy Pro list ID', 'ulp');
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
		if (isset($ulp->postdata["ulp_easysendypro_enable"])) $popup_options['easysendypro_enable'] = "on";
		else $popup_options['easysendypro_enable'] = "off";
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_easysendypro_field_')) == 'ulp_easysendypro_field_') {
				$field = substr($key, strlen('ulp_easysendypro_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['easysendypro_fields'] = serialize($fields);
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['easysendypro_enable'] == 'on') {
			$data = array(
				"EMAIL" => $_subscriber['{subscription-email}'],
				"api_key" => $popup_options['easysendypro_api_key'],
				"listUID" => $popup_options['easysendypro_list_id'],
				"req_type" => 'subscribe'
			);
			$fields = array();
			if (!empty($popup_options['easysendypro_fields'])) $fields = unserialize($popup_options['easysendypro_fields']);
			if (!empty($fields) && is_array($fields)) {
				foreach ($fields as $key => $value) {
					if (!empty($value) && $key != 'EMAIL') {
						$data[$key] = strtr($value, $_subscriber);
					}
				}
			}
			$result = $this->connect('http://api.easysendy.com/ver1/subscribeAPI', $data);
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
			$api_key = trim(stripslashes($_POST['ulp_api_key']));
			$lists = array();
			
			$result = $this->connect('http://api.easysendy.com/ver1/listAPI?req_type=allLists&api_key='.$api_key);
			if ($result) {
				if (array_key_exists("status", $result)) {
					if ($result['status'] == 'success') {
						if ($result['count'] == 0) {
							$return_object = array();
							$return_object['status'] = 'OK';
							$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No lists found!', 'ulp').'</div>';
							echo json_encode($return_object);
							exit;
						}
					} else {
						if (array_key_exists("message", $result)) $message = ucfirst(strip_tags($result['message']));
						else $message = __('Unknown error occured!', 'ulp');
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.esc_html($message).'</div>';
						echo json_encode($return_object);
						exit;
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Unknown error occured!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
				foreach($result['listsData'] as $list) {
					if (is_array($list)) {
						if (array_key_exists('list_uid', $list) && array_key_exists('name', $list)) {
							$lists[$list['list_uid']] = $list['name'];
						}
					}
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to EasySendy Pro server!', 'ulp').'</div>';
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
			if (!isset($_POST['ulp_key']) || !isset($_POST['ulp_list']) || empty($_POST['ulp_key']) || empty($_POST['ulp_list'])) {
				$return_object['html'] = '<strong>'.__('Invalid API Key or List ID!', 'ulp').'</strong>';
			} else {
				$key = trim(stripslashes($_POST['ulp_key']));
				$list = trim(stripslashes($_POST['ulp_list']));
				$return_object['html'] = $this->get_fields_html($key, $list, $this->default_popup_options['easysendypro_fields']);
			}
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_key, $_list, $_fields) {
		$fields = '';
		$result = $this->connect('http://api.easysendy.com/ver1/listAPI?req_type=listFields&list_uid='.$_list.'&api_key='.$_key);
		if ($result) {
			if (array_key_exists("status", $result)) {
				if ($result['status'] == 'success') {
					if ($result['count'] == 0) {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No fields found!', 'ulp').'</div>';
						echo json_encode($return_object);
						exit;
					}
				} else {
					if (array_key_exists("message", $result)) $message = ucfirst(strip_tags($result['message']));
					else $message = __('Unknown error occured!', 'ulp');
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.esc_html($message).'</div>';
					echo json_encode($return_object);
					exit;
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Unknown error occured!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$values = unserialize($_fields);
			if (!is_array($values)) $values = array();
			$fields = '
				'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate EasySendy Pro fields with the popup fields.', 'ulp').'
				<table style="min-width: 280px; width: 50%;">';
			foreach($result['listsData'] as $field) {
				if (array_key_exists('tag', $field) && array_key_exists('label', $field)) {
							$fields .= '
					<tr>
						<td style="width: 100px;"><strong>'.esc_html($field['tag']).':</strong></td>
						<td>
							<input type="text" id="ulp_easysendypro_field_'.esc_html($field['tag']).'" name="ulp_easysendypro_field_'.esc_html($field['tag']).'" value="'.esc_html(array_key_exists($field['tag'], $values) ? $values[$field['tag']] : '').'" class="widefat"'.($field['tag'] == 'EMAIL' ? ' readonly="readonly"' : '').' />
							<br /><em>'.esc_html($field['label']).'</em>
						</td>
					</tr>';
				}
			}
			$fields .= '
				</table>';
		} else {
			return '<div class="ulp-easysendypro-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not connect to EasySendy Pro API server!', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	
	function connect($_url, $_data = array(), $_method = '') {
		try {
			$curl = curl_init($_url);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
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
			curl_close($curl);
			$result = json_decode($response, true);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
}
$ulp_easysendypro = new ulp_easysendypro_class();
?>