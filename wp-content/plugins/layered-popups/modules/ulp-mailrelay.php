<?php
/* Mailrelay integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_mailrelay_class {
	var $default_popup_options = array(
		"mailrelay_enable" => "off",
		"mailrelay_hostname" => "",
		"mailrelay_api_key" => "",
		"mailrelay_groups" => "",
		"mailrelay_fields" => ""
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
			add_action('wp_ajax_ulp-mailrelay-groups', array(&$this, "show_groups"));
		}
		add_action('ulp_subscribe', array(&$this, 'subscribe'), 10, 2);
	}
	function popup_options_tabs($_tabs) {
		if (!array_key_exists("integration", $_tabs)) $_tabs["integration"] = __('Integration', 'ulp');
		return $_tabs;
	}
	function popup_options_show($_popup_options) {
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		$fields = unserialize($popup_options['mailrelay_fields']);
		echo '
				<h3>'.__('Mailrelay Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Mailrelay', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mailrelay_enable" name="ulp_mailrelay_enable" '.($popup_options['mailrelay_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Mailrelay', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Mailrelay.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Hostname', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-mailrelay-hostname" name="ulp_mailrelay_hostname" value="'.esc_html($popup_options['mailrelay_hostname']).'" class="widefat">
							<br /><em>'.__('Enter your Mailrelay Hostname. Usually it looks like: <code>username.ip-zone.com</code>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-mailrelay-api-key" name="ulp_mailrelay_api_key" value="'.esc_html($popup_options['mailrelay_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Mailrelay API Key. You can find it in your Mailrelay Control Panel (Right Side Menu >> Settings >> API Access).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Groups', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-mailrelay-groups-html">';
		if (!empty($popup_options['mailrelay_api_key']) && !empty($popup_options['mailrelay_hostname'])) {
			$groups = $this->get_groups_html($popup_options['mailrelay_api_key'], $popup_options['mailrelay_hostname'], $popup_options['mailrelay_groups']);
			echo $groups;
		}
		echo '
							</div>
							<a id="ulp_mailrelay_groups_button" class="ulp_button button-secondary" onclick="return ulp_mailrelay_loadgroups();">'.__('Load Groups', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-mailrelay-groups-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load groups of the list. Ignore if you do not use groups.', 'ulp').'</em>
							<script>
								function ulp_mailrelay_loadgroups() {
									jQuery("#ulp-mailrelay-groups-loading").fadeIn(350);
									jQuery(".ulp-mailrelay-groups-html").slideUp(350);
									var data = {action: "ulp-mailrelay-groups", ulp_key: jQuery("#ulp-mailrelay-api-key").val(), ulp_hostname: jQuery("#ulp-mailrelay-hostname").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-mailrelay-groups-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-mailrelay-groups-html").html(data.html);
												jQuery(".ulp-mailrelay-groups-html").slideDown(350);
											} else {
												jQuery(".ulp-mailrelay-groups-html").html("<div class=\'ulp-mailrelay-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Mailrelay server.', 'ulp').'</strong></div>");
												jQuery(".ulp-mailrelay-groups-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-mailrelay-groups-html").html("<div class=\'ulp-mailrelay-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Mailrelay server.', 'ulp').'</strong></div>");
											jQuery(".ulp-mailrelay-groups-html").slideDown(350);
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Custom Fields', 'ulp').':</th>
						<td>
							'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Mailrelay custom fields with the popup fields.', 'ulp').'
							<table style="width: 100%;">
								<tr>
									<td style="width: 200px;"><strong>'.__('Field ID', 'ulp').'</strong></td>
									<td><strong>'.__('Value', 'ulp').'</strong></td>
								</tr>';
		$i = 0;
		if (is_array($fields)) {
			foreach ($fields as $key => $value) {
				echo '									
								<tr>
									<td>
										<input type="text" name="ulp_mailrelay_fields_name[]" value="'.esc_html($key).'" class="widefat">
										<br /><em>'.($i > 0 ? '<a href="#" onclick="return ulp_mailrelay_remove_field(this);">'.__('Remove Custom Field', 'ulp').'</a>' : '').'</em>
									</td>
									<td>
										<input type="text" name="ulp_mailrelay_fields_value[]" value="'.esc_html($value).'" class="widefat">
									</td>
								</tr>';
				$i++;
			}
		}
		if ($i == 0) {
			echo '									
								<tr>
									<td>
										<input type="text" name="ulp_mailrelay_fields_name[]" value="" class="widefat">
									</td>
									<td>
										<input type="text" name="ulp_mailrelay_fields_value[]" value="" class="widefat">
									</td>
								</tr>';
		}
		echo '
								<tr style="display: none;" id="mailrelay-field-template">
									<td>
										<input type="text" name="ulp_mailrelay_fields_name[]" value="" class="widefat">
										<br /><em><a href="#" onclick="return ulp_mailrelay_remove_field(this);">'.__('Remove Custom Field', 'ulp').'</a></em>
									</td>
									<td>
										<input type="text" name="ulp_mailrelay_fields_value[]" value="" class="widefat">
									</td>
								</tr>
								<tr>
									<td colspan="2">
										'.__('You can find Field ID in your Mailrelay Control Panel (Right Side Menu >> Settings >> Custom Fields).', 'ulp').'
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<a href="#" class="button-secondary" onclick="return ulp_mailrelay_add_field(this);">'.__('Add Custom Field', 'ulp').'</a>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
				<script>
					function ulp_mailrelay_add_field(object) {
						jQuery("#mailrelay-field-template").before("<tr>"+jQuery("#mailrelay-field-template").html()+"</tr>");
						return false;
					}
					function ulp_mailrelay_remove_field(object) {
						var row = jQuery(object).parentsUntil("tr").parent();
						jQuery(row).fadeOut(300, function() {
							jQuery(row).remove();
						});
						return false;
					}
				</script>';
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
		if (isset($ulp->postdata["ulp_mailrelay_enable"])) $popup_options['mailrelay_enable'] = "on";
		else $popup_options['mailrelay_enable'] = "off";
		if ($popup_options['mailrelay_enable'] == 'on') {
			if (empty($popup_options['mailrelay_api_key'])) $errors[] = __('Invalid Mailrelay API Key.', 'ulp');
			if (empty($popup_options['mailrelay_hostname'])) $errors[] = __('Invalid Mailrelay Hostname.', 'ulp');
			else {
				if (substr($popup_options['mailrelay_hostname'], 0, 7) != "http://" && substr($popup_options['mailrelay_hostname'], 0, 8) != "https://") $popup_options['mailrelay_hostname'] = 'http://'.$popup_options['mailrelay_hostname'];
				$popup_options['mailrelay_hostname'] = parse_url($popup_options['mailrelay_hostname'], PHP_URL_HOST);
				if (empty($popup_options['mailrelay_hostname'])) $errors[] = __('Invalid Mailrelay Hostname.', 'ulp');
			}
			$group_selected = false;
			foreach($ulp->postdata as $key => $value) {
				if (substr($key, 0, strlen('ulp_mailrelay_group_')) == 'ulp_mailrelay_group_') {
					$group_selected = true;
					break;
				}
			}
			if (!$group_selected) $errors[] = __('Select at least one Mailrelay group.', 'ulp');
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
		
		$popup_options['mailrelay_hostname'] = strtolower($popup_options['mailrelay_hostname']);
		if (substr($popup_options['mailrelay_hostname'], 0, 7) != "http://" && substr($popup_options['mailrelay_hostname'], 0, 8) != "https://") $popup_options['mailrelay_hostname'] = 'http://'.$popup_options['mailrelay_hostname'];
		$popup_options['mailrelay_hostname'] = parse_url($popup_options['mailrelay_hostname'], PHP_URL_HOST);
				
		if (isset($ulp->postdata["ulp_mailrelay_enable"])) $popup_options['mailrelay_enable'] = "on";
		else $popup_options['mailrelay_enable'] = "off";
		$groups = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_mailrelay_group_')) == 'ulp_mailrelay_group_') {
				$groups[] = substr($key, strlen('ulp_mailrelay_group_'));
			}
		}
		$popup_options['mailrelay_groups'] = implode(':', $groups);
		if (is_array($ulp->postdata["ulp_mailrelay_fields_name"]) && is_array($ulp->postdata["ulp_mailrelay_fields_value"])) {
			$fields = array();
			for($i=0; $i<sizeof($ulp->postdata["ulp_mailrelay_fields_name"]); $i++) {
				$key = stripslashes(trim($ulp->postdata['ulp_mailrelay_fields_name'][$i]));
				$value = stripslashes(trim($ulp->postdata['ulp_mailrelay_fields_value'][$i]));
				if (!empty($key)) $fields[$key] = $value;
			}
			if (!empty($fields)) $popup_options['mailrelay_fields'] = serialize($fields);
			else $popup_options['mailrelay_fields'] = '';
		} else $popup_options['mailrelay_fields'] = '';
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['mailrelay_enable'] == 'on') {
			$mailrelay_url = 'https://'.$popup_options['mailrelay_hostname'].'/ccm/admin/api/version/2/&type=json';
			$data = array(
				'function' => 'getSubscribers',
				'apiKey' => $popup_options['mailrelay_api_key'],
				'email' => $_subscriber['{subscription-email}']
			);
			$request = json_encode($data);
			try {
				$curl = curl_init($mailrelay_url);
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

				$response = curl_exec($curl);
				curl_close($curl);
				$result = json_decode($response, true);
			} catch (Exception $e) {
			}
			if (empty($result)) return;
			if (array_key_exists('status', $result) && $result['status'] == '0') return;
			if (sizeof($result['data']) > 0) {
				$data = array(
					'function' => 'updateSubscriber',
					'apiKey' => $popup_options['mailrelay_api_key'],
					'id' => $result['data'][0]['id'],
					'email' => $_subscriber['{subscription-email}'],
					'name' => $_subscriber['{subscription-name}']
				);
				$add_groups = explode(':',$popup_options['mailrelay_groups']);
				if (is_array($result['data'][0]['groups'])) $data['groups'] = array_merge($result['data'][0]['groups'], $add_groups);
				else $data['groups'] = $add_groups;
				if (is_array($result['data'][0]['fields'])) $data['customFields'] = $result['data'][0]['fields'];
				$fields = unserialize($popup_options['mailrelay_fields']);
				if (is_array($fields)) {
					foreach ($fields as $key => $value) {
						if (!empty($value)) {
							$data['customFields']['f_'.$key] = strtr($value, $_subscriber);
						}
					}
				}
				$request = json_encode($data);
				try {
					$curl = curl_init($mailrelay_url);
					curl_setopt($curl, CURLOPT_POST, 1);
					curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
					curl_setopt($curl, CURLOPT_TIMEOUT, 10);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
					curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
					//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
					curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

					$response = curl_exec($curl);
					curl_close($curl);
					$result = json_decode($response, true);
				} catch (Exception $e) {
				}
			} else {
				$data = array(
					'function' => 'addSubscriber',
					'apiKey' => $popup_options['mailrelay_api_key'],
					'email' => $_subscriber['{subscription-email}'],
					'name' => $_subscriber['{subscription-name}']
				);
				$data['groups'] = explode(':',$popup_options['mailrelay_groups']);
				$fields = unserialize($popup_options['mailrelay_fields']);
				if (is_array($fields)) {
					foreach ($fields as $key => $value) {
						$data['customFields']['f_'.$key] = strtr($value, $_subscriber);
					}
				}
				$request = json_encode($data);
				try {
					$curl = curl_init($mailrelay_url);
					curl_setopt($curl, CURLOPT_POST, 1);
					curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
					curl_setopt($curl, CURLOPT_TIMEOUT, 10);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
					curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
					//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
					curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

					$response = curl_exec($curl);
					curl_close($curl);
					$result = json_decode($response, true);
				} catch (Exception $e) {
				}
			}
		}
	}
	function show_groups() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_key']) || !isset($_POST['ulp_hostname']) || empty($_POST['ulp_key']) || empty($_POST['ulp_hostname'])) exit;
			$key = trim(stripslashes($_POST['ulp_key']));
			$hostname = strtolower(trim(stripslashes($_POST['ulp_hostname'])));
			if (substr($hostname, 0, 7) != "http://" && substr($hostname, 0, 8) != "https://") $hostname = 'http://'.$hostname;
			$hostname = parse_url($hostname, PHP_URL_HOST);
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_groups_html($key, $hostname, '');
			echo json_encode($return_object);
		}
		exit;
	}
	function get_groups_html($_key, $_hostname, $_groups) {
		if (empty($_hostname)) {
			return '<div class="ulp-mailrelay-grouping" style="margin-bottom: 10px;"><strong>'.__('Internal error! Can not connect to Mailrelay server.', 'ulp').'</strong></div>';
		}
		$result = $this->get_groups($_key, $_hostname);
		$groups = '';
		$groups_marked = explode(':', $_groups);
		if (!empty($result)) {
			if (array_key_exists('status', $result) && $result['status'] == '0') {
				$groups = '<div class="ulp-mailrelay-grouping" style="margin-bottom: 10px;"><strong>'.$result['error'].'</strong></div>';
			} else {
				if (sizeof($result['data']) > 0) {
					$groups .= '<div class="ulp-mailrelay-grouping" style="margin-bottom: 10px;">';
					foreach ($result['data'] as $group) {
						$groups .= '<div class="ulp-mailrelay-group" style="margin: 1px 0 1px 10px;"><input type="checkbox" name="ulp_mailrelay_group_'.$group['id'].'"'.(in_array($group['id'], $groups_marked) ? ' checked="checked"' : '').' /> '.$group['name'].'</div>';
					}
					$groups .= '</div>';
				}
			}
		} else {
			$groups = '<div class="ulp-mailrelay-grouping" style="margin-bottom: 10px;"><strong>'.__('Internal error! Can not connect to Mailrelay server.', 'ulp').'</strong></div>';
		}
		return $groups;
	}
	function get_groups($_key, $_hostname) {
		$result = array();
		$mailrelay_url = 'https://'.$_hostname.'/ccm/admin/api/version/2/&type=json';
		$data = array(
			'function' => 'getGroups',
			'apiKey' => $_key,
			'offset' => 0,
			'count' => 100
		);
		$request = json_encode($data);
		try {
			$curl = curl_init($mailrelay_url);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
			//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

			$response = curl_exec($curl);
			curl_close($curl);
			$result = json_decode($response, true);
		} catch (Exception $e) {
		}
		return $result;
	}
}
$ulp_mailrelay = new ulp_mailrelay_class();
?>