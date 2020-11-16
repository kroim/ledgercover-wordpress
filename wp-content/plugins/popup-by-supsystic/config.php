<?php
    global $wpdb;
    if (!defined('WPLANG') || WPLANG == '') {
        define('PPS_WPLANG', 'en_GB');
    } else {
        define('PPS_WPLANG', WPLANG);
    }
    if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

    define('PPS_PLUG_NAME', basename(dirname(__FILE__)));
    define('PPS_DIR', WP_PLUGIN_DIR. DS. PPS_PLUG_NAME. DS);
    define('PPS_TPL_DIR', PPS_DIR. 'tpl'. DS);
    define('PPS_CLASSES_DIR', PPS_DIR. 'classes'. DS);
    define('PPS_TABLES_DIR', PPS_CLASSES_DIR. 'tables'. DS);
	define('PPS_HELPERS_DIR', PPS_CLASSES_DIR. 'helpers'. DS);
    define('PPS_LANG_DIR', PPS_DIR. 'languages'. DS);
    define('PPS_IMG_DIR', PPS_DIR. 'img'. DS);
    define('PPS_TEMPLATES_DIR', PPS_DIR. 'templates'. DS);
    define('PPS_MODULES_DIR', PPS_DIR. 'modules'. DS);
    define('PPS_FILES_DIR', PPS_DIR. 'files'. DS);
    define('PPS_ADMIN_DIR', ABSPATH. 'wp-admin'. DS);

	define('PPS_PLUGINS_URL', plugins_url());
    define('PPS_SITE_URL', get_bloginfo('wpurl'). '/');
    define('PPS_JS_PATH', PPS_PLUGINS_URL. '/'. PPS_PLUG_NAME. '/js/');
    define('PPS_CSS_PATH', PPS_PLUGINS_URL. '/'. PPS_PLUG_NAME. '/css/');
    define('PPS_IMG_PATH', PPS_PLUGINS_URL. '/'. PPS_PLUG_NAME. '/img/');
    define('PPS_MODULES_PATH', PPS_PLUGINS_URL. '/'. PPS_PLUG_NAME. '/modules/');
    define('PPS_TEMPLATES_PATH', PPS_PLUGINS_URL. '/'. PPS_PLUG_NAME. '/templates/');
    define('PPS_JS_DIR', PPS_DIR. 'js/');

    define('PPS_URL', PPS_SITE_URL);

    define('PPS_LOADER_IMG', PPS_IMG_PATH. 'loading.gif');
	define('PPS_TIME_FORMAT', 'H:i:s');
    define('PPS_DATE_DL', '/');
    define('PPS_DATE_FORMAT', 'm/d/Y');
    define('PPS_DATE_FORMAT_HIS', 'm/d/Y ('. PPS_TIME_FORMAT. ')');
    define('PPS_DATE_FORMAT_JS', 'mm/dd/yy');
    define('PPS_DATE_FORMAT_CONVERT', '%m/%d/%Y');
    define('PPS_WPDB_PREF', $wpdb->prefix);
    define('PPS_DB_PREF', 'pps_');
    define('PPS_MAIN_FILE', 'pps.php');

    define('PPS_DEFAULT', 'default');
    define('PPS_CURRENT', 'current');
	
	define('PPS_EOL', "\n");    
    
    define('PPS_PLUGIN_INSTALLED', true);
    define('PPS_VERSION', '1.10.1');
    define('PPS_USER', 'user');
    
    define('PPS_CLASS_PREFIX', 'ppsc');     
    define('PPS_FREE_VERSION', false);
	define('PPS_TEST_MODE', true);
    
    define('PPS_SUCCESS', 'Success');
    define('PPS_FAILED', 'Failed');
	define('PPS_ERRORS', 'ppsErrors');
	
	define('PPS_ADMIN',	'admin');
	define('PPS_LOGGED','logged');
	define('PPS_GUEST',	'guest');
	
	define('PPS_ALL',		'all');
	
	define('PPS_METHODS',		'methods');
	define('PPS_USERLEVELS',	'userlevels');
	/**
	 * Framework instance code
	 */
	define('PPS_CODE', 'pps');

	define('PPS_LANG_CODE', 'popup-by-supsystic');
	/**
	 * Plugin name
	 */
	define('PPS_WP_PLUGIN_NAME', 'PopUp by Supsystic');
	/**
	 * Allow minification
	 */
	define('PPS_MINIFY_ASSETS', true);
	/**
	 * Custom defined for plugin
	 */
	define('PPS_COMMON', 'common');
	define('PPS_FB_LIKE', 'fb_like');
	define('PPS_VIDEO', 'video');
	define('PPS_IFRAME', 'iframe');
	define('PPS_SIMPLE_HTML', 'simple_html');
	define('PPS_PDF', 'pdf');
	define('PPS_AGE_VERIFY', 'age_verify');
	define('PPS_FULL_SCREEN', 'full_screen');
	define('PPS_LOGIN_REGISTER', 'login_register');
	define('PPS_BAR', 'bar');
	define('PPS_SHORTCODE_CLICK', 'supsystic-show-popup');
	define('PPS_SHORTCODE', 'supsystic-popup');
	define('PPS_SHORTCODE_BUILD_IN', 'supsystic-popup-content');
	
	define('PPS_HOME_PAGE_ID', 0);
	
	define('PPS_VIDEO_YOUTUBE', 'youtube');
	define('PPS_VIDEO_VIMEO', 'vimeo');
	define('PPS_VIDEO_OTHER', 'other');
