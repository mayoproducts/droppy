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
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'admin_wetrblog' );

/** Database username */
define( 'DB_USER', 'admin_wetrblog' );

/** Database password */
define( 'DB_PASSWORD', '!zh7Ar52' );

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
define( 'AUTH_KEY',         'K2f$3l[o7)adPf_[)VQr!RBh-_(zkI]Yh/lp@eq#P9,Z~:W@s~G<;b=a.c^|gPwG' );
define( 'SECURE_AUTH_KEY',  'f+[w.y::MW2TzTg.#rTJ3Xp7%i1Sczf~|Q]-VKPHN({-N0G4LIDS-r7L_]tmr;Kp' );
define( 'LOGGED_IN_KEY',    ']pWoyb#$&xpr8dCO7C}&M{-]1EYgk8gle5!r.>tN4;mykDe&/|i| &]4mcS3j^V%' );
define( 'NONCE_KEY',        'eq&^x!88HI?JdiV^)Yx fMKYBR !1t;n8*)bhX*U:FlzHr;~:?S.DIB|_CgC!Pg~' );
define( 'AUTH_SALT',        ':`;//<<RGYR].Rd=r3uwtx4!dt3 ~pN-EL:Jgtm#|)s?Xp-Z;3{k,7CD#qwZQ|o`' );
define( 'SECURE_AUTH_SALT', '-XDAM;DLm2H:#V@d@h2-GHljNarL[ou 3lF0}eYnG5i@`ohnqV^z/EQGO !{~-L@' );
define( 'LOGGED_IN_SALT',   'Hxv A}=m!cvY0}r+#`u:VQj2v(8l65j-M$;$bFw]SE<U]27g@sI?xe6tZ-2:X$6v' );
define( 'NONCE_SALT',       'G;ZR==g1@zH1;e#(:Ydv(,z`>{ETnF~,8@mIn47DG]#Mf@Vx#88:j}@Z_:5DS![.' );

/**#@-*/

/**
 * WordPress database table prefix.
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
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
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
