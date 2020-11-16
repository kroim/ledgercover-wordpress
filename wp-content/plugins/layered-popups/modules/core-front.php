<?php
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_front_class {
	function __construct() {
		global $ulp;
		if (is_admin()) {
		} else {
			add_action("widgets_init", array(&$this, 'widgets_init'));
			$late_init = get_option('ulp_ext_late_init');
			add_action('init', array(&$this, 'init'), 15);
			if ($late_init == 'on') {
				add_action('wp_enqueue_scripts', array(&$this, 'front_init'), 99);
			} else {
				add_action('wp', array(&$this, 'front_init'), 15);
			}
			$inline_ajaxed = get_option('ulp_ext_inline_ajaxed');
			if ($inline_ajaxed == 'on') add_shortcode('ulp', array(&$this, "shortcode_ajaxed_handler"));
			else add_shortcode('ulp', array(&$this, "shortcode_handler"));
			add_shortcode('ulplinklocker', array(&$this, "shortcode_linklocker_handler"));
		}
	}

	function init() {
		global $wpdb, $ulp;
		if (isset($_GET['ulp-confirm'])) {
			$confirmation_id = preg_replace('/[^a-zA-Z0-9-]/', '', $_GET['ulp-confirm']);
			$message = __('Invalid confirmation URL.', 'ulp');
			if (!empty($confirmation_id)) {
				$subscriber_details = $wpdb->get_row("SELECT t1.*, t2.title AS popup_title FROM ".$wpdb->prefix."ulp_subscribers t1 LEFT JOIN ".$wpdb->prefix."ulp_popups t2 ON t2.id = t1.popup_id WHERE t1.confirmation_id = '".esc_sql($confirmation_id)."' AND t1.deleted = '0'", ARRAY_A);
				if ($subscriber_details) {
					$popup_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' AND id = '".$subscriber_details['popup_id']."'", ARRAY_A);
					if (empty($popup_details)) {
						$message = __('Relevant popup not found.', 'ulp');
					} else {
						$popup_options = unserialize($popup_details['options']);
						if (is_array($popup_options)) $popup_options = array_merge($ulp->default_popup_options, $popup_options);
						else $popup_options = $ulp->default_popup_options;
						if (empty($subscriber_details['name'])) $subscriber_details['name'] = substr($subscriber_details['email'], 0, strpos($subscriber_details['email'], '@'));
						$subscriber = array(
							'{id}' => $subscriber_details['id'],
							'{name}' => $subscriber_details['name'], 
							'{email}' => $subscriber_details['email'], 
							'{e-mail}' => $subscriber_details['email'], 
							'{phone}' => $subscriber_details['phone'], 
							'{message}' => $subscriber_details['message'],
							'{subscription-name}' => $subscriber_details['name'], 
							'{subscription-email}' => $subscriber_details['email'], 
							'{subscription-phone}' => $subscriber_details['phone'], 
							'{subscription-message}' => $subscriber_details['message'],
							'{popup}' => $popup_options['title'],
							'{popup-id}' => $popup_details['str_id'],
							'{confirmation-link}' => (defined('UAP_CORE') ? admin_url('do.php') : get_bloginfo('url')).'?ulp-confirm='.$confirmation_id
						);
						$subscriber = apply_filters('ulp_subscriber_details', $subscriber, $popup_options);
						if (array_key_exists('custom_fields', $subscriber_details) && !empty($subscriber_details['custom_fields'])) {
							$custom_fields = unserialize($subscriber_details['custom_fields']);
							if (array_key_exists('ip', $custom_fields) && is_array($custom_fields['ip'])) $subscriber['{ip}'] = $custom_fields['ip']['value'];
							if (array_key_exists('url', $custom_fields) && is_array($custom_fields['url'])) $subscriber['{url}'] = $custom_fields['url']['value'];
							if (array_key_exists('agent', $custom_fields) && is_array($custom_fields['agent'])) $subscriber['{user-agent}'] = $custom_fields['agent']['value'];
							$subscriber = apply_filters('ulp_subscriber_details_from_log', $subscriber, $popup_options, $custom_fields);
						}
						$custom_fields['confirmed-ip'] = array('name' => 'Confirmed from IP', 'value' => $_SERVER['REMOTE_ADDR']);
						$custom_fields['confirmed-time'] = array('name' => 'Confirmed at', 'value' => date("Y-m-d H:i:s"));
						$wpdb->query("UPDATE ".$wpdb->prefix."ulp_subscribers SET status = '".ULP_SUBSCRIBER_CONFIRMED."', custom_fields = '".esc_sql(serialize($custom_fields))."' WHERE deleted = '0' AND id = '".esc_sql($subscriber_details['id'])."'");
						$message = strtr($popup_options['doubleoptin_confirmation_message'], $subscriber);
						do_action('ulp_subscribe', $popup_options, $subscriber);
						if (!empty($popup_options['doubleoptin_redirect_url'])) {
							$urlencoded = $subscriber;
							foreach ($urlencoded as $key => $value) {
								$urlencoded[$key] = urlencode($value);
							}
							$return_url = apply_filters('ulp_thankyou_url', $popup_options['doubleoptin_redirect_url'], $popup_options, $subscriber);
							$return_url = strtr($return_url, $urlencoded);
							header('Location: '.$return_url);
							exit;
						}
					}
				}
			}
			echo '<!DOCTYPE html>
<html>
<head>
	<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>'.__('Confirmation', 'ulp').'</title>
	<link href="//fonts.googleapis.com/css?family=Open+Sans:400,300&subset=latin,cyrillic-ext,greek-ext,latin-ext,cyrillic,greek,vietnamese" rel="stylesheet" type="text/css">
	<style>
	body {font-family: "Open Sans", "Helvetica Neue", Helvetica, Arial, sans-serif; font-weight: 100; color: #333; background-color: #fff; font-size: 24px; line-height: 1.475;}
	.front-container {position: absolute;top: 0;right: 0;bottom: 0;left: 0;min-width: 240px;height: 100%;display: table;width: 100%;}
	.front-content {max-width: 1024px;margin: 0px auto;padding: 20px 0;position: relative;display: table-cell;text-align: center;vertical-align: middle;}
	</style>
</head>
<body>
	<div class="front-container">
		<div class="front-content">
			'.$message.'
		</div>
	</div>
</body>
</html>';
			exit;
		}
	}

	function front_init() {
		global $wpdb, $post, $current_user, $ulp, $porto_settings, $w2dc_instance;

		$post_id = 0;
		$posts_page_id = get_option('page_for_posts');
		if (is_home() && !empty($posts_page_id)) {
			$post_id = $posts_page_id;
		} else if (function_exists('is_product') && is_product()) {
			if (!empty($post)) $post_id = $post->ID;
			else $post_id = 0;
		} else if (function_exists('is_shop') && is_shop() && (function_exists('woocommerce_get_page_id') || function_exists('wc_get_page_id'))) {
			if (function_exists('wc_get_page_id')) $post_id = wc_get_page_id('shop');
			else $post_id = woocommerce_get_page_id('shop');
		} else if (is_singular()) {
			if (!empty($post)) $post_id = $post->ID;
			else $post_id = 0;
		} else if (defined('porto_version') && is_post_type_archive('portfolio')) {
			$post_id = $porto_settings['portfolio-archive-page'];
		} else if (defined('porto_version') && is_post_type_archive('event')) {
			$post_id = $porto_settings['event-archive-page'];
		} else if (defined('porto_version') && is_post_type_archive('member')) {
			$post_id = $porto_settings['member-archive-page'];
		} else if (defined('porto_version') && is_post_type_archive('faq')) {
			$post_id = $porto_settings['faq-archive-page'];
		}
		if (class_exists("w2dc_plugin") && is_object($w2dc_instance)) {
			if (property_exists($w2dc_instance, "frontend_controllers") && array_key_exists('webdirectory-listing', $w2dc_instance->frontend_controllers) && is_array($w2dc_instance->frontend_controllers['webdirectory-listing'])) {
				$w2dc_controller = $w2dc_instance->frontend_controllers['webdirectory-listing'][0];
				if (is_object($w2dc_controller) && $w2dc_controller->is_single && property_exists($w2dc_controller, "listing")) {
					$post_id = $w2dc_controller->listing->post->ID;
				}
			}
		}
		
		if ($ulp->ext_options['advanced_targeting'] == 'on') {
			if ($post_id == 0 && (is_tax() || is_tag() || is_category())) {
				$queried_object = get_queried_object();
				if (is_a($queried_object, 'WP_Term')) {
					if (property_exists($queried_object, 'term_id') && property_exists($queried_object, 'taxonomy')) {
						unset($post_id);
						$post_id = '{'.$queried_object->term_id.'}{'.$queried_object->taxonomy.'}';
					}
				}
			}
			if ($post_id == 0 && is_home()) $post_id = 'homepage';
			include_once(dirname(__FILE__).'/core-targeting.php');
			$targeting = new ulp_class_targeting();
			$targets = $targeting->front_init_inline($post_id);
		}
		$javascript_vars_html = '';
		if ($ulp->ext_options['async_init'] == 'on') {
			$ulp->front_header = '';
			if ($ulp->options['no_preload'] != 'on') {
				$data = $this->get_popups(array(), true, false);
				$ulp->front_header .= $data['header'];
				$ulp->front_footer .= $data['footer'];
			}
			$javascript_vars_html = '
			var ulp_onload_popup = "";
			var ulp_onload_mode = "none";
			var ulp_onload_period = "5";
			var ulp_onscroll_popup = "";
			var ulp_onscroll_mode = "none";
			var ulp_onscroll_period = "5";
			var ulp_onexit_popup = "";
			var ulp_onexit_mode = "none";
			var ulp_onexit_period = "5";
			var ulp_onidle_popup = "";
			var ulp_onidle_mode = "none";
			var ulp_onidle_period = "5";
			var ulp_onabd_popup = "";
			var ulp_onabd_mode = "none";
			var ulp_onabd_period = "5";
			var ulp_onload_delay = "5";
			var ulp_onload_close_delay = "0";
			var ulp_onscroll_offset = "600";
			var ulp_onidle_delay = "30";';
		} else {
			$event_data = $this->get_events_data($post_id);
			$filtered = array();
			if ($ulp->options['preload_event_popups'] == 'on' && $ulp->options['no_preload'] == 'on') {
				$filtered = $event_data['event_popups'];
				if (empty($filtered)) $filtered[] = 'none';
			}
			if ($ulp->options['no_preload'] != 'on' || $ulp->options['preload_event_popups'] == 'on') {
				$data = $this->get_popups($filtered, true, false);
				$ulp->front_header .= $data['header'];
				$ulp->front_footer .= $data['footer'];
			}
			foreach ($event_data['javascript_vars'] as $key => $value) {
				$javascript_vars_html .= '
			var ulp_'.$key.' = "'.esc_html($value).'";';
			}
		}
		
		$ulp->front_header .= '
		<script>
			var ulp_custom_handlers = {};
			var ulp_cookie_value = "'.$ulp->options['cookie_value'].'";'.$javascript_vars_html.'
			var ulp_recaptcha_enable = "'.$ulp->options['recaptcha_enable'].'";';
		$ulp->front_header .= $ulp->options['recaptcha_enable'] != 'on' ? '' : '
			var ulp_recaptcha_public_key = "'.esc_html($ulp->options['recaptcha_public_key']).'";';
		if ($ulp->ext_options['async_init'] == 'on') {
			$ulp->front_header .= '
			var ulp_content_id = "'.esc_html($post_id).'";';
			if (defined('ICL_LANGUAGE_CODE')) {
				if (ICL_LANGUAGE_CODE != 'all') $ulp->front_header .= '
			var ulp_icl_language = "'.esc_html(ICL_LANGUAGE_CODE).'";';
			}
		}
		$ulp->front_header .= '
		</script>';
		$ulp->front_footer .= '
		<script>
			var ulp_ajax_url = "'.admin_url('admin-ajax.php').'";
			var ulp_count_impressions = "'.$ulp->ext_options['count_impressions'].'";
			var ulp_async_init = "'.$ulp->ext_options['async_init'].'";
			var ulp_css3_enable = "'.$ulp->options['css3_enable'].'";
			var ulp_ga_tracking = "'.$ulp->options['ga_tracking'].'";
			var ulp_km_tracking = "'.$ulp->options['km_tracking'].'";
			var ulp_onexit_limits = "'.$ulp->options['onexit_limits'].'";
			var ulp_no_preload = "'.$ulp->options['no_preload'].'";
			var ulp_campaigns = {';
		$campaigns = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_campaigns WHERE deleted = '0' AND blocked = '0' ORDER BY created DESC", ARRAY_A);
		foreach ($campaigns as $campaign) {
			$popups = $wpdb->get_results("SELECT t1.*, t2.str_id FROM ".$wpdb->prefix."ulp_campaign_items t1 JOIN ".$wpdb->prefix."ulp_popups t2 ON t2.id = t1.popup_id WHERE t1.campaign_id = '".$campaign['id']."' AND t1.deleted = '0' AND t2.deleted = '0' AND t2.blocked = '0' ORDER BY t1.created DESC", ARRAY_A);
			$campaign_popups = array();
			foreach($popups as $popup) {
				$campaign_popups[] = $popup['str_id'];
			}
			$ulp->front_footer .= '"'.$campaign['str_id'].'":["'.implode('","', $campaign_popups).'"],';
		}
		$ulp->front_footer .= '"none":[""]};';
		$str_id = '';
		if (isset($_GET['ulp'])) $str_id = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['ulp']);
		$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' AND (blocked = '0'".(empty($str_id) ? '' : " OR str_id = '".esc_sql($str_id)."'").")", ARRAY_A);
		$ulp->front_footer .= '
			var ulp_overlays = {';
		foreach ($popups as $popup) {
			$popup_options = unserialize($popup['options']);
			if (is_array($popup_options)) $popup_options = array_merge($ulp->default_popup_options, $popup_options);
			else $popup_options = $ulp->default_popup_options;
			if ($ulp->options['spinkit_enable'] != 'on') $popup_options['ajax_spinner'] = 'classic';
			$ulp->front_footer .= '"'.$popup['str_id'].'":["'.($popup_options['disable_overlay'] == 'on' ? '' : (!empty($popup_options['overlay_color']) ? $popup_options['overlay_color'] : 'transparent')).'", "'.$popup_options['overlay_opacity'].'", "'.$popup_options['enable_close'].'", "'.$popup_options['position'].'", "'.$popup_options['overlay_animation'].'", "'.$popup_options['ajax_spinner'].'", "'.$popup_options['ajax_spinner_color'].'"],';
		}
		$ulp->front_footer .= '"none":["", "", "", "", ""]};';

		$ulp->front_footer .= '
			if (typeof ulp_init == "function") { 
				ulp_init(); jQuery(document).ready(function() {ulp_ready();});
			} else {
				jQuery(document).ready(function(){ulp_init(); ulp_ready();});
			}
		</script><script src="'.$ulp->plugins_url.'/js/ads.js?ver='.ULP_VERSION.'"></script>';
		
		if ($ulp->ext_options['late_init'] == 'on') {
			$this->front_enqueue_scripts();
		} else {
			add_action('wp_enqueue_scripts', array(&$this, 'front_enqueue_scripts'), 99);
		}
		add_action('wp_head', array(&$this, 'front_header'), 15);
		add_action('wp_footer', array(&$this, 'front_footer'), 999);
	}

	static function get_events_data($_raw_post_id) {
		global $ulp;
		$post_id = 0;
		$raw_post_id = trim(stripslashes($_raw_post_id), '{}');
		if ($raw_post_id == preg_replace('/[^0-9]/', '', $raw_post_id)) {
			$post_id = $raw_post_id;
		} else {
			$tm = explode('}{', $raw_post_id);
			if (sizeof($tm) == 2) {
				if ($tm[0] == preg_replace('/[^0-9]/', '', $tm[0]) && taxonomy_exists($tm[1])) {
					unset($post_id);
					$post_id = array('term' => $tm[0], 'taxonomy' => $tm[1]);
				}
			}
		}
		if ($ulp->ext_options['advanced_targeting'] != 'on' && is_array($post_id)) {
			unset($post_id);
			$post_id = 0;
		}

		$event_popups = array();
		$javascript_vars = array();
		if ($ulp->ext_options['advanced_targeting'] == 'on') {
			include_once(dirname(__FILE__).'/core-targeting.php');
			$targeting = new ulp_class_targeting();
			$targets = $targeting->front_init($post_id);
			foreach ($ulp->events as $key => $value) {
				if (array_key_exists($key.'_popup', $ulp->options)) {
					$javascript_vars[$key.'_popup'] = "";
					$javascript_vars[$key.'_mode'] = 'none';
					$javascript_vars[$key.'_period'] = 5;
				}
			}
			$javascript_vars['onload_delay'] = 0;
			$javascript_vars['onload_close_delay'] = 0;
			$javascript_vars['onscroll_offset'] = 600;
			$javascript_vars['onidle_delay'] = 30;
			
			foreach ($targets as $event => $target) {
				$popup = $target['popup'];
				if ($target['popup_mobile'] != 'same' && (!empty($target['popup']) || !empty($target['popup_mobile']))) $popup .= '*'.$target['popup_mobile'];
				if (!empty($target['popup'])) $event_popups[] = $target['popup'];
				if (!empty($target['popup_mobile']) && $target['popup_mobile'] != 'same') $event_popups[] = $target['popup_mobile'];
				$javascript_vars[$event.'_popup'] = $popup;
				$javascript_vars[$event.'_mode'] = $target['options']['mode'];
				$javascript_vars[$event.'_period'] = intval($target['options']['mode_period']);
				if (in_array($event, array('onload', 'onidle'))) {
					$javascript_vars[$event.'_delay'] = intval($target['options']['delay']);
				}
				if ($event == 'onload') {
					$javascript_vars['onload_close_delay'] = intval($target['options']['close_delay']);
				}
				if ($event == 'onscroll') {
					$javascript_vars['onscroll_offset'] = intval($target['options']['offset']).(strpos($target['options']['offset'], '%') !== false ? '%' : '');
				}
			}
		} else {
			foreach ($ulp->events as $key => $value) {
				if (array_key_exists($key.'_popup', $ulp->options)) {
					$ulp->options[$key.'_popup'] = $ulp->wpml_parse_popup_id($ulp->options[$key.'_popup']);
					$ulp->options[$key.'_popup_mobile'] = $ulp->wpml_parse_popup_id($ulp->options[$key.'_popup_mobile'], 'same');
				}
			}
			if ($post_id > 0) {
				$meta = $ulp->get_meta($post_id);
				foreach ($ulp->events as $key => $value) {
					if (array_key_exists($key.'_popup', $ulp->options)) {
						$popup = ($meta[$key.'_popup'] == 'default' ? $ulp->options[$key.'_popup'] : $meta[$key.'_popup']);
						$popup_mobile = ($meta[$key.'_popup_mobile'] == 'default' ? $ulp->options[$key.'_popup_mobile'] : $meta[$key.'_popup_mobile']);
						if (!empty($popup)) $event_popups[] = $popup;
						if (!empty($popup_mobile) && $popup_mobile != 'same') $event_popups[] = $popup_mobile;
						if ($popup_mobile != 'same' && (!empty($popup) || !empty($popup_mobile))) $popup .= '*'.$popup_mobile;
						$javascript_vars[$key.'_popup'] = $popup;
						$javascript_vars[$key.'_mode'] = $meta[$key.'_mode'] == 'default' ? $ulp->options[$key.'_mode'] : $meta[$key.'_mode'];
						$javascript_vars[$key.'_period'] = $meta[$key.'_mode'] == 'default' ? intval($ulp->options[$key.'_period']) : intval($meta[$key.'_period']);
					}
				}
				$javascript_vars['onload_delay'] = $meta['onload_popup'] == 'default' ? intval($ulp->options['onload_delay']) : intval($meta['onload_delay']);
				$javascript_vars['onload_close_delay'] = $meta['onload_popup'] == 'default' ? intval($ulp->options['onload_close_delay']) : intval($meta['onload_close_delay']);
				$javascript_vars['onscroll_offset'] = $meta['onscroll_popup'] == 'default' ? intval($ulp->options['onscroll_offset']).(strpos($ulp->options['onscroll_offset'], '%') !== false ? '%' : '') : intval($meta['onscroll_offset']).(strpos($meta['onscroll_offset'], '%') !== false ? '%' : '');
				$javascript_vars['onidle_delay'] = $meta['onidle_popup'] == 'default' ? intval($ulp->options['onidle_delay']) : intval($meta['onidle_delay']);
			} else {
				foreach ($ulp->events as $key => $value) {
					if (array_key_exists($key.'_popup', $ulp->options)) {
						$popup = $ulp->options[$key.'_popup'];
						if ($ulp->options[$key.'_popup_mobile'] != 'same' && (!empty($ulp->options[$key.'_popup']) || !empty($ulp->options[$key.'_popup_mobile']))) $popup .= '*'.$ulp->options[$key.'_popup_mobile'];
						if (!empty($ulp->options[$key.'_popup'])) $event_popups[] = $ulp->options[$key.'_popup'];
						if (!empty($ulp->options[$key.'_popup_mobile']) && $ulp->options[$key.'_popup_mobile'] != 'same') $event_popups[] = $ulp->options[$key.'_popup_mobile'];
						$javascript_vars[$key.'_popup'] = $popup;
						$javascript_vars[$key.'_mode'] = $ulp->options[$key.'_mode'];
						$javascript_vars[$key.'_period'] = intval($ulp->options[$key.'_period']);
					}
				}
				$javascript_vars['onload_delay'] = intval($ulp->options['onload_delay']);
				$javascript_vars['onload_close_delay'] = intval($ulp->options['onload_close_delay']);
				$javascript_vars['onscroll_offset'] = intval($ulp->options['onscroll_offset']).(strpos($ulp->options['onscroll_offset'], '%') !== false ? '%' : '');
				$javascript_vars['onidle_delay'] = intval($ulp->options['onidle_delay']);
			}
		}
		return array('javascript_vars' => $javascript_vars, 'event_popups' => $event_popups);
	}	
	
	static function get_popups($_popups = array(), $_add_overlay = true, $_all = false) {
		global $wpdb, $post, $ulp;

		$filtered = array();
		if (!empty($_popups)) {
			foreach($_popups as $popup) {
				$filtered[] = preg_replace('/[^a-zA-Z0-9-]/', '', $popup);
			}
			$rows = $wpdb->get_results("SELECT t1.*, t2.str_id FROM ".$wpdb->prefix."ulp_campaign_items t1 JOIN ".$wpdb->prefix."ulp_popups t2 ON t2.id = t1.popup_id JOIN ".$wpdb->prefix."ulp_campaigns t3 ON t3.id = t1.campaign_id WHERE t2.deleted = '0'".(!$_all ? " AND t2.blocked = '0' AND t3.blocked = '0'" : "")." AND t3.deleted = '0' AND t3.str_id IN ('".implode("', '", $filtered)."') AND t1.deleted = '0'", ARRAY_A);
			if (sizeof($rows) > 0) {
				foreach ($rows as $row) {
					$filtered[] = $row['str_id'];
				}
			}
		}
		
		$front_header = '';
		$front_footer = '';
		$style = '';
		$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0'".(!$_all ? " AND blocked = '0'" : "").(!empty($filtered) ? " AND str_id IN ('".implode("', '", $filtered)."')" : ""), ARRAY_A);
		foreach ($popups as $popup) {
			$popup_options = unserialize($popup['options']);
			if (is_array($popup_options)) $popup_options = array_merge($ulp->default_popup_options, $popup_options);
			else $popup_options = $ulp->default_popup_options;
			
			if (substr($popup_options['button_icon'], 0, 3) == 'fa-') $popup_options['button_icon'] = 'fa fa-noicon';
			
			if (!empty($popup_options['button_color'])) {
				$from = $ulp->get_rgb($popup_options['button_color']);
				$total = $from['r']+$from['g']+$from['b'];
				if ($total == 0) $total = 1;
				$to = array();
				$to['r'] = max(0, $from['r']-intval(48*$from['r']/$total));
				$to['g'] = max(0, $from['g']-intval(48*$from['g']/$total));
				$to['b'] = max(0, $from['b']-intval(48*$from['b']/$total));
				$to_color = '#'.($to['r'] < 16 ? '0' : '').dechex($to['r']).($to['g'] < 16 ? '0' : '').dechex($to['g']).($to['b'] < 16 ? '0' : '').dechex($to['b']);
				$from_color = $popup_options['button_color'];
			} else {
				$to_color = 'transparent';
				$from_color = 'transparent';
			}
			if (!empty($popup_options['input_background_color'])) $bg_color = $ulp->get_rgb($popup_options['input_background_color']);
			if ($popup_options['button_gradient'] == 'on') {
				$style .= '#ulp-'.$popup['str_id'].' .ulp-submit,#ulp-'.$popup['str_id'].' .ulp-submit:visited{border-radius: '.intval($popup_options['button_border_radius']).'px !important; background: '.$from_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$to_color.','.$from_color.');'.(!empty($popup_options['button_css']) ? $popup_options['button_css'] : '').'}';
				$style .= '#ulp-'.$popup['str_id'].' .ulp-submit:hover,#ulp-'.$popup['str_id'].' .ulp-submit:active{border-radius: '.intval($popup_options['button_border_radius']).'px !important; background: '.$to_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$from_color.','.$to_color.');'.(!empty($popup_options['button_css_hover']) ? $popup_options['button_css_hover'] : '').'}';
			} else {
				$style .= '#ulp-'.$popup['str_id'].' .ulp-submit,#ulp-'.$popup['str_id'].' .ulp-submit:visited{border-radius: '.intval($popup_options['button_border_radius']).'px !important; background: '.$from_color.';border:1px solid '.$from_color.';'.(!empty($popup_options['button_css']) ? $popup_options['button_css'] : '').'}';
				$style .= '#ulp-'.$popup['str_id'].' .ulp-submit:hover,#ulp-'.$popup['str_id'].' .ulp-submit:active{border-radius: '.intval($popup_options['button_border_radius']).'px !important; background: '.$to_color.';border:1px solid '.$to_color.';'.(!empty($popup_options['button_css_hover']) ? $popup_options['button_css_hover'] : '').'}';
			}
			$style .= '#ulp-'.$popup['str_id'].', #ulp-'.$popup['str_id'].' .ulp-content{width:'.($popup_options['width'] % 2 == 0 ? $popup_options['width'] : $popup_options['width']+1).'px;height:'.($popup_options['height'] % 2 == 0 ? $popup_options['height'] : $popup_options['height']+1).'px;}';
			$style .= '#ulp-'.$popup['str_id'].' .ulp-input,#ulp-'.$popup['str_id'].' .ulp-input:hover,#ulp-'.$popup['str_id'].' .ulp-input:active,#ulp-'.$popup['str_id'].' .ulp-input:focus,#ulp-'.$popup['str_id'].' .ulp-checkbox{border-width: '.intval($popup_options['input_border_width']).'px !important; border-radius: '.intval($popup_options['input_border_radius']).'px !important; border-color:'.(empty($popup_options['input_border_color']) ? 'transparent' : $popup_options['input_border_color']).';background-color:'.(empty($popup_options['input_background_color']) ? 'transparent' : $popup_options['input_background_color']).' !important;background-color:'.(empty($popup_options['input_background_color']) ? 'transparent' : 'rgba('.$bg_color['r'].','.$bg_color['g'].','.$bg_color['b'].','.floatval($popup_options['input_background_opacity'])).') !important;'.(!empty($popup_options['input_css']) ? $popup_options['input_css'] : '').'}';
			if ($ulp->options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on') {
				$style .= '#ulp-'.$popup['str_id'].' input.ulp-input, #ulp-'.$popup['str_id'].' select.ulp-input {padding-left: 2.5em !important;}';
			}
			if ($_add_overlay && $popup_options['disable_overlay'] != 'on') {
				$overlay_rgb = $ulp->get_rgb($popup_options['overlay_color']);
				$style .= '#ulp-'.$popup['str_id'].'-overlay{background:'.(is_array($overlay_rgb) ? 'rgba('.$overlay_rgb['r'].','.$overlay_rgb['g'].','.$overlay_rgb['b'].','.$popup_options['overlay_opacity'].')' : 'transparent').';}';
			}
			
			$style = apply_filters('ulp_front_popup_style', $style, $popup);
			
			if ($_add_overlay && $popup_options['disable_overlay'] != 'on') {
				$front_footer .= '
				<div class="ulp-overlay" id="ulp-'.$popup['str_id'].'-overlay"></div>';
			}
			$front_footer .= '
			<div class="ulp-window-container" onclick="jQuery(\'#ulp-'.$popup['str_id'].'-overlay\').click();">
				<div class="ulp-window ulp-window-'.$popup_options['position'].'" id="ulp-'.$popup['str_id'].'" data-title="'.esc_html($popup['title']).'" data-width="'.($popup_options['width'] % 2 == 0 ? $popup_options['width'] : $popup_options['width']+1).'" data-height="'.($popup_options['height'] % 2 == 0 ? $popup_options['height'] : $popup_options['height']+1).'" data-position="'.$popup_options['position'].'" data-close="'.$popup_options['enable_close'].'" data-enter="'.$popup_options['enable_enter'].'" onclick="event.stopPropagation();">
					<div class="ulp-content">';
			$layers = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_layers WHERE popup_id = '".$popup['id']."' AND deleted = '0' ORDER BY zindex ASC, id ASC", ARRAY_A);
			foreach ($layers as $layer) {
				$layer_options = unserialize($layer['details']);
				if (is_array($layer_options)) $layer_options = array_merge($ulp->default_layer_options, $layer_options);
				else $layer_options = $ulp->default_layer_options;
				$layer_options = $ulp->filter_lp($layer_options);
				
				$mask_class = '';
				$mask = '';
				if ($ulp->options['mask_enable'] == 'on') {
					$mask_class = $popup_options['phone_mask'] != 'none' ? ' ulp-input-mask' : '';
					if ($popup_options['phone_mask'] != 'none') $mask = ' data-mask="'.esc_html($popup_options['phone_mask'] != 'custom' ? $popup_options['phone_mask'] : $popup_options['phone_custom_mask']).'"';
				}
				if ($ulp->options['fa_enable'] == 'on' && !empty($popup_options['button_icon']) && $popup_options['button_icon'] != 'fa fa-noicon') $button_icon = $popup_options['button_icon'];
				else $button_icon = '';
				$content = str_replace(
					array('{subscription-name}', '{subscription-email}', '{subscription-phone}', '{subscription-message}', '{subscription-submit}'),
					array(
						'<input class="ulp-input ulp-input-field" type="text" autocomplete="name" name="ulp-name" placeholder="'.esc_html($popup_options['name_placeholder']).'" value="" onfocus="jQuery(this).removeClass(\'ulp-input-error\');">'.($ulp->options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on' ? '<div class="ulp-fa-input-table"><div class="ulp-fa-input-cell"><i class="'.($ulp->options['fa_solid_enable'] == 'on' ? 'fas fa-user' : 'far fa-user').'"></i></div></div>' : ''),
						'<input class="ulp-input ulp-input-field" type="email" autocomplete="email" name="ulp-email" placeholder="'.esc_html($popup_options['email_placeholder']).'" value="" onfocus="jQuery(this).removeClass(\'ulp-input-error\');">'.($ulp->options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on' ? '<div class="ulp-fa-input-table"><div class="ulp-fa-input-cell"><i class="'.($ulp->options['fa_solid_enable'] == 'on' ? 'fas fa-envelope' : 'far fa-envelope').'"></i></div></div>' : ''),
						'<input class="ulp-input ulp-input-field'.$mask_class.'"'.$mask.' autocomplete="phone" type="tel" name="ulp-phone" placeholder="'.esc_html($popup_options['phone_placeholder']).'" value="" onfocus="jQuery(this).removeClass(\'ulp-input-error\');">'.($ulp->options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on' ? '<div class="ulp-fa-input-table"><div class="ulp-fa-input-cell"><i class="'.($ulp->options['fa_solid_enable'] == 'on' ? 'fas fa-phone' : 'far fa-bell').'"></i></div></div>' : ''),
						'<textarea class="ulp-input ulp-input-field" name="ulp-message" placeholder="'.esc_html($popup_options['message_placeholder']).'" onfocus="jQuery(this).removeClass(\'ulp-input-error\');"></textarea>',
						'<a href="#" class="ulp-submit'.($popup_options['button_inherit_size'] == 'on' ? ' ulp-inherited' : '').'" onclick="return ulp_subscribe(this);"'.(!empty($button_icon) ? ' data-fa="'.$button_icon.'"' : '').' data-label="'.esc_html($popup_options['button_label']).'" data-loading="'.esc_html($popup_options['button_label_loading']).'">'.(!empty($button_icon) ? '<i class="'.$button_icon.'"></i>' : '').(!empty($button_icon) && !empty($popup_options['button_label']) ? '&nbsp; ' : '').esc_html($popup_options['button_label']).'</a>'),
					$layer_options['content']);
				if ($ulp->options['recaptcha_enable'] == 'on') {
					$recaptcha_id = 'ulp-recaptcha-'.$ulp->random_string(8);
					$content = str_replace('{recaptcha}', '<div class="ulp-recaptcha" id="'.$recaptcha_id.'" name="'.$recaptcha_id.'" data-theme="'.esc_html($popup_options['recaptcha_theme']).'"></div>', $content);
				}
				$content = apply_filters('ulp_front_popup_content', $content, $popup_options);
				$content = do_shortcode($content);
				$base64 = false;
				if (strpos(strtolower($content), '<iframe') !== false || strpos(strtolower($content), '<video') !== false || strpos(strtolower($content), '<audio') !== false) {
					$base64 = true;
					$content = base64_encode($content);
				}
				$front_footer .= '
						<div class="ulp-layer'.(!empty($layer_options['scrollbar']) && $layer_options['scrollbar'] == 'on' ? ' ulp-layer-scrollable' : '').'" id="ulp-layer-'.$layer['id'].'" data-left="'.$layer_options['left'].'" data-top="'.$layer_options['top'].'" data-appearance="'.$layer_options['appearance'].'" data-appearance-speed="'.$layer_options['appearance_speed'].'" data-appearance-delay="'.$layer_options['appearance_delay'].'"'.($base64 ? ' data-base64="yes"' : '').' '.(!empty($layer_options['confirmation_layer']) ? ' data-confirmation="'.$layer_options['confirmation_layer'].'"' : ' data-confirmation="off"').'>'.$content.'</div>';
				
				$background = '';		
				if (!empty($layer_options['background_color'])) {
					$rgb = $ulp->get_rgb($layer_options['background_color']);
					$background .= 'background-color:'.$layer_options['background_color'].';background-color:rgba('.$rgb['r'].','.$rgb['g'].','.$rgb['b'].','.$layer_options['background_opacity'].');';
					if ($layer_options['background_gradient'] == 'on') {
						if (!empty($layer_options['background_gradient_to'])) {
							$rgb_to = $ulp->get_rgb($layer_options['background_gradient_to']);
							$background .= 'background:linear-gradient('.$layer_options['background_gradient_angle'].'deg, rgba('.$rgb['r'].','.$rgb['g'].','.$rgb['b'].','.$layer_options['background_opacity'].') 0%, rgba('.$rgb_to['r'].','.$rgb_to['g'].','.$rgb_to['b'].','.$layer_options['background_opacity'].') 100%);';
						} else {
							$background .= 'background:linear-gradient('.$layer_options['background_gradient_angle'].'deg, rgba('.$rgb['r'].','.$rgb['g'].','.$rgb['b'].','.$layer_options['background_opacity'].') 0%, rgba(0,0,0,0) 100%);';
						}
					}
				}
				$background_hover = '';		
				if (!empty($layer_options['background_hover_color'])) {
					$rgb = $ulp->get_rgb($layer_options['background_hover_color']);
					$background_hover .= 'background-color:'.$layer_options['background_hover_color'].';background-color:rgba('.$rgb['r'].','.$rgb['g'].','.$rgb['b'].','.$layer_options['background_opacity'].');';
					if ($layer_options['background_gradient'] == 'on') {
						if (!empty($layer_options['background_hover_gradient_to'])) {
							$rgb_to = $ulp->get_rgb($layer_options['background_hover_gradient_to']);
							$background_hover .= 'background:linear-gradient('.$layer_options['background_gradient_angle'].'deg, rgba('.$rgb['r'].','.$rgb['g'].','.$rgb['b'].','.$layer_options['background_opacity'].') 0%, rgba('.$rgb_to['r'].','.$rgb_to['g'].','.$rgb_to['b'].','.$layer_options['background_opacity'].') 100%);';
						} else {
							$background_hover .= 'background:linear-gradient('.$layer_options['background_gradient_angle'].'deg, rgba('.$rgb['r'].','.$rgb['g'].','.$rgb['b'].','.$layer_options['background_opacity'].') 0%, rgba(0,0,0,0) 100%);';
						}
					}
				}
				if (!empty($layer_options['background_image'])) {
					$background .= 'background-image:url('.$layer_options['background_image'].');background-repeat:'.$layer_options['background_image_repeat'].';background-size:'.$layer_options['background_image_size'].';';
				}
				$box_shadow = '';
				if ($layer_options['box_shadow'] == 'on') {
					$box_shadow = 'box-shadow:'.$layer_options['box_shadow_h'].'px '.$layer_options['box_shadow_v'].'px '.$layer_options['box_shadow_blur'].'px '.$layer_options['box_shadow_spread'].'px '.$layer_options['box_shadow_color'];
					if ($layer_options['box_shadow_inset'] == 'on') $box_shadow .= ' inset';
					$box_shadow .= ';';
				}
				$border = 'border-radius:'.$layer_options['border_radius'].'px;';
				if (!empty($layer_options['border_color']) && $layer_options['border_width'] > 0 && $layer_options['border_style'] != 'none') {
					$border .= 'border:'.$layer_options['border_width'].'px '.$layer_options['border_style'].' '.$layer_options['border_color'].';';
				}
				$border_hover = '';
				if (!empty($layer_options['border_hover_color']) && $layer_options['border_width'] > 0 && $layer_options['border_style'] != 'none') {
					$border_hover .= 'border:'.$layer_options['border_width'].'px '.$layer_options['border_style'].' '.$layer_options['border_hover_color'].';';
				}
				if (!empty($layer_options['width']) || !empty($layer_options['height'])) {
					$style .= '#ulp-layer-'.$layer['id'].'{'.(!empty($layer_options['width']) ? 'width:'.$layer_options['width'].'px;' : '').(!empty($layer_options['height']) ? 'height:'.$layer_options['height'].'px;' : '').'}';
				}
				if (!empty($content)) {
					$font = "text-align:".$layer_options['content_align'].";".($layer_options['text_shadow_size'] > 0 && !empty($layer_options['text_shadow_color']) ? "text-shadow: ".$layer_options['text_shadow_color']." ".$layer_options['text_shadow_size']."px ".$layer_options['text_shadow_size']."px ".$layer_options['text_shadow_size']."px;" : "");
					if ($layer_options['font'] != 'inherit') $font .= "font-family:'".$layer_options['font']."',arial;";
					if ($layer_options['font_weight'] != 'inherit') $font .= "font-weight:".$layer_options['font_weight'].";";
					if ($layer_options['font_color'] != '') $font .= "color:".$layer_options['font_color'].";";
					if (!empty($layer_options['font_size'])) $font .= "font-size:".$layer_options['font_size']."px;";
					$font_hover = '';
					if (!empty($layer_options['font_hover_color'])) {
						$font_hover = 'color:'.$layer_options['font_hover_color'].';';
					}
					$style .= '#ulp-layer-'.$layer['id'].',#ulp-layer-'.$layer['id'].' * {'.$font.'}';
					if (!empty($font_hover)) $style .= '#ulp-layer-'.$layer['id'].':hover,#ulp-layer-'.$layer['id'].':focus,#ulp-layer-'.$layer['id'].':active,#ulp-layer-'.$layer['id'].' *:hover,#ulp-layer-'.$layer['id'].' *:focus,#ulp-layer-'.$layer['id'].' *:active {'.$font_hover.'}';
					if (strpos($content, 'class="ulp-checkbox"') !== false) {
						$style .= '#ulp-layer-'.$layer['id'].' .ulp-checkbox {font-size:'.min(intval($layer_options['width']),intval($layer_options['height'])).'px;} #ulp-layer-'.$layer['id'].' .ulp-checkbox label::after {line-height:'.floor(0.81*intval($layer_options['height'])).'px;}';
					}
				}
				$style .= '#ulp-layer-'.$layer['id'].'{'.$box_shadow.$background.$border.'z-index:'.($layer_options['index']+1000002).';text-align:'.$layer_options['content_align'].';padding:'.$layer_options['padding_v'].'px '.$layer_options['padding_h'].'px;'.$layer_options['style'].';}';
				if (!empty($background_hover) || !empty($border_hover)) $style .= '#ulp-layer-'.$layer['id'].':hover{'.$background_hover.$border_hover.'}';
				if (!array_key_exists($layer_options['font'], $ulp->local_fonts)) $layer_webfonts[] = $layer_options['font'];
			}
			$front_footer .= '
					</div>
				</div>
			</div>';
			$front_footer .= apply_filters('ulp_front_popup_suffix', '', 'ulp-'.$popup['str_id'], $popup);
		}
		if (!empty($layer_webfonts)) {
			$layer_webfonts = array_unique($layer_webfonts);
			if ($ulp->options['version'] >= 4.58) {
				$webfonts_array = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_webfonts WHERE family IN ('".implode("', '", $layer_webfonts)."') AND deleted = '0' ORDER BY family", ARRAY_A);
			} else $webfonts_array = array();
			if(!empty($webfonts_array)){
				$families = array();
				$subsets = array();
				foreach($webfonts_array as $webfont) {
					$families[] = str_replace(' ', '+', $webfont['family']).':'.$webfont['variants'];
					$webfont_subsets = explode(',', $webfont['subsets']);
					if (!empty($webfont_subsets) && is_array($webfont_subsets)) $subsets = array_merge($subsets, $webfont_subsets);
				}
				$subsets = array_unique($subsets);
				$query = '?family='.implode('|', $families);
				if (!empty($subsets)) $query .= '&subset='.implode(',', $subsets);
				$front_header .= '<link href="//fonts.googleapis.com/css'.$query.'" rel="stylesheet" type="text/css">';
			}
		}
		$front_header .= '<style>'.$style.'</style>';
		return array('header' => $front_header, 'footer' => $front_footer);
	}
	
	function front_enqueue_scripts() {
		global $ulp;
		wp_enqueue_script("jquery");
		if ($ulp->ext_options['minified_sources'] == 'on') {
			wp_enqueue_style('ulp', $ulp->plugins_url.'/css/style.min.css', array(), ULP_VERSION);
			if ($ulp->options['linkedbuttons_enable'] == 'on') wp_enqueue_style('ulp-link-buttons', $ulp->plugins_url.'/css/link-buttons.min.css', array(), ULP_VERSION);
			wp_enqueue_script('ulp', $ulp->plugins_url.'/js/script.min.js', array('jquery'), ULP_VERSION, true);
			if ($ulp->options['fa_enable'] == 'on' && $ulp->options['fa_css_disable'] != 'on') {
				if ($ulp->options['fa_solid_enable'] == 'on' && $ulp->options['fa_regular_enable'] == 'on' && $ulp->options['fa_brands_enable'] == 'on') wp_enqueue_style('ulp-font-awesome-all', $ulp->plugins_url.'/css/fontawesome-all.min.css', array(), ULP_VERSION);
				else {
					wp_enqueue_style('ulp-font-awesome-all', $ulp->plugins_url.'/css/fontawesome.min.css', array(), ULP_VERSION);
					if ($ulp->options['fa_solid_enable'] == 'on') wp_enqueue_style('ulp-font-awesome-solid', $ulp->plugins_url.'/css/fontawesome-solid.min.css', array(), ULP_VERSION);
					if ($ulp->options['fa_regular_enable'] == 'on') wp_enqueue_style('ulp-font-awesome-regular', $ulp->plugins_url.'/css/fontawesome-regular.min.css', array(), ULP_VERSION);
					if ($ulp->options['fa_brands_enable'] == 'on') wp_enqueue_style('ulp-font-awesome-brands', $ulp->plugins_url.'/css/fontawesome-brands.min.css', array(), ULP_VERSION);
				}
			}
			if ($ulp->options['mask_enable'] == 'on' && $ulp->options['mask_js_disable'] != 'on') wp_enqueue_script('jquery-mask', $ulp->plugins_url.'/js/jquery.mask.min.js', array('ulp'), ULP_VERSION, true);
			if ($ulp->options['css3_enable'] == 'on') wp_enqueue_style('animate.css', $ulp->plugins_url.'/css/animate.min.css', array(), ULP_VERSION);
			if (($ulp->options['no_preload'] == 'on' || $ulp->ext_options['inline_ajaxed'] == 'on') && $ulp->options['spinkit_enable'] == 'on') wp_enqueue_style('spinkit', $ulp->plugins_url.'/css/spinkit.min.css', array(), ULP_VERSION);
		} else {
			wp_enqueue_style('ulp', $ulp->plugins_url.'/css/style.css', array(), ULP_VERSION);
			if ($ulp->options['linkedbuttons_enable'] == 'on') wp_enqueue_style('ulp-link-buttons', $ulp->plugins_url.'/css/link-buttons.css', array(), ULP_VERSION);
			wp_enqueue_script('ulp', $ulp->plugins_url.'/js/script.js', array('jquery'), ULP_VERSION, true);
			if ($ulp->options['fa_enable'] == 'on' && $ulp->options['fa_css_disable'] != 'on') {
				if ($ulp->options['fa_solid_enable'] == 'on' && $ulp->options['fa_regular_enable'] == 'on' && $ulp->options['fa_brands_enable'] == 'on') wp_enqueue_style('ulp-font-awesome-all', $ulp->plugins_url.'/css/fontawesome-all.css', array(), ULP_VERSION);
				else {
					wp_enqueue_style('ulp-font-awesome-all', $ulp->plugins_url.'/css/fontawesome.css', array(), ULP_VERSION);
					if ($ulp->options['fa_solid_enable'] == 'on') wp_enqueue_style('ulp-font-awesome-solid', $ulp->plugins_url.'/css/fontawesome-solid.css', array(), ULP_VERSION);
					if ($ulp->options['fa_regular_enable'] == 'on') wp_enqueue_style('ulp-font-awesome-regular', $ulp->plugins_url.'/css/fontawesome-regular.css', array(), ULP_VERSION);
					if ($ulp->options['fa_brands_enable'] == 'on') wp_enqueue_style('ulp-font-awesome-brands', $ulp->plugins_url.'/css/fontawesome-brands.css', array(), ULP_VERSION);
				}
			}
			if ($ulp->options['mask_enable'] == 'on' && $ulp->options['mask_js_disable'] != 'on') wp_enqueue_script('jquery-mask', $ulp->plugins_url.'/js/jquery.mask.js', array('ulp'), ULP_VERSION, true);
			if ($ulp->options['css3_enable'] == 'on') wp_enqueue_style('animate.css', $ulp->plugins_url.'/css/animate.css', array(), ULP_VERSION);
			if (($ulp->options['no_preload'] == 'on' || $ulp->ext_options['inline_ajaxed'] == 'on') && $ulp->options['spinkit_enable'] == 'on') wp_enqueue_style('spinkit', $ulp->plugins_url.'/css/spinkit.css', array(), ULP_VERSION);
		}
		do_action('ulp_wp_enqueue_scripts');
		if ($ulp->options['recaptcha_enable'] == 'on' && $ulp->options['recaptcha_js_disable'] != 'on') {
			wp_deregister_script('recaptcha');
			wp_register_script('recaptcha', 'https://www.google.com/recaptcha/api.js?onload=ulp_recaptcha_loaded&render=explicit&hl=en');
			wp_enqueue_script("recaptcha", true, array(), ULP_VERSION, false);
		}
	}
	
	function front_header() {
		global $wpdb, $ulp;
		echo $ulp->front_header;
	}

	function front_footer() {
		global $wpdb, $ulp;
		echo $ulp->front_footer;
	}

	static function shortcode_handler($_atts) {
		global $post, $wpdb, $ulp;
		$html = '';
		if (is_feed()) {
			if (isset($_atts['feed'])) {
				return '<div>'.$_atts['feed'].'</div>';
			} else return '';
		}
		//if ($ulp->check_options() === true) {
			if (isset($_atts['id']) && !empty($_atts['id'])) {
				$device = array();
				$str_ids = explode('*', $_atts["id"]);
				if (sizeof($str_ids) > 1) {
					if (!empty($str_ids[0])) $device[$str_ids[0]] = 'desktop';
					if (!empty($str_ids[1])) {
						if ($str_ids[0] == $str_ids[1]) $device[$str_ids[0]] = 'all';
						else $device[$str_ids[1]] = 'mobile';
					}
				} else {
					if (!empty($str_ids[0])) $device[$str_ids[0]] = 'all';
				}
				$str_ids = array_slice($str_ids, 0, 2);
				foreach($str_ids as $key => $value) {
					$str_ids[$key] = "'".esc_sql($value)."'";
				}
				$str_ids_sql = implode(',', $str_ids);
				$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' AND str_id IN (".$str_ids_sql.") AND blocked = '0'", ARRAY_A);
				foreach ($popups as $popup) {
					$layer_webfonts = array();
					$style = '';
					$popup_options = unserialize($popup['options']);
					if (is_array($popup_options)) $popup_options = array_merge($ulp->default_popup_options, $popup_options);
					else $popup_options = $ulp->default_popup_options;
					
					if (substr($popup_options['button_icon'], 0, 3) == 'fa-') $popup_options['button_icon'] = 'fa fa-noicon';

					if (!empty($popup_options['button_color'])) {
						$from = $ulp->get_rgb($popup_options['button_color']);
						$total = $from['r']+$from['g']+$from['b'];
						if ($total == 0) $total = 1;
						$to = array();
						$to['r'] = max(0, $from['r']-intval(48*$from['r']/$total));
						$to['g'] = max(0, $from['g']-intval(48*$from['g']/$total));
						$to['b'] = max(0, $from['b']-intval(48*$from['b']/$total));
						$to_color = '#'.($to['r'] < 16 ? '0' : '').dechex($to['r']).($to['g'] < 16 ? '0' : '').dechex($to['g']).($to['b'] < 16 ? '0' : '').dechex($to['b']);
						$from_color = $popup_options['button_color'];
					} else {
						$to_color = 'transparent';
						$from_color = 'transparent';
					}
					
					if (array_key_exists($popup['str_id'], $device)) {
						if ($device[$popup['str_id']] == 'desktop' && array_key_exists('inline-id-desktop', $_atts)) $inline_id = $_atts['inline-id-desktop'];
						else if ($device[$popup['str_id']] == 'mobile' && array_key_exists('inline-id-mobile', $_atts)) $inline_id = $_atts['inline-id-mobile'];
						else if (array_key_exists('inline-id-desktop', $_atts)) $inline_id = $_atts['inline-id-desktop'];
						else $inline_id = $ulp->random_string();
					} else $inline_id = $ulp->random_string();
					
					if (!empty($popup_options['input_background_color'])) $bg_color = $ulp->get_rgb($popup_options['input_background_color']);
					if ($popup_options['button_gradient'] == 'on') {
						$style .= '#ulp-inline-'.$inline_id.' .ulp-submit,#ulp-inline-'.$inline_id.' .ulp-submit:visited{border-radius: '.intval($popup_options['button_border_radius']).'px !important; background: '.$from_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$to_color.','.$from_color.');'.(!empty($popup_options['button_css']) ? $popup_options['button_css'] : '').'}';
						$style .= '#ulp-inline-'.$inline_id.' .ulp-submit:hover,#ulp-inline-'.$inline_id.' .ulp-submit:active{border-radius: '.intval($popup_options['button_border_radius']).'px !important; background: '.$to_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$from_color.','.$to_color.');'.(!empty($popup_options['button_css_hover']) ? $popup_options['button_css_hover'] : '').'}';
					} else {
						$style .= '#ulp-inline-'.$inline_id.' .ulp-submit,#ulp-inline-'.$inline_id.' .ulp-submit:visited{border-radius: '.intval($popup_options['button_border_radius']).'px !important; background: '.$from_color.';border:1px solid '.$from_color.';'.(!empty($popup_options['button_css']) ? $popup_options['button_css'] : '').'}';
						$style .= '#ulp-inline-'.$inline_id.' .ulp-submit:hover,#ulp-inline-'.$inline_id.' .ulp-submit:active{border-radius: '.intval($popup_options['button_border_radius']).'px !important; background: '.$to_color.';border:1px solid '.$to_color.';'.(!empty($popup_options['button_css_hover']) ? $popup_options['button_css_hover'] : '').'}';
					}
					$style .= '#ulp-inline-'.$inline_id.' .ulp-input,#ulp-inline-'.$inline_id.' .ulp-input:hover,#ulp-inline-'.$inline_id.' .ulp-input:active,#ulp-inline-'.$inline_id.' .ulp-input:focus,#ulp-inline-'.$inline_id.' .ulp-checkbox{border-width: '.intval($popup_options['input_border_width']).'px !important; border-radius: '.intval($popup_options['input_border_radius']).'px !important; border-color:'.(empty($popup_options['input_border_color']) ? 'transparent' : $popup_options['input_border_color']).';background-color:'.(empty($popup_options['input_background_color']) ? 'transparent' : $popup_options['input_background_color']).' !important;background-color:'.(empty($popup_options['input_background_color']) ? 'transparent' : 'rgba('.$bg_color['r'].','.$bg_color['g'].','.$bg_color['b'].','.floatval($popup_options['input_background_opacity'])).') !important;'.(!empty($popup_options['input_css']) ? $popup_options['input_css'] : '').'}';
					$style .= '#ulp-inline-'.$inline_id.', #ulp-inline-'.$inline_id.' .ulp-content{width:'.$popup_options['width'].'px;height:'.$popup_options['height'].'px;}';
					if ($ulp->options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on') {
						$style .= '#ulp-inline-'.$inline_id.' input.ulp-input, #ulp-inline-'.$inline_id.' select.ulp-input {padding-left: 2.5em !important;}';
					}
					$style = apply_filters('ulp_front_inline_style', $style, $inline_id, $popup);

					$html .= '
						<div class="ulp-inline-window" id="ulp-inline-'.$inline_id.'" data-id="'.$popup['str_id'].'" data-title="'.esc_html($popup['title']).'" data-width="'.$popup_options['width'].'" data-height="'.$popup_options['height'].'" data-close="'.$popup_options['enable_close'].'" data-enter="'.$popup_options['enable_enter'].'" data-device="'.(array_key_exists($popup['str_id'], $device) ? $device[$popup['str_id']] : 'all').'">
							<div class="ulp-content">';
					$layers = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_layers WHERE popup_id = '".$popup['id']."' AND deleted = '0' ORDER BY zindex ASC, id ASC", ARRAY_A);
					foreach ($layers as $layer) {
						$layer_options = unserialize($layer['details']);
						if (is_array($layer_options)) $layer_options = array_merge($ulp->default_layer_options, $layer_options);
						else $layer_options = $ulp->default_layer_options;
						$layer_options = $ulp->filter_lp($layer_options);
						if ($layer_options['inline_disable'] == 'on') continue;
						$mask_class = '';
						$mask = '';
						if ($ulp->options['mask_enable'] == 'on') {
							$mask_class = $popup_options['phone_mask'] != 'none' ? ' ulp-input-mask' : '';
							if ($popup_options['phone_mask'] != 'none') $mask = ' data-mask="'.esc_html($popup_options['phone_mask'] != 'custom' ? $popup_options['phone_mask'] : $popup_options['phone_custom_mask']).'"';
						}
						if ($ulp->options['fa_enable'] == 'on' && !empty($popup_options['button_icon']) && $popup_options['button_icon'] != 'fa fa-noicon') $button_icon = $popup_options['button_icon'];
						else $button_icon = '';
						$content = str_replace(
							array('{subscription-name}', '{subscription-email}', '{subscription-phone}', '{subscription-message}', '{subscription-submit}'),
							array(
								'<input class="ulp-input ulp-input-field" type="text" autocomplete="name" name="ulp-name" placeholder="'.esc_html($popup_options['name_placeholder']).'" value="" onfocus="jQuery(this).removeClass(\'ulp-input-error\');">'.($ulp->options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on' ? '<div class="ulp-fa-input-table"><div class="ulp-fa-input-cell"><i class="'.($ulp->options['fa_solid_enable'] == 'on' ? 'fas fa-user' : 'far fa-user').'"></i></div></div>' : ''),
								'<input class="ulp-input ulp-input-field" type="email" autocomplete="email" name="ulp-email" placeholder="'.esc_html($popup_options['email_placeholder']).'" value="" onfocus="jQuery(this).removeClass(\'ulp-input-error\');">'.($ulp->options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on' ? '<div class="ulp-fa-input-table"><div class="ulp-fa-input-cell"><i class="'.($ulp->options['fa_solid_enable'] == 'on' ? 'fas fa-envelope' : 'far fa-envelope').'"></i></div></div>' : ''),
								'<input class="ulp-input ulp-input-field'.$mask_class.'"'.$mask.' type="text" autocomplete="phone" name="ulp-phone" placeholder="'.esc_html($popup_options['phone_placeholder']).'" value="" onfocus="jQuery(this).removeClass(\'ulp-input-error\');">'.($ulp->options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on' ? '<div class="ulp-fa-input-table"><div class="ulp-fa-input-cell"><i class="'.($ulp->options['fa_solid_enable'] == 'on' ? 'fas fa-phone' : 'far fa-bell').'"></i></div></div>' : ''),
								'<textarea class="ulp-input ulp-input-field" name="ulp-message" placeholder="'.esc_html($popup_options['message_placeholder']).'" onfocus="jQuery(this).removeClass(\'ulp-input-error\');"></textarea>',
								'<a href="#" class="ulp-submit'.($popup_options['button_inherit_size'] == 'on' ? ' ulp-inherited' : '').'" onclick="return ulp_subscribe(this);"'.(!empty($button_icon) ? ' data-fa="'.$button_icon.'"' : '').' data-label="'.esc_html($popup_options['button_label']).'" data-loading="'.esc_html($popup_options['button_label_loading']).'">'.(!empty($button_icon) ? '<i class="'.$button_icon.'"></i>' : '').(!empty($button_icon) && !empty($popup_options['button_label']) ? '&nbsp; ' : '').esc_html($popup_options['button_label']).'</a>'),
							$layer_options['content']);
						if ($ulp->options['recaptcha_enable'] == 'on') {
							$recaptcha_id = 'ulp-recaptcha-'.$ulp->random_string(8);
							$content = str_replace('{recaptcha}', '<div class="ulp-recaptcha" id="'.$recaptcha_id.'" name="'.$recaptcha_id.'" data-theme="'.esc_html($popup_options['recaptcha_theme']).'"></div>', $content);
						}
						$content = apply_filters('ulp_front_popup_content', $content, $popup_options);
						$content = do_shortcode($content);
						$base64 = false;
						if (strpos(strtolower($content), '<iframe') !== false || strpos(strtolower($content), '<video') !== false || strpos(strtolower($content), '<audio') !== false) {
							$base64 = true;
							$content = base64_encode($content);
						}
						$html .= '
								<div class="ulp-layer'.(!empty($layer_options['scrollbar']) && $layer_options['scrollbar'] == 'on' ? ' ulp-layer-scrollable' : '').'" id="ulp-inline-layer-'.$inline_id.'-'.$layer['id'].'" data-left="'.$layer_options['left'].'" data-top="'.$layer_options['top'].'" data-appearance="'.$layer_options['appearance'].'" data-appearance-speed="'.$layer_options['appearance_speed'].'" data-appearance-delay="'.$layer_options['appearance_delay'].'"'.($base64 ? ' data-base64="yes"' : '').' '.(!empty($layer_options['confirmation_layer']) ? ' data-confirmation="'.$layer_options['confirmation_layer'].'"' : ' data-confirmation="off"').'>'.$content.'</div>';
						$background = '';		
						if (!empty($layer_options['background_color'])) {
							$rgb = $ulp->get_rgb($layer_options['background_color']);
							$background .= 'background-color:'.$layer_options['background_color'].';background-color:rgba('.$rgb['r'].','.$rgb['g'].','.$rgb['b'].','.$layer_options['background_opacity'].');';
							if ($layer_options['background_gradient'] == 'on') {
								if (!empty($layer_options['background_gradient_to'])) {
									$rgb_to = $ulp->get_rgb($layer_options['background_gradient_to']);
									$background .= 'background:linear-gradient('.$layer_options['background_gradient_angle'].'deg, rgba('.$rgb['r'].','.$rgb['g'].','.$rgb['b'].','.$layer_options['background_opacity'].') 0%, rgba('.$rgb_to['r'].','.$rgb_to['g'].','.$rgb_to['b'].','.$layer_options['background_opacity'].') 100%);';
								} else {
									$background .= 'background:linear-gradient('.$layer_options['background_gradient_angle'].'deg, rgba('.$rgb['r'].','.$rgb['g'].','.$rgb['b'].','.$layer_options['background_opacity'].') 0%, rgba(0,0,0,0) 100%);';
								}
							}
						}
						$background_hover = '';
						if (!empty($layer_options['background_hover_color'])) {
							$rgb = $ulp->get_rgb($layer_options['background_hover_color']);
							$background_hover .= 'background-color:'.$layer_options['background_hover_color'].';background-color:rgba('.$rgb['r'].','.$rgb['g'].','.$rgb['b'].','.$layer_options['background_opacity'].');';
							if ($layer_options['background_gradient'] == 'on') {
								if (!empty($layer_options['background_hover_gradient_to'])) {
									$rgb_to = $ulp->get_rgb($layer_options['background_hover_gradient_to']);
									$background_hover .= 'background:linear-gradient('.$layer_options['background_gradient_angle'].'deg, rgba('.$rgb['r'].','.$rgb['g'].','.$rgb['b'].','.$layer_options['background_opacity'].') 0%, rgba('.$rgb_to['r'].','.$rgb_to['g'].','.$rgb_to['b'].','.$layer_options['background_opacity'].') 100%);';
								} else {
									$background_hover .= 'background:linear-gradient('.$layer_options['background_gradient_angle'].'deg, rgba('.$rgb['r'].','.$rgb['g'].','.$rgb['b'].','.$layer_options['background_opacity'].') 0%, rgba(0,0,0,0) 100%);';
								}
							}
						}
						if (!empty($layer_options['background_image'])) {
							$background .= 'background-image:url('.$layer_options['background_image'].');background-repeat:'.$layer_options['background_image_repeat'].';background-size:'.$layer_options['background_image_size'].';';
						}
						$box_shadow = '';
						if ($layer_options['box_shadow'] == 'on') {
							$box_shadow = 'box-shadow:'.$layer_options['box_shadow_h'].'px '.$layer_options['box_shadow_v'].'px '.$layer_options['box_shadow_blur'].'px '.$layer_options['box_shadow_spread'].'px '.$layer_options['box_shadow_color'];
							if ($layer_options['box_shadow_inset'] == 'on') $box_shadow .= ' inset';
							$box_shadow .= ';';
						}
						$border = 'border-radius:'.$layer_options['border_radius'].'px;';
						if (!empty($layer_options['border_color']) && $layer_options['border_width'] > 0 && $layer_options['border_style'] != 'none') {
							$border .= 'border:'.$layer_options['border_width'].'px '.$layer_options['border_style'].' '.$layer_options['border_color'].';';
						}
						$border_hover = '';
						if (!empty($layer_options['border_hover_color']) && $layer_options['border_width'] > 0 && $layer_options['border_style'] != 'none') {
							$border_hover .= 'border:'.$layer_options['border_width'].'px '.$layer_options['border_style'].' '.$layer_options['border_hover_color'].';';
						}
						if (!empty($layer_options['width']) || !empty($layer_options['height'])) {
							$style .= '#ulp-inline-layer-'.$inline_id.'-'.$layer['id'].'{'.(!empty($layer_options['width']) ? 'width:'.$layer_options['width'].'px;' : '').(!empty($layer_options['height']) ? 'height:'.$layer_options['height'].'px;' : '').'}';
						}
						if (!empty($content)) {
							$font = "text-align:".$layer_options['content_align'].";".($layer_options['text_shadow_size'] > 0 && !empty($layer_options['text_shadow_color']) ? "text-shadow: ".$layer_options['text_shadow_color']." ".$layer_options['text_shadow_size']."px ".$layer_options['text_shadow_size']."px ".$layer_options['text_shadow_size']."px;" : "");
							if ($layer_options['font'] != 'inherit') $font .= "font-family:'".$layer_options['font']."',arial;";
							if ($layer_options['font_weight'] != 'inherit') $font .= "font-weight:".$layer_options['font_weight'].";";
							if ($layer_options['font_color'] != '') $font .= "color:".$layer_options['font_color'].";";
							if (!empty($layer_options['font_size'])) $font .= "font-size:".$layer_options['font_size']."px;";
							$font_hover = '';
							if (!empty($layer_options['font_hover_color'])) {
								$font_hover = 'color:'.$layer_options['font_hover_color'].';';
							}
							$style .= '#ulp-inline-layer-'.$inline_id.'-'.$layer['id'].',#ulp-inline-layer-'.$inline_id.'-'.$layer['id'].' * {'.$font.'}';
							if (!empty($font_hover)) $style .= '#ulp-inline-layer-'.$inline_id.'-'.$layer['id'].':hover,#ulp-inline-layer-'.$inline_id.'-'.$layer['id'].' *:hover {'.$font_hover.'}';
							if (strpos($content, 'class="ulp-checkbox"') !== false) {
								$style .= '#ulp-inline-layer-'.$inline_id.'-'.$layer['id'].' .ulp-checkbox {font-size:'.min(intval($layer_options['width']),intval($layer_options['height'])).'px;} #ulp-inline-layer-'.$inline_id.'-'.$layer['id'].' .ulp-checkbox label::after {line-height:'.floor(0.81*intval($layer_options['height'])).'px;}';
							}
						}
						$style .= '#ulp-inline-layer-'.$inline_id.'-'.$layer['id'].'{'.$box_shadow.$background.$border.'z-index:'.($layer_options['index']+10).';text-align:'.$layer_options['content_align'].';padding:'.$layer_options['padding_v'].'px '.$layer_options['padding_h'].'px;'.$layer_options['style'].';}';
						if (!empty($background_hover) || !empty($border_hover)) $style .= '#ulp-inline-layer-'.$inline_id.'-'.$layer['id'].':hover{'.$background_hover.$border_hover.'}';
						if (!array_key_exists($layer_options['font'], $ulp->local_fonts)) $layer_webfonts[] = $layer_options['font'];
					}
					$html .= '
							</div>
						</div>';
					$html .= apply_filters('ulp_front_popup_suffix', '', 'ulp-inline-'.$inline_id, $popup);
					$html = '<style>'.$style.'</style>'.$html;
					
					if (!empty($layer_webfonts)) {
						$layer_webfonts = array_unique($layer_webfonts);
						if ($ulp->options['version'] >= 4.58) {
							$webfonts_array = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_webfonts WHERE family IN ('".implode("', '", $layer_webfonts)."') AND deleted = '0' ORDER BY family", ARRAY_A);
						} else $webfonts_array = array();
						if(!empty($webfonts_array)){
							$families = array();
							$subsets = array();
							foreach($webfonts_array as $webfont) {
								$families[] = str_replace(' ', '+', $webfont['family']).':'.$webfont['variants'];
								$webfont_subsets = explode(',', $webfont['subsets']);
								if (!empty($webfont_subsets) && is_array($webfont_subsets)) $subsets = array_merge($subsets, $webfont_subsets);
							}
							$subsets = array_unique($subsets);
							$query = '?family='.implode('|', $families);
							if (!empty($subsets)) $query .= '&subset='.implode(',', $subsets);
							$html = '<link href="//fonts.googleapis.com/css'.$query.'" rel="stylesheet" type="text/css">'.$html;
						}
					}
				}
			}
		//}
		return $html;
	}
	static function shortcode_ajaxed_handler($_atts) {
		global $post, $wpdb, $ulp;
		$html = '';
		if (is_feed()) {
			if (isset($_atts['feed'])) {
				return '<div>'.$_atts['feed'].'</div>';
			} else return '';
		}
		$layer_webfonts = array();
		$style = '';
		//if ($ulp->check_options() === true) {
			if (isset($_atts['id'])) {
				$str_id = $_atts["id"];
				$str_ids = explode('*', $_atts["id"]);
				foreach($str_ids as $key => $value) {
					$str_ids[$key] = "'".esc_sql($value)."'";
				}
				$str_ids_sql = implode(',', $str_ids);
				$popup = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' AND str_id IN (".$str_ids_sql.") AND blocked = '0'", ARRAY_A);
				if ($popup) {
					if (sizeof($str_ids) == 1) {
						$popup_options = unserialize($popup['options']);
						if (is_array($popup_options)) $popup_options = array_merge($ulp->default_popup_options, $popup_options);
						else $popup_options = $ulp->default_popup_options;
						if ($ulp->options['spinkit_enable'] != 'on') $popup_options['ajax_spinner'] = 'classic';
						switch ($popup_options['ajax_spinner']) {
							case 'chasing-dots':
								$html = '<style>#ulp-inline-spinner-'.$str_id.' .ulp-spinner-child {background-color: '.$popup_options['ajax_spinner_color'].' !important;}</style>';
								break;
							case 'circle':
								$html = '<style>#ulp-inline-spinner-'.$str_id.' .ulp-spinner-child:before {background-color: '.$popup_options['ajax_spinner_color'].' !important;}</style>';
								break;
							case 'double-bounce':
								$html = '<style>#ulp-inline-spinner-'.$str_id.' .ulp-spinner-child {background-color: '.$popup_options['ajax_spinner_color'].' !important;}</style>';
								break;
							case 'fading-circle':
								$html = '<style>#ulp-inline-spinner-'.$str_id.' .ulp-spinner-child:before {background-color: '.$popup_options['ajax_spinner_color'].' !important;}</style>';
								break;
							case 'folding-cube':
								$html = '<style>#ulp-inline-spinner-'.$str_id.' .ulp-spinner-child:before {background-color: '.$popup_options['ajax_spinner_color'].' !important;}</style>';
								break;
							case 'pulse':
								$html = '<style>#ulp-inline-spinner-'.$str_id.' .ulp-spinner-spinner-pulse {background-color: '.$popup_options['ajax_spinner_color'].' !important;}</style>';
								break;
							case 'rotating-plane':
								$html = '<style>#ulp-inline-spinner-'.$str_id.' .ulp-spinner-rotating-plane {background-color: '.$popup_options['ajax_spinner_color'].' !important;}</style>';
								break;
							case 'three-bounce':
								$html = '<style>#ulp-inline-spinner-'.$str_id.' .ulp-spinner-child {background-color: '.$popup_options['ajax_spinner_color'].' !important;}</style>';
								break;
							case 'wandering-cubes':
								$html = '<style>#ulp-inline-spinner-'.$str_id.' .ulp-spinner-child {background-color: '.$popup_options['ajax_spinner_color'].' !important;}</style>';
								break;
							case 'wave':
								$html = '<style>#ulp-inline-spinner-'.$str_id.' .ulp-spinner-child {background-color: '.$popup_options['ajax_spinner_color'].' !important;}</style>';
								break;
							default:
								break;
						}
						$html .= '
						<div class="ulp-inline" data-id="'.$str_id.'"><div class="ulp-inline-spinner" id="ulp-inline-spinner-'.$str_id.'">'.$ulp->ajax_spinners[$popup_options['ajax_spinner']].'</div></div>';
					} else {
						$html .= '
						<div class="ulp-inline" data-id="'.$str_id.'"><div class="ulp-inline-spinner">'.$ulp->ajax_spinners['classic'].'</div></div>';
					}
				}
			}
		//}	
		return $html;
	}
	function shortcode_linklocker_handler($_atts, $_content = null) {
		global $wpdb;
		if (is_feed()) {
			if (isset($_atts['feed'])) {
				return '<div>'.$_atts['feed'].'</div>';
			} else return $_content;
		}
		$content = $_content;
		if (isset($_atts['id'])) {
			$ids = explode('*', $_atts["id"]);
			$popup = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' AND str_id IN ('".implode("','", $ids)."') AND blocked = '0'", ARRAY_A);
			if ($popup) {
				$str_id = implode('*', $ids);
				$regexp = "<a\s[^>]*href=(\"|'??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
				$original_links = array();
				$new_links = array();
				if(preg_match_all("/$regexp/siU", $_content, $matches)) {
					foreach ($matches[0] as $key => $value) {
						if (substr($matches[2][$key], 0, 1) != '#' && !empty($matches[2][$key])) {
							$original_links[] = $value;
							$new_links[] = preg_replace('/'.preg_quote($matches[2][$key], '/').'/', '#ulp-'.$str_id.':'.base64_encode($matches[2][$key]), $value, 1);
						}
					}
					$content = str_replace($original_links, $new_links, $_content);
				}
			}
		}
		return $content;
	}
	function widgets_init() {
		include_once(dirname(dirname(__FILE__)).'/widget.php');
		register_widget('ulp_widget');
	}
}
?>