<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'jwt_project' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          'nx:ev@e9Vo>oWijoRp7/~fHIT&(Q%AT?1RIn@%resBIak3E10Pnt{z9Iu)XqtAT6' );
define( 'SECURE_AUTH_KEY',   '|lq5W7[t!7 Ny8K5<q=-YLTxSy5uY#XlQ!-Mp%/T;)7|70t0pa~KtP6_4@nMtG%-' );
define( 'LOGGED_IN_KEY',     'QgwP@bqZ^UXM`+yR}P/q,Qr(lg2U(2dSa&kVU:SL)/6Z7# x::%PUxTP!=+.E(c.' );
define( 'NONCE_KEY',         'bTPOOqd:)v757s@2hR2x )B$v3 I1Dj1PqhNs$^&^4+IOYBu[[TgU?wK+8OYwi~S' );
define( 'AUTH_SALT',         'Mz_;GGly)FNeS+t<hC}U@1,7@tCy%pU>i}c9KXX<2XrIYq&GDY(PpFsf#_ePI|^!' );
define( 'SECURE_AUTH_SALT',  ',&*CHjp4$wn{GY`:EBj4%j@wKwFWbFD3~o_q<9w&$Tnc7?lc2A%c{[c1YZ/&=@e]' );
define( 'LOGGED_IN_SALT',    '.JTEYq6C-$Ry0%N)a__G21XD8T&@At4k%<2{BKBN47o7}G`qbPs,82lQE|?NTznF' );
define( 'NONCE_SALT',        'p6;s*#LEL=ExbEfYDi@s}yN^sJB&K7rHZ<>u-,Uc`Vf}?E2I*o^MV!1l~c?Q|)SZ' );
define( 'WP_CACHE_KEY_SALT', 'c*0n nfd#?Dd*1#% ]I=Vrfb0Qh7xYnK]G[qUQ#|$NyHW#-U!c5(Uua[]yF^IV]^' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
// if ( ! defined( 'WP_DEBUG' ) ) {
// 	define( 'WP_DEBUG', false );
// }
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false); // Hide errors from frontend
@ini_set('display_errors', 0);
define('JWT_AUTH_SECRET_KEY', 'kjsdhf9823r!@#jhsdf98723rjksdf2jhsdf');


/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
