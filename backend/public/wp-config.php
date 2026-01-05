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
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

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
define( 'AUTH_KEY',          'wGicFnrQ5<w4|8vMqm1n)BVd+d>CejuI<-sVB$9fWdeWzA6HY>@ieKw3RlL~oi S' );
define( 'SECURE_AUTH_KEY',   '}>ro.w#f!>DW&xf[f?+`(vbS;(ej)D!wkq]_cOh9^shIA)xk3H/{q+&F8y}QyVFb' );
define( 'LOGGED_IN_KEY',     '=!C.L.@L-2P#>E+q]PL-q}rqc/8U5_?[?Q5BnsdAaM<`xqgCl6I1r` oAM`sKJM-' );
define( 'NONCE_KEY',         'H-| ==lMzt*G {so.!Kx#=Y,+^!5gz7d8G1Oo2DjCd6RA4`VeS|#,CW<1)=%5cDd' );
define( 'AUTH_SALT',         'TEUT+#W6r:x@i6`.}~UpDdgw$uC*UeC/kgRGvu@owpD06 ;fOhybYm67P@0ly?P1' );
define( 'SECURE_AUTH_SALT',  'n F, 6*[O Ph|3U ;tJnwo7F,g?ex!sm:{1vAt@H=gkKmXzHK:tvc_jrPXJc/S]4' );
define( 'LOGGED_IN_SALT',    'B;3t@4Hv~JnUAVswO>8im?rf*rMXVs,kF7LI_HgtXKRsis0[U1WD~<AA,L=.y~NX' );
define( 'NONCE_SALT',        'l^LMnDlo:|r=WOnhfo!+89`HX%0!$^X_/uy4C v(GtP 50ub/s/Pc+5qw(!LL:_H' );
define( 'WP_CACHE_KEY_SALT', 'DCD@C<D+?@8G0m9Te)(ui*S=(,N7IVX6jWT{^-E6BR&=~.),X]f(t)FW]6+;G;n7' );


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
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
