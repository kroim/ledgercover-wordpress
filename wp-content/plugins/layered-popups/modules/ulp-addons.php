<?php
/* Add-ons for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
define('ULP_ADDONS_URL', 'http://layeredpopups.com/addons/');
class ulp_addons_class {
	function __construct() {
		if (is_admin()) {
			add_action('ulp_admin_menu', array(&$this, 'admin_menu'));
		}
	}
	function admin_menu() {
		add_submenu_page(
			"ulp"
			, __('Add-Ons', 'ulp')
			, __('Add-Ons', 'ulp')
			, "add_users"
			, "ulp-addons"
			, array(&$this, 'admin_addons')
		);
	}
	function admin_addons() {
		global $wpdb, $ulp;
		$url = trailingslashit(ULP_ADDONS_URL).'get-items/';
		$items = array();
		$upload_dir = wp_upload_dir();
		$cache_file = $upload_dir["basedir"].'/'.ULP_UPLOADS_DIR.'/temp/cache-'.md5($url).'.txt';
		if (file_exists($cache_file)) {
			if (filemtime($cache_file)+3600*12 > time()) {
				$cached = file_get_contents($cache_file);
				$items_tmp = unserialize($cached);
				if ($items_tmp === false) unlink($cache_file);
				else $items = $items_tmp;
			}
		}
		if (empty($items)) {
			try {
				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2049.0 Safari/537.36');
				//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
				$response = curl_exec($curl);
				curl_close($curl);
													
				$result = json_decode($response, true);
				if($result && is_array($result) && !empty($result)) {
					$items = $result;
					file_put_contents($cache_file, serialize($items));
				}
			} catch (Exception $e) {
			}
		}
		if (!empty($ulp->error)) $message = "<div class='error'><p>".$ulp->error."</p></div>";
		else if (!empty($ulp->info)) $message = "<div class='updated'><p>".$ulp->info."</p></div>";
		else if (empty($items)) $message = '<div class="error"><p>'.__('Addons is currently <strong>not available</strong>. Please try again later.', 'ulp').'</p></div>';
		else $message = '';
		echo '
		<div class="wrap ulp">
			<h2>'.__('Layered Popups - Addons', 'ulp').' <a href="https://layeredpopups.com/documentation/" class="add-new-h2" target="_blank">Online Documentation</a></h2>
			'.$message.'
			<div class="ulp-options" style="margin-top: 20px;">';
		foreach($items as $item) {
			echo '
				<div class="ulp-addons-item-box">
					<img class="item-thumbnail" src="'.$item['image'].'" alt="'.esc_html($item['title']).'" />
					<div class="ulp-addons-item-box-hover">
						<div style="margin-top: 100px; text-align: center;">';
			if ($item['version'] > ULP_VERSION) {
				echo '
							<a href="http://codecanyon.net/item/layered-popups-for-wordpress/5978263?ref=halfdata" target="_blank" class="button-secondary ulp-button">'.__('Layered Popups upgrade required!', 'ulp').'</a>';
			} else if (empty($item['url'])) {
				echo '
							<a href="#" class="button-secondary ulp-button">'.__('Coming Soon!', 'ulp').'</a>';
			} else {
				echo '
							<a href="'.$item['url'].'" target="_blank" class="button-secondary ulp-button">'.__('Get Add-On', 'ulp').'</a>';
			}
			echo '
						</div>
					</div>
					<div class="ulp-addons-label">'.esc_html($item['title']).'</div>
				</div>';
		}
		echo '
			</div>
		</div>';
	}
}
$ulp_addons = new ulp_addons_class();
?>