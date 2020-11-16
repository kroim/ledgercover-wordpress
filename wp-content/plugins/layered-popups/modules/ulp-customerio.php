<?php
/* Customer.io integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_customerio_class {
	var $default_popup_options = array(
		"customerio_enable" => "off",
		"customerio_site_id" => "",
		"customerio_api_key" => "",
		"customerio_attributes" => ""
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
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		$attributes = unserialize($popup_options['customerio_attributes']);
		echo '
				<h3>'.__('Customer.io Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Customer.io', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_customerio_enable" name="ulp_customerio_enable" '.($popup_options['customerio_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Customer.io', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Customer.io.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Site ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_customerio_site_id" name="ulp_customerio_site_id" value="'.esc_html($popup_options['customerio_site_id']).'" class="widefat">
							<br /><em>'.__('Enter your Customer.io Site ID. You can get it <a href="https://manage.customer.io/integration" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_customerio_api_key" name="ulp_customerio_api_key" value="'.esc_html($popup_options['customerio_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Customer.io API Key. You can get it <a href="https://manage.customer.io/integration" target="_blank">here</a>.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Attributes', 'ulp').':</th>
						<td>
							<table style="width: 100%;">
								<tr>
									<td style="width: 200px;"><strong>'.__('Name', 'ulp').'</strong></td>
									<td><strong>'.__('Value', 'ulp').'</strong></td>
								</tr>';
		$i = 0;
		if (is_array($attributes)) {
			foreach ($attributes as $key => $value) {
				echo '									
								<tr>
									<td>
										<input type="text" name="ulp_customerio_attributes_name[]" value="'.esc_html($key).'" class="widefat">
										<br /><em>'.($i > 0 ? '<a href="#" onclick="return ulp_customerio_remove_attribute(this);">'.__('Remove Attribute', 'ulp').'</a>' : '').'</em>
									</td>
									<td>
										<input type="text" name="ulp_customerio_attributes_value[]" value="'.esc_html($value).'" class="widefat">
									</td>
								</tr>';
				$i++;
			}
		}
		if ($i == 0) {
			echo '									
								<tr>
									<td>
										<input type="text" name="ulp_customerio_attributes_name[]" value="" class="widefat">
									</td>
									<td>
										<input type="text" name="ulp_customerio_attributes_value[]" value="" class="widefat">
									</td>
								</tr>';
		}
		echo '
								<tr style="display: none;" id="customerio-attribute-template">
									<td>
										<input type="text" name="ulp_customerio_attributes_name[]" value="" class="widefat">
										<br /><em><a href="#" onclick="return ulp_customerio_remove_attribute(this);">'.__('Remove Attribute', 'ulp').'</a></em>
									</td>
									<td>
										<input type="text" name="ulp_customerio_attributes_value[]" value="" class="widefat">
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<a href="#" class="button-secondary" onclick="return ulp_customerio_add_attribute(this);">'.__('Add Attribute', 'ulp').'</a>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
				<script>
					function ulp_customerio_add_attribute(object) {
						jQuery("#customerio-attribute-template").before("<tr>"+jQuery("#customerio-attribute-template").html()+"</tr>");
						return false;
					}
					function ulp_customerio_remove_attribute(object) {
						var row = jQuery(object).parentsUntil("tr").parent();
						jQuery(row).fadeOut(300, function() {
							jQuery(row).remove();
						});
						return false;
					}
				</script>';
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
		if (isset($ulp->postdata["ulp_customerio_enable"])) $popup_options['customerio_enable'] = "on";
		else $popup_options['customerio_enable'] = "off";
		if ($popup_options['customerio_enable'] == 'on') {
			if (empty($popup_options['customerio_api_key'])) $errors[] = __('Invalid Customer.io API Key.', 'ulp');
			if (empty($popup_options['customerio_site_id'])) $errors[] = __('Invalid Customer.io Site ID.', 'ulp');
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
		if (isset($ulp->postdata["ulp_customerio_enable"])) $popup_options['customerio_enable'] = "on";
		else $popup_options['customerio_enable'] = "off";
		if (is_array($ulp->postdata["ulp_customerio_attributes_name"]) && is_array($ulp->postdata["ulp_customerio_attributes_value"])) {
			$attributes = array();
			for($i=0; $i<sizeof($ulp->postdata["ulp_customerio_attributes_name"]); $i++) {
				$key = stripslashes(trim($ulp->postdata['ulp_customerio_attributes_name'][$i]));
				$value = stripslashes(trim($ulp->postdata['ulp_customerio_attributes_value'][$i]));
				if (!empty($key)) $attributes[$key] = $value;
			}
			if (!empty($attributes)) $popup_options['customerio_attributes'] = serialize($attributes);
			else $popup_options['customerio_attributes'] = '';
		} else $popup_options['customerio_attributes'] = '';
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['customerio_enable'] == 'on') {
			$data = array('email' => $_subscriber['{subscription-email}'], 'name' => $_subscriber['{subscription-name}']);
			$attributes = unserialize($popup_options['customerio_attributes']);
			if (is_array($attributes)) {
				foreach ($attributes as $key => $value) {
					$data[$key] = strtr($value, $_subscriber);
				}
			}
			try {
				$curl = curl_init();
				$customerio_url = 'https://track.customer.io/api/v1/customers/ulp-'.md5(strtolower($_subscriber['{subscription-email}']));

				curl_setopt($curl, CURLOPT_URL, $customerio_url);
				curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
				curl_setopt($curl, CURLOPT_HTTPGET, 1);
				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($curl, CURLOPT_VERBOSE, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS,http_build_query($data));
				curl_setopt($curl, CURLOPT_USERPWD, $popup_options['customerio_site_id'].':'.$popup_options['customerio_api_key']);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_exec($curl);
				curl_close($curl);
			} catch (Exception $e) {
			}
		}
	}
}
$ulp_customerio = new ulp_customerio_class();
?>