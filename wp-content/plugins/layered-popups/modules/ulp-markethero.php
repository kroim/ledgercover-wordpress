<?php
/* Market Hero integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_markethero_class {
	var $default_popup_options = array(
		"markethero_enable" => "off",
		"markethero_api_key" => "",
		"markethero_firstname" => "{subscription-name}",
		"markethero_lastname" => "",
		"markethero_tags" => array()
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-markethero-tags', array(&$this, "show_tags"));
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
				<h3>'.__('Market Hero Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Market Hero', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_markethero_enable" name="ulp_markethero_enable" '.($popup_options['markethero_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Market Hero', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Market Hero.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_markethero_api_key" name="ulp_markethero_api_key" value="'.esc_html($popup_options['markethero_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Market Hero API Key. You can get it <a href="https://app.markethero.io/#/mh/settings" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-markethero-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Market Hero fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">
									<tr>
										<td style="width: 100px;"><strong>'.__('Email', 'ulp').':</strong></td>
										<td>
											<input type="text" id="ulp_markethero_email" name="ulp_markethero_email" value="{subscription-email}" class="widefat" readonly="readonly" />
											<br /><em>'.__('Lead email address.', 'ulp').'</em>
										</td>
									</tr>
									<tr>
										<td style="width: 100px;"><strong>'.__('First Name', 'ulp').':</strong></td>
										<td>
											<input type="text" id="ulp_markethero_firstname" name="ulp_markethero_firstname" value="'.esc_html($popup_options['markethero_firstname']).'" class="widefat" />
											<br /><em>'.__('Lead first name.', 'ulp').'</em>
										</td>
									</tr>
									<tr>
										<td style="width: 100px;"><strong>'.__('Last Name', 'ulp').':</strong></td>
										<td>
											<input type="text" id="ulp_markethero_lastname" name="ulp_markethero_lastname" value="'.esc_html($popup_options['markethero_lastname']).'" class="widefat" />
											<br /><em>'.__('Lead last name.', 'ulp').'</em>
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
					<tr>
						<th>'.__('Tags', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-markethero-tags-html">';
		if (!empty($popup_options['markethero_api_key'])) {
			$tags = $this->get_tags_html($popup_options['markethero_api_key'], $popup_options['markethero_tags']);
			echo $tags;
		}
		echo '
							</div>
							<a id="ulp_markethero_tags_button" class="ulp_button button-secondary" onclick="return ulp_markethero_loadtags();">'.__('Load Tags', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-markethero-tags-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load list of tags. Ignore if you do not use tags.', 'ulp').'</em>
							<script>
								function ulp_markethero_loadtags() {
									jQuery("#ulp-markethero-tags-loading").fadeIn(350);
									jQuery(".ulp-markethero-tags-html").slideUp(350);
									var data = {action: "ulp-markethero-tags", ulp_key: jQuery("#ulp_markethero_api_key").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-markethero-tags-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-markethero-tags-html").html(data.html);
												jQuery(".ulp-markethero-tags-html").slideDown(350);
											} else {
												jQuery(".ulp-markethero-tags-html").html("<div class=\'ulp-markethero-taging\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Market Hero server.', 'ulp').'</strong></div>");
												jQuery(".ulp-markethero-tags-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-markethero-tags-html").html("<div class=\'ulp-markethero-taging\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Market Hero server.', 'ulp').'</strong></div>");
											jQuery(".ulp-markethero-tags-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_markethero_enable"])) $popup_options['markethero_enable'] = "on";
		else $popup_options['markethero_enable'] = "off";
		if ($popup_options['markethero_enable'] == 'on') {
			if (empty($popup_options['markethero_api_key'])) $errors[] = __('Invalid Market Hero API Key.', 'ulp');
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
		if (isset($ulp->postdata["ulp_markethero_enable"])) $popup_options['markethero_enable'] = "on";
		else $popup_options['markethero_enable'] = "off";
		
		$tags = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_markethero_tag_')) == 'ulp_markethero_tag_') {
				$tags[] = substr($key, strlen('ulp_markethero_tag_'));
			}
		}
		$popup_options['markethero_tags'] = $tags;
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['markethero_enable'] == 'on') {
			$data = array(
				'email' => $_subscriber['{subscription-email}'],
				'firstName' => strtr($popup_options['markethero_firstname'], $_subscriber),
				'lastName' => strtr($popup_options['markethero_lastname'], $_subscriber)
			);
			foreach ($popup_options['markethero_tags'] as $tag) {
				$data['tags'][] = $tag;
			}
			$result = $this->connect($popup_options['markethero_api_key'], 'tag-lead', $data);
		}
	}
	function show_tags() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_key']) || empty($_POST['ulp_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-markethero-taging" style="margin-bottom: 10px;"><strong>'.__('Invalid API Key.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_key']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_tags_html($key, array());
			echo json_encode($return_object);
		}
		exit;
	}
	function get_tags_html($_key, $_tags = array()) {
		$result = $this->connect($_key, 'tags');
		$tags = '';
		if (!empty($result) && is_array($result)) {
			if (array_key_exists('error', $result)) {
				$tags = '<div class="ulp-markethero-taging" style="margin-bottom: 10px;"><strong>'.$result['error'].'</strong></div>';
			} else {
				if (array_key_exists('tags', $result) && sizeof($result['tags']) > 0) {
					$tags = '<div class="ulp-markethero-taging" style="margin-bottom: 10px;">';
					foreach ($result['tags'] as $tag) {
						$tags .= '<div class="ulp-markethero-tag" style="margin: 1px 0 1px 10px;"><input type="checkbox" name="ulp_markethero_tag_'.$tag.'"'.(in_array($tag, $_tags) ? ' checked="checked"' : '').' /> '.esc_html($tag).'</div>';
					}
					$tags .= '</div>';
				} else {
					$tags = '<div class="ulp-markethero-taging" style="margin-bottom: 10px;"><strong>'.__('No tags found.', 'ulp').'</strong></div>';
				}
			}
		} else {
			$tags = '<div class="ulp-markethero-taging" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $tags;
	}
	function connect($_api_key, $_path, $_data = array(), $_method = '') {
		$headers = array(
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		$url = 'https://api.markethero.io/v1/api/'.ltrim($_path, '/');
		if (empty($_data)) {
			$url .= (strpos($_path, '?') === false ? '?' : '&').'apiKey='.urlencode($_api_key);
		} else {
			$_data['apiKey'] = $_api_key;
		}
		try {
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 20);
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
$ulp_markethero = new ulp_markethero_class();
?>