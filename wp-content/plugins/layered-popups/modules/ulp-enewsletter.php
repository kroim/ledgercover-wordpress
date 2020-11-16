<?php
/* E-newsletter by WPMU DEV integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_enewsletter_class {
	var $default_popup_options = array(
		'enewsletter_enable' => "off",
		'enewsletter_email' => "{subscription-email}",
		'enewsletter_first_name' => "{subscription-name}",
		'enewsletter_last_name' => "",
		'enewsletter_groups' => array()
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
		}
		add_action('ulp_subscribe', array(&$this, 'subscribe'), 10, 2);
	}
	function popup_options_tabs($_tabs) {
		if (!array_key_exists("integration", $_tabs)) $_tabs["integration"] = __('Integration', 'ulp');
		return $_tabs;
	}
	function popup_options_show($_popup_options) {
		global $ulp, $wpdb, $email_newsletter;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('E-newsletter by WPMU DEV Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">';
		if (is_object($email_newsletter) && class_exists('Email_Newsletter')) {
			$groups = $email_newsletter->get_groups();
			echo '
					<tr>
						<th>'.__('Enable E-newsletter', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_enewsletter_enable" name="ulp_enewsletter_enable" '.($popup_options['enewsletter_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to E-newsletter by WPMU DEV', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to E-newsletter by WPMU DEV.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-enewsletter-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate E-newsletter by WPMU DEV fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">
									<tr>
										<td style="width: 120px;"><strong>'.__('E-mail', 'ulp').':</strong></td>
										<td>
											<input type="text"value="{subscription-email}" class="widefat" readonly="readonly" />
											<br /><em>'.__('E-mail address.', 'ulp').'</em>
										</td>
									</tr>
									<tr>
										<td style="width: 120px;"><strong>'.__('First name', 'ulp').':</strong></td>
										<td>
											<input type="text" id="ulp_enewsletter_first_name" name="ulp_enewsletter_first_name" value="'.$popup_options['enewsletter_first_name'].'" class="widefat" />
											<br /><em>'.__('First name.', 'ulp').'</em>
										</td>
									</tr>
									<tr>
										<td style="width: 120px;"><strong>'.__('Last name', 'ulp').':</strong></td>
										<td>
											<input type="text" id="ulp_enewsletter_last_name" name="ulp_enewsletter_last_name" value="'.$popup_options['enewsletter_last_name'].'" class="widefat" />
											<br /><em>'.__('Last name.', 'ulp').'</em>
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
					<tr>
						<th>'.__('Groups', 'ulp').':</th>
						<td>';
			if (sizeof($groups) > 0) {
				foreach ($groups as $group) {
					echo '
							<div style="margin-bottom: 3px;"><input type="checkbox" name="ulp_enewsletter_group_'.$group['group_id'].'"'.(in_array($group['group_id'], $popup_options['enewsletter_groups']) ? ' checked="checked"' : '').'> '.esc_html($group['group_name']).'</div>';
				}
				echo '
							<em>'.__('Select groups.', 'ulp').'</em>';
			} else {
				echo __('No groups found.', 'ulp');
			}
			echo '
						</td>
					</tr>';
		} else {
			echo '
					<tr>
						<th>'.__('Enable E-newsletter', 'ulp').':</th>
						<td>'.__('Please install and activate <a target="_blank" href="https://premium.wpmudev.org/project/e-newsletter/">E-newsletter by WPMU DEV</a> plugin.', 'ulp').'</td>
					</tr>';
		
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
		if (isset($ulp->postdata["ulp_enewsletter_enable"])) $popup_options['enewsletter_enable'] = "on";
		else $popup_options['enewsletter_enable'] = "off";
		return array_merge($_errors, $errors);
	}
	function popup_options_populate($_popup_options) {
		global $ulp, $email_newsletter;
		$popup_options = array();
		foreach ($this->default_popup_options as $key => $value) {
			if (isset($ulp->postdata['ulp_'.$key])) {
				$popup_options[$key] = stripslashes(trim($ulp->postdata['ulp_'.$key]));
			}
		}
		if (isset($ulp->postdata["ulp_enewsletter_enable"])) $popup_options['enewsletter_enable'] = "on";
		else $popup_options['enewsletter_enable'] = "off";
		$popup_options['enewsletter_groups'] = array();
		if (is_object($email_newsletter) && class_exists('Email_Newsletter')) {
			$groups = $email_newsletter->get_groups();
			foreach($groups as $group) {
				if (array_key_exists('ulp_enewsletter_group_'.$group['group_id'], $ulp->postdata)) {
					$popup_options['enewsletter_groups'][] = $group['group_id'];
				}
			}
		}
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp, $wpdb, $email_newsletter;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if (is_object($email_newsletter) && class_exists('Email_Newsletter')) {
			if ($popup_options['enewsletter_enable'] == 'on') {
				$member_data = array(
					'member_fname' => strtr($popup_options['enewsletter_first_name'], $_subscriber),
					'member_lname' => strtr($popup_options['enewsletter_last_name'], $_subscriber),
					'member_email' => $_subscriber['{subscription-email}']
				);
				$result = $email_newsletter->create_update_member_user('', $member_data, 1);
				$member_id = $result['member_id'];
				
				$memeber_groups = $email_newsletter->get_memeber_groups($member_id);
				if(!$memeber_groups) $memeber_groups = array();
				$memeber_groups = array_unique(array_merge($memeber_groups, $popup_options['enewsletter_groups']));
				$email_newsletter->add_members_to_groups($member_id, $memeber_groups);
			}
		}
	}
}
$ulp_enewsletter = new ulp_enewsletter_class();
?>