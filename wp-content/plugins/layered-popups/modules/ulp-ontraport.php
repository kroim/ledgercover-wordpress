<?php
/* Ontraport (Office Auto Pilot) integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_ontraport_class {
	var $default_popup_options = array(
		'ontraport_enable' => 'off',
		'ontraport_app_id' => '',
		'ontraport_api_key' => '',
		'ontraport_tags' => array(),
		'ontraport_sequences' => array(),
		'ontraport_fields' => array('email' => '{subscription-email}')
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
			add_action('wp_ajax_ulp-ontraport-tags', array(&$this, "admin_tags_html"));
			add_action('wp_ajax_ulp-ontraport-tags-load', array(&$this, "admin_tags_load_html"));
			add_action('wp_ajax_ulp-ontraport-sequences', array(&$this, "admin_sequences_html"));
			add_action('wp_ajax_ulp-ontraport-sequences-load', array(&$this, "admin_sequences_load_html"));
			add_action('wp_ajax_ulp-ontraport-fields', array(&$this, "admin_fields_html"));
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
				<h3>'.__('Ontraport Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Ontraport', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_ontraport_enable" name="ulp_ontraport_enable" '.($popup_options['ontraport_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Ontraport (Office Auto Pilot)', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Ontraport.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('App ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_ontraport_app_id" name="ulp_ontraport_app_id" value="'.esc_html($popup_options['ontraport_app_id']).'" class="widefat" onchange="ulp_ontraport_handler();">
							<br /><em>'.__('Enter your Ontraport App ID. It can be requested in your <a href="https://app.ontraport.com/#!/api_settings/listAll" target="_blank">Administration settings</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_ontraport_api_key" name="ulp_ontraport_api_key" value="'.esc_html($popup_options['ontraport_api_key']).'" class="widefat" onchange="ulp_ontraport_handler();">
							<br /><em>'.__('Enter your Ontraport API Key. It can be requested in your <a href="https://app.ontraport.com/#!/api_settings/listAll" target="_blank">Administration settings</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Tags', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-ontraport-tags-html">';
		if (!empty($popup_options['ontraport_app_id']) && !empty($popup_options['ontraport_api_key'])) {
			$tags_data = $this->get_tags_html($popup_options['ontraport_app_id'], $popup_options['ontraport_api_key'], $popup_options['ontraport_tags']);
			if ($tags_data['status'] == 'OK') echo $tags_data['html'];
		}
		echo '
							</div>
							<a class="ulp-button ulp-button-small" onclick="return ulp_ontraport_loadtags(this);"><i class="fas fa-check"></i><label>Load Tags</label></a>
							<br /><em>'.__('Click the button to (re)load tags list. Ignore if you do not need specify tags.', 'ulp').'</em>
							<script>
								function ulp_ontraport_loadtags(_object) {
									if (ulp_saving) return;
									ulp_saving = true;
									jQuery(_object).addClass("ulp-button-disabled");
									jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
									jQuery(".ulp-ontraport-tags-html").slideUp(350);
									var post_data = {action: "ulp-ontraport-tags", ulp_app_id: jQuery("#ulp_ontraport_app_id").val(), ulp_api_key: jQuery("#ulp_ontraport_api_key").val()};
									jQuery.ajax({
										type	: "POST",
										url		: "'.admin_url('admin-ajax.php').'", 
										data	: post_data,
										success	: function(return_data) {
											jQuery(_object).removeClass("ulp-button-disabled");
											jQuery(_object).find("i").attr("class", "fas fa-check");
											var data;
											try {
												if (typeof return_data == "object") data = return_data;
												else data = jQuery.parseJSON(return_data);
												if (data.status == "OK") {
													jQuery(".ulp-ontraport-tags-html").html(data.html);
													jQuery(".ulp-ontraport-tags-html").slideDown(350);
												} else if (data.status == "ERROR") {
													ulp_global_message_show("danger", data.message);
												} else {
													ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
												}
											} catch(error) {
												ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
											}
											ulp_saving = false;
										},
										error	: function(XMLHttpRequest, textStatus, errorThrown) {
											jQuery(_object).removeClass("ulp-button-disabled");
											jQuery(_object).find("i").attr("class", "fas fa-check");
											ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
											ulp_saving = false;
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Sequences', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-ontraport-sequences-html">';
		if (!empty($popup_options['ontraport_app_id']) && !empty($popup_options['ontraport_api_key'])) {
			$sequences_data = $this->get_sequences_html($popup_options['ontraport_app_id'], $popup_options['ontraport_api_key'], $popup_options['ontraport_sequences']);
			if ($sequences_data['status'] == 'OK') echo $sequences_data['html'];
		}
		echo '
							</div>
							<a class="ulp-button ulp-button-small" onclick="return ulp_ontraport_loadsequences(this);"><i class="fas fa-check"></i><label>Load Sequences</label></a>
							<br /><em>'.__('Click the button to (re)load sequences list. Ignore if you do not need specify sequences.', 'ulp').'</em>
							<script>
								function ulp_ontraport_loadsequences(_object) {
									if (ulp_saving) return;
									ulp_saving = true;
									jQuery(_object).addClass("ulp-button-disabled");
									jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
									jQuery(".ulp-ontraport-sequences-html").slideUp(350);
									var post_data = {action: "ulp-ontraport-sequences", ulp_app_id: jQuery("#ulp_ontraport_app_id").val(), ulp_api_key: jQuery("#ulp_ontraport_api_key").val()};
									jQuery.ajax({
										type	: "POST",
										url		: "'.admin_url('admin-ajax.php').'", 
										data	: post_data,
										success	: function(return_data) {
											jQuery(_object).removeClass("ulp-button-disabled");
											jQuery(_object).find("i").attr("class", "fas fa-check");
											var data;
											try {
												if (typeof return_data == "object") data = return_data;
												else data = jQuery.parseJSON(return_data);
												if (data.status == "OK") {
													jQuery(".ulp-ontraport-sequences-html").html(data.html);
													jQuery(".ulp-ontraport-sequences-html").slideDown(350);
												} else if (data.status == "ERROR") {
													ulp_global_message_show("danger", data.message);
												} else {
													ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
												}
											} catch(error) {
												ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
											}
											ulp_saving = false;
										},
										error	: function(XMLHttpRequest, textStatus, errorThrown) {
											jQuery(_object).removeClass("ulp-button-disabled");
											jQuery(_object).find("i").attr("class", "fas fa-check");
											ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
											ulp_saving = false;
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-ontraport-fields-html">';
		if (!empty($popup_options['ontraport_app_id']) && !empty($popup_options['ontraport_api_key'])) {
			$fields_data = $this->get_fields_html($popup_options['ontraport_app_id'], $popup_options['ontraport_api_key'], $popup_options['ontraport_fields']);
			if ($fields_data['status'] == 'OK') echo $fields_data['html'];
		}
		echo '
							</div>
							<a class="ulp-button ulp-button-small" onclick="return ulp_ontraport_loadfields(this);"><i class="fas fa-check"></i><label>Load Fields</label></a>
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields.', 'ulp').'</em>
							<script>
								function ulp_ontraport_loadfields(_object) {
									if (ulp_saving) return;
									ulp_saving = true;
									jQuery(_object).addClass("ulp-button-disabled");
									jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
									jQuery(".ulp-ontraport-fields-html").slideUp(350);
									var post_data = {action: "ulp-ontraport-fields", ulp_app_id: jQuery("#ulp_ontraport_app_id").val(), ulp_api_key: jQuery("#ulp_ontraport_api_key").val()};
									jQuery.ajax({
										type	: "POST",
										url		: "'.admin_url('admin-ajax.php').'", 
										data	: post_data,
										success	: function(return_data) {
											jQuery(_object).removeClass("ulp-button-disabled");
											jQuery(_object).find("i").attr("class", "fas fa-check");
											var data;
											try {
												if (typeof return_data == "object") data = return_data;
												else data = jQuery.parseJSON(return_data);
												if (data.status == "OK") {
													jQuery(".ulp-ontraport-fields-html").html(data.html);
													jQuery(".ulp-ontraport-fields-html").slideDown(350);
												} else if (data.status == "ERROR") {
													ulp_global_message_show("danger", data.message);
												} else {
													ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
												}
											} catch(error) {
												ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
											}
											ulp_saving = false;
										},
										error	: function(XMLHttpRequest, textStatus, errorThrown) {
											jQuery(_object).removeClass("ulp-button-disabled");
											jQuery(_object).find("i").attr("class", "fas fa-check");
											ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
											ulp_saving = false;
										}
									});
									return false;
								}
							</script>
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
		if (isset($ulp->postdata["ulp_ontraport_enable"])) $popup_options['ontraport_enable'] = "on";
		else $popup_options['ontraport_enable'] = "off";
		if ($popup_options['ontraport_enable'] == 'on') {
			if (empty($popup_options['ontraport_app_id'])) $errors[] = __('Invalid Ontraport App ID', 'ulp');
			if (empty($popup_options['ontraport_api_key'])) $errors[] = __('Invalid Ontraport API key', 'ulp');
		}
		return array_merge($_errors, $errors);
	}
	function popup_options_populate($_popup_options) {
		global $ulp;
		$popup_options = array();
		foreach ($this->default_popup_options as $key => $value) {
			if (isset($ulp->postdata['ulp_'.$key])) {
				if (is_array($ulp->postdata['ulp_'.$key])) $popup_options[$key] = $ulp->postdata['ulp_'.$key];
				else $popup_options[$key] = stripslashes(trim($ulp->postdata['ulp_'.$key]));
			}
		}
		if (isset($ulp->postdata["ulp_ontraport_enable"])) $popup_options['ontraport_enable'] = "on";
		else $popup_options['ontraport_enable'] = "off";
		
		return array_merge($_popup_options, $popup_options);
	}

	function admin_fields_html() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!array_key_exists('ulp_app_id', $_REQUEST) || empty($_REQUEST['ulp_app_id']) || !array_key_exists('ulp_api_key', $_REQUEST) || empty($_REQUEST['ulp_api_key'])) {
				$return_object = array('status' => 'ERROR', 'message' => __('Invalid API credentials.', 'ulp'));
				echo json_encode($return_object);
				exit;
			}
			$return_object = $this->get_fields_html($_REQUEST['ulp_app_id'], $_REQUEST['ulp_api_key'], $this->default_popup_options['ontraport_fields']);
			echo json_encode($return_object);
		}
		exit;
	}

	function get_fields_html($_app_id, $_api_key, $_fields) {
		global $wpdb, $ulp;
		$html = '';
		$result = $this->connect($_app_id, $_api_key, 'Contacts/meta');
		if (empty($result) || !is_array($result)) {
			return array('status' => 'ERROR', 'message' => __('Inavlid API credentials.', 'ulp'));
		}
		if (array_key_exists('data', $result) && (empty($result['data']) || empty($result['data'][0]['fields']))) {
			return array('status' => 'ERROR', 'message' => __('No fields found.', 'ulp'));
		}
		$html = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Ontraport fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
		foreach ($result['data'][0]['fields'] as $field_key => $field_value) {
			if (is_array($field_value)) {
				if ($field_value['editable']) {
					$html .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field_value['alias']).':</strong></td>
					<td>
						<input type="text" name="ulp_ontraport_fields['.esc_html($field_key).']" value="'.esc_html(array_key_exists($field_key, $_fields) ? $_fields[$field_key] : '').'" class="widefat"'.($field_key == 'email' ? ' readonly="readonly"' : '').' />
						<br /><em>'.esc_html($field_value['alias'].' ('.$field_key.')').'</em>
					</td>
				</tr>';
				}
			}
		}
		$html .= '
			</table>';
		return array('status' => 'OK', 'html' => $html);
	}

	
	function admin_tags_html() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!array_key_exists('ulp_app_id', $_REQUEST) || empty($_REQUEST['ulp_app_id']) || !array_key_exists('ulp_api_key', $_REQUEST) || empty($_REQUEST['ulp_api_key'])) {
				$return_object = array('status' => 'ERROR', 'message' => __('Invalid API credentials.', 'ulp'));
				echo json_encode($return_object);
				exit;
			}
			$return_object = $this->get_tags_html($_REQUEST['ulp_app_id'], $_REQUEST['ulp_api_key'], $this->default_popup_options['ontraport_tags']);
			echo json_encode($return_object);
		}
		exit;
	}

	function admin_tags_load_html() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!array_key_exists('ulp_ontraport_app_id', $_REQUEST) || empty($_REQUEST['ulp_ontraport_app_id']) || !array_key_exists('ulp_ontraport_api_key', $_REQUEST) || empty($_REQUEST['ulp_ontraport_api_key'])) {
				$return_object = array('status' => 'ERROR', 'message' => __('Invalid API credentials.', 'ulp'));
				echo json_encode($return_object);
				exit;
			}
			if (!array_key_exists('start', $_REQUEST) || empty($_REQUEST['start']) || !array_key_exists('range', $_REQUEST) || empty($_REQUEST['range'])) {
				$return_object = array('status' => 'ERROR', 'message' => __('Invalid request.', 'ulp'));
				echo json_encode($return_object);
				exit;
			}
			$tags_selected = array();
			if (array_key_exists('ulp_ontraport_tags_selected', $_REQUEST)) $tags_selected = explode(',', $_REQUEST['ulp_ontraport_tags_selected']);
			$return_object = $this->get_tags_html($_REQUEST['ulp_ontraport_app_id'], $_REQUEST['ulp_ontraport_api_key'], $tags_selected, intval($_REQUEST['start']));
			echo json_encode($return_object);
		}
		exit;
	}

	function get_tags_html($_app_id, $_api_key, $_tags, $_start = 0) {
		global $wpdb, $ulp;
		$html = '';
		$result = $this->connect($_app_id, $_api_key, 'Tags?range=50&start='.$_start.'&sort=tag_id&sortDir=asc');
		if (empty($result) || !is_array($result)) {
			return array('status' => 'ERROR', 'message' => __('Inavlid API credentials.', 'ulp'));
		}
		if (array_key_exists('data', $result) && empty($result['data'])) {
			return array('status' => 'ERROR', 'message' => __('No more Tags found.', 'ulp'));
		}
		foreach ($result['data'] as $tag) {
			if (in_array($tag['tag_id'], $_tags)) continue;
			$html .= '
	<div class="ulp-ajax-multiselect-record">
		<input type="checkbox" id="ulp-ontraport-tag-'.esc_html($tag['tag_id']).'" name="ulp_ontraport_tags['.esc_html($tag['tag_id']).']" value="'.esc_html($tag['tag_id']).'"><label for="ulp-ontraport-tag-'.esc_html($tag['tag_id']).'"></label><label for="ulp-ontraport-tag-'.esc_html($tag['tag_id']).'">'.esc_html($tag['tag_name'].' (ID: '.$tag['tag_id'].')').'</label>
	</div>';
		}
		$more = (sizeof($result['data']) >= 50 ? 'on' : 'off');
		if ($_start == 0) {
			$html_selected = '';
			if (sizeof($_tags) > 0) {
				$result = $this->connect($_app_id, $_api_key, 'Tags?ids='.implode(',',$_tags).'&sort=tag_id&sortDir=asc');
				if (empty($result) || !is_array($result)) {
					return array('status' => 'ERROR', 'message' => __('Inavlid API credentials.', 'ulp'));
				}
				foreach ($result['data'] as $tag) {
					$html_selected .= '
			<div class="ulp-ajax-multiselect-record">
				<input type="checkbox" id="ulp-ontraport-tag-'.esc_html($tag['tag_id']).'" checked="checked" name="ulp_ontraport_tags['.esc_html($tag['tag_id']).']" value="'.esc_html($tag['tag_id']).'"><label for="ulp-ontraport-tag-'.esc_html($tag['tag_id']).'"></label><label for="ulp-ontraport-tag-'.esc_html($tag['tag_id']).'">'.esc_html($tag['tag_name'].' (ID: '.$tag['tag_id'].')').'</label>
			</div>';
				}
			}
			$html = '
<input type="hidden" name="ulp_ontraport_tags_selected" value="'.esc_html(implode(',', $_tags)).'" />
<div class="ulp-ajax-multiselect" data-start="50" data-range="50" data-action="ulp-ontraport-tags-load" data-deps="ulp_ontraport_app_id,ulp_ontraport_api_key,ulp_ontraport_tags_selected" data-more="'.$more.'" onscroll="ulp_ajax_multiselect_scroll(this);">
	'.$html_selected.$html.'
	<div class="ulp-ajax-multiselect-loading"><i class="fas fa-spin fa-spinner"></i></div>
</div>';
		}
		return array('status' => 'OK', 'html' => $html, 'more' => $more);
	}

	function admin_sequences_html() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!array_key_exists('ulp_app_id', $_REQUEST) || empty($_REQUEST['ulp_app_id']) || !array_key_exists('ulp_api_key', $_REQUEST) || empty($_REQUEST['ulp_api_key'])) {
				$return_object = array('status' => 'ERROR', 'message' => __('Invalid API credentials.', 'ulp'));
				echo json_encode($return_object);
				exit;
			}
			$return_object = $this->get_sequences_html($_REQUEST['ulp_app_id'], $_REQUEST['ulp_api_key'], $this->default_popup_options['ontraport_sequences']);
			echo json_encode($return_object);
		}
		exit;
	}

	function admin_sequences_load_html() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!array_key_exists('ulp_ontraport_app_id', $_REQUEST) || empty($_REQUEST['ulp_ontraport_app_id']) || !array_key_exists('ulp_ontraport_api_key', $_REQUEST) || empty($_REQUEST['ulp_ontraport_api_key'])) {
				$return_object = array('status' => 'ERROR', 'message' => __('Invalid API credentials.', 'ulp'));
				echo json_encode($return_object);
				exit;
			}
			if (!array_key_exists('start', $_REQUEST) || empty($_REQUEST['start']) || !array_key_exists('range', $_REQUEST) || empty($_REQUEST['range'])) {
				$return_object = array('status' => 'ERROR', 'message' => __('Invalid request.', 'ulp'));
				echo json_encode($return_object);
				exit;
			}
			$sequences_selected = array();
			if (array_key_exists('ulp_ontraport_sequences_selected', $_REQUEST)) $sequences_selected = explode(',', $_REQUEST['ulp_ontraport_sequences_selected']);
			$return_object = $this->get_sequences_html($_REQUEST['ulp_ontraport_app_id'], $_REQUEST['ulp_ontraport_api_key'], $sequences_selected, intval($_REQUEST['start']));
			echo json_encode($return_object);
		}
		exit;
	}

	function get_sequences_html($_app_id, $_api_key, $_sequences, $_start = 0) {
		global $wpdb, $ulp;
		$html = '';
		$result = $this->connect($_app_id, $_api_key, 'Sequences?range=50&start='.$_start.'&sort=drip_id&sortDir=asc');
		if (empty($result) || !is_array($result)) {
			return array('status' => 'ERROR', 'message' => __('Inavlid API credentials.', 'ulp'));
		}
		if (array_key_exists('data', $result) && empty($result['data'])) {
			return array('status' => 'ERROR', 'message' => __('No more Sequences found.', 'ulp'));
		}
		foreach ($result['data'] as $sequence) {
			if (in_array($sequence['drip_id'], $_sequences)) continue;
			$html .= '
	<div class="ulp-ajax-multiselect-record">
		<input type="checkbox" id="ulp-ontraport-sequence-'.esc_html($sequence['drip_id']).'" name="ulp_ontraport_sequences['.esc_html($sequence['drip_id']).']" value="'.esc_html($sequence['drip_id']).'"><label for="ulp-ontraport-sequence-'.esc_html($sequence['drip_id']).'"></label><label for="ulp-ontraport-sequence-'.esc_html($sequence['drip_id']).'">'.esc_html($sequence['name'].' (ID: '.$sequence['drip_id'].')').'</label>
	</div>';
		}
		$more = (sizeof($result['data']) >= 50 ? 'on' : 'off');
		if ($_start == 0) {
			$html_selected = '';
			if (sizeof($_sequences) > 0) {
				$result = $this->connect($_app_id, $_api_key, 'Sequences?ids='.implode(',',$_sequences).'&sort=drip_id&sortDir=asc');
				if (empty($result) || !is_array($result)) {
					return array('status' => 'ERROR', 'message' => __('Inavlid API credentials.', 'ulp'));
				}
				foreach ($result['data'] as $sequence) {
					$html_selected .= '
			<div class="ulp-ajax-multiselect-record">
				<input type="checkbox" id="ulp-ontraport-sequence-'.esc_html($sequence['drip_id']).'" checked="checked" name="ulp_ontraport_sequences['.esc_html($sequence['drip_id']).']" value="'.esc_html($sequence['drip_id']).'"><label for="ulp-ontraport-sequence-'.esc_html($sequence['drip_id']).'"></label><label for="ulp-ontraport-sequence-'.esc_html($sequence['drip_id']).'">'.esc_html($sequence['name'].' (ID: '.$sequence['drip_id'].')').'</label>
			</div>';
				}
			}
			$html = '
<input type="hidden" name="ulp_ontraport_sequences_selected" value="'.esc_html(implode(',', $_sequences)).'" />
<div class="ulp-ajax-multiselect" data-start="50" data-range="50" data-action="ulp-ontraport-sequences-load" data-deps="ulp_ontraport_app_id,ulp_ontraport_api_key,ulp_ontraport_sequences_selected" data-more="'.$more.'" onscroll="ulp_ajax_multiselect_scroll(this);">
	'.$html_selected.$html.'
	<div class="ulp-ajax-multiselect-loading"><i class="fas fa-spin fa-spinner"></i></div>
</div>';
		}
		return array('status' => 'OK', 'html' => $html, 'more' => $more);
	}

	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['ontraport_enable'] == 'on') {
			$data = array('objectID' => 0, 'email' => $_subscriber['{subscription-email}']);
			foreach ($popup_options['ontraport_fields'] as $key => $value) {
				if ($key != 'email' && !empty($value)) $data[$key] = strtr($value, $_subscriber);
			}
			$result = $this->connect($popup_options['ontraport_app_id'], $popup_options['ontraport_api_key'], 'object/getByEmail?objectID=0&email='.$_subscriber['{subscription-email}']);
			if (is_array($result) && array_key_exists('data', $result) && array_key_exists('id', $result['data'])) {
				$object_id = $result['data']['id'];
				$data['id'] = $object_id;
				$result = $this->connect($popup_options['ontraport_app_id'], $popup_options['ontraport_api_key'], 'objects', $data, 'PUT');
			} else {
				$result = $this->connect($popup_options['ontraport_app_id'], $popup_options['ontraport_api_key'], 'objects', $data);
				if (is_array($result) && array_key_exists('data', $result) && array_key_exists('id', $result['data'])) $object_id = $result['data']['id'];
				else return;
			}
			if (!empty($popup_options['ontraport_tags'])) {
				$data = array('objectID' => 0, 'add_list' => implode(',', $popup_options['ontraport_tags']), 'ids' => $object_id);
				$result = $this->connect($popup_options['ontraport_app_id'], $popup_options['ontraport_api_key'], 'objects/tag', $data, 'PUT');
			}
			if (!empty($popup_options['ontraport_sequences'])) {
				$data = array('objectID' => 0, 'add_list' => implode(',', $popup_options['ontraport_sequences']), 'ids' => $object_id);
				$result = $this->connect($popup_options['ontraport_app_id'], $popup_options['ontraport_api_key'], 'objects/subscribe', $data, 'PUT');
			}
		}
	}
	
	function connect($_app_id, $_api_key, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Api-Key: '.$_api_key,
			'Api-Appid: '.$_app_id
		);
		try {
			$url = 'https://api.ontraport.com/1/'.ltrim($_path, '/');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 120);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
			//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($curl);
			curl_close($curl);
			$result = json_decode($response, true);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
}
$ulp_ontraport = new ulp_ontraport_class();
?>