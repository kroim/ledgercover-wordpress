<?php
/* Zoho CRM integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_zohocrm_class {
	var $options = array(
		"zohocrm_client_id" => "",
		"zohocrm_client_secret" => "",
		"zohocrm_redirect_uri" => "",
		"zohocrm_dc" => "",
		"zohocrm_refresh_token" => "",
		"zohocrm_api_domain" => "",
		"zohocrm_connection_data" => array()
	);
	var $default_popup_options = array(
		"zohocrm_enable" => "off",
		"zohocrm_xfields" => array('Email' => '{subscription-email}', 'Company' => 'My Company', 'Last_Name' => '{subscription-name}')
	);
	function __construct() {
		$this->get_options();
		if (is_admin()) {
			add_action('ulp_options_show', array(&$this, 'options_show'));
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-zohocrm-connect', array(&$this, "zohocrm_connect"));
			add_action('wp_ajax_ulp-zohocrm-disconnect', array(&$this, "zohocrm_disconnect"));
			add_action('wp_ajax_ulp-zohocrm-connected', array(&$this, "zohocrm_connected"));
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
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
			<h3 id="zohocrm-settings">'.__('Zoho CRM Connection', 'ulp').'</h3>';
		$account = null;
		if ($this->options['zohocrm_refresh_token']) {
			$data = array(
				'refresh_token' => $this->options["zohocrm_refresh_token"],
				'client_id' => $this->options["zohocrm_client_id"],
				'client_secret' => $this->options["zohocrm_client_secret"],
				'grant_type' => 'refresh_token'
			);
			$result = $this->connect_auth($this->options["zohocrm_dc"], '', $data);				
			if (is_array($result) && array_key_exists('access_token', $result)) {
				$account = true;
			}
		}
		if (!$account) {
			echo '
			<div id="ulp-zohocrm-connection">
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Connect', 'ulp').':</th>
						<td>
							<a class="ulp-button ulp-button-small" onclick="window.open(\''.admin_url('admin-ajax.php').'?action=ulp-zohocrm-connect\', \'_blank\', \'height=560,width=720,menubar=no,scrollbars=no,status=no,toolbar=no\'); return false;"><i class="fas fa-check"></i><label>'.__('Connect to Zoho CRM', 'ulp').'</label></a>
							<br /><em>'.__('Click the button to connect to Zoho CRM.', 'ulp').'</em>
						</td>
					</tr>
				</table>
			</div>';
		} else {
			echo '
			<div id="ulp-zohocrm-connection">
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Connected', 'ulp').':</th>
						<td>
							<a class="ulp-button ulp-button-small" onclick="return ulp_zohocrm_disconnect(this);"><i class="fas fa-times"></i><label>'.__('Disconnect from Zoho CRM', 'ulp').'</label></a>
							<br /><em>'.__('Click the button to disconnect from Zoho CRM.', 'ulp').'</em>
						</td>
					</tr>
				</table>
			</div>';
		}
		echo '
			<script>
				var ulp_zohocrm_connecting = false;
				function ulp_zohocrm_connected() {
					if (ulp_zohocrm_connecting) return false;
					var button_object = jQuery("#ulp-zohocrm-connection .ulp-button");
					jQuery(button_object).find("i").attr("class", "fas fa-spinner fa-spin");
					jQuery(button_object).addClass("ulp-button-disabled");
					ulp_zohocrm_connecting = true;
					var post_data = {"action" : "ulp-zohocrm-connected"};
					jQuery.ajax({
						type	: "POST",
						url		: "'.admin_url('admin-ajax.php').'", 
						data	: post_data,
						success	: function(return_data) {
							jQuery(button_object).find("i").attr("class", "fas fa-times");
							jQuery(button_object).removeClass("ulp-button-disabled");
							try {
								var data = jQuery.parseJSON(return_data);
								if (data.status == "OK") {
									jQuery("#ulp-zohocrm-connection").slideUp(350, function() {
										jQuery("#ulp-zohocrm-connection").html(data.html);
										jQuery("#ulp-zohocrm-connection").slideDown(350);
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
							ulp_zohocrm_connecting = false;
						},
						error	: function(XMLHttpRequest, textStatus, errorThrown) {
							jQuery(button_object).find("i").attr("class", "fas fa-times");
							jQuery(button_object).removeClass("ulp-button-disabled");
							ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
							ulp_zohocrm_connecting = false;
						}
					});
					return false;
				}
				function ulp_zohocrm_disconnect(_button) {
					if (ulp_zohocrm_connecting) return false;
					var button_object = _button;
					jQuery(button_object).find("i").attr("class", "fas fa-spinner fa-spin");
					jQuery(button_object).addClass("ulp-button-disabled");
					ulp_zohocrm_connecting = true;
					var post_data = {"action" : "ulp-zohocrm-disconnect"};
					jQuery.ajax({
						type	: "POST",
						url		: "'.admin_url('admin-ajax.php').'", 
						data	: post_data,
						success	: function(return_data) {
							jQuery(button_object).find("i").attr("class", "fas fa-times");
							jQuery(button_object).removeClass("ulp-button-disabled");
							try {
								var data = jQuery.parseJSON(return_data);
								if (data.status == "OK") {
									jQuery("#ulp-zohocrm-connection").slideUp(350, function() {
										jQuery("#ulp-zohocrm-connection").html(data.html);
										jQuery("#ulp-zohocrm-connection").slideDown(350);
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
							ulp_zohocrm_connecting = false;
						},
						error	: function(XMLHttpRequest, textStatus, errorThrown) {
							jQuery(button_object).find("i").attr("class", "fas fa-times");
							jQuery(button_object).removeClass("ulp-button-disabled");
							ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
							ulp_zohocrm_connecting = false;
						}
					});
					return false;
				}
			</script>';
	}
	
	function zohocrm_connect() {
		global $wpdb, $ulp;
		if (current_user_can('manage_options')) {
			$show_form = true;
			if (array_key_exists("do", $_REQUEST)) {
				switch ($_REQUEST['do']) {
					case 'save-data':
						$this->options["zohocrm_connection_data"] = array();
						if (array_key_exists("domain", $_REQUEST)) $this->options["zohocrm_connection_data"]["domain"] = trim(stripslashes($_REQUEST['domain']));
						else $this->options["zohocrm_connection_data"]["domain"] = 'com';
						if (array_key_exists("redirect-uri", $_REQUEST)) $this->options["zohocrm_connection_data"]["redirect-uri"] = trim(stripslashes($_REQUEST['redirect-uri']));
						else $this->options["zohocrm_connection_data"]["redirect-uri"] = '';
						if (array_key_exists("client-id", $_REQUEST)) $this->options["zohocrm_connection_data"]["client-id"] = trim(stripslashes($_REQUEST['client-id']));
						else $this->options["zohocrm_connection_data"]["client-id"] = '';
						if (array_key_exists("client-secret", $_REQUEST)) $this->options["zohocrm_connection_data"]["client-secret"] = trim(stripslashes($_REQUEST['client-secret']));
						else $this->options["zohocrm_connection_data"]["client-secret"] = '';
						$this->update_options();
						$return_data = array(
							'status' => 'OK',
							'url' => 'https://accounts.zoho.'.$this->options["zohocrm_connection_data"]["domain"].'/oauth/v2/auth?scope=ZohoCRM.modules.ALL,ZohoCRM.settings.ALL&client_id='.urlencode($this->options["zohocrm_connection_data"]["client-id"]).'&response_type=code&access_type=offline&redirect_uri='.urlencode($this->options["zohocrm_connection_data"]["redirect-uri"])
						);
						echo json_encode($return_data);
						exit;
						break;
						
					case 'connect':
						if (array_key_exists("code", $_REQUEST)) {
							$data = array(
								'code' => $_REQUEST['code'],
								'redirect_uri' => $this->options["zohocrm_connection_data"]["redirect-uri"],
								'client_id' => $this->options["zohocrm_connection_data"]["client-id"],
								'client_secret' => $this->options["zohocrm_connection_data"]["client-secret"],
								'grant_type' => 'authorization_code'
							);
							$result = $this->connect_auth($this->options["zohocrm_connection_data"]["domain"], '', $data);
							if (is_array($result) && array_key_exists('refresh_token', $result)) {
								$this->options['zohocrm_refresh_token'] = $result['refresh_token'];
								$this->options['zohocrm_api_domain'] = $result['api_domain'];
								$this->options['zohocrm_dc'] = $this->options["zohocrm_connection_data"]["domain"];
								$this->options['zohocrm_redirect_uri'] = $this->options["zohocrm_connection_data"]["redirect-uri"];
								$this->options['zohocrm_client_id'] = $this->options["zohocrm_connection_data"]["client-id"];
								$this->options['zohocrm_client_secret'] = $this->options["zohocrm_connection_data"]["client-secret"];
								$this->update_options();
								$content = __('Success!', 'ulp').'<script>window.opener.ulp_zohocrm_connected(); window.close();</script>';
							} else if (is_array($result)) {
								$content = __('Invalid connection credentials. Please try again.', 'ulp').'<div><a class="button" href="'.admin_url('admin-ajax.php').'?action=ulp-zohocrm-connect"><i class="fas fa-check"></i><label>'.__('Try again', 'ulp').'</label></a></div>';
							} else {
								$content = __('Something went wrong. We got unexpected server response. Please try again.', 'ulp').'<div><a class="button" href="'.admin_url('admin-ajax.php').'?action=ulp-zohocrm-connect"><i class="fas fa-check"></i><label>'.__('Try again', 'ulp').'</label></a></div>';
							}
						} else {
							$content = __('Something went wrong. We got unexpected server response. Please try again.', 'ulp').'<div><a class="button" href="'.admin_url('admin-ajax.php').'?action=ulp-zohocrm-connect"><i class="fas fa-check"></i><label>'.__('Try again', 'ulp').'</label></a></div>';
						}
						break;
						
					default:
						$content = __('Inavlid URL. Please try again.', 'ulp').'<div><a class="button" href="'.admin_url('admin-ajax.php').'?action=ulp-zohocrm-connect"><i class="fas fa-check"></i><label>'.__('Try again', 'ulp').'</label></a></div>';
						break;
				}
				echo '
<!DOCTYPE html>
<html>
<head>
	<title>'.__('Connect to Zoho CRM', 'ulp').'</title>
	<link rel="stylesheet" media="all" href="'.$ulp->plugins_url.'/css/oauth.css" />
	<link rel="stylesheet" media="all" href="'.$ulp->plugins_url.'/css/font-awesome.min.css" />
	<script src="'.$ulp->plugins_url.'/js/jquery.min.js" type="text/javascript"></script>
</head>
<body>
	<div class="main-container">
		<h1>'.__('Connect to Zoho CRM', 'ulp').'</h1>
		<div class="content">'.$content.'</div>
	</div>
</body>
</html>';
				exit;
			}
			echo '
<!DOCTYPE html>
<html>
<head>
	<title>'.__('Connect to Zoho CRM', 'ulp').'</title>
	<link rel="stylesheet" media="all" href="'.$ulp->plugins_url.'/css/oauth.css" />
	<link rel="stylesheet" media="all" href="'.$ulp->plugins_url.'/css/font-awesome.min.css" />
	<script src="'.$ulp->plugins_url.'/js/jquery.min.js" type="text/javascript"></script>
	<script>
		function domain_changed() {
			jQuery("a").each(function() {
				var href = jQuery(this).attr("data-href");
				if (href) {
					href = href.replace("{domain}", jQuery("#domain").val());
					jQuery(this).attr("href", href);
				}
			});
		}
		var connecting = false;
		function connect(_button) {
			if (connecting) return false;
			var button_object = _button;
			jQuery(button_object).find("i").attr("class", "fas fa-spinner fa-spin");
			jQuery(button_object).addClass("button-disabled");
			connecting = true;
			var post_data = {"action" : "ulp-zohocrm-connect", "do" : "save-data", "domain" : jQuery("#domain").val(), "redirect-uri" : jQuery("#redirect-uri").val(), "client-id" : jQuery("#client-id").val(), "client-secret" : jQuery("#client-secret").val()};
			jQuery.ajax({
				type	: "POST",
				url		: "'.admin_url('admin-ajax.php').'", 
				data	: post_data,
				success	: function(return_data) {
					jQuery(button_object).find("i").attr("class", "fas fa-check");
					jQuery(button_object).removeClass("button-disabled");
					try {
						var data = jQuery.parseJSON(return_data);
						if (data.status == "OK") {
							location.href = data.url;
						} else if (data.status == "ERROR") {
							alert(data.message);
						} else {
							alert(\'Something went wrong. We got unexpected server response.\');
						}
					} catch(error) {
						alert(\'Something went wrong. We got unexpected server response.\');
					}
					connecting = false;
				},
				error	: function(XMLHttpRequest, textStatus, errorThrown) {
					jQuery(button_object).find("i").attr("class", "fas fa-check");
					jQuery(button_object).removeClass("button-disabled");
					connecting = false;
				}
			});
			return false;
		}
	</script>
</head>
<body>
	<div class="main-container">
		<h1>'.__('Connect to Zoho CRM', 'ulp').'</h1>
		<ol>
			<li>
				Select your Zoho domain:
				<select id="domain" name="domain" onchange="domain_changed();">
					<option value="com">zoho.com</option>
					<option value="eu">zoho.eu</option>
					<option value="com.cn">zoho.com.cn</option>
					<option value="in">zoho.in</option>
				</select>
			</li>
			<li>
				Register new Application (create Client ID) in <a data-href="https://accounts.zoho.{domain}/developerconsole" target="_blank" href="https://accounts.zoho.com/developerconsole">Zoho Developer Console</a>. Use the following URL as Authorized Redirect URIs:
				<input type="text" readonly="readonly" id="redirect-uri" name="redirect-uri" onclick="this.focus();this.select();" value="'.admin_url('admin-ajax.php').'?action=ulp-zohocrm-connect&do=connect" />
				For more details please read chapter <a data-href="https://www.zoho.{domain}/crm/help/api/v2/#oauth-request" target="_blank" href="https://www.zoho.com/crm/help/api/v2/#oauth-request">Register your application</a>.
			</li>
			<li>
				Enter Client ID and Client Secret into fields below.
				<input type="text" id="client-id" name="client-id" value="" placeholder="Client ID" />
				<input type="text" id="client-secret" name="client-secret" value="" placeholder="Client Secret" />
			</li>
		</ol>
		<div class="button-container">
			<a class="button" href="#" onclick="return connect(this);"><i class="fas fa-check"></i><label>Connect to Zoho CRM</label></a>			
		</div>
	</div>
</body>
</html>';
		}
		exit;
	}

	function zohocrm_disconnect() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$data = array(
				'token' => $this->options["zohocrm_refresh_token"]
			);
			$result = $this->connect_auth($this->options["zohocrm_dc"], '/revoke', $data);
			if (is_array($result) && array_key_exists('status', $result) && $result['status'] == 'success') {
				$this->options['zohocrm_refresh_token'] = '';
				$this->options['zohocrm_api_domain'] = '';
				$this->options['zohocrm_dc'] = '';
				$this->options['zohocrm_redirect_uri'] = '';
				$this->options['zohocrm_client_id'] = '';
				$this->options['zohocrm_client_secret'] = '';
				$this->options["zohocrm_connection_data"] = array();
				$this->update_options();
					
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['message'] = __('Successfully disconnected from Zoho CRM!', 'ulp');
				$return_object['html'] = '
					<table class="ulp_useroptions">
						<tr>
							<th>'.__('Connect', 'ulp').':</th>
							<td>
								<a class="ulp-button ulp-button-small" onclick="window.open(\''.admin_url('admin-ajax.php').'?action=ulp-zohocrm-connect\', \'_blank\', \'height=560,width=720,menubar=no,scrollbars=no,status=no,toolbar=no\'); return false;"><i class="fas fa-check"></i><label>'.__('Connect to Zoho CRM', 'ulp').'</label></a>
								<br /><em>'.__('Click the button to connect to Zoho CRM.', 'ulp').'</em>
							</td>
						</tr>
					</table>';
				echo json_encode($return_object);
				exit;
			}
			$return_object = array();
			$return_object['status'] = 'ERROR';
			$return_object['message'] = __('Can not disconnect from Zoho CRM.', 'ulp');
			echo json_encode($return_object);
			exit;
		}
		exit;
	}

	function zohocrm_connected() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$data = array(
				'refresh_token' => $this->options["zohocrm_refresh_token"],
				'client_id' => $this->options["zohocrm_client_id"],
				'client_secret' => $this->options["zohocrm_client_secret"],
				'grant_type' => 'refresh_token'
			);
			$result = $this->connect_auth($this->options["zohocrm_dc"], '', $data);				
			if (is_array($result) && array_key_exists('access_token', $result)) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['message'] = __('Successfully connected to Zoho CRM!', 'ulp');
				$return_object['html'] = '
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Connected', 'ulp').':</th>
						<td>
							<a class="ulp-button ulp-button-small" onclick="return ulp_zohocrm_disconnect(this);"><i class="fas fa-times"></i><label>'.__('Disconnect from Zoho CRM', 'ulp').'</label></a>
							<br /><em>'.__('Click the button to disconnect from Zoho CRM.', 'ulp').'</em>
						</td>
					</tr>
				</table>';
				echo json_encode($return_object);
				exit;
			}
			$return_object = array();
			$return_object['status'] = 'ERROR';
			$return_object['message'] = __('Can not connect to Zoho CRM.', 'ulp');
			echo json_encode($return_object);
			exit;
		}
		exit;
	}
	
	function popup_options_show($_popup_options) {
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		
		$access_token = null;
		if ($this->options['zohocrm_refresh_token']) {
			$data = array(
				'refresh_token' => $this->options["zohocrm_refresh_token"],
				'client_id' => $this->options["zohocrm_client_id"],
				'client_secret' => $this->options["zohocrm_client_secret"],
				'grant_type' => 'refresh_token'
			);
			$result = $this->connect_auth($this->options["zohocrm_dc"], '', $data);				
			if (is_array($result) && array_key_exists('access_token', $result)) {
				$access_token = $result['access_token'];
			}
		}
		echo '
				<h3>'.__('Zoho CRM Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">';
		if (!$access_token) {
			echo '
					<tr>
						<th>'.__('Enable Zoho CRM', 'ulp').':</th>
						<td>'.__('Please connect your Zoho CRM account on <a target="_blank" href="admin.php?page=ulp-settings#zohocrm-settings">Settings</a> page.', 'ulp').'</td>
					</tr>';
		} else {		
			echo '
					<tr>
						<th>'.__('Enable Zoho CRM', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_zohocrm_enable" name="ulp_zohocrm_enable" '.($popup_options['zohocrm_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Zoho CRM (as Lead)', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Zoho CRM.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-zohocrm-fields-html">';
			$fields = $this->get_fields_html($access_token, $popup_options['zohocrm_xfields']);
			echo $fields;
			echo '
							</div>
						</td>
					</tr>';
		}
		echo '
				</table>';
	}
	function popup_options_populate($_popup_options) {
		global $ulp;
		$popup_options = array();
		if (isset($ulp->postdata["ulp_zohocrm_enable"])) $popup_options['zohocrm_enable'] = "on";
		else $popup_options['zohocrm_enable'] = "off";
		$popup_options['zohocrm_xfields'] = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_zohocrm_field_')) == 'ulp_zohocrm_field_') {
				$field = substr($key, strlen('ulp_zohocrm_field_'));
				$popup_options['zohocrm_xfields'][$field] = stripslashes(trim($value));
			}
		}
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['zohocrm_enable'] == 'on') {
			if ($this->options['zohocrm_refresh_token']) {
				$data = array(
					'refresh_token' => $this->options["zohocrm_refresh_token"],
					'client_id' => $this->options["zohocrm_client_id"],
					'client_secret' => $this->options["zohocrm_client_secret"],
					'grant_type' => 'refresh_token'
				);
				$result = $this->connect_auth($this->options["zohocrm_dc"], '', $data);
				if (is_array($result) && array_key_exists('access_token', $result)) {
					$access_token = $result['access_token'];
					$data = array();
					if (!empty($popup_options['zohocrm_xfields']) && is_array($popup_options['zohocrm_xfields'])) {
						foreach ($popup_options['zohocrm_xfields'] as $key => $value) {
							if ($key == 'Email') $data[$key] = $_subscriber['{subscription-email}'];
							else if (!empty($value)) {
								$data[$key] = strtr($value, $_subscriber);
							}
						}
					}
					$result = $this->connect_api($access_token, '/leads/upsert', array('data' => array($data)));
				}
			}
		}
	}
	function get_fields_html($_token, $_fields) {
		$result = $this->connect_api($_token, '/settings/fields?module=leads');
		$fields = '';
		$idx = 0;
		if (is_array($result) && array_key_exists('fields', $result)) {
			if (sizeof($result['fields']) > 0) {
				$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Zoho CRM fields with the popup fields. <strong>Important! Make sure that all required fields (configured on Zoho CRM side) are filled out.</strong>', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
				foreach ($result['fields'] as $field) {
					if (!in_array($field['data_type'], array('lookup', 'ownerlookup', 'boolean'))) {
						$fields .= '
						<tr>
							<td><strong>'.esc_html($field['field_label']).':</strong></td>
							<td>';
						if ($field['data_type'] == 'picklist') {
							$fields .= '
								<select id="ulp_zohocrm_field_'.$field['api_name'].'" name="ulp_zohocrm_field_'.$field['api_name'].'" class="widefat">';
							foreach ($field['pick_list_values'] as $val) {
								$fields .= '
									<option value="'.esc_html($val['actual_value']).'"'.(array_key_exists($field['api_name'], $_fields) && $_fields[$field['api_name']] == $val['actual_value'] ? ' selected="selected"' : '').'>'.esc_html($val['display_value']).'</option>';
							}
							$fields .= '
								</select>';
						} else {
							$fields .= '
								<input type="text" id="ulp_zohocrm_field_'.$field['api_name'].'" name="ulp_zohocrm_field_'.$field['api_name'].'" value="'.esc_html(($field['api_name'] == 'Email' ? '{subscription-email}' : (array_key_exists($field['api_name'], $_fields) ? $_fields[$field['api_name']] : ''))).'" class="widefat"'.($field['api_name'] == 'Email' ? ' readonly="readonly"' : '').' />';
						}
						$fields .= '
								<br /><em>'.esc_html($field['field_label']).'</em>
							</td>
						</tr>';
					}
				}
				$fields .= '
			</table>';
			} else {
				$fields = '<div class="ulp-zohocrm-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
			}
		} else if (is_array($result) && array_key_exists('message', $result)) {
			$fields = '<div class="ulp-zohocrm-grouping" style="margin-bottom: 10px;"><strong>'.ucwords($result['message']).'</strong></div>';
		} else {
			$fields = '<div class="ulp-zohocrm-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect_auth($_domain, $_path, $_data) {
		try {
			$url = 'https://accounts.zoho.'.$_domain.'/oauth/v2/token'.(empty($_path) ? '' : '/'.ltrim($_path, '/'));
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
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
	function connect_api($_token, $_path, $_data = array()) {
		try {
			$headers = array(
				'Authorization: Zoho-Oauthtoken '.$_token
			);
			$url = rtrim($this->options["zohocrm_api_domain"], '/').'/crm/v2/'.ltrim($_path, '/');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
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
	
}
$ulp_zohocrm = new ulp_zohocrm_class();
?>