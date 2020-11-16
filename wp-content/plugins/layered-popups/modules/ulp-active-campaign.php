<?php
/* Active Campaign integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_activecampaign_class {
	var $default_popup_options = array(
		'activecampaign_enable' => 'off',
		'activecampaign_url' => '',
		'activecampaign_api_key' => '',
		'activecampaign_list' => '',
		'activecampaign_list_id' => '',
		'activecampaign_fields' => '',
		'activecampaign_firstname' => '{subscription-name}',
		'activecampaign_lastname' => '',
		'activecampaign_phone' => '',
		'activecampaign_orgname' => '',
		'activecampaign_tags' => ''
	);
	function __construct() {
		$this->default_popup_options['activecampaign_fields'] = serialize(array());
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-activecampaign-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-activecampaign-fields', array(&$this, "show_fields"));
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
				<h3>'.__('Active Campaign Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable ActiveCampaign', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_activecampaign_enable" name="ulp_activecampaign_enable" '.($popup_options['activecampaign_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to ActiveCampaign', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to ActiveCampaign.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API URL', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_activecampaign_url" name="ulp_activecampaign_url" value="'.esc_html($popup_options['activecampaign_url']).'" class="widefat">
							<br /><em>'.__('Enter your ActiveCampaign API URL. To get API URL please go to your ActiveCampaign Account >> My Settings >> API.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_activecampaign_api_key" name="ulp_activecampaign_api_key" value="'.esc_html($popup_options['activecampaign_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your ActiveCampaign API Key. To get API Key please go to your ActiveCampaign Account >> My Settings >> API.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-activecampaign-list" name="ulp_activecampaign_list" value="'.esc_html($popup_options['activecampaign_list']).'" class="ulp-input-options" readonly="readonly" onfocus="ulp_activecampaign_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-activecampaign-list-id" name="ulp_activecampaign_list_id" value="'.esc_html($popup_options['activecampaign_list_id']).'" />
							<div id="ulp-activecampaign-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_activecampaign_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-activecampaign-lists", "ulp_url": jQuery("#ulp_activecampaign_url").val(), "ulp_api_key": jQuery("#ulp_activecampaign_api_key").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Active Campaign list fields with the popup fields.', 'ulp').'
							<table style="min-width: 280px; width: 50%;">
								<tr>
									<td style="width: 100px;"><strong>'.__('First Name', 'ulp').':</strong></td>
									<td>
										<input type="text" id="ulp_activecampaign_firstname" name="ulp_activecampaign_firstname" value="'.esc_html($popup_options['activecampaign_firstname']).'" class="widefat" />
										<br /><em>'.__('First Name', 'ulp').'</em>
									</td>
								</tr>
								<tr>
									<td><strong>'.__('Last Name', 'ulp').':</strong></td>
									<td>
										<input type="text" id="ulp_activecampaign_lastname" name="ulp_activecampaign_lastname" value="'.esc_html($popup_options['activecampaign_lastname']).'" class="widefat" />
										<br /><em>'.__('Last Name', 'ulp').'</em>
									</td>
								</tr>
								<tr>
									<td><strong>'.__('Phone #', 'ulp').':</strong></td>
									<td>
										<input type="text" id="ulp_activecampaign_phone" name="ulp_activecampaign_phone" value="'.esc_html($popup_options['activecampaign_phone']).'" class="widefat" />
										<br /><em>'.__('Phone #', 'ulp').'</em>
									</td>
								</tr>
								<tr>
									<td><strong>'.__('Organization', 'ulp').':</strong></td>
									<td>
										<input type="text" id="ulp_activecampaign_orgname" name="ulp_activecampaign_orgname" value="'.esc_html($popup_options['activecampaign_orgname']).'" class="widefat" />
										<br /><em>'.__('Organization name. Must have CRM feature for this.', 'ulp').'</em>
									</td>
								</tr>
							</table>
							<div class="ulp-activecampaign-fields-html">';
		if (!empty($popup_options['activecampaign_url']) && !empty($popup_options['activecampaign_api_key']) && !empty($popup_options['activecampaign_list_id'])) {
			$fields = $this->get_fields_html($popup_options['activecampaign_url'], $popup_options['activecampaign_api_key'], $popup_options['activecampaign_list_id'], $popup_options['activecampaign_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_activecampaign_fields_button" class="ulp_button button-secondary" onclick="return ulp_activecampaign_loadfields();">'.__('Load Custom Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-activecampaign-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_activecampaign_loadfields() {
									jQuery("#ulp-activecampaign-fields-loading").fadeIn(350);
									jQuery(".ulp-activecampaign-fields-html").slideUp(350);
									var data = {action: "ulp-activecampaign-fields", ulp_url: jQuery("#ulp_activecampaign_url").val(), ulp_api_key: jQuery("#ulp_activecampaign_api_key").val(), ulp_list_id: jQuery("#ulp-activecampaign-list-id").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-activecampaign-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-activecampaign-fields-html").html(data.html);
												jQuery(".ulp-activecampaign-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-activecampaign-fields-html").html("<div class=\'ulp-activecampaign-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Active Campaign server.', 'ulp').'</strong></div>");
												jQuery(".ulp-activecampaign-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-activecampaign-fields-html").html("<div class=\'ulp-activecampaign-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Active Campaign server.', 'ulp').'</strong></div>");
											jQuery(".ulp-activecampaign-fields-html").slideDown(350);
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
							<input type="text" id="ulp_activecampaign_tags" name="ulp_activecampaign_tags" value="'.esc_html($popup_options['activecampaign_tags']).'" class="widefat">
							<br /><em>'.__('Comma-separated tags.', 'ulp').'</em>
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
		if (isset($ulp->postdata["ulp_activecampaign_enable"])) $popup_options['activecampaign_enable'] = "on";
		else $popup_options['activecampaign_enable'] = "off";
		if ($popup_options['activecampaign_enable'] == 'on') {
			if (strlen($popup_options['activecampaign_url']) == 0 || !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $popup_options['activecampaign_url'])) $errors[] = __('ActiveCampaign API URL must be a valid URL.', 'ulp');
			if (empty($popup_options['activecampaign_api_key'])) $errors[] = __('Invalid ActiveCampaign API key', 'ulp');
			if (empty($popup_options['activecampaign_list_id'])) $errors[] = __('Invalid ActiveCampaign list ID', 'ulp');
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
		if (isset($ulp->postdata["ulp_activecampaign_enable"])) $popup_options['activecampaign_enable'] = "on";
		else $popup_options['activecampaign_enable'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_activecampaign_field_')) == 'ulp_activecampaign_field_') {
				$field = substr($key, strlen('ulp_activecampaign_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['activecampaign_fields'] = serialize($fields);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['activecampaign_enable'] == 'on') {
			$data = array(
				'api_action' => 'contact_add',
				'api_key' => $popup_options['activecampaign_api_key'],
				'api_output' => 'serialize',
				'p['.$popup_options['activecampaign_list_id'].']' => $popup_options['activecampaign_list_id'],
				'email' => $_subscriber['{subscription-email}'],
				'ip4' => $_SERVER['REMOTE_ADDR']
			);
			if (!empty($popup_options['activecampaign_firstname'])) $data['first_name'] = strtr($popup_options['activecampaign_firstname'], $_subscriber);
			if (!empty($popup_options['activecampaign_lastname'])) $data['last_name'] = strtr($popup_options['activecampaign_lastname'], $_subscriber);
			if (!empty($popup_options['activecampaign_phone'])) $data['phone'] = strtr($popup_options['activecampaign_phone'], $_subscriber);
			if (!empty($popup_options['activecampaign_orgname'])) $data['orgname'] = strtr($popup_options['activecampaign_orgname'], $_subscriber);
			if (!empty($popup_options['activecampaign_tags'])) $data['tags'] = strtr($popup_options['activecampaign_tags'], $_subscriber);
			$fields = array();
			if (!empty($popup_options['activecampaign_fields'])) $fields = unserialize($popup_options['activecampaign_fields']);
			if (!empty($fields) && is_array($fields)) {
				foreach ($fields as $key => $value) {
					if (!empty($value)) {
						$data['field['.$key.',0]'] = strtr($value, $_subscriber);
					}
				}
			}
			$url = str_replace('https://', 'http://', $popup_options['activecampaign_url']);
			$curl = curl_init($url.'/admin/api.php?api_action=contact_add');
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($curl, CURLOPT_TIMEOUT, 20);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			$response = curl_exec($curl);
			curl_close($curl);
			$return_data = unserialize($response);
			if ($return_data['result_code'] == 0) {
				$data['api_action'] = 'contact_edit';
				$data['overwrite'] = 0;
				$data['id'] = $return_data[0]['id'];
				if (isset($data['tags'])) unset($data['tags']);
				
				$url = str_replace('https://', 'http://', $popup_options['activecampaign_url']);
				$curl = curl_init($url.'/admin/api.php?api_action=contact_edit');
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
				curl_setopt($curl, CURLOPT_TIMEOUT, 20);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				$response = curl_exec($curl);
				curl_close($curl);
				
				$tmp = explode(',', $popup_options['activecampaign_tags']);
				$tags = array();
				foreach ($tmp as $tag) {
					$tag = strtr(trim($tag), $_subscriber);
					if (!empty($tag)) $tags[] = $tag;
				}
				if (sizeof($tags) > 0) {
					$data = array(
						'api_action' => 'contact_tag_add',
						'api_key' => $popup_options['activecampaign_api_key'],
						'api_output' => 'serialize',
						'email' => $_subscriber['{subscription-email}'],
						'tags' => (sizeof($tags) == 1 ? $tags[0] : $tags)
					);
					$url = str_replace('https://', 'http://', $popup_options['activecampaign_url']);
					$curl = curl_init($url.'/admin/api.php?api_action=contact_tag_add');
					curl_setopt($curl, CURLOPT_POST, 1);
					curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
					curl_setopt($curl, CURLOPT_TIMEOUT, 20);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
					curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
					curl_setopt($curl, CURLOPT_HEADER, 0);
					curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
					$response = curl_exec($curl);
					curl_close($curl);
				}
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_url']) || !isset($_POST['ulp_api_key']) || empty($_POST['ulp_url']) || empty($_POST['ulp_api_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API URL or API Key!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$url = trim(stripslashes($_POST['ulp_url']));
			$api_key = trim(stripslashes($_POST['ulp_api_key']));
			$request = http_build_query(array(
				'api_action' => 'list_list',
				'api_key' => $api_key,
				'api_output' => 'serialize',
				'ids' => 'all'
			));

			$url = str_replace('https://', 'http://', $url);
			$list_html = '';
			$lists = array();
			try {
				$curl = curl_init($url.'/admin/api.php?api_action=list_list');
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
				curl_setopt($curl, CURLOPT_TIMEOUT, 20);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				$response = curl_exec($curl);
				curl_close($curl);
				$result = unserialize($response);
				if (!is_array($result) || (isset($result['result_code']) && $result['result_code'] != 1)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid API URL or API Key!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
				foreach ($result as $key => $value) {
					if (is_array($value)) {
						$lists[$value['id']] = $value['name'];
					}
				}
			} catch (Exception $e) {
			}
			if (!empty($lists)) {
				foreach ($lists as $key => $list) {
					$list_html .= '<a href="#" data-id="'.esc_html($key).'" data-title="'.esc_html($key).(!empty($list) ? ' | '.esc_html($list) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($key).(!empty($list) ? ' | '.esc_html($list) : '').'</a>';
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
			if (!isset($_POST['ulp_url']) || !isset($_POST['ulp_api_key']) || !isset($_POST['ulp_list_id']) || empty($_POST['ulp_url']) || empty($_POST['ulp_api_key']) || empty($_POST['ulp_list_id'])) exit;
			$url = trim(stripslashes($_POST['ulp_url']));
			$api_key = trim(stripslashes($_POST['ulp_api_key']));
			$list_id = trim(stripslashes($_POST['ulp_list_id']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($url, $api_key, $list_id, $this->default_popup_options['activecampaign_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_url, $_key, $_list, $_fields) {
		//$result = $this->get_fields($_key, $_list);
		$request = http_build_query(array(
			'api_action' => 'list_field_view',
			'api_key' => $_key,
			'api_output' => 'serialize',
			'ids' => 'all'
		));
		$_url = str_replace('https://', 'http://', $_url);
		$values = unserialize($_fields);
		$fields = array();
		$fields_html = '';
		try {
			$curl = curl_init($_url.'/admin/api.php?api_action=list_field_view');
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
			curl_setopt($curl, CURLOPT_TIMEOUT, 20);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			$response = curl_exec($curl);
			curl_close($curl);
			$result = unserialize($response);
			if (!is_array($result) || (isset($result['result_code']) && $result['result_code'] != 1)) {
				return '<div class="ulp-activecampaign-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
			}
			foreach ($result as $field) {
				if (is_array($field) && array_key_exists('id', $field) && array_key_exists('lists', $field)) {
					if (in_array($_list, $field['lists']) || in_array(0, $field['lists'])) {
						$fields[$field['id']] = $field['title'];
					}
				}
			}
		} catch (Exception $e) {
		}
		if (!empty($fields)) {
			$fields_html = '
			<table style="min-width: 280px; width: 50%;">';
			foreach ($fields as $key => $field) {
				$fields_html .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field).':</strong></td>
					<td>
						<input type="text" id="ulp_activecampaign_field_'.esc_html($key).'" name="ulp_activecampaign_field_'.esc_html($key).'" value="'.esc_html(array_key_exists($key, $values) ? $values[$key] : '').'" class="widefat" />
						<br /><em>'.esc_html($field).'</em>
					</td>
				</tr>';
			}
			$fields_html .= '
			</table>';
		} else {
			$fields_html = '<div class="ulp-activecampaign-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
		}
		return $fields_html;
	}
}
$ulp_activecampaign = new ulp_activecampaign_class();
?>