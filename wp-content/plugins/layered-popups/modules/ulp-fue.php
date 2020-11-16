<?php
/* Follow-Up Emails integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_fue_class {
	var $default_popup_options = array(
		'fue_enable' => "off",
		'fue_lists' => array()
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
		global $ulp, $wpdb;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('Follow-Up Emails Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">';
		if (class_exists('Follow_Up_Emails')) {
			$lists = Follow_Up_Emails::instance()->newsletter->get_lists();
			echo '
					<tr>
						<th>'.__('Enable', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_fue_enable" name="ulp_fue_enable" '.($popup_options['fue_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit email address to Follow-Up Emails', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit email address to Follow-Up Emails.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Lists', 'ulp').':</th>
						<td>';
			if (empty($lists)) {
				echo __('No mailing lists found. You can create mailing list <a href="?page=followup-emails-subscribers&view=lists">here</a>.', 'ulp');
			} else {
				foreach ($lists as $list) {
					echo '
							<div style="margin-bottom: 5px;"><input type="checkbox" name="ulp_fue_list_'.$list['id'].'"'.(in_array($list['id'], $popup_options['fue_lists']) ? ' checked="checked"' : '').'> '.esc_html($list['list_name']).'</div>';
				}
				echo '
							<em>'.__('Please select lists where you want to submit email addresses to.', 'ulp').'</em>';
			}
			echo '
						</td>
					</tr>';
		} else {
			echo '
					<tr>
						<th>'.__('Enable Follow-Up Emails', 'ulp').':</th>
						<td>'.__('Please install and activate <a target="_blank" href="https://woocommerce.com/products/follow-up-emails/">Follow-Up Emails</a> plugin.', 'ulp').'</td>
					</tr>';
		
		}
		echo '
				</table>';
	}
	function popup_options_populate($_popup_options) {
		global $ulp;
		if (class_exists('Follow_Up_Emails')) {
			$popup_options = array();
			if (isset($ulp->postdata["ulp_fue_enable"])) $popup_options['fue_enable'] = "on";
			else $popup_options['fue_enable'] = "off";
			$popup_options['fue_lists'] = array();
			$lists = Follow_Up_Emails::instance()->newsletter->get_lists();
			if (!empty($lists)) {
				foreach ($lists as $list) {
					if (array_key_exists('ulp_fue_list_'.$list['id'], $ulp->postdata)) $popup_options['fue_lists'][] = $list['id'];
				}
			}
			return array_merge($_popup_options, $popup_options);
		}
		return $_popup_options;
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp, $wpdb;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if (class_exists('Follow_Up_Emails')) {
			if ($popup_options['fue_enable'] == 'on') {
				$lists = array();
				$raw_lists = Follow_Up_Emails::instance()->newsletter->get_lists();
				if (!empty($raw_lists)) {
					foreach ($raw_lists as $list) {
						if (in_array($list['id'], $popup_options['fue_lists'])) $lists[] = $list['id'];
					}
				}
				try {
					Follow_Up_Emails::instance()->newsletter->add_subscriber($_subscriber['{subscription-email}'], $lists);
				} catch (Exception $e) {
				}
			}
		}
	}
}
$ulp_fue = new ulp_fue_class();
?>