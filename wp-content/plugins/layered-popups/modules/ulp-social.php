<?php
/* Social buttons integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_social_class {
	var $facebook_sdk_loaded = false;
	var $options = array(
		"social_facebook_js_disable" => "off",
		"social_google_js_disable" => "off",
		"social_twitter_js_disable" => "off",
		"social_linkedin_js_disable" => "off"
	);
	var $default_popup_options = array(
		"social_url" => "",
		"social_facebook_like" => "on",
		"social_google_plusone" => "on",
		"social_twitter_tweet" => "on",
		"social_linkedin_share" => "on",
		"social_margin" => 5
	);
	function __construct() {
		$this->default_popup_options['social_url'] = get_bloginfo('url');
		$this->get_options();
		if (is_admin()) {
			add_action('ulp_options_show', array(&$this, 'options_show'));
			add_action('ulp_options_update', array(&$this, 'options_update'));
			add_action('ulp_popup_options_show', array(&$this, 'popup_options_show'));
			add_action('ulp_faq', array(&$this, 'faq'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('ulp_js_build_preview_content', array(&$this, 'js_build_preview_content'));
			add_filter('ulp_export_full_popup_options', array(&$this, 'export_full_popup_options'), 10, 1);
			add_filter('ulp_helper_add_layer_items', array(&$this, 'helper_add_layer_items'), 10, 1);
			add_action('ulp_helper2_window', array(&$this, 'helper2_window'));
		} else {
			add_action('wp', array(&$this, 'front_init'), 15);
			add_filter('ulp_facebook_sdk_loaded', array(&$this, 'facebook_sdk_loaded'), 10, 1);
		}
		add_filter('ulp_front_popup_content', array(&$this, 'front_popup_content'), 10, 2);
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
			<h3>'.__('Social Buttons Settings', 'ulp').'</h3>
			<table class="ulp_useroptions">
				<tr>
					<td colspan="2">'.__('If your theme or another plugin load one of these scripts, you can turn it off to avoid conflicts.', 'ulp').'</td>
				</tr>
				<tr>
					<th>'.__('Social JavaScript libraries', 'ulp').':</th>
					<td>
						<input type="checkbox" id="ulp_social_facebook_js_disable" name="ulp_social_facebook_js_disable" '.($this->options['social_facebook_js_disable'] == "on" ? 'checked="checked"' : '').'"> '.__('Disable Facebook JS loading', 'ulp').'
						<br /><em>'.__('Do not load https://connect.facebook.net/en_US/sdk.js', 'ulp').'</em>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<input type="checkbox" id="ulp_social_google_js_disable" name="ulp_social_google_js_disable" '.($this->options['social_google_js_disable'] == "on" ? 'checked="checked"' : '').'"> '.__('Disable Google JS loading', 'ulp').'
						<br /><em>'.__('Do not load https://apis.google.com/js/plusone.js', 'ulp').'</em>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<input type="checkbox" id="ulp_social_twitter_js_disable" name="ulp_social_twitter_js_disable" '.($this->options['social_twitter_js_disable'] == "on" ? 'checked="checked"' : '').'"> '.__('Disable Twitter JS loading', 'ulp').'
						<br /><em>'.__('Do not load https://platform.twitter.com/widgets.js', 'ulp').'</em>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<input type="checkbox" id="ulp_social_linkedin_js_disable" name="ulp_social_linkedin_js_disable" '.($this->options['social_linkedin_js_disable'] == "on" ? 'checked="checked"' : '').'"> '.__('Disable LinkedIn JS loading', 'ulp').'
						<br /><em>'.__('Do not load https://platform.linkedin.com/in.js', 'ulp').'</em>
					</td>
				</tr>
			</table>';
		
	}
	function options_update() {
		$this->populate_options();
		if (isset($_POST['ulp_social_facebook_js_disable'])) $this->options['social_facebook_js_disable'] = 'on';
		else $this->options['social_facebook_js_disable'] = 'off';
		if (isset($_POST['ulp_social_google_js_disable'])) $this->options['social_google_js_disable'] = 'on';
		else $this->options['social_google_js_disable'] = 'off';
		if (isset($_POST['ulp_social_twitter_js_disable'])) $this->options['social_twitter_js_disable'] = 'on';
		else $this->options['social_twitter_js_disable'] = 'off';
		if (isset($_POST['ulp_social_linkedin_js_disable'])) $this->options['social_linkedin_js_disable'] = 'on';
		else $this->options['social_linkedin_js_disable'] = 'off';
		$this->update_options();
	}
	function popup_options_show($_popup_options) {
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('Social Buttons Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('URL to share/like/etc.', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_social_url" name="ulp_social_url" value="'.esc_html($popup_options['social_url']).'" class="widefat">
							<br /><em>'.__('Enter the URL attached to social buttons. This URL will be shared/liked/etc. Leave the field blank if you wish to share current URL.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Social Buttons', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_social_google_plusone" name="ulp_social_google_plusone" '.($popup_options['social_google_plusone'] == "on" ? 'checked="checked"' : '').'"> '.__('Enable Google +1 button', 'ulp').'<br />
							<input type="checkbox" id="ulp_social_facebook_like" name="ulp_social_facebook_like" '.($popup_options['social_facebook_like'] == "on" ? 'checked="checked"' : '').'"> '.__('Enable Facebook Like button', 'ulp').'<br />
							<input type="checkbox" id="ulp_social_twitter_tweet" name="ulp_social_twitter_tweet" '.($popup_options['social_twitter_tweet'] == "on" ? 'checked="checked"' : '').'"> '.__('Enable Twitter Tweet button', 'ulp').'<br />
							<input type="checkbox" id="ulp_social_linkedin_share" name="ulp_social_linkedin_share" '.($popup_options['social_linkedin_share'] == "on" ? 'checked="checked"' : '').'"> '.__('Enable LinkedIn Share button', 'ulp').'<br />
							<em>'.__('Enable desired social buttons.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Button left/right margin', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" class="ic_input_number" id="ulp_social_margin" name="ulp_social_margin" value="'.esc_html($popup_options['social_margin']).'"> '.__('pixels', 'ulp').'
							<br /><em>'.__('Enter left/right margin of social buttons.', 'ulp').'</em>
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
		if (isset($ulp->postdata["ulp_social_google_plusone"])) $popup_options['social_google_plusone'] = "on";
		else $popup_options['social_google_plusone'] = "off";
		if (isset($ulp->postdata["ulp_social_facebook_like"])) $popup_options['social_facebook_like'] = "on";
		else $popup_options['social_facebook_like'] = "off";
		if (isset($ulp->postdata["ulp_social_twitter_tweet"])) $popup_options['social_twitter_tweet'] = "on";
		else $popup_options['social_twitter_tweet'] = "off";
		if (isset($ulp->postdata["ulp_social_linkedin_share"])) $popup_options['social_linkedin_share'] = "on";
		else $popup_options['social_linkedin_share'] = "off";
		if (strlen($popup_options['social_url']) > 0 && !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $popup_options['social_url'])) $errors[] = __('Social URL must be a valid URL.', 'ulp');
		if (strlen($popup_options['social_margin']) > 0 && $popup_options['social_margin'] != preg_replace('/[^0-9]/', '', $popup_options['social_margin'])) $errors[] = __('Invalid social button margin.', 'ulp');
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
		if (isset($ulp->postdata["ulp_social_google_plusone"])) $popup_options['social_google_plusone'] = "on";
		else $popup_options['social_google_plusone'] = "off";
		if (isset($ulp->postdata["ulp_social_facebook_like"])) $popup_options['social_facebook_like'] = "on";
		else $popup_options['social_facebook_like'] = "off";
		if (isset($ulp->postdata["ulp_social_twitter_tweet"])) $popup_options['social_twitter_tweet'] = "on";
		else $popup_options['social_twitter_tweet'] = "off";
		if (isset($ulp->postdata["ulp_social_linkedin_share"])) $popup_options['social_linkedin_share'] = "on";
		else $popup_options['social_linkedin_share'] = "off";
		return array_merge($_popup_options, $popup_options);
	}
	function js_build_preview_content() {
		echo '
			var social_margin = parseInt(jQuery("#ulp_social_margin").val(), 10)
			var social_panel = "";
			var social_facebook_like = "";
			var social_google_plusone = "";
			var social_twitter_tweet = "";
			var social_linkedin_share = "";
			if (jQuery("#ulp_social_google_plusone").is(":checked")) {
				social_google_plusone = "<div class=\'ulp-social-button ulp-social-button-google-plusone\' style=\'margin-left:"+social_margin+"px !important;margin-right:"+social_margin+"px !important;\'><img src=\''.plugins_url('/images/google-plusone.png', dirname(__FILE__)).'\' alt=\'\'></div>";
				social_panel = social_panel + social_google_plusone;
			}
			if (jQuery("#ulp_social_facebook_like").is(":checked")) {
				social_facebook_like = "<div class=\'ulp-social-button ulp-social-button-facebook-like\' style=\'margin-left:"+social_margin+"px !important;margin-right:"+social_margin+"px !important;\'><img src=\''.plugins_url('/images/facebook-like.png', dirname(__FILE__)).'\' alt=\'\'></div>";
				social_panel = social_panel + social_facebook_like;
			}
			if (jQuery("#ulp_social_twitter_tweet").is(":checked")) {
				social_twitter_tweet = "<div class=\'ulp-social-button ulp-social-button-twitter-tweet\' style=\'margin-left:"+social_margin+"px !important;margin-right:"+social_margin+"px !important;\'><img src=\''.plugins_url('/images/twitter-tweet.png', dirname(__FILE__)).'\' alt=\'\'></div>";
				social_panel = social_panel + social_twitter_tweet;
			}
			if (jQuery("#ulp_social_linkedin_share").is(":checked")) {
				ulp_social_linkedin_share = "<div class=\'ulp-social-button ulp-social-button-linkedin-share\' style=\'margin-left:"+social_margin+"px !important;margin-right:"+social_margin+"px !important;\'><img src=\''.plugins_url('/images/linkedin-share.png', dirname(__FILE__)).'\' alt=\'\'></div>";
				social_panel = social_panel + ulp_social_linkedin_share;
			}
			content = content.replace("{social-panel}", social_panel);
			content = content.replace("{social-facebook-like}", social_facebook_like);
			content = content.replace("{social-google-plusone}", social_google_plusone);
			content = content.replace("{social-twitter-tweet}", social_twitter_tweet);
			content = content.replace("{social-linkedin-share}", ulp_social_linkedin_share);';
	}
	function front_init() {
		global $wpdb;
		add_action('wp_enqueue_scripts', array(&$this, 'front_enqueue_scripts'));
		add_action('wp_head', array(&$this, 'front_header'));
		add_action('wp_footer', array(&$this, 'front_footer'));
	}
	function front_header() {
		global $wpdb;
		echo '<style>.fb_iframe_widget_lift {width: 100% !important; height: 100% !important;}</style>';
	}
	function front_footer() {
		if (!apply_filters('ulp_facebook_sdk_loaded', false)) {
			if ($this->options['social_facebook_js_disable'] != 'on') {
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
	function front_enqueue_scripts() {
/*		if ($this->options['social_facebook_js_disable'] != 'on') {
			wp_deregister_script('facebooksdk');
			wp_register_script('facebooksdk', 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.0');
			wp_enqueue_script("facebooksdk", true, array(), ULP_VERSION, true);
		} */
		if ($this->options['social_google_js_disable'] != 'on') {
			wp_deregister_script('plusone');
			wp_register_script('plusone', 'https://apis.google.com/js/plusone.js');
			wp_enqueue_script("plusone", true, array(), ULP_VERSION, true);
		}
		if ($this->options['social_twitter_js_disable'] != 'on') {
			wp_deregister_script('twittersdk');
			wp_register_script('twittersdk', 'https://platform.twitter.com/widgets.js');
			wp_enqueue_script("twittersdk", true, array(), ULP_VERSION, true);
		}
		if ($this->options['social_linkedin_js_disable'] != 'on') {
			wp_deregister_script('linkedin');
			wp_register_script('linkedin', 'https://platform.linkedin.com/in.js');
			wp_enqueue_script("linkedin", true, array(), ULP_VERSION, true);
		}
	}
	function front_popup_content($_content, $_popup_options) {
		global $ulp;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		$social_buttons = '';
		$social_facebook_like = '';
		$social_google_plusone = '';
		$social_twitter_tweet = '';
		$social_linkedin_share = '';
		if (!empty($popup_options['social_url'])) $url = $popup_options['social_url'];
		else if ($ulp->options['no_preload'] != 'on') $url = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off' ? 'http://' : 'https://').$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
		else $url = $_SERVER['HTTP_REFERER'];
		if ($popup_options['social_google_plusone'] == 'on') {
			$social_google_plusone = '<div class="ulp-social-button ulp-social-button-google-plusone" style="margin:0 '.intval($popup_options['social_margin']).'px;"><g:plusone size="tall" callback="ulp_social_google_plusone" href="'.$url.'"></g:plusone></div>';
			$social_buttons .= $social_google_plusone;
		}
		if ($popup_options['social_facebook_like'] == 'on') {
			$social_facebook_like = '<div class="ulp-social-button ulp-social-button-facebook-like" style="margin:0 '.intval($popup_options['social_margin']).'px;"><fb:like id="fbLikeButton" href="'.$url.'" show_faces="false" layout="box_count"></fb:like></div>';
			$social_buttons .= $social_facebook_like;
		}
		if ($popup_options['social_twitter_tweet'] == 'on') {
			$social_twitter_tweet = '<div class="ulp-social-button ulp-social-button-twitter-tweet" style="margin:0 '.intval($popup_options['social_margin']).'px;"><a href="http://twitter.com/share" class="twitter-share-button" data-text="" data-url="'.$url.'" data-count="vertical">'.__('Tweet', 'ulp').'</a></div>';
			$social_buttons .= $social_twitter_tweet;
		}
		if ($popup_options['social_linkedin_share'] == 'on') {
			$social_linkedin_share = '<div class="ulp-social-button ulp-social-button-linkedin-share" style="margin:0 '.intval($popup_options['social_margin']).'px;"><script type="IN/Share" data-url="'.$url.'" data-counter="top" data-onsuccess="ulp_social_linkedin_share"></script></div>';
			$social_buttons .= $social_linkedin_share;
		}
		$_content = str_replace(
			array('{social-panel}', '{social-facebook-like}', '{social-google-plusone}', '{social-twitter-tweet}', '{social-linkedin-share}'),
			array($social_buttons, $social_facebook_like, $social_google_plusone, $social_twitter_tweet, $social_linkedin_share),
			$_content);
		return $_content;
	}
	function faq() {
		echo '
				<h3>'.__('How can I add social buttons (Facebook Like, Google +1, Twitter Tweet and LinkedIn Share)?', 'ulp').'</h3>
				<p>
					Use these shortcodes to insert relevant social button:
					<br /><code>{social-panel}</code> - insert all social buttons
					<br /><code>{social-facebook-like}</code> - insert Facebook Like button
					<br /><code>{social-google-plusone}</code> - insert Google +1 button
					<br /><code>{social-twitter-tweet}</code> - insert Twitter Tweet button
					<br /><code>{social-linkedin-share}</code> - insert LinkedIn Share button
					<br />All you have to do is to insert shortcodes into popup layers.
				</p>';
	}
	function export_full_popup_options($_popup_options) {
		return array_merge($_popup_options, $this->default_popup_options);
	}
	function helper_add_layer_items($_items) {
		$social_items = array(
			'social-panel' => array(
				'icon' => 'far fa-thumbs-up',
				'label' => __('Social Buttons', 'ulp'),
				'comment' => __('Insert a set of social buttons.', 'ulp'),
				'unique' => '{social-panel}'
			),
			'social-facebook-like' => array(
				'icon' => 'fab fa-facebook-f',
				'label' => __('Facebook Like', 'ulp'),
				'comment' => __('Insert Facebook Like button.', 'ulp'),
				'unique' => '{social-facebook-like}'
			),
			'social-google-plusone' => array(
				'icon' => 'fab fa-google-plus',
				'label' => __('Google +1', 'ulp'),
				'comment' => __('Insert Google +1 button.', 'ulp'),
				'unique' => '{social-google-plusone}'
			),
			'social-twitter-tweet' => array(
				'icon' => 'fab fa-twitter',
				'label' => __('Twitter Tweet', 'ulp'),
				'comment' => __('Insert Twitter Tweet button.', 'ulp'),
				'unique' => '{social-twitter-tweet}'
			),
			'social-linkedin-share' => array(
				'icon' => 'fab fa-linkedin',
				'label' => __('LinkedIn Share', 'ulp'),
				'comment' => __('Insert LinkedIn Share button.', 'ulp'),
				'unique' => '{social-linkedin-share}'
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
var ulp_social_helper_add_layer_process;
if (typeof ulpext_helper_add_layer_process == "function") { 
	ulp_social_helper_add_layer_process = ulpext_helper_add_layer_process;
}
ulpext_helper_add_layer_process = function(content_type) {
	if (typeof ulp_social_helper_add_layer_process == "function") { 
		var result = ulp_social_helper_add_layer_process(content_type);
		if (result) return true;
	}
	switch(content_type) {
		case "social-panel":
			ulp_helper_close();
			ulp_neo_add_layer({"title":"Social Panel","content":"{social-panel}"});
			return true;
			break;
		case "social-facebook-like":
			ulp_helper_close();
			ulp_neo_add_layer({"title":"Facebook Like","content":"{social-facebook-like}"});
			return true;
			break;
		case "social-google-plusone":
			ulp_helper_close();
			ulp_neo_add_layer({"title":"Google +1","content":"{social-google-plusone}"});
			return true;
			break;
		case "social-twitter-tweet":
			ulp_helper_close();
			ulp_neo_add_layer({"title":"Twitter Tweet","content":"{social-twitter-tweet}"});
			return true;
			break;
		case "social-linkedin-share":
			ulp_helper_close();
			ulp_neo_add_layer({"title":"LinkedIn Share","content":"{social-linkedin-share}"});
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
$ulp_social = new ulp_social_class();
?>