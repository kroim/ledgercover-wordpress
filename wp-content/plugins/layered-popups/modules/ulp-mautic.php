<?php
/* Mautic integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_mautic_class {
	var $default_popup_options = array(
		'mautic_enable' => 'off',
		'mautic_url' => '',
		'mautic_username' => '',
		'mautic_password' => '',
		'mautic_owner' => '',
		'mautic_owner_id' => '',
		'mautic_segment' => '',
		'mautic_segment_id' => '',
		'mautic_campaign' => '',
		'mautic_campaign_id' => '',
		'mautic_fields' => array(
			'email' => '{subscription-email}',
			'firstname' => '{subscription-name}',
			'phone' => '{subscription-phone}'
		)
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-mautic-owners', array(&$this, "show_owners"));
			add_action('wp_ajax_ulp-mautic-segments', array(&$this, "show_segments"));
			add_action('wp_ajax_ulp-mautic-campaigns', array(&$this, "show_campaigns"));
			add_action('wp_ajax_ulp-mautic-fields', array(&$this, "show_fields"));
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
				<h3>'.__('Mautic Parameters', 'ulp').'</h3>
				<p><strong>'.__('Important! This module requires enabled HTTP Basic Auth. Please do it in your Mautic account on <code>Settings >> Configuration >> API Settings</code> page.', 'ulp').'</strong></p>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Mautic', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mautic_enable" name="ulp_mautic_enable" '.($popup_options['mautic_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Mautic', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Mautic.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Site URL', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mautic_url" name="ulp_mautic_url" value="'.esc_html($popup_options['mautic_url']).'" class="widefat">
							<br /><em>'.__('Enter unique website address of your account. Usually it looks like https://SITE-NAME.mautic.net/', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Username', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mautic_username" name="ulp_mautic_username" value="'.esc_html($popup_options['mautic_username']).'" class="widefat">
							<br /><em>'.__('Enter Mautic username to access your account.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Password', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mautic_password" name="ulp_mautic_password" value="'.esc_html($popup_options['mautic_password']).'" class="widefat">
							<br /><em>'.__('Enter Mautic password to access your account.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Owner:', 'ulp').'</th>
						<td>
							<input type="text" id="ulp-mautic-list" name="ulp_mautic_owner" value="'.esc_html($popup_options['mautic_owner']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_mautic_owners_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-mautic-list-id" name="ulp_mautic_owner_id" value="'.esc_html($popup_options['mautic_owner_id']).'" />
							<div id="ulp-mautic-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter owner of the contact.', 'ulp').'</em>
							<script>
								function ulp_mautic_owners_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-mautic-owners", "ulp_url": jQuery("#ulp_mautic_url").val(), "ulp_username": jQuery("#ulp_mautic_username").val(), "ulp_password": jQuery("#ulp_mautic_password").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Segment:', 'ulp').'</th>
						<td>
							<input type="text" id="ulp-mautic-segment" name="ulp_mautic_segment" value="'.esc_html($popup_options['mautic_segment']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_mautic_segments_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-mautic-segment-id" name="ulp_mautic_segment_id" value="'.esc_html($popup_options['mautic_segment_id']).'" />
							<div id="ulp-mautic-segment-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>	
							<br /><em>'.__('Enter segment of the contact.', 'ulp').'</em>
							<script>
								function ulp_mautic_segments_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-mautic-segments", "ulp_url": jQuery("#ulp_mautic_url").val(), "ulp_username": jQuery("#ulp_mautic_username").val(), "ulp_password": jQuery("#ulp_mautic_password").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Campaign:', 'ulp').'</th>
						<td>
							<input type="text" id="ulp-mautic-campaign" name="ulp_mautic_campaign" value="'.esc_html($popup_options['mautic_campaign']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_mautic_campaigns_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-mautic-campaign-id" name="ulp_mautic_campaign_id" value="'.esc_html($popup_options['mautic_campaign_id']).'" />
							<div id="ulp-mautic-campaign-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>	
							<br /><em>'.__('Enter campaign of the contact.', 'ulp').'</em>
							<script>
								function ulp_mautic_campaigns_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-mautic-campaigns", "ulp_url": jQuery("#ulp_mautic_url").val(), "ulp_username": jQuery("#ulp_mautic_username").val(), "ulp_password": jQuery("#ulp_mautic_password").val()});
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-mautic-fields-html">';
		if (!empty($popup_options['mautic_url']) && !empty($popup_options['mautic_username']) && !empty($popup_options['mautic_password'])) {
			$fields = $this->get_fields_html($popup_options['mautic_url'], $popup_options['mautic_username'], $popup_options['mautic_password'], $popup_options['mautic_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_mautic_fields_button" class="ulp_button button-secondary" onclick="return ulp_mautic_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-mautic-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_mautic_loadfields() {
									jQuery("#ulp-mautic-fields-loading").fadeIn(350);
									jQuery(".ulp-mautic-fields-html").slideUp(350);
									var data = {action: "ulp-mautic-fields", ulp_url: jQuery("#ulp_mautic_url").val(), ulp_username: jQuery("#ulp_mautic_username").val(), ulp_password: jQuery("#ulp_mautic_password").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-mautic-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-mautic-fields-html").html(data.html);
												jQuery(".ulp-mautic-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-mautic-fields-html").html("<div class=\'ulp-mautic-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Mautic server.', 'ulp').'</strong></div>");
												jQuery(".ulp-mautic-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-mautic-fields-html").html("<div class=\'ulp-mautic-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Mautic server.', 'ulp').'</strong></div>");
											jQuery(".ulp-mautic-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_mautic_enable"])) $popup_options['mautic_enable'] = "on";
		else $popup_options['mautic_enable'] = "off";
		if ($popup_options['mautic_enable'] == 'on') {
			if (empty($popup_options['mautic_url'])) $errors[] = __('Invalid Mautic Site URL', 'ulp');
			if (empty($popup_options['mautic_username'])) $errors[] = __('Invalid Mautic username', 'ulp');
			if (empty($popup_options['mautic_password'])) $errors[] = __('Invalid Mautic password', 'ulp');
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
		if (isset($ulp->postdata["ulp_mautic_enable"])) $popup_options['mautic_enable'] = "on";
		else $popup_options['mautic_enable'] = "off";
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_mautic_field_')) == 'ulp_mautic_field_') {
				$field = substr($key, strlen('ulp_mautic_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['mautic_fields'] = $fields;
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['mautic_enable'] == 'on') {
			$data = array(
				'email' => $_subscriber['{subscription-email}'],
				'ipAddress' => $_SERVER['REMOTE_ADDR'],
			);
			if (!empty($popup_options['mautic_owner_id'])) $data['owner'] = $popup_options['mautic_owner_id'];
			$fields = $popup_options['mautic_fields'];
			foreach ($fields as $key => $value) {
				if (!empty($value) && $key != 'email') {
					$data[$key] = strtr($value, $_subscriber);
				}
			}
			$result = $this->connect($popup_options['mautic_username'], $popup_options['mautic_password'], rtrim($popup_options['mautic_url'], '/').'/api/contacts?search='.rawurlencode($_subscriber['{subscription-email}']));
			if (empty($result) || $result['total'] == 0) {
				$result = $this->connect($popup_options['mautic_username'], $popup_options['mautic_password'], rtrim($popup_options['mautic_url'], '/').'/api/contacts/new', $data);
				$contact_id = $result['contact']['id'];
			} else {
				$contact_details = reset($result['contacts']);
				$contact_id = $contact_details['id'];
				$result = $this->connect($popup_options['mautic_username'], $popup_options['mautic_password'], rtrim($popup_options['mautic_url'], '/').'/api/contacts/'.rawurlencode($contact_id).'/edit', $data, 'PUT');
			}
			if (!empty($popup_options['mautic_segment_id']) && $popup_options['mautic_segment_id'] != 0) {
				$result = $this->connect($popup_options['mautic_username'], $popup_options['mautic_password'], rtrim($popup_options['mautic_url'], '/').'/api/segments/'.rawurlencode($popup_options['mautic_segment_id']).'/contact/'.rawurlencode($contact_id).'/add', array(), 'POST');
			}
			if (!empty($popup_options['mautic_campaign_id']) && $popup_options['mautic_campaign_id'] != 0) {
				$result = $this->connect($popup_options['mautic_username'], $popup_options['mautic_password'], rtrim($popup_options['mautic_url'], '/').'/api/campaigns/'.rawurlencode($popup_options['mautic_campaign_id']).'/contact/'.rawurlencode($contact_id).'/add', array(), 'POST');
			}
		}
	}
	function show_owners() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_url']) || !isset($_POST['ulp_username']) || !isset($_POST['ulp_password']) || empty($_POST['ulp_url']) || empty($_POST['ulp_username']) || empty($_POST['ulp_password'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Site URL or username/password.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$mautic_url = trim(stripslashes($_POST['ulp_url']));
			$username = trim(stripslashes($_POST['ulp_username']));
			$password = trim(stripslashes($_POST['ulp_password']));
			
			if (!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $mautic_url)) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Site URL!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$owners = array();
			$result = $this->connect($username, $password, rtrim($mautic_url, '/').'/api/contacts/list/owners');
			if ($result) {
				if (array_key_exists("error", $result)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid username or password.', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
				foreach($result as $owner) {
					if (is_array($owner)) {
						if (array_key_exists('id', $owner) && array_key_exists('firstName', $owner) && array_key_exists('lastName', $owner)) {
							$owners[$owner['id']] = $owner['firstName'].' '.$owner['lastName'];
						}
					}
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to Mautic Site URL!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$owner_html = '';
			if (!empty($owners)) {
				foreach ($owners as $id => $name) {
					$owner_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
				}
			} else $owner_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No owners found!', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $owner_html;
			$return_object['items'] = sizeof($owners);
			echo json_encode($return_object);
		}
		exit;
	}
	function show_segments() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_url']) || !isset($_POST['ulp_username']) || !isset($_POST['ulp_password']) || empty($_POST['ulp_url']) || empty($_POST['ulp_username']) || empty($_POST['ulp_password'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Site URL or username/password.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$mautic_url = trim(stripslashes($_POST['ulp_url']));
			$username = trim(stripslashes($_POST['ulp_username']));
			$password = trim(stripslashes($_POST['ulp_password']));
			
			if (!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $mautic_url)) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Site URL!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$segments = array("0" => "None");
			$result = $this->connect($username, $password, rtrim($mautic_url, '/').'/api/segments');
			if ($result) {
				if (array_key_exists("error", $result)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid username or password.', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
				foreach($result['lists'] as $segment) {
					if (is_array($segment)) {
						if (array_key_exists('id', $segment) && array_key_exists('name', $segment)) {
							$segments[$segment['id']] = $segment['name'];
						}
					}
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to Mautic Site URL!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$segment_html = '';
			if (!empty($segments)) {
				foreach ($segments as $id => $name) {
					$segment_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
				}
			} else $segment_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No segments found!', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $segment_html;
			$return_object['items'] = sizeof($segments);
			echo json_encode($return_object);
		}
		exit;
	}
	function show_campaigns() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_url']) || !isset($_POST['ulp_username']) || !isset($_POST['ulp_password']) || empty($_POST['ulp_url']) || empty($_POST['ulp_username']) || empty($_POST['ulp_password'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Site URL or username/password.', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$mautic_url = trim(stripslashes($_POST['ulp_url']));
			$username = trim(stripslashes($_POST['ulp_username']));
			$password = trim(stripslashes($_POST['ulp_password']));
			
			if (!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $mautic_url)) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Site URL!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$campaigns = array("0" => "None");
			$result = $this->connect($username, $password, rtrim($mautic_url, '/').'/api/campaigns');
			if ($result) {
				if (array_key_exists("error", $result)) {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid username or password.', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
				foreach($result['campaigns'] as $campaign) {
					if (is_array($campaign)) {
						if (array_key_exists('id', $campaign) && array_key_exists('name', $campaign)) {
							$campaigns[$campaign['id']] = $campaign['name'];
						}
					}
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Can not connect to Mautic Site URL!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$campaign_html = '';
			if (!empty($campaigns)) {
				foreach ($campaigns as $id => $name) {
					$campaign_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
				}
			} else $campaign_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No campaigns found!', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $campaign_html;
			$return_object['items'] = sizeof($campaigns);
			echo json_encode($return_object);
		}
		exit;
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_url']) || !isset($_POST['ulp_username']) || !isset($_POST['ulp_password']) || empty($_POST['ulp_url']) || empty($_POST['ulp_username']) || empty($_POST['ulp_password'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-mautic-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid Site URL or username/password.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$mautic_url = trim(stripslashes($_POST['ulp_url']));
			$username = trim(stripslashes($_POST['ulp_username']));
			$password = trim(stripslashes($_POST['ulp_password']));

			if (!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $mautic_url)) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-mautic-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid Site URL!', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($mautic_url, $username, $password, $this->default_popup_options['mautic_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_url, $_username, $_password, $_fields) {
		$result = $this->connect($_username, $_password, rtrim($_url, '/').'/api/contacts/list/fields');
		$fields = '';
		if (!is_array($_fields)) $_fields = array();
		$processed_fields = array();
		if (!empty($result) && is_array($result)) {
			if (array_key_exists('error', $result)) {
				$fields = '<div class="ulp-mautic-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid username or password.', 'ulp').'</strong></div>';
			} else {
				if (sizeof($result)) {
					$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Mautic fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
					foreach ($result as $field) {
						if (is_array($field)) {
							if (array_key_exists('alias', $field) && array_key_exists('label', $field)) {
								if (!in_array($field['alias'], $processed_fields)) {
									$processed_fields[] = $field['alias'];
									$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['label']).':</strong></td>
					<td>
						<input type="text" id="ulp_mautic_field_'.esc_html($field['alias']).'" name="ulp_mautic_field_'.esc_html($field['alias']).'" value="'.esc_html(array_key_exists($field['alias'], $_fields) ? $_fields[$field['alias']] : '').'" class="widefat"'.($field['alias'] == 'email' ? ' readonly="readonly"' : '').' />
						<br /><em>'.esc_html($field['label']).' ('.esc_html($field['alias']).')</em>
					</td>
				</tr>';
								}
							}
						}
					}
					$fields .= '
			</table>';
				} else {
					$fields = '<div class="ulp-mautic-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
				}
			}
		} else {
			$fields = '<div class="ulp-mautic-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not connect to Mautic Site URL.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_username, $_password, $_url, $_data = array(), $_method = '') {
		$headers = array(
			'Authorization: Basic '.base64_encode($_username.':'.$_password),
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		try {
			$curl = curl_init($_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
			}
			if (!empty($_method)) {
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
}
$ulp_mautic = new ulp_mautic_class();
?>