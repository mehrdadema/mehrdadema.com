<?php
/**
 * Global Default Settings Page — 5 Tabbed Sections
 * These values serve as fallbacks for all portfolios.
 * Each portfolio can override them individually via its own settings box.
 *
 * @package G_Folio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================
   REGISTER SETTINGS
   ========================================================= */

add_action( 'admin_init', 'gfolio_register_settings' );

function gfolio_register_settings(): void {
	register_setting(
		'gfolio_settings_group',
		'gfolio_settings',
		array(
			'sanitize_callback' => 'gfolio_sanitize_settings',
			'default'           => array(),
		)
	);
}

function gfolio_sanitize_settings( array $input ): array {
	$clean = array();

	// Tab 1: Layout
	$clean['grid_mode']         = in_array( $input['grid_mode'] ?? 'grid', array( 'grid', 'masonry' ), true ) ? $input['grid_mode'] : 'grid';
	$clean['columns']           = max( 1, min( 6, absint( $input['columns'] ?? 3 ) ) );
	$clean['thumbnail_padding'] = isset( $input['thumbnail_padding'] ) && '1' === $input['thumbnail_padding'] ? '1' : '0';
	$clean['padding_size']      = max( 0, min( 100, absint( $input['padding_size'] ?? 10 ) ) );
	$clean['outer_gap']         = isset( $input['outer_gap'] ) && '1' === $input['outer_gap'] ? '1' : '0';
	$clean['border_radius']     = max( 0, min( 200, absint( $input['border_radius'] ?? 8 ) ) );
	$aspect_raw                 = (float) ( $input['aspect_ratio'] ?? 1.7778 );
	$clean['aspect_ratio']      = (string) round( max( 0.25, min( 4.0, $aspect_raw ) ), 4 );
	$clean['full_width']        = isset( $input['full_width'] ) && '1' === $input['full_width'] ? '1' : '0';

	// Tab 2: Filtering
	$clean['enable_filter']    = isset( $input['enable_filter'] ) && '1' === $input['enable_filter'] ? '1' : '0';
	$clean['filter_position']  = 'top'; // Only top is supported
	$clean['show_all_button']  = isset( $input['show_all_button'] ) && '1' === $input['show_all_button'] ? '1' : '0';
	$allowed_anim              = array( 'fade', 'scale', 'slide-up', 'flip' );
	$clean['filter_animation'] = in_array( $input['filter_animation'] ?? 'fade', $allowed_anim, true ) ? $input['filter_animation'] : 'fade';

	// Tab 3: Thumbnail Overlay
	$clean['show_title_overlay']      = isset( $input['show_title_overlay'] ) && '1' === $input['show_title_overlay'] ? '1' : '0';
	$clean['show_subheading_overlay'] = isset( $input['show_subheading_overlay'] ) && '1' === $input['show_subheading_overlay'] ? '1' : '0';
	$clean['show_desc_overlay']       = isset( $input['show_desc_overlay'] ) && '1' === $input['show_desc_overlay'] ? '1' : '0';
	$allowed_overlay                  = array( 'always', 'hover', 'fade' );
	$clean['overlay_style']           = in_array( $input['overlay_style'] ?? 'hover', $allowed_overlay, true ) ? $input['overlay_style'] : 'hover';
	$clean['overlay_bg_color']        = sanitize_hex_color( $input['overlay_bg_color'] ?? '#000000' ) ?: '#000000';
	$opacity                          = floatval( $input['overlay_opacity'] ?? 0.7 );
	$clean['overlay_opacity']         = (string) max( 0, min( 1, $opacity ) );

	// Tab 4: Click Behavior
	$allowed_click                  = array( 'popup', 'page', 'custom_url' );
	$clean['default_click_behavior'] = in_array( $input['default_click_behavior'] ?? 'popup', $allowed_click, true ) ? $input['default_click_behavior'] : 'popup';

	// Tab 5: Expand Panel
	$clean['expand_bg_color']       = sanitize_hex_color( $input['expand_bg_color'] ?? '#ffffff' ) ?: '#ffffff';
	$clean['expand_text_color']     = sanitize_hex_color( $input['expand_text_color'] ?? '#333333' ) ?: '#333333';
	$allowed_expand_anim            = array( 'slide', 'fade' );
	$clean['expand_animation']      = in_array( $input['expand_animation'] ?? 'slide', $allowed_expand_anim, true ) ? $input['expand_animation'] : 'slide';
	$clean['expand_btn_bg_color']   = sanitize_hex_color( $input['expand_btn_bg_color'] ?? '#2c2c2c' ) ?: '#2c2c2c';
	$clean['expand_btn_text_color'] = sanitize_hex_color( $input['expand_btn_text_color'] ?? '#ffffff' ) ?: '#ffffff';
	$clean['expand_btn_label']      = sanitize_text_field( $input['expand_btn_label'] ?? 'View Project' );
	$allowed_btn_styles             = array( 'filled', 'outlined', 'ghost' );
	$clean['expand_btn_style']      = in_array( $input['expand_btn_style'] ?? 'filled', $allowed_btn_styles, true ) ? $input['expand_btn_style'] : 'filled';
	$allowed_alignment              = array( 'left', 'center', 'right' );
	$clean['expand_btn_alignment']  = in_array( $input['expand_btn_alignment'] ?? 'left', $allowed_alignment, true ) ? $input['expand_btn_alignment'] : 'left';

	return $clean;
}

/* =========================================================
   SETTINGS PAGE RENDER  (called from post-types.php menu)
   ========================================================= */

function gfolio_settings_page_render(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'g-folio' ) );
	}

	$s = gfolio_get_settings();

	// Show save notice
	$updated = false;
	if ( isset( $_GET['settings-updated'] ) && '1' === $_GET['settings-updated'] ) {
		$updated = true;
	}
	?>
	<div class="wrap gfolio-settings-wrap">

		<div class="gfolio-settings-header">
			<div class="gfolio-settings-brand">
				<span class="dashicons dashicons-portfolio gfolio-brand-icon"></span>
				<div>
					<h1 class="gfolio-settings-title"><?php esc_html_e( 'G Folio — Global Default Settings', 'g-folio' ); ?></h1>
				<p class="gfolio-settings-subtitle"><?php esc_html_e( 'These values apply to all portfolios unless overridden in the individual portfolio settings.', 'g-folio' ); ?></p>
					<p class="gfolio-settings-subtitle"><?php esc_html_e( 'Configure your portfolio showcase', 'g-folio' ); ?></p>
				</div>
			</div>
		</div>

		<?php if ( $updated ) : ?>
			<div class="gfolio-notice gfolio-notice-success">
				<span class="dashicons dashicons-yes-alt"></span>
				<?php esc_html_e( 'Settings saved successfully!', 'g-folio' ); ?>
			</div>
		<?php endif; ?>

		<!-- Shortcode Helper -->
		<div class="gfolio-shortcode-bar">
			<span class="gfolio-shortcode-label"><?php esc_html_e( 'Shortcode:', 'g-folio' ); ?></span>
			<code class="gfolio-shortcode-code">[gfolio]</code>
			<span class="gfolio-shortcode-hint"><?php esc_html_e( 'Paste this shortcode into any page or post to display your portfolio.', 'g-folio' ); ?></span>
		</div>

		<form method="post" action="options.php" id="gfolio-settings-form">
			<?php settings_fields( 'gfolio_settings_group' ); ?>

			<!-- Tab Navigation -->
			<nav class="gfolio-tabs-nav" role="tablist">
				<button type="button" class="gfolio-tab-btn is-active" data-tab="tab-layout" role="tab" aria-selected="true">
					<span class="dashicons dashicons-layout"></span>
					<?php esc_html_e( 'Layout', 'g-folio' ); ?>
				</button>
				<button type="button" class="gfolio-tab-btn" data-tab="tab-filtering" role="tab" aria-selected="false">
					<span class="dashicons dashicons-filter"></span>
					<?php esc_html_e( 'Filtering', 'g-folio' ); ?>
				</button>
				<button type="button" class="gfolio-tab-btn" data-tab="tab-overlay" role="tab" aria-selected="false">
					<span class="dashicons dashicons-visibility"></span>
					<?php esc_html_e( 'Thumbnail Overlay', 'g-folio' ); ?>
				</button>
				<button type="button" class="gfolio-tab-btn" data-tab="tab-click" role="tab" aria-selected="false">
					<span class="dashicons dashicons-cursor"></span>
					<?php esc_html_e( 'Click Behavior', 'g-folio' ); ?>
				</button>
				<button type="button" class="gfolio-tab-btn" data-tab="tab-expand" role="tab" aria-selected="false">
					<span class="dashicons dashicons-editor-expand"></span>
					<?php esc_html_e( 'Expand Panel', 'g-folio' ); ?>
				</button>
			</nav>

			<div class="gfolio-tabs-content">

				<!-- TAB 1: Layout -->
				<div class="gfolio-tab-panel is-active" id="tab-layout" role="tabpanel">
					<div class="gfolio-settings-card">
						<h2 class="gfolio-card-title">
							<span class="dashicons dashicons-layout"></span>
							<?php esc_html_e( 'Grid Layout', 'g-folio' ); ?>
						</h2>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label><?php esc_html_e( 'Grid Mode', 'g-folio' ); ?></label>
								<span class="gfolio-setting-desc"><?php esc_html_e( 'Choose between a uniform CSS grid or a Masonry (varying-height) layout.', 'g-folio' ); ?></span>
							</div>
							<div class="gfolio-setting-control">
								<div class="gfolio-segmented-control">
									<label class="gfolio-segment <?php echo 'grid' === $s['grid_mode'] ? 'is-active' : ''; ?>">
										<input type="radio" name="gfolio_settings[grid_mode]" value="grid" <?php checked( $s['grid_mode'], 'grid' ); ?> />
										<span class="dashicons dashicons-grid-view"></span>
										<?php esc_html_e( 'Grid', 'g-folio' ); ?>
									</label>
									<label class="gfolio-segment <?php echo 'masonry' === $s['grid_mode'] ? 'is-active' : ''; ?>">
										<input type="radio" name="gfolio_settings[grid_mode]" value="masonry" <?php checked( $s['grid_mode'], 'masonry' ); ?> />
										<span class="dashicons dashicons-columns"></span>
										<?php esc_html_e( 'Masonry', 'g-folio' ); ?>
									</label>
								</div>
							</div>
						</div>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label for="gfolio_columns"><?php esc_html_e( 'Number of Columns', 'g-folio' ); ?></label>
								<span class="gfolio-setting-desc"><?php esc_html_e( 'How many columns in the portfolio grid (1–6). Responsive on mobile.', 'g-folio' ); ?></span>
							</div>
							<div class="gfolio-setting-control">
								<div class="gfolio-slider-wrap">
									<input type="range" id="gfolio_columns" name="gfolio_settings[columns]"
										min="1" max="6" step="1"
										value="<?php echo esc_attr( $s['columns'] ); ?>"
										class="gfolio-slider" />
									<span class="gfolio-slider-value"><?php echo esc_html( $s['columns'] ); ?></span>
								</div>
							</div>
						</div>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label><?php esc_html_e( 'Thumbnail Gap', 'g-folio' ); ?></label>
								<span class="gfolio-setting-desc"><?php esc_html_e( 'Add spacing between grid items. Enable to set a custom gap size.', 'g-folio' ); ?></span>
							</div>
							<div class="gfolio-setting-control">
								<label class="gfolio-toggle-switch">
									<input type="checkbox" name="gfolio_settings[thumbnail_padding]" value="1"
										<?php checked( $s['thumbnail_padding'], '1' ); ?>
										class="gfolio-master-toggle" data-target=".gfolio-padding-size-wrap" />
									<span class="gfolio-toggle-slider"></span>
								</label>
							</div>
						</div>

						<div class="gfolio-setting-row gfolio-sub-setting gfolio-padding-size-wrap <?php echo '1' === $s['thumbnail_padding'] ? '' : 'hidden'; ?>">
							<div class="gfolio-setting-label">
								<label for="gfolio_padding_size"><?php esc_html_e( 'Gap Size', 'g-folio' ); ?></label>
								<span class="gfolio-setting-desc"><?php esc_html_e( 'Size of the spacing between thumbnails.', 'g-folio' ); ?></span>
							</div>
							<div class="gfolio-setting-control">
								<div class="gfolio-slider-wrap">
									<input type="range" id="gfolio_padding_size" name="gfolio_settings[padding_size]"
										min="0" max="100" step="1"
										value="<?php echo esc_attr( $s['padding_size'] ); ?>"
										class="gfolio-slider" />
									<span class="gfolio-slider-value"><?php echo esc_html( $s['padding_size'] ); ?></span>
									<span class="gfolio-unit">px</span>
								</div>
							</div>
						</div>

						<div class="gfolio-setting-row gfolio-sub-setting gfolio-padding-size-wrap <?php echo '1' === $s['thumbnail_padding'] ? '' : 'hidden'; ?>">
							<div class="gfolio-setting-label">
								<label><?php esc_html_e( 'Outer Gap', 'g-folio' ); ?></label>
								<span class="gfolio-setting-desc"><?php esc_html_e( 'Apply the same gap around the outside of the grid, not just between tiles.', 'g-folio' ); ?></span>
							</div>
							<div class="gfolio-setting-control">
								<label class="gfolio-toggle-switch">
									<input type="checkbox" name="gfolio_settings[outer_gap]" value="1"
										<?php checked( $s['outer_gap'], '1' ); ?> />
									<span class="gfolio-toggle-slider"></span>
								</label>
							</div>
						</div>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label><?php esc_html_e( 'Full-Width Grid', 'g-folio' ); ?></label>
								<span class="gfolio-setting-desc"><?php esc_html_e( 'Stretch the portfolio grid to the full viewport width.', 'g-folio' ); ?></span>
							</div>
							<div class="gfolio-setting-control">
								<label class="gfolio-toggle-switch">
									<input type="checkbox" name="gfolio_settings[full_width]" value="1" <?php checked( $s['full_width'], '1' ); ?> />
									<span class="gfolio-toggle-slider"></span>
								</label>
							</div>
						</div>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label for="gfolio_border_radius"><?php esc_html_e( 'Thumbnail Border Radius', 'g-folio' ); ?></label>
								<span class="gfolio-setting-desc"><?php esc_html_e( 'Rounds the corners of each thumbnail. 0 = sharp corners.', 'g-folio' ); ?></span>
							</div>
							<div class="gfolio-setting-control">
								<div class="gfolio-slider-wrap">
									<input type="range" id="gfolio_border_radius" name="gfolio_settings[border_radius]"
										min="0" max="50" step="1"
										value="<?php echo esc_attr( $s['border_radius'] ); ?>"
										class="gfolio-slider" />
									<span class="gfolio-slider-value"><?php echo esc_html( $s['border_radius'] ); ?></span>
									<span class="gfolio-unit">px</span>
								</div>
							</div>
						</div>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label><?php esc_html_e( 'Thumbnail Aspect Ratio', 'g-folio' ); ?></label>
								<span class="gfolio-setting-desc"><?php esc_html_e( 'Fixed crop ratio for all thumbnails. Choose a preset or drag the slider for a custom ratio.', 'g-folio' ); ?></span>
							</div>
							<div class="gfolio-setting-control">
								<div class="gfp-aspect-control">
									<select class="gfp-aspect-preset">
										<option value=""><?php esc_html_e( 'Custom', 'g-folio' ); ?></option>
										<option value="0.25"><?php esc_html_e( 'Portrait 1:4', 'g-folio' ); ?></option>
										<option value="0.5625"><?php esc_html_e( 'Portrait 9:16', 'g-folio' ); ?></option>
										<option value="0.75"><?php esc_html_e( 'Portrait 3:4', 'g-folio' ); ?></option>
										<option value="1"><?php esc_html_e( 'Square 1:1', 'g-folio' ); ?></option>
										<option value="1.3333"><?php esc_html_e( 'Landscape 4:3', 'g-folio' ); ?></option>
										<option value="1.7778"><?php esc_html_e( 'Landscape 16:9', 'g-folio' ); ?></option>
										<option value="4"><?php esc_html_e( 'Landscape 4:1', 'g-folio' ); ?></option>
									</select>
									<div class="gfp-aspect-row">
										<div class="gfp-aspect-stage">
											<div class="gfp-aspect-rect"></div>
										</div>
										<div class="gfp-aspect-slider-wrap">
											<input type="range" class="gfp-aspect-range"
												min="0.25" max="4" step="0.001"
												value="<?php echo esc_attr( $s['aspect_ratio'] ); ?>" />
											<span class="gfp-aspect-label"></span>
										</div>
									</div>
									<input type="hidden" name="gfolio_settings[aspect_ratio]"
										class="gfp-aspect-actual"
										value="<?php echo esc_attr( $s['aspect_ratio'] ); ?>" />
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- TAB 2: Filtering -->
				<div class="gfolio-tab-panel" id="tab-filtering" role="tabpanel">
					<div class="gfolio-settings-card">
						<h2 class="gfolio-card-title">
							<span class="dashicons dashicons-filter"></span>
							<?php esc_html_e( 'Category Filter Bar', 'g-folio' ); ?>
						</h2>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label><?php esc_html_e( 'Enable Filter Bar', 'g-folio' ); ?></label>
								<span class="gfolio-setting-desc"><?php esc_html_e( 'Display a row of category filter buttons above the grid.', 'g-folio' ); ?></span>
							</div>
							<div class="gfolio-setting-control">
								<label class="gfolio-toggle-switch">
									<input type="checkbox" name="gfolio_settings[enable_filter]" value="1" <?php checked( $s['enable_filter'], '1' ); ?> />
									<span class="gfolio-toggle-slider"></span>
								</label>
							</div>
						</div>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label><?php esc_html_e( 'Show "All" Button', 'g-folio' ); ?></label>
								<span class="gfolio-setting-desc"><?php esc_html_e( 'Include an "All" button that shows every portfolio item.', 'g-folio' ); ?></span>
							</div>
							<div class="gfolio-setting-control">
								<label class="gfolio-toggle-switch">
									<input type="checkbox" name="gfolio_settings[show_all_button]" value="1" <?php checked( $s['show_all_button'], '1' ); ?> />
									<span class="gfolio-toggle-slider"></span>
								</label>
							</div>
						</div>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label for="gfolio_filter_animation"><?php esc_html_e( 'Filter Animation Style', 'g-folio' ); ?></label>
								<span class="gfolio-setting-desc"><?php esc_html_e( 'Animation used when filtering portfolio items.', 'g-folio' ); ?></span>
							</div>
							<div class="gfolio-setting-control">
								<select id="gfolio_filter_animation" name="gfolio_settings[filter_animation]" class="gfolio-select">
									<option value="fade"     <?php selected( $s['filter_animation'], 'fade' ); ?>><?php esc_html_e( 'Fade', 'g-folio' ); ?></option>
									<option value="scale"    <?php selected( $s['filter_animation'], 'scale' ); ?>><?php esc_html_e( 'Scale', 'g-folio' ); ?></option>
									<option value="slide-up" <?php selected( $s['filter_animation'], 'slide-up' ); ?>><?php esc_html_e( 'Slide Up', 'g-folio' ); ?></option>
									<option value="flip"     <?php selected( $s['filter_animation'], 'flip' ); ?>><?php esc_html_e( 'Flip', 'g-folio' ); ?></option>
								</select>
							</div>
						</div>
					</div>
				</div>

				<!-- TAB 3: Thumbnail Overlay -->
				<div class="gfolio-tab-panel" id="tab-overlay" role="tabpanel">
					<div class="gfolio-settings-card">
						<h2 class="gfolio-card-title">
							<span class="dashicons dashicons-visibility"></span>
							<?php esc_html_e( 'Thumbnail Overlay', 'g-folio' ); ?>
						</h2>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label><?php esc_html_e( 'Show Title on Thumbnail', 'g-folio' ); ?></label>
							</div>
							<div class="gfolio-setting-control">
								<label class="gfolio-toggle-switch">
									<input type="checkbox" name="gfolio_settings[show_title_overlay]" value="1" <?php checked( $s['show_title_overlay'], '1' ); ?> />
									<span class="gfolio-toggle-slider"></span>
								</label>
							</div>
						</div>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label><?php esc_html_e( 'Show Sub Heading on Thumbnail', 'g-folio' ); ?></label>
							</div>
							<div class="gfolio-setting-control">
								<label class="gfolio-toggle-switch">
									<input type="checkbox" name="gfolio_settings[show_subheading_overlay]" value="1" <?php checked( $s['show_subheading_overlay'], '1' ); ?> />
									<span class="gfolio-toggle-slider"></span>
								</label>
							</div>
						</div>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label><?php esc_html_e( 'Show Description on Thumbnail', 'g-folio' ); ?></label>
							</div>
							<div class="gfolio-setting-control">
								<label class="gfolio-toggle-switch">
									<input type="checkbox" name="gfolio_settings[show_desc_overlay]" value="1" <?php checked( $s['show_desc_overlay'], '1' ); ?> />
									<span class="gfolio-toggle-slider"></span>
								</label>
							</div>
						</div>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label for="gfolio_overlay_style"><?php esc_html_e( 'Overlay Visibility Style', 'g-folio' ); ?></label>
								<span class="gfolio-setting-desc"><?php esc_html_e( 'When the overlay text is shown on thumbnails.', 'g-folio' ); ?></span>
							</div>
							<div class="gfolio-setting-control">
								<select id="gfolio_overlay_style" name="gfolio_settings[overlay_style]" class="gfolio-select">
									<option value="always" <?php selected( $s['overlay_style'], 'always' ); ?>><?php esc_html_e( 'Always Visible', 'g-folio' ); ?></option>
									<option value="hover"  <?php selected( $s['overlay_style'], 'hover' ); ?>><?php esc_html_e( 'Hover Reveal', 'g-folio' ); ?></option>
									<option value="fade"   <?php selected( $s['overlay_style'], 'fade' ); ?>><?php esc_html_e( 'Fade In on Hover', 'g-folio' ); ?></option>
								</select>
							</div>
						</div>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label for="gfolio_overlay_bg_color"><?php esc_html_e( 'Overlay Background Color', 'g-folio' ); ?></label>
							</div>
							<div class="gfolio-setting-control gfolio-color-row">
								<input type="text" id="gfolio_overlay_bg_color"
									name="gfolio_settings[overlay_bg_color]"
									value="<?php echo esc_attr( $s['overlay_bg_color'] ); ?>"
									class="gfolio-color-picker"
									data-default-color="#000000" />
							</div>
						</div>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label for="gfolio_overlay_opacity"><?php esc_html_e( 'Overlay Opacity', 'g-folio' ); ?></label>
								<span class="gfolio-setting-desc"><?php esc_html_e( 'Value between 0 (transparent) and 1 (opaque).', 'g-folio' ); ?></span>
							</div>
							<div class="gfolio-setting-control">
								<div class="gfolio-slider-wrap">
									<input type="range" id="gfolio_overlay_opacity" name="gfolio_settings[overlay_opacity]"
										min="0" max="1" step="0.05"
										value="<?php echo esc_attr( $s['overlay_opacity'] ); ?>"
										class="gfolio-slider" />
									<span class="gfolio-slider-value"><?php echo esc_html( $s['overlay_opacity'] ); ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- TAB 4: Click Behavior -->
				<div class="gfolio-tab-panel" id="tab-click" role="tabpanel">
					<div class="gfolio-settings-card">
						<h2 class="gfolio-card-title">
							<span class="dashicons dashicons-cursor"></span>
							<?php esc_html_e( 'Default Click Behavior', 'g-folio' ); ?>
						</h2>
						<p class="gfolio-card-desc"><?php esc_html_e( 'This setting is used as a fallback when individual portfolio items do not have their own click behavior specified.', 'g-folio' ); ?></p>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-control gfolio-full-width">
								<div class="gfolio-radio-group gfolio-radio-settings">
									<label class="gfolio-radio-option <?php echo 'popup' === $s['default_click_behavior'] ? 'is-active' : ''; ?>">
										<input type="radio" name="gfolio_settings[default_click_behavior]" value="popup" <?php checked( $s['default_click_behavior'], 'popup' ); ?> />
										<span class="gfolio-radio-icon"><span class="dashicons dashicons-editor-expand"></span></span>
										<span class="gfolio-radio-label"><?php esc_html_e( 'Lightbox Popup', 'g-folio' ); ?></span>
										<span class="gfolio-radio-desc"><?php esc_html_e( 'Opens content in a lightbox overlay', 'g-folio' ); ?></span>
									</label>
									<label class="gfolio-radio-option <?php echo 'page' === $s['default_click_behavior'] ? 'is-active' : ''; ?>">
										<input type="radio" name="gfolio_settings[default_click_behavior]" value="page" <?php checked( $s['default_click_behavior'], 'page' ); ?> />
										<span class="gfolio-radio-icon"><span class="dashicons dashicons-admin-page"></span></span>
										<span class="gfolio-radio-label"><?php esc_html_e( 'WordPress Page', 'g-folio' ); ?></span>
										<span class="gfolio-radio-desc"><?php esc_html_e( 'Navigate to an existing WP page', 'g-folio' ); ?></span>
									</label>
									<label class="gfolio-radio-option <?php echo 'custom_url' === $s['default_click_behavior'] ? 'is-active' : ''; ?>">
										<input type="radio" name="gfolio_settings[default_click_behavior]" value="custom_url" <?php checked( $s['default_click_behavior'], 'custom_url' ); ?> />
										<span class="gfolio-radio-icon"><span class="dashicons dashicons-admin-links"></span></span>
										<span class="gfolio-radio-label"><?php esc_html_e( 'Custom URL', 'g-folio' ); ?></span>
										<span class="gfolio-radio-desc"><?php esc_html_e( 'Navigate to a custom URL', 'g-folio' ); ?></span>
									</label>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- TAB 5: Expand Panel -->
				<div class="gfolio-tab-panel" id="tab-expand" role="tabpanel">
					<div class="gfolio-settings-card">
						<h2 class="gfolio-card-title">
							<span class="dashicons dashicons-editor-expand"></span>
							<?php esc_html_e( 'Expand Panel Defaults', 'g-folio' ); ?>
						</h2>

						<div class="gfolio-settings-two-col">

							<div class="gfolio-setting-row">
								<div class="gfolio-setting-label">
									<label for="gfolio_expand_bg_color"><?php esc_html_e( 'Panel Background Color', 'g-folio' ); ?></label>
								</div>
								<div class="gfolio-setting-control gfolio-color-row">
									<input type="text" id="gfolio_expand_bg_color"
										name="gfolio_settings[expand_bg_color]"
										value="<?php echo esc_attr( $s['expand_bg_color'] ); ?>"
										class="gfolio-color-picker"
										data-default-color="#ffffff" />
								</div>
							</div>

							<div class="gfolio-setting-row">
								<div class="gfolio-setting-label">
									<label for="gfolio_expand_text_color"><?php esc_html_e( 'Panel Text Color', 'g-folio' ); ?></label>
								</div>
								<div class="gfolio-setting-control gfolio-color-row">
									<input type="text" id="gfolio_expand_text_color"
										name="gfolio_settings[expand_text_color]"
										value="<?php echo esc_attr( $s['expand_text_color'] ); ?>"
										class="gfolio-color-picker"
										data-default-color="#333333" />
								</div>
							</div>

						</div>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label for="gfolio_expand_animation"><?php esc_html_e( 'Panel Open Animation', 'g-folio' ); ?></label>
							</div>
							<div class="gfolio-setting-control">
								<select id="gfolio_expand_animation" name="gfolio_settings[expand_animation]" class="gfolio-select">
									<option value="slide" <?php selected( $s['expand_animation'], 'slide' ); ?>><?php esc_html_e( 'Slide Down', 'g-folio' ); ?></option>
									<option value="fade"  <?php selected( $s['expand_animation'], 'fade' ); ?>><?php esc_html_e( 'Fade In', 'g-folio' ); ?></option>
								</select>
							</div>
						</div>

						<hr class="gfolio-divider" />
						<h3 class="gfolio-subsection-title"><?php esc_html_e( 'Default Project Button', 'g-folio' ); ?></h3>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label for="gfolio_expand_btn_label"><?php esc_html_e( 'Default Button Label', 'g-folio' ); ?></label>
								<span class="gfolio-setting-desc"><?php esc_html_e( 'Used when no per-item label is set.', 'g-folio' ); ?></span>
							</div>
							<div class="gfolio-setting-control">
								<input type="text" id="gfolio_expand_btn_label"
									name="gfolio_settings[expand_btn_label]"
									value="<?php echo esc_attr( $s['expand_btn_label'] ); ?>"
									class="gfolio-text-input" />
							</div>
						</div>

						<div class="gfolio-settings-two-col">
							<div class="gfolio-setting-row">
								<div class="gfolio-setting-label">
									<label for="gfolio_expand_btn_bg_color"><?php esc_html_e( 'Button Background Color', 'g-folio' ); ?></label>
								</div>
								<div class="gfolio-setting-control gfolio-color-row">
									<input type="text" id="gfolio_expand_btn_bg_color"
										name="gfolio_settings[expand_btn_bg_color]"
										value="<?php echo esc_attr( $s['expand_btn_bg_color'] ); ?>"
										class="gfolio-color-picker"
										data-default-color="#2c2c2c" />
								</div>
							</div>
							<div class="gfolio-setting-row">
								<div class="gfolio-setting-label">
									<label for="gfolio_expand_btn_text_color"><?php esc_html_e( 'Button Text Color', 'g-folio' ); ?></label>
								</div>
								<div class="gfolio-setting-control gfolio-color-row">
									<input type="text" id="gfolio_expand_btn_text_color"
										name="gfolio_settings[expand_btn_text_color]"
										value="<?php echo esc_attr( $s['expand_btn_text_color'] ); ?>"
										class="gfolio-color-picker"
										data-default-color="#ffffff" />
								</div>
							</div>
						</div>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label><?php esc_html_e( 'Button Style', 'g-folio' ); ?></label>
							</div>
							<div class="gfolio-setting-control">
								<div class="gfolio-btn-style-picker">
									<label class="gfolio-style-option <?php echo 'filled' === $s['expand_btn_style'] ? 'is-active' : ''; ?>">
										<input type="radio" name="gfolio_settings[expand_btn_style]" value="filled" <?php checked( $s['expand_btn_style'], 'filled' ); ?> />
										<span class="gfolio-style-preview gfolio-style-filled"><?php esc_html_e( 'Filled', 'g-folio' ); ?></span>
									</label>
									<label class="gfolio-style-option <?php echo 'outlined' === $s['expand_btn_style'] ? 'is-active' : ''; ?>">
										<input type="radio" name="gfolio_settings[expand_btn_style]" value="outlined" <?php checked( $s['expand_btn_style'], 'outlined' ); ?> />
										<span class="gfolio-style-preview gfolio-style-outlined"><?php esc_html_e( 'Outlined', 'g-folio' ); ?></span>
									</label>
									<label class="gfolio-style-option <?php echo 'ghost' === $s['expand_btn_style'] ? 'is-active' : ''; ?>">
										<input type="radio" name="gfolio_settings[expand_btn_style]" value="ghost" <?php checked( $s['expand_btn_style'], 'ghost' ); ?> />
										<span class="gfolio-style-preview gfolio-style-ghost"><?php esc_html_e( 'Ghost →', 'g-folio' ); ?></span>
									</label>
								</div>
							</div>
						</div>

						<div class="gfolio-setting-row">
							<div class="gfolio-setting-label">
								<label><?php esc_html_e( 'Button Alignment', 'g-folio' ); ?></label>
							</div>
							<div class="gfolio-setting-control">
								<div class="gfolio-segmented-control">
									<label class="gfolio-segment <?php echo 'left' === $s['expand_btn_alignment'] ? 'is-active' : ''; ?>">
										<input type="radio" name="gfolio_settings[expand_btn_alignment]" value="left" <?php checked( $s['expand_btn_alignment'], 'left' ); ?> />
										<span class="dashicons dashicons-editor-alignleft"></span>
										<?php esc_html_e( 'Left', 'g-folio' ); ?>
									</label>
									<label class="gfolio-segment <?php echo 'center' === $s['expand_btn_alignment'] ? 'is-active' : ''; ?>">
										<input type="radio" name="gfolio_settings[expand_btn_alignment]" value="center" <?php checked( $s['expand_btn_alignment'], 'center' ); ?> />
										<span class="dashicons dashicons-editor-aligncenter"></span>
										<?php esc_html_e( 'Center', 'g-folio' ); ?>
									</label>
									<label class="gfolio-segment <?php echo 'right' === $s['expand_btn_alignment'] ? 'is-active' : ''; ?>">
										<input type="radio" name="gfolio_settings[expand_btn_alignment]" value="right" <?php checked( $s['expand_btn_alignment'], 'right' ); ?> />
										<span class="dashicons dashicons-editor-alignright"></span>
										<?php esc_html_e( 'Right', 'g-folio' ); ?>
									</label>
								</div>
							</div>
						</div>

					</div>
				</div>

			</div><!-- .gfolio-tabs-content -->

			<div class="gfolio-settings-footer">
				<?php submit_button( __( 'Save Settings', 'g-folio' ), 'primary gfolio-save-btn', 'submit', false ); ?>
			</div>

		</form>
	</div><!-- .gfolio-settings-wrap -->
	<?php
}
