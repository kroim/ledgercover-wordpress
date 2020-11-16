<?php
/* MPZ Mail integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_mpzmail_class {
	var $default_popup_options = array(
		'mpzmail_enable' => 'off',
		'mpzmail_api_key' => '',
		'mpzmail_group' => '',
		'mpzmail_group_id' => '',
		'mpzmail_fields' => array(
			'email' => '{subscription-email}',
			'firstName' => '{subscription-name}',
			'lastName' => '',
			'companyName' => '',
			'customField1' => '',
			'customField2' => '',
			'customField3' => '',
			'customField4' => '',
			'customField5' => '',
			'customField6' => '',
			'customField7' => '',
			'customField8' => '',
			'customField9' => '',
			'customField10' => '',
			'address1' => '',
			'address2' => '',
			'town' => '',
			'county' => '',
			'country' => '',
			'zip' => ''
		)
	);
	var $field_labels = array(
		'email' => array('title' => 'Email', 'description' => 'Email of the new subscriber.'),
		'firstName' => array('title' => 'First name', 'description' => 'First name of the new subscriber.'),
		'lastName' => array('title' => 'Last name', 'description' => 'Last name of the new subscriber.'),
		'companyName' => array('title' => 'Company', 'description' => 'Company name of the new subscriber.'),
		'customField1' => array('title' => 'Custom Field 1', 'description' => 'Custom Field 1.'),
		'customField2' => array('title' => 'Custom Field 2', 'description' => 'Custom Field 2.'),
		'customField3' => array('title' => 'Custom Field 3', 'description' => 'Custom Field 3.'),
		'customField4' => array('title' => 'Custom Field 4', 'description' => 'Custom Field 4.'),
		'customField5' => array('title' => 'Custom Field 5', 'description' => 'Custom Field 5.'),
		'customField6' => array('title' => 'Custom Field 6', 'description' => 'Custom Field 6.'),
		'customField7' => array('title' => 'Custom Field 7', 'description' => 'Custom Field 7.'),
		'customField8' => array('title' => 'Custom Field 8', 'description' => 'Custom Field 8.'),
		'customField9' => array('title' => 'Custom Field 9', 'description' => 'Custom Field 9.'),
		'customField10' => array('title' => 'Custom Field 10', 'description' => 'Custom Field 10.'),
		'address1' => array('title' => 'Address 1', 'description' => 'Address 1 of the new subscriber.'),
		'address2' => array('title' => 'Address 2', 'description' => 'Address 2 of the new subscriber.'),
		'town' => array('title' => 'Town', 'description' => 'Town of the new subscriber.'),
		'county' => array('title' => 'County', 'description' => 'County or province of the new subscriber.'),
		'country' => array('title' => 'Country', 'description' => 'Country of the new subscriber.'),
		'zip' => array('title' => 'Postal code', 'description' => 'ZIP or postal code of the new subscriber.')
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-mpzmail-groups', array(&$this, "show_groups"));
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
				<h3>'.__('MPZ Mail Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable MPZ Mail', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mpzmail_enable" name="ulp_mpzmail_enable" '.($popup_options['mpzmail_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to MPZ Mail', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to MPZ Mail.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mpzmail_api_key" name="ulp_mpzmail_api_key" value="'.esc_html($popup_options['mpzmail_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your MPZ Mail API Key. Go to your MPZ Mail account, click "Account Settings" and "Overview".', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Group ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-mpzmail-group" name="ulp_mpzmail_group" value="'.esc_html($popup_options['mpzmail_group']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_mpzmail_groups_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-mpzmail-group-id" name="ulp_mpzmail_group_id" value="'.esc_html($popup_options['mpzmail_group_id']).'" />
							<div id="ulp-mpzmail-group-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your Group ID.', 'ulp').'</em>
							<script>
								function ulp_mpzmail_groups_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-mpzmail-groups", "ulp_api_key": jQuery("#ulp_mpzmail_api_key").val()});
								}
							</script>
						</td>
					</tr>';
		$fields = $popup_options['mpzmail_fields'];
		if (is_array($fields)) $fields = array_merge($this->default_popup_options['mpzmail_fields'], $fields);
		else $fields = $this->default_popup_options['mpzmail_fields'];
		echo '
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-mpzmail-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate MPZ Mail fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
		foreach ($this->default_popup_options['mpzmail_fields'] as $key => $value) {
			echo '
									<tr>
										<td style="width: 100px;"><strong>'.esc_html($this->field_labels[$key]['title']).':</strong></td>
										<td>
											<input type="text" id="ulp_mpzmail_field_'.esc_html($key).'" name="ulp_mpzmail_field_'.esc_html($key).'" value="'.esc_html($fields[$key]).'" class="widefat"'.($key == 'email' ? ' readonly="readonly"' : '').' />
											<br /><em>'.esc_html($this->field_labels[$key]['description']).'</em>
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
		if (isset($ulp->postdata["ulp_mpzmail_enable"])) $popup_options['mpzmail_enable'] = "on";
		else $popup_options['mpzmail_enable'] = "off";
		if ($popup_options['mpzmail_enable'] == 'on') {
			if (empty($popup_options['mpzmail_api_key'])) $errors[] = __('Invalid MPZ Mail API key', 'ulp');
			if (empty($popup_options['mpzmail_group_id'])) $errors[] = __('Invalid MPZ Mail Group ID', 'ulp');
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
		if (isset($ulp->postdata["ulp_mpzmail_enable"])) $popup_options['mpzmail_enable'] = "on";
		else $popup_options['mpzmail_enable'] = "off";
		$fields = array();
		foreach($this->default_popup_options['mpzmail_fields'] as $key => $value) {
			if (isset($ulp->postdata['ulp_mpzmail_field_'.$key])) {
				$fields[$key] = stripslashes(trim($ulp->postdata['ulp_mpzmail_field_'.$key]));
			}
		}
		$popup_options['mpzmail_fields'] = $fields;
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['mpzmail_enable'] == 'on') {
			$data = '<xml>
	<apiKey>'.esc_html($popup_options['mpzmail_api_key']).'</apiKey>
	<groupID>'.$popup_options['mpzmail_group_id'].'</groupID>
	<subscribers>
		<subscriber>
			<email>'.esc_html($_subscriber['{subscription-email}']).'</email>';
			$fields = $popup_options['mpzmail_fields'];
			if (is_array($fields)) $fields = array_merge($this->default_popup_options['mpzmail_fields'], $fields);
			else $fields = $this->default_popup_options['mpzmail_fields'];
			foreach ($fields as $key => $value) {
				if (!empty($value) && $key != 'email') {
					$data .= '
			<'.$key.'>'.esc_html(strtr($value, $_subscriber)).'</'.$key.'>';
				}
			}
			$data .= '
			<isActive>0</isActive>
		</subscriber>
	</subscribers>
</xml>';
			$result = $this->connect('subscribers/addSubscribers/', $data);
		}
	}
	function show_groups() {
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

			$data = '<xml><apiKey>'.esc_html($api_key).'</apiKey></xml>';
			$lists = array();
			$result = $this->connect('groups/listGroups/', $data);
			if ($result) {
				$p = xml_parser_create();
				if (xml_parse_into_struct($p, $result, $values, $index)) {
					if (isset($index['ERROR'])) {
						if ($values[$index['ERROR'][0]]['value'] != 0) {
							$return_object = array();
							$return_object['status'] = 'OK';
							$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.ucfirst($values[$index['STATUS'][0]]['value']).'</div>';
							echo json_encode($return_object);
							exit;
						} else {
							if ($values[$index['GROUPCNT'][0]]['value'] > 0) {
								if (isset($index['GROUPID'])) {
									foreach ($index['GROUPID'] as $idx => $value_idx) {
										$lists[$values[$value_idx]['value']] = $values[$index['GROUPNAME'][$idx]]['value'];
									}
								}
							} else {
								$return_object = array();
								$return_object['status'] = 'OK';
								$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No groups found!', 'ulp').'</div>';
								echo json_encode($return_object);
								exit;
							}
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
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to MPZ Mail Server!', 'ulp').'</div>';
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
	function connect($_path, $_data = array(), $_method = '') {
		$url = 'https://mpzmail.com/api/v3.0/'.ltrim($_path, '/');
		try {
			$curl = curl_init($url);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $_data);
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
		} catch (Exception $e) {
			$response = false;
		}
		return $response;
	}
}
$ulp_mpzmail = new ulp_mpzmail_class();
?>