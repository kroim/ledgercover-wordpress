<?php
/* Subscribe via social networks integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_social2_class {
	var $facebook_sdk_loaded = false;
	var $options = array(
		"social2_facebook_js_disable" => "off",
		"social2_facebook_appid" => "",
		"social2_google_js_disable" => "off",
		"social2_google_clientid" => "",
		"social2_google_apikey" => ""
	);
	var $default_popup_options = array(
		"social2_facebook_color" => "#3b5998",
		"social2_facebook_label" => "",
		"social2_google_color" => "#d34836",
		"social2_google_label" => ""
	);
	function __construct() {
		$this->default_popup_options['social2_facebook_label'] = __('Subscribe with Facebook', 'ulp');
		$this->default_popup_options['social2_google_label'] = __('Subscribe with Google', 'ulp');
		$this->get_options();
		if (is_admin()) {
			add_action('ulp_options_show', array(&$this, 'options_show'));
			add_action('ulp_options_update', array(&$this, 'options_update'));
			add_action('ulp_popup_options_show', array(&$this, 'popup_options_show'));
			add_action('ulp_faq', array(&$this, 'faq'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('ulp_js_build_preview_content', array(&$this, 'js_build_preview_content'));
			add_action('ulp_js_build_preview_popup_style', array(&$this, 'js_build_preview_popup_style'));
			add_filter('ulp_export_full_popup_options', array(&$this, 'export_full_popup_options'), 10, 1);
			add_filter('ulp_helper_add_layer_items', array(&$this, 'helper_add_layer_items'), 10, 1);
			add_action('ulp_helper2_window', array(&$this, 'helper2_window'));
		} else {
			add_action('wp', array(&$this, 'front_init'), 15);
			add_filter('ulp_facebook_sdk_loaded', array(&$this, 'facebook_sdk_loaded'), 10, 1);
		}
		add_filter('ulp_front_popup_content', array(&$this, 'front_popup_content'), 10, 2);
		add_filter('ulp_front_popup_style', array(&$this, 'front_popup_style'), 10, 3);
		add_filter('ulp_front_inline_style', array(&$this, 'front_inline_style'), 10, 3);
	}
	function facebook_sdk_loaded($_use) {
		return $this->facebook_sdk_loaded || $_use;
	}
	function get_options() {
		foreach ($this->options as $key => $value) {
			$this->options[$key] = get_option('ulp_'.$key, $this->options[$key]);
		}
	}
	function update_options() {
		if (current_user_can('manage_options')) {
			foreach ($this->options as $key => $value) {
				update_option('ulp_'.$key, $value);
			}
		}
	}
	function populate_options() {
		foreach ($this->options as $key => $value) {
			if (isset($_POST['ulp_'.$key])) {
				$this->options[$key] = trim(stripslashes($_POST['ulp_'.$key]));
			}
		}
	}
	function options_show() {
		echo '
			<h3>'.__('Social Media API Settings', 'ulp').'</h3>
			<table class="ulp_useroptions">
				<tr>
					<th>'.__('Facebook SDK', 'ulp').':</th>
					<td>
						<input type="checkbox" id="ulp_social2_facebook_js_disable" name="ulp_social2_facebook_js_disable" '.($this->options['social2_facebook_js_disable'] == "on" ? 'checked="checked"' : '').'"> '.__('Disable Facebook SDK loading', 'ulp').'
						<br /><em>'.__('Do not load https://connect.facebook.net/en_US/sdk.js. If your theme or another plugin load Facebook SDK, you can turn it off to avoid conflicts.', 'ulp').'</em>
					</td>
				</tr>
				<tr>
					<th>'.__('Facebook App ID', 'ulp').'</th>
					<td>
						<input type="text" class="widefat" id="ulp_social2_facebook_appid" name="ulp_social2_facebook_appid" value="'.esc_html($this->options['social2_facebook_appid']).'">
						<br /><em>'.__('Enter Facebook App ID. <a target="_blank" href="https://layeredpopups.com/documentation/#social-media-api">How to obtain App ID.</a> If your theme or another plugin already use Facebook App ID, you can leave this field blank to avoid conflicts.', 'ulp').'</em>
					</td>
				</tr>
				<tr>
					<th>'.__('Google Client Library', 'ulp').':</th>
					<td>
						<input type="checkbox" id="ulp_social2_google_js_disable" name="ulp_social2_google_js_disable" '.($this->options['social2_google_js_disable'] == "on" ? 'checked="checked"' : '').'"> '.__('Disable Google Client Library loading', 'ulp').'
						<br /><em>'.__('Do not load https://apis.google.com/js/client.js. If your theme or another plugin load Google Client Library, you can turn it off to avoid conflicts.', 'ulp').'</em>
					</td>
				</tr>
				<tr>
					<th>'.__('Google Client ID', 'ulp').'</th>
					<td>
						<input type="text" class="widefat" id="ulp_social2_google_clientid" name="ulp_social2_google_clientid" value="'.esc_html($this->options['social2_google_clientid']).'">
						<br /><em>'.__('Enter Google Client ID. <a target="_blank" href="https://layeredpopups.com/documentation/#social-media-api">How to obtain Client ID.</a> If your theme or another plugin already use Facebook App ID, you can leave this field blank to avoid conflicts.', 'ulp').'</em>
					</td>
				</tr>
				<tr>
					<th>'.__('Google API Key', 'ulp').'</th>
					<td>
						<input type="text" class="widefat" id="ulp_social2_google_apikey" name="ulp_social2_google_apikey" value="'.esc_html($this->options['social2_google_apikey']).'">
						<br /><em>'.__('Enter Google API Key. <a target="_blank" href="https://layeredpopups.com/documentation/#social-media-api">How to obtain API Key.</a> If your theme or another plugin already use Google API Key, you can leave this field blank to avoid conflicts.', 'ulp').'</em>
					</td>
				</tr>
			</table>';
		
	}
	function options_update() {
		$this->populate_options();
		if (isset($_POST['ulp_social2_facebook_js_disable'])) $this->options['social2_facebook_js_disable'] = 'on';
		else $this->options['social2_facebook_js_disable'] = 'off';
		if (isset($_POST['ulp_social2_google_js_disable'])) $this->options['social2_google_js_disable'] = 'on';
		else $this->options['social2_google_js_disable'] = 'off';
		$this->update_options();
	}
	function popup_options_show($_popup_options) {
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('"Subscribe with Social Media" Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Facebook Button label', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_social2_facebook_label" name="ulp_social2_facebook_label" value="'.esc_html($popup_options['social2_facebook_label']).'" class="widefat">
							<br /><em>'.__('Enter the label for "Subscribe with Facebook" button.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Facebook Button color', 'ulp').':</th>
						<td>
							<input type="text" class="ulp-color ic_input_number" name="ulp_social2_facebook_color" value="'.esc_html($popup_options['social2_facebook_color']).'" placeholder=""> 
							<br /><em>'.__('Set the "Subscribe with Facebook" button color.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Google Button label', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_social2_google_label" name="ulp_social2_google_label" value="'.esc_html($popup_options['social2_google_label']).'" class="widefat">
							<br /><em>'.__('Enter the label for "Subscribe with Google" button.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Google Button color', 'ulp').':</th>
						<td>
							<input type="text" class="ulp-color ic_input_number" name="ulp_social2_google_color" value="'.esc_html($popup_options['social2_google_color']).'" placeholder=""> 
							<br /><em>'.__('Set the "Subscribe with Google" button color.', 'ulp').'</em>
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
		if (strlen($popup_options['social2_facebook_label']) < 1) $errors[] = __('Facebook Button label is too short.', 'ulp');
		if (strlen($popup_options['social2_facebook_color']) == 0 || $ulp->get_rgb($popup_options['social2_facebook_color']) === false) $errors[] = __('Facebook Button color must be a valid value.', 'ulp');
		if (strlen($popup_options['social2_google_label']) < 1) $errors[] = __('Google Button label is too short.', 'ulp');
		if (strlen($popup_options['social2_google_color']) == 0 || $ulp->get_rgb($popup_options['social2_google_color']) === false) $errors[] = __('Google Button color must be a valid value.', 'ulp');
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
		return array_merge($_popup_options, $popup_options);
	}
	function js_build_preview_content() {
		global $ulp;
		if ($ulp->options['fa_enable'] == 'on' && $ulp->options['fa_brands_enable'] == 'on') {
			echo '
			var facebook_icon_html = "<i class=\'fab fa-facebook-f\'></i>&nbsp; ";
			var google_icon_html = "<i class=\'fab fa-google\'></i>&nbsp; ";';
		} else {
		echo '
			var facebook_icon_html = "";
			var google_icon_html = "";';
		}
		echo '
			var social2_facebook = "";
			if (jQuery("#ulp_button_inherit_size").is(":checked")) {
				social2_facebook = "<a class=\'ulp-preview-social ulp-inherited\' id=\'ulp-preview-social-facebook\'>"+facebook_icon_html+jQuery("#ulp_social2_facebook_label").val()+"</a>";
			} else {
				social2_facebook = "<a class=\'ulp-preview-social\' id=\'ulp-preview-social-facebook\'>"+facebook_icon_html+jQuery("#ulp_social2_facebook_label").val()+"</a>";
			}
			var social2_google = "";
			if (jQuery("#ulp_button_inherit_size").is(":checked")) {
				social2_google = "<a class=\'ulp-preview-social ulp-inherited\' id=\'ulp-preview-social-google\'>"+google_icon_html+jQuery("#ulp_social2_google_label").val()+"</a>";
			} else {
				social2_google = "<a class=\'ulp-preview-social\' id=\'ulp-preview-social-google\'>"+google_icon_html+jQuery("#ulp_social2_google_label").val()+"</a>";
			}
			content = content.replace("{subscription-facebook}", social2_facebook);
			content = content.replace("{subscription-google}", social2_google);';
	}
	function js_build_preview_popup_style() {
		global $ulp;
		echo '
			from_rgb = ulp_hex2rgb(jQuery("[name=\'ulp_social2_facebook_color\']").val());
			to_color = "transparent";
			from_color = "transparent";
			if (from_rgb) {
				var total = parseInt(from_rgb.r, 10)+parseInt(from_rgb.g, 10)+parseInt(from_rgb.b, 10);
				if (total == 0) total = 1;
				var to = {
					r : Math.max(0, parseInt(from_rgb.r, 10) - parseInt(48*from_rgb.r/total, 10)),
					g : Math.max(0, parseInt(from_rgb.g, 10) - parseInt(48*from_rgb.g/total, 10)),
					b : Math.max(0, parseInt(from_rgb.b, 10) - parseInt(48*from_rgb.b/total, 10))
				};
				from_color = jQuery("[name=\'ulp_social2_facebook_color\']").val();
				to_color = ulp_rgb2hex(to.r, to.g, to.b);
			}
			if (jQuery("#ulp_button_gradient").is(":checked")) {
				popup_style += "#ulp-preview-social-facebook,#ulp-preview-social-facebook:visited{background: "+from_color+";border:1px solid "+from_color+";background-image:linear-gradient("+to_color+","+from_color+");}";
				popup_style += "#ulp-preview-social-facebook:hover,#ulp-preview-social-facebook:active{background: "+to_color+";border:1px solid "+from_color+";background-image:linear-gradient("+from_color+","+to_color+");}";
			} else {
				popup_style += "#ulp-preview-social-facebook,#ulp-preview-social-facebook:visited{background: "+from_color+";border:1px solid "+from_color+";}";
				popup_style += "#ulp-preview-social-facebook:hover,#ulp-preview-social-facebook:active{background: "+to_color+";border:1px solid "+to_color+";}";
			}

			from_rgb = ulp_hex2rgb(jQuery("[name=\'ulp_social2_google_color\']").val());
			to_color = "transparent";
			from_color = "transparent";
			if (from_rgb) {
				var total = parseInt(from_rgb.r, 10)+parseInt(from_rgb.g, 10)+parseInt(from_rgb.b, 10);
				if (total == 0) total = 1;
				var to = {
					r : Math.max(0, parseInt(from_rgb.r, 10) - parseInt(48*from_rgb.r/total, 10)),
					g : Math.max(0, parseInt(from_rgb.g, 10) - parseInt(48*from_rgb.g/total, 10)),
					b : Math.max(0, parseInt(from_rgb.b, 10) - parseInt(48*from_rgb.b/total, 10))
				};
				from_color = jQuery("[name=\'ulp_social2_google_color\']").val();
				to_color = ulp_rgb2hex(to.r, to.g, to.b);
			}
			if (jQuery("#ulp_button_gradient").is(":checked")) {
				popup_style += "#ulp-preview-social-google,#ulp-preview-social-google:visited{background: "+from_color+";border:1px solid "+from_color+";background-image:linear-gradient("+to_color+","+from_color+");}";
				popup_style += "#ulp-preview-social-google:hover,#ulp-preview-social-google:active{background: "+to_color+";border:1px solid "+from_color+";background-image:linear-gradient("+from_color+","+to_color+");}";
			} else {
				popup_style += "#ulp-preview-social-google,#ulp-preview-social-google:visited{background: "+from_color+";border:1px solid "+from_color+";}";
				popup_style += "#ulp-preview-social-google:hover,#ulp-preview-social-google:active{background: "+to_color+";border:1px solid "+to_color+";}";
			}
			
			popup_style += ".ulp-preview-social,.ulp-preview-social:visited,.ulp-preview-social:hover,.ulp-preview-social:active{border-radius:"+parseInt(jQuery("[name=\'ulp_button_border_radius\']").val(), 10)+"px !important;}";
			if (jQuery("#ulp_button_css").val() != "") {
				popup_style += ".ulp-preview-social,.ulp-preview-social:visited{"+jQuery("#ulp_button_css").val()+"}";
			}
			if (jQuery("#ulp_button_css_hover").val() != "") {
				popup_style += ".ulp-preview-social:hover,.ulp-preview-social:active{"+jQuery("#ulp_button_css_hover").val()+"}";
			}
		';
	}
	function front_init() {
		global $wpdb, $post;
		add_action('wp_head', array(&$this, 'front_header'), 999);
		add_action('wp_enqueue_scripts', array(&$this, 'front_enqueue_scripts'), 10);
		add_action('wp_footer', array(&$this, 'front_footer'));
	}
	function front_enqueue_scripts() {
/*		if ($this->options['social2_facebook_js_disable'] != 'on' && !empty($this->options['social2_facebook_appid'])) {
			wp_deregister_script('facebooksdk');
			wp_register_script('facebooksdk', 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.8');
			wp_enqueue_script("facebooksdk", true, array(), ULP_VERSION, true);
		}*/
		wp_enqueue_script('ulp-social', plugins_url('/js/script-social.js', dirname(__FILE__)), array('ulp'), ULP_VERSION, true);
		if ($this->options['social2_google_js_disable'] != 'on' && !empty($this->options['social2_google_apikey'])) {
			wp_deregister_script('googleclient');
			wp_register_script('googleclient', 'https://apis.google.com/js/client.js?onload=ulp_google_load');
			wp_enqueue_script("googleclient", true, array(), ULP_VERSION, true);
		}
	}
	function front_footer() {
		if (!apply_filters('ulp_facebook_sdk_loaded', false)) {
			if ($this->options['social2_facebook_js_disable'] != 'on' && !empty($this->options['social2_facebook_appid'])) {
				echo '
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.8";
  fjs.parentNode.insertBefore(js, fjs);
}(document, "script", "facebook-jssdk"));</script>';
				$this->facebook_sdk_loaded = true;
			}
		}
	}
	function front_header() {
		echo '
		<style>.fb_iframe_widget_lift {width: 100% !important; height: 100% !important;}</style>
		<script>';
		if (!empty($this->options['social2_facebook_appid'])) {
			echo '
			var ulp_facebook_initialized = false;
			window.fbAsyncInit = function() {
				FB.init({
					appId      : "'.$this->options['social2_facebook_appid'].'",
					cookie     : true,
					xfbml      : true,
					version    : "v2.8"
				});
				ulp_facebook_initialized = true;
			};';
		} else {
			echo '
			var ulp_facebook_initialized = true;';
		}
		if (!empty($this->options['social2_google_apikey'])) {
			echo '
			var ulp_google_apikey = "'.esc_html($this->options['social2_google_apikey']).'";';
		}
		echo '
			var ulp_google_clientid = "'.esc_html($this->options['social2_google_clientid']).'";
		</script>';
	}
	function front_popup_style($_style, $_popup, $_args = '') {
		global $ulp;
		$sub_prefix = '';
		if (is_array($_args)) {
			if (array_key_exists('sub-prefix', $_args)) $sub_prefix = $_args['sub-prefix'];
		}
		$popup_options = unserialize($_popup['options']);
		if (is_array($popup_options)) $popup_options = array_merge($ulp->default_popup_options, $popup_options);
		else $popup_options = $ulp->default_popup_options;
		$popup_options = array_merge($this->default_popup_options, $popup_options);
		
		$style = '#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-button,#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-button:visited,#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-button:hover,#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-button:active{border-radius: '.intval($popup_options['button_border_radius']).'px !important;}';
		if (!empty($popup_options['button_css'])) {
			$style .= '#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-button,#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-button:visited{'.$popup_options['button_css'].'}';
		}
		if (!empty($popup_options['button_css_hover'])) {
			$style .= '#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-button:hover,#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-button:active{'.$popup_options['button_css_hover'].'}';
		}
		
		$from = $ulp->get_rgb($popup_options['social2_facebook_color']);
		$total = $from['r']+$from['g']+$from['b'];
		if ($total == 0) $total = 1;
		$to = array();
		$to['r'] = max(0, $from['r']-intval(48*$from['r']/$total));
		$to['g'] = max(0, $from['g']-intval(48*$from['g']/$total));
		$to['b'] = max(0, $from['b']-intval(48*$from['b']/$total));
		$to_color = '#'.($to['r'] < 16 ? '0' : '').dechex($to['r']).($to['g'] < 16 ? '0' : '').dechex($to['g']).($to['b'] < 16 ? '0' : '').dechex($to['b']);
		$from_color = $popup_options['social2_facebook_color'];
		if ($popup_options['button_gradient'] == 'on') {
			$style .= '#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-facebook,#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-facebook:visited{background: '.$from_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$to_color.','.$from_color.');}';
			$style .= '#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-facebook:hover,#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-facebook:active{background: '.$to_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$from_color.','.$to_color.');}';
		} else {
			$style .= '#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-facebook,#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-facebook:visited{background: '.$from_color.';border:1px solid '.$from_color.';}';
			$style .= '#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-facebook:hover,#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-facebook:active{background: '.$to_color.';border:1px solid '.$to_color.';}';
		}
		
		$from = $ulp->get_rgb($popup_options['social2_google_color']);
		$total = $from['r']+$from['g']+$from['b'];
		if ($total == 0) $total = 1;
		$to = array();
		$to['r'] = max(0, $from['r']-intval(48*$from['r']/$total));
		$to['g'] = max(0, $from['g']-intval(48*$from['g']/$total));
		$to['b'] = max(0, $from['b']-intval(48*$from['b']/$total));
		$to_color = '#'.($to['r'] < 16 ? '0' : '').dechex($to['r']).($to['g'] < 16 ? '0' : '').dechex($to['g']).($to['b'] < 16 ? '0' : '').dechex($to['b']);
		$from_color = $popup_options['social2_google_color'];
		if ($popup_options['button_gradient'] == 'on') {
			$style .= '#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-google,#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-google:visited{background: '.$from_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$to_color.','.$from_color.');}';
			$style .= '#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-google:hover,#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-google:active{background: '.$to_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$from_color.','.$to_color.');}';
		} else {
			$style .= '#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-google,#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-google:visited{background: '.$from_color.';border:1px solid '.$from_color.';}';
			$style .= '#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-google:hover,#ulp-'.$sub_prefix.$_popup['str_id'].' .ulp-submit-google:active{background: '.$to_color.';border:1px solid '.$to_color.';}';
		}
		
		return $_style.$style;
	}
	function front_inline_style($_style, $_inline_id, $_popup) {
		global $ulp;
		$popup_options = unserialize($_popup['options']);
		if (is_array($popup_options)) $popup_options = array_merge($ulp->default_popup_options, $popup_options);
		else $popup_options = $ulp->default_popup_options;
		$popup_options = array_merge($this->default_popup_options, $popup_options);
		
		$style = '#ulp-inline-'.$_inline_id.' .ulp-submit-button,#ulp-inline-'.$_inline_id.' .ulp-submit-button:visited,#ulp-inline-'.$_inline_id.' .ulp-submit-button:hover,#ulp-inline-'.$_inline_id.' .ulp-submit-button:active{border-radius: '.intval($popup_options['button_border_radius']).'px !important;}';
		if (!empty($popup_options['button_css'])) {
			$style .= '#ulp-inline-'.$_inline_id.' .ulp-submit-button,#ulp-inline-'.$_inline_id.' .ulp-submit-button:visited{'.$popup_options['button_css'].'}';
		}
		if (!empty($popup_options['button_css_hover'])) {
			$style .= '#ulp-inline-'.$_inline_id.' .ulp-submit-button:hover,#ulp-inline-'.$_inline_id.' .ulp-submit-button:active{'.$popup_options['button_css_hover'].'}';
		}
		
		$from = $ulp->get_rgb($popup_options['social2_facebook_color']);
		$total = $from['r']+$from['g']+$from['b'];
		if ($total == 0) $total = 1;
		$to = array();
		$to['r'] = max(0, $from['r']-intval(48*$from['r']/$total));
		$to['g'] = max(0, $from['g']-intval(48*$from['g']/$total));
		$to['b'] = max(0, $from['b']-intval(48*$from['b']/$total));
		$to_color = '#'.($to['r'] < 16 ? '0' : '').dechex($to['r']).($to['g'] < 16 ? '0' : '').dechex($to['g']).($to['b'] < 16 ? '0' : '').dechex($to['b']);
		$from_color = $popup_options['social2_facebook_color'];
		if ($popup_options['button_gradient'] == 'on') {
			$style .= '#ulp-inline-'.$_inline_id.' .ulp-submit-facebook,#ulp-inline-'.$_inline_id.' .ulp-submit-facebook:visited{background: '.$from_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$to_color.','.$from_color.');}';
			$style .= '#ulp-inline-'.$_inline_id.' .ulp-submit-facebook:hover,#ulp-inline-'.$_inline_id.' .ulp-submit-facebook:active{background: '.$to_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$from_color.','.$to_color.');}';
		} else {
			$style .= '#ulp-inline-'.$_inline_id.' .ulp-submit-facebook,#ulp-inline-'.$_inline_id.' .ulp-submit-facebook:visited{background: '.$from_color.';border:1px solid '.$from_color.';}';
			$style .= '#ulp-inline-'.$_inline_id.' .ulp-submit-facebook:hover,#ulp-inline-'.$_inline_id.' .ulp-submit-facebook:active{background: '.$to_color.';border:1px solid '.$to_color.';}';
		}
		
		$from = $ulp->get_rgb($popup_options['social2_google_color']);
		$total = $from['r']+$from['g']+$from['b'];
		if ($total == 0) $total = 1;
		$to = array();
		$to['r'] = max(0, $from['r']-intval(48*$from['r']/$total));
		$to['g'] = max(0, $from['g']-intval(48*$from['g']/$total));
		$to['b'] = max(0, $from['b']-intval(48*$from['b']/$total));
		$to_color = '#'.($to['r'] < 16 ? '0' : '').dechex($to['r']).($to['g'] < 16 ? '0' : '').dechex($to['g']).($to['b'] < 16 ? '0' : '').dechex($to['b']);
		$from_color = $popup_options['social2_google_color'];
		if ($popup_options['button_gradient'] == 'on') {
			$style .= '#ulp-inline-'.$_inline_id.' .ulp-submit-google,#ulp-inline-'.$_inline_id.' .ulp-submit-google:visited{background: '.$from_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$to_color.','.$from_color.');}';
			$style .= '#ulp-inline-'.$_inline_id.' .ulp-submit-google:hover,#ulp-inline-'.$_inline_id.' .ulp-submit-google:active{background: '.$to_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$from_color.','.$to_color.');}';
		} else {
			$style .= '#ulp-inline-'.$_inline_id.' .ulp-submit-google,#ulp-inline-'.$_inline_id.' .ulp-submit-google:visited{background: '.$from_color.';border:1px solid '.$from_color.';}';
			$style .= '#ulp-inline-'.$_inline_id.' .ulp-submit-google:hover,#ulp-inline-'.$_inline_id.' .ulp-submit-google:active{background: '.$to_color.';border:1px solid '.$to_color.';}';
		}
		
		return $_style.$style;
	}
	function front_popup_content($_content, $_popup_options) {
		global $ulp;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		$facebook_button = '<a class="ulp-submit-button ulp-submit-facebook'.($popup_options['button_inherit_size'] == 'on' ? ' ulp-inherited' : '').'" onclick="return ulp_subscribe_facebook(this);"'.($ulp->options['fa_enable'] == 'on' ? ' data-fa="fa-facebook"' : '').' data-label="'.esc_html($popup_options['social2_facebook_label']).'" data-loading="'.esc_html($popup_options['button_label_loading']).'">'.($ulp->options['fa_enable'] == 'on' && $ulp->options['fa_brands_enable'] == 'on' ? '<i class="fab fa-facebook-f"></i>&nbsp; ' : '').esc_html($popup_options['social2_facebook_label']).'</a>';
		$google_button = '<a class="ulp-submit-button ulp-submit-google'.($popup_options['button_inherit_size'] == 'on' ? ' ulp-inherited' : '').'" onclick="return ulp_subscribe_google(this);"'.($ulp->options['fa_enable'] == 'on' ? ' data-fa="fa-google"' : '').' data-label="'.esc_html($popup_options['social2_google_label']).'" data-loading="'.esc_html($popup_options['button_label_loading']).'">'.($ulp->options['fa_enable'] == 'on' && $ulp->options['fa_brands_enable'] == 'on' ? '<i class="fab fa-google"></i>&nbsp; ' : '').esc_html($popup_options['social2_google_label']).'</a>';
		$_content = str_replace(
			array('{subscription-facebook}', '{subscription-google}'),
			array($facebook_button, $google_button),
			$_content);
		return $_content;
	}
	function faq() {
		echo '
				<h3 id="ulp-facebook-app">'.__('How can can I use "Subscribe with Facebook" button?', 'ulp').'</h3>
				<p>
					If you want to use "Subscribe with Facebook" button, you must create Facebook App connected with your website.
					If you already have such App, please skip step 1 and start reading from step 2.
					<ol>
					<li>
						Go to <a href="https://developers.facebook.com/apps/" target="_blank">Facebook Apps</a> and create new application.
						Please watch the video below. It explains what settings are required for your application.<br />
						<iframe width="640" height="360" src="//www.youtube.com/embed/bIRPR_1ENKY?rel=0" frameborder="0" allowfullscreen></iframe>
					</li>
					<li>Go to <a href="admin.php?page=ulp-settings">Settings</a> page and set Facebook App ID.</li>
					<li>
						Use shortcode <code>{subscription-facebook}</code> to insert "Subscribe with Facebook" button into layer. 
						You can configure this button on Popup Editor page.
					</li>
					</ol>
				</p>
				<h3 id="ulp-google-app">'.__('How can can I use "Subscribe with Google" button?', 'ulp').'</h3>
				<p>
					If you want to use "Subscribe with Google" button, you must create Google Project connected with your website.
					If you already have such Project, please skip step 1 and start reading from step 2.
					<ol>
					<li>
						Go to <a href="https://console.developers.google.com/project?authuser=0" target="_blank">Google Developers Console</a> and create new project.
						Please watch the video below. It explains what settings are required for your project.<br />
						<iframe width="640" height="360" src="//www.youtube.com/embed/nP95OffQD0M?rel=0" frameborder="0" allowfullscreen></iframe>
					</li>
					<li>Go to <a href="admin.php?page=ulp-settings">Settings</a> page and set Google Client ID and Google API Key.</li>
					<li>
						Use shortcode <code>{subscription-google}</code> to insert "Subscribe with Google" button into layer. 
						You can configure this button on Popup Editor page.
					</li>
					</ol>
				</p>';
	}
	function export_full_popup_options($_popup_options) {
		return array_merge($_popup_options, $this->default_popup_options);
	}
	function helper_add_layer_items($_items) {
		$social_items = array(
			'subscription-facebook' => array(
				'icon' => 'fab fa-facebook-f',
				'label' => __('Subscribe with Facebook', 'ulp'),
				'comment' => __('Insert Facebook button.', 'ulp'),
				'unique' => '{subscription-facebook}'
			),
			'subscription-google' => array(
				'icon' => 'fab fa-google',
				'label' => __('Subscribe with Google', 'ulp'),
				'comment' => __('Insert Google button.', 'ulp'),
				'unique' => '{subscription-google}'
			)
		);
		if (!array_key_exists('social', $_items)) {
			$_items['social'] = array(
				'label' => __('Social Networks', 'ulp'),
				'items' => array()
			);
		}
		$_items['social']['items'] = array_merge($_items['social']['items'], $social_items);
		return $_items;
	}
	function helper2_window() {
		echo '
<script>
var ulp_social2_helper_add_layer_process;
if (typeof ulpext_helper_add_layer_process == "function") { 
	ulp_social2_helper_add_layer_process = ulpext_helper_add_layer_process;
}
ulpext_helper_add_layer_process = function(content_type) {
	if (typeof ulp_social2_helper_add_layer_process == "function") { 
		var result = ulp_social2_helper_add_layer_process(content_type);
		if (result) return true;
	}
	switch(content_type) {
		case "subscription-facebook":
			ulp_helper_close();
			ulp_neo_add_layer({"title":"Subscribe with Facebook","content":"{subscription-facebook}","width":"250","height":"50","content_align":"center","font_color":"#FFF","font_size":"15"});
			return true;
			break;
		case "subscription-google":
			ulp_helper_close();
			ulp_neo_add_layer({"title":"Subscribe with Google","content":"{subscription-google}","width":"250","height":"50","content_align":"center","font_color":"#FFF","font_size":"15"});
			return true;
			break;
		default:
			break;
	}
	return false;
}
</script>';
	}
}
$ulp_social2 = new ulp_social2_class();
?>