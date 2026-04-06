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
define( 'DB_NAME', 'mehrdadema' );

/** MySQL database username */
define( 'DB_USER', 'mehrdadema' );

/** MySQL database password */
define( 'DB_PASSWORD', 'AladarHenwen25!' );

/** MySQL hostname */
define( 'DB_HOST', 'marziaca.ipagemysql.com' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'A&M+S~6&o@.AzyF2Ph],u{a0X%e}8]vD9+A9rp+|mC4h^8+V<~cO/*!F}BxByv$|' );
define( 'SECURE_AUTH_KEY',  '?2+owu(nk%x.|V~r4Vii}4L6uL1IhhK}TBh)//@08p0{P0Lw8S+jS{V ]U*(dRTa' );
define( 'LOGGED_IN_KEY',    '{AUoJ^T=r&)|$XH+0GVch#B`Q[zrV&Ldo<iQ$YK/,+dzNY`DpN5J`tQ_U7Xi~lX?' );
define( 'NONCE_KEY',        'Y2!S6J=f3gxHG/J>3d:o;pQ7GEzBl[$lL)Y`)^LZK6?GX#!hzBF_zOZh+>WqtNj.' );
define( 'AUTH_SALT',        'HZ|H&d(|#rx&$5bMI wOfiGIW=zs*I.x hJi67[zra;-)BA7yP_o^l9iT@Wo[0~B' );
define( 'SECURE_AUTH_SALT', 'gVzXH+f[?%YvB@z c:{+)MBi&#1xeNUGIU]L/-p?b(np/MZF}#{CLFxCVRWDrwG|' );
define( 'LOGGED_IN_SALT',   'z7<dfKG[>V5Uc,(f>kA6p|Ic_wX1T<z+D&%/dOEaq#gc;tV)SG0<$V645l_>[E[u' );
define( 'NONCE_SALT',       'PPgX=btsy,~N>@5jvTk)N^U{YE/@WkR8wG_:;Og<IT0QACHFA%87VMuVKq)/aIhH' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_zopb';

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

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
