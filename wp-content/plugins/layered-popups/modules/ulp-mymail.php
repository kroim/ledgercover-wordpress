<?php
/* Mailster integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_mymail_class {
	var $default_popup_options = array(
		'mymail_enable' => "off",
		'mymail_listid' => "",
		'mymail_double' => "off",
		'mymail_firstname' => '{subscription-name}',
		'mymail_lastname' => '',
		'mymail_fields' => array()
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
				<h3>'.__('Mailster Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">';
		if (function_exists('mailster_subscribe') || function_exists('mailster')) {
			if (function_exists('mailster')) {
				$lists = mailster('lists')->get();
				$create_list_url = 'edit.php?post_type=newsletter&page=mailster_lists';
			} else {
				$lists = get_terms('newsletter_lists', array('hide_empty' => false));
				$create_list_url = 'edit-tags.php?taxonomy=newsletter_lists&post_type=newsletter';
			}
			if (sizeof($lists) == 0) {
				echo '
					<tr>
						<th>'.__('Enable Mailster', 'ulp').':</th>
						<td>'.__('Please', 'ulp').' <a href="'.$create_list_url.'">'.__('create', 'ulp').'</a> '.__('at least one list.', 'ulp').'</td>
					</tr>';
			} else {
				echo '
					<tr>
						<th>'.__('Enable Mailster', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mymail_enable" name="ulp_mymail_enable" '.($popup_options['mymail_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Mailster', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Mailster.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<select name="ulp_mymail_listid" class="ic_input_m">';
				foreach ($lists as $list) {
					if (function_exists('mailster')) $id = $list->ID;
					else $id = $list->term_id;
					echo '
								<option value="'.$id.'"'.($id == $popup_options['mymail_listid'] ? ' selected="selected"' : '').'>'.$list->name.'</option>';
				}
				echo '
							</select>
							<br /><em>'.__('Select your List ID.', 'ulp').'</em>
						</td>
					</tr>';
				if (function_exists('mailster_option')) {
					$custom_fields = mailster_option('custom_field', array());
					echo '
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-mymail-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Mailster custom fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">
									<tr>
										<td style="width: 120px;"><strong>'.__('Email', 'ulp').':</strong></td>
										<td>
											<input type="text" name="ulp_mymail_email" value="{subscription-email}" class="widefat" readonly="readonly" />
											<br /><em>'.__('Email address', 'ulp').'</em>
										</td>
									</tr>
									<tr>
										<td style="width: 120px;"><strong>'.__('First name', 'ulp').':</strong></td>
										<td>
											<input type="text" name="ulp_mymail_firstname" value="'.esc_html($popup_options['mymail_firstname']).'" class="widefat" />
											<br /><em>'.__('First name', 'ulp').'</em>
										</td>
									</tr>
									<tr>
										<td style="width: 120px;"><strong>'.__('Last name', 'ulp').':</strong></td>
										<td>
											<input type="text" name="ulp_mymail_lastname" value="'.esc_html($popup_options['mymail_lastname']).'" class="widefat" />
											<br /><em>'.__('Last name', 'ulp').'</em>
										</td>
									</tr>';
					if ($custom_fields) {
						foreach ($custom_fields as $id => $cdata) {
							echo '
									<tr>
										<td style="width: 120px;"><strong>'.esc_html($cdata['name']).':</strong></td>
										<td>
											<input type="text" id="ulp_mymail_field_'.esc_html($id).'" name="ulp_mymail_field_'.esc_html($id).'" value="'.(array_key_exists($id, (array)$popup_options['mymail_fields']) ? esc_html($popup_options['mymail_fields'][$id]) : '').'" class="widefat" />
											<br /><em>'.esc_html('{'.$id.'}').'</em>
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
				echo '
					<tr>
						<th>'.__('Double Opt-In', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_mymail_double" name="ulp_mymail_double" '.($popup_options['mymail_double'] == "on" ? 'checked="checked"' : '').'"> '.__('Enable Double Opt-In', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to enable double opt-in feature.', 'ulp').'</em>
						</td>
					</tr>';
			}
		} else {
			echo '
					<tr>
						<th>'.__('Enable Mailster', 'ulp').':</th>
						<td>'.__('Please install and activate <a target="_blank" href="http://codecanyon.net/item/mymail-email-newsletter-plugin-for-wordpress/3078294?ref=halfdata">Mailster</a> plugin.', 'ulp').'</td>
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
		if (isset($ulp->postdata["ulp_mymail_enable"])) $popup_options['mymail_enable'] = "on";
		else $popup_options['mymail_enable'] = "off";
		if (isset($ulp->postdata["ulp_mymail_double"])) $popup_options['mymail_double'] = "on";
		else $popup_options['mymail_double'] = "off";
		if ($popup_options['mymail_enable'] == 'on') {
			if (empty($popup_options['mymail_listid'])) $errors[] = __('Invalid Mailster List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_mymail_enable"])) $popup_options['mymail_enable'] = "on";
		else $popup_options['mymail_enable'] = "off";
		if (isset($ulp->postdata["ulp_mymail_double"])) $popup_options['mymail_double'] = "on";
		else $popup_options['mymail_double'] = "off";
		if (function_exists('mailster_option')) {
			$custom_fields = mailster_option('custom_field', array());
			$fields = array();
			foreach($custom_fields as $key => $value) {
				if (isset($ulp->postdata['ulp_mymail_field_'.$key])) {
					$fields[$key] = stripslashes(trim($ulp->postdata['ulp_mymail_field_'.$key]));
				}
			}
			$popup_options['mymail_fields'] = $fields;
		}
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if (function_exists('mailster_subscribe') || function_exists('mailster')) {
			if ($popup_options['mymail_enable'] == 'on') {
				if (function_exists('mailster')) {
					$list = mailster('lists')->get($popup_options['mymail_listid']);
				} else {
					$list = get_term_by('id', $popup_options['mymail_listid'], 'newsletter_lists');
				}
				if (!empty($list)) {
					try {
						if ($popup_options['mymail_double'] == "on") $double = true;
						else $double = false;
						if (function_exists('mailster')) {
							$mailster_subscriber = mailster('subscribers')->get_by_mail($_subscriber['{subscription-email}']);
							$entry = array(
								'firstname' => strtr($popup_options['mymail_firstname'], $_subscriber),
								'lastname' => strtr($popup_options['mymail_lastname'], $_subscriber),
								'email' => $_subscriber['{subscription-email}'],
								'ip' => $_SERVER['REMOTE_ADDR'],
								'signup_ip' => $_SERVER['REMOTE_ADDR'],
								'referer' => $_SERVER['HTTP_REFERER'],
								'signup' => time()
							);
							if (!$mailster_subscriber || $mailster_subscriber->status != 1) $entry['status'] = $double ? 0 : 1;
							if (function_exists('mailster_option')) {
								$custom_fields = mailster_option('custom_field', array());
								$fields = $popup_options['mymail_fields'];
								if (!is_array($fields)) $fields = array();
								foreach($custom_fields as $key => $value) {
									if (array_key_exists($key, $fields)) $entry[$key] = strtr($fields[$key], $_subscriber);
								}
							}
							$subscriber_id = mailster('subscribers')->add($entry, true);
							if (is_wp_error( $subscriber_id )) return;
							$result = mailster('subscribers')->assign_lists($subscriber_id, array($list->ID));
						} else {
							$result = mailster_subscribe($_subscriber['{subscription-email}'], array('firstname' => $_subscriber['{subscription-name}']), array($list->slug), $double);
						}
					} catch (Exception $e) {
					}
				}
			}
		}
	}
}
$ulp_mymail = new ulp_mymail_class();
?>