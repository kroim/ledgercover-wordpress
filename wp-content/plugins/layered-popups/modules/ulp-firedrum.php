<?php
/* FireDrum integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_firedrum_class {
	var $default_popup_options = array(
		"firedrum_enable" => "off",
		"firedrum_api_key" => "",
		"firedrum_categories" => "",
		"firedrum_static_fields" => "",
		"firedrum_fields" => ""
	);
	var $fields = array(
		'email' => '{subscription-email}',
		'firstName' => '{subscription-name}',
		'lastName' => '',
		'gender' => '',
		'company' => '',
		'address' => '',
		'address2' => '',
		'city' => '',
		'state' => '',
		'zip' => '',
		'phone' => '{subscription-phone}',
		'mobilePhone' => '',
		'fax' => ''
	);
	var $field_labels = array(
		'email' => array('title' => 'E-mail', 'description' => 'E-mail address of the memeber.'),
		'firstName' => array('title' => 'First name', 'description' => 'First name of the member.'),
		'lastName' => array('title' => 'Last name', 'description' => 'Last name of the member.'),
		'gender' => array('title' => 'Gender', 'description' => 'Gender of the member.'),
		'company' => array('title' => 'Company', 'description' => 'Organization name the member works for.'),
		'address' => array('title' => 'Address', 'description' => 'Address of the member.'),
		'address2' => array('title' => 'Address', 'description' => 'Address of the member.'),
		'city' => array('title' => 'City', 'description' => 'City of the member.'),
		'state' => array('title' => 'State', 'description' => 'State or province of the member.'),
		'zip' => array('title' => 'Postal code', 'description' => 'ZIP or postal code of the member.'),
		'phone' => array('title' => 'Phone #', 'description' => 'Phone number of the member.'),
		'mobilePhone' => array('title' => 'Mobile phone #', 'description' => 'Mobile phone number of the member.'),
		'fax' => array('title' => 'Fax', 'description' => 'Fax number of the member.')
	);
	
	function __construct() {
		$this->default_popup_options['firedrum_static_fields'] = serialize($this->fields);
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-firedrum-categories', array(&$this, "show_categories"));
			add_action('wp_ajax_ulp-firedrum-fields', array(&$this, "show_fields"));
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
				<h3>'.__('FireDrum Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable FireDrum', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_firedrum_enable" name="ulp_firedrum_enable" '.($popup_options['firedrum_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to FireDrum', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to FireDrum.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_firedrum_api_key" name="ulp_firedrum_api_key" value="'.esc_html($popup_options['firedrum_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your FireDrum API Key. You can find it <a href="https://www.firedrummarketing.com/client_admin_configure_account.jsp" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Categories', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-firedrum-categories-html">';
		if (!empty($popup_options['firedrum_api_key'])) {
			$categories = $this->get_categories_html($popup_options['firedrum_api_key'], $popup_options['firedrum_categories']);
			echo $categories;
		}
		echo '
							</div>
							<a id="ulp_firedrum_categories_button" class="ulp_button button-secondary" onclick="return ulp_firedrum_loadcategories();">'.__('Load Caregories', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-firedrum-categories-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('You can associate subscribers with certain category. Click the button to (re)load the list of categories. Ignore it, if you do not need associate subscribers with certain category.', 'ulp').'</em>
							<script>
								function ulp_firedrum_loadcategories() {
									jQuery("#ulp-firedrum-categories-loading").fadeIn(350);
									jQuery(".ulp-firedrum-categories-html").slideUp(350);
									var data = {action: "ulp-firedrum-categories", ulp_key: jQuery("#ulp_firedrum_api_key").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-firedrum-categories-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-firedrum-categories-html").html(data.html);
												jQuery(".ulp-firedrum-categories-html").slideDown(350);
											} else {
												jQuery(".ulp-firedrum-categories-html").html("<div class=\'ulp-firedrum-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to ConvertKit server.', 'ulp').'</strong></div>");
												jQuery(".ulp-firedrum-categories-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-firedrum-categories-html").html("<div class=\'ulp-firedrum-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to ConvertKit server.', 'ulp').'</strong></div>");
											jQuery(".ulp-firedrum-categories-html").slideDown(350);
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
							'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate FireDrum fields with the popup fields.', 'ulp').'
							<table style="min-width: 280px; width: 50%;">';
		$fields = unserialize($popup_options['firedrum_static_fields']);
		if (is_array($fields)) $fields = array_merge($this->fields, $fields);
		else $fields = $this->fields;
		foreach ($this->fields as $key => $value) {
			echo '
								<tr>
									<td style="width: 100px;"><strong>'.esc_html($this->field_labels[$key]['title']).':</strong></td>
									<td>
										<input type="text" id="ulp_firedrum_static_field_'.esc_html($key).'" name="ulp_firedrum_static_field_'.esc_html($key).'" value="'.esc_html($fields[$key]).'" class="widefat"'.($key == 'email' ? ' readonly="readonly"' : '').' />
										<br /><em>'.esc_html($this->field_labels[$key]['description']).'</em>
									</td>
								</tr>';
		}
		echo '
							</table>
							<div class="ulp-firedrum-fields-html">';
		if (!empty($popup_options['firedrum_api_key'])) {
			$fields = $this->get_fields_html($popup_options['firedrum_api_key'], $popup_options['firedrum_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_firedrum_fields_button" class="ulp_button button-secondary" onclick="return ulp_firedrum_loadfields();">'.__('Load Custom Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-firedrum-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load custom fields list. Ignore, if you do not need specify custom fields values.', 'ulp').'</em>
							<script>
								function ulp_firedrum_loadfields() {
									jQuery("#ulp-firedrum-fields-loading").fadeIn(350);
									jQuery(".ulp-firedrum-fields-html").slideUp(350);
									var data = {action: "ulp-firedrum-fields", ulp_key: jQuery("#ulp_firedrum_api_key").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-firedrum-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-firedrum-fields-html").html(data.html);
												jQuery(".ulp-firedrum-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-firedrum-fields-html").html("<div class=\'ulp-firedrum-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to FireDrum server.', 'ulp').'</strong></div>");
												jQuery(".ulp-firedrum-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-firedrum-fields-html").html("<div class=\'ulp-firedrum-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to FireDrum server.', 'ulp').'</strong></div>");
											jQuery(".ulp-firedrum-fields-html").slideDown(350);
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
		if (isset($ulp->postdata["ulp_firedrum_enable"])) $popup_options['firedrum_enable'] = "on";
		else $popup_options['firedrum_enable'] = "off";
		if ($popup_options['firedrum_enable'] == 'on') {
			if (empty($popup_options['firedrum_api_key'])) $errors[] = __('Invalid FireDrum API Token.', 'ulp');
			$found = false;
			foreach($ulp->postdata as $key => $value) {
				if (substr($key, 0, strlen('ulp_firedrum_category_')) == 'ulp_firedrum_category_') {
					$found = true;
					break;
				}
			}
			if (!$found) $errors[] = __('Select at least one FireDrum Category.', 'ulp');
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

		$categories = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_firedrum_category_')) == 'ulp_firedrum_category_') {
				$categories[] = substr($key, strlen('ulp_firedrum_category_'));
			}
		}
		$popup_options['firedrum_categories'] = implode(',', $categories);

		$fields = array();
		foreach($this->fields as $key => $value) {
			if (isset($ulp->postdata['ulp_firedrum_static_field_'.$key])) {
				$fields[$key] = stripslashes(trim($ulp->postdata['ulp_firedrum_static_field_'.$key]));
			}
		}
		$popup_options['firedrum_static_fields'] = serialize($fields);
		
		$fields = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_firedrum_field_')) == 'ulp_firedrum_field_') {
				$field = substr($key, strlen('ulp_firedrum_field_'));
				$fields[$field] = stripslashes(trim($value));
			}
		}
		$popup_options['firedrum_fields'] = serialize($fields);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['firedrum_enable'] == 'on') {
			$data = array(
				'updateIfExists' => true,
				'email' => $_subscriber['{subscription-email}']
			);
			$fields = array();
			if (!empty($popup_options['firedrum_static_fields'])) $fields = unserialize($popup_options['firedrum_static_fields']);
			if (!empty($fields) && is_array($fields)) {
				foreach ($fields as $key => $value) {
					if (!empty($value) && $key != 'email') {
						$data[$key] =  strtr($value, $_subscriber);
					}
				}
			}
			$serialized_data = http_build_query($data);
			$categories = explode(',', $popup_options['firedrum_categories']);
			foreach ($categories as $category) {
				$serialized_data .= '&categoryId[]='.$category;
			}
			$fields = array();
			if (!empty($popup_options['firedrum_fields'])) $fields = unserialize($popup_options['firedrum_fields']);
			if (!empty($fields) && is_array($fields)) {
				foreach ($fields as $key => $value) {
					if (!empty($value)) {
						$serialized_data .= '&customField[]='.json_encode(array('id' => $key, 'value' => strtr($value, $_subscriber)));
					}
				}
			}
			$result = $this->connect($popup_options['firedrum_api_key'], 'Member_Add', $serialized_data);
			if (is_array($result) && array_key_exists('responseCode', $result) && $result['responseCode']['id'] == 202) {
				$serialized_data .= '&replaceCategories=none&id='.$result['existingMember']['id'];
				$result = $this->connect($popup_options['firedrum_api_key'], 'Member_Update', $serialized_data);
			}
		}
	}
	function show_categories() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$return_object = array();
			$return_object['status'] = 'OK';
			if (!isset($_POST['ulp_key']) || empty($_POST['ulp_key'])) {
				$return_object['html'] = '<div class="ulp-firedrum-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Key!', 'ulp').'</strong></div>';
			} else {
				$key = trim(stripslashes($_POST['ulp_key']));
				$return_object['html'] = $this->get_categories_html($key, '');
			}
		}
		echo json_encode($return_object);
		exit;
	}
	function get_categories_html($_key, $_categories) {
		$result = $this->connect($_key, 'Category_List');
		$categories = '';
		$categories_marked = explode(',', $_categories);
		if (!empty($result) || !array_key_exists('responseCode', $result)) {
			if ($result['responseCode']['id'] !== 0) {
				$categories = '<div class="ulp-firedrum-grouping" style="margin-bottom: 10px;"><strong>'.__('The following error code returned', 'ulp').': '.$result['responseCode']['name'].'.</strong></div>';
			} else {
				if (array_key_exists('categories', $result)) {
					if (empty($result['categories'])) {
						$categories .= '<div class="ulp-firedrum-grouping" style="margin-bottom: 10px;"><strong>'.__('Categories not found.', 'ulp').'</strong></div>';
					} else {
						$categories .= '<div class="ulp-firedrum-grouping" style="margin-bottom: 10px;">';
						foreach ($result['categories'] as $category) {
							$categories .= '<div class="ulp-firedrum-category" style="margin: 1px 0 1px 10px;"><input type="checkbox" name="ulp_firedrum_category_'.$category['id'].'"'.(in_array($category['id'], $categories_marked) ? ' checked="checked"' : '').' /> '.$category['name'].'</div>';
						}
						$categories .= '</div>';
					}
				} else {
					$categories = '<div class="ulp-firedrum-grouping" style="margin-bottom: 10px;"><strong>'.__('Categories not found.', 'ulp').'</strong></div>';
				}
			}
		} else {
			$categories = '<div class="ulp-firedrum-grouping" style="margin-bottom: 10px;"><strong>'.__('Can not connect FireDrum API server.', 'ulp').'</strong></div>';
		}
		return $categories;
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_key']) || empty($_POST['ulp_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-firedrum-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Token.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_key']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($key, $this->default_popup_options['firedrum_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_key, $_fields) {
		$result = $this->connect($_key, 'CustomField_List');
		$fields = '';
		$values = unserialize($_fields);
		if (!is_array($values)) $values = array();
		if (!empty($result) || !array_key_exists('responseCode', $result)) {
			if ($result['responseCode']['id'] === 0) {
				if (array_key_exists('customFields', $result)) {
					$fields = '
			<table style="min-width: 280px; width: 50%;">';
					$found = false;
					foreach ($result['customFields'] as $field) {
						if (is_array($field)) {
							if (array_key_exists('id', $field) && array_key_exists('name', $field)) {
								$fields .= '
				<tr>
					<td style="width: 100px;"><strong>'.esc_html($field['name']).':</strong></td>
					<td>
						<input type="text" id="ulp_firedrum_field_'.esc_html($field['id']).'" name="ulp_firedrum_field_'.esc_html($field['id']).'" value="'.esc_html(array_key_exists($field['id'], $values) ? $values[$field['id']] : '').'" class="widefat" />
						<br /><em>'.esc_html($field['name']).'</em>
					</td>
				</tr>';
							}
						}
					}
					$fields .= '
			</table>';
				} else {
					$fields = '<div class="ulp-firedrum-grouping" style="margin-bottom: 10px;"><strong>'.__('No custom fields found.', 'ulp').'</strong></div>';
				}
			} else {
				$fields = '<div class="ulp-firedrum-grouping" style="margin-bottom: 10px;"><strong>'.__('The following error code returned', 'ulp').': '.$result['responseCode']['name'].'.</strong></div>';
			}
		} else {
			$fields = '<div class="ulp-firedrum-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_api_key, $_action, $_data = '', $_method = '') {
		try {
			$url = 'https://api.firedrummarketing.com/api/v2.jsp?action='.$_action.'&authAPIKey='.$_api_key;
			$curl = curl_init($url);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $_data);
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 30);
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
$ulp_firedrum = new ulp_firedrum_class();
?>