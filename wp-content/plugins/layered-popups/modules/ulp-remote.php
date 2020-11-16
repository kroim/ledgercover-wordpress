<?php
/* Integrate Layered Popups into non-WP pages */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_remote_class {
	function __construct() {
		if (is_admin()) {
			if (!defined('UAP_CORE')) {
				add_action('ulp_options_show', array(&$this, 'options_show'));
			}
			add_action('wp_ajax_ulp-remote-init', array(&$this, "init"));
			add_action('wp_ajax_nopriv_ulp-remote-init', array(&$this, "init"));
		}
	}
	function options_show() {
		global $ulp;
		echo '
			<h3>'.__('Remote access', 'ulp').'</h3>
			<table class="ulp_useroptions">
				<tr>
					<th>'.__('Snippet', 'ulp').':</th>
					<td>
						<textarea readonly="readonly" class="widefat ulp-js-code" style="height: 80px;" onclick="this.focus();this.select();">&lt;script id="ulp-remote" src="'.$ulp->plugins_url.'/js/remote.min.js?ver='.ULP_VERSION.'" data-handler="'.admin_url('admin-ajax.php').'"&gt;&lt;/script&gt;</textarea>
						<br /><em>'.__('Paste this snippet into your non-WP page or 3rd party website. Read <a href="https://layeredpopups.com/documentation/#remote" target="_blank">documentation</a> regarding using this feature.', 'ulp').'</em>
					</td>
				</tr>
			</table>';
	}
	function init() {
		global $wpdb, $ulp, $ulp_social2;
		if (isset($_REQUEST['callback'])) {
			header("Content-type: text/javascript");
			$jsonp_callback = $_REQUEST['callback'];
		} else die("JSONP is not supported!");
		if (isset($_REQUEST['ignore_status']) && $_REQUEST['ignore_status'] == 'on') $ignore_status = true;
		else $ignore_status = false;
		$return_data = array();
		$return_data['status'] = 'OK';
		if ($ulp->ext_options['minified_sources'] == 'on') {
			if ($ulp->options['linkedbuttons_enable'] == 'on') $return_data['resources']['css'][] = $ulp->plugins_url.'/css/link-buttons.min.css?ver='.ULP_VERSION;
			if ($ulp->options['fa_enable'] == 'on') $return_data['resources']['css'][] = $ulp->plugins_url.'/css/font-awesome.min.css?ver='.ULP_VERSION;
			if ($ulp->options['fa_enable'] == 'on') {
				if ($ulp->options['fa_solid_enable'] == 'on' && $ulp->options['fa_regular_enable'] == 'on' && $ulp->options['fa_brands_enable'] == 'on') $return_data['resources']['css'][] = $ulp->plugins_url.'/css/fontawesome-all.min.css?ver='.ULP_VERSION;
				else {
					$return_data['resources']['css'][] = $ulp->plugins_url.'/css/fontawesome.min.css?ver='.ULP_VERSION;
					if ($ulp->options['fa_solid_enable'] == 'on') $return_data['resources']['css'][] = $ulp->plugins_url.'/css/fontawesome-solid.min.css?ver='.ULP_VERSION;
					if ($ulp->options['fa_regular_enable'] == 'on')$return_data['resources']['css'][] = $ulp->plugins_url.'/css/fontawesome-regular.min.css?ver='.ULP_VERSION;
					if ($ulp->options['fa_brands_enable'] == 'on') $return_data['resources']['css'][] = $ulp->plugins_url.'/css/fontawesome-brands.min.css?ver='.ULP_VERSION;
				}
			}
			if ($ulp->options['css3_enable'] == 'on') $return_data['resources']['css'][] = $ulp->plugins_url.'/css/animate.min.css?ver='.ULP_VERSION;
			if ($ulp->options['mask_enable'] == 'on') $return_data['resources']['js'][] = $ulp->plugins_url.'/js/jquery.mask.min.js?ver='.ULP_VERSION;
			if ($ulp->options['spinkit_enable'] == 'on') $return_data['resources']['css'][] = $ulp->plugins_url.'/css/spinkit.min.css?ver='.ULP_VERSION;
			$return_data['resources']['css'][] = $ulp->plugins_url.'/css/style.min.css?ver='.ULP_VERSION;
		} else {
			if ($ulp->options['linkedbuttons_enable'] == 'on') $return_data['resources']['css'][] = $ulp->plugins_url.'/css/link-buttons.css?ver='.ULP_VERSION;
			if ($ulp->options['fa_enable'] == 'on') {
				if ($ulp->options['fa_solid_enable'] == 'on' && $ulp->options['fa_regular_enable'] == 'on' && $ulp->options['fa_brands_enable'] == 'on') $return_data['resources']['css'][] = $ulp->plugins_url.'/css/fontawesome-all.css?ver='.ULP_VERSION;
				else {
					$return_data['resources']['css'][] = $ulp->plugins_url.'/css/fontawesome.css?ver='.ULP_VERSION;
					if ($ulp->options['fa_solid_enable'] == 'on') $return_data['resources']['css'][] = $ulp->plugins_url.'/css/fontawesome-solid.css?ver='.ULP_VERSION;
					if ($ulp->options['fa_regular_enable'] == 'on')$return_data['resources']['css'][] = $ulp->plugins_url.'/css/fontawesome-regular.css?ver='.ULP_VERSION;
					if ($ulp->options['fa_brands_enable'] == 'on') $return_data['resources']['css'][] = $ulp->plugins_url.'/css/fontawesome-brands.css?ver='.ULP_VERSION;
				}
			}
			if ($ulp->options['css3_enable'] == 'on') $return_data['resources']['css'][] = $ulp->plugins_url.'/css/animate.css?ver='.ULP_VERSION;
			if ($ulp->options['mask_enable'] == 'on') $return_data['resources']['js'][] = $ulp->plugins_url.'/js/jquery.mask.js?ver='.ULP_VERSION;
			if ($ulp->options['spinkit_enable'] == 'on') $return_data['resources']['css'][] = $ulp->plugins_url.'/css/spinkit.css?ver='.ULP_VERSION;
			$return_data['resources']['css'][] = $ulp->plugins_url.'/css/style.css?ver='.ULP_VERSION;
		}
		if ($ulp->options['recaptcha_enable'] == 'on') {
			$return_data['resources']['recaptcha'] = 'on';
			$return_data['recaptcha_public_key'] = $ulp->options['recaptcha_public_key'];
		}
		$return_data['recaptcha_enable'] = $ulp->options['recaptcha_enable'];
		$return_data['cookie_value'] = $ulp->options['cookie_value'];
		
		$return_data['count_impressions'] = $ulp->ext_options['count_impressions'];
		$return_data['css3_enable'] = $ulp->options['css3_enable'];
		$return_data['ga_tracking'] = $ulp->options['ga_tracking'];
		$return_data['km_tracking'] = $ulp->options['km_tracking'];
		$return_data['onexit_limits'] = $ulp->options['onexit_limits'];
		
		if ($ulp->ext_options['enable_social'] == 'on') {
			$return_data['resources']['facebooksdk'] = 'on';
			$return_data['resources']['twittersdk'] = 'on';
			$return_data['resources']['linkedin'] = 'on';
			$return_data['resources']['googleplusone'] = 'on';
		}
		if ($ulp->ext_options['enable_social2'] == 'on') {
			$return_data['resources']['facebooksdk'] = 'on';
			$return_data['resources']['googleclient'] = 'on';
			$return_data['google_apikey'] = $ulp_social2->options['social2_google_apikey'];
			$return_data['google_clientid'] = $ulp_social2->options['social2_google_clientid'];
			$return_data['facebook_appid'] = $ulp_social2->options['social2_facebook_appid'];
		}
		$return_data = apply_filters('ulp_remote_data', $return_data);
		
		$campaigns = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_campaigns WHERE deleted = '0'".($ignore_status ? "" : " AND blocked = '0'")." ORDER BY created DESC", ARRAY_A);
		foreach ($campaigns as $campaign) {
			$popups = $wpdb->get_results("SELECT t1.*, t2.str_id FROM ".$wpdb->prefix."ulp_campaign_items t1 JOIN ".$wpdb->prefix."ulp_popups t2 ON t2.id = t1.popup_id WHERE t1.campaign_id = '".$campaign['id']."' AND t1.deleted = '0' AND t2.deleted = '0'".($ignore_status ? "" : " AND t2.blocked = '0'")." ORDER BY t1.created DESC", ARRAY_A);
			$campaign_popups = array();
			foreach($popups as $popup) {
				$return_data['campaigns'][$campaign['str_id']][] = $popup['str_id'];
			}
		}

		$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0'".($ignore_status ? "" : " AND blocked = '0'"), ARRAY_A);
		foreach ($popups as $popup) {
			$popup_options = unserialize($popup['options']);
			if (is_array($popup_options)) $popup_options = array_merge($ulp->default_popup_options, $popup_options);
			else $popup_options = $ulp->default_popup_options;
			if ($ulp->options['spinkit_enable'] != 'on') $popup_options['ajax_spinner'] = 'classic';
			$return_data['overlays'][$popup['str_id']] = array(
				($popup_options['disable_overlay'] == 'on' ? '' : (!empty($popup_options['overlay_color']) ? $popup_options['overlay_color'] : 'transparent')),
				$popup_options['overlay_opacity'],
				$popup_options['enable_close'],
				$popup_options['position'],
				$popup_options['overlay_animation'],
				$popup_options['ajax_spinner'],
				$popup_options['ajax_spinner_color']
			);
		}

		if (isset($_REQUEST['inline_ids'])) {
			$inline_ids = explode(',', preg_replace('/[^a-zA-Z0-9,]/', '', $_REQUEST['inline_ids']));
			if (sizeof($inline_ids) > 0) {
				include_once(dirname(__FILE__).'/core-front.php');
				foreach($inline_ids as $key => $value) {
					if (!empty($value)) {
						$return_data['inline_popups'][$value] = ulp_front_class::shortcode_handler(array('id' => $value));
					}
				}
			}
		}
		
		echo $jsonp_callback.'('.json_encode($return_data).')';
		exit;
	}
}
$ulp_remote = new ulp_remote_class();
?>