<?php
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_ajax_class {
	function __construct() {
		if (is_admin()) {
			add_action("widgets_init", array(&$this, 'widgets_init'));
			add_action('wp_ajax_ulp_handle_tinymce_button', array(&$this, "handle_tinymce_button"));
			add_action('wp_ajax_ulp_save_popup', array(&$this, "save_popup"));
			add_action('wp_ajax_ulp_save_campaign', array(&$this, "save_campaign"));
			add_action('wp_ajax_ulp-cookies-reset', array(&$this, "reset_cookie"));
			add_action('wp_ajax_ulp_wordfence_whitelist_ip', array(&$this, "wordfence_whitelist_ip"));
			add_action('wp_ajax_ulp_save_settings', array(&$this, "save_settings"));
			add_action('wp_ajax_ulp_save_ext_settings', array(&$this, "save_ext_settings"));
			add_action('wp_ajax_ulp_subscribe', array(&$this, "subscribe"));
			add_action('wp_ajax_nopriv_ulp_subscribe', array(&$this, "subscribe"));
			add_action('wp_ajax_ulp_share', array(&$this, "share"));
			add_action('wp_ajax_nopriv_ulp_share', array(&$this, "share"));
			add_action('wp_ajax_ulp_addimpression', array(&$this, "add_impression"));
			add_action('wp_ajax_nopriv_ulp_addimpression', array(&$this, "add_impression"));
			add_action('wp_ajax_ulp_loadpopup', array(&$this, "load_popup"));
			add_action('wp_ajax_nopriv_ulp_loadpopup', array(&$this, "load_popup"));
			add_action('wp_ajax_ulp-load-inline-popups', array(&$this, "load_inline_popups"));
			add_action('wp_ajax_nopriv_ulp-load-inline-popups', array(&$this, "load_inline_popups"));
			add_action('wp_ajax_ulp-init', array(&$this, "init_event_popups"));
			add_action('wp_ajax_nopriv_ulp-init', array(&$this, "init_event_popups"));
			add_action('wp_ajax_ulp-settings-reset', array(&$this, "reset_settings"));
			add_action('wp_ajax_ulp-campaigns-stats', array(&$this, "admin_campaign_stats"));
			add_action('wp_ajax_ulp-subscribers-details', array(&$this, "admin_log_details"));
			/* Advanced Targeting - 2017-04-12 - begin */
			if (!defined('UAP_CORE')) {
				add_action('wp_ajax_ulp_targets_load', array(&$this, "targets_load"));
				add_action('wp_ajax_ulp_targets_get_taxonomies', array(&$this, "targets_get_taxonomies"));
				add_action('wp_ajax_ulp_targets_get_posts', array(&$this, "targets_get_posts"));
				add_action('wp_ajax_ulp_targets_save', array(&$this, "targets_save"));
				add_action('wp_ajax_ulp_targets_save_list', array(&$this, "targets_save_list"));
			}
			/* Advanced Targeting - 2017-04-12 - end */
			/* Personal Data - 2018-05-26 - begin */
			include_once(dirname(__FILE__).'/core-personal-data.php');
			$ulp_personal_front = new ulp_personal_data_class();
			/* Personal Data - 2018-05-26 - end */
		}
	}
	
	/* Advanced Targeting - 2017-04-12 - begin */
	function targets_load() {
		global $wpdb;
		include_once(dirname(__FILE__).'/core-targeting.php');
		$targeting = new ulp_class_targeting();
		$targeting->admin_load();
		exit;
	}
	function targets_get_taxonomies() {
		global $wpdb;
		include_once(dirname(__FILE__).'/core-targeting.php');
		$targeting = new ulp_class_targeting();
		$targeting->admin_get_taxonomies();
		exit;
	}
	function targets_get_posts() {
		global $wpdb;
		include_once(dirname(__FILE__).'/core-targeting.php');
		$targeting = new ulp_class_targeting();
		$targeting->admin_get_posts();
		exit;
	}
	function targets_save() {
		global $wpdb;
		include_once(dirname(__FILE__).'/core-targeting.php');
		$targeting = new ulp_class_targeting();
		$targeting->admin_save();
		exit;
	}
	function targets_save_list() {
		global $wpdb;
		include_once(dirname(__FILE__).'/core-targeting.php');
		$targeting = new ulp_class_targeting();
		$targeting->admin_save_list();
		exit;
	}
	/* Advanced Targeting - 2017-04-12 - end */
	function reset_settings() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$return_object = array(
				'status' => 'OK',
				'settings' => 'off',
				'meta' => 'off'
			);
			if ($_REQUEST['settings'] == 'on') {
				$sql = "DELETE FROM ".$wpdb->prefix."options WHERE option_name LIKE 'ulp_%' AND option_name != 'ulp_purchase_code' AND option_name != 'ulp_version'";
				$wpdb->query($sql);
				$return_object['settings'] = 'on';
			}
			if ($_REQUEST['meta'] == 'on') {
				$sql = "DELETE FROM ".$wpdb->prefix."postmeta WHERE meta_key LIKE 'ulp_%'";
				$wpdb->query($sql);
				$return_object['meta'] = 'on';
			}
			echo json_encode($return_object);
		}
		exit;
	}
	function save_popup() {
		global $wpdb, $ulp;
		if (isset($_POST['ulp_postdata'])) {
			parse_str(base64_decode($_POST['ulp_postdata']), $ulp->postdata);
		} else $ulp->postdata = $_POST;
		$popup_options = array();
		if (current_user_can('manage_options')) {
			foreach ($ulp->default_popup_options as $key => $value) {
				if (array_key_exists('ulp_'.$key, $ulp->postdata)) {
					$popup_options[$key] = stripslashes(trim($ulp->postdata['ulp_'.$key]));
				}
			}
			if (isset($ulp->postdata["ulp_disable_overlay"])) $popup_options['disable_overlay'] = "on";
			else $popup_options['disable_overlay'] = "off";
			if (isset($ulp->postdata["ulp_enable_close"])) $popup_options['enable_close'] = "on";
			else $popup_options['enable_close'] = "off";
			if (isset($ulp->postdata["ulp_enable_enter"])) $popup_options['enable_enter'] = "on";
			else $popup_options['enable_enter'] = "off";
			if (isset($ulp->postdata["ulp_email_mandatory"])) $popup_options['email_mandatory'] = "on";
			else $popup_options['email_mandatory'] = "off";
			if (isset($ulp->postdata["ulp_name_mandatory"])) $popup_options['name_mandatory'] = "on";
			else $popup_options['name_mandatory'] = "off";
			if (isset($ulp->postdata["ulp_phone_mandatory"])) $popup_options['phone_mandatory'] = "on";
			else $popup_options['phone_mandatory'] = "off";
			if (isset($ulp->postdata["ulp_message_mandatory"])) $popup_options['message_mandatory'] = "on";
			else $popup_options['message_mandatory'] = "off";
			if (isset($ulp->postdata["ulp_button_gradient"])) $popup_options['button_gradient'] = "on";
			else $popup_options['button_gradient'] = "off";
			if (isset($ulp->postdata["ulp_button_inherit_size"])) $popup_options['button_inherit_size'] = "on";
			else $popup_options['button_inherit_size'] = "off";
			if (isset($ulp->postdata["ulp_input_icons"])) $popup_options['input_icons'] = "on";
			else $popup_options['input_icons'] = "off";
			if (isset($ulp->postdata["ulp_recaptcha_mandatory"])) $popup_options['recaptcha_mandatory'] = "on";
			else $popup_options['recaptcha_mandatory'] = "off";
			if (isset($ulp->postdata["ulp_doubleoptin_enable"])) $popup_options['doubleoptin_enable'] = "on";
			else $popup_options['doubleoptin_enable'] = "off";
			
			if (isset($ulp->postdata['ulp_id']) && $ulp->postdata['ulp_id'] != 0) {
				$popup_id = intval($ulp->postdata['ulp_id']);
				$popup_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE id = '".$popup_id."' AND deleted = '0'", ARRAY_A);
				if (empty($popup_details)) $popup_id = 0;
			} else $popup_id = 0;
			
			$errors = array();
			
			if (strlen($popup_options['title']) < 1) $errors[] = __('Popup title is too short.', 'ulp');
			if (strlen($popup_options['width']) > 0 && $popup_options['width'] != preg_replace('/[^0-9]/', '', $popup_options['width'])) $errors[] = __('Invalid popup basic width.', 'ulp');
			if (strlen($popup_options['height']) > 0 && $popup_options['height'] != preg_replace('/[^0-9]/', '', $popup_options['height'])) $errors[] = __('Invalid popup basic height.', 'ulp');
			if (strlen($popup_options['overlay_color']) > 0 && $ulp->get_rgb($popup_options['overlay_color']) === false) $errors[] = __('Ovarlay color must be a valid value.', 'ulp');
			if (floatval($popup_options['overlay_opacity']) < 0 || floatval($popup_options['overlay_opacity']) > 1) $errors[] = __('Overlay opacity must be in a range [0...1].', 'ulp');
			//if (strlen($popup_options['name_placeholder']) < 1) $errors[] = __('"Name" field placeholder is too short.', 'ulp');
			//if (strlen($popup_options['email_placeholder']) < 1) $errors[] = __('"E-mail" field placeholder is too short.', 'ulp');
			//if (strlen($popup_options['phone_placeholder']) < 1) $errors[] = __('"Phone number" field placeholder is too short.', 'ulp');
			//if (strlen($popup_options['message_placeholder']) < 1) $errors[] = __('"Message" text area placeholder is too short.', 'ulp');
			if (strlen($popup_options['input_border_color']) > 0 && $ulp->get_rgb($popup_options['input_border_color']) === false) $errors[] = __('Input field border color must be a valid value.', 'ulp');
			if (strlen($popup_options['input_background_color']) > 0 && $ulp->get_rgb($popup_options['input_background_color']) === false) $errors[] = __('Input field background color must be a valid value.', 'ulp');
			if (floatval($popup_options['input_background_opacity']) < 0 || floatval($popup_options['input_background_opacity']) > 1) $errors[] = __('Input field background opacity must be in a range [0...1].', 'ulp');
			//if (strlen($popup_options['button_label']) < 1) $errors[] = __('"Submit" button label is too short.', 'ulp');
			if (strlen($popup_options['button_color']) > 0 && $ulp->get_rgb($popup_options['button_color']) === false) $errors[] = __('"Submit" button color must be a valid value.', 'ulp');
			//if (strlen($popup_options['return_url']) > 0 && !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $popup_options['return_url'])) $errors[] = __('Redirect URL must be a valid URL.', 'ulp');
			if (strlen($popup_options['close_delay']) > 0 && $popup_options['close_delay'] != preg_replace('/[^0-9]/', '', $popup_options['close_delay'])) $errors[] = __('Invalid autoclose delay.', 'ulp');
			if (strlen($popup_options['cookie_lifetime']) > 0 && $popup_options['cookie_lifetime'] != preg_replace('/[^0-9]/', '', $popup_options['cookie_lifetime'])) $errors[] = __('Invalid cookie lifetime.', 'ulp');

			if (strlen($popup_options['input_border_width']) > 0 && $popup_options['input_border_width'] != preg_replace('/[^0-9]/', '', $popup_options['input_border_width'])) $errors[] = __('Invalid input field border width.', 'ulp');
			if (strlen($popup_options['input_border_radius']) > 0 && $popup_options['input_border_radius'] != preg_replace('/[^0-9]/', '', $popup_options['input_border_radius'])) $errors[] = __('Invalid input field border radius.', 'ulp');
			if (strlen($popup_options['button_border_radius']) > 0 && $popup_options['button_border_radius'] != preg_replace('/[^0-9]/', '', $popup_options['button_border_radius'])) $errors[] = __('Invalid "Submit" button border radius.', 'ulp');
			
			if ($ulp->options['mask_enable'] == 'on' && $popup_options['phone_mask'] == 'custom' && empty($popup_options['phone_custom_mask'])) $errors[] = __('Invalid phone custom mask.', 'ulp');
			
			if ($popup_options['doubleoptin_enable'] == 'on') {
				if (strlen($popup_options['doubleoptin_redirect_url']) > 0 && !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $popup_options['doubleoptin_redirect_url'])) $errors[] = __('Double Opt-In Thanksgiving URL must be a valid URL.', 'ulp');
				if (strpos($popup_options['doubleoptin_message'], '{confirmation-link}') === false) $errors[] = __('Double Opt-In Message must contain <strong>{confirmation-link}</strong> shortcode.', 'ulp');
			}
			
			if (!empty($popup_options['phone_length'])) {
				$lengths_raw = explode(',', $popup_options['phone_length']);
				$lengths = array();
				foreach ($lengths_raw as $length) {
					$length = intval(trim($length));
					if ($length > 0) $lengths[] = $length;
				}
				if (sizeof($lengths) > 0) $popup_options['phone_length'] = implode(', ', $lengths);
				else $popup_options['phone_length'] = '';
			}
			
			$errors = apply_filters('ulp_popup_options_check', $errors);

			$layers = explode(",",$ulp->postdata['ulp_layers']);
			$layer_set = array();
			$zindex = 0;
			if (empty($layers)) $errors[] = __('Create at least one layer.', 'ulp');
			else {
				foreach($layers as $layer_id) {
					$layer_options = array();
					foreach ($ulp->default_layer_options as $key => $value) {
						if (isset($ulp->postdata['ulp_layer_'.$layer_id.'_'.$key])) {
							$layer_options[$key] = stripslashes(trim($ulp->postdata['ulp_layer_'.$layer_id.'_'.$key]));
						}
					}
					if (strlen($layer_options['title']) < 1) $errors[] = __('Each layer must have the title.', 'ulp');
					else {
						if (strlen($layer_options['width']) > 0 && $layer_options['width'] != preg_replace('/[^0-9]/', '', $layer_options['width'])) $errors[] = __('Invalid layer width', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['height']) > 0 && $layer_options['height'] != preg_replace('/[^0-9]/', '', $layer_options['height'])) $errors[] = __('Invalid layer height', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['left']) == 0 || $layer_options['left'] != preg_replace('/[^0-9\-]/', '', $layer_options['left'])) $errors[] = __('Invalid left position', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['top']) == 0 || $layer_options['top'] != preg_replace('/[^0-9\-]/', '', $layer_options['top'])) $errors[] = __('Invalid top position', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['background_color']) > 0 && $ulp->get_rgb($layer_options['background_color']) === false) $errors[] = __('Background color must be a valid value', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['background_hover_color']) > 0 && $ulp->get_rgb($layer_options['background_hover_color']) === false) $errors[] = __('Background color:hover must be a valid value', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if ($layer_options['background_gradient'] == 'on') {
							if (strlen($layer_options['background_gradient_to']) > 0 && $ulp->get_rgb($layer_options['background_gradient_to']) === false) $errors[] = __('Background color must be a valid value', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
							if (strlen($layer_options['background_hover_gradient_to']) > 0 && $ulp->get_rgb($layer_options['background_hover_gradient_to']) === false) $errors[] = __('Background color:hover must be a valid value', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
							if (strlen($layer_options['background_gradient_angle']) == 0 || $layer_options['background_gradient_angle'] != preg_replace('/[^0-9]/', '', $layer_options['background_gradient_angle']) || $layer_options['background_gradient_angle'] > 360) $errors[] = __('Background gradient angle must be in a range [0...360]', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						}
						if (floatval($layer_options['background_opacity']) < 0 || floatval($layer_options['background_opacity']) > 1) $errors[] = __('Background opacity must be in a range [0...1]', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['background_image']) > 0 && !preg_match('~^((http(s)?://)|(//))[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$~i', $layer_options['background_image'])) $errors[] = __('Background image URL must be a valid URL', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['appearance_delay']) == 0 || $layer_options['appearance_delay'] != preg_replace('/[^0-9]/', '', $layer_options['appearance_delay']) || $layer_options['appearance_delay'] > 10000) $errors[] = __('Appearance start delay must be in a range [0...10000]', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['appearance_speed']) == 0 || $layer_options['appearance_speed'] != preg_replace('/[^0-9]/', '', $layer_options['appearance_speed']) || $layer_options['appearance_speed'] > 10000) $errors[] = __('Appearance duration speed must be in a range [0...10000]', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['font_color']) > 0 && $ulp->get_rgb($layer_options['font_color']) === false) $errors[] = __('Font color must be a valid value', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['font_hover_color']) > 0 && $ulp->get_rgb($layer_options['font_hover_color']) === false) $errors[] = __('Font hover color must be a valid value', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['font_size']) > 0 && $layer_options['font_size'] != preg_replace('/[^0-9]/', '', $layer_options['font_size']) && ($layer_options['font_size'] > 72 || $layer_options['font_size'] < 10)) $errors[] = __('Font size must be in a range [10...72]', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['text_shadow_color']) > 0 && $ulp->get_rgb($layer_options['text_shadow_color']) === false) $errors[] = __('Text shadow color must be a valid value', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['text_shadow_size']) > 0 && $layer_options['text_shadow_size'] != preg_replace('/[^0-9]/', '', $layer_options['text_shadow_size']) && $layer_options['text_shadow_size'] > 72) $errors[] = __('Text shadow size must be in a range [0...72]', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if ($layer_options['box_shadow'] == 'on') {
							if (strlen($layer_options['box_shadow_h']) == 0 || $layer_options['box_shadow_h'] != preg_replace('/[^0-9\-]/', '', $layer_options['box_shadow_h'])) $errors[] = __('Invalid H-Shadow value', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
							if (strlen($layer_options['box_shadow_v']) == 0 || $layer_options['box_shadow_v'] != preg_replace('/[^0-9\-]/', '', $layer_options['box_shadow_v'])) $errors[] = __('Invalid V-Shadow value', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
							if (strlen($layer_options['box_shadow_blur']) == 0 || $layer_options['box_shadow_blur'] != preg_replace('/[^0-9]/', '', $layer_options['box_shadow_blur'])) $errors[] = __('Invalid box shadow blur value', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
							if (strlen($layer_options['box_shadow_spread']) == 0 || $layer_options['box_shadow_spread'] != preg_replace('/[^0-9\-]/', '', $layer_options['box_shadow_spread'])) $errors[] = __('Invalid box shadow spread value', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
							if (strlen($layer_options['box_shadow_color']) == 0 || $ulp->get_rgb($layer_options['box_shadow_color']) === false) $errors[] = __('Box shadow color must be a valid value', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						}
						if (strlen($layer_options['border_width']) == 0 || $layer_options['border_width'] != preg_replace('/[^0-9]/', '', $layer_options['border_width'])) $errors[] = __('Invalid border width', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['border_radius']) == 0 || $layer_options['border_radius'] != preg_replace('/[^0-9]/', '', $layer_options['border_radius'])) $errors[] = __('Invalid border radius', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['border_color']) > 0 && $ulp->get_rgb($layer_options['border_color']) === false) $errors[] = __('Border color must be a valid value', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['border_hover_color']) > 0 && $ulp->get_rgb($layer_options['border_hover_color']) === false) $errors[] = __('Border hover color must be a valid value', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['padding_h']) == 0 || $layer_options['padding_h'] != preg_replace('/[^0-9\-]/', '', $layer_options['padding_h'])) $errors[] = __('Invalid horizontal padding', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
						if (strlen($layer_options['padding_v']) == 0 || $layer_options['padding_v'] != preg_replace('/[^0-9\-]/', '', $layer_options['padding_v'])) $errors[] = __('Invalid vertical padding', 'ulp').' ('.__('layer', 'ulp').': '.esc_html($layer_options['title']).').';
					}
					foreach ($layer_options as $key => $value) {
						$layer_options[$key] = str_replace(array($ulp->plugins_url.'/images/default', 'http://datastorage.pw/images'), array('ULP-DEMO-IMAGES-URL', 'ULP-DEMO-IMAGES-URL'), $layer_options[$key]);
					}
					$zindex++;
					$layer_options['index'] = $zindex;
					$layers_set[$layer_id] = $layer_options;
				}
			}
			
			if (!empty($errors)) {
				$return_object = array();
				$return_object['status'] = 'ERROR';
				$return_object['message'] = __('Attention! Please correct the errors below and try again.', 'ulp').'<ul><li>'.implode('</li><li>', $errors).'</li></ul>';
				echo json_encode($return_object);
				exit;
			}

			if ($ulp->options['spinkit_enable'] != 'on') {
				$popup_options['ajax_spinner'] = 'classic';
			}
			
			$popup_options = apply_filters('ulp_popup_options_populate', $popup_options);
			
			if ($popup_id > 0) {
				$sql = "UPDATE ".$wpdb->prefix."ulp_popups SET
					title = '".esc_sql($popup_options['title'])."',
					width = '".intval($popup_options['width'])."',
					height = '".intval($popup_options['height'])."',
					options = '".esc_sql(serialize($popup_options))."',
					deleted = '0'
					WHERE id = '".$popup_id."'";
				$wpdb->query($sql);
			} else {
				$str_id = $ulp->random_string(16);
				$sql = "INSERT INTO ".$wpdb->prefix."ulp_popups (str_id, title, width, height, options, created, blocked, deleted) VALUES (
				'".$str_id."', 
				'".esc_sql($popup_options['title'])."',
				'".intval($popup_options['width'])."', 
				'".intval($popup_options['height'])."', 
				'".esc_sql(serialize($popup_options))."',
				'".time()."', '0', '0')";
				$wpdb->query($sql);
				$popup_id = $wpdb->insert_id;
			}
			$sql = "UPDATE ".$wpdb->prefix."ulp_layers SET deleted = '1' WHERE popup_id = '".$popup_id."'";
			$wpdb->query($sql);
			foreach($layers_set as $layer_id => $layer_options) {
				if (substr($layer_id, 0, 4) == "new-") {
					$sql = "INSERT INTO ".$wpdb->prefix."ulp_layers (
						popup_id, title, content, zindex, details, created, deleted) VALUES (
						'".$popup_id."',
						'".esc_sql($layer_options['title'])."',
						'".esc_sql($layer_options['content'])."',
						'".esc_sql($layer_options['index'])."',
						'".esc_sql(serialize($layer_options))."',
						'".time()."', '0')";
				} else {
					$layer_id = intval($layer_id);
					$layer_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_layers WHERE id = '".$layer_id."' AND popup_id = '".$popup_id."'", ARRAY_A);
					if (!empty($layer_details)) {
						$sql = "UPDATE ".$wpdb->prefix."ulp_layers SET
							title = '".esc_sql($layer_options['title'])."',
							content = '".esc_sql($layer_options['content'])."',
							zindex = '".esc_sql($layer_options['index'])."',
							details = '".esc_sql(serialize($layer_options))."',
							deleted = '0'
							WHERE id = '".$layer_id."'";
					} else {
						$sql = "INSERT INTO ".$wpdb->prefix."ulp_layers (
							popup_id, title, content, zindex, details, created, deleted) VALUES (
							'".$popup_id."',
							'".esc_sql($layer_options['title'])."',
							'".esc_sql($layer_options['content'])."',
							'".esc_sql($layer_options['index'])."',
							'".esc_sql(serialize($layer_options))."',
							'".time()."', '0')";
					}
				}
				$wpdb->query($sql);
			}
			
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['id'] = $popup_id;
			$return_object['message'] = __('Popup details successfully <strong>saved</strong>.', 'ulp');
			echo json_encode($return_object);
			exit;
		}
	}

	function save_campaign() {
		global $wpdb, $ulp;
		if (current_user_can('manage_options')) {
			if (isset($_POST['ulp_id'])) {
				$id = intval($_POST['ulp_id']);
				$campaign_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_campaigns WHERE id = '".$id."' AND deleted = '0'", ARRAY_A);
			} else unset($campaign_details);
			if (isset($_POST['ulp_title'])) $title = stripslashes(trim($_POST['ulp_title']));
			else $title = '';
			
			$errors = array();
			if (strlen($title) < 1) $errors[] = __('Campaign title is too short.', 'ulp');

			$checked = false;
			$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0'", ARRAY_A);
			if (sizeof($popups) > 0) {
				foreach ($popups as $popup) {
					if (isset($_POST['ulp_popup_'.$popup['id']])) {
						$checked = true;
						break;
					}
				}
				if (!$checked) $errors[] = __('Select at least one popup for this campaign.', 'ulp');
			} else $errors[] = __('Create at least one popup.', 'ulp');
			
			if (!empty($errors)) {
				$return_object = array();
				$return_object['status'] = 'ERROR';
				$return_object['message'] = __('Attention! Please correct the errors below and try again.', 'ulp').'<ul><li>'.implode('</li><li>', $errors).'</li></ul>';
				echo json_encode($return_object);
				exit;
			}
			
			if (empty($campaign_details)) {
				$str_id = 'ab-'.$ulp->random_string(16);
				$sql = "INSERT INTO ".$wpdb->prefix."ulp_campaigns (
					title, str_id, details, created, blocked, deleted) VALUES (
					'".esc_sql($title)."',
					'".esc_sql($str_id)."',
					'', '".time()."', '0', '0')";
				$wpdb->query($sql);
				$campaign_id = $wpdb->insert_id;
				foreach ($popups as $popup) {
					if (isset($_POST['ulp_popup_'.$popup['id']])) {
						$sql = "INSERT INTO ".$wpdb->prefix."ulp_campaign_items (
							campaign_id, popup_id, impressions, clicks, created, deleted) VALUES (
							'".$campaign_id."',
							'".$popup['id']."',
							'0', '0', '".time()."', '0')";
						$wpdb->query($sql);
					}
				}
			} else {
				$campaign_id = $campaign_details['id'];
				$wpdb->query("UPDATE ".$wpdb->prefix."ulp_campaigns SET title = '".esc_sql($title)."' WHERE id = '".$campaign_details['id']."'");
				$wpdb->query("UPDATE ".$wpdb->prefix."ulp_campaign_items SET deleted = '1' WHERE campaign_id = '".$campaign_details['id']."'");
				foreach ($popups as $popup) {
					if (isset($_POST['ulp_popup_'.$popup['id']])) {
						$item_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_campaign_items WHERE campaign_id = '".$campaign_details['id']."' AND popup_id = '".$popup['id']."'", ARRAY_A);
						if (!empty($item_details)) {
							$sql = "UPDATE ".$wpdb->prefix."ulp_campaign_items SET deleted = '0' WHERE id = '".$item_details['id']."'";
							$wpdb->query($sql);
						} else {
							$sql = "INSERT INTO ".$wpdb->prefix."ulp_campaign_items (
								campaign_id, popup_id, impressions, clicks, created, deleted) VALUES (
								'".$campaign_details['id']."',
								'".$popup['id']."',
								'0', '0', '".time()."', '0')";
							$wpdb->query($sql);
						}
					}
				}
			}
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['id'] = $campaign_id;
			$return_object['message'] = __('Campaign details successfully <strong>saved</strong>.', 'ulp');
			echo json_encode($return_object);
			exit;
		}
	}

	function reset_cookie() {
		global $ulp;
		if (current_user_can('manage_options')) {
			$ulp->options["cookie_value"] = time();
			update_option('ulp_cookie_value', $ulp->options["cookie_value"]);
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['message'] = __('Cookies successfully reset.', 'ulp');
			echo json_encode($return_object);
		}
		exit;
	}

	function wordfence_whitelist_ip() {
		global $ulp;
		if (current_user_can('manage_options')) {
			if (class_exists(wordfence)) {
				try {
					wordfence::whitelistIP($_SERVER['REMOTE_ADDR']);
				} catch(Exception $e){
					die('IP-address '.$_SERVER['REMOTE_ADDR'].' was not whitelisted.');
				}
				echo 'OK';
			}
		} else {
			die('Seems you do not have rights to whitelist your IP-address.');
		}
		exit;
	}

	function save_settings() {
		global $wpdb, $ulp;
		$popup_options = array();
		if (current_user_can('manage_options')) {
			if (!defined('UAP_CORE')) {
				if (!empty($_POST['ulp_purchase_code']) && $_POST['ulp_purchase_code'] != $ulp->options['purchase_code']) {
					delete_option('_site_transient_update_plugins');
				}
			}
			$ulp->populate_options();
			if (isset($_POST['ulp_onexit_limits'])) $ulp->options['onexit_limits'] = 'on';
			else $ulp->options['onexit_limits'] = 'off';
			if (isset($_POST['ulp_email_validation'])) $ulp->options['email_validation'] = 'on';
			else $ulp->options['email_validation'] = 'off';
			if (isset($_POST['ulp_css3_enable'])) $ulp->options['css3_enable'] = 'on';
			else $ulp->options['css3_enable'] = 'off';
			if (isset($_POST['ulp_linkedbuttons_enable'])) $ulp->options['linkedbuttons_enable'] = 'on';
			else $ulp->options['linkedbuttons_enable'] = 'off';
			if (isset($_POST['ulp_spinkit_enable'])) $ulp->options['spinkit_enable'] = 'on';
			else $ulp->options['spinkit_enable'] = 'off';
			if (isset($_POST['ulp_ga_tracking'])) $ulp->options['ga_tracking'] = 'on';
			else $ulp->options['ga_tracking'] = 'off';
			if (isset($_POST['ulp_km_tracking'])) $ulp->options['km_tracking'] = 'on';
			else $ulp->options['km_tracking'] = 'off';
			if (isset($_POST['ulp_no_preload'])) $ulp->options['no_preload'] = 'on';
			else $ulp->options['no_preload'] = 'off';
			if (isset($_POST['ulp_preload_event_popups'])) $ulp->options['preload_event_popups'] = 'on';
			else $ulp->options['preload_event_popups'] = 'off';
			if (isset($_POST['ulp_fa_enable'])) $ulp->options['fa_enable'] = 'on';
			else $ulp->options['fa_enable'] = 'off';
			if (isset($_POST['ulp_fa_solid_enable'])) $ulp->options['fa_solid_enable'] = 'on';
			else $ulp->options['fa_solid_enable'] = 'off';
			if (isset($_POST['ulp_fa_regular_enable'])) $ulp->options['fa_regular_enable'] = 'on';
			else $ulp->options['fa_regular_enable'] = 'off';
			if (isset($_POST['ulp_fa_brands_enable'])) $ulp->options['fa_brands_enable'] = 'on';
			else $ulp->options['fa_brands_enable'] = 'off';
			if (isset($_POST["ulp_fa_css_disable"])) $ulp->options['fa_css_disable'] = "on";
			else $ulp->options['fa_css_disable'] = "off";
			if (isset($_POST['ulp_mask_enable'])) $ulp->options['mask_enable'] = 'on';
			else $ulp->options['mask_enable'] = 'off';
			if (isset($_POST["ulp_mask_js_disable"])) $ulp->options['mask_js_disable'] = "on";
			else $ulp->options['mask_js_disable'] = "off";
			if (isset($_POST['ulp_recaptcha_enable'])) $ulp->options['recaptcha_enable'] = 'on';
			else $ulp->options['recaptcha_enable'] = 'off';
			if (isset($_POST["ulp_recaptcha_js_disable"])) $ulp->options['recaptcha_js_disable'] = "on";
			else $ulp->options['recaptcha_js_disable'] = "off";
			
			$errors = array();
			if (strlen($ulp->options['onload_delay']) > 0 && $ulp->options['onload_delay'] != preg_replace('/[^0-9]/', '', $ulp->options['onload_delay'])) $errors[] = __('Invalid OnLoad delay value.', 'ulp');
			if (strlen($ulp->options['onload_close_delay']) > 0 && $ulp->options['onload_close_delay'] != preg_replace('/[^0-9]/', '', $ulp->options['onload_close_delay'])) $errors[] = __('Invalid OnLoad autoclosing delay value.', 'ulp');
			if (strlen($ulp->options['onscroll_offset']) > 0 && $ulp->options['onscroll_offset'] != preg_replace('/[^0-9]/', '', $ulp->options['onscroll_offset'])) $errors[] = __('Invalid OnScroll offset value.', 'ulp');
			if (strlen($ulp->options['onidle_delay']) == 0 || $ulp->options['onidle_delay'] != preg_replace('/[^0-9]/', '', $ulp->options['onidle_delay'])) $errors[] = __('Invalid OnInactivity period value.', 'ulp');
			if (apply_filters('ulp_use_mailing', false)) {
				if (!preg_match("/^[_a-z0-9-+]+(\.[_a-z0-9-+]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z0-9-]{2,19})$/i", $ulp->options['from_email']) || strlen($ulp->options['from_email']) == 0) $errors[] = __('Sender e-mail must be valid e-mail address.', 'ulp');
				if (strlen($ulp->options['from_name']) < 2) $errors[] = __('Sender name is too short.', 'ulp');
			}
			if (strlen($ulp->options['onload_period']) == 0 || $ulp->options['onload_period'] != preg_replace('/[^0-9]/', '', $ulp->options['onload_period']) || intval($ulp->options['onload_period']) < 1) $errors[] = __('Invalid OnLoad cookie period.', 'ulp');
			if (strlen($ulp->options['onscroll_period']) == 0 || $ulp->options['onscroll_period'] != preg_replace('/[^0-9]/', '', $ulp->options['onscroll_period']) || intval($ulp->options['onscroll_period']) < 1) $errors[] = __('Invalid OnScroll cookie period.', 'ulp');
			if (strlen($ulp->options['onexit_period']) == 0 || $ulp->options['onexit_period'] != preg_replace('/[^0-9]/', '', $ulp->options['onexit_period']) || intval($ulp->options['onexit_period']) < 1) $errors[] = __('Invalid OnExit cookie period.', 'ulp');
			if (strlen($ulp->options['onidle_period']) == 0 || $ulp->options['onidle_period'] != preg_replace('/[^0-9]/', '', $ulp->options['onidle_period']) || intval($ulp->options['onidle_period']) < 1) $errors[] = __('Invalid OnInactivity cookie period.', 'ulp');
			if (strlen($ulp->options['onabd_period']) == 0 || $ulp->options['onabd_period'] != preg_replace('/[^0-9]/', '', $ulp->options['onabd_period']) || intval($ulp->options['onabd_period']) < 1) $errors[] = __('Invalid OnAdBlockDetected cookie period.', 'ulp');
			
			if ($ulp->options['fa_enable'] == 'on') {
				if ($ulp->options['fa_solid_enable'] != 'on' && $ulp->options['fa_regular_enable'] != 'on') $errors[] = __('Either Solid Icons or Regular Icons must be enabled.', 'ulp');
			}
			if ($ulp->options['recaptcha_enable'] == 'on') {
				if (strlen($ulp->options['recaptcha_public_key']) == 0) $errors[] = __('reCAPTCHA public key can not be empty.', 'ulp');
				if (strlen($ulp->options['recaptcha_secret_key']) == 0) $errors[] = __('reCAPTCHA secret key can not be empty.', 'ulp');
			}
			
			$errors = apply_filters('ulp_options_check', $errors);
			
			if (!empty($errors)) {
				$return_object = array();
				$return_object['status'] = 'ERROR';
				$return_object['message'] = __('Attention! Please correct the errors below and try again.', 'ulp').'<ul><li>'.implode('</li><li>', $errors).'</li></ul>';
				echo json_encode($return_object);
				exit;
			}
			
			$ulp->options['purchase_code'] = preg_replace('/[^a-zA-Z0-9-]/', '', $ulp->options['purchase_code']);
			if ($_POST["ulp_onscroll_units"] == '%') {
				if ($ulp->options['onscroll_offset'] > 100) $ulp->options['onscroll_offset'] = '100';
				$ulp->options['onscroll_offset'] .= '%';
			}
			
			$ulp->update_options();
			
			do_action('ulp_options_update');
			
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['message'] = __('Settings successfully <strong>saved</strong>.', 'ulp');
			echo json_encode($return_object);
			exit;
		}
	}

	function save_ext_settings() {
		global $wpdb, $ulp;
		$popup_options = array();
		if (current_user_can('manage_options')) {
			$ulp->populate_ext_options();

			if (isset($_POST['ulp_ext_enable_algocheck'])) $ulp->ext_options['enable_algocheck'] = 'on';
			else $ulp->ext_options['enable_algocheck'] = 'off';
			if (isset($_POST['ulp_ext_enable_bulkemailchecker'])) $ulp->ext_options['enable_bulkemailchecker'] = 'on';
			else $ulp->ext_options['enable_bulkemailchecker'] = 'off';
			if (isset($_POST['ulp_ext_enable_thechecker'])) $ulp->ext_options['enable_thechecker'] = 'on';
			else $ulp->ext_options['enable_thechecker'] = 'off';
			if (isset($_POST['ulp_ext_enable_emaillistverify'])) $ulp->ext_options['enable_emaillistverify'] = 'on';
			else $ulp->ext_options['enable_emaillistverify'] = 'off';
			if (isset($_POST['ulp_ext_enable_kickbox'])) $ulp->ext_options['enable_kickbox'] = 'on';
			else $ulp->ext_options['enable_kickbox'] = 'off';
			if (isset($_POST['ulp_ext_enable_clearout'])) $ulp->ext_options['enable_clearout'] = 'on';
			else $ulp->ext_options['enable_clearout'] = 'off';
			if (isset($_POST['ulp_ext_enable_neverbounce'])) $ulp->ext_options['enable_neverbounce'] = 'on';
			else $ulp->ext_options['enable_neverbounce'] = 'off';
			if (isset($_POST['ulp_ext_enable_hunter'])) $ulp->ext_options['enable_hunter'] = 'on';
			else $ulp->ext_options['enable_hunter'] = 'off';
			if (isset($_POST['ulp_ext_enable_proofy'])) $ulp->ext_options['enable_proofy'] = 'on';
			else $ulp->ext_options['enable_proofy'] = 'off';
			if (isset($_POST['ulp_ext_enable_truemail'])) $ulp->ext_options['enable_truemail'] = 'on';
			else $ulp->ext_options['enable_truemail'] = 'off';
			
			if (isset($_POST['ulp_ext_enable_library'])) $ulp->ext_options['enable_library'] = 'on';
			else $ulp->ext_options['enable_library'] = 'off';
			if (isset($_POST['ulp_ext_enable_addons'])) $ulp->ext_options['enable_addons'] = 'on';
			else $ulp->ext_options['enable_addons'] = 'off';
			if (isset($_POST['ulp_ext_clean_database'])) $ulp->ext_options['clean_database'] = 'on';
			else $ulp->ext_options['clean_database'] = 'off';
			if (isset($_POST['ulp_ext_enable_social'])) $ulp->ext_options['enable_social'] = 'on';
			else $ulp->ext_options['enable_social'] = 'off';
			if (isset($_POST['ulp_ext_enable_social2'])) $ulp->ext_options['enable_social2'] = 'on';
			else $ulp->ext_options['enable_social2'] = 'off';
			if (isset($_POST['ulp_ext_enable_js'])) $ulp->ext_options['enable_js'] = 'on';
			else $ulp->ext_options['enable_js'] = 'off';
			if (isset($_POST['ulp_ext_enable_customfields'])) $ulp->ext_options['enable_customfields'] = 'on';
			else $ulp->ext_options['enable_customfields'] = 'off';
			if (isset($_POST['ulp_ext_enable_htmlform'])) $ulp->ext_options['enable_htmlform'] = 'on';
			else $ulp->ext_options['enable_htmlform'] = 'off';
			if (isset($_POST['ulp_ext_enable_wpuser'])) $ulp->ext_options['enable_wpuser'] = 'on';
			else $ulp->ext_options['enable_wpuser'] = 'off';
			if (isset($_POST['ulp_ext_enable_mailchimp'])) $ulp->ext_options['enable_mailchimp'] = 'on';
			else $ulp->ext_options['enable_mailchimp'] = 'off';
			if (isset($_POST['ulp_ext_enable_mailgun'])) $ulp->ext_options['enable_mailgun'] = 'on';
			else $ulp->ext_options['enable_mailgun'] = 'off';
			if (isset($_POST['ulp_ext_enable_bitrix24'])) $ulp->ext_options['enable_bitrix24'] = 'on';
			else $ulp->ext_options['enable_bitrix24'] = 'off';
			if (isset($_POST['ulp_ext_enable_birdsend'])) $ulp->ext_options['enable_birdsend'] = 'on';
			else $ulp->ext_options['enable_birdsend'] = 'off';
			if (isset($_POST['ulp_ext_enable_conversio'])) $ulp->ext_options['enable_conversio'] = 'on';
			else $ulp->ext_options['enable_conversio'] = 'off';
			if (isset($_POST['ulp_ext_enable_rapidmail'])) $ulp->ext_options['enable_rapidmail'] = 'on';
			else $ulp->ext_options['enable_rapidmail'] = 'off';
			if (isset($_POST['ulp_ext_enable_sendfox'])) $ulp->ext_options['enable_sendfox'] = 'on';
			else $ulp->ext_options['enable_sendfox'] = 'off';
			if (isset($_POST['ulp_ext_enable_omnisend'])) $ulp->ext_options['enable_omnisend'] = 'on';
			else $ulp->ext_options['enable_omnisend'] = 'off';
			if (isset($_POST['ulp_ext_enable_dotmailer'])) $ulp->ext_options['enable_dotmailer'] = 'on';
			else $ulp->ext_options['enable_dotmailer'] = 'off';
			if (isset($_POST['ulp_ext_enable_mnb'])) $ulp->ext_options['enable_mnb'] = 'on';
			else $ulp->ext_options['enable_mnb'] = 'off';
			if (isset($_POST['ulp_ext_enable_markethero'])) $ulp->ext_options['enable_markethero'] = 'on';
			else $ulp->ext_options['enable_markethero'] = 'off';
			if (isset($_POST['ulp_ext_enable_kirimemail'])) $ulp->ext_options['enable_kirimemail'] = 'on';
			else $ulp->ext_options['enable_kirimemail'] = 'off';
			if (isset($_POST['ulp_ext_enable_squalomail'])) $ulp->ext_options['enable_squalomail'] = 'on';
			else $ulp->ext_options['enable_squalomail'] = 'off';
			if (isset($_POST['ulp_ext_enable_unisender'])) $ulp->ext_options['enable_unisender'] = 'on';
			else $ulp->ext_options['enable_unisender'] = 'off';
			if (isset($_POST['ulp_ext_enable_moosend'])) $ulp->ext_options['enable_moosend'] = 'on';
			else $ulp->ext_options['enable_moosend'] = 'off';
			if (isset($_POST['ulp_ext_enable_zohocampaigns'])) $ulp->ext_options['enable_zohocampaigns'] = 'on';
			else $ulp->ext_options['enable_zohocampaigns'] = 'off';
			if (isset($_POST['ulp_ext_enable_zohocrm'])) $ulp->ext_options['enable_zohocrm'] = 'on';
			else $ulp->ext_options['enable_zohocrm'] = 'off';
			if (isset($_POST['ulp_ext_enable_mailigen'])) $ulp->ext_options['enable_mailigen'] = 'on';
			else $ulp->ext_options['enable_mailigen'] = 'off';
			if (isset($_POST['ulp_ext_enable_sendloop'])) $ulp->ext_options['enable_sendloop'] = 'on';
			else $ulp->ext_options['enable_sendloop'] = 'off';
			if (isset($_POST['ulp_ext_enable_perfit'])) $ulp->ext_options['enable_perfit'] = 'on';
			else $ulp->ext_options['enable_perfit'] = 'off';
			if (isset($_POST['ulp_ext_enable_newsletter2go'])) $ulp->ext_options['enable_newsletter2go'] = 'on';
			else $ulp->ext_options['enable_newsletter2go'] = 'off';
			if (isset($_POST['ulp_ext_enable_acellemail'])) $ulp->ext_options['enable_acellemail'] = 'on';
			else $ulp->ext_options['enable_acellemail'] = 'off';
			if (isset($_POST['ulp_ext_enable_mailfit'])) $ulp->ext_options['enable_mailfit'] = 'on';
			else $ulp->ext_options['enable_mailfit'] = 'off';
			if (isset($_POST['ulp_ext_enable_streamsend'])) $ulp->ext_options['enable_streamsend'] = 'on';
			else $ulp->ext_options['enable_streamsend'] = 'off';
			if (isset($_POST['ulp_ext_enable_vision6'])) $ulp->ext_options['enable_vision6'] = 'on';
			else $ulp->ext_options['enable_vision6'] = 'off';
			if (isset($_POST['ulp_ext_enable_mailleader'])) $ulp->ext_options['enable_mailleader'] = 'on';
			else $ulp->ext_options['enable_mailleader'] = 'off';
			if (isset($_POST['ulp_ext_enable_mpzmail'])) $ulp->ext_options['enable_mpzmail'] = 'on';
			else $ulp->ext_options['enable_mpzmail'] = 'off';
			if (isset($_POST['ulp_ext_enable_stampready'])) $ulp->ext_options['enable_stampready'] = 'on';
			else $ulp->ext_options['enable_stampready'] = 'off';
			if (isset($_POST['ulp_ext_enable_mautic'])) $ulp->ext_options['enable_mautic'] = 'on';
			else $ulp->ext_options['enable_mautic'] = 'off';
			if (isset($_POST['ulp_ext_enable_emailoctopus'])) $ulp->ext_options['enable_emailoctopus'] = 'on';
			else $ulp->ext_options['enable_emailoctopus'] = 'off';
			if (isset($_POST['ulp_ext_enable_intercom'])) $ulp->ext_options['enable_intercom'] = 'on';
			else $ulp->ext_options['enable_intercom'] = 'off';
			if (isset($_POST['ulp_ext_enable_firedrum'])) $ulp->ext_options['enable_firedrum'] = 'on';
			else $ulp->ext_options['enable_firedrum'] = 'off';
			if (isset($_POST['ulp_ext_enable_activetrail'])) $ulp->ext_options['enable_activetrail'] = 'on';
			else $ulp->ext_options['enable_activetrail'] = 'off';
			if (isset($_POST['ulp_ext_enable_userengage'])) $ulp->ext_options['enable_userengage'] = 'on';
			else $ulp->ext_options['enable_userengage'] = 'off';
			if (isset($_POST['ulp_ext_enable_pipedrive'])) $ulp->ext_options['enable_pipedrive'] = 'on';
			else $ulp->ext_options['enable_pipedrive'] = 'off';
			if (isset($_POST['ulp_ext_enable_jetpack'])) $ulp->ext_options['enable_jetpack'] = 'on';
			else $ulp->ext_options['enable_jetpack'] = 'off';
			if (isset($_POST['ulp_ext_enable_sgautorepondeur'])) $ulp->ext_options['enable_sgautorepondeur'] = 'on';
			else $ulp->ext_options['enable_sgautorepondeur'] = 'off';
			if (isset($_POST['ulp_ext_enable_hubspot'])) $ulp->ext_options['enable_hubspot'] = 'on';
			else $ulp->ext_options['enable_hubspot'] = 'off';
			if (isset($_POST['ulp_ext_enable_thenewsletterplugin'])) $ulp->ext_options['enable_thenewsletterplugin'] = 'on';
			else $ulp->ext_options['enable_thenewsletterplugin'] = 'off';
			if (isset($_POST['ulp_ext_enable_subscribe2'])) $ulp->ext_options['enable_subscribe2'] = 'on';
			else $ulp->ext_options['enable_subscribe2'] = 'off';
			if (isset($_POST['ulp_ext_enable_klaviyo'])) $ulp->ext_options['enable_klaviyo'] = 'on';
			else $ulp->ext_options['enable_klaviyo'] = 'off';
			if (isset($_POST['ulp_ext_enable_esputnik'])) $ulp->ext_options['enable_esputnik'] = 'on';
			else $ulp->ext_options['enable_esputnik'] = 'off';
			if (isset($_POST['ulp_ext_enable_easysendypro'])) $ulp->ext_options['enable_easysendypro'] = 'on';
			else $ulp->ext_options['enable_easysendypro'] = 'off';
			if (isset($_POST['ulp_ext_enable_cleverreach'])) $ulp->ext_options['enable_cleverreach'] = 'on';
			else $ulp->ext_options['enable_cleverreach'] = 'off';
			if (isset($_POST['ulp_ext_enable_mailkitchen'])) $ulp->ext_options['enable_mailkitchen'] = 'on';
			else $ulp->ext_options['enable_mailkitchen'] = 'off';
			if (isset($_POST['ulp_ext_enable_salesmanago'])) $ulp->ext_options['enable_salesmanago'] = 'on';
			else $ulp->ext_options['enable_salesmanago'] = 'off';
			if (isset($_POST['ulp_ext_enable_agilecrm'])) $ulp->ext_options['enable_agilecrm'] = 'on';
			else $ulp->ext_options['enable_agilecrm'] = 'off';
			if (isset($_POST['ulp_ext_enable_rocketresponder'])) $ulp->ext_options['enable_rocketresponder'] = 'on';
			else $ulp->ext_options['enable_rocketresponder'] = 'off';
			if (isset($_POST['ulp_ext_enable_simplycast'])) $ulp->ext_options['enable_simplycast'] = 'on';
			else $ulp->ext_options['enable_simplycast'] = 'off';
			if (isset($_POST['ulp_ext_enable_campayn'])) $ulp->ext_options['enable_campayn'] = 'on';
			else $ulp->ext_options['enable_campayn'] = 'off';
			if (isset($_POST['ulp_ext_enable_convertkit'])) $ulp->ext_options['enable_convertkit'] = 'on';
			else $ulp->ext_options['enable_convertkit'] = 'off';
			if (isset($_POST['ulp_ext_enable_totalsend'])) $ulp->ext_options['enable_totalsend'] = 'on';
			else $ulp->ext_options['enable_totalsend'] = 'off';
			if (isset($_POST['ulp_ext_enable_sendlane'])) $ulp->ext_options['enable_sendlane'] = 'on';
			else $ulp->ext_options['enable_sendlane'] = 'off';
			if (isset($_POST['ulp_ext_enable_emma'])) $ulp->ext_options['enable_emma'] = 'on';
			else $ulp->ext_options['enable_emma'] = 'off';
			if (isset($_POST['ulp_ext_enable_drip'])) $ulp->ext_options['enable_drip'] = 'on';
			else $ulp->ext_options['enable_drip'] = 'off';
			if (isset($_POST['ulp_ext_enable_sendinblue'])) $ulp->ext_options['enable_sendinblue'] = 'on';
			else $ulp->ext_options['enable_sendinblue'] = 'off';
			if (isset($_POST['ulp_ext_enable_klicktipp'])) $ulp->ext_options['enable_klicktipp'] = 'on';
			else $ulp->ext_options['enable_klicktipp'] = 'off';
			if (isset($_POST['ulp_ext_enable_sendpulse'])) $ulp->ext_options['enable_sendpulse'] = 'on';
			else $ulp->ext_options['enable_sendpulse'] = 'off';
			if (isset($_POST['ulp_ext_enable_mailjet'])) $ulp->ext_options['enable_mailjet'] = 'on';
			else $ulp->ext_options['enable_mailjet'] = 'off';
			if (isset($_POST['ulp_ext_enable_sendgrid'])) $ulp->ext_options['enable_sendgrid'] = 'on';
			else $ulp->ext_options['enable_sendgrid'] = 'off';
			if (isset($_POST['ulp_ext_enable_elasticemail'])) $ulp->ext_options['enable_elasticemail'] = 'on';
			else $ulp->ext_options['enable_elasticemail'] = 'off';
			if (isset($_POST['ulp_ext_enable_egoi'])) $ulp->ext_options['enable_egoi'] = 'on';
			else $ulp->ext_options['enable_egoi'] = 'off';
			if (isset($_POST['ulp_ext_enable_customerio'])) $ulp->ext_options['enable_customerio'] = 'on';
			else $ulp->ext_options['enable_customerio'] = 'off';
			if (isset($_POST['ulp_ext_enable_mailwizz'])) $ulp->ext_options['enable_mailwizz'] = 'on';
			else $ulp->ext_options['enable_mailwizz'] = 'off';
			if (isset($_POST['ulp_ext_enable_mumara'])) $ulp->ext_options['enable_mumara'] = 'on';
			else $ulp->ext_options['enable_mumara'] = 'off';
			if (isset($_POST['ulp_ext_enable_avangemail'])) $ulp->ext_options['enable_avangemail'] = 'on';
			else $ulp->ext_options['enable_avangemail'] = 'off';
			if (isset($_POST['ulp_ext_enable_mailautic'])) $ulp->ext_options['enable_mailautic'] = 'on';
			else $ulp->ext_options['enable_mailautic'] = 'off';
			if (isset($_POST['ulp_ext_enable_constantcontact'])) $ulp->ext_options['enable_constantcontact'] = 'on';
			else $ulp->ext_options['enable_constantcontact'] = 'off';
			if (isset($_POST['ulp_ext_enable_aweber'])) $ulp->ext_options['enable_aweber'] = 'on';
			else $ulp->ext_options['enable_aweber'] = 'off';
			if (isset($_POST['ulp_ext_enable_getresponse'])) $ulp->ext_options['enable_getresponse'] = 'on';
			else $ulp->ext_options['enable_getresponse'] = 'off';
			if (isset($_POST['ulp_ext_enable_icontact'])) $ulp->ext_options['enable_icontact'] = 'on';
			else $ulp->ext_options['enable_icontact'] = 'off';
			if (isset($_POST['ulp_ext_enable_madmimi'])) $ulp->ext_options['enable_madmimi'] = 'on';
			else $ulp->ext_options['enable_madmimi'] = 'off';
			if (isset($_POST['ulp_ext_enable_directmail'])) $ulp->ext_options['enable_directmail'] = 'on';
			else $ulp->ext_options['enable_directmail'] = 'off';
			if (isset($_POST['ulp_ext_enable_campaignmonitor'])) $ulp->ext_options['enable_campaignmonitor'] = 'on';
			else $ulp->ext_options['enable_campaignmonitor'] = 'off';
			if (isset($_POST['ulp_ext_enable_salesautopilot'])) $ulp->ext_options['enable_salesautopilot'] = 'on';
			else $ulp->ext_options['enable_salesautopilot'] = 'off';
			if (isset($_POST['ulp_ext_enable_sendy'])) $ulp->ext_options['enable_sendy'] = 'on';
			else $ulp->ext_options['enable_sendy'] = 'off';
			if (isset($_POST['ulp_ext_enable_benchmark'])) $ulp->ext_options['enable_benchmark'] = 'on';
			else $ulp->ext_options['enable_benchmark'] = 'off';
			if (isset($_POST['ulp_ext_enable_ontraport'])) $ulp->ext_options['enable_ontraport'] = 'on';
			else $ulp->ext_options['enable_ontraport'] = 'off';
			if (isset($_POST['ulp_ext_enable_mailerlite'])) $ulp->ext_options['enable_mailerlite'] = 'on';
			else $ulp->ext_options['enable_mailerlite'] = 'off';
			if (isset($_POST['ulp_ext_enable_mailrelay'])) $ulp->ext_options['enable_mailrelay'] = 'on';
			else $ulp->ext_options['enable_mailrelay'] = 'off';
			if (isset($_POST['ulp_ext_enable_activecampaign'])) $ulp->ext_options['enable_activecampaign'] = 'on';
			else $ulp->ext_options['enable_activecampaign'] = 'off';
			if (isset($_POST['ulp_ext_enable_mymail'])) $ulp->ext_options['enable_mymail'] = 'on';
			else $ulp->ext_options['enable_mymail'] = 'off';
			if (isset($_POST['ulp_ext_enable_fue'])) $ulp->ext_options['enable_fue'] = 'on';
			else $ulp->ext_options['enable_fue'] = 'off';
			if (isset($_POST['ulp_ext_enable_mailboxmarketing'])) $ulp->ext_options['enable_mailboxmarketing'] = 'on';
			else $ulp->ext_options['enable_mailboxmarketing'] = 'off';
			if (isset($_POST['ulp_ext_enable_enewsletter'])) $ulp->ext_options['enable_enewsletter'] = 'on';
			else $ulp->ext_options['enable_enewsletter'] = 'off';
			if (isset($_POST['ulp_ext_enable_arigatopro'])) $ulp->ext_options['enable_arigatopro'] = 'on';
			else $ulp->ext_options['enable_arigatopro'] = 'off';
			if (isset($_POST['ulp_ext_enable_mailpoet'])) $ulp->ext_options['enable_mailpoet'] = 'on';
			else $ulp->ext_options['enable_mailpoet'] = 'off';
			if (isset($_POST['ulp_ext_enable_tribulant'])) $ulp->ext_options['enable_tribulant'] = 'on';
			else $ulp->ext_options['enable_tribulant'] = 'off';
			if (isset($_POST['ulp_ext_enable_sendpress'])) $ulp->ext_options['enable_sendpress'] = 'on';
			else $ulp->ext_options['enable_sendpress'] = 'off';
			if (isset($_POST['ulp_ext_enable_ymlp'])) $ulp->ext_options['enable_ymlp'] = 'on';
			else $ulp->ext_options['enable_ymlp'] = 'off';
			if (isset($_POST['ulp_ext_enable_freshmail'])) $ulp->ext_options['enable_freshmail'] = 'on';
			else $ulp->ext_options['enable_freshmail'] = 'off';
			if (isset($_POST['ulp_ext_enable_sendreach'])) $ulp->ext_options['enable_sendreach'] = 'on';
			else $ulp->ext_options['enable_sendreach'] = 'off';
			if (isset($_POST['ulp_ext_enable_interspire'])) $ulp->ext_options['enable_interspire'] = 'on';
			else $ulp->ext_options['enable_interspire'] = 'off';
			if (isset($_POST['ulp_ext_enable_mail'])) $ulp->ext_options['enable_mail'] = 'on';
			else $ulp->ext_options['enable_mail'] = 'off';
			if (isset($_POST['ulp_ext_enable_welcomemail'])) $ulp->ext_options['enable_welcomemail'] = 'on';
			else $ulp->ext_options['enable_welcomemail'] = 'off';
			if (isset($_POST['ulp_ext_late_init'])) $ulp->ext_options['late_init'] = 'on';
			else $ulp->ext_options['late_init'] = 'off';
			if (isset($_POST['ulp_ext_inline_ajaxed'])) $ulp->ext_options['inline_ajaxed'] = 'on';
			else $ulp->ext_options['inline_ajaxed'] = 'off';
			if (isset($_POST['ulp_ext_log_data'])) $ulp->ext_options['log_data'] = 'on';
			else $ulp->ext_options['log_data'] = 'off';
			if (isset($_POST['ulp_ext_count_impressions'])) $ulp->ext_options['count_impressions'] = 'on';
			else $ulp->ext_options['count_impressions'] = 'off';
			if (isset($_POST['ulp_ext_async_init'])) $ulp->ext_options['async_init'] = 'on';
			else $ulp->ext_options['async_init'] = 'off';
			// Functionality for Advanced Targeting - 2017-04-29 - begin
			if (!defined('UAP_CORE')) {
				if (isset($_POST['ulp_ext_advanced_targeting'])) $ulp->ext_options['advanced_targeting'] = 'on';
				else $ulp->ext_options['advanced_targeting'] = 'off';
			}
			// Functionality for Advanced Targeting - 2017-04-29 - end
			if (isset($_POST['ulp_ext_minified_sources'])) $ulp->ext_options['minified_sources'] = 'on';
			else $ulp->ext_options['minified_sources'] = 'off';
			if (isset($_POST['ulp_ext_enable_remote'])) $ulp->ext_options['enable_remote'] = 'on';
			else $ulp->ext_options['enable_remote'] = 'off';
			if (isset($_POST['ulp_ext_admin_only_meta'])) $ulp->ext_options['admin_only_meta'] = 'on';
			else $ulp->ext_options['admin_only_meta'] = 'off';
			$errors = array();

			if (!empty($errors)) {
				$return_object = array();
				$return_object['status'] = 'ERROR';
				$return_object['message'] = __('Attention! Please correct the errors below and try again.', 'ulp').'<ul><li>'.implode('</li><li>', $errors).'</li></ul>';
				echo json_encode($return_object);
				exit;
			}
			$ulp->update_ext_options();
			
			// Functionality for Advanced Targeting - 2017-04-29 - begin
			if (!defined('UAP_CORE')) {
				if ($ulp->ext_options['advanced_targeting'] == 'on') {
					include_once(dirname(__FILE__).'/core-targeting.php');
					$targeting = new ulp_class_targeting();
					$targeting->convert_classic();
				}
			}
			// Functionality for Advanced Targeting - 2017-04-29 - end
			
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['message'] = __('Settings successfully <strong>saved</strong>.', 'ulp');
			echo json_encode($return_object);
			exit;
		}
	}

	function admin_campaign_stats() {
		global $wpdb, $ulp;
		if (current_user_can('manage_options')) {
			$callback = '';
			$html = '';
			if (isset($_REQUEST['callback'])) {
				header("Content-type: text/javascript");
				$callback = preg_replace('/[^a-zA-Z0-9_]/', '', $_REQUEST['callback']);
			}
			$id = null;
			if (array_key_exists('id', $_REQUEST)) {
				$id = intval($_REQUEST['id']);
				$campaign_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_campaigns WHERE id = '".esc_sql($id)."' AND deleted = '0'", ARRAY_A);
				if (empty($campaign_details)) $id = null;
			}
			if (empty($id)) {
				$return_data = array(
					'status' => 'ERROR',
					'message' => esc_html__('Requested campaign not found.', 'ulp')
				);
				if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
				else echo json_encode($return_data);
				exit;
			}
			$sql = "SELECT t1.*, t2.title FROM ".$wpdb->prefix."ulp_campaign_items t1 JOIN ".$wpdb->prefix."ulp_popups t2 ON t2.id = t1.popup_id WHERE t2.deleted = '0' AND t1.campaign_id = '".esc_sql($id)."' AND t1.deleted = '0'";
			$rows = $wpdb->get_results($sql, ARRAY_A);
			if (sizeof($rows) == 0) {
				$return_data = array(
					'status' => 'ERROR',
					'message' => esc_html__('No stats found.', 'ulp')
				);
				if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
				else echo json_encode($return_data);
				exit;
			}
			$max_impressions = 0;
			foreach ($rows as $row) {
				if ($row['impressions'] > $max_impressions) $max_impressions = $row['impressions'];
			}
			foreach ($rows as $row) {
				$impressions = $max_impressions > 0 ? intval(0+$row['impressions']*100/$max_impressions) : 0;
				$clicks = $max_impressions > 0 ? intval(0+$row['clicks']*100/$max_impressions) : 0;
				$ctr = $row['impressions'] > 0 ? intval(0+$row['clicks']*100/$row['impressions']) : 0;
				$html .= '
	<div class="ulp-old-chart-popup">
		<h4>'.esc_html($row['title']).'</h4>
		'.($ulp->ext_options['count_impressions'] == 'on' ? '<div class="ulp-old-chart ulp-old-chart-impressions" style="width: '.$impressions.'%;">'.($impressions > 10 ? $row['impressions'] : '&nbsp').'</div>'.($impressions > 10 ? '' : '<div class="ulp-old-chart-outer-label">'.$row['impressions'].'</div>').'<br />' : '').'
		<div class="ulp-old-chart ulp-old-chart-clicks" style="width: '.$clicks.'%;">'.($clicks > 10 ? $row['clicks'] : '&nbsp').'</div>'.($clicks > 10 ? '' : '<div class="ulp-old-chart-outer-label">'.$row['clicks'].'</div>').'<br />
		'.($ulp->ext_options['count_impressions'] == 'on' ? '<div class="ulp-old-chart ulp-old-chart-ctr" style="width: '.$ctr.'%;">'.($ctr > 10 ? ($row['impressions'] > 0 ? number_format($row['clicks']*100/$row['impressions'], 2, ".", "").'%' : '0%') : '&nbsp').'</div>'.($ctr > 10 ? '' : '<div class="ulp-old-chart-outer-label">'.($row['impressions'] > 0 ? number_format($row['clicks']*100/$row['impressions'], 2, ".", "").'%' : '0%').'</div>') : '').'
	</div>';
			}
			$html .= '
	<hr class="ulp-old-chart-hr">
	<div class="ulp-old-chart-popup">
		<h4>'.esc_html__('Legend', 'ulp').':</h4>
		'.($ulp->ext_options['count_impressions'] == 'on' ? '<div class="ulp-old-chart-legend ulp-old-chart-impressions">'.__('Impressions', 'ulp').'</div>' : '').'
		<div class="ulp-old-chart-legend ulp-old-chart-clicks">'.__('Submits', 'ulp').'</div>
		'.($ulp->ext_options['count_impressions'] == 'on' ? '<div class="ulp-old-chart-legend ulp-old-chart-ctr">'.__('CTR', 'ulp').'</div>' : '').'
	</div>';


			
			$return_data = array(
				'status' => 'OK',
				'html' => $html
			);
			if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
			else echo json_encode($return_data);
		}
		exit;
	}

	function admin_log_details() {
		global $wpdb, $ulp;
		if (current_user_can('manage_options')) {
			$callback = '';
			$html = '';
			if (isset($_REQUEST['callback'])) {
				header("Content-type: text/javascript");
				$callback = preg_replace('/[^a-zA-Z0-9_]/', '', $_REQUEST['callback']);
			}
			$id = null;
			if (array_key_exists('id', $_REQUEST)) {
				$id = intval($_REQUEST['id']);
				$subscriber_details = $wpdb->get_row("SELECT t1.*, t2.title AS popup_title FROM ".$wpdb->prefix."ulp_subscribers t1 LEFT JOIN ".$wpdb->prefix."ulp_popups t2 ON t2.id = t1.popup_id WHERE t1.id = '".$id."' AND t1.deleted = '0'", ARRAY_A);
				if (empty($subscriber_details)) $id = null;
			}
			if (empty($id)) {
				$return_data = array(
					'status' => 'ERROR',
					'message' => esc_html__('Requested record not found.', 'ulp')
				);
				if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
				else echo json_encode($return_data);
				exit;
			}
			$html .= '
	<table class="ulp-admin-popup-table">
		<tr>
			<th>'.__('Status', 'ulp').':</th>
			<td>'.(array_key_exists($subscriber_details['status'], $ulp->user_statuses) ? '<span class="'.$ulp->user_statuses[$subscriber_details['status']]['class'].'">'.$ulp->user_statuses[$subscriber_details['status']]['label'].'</span>' : 'N/A').'</td>
		</tr>
		<tr>
			<th>'.__('Email', 'ulp').':</th>
			<td>'.esc_html($subscriber_details['email']).'</td>
		</tr>
		<tr>
			<th>'.__('Name', 'ulp').':</th>
			<td>'.(empty($subscriber_details['name']) ? '-' : esc_html($subscriber_details['name'])).'</td>
		</tr>
		<tr>
			<th>'.__('Phone #', 'ulp').':</th>
			<td>'.(empty($subscriber_details['phone']) ? '-' : esc_html($subscriber_details['phone'])).'</td>
		</tr>
		<tr>
			<th>'.__('Message', 'ulp').':</th>
			<td>'.(empty($subscriber_details['message']) ? '-' : str_replace(array("\r", "\n"), array('', '<br />'), esc_html($subscriber_details['message']))).'</td>
		</tr>';
						if (array_key_exists('custom_fields', $subscriber_details) && !empty($subscriber_details['custom_fields'])) {
							$custom_fields = unserialize($subscriber_details['custom_fields']);
							if ($custom_fields && is_array($custom_fields)) {
								foreach ($custom_fields as $field) {
									$html .= '
		<tr>
			<th>'.esc_html($field['name']).':</th>
			<td>'.(empty($field['value']) ? '-' : str_replace(array("\r", "\n"), array('', '<br />'), esc_html($field['value']))).'</td>
		</tr>';
								}
							}
						}
						$html .= '
	</table>';
			
			$return_data = array(
				'status' => 'OK',
				'html' => $html
			);
			if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
			else echo json_encode($return_data);
		}
		exit;
	}

	function subscribe() {
		global $wpdb, $ulp;

		$callback = '';
		if (isset($_REQUEST['callback'])) {
			header("Content-type: text/javascript");
			$callback = preg_replace('/[^a-zA-Z0-9_]/', '', $_REQUEST['callback']);
		}

		if (isset($_REQUEST['encoded']) && $_REQUEST['encoded'] == true) {
			$request_data = json_decode(base64_decode(trim(stripslashes($_REQUEST['data']))), true);
		} else $request_data = $_REQUEST;

		if (isset($request_data['ulp-name'])) $name = trim(stripslashes($request_data['ulp-name']));
		else $name = '';
		if (isset($request_data['ulp-email'])) $email = trim(stripslashes($request_data['ulp-email']));
		else $email = '';
		if (isset($request_data['ulp-phone'])) $phone = trim(stripslashes($request_data['ulp-phone']));
		else $phone = '';
		if (isset($request_data['ulp-message'])) $message = trim(stripslashes($request_data['ulp-message']));
		else $message = '';
		if (isset($request_data['ulp-campaign'])) $campaign_str_id = trim(stripslashes($request_data['ulp-campaign']));
		else $campaign_str_id  = '';
		if (isset($request_data['ulp-popup'])) $str_id = trim(stripslashes($request_data['ulp-popup']));
		else {
			$return_data = array();
			$return_data['status'] = 'FATAL';
			if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
			else echo json_encode($return_data);
			exit;
		}
		$campaign_str_id = preg_replace('/[^a-zA-Z0-9-]/', '', $campaign_str_id);
		$str_id = preg_replace('/[^a-zA-Z0-9]/', '', $str_id);
		$popup_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' AND str_id = '".$str_id."'", ARRAY_A);
		if (empty($popup_details)) {
			$return_data = array();
			$return_data['status'] = 'FATAL';
			if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
			else echo json_encode($return_data);
			exit;
		}

		$popup_options = unserialize($popup_details['options']);
		if (is_array($popup_options)) $popup_options = array_merge($ulp->default_popup_options, $popup_options);
		else $popup_options = $ulp->default_popup_options;
		
		if ($name == $popup_options['name_placeholder']) $name = '';
		if ($email == $popup_options['email_placeholder']) $email = '';
		if ($phone == $popup_options['phone_placeholder']) $phone = '';
		if ($message == $popup_options['message_placeholder']) $message = '';
		
		if ($ulp->options['recaptcha_enable'] == 'on' && $popup_options['recaptcha_mandatory'] == 'on') {
			$verified = false;
			foreach($request_data as $key => $value) {
				if (substr($key, 0, strlen('ulp-recaptcha-')) == 'ulp-recaptcha-') {
					$verified = true;
					if (!$ulp->verify_recaptcha($value)) {
						$return_data = array();
						$return_data[$key] = 'ERROR';
						$return_data['status'] = 'ERROR';
						if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
						else echo json_encode($return_data);
						exit;
					}
				}
			}
			if (!$verified) {
				$return_data = array();
				$return_data['recaptcha'] = 'ERROR';
				$return_data['status'] = 'ERROR';
				if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
				else echo json_encode($return_data);
				exit;
			}
		}

		$return_data = array();
		if ($popup_options['email_mandatory'] == 'on' && empty($email)) $return_data['ulp-email'] = 'ERROR';
		else if (!empty($email) && !preg_match("/^[_a-z0-9-+]+(\.[_a-z0-9-+]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,19})$/i", $email)) $return_data['ulp-email'] = 'ERROR';
		else if (!empty($email)) {
			if ($ulp->options['email_validation'] == 'on') {
				$email_parts = explode('@',$email);
				if(checkdnsrr($email_parts[1], 'MX')) {
					//if(!fsockopen($email_parts[1], 25, $errno, $errstr, 30)) $return_data['ulp-email'] = 'ERROR';
				} else $return_data['ulp-email'] = 'ERROR';
			}
		}
	
		if ($popup_options['name_mandatory'] == 'on' && empty($name)) $return_data['ulp-name'] = 'ERROR';
		if ($popup_options['phone_mandatory'] == 'on' && empty($phone)) $return_data['ulp-phone'] = 'ERROR';
		$phone_digits = preg_replace('/[^0-9]/', '', $phone);
		if (!empty($phone_digits)) {
			if (!empty($popup_options['phone_length'])) {
				$lengths_raw = explode(',', $popup_options['phone_length']);
				$lengths = array();
				foreach ($lengths_raw as $length) {
					$length = intval(trim($length));
					if ($length > 0) $lengths[] = $length;
				}
				if (sizeof($lengths) > 0) {
					if (!in_array(strlen($phone_digits), $lengths)) $return_data['ulp-phone'] = 'ERROR';
				}
			}
		}
		if ($popup_options['message_mandatory'] == 'on' && empty($message)) $return_data['ulp-message'] = 'ERROR';
		
		$return_data = apply_filters('ulp_front_fields_check', $return_data, $popup_options);
		
		if (!empty($return_data)) {
			$return_data['status'] = 'ERROR';
			if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
			else echo json_encode($return_data);
			exit;
		}
		
		$custom_fields = apply_filters('ulp_log_custom_fields', array(), $popup_options);
		$custom_fields = array_merge($custom_fields, array('ip' => array('name' => 'IP Address', 'value' => $_SERVER['REMOTE_ADDR']), 'agent' => array('name' => 'User Agent', 'value' => (array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : '')), 'url' => array('name' => 'URL', 'value' => (array_key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER'] : ''))));

		if (!empty($email)) {
			$status = ULP_SUBSCRIBER_UNCONFIRMED;
			$subscriber_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_subscribers WHERE deleted = '0' AND popup_id = '".$popup_details['id']."' AND email = '".esc_sql($email)."' AND status = '".ULP_SUBSCRIBER_CONFIRMED."'", ARRAY_A);
			if (!empty($subscriber_details)) $status = ULP_SUBSCRIBER_CONFIRMED;
			$confirmation_id = $ulp->random_string(24);
		} else {
			$status = 0;
			$confirmation_id = '';
		}
		
		if ($ulp->ext_options['log_data'] == 'on') {
			//if (empty($subscriber_details)) {
				$sql = "INSERT INTO ".$wpdb->prefix."ulp_subscribers (
					popup_id, name, email, phone, message, custom_fields, status, confirmation_id, created, deleted) VALUES (
					'".$popup_details['id']."',
					'".esc_sql($name)."',
					'".esc_sql($email)."',
					'".esc_sql($phone)."',
					'".esc_sql($message)."',
					'".esc_sql(serialize($custom_fields))."',
					'".esc_sql($status)."',
					'".esc_sql($confirmation_id)."',
					'".time()."', '0')";
			//} else {
			//	$sql = "UPDATE ".$wpdb->prefix."ulp_subscribers SET name = '".esc_sql($name)."', created = '".time()."' WHERE id = '".$subscriber_details['id']."'";
			//}
			$wpdb->query($sql);
			$subscriber_id = $wpdb->insert_id;
		} else $subscriber_id = 0;
		
		$wpdb->query("UPDATE ".$wpdb->prefix."ulp_popups SET clicks = clicks + 1 WHERE deleted = '0' AND blocked = '0' AND id = '".$popup_details['id']."'");
		if (!empty($campaign_str_id)) {
			$wpdb->query("UPDATE ".$wpdb->prefix."ulp_campaign_items t1 JOIN ".$wpdb->prefix."ulp_campaigns t2 ON t2.id = t1.campaign_id JOIN ".$wpdb->prefix."ulp_popups t3 ON t3.id = t1.popup_id SET t1.clicks = t1.clicks + 1 WHERE t1.deleted = '0' AND t2.deleted = '0' AND t2.blocked = '0' AND t2.str_id = '".esc_sql($campaign_str_id)."' AND t3.deleted = '0' AND t3.blocked = '0' AND t3.id = '".$popup_details['id']."'");
		}
		if (empty($name)) $name = substr($email, 0, strpos($email, '@'));
		
		$subscriber = array(
			'{id}' => $subscriber_id,
			'{name}' => $name, 
			'{email}' => $email, 
			'{e-mail}' => $email, 
			'{phone}' => $phone, 
			'{message}' => $message,
			'{subscription-name}' => $name, 
			'{subscription-email}' => $email, 
			'{subscription-phone}' => $phone, 
			'{subscription-message}' => $message,
			'{ip}' => $_SERVER['REMOTE_ADDR'],
			'{url}' => $_SERVER['HTTP_REFERER'],
			'{user-agent}' => $_SERVER['HTTP_USER_AGENT'],
			'{popup}' => $popup_options['title'],
			'{popup-id}' => $popup_details['str_id'],
			'{confirmation-link}' => (defined('UAP_CORE') ? admin_url('do.php') : get_bloginfo('url')).'?ulp-confirm='.$confirmation_id
		);
		$subscriber = apply_filters('ulp_subscriber_details', $subscriber, $popup_options);
		
		if ($status == ULP_SUBSCRIBER_UNCONFIRMED && $popup_options['doubleoptin_enable'] == 'on' && !empty($subscriber['{subscription-email}']) && $ulp->ext_options['log_data'] == 'on') {
			$body = strtr($popup_options['doubleoptin_message'], $subscriber);
			if ($ulp->options['from_type'] == 'html') {
				if (strpos(strtolower($body), '<html') === false) $body = str_replace(array("\n", "\r"), array("<br />", ""), $body);
				$mail_headers = "Content-Type: text/html; charset=UTF-8\r\n";
			} else {
				$mail_headers = "Content-Type: text/plain; charset=UTF-8\r\n";
			}
			$mail_headers .= "From: ".(empty($ulp->options['from_name']) ? esc_html($ulp->options['from_email']) : esc_html($ulp->options['from_name']))." <".esc_html($ulp->options['from_email']).">\r\n";
			$mail_headers .= "X-Mailer: PHP/".phpversion()."\r\n";
			wp_mail($subscriber['{subscription-email}'], $popup_options['doubleoptin_subject'], $body, $mail_headers);
		} else do_action('ulp_subscribe', $popup_options, $subscriber);
		
		$urlencoded = $subscriber;
		foreach ($urlencoded as $key => $value) {
			$urlencoded[$key] = urlencode($value);
		}
		
		$return_url = apply_filters('ulp_thankyou_url', $popup_options['return_url'], $popup_options, $subscriber);
		
		$return_data = array();
		$return_data['status'] = 'OK';
		$return_data['return_url'] = strtr($return_url, $urlencoded);
		$return_data['close_delay'] = 1000*intval($popup_options['close_delay']);
		$return_data['cookie_lifetime'] = intval($popup_options['cookie_lifetime']);
		
		$return_data['thanksgiving_popup'] = $popup_options['thanksgiving_popup'];
		if (!empty($return_data['thanksgiving_popup'])) {
			$thanksgiving_popup = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' AND blocked = '0' AND str_id = '".esc_sql($return_data['thanksgiving_popup'])."'", ARRAY_A);
			if (empty($thanksgiving_popup)) $return_data['thanksgiving_popup'] = '';
		}
		
		$return_data = apply_filters('ulp_subscribed_data', $return_data, $popup_options, $subscriber);
		
		if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
		else echo json_encode($return_data);
		exit;
	}

	function share() {
		global $wpdb, $ulp;
		
		$callback = '';
		if (isset($_REQUEST['callback'])) {
			header("Content-type: text/javascript");
			$callback = preg_replace('/[^a-zA-Z0-9_]/', '', $_REQUEST['callback']);
		}
		
		if (isset($_REQUEST['ulp-campaign'])) $campaign_str_id = trim(stripslashes($_REQUEST['ulp-campaign']));
		else $campaign_str_id  = '';
		if (isset($_REQUEST['ulp-popup'])) $str_id = trim(stripslashes($_REQUEST['ulp-popup']));
		else {
			$return_data = array();
			$return_data['status'] = 'FATAL';
			if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
			else echo json_encode($return_data);
			exit;
		}
		$campaign_str_id = preg_replace('/[^a-zA-Z0-9-]/', '', $campaign_str_id);
		$str_id = preg_replace('/[^a-zA-Z0-9]/', '', $str_id);
		$popup_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' AND str_id = '".$str_id."'", ARRAY_A);
		if (empty($popup_details)) {
			$return_data = array();
			$return_data['status'] = 'FATAL';
			if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
			else echo json_encode($return_data);
			exit;
		}
		$return_data = array();
		$popup_options = unserialize($popup_details['options']);
		if (is_array($popup_options)) $popup_options = array_merge($ulp->default_popup_options, $popup_options);
		else $popup_options = $ulp->default_popup_options;
	
		if (!empty($return_data)) {
			$return_data['status'] = 'ERROR';
			if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
			else echo json_encode($return_data);
			exit;
		}
		
		$wpdb->query("UPDATE ".$wpdb->prefix."ulp_popups SET clicks = clicks + 1 WHERE deleted = '0' AND blocked = '0' AND id = '".$popup_details['id']."'");
		if (!empty($campaign_str_id)) {
			$wpdb->query("UPDATE ".$wpdb->prefix."ulp_campaign_items t1 JOIN ".$wpdb->prefix."ulp_campaigns t2 ON t2.id = t1.campaign_id JOIN ".$wpdb->prefix."ulp_popups t3 ON t3.id = t1.popup_id SET t1.clicks = t1.clicks + 1 WHERE t1.deleted = '0' AND t2.deleted = '0' AND t2.blocked = '0' AND t2.str_id = '".esc_sql($campaign_str_id)."' AND t3.deleted = '0' AND t3.blocked = '0' AND t3.id = '".$popup_details['id']."'");
		}
		
		$return_data = array();
		$return_data['status'] = 'OK';
		$return_data['return_url'] = $popup_options['return_url'];
		$return_data['close_delay'] = 1000*intval($popup_options['close_delay']);
		$return_data['cookie_lifetime'] = intval($popup_options['cookie_lifetime']);
		
		$return_data['thanksgiving_popup'] = $popup_options['thanksgiving_popup'];
		if (!empty($return_data['thanksgiving_popup'])) {
			$thanksgiving_popup = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' AND blocked = '0' AND str_id = '".esc_sql($return_data['thanksgiving_popup'])."'", ARRAY_A);
			if (empty($thanksgiving_popup)) $return_data['thanksgiving_popup'] = '';
		}
		
		if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
		else echo json_encode($return_data);
		exit;
	}

	function add_impression() {
		global $wpdb, $ulp;
		
		$callback = '';
		if (isset($_REQUEST['callback'])) {
			header("Content-type: text/javascript");
			$callback = preg_replace('/[^a-zA-Z0-9_]/', '', $_REQUEST['callback']);
		}
		
		if (isset($_REQUEST['ulp-popup'])) $popup_str_id = trim(stripslashes($_REQUEST['ulp-popup']));
		else $popup_str_id = '';
		if (isset($_REQUEST['ulp-campaign'])) $campaign_str_id = trim(stripslashes($_REQUEST['ulp-campaign']));
		else $campaign_str_id = '';
		$popup_str_id = preg_replace('/[^a-zA-Z0-9]/', '', $popup_str_id);
		$campaign_str_id = preg_replace('/[^a-zA-Z0-9-]/', '', $campaign_str_id);

		if (!empty($popup_str_id)) {
			$wpdb->query("UPDATE ".$wpdb->prefix."ulp_popups SET impressions = impressions + 1 WHERE deleted = '0' AND blocked = '0' AND str_id = '".esc_sql($popup_str_id)."'");
			if (!empty($campaign_str_id)) {
				$wpdb->query("UPDATE ".$wpdb->prefix."ulp_campaign_items t1 JOIN ".$wpdb->prefix."ulp_campaigns t2 ON t2.id = t1.campaign_id JOIN ".$wpdb->prefix."ulp_popups t3 ON t3.id = t1.popup_id SET t1.impressions = t1.impressions + 1 WHERE t1.deleted = '0' AND t2.deleted = '0' AND t2.blocked = '0' AND t2.str_id = '".esc_sql($campaign_str_id)."' AND t3.deleted = '0' AND t3.blocked = '0' AND t3.str_id = '".esc_sql($popup_str_id)."'");
			}
		}
		$return_data = array();
		$return_data['status'] = 'OK';
		if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
		else echo json_encode($return_data);
		exit;
	}
	
	function load_popup() {
		global $ulp;
		
		$callback = '';
		if (isset($_REQUEST['callback'])) {
			header("Content-type: text/javascript");
			$callback = preg_replace('/[^a-zA-Z0-9_]/', '', $_REQUEST['callback']);
		}
		
		$str_id = '';
		if (isset($_REQUEST['ulp-popup'])) {
			$str_id = preg_replace('/[^a-zA-Z0-9-]/', '', $_REQUEST['ulp-popup']);
		}
		include_once(dirname(__FILE__).'/core-front.php');
		$data = ulp_front_class::get_popups(array($str_id), false, true);
		$return_data = array();
		$return_data['status'] = 'OK';
		$return_data['html'] = $data['header'].$data['footer'];
		
		if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
		else echo json_encode($return_data);
		exit;
	}

	function load_inline_popups() {
		global $ulp;
		$callback = '';
		if (isset($_REQUEST['callback'])) {
			header("Content-type: text/javascript");
			$callback = preg_replace('/[^a-zA-Z0-9_]/', '', $_REQUEST['callback']);
		}
		$return_data = array();
		$return_data['status'] = 'OK';
		if (isset($_REQUEST['inline_ids'])) {
			$inline_ids = explode(',', preg_replace('/[^a-zA-Z0-9,:]/', '', $_REQUEST['inline_ids']));
			if (sizeof($inline_ids) > 0) {
				include_once(dirname(__FILE__).'/core-front.php');
				foreach($inline_ids as $encoded_id) {
					$id = explode(':', $encoded_id);
					if (sizeof($id) == 2 && !empty($id[1])) {
						$return_data['popups'][$id[0]] = ulp_front_class::shortcode_handler(array('id' => $id[1]));
					} else $return_data['popups'][$id[0]] = '';
				}
			}
		}
		if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
		else echo json_encode($return_data);
		exit;
	}

	function init_event_popups() {
		global $ulp;
		$callback = '';
		if (isset($_REQUEST['callback'])) {
			header("Content-type: text/javascript");
			$callback = preg_replace('/[^a-zA-Z0-9_]/', '', $_REQUEST['callback']);
		}

		if (array_key_exists('post-id', $_REQUEST)) $raw_post_id = $_REQUEST['post-id'];
		else $raw_post_id = 0;
		include_once(dirname(__FILE__).'/core-front.php');
		$event_data = ulp_front_class::get_events_data($raw_post_id);

		$return_data = array();
		$return_data['status'] = 'OK';
		$return_data['event_data'] = $event_data['javascript_vars'];

		$filtered = array();
		if ($ulp->options['preload_event_popups'] == 'on' && $ulp->options['no_preload'] == 'on') {
			$filtered = $event_data['event_popups'];
			if (empty($filtered)) $filtered[] = 'none';
		}		
		if ($ulp->options['no_preload'] == 'on' && $ulp->options['preload_event_popups'] == 'on') {
			include_once(dirname(__FILE__).'/core-front.php');
			$data = ulp_front_class::get_popups($filtered, true, false);
			$return_data['footer'] = $data['header'].$data['footer'];
		}
		
		if (!empty($callback)) echo $callback.'('.json_encode($return_data).')';
		else echo json_encode($return_data);
		exit;
	}

	
	function widgets_init() {
		include_once(dirname(dirname(__FILE__)).'/widget.php');
		register_widget('ulp_widget');
	}
	
	function handle_tinymce_button() {
		global $wpdb;
		$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY blocked, title ASC", ARRAY_A);
		echo '
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Layered Popups Shortcode</title>
	<meta http-equiv="Content-Type" content="'.get_bloginfo('html_type').' charset='.get_option('blog_charset').'" />
	<base target="_self" />
	<script type="text/javascript" src="'.get_bloginfo('wpurl').'/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
	<style>label {clear: both;} #insert, #cancel {line-height: 28px; height: 30px; padding: 0 12px 2px;} #ulp_popup, #ulp_type {padding: 6px 0; font-size: 14px;}</style>
</head>
<body>';
	if (sizeof($popups) > 0) {
		echo '
	<script>
		function ulp_insert_shortcode() {
			var popup = document.getElementById("ulp_popup").value;
			var type = document.getElementById("ulp_type").value;
			var content = "";
			if (popup) {
				var selected = tinyMCEPopup.getWindowArg("selected");
				if (selected) content = selected;
				if (type == "ulp") {
					content = "[ulp id=\'"+popup+"\']"+content;
				} else if (type == "ulplinklocker") {
					content = "[ulplinklocker id=\'"+popup+"\']"+content+"[/ulplinklocker]";
				}
				tinymce.execCommand("mceInsertContent", false, content);
			}    
			tinyMCEPopup.close();
			return false;
		}
	</script>
	<div style="margin: 10px;">
		<div style="margin-bottom: 10px;">
			<label for="ulp_popup">'.__('Shortcode', 'ulp').':</label>
			<select id="ulp_type" style="width: 100%;">
				<option value="ulp">Inline Popup</option>
				<option value="ulplinklocker">Link Locker</option>
			</select>
		</div>
		<div style="margin-bottom: 10px;">
			<label for="ulp_popup">'.__('Popup', 'ulp').':</label>
			<select id="ulp_popup" style="width: 100%;">';
			$status = -1;
			foreach($popups as $popup) {
				if ($popup['blocked'] != $status) {
					if ($popup['blocked'] == 0) echo '<option disabled="disabled">--------- '.__('Active Popups', 'ulp').' ---------</option>';
					else echo '<option disabled="disabled">--------- '.__('Blocked Popups', 'ulp').' ---------</option>';
					$status = $popup['blocked'];
				}
				echo '
				<option value="'.$popup['str_id'].'"'.($popup['blocked'] == 1 ? ' disabled="disabled"' : '').'>'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		echo '
			</select>
		</div>
		<div class="mceActionPanel">
			<div style="float: left">
				<input type="button" id="cancel" name="cancel" value="Cancel" onclick="tinyMCEPopup.close();" />
			</div>
			<div style="float: right">
				<input type="submit" id="insert" name="insert" onclick="return ulp_insert_shortcode();" value="Insert Shortcode" />
			</div>
		</div>
	</div>';
	} else {
		echo '<div style="margin-top: 50px; text-align: center;"><strong>'.__('Create at least one Layered Popup!', 'ulp').'</strong></div>';
	}
	echo '
</body>
</html>';
		exit;
	}
}
?>