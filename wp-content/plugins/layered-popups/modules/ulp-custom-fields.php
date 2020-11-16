<?php
/* Custom Fields integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_customfields_class {
	var $options = array(
		"customfields_date_enable" => "off",
		"customfields_date_js_disable" => "off",
		"customfields_date_format" => "Y-m-d"
	);
	var $default_popup_options = array(
		"customfields" => ""
	);
	var $field_types = array(
		"input" => "Text Field",
		"textarea" => "Text Area",
		"select" => "Drop-down List",
		"checkbox" => "Checkbox",
		"date" => "Date"
	);
	var $default_input = array(
		"name" => "Custom Text Field",
		"icon" => "fas fa-check",
		"mandatory" => "off",
		"placeholder" => "Enter your value...",
		"mask" => "",
		"value" => ""
	);
	var $default_date = array(
		"name" => "Custom Date Field",
		"icon" => "far fa-calendar-alt",
		"mandatory" => "off",
		"placeholder" => "Date",
		"value" => "",
		"mindatetype" => "none",
		"mindatevalue" => "",
		"maxdatetype" => "none",
		"maxdatevalue" => ""
	);
	var $default_textarea = array(
		"name" => "Custom Text Area",
		"mandatory" => "off",
		"placeholder" => "Enter your text...",
		"value" => ""
	);
	var $default_select = array(
		"name" => "Custom Drop-down List",
		"icon" => "fas fa-check",
		"mandatory" => "off",
		"placeholder" => "Select desired option...",
		"values" => "",
		"value" => ""
	);
	var $default_checkbox = array(
		"name" => "Custom Checkbox",
		"mandatory" => "off",
		"checked" => "off"
	);
	var $date_formats = array("m/d/Y", "d/m/Y", "d.m.Y", "Y-m-d");
	var $date_types = array(
		'none' => 'None',
		'yesterday' => 'Yesterday',
		'today' => 'Today',
		'tomorrow' => 'Tomorrow',
		'date' => 'Fixed date',
		'field' => 'Other field'
	);
	function __construct() {
		$this->get_options();
		if ($this->options['customfields_date_enable'] != 'on') {
			unset($this->field_types['date']);
		} else {
			add_action('ulp_wp_enqueue_scripts', array(&$this, 'front_enqueue_scripts'), 99);
			add_filter('ulp_remote_data', array(&$this, 'remote_data'), 10, 1);
		}

		if (is_admin()) {
			add_action('ulp_options_show', array(&$this, 'options_show'));
			add_action('ulp_options_update', array(&$this, 'options_update'));
			add_action('ulp_popup_options_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-customfields-addfield', array(&$this, "add_field"));
			add_action('ulp_js_build_preview_content', array(&$this, 'js_build_preview_content'));
			add_filter('ulp_export_full_popup_options', array(&$this, 'export_full_popup_options'), 10, 1);
			add_action('ulp_helper2_window', array(&$this, 'helper2_window'));
		}
		add_filter('ulp_front_popup_content', array(&$this, 'front_popup_content'), 10, 2);
		add_filter('ulp_front_fields_check', array(&$this, 'front_fields_check'), 10, 2);
		add_filter('ulp_log_custom_fields', array(&$this, 'log_custom_fields'), 10, 2);
		add_filter('ulp_subscriber_details', array(&$this, 'subscriber_details'), 10, 2);
		add_filter('ulp_subscriber_details_from_log', array(&$this, 'subscriber_details_from_log'), 10, 3);
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
			<h3>'.__('Custom Fields Global Settings', 'ulp').'</h3>
			<table class="ulp_useroptions">
				<tr>
					<th>'.__('Enable DatePicker', 'ulp').':</th>
					<td>
						<input type="checkbox" id="ulp_customfields_date_enable" name="ulp_customfields_date_enable" '.($this->options['customfields_date_enable'] == "on" ? 'checked="checked"' : '').'> '.__('Enable DatePicker field', 'ulp').'
						<br /><em>'.__('Activate this feature if you want to use DatePicker field on popups.', 'ulp').'</em>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<input type="checkbox" id="ulp_customfields_date_js_disable" name="ulp_customfields_date_js_disable" '.($this->options['customfields_date_js_disable'] == "on" ? 'checked="checked"' : '').'> '.__('Disable DatePicker JS loading', 'ulp').'
						<br /><em>'.__('If your theme or another plugin load <a href="https://xdsoft.net/jqplugins/datetimepicker/" target="_blank">DatePicker</a> plugin, you can turn it off to avoid conflicts.', 'ulp').'</em>
					</td>
				</tr>
				<tr>
					<th>'.__('Date format', 'ulp').':</th>
					<td>
						<select id="ulp_customfields_date_format" name="ulp_customfields_date_format">';
		foreach($this->date_formats as $format) {
			echo '
							<option value="'.esc_html($format).'"'.($this->options['customfields_date_format'] == $format ? ' selected="selected"' : '').'>'.esc_html($format).'</option>';
		}
		echo '
						</select>
						<br /><em>'.__('Set the format of the date.', 'ulp').'</em>
					</td>
				</tr>
			</table>';
		
	}
	function options_update() {
		$this->populate_options();
		if (isset($_POST['ulp_customfields_date_enable'])) $this->options['customfields_date_enable'] = 'on';
		else $this->options['customfields_date_enable'] = 'off';
		if (isset($_POST['ulp_customfields_date_js_disable'])) $this->options['customfields_date_js_disable'] = 'on';
		else $this->options['customfields_date_js_disable'] = 'off';
		$this->update_options();
	}
	function popup_options_show($_popup_options) {
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		$custom_fields = unserialize($popup_options['customfields']);
		echo '
				<h3>'.__('Custom Fields', 'ulp').'</h3>
				<div id="ulp-customfields">';
		if (!empty($custom_fields)) {
			foreach ($custom_fields as $field_id => $field) {
				$html = '';
				if (is_array($field) && array_key_exists('type', $field)) {
					if (array_key_exists($field['type'], $this->field_types)) {
						switch ($field['type']) {
							case 'input':
								$html = $this->get_input_settings($field_id, $field);
								break;

							case 'date':
								$html = $this->get_date_settings($field_id, $field);
								break;

							case 'textarea':
								$html = $this->get_textarea_settings($field_id, $field);
								break;

							case 'select':
								$html = $this->get_select_settings($field_id, $field);
								break;

							case 'checkbox':
								$html = $this->get_checkbox_settings($field_id, $field);
								break;
								
							default:
								break;
						}
					}
				}
				echo $html;
			}
		}
		echo '
				</div>
				<div style="margin-bottom: 5px;">
					<a id="ulp_customfields_button" class="ulp_button button-secondary" onclick="jQuery(\'#ulp-customfields-selector\').toggle(200); return false;">'.__('Add Custom Field', 'ulp').'</a>
					<img id="ulp-customfields-loading" class="ulp-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
					<div id="ulp-customfields-selector">';
		foreach ($this->field_types as $key => $value) {
			echo '
						<a class="ulp-customfields-selector-item" href="#" onclick="return ulp_customfields_addfield(\''.$key.'\');">'.$value.'</a>';
		}
		echo '
					</div>
				</div>
				<script>ulp_customfields_minmaxdate_options_set();</script>
				<div id="ulp-customfields-message" class="ulp-message"></div>';
	}
	function popup_options_check($_errors) {
		global $ulp;
		$errors = array();
		if (!isset($ulp->postdata['ulp_customfields_ids']) || !is_array($ulp->postdata['ulp_customfields_ids'])) return $_errors;
		foreach ($ulp->postdata['ulp_customfields_ids'] as $field_id) {
			if (isset($ulp->postdata['ulp_customfields_name_'.$field_id])) {
				$name = stripslashes(trim($ulp->postdata['ulp_customfields_name_'.$field_id]));
				if (empty($name)) $errors[] = __('Custom Field name can not be empty.', 'ulp');
			} else $errors[] = __('Invalid Custom Field name.', 'ulp');
			$type = stripslashes(trim($ulp->postdata['ulp_customfields_type_'.$field_id]));
			switch($type) {
				case 'select':
					$values = stripslashes(trim($ulp->postdata['ulp_customfields_values_'.$field_id]));
					if (empty($values)) $errors[] = __('Options list for Custom Drop-down List can not be empty.', 'ulp');
					break;
			
				default:
					break;
			}
		}
		return array_merge($_errors, $errors);
	}
	function popup_options_populate($_popup_options) {
		global $ulp;
		$popup_options = array();
		if (!isset($ulp->postdata['ulp_customfields_ids']) || !is_array($ulp->postdata['ulp_customfields_ids'])) return $_popup_options;
		$custom_fields = array();
		foreach ($ulp->postdata['ulp_customfields_ids'] as $field_id) {
			if (!empty($field_id)) {
				$type = stripslashes(trim($ulp->postdata['ulp_customfields_type_'.$field_id]));
				if (array_key_exists($type, $this->field_types)) {
					switch ($type) {
						case 'input':
							$custom_fields[$field_id]['type'] = $type;
							foreach ($this->default_input as $key => $value) {
								if (isset($ulp->postdata['ulp_customfields_'.$key.'_'.$field_id])) {
									$custom_fields[$field_id][$key] = stripslashes(trim($ulp->postdata['ulp_customfields_'.$key.'_'.$field_id]));
								} else $custom_fields[$field_id][$key] = $value;
							}
							if (isset($ulp->postdata["ulp_customfields_mandatory_".$field_id])) $custom_fields[$field_id]['mandatory'] = "on";
							else $custom_fields[$field_id]['mandatory'] = "off";
							break;

						case 'date':
							$custom_fields[$field_id]['type'] = $type;
							foreach ($this->default_date as $key => $value) {
								if (isset($ulp->postdata['ulp_customfields_'.$key.'_'.$field_id])) {
									$custom_fields[$field_id][$key] = stripslashes(trim($ulp->postdata['ulp_customfields_'.$key.'_'.$field_id]));
								} else $custom_fields[$field_id][$key] = $value;
							}
							if (isset($ulp->postdata["ulp_customfields_mandatory_".$field_id])) $custom_fields[$field_id]['mandatory'] = "on";
							else $custom_fields[$field_id]['mandatory'] = "off";
							if ($custom_fields[$field_id]['mindatetype'] == 'date') $custom_fields[$field_id]['mindatevalue'] = stripslashes(trim($ulp->postdata['ulp_customfields_mindate_date_'.$field_id]));
							if ($custom_fields[$field_id]['maxdatetype'] == 'date') $custom_fields[$field_id]['maxdatevalue'] = stripslashes(trim($ulp->postdata['ulp_customfields_maxdate_date_'.$field_id]));
							if ($custom_fields[$field_id]['mindatetype'] == 'field') $custom_fields[$field_id]['mindatevalue'] = stripslashes(trim($ulp->postdata['ulp_customfields_mindate_field_'.$field_id]));
							if ($custom_fields[$field_id]['maxdatetype'] == 'field') $custom_fields[$field_id]['maxdatevalue'] = stripslashes(trim($ulp->postdata['ulp_customfields_maxdate_field_'.$field_id]));
							break;

						case 'textarea':
							$custom_fields[$field_id]['type'] = $type;
							foreach ($this->default_textarea as $key => $value) {
								if (isset($ulp->postdata['ulp_customfields_'.$key.'_'.$field_id])) {
									$custom_fields[$field_id][$key] = stripslashes(trim($ulp->postdata['ulp_customfields_'.$key.'_'.$field_id]));
								} else $custom_fields[$field_id][$key] = $value;
							}
							if (isset($ulp->postdata["ulp_customfields_mandatory_".$field_id])) $custom_fields[$field_id]['mandatory'] = "on";
							else $custom_fields[$field_id]['mandatory'] = "off";
							break;

						case 'select':
							$custom_fields[$field_id]['type'] = $type;
							foreach ($this->default_select as $key => $value) {
								if (isset($ulp->postdata['ulp_customfields_'.$key.'_'.$field_id])) {
									$custom_fields[$field_id][$key] = stripslashes(trim($ulp->postdata['ulp_customfields_'.$key.'_'.$field_id]));
								} else $custom_fields[$field_id][$key] = $value;
							}
							if (isset($ulp->postdata["ulp_customfields_mandatory_".$field_id])) $custom_fields[$field_id]['mandatory'] = "on";
							else $custom_fields[$field_id]['mandatory'] = "off";
							break;

						case 'checkbox':
							$custom_fields[$field_id]['type'] = $type;
							foreach ($this->default_checkbox as $key => $value) {
								if (isset($ulp->postdata['ulp_customfields_'.$key.'_'.$field_id])) {
									$custom_fields[$field_id][$key] = stripslashes(trim($ulp->postdata['ulp_customfields_'.$key.'_'.$field_id]));
								} else $custom_fields[$field_id][$key] = $value;
							}
							if (isset($ulp->postdata["ulp_customfields_mandatory_".$field_id])) $custom_fields[$field_id]['mandatory'] = "on";
							else $custom_fields[$field_id]['mandatory'] = "off";
							if (isset($ulp->postdata["ulp_customfields_checked_".$field_id])) $custom_fields[$field_id]['checked'] = "on";
							else $custom_fields[$field_id]['checked'] = "off";
							break;
							
						default:
							break;
					}
				}
			}
		}
		$popup_options['customfields'] = serialize($custom_fields);
		return array_merge($_popup_options, $popup_options);
	}
	function add_field() {
		global $wpdb, $ulp;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_type']) || !array_key_exists($_POST['ulp_type'], $this->field_types)) exit;
			$field_type = trim(stripslashes($_POST['ulp_type']));
			$html = '';
			$field_id = $ulp->random_string(4);
			switch ($field_type) {
				case 'input':
					$html = $this->get_input_settings($field_id, $this->default_input, false);
					break;

				case 'date':
					$html = $this->get_date_settings($field_id, $this->default_date, false);
					break;

				case 'textarea':
					$html = $this->get_textarea_settings($field_id, $this->default_textarea, false);
					break;

				case 'select':
					$html = $this->get_select_settings($field_id, $this->default_select, false);
					break;

				case 'checkbox':
					$html = $this->get_checkbox_settings($field_id, $this->default_checkbox, false);
					break;
					
				default:
					$return_object = array();
					$return_object['status'] = 'ERROR';
					$return_object['message'] = __('The requested field type is not supported yet!', 'ulp');
					echo json_encode($return_object);
					exit;
					break;
			}
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $html;
			$return_object['id'] = $field_id;
			echo json_encode($return_object);
		}
		exit;
	}
	function js_build_preview_content() {
		global $ulp;
		echo '
			jQuery(".ulp-customfields-ids").each(function() {
				var field_id = jQuery(this).val();
				var type = jQuery("#ulp_customfields_type_"+field_id).val();
				var field = "";
				if (type == "input" || type == "date") {
					var custom_icon_html = "";';
		if ($ulp->options['fa_enable'] == 'on') {
			echo '
					if (jQuery("#ulp_input_icons").is(":checked")) {
						custom_icon_html = "<div class=\'ulp-fa-input-table\'><div class=\'ulp-fa-input-cell\'><i class=\'"+jQuery("#ulp-customfields-icon-"+field_id).val()+"\'></i></div></div>";
					}';
		}
		echo '
					field = "<input class=\'ulp-preview-input\' type=\'text\' placeholder=\'"+ulp_escape_html(jQuery("#ulp_customfields_placeholder_"+field_id).val())+"\' value=\'"+ulp_escape_html(jQuery("#ulp_customfields_value_"+field_id).val())+"\'>"+custom_icon_html+input_cover;
				} else if (type == "textarea") {
					field = "<textarea class=\'ulp-preview-input\' placeholder=\'"+ulp_escape_html(jQuery("#ulp_customfields_placeholder_"+field_id).val())+"\'>"+ulp_escape_html(jQuery("#ulp_customfields_value_"+field_id).val())+"</textarea>"+input_cover;
				} else if (type == "select") {
					var custom_icon_html = "";';
		if ($ulp->options['fa_enable'] == 'on') {
			echo '
					if (jQuery("#ulp_input_icons").is(":checked")) {
						custom_icon_html = "<div class=\'ulp-fa-input-table\'><div class=\'ulp-fa-input-cell\'><i class=\'"+jQuery("#ulp-customfields-icon-"+field_id).val()+"\'></i></div></div>";
					}';
		}
		echo '
					var options_html;
					var text = jQuery("#ulp_customfields_placeholder_"+field_id).val();
					if (text.length > 0) options_html = options_html+"<option value=\'\'>"+ulp_escape_html(text)+"</option>";
					text = jQuery("#ulp_customfields_values_"+field_id).val();
					var options = text.split(/\r?\n/);
					for (var i=0; i<options.length; i++) {
						options[i] = options[i].trim();
						if (options[i].length > 0) options_html = options_html+"<option value=\'"+ulp_escape_html(options[i])+"\'>"+ulp_escape_html(options[i])+"</option>";
					}
					field = "<select class=\'ulp-preview-input\'>"+options_html+"</select>"+custom_icon_html+input_cover;
				} else if (type == "checkbox") {
					var checbox_id = Math.floor(Math.random()*10000);
					var checked = "";
					if (jQuery("#ulp_customfields_checked_"+field_id).is(":checked")) {
						checked = " checked=\'checked\'";
					}
					field = "<div class=\'ulp-preview-checkbox\'><input type=\'checkbox\' id=\'cb"+checbox_id+"\'"+checked+" /><label for=\'cb"+checbox_id+"\'></label></div>";
				}
				content = content.replace("{custom-field-"+field_id+"}", field);
			});';
	}
	function get_input_settings($field_id, $parameters, $visible = true) {
		global $ulp;
		$parameters = array_merge($this->default_input, $parameters);
		$html = '
<div id="ulp-customfields-field-'.$field_id.'"'.($visible ? '' : ' style="display: none;"').'>
	<input class="ulp-customfields-ids" type="hidden" name="ulp_customfields_ids[]" value="'.$field_id.'" />
	<input type="hidden" id="ulp_customfields_type_'.$field_id.'" name="ulp_customfields_type_'.$field_id.'" value="input" />
	<table class="ulp_useroptions">
		<tr>
			<th>'.__('Shortcode', 'ulp').':</th>
			<td>
				<a class="ulp-delete-custom-field" href="#" onclick="return ulp_delete_custom_field(\''.$field_id.'\')" style="float: right;" title="'.__('Delete custom field', 'ulp').'"><i class="fas fa-trash-alt"></i></a>
				<input type="text" value="{custom-field-'.$field_id.'}" class="ulp-input-30p ulp-js-code" onclick="this.focus();this.select();" />
				<br /><em>'.__('Use this shortcode to insert the field into the popup.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th>'.__('Field name', 'ulp').':</th>
			<td>
				<input type="text" id="ulp_customfields_name_'.$field_id.'" name="ulp_customfields_name_'.$field_id.'" value="'.esc_html($parameters['name']).'" class="widefat">
				<br /><em>'.__('Enter the name of custom field. It is used for your own reference.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th></th>
			<td>
				<input type="checkbox" id="ulp_customfields_mandatory_'.$field_id.'" name="ulp_customfields_mandatory_'.$field_id.'" '.($parameters['mandatory'] == "on" ? 'checked="checked"' : '').'> '.__('Mandatory field', 'ulp').'
				<br /><em>'.__('Please tick checkbox to set the field as mandatory.', 'ulp').'</em>
			</td>
		</tr>';
		if ($ulp->options['fa_enable'] == 'on') {
			$html .= '
		<tr>
			<th>'.__('Field icon', 'ulp').':</th>
			<td>
				<span id="ulp-customfields-icon-'.$field_id.'-image" class="ulp-icon ulp-icon-active" title="'.__('Icons', 'ulp').'" onclick="jQuery(\'#ulp-customfields-icon-'.$field_id.'-set\').slideToggle(300);"><i class="'.$parameters['icon'].'"></i></span><br />
				<div id="ulp-customfields-icon-'.$field_id.'-set" class="ulp-icon-set">';
			if ($ulp->options['fa_solid_enable'] == 'on') {
				foreach ($ulp->fa_solid as $value) {
					$html .= '<span class="ulp-icon'.($parameters['icon'] == $value ? ' ulp-icon-active' : '').'" title="'.$value.'" onclick="ulp_seticon(this, \'ulp-customfields-icon-'.$field_id.'\');"><i class="fas fa-'.$value.'"></i></span>';
				}
			}
			if ($ulp->options['fa_regular_enable'] == 'on') {
				foreach ($ulp->fa_regular as $value) {
					$html .= '<span class="ulp-icon'.($parameters['icon'] == $value ? ' ulp-icon-active' : '').'" title="'.$value.'" onclick="ulp_seticon(this, \'ulp-customfields-icon-'.$field_id.'\');"><i class="far fa-'.$value.'"></i></span>';
				}
			}
			if ($ulp->options['fa_brands_enable'] == 'on') {
				foreach ($ulp->fa_brands as $value) {
					$html .= '<span class="ulp-icon'.($parameters['icon'] == $value ? ' ulp-icon-active' : '').'" title="'.$value.'" onclick="ulp_seticon(this, \'ulp-customfields-icon-'.$field_id.'\');"><i class="fab fa-'.$value.'"></i></span>';
				}
			}
			$html .= '
				</div>
				<input type="hidden" name="ulp_customfields_icon_'.$field_id.'" id="ulp-customfields-icon-'.$field_id.'" value="'.$parameters['icon'].'">
				<em>'.__('Select field icon.', 'ulp').'</em>
			</td>
		</tr>';
		}
		$html .= '
		<tr>
			<th>'.__('Placeholder', 'ulp').':</th>
			<td>
				<input type="text" id="ulp_customfields_placeholder_'.$field_id.'" name="ulp_customfields_placeholder_'.$field_id.'" value="'.esc_html($parameters['placeholder']).'" class="widefat">
				<br /><em>'.__('Enter the placeholder for custom field.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th>'.__('Default value', 'ulp').':</th>
			<td>
				<input type="text" id="ulp_customfields_value_'.$field_id.'" name="ulp_customfields_value_'.$field_id.'" value="'.esc_html($parameters['value']).'" class="widefat">
				<br /><em>'.__('Enter default value of the custom field.', 'ulp').'</em>
			</td>
		</tr>';
		if ($ulp->options['mask_enable'] == 'on') {
			$html .= '
		<tr>
			<th>'.__('Mask', 'ulp').':</th>
			<td>
				<input type="text" id="ulp_customfields_mask_'.$field_id.'" name="ulp_customfields_mask_'.$field_id.'" value="'.esc_html($parameters['mask']).'" class="widefat">
				<br /><em>'.__('Set custom field mask. For more details please visit <a target="_blank" href="http://igorescobar.github.io/jQuery-Mask-Plugin/">jQuery Mask plugin page</a>.', 'ulp').'</em>
			</td>
		</tr>';
		}
		$html .= '
	</table>
	<hr>
</div>';
		return $html;
	}
	function get_date_settings($field_id, $parameters, $visible = true) {
		global $ulp;
		$parameters = array_merge($this->default_date, $parameters);
		$html = '
<div id="ulp-customfields-field-'.$field_id.'"'.($visible ? '' : ' style="display: none;"').'>
	<input class="ulp-customfields-ids" type="hidden" name="ulp_customfields_ids[]" value="'.$field_id.'" />
	<input type="hidden" id="ulp_customfields_type_'.$field_id.'" name="ulp_customfields_type_'.$field_id.'" value="date" />
	<table class="ulp_useroptions">
		<tr>
			<th>'.__('Shortcode', 'ulp').':</th>
			<td>
				<a class="ulp-delete-custom-field" href="#" onclick="return ulp_delete_custom_field(\''.$field_id.'\')" style="float: right;" title="'.__('Delete custom field', 'ulp').'"><i class="fas fa-trash-alt"></i></a>
				<input type="text" value="{custom-field-'.$field_id.'}" class="ulp-input-30p ulp-js-code" onclick="this.focus();this.select();" />
				<br /><em>'.__('Use this shortcode to insert the field into the popup.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th>'.__('Field name', 'ulp').':</th>
			<td>
				<input type="text" id="ulp_customfields_name_'.$field_id.'" name="ulp_customfields_name_'.$field_id.'" value="'.esc_html($parameters['name']).'" class="widefat">
				<br /><em>'.__('Enter the name of custom field. It is used for your own reference.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th></th>
			<td>
				<input type="checkbox" id="ulp_customfields_mandatory_'.$field_id.'" name="ulp_customfields_mandatory_'.$field_id.'" '.($parameters['mandatory'] == "on" ? 'checked="checked"' : '').'> '.__('Mandatory field', 'ulp').'
				<br /><em>'.__('Please tick checkbox to set the field as mandatory.', 'ulp').'</em>
			</td>
		</tr>';
		if ($ulp->options['fa_enable'] == 'on') {
			$html .= '
		<tr>
			<th>'.__('Field icon', 'ulp').':</th>
			<td>
				<span id="ulp-customfields-icon-'.$field_id.'-image" class="ulp-icon ulp-icon-active" title="'.__('Icons', 'ulp').'" onclick="jQuery(\'#ulp-customfields-icon-'.$field_id.'-set\').slideToggle(300);"><i class="'.$parameters['icon'].'"></i></span><br />
				<div id="ulp-customfields-icon-'.$field_id.'-set" class="ulp-icon-set">';
			if ($ulp->options['fa_solid_enable'] == 'on') {
				foreach ($ulp->fa_solid as $value) {
					$html .= '<span class="ulp-icon'.($parameters['icon'] == $value ? ' ulp-icon-active' : '').'" title="'.$value.'" onclick="ulp_seticon(this, \'ulp-customfields-icon-'.$field_id.'\');"><i class="fas fa-'.$value.'"></i></span>';
				}
			}
			if ($ulp->options['fa_regular_enable'] == 'on') {
				foreach ($ulp->fa_regular as $value) {
					$html .= '<span class="ulp-icon'.($parameters['icon'] == $value ? ' ulp-icon-active' : '').'" title="'.$value.'" onclick="ulp_seticon(this, \'ulp-customfields-icon-'.$field_id.'\');"><i class="far fa-'.$value.'"></i></span>';
				}
			}
			if ($ulp->options['fa_brands_enable'] == 'on') {
				foreach ($ulp->fa_brands as $value) {
					$html .= '<span class="ulp-icon'.($parameters['icon'] == $value ? ' ulp-icon-active' : '').'" title="'.$value.'" onclick="ulp_seticon(this, \'ulp-customfields-icon-'.$field_id.'\');"><i class="fab fa-'.$value.'"></i></span>';
				}
			}
			$html .= '
				</div>
				<input type="hidden" name="ulp_customfields_icon_'.$field_id.'" id="ulp-customfields-icon-'.$field_id.'" value="'.$parameters['icon'].'">
				<em>'.__('Select field icon.', 'ulp').'</em>
			</td>
		</tr>';
		}
		$html .= '
		<tr>
			<th>'.__('Placeholder', 'ulp').':</th>
			<td>
				<input type="text" id="ulp_customfields_placeholder_'.$field_id.'" name="ulp_customfields_placeholder_'.$field_id.'" value="'.esc_html($parameters['placeholder']).'" class="widefat">
				<br /><em>'.__('Enter the placeholder for custom field.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th>'.__('Default value', 'ulp').':</th>
			<td>
				<input type="text" id="ulp_customfields_value_'.$field_id.'" name="ulp_customfields_value_'.$field_id.'" value="'.esc_html($parameters['value']).'" class="ulp-date ulp-input-30p">
				<br /><em>'.__('Enter default value of the custom field.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th>'.__('Min value', 'ulp').':</th>
			<td>
				<select id="ulp_customfields_mindatetype_'.$field_id.'" name="ulp_customfields_mindatetype_'.$field_id.'" onchange="ulp_customfields_datetype_changed(this);">';
		foreach($this->date_types as $key => $value) {
			$html .= '
					<option value="'.esc_html($key).'"'.($parameters['mindatetype'] == $key ? ' selected="selected"' : '').'>'.esc_html($value).'</option>';
		}
		$html .= '
				</select>
				<br /><em>'.__('Set the min date that can be selected.', 'ulp').'</em>
				<div class="ulp_customfields_minmaxdate_date"'.($parameters['mindatetype'] == 'date' ? '' : ' style="display:none;"').'>
					<input type="text" id="ulp_customfields_mindate_date_'.$field_id.'" name="ulp_customfields_mindate_date_'.$field_id.'" value="'.($parameters['mindatetype'] == 'date' ? esc_html($parameters['mindatevalue']) : '').'" class="ulp-date ulp-input-30p" />
					<br /><em>'.__('Enter the earliest date that can be selected.', 'ulp').'</em>
				</div>
				<div class="ulp_customfields_minmaxdate_field"'.($parameters['mindatetype'] == 'field' ? '' : ' style="display:none;"').'>
					<select id="ulp_customfields_mindate_field_'.$field_id.'" name="ulp_customfields_mindate_field_'.$field_id.'" data-value="'.($parameters['mindatetype'] == 'field' ? esc_html($parameters['mindatevalue']) : '').'" onchange="ulp_customfields_minmaxdate_changed(this);">
					</select>
					<br /><em>'.__('Select date field. Its value will be the earliest date that can be selected.', 'ulp').'</em>
				</div>
			</td>
		</tr>
		<tr>
			<th>'.__('Max value', 'ulp').':</th>
			<td>
				<select id="ulp_customfields_maxdatetype_'.$field_id.'" name="ulp_customfields_maxdatetype_'.$field_id.'" onchange="ulp_customfields_datetype_changed(this);">';
		foreach($this->date_types as $key => $value) {
			$html .= '
					<option value="'.esc_html($key).'"'.($parameters['maxdatetype'] == $key ? ' selected="selected"' : '').'>'.esc_html($value).'</option>';
		}
		$html .= '
				</select>
				<br /><em>'.__('Set the max date that can be selected.', 'ulp').'</em>
				<div class="ulp_customfields_minmaxdate_date"'.($parameters['maxdatetype'] == 'date' ? '' : ' style="display:none;"').'>
					<input type="text" id="ulp_customfields_maxdate_date_'.$field_id.'" name="ulp_customfields_maxdate_date_'.$field_id.'" value="'.($parameters['maxdatetype'] == 'date' ? esc_html($parameters['maxdatevalue']) : '').'" class="ulp-date ulp-input-30p" />
					<br /><em>'.__('Enter the latest date that can be selected.', 'ulp').'</em>
				</div>
				<div class="ulp_customfields_minmaxdate_field"'.($parameters['maxdatetype'] == 'field' ? '' : ' style="display:none;"').'>
					<select id="ulp_customfields_maxdate_field_'.$field_id.'" name="ulp_customfields_maxdate_field_'.$field_id.'" data-value="'.($parameters['maxdatetype'] == 'field' ? esc_html($parameters['maxdatevalue']) : '').'" onchange="ulp_customfields_minmaxdate_changed(this);">
					</select>
					<br /><em>'.__('Select date field. Its value will be the latest date that can be selected.', 'ulp').'</em>
				</div>
			</td>
		</tr>
	</table>
	<hr>
	<script>
		jQuery("#ulp-customfields-field-'.$field_id.' .ulp-date").datetimepicker({
			format: "'.$this->options['customfields_date_format'].'",
			formatDate: "'.$this->options['customfields_date_format'].'",
			timepicker: false
		});
	</script>
</div>';
		return $html;
	}
	function get_textarea_settings($field_id, $parameters, $visible = true) {
		global $ulp;
		$parameters = array_merge($this->default_textarea, $parameters);
		$html = '
<div id="ulp-customfields-field-'.$field_id.'"'.($visible ? '' : ' style="display: none;"').'>
	<input class="ulp-customfields-ids" type="hidden" name="ulp_customfields_ids[]" value="'.$field_id.'" />
	<input type="hidden" id="ulp_customfields_type_'.$field_id.'" name="ulp_customfields_type_'.$field_id.'" value="textarea" />
	<table class="ulp_useroptions">
		<tr>
			<th>'.__('Shortcode', 'ulp').':</th>
			<td>
				<a class="ulp-delete-custom-field" href="#" onclick="return ulp_delete_custom_field(\''.$field_id.'\')" style="float: right;" title="'.__('Delete custom field', 'ulp').'"><i class="fas fa-trash-alt"></i></a>
				<input type="text" value="{custom-field-'.$field_id.'}" class="ulp-input-30p ulp-js-code" onclick="this.focus();this.select();" />
				<br /><em>'.__('Use this shortcode to insert the textarea into the popup.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th>'.__('Text area name', 'ulp').':</th>
			<td>
				<input type="text" id="ulp_customfields_name_'.$field_id.'" name="ulp_customfields_name_'.$field_id.'" value="'.esc_html($parameters['name']).'" class="widefat">
				<br /><em>'.__('Enter the name of custom textarea. It is used for your own reference.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th></th>
			<td>
				<input type="checkbox" id="ulp_customfields_mandatory_'.$field_id.'" name="ulp_customfields_mandatory_'.$field_id.'" '.($parameters['mandatory'] == "on" ? 'checked="checked"' : '').'> '.__('Mandatory field', 'ulp').'
				<br /><em>'.__('Please tick checkbox to set the textarea as mandatory.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th>'.__('Placeholder', 'ulp').':</th>
			<td>
				<input type="text" id="ulp_customfields_placeholder_'.$field_id.'" name="ulp_customfields_placeholder_'.$field_id.'" value="'.esc_html($parameters['placeholder']).'" class="widefat">
				<br /><em>'.__('Enter the placeholder for custom textarea.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th>'.__('Default value', 'ulp').':</th>
			<td>
				<textarea id="ulp_customfields_value_'.$field_id.'" name="ulp_customfields_value_'.$field_id.'" class="widefat" style="height: 120px;">'.esc_html($parameters['value']).'</textarea>
				<br /><em>'.__('Enter default value of the custom textarea.', 'ulp').'</em>
			</td>
		</tr>
	</table>
	<hr>
</div>';
		return $html;
	}
	function get_select_settings($field_id, $parameters, $visible = true) {
		global $ulp;
		$parameters = array_merge($this->default_select, $parameters);
		$html = '
<div id="ulp-customfields-field-'.$field_id.'"'.($visible ? '' : ' style="display: none;"').'>
	<input class="ulp-customfields-ids" type="hidden" name="ulp_customfields_ids[]" value="'.$field_id.'" />
	<input type="hidden" id="ulp_customfields_type_'.$field_id.'" name="ulp_customfields_type_'.$field_id.'" value="select" />
	<table class="ulp_useroptions">
		<tr>
			<th>'.__('Shortcode', 'ulp').':</th>
			<td>
				<a class="ulp-delete-custom-field" href="#" onclick="return ulp_delete_custom_field(\''.$field_id.'\')" style="float: right;" title="'.__('Delete custom field', 'ulp').'"><i class="fas fa-trash-alt"></i></a>
				<input type="text" value="{custom-field-'.$field_id.'}" class="ulp-input-30p ulp-js-code" onclick="this.focus();this.select();" />
				<br /><em>'.__('Use this shortcode to insert the field into the popup.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th>'.__('Field name', 'ulp').':</th>
			<td>
				<input type="text" id="ulp_customfields_name_'.$field_id.'" name="ulp_customfields_name_'.$field_id.'" value="'.esc_html($parameters['name']).'" class="widefat">
				<br /><em>'.__('Enter the name of drop-down list. It is used for your own reference.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th></th>
			<td>
				<input type="checkbox" id="ulp_customfields_mandatory_'.$field_id.'" name="ulp_customfields_mandatory_'.$field_id.'" '.($parameters['mandatory'] == "on" ? 'checked="checked"' : '').'> '.__('Mandatory field', 'ulp').'
				<br /><em>'.__('Please tick checkbox to set the field as mandatory.', 'ulp').'</em>
			</td>
		</tr>';
		if ($ulp->options['fa_enable'] == 'on') {
			$html .= '
		<tr>
			<th>'.__('Field icon', 'ulp').':</th>
			<td>
				<span id="ulp-customfields-icon-'.$field_id.'-image" class="ulp-icon ulp-icon-active" title="'.__('Icons', 'ulp').'" onclick="jQuery(\'#ulp-customfields-icon-'.$field_id.'-set\').slideToggle(300);"><i class="'.$parameters['icon'].'"></i></span><br />
				<div id="ulp-customfields-icon-'.$field_id.'-set" class="ulp-icon-set">';
			if ($ulp->options['fa_solid_enable'] == 'on') {
				foreach ($ulp->fa_solid as $value) {
					$html .= '<span class="ulp-icon'.($parameters['icon'] == $value ? ' ulp-icon-active' : '').'" title="'.$value.'" onclick="ulp_seticon(this, \'ulp-customfields-icon-'.$field_id.'\');"><i class="fas fa-'.$value.'"></i></span>';
				}
			}
			if ($ulp->options['fa_regular_enable'] == 'on') {
				foreach ($ulp->fa_regular as $value) {
					$html .= '<span class="ulp-icon'.($parameters['icon'] == $value ? ' ulp-icon-active' : '').'" title="'.$value.'" onclick="ulp_seticon(this, \'ulp-customfields-icon-'.$field_id.'\');"><i class="far fa-'.$value.'"></i></span>';
				}
			}
			if ($ulp->options['fa_brands_enable'] == 'on') {
				foreach ($ulp->fa_brands as $value) {
					$html .= '<span class="ulp-icon'.($parameters['icon'] == $value ? ' ulp-icon-active' : '').'" title="'.$value.'" onclick="ulp_seticon(this, \'ulp-customfields-icon-'.$field_id.'\');"><i class="fab fa-'.$value.'"></i></span>';
				}
			}
			$html .= '
				</div>
				<input type="hidden" name="ulp_customfields_icon_'.$field_id.'" id="ulp-customfields-icon-'.$field_id.'" value="'.$parameters['icon'].'">
				<em>'.__('Select field icon.', 'ulp').'</em>
			</td>
		</tr>';
		}
		$html .= '
		<tr>
			<th>'.__('Placeholder', 'ulp').':</th>
			<td>
				<input type="text" id="ulp_customfields_placeholder_'.$field_id.'" name="ulp_customfields_placeholder_'.$field_id.'" value="'.esc_html($parameters['placeholder']).'" class="widefat">
				<br /><em>'.__('Enter the placeholder for drop-down list. This value is added as a first option into drop-down list.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th>'.__('Options', 'ulp').':</th>
			<td>
				<textarea id="ulp_customfields_values_'.$field_id.'" name="ulp_customfields_values_'.$field_id.'" class="widefat" style="height: 120px;">'.esc_html($parameters['values']).'</textarea>
				<br /><em>'.__('Enter the list of options. One option per line.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th>'.__('Default option', 'ulp').':</th>
			<td>
				<input type="text" id="ulp_customfields_value_'.$field_id.'" name="ulp_customfields_value_'.$field_id.'" value="'.esc_html($parameters['value']).'" class="widefat">
				<br /><em>'.__('Enter default value of drop-down list.', 'ulp').'</em>
			</td>
		</tr>
	</table>
	<hr>
</div>';
		return $html;
	}
	function get_checkbox_settings($field_id, $parameters, $visible = true) {
		global $ulp;
		$parameters = array_merge($this->default_checkbox, $parameters);
		$html = '
<div id="ulp-customfields-field-'.$field_id.'"'.($visible ? '' : ' style="display: none;"').'>
	<input class="ulp-customfields-ids" type="hidden" name="ulp_customfields_ids[]" value="'.$field_id.'" />
	<input type="hidden" id="ulp_customfields_type_'.$field_id.'" name="ulp_customfields_type_'.$field_id.'" value="checkbox" />
	<table class="ulp_useroptions">
		<tr>
			<th>'.__('Shortcode', 'ulp').':</th>
			<td>
				<a class="ulp-delete-custom-field" href="#" onclick="return ulp_delete_custom_field(\''.$field_id.'\')" style="float: right;" title="'.__('Delete custom field', 'ulp').'"><i class="fas fa-trash-alt"></i></a>
				<input type="text" value="{custom-field-'.$field_id.'}" class="ulp-input-30p ulp-js-code" onclick="this.focus();this.select();" />
				<br /><em>'.__('Use this shortcode to insert the field into the popup.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th>'.__('Field name', 'ulp').':</th>
			<td>
				<input type="text" id="ulp_customfields_name_'.$field_id.'" name="ulp_customfields_name_'.$field_id.'" value="'.esc_html($parameters['name']).'" class="widefat">
				<br /><em>'.__('Enter the name of custom field. It is used for your own reference.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th></th>
			<td>
				<input type="checkbox" id="ulp_customfields_mandatory_'.$field_id.'" name="ulp_customfields_mandatory_'.$field_id.'" '.($parameters['mandatory'] == "on" ? 'checked="checked"' : '').'> '.__('Mandatory field', 'ulp').'
				<br /><em>'.__('Please tick checkbox to set the field as mandatory.', 'ulp').'</em>
			</td>
		</tr>
		<tr>
			<th>'.__('Checked', 'ulp').':</th>
			<td>
				<input type="checkbox" id="ulp_customfields_checked_'.$field_id.'" name="ulp_customfields_checked_'.$field_id.'"'.($parameters['checked'] == 'on' ? ' checked="checked"' : '').'" /> '.__('Checked by default', 'ulp').'
				<br /><em>'.__('Tick checkbox to set default state as checked.', 'ulp').'</em>
			</td>
		</tr>
	</table>
	<hr>
</div>';
		return $html;
	}
	function front_enqueue_scripts() {
		global $ulp;
		if ($this->options['customfields_date_js_disable'] != 'on') {
			if ($ulp->ext_options['minified_sources'] == 'on') {
				wp_enqueue_style('jquery.datetimepicker', $ulp->plugins_url.'/css/jquery.datetimepicker.min.css', array(), ULP_VERSION);
				wp_enqueue_script('jquery.datetimepicker', $ulp->plugins_url.'/js/jquery.datetimepicker.full.min.js', array('jquery'), ULP_VERSION, true);
			} else {
				wp_enqueue_style('jquery.datetimepicker', $ulp->plugins_url.'/css/jquery.datetimepicker.min.css', array(), ULP_VERSION);
				wp_enqueue_script('jquery.datetimepicker', $ulp->plugins_url.'/js/jquery.datetimepicker.full.min.js', array('jquery'), ULP_VERSION, true);
			}
		}
	}
	function front_popup_content($_content, $_popup_options) {
		global $ulp;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		$custom_fields = unserialize($popup_options['customfields']);
		if (!empty($custom_fields)) {
			foreach ($custom_fields as $field_id => $field) {
				$html = '';
				if (is_array($field) && array_key_exists('type', $field)) {
					if (array_key_exists($field['type'], $this->field_types)) {
						switch ($field['type']) {
							case 'input':
								$mask_class = '';
								$mask = '';
								if ($ulp->options['mask_enable'] == 'on' && !empty($field['mask'])) {
									$mask_class = ' ulp-input-mask';
									$mask = ' data-mask="'.esc_html($field['mask']).'"';
								}
								$html = '<input class="ulp-input ulp-input-field'.$mask_class.'"'.$mask.' type="text" name="ulp-custom-field-'.$field_id.'" placeholder="'.esc_html($field['placeholder']).'" value="'.esc_html($field['value']).'" onfocus="jQuery(this).removeClass(\'ulp-input-error\');" />'.($ulp->options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on' ? '<div class="ulp-fa-input-table"><div class="ulp-fa-input-cell"><i class="'.esc_html($field['icon']).'"></i></div></div>' : '');
								break;

							case 'date':
								$html = '<input class="ulp-input ulp-date ulp-input-field" type="text" name="ulp-custom-field-'.$field_id.'" placeholder="'.esc_html($field['placeholder']).'" value="'.esc_html($field['value']).'" onfocus="jQuery(this).removeClass(\'ulp-input-error\');" data-format="'.esc_html($this->options['customfields_date_format']).'" data-min-type="'.$field['mindatetype'].'" data-min-value="'.$field['mindatevalue'].'" data-max-type="'.$field['maxdatetype'].'" data-max-value="'.$field['maxdatevalue'].'" />'.($ulp->options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on' ? '<div class="ulp-fa-input-table"><div class="ulp-fa-input-cell"><i class="'.esc_html($field['icon']).'"></i></div></div>' : '');
								break;
								
							case 'textarea':
								$html = '<textarea class="ulp-input ulp-input-field" name="ulp-custom-field-'.$field_id.'" placeholder="'.esc_html($field['placeholder']).'" onfocus="jQuery(this).removeClass(\'ulp-input-error\');">'.esc_html($field['value']).'</textarea>';
								break;

							case 'select':
								$options_html = '';
								if (!empty($field['placeholder'])) $options_html .= '<option value="">'.esc_html($field['placeholder']).'</option>';
								$options = explode("\n", $field['values']);
								foreach ($options as $option) {
									$option = trim($option);
									$option_array = explode('==', $option, 2);
									if (sizeof($option_array) > 1) {
										$option_value = trim($option_array[0]);
										$option_label = trim($option_array[1]);
									} else {
										$option_value = $option_array[0];
										$option_label = $option_array[0];
									}
									if (!empty($option)) $options_html .= '<option value="'.esc_html($option_value).'"'.($option_value == $field['value'] ? ' selected="selected"' : '').'>'.esc_html($option_label).'</option>';
								}
								$html = '<select class="ulp-input ulp-input-field" name="ulp-custom-field-'.$field_id.'" onfocus="jQuery(this).removeClass(\'ulp-input-error\');">'.$options_html.'</select>'.($ulp->options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on' ? '<div class="ulp-fa-input-table"><div class="ulp-fa-input-cell"><i class="'.esc_html($field['icon']).'"></i></div></div>' : '');
								break;
								
							case 'checkbox':
								$checbox_id = $ulp->random_string(6);
								$html = '<div class="ulp-checkbox" name="ulp-custom-field-'.$field_id.'"><input class="ulp-input-field" type="checkbox" name="ulp-custom-field-'.$field_id.'" value="off" id="cb'.$checbox_id.'"'.($field['checked'] == 'on' ? ' checked="checked"' : '').' onclick="jQuery(this).parent().removeClass(\'ulp-input-error\');" /><label for="cb'.$checbox_id.'"></label></div>';
								break;
								
							default:
								break;
						}
					}
				}
				$_content = str_replace('{custom-field-'.$field_id.'}', $html, $_content);
			}
		}
		return $_content;
	}
	function front_fields_check($_field_errors, $_popup_options) {
		if (isset($_REQUEST['encoded']) && $_REQUEST['encoded'] == true) {
			$request_data = json_decode(base64_decode(trim(stripslashes($_REQUEST['data']))), true);
		} else $request_data = $_REQUEST;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		$custom_fields = unserialize($popup_options['customfields']);
		if (!empty($custom_fields)) {
			foreach ($custom_fields as $field_id => $field) {
				if (is_array($field) && array_key_exists('type', $field)) {
					if (array_key_exists($field['type'], $this->field_types)) {
						switch ($field['type']) {
							case 'date':
								if (isset($request_data['ulp-custom-field-'.$field_id])) $value = trim(stripslashes($request_data['ulp-custom-field-'.$field_id]));
								else $value = '';
								if ($value == $field['placeholder']) $value = '';
								if ($field['mandatory'] == 'on' && empty($value)) $_field_errors['ulp-custom-field-'.$field_id] = 'ERROR';
								else if (!empty($value)) {
									if (strlen($value) != 10) $_field_errors['ulp-custom-field-'.$field_id] = 'ERROR';
									else {
										$year = '';
										$month = '';
										$day = '';
										switch ($this->options['customfields_date_format']) {
											case 'm/d/Y':
												list($month, $day, $year) = array_pad(explode('/', $value), 3, '');
												break;
											case 'd/m/Y':
												list($day, $month, $year) = array_pad(explode('/', $value), 3, '');
												break;
											case 'd.m.Y':
												list($day, $month, $year) = array_pad(explode('.', $value), 3, '');
												break;
											case 'Y-m-d':
												list($year, $month, $day) = array_pad(explode('-', $value), 3, '');
												break;
											default:
												break;
										}
										if (!checkdate($month, $day, $year)) $_field_errors['ulp-custom-field-'.$field_id] = 'ERROR';
									}
								}
								break;
							case 'input':
							case 'textarea':
							case 'select':
								if (isset($request_data['ulp-custom-field-'.$field_id])) $value = trim(stripslashes($request_data['ulp-custom-field-'.$field_id]));
								else $value = '';
								if ($value == $field['placeholder']) $value = '';
								if ($field['mandatory'] == 'on' && empty($value)) $_field_errors['ulp-custom-field-'.$field_id] = 'ERROR';
								break;
							case 'checkbox':
								if (isset($request_data['ulp-custom-field-'.$field_id])) $value = trim(stripslashes($request_data['ulp-custom-field-'.$field_id]));
								else $value = '';
								if ($field['mandatory'] == 'on' && (empty($value) || $value == 'off')) $_field_errors['ulp-custom-field-'.$field_id] = 'ERROR';
								break;
							default:
								break;
						}
					}
				}
			}
		}
		return $_field_errors;
	}
	function log_custom_fields($_custom_fields, $_popup_options) {
		if (isset($_REQUEST['encoded']) && $_REQUEST['encoded'] == true) {
			$request_data = json_decode(base64_decode(trim(stripslashes($_REQUEST['data']))), true);
		} else $request_data = $_REQUEST;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		$custom_fields = unserialize($popup_options['customfields']);
		if (!empty($custom_fields)) {
			foreach ($custom_fields as $field_id => $field) {
				if (is_array($field) && array_key_exists('type', $field)) {
					if (array_key_exists($field['type'], $this->field_types)) {
						switch ($field['type']) {
							case 'input':
							case 'date':
							case 'textarea':
							case 'select':
							case 'checkbox':
								if (isset($request_data['ulp-custom-field-'.$field_id])) $value = trim(stripslashes($request_data['ulp-custom-field-'.$field_id]));
								else $value = '';
								if ($field['type'] != 'checkbox' && $value == $field['placeholder']) $value = '';
								$_custom_fields[$field_id]['name'] = $field['name'];
								$_custom_fields[$field_id]['value'] = $value;
								break;
								
							default:
								break;
						}
					}
				}
			}
		}
		return $_custom_fields;
	}
	function subscriber_details($_subscriber, $_popup_options) {
		if (isset($_REQUEST['encoded']) && $_REQUEST['encoded'] == true) {
			$request_data = json_decode(base64_decode(trim(stripslashes($_REQUEST['data']))), true);
		} else $request_data = $_REQUEST;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		$custom_fields = unserialize($popup_options['customfields']);
		if (!empty($custom_fields)) {
			foreach ($custom_fields as $field_id => $field) {
				if (is_array($field) && array_key_exists('type', $field)) {
					if (array_key_exists($field['type'], $this->field_types)) {
						switch ($field['type']) {
							case 'input':
							case 'date':
							case 'textarea':
							case 'select':
							case 'checkbox':
								if (isset($request_data['ulp-custom-field-'.$field_id])) $value = trim(stripslashes($request_data['ulp-custom-field-'.$field_id]));
								else $value = '';
								if ($field['type'] != 'checkbox' && $value == $field['placeholder']) $value = '';
								$_subscriber['{custom-field-'.$field_id.'}'] = $value;
								break;
								
							default:
								break;
						}
					}
				}
			}
		}
		return $_subscriber;
	}
	function subscriber_details_from_log($_subscriber, $_popup_options, $_custom_fields) {
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		$custom_fields = unserialize($popup_options['customfields']);
		if (!empty($custom_fields)) {
			foreach ($custom_fields as $field_id => $field) {
				if (is_array($field) && array_key_exists('type', $field)) {
					if (array_key_exists($field['type'], $this->field_types)) {
						switch ($field['type']) {
							case 'input':
							case 'date':
							case 'textarea':
							case 'select':
							case 'checkbox':
								if (array_key_exists($field_id, $_custom_fields) && is_array($_custom_fields[$field_id])) $value = $_custom_fields[$field_id]['value'];
								else $value = '';
								$_subscriber['{custom-field-'.$field_id.'}'] = $value;
								break;
							default:
								break;
						}
					}
				}
			}
		}
		return $_subscriber;
	}
	function export_full_popup_options($_popup_options) {
		return array_merge($_popup_options, $this->default_popup_options);
	}
	function helper2_window() {
		global $ulp;
		echo '
<script>
var ulp_customfields_helper_add_layer;
if (typeof ulpext_helper_add_layer == "function") { 
	ulp_customfields_helper_add_layer = ulpext_helper_add_layer;
}
ulpext_helper_add_layer = function() {
	if (typeof ulp_customfields_helper_add_layer == "function") { 
		ulp_customfields_helper_add_layer();
	}
	jQuery(".ulp-helper-add-layer-item-custom-field").remove();
	jQuery(".ulp-customfields-ids").each(function() {
		var field_id = jQuery(this).val();
		var icon = "fa-code";';
		if ($ulp->options['fa_enable'] == 'on') {
			echo '
		icon = jQuery("#ulp-customfields-icon-"+field_id).val();
		if (!icon || icon == "" || icon == "fa-noicon") icon = "fas fa-code";';
		}
		echo '
		var type = jQuery("#ulp_customfields_type_"+field_id).val();
		if (type == "checkbox") icon = "fas fa-check";
		var label = jQuery("#ulp_customfields_name_"+field_id).val();
		label = label.trim();
		if (label.length == 0) label = "Custom Field: "+field_id;
		var comment = "Insert custom field: "+field_id;
		var item = "<div class=\'ulp-helper-add-layer-item ulp-helper-add-layer-item-custom-field\' id=\'ulp-helper-add-layer-item-custom-field-"+field_id+"\' data-unique=\'{custom-field-"+field_id+"}\' data-item=\'custom-field-"+field_id+"\' onclick=\"ulp_helper_add_layer_process(\'custom-field-"+field_id+"\');\"><i class=\'"+icon+"\'></i><label>"+ulp_escape_html(label)+"</label><span>"+ulp_escape_html(comment)+"</span></div>";
		jQuery("#ulp-helper-group-form").append(item);
	});
	return false;
}
var ulp_customfields_helper_add_layer_process;
if (typeof ulpext_helper_add_layer_process == "function") { 
	ulp_customfields_helper_add_layer_process = ulpext_helper_add_layer_process;
}
ulpext_helper_add_layer_process = function(content_type) {
	if (typeof ulp_customfields_helper_add_layer_process == "function") { 
		var result = ulp_customfields_helper_add_layer_process(content_type);
		if (result) return true;
	}
	var found = false;
	jQuery(".ulp-customfields-ids").each(function() {
		var field_id = jQuery(this).val();
		if (content_type == "custom-field-"+field_id) {
			var width, height;
			var label = jQuery("#ulp_customfields_name_"+field_id).val();
			label = label.trim();
			if (label.length == 0) label = "Custom Field: "+field_id;
			var type = jQuery("#ulp_customfields_type_"+field_id).val();
			switch (type) {
				case "textarea":
					width = 250;
					height = 120;
					break;
				case "checkbox":
					width = 24;
					height = 24;
					break;
				default:
					width = 250;
					height = 40;
					break;
			}
			ulp_helper_close();
			ulp_neo_add_layer({"title":label,"content":"{custom-field-"+field_id+"}","width":width,"height":height});
			found = true;
			return false;
		}
	});
	return found;
}
</script>';
	}
	function remote_data($_remote_data) {
		global $ulp;
		if ($this->options['customfields_date_js_disable'] != 'on') {
			if ($ulp->ext_options['minified_sources'] == 'on') {
				$_remote_data['resources']['css'][] = $ulp->plugins_url.'/css/jquery.datetimepicker.min.css?ver='.ULP_VERSION;
				$_remote_data['resources']['js'][] = $ulp->plugins_url.'/js/jquery.datetimepicker.full.min.js?ver='.ULP_VERSION;
			} else {
				$_remote_data['resources']['css'][] = $ulp->plugins_url.'/css/jquery.datetimepicker.css?ver='.ULP_VERSION;
				$_remote_data['resources']['js'][] = $ulp->plugins_url.'/js/jquery.datetimepicker.full.js?ver='.ULP_VERSION;
			}
		}
		return $_remote_data;
	}
}
$ulp_customfields = new ulp_customfields_class();
?>