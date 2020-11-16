<?php
/* Mailbox Marketing integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_mailboxmarketing_class {
	var $default_popup_options = array(
		'mailboxmarketing_enable' => "off",
		'mailboxmarketing_userid' => "",
		'mailboxmarketing_listid' => "",
		'mailboxmarketing_email' => "",
		'mailboxmarketing_first_name' => "{subscription-name}",
		'mailboxmarketing_last_name' => ""
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
		global $ulp, $wpdb;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('Mailbox Marketing Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">';
		if (class_exists('Mailbox_Marketing')) {
			$current_user = wp_get_current_user();
			$user_id = $current_user->ID;
			$lists = $wpdb->get_results('SELECT id, list_name FROM '.$wpdb->prefix.'mailbox_marketing_lists WHERE user_id = '.intval($user_id).' ORDER BY id DESC', ARRAY_A);
			if (sizeof($lists) == 0) {
				echo '
					<tr>
						<th>'.__('Enable Mailbox Marketing', 'ulp').':</th>
						<td>'.__('Please', 'ulp').' <a href="'.admin_url('admin.php').'?page=mailbox_marketing">'.__('create', 'ulp').'</a> '.__('at least one list.', 'ulp').'</td>
					</tr>';
			} else {
				echo '
					<tr>
						<th>'.__('Enable Mailbox Marketing', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mailboxmarketing_enable" name="ulp_mailboxmarketing_enable" '.($popup_options['mailboxmarketing_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Mailbox Marketing', 'ulp').'
							<input type="hidden" id="ulp_mailboxmarketing_userid" name="ulp_mailboxmarketing_userid" value="'.$user_id.'" class="widefat" />
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Mailbox Marketing.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<select name="ulp_mailboxmarketing_listid" class="ic_input_m">';
				foreach ($lists as $list) {
					echo '
								<option value="'.$list['id'].'"'.($list['id'] == $popup_options['mailboxmarketing_listid'] ? ' selected="selected"' : '').'>'.esc_html($list['list_name']).'</option>';
				}
				echo '
							</select>
							<br /><em>'.__('Select your List ID.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-mailboxmarketing-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Mailbox Marketing fields with the popup fields.', 'ulp').'
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
											<input type="text" id="ulp_mailboxmarketing_first_name" name="ulp_mailboxmarketing_first_name" value="'.$popup_options['mailboxmarketing_first_name'].'" class="widefat" />
											<br /><em>'.__('First name.', 'ulp').'</em>
										</td>
									</tr>
									<tr>
										<td style="width: 120px;"><strong>'.__('Last name', 'ulp').':</strong></td>
										<td>
											<input type="text" id="ulp_mailboxmarketing_last_name" name="ulp_mailboxmarketing_last_name" value="'.$popup_options['mailboxmarketing_last_name'].'" class="widefat" />
											<br /><em>'.__('Last name.', 'ulp').'</em>
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>';
			}
		} else {
			echo '
					<tr>
						<th>'.__('Enable Mailbox Marketing', 'ulp').':</th>
						<td>'.__('Please install and activate <a target="_blank" href="https://codecanyon.net/item/mailbox-marketing-email-marketing-application-for-wordpress/18479734?ref=halfdata">Mailbox Marketing</a> plugin.', 'ulp').'</td>
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
		if (isset($ulp->postdata["ulp_mailboxmarketing_enable"])) $popup_options['mailboxmarketing_enable'] = "on";
		else $popup_options['mailboxmarketing_enable'] = "off";
		if ($popup_options['mailboxmarketing_enable'] == 'on') {
			if (empty($popup_options['mailboxmarketing_listid'])) $errors[] = __('Invalid Mailbox Marketing List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_mailboxmarketing_enable"])) $popup_options['mailboxmarketing_enable'] = "on";
		else $popup_options['mailboxmarketing_enable'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp, $wpdb;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if (class_exists('Mailbox_Marketing')) {
			if ($popup_options['mailboxmarketing_enable'] == 'on') {
				$list = $wpdb->get_results("SELECT id, list_name FROM ".$wpdb->prefix."mailbox_marketing_lists WHERE user_id = '".intval($popup_options['mailboxmarketing_userid'])."' AND id = '".intval($popup_options['mailboxmarketing_listid'])."'", ARRAY_A);
				if (!empty($list)) {
					try {
						$check = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->prefix."mailbox_marketing_list_subscribers WHERE user_id = '".intval($popup_options['mailboxmarketing_userid'])."' AND list_id = '".intval($popup_options['mailboxmarketing_listid'])."' AND email_address = '".esc_sql($_subscriber['{subscription-email}'])."'");
						if ($check > 0) {
							$sql = "UPDATE ".$wpdb->prefix."mailbox_marketing_list_subscribers SET 
								first_name = '".esc_sql(strtr($popup_options['mailboxmarketing_first_name'], $_subscriber))."',
								last_name = '".esc_sql(strtr($popup_options['mailboxmarketing_last_name'], $_subscriber))."'
								WHERE user_id = '".intval($popup_options['mailboxmarketing_userid'])."' AND list_id = '".intval($popup_options['mailboxmarketing_listid'])."' AND email_address = '".esc_sql($_subscriber['{subscription-email}'])."'";
						} else {
							$sql = "INSERT INTO ".$wpdb->prefix."mailbox_marketing_list_subscribers (
								user_id, list_id, first_name, last_name, email_address) VALUES (
								'".intval($popup_options['mailboxmarketing_userid'])."',
								'".intval($popup_options['mailboxmarketing_listid'])."',
								'".esc_sql(strtr($popup_options['mailboxmarketing_first_name'], $_subscriber))."',
								'".esc_sql(strtr($popup_options['mailboxmarketing_last_name'], $_subscriber))."',
								'".esc_sql($_subscriber['{subscription-email}'])."')";
						}
						$wpdb->query($sql);
					} catch (Exception $e) {
					}
				}
			}
		}
	}
}
$ulp_mailboxmarketing = new ulp_mailboxmarketing_class();
?>