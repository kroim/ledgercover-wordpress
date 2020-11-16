<?php
/* Jetpack Subscriptions integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_jetpack_class {
	var $default_popup_options = array(
		'jetpack_enable' => "off"
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
		global $ulp;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('Jetpack Subscriptions Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">';
		if (class_exists('Jetpack_Subscriptions')) {
			echo '
					<tr>
						<th>'.__('Enable Jetpack Subscriptions', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_jetpack_enable" name="ulp_jetpack_enable" '.($popup_options['jetpack_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Jetpack Subscriptions', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Jetpack Subscriptions.', 'ulp').'</em>
						</td>
					</tr>';
		} else if (class_exists('Jetpack')) {
			echo '
					<tr>
						<th>'.__('Enable Jetpack Subscriptions', 'ulp').':</th>
						<td>'.__('Please <a href="'.admin_url('admin.php').'?page=jetpack">Connect Jetpack</a> firstly.', 'ulp').'</td>
					</tr>';
		} else {
			echo '
					<tr>
						<th>'.__('Enable Jetpack Subscriptions', 'ulp').':</th>
						<td>'.__('Please install and activate <a target="_blank" href="https://wordpress.org/plugins/jetpack/">Jetpack by WordPress.com</a> plugin.', 'ulp').'</td>
					</tr>';
		}
		echo '
				</table>';
	}
	function popup_options_populate($_popup_options) {
		global $ulp;
		$popup_options = array();
		if (isset($ulp->postdata["ulp_jetpack_enable"])) $popup_options['jetpack_enable'] = "on";
		else $popup_options['jetpack_enable'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if (class_exists('Jetpack_Subscriptions')) {
			if ($popup_options['jetpack_enable'] == 'on') {
				$subscribe = Jetpack_Subscriptions::subscribe($_subscriber['{subscription-email}'], 0, false);
			}
		}
	}
}
$ulp_jetpack = new ulp_jetpack_class();
?>