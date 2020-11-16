<?php
/* Welcome E-mail for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_welcomemail_class {
	var $default_popup_options = array(
		"welcomemail_enable" => "off",
		"welcomemail_subject" => "",
		"welcomemail_message" => ""
	);
	function __construct() {
		$this->default_popup_options = array_merge($this->default_popup_options, array(
			"welcomemail_subject" => __('Thank you for subscription', 'ulp'),
			"welcomemail_message" => __('Dear {name},', 'ulp').PHP_EOL.PHP_EOL.__('Thank you for subscription.', 'ulp').PHP_EOL.PHP_EOL.__('Thanks,', 'ulp').PHP_EOL.get_bloginfo("name")
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
				<h3>'.__('Welcome E-mail Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable mailing', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_welcomemail_enable" name="ulp_welcomemail_enable" '.($popup_options['welcomemail_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Send Welcome E-mail', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to send welcome e-mail message to subscribers.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Subject', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_welcomemail_subject" name="ulp_welcomemail_subject" value="'.esc_html($popup_options['welcomemail_subject']).'" class="widefat">
							<br /><em>'.__('In case of successful subscription, subscribers may receive welcome e-mail message. This is subject field of the message.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Message', 'ulp').':</th>
						<td>
							<textarea id="ulp_welcomemail_message" name="ulp_welcomemail_message" class="widefat" style="height: 120px;">'.esc_html($popup_options['welcomemail_message']).'</textarea>
							<br /><em>'.__('This e-mail message is sent to subscribers in case of successful subscription. You can use the shortcodes ({subscription-email}, {subscription-name}, etc.).', 'ulp').'</em>
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
		if (isset($ulp->postdata["ulp_welcomemail_enable"])) $popup_options['welcomemail_enable'] = "on";
		else $popup_options['welcomemail_enable'] = "off";
		if ($popup_options['welcomemail_enable'] == 'on') {
			if (strlen($popup_options['welcomemail_subject']) < 3) $errors[] = __('Welcome E-mail subject must contain at least 3 characters', 'ulp');
			else if (strlen($popup_options['welcomemail_subject']) > 128) $errors[] = __('Welcome E-mail subject must contain maximum 128 characters', 'ulp');
			if (strlen($popup_options['welcomemail_message']) < 3) $errors[] = __('Welcome E-mail body must contain at least 3 characters', 'ulp');
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
		if (isset($ulp->postdata["ulp_welcomemail_enable"])) $popup_options['welcomemail_enable'] = "on";
		else $popup_options['welcomemail_enable'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['welcomemail_enable'] == 'on') {
			$body = strtr($popup_options['welcomemail_message'], $_subscriber);
			if ($ulp->options['from_type'] == 'html') {
				if (strpos(strtolower($body), '<html') === false) $body = str_replace(array("\n", "\r"), array("<br />", ""), $body);
				$mail_headers = "Content-Type: text/html; charset=UTF-8\r\n";
			} else {
				$mail_headers = "Content-Type: text/plain; charset=UTF-8\r\n";
			}
			if (isset($ulp)) {
				$mail_headers .= "From: ".(empty($ulp->options['from_name']) ? $ulp->options['from_email'] : $ulp->options['from_name'])." <".$ulp->options['from_email'].">\r\n";
			}
			$mail_headers .= "X-Mailer: PHP/".phpversion()."\r\n";
			$subject = strtr($popup_options['welcomemail_subject'], $_subscriber);
			wp_mail($_subscriber['{subscription-email}'], $subject, $body, $mail_headers);
		}
	}
}
$ulp_welcomemail = new ulp_welcomemail_class();
?>