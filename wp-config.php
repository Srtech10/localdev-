<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'localdev' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'HO;awIkM3o1/t^0/-)ay`#.IF,o(nk*c=#q%E/bNp[@wm/eogz=[t#.E~n+Pz#}9' );
define( 'SECURE_AUTH_KEY',  'tcb@aPo]+M#kYhf85ff_0AQI.g)fwPe#-^d#}CwO&{gPu{GyJ1);ap-h|er-kOwM' );
define( 'LOGGED_IN_KEY',    '?eydl::t(%,ON1;Q[w!b+RKpC{91ch0ecsm24sCQt;mYF`yUOs&ZvkSphC5UUY&F' );
define( 'NONCE_KEY',        '2:.G^XCmTEa*#uGGcT JIfZ6U3DK&NkN%d[=JY=i6mH8k}&Lpcz:?cMIe16x8{`]' );
define( 'AUTH_SALT',        'jYVmr`1g0+=`zB_Bt__;yFEx=%lM%bz>HU;!b+l4O~Us]kc8$;+6:4`!xIG!R/ol' );
define( 'SECURE_AUTH_SALT', 'pY4TnsD(;-Bs(]Br &Hj!5AXS+?ID7ROX2FB@95bO&=>-kz]Me*]Z{=8$=[j+cyO' );
define( 'LOGGED_IN_SALT',   'OJ`5#bL#SL%z~rV}5X)8@nH[KHC`lF[#!jfIVVj%[5JKV*+B?lsK|v&i*k1GyTcG' );
define( 'NONCE_SALT',       'omp8G>1Q2hHzJxyxvgzzRNya;FbPPmsz r3^B>m(+YYa_FgY:rf$1G+/,3$FR0gv' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
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
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
