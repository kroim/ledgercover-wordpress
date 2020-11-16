<?php
/* Arigato Pro integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_arigatopro_class {
	var $default_popup_options = array(
		'arigatopro_enable' => "off",
		'arigatopro_listid' => "",
		'arigatopro_fields' => array()
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
			add_action('wp_ajax_ulp-arigatopro-fields', array(&$this, "show_fields"));
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
				<h3>'.__('Arigato Pro Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">';
		if (defined('BFTPRO_PATH')) {
			if (file_exists(BFTPRO_PATH.'/models/list.php')) include_once(BFTPRO_PATH.'/models/list.php');
			$arigato_list = new BFTProList();
			$lists = $arigato_list->select();
			if (sizeof($lists) == 0) {
				echo '
					<tr>
						<th>'.__('Enable Arigato Pro', 'ulp').':</th>
						<td>'.__('Please', 'ulp').' <a href="'.admin_url('admin.php').'?page=bftpro_mailing_lists">'.__('create', 'ulp').'</a> '.__('at least one list.', 'ulp').'</td>
					</tr>';
			} else {
				echo '
					<tr>
						<th>'.__('Enable Arigato Pro', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_arigatopro_enable" name="ulp_arigatopro_enable" '.($popup_options['arigatopro_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Arigato Pro', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Arigato Pro.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Mailing List', 'ulp').':</th>
						<td>
							<select name="ulp_arigatopro_listid" id="ulp-arigatopro-list-id" class="ic_input_m">
								<option value="">'.__('Select Mailing List.', 'ulp').'</option>';
				foreach ($lists as $list) {
					echo '
								<option value="'.$list->id.'"'.($list->id == $popup_options['arigatopro_listid'] ? ' selected="selected"' : '').'>'.$list->name.'</option>';
				}
				echo '
							</select>
							<br /><em>'.__('Select Mailing List.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-arigatopro-fields-html">';
				if (!empty($popup_options['arigatopro_listid'])) {
					$fields = $this->get_fields_html($popup_options['arigatopro_listid'], $popup_options['arigatopro_fields']);
					echo $fields;
				}
				echo '
							</div>
							<a id="ulp_arigatopro_fields_button" class="ulp_button button-secondary" onclick="return ulp_arigatopro_loadfields();">'.__('Load Custom Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-arigatopro-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load custom fields list. Ignore if you do not need specify custom fields values.', 'ulp').'</em>
							<script>
								function ulp_arigatopro_loadfields() {
									jQuery("#ulp-arigatopro-fields-loading").fadeIn(350);
									jQuery(".ulp-arigatopro-fields-html").slideUp(350);
									var data = {action: "ulp-arigatopro-fields", ulp_list: jQuery("#ulp-arigatopro-list-id").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-arigatopro-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-arigatopro-fields-html").html(data.html);
												jQuery(".ulp-arigatopro-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-arigatopro-fields-html").html("<div class=\'ulp-arigatopro-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not fetch data.', 'ulp').'</strong></div>");
												jQuery(".ulp-arigatopro-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-arigatopro-fields-html").html("<div class=\'ulp-arigatopro-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not fetch data.', 'ulp').'</strong></div>");
											jQuery(".ulp-arigatopro-fields-html").slideDown(350);
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>';
			}
		} else {
			echo '
					<tr>
						<th>'.__('Enable Arigato Pro', 'ulp').':</th>
						<td>'.__('Please install and activate <a target="_blank" href="http://calendarscripts.info/bft-pro/">Arigato Pro</a> plugin.', 'ulp').'</td>
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
		if (isset($ulp->postdata["ulp_arigatopro_enable"])) $popup_options['arigatopro_enable'] = "on";
		else $popup_options['arigatopro_enable'] = "off";
		if ($popup_options['arigatopro_enable'] == 'on') {
			if (empty($popup_options['arigatopro_listid'])) $errors[] = __('Invalid Arigato Pro List ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_arigatopro_enable"])) $popup_options['arigatopro_enable'] = "on";
		else $popup_options['arigatopro_enable'] = "off";
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_arigatopro_field_')) == 'ulp_arigatopro_field_') {
				$field = substr($key, strlen('ulp_arigatopro_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['arigatopro_fields'] = $fields;
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if (defined('BFTPRO_PATH')) {
			if (file_exists(BFTPRO_PATH.'/models/user.php')) include_once(BFTPRO_PATH.'/models/user.php');
			//if (file_exists(BFTPRO_PATH.'/models/sender.php')) include_once(BFTPRO_PATH.'/models/sender.php');
			$arigato_user = new BFTProUser();
			$data = array(
				'name' => $_subscriber['{subscription-name}'],
				'email' => $_subscriber['{subscription-email}'],
				'list_id' => $popup_options['arigatopro_listid'],
				'ip' => $_SERVER['REMOTE_ADDR'],
				'date' => date('Y').'-'.date('m').'-'.date('d'),
				'source' => 'Layered Popups',
				'status' => 1
			);
			foreach($popup_options['arigatopro_fields'] as $key => $value) {
				if (!empty($value)) $data['field_'.$key] = $value;
			}
			$message = "";
			$arigato_user->subscribe($data, $message, true);
		}
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_list']) || empty($_POST['ulp_list'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-arigatopro-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid Mailing List.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$list = trim(stripslashes($_POST['ulp_list']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($list, $this->default_popup_options['arigatopro_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_list, $_fields) {
		$fields = '';
		if (defined('BFTPRO_PATH')) {
			if (file_exists(BFTPRO_PATH.'/models/field.php')) include_once(BFTPRO_PATH.'/models/field.php');
			$arigato_field = new BFTProField();
			$result = $arigato_field->select($_list);
			if (!is_array($result)) {
				$fields = '<div class="ulp-arigatopro-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not fetch data.', 'ulp').'</strong></div>';
			} else {
				if (!empty($result)) {
					$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Arigato Pro fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
					foreach ($result as $field) {
						$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field->label).':</strong></td>
					<td>
						<input type="text" id="ulp_arigatopro_field_'.esc_html($field->id).'" name="ulp_arigatopro_field_'.esc_html($field->id).'" value="'.esc_html(array_key_exists($field->id, $_fields) ? $_fields[$field->id] : '').'" class="widefat" />
						<br /><em>'.esc_html($field->label.' ('.$field->name.')').'</em>
					</td>
				</tr>';
					}
					$fields .= '
			</table>';
				} else {
					$fields = '<div class="ulp-arigatopro-grouping" style="margin-bottom: 10px;"><strong>'.__('No custom fields found.', 'ulp').'</strong></div>';
				}
			}
		} else {
			$fields = '<div class="ulp-arigatopro-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	
}
$ulp_arigatopro = new ulp_arigatopro_class();
?>