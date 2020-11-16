<?php
/* The Newsletter Plugin integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_thenewsletterplugin_class {
	var $default_popup_options = array(
		'thenewsletterplugin_enable' => "off",
		'thenewsletterplugin_preferences' => array(),
		'thenewsletterplugin_static_fields' => array(
			'email' => '{subscription-email}',
			'name' => '{subscription-name}',
			'surname' => '',
		),
		'thenewsletterplugin_fields' => array()
	);
	var $field_labels = array(
		'name' => array('title' => 'First name', 'description' => 'First name (or full name) of the contact.'),
		'surname' => array('title' => 'Last name', 'description' => 'Last name of the contact.'),
		'email' => array('title' => 'E-mail', 'description' => 'E-mail address of the contact.')
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
		global $ulp;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('The Newsletter Plugin Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">';
		if (class_exists('Newsletter')) {
			echo '
					<tr>
						<th>'.__('Enable The Newsletter Plugin', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_thenewsletterplugin_enable" name="ulp_thenewsletterplugin_enable" '.($popup_options['thenewsletterplugin_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to The Newsletter Plugin', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to The Newsletter Plugin.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Preferences', 'ulp').':</th>
						<td>';
			$exists = false;
			$options_profile = get_option('newsletter_profile');
			for ($i = 1; $i <= NEWSLETTER_LIST_MAX; $i++) {
				if (empty($options_profile['list_' . $i])) continue;
				echo '<input type="checkbox" id="ulp_thenewsletterplugin_preference_'.$i.'" name="ulp_thenewsletterplugin_preference_'.$i.'"'.(in_array($i, $popup_options['thenewsletterplugin_preferences']) ? ' checked="checked"' : '').'> '.esc_html($options_profile['list_' . $i]).'<br />';
				$exists = true;
			}
			if ($exists) echo '<em>'.__('Select user preferences.', 'ulp').'</em>';
			else echo '<strong>'.__('No user preferences found!', 'ulp').'</strong>';
			echo '
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-thenewsletterplugin-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate The Newsletter Plugin fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
			foreach ($popup_options['thenewsletterplugin_static_fields'] as $id => $cdata) {
				echo '
									<tr>
										<td style="width: 120px;"><strong>'.esc_html($this->field_labels[$id]['title']).':</strong></td>
										<td>
											<input type="text" id="ulp_thenewsletterplugin_static_field_'.esc_html($id).'" name="ulp_thenewsletterplugin_static_field_'.esc_html($id).'" value="'.esc_html($cdata).'" class="widefat"'.($id == 'email' ? ' readonly="readonly"' : '').' />
											<br /><em>'.esc_html($this->field_labels[$id]['description']).'</em>
										</td>
									</tr>';
			}
			for ($i = 1; $i <= NEWSLETTER_PROFILE_MAX; $i++) {
				if (empty($options_profile['profile_' . $i])) continue;
				echo '
									<tr>
										<td style="width: 120px;"><strong>'.esc_html($options_profile['profile_' . $i]).':</strong></td>
										<td>
											<input type="text" id="ulp_thenewsletterplugin_field_'.$i.'" name="ulp_thenewsletterplugin_field_'.$i.'" value="'.(array_key_exists($i, $popup_options['thenewsletterplugin_fields']) ? esc_html($popup_options['thenewsletterplugin_fields'][$i]) : '').'" class="widefat" />
											<br /><em>'.esc_html($options_profile['profile_' . $i]).'</em>
										</td>
									</tr>';
			}
			echo '
								</table>
							</div>
						</td>
					</tr>';
		} else {
			echo '
					<tr>
						<th>'.__('Enable The Newsletter Plugin', 'ulp').':</th>
						<td>'.__('Please install and activate <a target="_blank" href="https://wordpress.org/plugins/newsletter/">The Newsletter Plugin</a> plugin.', 'ulp').'</td>
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
		if (isset($ulp->postdata["ulp_thenewsletterplugin_enable"])) $popup_options['thenewsletterplugin_enable'] = "on";
		else $popup_options['thenewsletterplugin_enable'] = "off";
		return array_merge($_errors, $errors);
	}
	function popup_options_populate($_popup_options) {
		global $ulp;
		$popup_options = $this->default_popup_options;
		foreach ($this->default_popup_options as $key => $value) {
			if (isset($ulp->postdata['ulp_'.$key])) {
				$popup_options[$key] = stripslashes(trim($ulp->postdata['ulp_'.$key]));
			}
		}
		if (isset($ulp->postdata["ulp_thenewsletterplugin_enable"])) $popup_options['thenewsletterplugin_enable'] = "on";
		else $popup_options['thenewsletterplugin_enable'] = "off";
		if (class_exists('Newsletter')) {
			$options_profile = get_option('newsletter_profile');
			foreach($popup_options['thenewsletterplugin_static_fields'] as $key => $value) {
				if (isset($ulp->postdata['ulp_thenewsletterplugin_static_field_'.$key])) {
					$popup_options['thenewsletterplugin_static_fields'][$key] = stripslashes(trim($ulp->postdata['ulp_thenewsletterplugin_static_field_'.$key]));
				}
			}
			for ($i = 1; $i <= NEWSLETTER_PROFILE_MAX; $i++) {
				if (empty($options_profile['profile_' . $i])) continue;
				if (isset($ulp->postdata['ulp_thenewsletterplugin_field_'.$i])) {
					$popup_options['thenewsletterplugin_fields'][$i] = stripslashes(trim($ulp->postdata['ulp_thenewsletterplugin_field_'.$i]));
				}
			}
			for ($i = 1; $i <= NEWSLETTER_LIST_MAX; $i++) {
				if (empty($options_profile['list_' . $i])) continue;
				if (isset($ulp->postdata['ulp_thenewsletterplugin_preference_'.$i])) {
					$popup_options['thenewsletterplugin_preferences'][] = $i;
				}
			}
		}
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if (class_exists('Newsletter')) {
			if ($popup_options['thenewsletterplugin_enable'] == 'on') {
				try {
					$data = array(
						'email' => $_subscriber['{subscription-email}'],
						'status' => 'C'
					);
					$options_feed = get_option('newsletter_feed', array());
					if ($options_feed['add_new'] == 1) $data['feed'] = 1;
					$options_followup = get_option('newsletter_followup', array());
					if ($options_followup['add_new'] == 1) {
					  $data['followup'] = 1;
					  $data['followup_time'] = time() + $options_followup['interval'] * 3600;
					}
					foreach($popup_options['thenewsletterplugin_static_fields'] as $key => $value) {
						if (!empty($value) && $key != 'email') {
							$data[$key] = strtr($value, $_subscriber);
						}
					}
					foreach($popup_options['thenewsletterplugin_fields'] as $key => $value) {
						if (!empty($value)) {
							$data['profile_'.$key] = strtr($value, $_subscriber);
						}
					}
					foreach($popup_options['thenewsletterplugin_preferences'] as $key => $value) {
						if (!empty($value)) {
							$data['list_'.$value] = 1;
						}
					}
					$user = NewsletterUsers::instance()->get_user($_subscriber['{subscription-email}']);
					if (is_object($user) && !empty($user->id)) $data['id'] = $user->id;
					$result = NewsletterUsers::instance()->save_user($data);
			} catch (Exception $e) {
				}
			}
		}
	}
}
$ulp_thenewsletterplugin = new ulp_thenewsletterplugin_class();
?>