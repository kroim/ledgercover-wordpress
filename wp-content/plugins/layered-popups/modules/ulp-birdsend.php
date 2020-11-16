<?php
/* BirdSend integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_birdsend_class {
	var $default_popup_options = array(
		"birdsend_enable" => "off",
		"birdsend_access_token" => "",
		"birdsend_sequence" => "",
		"birdsend_sequence_id" => "",
		"birdsend_fields" => array(
			'first_name' => '{subscription-name}'
		),
		"birdsend_tags" => ""
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-birdsend-sequences', array(&$this, "show_sequences"));
			add_action('wp_ajax_ulp-birdsend-fields', array(&$this, "show_fields"));
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
				<h3>'.__('BirdSend Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable BirdSend', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_birdsend_enable" name="ulp_birdsend_enable" '.($popup_options['birdsend_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to BirdSend', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to BirdSend.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							Important! Please go to your BirdSend Account >> Settings >> Integrations >> BirdSend Apps and create new App. After that go to Permissions tab of App settings and set them as "Write". Then go to Access Token tab of App settings and create Personal Access Token. Copy and Paste it into field below.
						</td>
					</tr>
					<tr>
						<th>'.__('Personal Access Token', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_birdsend_access_token" name="ulp_birdsend_access_token" value="'.esc_html($popup_options['birdsend_access_token']).'" class="widefat">
							<br /><em>'.__('Paste your Personal Access Token with "write" permissions.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Sequence ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-birdsend-sequence" name="ulp_birdsend_sequence" value="'.esc_html($popup_options['birdsend_sequence']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_birdsend_sequences_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-birdsend-sequence-id" name="ulp_birdsend_sequence_id" value="'.esc_html($popup_options['birdsend_sequence_id']).'" />
							<div id="ulp-birdsend-sequence-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your Sequence ID.', 'ulp').'</em>
							<script>
								function ulp_birdsend_sequences_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-birdsend-sequences", "ulp_access_token": jQuery("#ulp_birdsend_access_token").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-birdsend-fields-html">';
		if (!empty($popup_options['birdsend_access_token'])) {
			$fields_data = $this->get_fields_html($popup_options['birdsend_access_token'], $popup_options['birdsend_fields']);
			if ($fields_data['status'] == 'OK') echo $fields_data['html'];
		}
		echo '
							</div>
							<a class="ulp-button ulp-button-small" onclick="return ulp_birdsend_loadfields(this);"><i class="fas fa-check"></i><label>'.__('Load Fields', 'ulp').'</label></a>
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_birdsend_loadfields(_object) {
									if (ulp_saving) return;
									ulp_saving = true;
									jQuery(_object).addClass("ulp-button-disabled");
									jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
									jQuery(".ulp-birdsend-fields-html").slideUp(350);
									var post_data = {action: "ulp-birdsend-fields", ulp_access_token: jQuery("#ulp_birdsend_access_token").val()};
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
													jQuery(".ulp-birdsend-fields-html").html(data.html);
													jQuery(".ulp-birdsend-fields-html").slideDown(350);
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
							<input type="text" id="ulp_birdsend_tags" name="ulp_birdsend_tags" value="'.esc_html($popup_options['birdsend_tags']).'" class="widefat">
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
		if (isset($ulp->postdata["ulp_birdsend_enable"])) $popup_options['birdsend_enable'] = "on";
		else $popup_options['birdsend_enable'] = "off";
		if ($popup_options['birdsend_enable'] == 'on') {
			if (empty($popup_options['birdsend_access_token']) || strpos($popup_options['birdsend_access_token'], '-') === false) $errors[] = __('Invalid BirdSend Access Token.', 'ulp');
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
		if (isset($ulp->postdata["ulp_birdsend_enable"])) $popup_options['birdsend_enable'] = "on";
		else $popup_options['birdsend_enable'] = "off";
		
		$popup_options['birdsend_fields'] = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_birdsend_field_')) == 'ulp_birdsend_field_') {
				$field = substr($key, strlen('ulp_birdsend_field_'));
				$popup_options['birdsend_fields'][$field] = stripslashes(trim($value));
			}
		}
		
		$tags_raw = explode(',', $ulp->postdata['ulp_birdsend_tags']);
		$tags = array();
		foreach($tags_raw as $tag_raw) {
			$tag_raw = trim($tag_raw);
			if (!empty($tag_raw)) $tags[] = $tag_raw;
		}
		$popup_options['birdsend_tags'] = implode(', ', $tags);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['birdsend_enable'] == 'on') {
			$post_data = array(
				'ipaddress' => $_SERVER['REMOTE_ADDR'],
				'email' => $_subscriber['{subscription-email}']
			);
			$fields = array();
			if (!empty($popup_options['birdsend_fields']) && is_array($popup_options['birdsend_fields'])) {
				foreach ($popup_options['birdsend_fields'] as $key => $value) {
					if (!empty($value)) {
						$fields[$key] = strtr($value, $_subscriber);
					}
				}
			}
			if (!empty($fields)) $post_data['fields'] = $fields;
			$tags = array();
			$tags_raw = explode(',', $popup_options['birdsend_tags']);
			foreach ($tags_raw as $tag_raw) {
				$tag_raw = trim($tag_raw);
				if (!empty($tag_raw)) $tags[] = $tag_raw;
			}

			$result = $this->connect($popup_options['birdsend_access_token'], 'contacts?search_by=email&keyword='.urlencode($_subscriber['{subscription-email}']));
			if (array_key_exists('data', $result) && !empty($result['data'])) {
				$contact_id = $result['data'][0]['contact_id'];
				$result = $this->connect($popup_options['birdsend_access_token'], 'contacts/'.$contact_id, $post_data, 'PATCH');
				if (!empty($tags)) $result = $this->connect($popup_options['birdsend_access_token'], 'contacts/'.$contact_id.'/tags', array('tags' => $tags));
				if (!empty($popup_options['birdsend_sequence_id'])) $result = $this->connect($popup_options['birdsend_access_token'], 'contacts/'.$contact_id.'/subscribe', array('sequence_id' => $popup_options['birdsend_sequence_id']));
			} else {
				if (!empty($popup_options['birdsend_sequence_id'])) $post_data['sequence_id'] = $popup_options['birdsend_sequence_id'];
				if (!empty($tags)) $post_data['tags'] = $tags;
				$result = $this->connect($popup_options['birdsend_access_token'], 'contacts', $post_data);
			}
		}
	}
	function show_sequences() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_access_token']) || empty($_POST['ulp_access_token'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Access Token', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$access_token = trim(stripslashes($_POST['ulp_access_token']));
			
			$result = $this->connect($access_token, 'sequences?per_page=100&page=1');
			if (is_array($result) && !empty($result)) {
				if (array_key_exists('data', $result)) {
					if (!empty($result['data'])) {
						foreach ($result['data'] as $list) {
							if (is_array($list)) {
								if (array_key_exists('sequence_id', $list) && array_key_exists('name', $list)) {
									$lists[$list['sequence_id']] = $list['name'];
								}
							}
						}
					} else {
						$return_object = array();
						$return_object['status'] = 'OK';
						$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No sequences found.', 'ulp').'</div>';
						echo json_encode($return_object);
						exit;
					}
				} else if (array_key_exists('status', $result) && array_key_exists('message', $result)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.$result['message'].'</div>';
					echo json_encode($return_object);
					exit;
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response.', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$list_html = '';
			if (!empty($lists)) {
				$list_html .= '<a href="#" data-id="" data-title="'.__('No sequence', 'ulp').'" onclick="return ulp_input_options_selected(this);">'.__('No sequence', 'ulp').'</a>';
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
			if (!isset($_POST['ulp_access_token']) || empty($_POST['ulp_access_token'])) {
				$return_object = array('status' => 'ERROR', 'message' => __('Invalid Personal Access Token.', 'ulp'));
				echo json_encode($return_object);
				exit;
			}
			$access_token = trim(stripslashes($_POST['ulp_access_token']));
			$return_object = $this->get_fields_html($access_token, $this->default_popup_options['birdsend_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_access_token, $_fields) {
		$result = $this->connect($_access_token, 'fields?per_page=100&page=1');
		if (!empty($result) && is_array($result)) {
			if (array_key_exists('data', $result)) {
				if (!empty($result['data'])) {
					$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate BirdSend fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
					foreach ($result['data'] as $field) {
						if (is_array($field)) {
							if (array_key_exists('key', $field) && array_key_exists('label', $field)) {
								$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['label']).':</strong></td>
					<td>
						<input type="text" id="ulp_birdsend_field_'.esc_html($field['key']).'" name="ulp_birdsend_field_'.esc_html($field['key']).'" value="'.esc_html(array_key_exists($field['key'], $_fields) ? $_fields[$field['key']] : '').'" class="widefat" />
						<br /><em>'.esc_html($field['label'].' ('.$field['key'].')').'</em>
					</td>
				</tr>';
							}
						}
					}
					$fields .= '
			</table>';
				} else {
					return array('status' => 'ERROR', 'message' => __('No fields found.', 'ulp'));
				}
			} else if (array_key_exists('status', $result) && array_key_exists('message', $result)) {
				return array('status' => 'ERROR', 'message' => $result['message']);
			} else {
				return array('status' => 'ERROR', 'message' => __('Invalid server response.', 'ulp'));
			}
		} else {
			return array('status' => 'ERROR', 'message' => __('Inavlid server response.', 'ulp'));
		}
		return array('status' => 'OK', 'html' => $fields);
	}
	function connect($_access_token, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Authorization: Bearer '.$_access_token,
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		try {
			$url = 'https://api.birdsend.co/v1/'.ltrim($_path, '/');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
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
$ulp_birdsend = new ulp_birdsend_class();
?>