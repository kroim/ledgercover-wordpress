<?php
/* Subscribe2 integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_subscribe2_class {
	var $default_popup_options = array(
		"subscribe2_enable" => "off",
		"subscribe2_double" => "off"
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
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
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('Subscribe2 Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">';
		if (class_exists('s2class') || class_exists('S2_Core')) {
			echo '
					<tr>
						<th>'.__('Enable Subscribe2', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_subscribe2_enable" name="ulp_subscribe2_enable" '.($popup_options['subscribe2_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Subscribe2', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Subscribe2.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Double opt-in', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_subscribe2_double" name="ulp_subscribe2_double" '.($popup_options['subscribe2_double'] == "on" ? 'checked="checked"' : '').'"> '.__('Ask users to confirm their subscription', 'ulp').'
							<br /><em>'.__('Control whether a double opt-in confirmation message is sent.', 'ulp').'</em>
						</td>
					</tr>';
		} else {
			echo '
					<tr>
						<th>'.__('Enable Subscribe2', 'ulp').':</th>
						<td>'.__('Please install and activate <a target="_blank" href="https://wordpress.org/plugins/subscribe2/">Subscribe2</a> plugin.', 'ulp').'</td>
					</tr>';
		}
		echo '
				</table>';
	}
	function popup_options_populate($_popup_options) {
		global $ulp;
		$popup_options = array();
		if (isset($ulp->postdata["ulp_subscribe2_double"])) $popup_options['subscribe2_double'] = "on";
		else $popup_options['subscribe2_double'] = "off";
		if (isset($ulp->postdata["ulp_subscribe2_enable"])) $popup_options['subscribe2_enable'] = "on";
		else $popup_options['subscribe2_enable'] = "off";
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $wpdb;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if (class_exists('s2class') || class_exists('S2_Core')) {
			if ($popup_options['subscribe2_enable'] == 'on') {
				if (class_exists('s2class')) {
					$s2 = new s2class();
					$s2->public = $wpdb->prefix.'subscribe2';
					$s2->add($_subscriber['{subscription-email}'], ($popup_options['subscribe2_enable'] == 'on' ? true : false));
				} else {
					global $mysubscribe2;
					$mysubscribe2->add($_subscriber['{subscription-email}'], ($popup_options['subscribe2_enable'] == 'on' ? true : false));
				}
			}
		}
	}
}
$ulp_subscribe2 = new ulp_subscribe2_class();
?>