<?php
/* CleverReach integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_cleverreach_class {
	var $default_popup_options = array(
		"cleverreach_enable" => "off",
		"cleverreach_client_id" => "",
		"cleverreach_client_secret" => "",
		"cleverreach_access_token" => "",
		"cleverreach_list" => "",
		"cleverreach_list_id" => "",
		"cleverreach_fields" => array(),
		"cleverreach_globalfields" => array(),
		"cleverreach_tags" => array()
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-cleverreach-lists', array(&$this, "show_lists"));
			add_action('wp_ajax_ulp-cleverreach-fields', array(&$this, "show_fields"));
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
				<h3>'.__('CleverReach Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable CleverReach', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_cleverreach_enable" name="ulp_cleverreach_enable" '.($popup_options['cleverreach_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to CleverReach', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to CleverReach.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Client ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_cleverreach_client_id" name="ulp_cleverreach_client_id" value="'.esc_html($popup_options['cleverreach_client_id']).'" class="widefat">
							<br /><em>'.__('Enter Client ID of your OAuth App. Please go to CleverReach account >> My Account >> Extras >> REST API and click "Create OAuth". After that click created app and find Client ID there.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Client Secret', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_cleverreach_client_secret" name="ulp_cleverreach_client_secret" value="'.esc_html($popup_options['cleverreach_client_secret']).'" class="widefat">
							<br /><em>'.__('Enter Client Secret of your OAuth App. Find it the same way as Client ID.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID:', 'ulp').'</th>
						<td>
							<input type="text" id="ulp-cleverreach-list" name="ulp_cleverreach_list" value="'.esc_html($popup_options['cleverreach_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_cleverreach_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-cleverreach-list-id" name="ulp_cleverreach_list_id" value="'.esc_html($popup_options['cleverreach_list_id']).'" />
							<div id="ulp-cleverreach-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_cleverreach_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-cleverreach-lists", "client-id": jQuery("#ulp_cleverreach_client_id").val(), "client-secret": jQuery("#ulp_cleverreach_client_secret").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-cleverreach-fields-html">';
		if (!empty($popup_options['cleverreach_client_id']) && !empty($popup_options['cleverreach_client_secret']) && !empty($popup_options['cleverreach_list_id'])) {
			$fields = $this->get_fields_html($popup_options['cleverreach_client_id'], $popup_options['cleverreach_client_secret'], $popup_options['cleverreach_list_id'], $popup_options['cleverreach_fields'], $popup_options['cleverreach_globalfields']);
			echo $fields;
		}
		echo '
							</div>
							<a class="ulp-button ulp-button-small" onclick="return ulp_cleverreach_loadfields(this);"><i class="fas fa-check"></i><label>'.__('Load Fields', 'ulp').'</label></a>
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_cleverreach_loadfields(_object) {
									if (ulp_saving) return;
									ulp_saving = true;
									jQuery(_object).addClass("ulp-button-disabled");
									jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
									jQuery(".ulp-cleverreach-fields-html").slideUp(350);
									var post_data = {"action": "ulp-cleverreach-fields", "client-id": jQuery("#ulp_cleverreach_client_id").val(), "client-secret": jQuery("#ulp_cleverreach_client_secret").val(), "list-id": jQuery("#ulp-cleverreach-list-id").val()};
									jQuery.ajax({
										type	: "POST",
										url		: "'.admin_url('admin-ajax.php').'", 
										data	: post_data,
										success	: function(return_data) {
											jQuery(_object).removeClass("ulp-button-disabled");
											jQuery(_object).find("i").attr("class", "fas fa-check");
											var data;
											try {
												if (typeof return_data == "object") data = return_data;
												else data = jQuery.parseJSON(return_data);
												if (data.status == "OK") {
													jQuery(".ulp-cleverreach-fields-html").html(data.html);
													jQuery(".ulp-cleverreach-fields-html").slideDown(350);
												} else if (data.status == "ERROR") {
													ulp_global_message_show("danger", data.message);
												} else {
													ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
												}
											} catch(error) {
												ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
											}
											ulp_saving = false;
										},
										error	: function(XMLHttpRequest, textStatus, errorThrown) {
											jQuery(_object).removeClass("ulp-button-disabled");
											jQuery(_object).find("i").attr("class", "fas fa-check");
											ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
											ulp_saving = false;
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
							<input type="text" id="ulp_cleverreach_tags" name="ulp_cleverreach_tags" value="'.esc_html(implode(', ', $popup_options['cleverreach_tags'])).'" class="widefat">
							<br /><em>'.__('Comma-separated list of tags.', 'ulp').'</em>
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
		if (isset($ulp->postdata["ulp_cleverreach_enable"])) $popup_options['cleverreach_enable'] = "on";
		else $popup_options['cleverreach_enable'] = "off";
		if ($popup_options['cleverreach_enable'] == 'on') {
			if (empty($popup_options['cleverreach_client_id'])) $errors[] = __('Invalid CleverReach Client ID.', 'ulp');
			if (empty($popup_options['cleverreach_client_secret'])) $errors[] = __('Invalid CleverReach Client Secret.', 'ulp');
			if (empty($popup_options['cleverreach_list_id'])) $errors[] = __('Invalid CleverReach List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_cleverreach_enable"])) $popup_options['cleverreach_enable'] = "on";
		else $popup_options['cleverreach_enable'] = "off";
		
		$popup_options['cleverreach_fields'] = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_cleverreach_field_')) == 'ulp_cleverreach_field_') {
				$field = substr($key, strlen('ulp_cleverreach_field_'));
				$popup_options['cleverreach_fields'][$field] = stripslashes(trim($value));
			}
		}
		$popup_options['cleverreach_globalfields'] = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_cleverreach_globalfield_')) == 'ulp_cleverreach_globalfield_') {
				$field = substr($key, strlen('ulp_cleverreach_globalfield_'));
				$popup_options['cleverreach_globalfields'][$field] = stripslashes(trim($value));
			}
		}

		$tags_raw = explode(',', $ulp->postdata['ulp_cleverreach_tags']);
		$popup_options['cleverreach_tags'] = array();
		foreach($tags_raw as $tag_raw) {
			$tag_raw = trim($tag_raw);
			if (!empty($tag_raw)) $popup_options['cleverreach_tags'][] = $tag_raw;
		}
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['cleverreach_enable'] == 'on') {
			$post_data = array(
				"email" => $_subscriber['{subscription-email}'],
				"registered" => time(),
				"activated" => time(),
				"source" => $popup_options['title'],
				"tags" => $popup_options['cleverreach_tags']
			);
			if (!empty($popup_options['cleverreach_fields']) && is_array($popup_options['cleverreach_fields'])) {
				foreach ($popup_options['cleverreach_fields'] as $key => $value) {
					if (!empty($value)) {
						$post_data['attributes'][$key] = strtr($value, $_subscriber);
					}
				}
			}
			if (!empty($popup_options['cleverreach_globalfields']) && is_array($popup_options['cleverreach_globalfields'])) {
				foreach ($popup_options['cleverreach_globalfields'] as $key => $value) {
					if (!empty($value)) {
						$post_data['global_attributes'][$key] = strtr($value, $_subscriber);
					}
				}
			}
			$token = $this->_get_token($popup_options['cleverreach_client_id'], $popup_options['cleverreach_client_secret']);
			if (!empty($token) && is_array($token) && array_key_exists('access_token', $token)) {
				$result = $this->_connect($token['access_token'], 'groups.json/'.$popup_options['cleverreach_list_id'].'/receivers/upsert', array($post_data));
			}
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['client-id']) || empty($_POST['client-id']) || !isset($_POST['client-secret']) || empty($_POST['client-secret'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid OAuth Credentials.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$client_id = trim(stripslashes($_POST['client-id']));
			$client_secret = trim(stripslashes($_POST['client-secret']));
			$lists = array();
			$token = $this->_get_token($client_id, $client_secret);
			if (!empty($token) && is_array($token)) {
				if (array_key_exists('access_token', $token)) {
					$result = $this->_connect($token['access_token'], 'groups.json');
					if (is_array($result)) {
						if (array_key_exists('error', $result)) {
							$return_object = array();
							$return_object['status'] = 'OK';
							$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.esc_html($result['error']['message']).'</div>';
							echo json_encode($return_object);
							exit;
						} else if (sizeof($result) == 0) {
							$return_object = array();
							$return_object['status'] = 'OK';
							$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Lists not found.', 'ulp').'</div>';
							echo json_encode($return_object);
							exit;
						} else {
							foreach($result as $list) {
								if (is_array($list)) {
									if (array_key_exists('id', $list) && array_key_exists('name', $list)) {
										$lists[$list['id']] = $list['name'];
									}
								}
							}
						}
					} else {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Unexpected server response.', 'ulp').'</div>';
						echo json_encode($return_object);
						exit;
					}
				} else if (array_key_exists('error_description', $token)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.esc_html($token['error_description']).'</div>';
					echo json_encode($return_object);
					exit;
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Unexpected server response.', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Unexpected server response.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$list_html = '';
			if (!empty($lists)) {
				foreach ($lists as $id => $name) {
					$list_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
				}
			} else $list_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No lists found.', 'ulp').'</div>';
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
			if (!isset($_POST['client-id']) || !isset($_POST['client-secret']) || !isset($_POST['list-id']) || empty($_POST['client-id']) || empty($_POST['client-secret']) || empty($_POST['list-id'])) {
				$return_object['html'] = '<strong>'.__('Invalid OAuth Credentials or List ID.', 'ulp').'</strong>';
			} else {
				$client_id = trim(stripslashes($_POST['client-id']));
				$client_secret = trim(stripslashes($_POST['client-secret']));
				$list_id = trim(stripslashes($_POST['list-id']));
				$return_object['html'] = $this->get_fields_html($client_id, $client_secret, $list_id, $this->default_popup_options['cleverreach_fields'], $this->default_popup_options['cleverreach_globalfields']);
			}
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_client_id, $_client_secret, $_list_id, $_fields, $_globalfields) {
		$token = $this->_get_token($_client_id, $_client_secret);
		if (!empty($token) && is_array($token)) {
			if (array_key_exists('access_token', $token)) {
				$result_local = $this->_connect($token['access_token'], 'attributes.json?group_id='.$_list_id);
				$result_global = $this->_connect($token['access_token'], 'attributes.json');
				if (is_array($result_local)) {
					if (array_key_exists('error', $result_local)) {
						$fields = '<div class="ulp-cleverreach-grouping" style="margin-bottom: 10px;"><strong>'.esc_html($result_local['error']['message']).'</strong></div>';
					} else {
						$result = array_merge($result_local, $result_global);
						if (sizeof($result) == 0) {
							$fields = '<div class="ulp-cleverreach-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
						} else {
							$fields = '
							'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate CleverReach fields with the popup fields.', 'ulp').'
							<table style="min-width: 280px; width: 50%;">';
							foreach ($result_local as $field) {
								if (is_array($field)) {
									if (array_key_exists('name', $field) && array_key_exists('description', $field)) {
										$fields .= '
								<tr>
									<td style="width: 100px;"><strong>'.esc_html($field['description']).':</strong></td>
									<td>
										<input type="text" id="ulp_cleverreach_field_'.esc_html($field['name']).'" name="ulp_cleverreach_field_'.esc_html($field['name']).'" value="'.esc_html(array_key_exists($field['name'], $_fields) ? $_fields[$field['name']] : '').'" class="widefat" />
										<br /><em>'.esc_html($field['description'].' ('.$field['name'].')').'</em>
									</td>
								</tr>';
									}
								}
							}
							foreach ($result_global as $field) {
								if (is_array($field)) {
									if (array_key_exists('name', $field) && array_key_exists('description', $field)) {
										$fields .= '
								<tr>
									<td style="width: 100px;"><strong>'.esc_html($field['description']).':</strong></td>
									<td>
										<input type="text" id="ulp_cleverreach_globalfield_'.esc_html($field['name']).'" name="ulp_cleverreach_globalfield_'.esc_html($field['name']).'" value="'.esc_html(array_key_exists($field['name'], $_globalfields) ? $_globalfields[$field['name']] : '').'" class="widefat" />
										<br /><em>'.esc_html($field['description'].' ('.$field['name'].')').'</em>
									</td>
								</tr>';
									}
								}
							}
							$fields .= '
							</table>';
							
						}
					}
				} else {
					$fields = '<div class="ulp-cleverreach-grouping" style="margin-bottom: 10px;"><strong>'.__('Unexpected server response.', 'ulp').'</strong></div>';
				}
			} else if (array_key_exists('error_description', $token)) {
				$fields = '<div class="ulp-cleverreach-grouping" style="margin-bottom: 10px;"><strong>'.esc_html($token['error_description']).'</strong></div>';
			} else {
				$fields = '<div class="ulp-cleverreach-grouping" style="margin-bottom: 10px;"><strong>'.__('Unexpected server response.', 'ulp').'</strong></div>';
			}
		} else {
			$fields = '<div class="ulp-cleverreach-grouping" style="margin-bottom: 10px;"><strong>'.__('Unexpected server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	
	function _get_token($_client_id, $_client_secret) {
		try {
			$curl = curl_init('https://rest.cleverreach.com/oauth/token.php');
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_USERPWD, $_client_id.':'.$_client_secret);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, array("grant_type" => "client_credentials"));
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

	function _connect($_access_token, $_path, $_data = array(), $_method = '') {
		$url = 'https://rest.cleverreach.com/v3/'.ltrim($_path, '/');
		if (strpos($url, '?') === false) $url .= '?token='.$_access_token;
		else $url .= '&token='.$_access_token;
		$headers = array(
			'Accept: application/json',
			'Content-Type: application/json'
		);
		try {
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array('postdata' => $_data)));
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
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
$ulp_cleverreach = new ulp_cleverreach_class();
?>