<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'bitnami_wordpress' );

/** MySQL database username */
// define( 'DB_USER', 'bn_wordpress' );
define( 'DB_USER', 'root' );

/** MySQL database password */
// define( 'DB_PASSWORD', '575339d712' );
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost:3306' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', '5f1e6f676b27d405984b1e697ed92472b8d713feb40bab49724dbd177b6eeaa2');
define('SECURE_AUTH_KEY', 'ce5c0546483c7af380766306e0639eeaaa350399528cc198cce22f2e8798e2c6');
define('LOGGED_IN_KEY', '8f616556b005df3efad70a08f2a6801c5c24c29f45c6be49abc65a3d5ee1fd26');
define('NONCE_KEY', '255a376cfcab095ea4af7b22dbd320542ff4eea0739e905f4fa671ae51c2d028');
define('AUTH_SALT', '0f8e0a323454b2d49e2a9a8499f8ad5bdf71c352024d5380266ad3abce7ae1e5');
define('SECURE_AUTH_SALT', '18be3e875c3bd16be74637b4eb37ed0182b836af607be56aa93a1da97015b064');
define('LOGGED_IN_SALT', 'f86d00bd83ca89c45153f2e35b0d39f4697b9e19733b119d46d4db074a2eabd8');
define('NONCE_SALT', '4079eb22a3701261ab98235125c550b7350ba7f524e6d87c2b7697b7b3a824a5');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

define('FS_METHOD', 'direct');

/**
 * The WP_SITEURL and WP_HOME options are configured to access from any hostname or IP address.
 * If you want to access only from an specific domain, you can modify them. For example:
 *  define('WP_HOME','https://example.com');
 *  define('WP_SITEURL','https://example.com');
 *
*/

if ( defined( 'WP_CLI' ) ) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

// define('WP_SITEURL','https://' . $_SERVER['HTTP_HOST'] . '/');
// define('WP_HOME','https://' . $_SERVER['HTTP_HOST'] . '/');
define('WP_SITEURL','http://' . $_SERVER['HTTP_HOST'] . '/');
define('WP_HOME','http://' . $_SERVER['HTTP_HOST'] . '/');

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );

define('WP_TEMP_DIR', '/opt/bitnami/apps/wordpress/tmp');


//  Disable pingback.ping xmlrpc method to prevent Wordpress from participating in DDoS attacks
//  More info at: https://docs.bitnami.com/general/apps/wordpress/troubleshooting/xmlrpc-and-pingback/

if ( !defined( 'WP_CLI' ) ) {
    // remove x-pingback HTTP header
    add_filter('wp_headers', function($headers) {
        unset($headers['X-Pingback']);
        return $headers;
    });
    // disable pingbacks
    add_filter( 'xmlrpc_methods', function( $methods ) {
            unset( $methods['pingback.ping'] );
            return $methods;
    });
    add_filter( 'auto_update_translation', '__return_false' );
}
