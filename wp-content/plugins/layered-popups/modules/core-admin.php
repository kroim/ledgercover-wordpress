<?php
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_admin_class {
	var $helper_add_layer_items;
	function __construct() {
		global $ulp;
		if (is_admin()) {
			$this->helper_add_layer_items = array(
				'general' => array(
					'label' => __('General', 'ulp'),
					'items' => array(
						'label' => array(
							'icon' => 'fas fa-font',
							'label' => __('Text Label/Link', 'ulp'),
							'comment' => __('Insert text label or link', 'ulp')
						),
						'rectangle' => array(
							'icon' => 'fas fa-stop',
							'label' => __('Rectangle', 'ulp'),
							'comment' => __('Insert rectangle or square', 'ulp')
						),
						'image' => array(
							'icon' => 'fas fa-image',
							'label' => __('Image', 'ulp'),
							'comment' => __('Insert image', 'ulp')
						),
						'icon' => array(
							'icon' => 'fas fa-info',
							'label' => __('FontAwesome Icon', 'ulp'),
							'comment' => __('Insert FontAwesome icon', 'ulp')
						),
						'linked-button' => array(
							'icon' => 'fas fa-link',
							'label' => __('Linked Button', 'ulp'),
							'comment' => __('Insert linked button', 'ulp')
						),
						'html' => array(
							'icon' => 'fas fa-code',
							'label' => __('Custom HTML', 'ulp'),
							'comment' => __('Insert custom HTML-code', 'ulp')
						),
						'bullet' => array(
							'icon' => 'fas fa-list-ul',
							'label' => __('Bulleted List', 'ulp'),
							'comment' => __('Insert bulleted HTML-list', 'ulp')
						),
						'close' => array(
							'icon' => 'fas fa-times',
							'label' => __('Close Icon', 'ulp'),
							'comment' => __('Insert "close" icon', 'ulp')
						)
					)
				),
				'form' => array(
					'label' => __('Form Elements', 'ulp'),
					'items' => array(
						'field-email' => array(
							'icon' => 'fas fa-envelope',
							'label' => __('E-mail Field', 'ulp'),
							'comment' => __('Insert "e-mail" input field', 'ulp'),
							'unique' => '{subscription-email}'
						),
						'field-name' => array(
							'icon' => 'fas fa-user',
							'label' => __('Name Field', 'ulp'),
							'comment' => __('Insert "name" input field', 'ulp'),
							'unique' => '{subscription-name}'
						),
						'field-phone' => array(
							'icon' => 'fas fa-phone',
							'label' => __('Phone Field', 'ulp'),
							'comment' => __('Insert "phone" input field', 'ulp'),
							'unique' => '{subscription-phone}'
						),
						'field-message' => array(
							'icon' => 'fas fa-align-left',
							'label' => __('Message Field', 'ulp'),
							'comment' => __('Insert "message" field', 'ulp'),
							'unique' => '{subscription-message}'
						),
						'submit-button' => array(
							'icon' => 'fas fa-paper-plane',
							'label' => __('Submit Button', 'ulp'),
							'comment' => __('Insert "submit" button', 'ulp'),
							'unique' => '{subscription-submit}'
						)
					)
				),
				'video' => array(
					'label' => __('Video', 'ulp'),
					'items' => array(
						'youtube' => array(
							'icon' => 'fab fa-youtube',
							'label' => __('YouTube Video', 'ulp'),
							'comment' => __('Embed YouTube video', 'ulp')
						),
						'vimeo' => array(
							'icon' => 'fab fa-vimeo-square',
							'label' => __('Vimeo Video', 'ulp'),
							'comment' => __('Embed Vimeo video', 'ulp')
						)
					)
				)
			);

			$linkedbuttons_enable = get_option('ulp_linkedbuttons_enable');
			if ($linkedbuttons_enable != 'on') unset($this->helper_add_layer_items['general']['items']['linked-button']);
			
			$version = get_option('ulp_version');
			$webfonts_version = get_option('ulp_webfonts_version', 0);
			if (($version && $version < 6.34) || $webfonts_version < ULP_WEBFONTS_VERSION) {
				add_action('admin_notices', array(&$this, 'admin_warning'));
			}
			$clean_database = get_option('ulp_ext_clean_database');
			if ($clean_database && $clean_database == 'on') {
				add_action('admin_notices', array(&$this, 'admin_warning_clean_database'));
				add_action('after_plugin_row_'.basename(dirname(dirname(__FILE__))).'/layered-popups.php', array(&$this, 'after_plugin_row'), 10, 3);
			}
			
			add_action('widgets_init', array(&$this, 'widgets_init'));
			add_filter('mce_external_plugins', array(&$this, 'register_tinymce_plugin'));
			add_filter('mce_buttons', array(&$this, 'add_tinymce_button'));
			
			add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
			add_action('admin_head', array(&$this, 'admin_head'));
			add_action('admin_menu', array(&$this, 'admin_menu'), 1);
			add_action('init', array(&$this, 'admin_request_handler'));
			if (!defined('UAP_CORE')) {
				$advanced_targeting_enable = get_option('ulp_ext_advanced_targeting');
				if ($advanced_targeting_enable != 'on') {
					add_action('admin_menu', array(&$this, 'add_meta'));
					add_action('save_post', array(&$this, 'save_meta'), 10, 2);
				}
			}
			//if (!empty($this->options['purchase_code'])) {
				add_filter('pre_set_site_transient_update_plugins', array(&$this, 'check_for_plugin_update'));
				add_filter('plugins_api', array(&$this, 'plugin_api_call'), 10, 3);
			//}
			/* Personal Data - 2018-05-26 - begin */
			include_once(dirname(__FILE__).'/core-personal-data.php');
			$ulp_personal_front = new ulp_personal_data_class();
			/* Personal Data - 2018-05-26 - end */
		}
	}
	
	function admin_warning() {
		echo '
		<div class="error ulp-error ulp-error-animated"><p><strong>IMPORTANT!</strong> Please deactivate and activate <strong>Layered Popups</strong> plugin <a href="'.admin_url('plugins.php').'">here</a>! It is necessary to sync database for additional functionality.</p></div>';
	}

	function admin_warning_clean_database() {
		echo '
		<div class="error ulp-error ulp-error-animated"><p><strong>IMPORTANT!</strong> You enabled CLEAN DATABASE feature for Layered Popups. Once Layered Popups deactivted, all tables and records, related to this plugin, will be completely deleted. If you enabled this feature by mistake, deactivate it on <a href="'.admin_url('admin.php').'?page=ulp-settings&mode=ext">Advanced Settings</a> page.</p></div>';
	}
	
	function after_plugin_row($_plugin_file, $_plugin_data, $_status) {
		echo '
		<tr class="active"><th scope="row" class="check-column" colspan="3" style="padding: 0; font-size: 13px;"><div class="ulp-error-plugins-list ulp-error-animated"><strong>IMPORTANT!</strong> You enabled CLEAN DATABASE feature for Layered Popups. Once Layered Popups deactivted, all tables and records, related to this plugin, will be completely deleted. If you enabled this feature by mistake, deactivate it on <a href="'.admin_url('admin.php').'?page=ulp-settings&mode=ext">Advanced Settings</a> page.</div></td></tr>';
	}
	
	function admin_enqueue_scripts() {
		global $ulp;
		wp_enqueue_script("jquery");
		wp_enqueue_style('ulp', $ulp->plugins_url.'/css/admin.css', array(), ULP_VERSION);
		wp_enqueue_style('ulp-link-buttons', $ulp->plugins_url.'/css/link-buttons.css', array(), ULP_VERSION);
		wp_enqueue_style('ulp-spinners', $ulp->plugins_url.'/css/spinkit.css', array(), ULP_VERSION);
		wp_enqueue_script('ulp', $ulp->plugins_url.'/js/admin.js', array(), ULP_VERSION);
		if (isset($_GET['page']) && in_array($_GET['page'], array('ulp-add', 'ulp-targeting', 'ulp', 'ulp-campaigns', 'ulp-subscribers', 'ulp-settings', 'ulp-add-campaign'))) {
			wp_enqueue_style('jquery-ui', $ulp->plugins_url.'/css/jquery-ui/jquery-ui.min.css', array(), ULP_VERSION);
			wp_enqueue_style('datetimepicker', $ulp->plugins_url.'/css/jquery.datetimepicker.min.css', array(), ULP_VERSION);
			wp_enqueue_script('datetimepicker', $ulp->plugins_url.'/js/jquery.datetimepicker.full.min.js', array(), ULP_VERSION);
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-draggable');
			wp_enqueue_script('jquery-ui-resizable');
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_style('wp-color-picker');
			wp_enqueue_script('wp-color-picker');
			wp_enqueue_style('font-awesome-5.7.2', $ulp->plugins_url.'/css/fontawesome-all.min.css', array(), ULP_VERSION);
			wp_enqueue_media();
		}
	}
	
	function admin_head() {
		global $ulp, $wpdb;
		$rows = $wpdb->get_results("SELECT str_id, title, blocked FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY blocked, id DESC", ARRAY_A);
		echo '<script>var ulp_ajax_handler = "'.admin_url('admin-ajax.php').'";var ulp_post_method = "'.$ulp->options['post_method'].'";var ulp_popups_encoded = "'.base64_encode(json_encode($rows)).'";</script>';
	}
	
	function add_meta() {
		global $ulp;
		if ($ulp->ext_options['admin_only_meta'] == 'on' && !current_user_can('manage_options')) return;
		add_meta_box("ulp", 'Layered Popups Events Settings', array(&$this, 'show_meta'), "post", "normal", "default");
		add_meta_box("ulp", 'Layered Popups Events Settings', array(&$this, 'show_meta'), "page", "normal", "default");
		$post_types = get_post_types(array('public' => true, '_builtin' => false), 'names', 'and'); 
		foreach ($post_types as $post_type ) {
			add_meta_box("ulp", 'Layered Popups Events Settings', array(&$this, 'show_meta'), $post_type, "normal", "default");
		}		
	}
	
	function show_meta($post, $box) {
		global $wpdb, $ulp;
		$meta = $ulp->get_meta($post->ID);
		wp_nonce_field(basename( __FILE__ ), 'ulp_nonce');

		$onscroll_units = 'px';
		if (strpos($meta['onscroll_offset'], '%') !== false) {
			$onscroll_units = '%';
			$meta['onscroll_offset'] = intval($meta['onscroll_offset']);
			if ($meta['onscroll_offset'] > 100) $meta['onscroll_offset'] = 100;
		}
		
		echo '
			<input type="hidden" name="ulp_meta_version" value="'.ULP_VERSION.'">
			<div class="ulp ulp-meta">
				<h3>'.__('OnLoad Settings', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Popup or A/B Campaign', 'ulp').':</th>
						<td style="vertical-align: middle; line-height: 1.6;">
							<strong>'.__('For desktops:', 'ulp').'</strong><br />
							<select id="ulp_onload_popup" name="ulp_onload_popup">';
		$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				echo '
								<option value="'.$popup['str_id'].'"'.($meta['onload_popup'] == $popup['str_id'] ? ' selected="selected"' : '').'>'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				echo '
								<option value="'.$campaign['str_id'].'"'.($meta['onload_popup'] == $campaign['str_id'] ? ' selected="selected"' : '').'>'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		echo '
								<option value=""'.(empty($meta['onload_popup']) ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
								<option value="default"'.($meta['onload_popup'] == 'default' ? ' selected="selected"' : '').'>'.__('Default Popup (taken from Settings page)', 'ulp').'</option>
							<select>
							<br /><strong>'.__('For mobile devices:', 'ulp').'</strong><br />
							<select id="ulp_onload_popup_mobile" name="ulp_onload_popup_mobile">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				echo '
								<option value="'.$popup['str_id'].'"'.($meta['onload_popup_mobile'] == $popup['str_id'] ? ' selected="selected"' : '').'>'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				echo '
								<option value="'.$campaign['str_id'].'"'.($meta['onload_popup_mobile'] == $campaign['str_id'] ? ' selected="selected"' : '').'>'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		echo '
								<option value=""'.(empty($meta['onload_popup_mobile']) ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
								<option value="default"'.($meta['onload_popup_mobile'] == 'default' ? ' selected="selected"' : '').'>'.__('Default Popup (taken from Settings page)', 'ulp').'</option>
								<option value="same"'.($meta['onload_popup_mobile'] == 'same' ? ' selected="selected"' : '').'>'.__('Same as for desktops', 'ulp').'</option>
							<select>
							<br /><em>'.__('Select popup to be displayed on page load.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Display mode', 'ulp').':</th>
						<td style="line-height: 1.8; vertical-align: middle;">';
		foreach ($ulp->display_modes as $key => $value) {
			$value = str_replace('%X', '<input type="text" name="ulp_onload_period" id="ulp_onload_period" class="ic_input_number_short" value="'.$meta['onload_period'].'">', $value);
			echo '
							<input type="radio" name="ulp_onload_mode" id="ulp_onload_mode" value="'.$key.'"'.($meta['onload_mode'] == $key ? ' checked="checked"' : '').'> '.$value.'<br />';
		}
		echo '
							<input type="radio" name="ulp_onload_mode" id="ulp_onload_mode" value="default"'.($meta['onload_mode'] == 'default' ? ' checked="checked"' : '').'> '.__('Default Mode (taken from <a href="admin.php?page=ulp-settings" target="_blank">Settings</a> page)', 'ulp').'<br />
							<em>'.__('Select the popup display mode.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Start delay', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" name="ulp_onload_delay" value="'.esc_html($meta['onload_delay']).'" class="ic_input_number" placeholder="Delay"> '.__('seconds', 'ulp').'
							<br /><em>'.__('Popup appears with this delay after page loaded. Set "0" for immediate start. Value is ignored for "Default" popup.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Autoclose delay', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" name="ulp_onload_close_delay" value="'.esc_html($meta['onload_close_delay']).'" class="ic_input_number" placeholder="Autoclose delay"> '.__('seconds', 'ulp').'
							<br /><em>'.__('Popup is automatically closed after this period of time. Set "0", if you do not need autoclosing. Value is ignored for "Default" popup.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<h3>'.__('OnScroll Settings', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Popup or A/B Campaign', 'ulp').':</th>
						<td style="vertical-align: middle; line-height: 1.6;">
							<strong>'.__('For desktops:', 'ulp').'</strong><br />
							<select id="ulp_onscroll_popup" name="ulp_onscroll_popup">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				echo '
								<option value="'.$popup['str_id'].'"'.($meta['onscroll_popup'] == $popup['str_id'] ? ' selected="selected"' : '').'>'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				echo '
								<option value="'.$campaign['str_id'].'"'.($meta['onscroll_popup'] == $campaign['str_id'] ? ' selected="selected"' : '').'>'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		echo '
								<option value=""'.(empty($meta['onscroll_popup']) ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
								<option value="default"'.($meta['onscroll_popup'] == 'default' ? ' selected="selected"' : '').'>'.__('Default Popup (taken from Settings page)', 'ulp').'</option>
							<select>
							<br /><strong>'.__('For mobile devices:', 'ulp').'</strong><br />
							<select id="ulp_onscroll_popup_mobile" name="ulp_onscroll_popup_mobile">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				echo '
								<option value="'.$popup['str_id'].'"'.($meta['onscroll_popup_mobile'] == $popup['str_id'] ? ' selected="selected"' : '').'>'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				echo '
								<option value="'.$campaign['str_id'].'"'.($meta['onscroll_popup_mobile'] == $campaign['str_id'] ? ' selected="selected"' : '').'>'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		echo '
								<option value=""'.(empty($meta['onscroll_popup_mobile']) ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
								<option value="default"'.($meta['onscroll_popup_mobile'] == 'default' ? ' selected="selected"' : '').'>'.__('Default Popup (taken from Settings page)', 'ulp').'</option>
								<option value="same"'.($meta['onscroll_popup_mobile'] == 'same' ? ' selected="selected"' : '').'>'.__('Same as for desktops', 'ulp').'</option>
							<select>
							<br /><em>'.__('Select the popup to be displayed on scrolling the page.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Display mode', 'ulp').':</th>
						<td style="line-height: 1.8; vertical-align: middle;">';
		foreach ($ulp->display_modes as $key => $value) {
			$value = str_replace('%X', '<input type="text" name="ulp_onscroll_period" id="ulp_onscroll_period" class="ic_input_number_short" value="'.$meta['onscroll_period'].'">', $value);
			echo '
							<input type="radio" name="ulp_onscroll_mode" id="ulp_onscroll_mode" value="'.$key.'"'.($meta['onscroll_mode'] == $key ? ' checked="checked"' : '').'> '.$value.'<br />';
		}
		echo '
							<input type="radio" name="ulp_onscroll_mode" id="ulp_onscroll_mode" value="default"'.($meta['onscroll_mode'] == 'default' ? ' checked="checked"' : '').'> '.__('Default Mode (taken from <a href="admin.php?page=ulp-settings" target="_blank">Settings</a> page)', 'ulp').'<br />
							<em>'.__('Select the popup display mode.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Scrolling offset', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" name="ulp_onscroll_offset" id="ulp_onscroll_offset" value="'.esc_html($meta['onscroll_offset']).'" class="ic_input_number" placeholder="Offset">
							<select id="ulp_onscroll_units" name="ulp_onscroll_units" style="width: 80px; min-width: 80px;" onchange="ulp_onscroll_units_changed();">
								<option value=""'.($onscroll_units != '%' ? ' selected="selected"' : '').'>pixels</option>
								<option value="%"'.($onscroll_units == '%' ? ' selected="selected"' : '').'>%</option>
							</select>
							<br /><em>'.__('Popup appears when user scroll down to this number of pixels or percents.', 'ulp').'</em>
							<script>
								var ulp_onscroll_offset = "";
								function ulp_onscroll_units_changed() {
									if (jQuery("#ulp_onscroll_units").val() == "%") {
										ulp_tmp = jQuery("#ulp_onscroll_offset").val();
										if (ulp_onscroll_offset == "") ulp_onscroll_offset = ulp_tmp;
										if (ulp_onscroll_offset > 100) ulp_onscroll_offset = 100;
										jQuery("#ulp_onscroll_offset").val(ulp_onscroll_offset);
										ulp_onscroll_offset = ulp_tmp;
									} else {
										ulp_tmp = jQuery("#ulp_onscroll_offset").val();
										if (ulp_onscroll_offset != "") jQuery("#ulp_onscroll_offset").val(ulp_onscroll_offset);
										ulp_onscroll_offset = ulp_tmp;
									}
								}
							</script>
						</td>
					</tr>
				</table>
				<h3>'.__('OnExit Settings', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Popup or A/B Campaign', 'ulp').':</th>
						<td style="vertical-align: middle; line-height: 1.6;">
							<strong>'.__('For desktops:', 'ulp').'</strong><br />
							<select id="ulp_onexit_popup" name="ulp_onexit_popup">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				echo '
								<option value="'.$popup['str_id'].'"'.($meta['onexit_popup'] == $popup['str_id'] ? ' selected="selected"' : '').'>'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				echo '
								<option value="'.$campaign['str_id'].'"'.($meta['onexit_popup'] == $campaign['str_id'] ? ' selected="selected"' : '').'>'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		echo '
								<option value=""'.(empty($meta['onexit_popup']) ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
								<option value="default"'.($meta['onexit_popup'] == 'default' ? ' selected="selected"' : '').'>'.__('Default Popup (taken from Settings page)', 'ulp').'</option>
							<select>
							<br /><strong>'.__('For mobile devices:', 'ulp').'</strong><br />
							<select id="ulp_onexit_popup_moblie" name="ulp_onexit_popup_mobile">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				echo '
								<option value="'.$popup['str_id'].'"'.($meta['onexit_popup_mobile'] == $popup['str_id'] ? ' selected="selected"' : '').'>'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				echo '
								<option value="'.$campaign['str_id'].'"'.($meta['onexit_popup_mobile'] == $campaign['str_id'] ? ' selected="selected"' : '').'>'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		echo '
								<option value=""'.(empty($meta['onexit_popup_mobile']) ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
								<option value="default"'.($meta['onexit_popup_mobile'] == 'default' ? ' selected="selected"' : '').'>'.__('Default Popup (taken from Settings page)', 'ulp').'</option>
								<option value="same"'.($meta['onexit_popup_mobile'] == 'same' ? ' selected="selected"' : '').'>'.__('Same as for desktops', 'ulp').'</option>
							<select>
							<br /><em>'.__('Select the popup to be displayed on exit intent.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Display mode', 'ulp').':</th>
						<td style="line-height: 1.8; vertical-align: middle;">';
		foreach ($ulp->display_modes as $key => $value) {
			$value = str_replace('%X', '<input type="text" name="ulp_onexit_period" id="ulp_onexit_period" class="ic_input_number_short" value="'.$meta['onexit_period'].'">', $value);
			echo '
							<input type="radio" name="ulp_onexit_mode" id="ulp_onexit_mode" value="'.$key.'"'.($meta['onexit_mode'] == $key ? ' checked="checked"' : '').'> '.$value.'<br />';
		}
		echo '
							<input type="radio" name="ulp_onexit_mode" id="ulp_onexit_mode" value="default"'.($meta['onexit_mode'] == 'default' ? ' checked="checked"' : '').'> '.__('Default Mode (taken from <a href="admin.php?page=ulp-settings" target="_blank">Settings</a> page)', 'ulp').'<br />
							<em>'.__('Select the popup display mode.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<h3>'.__('OnInactivity Settings', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Popup or A/B Campaign', 'ulp').':</th>
						<td style="vertical-align: middle; line-height: 1.6;">
							<strong>'.__('For desktops:', 'ulp').'</strong><br />
							<select id="ulp_onidle_popup" name="ulp_onidle_popup">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				echo '
								<option value="'.$popup['str_id'].'"'.($meta['onidle_popup'] == $popup['str_id'] ? ' selected="selected"' : '').'>'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				echo '
								<option value="'.$campaign['str_id'].'"'.($meta['onidle_popup'] == $campaign['str_id'] ? ' selected="selected"' : '').'>'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		echo '
								<option value=""'.(empty($meta['onidle_popup']) ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
								<option value="default"'.($meta['onidle_popup'] == 'default' ? ' selected="selected"' : '').'>'.__('Default Popup (taken from Settings page)', 'ulp').'</option>
							<select>
							<br /><strong>'.__('For mobile devices:', 'ulp').'</strong><br />
							<select id="ulp_onidle_popup_mobile" name="ulp_onidle_popup_mobile">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				echo '
								<option value="'.$popup['str_id'].'"'.($meta['onidle_popup_mobile'] == $popup['str_id'] ? ' selected="selected"' : '').'>'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				echo '
								<option value="'.$campaign['str_id'].'"'.($meta['onidle_popup_mobile'] == $campaign['str_id'] ? ' selected="selected"' : '').'>'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		echo '
								<option value=""'.(empty($meta['onidle_popup_mobile']) ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
								<option value="default"'.($meta['onidle_popup_mobile'] == 'default' ? ' selected="selected"' : '').'>'.__('Default Popup (taken from Settings page)', 'ulp').'</option>
								<option value="same"'.($meta['onidle_popup_mobile'] == 'same' ? ' selected="selected"' : '').'>'.__('Same as for desktops', 'ulp').'</option>
							<select>
							<br /><em>'.__('Select the popup to be displayed on user inactivity.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Display mode', 'ulp').':</th>
						<td style="line-height: 1.8; vertical-align: middle;">';
		foreach ($ulp->display_modes as $key => $value) {
			$value = str_replace('%X', '<input type="text" name="ulp_onidle_period" id="ulp_onidle_period" class="ic_input_number_short" value="'.$meta['onidle_period'].'">', $value);
			echo '
							<input type="radio" name="ulp_onidle_mode" id="ulp_onidle_mode" value="'.$key.'"'.($meta['onidle_mode'] == $key ? ' checked="checked"' : '').'> '.$value.'<br />';
		}
		echo '
							<input type="radio" name="ulp_onidle_mode" id="ulp_onidle_mode" value="default"'.($meta['onidle_mode'] == 'default' ? ' checked="checked"' : '').'> '.__('Default Mode (taken from <a href="admin.php?page=ulp-settings" target="_blank">Settings</a> page)', 'ulp').'<br />
							<em>'.__('Select the popup display mode.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Period of inactivity', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" name="ulp_onidle_delay" value="'.esc_html($meta['onidle_delay']).'" class="ic_input_number" placeholder="seconds"> '.__('seconds', 'ulp').'
							<br /><em>'.__('The popup appears after this period of inactivity.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<h3>'.__('OnAdBlockDetected Settings', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Popup or A/B Campaign', 'ulp').':</th>
						<td style="vertical-align: middle; line-height: 1.6;">
							<strong>'.__('For desktops:', 'ulp').'</strong><br />
							<select id="ulp_onabd_popup" name="ulp_onabd_popup">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				echo '
								<option value="'.$popup['str_id'].'"'.($meta['onabd_popup'] == $popup['str_id'] ? ' selected="selected"' : '').'>'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				echo '
								<option value="'.$campaign['str_id'].'"'.($meta['onabd_popup'] == $campaign['str_id'] ? ' selected="selected"' : '').'>'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		echo '
								<option value=""'.(empty($meta['onabd_popup']) ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
								<option value="default"'.($meta['onabd_popup'] == 'default' ? ' selected="selected"' : '').'>'.__('Default Popup (taken from Settings page)', 'ulp').'</option>
							<select>
							<br /><strong>'.__('For mobile devices:', 'ulp').'</strong><br />
							<select id="ulp_onabd_popup_mobile" name="ulp_onabd_popup_mobile">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				echo '
								<option value="'.$popup['str_id'].'"'.($meta['onabd_popup_mobile'] == $popup['str_id'] ? ' selected="selected"' : '').'>'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				echo '
								<option value="'.$campaign['str_id'].'"'.($meta['onabd_popup_mobile'] == $campaign['str_id'] ? ' selected="selected"' : '').'>'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		echo '
								<option value=""'.(empty($meta['onabd_popup_mobile']) ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
								<option value="default"'.($meta['onabd_popup_mobile'] == 'default' ? ' selected="selected"' : '').'>'.__('Default Popup (taken from Settings page)', 'ulp').'</option>
								<option value="same"'.($meta['onabd_popup_mobile'] == 'same' ? ' selected="selected"' : '').'>'.__('Same as for desktops', 'ulp').'</option>
							<select>
							<br /><em>'.__('Select the popup to be displayed AdBlock detected.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Display mode', 'ulp').':</th>
						<td style="line-height: 1.8; vertical-align: middle;">';
		foreach ($ulp->display_modes as $key => $value) {
			$value = str_replace('%X', '<input type="text" name="ulp_onabd_period" id="ulp_onabd_period" class="ic_input_number_short" value="'.$meta['onabd_period'].'">', $value);
			echo '
							<input type="radio" name="ulp_onabd_mode" id="ulp_onabd_mode" value="'.$key.'"'.($meta['onabd_mode'] == $key ? ' checked="checked"' : '').'> '.$value.'<br />';
		}
		echo '
							<input type="radio" name="ulp_onabd_mode" id="ulp_onabd_mode" value="default"'.($meta['onabd_mode'] == 'default' ? ' checked="checked"' : '').'> '.__('Default Mode (taken from <a href="admin.php?page=ulp-settings" target="_blank">Settings</a> page)', 'ulp').'<br />
							<em>'.__('Select the popup display mode.', 'ulp').'</em>
						</td>
					</tr>
				</table>';
		do_action('ulp_show_meta', $post, $box);
		echo '
			</div>';
	}

	function save_meta($post_id, $post) {
		global $ulp;
		if (!isset( $_POST['ulp_nonce'] ) || !wp_verify_nonce($_POST['ulp_nonce'], basename( __FILE__ ))) {
			return $post_id;
		}
		
		$post_type = get_post_type_object($post->post_type);
		if (!current_user_can($post_type->cap->edit_post, $post_id)) {
			return $post_id;
		}
		if (isset($_POST['ulp_meta_version'])) {
			$meta = array();
			foreach($ulp->default_meta as $key => $value) {
				if (isset($_POST['ulp_'.$key])) $value = trim(stripslashes($_POST['ulp_'.$key]));
				if ($key == 'onload_period' || $key == 'onscroll_period' || $key == 'onexit_period' || $key == 'onidle_period' || $key == 'onidle_delay' || $key == 'onabd_period') {
					if (strlen($value) == 0 || $value != preg_replace('/[^0-9]/', '', $value) || intval($value) < 1) $value = $ulp->options[$key];
				} else if ($key == 'onload_delay' || $key == 'onload_close_delay' || $key == 'onscroll_offset') {
					if (strlen($value) > 0 && $value != preg_replace('/[^0-9]/', '', $value)) $value = $ulp->options[$key];
				} 
				if ($key == 'onscroll_offset') {
					if ($_POST["ulp_onscroll_units"] == '%') {
						if ($value > 100) $value = '100';
						$value .= '%';
					}
				}
				update_post_meta($post_id, 'ulp_'.$key, $value);
			}
		}
		do_action('ulp_save_meta', $post_id, $post);
	}

	function register_tinymce_plugin($plugin_array) {
		global $ulp;
		$plugin_array['ulp_button'] = $ulp->plugins_url.'/js/tinymce-button.js';
		return $plugin_array;
	}

	function add_tinymce_button($buttons) {
		$buttons[] = "ulp_button";
		return $buttons;
	}
	
	function admin_menu() {
		global $ulp;
		add_menu_page(
			"Layered Popups"
			, "Layered Popups"
			, "manage_options"
			, "ulp"
			, array(&$this, 'admin_popups')
			, 'none'
			, 57
		);
		add_submenu_page(
			"ulp"
			, __('Popups', 'ulp')
			, __('Popups', 'ulp')
			, "manage_options"
			, "ulp"
			, array(&$this, 'admin_popups')
		);
		add_submenu_page(
			"ulp"
			, __('Create Popup', 'ulp')
			, __('Create Popup', 'ulp')
			, "manage_options"
			, "ulp-add"
			, array(&$this, 'admin_add_popup')
		);
		add_submenu_page(
			"ulp"
			, __('A/B Campaigns', 'ulp')
			, __('A/B Campaigns', 'ulp')
			, "manage_options"
			, "ulp-campaigns"
			, array(&$this, 'admin_campaigns')
		);
		add_submenu_page(
			"ulp"
			, __('Create Campaign', 'ulp')
			, __('Create Campaign', 'ulp')
			, "manage_options"
			, "ulp-add-campaign"
			, array(&$this, 'admin_add_campaign')
		);
		if (!defined('UAP_CORE') && $ulp->ext_options['advanced_targeting'] == 'on') {
			add_submenu_page(
				"ulp"
				, __('Targeting', 'ulp')
				, __('Targeting', 'ulp')
				, "manage_options"
				, "ulp-targeting"
				, array(&$this, 'admin_targeting')
			);
		}
		if ($ulp->ext_options['log_data'] == "on") {
			add_submenu_page(
				"ulp"
				, __('Log', 'ulp')
				, __('Log', 'ulp')
				, "manage_options"
				, "ulp-subscribers"
				, array(&$this, 'admin_subscribers')
			);
		}
		do_action('ulp_admin_menu');
		add_submenu_page(
			"ulp"
			, __('Settings', 'ulp')
			, __('Settings', 'ulp')
			, "manage_options"
			, "ulp-settings"
			, array(&$this, 'admin_settings')
		);
		if (!defined('UAP_CORE')) {
			add_submenu_page(
				"ulp"
				, __('FAQ', 'ulp')
				, __('FAQ', 'ulp')
				, "manage_options"
				, "ulp-faq"
				, array(&$this, 'admin_faq')
			);
		} else {
			add_submenu_page(
				"ulp"
				, __('How To Use', 'ulp')
				, __('How To Use', 'ulp')
				, "manage_options"
				, "ulp-using"
				, array(&$this, 'admin_using')
			);
		}
	}

	function admin_faq() {
		global $wpdb;
		echo '
		<div class="wrap ulp">
			<h2>Layered Popups - FAQ <a href="https://layeredpopups.com/documentation/" class="add-new-h2" target="_blank">Online Documentation</a></h2>
			<div class="ulp-options" style="width: 100%; position: relative;">';
		include_once(dirname(dirname(__FILE__)).'/faq.php');
		do_action('ulp_faq');
		echo '
				<h3>'.__('Credits', 'ulp').'</h3>
				<ol>
					<li><a href="http://p.yusukekamiyamane.com/" target="_blank">Fugue Icons</a> [icons]</li>
					<li><a href="http://www.google.com/fonts/specimen/Open+Sans" target="_blank">Open Sans</a> [font]</li>
					<li><a href="http://www.google.com/fonts/specimen/Walter+Turncoat" target="_blank">Walter Turncoat</a> [font]</li>
					<li><a href="http://www.flickr.com/photos/duncanh1/8506986371/in/photolist-dXJwEP-7ZogK1-8bHpxi-eoL5K2-dU8WLK-7Zk6DD-dyBCL2-dyH6vN-87oTAm-dVq9ex-bax8Fe-a3sk3a-dyBCG8-dyBCye-dxoaup-aFxFtK-a25d6s-cA1TLd-fEy7Vh-a25t97-a3sk3i-a25t9d-bt324c-9eWYyv-e9v5L6-9ZYJCb-7YgSdJ-aow783-dV8L1k-9dB9zs-8A5WTw-9ZvMxn-b9HKsk-bp15Kf-ecHEZB-bPkHhK-8Ebh3A-a1S7W5-e3vpbv-9Zz3hW-a7uaQT-egTcNK-a1S7Wh-7PsHJT-fEuMRY-fq7Cz9-aEQRuu-cz4kYU-8WrG2Q-dxtAQA-brkWsD/" target="_blank">The City from the Shard</a> [image]</li>
					<li><a href="http://www.fasticon.com" target="_blank">Fast Icon</a> [icons]</li>
					<li><a href="http://www.wallsave.com" target="_blank">Wallpapers Business Graph</a> [image]</li>
					<li><a href="http://daneden.github.io/animate.css/" target="_blank">Animate.css</a> [stylesheet]</li>
				</ol>
			</div>
		</div>';
	}

	function admin_targeting() {
		global $wpdb;
		include_once(dirname(__FILE__).'/core-targeting.php');
		$targeting = new ulp_class_targeting();
		$targeting->admin_page();
	}
	
	function admin_using() {
		global $wpdb, $ulp;
		$remote_snippet = '<script id="ulp-remote" src="'.$ulp->plugins_url.'/js/remote'.($ulp->ext_options['minified_sources'] == "on" ? '.min' : '').'.js?ver='.ULP_VERSION.'" data-handler="'.admin_url('admin-ajax.php').'"></script>';
		echo '
		<div class="wrap ulp">
			<h2>Layered Popups - How To Use <a href="https://layeredpopups.com/documentation/" class="add-new-h2" target="_blank">Online Documentation</a></h2>
			<div class="ulp-options" style="width: 100%; position: relative;">';
		include_once(dirname(dirname(__FILE__)).'/remote.php');
		do_action('ulp_using');
		echo '
			</div>
		</div>';
	}
	
	function admin_settings() {
		global $wpdb, $ulp;

		if (isset($_GET['mode']) && $_GET['mode'] == 'ext') {
			$this->admin_ext_settings();
			return;
		}
		
		if (!empty($ulp->error)) $message = "<div class='error'><p>".$ulp->error."</p></div>";
		else if (!empty($ulp->info)) $message = "<div class='updated'><p>".$ulp->info."</p></div>";
		else $message = '';

		$ulp->options['onload_popup'] = $ulp->wpml_parse_popup_id($ulp->options['onload_popup']);
		$ulp->options['onload_popup_mobile'] = $ulp->wpml_parse_popup_id($ulp->options['onload_popup_mobile'], 'same');
		$ulp->options['onexit_popup'] = $ulp->wpml_parse_popup_id($ulp->options['onexit_popup']);
		$ulp->options['onexit_popup_mobile'] = $ulp->wpml_parse_popup_id($ulp->options['onexit_popup_mobile'], 'same');
		$ulp->options['onscroll_popup'] = $ulp->wpml_parse_popup_id($ulp->options['onscroll_popup']);
		$ulp->options['onscroll_popup_mobile'] = $ulp->wpml_parse_popup_id($ulp->options['onscroll_popup_mobile'], 'same');
		$ulp->options['onidle_popup'] = $ulp->wpml_parse_popup_id($ulp->options['onidle_popup']);
		$ulp->options['onidle_popup_mobile'] = $ulp->wpml_parse_popup_id($ulp->options['onidle_popup_mobile'], 'same');
		$ulp->options['onabd_popup'] = $ulp->wpml_parse_popup_id($ulp->options['onabd_popup']);
		$ulp->options['onabd_popup_mobile'] = $ulp->wpml_parse_popup_id($ulp->options['onabd_popup_mobile'], 'same');
		
		$onscroll_units = 'px';
		if (strpos($ulp->options['onscroll_offset'], '%') !== false) {
			$onscroll_units = '%';
			$ulp->options['onscroll_offset'] = intval($ulp->options['onscroll_offset']);
			if ($ulp->options['onscroll_offset'] > 100) $ulp->options['onscroll_offset'] = 100;
		}
		
		echo '
		<div class="wrap ulp">
			<h2>'.__('Layered Popups - Settings', 'ulp').' <a href="https://layeredpopups.com/documentation/" class="add-new-h2" target="_blank">Online Documentation</a></h2>
			'.$message.'
			<h2 class="ulp-tabs">
				<a class="ulp-tab ulp-tab-active" href="'.admin_url('admin.php').'?page=ulp-settings">'.__('General', 'ulp').'</a>
				<a class="ulp-tab" href="'.admin_url('admin.php').'?page=ulp-settings&mode=ext">'.__('Advanced', 'ulp').'</a>
			</h2>
			<form class="ulp-popup-form" enctype="multipart/form-data" method="post" style="margin: 0px" action="'.admin_url('admin.php').'">
			<div class="ulp-options" style="width: 100%; position: relative;">';
		if (!defined('UAP_CORE') && $ulp->ext_options['advanced_targeting'] != 'on') {
			echo '
				<h3>'.__('OnLoad Settings', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Popup or A/B Campaign', 'ulp').':</th>
						<td style="vertical-align: middle; line-height: 1.6;">
							<strong>'.__('For desktops:', 'ulp').'</strong><br />
							<select id="ulp_onload_popup" name="ulp_onload_popup">';
		$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		$checked = false;
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				if ($ulp->options['onload_popup'] == $popup['str_id']) {
					$checked = true;
					echo '
								<option value="'.$popup['str_id'].'" selected="selected">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$popup['str_id'].'">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				if ($ulp->options['onload_popup'] == $campaign['str_id']) {
					$checked = true;
					echo '
								<option value="'.$campaign['str_id'].'" selected="selected">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$campaign['str_id'].'">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		echo '
								<option value=""'.(!$checked ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
							</select>
							<br /><strong>'.__('For mobile devices:', 'ulp').'</strong><br />
							<select id="ulp_onload_popup_mobile" name="ulp_onload_popup_mobile">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		$checked = false;
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				if ($ulp->options['onload_popup_mobile'] == $popup['str_id']) {
					$checked = true;
					echo '
								<option value="'.$popup['str_id'].'" selected="selected">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$popup['str_id'].'">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				if ($ulp->options['onload_popup_mobile'] == $campaign['str_id']) {
					$checked = true;
					echo '
								<option value="'.$campaign['str_id'].'" selected="selected">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$campaign['str_id'].'">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		if ($ulp->options['onload_popup_mobile'] == 'same') {
			$checked = true;
			echo '
								<option value="same" selected="selected">'.__('Same as for desktops', 'ulp').'</option>';
		} else {
			echo '
								<option value="same">'.__('Same as for desktops', 'ulp').'</option>';
		}
		echo '
								<option value=""'.(!$checked ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
							</select>
							<br /><em>'.__('Select popup or A/B campaign to be displayed on page load.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Display mode', 'ulp').':</th>
						<td style="line-height: 1.8; vertical-align: middle;">';
		foreach ($ulp->display_modes as $key => $value) {
			$value = str_replace('%X', '<input type="text" name="ulp_onload_period" id="ulp_onload_period" class="ic_input_number_short" value="'.$ulp->options['onload_period'].'">', $value);
			echo '
							<input type="radio" name="ulp_onload_mode" id="ulp_onload_mode" value="'.$key.'"'.($ulp->options['onload_mode'] == $key ? ' checked="checked"' : '').'> '.$value.'<br />';
		}
		echo '
							<em>'.__('Select the popup display mode.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Start delay', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" name="ulp_onload_delay" value="'.esc_html($ulp->options['onload_delay']).'" class="ic_input_number" placeholder="Delay"> '.__('seconds', 'ulp').'
							<br /><em>'.__('Popup appears with this delay after page loaded. Set "0" for immediate start.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Autoclose delay', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" name="ulp_onload_close_delay" value="'.esc_html($ulp->options['onload_close_delay']).'" class="ic_input_number" placeholder="Autoclose delay"> '.__('seconds', 'ulp').'
							<br /><em>'.__('Popup is automatically closed after this period of time. Set "0", if you do not need autoclosing.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<h3>'.__('OnScroll Settings', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Popup or A/B Campaign', 'ulp').':</th>
						<td style="vertical-align: middle; line-height: 1.6;">
							<strong>'.__('For desktops:', 'ulp').'</strong><br />
							<select id="ulp_onscroll_popup" name="ulp_onscroll_popup">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		$checked = false;
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				if ($ulp->options['onscroll_popup'] == $popup['str_id']) {
					$checked = true;
					echo '
								<option value="'.$popup['str_id'].'" selected="selected">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$popup['str_id'].'">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				if ($ulp->options['onscroll_popup'] == $campaign['str_id']) {
					$checked = true;
					echo '
								<option value="'.$campaign['str_id'].'" selected="selected">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$campaign['str_id'].'">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		echo '
								<option value=""'.(!$checked ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
							</select>
							<br /><strong>'.__('For mobile devices:', 'ulp').'</strong><br />
							<select id="ulp_onscroll_popup_mobile" name="ulp_onscroll_popup_mobile">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		$checked = false;
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				if ($ulp->options['onscroll_popup_mobile'] == $popup['str_id']) {
					$checked = true;
					echo '
								<option value="'.$popup['str_id'].'" selected="selected">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$popup['str_id'].'">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				if ($ulp->options['onscroll_popup_mobile'] == $campaign['str_id']) {
					$checked = true;
					echo '
								<option value="'.$campaign['str_id'].'" selected="selected">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$campaign['str_id'].'">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		if ($ulp->options['onscroll_popup_mobile'] == 'same') {
			$checked = true;
			echo '
								<option value="same" selected="selected">'.__('Same as for desktops', 'ulp').'</option>';
		} else {
			echo '
								<option value="same">'.__('Same as for desktops', 'ulp').'</option>';
		}
		echo '
								<option value=""'.(!$checked ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
							</select>
							<br /><em>'.__('Select popup or A/B campaign to be displayed on scrolling down.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Display mode', 'ulp').':</th>
						<td style="line-height: 1.8; vertical-align: middle;">';
		foreach ($ulp->display_modes as $key => $value) {
			$value = str_replace('%X', '<input type="text" name="ulp_onscroll_period" id="ulp_onscroll_period" class="ic_input_number_short" value="'.$ulp->options['onscroll_period'].'">', $value);
			echo '
							<input type="radio" name="ulp_onscroll_mode" id="ulp_onscroll_mode" value="'.$key.'"'.($ulp->options['onscroll_mode'] == $key ? ' checked="checked"' : '').'> '.$value.'<br />';
		}
		echo '
							<em>'.__('Select the popup display mode.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Scrolling offset', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" name="ulp_onscroll_offset" id="ulp_onscroll_offset" value="'.esc_html($ulp->options['onscroll_offset']).'" class="ic_input_number" placeholder="Offset">
							<select id="ulp_onscroll_units" name="ulp_onscroll_units" style="width: 80px; min-width: 80px;" onchange="ulp_onscroll_units_changed();">
								<option value=""'.($onscroll_units != '%' ? ' selected="selected"' : '').'>pixels</option>
								<option value="%"'.($onscroll_units == '%' ? ' selected="selected"' : '').'>%</option>
							</select>
							<br /><em>'.__('Popup appears when user scroll down to this number of pixels or percents.', 'ulp').'</em>
							<script>
								var ulp_onscroll_offset = "";
								function ulp_onscroll_units_changed() {
									if (jQuery("#ulp_onscroll_units").val() == "%") {
										ulp_tmp = jQuery("#ulp_onscroll_offset").val();
										if (ulp_onscroll_offset == "") ulp_onscroll_offset = ulp_tmp;
										if (ulp_onscroll_offset > 100) ulp_onscroll_offset = 100;
										jQuery("#ulp_onscroll_offset").val(ulp_onscroll_offset);
										ulp_onscroll_offset = ulp_tmp;
									} else {
										ulp_tmp = jQuery("#ulp_onscroll_offset").val();
										if (ulp_onscroll_offset != "") jQuery("#ulp_onscroll_offset").val(ulp_onscroll_offset);
										ulp_onscroll_offset = ulp_tmp;
									}
								}
							</script>
						</td>
					</tr>
				</table>
				<h3>'.__('OnExit Settings', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Popup or A/B Campaign', 'ulp').':</th>
						<td style="vertical-align: middle; line-height: 1.6;">
							<strong>'.__('For desktops:', 'ulp').'</strong><br />
							<select id="ulp_onexit_popup" name="ulp_onexit_popup">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		$checked = false;
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				if ($ulp->options['onexit_popup'] == $popup['str_id']) {
					$checked = true;
					echo '
								<option value="'.$popup['str_id'].'" selected="selected">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$popup['str_id'].'">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				if ($ulp->options['onexit_popup'] == $campaign['str_id']) {
					$checked = true;
					echo '
								<option value="'.$campaign['str_id'].'" selected="selected">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$campaign['str_id'].'">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		echo '
								<option value=""'.(!$checked ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
							</select>
							<br /><strong>'.__('For mobile devices:', 'ulp').'</strong><br />
							<select id="ulp_onexit_popup_mobile" name="ulp_onexit_popup_mobile">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		$checked = false;
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				if ($ulp->options['onexit_popup_mobile'] == $popup['str_id']) {
					$checked = true;
					echo '
								<option value="'.$popup['str_id'].'" selected="selected">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$popup['str_id'].'">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				if ($ulp->options['onexit_popup_mobile'] == $campaign['str_id']) {
					$checked = true;
					echo '
								<option value="'.$campaign['str_id'].'" selected="selected">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$campaign['str_id'].'">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		if ($ulp->options['onexit_popup_mobile'] == 'same') {
			$checked = true;
			echo '
								<option value="same" selected="selected">'.__('Same as for desktops', 'ulp').'</option>';
		} else {
			echo '
								<option value="same">'.__('Same as for desktops', 'ulp').'</option>';
		}
		echo '
								<option value=""'.(!$checked ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
							</select>
							<br /><em>'.__('Select popup or A/B campaign to be displayed on exit intent.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Display mode', 'ulp').':</th>
						<td style="line-height: 1.8; vertical-align: middle;">';
		foreach ($ulp->display_modes as $key => $value) {
			$value = str_replace('%X', '<input type="text" name="ulp_onexit_period" id="ulp_onexit_period" class="ic_input_number_short" value="'.$ulp->options['onexit_period'].'">', $value);
			echo '
							<input type="radio" name="ulp_onexit_mode" id="ulp_onexit_mode" value="'.$key.'"'.($ulp->options['onexit_mode'] == $key ? ' checked="checked"' : '').'> '.$value.'<br />';
		}
		echo '
							<em>'.__('Select the popup display mode.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<h3>'.__('OnInactivity Settings', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Popup or A/B Campaign', 'ulp').':</th>
						<td style="vertical-align: middle; line-height: 1.6;">
							<strong>'.__('For desktops:', 'ulp').'</strong><br />
							<select id="ulp_onidle_popup" name="ulp_onidle_popup">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		$checked = false;
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				if ($ulp->options['onidle_popup'] == $popup['str_id']) {
					$checked = true;
					echo '
								<option value="'.$popup['str_id'].'" selected="selected">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$popup['str_id'].'">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				if ($ulp->options['onidle_popup'] == $campaign['str_id']) {
					$checked = true;
					echo '
								<option value="'.$campaign['str_id'].'" selected="selected">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$campaign['str_id'].'">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		echo '
								<option value=""'.(!$checked ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
							</select>
							<br /><strong>'.__('For mobile devices:', 'ulp').'</strong><br />
							<select id="ulp_onidle_popup_mobile" name="ulp_onidle_popup_mobile">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		$checked = false;
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				if ($ulp->options['onidle_popup_mobile'] == $popup['str_id']) {
					$checked = true;
					echo '
								<option value="'.$popup['str_id'].'" selected="selected">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$popup['str_id'].'">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				if ($ulp->options['onidle_popup_mobile'] == $campaign['str_id']) {
					$checked = true;
					echo '
								<option value="'.$campaign['str_id'].'" selected="selected">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$campaign['str_id'].'">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		if ($ulp->options['onidle_popup_mobile'] == 'same') {
			$checked = true;
			echo '
								<option value="same" selected="selected">'.__('Same as for desktops', 'ulp').'</option>';
		} else {
			echo '
								<option value="same">'.__('Same as for desktops', 'ulp').'</option>';
		}
		echo '
								<option value=""'.(!$checked ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
							</select>
							<br /><em>'.__('Select popup or A/B campaign to be displayed on user inactivity.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Display mode', 'ulp').':</th>
						<td style="line-height: 1.8; vertical-align: middle;">';
		foreach ($ulp->display_modes as $key => $value) {
			$value = str_replace('%X', '<input type="text" name="ulp_onidle_period" id="ulp_onidle_period" class="ic_input_number_short" value="'.$ulp->options['onidle_period'].'">', $value);
			echo '
							<input type="radio" name="ulp_onidle_mode" id="ulp_onidle_mode" value="'.$key.'"'.($ulp->options['onidle_mode'] == $key ? ' checked="checked"' : '').'> '.$value.'<br />';
		}
		echo '
							<em>'.__('Select the popup display mode.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Period of inactivity', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" name="ulp_onidle_delay" value="'.esc_html($ulp->options['onidle_delay']).'" class="ic_input_number" placeholder="seconds"> '.__('seconds', 'ulp').'
							<br /><em>'.__('The popup appears after this period of inactivity.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<h3>'.__('OnAdBlockDetected Settings', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Popup or A/B Campaign', 'ulp').':</th>
						<td style="vertical-align: middle; line-height: 1.6;">
							<strong>'.__('For desktops:', 'ulp').'</strong><br />
							<select id="ulp_onabd_popup" name="ulp_onabd_popup">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		$checked = false;
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				if ($ulp->options['onabd_popup'] == $popup['str_id']) {
					$checked = true;
					echo '
								<option value="'.$popup['str_id'].'" selected="selected">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$popup['str_id'].'">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				if ($ulp->options['onabd_popup'] == $campaign['str_id']) {
					$checked = true;
					echo '
								<option value="'.$campaign['str_id'].'" selected="selected">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$campaign['str_id'].'">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		echo '
								<option value=""'.(!$checked ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
							</select>
							<br /><strong>'.__('For mobile devices:', 'ulp').'</strong><br />
							<select id="ulp_onabd_popup_mobile" name="ulp_onabd_popup_mobile">';
		//$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
		$checked = false;
		if (sizeof($popups) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('Popups', 'ulp').' ---------</option>';
			foreach($popups as $popup) {
				if ($ulp->options['onabd_popup_mobile'] == $popup['str_id']) {
					$checked = true;
					echo '
								<option value="'.$popup['str_id'].'" selected="selected">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$popup['str_id'].'">'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		//$campaigns = $wpdb->get_results("SELECT t1.*, t2.popups FROM ".$wpdb->prefix."ulp_campaigns t1 JOIN (SELECT COUNT(*) AS popups, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0' AND t2.popups > 0 ORDER BY t1.title ASC", ARRAY_A);
		if (sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">--------- '.__('A/B Campaigns', 'ulp').' ---------</option>';
			foreach($campaigns as $campaign) {
				if ($ulp->options['onabd_popup_mobile'] == $campaign['str_id']) {
					$checked = true;
					echo '
								<option value="'.$campaign['str_id'].'" selected="selected">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
								<option value="'.$campaign['str_id'].'">'.esc_html($campaign['title']).($campaign['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
		}
		if (sizeof($popups) > 0 || sizeof($campaigns) > 0) {
			echo '
								<option disabled="disabled">------------------</option>';
		}
		if ($ulp->options['onabd_popup_mobile'] == 'same') {
			$checked = true;
			echo '
								<option value="same" selected="selected">'.__('Same as for desktops', 'ulp').'</option>';
		} else {
			echo '
								<option value="same">'.__('Same as for desktops', 'ulp').'</option>';
		}
		echo '
								<option value=""'.(!$checked ? ' selected="selected"' : '').'>'.__('None (disabled)', 'ulp').'</option>
							</select>
							<br /><em>'.__('Select popup or A/B campaign to be displayed if AdBlock detected.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Display mode', 'ulp').':</th>
						<td style="line-height: 1.8; vertical-align: middle;">';
		foreach ($ulp->display_modes as $key => $value) {
			$value = str_replace('%X', '<input type="text" name="ulp_onabd_period" id="ulp_onabd_period" class="ic_input_number_short" value="'.$ulp->options['onabd_period'].'">', $value);
			echo '
							<input type="radio" name="ulp_onabd_mode" id="ulp_onabd_mode" value="'.$key.'"'.($ulp->options['onabd_mode'] == $key ? ' checked="checked"' : '').'> '.$value.'<br />';
		}
		echo '
							<em>'.__('Select the popup display mode.', 'ulp').'</em>
						</td>
					</tr>
				</table>';
		}
		//if (apply_filters('ulp_use_mailing', false)) {
			echo '
				<h3>'.__('Mailing Settings', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('E-mail type', 'ulp').':</th>
						<td>
							<select id="ulp_from_type" name="ulp_from_type">
								<option value="html"'.($ulp->options['from_type'] == 'html' ? ' selected="selected"' : '').'>'.__('HTML', 'ulp').'</option>
								<option value="text"'.($ulp->options['from_type'] == 'text' ? ' selected="selected"' : '').'>'.__('Text', 'ulp').'</option>
							</select>
							<br /><em>'.__('Please select email type.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Sender name', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_from_name" name="ulp_from_name" value="'.esc_html($ulp->options['from_name']).'" class="widefat">
							<br /><em>'.__('Please enter sender name. All messages from plugin are sent using this name as "FROM:" header value.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Sender e-mail', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_from_email" name="ulp_from_email" value="'.esc_html($ulp->options['from_email']).'" class="widefat">
							<br /><em>'.__('Please enter sender e-mail. All messages from plugin are sent using this e-mail as "FROM:" header value.', 'ulp').'</em>
						</td>
					</tr>
				</table>';
		//}
		echo '
				<h3>'.__('Miscellaneous', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Single subscription', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_onexit_limits" name="ulp_onexit_limits" '.($ulp->options['onexit_limits'] == "on" ? 'checked="checked"' : '').'> '.__('Disable all event popups if user subscribed through any popup or inline form', 'ulp').'
							<br /><em>'.__('Disable event popups (OnLoad, OnExit, OnScroll, OnInactivity), if user subscribed through any popup or inline form.', 'ulp').'</em>
						</td>
					</tr>';
		if (!defined('UAP_CORE')) {
			echo '
					<tr>
						<th>'.__('Pre-load popups', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_no_preload" name="ulp_no_preload" '.($ulp->options['no_preload'] == "on" ? 'checked="checked"' : '').' onclick="ulp_toggle_loader_settings();"> '.__('Do not pre-load popups', 'ulp').'
							<br /><em>'.__('Tick checkbox to disable popups pre-load. Popup will be pulled on demand using AJAX.', 'ulp').'</em>
						</td>
					</tr>
					<tr class="ulp-row-loader-settings" style="display: none;">
						<th></th>
						<td>
							<input type="checkbox" id="ulp_preload_event_popups" name="ulp_preload_event_popups" '.($ulp->options['preload_event_popups'] == "on" ? 'checked="checked"' : '').'> '.__('Pre-load event popups', 'ulp').'
							<br /><em>'.__('If you turn this option on, only event popups (OnLoad, OnExit, OnScroll, OnInactivity) will be loaded together with website. All other popups will be pulled on demand using AJAX.', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th>'.__('CSV column separator', 'ulp').':</th>
						<td>
							<select id="ulp_csv_separator" name="ulp_csv_separator">
								<option value=";"'.($ulp->options['csv_separator'] == ';' ? ' selected="selected"' : '').'>'.__('Semicolon - ";"', 'ulp').'</option>
								<option value=","'.($ulp->options['csv_separator'] == ',' ? ' selected="selected"' : '').'>'.__('Comma - ","', 'ulp').'</option>
								<option value="tab"'.($ulp->options['csv_separator'] == 'tab' ? ' selected="selected"' : '').'>'.__('Tab', 'ulp').'</option>
							</select>
							<br /><em>'.__('Please select CSV column separator.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Extended e-mail validation', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_email_validation" name="ulp_email_validation" '.($ulp->options['email_validation'] == "on" ? 'checked="checked"' : '').'> '.__('Check MX records', 'ulp').'
							<br /><em>'.__('Check MX records according to the host provided within the email address. PHP 5 >= 5.3 required!', 'ulp').'</em>
						</td>
					</tr>';
		do_action('ulp_options_email_verification_show');
		echo '
					<script>ulp_toggle_verifier();</script>
					<tr>
						<th>'.__('Google Analytics tracking', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_ga_tracking" name="ulp_ga_tracking" '.($ulp->options['ga_tracking'] == "on" ? 'checked="checked"' : '').'> '.__('Enable Google Analytics tracking', 'ulp').'
							<br /><em>'.__('Send popup events to Google Analytics. Google Analytics must be installed on your website.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('KISSmetrics tracking', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_km_tracking" name="ulp_km_tracking" '.($ulp->options['km_tracking'] == "on" ? 'checked="checked"' : '').'> '.__('Enable KISSmetrics tracking', 'ulp').'
							<br /><em>'.__('Identify the current person with a e-mail address submitted through opt-in form. KISSmetrics must be installed on your website.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Font Awesome icons', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_fa_enable" name="ulp_fa_enable" '.($ulp->options['fa_enable'] == "on" ? 'checked="checked"' : '').' onclick="if (jQuery(this).is(\':checked\')) {jQuery(\'.ulp-fa-details\').fadeIn(300);} else {jQuery(\'.ulp-fa-details\').fadeOut(300);}" /> '.__('Enable Font Awesome 5 icons (free pack)', 'ulp').'
							<br /><em>'.__('Enable Font Awesome icons.', 'ulp').'</em>
						</td>
					</tr>
					<tr class="ulp-fa-details"'.($ulp->options['fa_enable'] != 'on' ? ' style="display:none;"' : '').'>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_fa_solid_enable" name="ulp_fa_solid_enable" '.($ulp->options['fa_solid_enable'] == "on" ? 'checked="checked"' : '').'> '.__('Enable Solid Icons', 'ulp').'
							<br /><em>'.__('Enable Font Awesome Solid Icons, you can turn it off you do not need Solid Icons pack. More details <a href="https://fontawesome.com/cheatsheet" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr class="ulp-fa-details"'.($ulp->options['fa_enable'] != 'on' ? ' style="display:none;"' : '').'>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_fa_regular_enable" name="ulp_fa_regular_enable" '.($ulp->options['fa_regular_enable'] == "on" ? 'checked="checked"' : '').'> '.__('Enable Regular Icons', 'ulp').'
							<br /><em>'.__('Enable Font Awesome Regular Icons, you can turn it off you do not need Regular Icons pack. More details <a href="https://fontawesome.com/cheatsheet" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr class="ulp-fa-details"'.($ulp->options['fa_enable'] != 'on' ? ' style="display:none;"' : '').'>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_fa_brands_enable" name="ulp_fa_brands_enable" '.($ulp->options['fa_brands_enable'] == "on" ? 'checked="checked"' : '').'> '.__('Enable Brand Icons', 'ulp').'
							<br /><em>'.__('Enable Font Awesome Brand Icons, you can turn it off you do not need Brand Icons pack. More details <a href="https://fontawesome.com/cheatsheet" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr class="ulp-fa-details"'.($ulp->options['fa_enable'] != 'on' ? ' style="display:none;"' : '').'>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_fa_css_disable" name="ulp_fa_css_disable" '.($ulp->options['fa_css_disable'] == "on" ? 'checked="checked"' : '').'> '.__('Disable Font Awesome CSS loading', 'ulp').'
							<br /><em>'.__('If your theme or another plugin load Font Awesome, you can turn it off to avoid conflicts.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('jQuery Mask plugin', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mask_enable" name="ulp_mask_enable" '.($ulp->options['mask_enable'] == "on" ? 'checked="checked"' : '').'> '.__('Enable jQuery Mask plugin', 'ulp').'
							<br /><em>'.__('Enable jQuery Mask plugin to make masks on form fields.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_mask_js_disable" name="ulp_mask_js_disable" '.($ulp->options['mask_js_disable'] == "on" ? 'checked="checked"' : '').'> '.__('Disable jQuery Mask JS loading', 'ulp').'
							<br /><em>'.__('If your theme or another plugin load jQuery Mask, you can turn it off to avoid conflicts.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('SpinKit', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_spinkit_enable" name="ulp_spinkit_enable" '.($ulp->options['spinkit_enable'] == "on" ? 'checked="checked"' : '').'> '.__('Enable SpinKit', 'ulp').'
							<br /><em>'.__('Activate this feature to set nice CSS3 loading spinners.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Linked Buttons', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_linkedbuttons_enable" name="ulp_linkedbuttons_enable" '.($ulp->options['linkedbuttons_enable'] == "on" ? 'checked="checked"' : '').'> '.__('Enable Linked Buttons', 'ulp').'
							<br /><em>'.__('Activate this feature if youplan to use linked buttons.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('CSS3 animation', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_css3_enable" name="ulp_css3_enable" '.($ulp->options['css3_enable'] == "on" ? 'checked="checked"' : '').'> '.__('Enable CSS3 animation', 'ulp').'
							<br /><em>'.__('Activate CSS3 animation (driven by <a href="http://daneden.github.io/animate.css/" target="_blank">Animate.css</a>).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Reset cookie', 'ulp').':</th>
						<td>
							<a class="ulp-button ulp-button-small" onclick="return ulp_cookies_reset(this);"><i class="fas fa-times"></i><label>'.__('Reset Cookies', 'ulp').'</label></a>
							<br /><em>'.__('Click the button to reset cookie. Popup will appear for all users. Do this operation if you changed content in popup and want to display it for returning visitors.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<script>ulp_toggle_loader_settings();</script>
				<h3>'.__('reCAPTCHA Settings', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable reCAPTCHA', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_recaptcha_enable" name="ulp_recaptcha_enable" '.($ulp->options['recaptcha_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Enable reCAPTCHA', 'ulp').'
							<br /><em>'.__('Enable reCAPTCHA.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_recaptcha_js_disable" name="ulp_recaptcha_js_disable" '.($ulp->options['recaptcha_js_disable'] == "on" ? 'checked="checked"' : '').'"> '.__('Disable reCAPTCHA library loading', 'ulp').'
							<br /><em>'.__('If your theme or another plugin load reCAPTCHA library, you can turn it off to avoid conflicts.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Public key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_recaptcha_public_key" name="ulp_recaptcha_public_key" value="'.esc_html($ulp->options['recaptcha_public_key']).'" class="widefat">
							<br /><em>'.__('Please enter Public Key, generated <a href="https://www.google.com/recaptcha/">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Secret key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_recaptcha_secret_key" name="ulp_recaptcha_secret_key" value="'.esc_html($ulp->options['recaptcha_secret_key']).'" class="widefat">
							<br /><em>'.__('Please enter Secret Key, generated <a href="https://www.google.com/recaptcha/">here</a>.', 'ulp').'</em>
						</td>
					</tr>
				</table>';
		do_action('ulp_options_show');
		echo '
				<h3>'.__('Item Purchase Code', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Item Purchase Code', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_purchase_code" name="ulp_purchase_code" value="'.esc_html($ulp->options['purchase_code']).'" class="widefat">
							<br /><em>'.__('To activate automatic update feature please enter Item Purchase Code. Item Purchase Code goes with your license.', 'ulp').' <a target="_blank" href="https://help.market.envato.com/hc/en-us/articles/202822600">'.__('Where can I find my Purchase Code?', 'ulp').'</a></em>
						</td>
					</tr>
					</tr>
				</table>
				<hr>
				<div class="ulp-button-container">
					<input type="hidden" name="action" value="ulp_save_settings" />
					<a class="ulp-button" onclick="return ulp_save_settings(this);"><i class="fas fa-check"></i><label>'.__('Save Settings', 'ulp').'</label></a>
				</div>
				<div class="ulp-message"></div>
			</div>
			</form>
			<div id="ulp-global-message"></div>
		</div>';
	}

	function admin_ext_settings() {
		global $wpdb, $ulp;

		if (!empty($ulp->error)) $message = "<div class='error'><p>".$ulp->error."</p></div>";
		else if (!empty($ulp->info)) $message = "<div class='updated'><p>".$ulp->info."</p></div>";
		else $message = '';
		
		if (!in_array('curl', get_loaded_extensions())) {
			$is_curl = false;
			$message .= '<div class="error"><p>'.__('cURL is <strong>not installed</strong>! Some modules are <strong>not available</strong>.', 'ulp').'</p></div>';
		
		} else $is_curl = true;
		
		echo '
		<div class="wrap ulp">
			<h2>'.__('Layered Popups - Settings', 'ulp').' <a href="https://layeredpopups.com/documentation/" class="add-new-h2" target="_blank">Online Documentation</a></h2>
			'.$message.'
			<h2 class="ulp-tabs">
				<a class="ulp-tab" href="'.admin_url('admin.php').'?page=ulp-settings">'.__('General', 'ulp').'</a>
				<a class="ulp-tab ulp-tab-active" href="'.admin_url('admin.php').'?page=ulp-settings&mode=ext">'.__('Advanced', 'ulp').'</a>
			</h2>
			<form class="ulp-popup-form" enctype="multipart/form-data" method="post" style="margin: 0px" action="'.admin_url('admin.php').'">
			<div class="ulp-options" style="width: 100%; position: relative;">
				<h3>'.__('Plugin Modules', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Basic Modules', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_social" name="ulp_ext_enable_social" '.($ulp->ext_options['enable_social'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Social Buttons module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to use Social Buttons module (Facebook Like, Google +1, Twitter Tweet, LinkedIn Share).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_social2" name="ulp_ext_enable_social2" '.($ulp->ext_options['enable_social2'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate "Subscribe with Social Media" module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to use "Subscribe with Social Media" module (Facebook, Google).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_customfields" name="ulp_ext_enable_customfields" '.($ulp->ext_options['enable_customfields'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate "Custom Fields" module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to use "Custom Fields" module.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_js" name="ulp_ext_enable_js" '.($ulp->ext_options['enable_js'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate "Custom JavaScript Handlers" module', 'ulp').' <span class="ulp-badge ulp-badge-beta">Beta</span>
							<br /><em>'.__('Tick checkbox if you want to use custom javascript event handlers for popups.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_mail" name="ulp_ext_enable_mail" '.($ulp->ext_options['enable_mail'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Admin Notification module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to receive data submitted by subscribers.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_welcomemail" name="ulp_ext_enable_welcomemail" '.($ulp->ext_options['enable_welcomemail'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Welcome Mail module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to send Welcome Mail to subscribers.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_html" name="ulp_ext_enable_htmlform" '.($ulp->ext_options['enable_htmlform'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate HTML Form Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to submit opt-in details as a part of various HTML form.', 'ulp').'</em>
						</td>
					</tr>';
		if (!defined('UAP_CORE')) {
			echo '
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_wpuser" name="ulp_ext_enable_wpuser" '.($ulp->ext_options['enable_wpuser'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate WP User Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to create WP User when popup\'s form submitted.', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
					<tr><td colspan="2"><hr /></td></tr>
					<tr>
						<th>'.__('Marketing Systems and Newsletters', 'ulp').':</th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_acellemail" name="ulp_ext_enable_acellemail" '.($ulp->ext_options['enable_acellemail'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Acelle Mail Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Acelle Mail.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_activecampaign" name="ulp_ext_enable_activecampaign" '.($ulp->ext_options['enable_activecampaign'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Active Campaign Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Active Campaign.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_activetrail" name="ulp_ext_enable_activetrail" '.($ulp->ext_options['enable_activetrail'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate ActiveTrail Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with ActiveTrail.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_agilecrm" name="ulp_ext_enable_agilecrm" '.($ulp->ext_options['enable_agilecrm'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate AgileCRM Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with AgileCRM.', 'ulp').'</em>
						</td>
					</tr>';
		if (!defined('UAP_CORE')) {
			echo '
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_arigatopro" name="ulp_ext_enable_arigatopro" '.($ulp->ext_options['enable_arigatopro'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Arigato Pro Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Arigato Pro.', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_avangemail" name="ulp_ext_enable_avangemail" '.($ulp->ext_options['enable_avangemail'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate AvangEmail Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with AvangEmail.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_aweber" name="ulp_ext_enable_aweber" '.($ulp->ext_options['enable_aweber'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate AWeber Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with AWeber.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_benchmark" name="ulp_ext_enable_benchmark" '.($ulp->ext_options['enable_benchmark'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Benchmark Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Benchmark.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_birdsend" name="ulp_ext_enable_birdsend" '.($ulp->ext_options['enable_birdsend'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate BirdSend Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with BirdSend.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_bitrix24" name="ulp_ext_enable_bitrix24" '.($ulp->ext_options['enable_bitrix24'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Bitrix24 Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Bitrix24.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_campaignmonitor" name="ulp_ext_enable_campaignmonitor" '.($ulp->ext_options['enable_campaignmonitor'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Campaign Monitor Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Campaign Monitor.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_campayn" name="ulp_ext_enable_campayn" '.($ulp->ext_options['enable_campayn'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Campayn Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Campayn.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.(class_exists('SoapClient') ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_cleverreach" name="ulp_ext_enable_cleverreach" '.($ulp->ext_options['enable_cleverreach'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate CleverReach Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with CleverReach.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_constantcontact" name="ulp_ext_enable_constantcontact" '.($ulp->ext_options['enable_constantcontact'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Constant Contact Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Constant Contact.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_conversio" name="ulp_ext_enable_conversio" '.($ulp->ext_options['enable_conversio'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Conversio Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Conversio.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_convertkit" name="ulp_ext_enable_convertkit" '.($ulp->ext_options['enable_convertkit'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate ConvertKit Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with ConvertKit.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_customerio" name="ulp_ext_enable_customerio" '.($ulp->ext_options['enable_customerio'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Customer.io Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Customer.io.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_directmail" name="ulp_ext_enable_directmail" '.($ulp->ext_options['enable_directmail'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Direct Mail Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Direct Mail.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_dotmailer" name="ulp_ext_enable_dotmailer" '.($ulp->ext_options['enable_dotmailer'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate dotmailer Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with dotmailer.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_drip" name="ulp_ext_enable_drip" '.($ulp->ext_options['enable_drip'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Drip Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Drip.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_easysendypro" name="ulp_ext_enable_easysendypro" '.($ulp->ext_options['enable_easysendypro'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate EasySendy Pro Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with EasySendy Pro.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_egoi" name="ulp_ext_enable_egoi" '.($ulp->ext_options['enable_egoi'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate E-goi Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with E-goi.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_elasticemail" name="ulp_ext_enable_elasticemail" '.($ulp->ext_options['enable_elasticemail'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Elastic Email Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Elastic Email.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_emailoctopus" name="ulp_ext_enable_emailoctopus" '.($ulp->ext_options['enable_emailoctopus'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate EmailOctopus Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with EmailOctopus.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_emma" name="ulp_ext_enable_emma" '.($ulp->ext_options['enable_emma'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Emma Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Emma.', 'ulp').'</em>
						</td>
					</tr>';
		if (!defined('UAP_CORE')) {
			echo '
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_enewsletter" name="ulp_ext_enable_enewsletter" '.($ulp->ext_options['enable_enewsletter'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate E-newsletter by WPMU DEV Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with E-newsletter by WPMU DEV.', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_esputnik" name="ulp_ext_enable_esputnik" '.($ulp->ext_options['enable_esputnik'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate eSputnik Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with eSputnik.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_firedrum" name="ulp_ext_enable_firedrum" '.($ulp->ext_options['enable_firedrum'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate FireDrum Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with FireDrum.', 'ulp').'</em>
						</td>
					</tr>';
		if (!defined('UAP_CORE')) {
			echo '
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_fue" name="ulp_ext_enable_fue" '.($ulp->ext_options['enable_fue'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Follow-Up Emails Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Follow-Up Emails.', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_freshmail" name="ulp_ext_enable_freshmail" '.($ulp->ext_options['enable_freshmail'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate FreshMail Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with FreshMail.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_getresponse" name="ulp_ext_enable_getresponse" '.($ulp->ext_options['enable_getresponse'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate GetResponse Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with GetResponse.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_hubspot" name="ulp_ext_enable_hubspot" '.($ulp->ext_options['enable_hubspot'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate HubSpot Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with HubSpot.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_icontact" name="ulp_ext_enable_icontact" '.($ulp->ext_options['enable_icontact'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate iContact Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with iContact.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_intercom" name="ulp_ext_enable_intercom" '.($ulp->ext_options['enable_intercom'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Intercom Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Intercom.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_interspire" name="ulp_ext_enable_interspire" '.($ulp->ext_options['enable_interspire'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Interspire Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Interspire.', 'ulp').'</em>
						</td>
					</tr>';
		if (!defined('UAP_CORE')) {
			echo '
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_jetpack" name="ulp_ext_enable_jetpack" '.($ulp->ext_options['enable_jetpack'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Jetpack Subscriptions Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Jetpack Subscriptions.', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_kirimemail" name="ulp_ext_enable_kirimemail" '.($ulp->ext_options['enable_kirimemail'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate KIRIM.EMAIL Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with KIRIM.EMAIL.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_klaviyo" name="ulp_ext_enable_klaviyo" '.($ulp->ext_options['enable_klaviyo'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Klaviyo Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Klaviyo.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_klicktipp" name="ulp_ext_enable_klicktipp" '.($ulp->ext_options['enable_klicktipp'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Klick Tipp Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Klick Tipp.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_madmimi" name="ulp_ext_enable_madmimi" '.($ulp->ext_options['enable_madmimi'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Mad Mimi Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Mad Mimi.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_mailautic" name="ulp_ext_enable_mailautic" '.($ulp->ext_options['enable_mailautic'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Mailautic Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Mailautic.', 'ulp').'</em>
						</td>
					</tr>';
		if (!defined('UAP_CORE')) {
			echo '
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_mailboxmarketing" name="ulp_ext_enable_mailboxmarketing" '.($ulp->ext_options['enable_mailboxmarketing'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Mailbox Marketing Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Mailbox Marketing.', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_mailchimp" name="ulp_ext_enable_mailchimp" '.($ulp->ext_options['enable_mailchimp'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate MailChimp Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with MailChimp.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_mailerlite" name="ulp_ext_enable_mailerlite" '.($ulp->ext_options['enable_mailerlite'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate MailerLite Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with MailerLite.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_mailfit" name="ulp_ext_enable_mailfit" '.($ulp->ext_options['enable_mailfit'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate MailFit Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with MailFit.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_mailgun" name="ulp_ext_enable_mailgun" '.($ulp->ext_options['enable_mailgun'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Mailgun Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Mailgun.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_mailigen" name="ulp_ext_enable_mailigen" '.($ulp->ext_options['enable_mailigen'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Mailigen Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Mailigen.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_mailjet" name="ulp_ext_enable_mailjet" '.($ulp->ext_options['enable_mailjet'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Mailjet Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Mailjet.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.(class_exists('SoapClient') ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_mailkitchen" name="ulp_ext_enable_mailkitchen" '.($ulp->ext_options['enable_mailkitchen'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate MailKitchen Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with MailKitchen.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_mailleader" name="ulp_ext_enable_mailleader" '.($ulp->ext_options['enable_mailleader'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Mailleader Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Mailleader.', 'ulp').'</em>
						</td>
					</tr>';
		if (!defined('UAP_CORE')) {
			echo '
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_mailpoet" name="ulp_ext_enable_mailpoet" '.($ulp->ext_options['enable_mailpoet'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate MailPoet (WISYJA) Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with MailPoet (WYSIJA).', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_mailrelay" name="ulp_ext_enable_mailrelay" '.($ulp->ext_options['enable_mailrelay'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Mailrelay Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Mailrelay.', 'ulp').'</em>
						</td>
					</tr>';
		if (!defined('UAP_CORE')) {
			echo '
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_mymail" name="ulp_ext_enable_mymail" '.($ulp->ext_options['enable_mymail'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Mailster (MyMail) Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Mailster (MyMail).', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '			
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_mailwizz" name="ulp_ext_enable_mailwizz" '.($ulp->ext_options['enable_mailwizz'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate MailWizz Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with MailWizz.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_markethero" name="ulp_ext_enable_markethero" '.($ulp->ext_options['enable_markethero'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Market Hero Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Market Hero.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_mautic" name="ulp_ext_enable_mautic" '.($ulp->ext_options['enable_mautic'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Mautic Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Mautic.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_moosend" name="ulp_ext_enable_moosend" '.($ulp->ext_options['enable_moosend'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Moosend Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Moosend.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_mpzmail" name="ulp_ext_enable_mpzmail" '.($ulp->ext_options['enable_mpzmail'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate MPZ Mail Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with MPZ Mail.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_mumara" name="ulp_ext_enable_mumara" '.($ulp->ext_options['enable_mumara'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Mumara Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Mumara.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_mnb" name="ulp_ext_enable_mnb" '.($ulp->ext_options['enable_mnb'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate MyNewsletterBuilder Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with MyNewsletterBuilder.', 'ulp').'</em>
						</td>
					</tr>
<!--					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_newsletter2go" name="ulp_ext_enable_newsletter2go" '.($ulp->ext_options['enable_newsletter2go'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Newsletter2Go Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Newsletter2Go.', 'ulp').'</em>
						</td>
					</tr>-->
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_omnisend" name="ulp_ext_enable_omnisend" '.($ulp->ext_options['enable_omnisend'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Omnisend Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Omnisend.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_ontraport" name="ulp_ext_enable_ontraport" '.($ulp->ext_options['enable_ontraport'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Ontraport (Office Auto Pilot) Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Ontraport (Office Auto Pilot).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_perfit" name="ulp_ext_enable_perfit" '.($ulp->ext_options['enable_perfit'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Perfit Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Perfit.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_pipedrive" name="ulp_ext_enable_pipedrive" '.($ulp->ext_options['enable_pipedrive'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Pipedrive Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Pipedrive.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_rapidmail" name="ulp_ext_enable_rapidmail" '.($ulp->ext_options['enable_rapidmail'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Rapidmail Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Rapidmail.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_rocketresponder" name="ulp_ext_enable_rocketresponder" '.($ulp->ext_options['enable_rocketresponder'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate RocketResponder Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with RocketResponder.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_salesautopilot" name="ulp_ext_enable_salesautopilot" '.($ulp->ext_options['enable_salesautopilot'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate SalesAutoPilot Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with SalesAutoPilot.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_salesmanago" name="ulp_ext_enable_salesmanago" '.($ulp->ext_options['enable_salesmanago'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate SALESmanago Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with SALESmanago.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_sendloop" name="ulp_ext_enable_sendloop" '.($ulp->ext_options['enable_sendloop'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Sendloop Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Sendloop.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_sgautorepondeur" name="ulp_ext_enable_sgautorepondeur" '.($ulp->ext_options['enable_sgautorepondeur'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate SG Autorepondeur Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with SG Autorepondeur.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_sendfox" name="ulp_ext_enable_sendfox" '.($ulp->ext_options['enable_sendfox'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate SendFox Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with SendFox.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_sendgrid" name="ulp_ext_enable_sendgrid" '.($ulp->ext_options['enable_sendgrid'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate SendGrid Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with SendGrid.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_sendinblue" name="ulp_ext_enable_sendinblue" '.($ulp->ext_options['enable_sendinblue'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate SendinBlue Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with SendinBlue.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_sendlane" name="ulp_ext_enable_sendlane" '.($ulp->ext_options['enable_sendlane'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Sendlane Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Sendlane.', 'ulp').'</em>
						</td>
					</tr>';
		if (!defined('UAP_CORE')) {
			echo '
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_sendpress" name="ulp_ext_enable_sendpress" '.($ulp->ext_options['enable_sendpress'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate SendPress Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with SendPress.', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_sendpulse" name="ulp_ext_enable_sendpulse" '.($ulp->ext_options['enable_sendpulse'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate SendPulse Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with SendPulse.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_sendreach" name="ulp_ext_enable_sendreach" '.($ulp->ext_options['enable_sendreach'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate SendReach Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with SendReach.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_sendy" name="ulp_ext_enable_sendy" '.($ulp->ext_options['enable_sendy'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Sendy Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Sendy.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_simplycast" name="ulp_ext_enable_simplycast" '.($ulp->ext_options['enable_simplycast'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate SimplyCast Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with SimplyCast.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_squalomail" name="ulp_ext_enable_squalomail" '.($ulp->ext_options['enable_squalomail'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate SqualoMail Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with SqualoMail.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_stampready" name="ulp_ext_enable_stampready" '.($ulp->ext_options['enable_stampready'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate StampReady Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with StampReady.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_streamsend" name="ulp_ext_enable_streamsend" '.($ulp->ext_options['enable_streamsend'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate StreamSend Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with StreamSend.', 'ulp').'</em>
						</td>
					</tr>';
		if (!defined('UAP_CORE')) {
			echo '
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_subscribe2" name="ulp_ext_enable_subscribe2" '.($ulp->ext_options['enable_subscribe2'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Subscribe2 Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Subscribe2.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_thenewsletterplugin" name="ulp_ext_enable_thenewsletterplugin" '.($ulp->ext_options['enable_thenewsletterplugin'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate The Newsletter Plugin Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with The Newsletter Plugin.', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_totalsend" name="ulp_ext_enable_totalsend" '.($ulp->ext_options['enable_totalsend'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate TotalSend Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with TotalSend.', 'ulp').'</em>
						</td>
					</tr>';
		if (!defined('UAP_CORE')) {
			echo '
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_tribulant" name="ulp_ext_enable_tribulant" '.($ulp->ext_options['enable_tribulant'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Tribulant Newsletters Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Tribulant Newsletters.', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_unisender" name="ulp_ext_enable_unisender" '.($ulp->ext_options['enable_unisender'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate UniSender Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with UniSender.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_userengage" name="ulp_ext_enable_userengage" '.($ulp->ext_options['enable_userengage'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate UserEngage Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with UserEngage.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_vision6" name="ulp_ext_enable_vision6" '.($ulp->ext_options['enable_vision6'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Vision6 Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Vision6.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_ymlp" name="ulp_ext_enable_ymlp" '.($ulp->ext_options['enable_ymlp'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Your Mailing List Provider Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Your Mailing List Provider.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_zohocampaigns" name="ulp_ext_enable_zohocampaigns" '.($ulp->ext_options['enable_zohocampaigns'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Zoho Campaigns Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Zoho Campaigns.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_zohocrm" name="ulp_ext_enable_zohocrm" '.($ulp->ext_options['enable_zohocrm'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate Zoho CRM Integration module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to integrate popups with Zoho CRM.', 'ulp').'</em>
						</td>
					</tr>
					<tr><td colspan="2"><hr /></td></tr>
					<tr>
						<th>'.__('Extended email verification', 'ulp').':</th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_algocheck" name="ulp_ext_enable_algocheck" '.($ulp->ext_options['enable_algocheck'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate <a href="https://www.algocheck.com/" target="_blank">AlgoCheck</a> module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to verify emails with AlgoCheck. Special Offer! Use coupon <strong>layeredpopups</strong> to get 10% off for their service.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_bulkemailchecker" name="ulp_ext_enable_bulkemailchecker" '.($ulp->ext_options['enable_bulkemailchecker'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate <a href="https://www.bulkemailchecker.com/" target="_blank">Bulk Email Checker</a> module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to verify emails with Bulk Email Checker.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_clearout" name="ulp_ext_enable_clearout" '.($ulp->ext_options['enable_clearout'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate <a href="https://clearout.io/" target="_blank">Clearout</a> module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to verify emails with Clearout.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_emaillistverify" name="ulp_ext_enable_emaillistverify" '.($ulp->ext_options['enable_emaillistverify'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate <a href="https://emaillistverify.com/" target="_blank">Emaillistverify®</a> module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to verify emails with Emaillistverify®.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_hunter" name="ulp_ext_enable_hunter" '.($ulp->ext_options['enable_hunter'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate <a href="https://hunter.io/" target="_blank">Hunter</a> module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to verify emails with Hunter.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_kickbox" name="ulp_ext_enable_kickbox" '.($ulp->ext_options['enable_kickbox'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate <a href="https://kickbox.com/" target="_blank">Kickbox</a> module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to verify emails with Kickbox.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_neverbounce" name="ulp_ext_enable_neverbounce" '.($ulp->ext_options['enable_neverbounce'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate <a href="https://neverbounce.com/" target="_blank">NeverBounce</a> module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to verify emails with NeverBounce.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_proofy" name="ulp_ext_enable_proofy" '.($ulp->ext_options['enable_proofy'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate <a href="https://proofy.io/" target="_blank">Proofy.io</a> module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to verify emails with Proofy.io.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_thechecker" name="ulp_ext_enable_thechecker" '.($ulp->ext_options['enable_thechecker'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate <a href="https://thechecker.co/" target="_blank">TheChecker.co</a> module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to verify emails with TheChecker.co. Special Offer! Use coupon <strong>LAYEREDPOPUPS</strong> to get 5000 email verification credits.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_truemail" name="ulp_ext_enable_truemail" '.($ulp->ext_options['enable_truemail'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate <a href="https://truemail.io/" target="_blank">TrueMail</a> module', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to verify emails with TrueMail.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<h3>'.__('Miscellaneous', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Log subscribers', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_ext_log_data" name="ulp_ext_log_data" '.($ulp->ext_options['log_data'] == "on" ? 'checked="checked"' : '').'"> '.__('Save subscriber details in local database', 'ulp').'
							<br /><em>'.__('Tick this checkbox if you want to save subscriber details in local database.', 'ulp').'</em>
						</td>
					</tr>';
		if (!defined('UAP_CORE')) {
			// Functionality for Advanced Targeting - 2017-04-29 - begin
			if ($ulp->options['version'] >= 6.10) {
				echo '
					<tr>
						<th>'.__('Advanced targeting', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_ext_advanced_targeting" name="ulp_ext_advanced_targeting" '.($ulp->ext_options['advanced_targeting'] == "on" ? 'checked="checked"' : '').'"> '.__('Activate advanced targeting (recommended)', 'ulp').'
							<br /><em>'.__('Adjust more accurately where to display event (OnLoad, OnScroll, etc.) popups.', 'ulp').'</em>
						</td>
					</tr>';
			}
			// Functionality for Advanced Targeting - 2017-04-29 - end
			echo '
					<tr>
						<th>'.__('Inline popups', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_ext_inline_ajaxed" name="ulp_ext_inline_ajaxed" '.($ulp->ext_options['inline_ajaxed'] == "on" ? 'checked="checked"' : '').'"> '.__('Pull inline popups using AJAX (recommended)', 'ulp').'
							<br /><em>'.__('Pull inline popups when page loaded. Activating this feature allows you to use different inline popups for desktops and mobiles.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Late Initialization', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_ext_late_init" name="ulp_ext_late_init" '.($ulp->ext_options['late_init'] == "on" ? 'checked="checked"' : '').'"> '.__('Enable late initialization', 'ulp').'
							<br /><em>'.__('Tick checkbox to enable late initilaization.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Async Initialization', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_ext_async_init" name="ulp_ext_async_init" '.($ulp->ext_options['async_init'] == "on" ? 'checked="checked"' : '').'"> '.__('Enable async initialization of event popups', 'ulp').'
							<br /><em>'.__('Tick checkbox to enable initilaization of event popups asynchronously (recommended for best front-end performance).', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th>'.__('Count impressions', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_ext_count_impressions" name="ulp_ext_count_impressions" '.($ulp->ext_options['count_impressions'] == "on" ? 'checked="checked"' : '').'"> '.__('Count impressions', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to count number of popup impressions.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Enable minification', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_ext_minified_sources" name="ulp_ext_minified_sources" '.($ulp->ext_options['minified_sources'] == "on" ? 'checked="checked"' : '').'"> '.__('Use minified JS and CSS files', 'ulp').'
							<br /><em>'.__('Tick checkbox to use minified JS and CSS files.', 'ulp').'</em>
						</td>
					</tr>';
		if (!defined('UAP_CORE')) {
			echo '
					<tr>
						<th>'.__('Remote access', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_ext_enable_remote" name="ulp_ext_enable_remote" '.($ulp->ext_options['enable_remote'] == "on" ? 'checked="checked"' : '').'"> '.__('Allow to embed popups into non-WP pages', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to use popups with non-WP pages and 3rd party websites. Read <a href="https://layeredpopups.com/documentation/#remote" target="_blank">documentation</a> regarding using this feature.', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th>'.__('Popups Library', 'ulp').':</th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_library" name="ulp_ext_enable_library" '.($ulp->ext_options['enable_library'] == "on" ? 'checked="checked"' : '').'"> '.__('Enable access to Popups Library', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to have access to Popups Library.', 'ulp').'</em>
						</td>
					</tr>';
		if (!defined('UAP_CORE')) {
			echo '
					<tr>
						<th>'.__('Add-ons', 'ulp').':</th>
						<td>
							<input '.($is_curl ? '' : 'disabled="disabled" ').'type="checkbox" id="ulp_ext_enable_addons" name="ulp_ext_enable_addons" '.($ulp->ext_options['enable_addons'] == "on" ? 'checked="checked"' : '').'"> '.__('Show available add-ons', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to view available add-ons.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Post meta', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_ext_admin_only_meta" name="ulp_ext_admin_only_meta" '.($ulp->ext_options['admin_only_meta'] == "on" ? 'checked="checked"' : '').'"> '.__('Disable post/page meta box for non-administrators', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to hide post/page meta box for non-administrators.', 'ulp').'</em>
						</td>
					</tr>
					<tr><td colspan="2"><hr></td></tr>
					<tr>
						<th>'.__('Reset settings', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp-reset-settings"> '.__('Reset General/Advanced settings', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to reset General/Advanced settings (set them to default values).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp-reset-meta"> '.__('Reset post/page meta', 'ulp').'
							<br /><em>'.__('Tick checkbox if you want to reset post/page meta (set them to default values).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<a class="ulp-button ulp-button-small" onclick="return ulp_reset_settings(this);"><i class="fas fa-times"></i><label>'.__('Reset Settings', 'ulp').'</label></a>
							<br /><em>'.__('Click the button to reset settings.', 'ulp').'</em>
						</td>
					</tr>
					<tr><td colspan="2"><hr></td></tr>
					<tr>
						<th style="color: red;">'.__('Clean database', 'ulp').':</th>
						<td style="color: red;">
							<input type="checkbox" id="ulp_ext_clean_database" name="ulp_ext_clean_database" '.($ulp->ext_options['clean_database'] == "on" ? 'checked="checked"' : '').'"> '.__('Clean database when plugin deactivated (not recommended)', 'ulp').'
							<br /><em>'.__('If you want to delete all tables and records, related to Layered Popups, tick this checbox. Database will be cleaned when you deactivate the plugin.', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
				</table>
				<hr>
				<div class="ulp-button-container">
					<input type="hidden" name="action" value="ulp_save_ext_settings" />
					<a class="ulp-button" onclick="return ulp_save_settings(this);"><i class="fas fa-check"></i><label>'.__('Save Settings', 'ulp').'</label></a>
				</div>
				<div class="ulp-message"></div>
			</div>
			</form>
			<div id="ulp-global-message"></div>
		</div>';
		echo $ulp->admin_modal_html();
	}

	function admin_popups() {
		global $wpdb, $ulp;

		if (isset($_GET["s"])) $search_query = trim(stripslashes($_GET["s"]));
		else $search_query = "";
		$tmp = $wpdb->get_row("SELECT COUNT(*) AS total FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0'".((strlen($search_query) > 0) ? " AND title LIKE '%".addslashes($search_query)."%'" : ""), ARRAY_A);
		$total = $tmp["total"];
		$totalpages = ceil($total/ULP_RECORDS_PER_PAGE);
		if ($totalpages == 0) $totalpages = 1;
		if (isset($_GET["p"])) $page = intval($_GET["p"]);
		else $page = 1;
		if ($page < 1 || $page > $totalpages) $page = 1;
		$switcher = $ulp->page_switcher(admin_url('admin.php').'?page=ulp'.((strlen($search_query) > 0) ? '&s='.rawurlencode($search_query) : ''), $page, $totalpages);

		if (isset($_GET['o'])) {
			$sort = $_GET['o'];
			if (in_array($sort, $ulp->sort_methods)) {
				if ($sort != $ulp->options['popups_sort']) {
					update_option('ulp_popups_sort', $sort);
					$ulp->options['popups_sort'] = $sort;
				}
			} else $sort = $ulp->options['popups_sort'];
		} else $sort = $ulp->options['popups_sort'];
		$orderby = 't1.created DESC';
		switch ($sort) {
			case 'title-az':
				$orderby = 't1.title ASC';
				break;
			case 'title-za':
				$orderby = 't1.title DESC';
				break;
			case 'date-az':
				$orderby = 't1.created ASC';
				break;
			default:
				$orderby = 't1.created DESC';
				break;
		}
		
		$sql = "SELECT t1.*, t2.layers FROM ".$wpdb->prefix."ulp_popups t1 LEFT JOIN (SELECT COUNT(*) AS layers, popup_id FROM ".$wpdb->prefix."ulp_layers WHERE deleted = '0' GROUP BY popup_id) t2 ON t2.popup_id = t1.id WHERE t1.deleted = '0'".((strlen($search_query) > 0) ? " AND t1.title LIKE '%".addslashes($search_query)."%'" : "")." ORDER BY ".$orderby." LIMIT ".(($page-1)*ULP_RECORDS_PER_PAGE).", ".ULP_RECORDS_PER_PAGE;
		$rows = $wpdb->get_results($sql, ARRAY_A);
		if (!empty($ulp->error)) $message = "<div class='error'><p>".$ulp->error."</p></div>";
		else if (!empty($ulp->info)) $message = "<div class='updated'><p>".$ulp->info."</p></div>";
		else {
			$message = '';
			$tmp = $wpdb->get_row("SELECT COUNT(*) AS total FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' AND blocked = '0'", ARRAY_A);
			if (intval($tmp["total"]) == 0) $message = '<div class="error"><p>'.sprintf(__('<strong>Important!</strong> All existing popups are <strong>deactivated</strong>. Please activate desired popups by clicking icon %s. After that you can use them on your website.', 'ulp'), '<i class="fas fa-plus-square"></i>').'</p></div>';
		}
		$upload_dir = wp_upload_dir();
		if (!class_exists('ZipArchive') || !class_exists('DOMDocument') || !file_exists($upload_dir["basedir"].'/'.ULP_UPLOADS_DIR.'/temp')) $export_full = false;
		else $export_full = true;
		
		echo '
			<div class="wrap admin_ulp_wrap">
				<h2>'.__('Layered Popups', 'ulp').' <a href="https://layeredpopups.com/documentation/" class="add-new-h2" target="_blank">Online Documentation</a></h2>
				'.$message.'
				<div class="ulp-top-forms">
					<div class="ulp-top-form-left">
						<form action="'.admin_url('admin.php').'" method="get" class="uap-filter-form ulp-filter-form">
							<input type="hidden" name="page" value="ulp" />
							<label>'.__('Search:', 'ulp').'</label>
							<input type="text" name="s" style="width: 200px;" class="form-control" value="'.esc_html($search_query).'">
							<input type="submit" class="button-secondary action" value="'.__('Search', 'ulp').'" />
							'.((strlen($search_query) > 0) ? '<input type="button" class="button-secondary action" value="'.__('Reset search results', 'ulp').'" onclick="window.location.href=\''.admin_url('admin.php').'?page=ulp\';" />' : '').'
						</form>
					</div>
					<div class="ulp-top-form-right">
						<form id="ulp-sorting-form" action="'.admin_url('admin.php').'" method="get" class="uap-filter-form ulp-filter-form">
						<input type="hidden" name="page" value="ulp" />
						<label>'.__('Sort:', 'ulp').'</label>
						'.((strlen($search_query) > 0) ? '<input type="hidden" name="s" value="'.esc_html($search_query).'">' : '').'
						'.(($page > 1) ? '<input type="hidden" name="p" value="'.esc_html($page).'">' : '').'
						<select name="o" onchange="jQuery(\'#ulp-sorting-form\').submit();" style="width: 150px;" class="form-control">
							<option value="title-az"'.($sort == 'title-az' ? ' selected="selected"' : '').'>'.__('Alphabetically', 'ulp').' ▲</option>
							<option value="title-za"'.($sort == 'title-za' ? ' selected="selected"' : '').'>'.__('Alphabetically', 'ulp').' ▼</option>
							<option value="date-az"'.($sort == 'date-az' ? ' selected="selected"' : '').'>'.__('Created', 'ulp').' ▲</option>
							<option value="date-za"'.($sort == 'date-za' ? ' selected="selected"' : '').'>'.__('Created', 'ulp').' ▼</option>
						</select>
						</form>
					</div>
				</div>
				<div class="ulp_buttons"><a href="'.admin_url('admin.php').'?page=ulp-add" class="ulp-button ulp-button-small"><i class="fas fa-plus"></i><label>Create New Popup</label></a></div>
				<div class="ulp_pageswitcher">'.$switcher.'</div>
				<table class="ulp_records">
				<tr>
					<th>'.__('Title', 'ulp').'</th>
					<th style="width: 160px;">'.__('ID', 'ulp').'</th>
					<th style="width: 80px;">'.__('Layers', 'ulp').'</th>
					<th style="width: 80px;">'.__('Submits', 'ulp').'</th>
					'.($ulp->ext_options['count_impressions'] == 'on' ? '<th style="width: 80px;">'.__('Impressions', 'ulp').'</th>' : '').'
					<th style="width: '.($export_full ? '210' : '190').'px;"></th>
				</tr>';
		if (sizeof($rows) > 0) {
			foreach ($rows as $row) {
				$bg_color = "";
				if (!defined('UAP_CORE')) $preview_url = get_bloginfo('url').'?ulp='.$row['str_id'].'&ac='.$ulp->random_string().'#ulp-'.$row['str_id'];
				else $preview_url = $ulp->plugins_url.'/index.html?ulp='.$row['str_id'].'#ulp-'.$row['str_id'];
				echo '
				<tr>
					<td>'.($row['blocked'] == 1 ? '<span class="ulp-badge ulp-badge-blocked">Blocked</span> ' : '').esc_html($row['title']).'</td>
					<td><input type="text" value="'.$row['str_id'].'" readonly="readonly" style="width: 100%;" onclick="this.focus();this.select();"></td>
					<td style="text-align: right;">'.intval($row['layers']).'</td>
					<td style="text-align: right;">'.intval($row['clicks']).'</td>
					'.($ulp->ext_options['count_impressions'] == 'on' ? '<td style="text-align: right;">'.intval($row['impressions']).'</td>' : '').'
					<td class="ulp-popups-actions">
						<a target="ulp-preview" href="'.$preview_url.'" title="'.__('Preview popup', 'ulp').'"><i class="far fa-window-maximize"></i></a>
						<a href="'.admin_url('admin.php').'?page=ulp-add&id='.$row['id'].'" title="'.__('Edit popup details', 'ulp').'"><i class="fas fa-pencil-alt"></i></a>
						<a href="'.admin_url('admin.php').'?action=ulp-copy&id='.$row['id'].'&ac='.$ulp->random_string().'" title="'.__('Duplicate popup', 'ulp').'" onclick="return ulp_confirm_redirect(this, \'duplicate\');"><i class="fas fa-copy"></i></a>
						<a href="'.admin_url('admin.php').'?action=ulp-export&id='.$row['id'].'&ac='.$ulp->random_string().'" title="'.__('Export popup details', 'ulp').'"><i class="fas fa-sign-out-alt"></i></a>
						'.($export_full ? '<a href="'.admin_url('admin.php').'?action=ulp-export-full&id='.$row['id'].'&ac='.$ulp->random_string().'" title="'.__('Export full popup details (including images)', 'ulp').'"><i class="fas fa-download"></i></a>' : '').'
						'.($row['blocked'] == 1 ? '<a href="'.admin_url('admin.php').'?action=ulp-unblock&id='.$row['id'].'&ac='.$ulp->random_string().'" title="'.__('Unblock popup', 'ulp').'"><i class="fas fa-plus-square"></i></a>' : '<a href="'.admin_url('admin.php').'?action=ulp-block&id='.$row['id'].'&ac='.$ulp->random_string().'" title="'.__('Block popup', 'ulp').'"><i class="fas fa-minus-square"></i></a>').'
						<a href="'.admin_url('admin.php').'?action=ulp-drop-counters&id='.$row['id'].'&ac='.$ulp->random_string().'" title="'.__('Drop counters', 'ulp').'" onclick="return ulp_confirm_redirect(this, \'reset-stats\');"><i class="fas fa-eraser"></i></a>
						'.($ulp->ext_options['log_data'] == 'on' ? '<a href="'.admin_url('admin.php').'?page=ulp-subscribers&pid='.$row['id'].'" title="'.__('View log', 'ulp').'"><i class="fas fa-list"></i></a>' : '').'
						<a href="'.admin_url('admin.php').'?action=ulp-delete&id='.$row['id'].'&ac='.$ulp->random_string().'" title="'.__('Delete popup', 'ulp').'" onclick="return ulp_confirm_redirect(this, \'delete\');"><i class="fas fa-trash-alt"></i></a>
					</td>
				</tr>';
			}
		} else {
			echo '
				<tr><td colspan="'.($ulp->ext_options['count_impressions'] == 'on' ? '6' : '5').'" style="padding: 20px; text-align: center;">'.((strlen($search_query) > 0) ? __('No results found for', 'ulp').' "<strong>'.esc_html($search_query).'</strong>"' : __('List is empty.', 'ulp')).'</td></tr>';
		}
		echo '
				</table>
				<div class="ulp_buttons">
					<form id="ulp-import-form" enctype="multipart/form-data" method="post" action="'.admin_url('admin.php').'?action=ulp-import">
						<input id="ulp-import-form-file" type="file" name="ulp-file" onchange="jQuery(\'#ulp-import-form\').submit();">
					</form>
					<a class="ulp-button ulp-button-small" onclick="jQuery(\'#ulp-import-form-file\').click(); return false;"><i class="fas fa-upload"></i><label>Import Popup</label></a>
					<a href="'.admin_url('admin.php').'?page=ulp-add" class="ulp-button ulp-button-small"><i class="fas fa-plus"></i><label>Create New Popup</label></a>
				</div>
				<div class="ulp_pageswitcher">'.$switcher.'</div>
			</div>';
		echo $ulp->admin_modal_html();
	}

	function admin_add_popup() {
		global $wpdb, $ulp;

		if (isset($_GET["id"]) && !empty($_GET["id"])) {
			$id = intval($_GET["id"]);
			$popup_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE id = '".$id."' AND deleted = '0'", ARRAY_A);
		}
		if (!empty($popup_details)) {
			$id = $popup_details['id'];
			$popup_options = unserialize($popup_details['options']);
			if (is_array($popup_options)) $popup_options = array_merge($ulp->default_popup_options, $popup_options);
			else $popup_options = $ulp->default_popup_options;
		} else {
			$id = 0;
			$popup_options = $ulp->default_popup_options;
		}
		if (substr($popup_options['button_icon'], 0, 3) == 'fa-') $popup_options['button_icon'] = 'fa fa-noicon';
		
		if ($ulp->options['version'] >= 4.58) {
			$webfonts_array = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_webfonts WHERE deleted = '0' ORDER BY family", ARRAY_A);
		} else $webfonts_array = array();
		
		if (!empty($ulp->error)) $message = "<div class='error'><p>".$ulp->error."</p></div>";
		else if (!empty($ulp->info)) $message = "<div class='updated'><p>".$ulp->info."</p></div>";
		else $message = '';
		
		$extra_tabs = apply_filters('ulp_popup_options_tabs', array());
		
		echo '
		<style>body {position: absolute; width: 100%;}</style>
		<div class="wrap ulp">
			<h2>'.(!empty($popup_details) ? __('Layered Popups - Edit Popup', 'ulp') : __('Layered Popups - Create Popup', 'ulp')).' <a href="https://layeredpopups.com/documentation/" class="add-new-h2" target="_blank">Online Documentation</a></h2>
			'.$message.'
			<form class="ulp-popup-form" enctype="multipart/form-data" method="post" style="margin: 0px" action="'.admin_url('admin.php').'">
			<h2 id="ulp-popup-editor-tabs" class="ulp-tabs">
				<a class="ulp-tab ulp-tab-active" href="#ulp-popup-editor-general">'.__('General', 'ulp').'</a>
				<a class="ulp-tab" href="#ulp-popup-editor-mailing">'.__('Mailing', 'ulp').'</a>';
		foreach($extra_tabs as $key => $value) {
			echo '
				<a class="ulp-tab" href="#ulp-popup-editor-'.$key.'">'.$value.'</a>';
		}
		echo '
			</h2>
			<div id="ulp-popup-editor-general" class="ulp-popup-editor-tab" style="width: 100%; position: relative; display: block;">
				<h3>'.__('General Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Title', 'ulp').':</th>
						<td>
							<input type="text" name="ulp_title" value="'.(!empty($popup_details['title']) ? esc_html($popup_details['title']) : esc_html($ulp->default_popup_options['title'])).'" class="widefat" placeholder="Enter the popup title...">
							<br /><em>'.__('Enter the popup title. It is used for your reference.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Basic size', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" name="ulp_width" value="'.(!empty($popup_details['width']) ? esc_html($popup_details['width']) : esc_html($ulp->default_popup_options['width'])).'" class="ic_input_number" placeholder="Width" onblur="ulp_build_preview();" onchange="ulp_build_preview();"> x
							<input type="text" name="ulp_height" value="'.(!empty($popup_details['height']) ? esc_html($popup_details['height']) : esc_html($ulp->default_popup_options['height'])).'" class="ic_input_number" placeholder="Height" onblur="ulp_build_preview();" onchange="ulp_build_preview();"> pixels
							<br /><em>'.__('Enter the size of basic frame. This frame will be positioned according to <strong>Position</strong> parameter and all layers will be placed relative to the top-left corner of this frame.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Position', 'ulp').':</th>
						<td>
							<div id="ulp-position-top-left" class="ulp-position-box'.($popup_options['position'] == 'top-left' ? ' ulp-position-selected' : '').'" onclick="ulp_set_position(this);"><div class="ulp-position-element ulp-position-top-left"></div></div>
							<div id="ulp-position-top-center" class="ulp-position-box'.($popup_options['position'] == 'top-center' ? ' ulp-position-selected' : '').'" onclick="ulp_set_position(this);"><div class="ulp-position-element ulp-position-top-center"></div></div>
							<div id="ulp-position-top-right" class="ulp-position-box'.($popup_options['position'] == 'top-right' ? ' ulp-position-selected' : '').'" onclick="ulp_set_position(this);"><div class="ulp-position-element ulp-position-top-right"></div></div>
							<br />
							<div id="ulp-position-middle-left" class="ulp-position-box'.($popup_options['position'] == 'middle-left' ? ' ulp-position-selected' : '').'" onclick="ulp_set_position(this);"><div class="ulp-position-element ulp-position-middle-left"></div></div>
							<div id="ulp-position-middle-center" class="ulp-position-box'.($popup_options['position'] == 'middle-center' ? ' ulp-position-selected' : '').'" onclick="ulp_set_position(this);"><div class="ulp-position-element ulp-position-middle-center"></div></div>
							<div id="ulp-position-middle-right" class="ulp-position-box'.($popup_options['position'] == 'middle-right' ? ' ulp-position-selected' : '').'" onclick="ulp_set_position(this);"><div class="ulp-position-element ulp-position-middle-right"></div></div>
							<br />
							<div id="ulp-position-bottom-left" class="ulp-position-box'.($popup_options['position'] == 'bottom-left' ? ' ulp-position-selected' : '').'" onclick="ulp_set_position(this);"><div class="ulp-position-element ulp-position-bottom-left"></div></div>
							<div id="ulp-position-bottom-center" class="ulp-position-box'.($popup_options['position'] == 'bottom-center' ? ' ulp-position-selected' : '').'" onclick="ulp_set_position(this);"><div class="ulp-position-element ulp-position-bottom-center"></div></div>
							<div id="ulp-position-bottom-right" class="ulp-position-box'.($popup_options['position'] == 'bottom-right' ? ' ulp-position-selected' : '').'" onclick="ulp_set_position(this);"><div class="ulp-position-element ulp-position-bottom-right"></div></div>
							<input type="hidden" id="ulp_position" name="ulp_position" value="'.(!empty($popup_options['position']) ? esc_html($popup_options['position']) : esc_html($ulp->default_popup_options['position'])).'">
							<br /><em>'.__('Select popup position on browser window.', 'ulp').'</em>
							<script>
								function ulp_set_position(object) {
									var position = jQuery(object).attr("id");
									position = position.replace("ulp-position-", "");
									jQuery("#ulp_position").val(position);
									jQuery(".ulp-position-box").removeClass("ulp-position-selected");
									jQuery(object).addClass("ulp-position-selected");
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Disable overlay', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_disable_overlay" name="ulp_disable_overlay" '.($popup_options['disable_overlay'] == "on" ? 'checked="checked"' : '').'"> '.__('Disable overlay', 'ulp').'
							<br /><em>'.__('Please tick checkbox to disable overlay.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Overlay color', 'ulp').':</th>
						<td>
							<input type="text" class="ulp-color ic_input_number" name="ulp_overlay_color" value="'.(!empty($popup_options['overlay_color']) ? esc_html($popup_options['overlay_color']) : esc_html($ulp->default_popup_options['overlay_color'])).'" placeholder="">
							<em>'.__('Set the overlay color.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Overlay opacity', 'ulp').':</th>
						<td>
							<input type="text" name="ulp_overlay_opacity" value="'.(!empty($popup_options['overlay_opacity']) ? esc_html($popup_options['overlay_opacity']) : esc_html($ulp->default_popup_options['overlay_opacity'])).'" class="ic_input_number" placeholder="Opacity">
							<br /><em>'.__('Set the overlay opacity. The value must be in a range [0...1].', 'ulp').'</em>
						</td>
					</tr>';
		if ($ulp->options['css3_enable'] == 'on') {
			echo '
					<tr>
						<th>'.__('Overlay animation', 'ulp').':</th>
						<td>
							<select id="ulp_overlay_animation" name="ulp_overlay_animation">';
			foreach ($ulp->css3_appearances as $key => $value) {
				echo '
								<option value="'.$key.'"'.($popup_options['overlay_animation'] == $key ? ' selected="selected"' : '').'>'.esc_html($value).'</option>';
			}
			echo '
							</select>
							<br /><em>'.__('Set the overlay animation.', 'ulp').'</em>
						</td>
					</tr>';
		}
		if (($ulp->options['no_preload'] == 'on' || $ulp->ext_options['inline_ajaxed'] == 'on') && $ulp->options['spinkit_enable'] == 'on') {
			echo '
					<tr>
						<th>'.__('AJAX-spinner', 'ulp').':</th>
						<td>
							<a class="button-secondary ulp_button" href="#" onclick="jQuery(\'#ulp-spinners-box\').slideToggle(300); return false;">'.__('Select Spinner', 'ulp').'</a><br />
							<div id="ulp-spinners-box">
							<input type="hidden" id="ulp_ajax_spinner" name="ulp_ajax_spinner" value="'.esc_html($popup_options['ajax_spinner']).'">';
			foreach ($ulp->ajax_spinners as $key => $html) {
				echo '<div class="ulp-spinner-item'.($popup_options['ajax_spinner'] == $key ? ' ulp-spinner-item-selected' : '').'" onclick="ulp_set_spinner(this, \''.$key.'\');"><div class="ulp-spinner-item-container">'.$html.'</div></div>';
			}
			echo '					
							</div>
							<em>'.__('Select desired spinner for popup loading. This spinner is displayed while popup is pulling using AJAX (both modes - popup and inline).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="text" class="ulp-color ic_input_number" name="ulp_ajax_spinner_color" value="'.(!empty($popup_options['ajax_spinner_color']) ? esc_html($popup_options['ajax_spinner_color']) : esc_html($ulp->default_popup_options['ajax_spinner_color'])).'" placeholder="">
							<em>'.__('Set the AJAX-spinner color. Applied to CSS3-spinners (all spinners except first one).', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '			
					<tr>
						<th>'.__('Extended closing', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_enable_close" name="ulp_enable_close" '.($popup_options['enable_close'] == "on" ? 'checked="checked"' : '').'"> '.__('Close popup window on ESC-button pressing and overlay click', 'ulp').'
							<br /><em>'.__('Please tick checkbox to enable popup closing on ESC-button click and overlay click.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Extended submission', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_enable_enter" name="ulp_enable_enter" '.($popup_options['enable_enter'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit the form on ENTER-button pressing', 'ulp').'
							<br /><em>'.__('Please tick checkbox to allow users to submit by pressing ENTER-button.', 'ulp').'</em>
						</td>
					</tr>
				</table>
				<h3>'.__('Popup Constructor', 'ulp').'</h3>
				<div id="ulp-layers">
					<div>
						<a id="ulp-layers-expand-collapse" href="#" onclick="return ulp_neo_toggle_layers();"><i id="ulp-toggle-layers-icon" class="fas fa-minus-square"></i></a>
						<a id="ulp-layers-expand-collapse" href="#" onclick="return ulp_neo_toggle_constructor_settings();"><i id="ulp-toggle-constructor-settings-icon" class="fas fa-cog"></i></a>
						<i class="fas fa-angle-double-right"></i> '.__('Layers', 'ulp').'
					</div>
					<div id="ulp-layers-constructor-settings">
						'.__('Grid cell size', 'ulp').': <input id="ulp-grid-size" value="5" /> '.__('px', 'ulp').'
					</div>
					<ul id="ulp-layers-list">';
		$sql = "SELECT * FROM ".$wpdb->prefix."ulp_layers WHERE deleted = '0' AND popup_id = '".$id."' ORDER BY zindex, created ASC";
		$layers = $wpdb->get_results($sql, ARRAY_A);
		$layers_options_html = '';
		if (sizeof($layers) > 0) {
			foreach ($layers as $layer) {
				$layer_options = unserialize($layer['details']);
				if (is_array($layer_options)) $layer_options = array_merge($ulp->default_layer_options, $layer_options);
				else $layer_options = $ulp->default_layer_options;
				$layer_options = $ulp->filter_lp($layer_options);
				if (strlen($layer_options['content']) == 0) $content = 'No content...';
				else if (strlen($layer_options['content']) > 192) $content = substr($layer_options['content'], 0, 180).'...';
				else $content = $layer_options['content'];
				//$layers_options_html .= '<div class="ulp-layer-options-data" data-id="'.$layer['id'].'">';
				foreach ($layer_options as $key => $value) {
					$layers_options_html .= '<input type="hidden" id="ulp_layer_'.$layer['id'].'_'.$key.'" name="ulp_layer_'.$layer['id'].'_'.$key.'" value="'.esc_html($value).'">';
				}
				//$layers_options_html .= '</div>';
				echo '
						<li id="ulp-layer-'.$layer['id'].'">
							<i class="fas fa-arrows-alt-v ulp-sortable-icon"></i>
							<a href="#" class="ulp-layer-action-icon ulp-layer-action-delete" title="'.__('Delete the layer', 'ulp').'"><i class="fas fa-times"></i></a>
							<a href="#" class="ulp-layer-action-icon ulp-layer-action-copy" title="'.__('Duplicate the layer', 'ulp').'"><i class="far fa-copy"></i></a>
							<label>'.esc_html($layer_options['title']).'</label>
							<span>'.esc_html($content).'</span>
						</li>';
			}
		}
		echo '
					</ul>
					<div id="ulp-layers-add"><a href="#" onclick="return ulp_helper_add_layer(); return ulp_neo_add_layer();"><i class="fas fa-plus"></i> '.__('Add Layer', 'ulp').'</a></div>
					'.$layers_options_html.'
				</div>
				<div id="ulp-layer-details">
					<div id="ulp-layer-details-container">
						<div class="ulp-layer-details-title">
							<a href="#" onclick="return ulp_neo_hide_layer_details();">'.__('Hide', 'ulp').' <i class="fas fa-angle-double-right"></i></span></a>
							<label><i class="fas fa-cog"></i> '.__('Layer Details', 'ulp').'</label>
						</div>
						<div class="ulp-layer-details-content">
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Title', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Enter the layer title. It is used for your reference.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_title" name="ulp_layer_title" value="" class="ulp-layer-input-wide" placeholder="'.__('Enter the layer title...', 'ulp').'">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Content', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Enter the layer content. HTML-code allowed.', 'ulp').'"></i> :<br /><a target="_blank" href="https://layeredpopups.com/documentation/#content">'.__('Details', 'ulp').'</a></label>
								<div><textarea id="ulp_layer_content" name="ulp_layer_content" class="widefat" placeholder="'.__('Enter the layer content...', 'ulp').'"></textarea></div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Size', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Enter the layer size, width x height. Leave both or one field empty for auto calculation.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_width" name="ulp_layer_width" value="" class="ulp-layer-input-number" placeholder="'.__('Width', 'ulp').'"> x
									<input type="text" id="ulp_layer_height" name="ulp_layer_height" value="" class="ulp-layer-input-number" placeholder="'.__('Height', 'ulp').'"> px
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Position', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Enter the layer top-left position relative basic frame top-left corner.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_top" name="ulp_layer_top" value="" class="ulp-layer-input-number" placeholder="'.__('Top', 'ulp').'"> x
									<input type="text" id="ulp_layer_left" name="ulp_layer_left" value="" class="ulp-layer-input-number" placeholder="'.__('Left', 'ulp').'"> px
								</div>
							</div>
						</div>
						<div class="ulp-layer-details-title">
							<a href="#" onclick="return ulp_neo_hide_layer_details();">'.__('Hide', 'ulp').' <i class="fas fa-angle-double-right"></i></span></a>
							<label><i class="fas fa-font"></i> '.__('Text and Font', 'ulp').'</label>
						</div>
						<div class="ulp-layer-details-content">
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Font', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Select the font.', 'ulp').'"></i> :</label>
								<div>
									<select class="ulp-layer-input-wide" id="ulp_layer_font" name="ulp_layer_font">
										<option disabled="disabled">------ LOCAL FONTS ------</option>';
		foreach ($ulp->local_fonts as $key => $value) {
			echo '
										<option value="'.$key.'">'.esc_html($value).'</option>';
		}
		if (is_array($webfonts_array) && !empty($webfonts_array)) {
			echo '
										<option disabled="disabled">------ WEB FONTS ------</option>';
			foreach ($webfonts_array as $webfont) {
				echo '
										<option value="'.esc_html($webfont['family']).'">'.esc_html($webfont['family']).'</option>';
			}
		}
		echo '
									</select>
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Color', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the font color.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" class="ulp-color" id="ulp_layer_font_color" name="ulp_layer_font_color" value="" placeholder="">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Color:hover', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the font hover color.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" class="ulp-color" id="ulp_layer_font_hover_color" name="ulp_layer_font_hover_color" value="" placeholder="">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Size', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the font size. The value must be in a range [10...64].', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_font_size" name="ulp_layer_font_size" value="" class="ulp-layer-input-number" placeholder="'.__('Font size', 'ulp').'"> px
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Weight', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Select the font weight. Some fonts may not support selected font weight.', 'ulp').'"></i> :</label>
								<div>
									<select class="ulp-layer-input-wide" id="ulp_layer_font_weight" name="ulp_layer_font_weight">';
		foreach ($ulp->font_weights as $key => $value) {
			echo '
										<option value="'.$key.'">'.esc_html($key.' - '.$value).'</option>';
		}
		echo '
									</select>
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Shadow size', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the text shadow size.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_text_shadow_size" name="ulp_layer_text_shadow_size" value="" class="ulp-layer-input-number" placeholder="'.__('Size', 'ulp').'"> px
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Shadow color', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the text shadow color.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" class="ulp-color" id="ulp_layer_text_shadow_color" name="ulp_layer_text_shadow_color" value="" placeholder="">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Alignment', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the horizontal content alignment.', 'ulp').'"></i> :</label>
								<div>
									<select class="ulp-layer-input-wide" id="ulp_layer_content_align" name="ulp_layer_content_align">';
		foreach ($ulp->alignments as $key => $value) {
			echo '
										<option value="'.$key.'">'.esc_html($value).'</option>';
		}
		echo '
									</select>
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('H-Padding', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the content horizontal padding.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_padding_h" name="ulp_layer_padding_h" value="" class="ulp-layer-input-number" placeholder="'.__('px', 'ulp').'"> px
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('V-Padding', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the content vertical padding.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_padding_v" name="ulp_layer_padding_v" value="" class="ulp-layer-input-number" placeholder="'.__('px', 'ulp').'"> px
								</div>
							</div>
						</div>
						<div class="ulp-layer-details-title">
							<a href="#" onclick="return ulp_neo_hide_layer_details();">'.__('Hide', 'ulp').' <i class="fas fa-angle-double-right"></i></span></a>
							<label><i class="fas fa-image"></i> '.__('Background', 'ulp').'</label>
						</div>
						<div class="ulp-layer-details-content">
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Gradient', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Tick checkbox to enable gradient background color.', 'ulp').'"></i> :</label>
								<div>
									<i data-id="ulp_layer_background_gradient" class="far fa-square ulp-checkbox"></i> '.__('Enable gradient', 'ulp').'
									<input type="hidden" id="ulp_layer_background_gradient" name="ulp_layer_background_gradient" value="off">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline ulp-background-gradient-only">
								<label>'.__('Angle', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the background gradient angle. The value must be in a range [0...360].', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_background_gradient_angle" name="ulp_layer_background_gradient_angle" value="" class="ulp-layer-input-number" placeholder="[0...360]"> deg
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Color', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the background color. Leave empty for transparent background.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" class="ulp-color" id="ulp_layer_background_color" name="ulp_layer_background_color" value="" placeholder="">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline ulp-background-gradient-only">
								<label>'.__('To color', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the background color.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" class="ulp-color" id="ulp_layer_background_gradient_to" name="ulp_layer_background_gradient_to" value="" placeholder="">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Color:hover', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the background on hover color. Leave empty to disable color changing.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" class="ulp-color" id="ulp_layer_background_hover_color" name="ulp_layer_background_hover_color" value="" placeholder="">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline ulp-background-gradient-only">
								<label>'.__('To color:hover', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the background on hover color.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" class="ulp-color" id="ulp_layer_background_hover_gradient_to" name="ulp_layer_background_hover_gradient_to" value="" placeholder="">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Opacity', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the background opacity. The value must be in a range [0...1].', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_background_opacity" name="ulp_layer_background_opacity" value="" class="ulp-layer-input-number" placeholder="[0...1]">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Image URL', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Enter the background image URL.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_background_image" name="ulp_layer_background_image" value="" class="ulp-layer-input-wide" placeholder="'.__('Enter the background image URL...', 'ulp').'">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Image repeat', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set how a background image will be repeated.', 'ulp').'"></i> :</label>
								<div>
									<select class="ulp-layer-input-wide" id="ulp_layer_background_image_repeat" name="ulp_layer_background_image_repeat">';
		foreach ($ulp->background_repeats as $key => $value) {
			echo '
										<option value="'.$key.'">'.esc_html($value).'</option>';
		}
		echo '
									</select>
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Image size', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Specify the size of the background image.', 'ulp').'"></i> :</label>
								<div>
									<select class="ulp-layer-input-wide" id="ulp_layer_background_image_size" name="ulp_layer_background_image_size">';
		foreach ($ulp->background_sizes as $key => $value) {
			echo '
										<option value="'.$key.'">'.esc_html($value).'</option>';
		}
		echo '
									</select>
								</div>
							</div>
						</div>
						<div class="ulp-layer-details-title">
							<a href="#" onclick="return ulp_neo_hide_layer_details();">'.__('Hide', 'ulp').' <i class="fas fa-angle-double-right"></i></span></a>
							<label><i class="far fa-square"></i> '.__('Border', 'ulp').'</label>
						</div>
						<div class="ulp-layer-details-content">
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Width', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the border width.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_border_width" name="ulp_layer_border_width" value="" class="ulp-layer-input-number" placeholder="'.__('px', 'ulp').'"> px
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Style', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Specify the border style.', 'ulp').'"></i> :</label>
								<div>
									<select class="ulp-layer-input-wide" id="ulp_layer_border_style" name="ulp_layer_border_style">';
		foreach ($ulp->border_styles as $key => $value) {
			echo '
										<option value="'.$key.'">'.esc_html($value).'</option>';
		}
		echo '
									</select>
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Color', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the border color.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" class="ulp-color" id="ulp_layer_border_color" name="ulp_layer_border_color" value="" placeholder="">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Color:hover', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the border on hover color.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" class="ulp-color" id="ulp_layer_border_hover_color" name="ulp_layer_border_hover_color" value="" placeholder="">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Radius', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the border radius.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_border_radius" name="ulp_layer_border_radius" value="" class="ulp-layer-input-number" placeholder="'.__('px', 'ulp').'"> px
								</div>
							</div>
						</div>
						<div class="ulp-layer-details-title">
							<a href="#" onclick="return ulp_neo_hide_layer_details();">'.__('Hide', 'ulp').' <i class="fas fa-angle-double-right"></i></span></a>
							<label><i class="fas fa-image"></i> '.__('Box Shadow', 'ulp').'</label>
						</div>
						<div class="ulp-layer-details-content">
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Shadow', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Tick checkbox to enable box shadow.', 'ulp').'"></i> :</label>
								<div>
									<i data-id="ulp_layer_box_shadow" class="far fa-square ulp-checkbox"></i> '.__('Enable box shadow', 'ulp').'
									<input type="hidden" id="ulp_layer_box_shadow" name="ulp_layer_box_shadow" value="off">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline ulp-box-shadow-only">
								<label>'.__('H-Shadow', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the position of the horizontal shadow.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_box_shadow_h" name="ulp_layer_box_shadow_h" value="" class="ulp-layer-input-number" placeholder="'.__('px', 'ulp').'"> px
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline ulp-box-shadow-only">
								<label>'.__('V-Shadow', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the position of the vertical shadow.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_box_shadow_v" name="ulp_layer_box_shadow_v" value="" class="ulp-layer-input-number" placeholder="'.__('px', 'ulp').'"> px
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline ulp-box-shadow-only">
								<label>'.__('Blur', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the blur distance.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_box_shadow_blur" name="ulp_layer_box_shadow_blur" value="" class="ulp-layer-input-number" placeholder="'.__('px', 'ulp').'"> px
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline ulp-box-shadow-only">
								<label>'.__('Spread', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the size of shadow.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_box_shadow_spread" name="ulp_layer_box_shadow_spread" value="" class="ulp-layer-input-number" placeholder="'.__('px', 'ulp').'"> px
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline ulp-box-shadow-only">
								<label>'.__('Color', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the box shadow color.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" class="ulp-color" id="ulp_layer_box_shadow_color" name="ulp_layer_box_shadow_color" value="" placeholder="">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline ulp-box-shadow-only">
								<label>'.__('Inset', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Tick checkbox to change the shadow from an outer shadow (outset) to an inner shadow.', 'ulp').'"></i> :</label>
								<div>
									<i data-id="ulp_layer_box_shadow_inset" class="far fa-square ulp-checkbox"></i> '.__('Enable inset shadow', 'ulp').'
									<input type="hidden" id="ulp_layer_box_shadow_inset" name="ulp_layer_box_shadow_inset" value="off">
								</div>
							</div>
						</div>
						<div class="ulp-layer-details-title">
							<a href="#" onclick="return ulp_neo_hide_layer_details();">'.__('Hide', 'ulp').' <i class="fas fa-angle-double-right"></i></span></a>
							<label><i class="far fa-eye"></i> '.__('Animation', 'ulp').'</label>
						</div>
						<div class="ulp-layer-details-content">
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Animation', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set animation effect of the layer.', 'ulp').'"></i> :</label>
								<div>
									<select class="ulp-layer-input-wide" id="ulp_layer_appearance" name="ulp_layer_appearance">';
		if ($ulp->options['css3_enable'] == 'on') {
			echo '
										<option value="" disabled="disabled">'.__('=== Basic jQuery Animation ===', 'ulp').'</option>';
		}
		foreach ($ulp->appearances as $key => $value) {
			echo '
										<option value="'.$key.'">'.esc_html($value).'</option>';
		}
		if ($ulp->options['css3_enable'] == 'on') {
			echo '
										<option value="" disabled="disabled">'.__('=== CSS3 Animation ===', 'ulp').'</option>';
			foreach ($ulp->css3_appearances as $key => $value) {
				echo '
										<option value="'.$key.'">'.esc_html($value).'</option>';
			}
		}
		echo '
									</select>
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Duration', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the duration speed in milliseconds.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_appearance_speed" name="ulp_layer_appearance_speed" value="" class="ulp-layer-input-number" placeholder="[0...10000]"> ms
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Start delay', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Set the animation start delay.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_appearance_delay" name="ulp_layer_appearance_delay" value="" class="ulp-layer-input-number" placeholder="[0...10000]"> ms
								</div>
							</div>
						</div>
						<div class="ulp-layer-details-title">
							<a href="#" onclick="return ulp_neo_hide_layer_details();">'.__('Hide', 'ulp').' <i class="fas fa-angle-double-right"></i></span></a>
							<label><i class="fas fa-cogs"></i> '.__('Miscellaneous', 'ulp').'</label>
						</div>
						<div class="ulp-layer-details-content">
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Scrollbar', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Add scrollbar to the layer. Layer height must be set.', 'ulp').'"></i> :</label>
								<div>
									<i data-id="ulp_layer_scrollbar" class="far fa-square ulp-checkbox"></i> '.__('Add scrollbar', 'ulp').'
									<input type="hidden" id="ulp_layer_scrollbar" name="ulp_layer_scrollbar" value="off">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Inline mode', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('This layer appears only in popup mode and disabled for inline mode.', 'ulp').'"></i> :</label>
								<div>
									<i data-id="ulp_layer_inline_disable" class="far fa-square ulp-checkbox"></i> '.__('Disable for inline mode', 'ulp').'
									<input type="hidden" id="ulp_layer_inline_disable" name="ulp_layer_inline_disable" value="off">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Special', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('This layer appears only on successful submitting of subscription/contact form.', 'ulp').'"></i> :</label>
								<div>
									<i data-id="ulp_layer_confirmation_layer" class="far fa-square ulp-checkbox"></i> '.__('"Confirmation of subscription" layer', 'ulp').'
									<input type="hidden" id="ulp_layer_confirmation_layer" name="ulp_layer_confirmation_layer" value="off">
								</div>
							</div>
							<div class="ulp-layer-details-item-singleline">
								<label>'.__('Custom CSS', 'ulp').' <i class="fas fa-info-circle ulp-layer-details-tooltip" title="'.__('Enter the custom style string. This value is added to layer style attribute.', 'ulp').'"></i> :</label>
								<div>
									<input type="text" id="ulp_layer_style" name="ulp_layer_style" value="" class="ulp-layer-input-wide" placeholder="'.__('Enter the custom style string...', 'ulp').'">
								</div>
							</div>
						</div>
					</div>
				</div>
				<script>
					jQuery(".ulp-checkbox").click(function(){
						var checkbox_id = jQuery(this).attr("data-id");
						if (jQuery("#"+checkbox_id).val() == "off") {
							jQuery(this).removeClass("fa-square");
							jQuery(this).addClass("fa-check-square");
							jQuery("#"+checkbox_id).val("on");
							if (checkbox_id == "ulp_layer_background_gradient") jQuery(".ulp-background-gradient-only").slideDown();
							if (checkbox_id == "ulp_layer_box_shadow") jQuery(".ulp-box-shadow-only").slideDown();
						} else {
							jQuery(this).removeClass("fa-check-square");
							jQuery(this).addClass("fa-square");
							jQuery("#"+checkbox_id).val("off");
							if (checkbox_id == "ulp_layer_background_gradient") jQuery(".ulp-background-gradient-only").slideUp();
							if (checkbox_id == "ulp_layer_box_shadow") jQuery(".ulp-box-shadow-only").slideUp();
						}
						ulp_build_preview();
					});
				</script>
				<div class="ulp-preview-container">
					<div class="ulp-preview-window ulp-preview-window-loading" style="width: '.intval($popup_options['width']).'px; height: '.intval($popup_options['height']).'px;">
						<div class="ulp-preview-content">
						</div>
					</div>
				</div>
				<h3>'.__('Native Form Parameters', 'ulp').'</h3>
				<p>'.__('The parameters below are used for subscription form only. Please read FAQ section about adding subscription form into layers.', 'ulp').'</p>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('"Name" field placeholder', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_name_placeholder" name="ulp_name_placeholder" value="'.esc_html($popup_options['name_placeholder']).'" class="widefat">
							<br /><em>'.__('Enter the placeholder for "Name" input field.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_name_mandatory" name="ulp_name_mandatory" '.($popup_options['name_mandatory'] == "on" ? 'checked="checked"' : '').'> '.__('"Name" field is mandatory', 'ulp').'
							<br /><em>'.__('Please tick checkbox to set "Name" field as mandatory.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('"Phone number" field placeholder', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_phone_placeholder" name="ulp_phone_placeholder" value="'.esc_html($popup_options['phone_placeholder']).'" class="widefat">
							<br /><em>'.__('Enter the placeholder for "Phone number" input field.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_phone_mandatory" name="ulp_phone_mandatory" '.($popup_options['phone_mandatory'] == "on" ? 'checked="checked"' : '').'> '.__('"Phone number" field is mandatory', 'ulp').'
							<br /><em>'.__('Please tick checkbox to set "Phone number" field as mandatory.', 'ulp').'</em>
						</td>
					</tr>';
		if ($ulp->options['mask_enable'] == 'on') {
			echo '
					<tr>
						<th>'.__('"Phone number" mask', 'ulp').':</th>
						<td style="vertical-align: middle; line-height: 1.6;">
							<select name="ulp_phone_mask" id="ulp_phone_mask" onchange="ulp_phone_mask_changed();">';
			foreach($ulp->phone_masks as $key => $mask) {
				echo '
								<option value="'.$key.'"'.($popup_options['phone_mask'] == $key ? ' selected="selected"' : '').'>'.esc_html($mask).'</option>';
			}
		echo '
							<select>
							<br /><em>'.__('Select desired phone field mask.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="text" id="ulp_phone_custom_mask" name="ulp_phone_custom_mask" value="'.esc_html($popup_options['phone_custom_mask']).'" class="widefat"'.($popup_options['phone_mask'] != 'custom' ? ' disabled="disabled"' : '').'>
							<br /><em>'.__('Set custom phone mask. For more details please visit <a target="_blank" href="http://igorescobar.github.io/jQuery-Mask-Plugin/">jQuery Mask plugin page</a>.', 'ulp').'</em>
							<script>
								function ulp_phone_mask_changed() {
									if (jQuery("#ulp_phone_mask").val() == "custom") jQuery("#ulp_phone_custom_mask").removeAttr("disabled");
									else jQuery("#ulp_phone_custom_mask").attr("disabled", "disabled");
								}
							</script>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th>'.__('"Phone number" length', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" id="ulp_phone_length" name="ulp_phone_length" value="'.esc_html($popup_options['phone_length']).'" class="ic_input_number" placeholder="..."> '.__('digits', 'ulp').'
							<br /><em>'.__('How many digits "Phone number" can contain. For several options put several numbers separated by commas. Leave field empty, if not required.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('"Message" text area placeholder', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_message_placeholder" name="ulp_message_placeholder" value="'.esc_html($popup_options['message_placeholder']).'" class="widefat">
							<br /><em>'.__('Enter the placeholder for "Message" text area.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_message_mandatory" name="ulp_message_mandatory" '.($popup_options['message_mandatory'] == "on" ? 'checked="checked"' : '').'> '.__('"Message" text area is mandatory', 'ulp').'
							<br /><em>'.__('Please tick checkbox to set "Message" text area as mandatory.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('"E-mail" field placeholder', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_email_placeholder" name="ulp_email_placeholder" value="'.esc_html($popup_options['email_placeholder']).'" class="widefat">
							<br /><em>'.__('Enter the placeholder for "E-mail" input field.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_email_mandatory" name="ulp_email_mandatory" '.($popup_options['email_mandatory'] == "on" ? 'checked="checked"' : '').'> '.__('"E-mail" field is mandatory', 'ulp').'
							<br /><em>'.__('Please tick checkbox to set "E-mail" field as mandatory.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('"Submit" button label', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_button_label" name="ulp_button_label" value="'.esc_html($popup_options['button_label']).'" class="widefat">
							<br /><em>'.__('Enter the label for "Submit" button.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('"Loading" button label', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_button_label_loading" name="ulp_button_label_loading" value="'.esc_html($popup_options['button_label_loading']).'" class="widefat">
							<br /><em>'.__('Enter the label for "Submit" button which appears on subscription form submission.', 'ulp').'</em>
						</td>
					</tr>';
		if ($ulp->options['fa_enable'] == 'on') {
			echo '
					<tr>
						<th>'.__('Button icon', 'ulp').':</th>
						<td>
							<span id="ulp-button-icon-image" class="ulp-icon ulp-icon-active" title="'.__('Icons', 'ulp').'" onclick="jQuery(\'#ulp-button-icon-set\').slideToggle(300);"><i class="'.$popup_options['button_icon'].'"></i></span><br />
							<div id="ulp-button-icon-set" class="ulp-icon-set">
							<span class="ulp-icon'.($popup_options['button_icon'] == 'fa fa-noicon' ? ' ulp-icon-active' : '').'" title="noicon" onclick="ulp_seticon(this, \'ulp-button-icon\');"><i class="fa fa-noicon"></i></span>';
			if ($ulp->options['fa_solid_enable'] == 'on') {
				foreach ($ulp->fa_solid as $value) {
					echo '<span class="ulp-icon'.($popup_options['button_icon'] == $value ? ' ulp-icon-active' : '').'" title="'.$value.'" onclick="ulp_seticon(this, \'ulp-button-icon\');"><i class="fas fa-'.$value.'"></i></span>';
				}
			}
			if ($ulp->options['fa_regular_enable'] == 'on') {
				foreach ($ulp->fa_regular as $value) {
					echo '<span class="ulp-icon'.($popup_options['button_icon'] == $value ? ' ulp-icon-active' : '').'" title="'.$value.'" onclick="ulp_seticon(this, \'ulp-button-icon\');"><i class="far fa-'.$value.'"></i></span>';
				}
			}
			if ($ulp->options['fa_brands_enable'] == 'on') {
				foreach ($ulp->fa_brands as $value) {
					echo '<span class="ulp-icon'.($popup_options['button_icon'] == $value ? ' ulp-icon-active' : '').'" title="'.$value.'" onclick="ulp_seticon(this, \'ulp-button-icon\');"><i class="fab fa-'.$value.'"></i></span>';
				}
			}
			echo '
							</div>
							<input type="hidden" name="ulp_button_icon" id="ulp-button-icon" value="'.$popup_options['button_icon'].'">
							<em>'.__('Select "Submit" button icon.', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th>'.__('Button color', 'ulp').':</th>
						<td>
							<input type="text" class="ulp-color ic_input_number" name="ulp_button_color" value="'.esc_html($popup_options['button_color']).'" placeholder=""> 
							<em>'.__('Set the "Submit" button color.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_button_gradient" name="ulp_button_gradient" '.($popup_options['button_gradient'] == "on" ? 'checked="checked"' : '').'"> '.__('Add color gradient', 'ulp').'
							<br /><em>'.__('Please tick checkbox to want to add color gradient to "Submit" button.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Button size', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_button_inherit_size" name="ulp_button_inherit_size" '.($popup_options['button_inherit_size'] == "on" ? 'checked="checked"' : '').'"> '.__('Inherit layer size', 'ulp').'
							<br /><em>'.__('Please tick checkbox to want to inherit layer size for button size.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Button border radius', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" id="ulp_button_border_radius" name="ulp_button_border_radius" value="'.esc_html($popup_options['button_border_radius']).'" class="ic_input_number" placeholder="'.__('pixels', 'ulp').'"> '.__('pixels', 'ulp').'
							<br /><em>'.__('Set the border radius of "Submit" button.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Button CSS', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" id="ulp_button_css" name="ulp_button_css" value="'.esc_html($popup_options['button_css']).'" class="widefat" placeholder="'.__('Custom button CSS', 'ulp').'">
							<br /><em>'.__('Customize CSS for "Submit" button.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Button:hover CSS', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" id="ulp_button_css_hover" name="ulp_button_css_hover" value="'.esc_html($popup_options['button_css_hover']).'" class="widefat" placeholder="'.__('Custom button:hover CSS', 'ulp').'">
							<br /><em>'.__('Customize CSS for "Submit" button when pointer is over the button.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Input field border color', 'ulp').':</th>
						<td>
							<input type="text" class="ulp-color ic_input_number" name="ulp_input_border_color" value="'.esc_html($popup_options['input_border_color']).'" placeholder="">
							<em>'.__('Set the border color of input fields.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Input field border width', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" id="ulp_input_border_width" name="ulp_input_border_width" value="'.esc_html($popup_options['input_border_width']).'" class="ic_input_number" placeholder="'.__('pixels', 'ulp').'"> '.__('pixels', 'ulp').'
							<br /><em>'.__('Set the border width of input fields.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Input field border radius', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" id="ulp_input_border_radius" name="ulp_input_border_radius" value="'.esc_html($popup_options['input_border_radius']).'" class="ic_input_number" placeholder="'.__('pixels', 'ulp').'"> '.__('pixels', 'ulp').'
							<br /><em>'.__('Set the border radius of input fields.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Input field background color', 'ulp').':</th>
						<td>
							<input type="text" class="ulp-color ic_input_number" name="ulp_input_background_color" value="'.esc_html($popup_options['input_background_color']).'" placeholder="">
							<em>'.__('Set the background color of input fields.', 'ulp').'</em>
						</td>
					</tr>';
		if ($ulp->options['fa_enable'] == 'on') {
			echo '
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_input_icons" name="ulp_input_icons" '.($popup_options['input_icons'] == "on" ? 'checked="checked"' : '').'"> '.__('Add icons to input fields', 'ulp').'
							<br /><em>'.__('Please tick checkbox to want to add icons to input fields.', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th>'.__('Input field background opacity', 'ulp').':</th>
						<td>
							<input type="text" class="ic_input_number" name="ulp_input_background_opacity" value="'.esc_html($popup_options['input_background_opacity']).'" placeholder="[0...1]">
							<br /><em>'.__('Set the background opacity of input fields. The value must be in a range [0...1].', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Input field CSS', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" id="ulp_input_css" name="ulp_input_css" value="'.esc_html($popup_options['input_css']).'" class="widefat" placeholder="'.__('Custom input field CSS', 'ulp').'">
							<br /><em>'.__('Customize CSS for input fields.', 'ulp').'</em>
						</td>
					</tr>';
		if ($ulp->options['recaptcha_enable'] == 'on') {
			echo '
					<tr>
						<th>'.__('reCAPTCHA theme', 'ulp').':</th>
						<td>
							<select class="ic_input_m" name="ulp_recaptcha_theme" id="ulp_recaptcha_theme">
								<option value="light"'.($popup_options['recaptcha_theme'] == 'light' ? ' selected="selected"' : '').'>Light</option>
								<option value="dark"'.($popup_options['recaptcha_theme'] == 'dark' ? ' selected="selected"' : '').'>Dark</option>
							</select>
							<br /><em>'.__('Select reCAPTCHA theme.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="checkbox" id="ulp_recaptcha_mandatory" name="ulp_recaptcha_mandatory" '.($popup_options['recaptcha_mandatory'] == "on" ? 'checked="checked"' : '').'"> '.__('reCAPTCHA is mandatory', 'ulp').'
							<br /><em>'.__('Please tick checkbox to set reCAPTCHA as mandatory. Do not forget to create new layer and insert <code>{recaptcha}</code> shortcode into its content.', 'ulp').'</em>
						</td>
					</tr>';
		}
		echo '
					<tr>
						<th>'.__('Autoclose delay', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" id="ulp_close_delay" name="ulp_close_delay" value="'.esc_html($popup_options['close_delay']).'" class="ic_input_number" placeholder="'.__('seconds', 'ulp').'"> '.__('seconds', 'ulp').'
							<br /><em>'.__('When form submission is succesfull, the popup will be automatically closed after this delay.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Redirect URL', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_return_url" name="ulp_return_url" value="'.esc_html($popup_options['return_url']).'" class="widefat">
							<br /><em>'.__('Enter the redirect URL. After successful form submission user is redirected to this URL. Leave blank to stay on the same page.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Thanksgiving popup', 'ulp').':</th>
						<td style="vertical-align: middle; line-height: 1.6;">
							<select name="ulp_thanksgiving_popup">
								<option value="">'.__('None (disabled)', 'ulp').'</option>';
		$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY title ASC", ARRAY_A);
			foreach($popups as $popup) {
				echo '
								<option value="'.$popup['str_id'].'"'.($popup_options['thanksgiving_popup'] == $popup['str_id'] ? ' selected="selected"' : '').'>'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
			}
		echo '
							<select>
							<br /><em>'.__('Thanksgiving popup appears after successful form submission.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Cookie lifetime', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<input type="text" id="ulp_cookie_lifetime" name="ulp_cookie_lifetime" value="'.esc_html($popup_options['cookie_lifetime']).'" class="ic_input_number" placeholder="'.__('days', 'ulp').'"> '.__('days', 'ulp').'
							<br /><em>'.__('When form submission is successful, the cookie is set to avoid further appearance. This is cookie lifetime.', 'ulp').'</em>
						</td>
					</tr>
				</table>';
				do_action('ulp_popup_options_show', $popup_options);
		echo '
			</div>
			<div id="ulp-popup-editor-mailing" class="ulp-popup-editor-tab" style="width: 100%; position: relative;">
				<h3>'.__('Double Opt-In Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable double opt-in', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_doubleoptin_enable" name="ulp_doubleoptin_enable"'.($popup_options['doubleoptin_enable'] == "on" && $ulp->ext_options['log_data'] == "on" ? ' checked="checked"' : '').($ulp->ext_options['log_data'] != "on" ? ' disabled="disabled"' : '').'> '.__('Activate double opt-in', 'ulp').'
							<br /><em>'.($ulp->ext_options['log_data'] != "on" ? __('Tick "Log subscribers" checkbox <a href="?page=ulp-settings&mode=ext">here</a> to make this feature available.', 'ulp') : __('Please tick checkbox to activate double opt-in. Subscribers must confirm their e-mail addresses.', 'ulp')).'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Subject', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_doubleoptin_subject" name="ulp_doubleoptin_subject" value="'.esc_html($popup_options['doubleoptin_subject']).'" class="widefat">
							<br /><em>'.__('After submission subscribers receive e-mail message with confirmation link. This is the subject field of the message.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Message', 'ulp').':</th>
						<td>
							<textarea id="ulp_doubleoptin_message" name="ulp_doubleoptin_message" class="widefat" style="height: 120px;">'.esc_html($popup_options['doubleoptin_message']).'</textarea>
							<br /><em>'.__('After submission subscribers receive e-mail message with confirmation link. You can use the shortcodes ({confirmation-link}, {subscription-email}, {subscription-name}, etc.).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Thanksgiving message', 'ulp').':</th>
						<td>
							<textarea id="ulp_doubleoptin_confirmation_message" name="ulp_doubleoptin_confirmation_message" class="widefat" style="height: 120px;">'.esc_html($popup_options['doubleoptin_confirmation_message']).'</textarea>
							<br /><em>'.__('This message is displayed when users successfully confirmed their e-mail addresses. You can use the shortcodes ({subscription-email}, {subscription-name}, etc.).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Thanksgiving URL', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_doubleoptin_redirect_url" name="ulp_doubleoptin_redirect_url" value="'.esc_html($popup_options['doubleoptin_redirect_url']).'" class="widefat">
							<br /><em>'.__('This is alternate way of thanksgiving message. After confirmation users are redirected to this URL.', 'ulp').'</em>
						</td>
					</tr>
				</table>';
				do_action('ulp_popup_options_mailing_show', $popup_options);
		echo '
			</div>';
		foreach($extra_tabs as $key => $value) {
			echo '
			<div id="ulp-popup-editor-'.$key.'" class="ulp-popup-editor-tab" style="width: 100%; position: relative;">';
				do_action('ulp_popup_options_'.$key.'_show', $popup_options);
			echo '
			</div>';
		}
		echo '
			<div style="width: 100%; position: relative; margin-top: 30px;">
				<hr>
				<div class="ulp-button-container">
					<input type="hidden" name="action" value="ulp_save_popup" />
					<input type="hidden" id="ulp-popup-id" name="ulp_id" value="'.$id.'" />
					<input type="hidden" name="ulp_layers" id="ulp_layers" value="" />
					<a class="ulp-button" onclick="return ulp_save_popup(this);"><i class="fas fa-check"></i><label>'.__('Save Popup Details', 'ulp').'</label></a>
				</div>
				<div id="ulp-message" class="ulp-message"></div>
				<div id="ulp-overlay"></div>
			</div>
			</form>
			<div id="ulp-global-message"></div>
			<script type="text/javascript">
				var ulp_local_fonts = new Array("'.strtolower(implode('","', $ulp->local_fonts)).'");
				var ulp_default_layer_options = {';
		foreach ($ulp->default_layer_options as $key => $value) {
			echo '
					"'.$key.'" : "'.esc_html($value).'",';
		}
		echo '
					"a" : ""
				};
				function ulp_build_preview() {
					jQuery(".ulp-preview-window-loading").removeClass("ulp-preview-window-loading");
					//jQuery(".ulp-preview-container").css({
					//	"background" : jQuery("[name=\'ulp_overlay_color\']").val()
					//});
					ulp_neo_sync_layer_details();
					jQuery(".ulp-preview-window").css({
						"width" : parseInt(jQuery("[name=\'ulp_width\']").val(), 10) + "px",
						"height" : parseInt(jQuery("[name=\'ulp_height\']").val(), 10) + "px"
					});
					
					var popup_style = "";
					var from_rgb = ulp_hex2rgb(jQuery("[name=\'ulp_button_color\']").val());
					var to_color = "transparent";
					var from_color = "transparent";
					if (from_rgb) {
						var total = parseInt(from_rgb.r, 10)+parseInt(from_rgb.g, 10)+parseInt(from_rgb.b, 10);
						if (total == 0) total = 1;
						var to = {
							r : Math.max(0, parseInt(from_rgb.r, 10) - parseInt(48*from_rgb.r/total, 10)),
							g : Math.max(0, parseInt(from_rgb.g, 10) - parseInt(48*from_rgb.g/total, 10)),
							b : Math.max(0, parseInt(from_rgb.b, 10) - parseInt(48*from_rgb.b/total, 10))
						};
						from_color = jQuery("[name=\'ulp_button_color\']").val();
						to_color = ulp_rgb2hex(to.r, to.g, to.b);
					}
					var input_border_color = "border-color:transparent !important;";
					if (jQuery("[name=\'ulp_input_border_color\']").val() != "") input_border_color = "border-color:"+jQuery("[name=\'ulp_input_border_color\']").val()+" !important;";
					input_border_color = input_border_color + " border-width:"+parseInt(jQuery("[name=\'ulp_input_border_width\']").val(), 10)+"px !important; border-radius:"+parseInt(jQuery("[name=\'ulp_input_border_radius\']").val(), 10)+"px !important;"
					var input_background_color = "background-color: transparent !important;";
					if (jQuery("[name=\'ulp_input_background_color\']").val() != "") {
						var bg_rgb = ulp_hex2rgb(jQuery("[name=\'ulp_input_background_color\']").val());
						input_background_color = "background-color:rgb("+parseInt(bg_rgb.r)+","+parseInt(bg_rgb.g)+","+parseInt(bg_rgb.b)+") !important;background-color:rgba("+parseInt(bg_rgb.r)+","+parseInt(bg_rgb.g)+","+parseInt(bg_rgb.b)+", "+jQuery("[name=\'ulp_input_background_opacity\']").val()+") !important;";
					}
					if (jQuery("#ulp_button_gradient").is(":checked")) {
						popup_style += ".ulp-preview-submit,.ulp-preview-submit:visited{background: "+from_color+";border:1px solid "+from_color+";background-image:linear-gradient("+to_color+","+from_color+"); border-radius:"+parseInt(jQuery("[name=\'ulp_button_border_radius\']").val(), 10)+"px !important;}";
						popup_style += ".ulp-preview-submit:hover,.ulp-preview-submit:active{background: "+to_color+";border:1px solid "+from_color+";background-image:linear-gradient("+from_color+","+to_color+"); border-radius:"+parseInt(jQuery("[name=\'ulp_button_border_radius\']").val(), 10)+"px !important;}";
					} else {
						popup_style += ".ulp-preview-submit,.ulp-preview-submit:visited{background: "+from_color+";border:1px solid "+from_color+"; border-radius:"+parseInt(jQuery("[name=\'ulp_button_border_radius\']").val(), 10)+"px !important;}";
						popup_style += ".ulp-preview-submit:hover,.ulp-preview-submit:active{background: "+to_color+";border:1px solid "+to_color+"; border-radius:"+parseInt(jQuery("[name=\'ulp_button_border_radius\']").val(), 10)+"px !important;}";
					}
					if (jQuery("#ulp_button_css").val() != "") {
						popup_style += ".ulp-preview-submit,.ulp-preview-submit:visited{"+jQuery("#ulp_button_css").val()+"}";
					}
					if (jQuery("#ulp_button_css_hover").val() != "") {
						popup_style += ".ulp-preview-submit:hover,.ulp-preview-submit:active{"+jQuery("#ulp_button_css_hover").val()+"}";
					}
					popup_style += ".ulp-preview-input,.ulp-preview-input:hover,.ulp-preview-input:active,.ulp-preview-input:focus,.ulp-preview-checkbox{"+input_border_color+""+input_background_color+"}";
					if (jQuery("#ulp_input_css").val() != "") {
						popup_style += ".ulp-preview-input,.ulp-preview-input:hover,.ulp-preview-input:active,.ulp-preview-input:focus,.ulp-preview-checkbox{"+jQuery("#ulp_input_css").val()+"}";
					}';
		do_action('ulp_js_build_preview_popup_style');
		echo '			
					jQuery(".ulp-preview-content").html("<style>"+popup_style+"</style>");
					var input_name_icon_html = "";
					var input_email_icon_html = "";
					var input_phone_icon_html = "";';
		if ($ulp->options['fa_enable'] == 'on') {
			echo '
					if (jQuery("#ulp_input_icons").is(":checked")) {
						input_name_icon_html = "<div class=\'ulp-fa-input-table\'><div class=\'ulp-fa-input-cell\'><i class=\''.($ulp->options['fa_solid_enable'] == 'on' ? 'fas fa-user' : 'far fa-user').'\'></i></div></div>";
						input_email_icon_html = "<div class=\'ulp-fa-input-table\'><div class=\'ulp-fa-input-cell\'><i class=\''.($ulp->options['fa_solid_enable'] == 'on' ? 'fas fa-envelope' : 'far fa-envelope').'\'></i></div></div>";
						input_phone_icon_html = "<div class=\'ulp-fa-input-table\'><div class=\'ulp-fa-input-cell\'><i class=\''.($ulp->options['fa_solid_enable'] == 'on' ? 'fas fa-phone' : 'far fa-bell').'\'></i></div></div>";
					}';
		}
		echo '		
					var recaptcha_image = "'.$ulp->plugins_url.'/images/recaptcha_light.png";
					if (jQuery("#ulp_recaptcha_theme").val() == "dark") recaptcha_image = "'.$ulp->plugins_url.'/images/recaptcha_dark.png";
					var zindex = 1;
					var input_cover = "<div class=\'ulp-input-cover\'></div>";
					jQuery("#ulp-layers-list li").each(function() {
						var layer_id = jQuery(this).attr("id").replace("ulp-layer-", "");
						var content = jQuery("#ulp_layer_"+layer_id+"_content").val();
						content = content.replace("{subscription-name}", "<input class=\'ulp-preview-input\' id=\'ulp-preview-input-name\' type=\'text\'>"+input_name_icon_html+input_cover);
						content = content.replace("{subscription-email}", "<input class=\'ulp-preview-input\' id=\'ulp-preview-input-email\' type=\'text\'>"+input_email_icon_html+input_cover);
						content = content.replace("{subscription-submit}", "<a class=\'ulp-preview-submit\' id=\'ulp-preview-submit\'></a>");
						content = content.replace("{subscription-phone}", "<input class=\'ulp-preview-input\' id=\'ulp-preview-input-phone\' type=\'tel\'>"+input_phone_icon_html+input_cover);
						content = content.replace("{subscription-message}", "<textarea class=\'ulp-preview-input\' id=\'ulp-preview-input-message\'></textarea>"+input_cover);';
		if ($ulp->options['recaptcha_enable'] == 'on') {
			echo '
						content = content.replace("{recaptcha}", "<div class=\'ulp-preview-recaptcha\' style=\'width: 306px; height: 80px;\'><img src=\'"+recaptcha_image+"\' /></div>");';
		}
		do_action('ulp_js_build_preview_content');
		echo '					
						var style = "#ulp-preview-layer-"+layer_id+" {left:" + parseInt(jQuery("#ulp_layer_"+layer_id+"_left").val(), 10) + "px;top:" + parseInt(jQuery("#ulp_layer_"+layer_id+"_top").val(), 10) + "px;}";
						if (jQuery("#ulp_layer_"+layer_id+"_width").val() != "") style += "#ulp-preview-layer-"+layer_id+" {width:"+parseInt(jQuery("#ulp_layer_"+layer_id+"_width").val(), 10)+"px;}";
						if (jQuery("#ulp_layer_"+layer_id+"_height").val() != "") style += "#ulp-preview-layer-"+layer_id+" {height:"+parseInt(jQuery("#ulp_layer_"+layer_id+"_height").val(), 10)+"px;}";
						var background = "";
						var rgb, rgb_to;
						if (jQuery("#ulp_layer_"+layer_id+"_background_color").val() != "") {
							rgb = ulp_hex2rgb(jQuery("#ulp_layer_"+layer_id+"_background_color").val());
							if (rgb != false) background = "background-color:"+jQuery("#ulp_layer_"+layer_id+"_background_color").val()+";background-color:rgba("+rgb.r+","+rgb.g+","+rgb.b+","+jQuery("#ulp_layer_"+layer_id+"_background_opacity").val()+");";
							if (jQuery("#ulp_layer_"+layer_id+"_background_gradient").val() == "on") {
								rgb_to = ulp_hex2rgb(jQuery("#ulp_layer_"+layer_id+"_background_gradient_to").val());
								if (rgb_to != false) background += "background:linear-gradient("+jQuery("#ulp_layer_"+layer_id+"_background_gradient_angle").val()+"deg, rgba("+rgb.r+","+rgb.g+","+rgb.b+","+jQuery("#ulp_layer_"+layer_id+"_background_opacity").val()+") 0%,rgba("+rgb_to.r+","+rgb_to.g+","+rgb_to.b+","+jQuery("#ulp_layer_"+layer_id+"_background_opacity").val()+") 100%);";
								else background += "background:linear-gradient("+jQuery("#ulp_layer_"+layer_id+"_background_gradient_angle").val()+"deg, rgba("+rgb.r+","+rgb.g+","+rgb.b+","+jQuery("#ulp_layer_"+layer_id+"_background_opacity").val()+") 0%,rgba(0,0,0,0) 100%);";
							}
						}
						var background_hover = "";
						if (jQuery("#ulp_layer_"+layer_id+"_background_hover_color").val() != "") {
							rgb = ulp_hex2rgb(jQuery("#ulp_layer_"+layer_id+"_background_hover_color").val());
							if (rgb != false) background_hover = "background-color:"+jQuery("#ulp_layer_"+layer_id+"_background_hover_color").val()+";background-color:rgba("+rgb.r+","+rgb.g+","+rgb.b+","+jQuery("#ulp_layer_"+layer_id+"_background_opacity").val()+");";
							if (jQuery("#ulp_layer_"+layer_id+"_background_gradient").val() == "on") {
								rgb_to = ulp_hex2rgb(jQuery("#ulp_layer_"+layer_id+"_background_hover_gradient_to").val());
								if (rgb_to != false) background_hover += "background:linear-gradient("+jQuery("#ulp_layer_"+layer_id+"_background_gradient_angle").val()+"deg, rgba("+rgb.r+","+rgb.g+","+rgb.b+","+jQuery("#ulp_layer_"+layer_id+"_background_opacity").val()+") 0%,rgba("+rgb_to.r+","+rgb_to.g+","+rgb_to.b+","+jQuery("#ulp_layer_"+layer_id+"_background_opacity").val()+") 100%);";
								else background_hover += "background:linear-gradient("+jQuery("#ulp_layer_"+layer_id+"_background_gradient_angle").val()+"deg, rgba("+rgb.r+","+rgb.g+","+rgb.b+","+jQuery("#ulp_layer_"+layer_id+"_background_opacity").val()+") 0%,rgba(0,0,0,0) 100%);";
							}
						}
						if (jQuery("#ulp_layer_"+layer_id+"_background_image").val() != "") {
							background += "background-image:url("+jQuery("#ulp_layer_"+layer_id+"_background_image").val()+");background-repeat:"+jQuery("#ulp_layer_"+layer_id+"_background_image_repeat").val()+";background-size:"+jQuery("#ulp_layer_"+layer_id+"_background_image_size").val()+";";
						}
						var box_shadow = "";
						if (jQuery("#ulp_layer_"+layer_id+"_box_shadow").val() == "on") {
							box_shadow = "box-shadow:"+parseInt(jQuery("#ulp_layer_"+layer_id+"_box_shadow_h").val(), 10)+"px "+parseInt(jQuery("#ulp_layer_"+layer_id+"_box_shadow_v").val(), 10)+"px "+parseInt(jQuery("#ulp_layer_"+layer_id+"_box_shadow_blur").val(), 10)+"px "+parseInt(jQuery("#ulp_layer_"+layer_id+"_box_shadow_spread").val(), 10)+"px "+jQuery("#ulp_layer_"+layer_id+"_box_shadow_color").val();
							if (jQuery("#ulp_layer_"+layer_id+"_box_shadow_inset").val() == "on") box_shadow += " inset";
							box_shadow += ";";
						}
						var border = "border-radius:"+jQuery("#ulp_layer_"+layer_id+"_border_radius").val()+"px;";
						if (jQuery("#ulp_layer_"+layer_id+"_border_color").val() != "" && jQuery("#ulp_layer_"+layer_id+"_border_width").val() != "" && jQuery("#ulp_layer_"+layer_id+"_border_style").val() != "none") {
							border = border + "border:"+jQuery("#ulp_layer_"+layer_id+"_border_width").val()+"px "+jQuery("#ulp_layer_"+layer_id+"_border_style").val()+" "+jQuery("#ulp_layer_"+layer_id+"_border_color").val()+";";
						}
						var border_hover = "";
						if (jQuery("#ulp_layer_"+layer_id+"_border_hover_color").val() != "" && jQuery("#ulp_layer_"+layer_id+"_border_width").val() != "" && jQuery("#ulp_layer_"+layer_id+"_border_style").val() != "none") {
							border_hover = border_hover + "border:"+jQuery("#ulp_layer_"+layer_id+"_border_width").val()+"px "+jQuery("#ulp_layer_"+layer_id+"_border_style").val()+" "+jQuery("#ulp_layer_"+layer_id+"_border_hover_color").val()+";";
						}
						var font = "text-align:"+jQuery("#ulp_layer_"+layer_id+"_content_align").val()+";";
						if (jQuery("#ulp_layer_"+layer_id+"_font").val() != "inherit") font = font + "font-family:\'"+jQuery("#ulp_layer_"+layer_id+"_font").val()+"\',arial;";
						if (jQuery("#ulp_layer_"+layer_id+"_font_weight").val() != "inherit") font = font + "font-weight:"+jQuery("#ulp_layer_"+layer_id+"_font_weight").val()+";";
						if (jQuery("#ulp_layer_"+layer_id+"_font_color").val() != "") font = font + "color:"+jQuery("#ulp_layer_"+layer_id+"_font_color").val()+";";
						if (jQuery("#ulp_layer_"+layer_id+"_font_size").val() != "") font = font + "font-size:"+parseInt(jQuery("#ulp_layer_"+layer_id+"_font_size").val(), 10)+"px;";
						var font_hover = "";
						if (jQuery("#ulp_layer_"+layer_id+"_font_hover_color").val() != "") font_hover = "color:"+jQuery("#ulp_layer_"+layer_id+"_font_hover_color").val()+";";
							
						if (parseInt(jQuery("#ulp_layer_"+layer_id+"_text_shadow_size").val(), 10) != 0 && jQuery("#ulp_layer_"+layer_id+"_text_shadow_color").val() != "") font += "text-shadow:"+jQuery("#ulp_layer_"+layer_id+"_text_shadow_color").val()+" "+jQuery("#ulp_layer_"+layer_id+"_text_shadow_size").val()+"px "+" "+jQuery("#ulp_layer_"+layer_id+"_text_shadow_size").val()+"px "+" "+jQuery("#ulp_layer_"+layer_id+"_text_shadow_size").val()+"px";
						style += "#ulp-preview-layer-"+layer_id+",#ulp-preview-layer-"+layer_id+" * {"+font+"}";
						if (font_hover != "") style += "#ulp-preview-layer-"+layer_id+":hover,#ulp-preview-layer-"+layer_id+" *:hover {"+font_hover+"}";';
		if ($ulp->options['fa_enable'] == 'on') {
/*			echo '
						if (jQuery("#ulp_input_icons").is(":checked")) {
							style += "#ulp-preview-layer-"+layer_id+" input.ulp-preview-input, #ulp-preview-layer-"+layer_id+" select.ulp-preview-input {padding-left:"+parseInt(4+2*parseInt(jQuery("#ulp_layer_"+layer_id+"_font_size").val(), 10), 10)+"px !important;} #ulp-preview-layer-"+layer_id+" div.ulp-fa-input-cell {width: "+parseInt(2*parseInt(jQuery("#ulp_layer_"+layer_id+"_font_size").val(), 10), 10)+"px !important; padding-left: 4px !important;}";
						}';*/
		}
		echo '
						style += "#ulp-preview-layer-"+layer_id+" .ulp-preview-checkbox{font-size:"+Math.min(parseInt(jQuery("#ulp_layer_"+layer_id+"_height").val(), 10), parseInt(jQuery("#ulp_layer_"+layer_id+"_width").val(), 10))+"px; line-height:"+Math.floor(0.81*parseInt(jQuery("#ulp_layer_"+layer_id+"_height").val(), 10))+"px;}";
						style += "#ulp-preview-layer-"+layer_id+"{"+box_shadow+background+border+"z-index:"+parseInt(zindex+1000, 10)+";text-align:"+jQuery("#ulp_layer_"+layer_id+"_content_align").val()+";}";
						style += "#ulp-preview-layer-"+layer_id+":hover{"+background_hover+border_hover+"}";
						if (jQuery("#ulp_layer_"+layer_id+"_style").val() != "") style += "#ulp-preview-layer-"+layer_id+"{"+jQuery("#ulp_layer_"+layer_id+"_style").val()+"}";
						if (jQuery("#ulp_layer_"+layer_id+"_scrollbar").val() == "on") style += "#ulp-preview-layer-"+layer_id+"{overflow:hidden;}";
						var v_padding = parseInt(jQuery("#ulp_layer_"+layer_id+"_padding_v").val(), 10);
						var h_padding = parseInt(jQuery("#ulp_layer_"+layer_id+"_padding_h").val(), 10);
						var font_link = "";
						if (!ulp_inarray(jQuery("#ulp_layer_"+layer_id+"_font").val(), ulp_local_fonts)) font_link = "<link href=\'//fonts.googleapis.com/css?family="+jQuery("#ulp_layer_"+layer_id+"_font").val().replace(" ", "+")+":100,200,300,400,500,600,700,800,900&subset=arabic,vietnamese,hebrew,thai,bengali,latin,latin-ext,cyrillic,cyrillic-ext,greek\' rel=\'stylesheet\' type=\'text/css\'>";
						if (content.indexOf("<iframe") > -1) content += "<div class=\'ulp-input-cover\'></div>";
						if (v_padding > 0 || h_padding > 0) content = "<div style=\'padding: "+v_padding+"px "+h_padding+"px;\'>"+content+"</div>";
						var layer = font_link+"<style>"+style+"</style><div class=\'ulp-preview-layer\' id=\'ulp-preview-layer-"+layer_id+"\'>"+content+"</div>";
						jQuery(".ulp-preview-content").append(layer);
						zindex++;
					});
					jQuery("#ulp-preview-input-name").attr("placeholder", jQuery("[name=\'ulp_name_placeholder\']").val());
					jQuery("#ulp-preview-input-email").attr("placeholder", jQuery("[name=\'ulp_email_placeholder\']").val());
					if (jQuery("#ulp_button_inherit_size").is(":checked")) {
						jQuery("#ulp-preview-submit").addClass("ulp-inherited");
					} else {
						jQuery("#ulp-preview-submit").removeClass("ulp-inherited");
					}
					var button_icon = "";
					if (jQuery("#ulp-button-icon").val() && jQuery("#ulp-button-icon").val() != "fa fa-noicon") button_icon = "<i class=\'"+jQuery("#ulp-button-icon").val()+"\'></i>";
					var button_label = jQuery("[name=\'ulp_button_label\']").val();
					if (button_icon != "" && button_label != "") button_icon = button_icon+"&nbsp; ";
					jQuery("#ulp-preview-submit").html(button_icon+button_label);
					jQuery("#ulp-preview-input-phone").attr("placeholder", jQuery("[name=\'ulp_phone_placeholder\']").val());
					jQuery("#ulp-preview-input-message").attr("placeholder", jQuery("[name=\'ulp_message_placeholder\']").val());';
		if ($ulp->options['fa_enable'] == 'on') {
			echo '
					if (jQuery("#ulp_input_icons").is(":checked")) {
						jQuery("input.ulp-preview-input, select.ulp-preview-input").addClass("ulp-preview-input-iconized");
					}';
		}
		echo '			
					if (jQuery(".ulp-layers-list-item-selected").length) {
						var layer_id = jQuery(".ulp-layers-list-item-selected").attr("id");
						layer_id = layer_id.replace("ulp-layer-", "");
						ulp_neo_select_preview_layer(layer_id);
					}
					
					jQuery(".ulp-preview-layer").mousedown(function(){
						var layer_id = jQuery(this).attr("id");
						layer_id = layer_id.replace("ulp-preview-layer-", "");
						ulp_neo_edit_layer(layer_id, false);
					});
					var grid_size = jQuery("#ulp-grid-size").val();
					if (!isFinite(grid_size) || isNaN(parseFloat(grid_size))) {
						jQuery("#ulp-grid-size").val("5");
						grid_size = 5;
					}
					if (grid_size > 50) {
						jQuery("#ulp-grid-size").val("50");
						grid_size = 50;
					} else if (grid_size < 1) {
						jQuery("#ulp-grid-size").val("1");
						grid_size = 1;
					}
					jQuery(".ulp-preview-layer").draggable({
						grid: [grid_size, grid_size], 
						cursor: "move",
						start: function() {
						},
						drag: function() {
							var position = jQuery(this).position();
							position.left = parseInt(position.left, 10);
							position.top = parseInt(position.top, 10);
							jQuery(this).find(".ulp-layer-position").html("("+position.left+", "+position.top+")");
						},
						stop: function() {
							var position = jQuery(this).position();
							position.left = parseInt(position.left, 10);
							position.top = parseInt(position.top, 10);
							jQuery(this).find(".ulp-layer-position").html("("+position.left+", "+position.top+")");
							var id = jQuery(this).attr("id");
							id = id.replace("ulp-preview-layer-", "");
							if (jQuery("[name=\'ulp_layer_id\']").val() == id) {
								jQuery("#ulp_layer_left").val(position.left);
								jQuery("#ulp_layer_top").val(position.top);
							}
							jQuery("#ulp_layer_"+id+"_left").val(position.left);
							jQuery("#ulp_layer_"+id+"_top").val(position.top);
							if (ulp_active_layer == id) {
								jQuery("[name=\'ulp_layer_left\']").val(position.left);
								jQuery("[name=\'ulp_layer_top\']").val(position.top);
							}
						}
					});
					jQuery(".ulp-preview-layer").resizable({
						grid: [grid_size, grid_size], 
						start: function() {
						},
						resize: function() {
							var width = parseInt(jQuery(this).outerWidth(), 10);
							var height = parseInt(jQuery(this).outerHeight(), 10);
							jQuery(this).find(".ulp-layer-size").html(""+width+" x "+height+"");
							jQuery(this).find(".ulp-preview-checkbox").css({"font-size": Math.min(parseInt(jQuery(this).height(), 10), parseInt(jQuery(this).width(), 10))+"px", "line-height": Math.floor(0.81*parseInt(jQuery(this).height(), 10))+"px"});
						},
						stop: function() {
							var width = parseInt(jQuery(this).outerWidth(), 10);
							var height = parseInt(jQuery(this).outerHeight(), 10);
							jQuery(this).find(".ulp-layer-size").html(""+width+" x "+height+"");
							var id = jQuery(this).attr("id");
							id = id.replace("ulp-preview-layer-", "");
							if (jQuery("[name=\'ulp_layer_id\']").val() == id) {
								jQuery("#ulp_layer_width").val(width);
								jQuery("#ulp_layer_height").val(height);
							}
							jQuery("#ulp_layer_"+id+"_width").val(width);
							jQuery("#ulp_layer_"+id+"_height").val(height);
							if (ulp_active_layer == id) {
								jQuery("[name=\'ulp_layer_width\']").val(width);
								jQuery("[name=\'ulp_layer_height\']").val(height);
							}
						}
					});
				}
				var ulp_keyuprefreshtimer;
				jQuery(document).ready(function(){
					ulp_build_preview();
					jQuery(".ulp-color").wpColorPicker({
						change: function(event, ui) {
							if (!ulp_updating_layer_details) {
								console.log("SHIT!");
								setTimeout(function(){ulp_build_preview();}, 300);
							}
						},
						clear: function() {
							if (!ulp_updating_layer_details) {
								ulp_build_preview();
							}
						}
					});
					jQuery("input, select, textarea").bind("change", function() {
						clearTimeout(ulp_keyuprefreshtimer);
						ulp_build_preview();
					});
					jQuery(\'input[type="checkbox"]\').bind("click", function() {
						ulp_build_preview();
					});
					jQuery("input, select, textarea").bind("keyup", function() {
						clearTimeout(ulp_keyuprefreshtimer);
						ulp_keyuprefreshtimer = setTimeout(function(){ulp_build_preview();}, 1000);
					});
					jQuery("#ulp_layer_title").bind("keyup", function() {
						var title = jQuery("#ulp_layer_title").val();
						if (title == "") title = "No title...";
						jQuery("#ulp-layer-"+ulp_active_layer+" label").html(ulp_escape_html(title));
					});
					jQuery("#ulp_layer_content").bind("keyup", function() {
						var content = jQuery("#ulp_layer_content").val();
						if (content == "") content = "No content...";
						jQuery("#ulp-layer-"+ulp_active_layer+" span").html(ulp_escape_html(content));
					});
					jQuery("#ulp-layers").draggable({ containment: "parent" });
					jQuery("#ulp-layers-list").sortable({
						stop: function() {
							ulp_build_preview();
						}
					});
					jQuery(".ulp-layer-action-delete").click(function(event){
						event.stopPropagation();
						ulp_neo_delete_layer(this);
						return false;
					});
					jQuery(".ulp-layer-action-copy").click(function(event){
						event.stopPropagation();
						ulp_neo_copy_layer(this);
						return false;
					});
					jQuery("#ulp-layers-list li").click(function(){
						var layer_id = jQuery(this).attr("id");
						layer_id = layer_id.replace("ulp-layer-", "");
						ulp_neo_edit_layer(layer_id, true);
					});
					jQuery("#ulp-popup-editor-tabs a").click(function(){
						if (jQuery(this).hasClass("ulp-nav-tab-active")) {
						} else {
							ulp_neo_hide_layer_details();
							var active_tab = jQuery(".ulp-tab-active").attr("href");
							jQuery(".ulp-tab-active").removeClass("ulp-tab-active");
							var tab = jQuery(this).attr("href");
							jQuery(this).addClass("ulp-tab-active");
							jQuery(active_tab).fadeOut(300, function(){
								jQuery(tab).fadeIn(300);
							});
						}
						return false;
					});
					jQuery(".ulp-helper-overlay").click(function(){ulp_helper_close();});
					jQuery(".ulp-helper2-overlay").click(function(){ulp_helper2_close();});
					jQuery(".ulp-helper3-overlay").click(function(){ulp_helper3_close();});
					jQuery(".ulp-helper2-link-button-item").click(function(){
						jQuery(".ulp-helper2-link-button-item-selected").removeClass("ulp-helper2-link-button-item-selected");
						jQuery(this).addClass("ulp-helper2-link-button-item-selected");
						color = jQuery(this).find("a").prop("class");
						color = color.replace("ulp-link-button-","").replace("ulp-link-button","").trim();
						jQuery("#ulp-helper2-linked-button-color").val(color);
					});
				});
			</script>
		</div>
		<div class="ulp-helper-overlay" id="ulp-helper-overlay"></div>
		<div class="ulp-helper-window" id="ulp-helper-add-layer-window">
			<div class="ulp-helper-window-title">
				<a href="#" onclick="return ulp_helper_close();"><i class="fas fa-times"></i></a>
				'.__('Select the content type of the layer', 'ulp').'
			</div>
			<div class="ulp-helper-window-content">';
		foreach($this->helper_add_layer_items as $group_id => $group) {
			echo '
				<h3>'.$group['label'].'</h3>
				<div id="ulp-helper-group-'.$group_id.'">';
			foreach ($group['items'] as $key => $value) {
				echo '
					<div class="ulp-helper-add-layer-item" id="ulp-helper-add-layer-item-'.$key.'"'.(array_key_exists('unique', $value) ? ' data-unique="'.$value['unique'].'"' : '').' data-item="'.$key.'" onclick="ulp_helper_add_layer_process(\''.$key.'\');">
						<i class="'.$value['icon'].'"></i>
						<label>'.$value['label'].'</label>
						<span>'.$value['comment'].'</span>
					</div>';
			}
			echo '
				</div>';
		}
		echo '
			</div>
		</div>
		<div class="ulp-helper3-overlay" id="ulp-helper3-overlay"></div>
		<div class="ulp-helper3-window" id="ulp-helper-window-message" style="width: 480px; height: auto;">
			<div class="ulp-helper-window-title">
				<a href="#" onclick="return ulp_helper3_close();"><i class="fas fa-times"></i></a>
				'.__('Message', 'ulp').'
			</div>
			<div class="ulp-helper-window-content" style="height: auto;">
				<div id="ulp-helper3-message"></div>
				<div class="ulp-helper-center">
					<a class="ulp-helper-button" href="#" onclick="return ulp_helper3_close();">'.__('Close', 'ulp').'</a>
				</div>
			</div>
		</div>
		<div class="ulp-helper2-overlay" id="ulp-helper2-overlay"></div>
		<div class="ulp-helper2-window" id="ulp-helper-window-label">
			<div class="ulp-helper-window-title">
				<a href="#" onclick="return ulp_helper2_close();"><i class="fas fa-times"></i></a>
				'.$this->helper_add_layer_items['general']['items']['label']['label'].'
			</div>
			<div class="ulp-helper-window-content">
				<div class="ulp-helper-singleline">
					<label>'.__('Text', 'ulp').':</label>
					<div>
						<input type="text" id="ulp-helper2-label-label" value="" class="ulp-layer-input-wide" placeholder="Enter the text...">
						<span>'.__('Drop your text label into this field.', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-singleline">
					<label>'.__('URL', 'ulp').':</label>
					<div>
						<input type="text" id="ulp-helper2-label-url" value="" class="ulp-layer-input-wide" placeholder="Enter the URL...">
						<span>'.__('Drop URL, if you want to hyperlink the text. Leave empty if not required.', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-singleline">
					<label></label>
					<div>
						<input type="checkbox" id="ulp-helper2-label-inherited"> '.__('Make whole layer clickable', 'ulp').'
						<br /><span>'.__('Make whole layer clickable, not only text.', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-right">
					<a class="ulp-helper-button" href="#" onclick="return ulp_helper_create_label();">'.__('Create Layer', 'ulp').'</a>
				</div>
			</div>
		</div>
		<div class="ulp-helper2-window" id="ulp-helper-window-youtube">
			<div class="ulp-helper-window-title">
				<a href="#" onclick="return ulp_helper2_close();"><i class="fas fa-times"></i></a>
				'.$this->helper_add_layer_items['video']['items']['youtube']['label'].'
			</div>
			<div class="ulp-helper-window-content">
				<div class="ulp-helper-singleline">
					<label>'.__('Code', 'ulp').':</label>
					<div>
						<textarea id="ulp-helper2-youtube-code"></textarea>
						<span>'.__('Drop YouTube video URL or embed code into this field.', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-right">
					<a class="ulp-helper-button" href="#" onclick="return ulp_helper_create_youtube();">'.__('Create Layer', 'ulp').'</a>
				</div>
			</div>
		</div>
		<div class="ulp-helper2-window" id="ulp-helper-window-vimeo">
			<div class="ulp-helper-window-title">
				<a href="#" onclick="return ulp_helper2_close();"><i class="fas fa-times"></i></a>
				'.$this->helper_add_layer_items['video']['items']['vimeo']['label'].'
			</div>
			<div class="ulp-helper-window-content">
				<div class="ulp-helper-singleline">
					<label>'.__('Code', 'ulp').':</label>
					<div>
						<textarea id="ulp-helper2-vimeo-code"></textarea>
						<span>'.__('Drop Vimeo video URL or embed code into this field.', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-right">
					<a class="ulp-helper-button" href="#" onclick="return ulp_helper_create_vimeo();">'.__('Create Layer', 'ulp').'</a>
				</div>
			</div>
		</div>
		<div class="ulp-helper2-window" id="ulp-helper-window-html">
			<div class="ulp-helper-window-title">
				<a href="#" onclick="return ulp_helper2_close();"><i class="fas fa-times"></i></a>
				'.$this->helper_add_layer_items['general']['items']['html']['label'].'
			</div>
			<div class="ulp-helper-window-content">
				<div class="ulp-helper-singleline">
					<label>'.__('HTML-code', 'ulp').':</label>
					<div>
						<textarea id="ulp-helper2-html-code"></textarea>
						<span>'.__('Drop your HTML-code into this field. JavaScript is not allowed.', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-right">
					<a class="ulp-helper-button" href="#" onclick="return ulp_helper_create_html();">'.__('Create Layer', 'ulp').'</a>
				</div>
			</div>
		</div>
		<div class="ulp-helper2-window" id="ulp-helper-window-linked-button">
			<div class="ulp-helper-window-title">
				<a href="#" onclick="return ulp_helper2_close();"><i class="fas fa-times"></i></a>
				'.$this->helper_add_layer_items['general']['items']['linked-button']['label'].'
			</div>
			<div class="ulp-helper-window-content">
				<div class="ulp-helper-singleline">
					<label>'.__('Label', 'ulp').':</label>
					<div>
						<input type="text" id="ulp-helper2-linked-button-label" value="" class="ulp-layer-input-wide" placeholder="Enter the label...">
						<span>'.__('Drop your button label into this field.', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-singleline">
					<label>'.__('URL', 'ulp').':</label>
					<div>
						<input type="text" id="ulp-helper2-linked-button-url" value="" class="ulp-layer-input-wide" placeholder="Enter the URL...">
						<span>'.__('Drop your URL.', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-singleline">
					<label>'.__('Color', 'ulp').':</label>
					<div>
						<div class="ulp-helper2-link-button-item"><a href="#" class="ulp-link-button ulp-link-button-red">Button</a><div class="ulp-input-cover"></div></div>
						<div class="ulp-helper2-link-button-item"><a href="#" class="ulp-link-button ulp-link-button-blue">Button</a><div class="ulp-input-cover"></div></div>
						<div class="ulp-helper2-link-button-item"><a href="#" class="ulp-link-button ulp-link-button-green">Button</a><div class="ulp-input-cover"></div></div>
						<div class="ulp-helper2-link-button-item"><a href="#" class="ulp-link-button ulp-link-button-yellow">Button</a><div class="ulp-input-cover"></div></div>
						<div class="ulp-helper2-link-button-item"><a href="#" class="ulp-link-button ulp-link-button-orange">Button</a><div class="ulp-input-cover"></div></div>
						<div class="ulp-helper2-link-button-item"><a href="#" class="ulp-link-button ulp-link-button-pink">Button</a><div class="ulp-input-cover"></div></div>
						<div class="ulp-helper2-link-button-item"><a href="#" class="ulp-link-button ulp-link-button-black">Button</a><div class="ulp-input-cover"></div></div>
						<input type="hidden" id="ulp-helper2-linked-button-color" value="">
					</div>
				</div>
				<div class="ulp-helper-right">
					<a class="ulp-helper-button" href="#" onclick="return ulp_helper_create_linkedbutton();">'.__('Create Layer', 'ulp').'</a>
				</div>
			</div>
		</div>
		<div class="ulp-helper2-window" id="ulp-helper-window-image">
			<div class="ulp-helper-window-title">
				<a href="#" onclick="return ulp_helper2_close();"><i class="fas fa-times"></i></a>
				'.$this->helper_add_layer_items['general']['items']['image']['label'].'
			</div>
			<div class="ulp-helper-window-content">
				<div class="ulp-helper-singleline">
					<label>'.__('Image URL', 'ulp').':</label>
					<div>
						<input type="text" id="ulp-helper2-image-url" value="" class="ulp-layer-input-wide" placeholder="Enter the image URL...">
						<a class="ulp-helper-button ulp-helper-media-library-button" href="#" onclick="return ulp_helper_media_library_image(\'ulp-helper2-image-url\');">'.__('Media Library', 'ulp').'</a><br />
						<span>'.__('Drop your image URL into this field.', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-singleline">
					<label>'.__('URL', 'ulp').':</label>
					<div>
						<input type="text" id="ulp-helper2-image-url2" value="" class="ulp-layer-input-wide" placeholder="Enter the URL...">
						<span>'.__('Drop URL, if you want to hyperlink the image. Leave empty if not required.', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-singleline">
					<label>'.__('Title', 'ulp').':</label>
					<div>
						<input type="text" id="ulp-helper2-image-title" value="" class="ulp-layer-input-wide" placeholder="Enter the title...">
						<span>'.__('Enter the hyperlink title. Leave empty if not required.', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-singleline">
					<label>'.__('Method', 'ulp').':</label>
					<div>
						<input type="radio" checked="checked" name="ulp-helper2-image-type" id="ulp-helper2-image-type-img"> '.__('Insert as <code>&lt;IMG&gt;</code> tag', 'ulp').'<br />
						<input type="radio" name="ulp-helper2-image-type" id="ulp-helper2-image-type-bg"> '.__('Insert as background', 'ulp').'<br />
						<span>'.__('Specify how to insert the image.', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-right">
					<a class="ulp-helper-button" href="#" onclick="return ulp_helper_create_image();">'.__('Create Layer', 'ulp').'</a>
				</div>
			</div>
		</div>
		<div class="ulp-helper2-window" id="ulp-helper-window-close">
			<div class="ulp-helper-window-title">
				<a href="#" onclick="return ulp_helper2_close();"><i class="fas fa-times"></i></a>
				'.$this->helper_add_layer_items['general']['items']['close']['label'].'
			</div>
			<div class="ulp-helper-window-content">
				<div class="ulp-helper-singleline">
					<label>'.__('Close Icon', 'ulp').':</label>
					<div>
						<select id="ulp-helper2-close-type" class="ulp-layer-input-wide" onchange="ulp_helper_change_close_type();">
							<option value="">Unicode Symbol "×"</option>
							<option value="image">Image</option>
							'.($ulp->options['fa_enable'] == 'on' ? '<option value="icon">FontAwesome Icon</option>' : '').'
						</select>
						<span>'.__('Select the type of close icon', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-singleline ulp-helper2-close-types" id="ulp-helper2-close-type-image" style="display: none;">
					<label></label>
					<div>
						<input type="text" id="ulp-helper2-close-image" value="" class="ulp-layer-input-wide" placeholder="Enter the image URL...">
						<a class="ulp-helper-button ulp-helper-media-library-button" href="#" onclick="return ulp_helper_media_library_image(\'ulp-helper2-close-image\');">'.__('Media Library', 'ulp').'</a><br />
						<span>'.__('Drop your close icon image URL into this field.', 'ulp').'</span>
					</div>
				</div>';
			if ($ulp->options['fa_enable'] == 'on') {
				echo '
				<div class="ulp-helper-singleline ulp-helper2-close-types" id="ulp-helper2-close-type-icon" style="display: none;">
					<label></label>
					<div id="ulp-helper2-close-icon-set">
						'.($ulp->options['fa_solid_enable'] == 'on' ? '<span class="ulp-helper-icon" title="fa-close" onclick="ulp_helper_seticon(this, \'ulp-helper2-close-icon\');"><i class="fas fa-times"></i></span>' : '').'
						'.($ulp->options['fa_regular_enable'] == 'on' ? '<span class="ulp-helper-icon" title="fa-times-circle" onclick="ulp_helper_seticon(this, \'ulp-helper2-close-icon\');"><i class="far fa-times-circle"></i></span>' : '').'
						'.($ulp->options['fa_solid_enable'] == 'on' ? '<span class="ulp-helper-icon" title="fa-times-circle" onclick="ulp_helper_seticon(this, \'ulp-helper2-close-icon\');"><i class="fas fa-times-circle"></i></span>' : '').'
						<br /><span>'.__('Select desired close icon.', 'ulp').'</span>
					</div>
					<input type="hidden" id="ulp-helper2-close-icon" value="">
				</div>';
			}
			echo '
				<div class="ulp-helper-singleline">
					<label>'.__('Action', 'ulp').':</label>
					<div>
						<input type="radio" checked="checked" name="ulp-helper2-close-action" id="ulp-helper2-close-action-close"> '.__('Close popup', 'ulp').'<br />
						<input type="radio" name="ulp-helper2-close-action" id="ulp-helper2-close-action-forever"> '.__('Close popup and set cookie (close forever)', 'ulp').'<br />
						<span>'.__('Specify the action of closing icon.', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-right">
					<a class="ulp-helper-button" href="#" onclick="return ulp_helper_create_close();">'.__('Create Layer', 'ulp').'</a>
				</div>
			</div>
		</div>
		<div class="ulp-helper2-window" id="ulp-helper-window-bullet">
			<div class="ulp-helper-window-title">
				<a href="#" onclick="return ulp_helper2_close();"><i class="fas fa-times"></i></a>
				'.$this->helper_add_layer_items['general']['items']['bullet']['label'].'
			</div>
			<div class="ulp-helper-window-content">
				<div class="ulp-helper-singleline">
					<label>'.__('Items', 'ulp').':</label>
					<div>
						<textarea id="ulp-helper2-bullet-items"></textarea>
						<span>'.__('Drop your items here. One item per line!.', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-singleline">
					<label>'.__('Bullet', 'ulp').':</label>
					<div>
						<select id="ulp-helper2-bullet-type" class="ulp-layer-input-wide" onchange="ulp_helper_change_bullet_type();">
							<option value="">Unicode Symbol "●"</option>
							'.($ulp->options['fa_enable'] == 'on' ? '<option value="icon">FontAwesome Icon</option>' : '').'
						</select>
						<span>'.__('Select the type of bullet', 'ulp').'</span>
					</div>
				</div>';
			if ($ulp->options['fa_enable'] == 'on') {
				echo '
				<div class="ulp-helper-singleline ulp-helper2-bullet-types" id="ulp-helper2-bullet-type-icon" style="display: none;">
					<label></label>
					<div id="ulp-helper2-bullet-icon-set">';
				if ($ulp->options['fa_solid_enable'] == 'on') {
					foreach ($ulp->fa_solid as $value) {
						echo '<span class="ulp-helper-icon" title="'.$value.'" onclick="ulp_helper_seticon(this, \'ulp-helper2-bullet-icon\');"><i class="fas fa-'.$value.'"></i></span>';
					}
				}
				if ($ulp->options['fa_regular_enable'] == 'on') {
					foreach ($ulp->fa_regular as $value) {
						echo '<span class="ulp-helper-icon" title="'.$value.'" onclick="ulp_helper_seticon(this, \'ulp-helper2-bullet-icon\');"><i class="far fa-'.$value.'"></i></span>';
					}
				}
				if ($ulp->options['fa_brands_enable'] == 'on') {
					foreach ($ulp->fa_brands as $value) {
						echo '<span class="ulp-helper-icon" title="'.$value.'" onclick="ulp_helper_seticon(this, \'ulp-helper2-bullet-icon\');"><i class="fab fa-'.$value.'"></i></span>';
					}
				}
				echo '
						<br /><span>'.__('Select desired bullet icon.', 'ulp').'</span>
						<input type="hidden" id="ulp-helper2-bullet-icon" value="">
					</div>
					<input type="hidden" id="ulp-helper2-close-icon" value="">
				</div>';
			}
			echo '
				<div class="ulp-helper-singleline">
					<label>'.__('Color', 'ulp').':</label>
					<div id="ulp-helper2-bullet-color-set">
						<span class="ulp-helper2-color-item" onclick="return ulp_helper_setcolor(this, \'ulp-helper2-bullet-color\');" data-color="red"><span class="ulp-helper2-color-box ulp-helper2-color-box-red"></span></span>
						<span class="ulp-helper2-color-item" onclick="return ulp_helper_setcolor(this, \'ulp-helper2-bullet-color\');" data-color="blue"><span class="ulp-helper2-color-box ulp-helper2-color-box-blue"></span></span>
						<span class="ulp-helper2-color-item" onclick="return ulp_helper_setcolor(this, \'ulp-helper2-bullet-color\');" data-color="green"><span class="ulp-helper2-color-box ulp-helper2-color-box-green"></span></span>
						<span class="ulp-helper2-color-item" onclick="return ulp_helper_setcolor(this, \'ulp-helper2-bullet-color\');" data-color="yellow"><span class="ulp-helper2-color-box ulp-helper2-color-box-yellow"></span></span>
						<span class="ulp-helper2-color-item" onclick="return ulp_helper_setcolor(this, \'ulp-helper2-bullet-color\');" data-color="orange"><span class="ulp-helper2-color-box ulp-helper2-color-box-orange"></span></span>
						<span class="ulp-helper2-color-item" onclick="return ulp_helper_setcolor(this, \'ulp-helper2-bullet-color\');" data-color="pink"><span class="ulp-helper2-color-box ulp-helper2-color-box-pink"></span></span>
						<span class="ulp-helper2-color-item" onclick="return ulp_helper_setcolor(this, \'ulp-helper2-bullet-color\');" data-color="black"><span class="ulp-helper2-color-box ulp-helper2-color-box-black"></span></span>
						<span class="ulp-helper2-color-item" onclick="return ulp_helper_setcolor(this, \'ulp-helper2-bullet-color\');" data-color="white"><span class="ulp-helper2-color-box ulp-helper2-color-box-white"></span></span>
						<br /><span>'.__('Select desired bullet color.', 'ulp').'</span>
						<input type="hidden" id="ulp-helper2-bullet-color" value="">
					</div>
				</div>
				<div class="ulp-helper-right">
					<a class="ulp-helper-button" href="#" onclick="return ulp_helper_create_bullet();">'.__('Create Layer', 'ulp').'</a>
				</div>
			</div>
		</div>';
		if ($ulp->options['fa_enable'] == 'on') {
			echo '
		<div class="ulp-helper2-window" id="ulp-helper-window-icon">
			<div class="ulp-helper-window-title">
				<a href="#" onclick="return ulp_helper2_close();"><i class="fas fa-times"></i></a>
				'.$this->helper_add_layer_items['general']['items']['icon']['label'].'
			</div>
			<div class="ulp-helper-window-content">
				<div class="ulp-helper-singleline">
					<label>'.__('Icon', 'ulp').':</label>
					<div id="ulp-helper2-icon-icon-set">';
			if ($ulp->options['fa_solid_enable'] == 'on') {
				foreach ($ulp->fa_solid as $value) {
					echo '<span class="ulp-helper-icon" title="'.$value.'" onclick="ulp_helper_seticon(this, \'ulp-helper2-icon-icon\');"><i class="fas fa-'.$value.'"></i></span>';
				}
			}
			if ($ulp->options['fa_regular_enable'] == 'on') {
				foreach ($ulp->fa_regular as $value) {
					echo '<span class="ulp-helper-icon" title="'.$value.'" onclick="ulp_helper_seticon(this, \'ulp-helper2-icon-icon\');"><i class="far fa-'.$value.'"></i></span>';
				}
			}
			if ($ulp->options['fa_brands_enable'] == 'on') {
				foreach ($ulp->fa_brands as $value) {
					echo '<span class="ulp-helper-icon" title="'.$value.'" onclick="ulp_helper_seticon(this, \'ulp-helper2-icon-icon\');"><i class="fab fa-'.$value.'"></i></span>';
				}
			}
			echo '
					</div>
					<input type="hidden" id="ulp-helper2-icon-icon" value="">
				</div>
				<div class="ulp-helper-singleline">
					<label>'.__('URL', 'ulp').':</label>
					<div>
						<input type="text" id="ulp-helper2-icon-url" value="" class="ulp-layer-input-wide" placeholder="Enter the URL...">
						<span>'.__('Drop URL, if you want to hyperlink the icon. Leave empty if not required.', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-singleline">
					<label>'.__('Title', 'ulp').':</label>
					<div>
						<input type="text" id="ulp-helper2-icon-title" value="" class="ulp-layer-input-wide" placeholder="Enter the title...">
						<span>'.__('Enter the hyperlink title. Leave empty if not required.', 'ulp').'</span>
					</div>
				</div>
				<div class="ulp-helper-right">
					<a class="ulp-helper-button" href="#" onclick="return ulp_helper_create_icon();">'.__('Create Layer', 'ulp').'</a>
				</div>
			</div>
		</div>';
		}
		do_action('ulp_helper2_window');
	}

	function admin_campaigns() {
		global $wpdb, $ulp;

		if (isset($_GET["s"])) $search_query = trim(stripslashes($_GET["s"]));
		else $search_query = "";
		$tmp = $wpdb->get_row("SELECT COUNT(*) AS total FROM ".$wpdb->prefix."ulp_campaigns WHERE deleted = '0'".((strlen($search_query) > 0) ? " AND title LIKE '%".addslashes($search_query)."%'" : ""), ARRAY_A);
		$total = $tmp["total"];
		$totalpages = ceil($total/ULP_RECORDS_PER_PAGE);
		if ($totalpages == 0) $totalpages = 1;
		if (isset($_GET["p"])) $page = intval($_GET["p"]);
		else $page = 1;
		if ($page < 1 || $page > $totalpages) $page = 1;
		$switcher = $ulp->page_switcher(admin_url('admin.php').'?page=ulp-campaigns'.((strlen($search_query) > 0) ? '&s='.rawurlencode($search_query) : ''), $page, $totalpages);

		if (isset($_GET['o'])) {
			$sort = $_GET['o'];
			if (in_array($sort, $ulp->sort_methods)) {
				if ($sort != $ulp->options['campaigns_sort']) {
					update_option('ulp_campaigns_sort', $sort);
					$ulp->options['campaigns_sort'] = $sort;
				}
			} else $sort = $ulp->options['campaigns_sort'];
		} else $sort = $ulp->options['campaigns_sort'];
		$orderby = 't1.created DESC';
		switch ($sort) {
			case 'title-az':
				$orderby = 't1.title ASC';
				break;
			case 'title-za':
				$orderby = 't1.title DESC';
				break;
			case 'date-az':
				$orderby = 't1.created ASC';
				break;
			default:
				$orderby = 't1.created DESC';
				break;
		}
		
		$sql = "SELECT t1.*, t2.popups, t2.clicks, t2.impressions FROM ".$wpdb->prefix."ulp_campaigns t1 LEFT JOIN (SELECT COUNT(*) AS popups, SUM(tt1.clicks) AS clicks, SUM(tt1.impressions) AS impressions, tt1.campaign_id FROM ".$wpdb->prefix."ulp_campaign_items tt1 JOIN ".$wpdb->prefix."ulp_popups tt2 ON tt2.id = tt1.popup_id WHERE tt1.deleted = '0' AND tt2.deleted = '0' GROUP BY tt1.campaign_id) t2 ON t2.campaign_id = t1.id WHERE t1.deleted = '0'".((strlen($search_query) > 0) ? " AND t1.title LIKE '%".addslashes($search_query)."%'" : "")." ORDER BY ".$orderby." LIMIT ".(($page-1)*ULP_RECORDS_PER_PAGE).", ".ULP_RECORDS_PER_PAGE;
		$rows = $wpdb->get_results($sql, ARRAY_A);
		if (!empty($ulp->error)) $message = "<div class='error'><p>".$ulp->error."</p></div>";
		else if (!empty($ulp->info)) $message = "<div class='updated'><p>".$ulp->info."</p></div>";
		else $message = '';

		echo '
			<div class="wrap admin_ulp_wrap">
				<h2>'.__('Layered Popups - A/B Campaigns', 'ulp').' <a href="https://layeredpopups.com/documentation/" class="add-new-h2" target="_blank">Online Documentation</a></h2>
				'.$message.'
				<div class="ulp-top-forms">
					<div class="ulp-top-form-left">
						<form action="'.admin_url('admin.php').'" method="get" class="uap-filter-form ulp-filter-form">
						<input type="hidden" name="page" value="ulp-campaigns" />
						<label>'.__('Search:', 'ulp').'</label>
						<input type="text" name="s" class="form-control" style="width: 200px;" value="'.esc_html($search_query).'">
						<input type="submit" class="button-secondary action" value="'.__('Search', 'ulp').'" />
						'.((strlen($search_query) > 0) ? '<input type="button" class="button-secondary action" value="'.__('Reset search results', 'ulp').'" onclick="window.location.href=\''.admin_url('admin.php').'?page=ulp-campaigns\';" />' : '').'
						</form>
					</div>
					<div class="ulp-top-form-right">
						<form id="ulp-sorting-form" action="'.admin_url('admin.php').'" method="get" class="uap-filter-form ulp-filter-form">
						<input type="hidden" name="page" value="ulp-campaigns" />
						<label>'.__('Sort:', 'ulp').'</label>
						'.((strlen($search_query) > 0) ? '<input type="hidden" name="s" value="'.esc_html($search_query).'">' : '').'
						'.(($page > 1) ? '<input type="hidden" name="p" value="'.esc_html($page).'">' : '').'
						<select name="o" onchange="jQuery(\'#ulp-sorting-form\').submit();" class="form-control" style="width: 150px;">
							<option value="title-az"'.($sort == 'title-az' ? ' selected="selected"' : '').'>'.__('Alphabetically', 'ulp').' ▲</option>
							<option value="title-za"'.($sort == 'title-za' ? ' selected="selected"' : '').'>'.__('Alphabetically', 'ulp').' ▼</option>
							<option value="date-az"'.($sort == 'date-az' ? ' selected="selected"' : '').'>'.__('Created', 'ulp').' ▲</option>
							<option value="date-za"'.($sort == 'date-za' ? ' selected="selected"' : '').'>'.__('Created', 'ulp').' ▼</option>
						</select>
						</form>
					</div>
				</div>
				<div class="ulp_buttons"><a href="'.admin_url('admin.php').'?page=ulp-add-campaign" class="ulp-button ulp-button-small"><i class="fas fa-plus"></i><label>Create New Campaign</label></a></div>
				<div class="ulp_pageswitcher">'.$switcher.'</div>
				<table class="ulp_records">
				<tr>
					<th>'.__('Title', 'ulp').'</th>
					<th style="width: 180px;">'.__('ID', 'ulp').'</th>
					<th style="width: 80px;">'.__('Popups', 'ulp').'</th>
					<th style="width: 80px;">'.__('Submits', 'ulp').'</th>
					'.($ulp->ext_options['count_impressions'] == 'on' ? '<th style="width: 80px;">'.__('Impressions', 'ulp').'</th>' : '').'
					<th style="width: 120px;"></th>
				</tr>';
		if (sizeof($rows) > 0) {
			foreach ($rows as $row) {
				echo '
				<tr>
					<td>'.($row['blocked'] == 1 ? '<span class="ulp-badge ulp-badge-blocked">Blocked</span> ' : '').esc_html($row['title']).'</td>
					<td><input type="text" value="'.$row['str_id'].'" readonly="readonly" style="width: 100%;" onclick="this.focus();this.select();"></td>
					<td style="text-align: right;">'.intval($row['popups']).'</td>
					<td style="text-align: right;">'.intval($row['clicks']).'</td>
					'.($ulp->ext_options['count_impressions'] == 'on' ? '<td style="text-align: right;">'.intval($row['impressions']).'</td>' : '').'
					<td class="ulp-popups-actions">
						<a href="'.admin_url('admin.php').'?page=ulp-add-campaign&id='.$row['id'].'" title="'.__('Edit campaign details', 'ulp').'"><i class="fas fa-pencil-alt"></i></a>
						<a onclick="return ulp_admin_popup_open(this);" data-id="'.esc_html($row['id']).'" data-title="'.esc_html__('Statistics', 'ulp').'" data-subtitle="'.esc_html($row['title']).'" data-action="ulp-campaigns-stats" href="#" title="'.__('Statistics', 'ulp').': '.esc_html($row['title']).'"><i class="fas fa-chart-bar"></i></a>
						'.($row['blocked'] == 1 ? '<a href="'.admin_url('admin.php').'?action=ulp-campaigns-unblock&id='.$row['id'].'&ac='.$ulp->random_string().'" title="'.__('Unblock campaign', 'ulp').'"><i class="fas fa-plus-square"></i></a>' : '<a href="'.admin_url('admin.php').'?action=ulp-campaigns-block&id='.$row['id'].'&ac='.$ulp->random_string().'" title="'.__('Block campaign', 'ulp').'"><i class="fas fa-minus-square"></i></a>').'
						<a href="'.admin_url('admin.php').'?action=ulp-campaigns-drop-counters&id='.$row['id'].'&ac='.$ulp->random_string().'" title="'.__('Drop counters', 'ulp').'" onclick="return ulp_confirm_redirect(this, \'reset-stats\');"><i class="fas fa-eraser"></i></a>
						<a href="'.admin_url('admin.php').'?action=ulp-campaigns-delete&id='.$row['id'].'&ac='.$ulp->random_string().'" title="'.__('Delete campaign', 'ulp').'" onclick="return ulp_confirm_redirect(this, \'delete\');"><i class="fas fa-trash-alt"></i></a>
					</td>
				</tr>';
			}
		} else {
			echo '
				<tr><td colspan="6" style="padding: 20px; text-align: center;">'.((strlen($search_query) > 0) ? __('No results found for', 'ulp').' "<strong>'.esc_html($search_query).'</strong>"' : __('List is empty.', 'ulp')).'</td></tr>';
		}
		echo '
				</table>
				<div class="ulp_buttons">
					<a href="'.admin_url('admin.php').'?page=ulp-add-campaign" class="ulp-button ulp-button-small"><i class="fas fa-plus"></i><label>Create New Campaign</label></a>
				</div>
				<div class="ulp_pageswitcher">'.$switcher.'</div>
			</div>
			<div class="ulp-admin-popup-overlay" id="ulp-admin-popup-overlay"></div>
			<div class="ulp-admin-popup" id="ulp-admin-popup">
				<div class="ulp-admin-popup-inner">
					<div class="ulp-admin-popup-title">
						<a href="#" title="'.esc_html__('Close', 'ulp').'" onclick="return ulp_admin_popup_close();"><i class="fas fa-times"></i></a>
						<h3><i class="fas fa-info-circle"></i> <label></label><span></span></h3>
					</div>
					<div class="ulp-admin-popup-content">
						<div class="ulp-admin-popup-content-form">
						</div>
					</div>
					<div class="ulp-admin-popup-loading"><i class="fas fa-spinner fa-spin"></i></div>
				</div>
			</div>
			<div id="ulp-global-message"></div>
			<script>jQuery(document).ready(function(){ulp_admin_popup_ready();});</script>';
		echo $ulp->admin_modal_html();
	}

	function admin_add_campaign() {
		global $wpdb, $ulp;

		$campaign_details = array();
		if (isset($_GET["id"]) && !empty($_GET["id"])) {
			$id = intval($_GET["id"]);
			$campaign_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_campaigns WHERE id = '".$id."' AND deleted = '0'", ARRAY_A);
		}
		
		$errors = true;
		if (!empty($ulp->error)) $message = "<div class='error'><p>".$ulp->error."</p></div>";
		else if (!empty($ulp->info)) $message = "<div class='updated'><p>".$ulp->info."</p></div>";
		else $message = '';
		
		echo '
		<div class="wrap ulp">
			<h2>'.(!empty($campaign_details) ? __('Layered Popups - Edit A/B Campaign', 'ulp') : __('Layered Popups - Create A/B Campaign', 'ulp')).' <a href="https://layeredpopups.com/documentation/" class="add-new-h2" target="_blank">Online Documentation</a></h2>
			'.$message.'
			<form class="ulp-campaign-form" enctype="multipart/form-data" method="post" style="margin: 0px" action="'.admin_url('admin.php').'">
			<div class="ulp-options" style="width: 100%; position: relative;">
				<h3>'.__('Campaign Details', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Title', 'ulp').':</th>
						<td>
							<input type="text" name="ulp_title" value="'.(!empty($campaign_details) ? esc_html($campaign_details['title']) : __('Default A/B Campaign', 'ulp')).'" class="widefat" placeholder="Enter the campaign title...">
							<br /><em>'.__('Enter the campaign title. It is used for your reference.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Popups', 'ulp').':</th>
						<td>';
		if (!empty($campaign_details)) $sql = "SELECT t1.*, t2.id AS item_id FROM ".$wpdb->prefix."ulp_popups t1 LEFT JOIN ".$wpdb->prefix."ulp_campaign_items t2 ON t2.popup_id = t1.id AND t2.deleted = '0' AND t2.campaign_id = '".$campaign_details['id']."' WHERE t1.deleted = '0' ORDER BY t1.created DESC";
		else $sql = "SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY created DESC";
		$rows = $wpdb->get_results($sql, ARRAY_A);
		if (sizeof($rows) > 0) {
			foreach ($rows as $row) {
				echo '
							<input type="checkbox" name="ulp_popup_'.$row['id'].'"'.(isset($row['item_id']) && intval($row['item_id']) > 0 ? ' checked="checked"' : '').'> '.esc_html($row['title']).($row['blocked'] == 1 ? ' <span class="ulp-badge ulp-badge-blocked">'.__('Blocked', 'ulp').'</span>' : '').'<br />';
			}
			echo '
							<em>'.__('Select popups that you would like to include into campaign.', 'ulp').'</em>';

		} else {
			echo '
							'.__('Create at least one popup to start A/B Campaign.', 'ulp');
		}
							
		echo '
						</td>
					</tr>
				</table>
				<hr>
				<div class="ulp-button-container">
					<input type="hidden" name="action" value="ulp_save_campaign" />
					<input type="hidden" id="ulp-campaign-id" name="ulp_id" value="'.(!empty($campaign_details) ? $campaign_details['id'] : '0').'" />
					<a class="ulp-button" onclick="return ulp_save_campaign(this);"><i class="fas fa-check"></i><label>'.__('Save Campaign Details', 'ulp').'</label></a>
				</div>
				<div class="ulp-message"></div>
				<div id="ulp-overlay"></div>
			</div>
			</form>
			<div id="ulp-global-message"></div>
		</div>';
	}

	function admin_subscribers() {
		global $wpdb, $ulp;

		if (isset($_GET["s"])) $search_query = trim(stripslashes($_GET["s"]));
		else $search_query = "";
		
		if (isset($_GET["pid"])) $pid = intval(stripslashes($_GET["pid"]));
		else $pid = 0;
		$pids = $wpdb->get_results("SELECT DISTINCT t1.popup_id, t2.deleted, t2.title AS popup_title FROM ".$wpdb->prefix."ulp_subscribers t1 LEFT JOIN ".$wpdb->prefix."ulp_popups t2 ON t2.id = t1.popup_id WHERE t1.deleted = '0' ORDER BY t2.title ASC", ARRAY_A);
		
		$tmp = $wpdb->get_row("SELECT COUNT(*) AS total FROM ".$wpdb->prefix."ulp_subscribers WHERE deleted = '0'".((strlen($search_query) > 0) ? " AND (name LIKE '%".addslashes($search_query)."%' OR email LIKE '%".addslashes($search_query)."%')" : "").($pid > 0 ? " AND popup_id = '".$pid."'" : ""), ARRAY_A);
		$total = $tmp["total"];
		$totalpages = ceil($total/ULP_RECORDS_PER_PAGE);
		if ($totalpages == 0) $totalpages = 1;
		if (isset($_GET["p"])) $page = intval($_GET["p"]);
		else $page = 1;
		if ($page < 1 || $page > $totalpages) $page = 1;
		$switcher = $ulp->page_switcher(admin_url('admin.php').'?page=ulp-subscribers'.((strlen($search_query) > 0) ? '&s='.rawurlencode($search_query) : '').($pid > 0 ? '&pid='.$pid : ''), $page, $totalpages);

		$sql = "SELECT t1.*, t2.title AS popup_title FROM ".$wpdb->prefix."ulp_subscribers t1 LEFT JOIN ".$wpdb->prefix."ulp_popups t2 ON t2.id = t1.popup_id WHERE t1.deleted = '0'".((strlen($search_query) > 0) ? " AND (t1.name LIKE '%".addslashes($search_query)."%' OR t1.email LIKE '%".addslashes($search_query)."%')" : "").($pid > 0 ? " AND t1.popup_id = '".$pid."'" : "")." ORDER BY t1.created DESC LIMIT ".(($page-1)*ULP_RECORDS_PER_PAGE).", ".ULP_RECORDS_PER_PAGE;
		$rows = $wpdb->get_results($sql, ARRAY_A);
		if (!empty($ulp->error)) $message = "<div class='error'><p>".$ulp->error."</p></div>";
		else if (!empty($ulp->info)) $message = "<div class='updated'><p>".$ulp->info."</p></div>";
		else $message = '';

		echo '
			<div class="wrap admin_ulp_wrap">
				<h2>'.__('Layered Popups - Log', 'ulp').' <a href="https://layeredpopups.com/documentation/" class="add-new-h2" target="_blank">Online Documentation</a></h2>
				'.$message.'
				<div class="ulp-top-forms">
					<div class="ulp-top-form-left">
						<form action="'.admin_url('admin.php').'" method="get"  class="uap-filter-form ulp-filter-form">
						<input type="hidden" name="page" value="ulp-subscribers" />
						'.($pid > 0 ? '<input type="hidden" name="pid" value="'.$pid.'" />' : '').'
						<label>'.__('Search', 'ulp').':</label>
						<input type="text" name="s" class="form-control" style="width: 200px;" value="'.esc_html($search_query).'">
						<input type="submit" class="button-secondary action" value="'.__('Search', 'ulp').'" />
						'.((strlen($search_query) > 0) ? '<input type="button" class="button-secondary action" value="'.__('Reset search results', 'ulp').'" onclick="window.location.href=\''.admin_url('admin.php').'?page=ulp-subscribers'.($pid > 0 ? '&pid='.$pid : '').'\';" />' : '').'
						</form>
					</div>
					<div class="ulp-top-form-right">
						<form id="ulp-filter-form" action="'.admin_url('admin.php').'" method="get"  class="uap-filter-form ulp-filter-form">
						<input type="hidden" name="page" value="ulp-subscribers" />
						<label>'.__('Filter:', 'ulp').'</label>
						<select name="pid" class="form-control" style="width: 150px;" onchange="jQuery(\'#ulp-filter-form\').submit();">
							<option value="">'.__('All Popups', 'ulp').'</option>';
		foreach ($pids as $value) {
			echo '
							<option value="'.$value['popup_id'].'"'.($value['popup_id'] == $pid ? ' selected="selected"' : '').'>'.esc_html($value['popup_title']).($value['deleted'] == 1 ? ' [deleted]': '').'</option>';
		
		}
		echo '
						</select>
						</form>
					</div>
				</div>
				<div class="ulp_buttons"><a href="'.admin_url('admin.php').'?action=ulp-subscribers-csv'.((strlen($search_query) > 0) ? '&s='.rawurlencode($search_query) : '').($pid > 0 ? '&pid='.$pid : '').'&ac='.$ulp->random_string().'" class="ulp-button ulp-button-small"><i class="fas fa-download"></i><label>CSV Export</label></a></div>
				<div class="ulp_pageswitcher">'.$switcher.'</div>
				<table class="ulp_records">
				<tr>
					<th>'.__('Name', 'ulp').'</th>
					<th>'.__('E-mail', 'ulp').'</th>
					<th>'.__('Popup', 'ulp').'</th>
					<th style="width: 130px;">'.__('Created', 'ulp').'</th>
					<th style="width: 60px;"></th>
				</tr>';
		if (sizeof($rows) > 0) {
			foreach ($rows as $row) {
				echo '
				<tr>
					<td>'.(empty($row['name']) ? '-' : esc_html($row['name'])).'</td>
					<td>'.esc_html($row['email']).(array_key_exists($row['status'], $ulp->user_statuses) ? ' <span class="'.$ulp->user_statuses[$row['status']]['class'].'">'.$ulp->user_statuses[$row['status']]['label'].'</span>' : '').'</td>
					<td>'.esc_html($row['popup_title']).'</td>
					<td>'.date("Y-m-d H:i", $row['created']).'</td>
					<td class="ulp-popups-actions">
						<a onclick="return ulp_admin_popup_open(this);" data-id="'.esc_html($row['id']).'" data-title="'.esc_html__('Details', 'ulp').'" data-subtitle="'.esc_html($row['email']).'" data-action="ulp-subscribers-details" href="#" title="'.__('View details', 'ulp').': '.esc_html($row['email']).'"><i class="fas fa-list-alt"></i></a>
						<a href="'.admin_url('admin.php').'?action=ulp-subscribers-delete&id='.$row['id'].((strlen($search_query) > 0) ? '&s='.rawurlencode($search_query) : '').($pid > 0 ? '&pid='.$pid : '').'&ac='.$ulp->random_string().'" title="'.__('Delete record', 'ulp').'" onclick="return ulp_confirm_redirect(this, \'delete\');"><i class="fas fa-trash-alt"></i></a>
					</td>
				</tr>';
			}
		} else {
			echo '
				<tr><td colspan="5" style="padding: 20px; text-align: center;">'.((strlen($search_query) > 0) ? __('No results found for', 'ulp').' "<strong>'.esc_html($search_query).'</strong>"' : __('List is empty.', 'ulp')).'</td></tr>';
		}
		echo '
				</table>
				<div class="ulp_buttons">
					<a href="'.admin_url('admin.php').'?action=ulp-subscribers-delete-all'.((strlen($search_query) > 0) ? '&s='.rawurlencode($search_query) : '').($pid > 0 ? '&pid='.$pid : '').'&ac='.$ulp->random_string().'" onclick="return ulp_confirm_redirect(this, \'delete-all\');" class="ulp-button ulp-button-small"><i class="fas fa-trash-alt"></i><label>Delete All</label></a>
					<a href="'.admin_url('admin.php').'?action=ulp-subscribers-csv'.((strlen($search_query) > 0) ? '&s='.rawurlencode($search_query) : '').($pid > 0 ? '&pid='.$pid : '').'&ac='.$ulp->random_string().'" class="ulp-button ulp-button-small"><i class="fas fa-download"></i><label>CSV Export</label></a>
				</div>
				<div class="ulp_pageswitcher">'.$switcher.'</div>
			</div>
			<div class="ulp-admin-popup-overlay" id="ulp-admin-popup-overlay"></div>
			<div class="ulp-admin-popup" id="ulp-admin-popup">
				<div class="ulp-admin-popup-inner">
					<div class="ulp-admin-popup-title">
						<a href="#" title="'.esc_html__('Close', 'ulp').'" onclick="return ulp_admin_popup_close();"><i class="fas fa-times"></i></a>
						<h3><i class="fas fa-info-circle"></i> <label></label><span></span></h3>
					</div>
					<div class="ulp-admin-popup-content">
						<div class="ulp-admin-popup-content-form">
						</div>
					</div>
					<div class="ulp-admin-popup-loading"><i class="fas fa-spinner fa-spin"></i></div>
				</div>
			</div>
			<div id="ulp-global-message"></div>
			<script>jQuery(document).ready(function(){ulp_admin_popup_ready();});</script>';
		echo $ulp->admin_modal_html();
	}

	function admin_request_handler() {
		global $wpdb, $wp_header_to_desc, $ulp;
		if (!current_user_can('manage_options')) return;
		if (isset($_GET['ulp-post-method']) && in_array($_GET['ulp-post-method'], array('array','string'))) {
			update_option('ulp_post_method', $_GET['ulp-post-method']);
			$ulp->options['post_method'] = $_GET['ulp-post-method'];
		}
		if ($ulp->options['fa_enable'] != 'on') unset($this->helper_add_layer_items['general']['items']['icon']);
		$this->helper_add_layer_items = apply_filters('ulp_helper_add_layer_items', $this->helper_add_layer_items);
		$wp_header_to_desc[682] = __('Invalid Item Purchase Code!', 'ulp');
		$wp_header_to_desc[683] = __('Specified Item Purchase Code is already in use!', 'ulp');
		if (!empty($_GET['action'])) {
			switch($_GET['action']) {
				case 'ulp-copy':
					$id = intval($_GET["id"]);
					$popup_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE id = '".$id."' AND deleted = '0'", ARRAY_A);
					if (empty($popup_details)) {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp');
						exit;
					}
					$str_id = $ulp->random_string(16);
					$sql = "INSERT INTO ".$wpdb->prefix."ulp_popups (str_id, title, width, height, options, created, blocked, deleted) 
						VALUES (
						'".$str_id."', 
						'".esc_sql($popup_details['title'])."', 
						'".intval($popup_details['width'])."', 
						'".intval($popup_details['height'])."', 
						'".esc_sql($popup_details['options'])."', 
						'".time()."', '0', '0')";
					$wpdb->query($sql);
					$popup_id = $wpdb->insert_id;
					$layers = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_layers WHERE popup_id = '".$popup_details['id']."' AND deleted = '0'", ARRAY_A);
					if (sizeof($layers) > 0) {
						foreach ($layers as $layer) {
							$sql = "INSERT INTO ".$wpdb->prefix."ulp_layers (
								popup_id, title, content, zindex, details, created, deleted) VALUES (
								'".$popup_id."',
								'".esc_sql($layer['title'])."',
								'".esc_sql($layer['content'])."',
								'".esc_sql($layer['zindex'])."',
								'".esc_sql($layer['details'])."',
								'".time()."', '0')";
							$wpdb->query($sql);
						}
					}
					setcookie("ulp_info", __('Popup successfully <strong>duplicated</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
					header('Location: '.admin_url('admin.php').'?page=ulp');
					exit;
					break;
				case 'ulp-delete':
					$id = intval($_GET["id"]);
					$popup_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE id = '".$id."' AND deleted = '0'", ARRAY_A);
					if (intval($popup_details["id"]) == 0) {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp');
						exit;
					}
					$sql = "UPDATE ".$wpdb->prefix."ulp_popups SET deleted = '1' WHERE id = '".$id."'";
					if ($wpdb->query($sql) !== false) {
						setcookie("ulp_info", __('Popup successfully <strong>removed</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp');
						exit;
					} else {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp');
						exit;
					}
					exit;
					break;
				case 'ulp-drop-counters':
					$id = intval($_GET["id"]);
					$popup_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE id = '".$id."' AND deleted = '0'", ARRAY_A);
					if (intval($popup_details["id"]) == 0) {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp');
						exit;
					}
					$sql = "UPDATE ".$wpdb->prefix."ulp_popups SET clicks = '0', impressions = '0' WHERE id = '".$id."'";
					if ($wpdb->query($sql) !== false) {
						setcookie("ulp_info", __('Popup counters successfully <strong>cleared</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp');
						exit;
					} else {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp');
						exit;
					}
					exit;
					break;
				case 'ulp-block':
					$id = intval($_GET["id"]);
					$popup_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE id = '".$id."' AND deleted = '0'", ARRAY_A);
					if (intval($popup_details["id"]) == 0) {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp');
						exit;
					}
					$sql = "UPDATE ".$wpdb->prefix."ulp_popups SET blocked = '1' WHERE id = '".$id."'";
					if ($wpdb->query($sql) !== false) {
						setcookie("ulp_info", __('Popup successfully <strong>blocked</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp');
						exit;
					} else {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp');
						exit;
					}
					exit;
					break;
				case 'ulp-unblock':
					$id = intval($_GET["id"]);
					$popup_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE id = '".$id."' AND deleted = '0'", ARRAY_A);
					if (intval($popup_details["id"]) == 0) {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp');
						exit;
					}
					$sql = "UPDATE ".$wpdb->prefix."ulp_popups SET blocked = '0' WHERE id = '".$id."'";
					if ($wpdb->query($sql) !== false) {
						setcookie("ulp_info", __('Popup successfully <strong>unblocked</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp');
						exit;
					} else {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp');
						exit;
					}
					exit;
					break;
				case 'ulp-campaigns-delete':
					$id = intval($_GET["id"]);
					$campaign_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_campaigns WHERE id = '".$id."' AND deleted = '0'", ARRAY_A);
					if (intval($campaign_details["id"]) == 0) {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp-campaigns');
						exit;
					}
					$sql = "UPDATE ".$wpdb->prefix."ulp_campaigns SET deleted = '1' WHERE id = '".$id."'";
					if ($wpdb->query($sql) !== false) {
						setcookie("ulp_info", __('Campaign successfully <strong>removed</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp-campaigns');
						exit;
					} else {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp-campaigns');
						exit;
					}
					exit;
					break;
				case 'ulp-campaigns-drop-counters':
					$id = intval($_GET["id"]);
					$campaign_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_campaigns WHERE id = '".$id."' AND deleted = '0'", ARRAY_A);
					if (intval($campaign_details["id"]) == 0) {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp-campaigns');
						exit;
					}
					$sql = "UPDATE ".$wpdb->prefix."ulp_campaign_items SET clicks = '0', impressions = '0' WHERE campaign_id = '".$id."'";
					if ($wpdb->query($sql) !== false) {
						setcookie("ulp_info", __('Campaign counters successfully <strong>cleared</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp-campaigns');
						exit;
					} else {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp-campaigns');
						exit;
					}
					exit;
					break;
				case 'ulp-campaigns-block':
					$id = intval($_GET["id"]);
					$campaign_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_campaigns WHERE id = '".$id."' AND deleted = '0'", ARRAY_A);
					if (intval($campaign_details["id"]) == 0) {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp-campaigns');
						exit;
					}
					$sql = "UPDATE ".$wpdb->prefix."ulp_campaigns SET blocked = '1' WHERE id = '".$id."'";
					if ($wpdb->query($sql) !== false) {
						setcookie("ulp_info", __('Campaign successfully <strong>blocked</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp-campaigns');
						exit;
					} else {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp-campaigns');
						exit;
					}
					exit;
					break;
				case 'ulp-campaigns-unblock':
					$id = intval($_GET["id"]);
					$campaign_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_campaigns WHERE id = '".$id."' AND deleted = '0'", ARRAY_A);
					if (intval($campaign_details["id"]) == 0) {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp-campaigns');
						exit;
					}
					$sql = "UPDATE ".$wpdb->prefix."ulp_campaigns SET blocked = '0' WHERE id = '".$id."'";
					if ($wpdb->query($sql) !== false) {
						setcookie("ulp_info", __('Campaign successfully <strong>unblocked</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp-campaigns');
						exit;
					} else {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp-campaigns');
						exit;
					}
					exit;
					break;
				case 'ulp-subscribers-delete':
					if (isset($_GET["s"])) $search_query = trim(stripslashes($_GET["s"]));
					else $search_query = "";
					if (isset($_GET["pid"])) $pid = intval(stripslashes($_GET["pid"]));
					else $pid = 0;
					
					$id = intval($_GET["id"]);
					$subscriber_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_subscribers WHERE id = '".$id."' AND deleted = '0'", ARRAY_A);
					if (intval($subscriber_details["id"]) == 0) {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp-subscribers'.((strlen($search_query) > 0) ? '&s='.rawurlencode($search_query) : '').($pid > 0 ? '&pid='.$pid : ''));
						exit;
					}
					$sql = "UPDATE ".$wpdb->prefix."ulp_subscribers SET deleted = '1' WHERE id = '".$id."'";
					if ($wpdb->query($sql) !== false) {
						setcookie("ulp_info", __('Record successfully <strong>removed</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp-subscribers'.((strlen($search_query) > 0) ? '&s='.rawurlencode($search_query) : '').($pid > 0 ? '&pid='.$pid : ''));
						exit;
					} else {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp-subscribers'.((strlen($search_query) > 0) ? '&s='.rawurlencode($search_query) : '').($pid > 0 ? '&pid='.$pid : ''));
						exit;
					}
					break;
				case 'ulp-subscribers-csv':
					if (isset($_GET["s"])) $search_query = trim(stripslashes($_GET["s"]));
					else $search_query = "";
					if (isset($_GET["pid"])) $pid = intval(stripslashes($_GET["pid"]));
					else $pid = 0;
				
					$limit_start = 0;
					$rows = $wpdb->get_results("SELECT t1.*, t2.title AS popup_title FROM ".$wpdb->prefix."ulp_subscribers t1 LEFT JOIN ".$wpdb->prefix."ulp_popups t2 ON t2.id = t1.popup_id WHERE t1.deleted = '0'".((strlen($search_query) > 0) ? " AND (t1.name LIKE '%".addslashes($search_query)."%' OR t1.email LIKE '%".addslashes($search_query)."%')" : "").($pid > 0 ? " AND t1.popup_id = '".$pid."'" : "")." ORDER BY t1.created DESC LIMIT 0, 200", ARRAY_A);
					if (sizeof($rows) > 0) {
						do {
							if (strstr($_SERVER["HTTP_USER_AGENT"],"MSIE")) {
								header("Pragma: public");
								header("Expires: 0");
								header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
								header("Content-type: application-download");
								header("Content-Disposition: attachment; filename=\"emails.csv\"");
								header("Content-Transfer-Encoding: binary");
							} else {
								header("Content-type: application-download");
								header("Content-Disposition: attachment; filename=\"emails.csv\"");
							}
							$separator = $ulp->options['csv_separator'];
							if ($separator == 'tab') $separator = "\t";
							echo '"Status"'.$separator.'"Name"'.$separator.'"E-Mail"'.$separator.'"Phone"'.$separator.'"Message"'.$separator.'"Popup"'.$separator.'"Created"'.$separator.'"Custom Fields"'."\r\n";
							foreach ($rows as $row) {
								echo '"'.(array_key_exists($row['status'], $ulp->user_statuses) ? $ulp->user_statuses[$row['status']]['label'] : 'N/A').'"'.$separator;
								echo '"'.str_replace('"', "'", $row["name"]).'"'.$separator.'"'.str_replace('"', "'", $row["email"]).'"'.$separator.'"'.str_replace('"', "'", $row["phone"]).'"'.$separator.'"'.str_replace('"', "'", $row["message"]).'"'.$separator.'"'.str_replace('"', "'", $row["popup_title"]).'"'.$separator.'"'.date("Y-m-d H:i:s", $row["created"]).'"';
								if (array_key_exists('custom_fields', $row) && !empty($row['custom_fields'])) {
									$custom_fields = unserialize($row['custom_fields']);
									if ($custom_fields && is_array($custom_fields)) {
										foreach ($custom_fields as $field) {
											echo $separator.'"'.str_replace('"', "'", $field['value']).'"';
										}
									}
								}
								echo "\r\n";
							}
							$limit_start += 200;
							$rows = $wpdb->get_results("SELECT t1.*, t2.title AS popup_title FROM ".$wpdb->prefix."ulp_subscribers t1 LEFT JOIN ".$wpdb->prefix."ulp_popups t2 ON t2.id = t1.popup_id WHERE t1.deleted = '0'".((strlen($search_query) > 0) ? " AND (t1.name LIKE '%".addslashes($search_query)."%' OR t1.email LIKE '%".addslashes($search_query)."%')" : "").($pid > 0 ? " AND t1.popup_id = '".$pid."'" : "")." ORDER BY t1.created DESC LIMIT ".$limit_start.", 200", ARRAY_A);
						} while (sizeof($rows) > 0);
						exit;
		            }
		            header('Location: '.admin_url('admin.php').'?page=ulp-subscribers'.((strlen($search_query) > 0) ? '&s='.rawurlencode($search_query) : '').($pid > 0 ? '&pid='.$pid : ''));
					exit;
					break;
				case 'ulp-subscribers-delete-all':
					if (isset($_GET["s"])) $search_query = trim(stripslashes($_GET["s"]));
					else $search_query = "";
					if (isset($_GET["pid"])) $pid = intval(stripslashes($_GET["pid"]));
					else $pid = 0;
					
					$sql = "UPDATE ".$wpdb->prefix."ulp_subscribers SET deleted = '1' WHERE deleted = '0'".((strlen($search_query) > 0) ? " AND (name LIKE '%".addslashes($search_query)."%' OR email LIKE '%".addslashes($search_query)."%')" : "").($pid > 0 ? " AND popup_id = '".$pid."'" : "");
					if ($wpdb->query($sql) !== false) {
						setcookie("ulp_info", __('Records successfully <strong>removed</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
					} else {
						setcookie("ulp_error", __('<strong>Invalid</strong> service call.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
					}
					header('Location: '.admin_url('admin.php').'?page=ulp-subscribers'.((strlen($search_query) > 0) ? '&s='.rawurlencode($search_query) : '').($pid > 0 ? '&pid='.$pid : ''));
					exit;
					break;
				case 'ulp-export':
					error_reporting(0);
					$id = intval($_GET["id"]);
					$popup_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE id = '".$id."' AND deleted = '0'", ARRAY_A);
					$popup_full = array();
					if (!empty($popup_details)) {
						$popup_full = array();
						$popup_full['popup'] = $popup_details;
						$popup_full['layers'] = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_layers WHERE popup_id = '".$id."' AND deleted = '0'", ARRAY_A);
						foreach ($popup_full['layers'] as $idx => $layer) {
							$layer_options = unserialize($layer['details']);
							if (is_array($layer_options)) $layer_options = array_merge($ulp->default_layer_options, $layer_options);
							else $layer_options = $ulp->default_layer_options;
							$layer_options = $ulp->filter_lp_reverse($layer_options);
							$popup_full['layers'][$idx]['content'] = str_replace(array('http://datastorage.pw/images', $ulp->plugins_url.'/images/default'), array('ULP-DEMO-IMAGES-URL', 'ULP-DEMO-IMAGES-URL'), $popup_full['layers'][$idx]['content']);
							$popup_full['layers'][$idx]['details'] = serialize($layer_options);
						}
						$popup_data = serialize($popup_full);
						$output = ULP_EXPORT_VERSION.PHP_EOL.md5($popup_data).PHP_EOL.base64_encode($popup_data);
						if (strstr($_SERVER["HTTP_USER_AGENT"],"MSIE")) {
							header("Pragma: public");
							header("Expires: 0");
							header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
							header("Content-type: application-download");
							header('Content-Disposition: attachment; filename="'.$popup_details['str_id'].'.txt"');
							header("Content-Transfer-Encoding: binary");
						} else {
							header("Content-type: application-download");
							header('Content-Disposition: attachment; filename="'.$popup_details['str_id'].'.txt"');
						}
						echo $output;
						flush();
						ob_flush();
						exit;
		            }
		            header('Location: '.admin_url('admin.php').'?page=ulp');
					exit;
					break;
				case 'ulp-export-full':
					if (!class_exists('ZipArchive') || !class_exists('DOMDocument')) {
						setcookie("ulp_error", __('This operation <strong>requires</strong> <em>ZipArchive</em> and <em>DOMDocument</em> classes. Some of them <strong>not found</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp');
						exit;
					}
					$upload_dir = wp_upload_dir();
					if (!file_exists($upload_dir["basedir"].'/'.ULP_UPLOADS_DIR.'/temp')) {
						setcookie("ulp_error", __('Please <strong>re-activate</strong> the plugin and try again.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
						header('Location: '.admin_url('admin.php').'?page=ulp');
						exit;
					}
					$id = intval($_GET["id"]);
					$popup_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE id = '".$id."' AND deleted = '0'", ARRAY_A);
					$popup_full = array();
					if (!empty($popup_details)) {
						if (!defined('UAP_CORE')) {
							require_once(ABSPATH.'wp-admin/includes/file.php');
						}
						$zip = new ZipArchive();
						$zip_filename = $upload_dir["basedir"].'/'.ULP_UPLOADS_DIR.'/temp/'.$ulp->random_string(16).'.zip';
						if ($zip->open($zip_filename, ZipArchive::CREATE) !== true) {
							setcookie("ulp_error", __('<strong>Can not create</strong> zip-archive. Make sure that the following folder has write permission:', 'ulp').' '.$upload_dir["basedir"].'/'.ULP_UPLOADS_DIR.'/temp', time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
							header('Location: '.admin_url('admin.php').'?page=ulp');
							exit;
						}
						$popup_full = array();
						$images_processed = array();
						$popup_options = unserialize($popup_details['options']);
						
						$export_options = apply_filters('ulp_export_full_popup_options', $ulp->default_popup_options);
						
						if (is_array($popup_options)) $popup_options = array_intersect_key($popup_options, $export_options);
						else $popup_options = $ulp->default_popup_options;
						$popup_options['redirect_url'] = '';
						$popup_details['options'] = serialize($popup_options);
						$popup_full['popup'] = $popup_details;
						$popup_full['layers'] = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_layers WHERE popup_id = '".$id."' AND deleted = '0'", ARRAY_A);
						foreach ($popup_full['layers'] as $idx => $layer) {
							$layer_options = unserialize($layer['details']);
							if (is_array($layer_options)) $layer_options = array_merge($ulp->default_layer_options, $layer_options);
							else $layer_options = $ulp->default_layer_options;
							$layer_options = $ulp->filter_lp($layer_options);
							
							if (!empty($layer_options['background_image']) && preg_match('~^((http(s)?://)|(//))[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$~i', $layer_options['background_image'])) {
								$filename = $this->add_to_archive($zip, $layer_options['background_image'], $images_processed);
								if ($filename !== false) {
									$layer_options['background_image'] = 'ULP-UPLOADS-DIR/'.$filename;
								}
							}
							
							if (function_exists('libxml_use_internal_errors')) libxml_use_internal_errors(true);
							if (!empty($layer_options['content'])) {
								$dom = new DOMDocument();
								$dom->loadHTML($layer_options['content']);
								if (!$dom) {
									$zip->close();
									unlink($zip_filename);
									setcookie("ulp_error", __('<strong>Can not parse</strong> layer HTML-content. Make sure it is valid.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
									header('Location: '.admin_url('admin.php').'?page=ulp');
									exit;
								}
								$imgs = $dom->getElementsByTagName('img');
								foreach ($imgs as $img) {
									$img_string = $img->getAttribute('src');
									if (!empty($img_string) && preg_match('~^((http(s)?://)|(//))[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$~i', $img_string)) {
										$filename = $this->add_to_archive($zip, $img_string, $images_processed);
										if ($filename !== false) {
											$layer_options['content'] = str_replace($img_string, 'ULP-UPLOADS-DIR/'.$filename, $layer_options['content']);
										}
									}
								}								
							}
							$popup_full['layers'][$idx]['content'] = $layer_options['content'];
							$popup_full['layers'][$idx]['details'] = serialize($layer_options);
						}
						$popup_data = serialize($popup_full);
						$zip->addFromString('popup.txt', ULP_EXPORT_VERSION.PHP_EOL.md5($popup_data).PHP_EOL.base64_encode($popup_data));
						$zip->addFromString('index.html', 'Get your copy of Layered Popups: <a href="http://codecanyon.net/item/layered-popups-for-wordpress/5978263">WordPress Plugin</a>, <a href="http://codecanyon.net/item/layered-popups/6027291">Standalone Script</a>.');
						$zip->close();
						error_reporting(0);
						$length = filesize($zip_filename);
						if (strstr($_SERVER["HTTP_USER_AGENT"], "MSIE")) {
							header("Pragma: public");
							header("Expires: 0");
							header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
							header("Content-Type: application-download");
							header("Content-Length: ".$length);
							header('Content-Disposition: attachment; filename="'.$popup_details['str_id'].'.zip"');
							header("Content-Transfer-Encoding: binary");
						} else {
							header("Content-Type: application-download");
							header("Content-Length: ".$length);
							header('Content-Disposition: attachment; filename="'.$popup_details['str_id'].'.zip"');
						}
						$handle_read = fopen($zip_filename, "rb");
						while (!feof($handle_read) && $length > 0) {
							$content = fread($handle_read, 1024);
							echo substr($content, 0, min($length, 1024));
							flush();
							$length = $length - strlen($content);
							if ($length < 0) $length = 0;
						}
						fclose($handle_read);
						unlink($zip_filename);
						foreach ($images_processed as $value) {
							if (!empty($value['temp']) && file_exists($value['temp']) && is_file($value['temp'])) unlink($value['temp']);
						}
						exit;
		            }
		            header('Location: '.admin_url('admin.php').'?page=ulp');
					exit;
					break;
				case 'ulp-import':
					if (is_uploaded_file($_FILES["ulp-file"]["tmp_name"])) {
						$dot_pos = strrpos($_FILES["ulp-file"]["name"], '.');
						if ($dot_pos === false) {
							setcookie("ulp_error", __('<strong>Invalid</strong> popup file.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
							header('Location: '.admin_url('admin.php').'?page=ulp');
							exit;
						}
						$ext = strtolower(substr($_FILES["ulp-file"]["name"], $dot_pos));
						if ($ext == '.txt') {
							$lines = file($_FILES["ulp-file"]["tmp_name"]);
							if (sizeof($lines) != 3) {
								setcookie("ulp_error", __('<strong>Invalid</strong> popup file.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
								header('Location: '.admin_url('admin.php').'?page=ulp');
								exit;
							}
							$version = intval(trim($lines[0]));
							if ($version > intval(ULP_EXPORT_VERSION)) {
								setcookie("ulp_error", __('Popup file version <strong>is not supported</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
								header('Location: '.admin_url('admin.php').'?page=ulp');
								exit;
							}
							$md5_hash = trim($lines[1]);
							$popup_data = trim($lines[2]);
							$popup_data = base64_decode($popup_data);
							if (!$popup_data || md5($popup_data) != $md5_hash) {
								setcookie("ulp_error", __('Popup file <strong>corrupted</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
								header('Location: '.admin_url('admin.php').'?page=ulp');
								exit;
							}
							$popup = unserialize($popup_data);
							$popup_details = $popup['popup'];
							$str_id = $ulp->random_string(16);
							$sql = "INSERT INTO ".$wpdb->prefix."ulp_popups (str_id, title, width, height, options, created, blocked, deleted) 
								VALUES (
								'".$str_id."', 
								'".esc_sql($popup_details['title'])."', 
								'".intval($popup_details['width'])."', 
								'".intval($popup_details['height'])."', 
								'".esc_sql($popup_details['options'])."', 
								'".time()."', '1', '0')";
							$wpdb->query($sql);
							$popup_id = $wpdb->insert_id;
							$layers = $popup['layers'];
							if (sizeof($layers) > 0) {
								foreach ($layers as $layer) {
									$sql = "INSERT INTO ".$wpdb->prefix."ulp_layers (
										popup_id, title, content, zindex, details, created, deleted) VALUES (
										'".$popup_id."',
										'".esc_sql($layer['title'])."',
										'".esc_sql($layer['content'])."',
										'".esc_sql($layer['zindex'])."',
										'".esc_sql($layer['details'])."',
										'".time()."', '0')";
									$wpdb->query($sql);
								}
							}
							setcookie("ulp_info", __('The new popup successfully <strong>imported</strong> and marked as <strong>blocked</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
							header('Location: '.admin_url('admin.php').'?page=ulp');
							exit;
						} else if ($ext == '.zip') {
							$result = $this->import_zip($_FILES["ulp-file"]["tmp_name"]);
							if (is_wp_error($result)) {
								setcookie("ulp_error", $result->get_error_message(), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
								header('Location: '.admin_url('admin.php').'?page=ulp');
								exit;
							}
							setcookie("ulp_info", __('The new popup successfully <strong>imported</strong> and marked as <strong>blocked</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
							header('Location: '.admin_url('admin.php').'?page=ulp');
							exit;
						} else {
							setcookie("ulp_error", __('<strong>Invalid</strong> popup file.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
							header('Location: '.admin_url('admin.php').'?page=ulp');
							exit;
						}
					}
					setcookie("ulp_error", __('Popup file <strong>not uploaded</strong>.', 'ulp'), time()+30, "/", ".".str_replace("www.", "", $_SERVER["SERVER_NAME"]));
					header('Location: '.admin_url('admin.php').'?page=ulp');
					exit;
					break;
				default:
					break;
			}
		}
	}

	function widgets_init() {
		include_once(dirname(dirname(__FILE__)).'/widget.php');
		register_widget('ulp_widget');
	}

	function check_for_plugin_update($checked_data) {
		global $wp_version, $ulp;
		
		$file = basename(dirname(dirname(__FILE__))).'/layered-popups.php';
		$purchase_code = preg_replace('/[^a-zA-Z0-9-]/', '', $ulp->options['purchase_code']);
		
		if (empty($checked_data->checked))
			return $checked_data;

		if (!array_key_exists($file, (array)$checked_data->checked))
			return $checked_data;
		
		$args = array(
			'slug' => 'layered-popups',
			'version' => $checked_data->checked[$file],
		);
		$request_string = array(
				'body' => array(
					'action' => 'basic_check', 
					'request' => serialize($args),
					'api-key' => $purchase_code
				),
				'user-agent' => 'WordPress/'.$wp_version.'; '.get_bloginfo('url')
			);
		
		$raw_response = wp_remote_post(ULP_API_URL, $request_string);
		
		if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200)) {
			$response = unserialize($raw_response['body']);
		}
		if (!empty($response) && is_object($response)) {
			$checked_data->response[$file] = $response;
		}
		return $checked_data;
	}
	
	function plugin_api_call($def, $action, $args) {
		global $wp_version, $ulp;

		$file = basename(dirname(dirname(__FILE__))).'/layered-popups.php';
		$purchase_code = preg_replace('/[^a-zA-Z0-9-]/', '', $ulp->options['purchase_code']);
		
		if (!isset($args->slug) || ($args->slug != 'layered-popups'))
			return false;
		
		$plugin_info = get_site_transient('update_plugins');
		if (!property_exists($plugin_info, 'checked') || !array_key_exists($file, $plugin_info->checked)) return false;
		$current_version = $plugin_info->checked[$file];
		$args->version = $current_version;
		
		$request_string = array(
				'body' => array(
					'action' => $action, 
					'request' => serialize($args),
					'api-key' => $purchase_code
				),
				'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
			);
		
		$request = wp_remote_post(ULP_API_URL, $request_string);
		
		if (is_wp_error($request)) {
			$res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.', 'ulp').' <a href="#" onclick="document.location.reload(); return false;">'.__('Try again.', 'ulp').'</a>', $request->get_error_message());
		} else {
			$res = unserialize($request['body']);
			if ($res === false) {
				$res = new WP_Error('plugins_api_failed', __('An unknown error occurred', 'ulp'), $request['body']);
			}
		}
		return $res;
	}

	function add_to_archive(&$_zip, $_image_url, &$_images_processed) {
		if (substr($_image_url, 0, 2) == '//') $_image_url = 'http:'.$_image_url;
		if (strtolower(substr($_image_url, 0, 8)) == 'https://') $processed_key = substr($_image_url, 8);
		else $processed_key = substr($_image_url, 7);
		if (strtolower(substr($processed_key, 0, 4)) == 'www.') $processed_key = substr($processed_key, 4);
		if (array_key_exists($processed_key, $_images_processed)) {
			return $_images_processed[$processed_key]['image'];
		}
		$filename = 'img-'.sizeof($_images_processed);
		$mime_types = array(
			'image/png' => 'png',
			'image/jpeg' => 'jpg',
			'image/gif' => 'gif',
			'image/bmp' => 'bmp',
			'image/vnd.microsoft.icon' => 'ico',
			'image/tiff' => 'tiff',
			'image/svg+xml' => 'svg',
			'image/svg+xml' => 'svgz'
		);
		$download_file = download_url($_image_url);
		if (is_wp_error($download_file)) {
			return false;
		}
		$path = parse_url($_image_url, PHP_URL_PATH);
		$check_image = true;
		if ($path !== false && strlen($path) > 4) {
			$ext = strtolower(substr($path, strlen($path)-4));
			if ($ext == '.svg') {
				$filename .= '.svg';
				$check_image = false;
			}
		}
		if ($check_image) {
			$img_data = getimagesize($download_file);
			if (is_array($img_data) && array_key_exists('mime', $img_data)) {
				if (array_key_exists($img_data['mime'], $mime_types)) {
					$filename .= '.'.$mime_types[$img_data['mime']];
				}
			}
		}
		if ($_zip->addFile($download_file, $filename)) {
			$_images_processed[$processed_key] = array(
				'image' => $filename,
				'temp' => $download_file
			);
			return $filename;
		}
		unlink($download_file);
		return false;
	}
	
	function import_zip($_filename, $_title = '') {
		global $wpdb, $ulp;
		//error_reporting(0);
		$str_id = $ulp->random_string(16);
		//if (!class_exists('ZipArchive')) {
		//	return new WP_Error('ulp_no_required_classes', __('This operation <strong>requires</strong> <em>ZipArchive</em> class. It is <strong>not found</strong>.', 'ulp'));
		//}
		$upload_dir = wp_upload_dir();
		if (!file_exists($upload_dir["basedir"].'/'.ULP_UPLOADS_DIR.'/temp')) {
			return new WP_Error('ulp_no_temp_folder', __('Please <strong>re-activate</strong> the plugin and try again.', 'ulp'));
		}
		$temp_dir = $upload_dir["basedir"].'/'.ULP_UPLOADS_DIR.'/'.$str_id;
		if (!wp_mkdir_p($temp_dir)) {
			return new WP_Error('ulp_no_temp_folder', __('Make sure that the following folder has write permission:', 'ulp').' '.$upload_dir["basedir"].'/'.ULP_UPLOADS_DIR);
		}
		if (!defined('UAP_CORE')) {
			require_once(ABSPATH.'wp-admin/includes/file.php');
			WP_Filesystem();
			$result = unzip_file($_filename, $temp_dir);
		} else {
			$result = new WP_Error();
		}
		if (is_wp_error($result)) {
			$zip = new ZipArchive;
			if ($zip->open($_filename) === TRUE) {
				$zip->extractTo($temp_dir);
				$zip->close();
			} else {
				return new WP_Error('ulp_cant_unzip', __('Can not unzip archive into folder', 'ulp').' '.$temp_dir);
			}
		}
		if (!file_exists($temp_dir.'/popup.txt')) {
			$this->remove_dir($temp_dir);
			return new WP_Error('ulp_invalid_archive', __('Please make sure that you uploaded valid popup file. Error #1.', 'ulp'));
		}
		$lines = file($temp_dir.'/popup.txt');
		unlink($temp_dir.'/popup.txt');
		if (sizeof($lines) != 3) {
			$this->remove_dir($temp_dir);
			return new WP_Error('ulp_invalid_archive', __('Please make sure that you uploaded valid popup file. Error #2.', 'ulp'));
		}
		$version = intval(trim($lines[0]));
		if ($version > intval(ULP_EXPORT_VERSION)) {
			$this->remove_dir($temp_dir);
			return new WP_Error('ulp_invalid_archive', __('Please make sure that you uploaded valid popup file. Error #3.', 'ulp'));
		}
		$md5_hash = trim($lines[1]);
		$popup_data = trim($lines[2]);
		$popup_data = base64_decode($popup_data);
		if (!$popup_data || md5($popup_data) != $md5_hash) {
			$this->remove_dir($temp_dir);
			return new WP_Error('ulp_invalid_archive', __('Please make sure that you uploaded valid popup file. Error #4.', 'ulp'));
		}
		$popup = unserialize($popup_data);
		if ($popup === false) {
			$this->remove_dir($temp_dir);
			return new WP_Error('ulp_invalid_archive', __('Please make sure that you uploaded valid popup file. Error #5.', 'ulp'));
		}
		$popup_details = $popup['popup'];
		if (!empty($_title)) $popup_details['title'] = $_title;
		
		$upload_url = trailingslashit($upload_dir['baseurl']).ULP_UPLOADS_DIR.'/'.$str_id;
		if (strtolower(substr($upload_url, 0, 7)) == 'http://') $upload_url = substr($upload_url, 5);
		else if (strtolower(substr($upload_url, 0, 8)) == 'https://') $upload_url = substr($upload_url, 6);
		
		$sql = "INSERT INTO ".$wpdb->prefix."ulp_popups (str_id, title, width, height, options, created, blocked, deleted) VALUES (
			'".$str_id."', 
			'".esc_sql($popup_details['title'])."', 
			'".intval($popup_details['width'])."', 
			'".intval($popup_details['height'])."', 
			'".esc_sql($popup_details['options'])."', 
			'".time()."', '1', '0')";
		$wpdb->query($sql);
		$popup_id = $wpdb->insert_id;
		$layers = $popup['layers'];
		if (sizeof($layers) > 0) {
			foreach ($layers as $layer) {
				$layer_options = unserialize($layer['details']);
				if (is_array($layer_options)) $layer_options = array_merge($ulp->default_layer_options, $layer_options);
				else $layer_options = $ulp->default_layer_options;
				$layer_options['content'] = str_replace('ULP-UPLOADS-DIR', $upload_url, $layer_options['content']);
				$layer_options['background_image'] = str_replace('ULP-UPLOADS-DIR', $upload_url, $layer_options['background_image']);
				$layer['content'] = str_replace('ULP-UPLOADS-DIR', $upload_url, $layer['content']);
				$layer['details'] = serialize($layer_options);
				$sql = "INSERT INTO ".$wpdb->prefix."ulp_layers (popup_id, title, content, zindex, details, created, deleted) VALUES (
					'".$popup_id."',
					'".esc_sql($layer['title'])."',
					'".esc_sql($layer['content'])."',
					'".esc_sql($layer['zindex'])."',
					'".esc_sql($layer['details'])."',
					'".time()."', '0')";
				$wpdb->query($sql);
			}
		}
		return true;
	}
	
	function remove_dir($_dir) { 
		$files = array_diff(scandir($_dir), array('.','..')); 
		foreach ($files as $file) { 
			if (is_dir($_dir.'/'.$file)) {
				$this->remove_dir($_dir.'/'.$file);
			} else {
				unlink($_dir.'/'.$file); 
			}
		}
		return rmdir($_dir);
	}
}
?>