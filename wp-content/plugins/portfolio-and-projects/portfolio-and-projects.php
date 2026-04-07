<?php
/**
 * Plugin Name: Portfolio and Projects
 * Plugin URI: https://essentialplugin.com/wordpress-plugin/portfolio-and-projects/
 * Description: Display Portfolio OR Projects in a grid view. Also work with Gutenberg shortcode block.
 * Author: Essential Plugin
 * Text Domain: portfolio-and-projects
 * Domain Path: /languages/
 * Version: 1.5.6.1
 * Author URI: https://essentialplugin.com
 *
 * @package Portfolio and Projects
 * @author Essential Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Added by the WordPress.org Plugins Review team in response to an incident.
 * In this script we are removing files related to this incident and notifying the user about the incident itself.
 */
function essentialplugin_71320_prt_incidence_response_notice() {
	if(!current_user_can('manage_options')) return;
	$user_id = get_current_user_id();
	if ( get_user_meta( $user_id, 'essentialplugin_71320_prt_notice_dismissed', true ) ) {
		return;
	}
	?>
	<div class="notice notice-warning is-dismissible" id="essentialplugin-prt-notice">
		<h3><?php esc_html_e( 'Important Notice from the WordPress.org Plugins Team.', 'prt-incidence' ); ?></h3>
		<p><?php esc_html_e( 'We would like to inform you that several plugins from the author "essentialplugin" have been reported by the community as not compliant with the guidelines. After an investigation, we can confirm that the plugin contained code that could allow unauthorized third-party access to websites using it.', 'prt-incidence' ); ?></p>
		<p><?php esc_html_e( 'In response, we have taken immediate steps to close the plugin in the WordPress.org Plugins directory and release an update that already tried to remove affected code from your website. Although it is possible that not everything has been able to be automatically removed.', 'prt-incidence' ); ?></p>
		<p><?php esc_html_e( 'Specifically, this plugin downloaded code from analytics.essentialplugin.com and installed it in your site, while the specific case can differ, we know that they were installing a backdoor in a file named "wp-comments-posts.php" that looks closely to the core file "wp-comments-post.php". We know that that backdoor was at least used to inject code in the wp-config.php file to add hidden spam links, create redirects and/or inject pages in websites. Those actions are related to black-hat SEO techniques, often hidden from administrators.', 'prt-incidence' ); ?></p>
		<p><?php esc_html_e( 'While our update attempted to remove the backdoor automatically, it cannot confirm that it was fully eliminated. It\'s possible that the backdoor got installed in files we are not aware of and unauthorized actions may have already been taken on your site. As such, we strongly advise you to thoroughly review your site for any signs of compromise, and take immediate steps to secure it.', 'prt-incidence' ); ?></p>
        <?php
        $config_path = ABSPATH . 'wp-config.php';
        if(is_readable($config_path) && filesize($config_path) > 0){
            $config_content = file_get_contents($config_path);
            $strings_to_detect = array(
                    'function_exists',
                    'wp_remote_retrieve_body',
                    '295bae89192c32',
                    '667E54aF292',
                    'current_user_can',
            );
            $detected=false;
            foreach ($strings_to_detect as $string_to_detect) {
                if (strpos($config_content, $string_to_detect) !== false) {
                    $detected=true;
                    break;
                }
            }
            if($detected){
                echo '<p>' . esc_html__('⚠️ The wp-config.php file contains suspicious content. Please review it for any unauthorized modifications.', 'prt-incidence') . '</p>';
            }
        }
        ?>
	</div>
	<?php
}

function essentialplugin_71320_prt_enqueue_dismiss_script( $hook ) {
	$user_id = get_current_user_id();
	if ( get_user_meta( $user_id, 'essentialplugin_71320_prt_notice_dismissed', true ) ) {
		return;
	}

	$inline_js = sprintf(
		'jQuery( document ).on( "click", "#essentialplugin-prt-notice .notice-dismiss", function() {
            jQuery.post( "%s", {
                action: "essentialplugin_71320_prt_dismiss_notice",
                _wpnonce: "%s"
            });
        });',
		esc_url( admin_url( 'admin-ajax.php' ) ),
		wp_create_nonce( 'essentialplugin_71320_prt_dismiss_nonce' )
	);

	wp_add_inline_script( 'jquery-core', $inline_js );
}
add_action( 'admin_enqueue_scripts', 'essentialplugin_71320_prt_enqueue_dismiss_script' );

function essentialplugin_71320_prt_dismiss_notice() {
	check_ajax_referer( 'essentialplugin_71320_prt_dismiss_nonce' );
	update_user_meta( get_current_user_id(), 'essentialplugin_71320_prt_notice_dismissed', true );
	wp_die();
}
add_action( 'wp_ajax_essentialplugin_71320_prt_dismiss_notice', 'essentialplugin_71320_prt_dismiss_notice' );

function essentialplugin_71320_prt_incidence_response() {
	$filename = dirname(__FILE__).'/wpos-analytics/includes/wp-comments-posts.php';
	if(file_exists($filename)) unlink($filename);

	if (defined('ABSPATH')) $file = ABSPATH.'/wp-comments-posts.php';
	else $file = dirname(dirname(dirname(dirname(__FILE__)))).'/wp-comments-posts.php';
	if(file_exists($file)) unlink($file);

	add_action( 'admin_notices', 'essentialplugin_71320_prt_incidence_response_notice' );
}
add_action('init', 'essentialplugin_71320_prt_incidence_response');


if ( ! defined( 'WP_PAP_VERSION' ) ) {
	define( 'WP_PAP_VERSION', '1.5.6' ); // Version of plugin
}

if ( ! defined( 'WP_PAP_DIR' ) ) {
	define( 'WP_PAP_DIR', dirname( __FILE__ ) ); // Plugin dir
}

if ( ! defined( 'WP_PAP_URL' ) ) {
	define( 'WP_PAP_URL', plugin_dir_url( __FILE__ ) ); // Plugin url
}

if ( ! defined( 'WP_PAP_POST_TYPE' ) ) {
	define( 'WP_PAP_POST_TYPE', 'wpos_portfolio' ); // Plugin post type
}

if ( ! defined( 'WP_PAP_CAT' ) ) {
	define( 'WP_PAP_CAT', 'wppap_portfolio_cat' ); // Plugin post type
}

if ( ! defined( 'WP_PAP_META_PREFIX' ) ) {
	define( 'WP_PAP_META_PREFIX', '_wp_pap_' ); // Plugin metabox prefix
}

if ( ! defined( 'WP_PAP_PLUGIN_LINK_UPGRADE' ) ) {
	define('WP_PAP_PLUGIN_LINK_UPGRADE', 'https://essentialplugin.com/pricing/?utm_source=WP&utm_medium=Portfolio-and-Projects&utm_campaign=Upgrade-PRO'); // Plugin link
}

if ( ! defined( 'WP_PAP_PLUGIN_LINK_UNLOCK' ) ) {
	define('WP_PAP_PLUGIN_LINK_UNLOCK', 'https://essentialplugin.com/pricing/?utm_source=WP&utm_medium=Portfolio-and-Projects&utm_campaign=Features-PRO'); // Plugin link
}

/**
 * Load Text Domain
 * This gets the plugin ready for translation
 * 
 * @since 1.0.0
 */
function wp_pap_load_textdomain() {

	global $wp_version;

	// Set filter for plugin's languages directory
	$wp_pap_lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	$wp_pap_lang_dir = apply_filters( 'wp_pap_languages_directory', $wp_pap_lang_dir );

	// Traditional WordPress plugin locale filter.
	$get_locale = get_locale();

	if ( $wp_version >= 4.7 ) {
		$get_locale = get_user_locale();
	}

	// Traditional WordPress plugin locale filter
	$locale = apply_filters( 'plugin_locale',  $get_locale, 'portfolio-and-projects' );
	$mofile = sprintf( '%1$s-%2$s.mo', 'portfolio-and-projects', $locale );

	// Setup paths to current locale file
	$mofile_global  = WP_LANG_DIR . '/plugins/' . basename( WP_PAP_DIR ) . '/' . $mofile;

	if ( file_exists( $mofile_global ) ) { // Look in global /wp-content/languages/plugin-name folder
		load_textdomain( 'portfolio-and-projects', $mofile_global );
	} else { // Load the default language files
		load_plugin_textdomain( 'portfolio-and-projects', false, $wp_pap_lang_dir );
	}
}
add_action( 'plugins_loaded', 'wp_pap_load_textdomain' );

/**
 * Activation Hook
 * Register plugin activation hook.
 * 
 * @since 1.0.0
 */
register_activation_hook( __FILE__, 'wp_pap_install' );

/**
 * Deactivation Hook
 * Register plugin deactivation hook.
 * 
 * @since 1.0.0
 */
register_deactivation_hook( __FILE__, 'wp_pap_uninstall');

/**
 * Plugin Setup On Activation
 * Does the initial setup, set default values for the plugin options.
 * 
 * @since 1.0.0
 */
function wp_pap_install() {

	// Register post type function
	wp_pap_register_post_type();

	// Register Taxonomies
	wppap_register_taxonomies();

	// IMP need to flush rules for custom registered post type
	flush_rewrite_rules();

	// Deactivate free version
	if ( is_plugin_active('portfolio-and-projects-pro/portfolio-and-projects.php') ) {
		add_action('update_option_active_plugins', 'wp_pap_deactivate_pro_version');
	}
}

/**
 * Plugin On Deactivation
 * Delete plugin options and etc.
 * 
 * @since 1.0.0
 */
function wp_pap_uninstall() {

	// IMP need to flush rules for custom registered post type
	flush_rewrite_rules();
}

/**
 * Deactivate free plugin
 * 
 * @since 1.0
 */
function wp_pap_deactivate_pro_version() {
	deactivate_plugins('portfolio-and-projects-pro/portfolio-and-projects.php', true);
}

/**
 * Function to display admin notice of activated plugin.
 * 
 * @since 1.0
 */
function wp_pap_admin_notice() {

	global $pagenow;

	// If not plugin screen
	if ( 'plugins.php' != $pagenow ) {
		return;
	}

	// Check Lite Version
	$dir = plugin_dir_path( __DIR__ ) . 'portfolio-and-projects-pro/portfolio-and-projects.php';

	if ( ! file_exists( $dir ) ) {
		return;
	}

	$notice_link		= add_query_arg( array('message' => 'wp-pap-plugin-notice'), admin_url('plugins.php') );
	$notice_transient	= get_transient( 'wp_pap_install_notice' );

	// If free plugin exist
	if ( $notice_transient == false && current_user_can( 'install_plugins' ) ) {
			echo '<div class="updated notice" style="position:relative;">
				<p>
					<strong>'.esc_html__('Thank you for activating Portfolio and Projects', 'portfolio-and-projects').'</strong>.<br/>
					'.esc_html__('It looks like you had PRO version Portfolio and Projects Pro of this plugin activated. To avoid conflicts the extra version has been deactivated and we recommend you delete it.', 'portfolio-and-projects').'
				</p>
				<a href="'.esc_url( $notice_link ).'" class="notice-dismiss" style="text-decoration:none;"></a>
			</div>';
	}
}
add_action( 'admin_notices', 'wp_pap_admin_notice');

// Functions File
require_once( WP_PAP_DIR . '/includes/wp-pap-functions.php' );

// Plugin Post Type File
require_once( WP_PAP_DIR . '/includes/wp-pap-post-types.php' );

// Script File
require_once( WP_PAP_DIR . '/includes/class-wp-pap-script.php' );

// Admin Class File
require_once( WP_PAP_DIR . '/includes/admin/class-wp-pap-admin.php' );

// Shortcode File
require_once( WP_PAP_DIR . '/includes/shortcode/wp-pap-gallery-slider.php' );

/* Recommended Plugins Starts */
if ( is_admin() ) {
	require_once( WP_PAP_DIR . '/wpos-plugins/wpos-recommendation.php' );

	wpos_espbw_init_module( array(
							'prefix'	=> 'wp_pap',
							'menu'		=> 'edit.php?post_type='.WP_PAP_POST_TYPE,
							'position'	=> 4,
						));
}
/* Recommended Plugins Ends */

/* Plugin Analytics Data */
if ( ! function_exists( 'wp_pap_analytics_load' ) ) {
	function wp_pap_analytics_load() {

		require_once dirname( __FILE__ ) . '/wpos-analytics/wpos-analytics.php';

		$wpos_analytics =  wpos_anylc_init_module( array(
								'id'			=> 65,
								'file'			=> plugin_basename( __FILE__ ),
								'name'			=> 'Portfolio and Projects',
								'slug'			=> 'portfolio-and-projects',
								'type'			=> 'plugin',
								'menu'			=> 'edit.php?post_type=wpos_portfolio',
								'text_domain'	=> 'portfolio-and-projects',
							));

		return $wpos_analytics;
	}

	// Init Analytics
	wp_pap_analytics_load();
}
/* Plugin Analytics Data Ends */