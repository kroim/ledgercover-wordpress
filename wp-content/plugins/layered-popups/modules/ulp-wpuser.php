<?php
/* Create/update WP user */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_wpuser_class {
	var $default_popup_options = array(
		'wpuser_enable' => 'off',
		'wpuser_role' => 'subscriber',
		'wpuser_fields' => array(
			'user_email' => '{subscription-email}',
			'user_login' => '{subscription-email}',
			'first_name' => '{subscription-name}',
			'last_name' => '',
			'user_url' => ''
		),
		'wpuser_notification_enable' => 'on',
		'wpuser_allow_update' => 'off'
	);
	var $field_labels = array(
		'user_email' => array('title' => 'E-mail', 'description' => 'E-mail address of the user.'),
		'user_login' => array('title' => 'Username', 'description' => 'Username of the user.'),
		'first_name' => array('title' => 'First name', 'description' => 'First name of the user.'),
		'last_name' => array('title' => 'Last name', 'description' => 'Last name of the user.'),
		'user_url' => array('title' => 'Website URL', 'description' => 'Website URL of the user.')
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-wpuser-lists', array(&$this, "show_lists"));
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
		}
		add_filter('ulp_subscriber_details', array(&$this, 'subscriber_details'), 10, 2);
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
				<h3>'.__('WP User Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Create WP User', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_wpuser_enable" name="ulp_wpuser_enable" '.($popup_options['wpuser_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Create new WP User', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to create new WP User.', 'ulp').'</em>
						</td>
					</tr>
					<tr>';
		$fields = array_merge($this->default_popup_options['wpuser_fields'], $popup_options['wpuser_fields']);
		echo '
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-wpuser-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate WP User fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
		foreach ($this->default_popup_options['wpuser_fields'] as $key => $value) {
			echo '
									<tr>
										<td style="width: 100px;"><strong>'.esc_html($this->field_labels[$key]['title']).':</strong></td>
										<td>
											<input type="text" id="ulp_wpuser_field_'.esc_html($key).'" name="ulp_wpuser_field_'.esc_html($key).'" value="'.esc_html($fields[$key]).'" class="widefat"'.($key == 'user_email' || $key == 'user_login' ? ' readonly="readonly"' : '').' />
											<br /><em>'.esc_html($this->field_labels[$key]['description']).'</em>
										</td>
									</tr>';
		}
		echo '
								</table>
							</div>
						</td>
					</tr>
					<tr>
						<th>'.__('User role', 'ulp').':</th>
						<td>
							<select id="ulp_wpuser_role" name="ulp_wpuser_role">';
		$roles = get_editable_roles();
		foreach ($roles as $key => $value) {
			echo '
								<option'.($popup_options['wpuser_role'] == $key ? ' selected="selected"' : '').' value="'.esc_html($key).'">'.esc_html($value['name']).'</option>';
		}
		echo '
							</select>
							<br /><em>'.__('Select the role for the newly created user.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('User notification', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_wpuser_notification_enable" name="ulp_wpuser_notification_enable" '.($popup_options['wpuser_notification_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Send User Notification', 'ulp').'
							<br /><em>'.__('Send the new user an email about their account (standard WP message).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Allow updates', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_wpuser_allow_update" name="ulp_wpuser_allow_update" '.($popup_options['wpuser_allow_update'] == "on" ? 'checked="checked"' : '').'"> '.__('Update details for already existing users', 'ulp').'
							<br /><em>'.__('Update user data for already existing users. Existing user must have the same user role.', 'ulp').'</em>
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
		if (isset($ulp->postdata["ulp_wpuser_enable"])) $popup_options['wpuser_enable'] = "on";
		else $popup_options['wpuser_enable'] = "off";
		if (isset($ulp->postdata["ulp_wpuser_notification_enable"])) $popup_options['wpuser_notification_enable'] = "on";
		else $popup_options['wpuser_notification_enable'] = "off";
		if (isset($ulp->postdata["ulp_wpuser_allow_update"])) $popup_options['wpuser_allow_update'] = "on";
		else $popup_options['wpuser_allow_update'] = "off";
		$popup_options['wpuser_fields'] = array();
		foreach($this->default_popup_options['wpuser_fields'] as $key => $value) {
			if (isset($ulp->postdata['ulp_wpuser_field_'.$key])) {
				$popup_options['wpuser_fields'][$key] = stripslashes(trim($ulp->postdata['ulp_wpuser_field_'.$key]));
			}
		}
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['wpuser_enable'] == 'on') {
			$fields = array_merge($this->default_popup_options['wpuser_fields'], $popup_options['wpuser_fields']);
			$data = array(
				'user_email' => $_subscriber['{subscription-email}'],
				'user_login' => $_subscriber['{subscription-email}'],
				'role' => $popup_options['wpuser_role']
			);
			foreach ($this->default_popup_options['wpuser_fields'] as $key => $value) {
				if (!empty($popup_options['wpuser_fields'][$key]) && $key != 'user_email' && $key != 'user_login') {
					$data[$key] = strtr($popup_options['wpuser_fields'][$key], $_subscriber);
				}
			}
			$user_id = username_exists($_subscriber['{subscription-email}']);
			if (!$user_id) $user_id = email_exists($_subscriber['{subscription-email}']);
			if (!$user_id) {
				$data['user_pass'] = $_subscriber['{wpuser-password}'];
				$user_id = wp_insert_user($data);
				if (!is_wp_error($user_id)) {
					if ($popup_options['wpuser_notification_enable'] == 'on') {
						wp_new_user_notification($user_id, null, 'both');
					}
				}
			} else {
				if ($popup_options['wpuser_allow_update'] == 'on') {
					$user = get_userdata($user_id);
					if ($user) {
						if (in_array($popup_options['wpuser_role'], $user->roles)) {
							$data['ID'] = $user_id;
							wp_update_user($data);
						}
					}
				}
			}
		}
	}
	function subscriber_details($_subscriber, $_popup_options) {
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['wpuser_enable'] == 'on') {
			$_subscriber['{wpuser-password}'] = wp_generate_password();
		}
		return $_subscriber;
	}
}
$ulp_wpuser = new ulp_wpuser_class();
?>