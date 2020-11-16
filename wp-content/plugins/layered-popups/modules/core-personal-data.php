<?php
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_personal_data_class {
	function __construct() {
		global $ulp;
		if (is_admin()) {
			add_filter('wp_privacy_personal_data_exporters', array(&$this, 'personal_data_exporters'), 2);
			add_filter('wp_privacy_personal_data_erasers', array(&$this, 'personal_data_erasers'), 2);
		}
	}

	function personal_data_exporters($_exporters) {
		$_exporters['ulp'] = array(
			'exporter_friendly_name' => __('Layered Popups', 'ulp'),
			'callback' => array(&$this, 'personal_data_exporter')
		);
		return $_exporters;
	}
	
	function personal_data_exporter($_email_address, $_page = 1) {
		global $wpdb, $ulp;
		if (empty($_email_address)) {
			return array(
				'data' => array(),
				'done' => true
			);
		}
		$data_to_export = array();
		$rows = $wpdb->get_results("SELECT t1.*, t2.title AS popup_title FROM ".$wpdb->prefix."ulp_subscribers t1 LEFT JOIN ".$wpdb->prefix."ulp_popups t2 ON t2.id = t1.popup_id WHERE t1.email = '".esc_sql($_email_address)."' ORDER BY t1.created DESC", ARRAY_A);
		foreach ($rows as $row) {
			$data = array(
				'group_id' => 'ulp-log-'.$row['popup_id'],
				'group_label' => __('Layered Popups', 'ulp').': '.$row['popup_title'],
				'item_id' => 'ulp-log-'.$row['id']
			);
			if (!empty($row['email'])) $data['data'][] = array('name' => __('Email', 'ulp'), 'value' => $row['email']);
			if (!empty($row['name'])) $data['data'][] = array('name' => __('Name', 'ulp'), 'value' => $row['name']);
			if (!empty($row['phone'])) $data['data'][] = array('name' => __('Phone #', 'ulp'), 'value' => $row['phone']);
			if (!empty($row['message'])) $data['data'][] = array('name' => __('Message', 'ulp'), 'value' => $row['message']);
			if (array_key_exists('custom_fields', $row) && !empty($row['custom_fields'])) {
				$custom_fields = unserialize($row['custom_fields']);
				if ($custom_fields && is_array($custom_fields)) {
					foreach ($custom_fields as $field) {
						if (!empty($field['value'])) $data['data'][] = array('name' => $field['name'], 'value' => $field['value']);
					}
				}
			}
			$data['data'][] = array('name' => __('Created', 'ulp'), 'value' => date("Y-m-d H:i", $row['created']));
			$status = '';
			if ($row['deleted'] != 0) $status = __('Deleted', 'ulp');
			else if (array_key_exists($row['status'], $ulp->user_statuses)) $status = $ulp->user_statuses[$row['status']]['label'];
			if (!empty($status)) $data['data'][] = array('name' => __('Status', 'ulp'), 'value' => $status);
			$data_to_export[] = $data;
		}
		return array(
			'data' => $data_to_export,
			'done' => true
		);
	}
	
	function personal_data_erasers($_erasers) {
		$_erasers['ulp'] = array(
			'eraser_friendly_name' => __('Layered Popups', 'ulp'),
			'callback' => array(&$this, 'personal_data_eraser')
		);
		return $_erasers;
	}

	function personal_data_eraser($_email_address, $_page = 1) {
		global $wpdb;
		if (empty($_email_address)) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}
		$tmp = $wpdb->get_row("SELECT COUNT(*) AS total FROM ".$wpdb->prefix."ulp_subscribers WHERE email = '".esc_sql($_email_address)."'", ARRAY_A);
		$total = $tmp["total"];
		$wpdb->query("DELETE FROM ".$wpdb->prefix."ulp_subscribers WHERE email = '".esc_sql($_email_address)."'");
		return array(
			'items_removed'  => $total,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
?>