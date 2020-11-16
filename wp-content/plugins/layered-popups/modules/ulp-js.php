<?php
/* Custom popup event handlers for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_js_class {
	var $default_popup_options = array(
		"js_after_open_enable" => "off",
		"js_after_open" => "",
		"js_before_submit_enable" => "off",
		"js_before_submit" => "",
		"js_after_submit_success_enable" => "off",
		"js_after_submit_success" => "",
		"js_after_submit_fail_enable" => "off",
		"js_after_submit_fail" => "",
		"js_after_close_enable" => "off",
		"js_after_close" => ""
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_js_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
		}
		add_filter('ulp_front_popup_suffix', array(&$this, 'front_popup_suffix'), 10, 3);
	}
	function popup_options_tabs($_tabs) {
		if (!array_key_exists("js", $_tabs)) $_tabs["js"] = __('JavaScript Handlers', 'ulp');
		return $_tabs;
	}
	function popup_options_show($_popup_options) {
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('AfterOpen Handler', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_js_after_open_enable" name="ulp_js_after_open_enable" '.($popup_options['js_after_open_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Enable AfterOpen Handler', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want the code below to be executed after popup opened. Ignored for inline popups.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Handler', 'ulp').':</th>
						<td>
							<textarea id="ulp_js_after_open" name="ulp_js_after_open" class="widefat ulp-js-code">'.esc_html($popup_options['js_after_open']).'</textarea>
							<br /><em>'.__('JavaScript code which is executed after popup opened. Ignored for inline popups.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<h3>'.__('BeforeSubmit Handler', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_js_before_submit_enable" name="ulp_js_before_submit_enable" '.($popup_options['js_before_submit_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Enable BeforeSubmit Handler', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want the code below to be executed before popup form submitted.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Handler', 'ulp').':</th>
						<td>
							<textarea id="ulp_js_before_submit" name="ulp_js_before_submit" class="widefat ulp-js-code">'.esc_html($popup_options['js_before_submit']).'</textarea>
							<br /><em>'.__('JavaScript code which is executed before popup form submitted.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<h3>'.__('AfterSubmitSuccess Handler', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_js_after_submit_success_enable" name="ulp_js_after_submit_success_enable" '.($popup_options['js_after_submit_success_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Enable AfterSubmitSuccess Handler', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want the code below to be executed after popup form successfully submitted.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Handler', 'ulp').':</th>
						<td>
							<textarea id="ulp_js_after_submit_success" name="ulp_js_after_submit_success" class="widefat ulp-js-code">'.esc_html($popup_options['js_after_submit_success']).'</textarea>
							<br /><em>'.__('JavaScript code which is executed after popup form successfully submitted.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<h3>'.__('AfterSubmitFail Handler', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_js_after_submit_fail_enable" name="ulp_js_after_submit_fail_enable" '.($popup_options['js_after_submit_fail_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Enable AfterSubmitFail Handler', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want the code below to be executed after popup form submitted, but some errors were returned.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Handler', 'ulp').':</th>
						<td>
							<textarea id="ulp_js_after_submit_fail" name="ulp_js_after_submit_fail" class="widefat ulp-js-code">'.esc_html($popup_options['js_after_submit_fail']).'</textarea>
							<br /><em>'.__('JavaScript code which is executed after popup form submitted, but some errors were returned.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<h3>'.__('AfterClose Handler', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_js_after_close_enable" name="ulp_js_after_close_enable" '.($popup_options['js_after_close_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Enable AfterClose Handler', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want the code below to be executed after popup completely closed. Ignored for inline popups.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Handler', 'ulp').':</th>
						<td>
							<textarea id="ulp_js_after_close" name="ulp_js_after_close" class="widefat ulp-js-code">'.esc_html($popup_options['js_after_close']).'</textarea>
							<br /><em>'.__('JavaScript code which is executed after popup completely closed. Ignored for inline popups.', 'ulp').'</em>
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
		if (isset($ulp->postdata["ulp_js_after_open_enable"])) $popup_options['js_after_open_enable'] = "on";
		else $popup_options['js_after_open_enable'] = "off";
		if ($popup_options['js_after_open_enable'] == 'on') {
			if (empty($popup_options['js_after_open'])) $errors[] = __('AfterOpen Handler can not be empty.', 'ulp');
		}
		if (isset($ulp->postdata["ulp_js_before_submit_enable"])) $popup_options['js_before_submit_enable'] = "on";
		else $popup_options['js_before_submit_enable'] = "off";
		if ($popup_options['js_before_submit_enable'] == 'on') {
			if (empty($popup_options['js_before_submit'])) $errors[] = __('BeforeSubmit Handler can not be empty.', 'ulp');
		}
		if (isset($ulp->postdata["ulp_js_after_submit_success_enable"])) $popup_options['js_after_submit_success_enable'] = "on";
		else $popup_options['js_after_submit_success_enable'] = "off";
		if ($popup_options['js_after_submit_success_enable'] == 'on') {
			if (empty($popup_options['js_after_submit_success'])) $errors[] = __('AfterSubmitSuccess Handler can not be empty.', 'ulp');
		}
		if (isset($ulp->postdata["ulp_js_after_submit_fail_enable"])) $popup_options['js_after_submit_fail_enable'] = "on";
		else $popup_options['js_after_submit_fail_enable'] = "off";
		if ($popup_options['js_after_submit_fail_enable'] == 'on') {
			if (empty($popup_options['js_after_submit_fail'])) $errors[] = __('AfterSubmitFail Handler can not be empty.', 'ulp');
		}
		if (isset($ulp->postdata["ulp_js_after_close_enable"])) $popup_options['js_after_close_enable'] = "on";
		else $popup_options['js_after_close_enable'] = "off";
		if ($popup_options['js_after_close_enable'] == 'on') {
			if (empty($popup_options['js_after_close'])) $errors[] = __('AfterClose Handler can not be empty.', 'ulp');
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
		if (isset($ulp->postdata["ulp_js_after_open_enable"])) $popup_options['js_after_open_enable'] = "on";
		else $popup_options['js_after_open_enable'] = "off";
		if (isset($ulp->postdata["ulp_js_before_submit_enable"])) $popup_options['js_before_submit_enable'] = "on";
		else $popup_options['js_before_submit_enable'] = "off";
		if (isset($ulp->postdata["ulp_js_after_submit_success_enable"])) $popup_options['js_after_submit_success_enable'] = "on";
		else $popup_options['js_after_submit_success_enable'] = "off";
		if (isset($ulp->postdata["ulp_js_after_submit_fail_enable"])) $popup_options['js_after_submit_fail_enable'] = "on";
		else $popup_options['js_after_submit_fail_enable'] = "off";
		if (isset($ulp->postdata["ulp_js_after_close_enable"])) $popup_options['js_after_close_enable'] = "on";
		else $popup_options['js_after_close_enable'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function front_popup_suffix($_suffix, $_element_id, $_popup) {
		global $ulp;
		$popup_options = unserialize($_popup['options']);
		if (is_array($popup_options)) $popup_options = array_merge($ulp->default_popup_options, $popup_options);
		else $popup_options = $ulp->default_popup_options;
		$popup_options = array_merge($this->default_popup_options, $popup_options);
		$suffix = '
<script>
ulp_custom_handlers["'.$_element_id.'"] = {'.($popup_options['js_after_open_enable'] == 'on' ? '
	after_open:				function(){
		'.$popup_options['js_after_open'].'
	},': '').($popup_options['js_before_submit_enable'] == 'on' ? '
	before_submit:			function(){
		'.$popup_options['js_before_submit'].'
	},': '').($popup_options['js_after_submit_success_enable'] == 'on' ? '
	after_submit_success:	function(){
		'.$popup_options['js_after_submit_success'].'
	},': '').($popup_options['js_after_submit_fail_enable'] == 'on' ? '
	after_submit_fail:		function(){
		'.$popup_options['js_after_submit_fail'].'
	},': '').($popup_options['js_after_close_enable'] == 'on' ? '
	after_close:			function(){
		'.$popup_options['js_after_close'].'
	},': '').'
	element_id:		"'.$_element_id.'",
	popup_id:		"'.$_popup['str_id'].'",
	form:			{},
	errors:			{},
	user_data:		{}
};
</script>';
		return $_suffix.$suffix;
	}
}
$ulp_js = new ulp_js_class();
?>