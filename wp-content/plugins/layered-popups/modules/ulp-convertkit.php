<?php
/* ConvertKit integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_convertkit_class {
	var $default_popup_options = array(
		"convertkit_enable" => "off",
		"convertkit_api_key" => "",
		"convertkit_forms" => "",
		"convertkit_sequences" => "",
		"convertkit_tags" => ""
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-convertkit-forms', array(&$this, "show_forms"));
			add_action('wp_ajax_ulp-convertkit-sequences', array(&$this, "show_sequences"));
			add_action('wp_ajax_ulp-convertkit-tags', array(&$this, "show_tags"));
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
				<h3>'.__('ConvertKit Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable ConvertKit', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_convertkit_enable" name="ulp_convertkit_enable" '.($popup_options['convertkit_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to ConvertKit', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to ConvertKit.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_convertkit_api_key" name="ulp_convertkit_api_key" value="'.esc_html($popup_options['convertkit_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your ConvertKit API Key. You can get it <a href="https://app.convertkit.com/account/edit" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Forms', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-convertkit-forms-html">';
		if (!empty($popup_options['convertkit_api_key'])) {
			$forms = $this->get_forms_html($popup_options['convertkit_api_key'], $popup_options['convertkit_forms']);
			echo $forms;
		}
		echo '
							</div>
							<a id="ulp_convertkit_forms_button" class="ulp_button button-secondary" onclick="return ulp_convertkit_loadforms();">'.__('Load Forms', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-convertkit-forms-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('You can associate subscribers with certain ConvertKit form. Click the button to (re)load the list of forms.', 'ulp').'</em>
							<script>
								function ulp_convertkit_loadforms() {
									jQuery("#ulp-convertkit-forms-loading").fadeIn(350);
									jQuery(".ulp-convertkit-forms-html").slideUp(350);
									var data = {action: "ulp-convertkit-forms", ulp_key: jQuery("#ulp_convertkit_api_key").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-convertkit-forms-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-convertkit-forms-html").html(data.html);
												jQuery(".ulp-convertkit-forms-html").slideDown(350);
											} else {
												jQuery(".ulp-convertkit-forms-html").html("<div class=\'ulp-convertkit-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to ConvertKit server.', 'ulp').'</strong></div>");
												jQuery(".ulp-convertkit-forms-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-convertkit-forms-html").html("<div class=\'ulp-convertkit-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to ConvertKit server.', 'ulp').'</strong></div>");
											jQuery(".ulp-convertkit-forms-html").slideDown(350);
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
							<div class="ulp-convertkit-sequences-html">';
		if (!empty($popup_options['convertkit_api_key'])) {
			$sequences = $this->get_sequences_html($popup_options['convertkit_api_key'], $popup_options['convertkit_sequences']);
			echo $sequences;
		}
		echo '
							</div>
							<a id="ulp_convertkit_sequences_button" class="ulp_button button-secondary" onclick="return ulp_convertkit_loadsequences();">'.__('Load Sequences', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-convertkit-sequences-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('You can associate subscribers with certain ConvertKit sequence. Click the button to (re)load the list of sequences. Ignore it, if you do not need associate subscribers with certain ConvertKit sequence.', 'ulp').'</em>
							<script>
								function ulp_convertkit_loadsequences() {
									jQuery("#ulp-convertkit-sequences-loading").fadeIn(350);
									jQuery(".ulp-convertkit-sequences-html").slideUp(350);
									var data = {action: "ulp-convertkit-sequences", ulp_key: jQuery("#ulp_convertkit_api_key").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-convertkit-sequences-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-convertkit-sequences-html").html(data.html);
												jQuery(".ulp-convertkit-sequences-html").slideDown(350);
											} else {
												jQuery(".ulp-convertkit-sequences-html").html("<div class=\'ulp-convertkit-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to ConvertKit server.', 'ulp').'</strong></div>");
												jQuery(".ulp-convertkit-sequences-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-convertkit-sequences-html").html("<div class=\'ulp-convertkit-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to ConvertKit server.', 'ulp').'</strong></div>");
											jQuery(".ulp-convertkit-sequences-html").slideDown(350);
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Tags', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-convertkit-tags-html">';
		if (!empty($popup_options['convertkit_api_key'])) {
			$tags = $this->get_tags_html($popup_options['convertkit_api_key'], $popup_options['convertkit_tags']);
			echo $tags;
		}
		echo '
							</div>
							<a id="ulp_convertkit_tags_button" class="ulp_button button-secondary" onclick="return ulp_convertkit_loadtags();">'.__('Load Tags', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-convertkit-tags-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('You can associate subscribers with certain ConvertKit tag. Click the button to (re)load the list of tags. Ignore it, if you do not need associate subscribers with certain ConvertKit tag.', 'ulp').'</em>
							<script>
								function ulp_convertkit_loadtags() {
									jQuery("#ulp-convertkit-tags-loading").fadeIn(350);
									jQuery(".ulp-convertkit-tags-html").slideUp(350);
									var data = {action: "ulp-convertkit-tags", ulp_key: jQuery("#ulp_convertkit_api_key").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-convertkit-tags-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-convertkit-tags-html").html(data.html);
												jQuery(".ulp-convertkit-tags-html").slideDown(350);
											} else {
												jQuery(".ulp-convertkit-tags-html").html("<div class=\'ulp-convertkit-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to ConvertKit server.', 'ulp').'</strong></div>");
												jQuery(".ulp-convertkit-tags-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-convertkit-tags-html").html("<div class=\'ulp-convertkit-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to ConvertKit server.', 'ulp').'</strong></div>");
											jQuery(".ulp-convertkit-tags-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_convertkit_enable"])) $popup_options['convertkit_enable'] = "on";
		else $popup_options['convertkit_enable'] = "off";
		if ($popup_options['convertkit_enable'] == 'on') {
			if (empty($popup_options['convertkit_api_key'])) $errors[] = __('Invalid ConvertKit API Key.', 'ulp');
			$forms = array();
			foreach($ulp->postdata as $key => $value) {
				if (substr($key, 0, strlen('ulp_convertkit_form_')) == 'ulp_convertkit_form_') {
					$forms[] = substr($key, strlen('ulp_convertkit_form_'));
				}
			}
			if (empty($forms)) $errors[] = __('You need select at least one ConvertKit form.', 'ulp');
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
		if (isset($ulp->postdata["ulp_convertkit_enable"])) $popup_options['convertkit_enable'] = "on";
		else $popup_options['convertkit_enable'] = "off";
		
		$forms = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_convertkit_form_')) == 'ulp_convertkit_form_') {
				$forms[] = substr($key, strlen('ulp_convertkit_form_'));
			}
		}
		$popup_options['convertkit_forms'] = implode(',', $forms);

		$sequences = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_convertkit_sequence_')) == 'ulp_convertkit_sequence_') {
				$sequences[] = substr($key, strlen('ulp_convertkit_sequence_'));
			}
		}
		$popup_options['convertkit_sequences'] = implode(',', $sequences);

		$tags = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_convertkit_tag_')) == 'ulp_convertkit_tag_') {
				$tags[] = substr($key, strlen('ulp_convertkit_tag_'));
			}
		}
		$popup_options['convertkit_tags'] = implode(',', $tags);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['convertkit_enable'] == 'on') {
			$forms = explode(',', $popup_options['convertkit_forms']);
			$data = array(
				'email' => $_subscriber['{subscription-email}'],
				'name' => $_subscriber['{subscription-name}'],
				'forms' => $popup_options['convertkit_forms'],
				'courses' => $popup_options['convertkit_sequences'],
				'tags' => $popup_options['convertkit_tags']
			);
			$result = $this->connect($popup_options['convertkit_api_key'], '/forms/'.rawurlencode($forms[0]).'/subscribe', $data);
		}
	}
	function show_forms() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$return_object = array();
			$return_object['status'] = 'OK';
			if (!isset($_POST['ulp_key']) || empty($_POST['ulp_key'])) {
				$return_object['html'] = '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Key!', 'ulp').'</strong></div>';
			} else {
				$key = trim(stripslashes($_POST['ulp_key']));
				$return_object['html'] = $this->get_forms_html($key, '');
			}
		}
		echo json_encode($return_object);
		exit;
	}
	function get_forms_html($_key, $_forms) {
		$result = $this->connect($_key, '/forms');
		$forms = '';
		$forms_marked = explode(',', $_forms);
		if (!empty($result)) {
			if (array_key_exists('error', $result)) {
				$forms = '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;"><strong>'.rtrim($result['message'], '.').'.</strong></div>';
			} else {
				if (array_key_exists('forms', $result)) {
					if (empty($result['forms'])) {
						$forms .= '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;"><strong>'.__('Forms not found.', 'ulp').'</strong></div>';
					} else {
						$forms .= '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;">';
						foreach ($result['forms'] as $form) {
							$forms .= '<div class="ulp-convertkit-form" style="margin: 1px 0 1px 10px;"><input type="checkbox" name="ulp_convertkit_form_'.$form['id'].'"'.(in_array($form['id'], $forms_marked) ? ' checked="checked"' : '').' /> '.$form['name'].'</div>';
						}
						$forms .= '</div>';
					}
				} else {
					$forms = '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;"><strong>'.__('Forms not found.', 'ulp').'</strong></div>';
				}
			}
		} else {
			$forms = '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not connect ConvertKit API server.', 'ulp').'</strong></div>';
		}
		return $forms;
	}
	function show_sequences() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$return_object = array();
			$return_object['status'] = 'OK';
			if (!isset($_POST['ulp_key']) || empty($_POST['ulp_key'])) {
				$return_object['html'] = '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Key!', 'ulp').'</strong></div>';
			} else {
				$key = trim(stripslashes($_POST['ulp_key']));
				$return_object['html'] = $this->get_sequences_html($key, '');
			}
		}
		echo json_encode($return_object);
		exit;
	}
	function get_sequences_html($_key, $_sequences) {
		$result = $this->connect($_key, '/sequences');
		$sequences = '';
		$sequences_marked = explode(',', $_sequences);
		if (!empty($result)) {
			if (array_key_exists('error', $result)) {
				$sequences = '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;"><strong>'.rtrim($result['message'], '.').'.</strong></div>';
			} else {
				if (array_key_exists('courses', $result)) {
					if (empty($result['courses'])) {
						$sequences .= '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;"><strong>'.__('Sequences not found.', 'ulp').'</strong></div>';
					} else {
						$sequences .= '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;">';
						foreach ($result['courses'] as $sequence) {
							$sequences .= '<div class="ulp-convertkit-sequence" style="margin: 1px 0 1px 10px;"><input type="checkbox" name="ulp_convertkit_sequence_'.$sequence['id'].'"'.(in_array($sequence['id'], $sequences_marked) ? ' checked="checked"' : '').' /> '.$sequence['name'].'</div>';
						}
						$sequences .= '</div>';
					}
				} else {
					$sequences = '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;"><strong>'.__('Sequences not found.', 'ulp').'</strong></div>';
				}
			}
		} else {
			$sequences = '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not connect ConvertKit API server.', 'ulp').'</strong></div>';
		}
		return $sequences;
	}
	function show_tags() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$return_object = array();
			$return_object['status'] = 'OK';
			if (!isset($_POST['ulp_key']) || empty($_POST['ulp_key'])) {
				$return_object['html'] = '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Key!', 'ulp').'</strong></div>';
			} else {
				$key = trim(stripslashes($_POST['ulp_key']));
				$return_object['html'] = $this->get_tags_html($key, '');
			}
			echo json_encode($return_object);
		}
		exit;
	}
	function get_tags_html($_key, $_tags) {
		$result = $this->connect($_key, '/tags');
		$tags = '';
		$tags_marked = explode(',', $_tags);
		if (!empty($result)) {
			if (array_key_exists('error', $result)) {
				$tags = '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;"><strong>'.rtrim($result['message'], '.').'.</strong></div>';
			} else {
				if (array_key_exists('tags', $result)) {
					if (empty($result['tags'])) {
						$tags .= '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;"><strong>'.__('Tags not found.', 'ulp').'</strong></div>';
					} else {
						$tags .= '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;">';
						foreach ($result['tags'] as $tag) {
							$tags .= '<div class="ulp-convertkit-tag" style="margin: 1px 0 1px 10px;"><input type="checkbox" name="ulp_convertkit_tag_'.$tag['id'].'"'.(in_array($tag['id'], $tags_marked) ? ' checked="checked"' : '').' /> '.$tag['name'].'</div>';
						}
						$tags .= '</div>';
					}
				} else {
					$tags = '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;"><strong>'.__('Tags not found.', 'ulp').'</strong></div>';
				}
			}
		} else {
			$tags = '<div class="ulp-convertkit-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not connect ConvertKit API server.', 'ulp').'</strong></div>';
		}
		return $tags;
	}
	function connect($_api_key, $_path, $_data = array(), $_method = '') {
		$url = 'https://api.convertkit.com/v3/'.ltrim($_path, '/').'?api_key='.$_api_key;
		try {
			$curl = curl_init($url);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
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
$ulp_convertkit = new ulp_convertkit_class();
?>