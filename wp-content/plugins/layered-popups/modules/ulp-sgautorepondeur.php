<?php
/* SG Autorepondeur integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_sgautorepondeur_class {
	var $default_popup_options = array(
		'sgautorepondeur_enable' => 'off',
		'sgautorepondeur_member_id' => '',
		'sgautorepondeur_code' => '',
		'sgautorepondeur_list' => '',
		'sgautorepondeur_list_id' => '',
		'sgautorepondeur_fields' => ''
	);
	var $fields = array(
		'email' => '{subscription-email}',
		'nom' => '{subscription-name}',
		'prenom' => '',
		'civilite' => '',
		'adresse' => '',
		'codepostal' => '',
		'ville' => '',
		'pays' => '',
		'telephone' => '{subscription-phone}',
		'mobile' => '',
		'champs_1' => '',
		'champs_2' => '',
		'champs_3' => '',
		'champs_4' => '',
		'champs_5' => '',
		'champs_6' => '',
		'champs_7' => '',
		'champs_8' => '',
		'champs_9' => '',
		'champs_10' => '',
		'champs_11' => '',
		'champs_12' => '',
		'champs_13' => '',
		'champs_14' => '',
		'champs_15' => '',
		'champs_16' => ''
	);
	var $field_labels = array(
		'email' => array('title' => 'E-mail', 'description' => 'Adresse email de l\'abonné.'),
		'nom' => array('title' => 'Nom', 'description' => 'Nom de l\'abonné.'),
		'prenom' => array('title' => 'Prenom', 'description' => 'Prénom de l\'abonné.'),
		'civilite' => array('title' => 'Civilité', 'description' => 'Civilité de l\'abonné (M, Mme, Mlle).'),
		'adresse' => array('title' => 'Adresse', 'description' => 'Adresse de l\'abonné.'),
		'codepostal' => array('title' => 'Code postal', 'description' => 'Code postal de l\'abonné.'),
		'ville' => array('title' => 'Ville', 'description' => 'Ville de l\'abonné.'),
		'pays' => array('title' => 'Pays', 'description' => 'Pays de l\'abonné.'),
		'telephone' => array('title' => 'Telephone #', 'description' => 'Numéro de téléphone de l\'abonné.'),
		'mobile' => array('title' => 'Mobile #', 'description' => 'Numéro de téléphone mobile de l\'abonné.')
	);
	function __construct() {
		$this->default_popup_options['sgautorepondeur_fields'] = serialize($this->fields);
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
			add_action('wp_ajax_ulp-sgautorepondeur-lists', array(&$this, "show_lists"));
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
				<h3>'.__('SG Autorepondeur Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable SG Autorepondeur', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_sgautorepondeur_enable" name="ulp_sgautorepondeur_enable" '.($popup_options['sgautorepondeur_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to SG Autorepondeur', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to SG Autorepondeur.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Member ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_sgautorepondeur_member_id" name="ulp_sgautorepondeur_member_id" value="'.esc_html($popup_options['sgautorepondeur_member_id']).'" class="widefat">
							<br /><em>'.__('Enter your SG Autorepondeur Member ID. You can find Member ID at the left side menu in your SG Autorepondeur account.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Activation Code', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_sgautorepondeur_code" name="ulp_sgautorepondeur_code" value="'.esc_html($popup_options['sgautorepondeur_code']).'" class="widefat">
							<br /><em>'.__('Enter your SG Autorepondeur Activation Code. You can find it <a href="http://sg-autorepondeur.com/membre_v2/compte-options.php" target="_blank">here</a> ("Informations administratives" section).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<input type="text" id="ulp-sgautorepondeur-list" name="ulp_sgautorepondeur_list" value="'.esc_html($popup_options['sgautorepondeur_list']).'" class="ulp-input-options ulp-input" readonly="readonly" onfocus="ulp_sgautorepondeur_lists_focus(this);" onblur="ulp_input_options_blur(this);" />
							<input type="hidden" id="ulp-sgautorepondeur-list-id" name="ulp_sgautorepondeur_list_id" value="'.esc_html($popup_options['sgautorepondeur_list_id']).'" />
							<div id="ulp-sgautorepondeur-list-items" class="ulp-options-list">
								<div class="ulp-options-list-data"></div>
								<div class="ulp-options-list-spinner"></div>
							</div>
							<br /><em>'.__('Enter your List ID.', 'ulp').'</em>
							<script>
								function ulp_sgautorepondeur_lists_focus(object) {
									ulp_input_options_focus(object, {"action": "ulp-sgautorepondeur-lists", "ulp_member_id": jQuery("#ulp_sgautorepondeur_member_id").val(), "ulp_code": jQuery("#ulp_sgautorepondeur_code").val()});
								}
							</script>
						</td>
					</tr>';
		if ($ulp->ext_options['enable_customfields'] == 'on') {
			$fields = unserialize($popup_options['sgautorepondeur_fields']);
			if (is_array($fields)) $fields = array_merge($this->fields, $fields);
			else $fields = $this->fields;
			echo '
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-sgautorepondeur-fields-html">
								'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate SG Autorepondeur fields with the popup fields.', 'ulp').'
								<table style="min-width: 280px; width: 50%;">';
			foreach ($this->fields as $key => $value) {
				if (strpos($key, 'champs_') !== false) {
					$number = substr($key, strlen('champs_'));
					$title = 'Champ '.$number;
					$description = 'Champ personnalisés '.$number.' de l\'abonné.';
				} else {
					$title = $this->field_labels[$key]['title'];
					$description = $this->field_labels[$key]['description'];
				}
				echo '
									<tr>
										<td style="width: 100px;"><strong>'.esc_html($title).':</strong></td>
										<td>
											<input type="text" id="ulp_sgautorepondeur_field_'.esc_html($key).'" name="ulp_sgautorepondeur_field_'.esc_html($key).'" value="'.esc_html($fields[$key]).'" class="widefat"'.($key == 'email' ? ' readonly="readonly"' : '').' />
											<br /><em>'.esc_html($description).'</em>
										</td>
									</tr>';
			}
			echo '
								</table>
							</div>
						</td>
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
		if (isset($ulp->postdata["ulp_sgautorepondeur_enable"])) $popup_options['sgautorepondeur_enable'] = "on";
		else $popup_options['sgautorepondeur_enable'] = "off";
		if ($popup_options['sgautorepondeur_enable'] == 'on') {
			if (empty($popup_options['sgautorepondeur_member_id'])) $errors[] = __('Invalid SG Autorepondeur Member ID', 'ulp');
			if (empty($popup_options['sgautorepondeur_code'])) $errors[] = __('Invalid SG Autorepondeur Activation Code', 'ulp');
			if (empty($popup_options['sgautorepondeur_list_id'])) $errors[] = __('Invalid SG Autorepondeur List ID', 'ulp');
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
		if (isset($ulp->postdata["ulp_sgautorepondeur_enable"])) $popup_options['sgautorepondeur_enable'] = "on";
		else $popup_options['sgautorepondeur_enable'] = "off";
		if ($ulp->ext_options['enable_customfields'] == 'on') {
			$fields = array();
			foreach($this->fields as $key => $value) {
				if (isset($ulp->postdata['ulp_sgautorepondeur_field_'.$key])) {
					$fields[$key] = stripslashes(trim($ulp->postdata['ulp_sgautorepondeur_field_'.$key]));
				}
			}
			$popup_options['sgautorepondeur_fields'] = serialize($fields);
		}
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		global $ulp;
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['sgautorepondeur_enable'] == 'on') {
			$data = array(
				'action' => 'set_subscriber',
				'email' => $_subscriber['{subscription-email}'],
				'membreid' => $popup_options['sgautorepondeur_member_id'],
				'codeactivation' => $popup_options['sgautorepondeur_code'],
				'listeid' => $popup_options['sgautorepondeur_list_id'],
				'ip' => $_SERVER["REMOTE_ADDR"]
			);
			if ($ulp->ext_options['enable_customfields'] == 'on') {
				$fields = unserialize($_popup_options['sgautorepondeur_fields']);
				if (is_array($fields)) $fields = array_merge($this->fields, $fields);
				else $fields = $this->fields;
				foreach ($fields as $key => $value) {
					if (!empty($value) && $key != 'email') {
						$data[$key] = strtr($value, $_subscriber);
					}
				}
			} else {
				$data['nom'] = $_subscriber['{subscription-name}'];
				if (!empty($_subscriber['{subscription-phone}'])) $data['telephone'] = $_subscriber['{subscription-phone}'];
			}
			$result = $this->connect($data);
		}
	}
	function show_lists() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			$lists = array();
			if (!isset($_POST['ulp_member_id']) || empty($_POST['ulp_member_id']) || !isset($_POST['ulp_code']) || empty($_POST['ulp_code'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid Member ID or Activation Code!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$member_id = trim(stripslashes($_POST['ulp_member_id']));
			$code = trim(stripslashes($_POST['ulp_code']));
			
			$result = $this->connect(array('membreid' => $member_id, 'codeactivation' => $code, 'action' => 'get_list'));
			if (is_array($result)) {
				if (array_key_exists('valid', $result) && $result['valid']) {
					foreach ($result['reponse'] as $list) {
						if (is_array($list)) {
							if (array_key_exists('listeid', $list) && array_key_exists('nom', $list)) {
								$lists[$list['listeid']] = $list['nom'];
							}
						}
					}
				} else {
					$return_object = array();
					$return_object['status'] = 'OK';
					$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('No Lists found!', 'ulp').'</div>';
					echo json_encode($return_object);
					exit;
				}
			} else {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div style="text-align: center; margin: 20px 0px;">'.__('Invalid server response!', 'ulp').'</div>';
				echo json_encode($return_object);
				exit;
			}
			$list_html = '';
			if (!empty($lists)) {
				foreach ($lists as $id => $name) {
					$list_html .= '<a href="#" data-id="'.esc_html($id).'" data-title="'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'" onclick="return ulp_input_options_selected(this);">'.esc_html($id).(!empty($name) ? ' | '.esc_html($name) : '').'</a>';
				}
			} else $list_html .= '<div style="text-align: center; margin: 20px 0px;">'.__('No data found!', 'ulp').'</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $list_html;
			$return_object['items'] = sizeof($lists);
			echo json_encode($return_object);
		}
		exit;
	}
	function connect($_data) {
		try {
			$url = 'https://sg-autorepondeur.com/API_V2/';
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
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
$ulp_sgautorepondeur = new ulp_sgautorepondeur_class();
?>