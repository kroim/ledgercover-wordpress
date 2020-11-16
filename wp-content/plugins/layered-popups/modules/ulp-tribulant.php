<?php
/* Tribulant Newsletters integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_tribulant_class {
	var $default_popup_options = array(
		'tribulant_enable' => "off",
		'tribulant_listid' => "",
		'tribulant_fields' => ""
	);
	function __construct() {
		$this->default_popup_options['tribulant_fields'] = serialize(array('email' => '{subscription-email}'));
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
				<h3>'.__('Tribulant Newsletters Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">';
		if (class_exists('wpMailPlugin') && function_exists('wpml_get_mailinglists')) {
			$mailinglists = wpml_get_mailinglists();
			if (sizeof($mailinglists) == 0) {
				echo '
					<tr>
						<th>'.__('Enable Tribulant Newsletters', 'ulp').':</th>
						<td>'.__('Please <a href="admin.php?page=newsletters-lists">create</a> at least one list.', 'ulp').'</td>
					</tr>';
			} else {
				echo '
					<tr>
						<th>'.__('Enable Tribulant Newsletters', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_tribulant_enable" name="ulp_tribulant_enable" '.($popup_options['tribulant_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Tribulant Newsletters', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Tribulant Newsletters.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<select name="ulp_tribulant_listid" class="ic_input_m">';
				foreach ($mailinglists as $list) {
					echo '
								<option value="'.$list->id.'"'.($list->id == $popup_options['tribulant_listid'] ? ' selected="selected"' : '').'>'.esc_html($list->title).'</option>';
				}
				echo '
							</select>
							<br /><em>'.__('Select your List ID.', 'ulp').'</em>
						</td>
					</tr>';
				if (function_exists('wpml_get_fields')) {
					$custom_fields = wpml_get_fields();
					$fields = unserialize($popup_options['tribulant_fields']);
					if (!is_array($fields)) $fields = array();
					if ($custom_fields && !(sizeof($custom_fields) == 1 && $custom_fields[0]->slug == 'list')) {
						echo '
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-tribulant-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Tribulant Newsletters custom fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
						foreach ($custom_fields as $custom_field) {
							if ($custom_field->slug != 'list') {
								echo '
									<tr>
										<td style="width: 120px;"><strong>'.esc_html($custom_field->title).':</strong></td>
										<td>
											<input type="text" id="ulp_tribulant_field_'.esc_html($custom_field->slug).'" name="ulp_tribulant_field_'.esc_html($custom_field->slug).'" value="'.(array_key_exists($custom_field->slug, $fields) ? esc_html($fields[$custom_field->slug]) : '').'" class="widefat"'.($custom_field->slug == 'email' ? ' readonly="readonly"' : '').' />
											<br /><em>'.esc_html($custom_field->title.' ('.$custom_field->slug.')').'</em>
										</td>
									</tr>';
							}
						}
						echo '
								</table>
							</div>
						</td>
					</tr>';
					}
				}
			}
		} else {
			echo '
					<tr>
						<th>'.__('Enable Tribulant Newsletters', 'ulp').':</th>
						<td>'.__('Please install and activate <a target="_blank" href="https://wordpress.org/plugins/newsletters-lite/">Tribulant Newsletters</a> plugin.', 'ulp').'</td>
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
		if (isset($ulp->postdata["ulp_tribulant_enable"])) $popup_options['tribulant_enable'] = "on";
		else $popup_options['tribulant_enable'] = "off";
		if ($popup_options['tribulant_enable'] == 'on') {
			if (empty($popup_options['tribulant_listid'])) $errors[] = __('Invalid Tribulant Newsletters List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_tribulant_enable"])) $popup_options['tribulant_enable'] = "on";
		else $popup_options['tribulant_enable'] = "off";
		if (function_exists('wpml_get_fields')) {
			$custom_fields = wpml_get_fields();
			$fields = array();
			foreach($custom_fields as $custom_field) {
				if (isset($ulp->postdata['ulp_tribulant_field_'.$custom_field->slug])) {
					$fields[$custom_field->slug] = stripslashes(trim($ulp->postdata['ulp_tribulant_field_'.$custom_field->slug]));
				}
			}
			$popup_options['tribulant_fields'] = serialize($fields);
		}
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $Subscriber;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if (class_exists('wpMailPlugin')) {
			if ($popup_options['tribulant_enable'] == 'on') {
				try {
					$user_data['Subscriber'] = array(
						'active' => 'Y',
						'email' => $_subscriber['{subscription-email}'],
						'mandatory' => 'N',
						'mailinglists' => array($popup_options['tribulant_listid']));
					if (function_exists('wpml_get_fields')) {
						$custom_fields = wpml_get_fields();
						$fields = unserialize($popup_options['tribulant_fields']);
						if (!is_array($fields)) $fields = array();
						foreach($custom_fields as $custom_field) {
							if (!in_array($custom_field->slug, array('email', 'list'))) {
								if (array_key_exists($custom_field->slug, $fields)) $user_data['Subscriber'][$custom_field->slug] = strtr($fields[$custom_field->slug], $_subscriber);
							}
						}
					}
					if (!$Subscriber->save($user_data)) {
						if (array_key_exists('Subscriber', $Subscriber->data)) {
							if ($Subscriber->data['Subscriber']->id) {
								$user_data['Subscriber']['id'] = $Subscriber->data['Subscriber']->id;
								$user_data['Subscriber']['mailinglists'] = $Subscriber->data['Subscriber']->mailinglists;
								$Subscriber->save($user_data);
							}
						}
					}
//					print_r($Subscriber->data);
//					echo 1;
				} catch (Exception $e) {
				}
			}
		}
	}
}
$ulp_tribulant = new ulp_tribulant_class();
?>