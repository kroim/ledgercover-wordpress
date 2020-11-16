<?php
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_widget extends WP_Widget {
	function __construct() {
		parent::__construct(false, __('Layered Popups', 'ulp'));
	}

	function widget($args, $instance) {
		global $ulp, $wpdb;
		$content = '';
		$popup_id = $ulp->wpml_parse_popup_id($instance['popup']);
		include_once(dirname(__FILE__).'/modules/core-front.php');
		$html = ulp_front_class::shortcode_handler(array('id' => $popup_id));
		if (!empty($html)) {
			$content = $args['before_widget'].'<div style="clear:both; max-width:'.$instance['width_max'].'px; margin:'.$instance['margin_top'].'px '.$instance['margin_right'].'px '.$instance['margin_bottom'].'px '.$instance['margin_left'].'px;">'.$html.'</div>'.$args['after_widget'];
		}
		echo $content;
	}

	function update($new_instance, $old_instance) {
		global $ulp, $wpdb;
		$instance = $old_instance;
		$instance['popup'] = $ulp->wpml_compile_popup_id(strip_tags($new_instance['popup']), $instance['popup']);
		$instance['width_max'] = intval($new_instance['width_max']);
		$instance['margin_top'] = intval($new_instance['margin_top']);
		$instance['margin_bottom'] = intval($new_instance['margin_bottom']);
		$instance['margin_left'] = intval($new_instance['margin_left']);
		$instance['margin_right'] = intval($new_instance['margin_right']);
		return $instance;
	}

	function form($instance) {
		global $ulp, $wpdb;
		$instance = wp_parse_args((array)$instance, array('popup' => '', 'margin_top' => 0, 'margin_bottom' => 0, 'margin_left' => 0, 'margin_right' => 0, 'width_max' => 320));
		$popup_selected = $ulp->wpml_parse_popup_id(strip_tags($instance['popup']));
		$margin_top = intval($instance['margin_top']);
		$margin_bottom = intval($instance['margin_bottom']);
		$margin_right = intval($instance['margin_right']);
		$margin_left = intval($instance['margin_left']);
		$width_max = intval($instance['width_max']);
		
		$popups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ulp_popups WHERE deleted = '0' ORDER BY blocked, title ASC", ARRAY_A);
		echo '
		<p>
			<label for="'.$this->get_field_id("popup").'">'.__('Popup', 'ulp').':</label>';
		if (sizeof($popups) > 0) {
			$status = -1;
			echo '
			<select class="widefat" id="'.$this->get_field_id('popup').'" name="'.$this->get_field_name('popup').'">';
			foreach($popups as $popup) {
				if ($popup['blocked'] != $status) {
					if ($popup['blocked'] == 0) echo '<option disabled="disabled">--------- '.__('Active Popups', 'ulp').' ---------</option>';
					else echo '<option disabled="disabled">--------- '.__('Blocked Popups', 'ulp').' ---------</option>';
					$status = $popup['blocked'];
				}
				if ($popup_selected == $popup['str_id']) {
					echo '
				<option value="'.$popup['str_id'].'" selected="selected"'.($popup['blocked'] == 1 ? ' disabled="disabled"' : '').'>'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				} else {
					echo '
				<option value="'.$popup['str_id'].'"'.($popup['blocked'] == 1 ? ' disabled="disabled"' : '').'>'.esc_html($popup['title']).($popup['blocked'] == 1 ? ' '.__('[blocked]', 'ulp') : '').'</option>';
				}
			}
			echo '
			</select>';
		} else {
			echo __('Create at least one Layered Popup.', 'ulp');
		}
		echo '
		</p>
		<p>
			<label class="ulp-widget-label" for="'.$this->get_field_id("margin_top").'">'.__('Top margin', 'ulp').':</label>
			<input class="ulp-tiny-text" id="'.$this->get_field_id('margin_top').'" name="'.$this->get_field_name('margin_top').'" type="number" step="1" min="-20" value="'.$margin_top.'" size="3"> '.__('px', 'ulp').'
			<label class="ulp-widget-label" for="'.$this->get_field_id("margin_bottom").'">'.__('Bottom margin', 'ulp').':</label>
			<input class="ulp-tiny-text" id="'.$this->get_field_id('margin_bottom').'" name="'.$this->get_field_name('margin_bottom').'" type="number" step="1" min="-20" value="'.$margin_bottom.'" size="3"> '.__('px', 'ulp').'
			<label class="ulp-widget-label" for="'.$this->get_field_id("margin_left").'">'.__('Left margin', 'ulp').':</label>
			<input class="ulp-tiny-text" id="'.$this->get_field_id('margin_left').'" name="'.$this->get_field_name('margin_left').'" type="number" step="1" min="-20" value="'.$margin_left.'" size="3"> '.__('px', 'ulp').'
			<label class="ulp-widget-label" for="'.$this->get_field_id("margin_right").'">'.__('Right margin', 'ulp').':</label>
			<input class="ulp-tiny-text" id="'.$this->get_field_id('margin_right').'" name="'.$this->get_field_name('margin_right').'" type="number" step="1" min="-20" value="'.$margin_right.'" size="3"> '.__('px', 'ulp').'
		</p>
		<p>
			<label class="ulp-widget-label" for="'.$this->get_field_id("width_max").'">'.__('Max width', 'ulp').':</label>
			<input class="ulp-tiny-text" id="'.$this->get_field_id('width_max').'" name="'.$this->get_field_name('width_max').'" type="number" step="1" min="120" value="'.$width_max.'" size="3"> '.__('px', 'ulp').'
		</p>';
	}
}
?>