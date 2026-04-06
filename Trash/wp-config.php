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
define( 'DB_NAME', 'invite' );

/** Database username */
define( 'DB_USER', 'invite_002' );

/** Database password */
define( 'DB_PASSWORD', 'AladarHenwen23!' );

/** Database hostname */
define( 'DB_HOST', 'marziaca.ipagemysql.com' );

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
define( 'AUTH_KEY',         '_ix[M~>?Ent:{I.a|W5hkE`O7JK`4wm(Td>.-b{TePVGA`%7asV(MI^Sa=q5O]d8' );
define( 'SECURE_AUTH_KEY',  'nFXRA|oe,bSNAfb[rh&Qw5hv4X&3I-JCVJAbm%{0!9.NcX>vNXP3Eao*vP*mqWik' );
define( 'LOGGED_IN_KEY',    ':u$U{<(Lt[`+5.;meqCIsBZ+QdOJOApd4j{#slhT_aN oa{6>cCB{UBS`WQ3wcEF' );
define( 'NONCE_KEY',        'Oh_s|UrC~k,+!072pp22anVG:ID&U;)Z57xO<)1Oaw|k2ZX|Tys.NZja<hxV(,hq' );
define( 'AUTH_SALT',        '}F._h;-RS2#Zy&g|a@9)<o/!=,p}Zf7BPh-EA!kOkeEYK|]?fX&8taKXDCK> GhA' );
define( 'SECURE_AUTH_SALT', 'vzqJ0zPT}N r*s/{}<q~sBkb,<{=3h/Ba oA-{}crnvzx30tJ%>3KE)9UeM`W*2;' );
define( 'LOGGED_IN_SALT',   'LUtu6L,FE?_H6#MCeJ>:n}(a|!lm_Qrp8+3S*9G~F-2JBy>Q;&$G?Jd1sS0CUMLX' );
define( 'NONCE_SALT',       '1&FA5k5![Qa*?*1|sgo#b2dWL1m`#-{)5~bT!WH:&^%cHaWJDj2TyLz4A!53;:Rn' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'rsvp_';

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
