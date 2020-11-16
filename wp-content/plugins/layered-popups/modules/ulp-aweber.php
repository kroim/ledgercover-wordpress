<?php
/* AWeber integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
define('ULP_AWEBER_APPID', '0e193739');

class ulp_aweber_class {
	var $options = array(
		"aweber_consumer_key" => "",
		"aweber_consumer_secret" => "",
		"aweber_access_key" => "",
		"aweber_access_secret" => ""
	);
	var $default_popup_options = array(
		'aweber_enable' => "off",
		'aweber_listid' => "",
		'aweber_email' => '{subscription-email}',
		'aweber_name' => '{subscription-name}',
		'aweber_fields' => array(),
		'aweber_fieldnames' => array(),
		'aweber_tags' => '',
		'aweber_misc_notes' => '',
		'aweber_ad_tracking' => "layered-popups"
	);
	function __construct() {
		$this->get_options();
		if (is_admin()) {
			add_action('ulp_options_show', array(&$this, 'options_show'));
			add_action('wp_ajax_ulp-aweber-auth-code', array(&$this, "aweber_auth_code"));
			add_action('wp_ajax_ulp-aweber-connect', array(&$this, "aweber_connect"));
			add_action('wp_ajax_ulp-aweber-disconnect', array(&$this, "aweber_disconnect"));
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
			add_action('wp_ajax_ulp-aweber-fields', array(&$this, "show_fields"));
		}
		add_action('ulp_subscribe', array(&$this, 'subscribe'), 10, 2);
	}
	function popup_options_tabs($_tabs) {
		if (!array_key_exists("integration", $_tabs)) $_tabs["integration"] = __('Integration', 'ulp');
		return $_tabs;
	}
	function get_options() {
		foreach ($this->options as $key => $value) {
			$this->options[$key] = get_option('ulp_'.$key, $this->options[$key]);
		}
	}
	function update_options() {
		if (current_user_can('manage_options')) {
			foreach ($this->options as $key => $value) {
				update_option('ulp_'.$key, $value);
			}
		}
	}
	function populate_options() {
		foreach ($this->options as $key => $value) {
			if (isset($_POST['ulp_'.$key])) {
				$this->options[$key] = trim(stripslashes($_POST['ulp_'.$key]));
			}
		}
	}
	function options_show() {
		echo '
			<h3>'.__('AWeber Connection', 'ulp').'</h3>';
		$account_id = null;
		if (!empty($this->options['aweber_access_key']) && !empty($this->options['aweber_access_secret'])) {
			$accounts = $this->connect_api($this->options['aweber_consumer_key'], $this->options['aweber_consumer_secret'], $this->options['aweber_access_key'], $this->options['aweber_access_secret'], 'accounts', array(), 'GET');
			if (is_array($accounts) && array_key_exists('entries', $accounts) && sizeof($accounts['entries']) > 0 && !empty($accounts['entries'][0]['id'])) {
				$account_id = $accounts['entries'][0]['id'];
			}
		}
		if (empty($account_id)) {
			echo '
			<div id="ulp-aweber-connection">
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Authorization code', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-aweber-auth-code" value="" class="widefat" placeholder="AWeber Authorization Code">
							<br /><em>Get your authorization code <a target="_blank" href="'.admin_url('admin-ajax.php').'?action=ulp-aweber-auth-code" onclick="window.open(\''.admin_url('admin-ajax.php').'?action=ulp-aweber-auth-code\', \'_blank\', \'height=560,width=720,menubar=no,scrollbars=no,status=no,toolbar=no\'); return false;">'.__('here', 'ulp').'</a></em>.
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<a class="ulp-button ulp-button-small" onclick="return ulp_aweber_connect(this);"><i class="fas fa-check"></i><label>'.__('Connect to AWeber', 'ulp').'</label></a>
							<br /><em>'.__('Click the button to connect to AWeber.', 'ulp').'</em>
						</td>
					</tr>
				</table>
			</div>';
		} else {
			echo '
			<div id="ulp-aweber-connection">
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Connected', 'ulp').':</th>
						<td>
							<a class="ulp-button ulp-button-small" onclick="return ulp_aweber_disconnect(this);"><i class="fas fa-times"></i><label>'.__('Disconnect from AWeber', 'ulp').'</label></a>
							<br /><em>'.__('Click the button to disconnect from AWeber.', 'ulp').'</em>
						</td>
					</tr>
				</table>
			</div>';
		}
		echo '
			<script>
				var ulp_aweber_connecting = false;
				function ulp_aweber_connect(_object) {
					if (ulp_aweber_connecting) return false;
					jQuery(_object).find("i").attr("class", "fas fa-spinner fa-spin");
					jQuery(_object).addClass("ulp-button-disabled");
					ulp_aweber_connecting = true;
					var post_data = {"action" : "ulp-aweber-connect", "ulp-auth-code": jQuery("#ulp-aweber-auth-code").val()};
					jQuery.ajax({
						type	: "POST",
						url		: "'.admin_url('admin-ajax.php').'", 
						data	: post_data,
						success	: function(return_data) {
							jQuery(_object).find("i").attr("class", "fas fa-times");
							jQuery(_object).removeClass("ulp-button-disabled");
							try {
								var data = jQuery.parseJSON(return_data);
								if (data.status == "OK") {
									jQuery("#ulp-aweber-connection").slideUp(350, function() {
										jQuery("#ulp-aweber-connection").html(data.html);
										jQuery("#ulp-aweber-connection").slideDown(350);
									});
									ulp_global_message_show("success", data.message);
								} else if (data.status == "ERROR") {
									ulp_global_message_show("danger", data.message);
								} else {
									ulp_global_message_show("danger", "Something went wrong. We got unexpected server response 1.");
								}
							} catch(error) {
								ulp_global_message_show("danger", "Something went wrong. We got unexpected server response 2.");
							}
							ulp_aweber_connecting = false;
						},
						error	: function(XMLHttpRequest, textStatus, errorThrown) {
							jQuery(_object).find("i").attr("class", "fas fa-times");
							jQuery(_object).removeClass("ulp-button-disabled");
							ulp_global_message_show("danger", "Something went wrong. We got unexpected server response 3.");
							ulp_aweber_connecting = false;
						}
					});
					return false;
				}
				function ulp_aweber_disconnect(_object) {
					if (ulp_aweber_connecting) return false;
					jQuery(_object).find("i").attr("class", "fas fa-spinner fa-spin");
					jQuery(_object).addClass("ulp-button-disabled");
					ulp_aweber_connecting = true;
					var post_data = {"action" : "ulp-aweber-disconnect"};
					jQuery.ajax({
						type	: "POST",
						url		: "'.admin_url('admin-ajax.php').'", 
						data	: post_data,
						success	: function(return_data) {
							jQuery(_object).find("i").attr("class", "fas fa-times");
							jQuery(_object).removeClass("ulp-button-disabled");
							try {
								var data = jQuery.parseJSON(return_data);
								if (data.status == "OK") {
									jQuery("#ulp-aweber-connection").slideUp(350, function() {
										jQuery("#ulp-aweber-connection").html(data.html);
										jQuery("#ulp-aweber-connection").slideDown(350);
									});
									ulp_global_message_show("success", data.message);
								} else if (data.status == "ERROR") {
									ulp_global_message_show("danger", data.message);
								} else {
									ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
								}
							} catch(error) {
								ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
							}
							ulp_aweber_connecting = false;
						},
						error	: function(XMLHttpRequest, textStatus, errorThrown) {
							jQuery(_object).find("i").attr("class", "fas fa-times");
							jQuery(_object).removeClass("ulp-button-disabled");
							ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
							ulp_aweber_connecting = false;
						}
					});
					return false;
				}
			</script>';
	}
	function aweber_auth_code() {
		global $wpdb, $ulp;
		if (current_user_can('manage_options')) {
			echo '
<!DOCTYPE html>
<html>
<head>
	<title>'.__('Get AWeber Authorization Code', 'ulp').'</title>
	<link rel="stylesheet" media="all" href="'.$ulp->plugins_url.'/css/oauth.css" />
	<link rel="stylesheet" media="all" href="'.$ulp->plugins_url.'/css/font-awesome.min.css" />
	<script src="'.$ulp->plugins_url.'/js/jquery.min.js" type="text/javascript"></script>
	<script>
		function appid_changed() {
			jQuery("a").each(function() {
				var href = jQuery(this).attr("data-href");
				if (href) {
					href = href.replace("{app-id}", jQuery("#app-id").val());
					jQuery(this).attr("href", href);
				}
			});
		}
	</script>
</head>
<body>
	<div class="main-container">
		<h1>'.__('Get AWeber Authorization Code', 'ulp').'</h1>
		<ol>
			<li>
				Register new Application (create App ID) in <a target="_blank" href="https://labs.aweber.com/apps">AWeberAPI Console</a> or use existing App ID: <code>'.ULP_AWEBER_APPID.'</code>. If you register your own Application, please make sure that checkbox "Request Subscriber Data" on Permission Settings is set.
			</li>
			<li>
				Enter App ID below.
				<input type="text" id="app-id" name="app-id" value="'.ULP_AWEBER_APPID.'" placeholder="App ID" oninput="appid_changed();" />
			</li>
		</ol>
		<div class="button-container">
			<a class="button" data-href="https://auth.aweber.com/1.0/oauth/authorize_app/{app-id}" href="https://auth.aweber.com/1.0/oauth/authorize_app/'.ULP_AWEBER_APPID.'"><i class="fas fa-check"></i><label>Get Authorization Code</label></a>
		</div>
	</div>
</body>
</html>';
		}
		exit;
	}
	
	function aweber_connect() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$auth_code = trim(stripslashes($_REQUEST['ulp-auth-code']));
			$values = explode('|', $auth_code);
			if (sizeof($values) < 5) {
				$return_object = array('status' => 'ERROR', 'message' => __('Invalid AWeber Authorization Code.', 'ulp'));
				echo json_encode($return_object);
				exit;
			}
			$this->options['aweber_consumer_key'] = $values[0];
			$this->options['aweber_consumer_secret'] = $values[1];
			
			$data = array('oauth_verifier' => $values[4]);
			$response = $this->connect_auth($this->options['aweber_consumer_key'], $this->options['aweber_consumer_secret'], $values[2], $values[3], $data);
			if (is_array($response)) {
				$return_object = array('status' => 'ERROR', 'message' => __('Invalid AWeber Authorization Code.', 'ulp'));
				echo json_encode($return_object);
				exit;
			}
			parse_str($response, $result);
			if (!array_key_exists('oauth_token', $result) || empty($result['oauth_token']) || !array_key_exists('oauth_token_secret', $result)) {
				$return_object = array('status' => 'ERROR', 'message' => __('Invalid AWeber Authorization Code.', 'ulp'));
				echo json_encode($return_object);
				exit;
			}
			$this->options['aweber_access_key'] = $result['oauth_token'];
			$this->options['aweber_access_secret'] = $result['oauth_token_secret'];
			$this->update_options();
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['message'] = __('Successfully connected from AWeber!', 'ulp');
			$return_object['html'] = '
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Connected', 'ulp').':</th>
						<td>
							<a class="ulp-button ulp-button-small" onclick="return ulp_aweber_disconnect(this);"><i class="fas fa-times"></i><label>'.__('Disconnect from AWeber', 'ulp').'</label></a>
							<br /><em>'.__('Click the button to disconnect from AWeber.', 'ulp').'</em>
						</td>
					</tr>
				</table>';
			echo json_encode($return_object);
			exit;
		}
		exit;
	}
	function aweber_disconnect() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$this->options = array(
				"aweber_consumer_key" => "",
				"aweber_consumer_secret" => "",
				"aweber_access_key" => "",
				"aweber_access_secret" => ""
			);
			$this->update_options();
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['message'] = __('Successfully disconnected from AWeber!', 'ulp');
			$return_object['html'] = '
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Authorization code', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-aweber-auth-code" value="" class="widefat" placeholder="AWeber Authorization Code">
							<br /><em>Get your authorization code <a target="_blank" href="'.admin_url('admin-ajax.php').'?action=ulp-aweber-auth-code" onclick="window.open(\''.admin_url('admin-ajax.php').'?action=ulp-aweber-auth-code\', \'_blank\', \'height=560,width=720,menubar=no,scrollbars=no,status=no,toolbar=no\'); return false;">'.__('here', 'ulp').'</a></em>.
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<a class="ulp-button ulp-button-small" onclick="return ulp_aweber_connect(this);"><i class="fas fa-check"></i><label>'.__('Connect to AWeber', 'ulp').'</label></a>
							<br /><em>'.__('Click the button to connect to AWeber.', 'ulp').'</em>
						</td>
					</tr>
				</table>';
			echo json_encode($return_object);
			exit;
		}
		exit;
	}
	function popup_options_show($_popup_options) {
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('AWeber Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">';

		$account_id = null;
		if (!empty($this->options['aweber_access_key']) && !empty($this->options['aweber_access_secret'])) {
			$accounts = $this->connect_api($this->options['aweber_consumer_key'], $this->options['aweber_consumer_secret'], $this->options['aweber_access_key'], $this->options['aweber_access_secret'], 'accounts', array(), 'GET');
			if (is_array($accounts) && array_key_exists('entries', $accounts) && sizeof($accounts['entries']) > 0 && !empty($accounts['entries'][0]['id'])) {
				$account_id = $accounts['entries'][0]['id'];
			}
		}
		if (empty($account_id)) {
			echo '
					<tr>
						<th>'.__('Enable AWeber', 'ulp').':</th>
						<td>'.__('Please connect your AWeber account on <a target="_blank" href="'.admin_url('admin.php').'?page=ulp-settings">Settings</a> page.', 'ulp').'</td>
					</tr>';
		} else {
			$lists = $this->connect_api($this->options['aweber_consumer_key'], $this->options['aweber_consumer_secret'], $this->options['aweber_access_key'], $this->options['aweber_access_secret'], 'accounts/'.$account_id.'/lists', array(), 'GET');
            if (!is_array($lists) || !array_key_exists('entries', $lists) || sizeof($lists['entries']) == 0) {
				echo '
					<tr>
						<th>'.__('Enable AWeber', 'ulp').':</th>
						<td>'.__('This AWeber account does not currently have any lists.', 'ulp').'</td>
					</tr>';
			} else {
				echo '
					<tr>
						<th>'.__('Enable AWeber', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_aweber_enable" name="ulp_aweber_enable" '.($popup_options['aweber_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to AWeber', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to AWeber.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<select id="ulp-aweber-listid" name="ulp_aweber_listid" class="ic_input_m">';
				if (empty($popup_options['aweber_listid'])) $popup_options['aweber_listid'] = $lists['entries'][0]['id'];
				foreach ($lists['entries'] as $list) {
					echo '
								<option value="'.$list['id'].'"'.($list['id'] == $popup_options['aweber_listid'] ? ' selected="selected"' : '').'>'.$list['id'].' | '.$list['name'].'</option>';
				}
				echo '
							</select>
							<br /><em>'.__('Select your List ID.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate AWeber fields with the popup fields.', 'ulp').'
							<table style="min-width: 280px; width: 50%;">
								<tr>
									<td style="width: 100px;"><strong>'.__('Email', 'ulp').':</strong></td>
									<td>
										<input type="text" id="ulp_aweber_email" name="ulp_aweber_email" value="{subscription-email}" class="widefat" readonly="readonly" />
										<br /><em>'.__('Email address of the contact.', 'ulp').'</em>
									</td>
								</tr>
								<tr>
									<td><strong>'.__('Name', 'ulp').':</strong></td>
									<td>
										<input type="text" id="ulp_aweber_name" name="ulp_aweber_name" value="'.esc_html($popup_options['aweber_name']).'" class="widefat" />
										<br /><em>'.__('Name of the contact.', 'ulp').'</em>
									</td>
								</tr>
							</table>
							<div class="ulp-aweber-fields-html">';
		$fields_data = $this->get_fields_html($account_id, $popup_options['aweber_listid'], $popup_options['aweber_fields']);
		if ($fields_data['status'] == 'OK') echo $fields_data['html'];
		echo '
							</div>
							<a class="ulp-button ulp-button-small" onclick="return ulp_aweber_loadfields(this);"><i class="fas fa-download"></i><label>'.__('Load Fields', 'ulp').'</label></a>
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify custom fields values.', 'ulp').'</em>
							<script>
								function ulp_aweber_loadfields(_object) {
									if (ulp_saving) return;
									ulp_saving = true;
									jQuery(_object).addClass("ulp-button-disabled");
									jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
									jQuery(".ulp-aweber-fields-html").slideUp(350);
									var post_data = {action: "ulp-aweber-fields", ulp_list: jQuery("#ulp-aweber-listid").val()};
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
													jQuery(".ulp-aweber-fields-html").html(data.html);
													jQuery(".ulp-aweber-fields-html").slideDown(350);
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
							<input type="text" id="ulp_aweber_tags" name="ulp_aweber_tags" value="'.esc_html($popup_options['aweber_tags']).'" class="widefat">
							<br /><em>'.__('Enter comma-separated list of tags applied to the contact.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Notes', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_aweber_misc_notes" name="ulp_aweber_misc_notes" value="'.esc_html($popup_options['aweber_misc_notes']).'" class="widefat">
							<br /><em>'.__('Enter notes applied to the contact (max 60 sybmols).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Ad Tracking', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_aweber_ad_tracking" name="ulp_aweber_ad_tracking" value="'.esc_html($popup_options['aweber_ad_tracking']).'" class="widefat">
							<br /><em>'.__('Enter your Ad Tracking info applied to the contact.', 'ulp').'</em>
						</td>
					</tr>';
			}
		}
		echo '
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
		if (isset($ulp->postdata["ulp_aweber_enable"])) $popup_options['aweber_enable'] = "on";
		else $popup_options['aweber_enable'] = "off";
		if ($popup_options['aweber_enable'] == 'on') {
			if (empty($popup_options['aweber_listid'])) $errors[] = __('Invalid AWeber List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_aweber_enable"])) $popup_options['aweber_enable'] = "on";
		else $popup_options['aweber_enable'] = "off";
		
		$popup_options['aweber_fields'] = array();
		$popup_options['aweber_fieldnames'] = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_aweber_field_')) == 'ulp_aweber_field_') {
				$field = substr($key, strlen('ulp_aweber_field_'));
				$popup_options['aweber_fields'][$field] = stripslashes(trim($value));
				$popup_options['aweber_fieldnames'][$field] = stripslashes(trim($ulp->postdata['ulp_aweber_fieldname_'.$field]));
			}
		}
		$tags = explode(',', $popup_options['aweber_tags']);
		$ready_tags = array();
		foreach($tags as $tag) {
			$tag = trim($tag);
			if (strlen($tag) > 0) $ready_tags[] = $tag;
		}
		$popup_options['aweber_tags'] = implode(', ', $ready_tags);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($this->options['aweber_access_secret']) {
			if ($popup_options['aweber_enable'] == 'on') {
				$post_data = array(
					'email' => $_subscriber['{subscription-email}'],
					'ip_address' => $_SERVER['REMOTE_ADDR'],
					'name' => strtr($popup_options['aweber_name'], $_subscriber),
					'ad_tracking' => strtr($popup_options['aweber_ad_tracking'], $_subscriber),
					'last_followup_message_number_sent' => 0,
					'misc_notes' => strtr($popup_options['aweber_misc_notes'], $_subscriber)
				);
				$custom_fields = array();
				if (!empty($popup_options['aweber_fields']) && is_array($popup_options['aweber_fields'])) {
					foreach ($popup_options['aweber_fields'] as $key => $value) {
						if (!empty($value)) {
							$custom_fields[$popup_options['aweber_fieldnames'][$key]] = strtr($value, $_subscriber);
						}
					}
				}
				if (!empty($custom_fields)) $post_data['custom_fields'] = json_encode($custom_fields);
				
				$tags_raw = explode(',', $popup_options['aweber_tags']);
				$tags = array();
				foreach($tags_raw as $tag) {
					$tag = trim($tag);
					if (strlen($tag) > 0) $tags[] = $tag;
				}
				if (!empty($tags)) $post_data['tags'] = json_encode($tags);
				
				$account_id = null;
				if (!empty($this->options['aweber_access_key']) && !empty($this->options['aweber_access_secret'])) {
					$accounts = $this->connect_api($this->options['aweber_consumer_key'], $this->options['aweber_consumer_secret'], $this->options['aweber_access_key'], $this->options['aweber_access_secret'], 'accounts', array(), 'GET');
					if (is_array($accounts) && array_key_exists('entries', $accounts) && sizeof($accounts['entries']) > 0 && !empty($accounts['entries'][0]['id'])) {
						$account_id = $accounts['entries'][0]['id'];
					}
				}
				if (!empty($account_id)) {
					$result = $this->connect_api($this->options['aweber_consumer_key'], $this->options['aweber_consumer_secret'], $this->options['aweber_access_key'], $this->options['aweber_access_secret'], 'accounts/'.$account_id.'/lists/'.$popup_options['aweber_listid'].'/subscribers', array('ws.op' => 'find', 'email' => $_subscriber['{subscription-email}']), 'GET');
					if (array_key_exists('entries', $result) && sizeof($result['entries']) > 0) {
						$post_data['status'] = 'subscribed';
						if (!empty($tags)) $post_data['tags'] = json_encode(array('add' => $tags));
						$result = $this->connect_api($this->options['aweber_consumer_key'], $this->options['aweber_consumer_secret'], $this->options['aweber_access_key'], $this->options['aweber_access_secret'], 'accounts/'.$account_id.'/lists/'.$popup_options['aweber_listid'].'/subscribers/'.$result['entries'][0]['id'], $post_data, 'PATCH');
					} else {
						$result = $this->connect_api($this->options['aweber_consumer_key'], $this->options['aweber_consumer_secret'], $this->options['aweber_access_key'], $this->options['aweber_access_secret'], 'accounts/'.$account_id.'/lists/'.$popup_options['aweber_listid'].'/subscribers', $post_data, 'POST');
					}
				}
			}
		}
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_list']) || empty($_POST['ulp_list'])) {
				$return_object = array('status' => 'ERROR', 'message' => __('Invalid AWeber List.', 'ulp'));
				echo json_encode($return_object);
				exit;
			}
			$list_id = stripslashes(trim($_POST['ulp_list']));
			$account_id = null;
			if (!empty($this->options['aweber_access_key']) && !empty($this->options['aweber_access_secret'])) {
				$accounts = $this->connect_api($this->options['aweber_consumer_key'], $this->options['aweber_consumer_secret'], $this->options['aweber_access_key'], $this->options['aweber_access_secret'], 'accounts', array(), 'GET');
				if (is_array($accounts) && array_key_exists('entries', $accounts) && sizeof($accounts['entries']) > 0 && !empty($accounts['entries'][0]['id'])) {
					$account_id = $accounts['entries'][0]['id'];
				}
			}
			if (empty($account_id)) {
				$return_object = array('status' => 'ERROR', 'message' => __('Connect your AWeber account on General Settings page.', 'ulp'));
				echo json_encode($return_object);
				exit;
			}
			
			$return_object = $this->get_fields_html($account_id, $list_id, array());
			echo json_encode($return_object);
		}
		exit;
	}
	
	function get_fields_html($_account_id, $_list, $_fields) {
		global $wpdb, $ulp;
		$result = $this->connect_api($this->options['aweber_consumer_key'], $this->options['aweber_consumer_secret'], $this->options['aweber_access_key'], $this->options['aweber_access_secret'], 'accounts/'.$_account_id.'/lists/'.$_list.'/custom_fields', array(), 'GET');
		$fields_html = '';
		if (!empty($result) && is_array($result)) {
			if (array_key_exists('status', $result)) {
				return array('status' => 'ERROR', 'message' => $result['title']);
			} else {
				if (array_key_exists('entries', $result) && sizeof($result['entries']) > 0) {
					$fields_html = '
			<table style="min-width: 280px; width: 50%;">';
					foreach ($result['entries'] as $field) {
						if (is_array($field)) {
							if (array_key_exists('id', $field) && array_key_exists('name', $field)) {
								$fields_html .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['name']).':</strong></td>
					<td>
						<input type="text" name="ulp_aweber_field_'.esc_html($field['id']).'" value="'.esc_html(array_key_exists($field['id'], $_fields) ? $_fields[$field['id']] : '').'" class="widefat" />
						<input type="hidden" name="ulp_aweber_fieldname_'.esc_html($field['id']).'" value="'.esc_html($field['name']).'" />
						<br /><em>'.esc_html($field['name']).'</em>
					</td>
				</tr>';
							}
						}
					}
					$fields_html .= '
			</table>';
				} else {
					return array('status' => 'ERROR', 'message' => __('No custom fields found.', 'ulp'));
				}
			}
		} else {
			return array('status' => 'ERROR', 'message' => __('Inavlid server response.', 'ulp'));
		}
		return array('status' => 'OK', 'html' => $fields_html);
	}

	function connect_auth($_consumer_key, $_consumer_secret, $_access_token, $_access_secret, $_data) {
		try {
			$url = 'https://auth.aweber.com/1.0/oauth/access_token';
			$timestamp = time();
			$data = array(
				'oauth_token' => 			$_access_token,
				'oauth_consumer_key' =>		$_consumer_key,
				'oauth_version' => 			'1.0',
				'oauth_timestamp' => 		$timestamp,
				'oauth_signature_method' => 'HMAC-SHA1',
				'oauth_nonce' => 			md5($timestamp.'-'.rand(10000,99999).'-'.uniqid())
			);
			$data = array_merge($_data, $data);
			$data = $this->_sign_request('POST', $url, $data, $_consumer_secret, $_access_secret);
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($curl, CURLOPT_TIMEOUT, 30);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
			//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($curl);
			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			if ($httpCode >= 400) $result = json_decode($response, true);
			else $result = $response;
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}

	function connect_api($_consumer_key, $_consumer_secret, $_access_token, $_access_secret, $_path, $_data, $_method = 'GET') {
		try {
			$url = 'https://api.aweber.com/1.0'.(empty($_path) ? '' : '/'.ltrim($_path, '/'));
			$timestamp = time();
			$data = array(
				'oauth_token' => 			$_access_token,
				'oauth_consumer_key' =>		$_consumer_key,
				'oauth_version' => 			'1.0',
				'oauth_timestamp' => 		$timestamp,
				'oauth_signature_method' => 'HMAC-SHA1',
				'oauth_nonce' => 			md5($timestamp.'-'.rand(10000,99999).'-'.uniqid())
			);
			$data = array_merge($_data, $data);
			$data = $this->_sign_request($_method, $url, $data, $_consumer_secret, $_access_secret);
			ksort($data);
			$params = array();
			foreach ($data as $key => $value) {
				$params[] = $key.'='.rawurlencode(utf8_encode($value));
			}
			$query = implode('&', $params);
			if ($_method == 'GET') {
				if (strpos($url, '?') === false) $url .= '?'.$query;
				else $url .= '?'.$query;
			}
			$curl = curl_init($url);
			if ($_method != 'GET') {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
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
			$result = json_decode($response, true);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
	
    function _sign_request($_method, $_url, $_data, $_consumer_secret, $_token_secret) {
        $_method = rawurlencode(utf8_encode(strtoupper($_method)));
        $query = parse_url($_url, PHP_URL_QUERY);
        if ($query) {
            $_url = array_shift(explode('?', $_url, 2));
            $items = explode('&', $query);
            foreach ($items as $item) {
                list($key, $value) = explode('=', $item);
                $_data[$key] = $value;
            }
        }
		$_url = rawurlencode(utf8_encode($_url));
        ksort($_data);
        $data_str = '';
        foreach ($_data as $key => $val) {
            if (!empty($data_str)) $data_str .= '&';
            $data_str .= $key.'='.rawurlencode(utf8_encode($val));
        }
        $signature_base = $_method.'&'.$_url.'&'.rawurlencode(utf8_encode($data_str));
        $signature_key  = $_consumer_secret.'&'.$_token_secret;
		
        $_data['oauth_signature'] = base64_encode(hash_hmac('sha1', $signature_base, $signature_key, true));
        ksort($_data);
        return $_data;
    }
	
}
$ulp_aweber = new ulp_aweber_class();
?>