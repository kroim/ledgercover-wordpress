<?php
/* Admin Notification for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_mail_class {
	var $default_popup_options = array(
		"mail_enable" => "off",
		"mail_email" => "",
		"mail_subject" => "",
		"mail_message" => "",
		"mail_from" => "off"
	);
	function __construct() {
		$this->default_popup_options = array_merge($this->default_popup_options, array(
			"mail_subject" => __('Subscription/Contact form submitted', 'ulp'),
			"mail_message" => __('Subscription/Contact form submitted.', 'ulp').PHP_EOL.PHP_EOL.''.__('Name', 'ulp').': {subscription-name}'.PHP_EOL.''.__('E-mail', 'ulp').': {subscription-email}'.PHP_EOL.''.__('Phone #', 'ulp').': {subscription-phone}'.PHP_EOL.''.__('IP', 'ulp').': {ip}'.PHP_EOL.''.__('Popup', 'ulp').': {popup}'.PHP_EOL.''.__('URL', 'ulp').': {url}'.PHP_EOL.''.__('Message', 'ulp').':'.PHP_EOL.'{subscription-message}'.PHP_EOL.PHP_EOL.__('Thanks,', 'ulp').PHP_EOL.get_bloginfo("name")
		));
		if (is_admin()) {
			add_action('ulp_popup_options_mailing_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_filter('ulp_use_mailing', array(&$this, 'use_mailing'), 10, 1);
		}
		add_action('ulp_subscribe', array(&$this, 'subscribe'), 10, 2);
	}
	function use_mailing($_use) {
		return true || $_use;
	}
	function popup_options_show($_popup_options) {
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('Admin Notification Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable mailing', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mail_enable" name="ulp_mail_enable" '.($popup_options['mail_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Send details to admin', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to receive submitted contact details by e-mail.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Admin e-mail address', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mail_email" name="ulp_mail_email" value="'.esc_html($popup_options['mail_email']).'" class="widefat">
							<br /><em>'.__('Enter your e-mail address. Submitted contact details will be sent to this e-mail address. You can set several comma-separated e-mails.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Subject', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_mail_subject" name="ulp_mail_subject" value="'.esc_html($popup_options['mail_subject']).'" class="widefat">
							<br /><em>'.__('In case of successful subscription, administrator may receive notification message. This is subject field of the message.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Message', 'ulp').':</th>
						<td>
							<textarea id="ulp_mail_message" name="ulp_mail_message" class="widefat" style="height: 120px;">'.esc_html($popup_options['mail_message']).'</textarea>
							<br /><em>'.__('This notification is sent to administrator in case of successful subscription. You can use the shortcodes ({subscription-email}, {subscription-name}, etc.).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('"FROM" header', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mail_from" name="ulp_mail_from" '.($popup_options['mail_from'] == "on" ? 'checked="checked"' : '').'"> '.__("Put user's e-mail into FROM header (not recommended)", 'ulp').'
							<br /><em>'.__('Please remember, many hosting providers do not allow to send e-mails with 3rd party FROM header.', 'ulp').'</em>
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
		if (isset($ulp->postdata["ulp_mail_enable"])) $popup_options['mail_enable'] = "on";
		else $popup_options['mail_enable'] = "off";
		if ($popup_options['mail_enable'] == 'on') {
			$emails = explode(',', $popup_options['mail_email']);
			$emails_found = false;
			$emails_invalid = false;
			foreach ($emails as $email) {
				$email = trim($email);
				if (!empty($email)) {
					if (!preg_match("/^[_a-z0-9-+]+(\.[_a-z0-9-+]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,19})$/i", $email)) $emails_invalid = true;
					else $emails_found = true;
				}
			}
			if (!$emails_found) $errors[] = __('Admin e-mail must be valid e-mail address.', 'ulp');
			else if ($emails_invalid) $errors[] = __('Admin e-mail must be valid e-mail address.', 'ulp');
			if (strlen($popup_options['mail_subject']) < 3) $errors[] = __('Notification subject must contain at least 3 characters', 'ulp');
			else if (strlen($popup_options['mail_subject']) > 128) $errors[] = __('Notification subject must contain maximum 128 characters', 'ulp');
			if (strlen($popup_options['mail_message']) < 3) $errors[] = __('Notification body must contain at least 3 characters', 'ulp');
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
		if (isset($ulp->postdata["ulp_mail_enable"])) $popup_options['mail_enable'] = "on";
		else $popup_options['mail_enable'] = "off";
		if (isset($ulp->postdata["ulp_mail_from"])) $popup_options['mail_from'] = "on";
		else $popup_options['mail_from'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['mail_enable'] == 'on') {
			$body = strtr($popup_options['mail_message'], $_subscriber);
			if ($ulp->options['from_type'] == 'html') {
				if (strpos(strtolower($body), '<html') === false) $body = str_replace(array("\n", "\r"), array("<br />", ""), $body);
				$mail_headers = "Content-Type: text/html; charset=UTF-8\r\n";
			} else {
				$mail_headers = "Content-Type: text/plain; charset=UTF-8\r\n";
			}
			$from_set = false;
			if (!empty($_subscriber['{subscription-email}'])) {
				$mail_headers .= "Reply-To: ".(empty($_subscriber['{subscription-name}']) ? $_subscriber['{subscription-email}'] : $_subscriber['{subscription-name}'])." <".$_subscriber['{subscription-email}'].">\r\n";
				if ($popup_options['mail_from'] == 'on') {
					$from_set = true;
					$mail_headers .= "From: ".(empty($_subscriber['{subscription-name}']) ? $_subscriber['{subscription-email}'] : $_subscriber['{subscription-name}'])." <".$_subscriber['{subscription-email}'].">\r\n";
				}
			}
			if (isset($ulp) && !$from_set) {
				$mail_headers .= "From: ".(empty($ulp->options['from_name']) ? $ulp->options['from_email'] : $ulp->options['from_name'])." <".$ulp->options['from_email'].">\r\n";
			}
			$mail_emails = explode(',', $popup_options['mail_email']);
			$subject = strtr($popup_options['mail_subject'], $_subscriber);
			foreach ($mail_emails as $mail_email) {
				$mail_email = trim($mail_email);
				if (!empty($mail_email)) {
					if (preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,19})$/i", $mail_email)) {
						wp_mail($mail_email, $subject, $body, $mail_headers);					
					}
				}
			}
		}
	}
}
$ulp_mail = new ulp_mail_class();
?>