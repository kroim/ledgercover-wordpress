<?php
/* SendPress integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_sendpress_class {
	var $default_popup_options = array(
		'sendpress_enable' => "off",
		'sendpress_listid' => ""
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
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('SendPress Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">';
		if (class_exists('SendPress_Data')) {
			$lists = SendPress_Data::get_lists();
			if (sizeof($lists->posts) == 0) {
				echo '
					<tr>
						<th>'.__('Enable SendPress', 'ulp').':</th>
						<td>'.__('Please <a href="admin.php?page=sp-subscribers">create</a> at least one list.', 'ulp').'</td>
					</tr>';
			} else {
				echo '
					<tr>
						<th>'.__('Enable SendPress', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_sendpress_enable" name="ulp_sendpress_enable" '.($popup_options['sendpress_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to SendPress', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to SendPress.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<select name="ulp_sendpress_listid" class="ic_input_m">';
				foreach ($lists->posts as $list) {
					echo '
								<option value="'.$list->ID.'"'.($list->ID == $popup_options['sendpress_listid'] ? ' selected="selected"' : '').'>'.$list->post_title.'</option>';
				}
				echo '
							</select>
							<br /><em>'.__('Select your List ID.', 'ulp').'</em>
						</td>
					</tr>';
			}
		} else {
			echo '
					<tr>
						<th>'.__('Enable SendPress', 'ulp').':</th>
						<td>'.__('Please install and activate <a target="_blank" href="https://wordpress.org/plugins/sendpress/">SendPress</a> plugin.', 'ulp').'</td>
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
		if (isset($ulp->postdata["ulp_sendpress_enable"])) $popup_options['sendpress_enable'] = "on";
		else $popup_options['sendpress_enable'] = "off";
		if ($popup_options['sendpress_enable'] == 'on') {
			if (empty($popup_options['sendpress_listid'])) $errors[] = __('Invalid SendPress List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_sendpress_enable"])) $popup_options['sendpress_enable'] = "on";
		else $popup_options['sendpress_enable'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if (class_exists('SendPress_Data')) {
			if ($popup_options['sendpress_enable'] == 'on') {
				try {
					SendPress_Data::subscribe_user($popup_options['sendpress_listid'], $_subscriber['{subscription-email}'], $_subscriber['{subscription-name}'], '');
				} catch (Exception $e) {
				}
			}
		}
	}
}
$ulp_sendpress = new ulp_sendpress_class();
?>