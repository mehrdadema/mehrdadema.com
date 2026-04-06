<?php
/**
 * Script & Style Enqueuing
 *
 * @package G_Folio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================
   ADMIN SCRIPTS & STYLES
   ========================================================= */

add_action( 'admin_enqueue_scripts', 'gfolio_admin_enqueue' );

function gfolio_admin_enqueue( string $hook ): void {
	global $post_type, $current_screen;

	$is_gfolio_post     = in_array( $post_type, array( 'gfolio_item', 'gfolio_portfolio' ), true );
	$is_gfolio_settings = str_contains( $hook, 'gfolio-settings' );
	$is_gfolio_taxonomy = isset( $current_screen->taxonomy ) && 'gfolio_category' === $current_screen->taxonomy;

	if ( ! $is_gfolio_post && ! $is_gfolio_settings && ! $is_gfolio_taxonomy ) {
		return;
	}

	// WordPress built-in: media uploader
	wp_enqueue_media();

	// WordPress built-in: color picker
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );

	// G Folio admin stylesheet
	wp_enqueue_style(
		'gfolio-admin-style',
		GFOLIO_PLUGIN_URL . 'admin/css/admin-style.css',
		array( 'wp-color-picker' ),
		GFOLIO_VERSION
	);

	// G Folio admin script
	wp_enqueue_script(
		'gfolio-admin-script',
		GFOLIO_PLUGIN_URL . 'admin/js/admin-script.js',
		array( 'jquery', 'wp-color-picker', 'jquery-ui-slider' ),
		GFOLIO_VERSION,
		true
	);

	wp_localize_script(
		'gfolio-admin-script',
		'gfolioAdmin',
		array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'mediaTitle'  => __( 'Select Portfolio Image', 'g-folio' ),
			'mediaButton' => __( 'Use this image', 'g-folio' ),
			'nonce'       => wp_create_nonce( 'gfolio-admin-nonce' ),
		)
	);
}

/* =========================================================
   PUBLIC SCRIPTS & STYLES
   ========================================================= */

add_action( 'wp_enqueue_scripts', 'gfolio_public_enqueue' );

function gfolio_public_enqueue(): void {
	/*
	 * Always enqueue on the public frontend.
	 *
	 * A "has_shortcode()" gate was used previously, but it fails with page
	 * builders (Beaver Builder, Elementor, Divi, etc.) that store their module
	 * content in post-meta rather than in $post->post_content.  The result was
	 * that CSS and JS were never loaded when the shortcode lived inside a
	 * builder module, causing the grid to collapse and items to be cut off.
	 *
	 * The assets are small (~30 KB combined) so loading them site-wide is
	 * preferable to breaking compatibility with popular builders.  Use the
	 * filter below to opt out on specific pages if needed.
	 */
	if ( ! apply_filters( 'gfolio_enqueue_public_assets', true ) ) {
		return;
	}

	// Isotope.js (layout + filtering engine)
	wp_enqueue_script(
		'isotope',
		'https://cdnjs.cloudflare.com/ajax/libs/jquery.isotope/3.0.6/isotope.pkgd.min.js',
		array( 'jquery' ),
		'3.0.6',
		true
	);

	// Masonry.js (isotope uses it natively, but register separately for fallback)
	wp_enqueue_script(
		'masonry',
		'https://cdnjs.cloudflare.com/ajax/libs/masonry/4.2.2/masonry.pkgd.min.js',
		array( 'jquery' ),
		'4.2.2',
		true
	);

	// Animate.css (for filter animations)
	wp_enqueue_style(
		'animate-css',
		'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css',
		array(),
		'4.1.1'
	);

	// G Folio public stylesheet
	wp_enqueue_style(
		'gfolio-public-style',
		GFOLIO_PLUGIN_URL . 'public/css/gfolio-public.css',
		array( 'animate-css' ),
		GFOLIO_VERSION
	);

	// G Folio public script
	wp_enqueue_script(
		'gfolio-public-script',
		GFOLIO_PLUGIN_URL . 'public/js/gfolio-public.js',
		array( 'jquery', 'isotope', 'masonry' ),
		GFOLIO_VERSION,
		true
	);

	$s = gfolio_get_settings();

	wp_localize_script(
		'gfolio-public-script',
		'gfolioPublic',
		array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'gfolio-public-nonce' ),
			'closeLabel'    => __( 'Close', 'g-folio' ),
			'filterAnim'    => esc_js( $s['filter_animation'] ),
			'expandAnim'    => esc_js( $s['expand_animation'] ),
			'btnAlign'      => esc_js( $s['expand_btn_alignment'] ),
			'btnBgColor'    => esc_js( $s['expand_btn_bg_color'] ),
			'btnTextColor'  => esc_js( $s['expand_btn_text_color'] ),
			'expandBgColor' => esc_js( $s['expand_bg_color'] ),
			'expandFgColor' => esc_js( $s['expand_text_color'] ),
		)
	);
}

/* =========================================================
   ADMIN MENU ICON STYLE
   ========================================================= */

add_action( 'admin_head', 'gfolio_admin_menu_icon_style' );

function gfolio_admin_menu_icon_style(): void {
	?>
	<style>
		#adminmenu #toplevel_page_gfolio-portfolio .wp-menu-image::before {
			content: "\f16c"; /* dashicons-portfolio */
			font-family: dashicons;
		}
		#adminmenu #toplevel_page_gfolio-portfolio,
		#adminmenu #toplevel_page_gfolio-portfolio a {
			color: #e8e8e8;
		}
		#adminmenu #toplevel_page_gfolio-portfolio.current > a,
		#adminmenu #toplevel_page_gfolio-portfolio.wp-has-current-submenu > a,
		#adminmenu #toplevel_page_gfolio-portfolio:hover > a {
			background-color: #1e1e2e;
			color: #a78bfa;
		}
		#adminmenu #toplevel_page_gfolio-portfolio .wp-has-current-submenu .wp-menu-image::before,
		#adminmenu #toplevel_page_gfolio-portfolio:hover .wp-menu-image::before {
			color: #a78bfa;
		}
	</style>
	<?php
}
