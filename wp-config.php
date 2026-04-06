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
define( 'DB_NAME', 'local' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', 'root' );
define( 'DB_HOST', 'localhost:/Users/mehrdade/Library/Application Support/Local/run/azaD9O4tt/mysql/mysqld.sock' );

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'iBtP^$m>tBbFD eN@S8l(&e450(zbt6@l=(+N1)KJKH5|_!tjW#^vI_=}A]Z_C-C');
define('SECURE_AUTH_KEY',  'et[];34m(LT[X=)>NLXs~y7 bBi?w?-Lar[W49?aOqT%4CYp12B)xc U1E<EDCJf');
define('LOGGED_IN_KEY',    'JHvl6|/%>#=!~&BjZCM62n(U|CXS?9,iG{_%-#/j@K9)Ao7=JX_m3=g2S/qaEnU0');
define('NONCE_KEY',        '[Y(}I&3[^E{xk?&l8)NC7V]hY6s{UEFYcdv}5R1MQ&_D|Fx>thvJ}9lJ50m~hmc7');
define('AUTH_SALT',        'V~(3>{@F!5>3H2|jMx};n(%Bn)F?>N`drv^K.QZ+a[z3#h+cc=v=E~cKL4 D0 ,s');
define('SECURE_AUTH_SALT', 'x=D1[6R)C7L~u[fUOd>T,Um^laVC;`vV?wHI*GD7aI4D.ve*aiV3^;iq3I@7c,ld');
define('LOGGED_IN_SALT',   '4u)_DpB-v`I6m;^u4Cs&`&*whV#ca*4>^XHurQt81cmZ+JQR}-VNnV0*JZhVf7:y');
define('NONCE_SALT',       'oQ |xA|@qPKO9mGT3ifCZ~U77zOzAnxS l-AthVN=P}c*uql@aW4W%7j<ym3)AFU');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_zopb_';

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
define('WP_DEBUG', false);

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy blogging. */
define( 'WP_MEMORY_LIMIT', '512M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );
/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
