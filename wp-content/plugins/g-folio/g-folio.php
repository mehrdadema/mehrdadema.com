<?php
/**
 * Plugin Name: G Folio
 * Plugin URI:  https://example.com/g-folio
 * Description: A professional visual portfolio showcase plugin with advanced grid layouts, filtering, lightbox, and expand panel features.
 * Version:     1.0.9
 * Author:      G Folio
 * Author URI:  https://example.com
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: g-folio
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'GFOLIO_VERSION',     '1.0.9' );
define( 'GFOLIO_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'GFOLIO_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'GFOLIO_PLUGIN_FILE', __FILE__ );

// Include core files
require_once GFOLIO_PLUGIN_DIR . 'includes/post-types.php';
require_once GFOLIO_PLUGIN_DIR . 'includes/meta-boxes.php';
require_once GFOLIO_PLUGIN_DIR . 'includes/settings.php';
require_once GFOLIO_PLUGIN_DIR . 'includes/shortcode.php';
require_once GFOLIO_PLUGIN_DIR . 'includes/enqueue.php';

/**
 * Plugin activation
 */
register_activation_hook( __FILE__, 'gfolio_activate' );
function gfolio_activate(): void {
	gfolio_register_post_types();
	flush_rewrite_rules();
}

/**
 * Plugin deactivation
 */
register_deactivation_hook( __FILE__, 'gfolio_deactivate' );
function gfolio_deactivate(): void {
	flush_rewrite_rules();
}

/**
 * Load text domain for translations
 */
add_action( 'plugins_loaded', 'gfolio_load_textdomain' );
function gfolio_load_textdomain(): void {
	load_plugin_textdomain(
		'g-folio',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}

/**
 * Get global default settings (used as fallback for all portfolios)
 */
function gfolio_get_settings(): array {
	$defaults = array(
		// Tab 1: Layout
		'grid_mode'         => 'grid',
		'columns'           => 3,
		'thumbnail_padding' => '0',
		'padding_size'      => 10,
		'border_radius'     => 8,
		'aspect_ratio'      => '1.7778',
		'full_width'        => '0',

		// Tab 2: Filtering
		'enable_filter'     => '1',
		'filter_position'   => 'top',
		'show_all_button'   => '1',
		'filter_animation'  => 'fade',

		// Tab 3: Thumbnail Overlay
		'show_title_overlay'      => '1',
		'show_subheading_overlay' => '1',
		'show_desc_overlay'       => '0',
		'overlay_style'           => 'hover',
		'overlay_bg_color'        => '#000000',
		'overlay_opacity'         => '0.7',

		// Tab 4: Item Click Behavior
		'default_click_behavior'  => 'popup',

		// Tab 5: Expand Panel
		'expand_bg_color'         => '#ffffff',
		'expand_text_color'       => '#333333',
		'expand_animation'        => 'slide',
		'expand_btn_bg_color'     => '#2c2c2c',
		'expand_btn_text_color'   => '#ffffff',
		'expand_btn_label'        => 'View Project',
		'expand_btn_style'        => 'filled',
		'expand_btn_alignment'    => 'left',
	);

	$saved = get_option( 'gfolio_settings', array() );
	return wp_parse_args( $saved, $defaults );
}

/**
 * Get per-portfolio settings, falling back to global defaults.
 * Settings are stored as post meta on gfolio_portfolio posts
 * using the prefix _gfoliop_.
 *
 * @param int $portfolio_id  Post ID of the gfolio_portfolio post.
 * @return array             Merged settings array.
 */
function gfolio_get_portfolio_settings( int $portfolio_id ): array {
	$defaults = gfolio_get_settings();

	if ( ! $portfolio_id ) {
		return $defaults;
	}

	$keys = array(
		'grid_mode', 'columns', 'thumbnail_padding', 'padding_size', 'border_radius',
		'aspect_ratio', 'full_width', 'enable_filter', 'show_all_button', 'filter_animation',
		'show_title_overlay', 'show_subheading_overlay', 'show_desc_overlay',
		'overlay_style', 'overlay_bg_color', 'overlay_opacity',
		'default_click_behavior', 'expand_bg_color', 'expand_text_color',
		'expand_animation', 'expand_btn_bg_color', 'expand_btn_text_color',
		'expand_btn_label', 'expand_btn_style', 'expand_btn_alignment',
	);

	foreach ( $keys as $key ) {
		$val = get_post_meta( $portfolio_id, '_gfoliop_' . $key, true );
		// Only override when meta actually has a value saved
		if ( $val !== '' && $val !== false ) {
			$defaults[ $key ] = $val;
		}
	}

	return $defaults;
}
