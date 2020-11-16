<?php
/* Benchmark Email integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_benchmark_class {
	var $default_popup_options = array(
		'benchmark_enable' => 'off',
		'benchmark_api_key' => '',
		'benchmark_list' => '',
		'benchmark_list_id' => '',
		'benchmark_fields' => array(
			'firstname' => '{subscription-name}'
		),
		'benchmark_double' => 'off'
	);
	var $optional_fields = array(
		'firstname' => array('name' => 'firstname', 'label' => 'First Name'),
		'middlename' => array('name' => 'middlename', 'label' => 'Middle Name'),
		'lastname' => array('name' => 'lastname', 'label' => 'Last Name'),
		'address' => array('name' => 'Address', 'label' => 'Address'),
		'city' => array('name' => 'City', 'label' => 'City'),
		'state' => array('name' => 'State', 'label' => 'State'),
		'zip' => array('name' => 'Zip', 'label' => 'Zip'),
		'country' => array('name' => 'Country', 'label' => 'Country'),
		'phone' => array('name' => 'Phone', 'label' => 'Phone'),
		'fax' => array('name' => 'Fax', 'label' => 'Fax'),
		'cellphone' => array('name' => 'Cell Phone', 'label' => 'Cell Phone'),
		'companyname' => array('name' => 'Company Name', 'label' => 'Company Name'),
		'jobtitle' => array('name' => 'Job Title', 'label' => 'Job Title'),
		'businessphone' => array('name' => 'Business Phone', 'label' => 'Business Phone'),
		'businessfax' => array('name' => 'Business Fax', 'label' => 'Business Fax'),
		'businessaddress' => array('name' => 'Business Address', 'label' => 'Business Address'),
		'businesscity' => array('name' => 'Business City', 'label' => 'Business City'),
		'businessstate' => array('name' => 'Business State', 'label' => 'Business State'),
		'businesszip' => array('name' => 'Business Zip', 'label' => 'Business Zip'),
		'businesscountry' => array('name' => 'Business Country', 'label' => 'Business Country'),
		'notes' => array('name' => 'Notes', 'label' => 'Notes'),
		'date1' => array('name' => 'Date 1', 'label' => 'Date 1'),
		'date2' => array('name' => 'Date 2', 'label' => 'Date 2'),
		'extra3' => array('name' => 'Extra 3', 'label' => 'Extra 3'),
		'extra4' => array('name' => 'Extra 4', 'label' => 'Extra 4'),
		'extra5' => array('name' => 'Extra 5', 'label' => 'Extra 5'),
		'extra6' => array('name' => 'Extra 6', 'label' => 'Extra 6')
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-benchmark-lists', array(&$this, "show_lists"));
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
				<h3>'.__('Benchmark Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Benchmark', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_benchmark_enable" name="ulp_benchmark_enable" '.($popup_options['benchmark_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Benchmark Email', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Benchmark Email.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_benchmark_api_key" name="ulp_benchmark_api_key" value="'.esc_html($popup_options['benchmark_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Benchmark Email API Key. You can get your API Key <a href="https://ui.benchmarkemail.com/Integrate#API" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-benchmark-list" name="ulp_benchmark_list" value="'.esc_html($popup_options['benchmark_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_benchmark_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-benchmark-list-id" name="ulp_benchmark_list_id" value="'.esc_html($popup_options['benchmark_list_id']).'" />
							<div id="ulp-benchmark-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_benchmark_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-benchmark-lists", "ulp_api_key": jQuery("#ulp_benchmark_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Optional Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div id="ulp-benchmark-fields" style="display:none;">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Benchmark fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
		foreach ($this->optional_fields as $key => $field) {
			echo '
									<tr>
										<td style="width: 100px;"><strong>'.esc_html($field['label']).':</strong></td>
										<td>
											<input type="text" id="ulp_benchmark_field_'.esc_html($key).'" name="ulp_benchmark_field_'.esc_html($key).'" value="'.esc_html(array_key_exists($key, $popup_options['benchmark_fields']) ? $popup_options['benchmark_fields'][$key] : '').'" class="widefat" />
											<br /><em>'.esc_html($field['label']).'</em>
										</td>
									</tr>';
		}
		echo '
								</table>
							</div>
							<a id="ulp-benchmark-toggle-fields" class="ulp_button button-secondary" data-state="hidden" onclick="return ulp_toggle_fields(this);">'.__('Show Optional Fields', 'ulp').'</a>
							<script>
								function ulp_toggle_fields(object) {
									if (jQuery(object).attr("data-state") == "hidden") {
										jQuery(object).attr("data-state", "visible");
										jQuery(object).html("Hide Optional Fields");
										jQuery("#ulp-benchmark-fields").slideDown(500);
									} else {
										jQuery(object).attr("data-state", "hidden");
										jQuery(object).html("Show Optional Fields");
										jQuery("#ulp-benchmark-fields").slideUp(500);
									}
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Double opt-in', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_benchmark_double" name="ulp_benchmark_double" '.($popup_options['benchmark_double'] == "on" ? 'checked="checked"' : '').'"> '.__('Ask users to confirm their subscription', 'ulp').'
							<br /><em>'.__('Control whether a double opt-in confirmation message is sent.', 'ulp').'</em>
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
		if (isset($ulp->postdata["ulp_benchmark_enable"])) $popup_options['benchmark_enable'] = "on";
		else $popup_options['benchmark_enable'] = "off";
		if ($popup_options['benchmark_enable'] == 'on') {
			if (empty($popup_options['benchmark_api_key'])) $errors[] = __('Invalid Benchmark Email API Key', 'ulp');
			if (empty($popup_options['benchmark_list_id'])) $errors[] = __('Invalid Benchmark Email List ID', 'ulp');
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
		foreach ($this->optional_fields as $key => $field) {
			if (isset($ulp->postdata['ulp_benchmark_field_'.$key])) {
				$popup_options['benchmark_fields'][$key] = stripslashes(trim($ulp->postdata['ulp_benchmark_field_'.$key]));
			}
		}
		if (isset($ulp->postdata["ulp_benchmark_enable"])) $popup_options['benchmark_enable'] = "on";
		else $popup_options['benchmark_enable'] = "off";
		if (isset($ulp->postdata["ulp_benchmark_double"])) $popup_options['benchmark_double'] = "on";
		else $popup_options['benchmark_double'] = "off";
		return array_merge($_popup_options, $popup_options);
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
			
			$data = array(
				'pageNumber' => 1,
				'pageSize' => 100
			);
			$result = $this->connect($key, 'listGet', $data);
			
			if (is_array($result) && !array_key_exists('error', $result)) {
				if (sizeof($result) > 1 || (sizeof($result) == 1 && $result[0]['is_master_unsubscribe'] != 1)) {
					foreach ($result as $list) {
						if (is_array($list)) {
							if (array_key_exists('id', $list) && array_key_exists('listname', $list)) {
								$lists[$list['id']] = $list['listname'];
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
	
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['benchmark_enable'] == 'on') {
			$data = array(
				'emailAddress' => $_subscriber['{subscription-email}'],
				'listID' => $popup_options['benchmark_list_id']
			);
			$result = $this->connect($popup_options['benchmark_api_key'], 'listGetContactDetails', $data);
			if (array_key_exists('id', $result)) {
				$data = array(
					'contactID' => $result['id'],
					'contactDetail' => array(
						'id' => $result['id'],
						'email' => $_subscriber['{subscription-email}']
					),
					'listID' => $popup_options['benchmark_list_id']
				);
				foreach ($popup_options['benchmark_fields'] as $key => $value) {
					if (!empty($value) && array_key_exists($key, $this->optional_fields)) {
						$data['contactDetail'][$this->optional_fields[$key]['name']] = strtr($value, $_subscriber);
					}
				}
				$result = $this->connect($popup_options['benchmark_api_key'], 'listUpdateContactDetails', $data);
			} else {
				$data = array(
					'contacts' => array(
						'email' => $_subscriber['{subscription-email}']
					),
					'optin' => ($popup_options['benchmark_double'] == 'on' ? 1 : 0),
					'listID' => $popup_options['benchmark_list_id']
				);
				foreach ($popup_options['benchmark_fields'] as $key => $value) {
					if (!empty($value) && array_key_exists($key, $this->optional_fields)) {
						$data['contacts'][$this->optional_fields[$key]['name']] = strtr($value, $_subscriber);
					}
				}
				$result = $this->connect($popup_options['benchmark_api_key'], 'listAddContacts', $data);
			}
		}
	}
	function benchmark_getlists($_key) {
		$request = http_build_query(array(
			'token' => $_key,
			'pageNumber' => 1,
			'pageSize' => 100
		));

		$curl = curl_init('https://www.benchmarkemail.com/api/1.0/?output=php&method=listGet');
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);

		curl_setopt($curl, CURLOPT_TIMEOUT, 20);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
								
		$response = curl_exec($curl);
		curl_close($curl);

		$result = unserialize($response);
		if (!is_array($result) || isset($result['error'])) return array();
		$lists = array();
		foreach ($result as $key => $value) {
			$lists[$value['id']] = $value['listname'];
		}
		return $lists;
	}
	function connect($_api_key, $_method, $_data = array()) {
		$_data['token'] = $_api_key;
		try {
			$curl = curl_init('https://www.benchmarkemail.com/api/1.1/?output=json&method='.rawurlencode($_method));
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
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
$ulp_benchmark = new ulp_benchmark_class();
?>