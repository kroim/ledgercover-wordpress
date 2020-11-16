<?php
/* SALESmanago integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_salesmanago_class {
	var $default_popup_options = array(
		'salesmanago_enable' => 'off',
		'salesmanago_owner' => '',
		'salesmanago_client_id' => '',
		'salesmanago_api_secret' => '',
		'salesmanago_fields' => '',
		'salesmanago_tags' => array(),
		'salesmanago_newtags' => ''
	);
	var $fields = array(
		'email' => '{subscription-email}',
		'name' => '{subscription-name}',
		'phone' => '{subscription-phone}',
		'fax' => '',
		'streetAddress' => '',
		'city' => '',
		'zipCode' => '',
		'country' => '',
		'company' => ''
	);
	var $field_labels = array(
		'email' => array('title' => 'E-mail', 'description' => 'E-mail address of contact/recipient.'),
		'name' => array('title' => 'Name', 'description' => 'Name of the contact.'),
		'phone' => array('title' => 'Phone #', 'description' => 'Phone number of the contact.'),
		'fax' => array('title' => 'Fax #', 'description' => 'Fax number of the contact.'),
		'streetAddress' => array('title' => 'Street address', 'description' => 'Address of the contact.'),
		'city' => array('title' => 'City', 'description' => 'City of the contact.'),
		'country' => array('title' => 'Country', 'description' => 'Country of the contact.'),
		'zipCode' => array('title' => 'Postal code', 'description' => 'ZIP or postal code of the contact.'),
		'company' => array('title' => 'Company', 'description' => 'Organization name the contact works for.')
	);
	function __construct() {
		$this->default_popup_options['salesmanago_fields'] = serialize($this->fields);
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-salesmanago-tags', array(&$this, "show_tags"));
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
				<h3>'.__('SALESmanago Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable SALESmanago', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_salesmanago_enable" name="ulp_salesmanago_enable" '.($popup_options['salesmanago_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to SALESmanago', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to SALESmanago.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Owner E-mail', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_salesmanago_owner" name="ulp_salesmanago_owner" value="'.esc_html($popup_options['salesmanago_owner']).'" class="widefat">
							<br /><em>'.__('Enter your SALESmanago account e-mail address.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Client ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_salesmanago_client_id" name="ulp_salesmanago_client_id" value="'.esc_html($popup_options['salesmanago_client_id']).'" class="widefat">
							<br /><em>'.__('Enter your Client ID. You can find it <a target="_blank" href="https://www.salesmanago.com/settings/integration.htm">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Secret', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_salesmanago_api_secret" name="ulp_salesmanago_api_secret" value="'.esc_html($popup_options['salesmanago_api_secret']).'" class="widefat">
							<br /><em>'.__('Enter your API Secret. You can find it <a target="_blank" href="https://www.salesmanago.com/settings/integration.htm">here</a>.', 'ulp').'</em>
						</td>
					</tr>';
		if ($ulp->ext_options['enable_customfields'] == 'on') {
			$fields = unserialize($popup_options['salesmanago_fields']);
			if (is_array($fields)) $fields = array_merge($this->fields, $fields);
			else $fields = $this->fields;
			echo '
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-salesmanago-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate SALESmanago fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
			foreach ($this->fields as $key => $value) {
				echo '
									<tr>
										<td style="width: 100px;"><strong>'.esc_html($this->field_labels[$key]['title']).':</strong></td>
										<td>
											<input type="text" id="ulp_salesmanago_field_'.esc_html($key).'" name="ulp_salesmanago_field_'.esc_html($key).'" value="'.esc_html($fields[$key]).'" class="widefat"'.($key == 'email' ? ' readonly="readonly"' : '').' />
											<br /><em>'.esc_html($this->field_labels[$key]['description']).'</em>
										</td>
									</tr>';
			}
			echo '
								</table>
							</div>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th>'.__('Existing Tags', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-salesmanago-tags-html">';
		if (!empty($popup_options['salesmanago_owner']) && !empty($popup_options['salesmanago_client_id']) && !empty($popup_options['salesmanago_api_secret'])) {
			$tags = $this->get_tags_html($popup_options['salesmanago_owner'], $popup_options['salesmanago_client_id'], $popup_options['salesmanago_api_secret'], $popup_options['salesmanago_tags']);
			echo $tags;
		}
		echo '
							</div>
							<a id="ulp_salesmanago_tags_button" class="ulp_button button-secondary" onclick="return ulp_salesmanago_loadtags();">'.__('Load Tags', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-salesmanago-tags-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('You can tag contact with certain SALESmanago tags. Click the button to (re)load the list of tags.', 'ulp').'</em>
							<script>
								function ulp_salesmanago_loadtags() {
									jQuery("#ulp-salesmanago-tags-loading").fadeIn(350);
									jQuery(".ulp-salesmanago-tags-html").slideUp(350);
									var data = {action: "ulp-salesmanago-tags", ulp_owner: jQuery("#ulp_salesmanago_owner").val(), ulp_client_id: jQuery("#ulp_salesmanago_client_id").val(), ulp_api_secret: jQuery("#ulp_salesmanago_api_secret").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-salesmanago-tags-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-salesmanago-tags-html").html(data.html);
												jQuery(".ulp-salesmanago-tags-html").slideDown(350);
											} else {
												jQuery(".ulp-salesmanago-tags-html").html("<div class=\'ulp-salesmanago-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to SALESmanago server.', 'ulp').'</strong></div>");
												jQuery(".ulp-salesmanago-tags-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-salesmanago-tags-html").html("<div class=\'ulp-salesmanago-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to SALESmanago server.', 'ulp').'</strong></div>");
											jQuery(".ulp-salesmanago-tags-html").slideDown(350);
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('New Tags', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_salesmanago_newtags" name="ulp_salesmanago_newtags" value="'.esc_html($popup_options['salesmanago_newtags']).'" class="widefat">
							<br /><em>'.__('If you want to tag contact with new tags, drop them here (comma-separated string).', 'ulp').'</em>
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
		if (isset($ulp->postdata["ulp_salesmanago_enable"])) $popup_options['salesmanago_enable'] = "on";
		else $popup_options['salesmanago_enable'] = "off";
		if ($popup_options['salesmanago_enable'] == 'on') {
			if (empty($popup_options['salesmanago_owner'])) $errors[] = __('Invalid SALESmanago Owner e-mail address.', 'ulp');
			if (empty($popup_options['salesmanago_client_id'])) $errors[] = __('Invalid SALESmanago Site URL.', 'ulp');
			if (empty($popup_options['salesmanago_api_secret'])) $errors[] = __('Invalid SALESmanago API Secret.', 'ulp');
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
		if (isset($ulp->postdata["ulp_salesmanago_enable"])) $popup_options['salesmanago_enable'] = "on";
		else $popup_options['salesmanago_enable'] = "off";
		if ($ulp->ext_options['enable_customfields'] == 'on') {
			$fields = array();
			foreach($this->fields as $key => $value) {
				if (isset($ulp->postdata['ulp_salesmanago_field_'.$key])) {
					$fields[$key] = stripslashes(trim($ulp->postdata['ulp_salesmanago_field_'.$key]));
				}
			}
			$popup_options['salesmanago_fields'] = serialize($fields);
		}
		$tags = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_salesmanago_tag_')) == 'ulp_salesmanago_tag_') {
				$tags[] = $ulp->postdata['ulp_salesmanago_tagvalue_'.substr($key, strlen('ulp_salesmanago_tag_'))];
			}
		}
		$popup_options['salesmanago_tags'] = $tags;
		
		$new_tags = explode(',', $ulp->postdata['ulp_salesmanago_newtags']);
		foreach($new_tags as $key => $value) {
			$new_tags[$key] = trim($value);
			if (empty($new_tags[$key])) unset($new_tags[$key]);
		}
		$popup_options['salesmanago_newtags'] = implode(', ', $new_tags);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['salesmanago_enable'] == 'on') {
			$data = array(
				'forceOptIn' => true
			);
			$tags = $popup_options['salesmanago_tags'];
			$new_tags = explode(',', $popup_options['salesmanago_newtags']);
			foreach ($new_tags as $tag) {
				$tag = trim($tag);
				if (!empty($tag) && !in_array($tag, $tags)) {
					$tags[] = $tag;
				}
			}
			$data['tags'] = $tags;
			if ($ulp->ext_options['enable_customfields'] == 'on') {
				$fields = unserialize($_popup_options['salesmanago_fields']);
				if (is_array($fields)) $fields = array_merge($this->fields, $fields);
				else $fields = $this->fields;
				foreach ($fields as $key => $value) {
					if (!empty($value) && $key != 'email') {
						if ($key == 'streetAddress' || $key == 'zipCode' || $key == 'country' || $key == 'city') {
							$data['contact']['address'][$key] = strtr($value, $_subscriber);
						} else $data['contact'][$key] = strtr($value, $_subscriber);
					}
				}
				$data['contact']['email'] = $_subscriber['{subscription-email}'];
			} else {
				$data['contact'] = array(
					'email' => $_subscriber['{subscription-email}'],
					'name' => $_subscriber['{subscription-name}']
				);
				if (!empty($_subscriber['{subscription-phone}'])) $data['phone'] = $_subscriber['{subscription-phone}'];
			}
			$data['contact']['state'] = 'CUSTOMER';

			$result = $this->connect($popup_options['salesmanago_owner'], $popup_options['salesmanago_client_id'], $popup_options['salesmanago_api_secret'], '/contact/upsert', $data);
		}
	}
	function show_tags() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$return_object = array();
			$return_object['status'] = 'OK';
			if (!isset($_POST['ulp_owner']) || !isset($_POST['ulp_client_id']) || !isset($_POST['ulp_api_secret']) || empty($_POST['ulp_owner']) || empty($_POST['ulp_client_id']) || empty($_POST['ulp_api_secret'])) {
				$return_object['html'] = '<div class="ulp-salesmanago-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Credentials!', 'ulp').'</strong></div>';
			} else {
				$owner = trim(stripslashes($_POST['ulp_owner']));
				$client_id = trim(stripslashes($_POST['ulp_client_id']));
				$api_secret = trim(stripslashes($_POST['ulp_api_secret']));
				$return_object['html'] = $this->get_tags_html($owner, $client_id, $api_secret, array());
			}
		}
		echo json_encode($return_object);
		exit;
	}
	function get_tags_html($_owner, $_client_id, $_api_secret, $_tags) {
		$result = $this->connect($_owner, $_client_id, $_api_secret, '/contact/tags', array("showSystemTags" => true));
		$tags = '';
		if (!empty($result)) {
			if (array_key_exists('error', $result)) {
				$tags = '<div class="ulp-salesmanago-grouping" style="margin-bottom: 10px;"><strong>'.rtrim($result['error'], '.').'.</strong></div>';
			} else if (array_key_exists('success', $result)) {
				if (!$result['success']) {
					$tags = '<div class="ulp-salesmanago-grouping" style="margin-bottom: 10px;"><strong>'.rtrim($result['message'][0], '.').'.</strong></div>';
				} else {
					if (empty($result['tags'])) {
						$tags .= '<div class="ulp-salesmanago-grouping" style="margin-bottom: 10px;"><strong>'.__('Tags not found.', 'ulp').'</strong></div>';
					} else {
						$tags .= '<div class="ulp-salesmanago-grouping" style="margin-bottom: 10px;">';
						$i = 0;
						foreach ($result['tags'] as $tag) {
							$tags .= '<div class="ulp-salesmanago-tag" style="margin: 1px 0 1px 10px;"><input type="hidden" name="ulp_salesmanago_tagvalue_'.$i.'" value="'.$tag['tag'].'" /><input type="checkbox" name="ulp_salesmanago_tag_'.$i.'"'.(in_array($tag['tag'], $_tags) ? ' checked="checked"' : '').' /> '.$tag['tag'].'</div>';
							$i++;
						}
						$tags .= '</div>';
					}
				}
			} else {
				$tags = '<div class="ulp-salesmanago-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not connect SALESmanago API server.', 'ulp').'</strong></div>';
			}
		} else {
			$tags = '<div class="ulp-salesmanago-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not connect SALESmanago API server.', 'ulp').'</strong></div>';
		}
		return $tags;
	}
	function connect($_owner, $_client_id, $_api_secret, $_path, $_data = array(), $_method = '') {
		$url = 'http://www.salesmanago.pl/api/'.ltrim($_path, '/');
		$headers = array(
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		$data = array_merge($_data, array(
			'clientId' => $_client_id,
			'apiKey' => 'LayeredPopupsRules',
			'requestTime' => time(),
			'sha' => sha1('LayeredPopupsRules'.$_client_id.$_api_secret),
			'owner' => $_owner
		));
		try {
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
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
			if ($http_code == 200) $result = json_decode($response, true);
			else $result = array('error' => __('Can not connect to SALESmanago server.', 'ulp'));
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
}
$ulp_salesmanago = new ulp_salesmanago_class();
?>