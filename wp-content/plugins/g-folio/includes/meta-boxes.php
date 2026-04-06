<?php
/**
 * Meta Boxes for Portfolio Items & Portfolios
 *
 * @package G_Folio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================
   REMOVE THE DEFAULT TAXONOMY META BOX FOR gfolio_category
   We replace it with a custom per-portfolio-filtered version.
   ========================================================= */

add_action( 'add_meta_boxes_gfolio_item', 'gfolio_remove_default_boxes' );
function gfolio_remove_default_boxes(): void {
	remove_meta_box( 'gfolio_categorydiv', 'gfolio_item', 'side' );
	remove_meta_box( 'postimagediv',       'gfolio_item', 'side' );
}

/* =========================================================
   ENSURE ALL G FOLIO META BOXES ARE VISIBLE BY DEFAULT
   ========================================================= */

add_filter( 'default_hidden_meta_boxes', 'gfolio_default_shown_meta_boxes', 10, 2 );
function gfolio_default_shown_meta_boxes( array $hidden, WP_Screen $screen ): array {
	if ( ! isset( $screen->post_type ) ) {
		return $hidden;
	}

	if ( 'gfolio_item' === $screen->post_type ) {
		$our_boxes = array(
			'gfolio_media_box',
			'gfolio_details_box',
			'gfolio_assign_portfolio_box',
			'gfolio_click_behavior_box',
			'gfolio_expand_panel_box',
			'gfolio_project_button_box',
		);
		$hidden = array_diff( $hidden, $our_boxes );
	}

	if ( 'gfolio_portfolio' === $screen->post_type ) {
		$our_boxes = array(
			'gfolio_shortcode_box',
			'gfolio_portfolio_settings_box',
		);
		$hidden = array_diff( $hidden, $our_boxes );
	}

	return $hidden;
}

/* =========================================================
   REGISTER META BOXES
   ========================================================= */

add_action( 'add_meta_boxes', 'gfolio_register_meta_boxes' );

function gfolio_register_meta_boxes(): void {

	// ---- gfolio_item boxes ----
	add_meta_box(
		'gfolio_media_box',
		__( 'Thumbnail Image', 'g-folio' ),
		'gfolio_render_media_box',
		'gfolio_item',
		'side',
		'high'
	);

	add_meta_box(
		'gfolio_assign_portfolio_box',
		__( 'Assign to Portfolio(s)', 'g-folio' ),
		'gfolio_render_assign_portfolio_box',
		'gfolio_item',
		'side',
		'high'
	);

	add_meta_box(
		'gfolio_details_box',
		__( 'Portfolio Item Details', 'g-folio' ),
		'gfolio_render_details_box',
		'gfolio_item',
		'normal',
		'high'
	);

	add_meta_box(
		'gfolio_click_behavior_box',
		__( 'Click / Link Behavior', 'g-folio' ),
		'gfolio_render_click_box',
		'gfolio_item',
		'normal',
		'high'
	);

	add_meta_box(
		'gfolio_expand_panel_box',
		__( 'Expanded Content Panel (Lightbox & In-Grid Drawer)', 'g-folio' ),
		'gfolio_render_expand_box',
		'gfolio_item',
		'normal',
		'high'
	);

	add_meta_box(
		'gfolio_project_button_box',
		__( 'Project Button', 'g-folio' ),
		'gfolio_render_button_box',
		'gfolio_item',
		'normal',
		'default'
	);

	// ---- gfolio_portfolio boxes ----
	add_meta_box(
		'gfolio_shortcode_box',
		__( 'Shortcode', 'g-folio' ),
		'gfolio_render_shortcode_box',
		'gfolio_portfolio',
		'side',
		'high'
	);

	add_meta_box(
		'gfolio_portfolio_settings_box',
		__( 'Portfolio Settings', 'g-folio' ),
		'gfolio_render_portfolio_settings_box',
		'gfolio_portfolio',
		'normal',
		'high'
	);
}

/* =========================================================
   HELPER: GET POST META WITH DEFAULT
   ========================================================= */

function gfolio_get_meta( int $post_id, string $key, mixed $default = '' ): mixed {
	$val = get_post_meta( $post_id, $key, true );
	return ( $val !== '' && $val !== false ) ? $val : $default;
}

/* =========================================================
   RENDER: THUMBNAIL IMAGE BOX
   ========================================================= */

function gfolio_render_media_box( WP_Post $post ): void {
	$thumb_id  = gfolio_get_meta( $post->ID, '_gfolio_thumbnail_id' );
	$thumb_url = $thumb_id ? wp_get_attachment_image_url( (int) $thumb_id, 'medium' ) : '';
	?>
	<div class="gfolio-media-box">
		<div class="gfolio-thumb-preview">
			<?php if ( $thumb_url ) : ?>
				<img src="<?php echo esc_url( $thumb_url ); ?>" id="gfolio-thumb-preview-img" alt="<?php esc_attr_e( 'Thumbnail preview', 'g-folio' ); ?>" />
			<?php else : ?>
				<div class="gfolio-thumb-placeholder" id="gfolio-thumb-placeholder">
					<span class="dashicons dashicons-format-image"></span>
					<p><?php esc_html_e( 'No image selected', 'g-folio' ); ?></p>
				</div>
				<img src="" id="gfolio-thumb-preview-img" alt="" style="display:none;" />
			<?php endif; ?>
		</div>

		<input type="hidden" name="gfolio_thumbnail_id" id="gfolio_thumbnail_id" value="<?php echo esc_attr( $thumb_id ); ?>" />

		<div class="gfolio-media-buttons">
			<button type="button" class="button button-primary gfolio-upload-btn" id="gfolio-upload-thumb-btn">
				<span class="dashicons dashicons-upload"></span>
				<?php esc_html_e( 'Select Image', 'g-folio' ); ?>
			</button>
			<button type="button" class="button gfolio-remove-btn <?php echo $thumb_id ? '' : 'hidden'; ?>" id="gfolio-remove-thumb-btn">
				<span class="dashicons dashicons-trash"></span>
				<?php esc_html_e( 'Remove', 'g-folio' ); ?>
			</button>
		</div>
		<p class="gfolio-field-hint"><?php esc_html_e( 'Select an image from the Media Library to use as the portfolio item thumbnail.', 'g-folio' ); ?></p>
	</div>
	<?php
}

/* =========================================================
   RENDER: ASSIGN TO PORTFOLIO(S) BOX
   ========================================================= */

function gfolio_render_assign_portfolio_box( WP_Post $post ): void {
	wp_nonce_field( 'gfolio_save_meta', 'gfolio_meta_nonce' );

	$portfolios = gfolio_get_all_portfolios();

	// Saved portfolio IDs for this item
	$assigned = get_post_meta( $post->ID, '_gfolio_portfolio_ids', true );
	if ( ! is_array( $assigned ) ) {
		$assigned = array();
	}
	$assigned = array_map( 'intval', $assigned );

	// Saved category terms for this item
	$current_terms = wp_get_object_terms( $post->ID, 'gfolio_category', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $current_terms ) ) {
		$current_terms = array();
	}

	// All category terms, pre-grouped by their assigned portfolio
	$all_terms = get_terms( array(
		'taxonomy'   => 'gfolio_category',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	) );
	if ( is_wp_error( $all_terms ) ) {
		$all_terms = array();
	}

	// Build map: portfolio_id => [ WP_Term, ... ]
	$terms_by_portfolio = array();
	foreach ( $all_terms as $term ) {
		$term_portfolio_id = (int) get_term_meta( $term->term_id, 'gfolio_cat_portfolio_id', true );
		if ( $term_portfolio_id ) {
			$terms_by_portfolio[ $term_portfolio_id ][] = $term;
		}
	}
	?>
	<div class="gfolio-meta-section">

		<?php if ( empty( $portfolios ) ) : ?>
			<p class="gfolio-field-hint">
				<?php esc_html_e( 'No portfolios yet. ', 'g-folio' ); ?>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=gfolio_portfolio' ) ); ?>">
					<?php esc_html_e( 'Create your first portfolio →', 'g-folio' ); ?>
				</a>
			</p>
		<?php else : ?>

			<div class="gfolio-portfolio-cat-list">
				<?php foreach ( $portfolios as $portfolio ) :
					$is_assigned = in_array( $portfolio->ID, $assigned, true );
				?>
					<div class="gfolio-portfolio-row">

						<!-- Portfolio header: checkbox + name + toggle arrow -->
						<div class="gfolio-portfolio-header">
							<label class="gfolio-portfolio-label">
								<input
									type="checkbox"
									name="gfolio_portfolio_ids[]"
									value="<?php echo esc_attr( $portfolio->ID ); ?>"
									class="gfolio-portfolio-checkbox"
									<?php checked( $is_assigned ); ?>
								/>
								<span class="gfolio-portfolio-name"><?php echo esc_html( $portfolio->post_title ); ?></span>
							</label>
							<button
								type="button"
								class="gfolio-accordion-toggle<?php echo $is_assigned ? ' is-open' : ''; ?>"
								aria-expanded="<?php echo $is_assigned ? 'true' : 'false'; ?>"
								aria-label="<?php esc_attr_e( 'Toggle categories', 'g-folio' ); ?>"
							>
								<span class="dashicons dashicons-arrow-down-alt2"></span>
							</button>
						</div><!-- .gfolio-portfolio-header -->

						<!-- Collapsible category list — only terms assigned to this portfolio -->
						<?php $portfolio_terms = $terms_by_portfolio[ $portfolio->ID ] ?? array(); ?>
						<div class="gfolio-inline-cats"<?php echo $is_assigned ? '' : ' style="display:none;"'; ?>>
							<?php if ( empty( $portfolio_terms ) ) : ?>
								<p class="gfolio-field-hint" style="padding-left:8px;">
									<?php esc_html_e( 'No categories assigned to this portfolio. ', 'g-folio' ); ?>
									<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=gfolio_category&post_type=gfolio_item' ) ); ?>">
										<?php esc_html_e( 'Manage categories →', 'g-folio' ); ?>
									</a>
								</p>
							<?php else : ?>
								<div class="gfolio-checkbox-group gfolio-checkbox-group--indent">
									<?php foreach ( $portfolio_terms as $term ) : ?>
										<label class="gfolio-checkbox-plain">
											<input
												type="checkbox"
												name="gfolio_categories[]"
												value="<?php echo esc_attr( $term->term_id ); ?>"
												class="gfolio-category-checkbox"
												<?php checked( in_array( $term->term_id, $current_terms, true ) ); ?>
											/>
											<?php echo esc_html( $term->name ); ?>
										</label>
									<?php endforeach; ?>
								</div>
								<p class="gfolio-field-hint gfolio-field-hint--indent" style="padding-left:8px;margin-top:4px;">
									<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=gfolio_category&post_type=gfolio_item' ) ); ?>">
										<?php esc_html_e( '+ Manage categories', 'g-folio' ); ?>
									</a>
								</p>
							<?php endif; ?>
						</div><!-- .gfolio-inline-cats -->

					</div><!-- .gfolio-portfolio-row -->
				<?php endforeach; ?>
			</div><!-- .gfolio-portfolio-cat-list -->

		<?php endif; ?>

	</div>
	<?php
}

/* =========================================================
   RENDER: ITEM DETAILS BOX
   ========================================================= */

function gfolio_render_details_box( WP_Post $post ): void {
	$subheading  = gfolio_get_meta( $post->ID, '_gfolio_subheading' );
	$description = gfolio_get_meta( $post->ID, '_gfolio_description' );
	?>
	<div class="gfolio-meta-section">
		<div class="gfolio-field-row">
			<label class="gfolio-field-label" for="gfolio_subheading">
				<?php esc_html_e( 'Sub Heading', 'g-folio' ); ?>
			</label>
			<input
				type="text"
				id="gfolio_subheading"
				name="gfolio_subheading"
				class="gfolio-text-input"
				value="<?php echo esc_attr( $subheading ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. Web Design · Brand Identity', 'g-folio' ); ?>"
			/>
			<span class="gfolio-field-hint"><?php esc_html_e( 'A short subtitle shown below the title.', 'g-folio' ); ?></span>
		</div>

		<div class="gfolio-field-row">
			<label class="gfolio-field-label" for="gfolio_description">
				<?php esc_html_e( 'Description', 'g-folio' ); ?>
			</label>
			<textarea
				id="gfolio_description"
				name="gfolio_description"
				class="gfolio-textarea"
				rows="4"
				placeholder="<?php esc_attr_e( 'Brief description of this portfolio item…', 'g-folio' ); ?>"
			><?php echo esc_textarea( $description ); ?></textarea>
			<span class="gfolio-field-hint"><?php esc_html_e( 'Shown on the overlay and/or the expanded panel.', 'g-folio' ); ?></span>
		</div>
	</div>
	<?php
}

/* =========================================================
   RENDER: CLICK BEHAVIOR BOX
   ========================================================= */

function gfolio_render_click_box( WP_Post $post ): void {
	$click_type = gfolio_get_meta( $post->ID, '_gfolio_click_type', 'popup' );
	$page_id    = gfolio_get_meta( $post->ID, '_gfolio_link_page_id' );
	$custom_url = gfolio_get_meta( $post->ID, '_gfolio_link_custom_url' );
	$link_blank = gfolio_get_meta( $post->ID, '_gfolio_link_blank', '0' );

	$pages = get_pages( array( 'sort_column' => 'post_title', 'sort_order' => 'ASC' ) );
	?>
	<div class="gfolio-meta-section">
		<p class="gfolio-section-desc"><?php esc_html_e( 'Choose what happens when a visitor clicks this portfolio item.', 'g-folio' ); ?></p>
		<div class="gfolio-info-box">
			<span class="dashicons dashicons-info-outline"></span>
			<?php esc_html_e( 'The rich content editor (WYSIWYG) for both the Lightbox Popup and the In-Grid Drawer is in the "Expanded Content Panel" box directly below.', 'g-folio' ); ?>
		</div>

		<div class="gfolio-radio-group">
			<label class="gfolio-radio-option <?php echo 'popup' === $click_type ? 'is-active' : ''; ?>">
				<input type="radio" name="gfolio_click_type" value="popup" <?php checked( $click_type, 'popup' ); ?> />
				<span class="gfolio-radio-icon"><span class="dashicons dashicons-editor-expand"></span></span>
				<span class="gfolio-radio-label"><?php esc_html_e( 'Lightbox Popup', 'g-folio' ); ?></span>
				<span class="gfolio-radio-desc"><?php esc_html_e( 'Opens expanded content in a lightbox overlay', 'g-folio' ); ?></span>
			</label>

			<label class="gfolio-radio-option <?php echo 'page' === $click_type ? 'is-active' : ''; ?>">
				<input type="radio" name="gfolio_click_type" value="page" <?php checked( $click_type, 'page' ); ?> />
				<span class="gfolio-radio-icon"><span class="dashicons dashicons-admin-page"></span></span>
				<span class="gfolio-radio-label"><?php esc_html_e( 'WordPress Page', 'g-folio' ); ?></span>
				<span class="gfolio-radio-desc"><?php esc_html_e( 'Navigate to an existing WordPress page', 'g-folio' ); ?></span>
			</label>

			<label class="gfolio-radio-option <?php echo 'custom_url' === $click_type ? 'is-active' : ''; ?>">
				<input type="radio" name="gfolio_click_type" value="custom_url" <?php checked( $click_type, 'custom_url' ); ?> />
				<span class="gfolio-radio-icon"><span class="dashicons dashicons-admin-links"></span></span>
				<span class="gfolio-radio-label"><?php esc_html_e( 'Custom URL', 'g-folio' ); ?></span>
				<span class="gfolio-radio-desc"><?php esc_html_e( 'Enter any URL to navigate to', 'g-folio' ); ?></span>
			</label>
		</div>

		<!-- Page Selector -->
		<div class="gfolio-click-sub-field gfolio-field-page <?php echo 'page' === $click_type ? '' : 'hidden'; ?>">
			<label class="gfolio-field-label"><?php esc_html_e( 'Select Page', 'g-folio' ); ?></label>
			<select name="gfolio_link_page_id" class="gfolio-select">
				<option value=""><?php esc_html_e( '— Choose a page —', 'g-folio' ); ?></option>
				<?php foreach ( $pages as $page ) : ?>
					<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $page_id, $page->ID ); ?>>
						<?php echo esc_html( $page->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<!-- Custom URL Input -->
		<div class="gfolio-click-sub-field gfolio-field-custom-url <?php echo 'custom_url' === $click_type ? '' : 'hidden'; ?>">
			<label class="gfolio-field-label" for="gfolio_link_custom_url"><?php esc_html_e( 'Custom URL', 'g-folio' ); ?></label>
			<input
				type="url"
				id="gfolio_link_custom_url"
				name="gfolio_link_custom_url"
				class="gfolio-text-input"
				value="<?php echo esc_url( $custom_url ); ?>"
				placeholder="https://"
			/>
		</div>

		<!-- Open in New Tab -->
		<div class="gfolio-click-sub-field gfolio-field-blank <?php echo 'popup' === $click_type ? 'hidden' : ''; ?>">
			<label class="gfolio-checkbox-plain" for="gfolio_link_blank">
				<input type="checkbox" name="gfolio_link_blank" value="1" <?php checked( $link_blank, '1' ); ?> id="gfolio_link_blank" />
				<?php esc_html_e( 'Open in new tab', 'g-folio' ); ?>
			</label>
		</div>
	</div>
	<?php
}

/* =========================================================
   RENDER: PROJECT BUTTON BOX
   ========================================================= */

function gfolio_render_button_box( WP_Post $post ): void {
	$btn_label = gfolio_get_meta( $post->ID, '_gfolio_btn_label' );
	$btn_url   = gfolio_get_meta( $post->ID, '_gfolio_btn_url' );
	$btn_blank = gfolio_get_meta( $post->ID, '_gfolio_btn_blank', '0' );
	$btn_style = gfolio_get_meta( $post->ID, '_gfolio_btn_style', 'filled' );
	?>
	<div class="gfolio-meta-section">
		<p class="gfolio-section-desc"><?php esc_html_e( 'Optional. If a Button URL is provided, a call-to-action button will automatically appear in the popup and expand panel. Leave the URL empty to hide the button.', 'g-folio' ); ?></p>

		<div class="gfolio-field-row">
			<label class="gfolio-field-label" for="gfolio_btn_label"><?php esc_html_e( 'Button Label', 'g-folio' ); ?></label>
			<input
				type="text"
				id="gfolio_btn_label"
				name="gfolio_btn_label"
				class="gfolio-text-input"
				value="<?php echo esc_attr( $btn_label ); ?>"
				placeholder="<?php esc_attr_e( 'View Project', 'g-folio' ); ?>"
			/>
		</div>

		<div class="gfolio-field-row">
			<label class="gfolio-field-label" for="gfolio_btn_url"><?php esc_html_e( 'Button URL', 'g-folio' ); ?></label>
			<input
				type="url"
				id="gfolio_btn_url"
				name="gfolio_btn_url"
				class="gfolio-text-input"
				value="<?php echo esc_url( $btn_url ); ?>"
				placeholder="https://"
			/>
		</div>

		<div class="gfolio-field-row">
			<label class="gfolio-checkbox-plain" for="gfolio_btn_blank">
				<input type="checkbox" name="gfolio_btn_blank" value="1" <?php checked( $btn_blank, '1' ); ?> id="gfolio_btn_blank" />
				<?php esc_html_e( 'Open in new tab', 'g-folio' ); ?>
			</label>
		</div>

		<div class="gfolio-field-row">
			<label class="gfolio-field-label"><?php esc_html_e( 'Button Style', 'g-folio' ); ?></label>
			<div class="gfolio-btn-style-picker">
				<label class="gfolio-style-option <?php echo 'filled' === $btn_style ? 'is-active' : ''; ?>">
					<input type="radio" name="gfolio_btn_style" value="filled" <?php checked( $btn_style, 'filled' ); ?> />
					<span class="gfolio-style-preview gfolio-style-filled"><?php esc_html_e( 'Filled', 'g-folio' ); ?></span>
				</label>
				<label class="gfolio-style-option <?php echo 'outlined' === $btn_style ? 'is-active' : ''; ?>">
					<input type="radio" name="gfolio_btn_style" value="outlined" <?php checked( $btn_style, 'outlined' ); ?> />
					<span class="gfolio-style-preview gfolio-style-outlined"><?php esc_html_e( 'Outlined', 'g-folio' ); ?></span>
				</label>
				<label class="gfolio-style-option <?php echo 'ghost' === $btn_style ? 'is-active' : ''; ?>">
					<input type="radio" name="gfolio_btn_style" value="ghost" <?php checked( $btn_style, 'ghost' ); ?> />
					<span class="gfolio-style-preview gfolio-style-ghost"><?php esc_html_e( 'Ghost →', 'g-folio' ); ?></span>
				</label>
			</div>
		</div>
	</div>
	<?php
}

/* =========================================================
   RENDER: EXPANDED CONTENT PANEL BOX
   ========================================================= */

function gfolio_render_expand_box( WP_Post $post ): void {
	$expand_content    = gfolio_get_meta( $post->ID, '_gfolio_expand_content' );
	$expand_show_title = gfolio_get_meta( $post->ID, '_gfolio_expand_show_title', '1' );
	$expand_show_sub   = gfolio_get_meta( $post->ID, '_gfolio_expand_show_sub', '1' );
	$expand_show_desc  = gfolio_get_meta( $post->ID, '_gfolio_expand_show_desc', '1' );

	$editor_id = 'gfolio_expand_content';
	?>
	<div class="gfolio-meta-section">
		<p class="gfolio-section-desc"><?php esc_html_e( 'Optional. Add content below to enable the in-grid expand drawer and lightbox popup for this item. Leave empty to disable.', 'g-folio' ); ?></p>

		<div class="gfolio-field-row">
			<label class="gfolio-field-label"><?php esc_html_e( 'Show in Panel', 'g-folio' ); ?></label>
			<div class="gfolio-checkbox-group">
				<label class="gfolio-checkbox-plain">
					<input type="checkbox" name="gfolio_expand_show_title" value="1" <?php checked( $expand_show_title, '1' ); ?> />
					<?php esc_html_e( 'Title', 'g-folio' ); ?>
				</label>
				<label class="gfolio-checkbox-plain">
					<input type="checkbox" name="gfolio_expand_show_sub" value="1" <?php checked( $expand_show_sub, '1' ); ?> />
					<?php esc_html_e( 'Sub Heading', 'g-folio' ); ?>
				</label>
				<label class="gfolio-checkbox-plain">
					<input type="checkbox" name="gfolio_expand_show_desc" value="1" <?php checked( $expand_show_desc, '1' ); ?> />
					<?php esc_html_e( 'Description', 'g-folio' ); ?>
				</label>
			</div>
		</div>

		<div class="gfolio-field-row">
			<label class="gfolio-field-label"><?php esc_html_e( 'Rich Content (supports shortcodes)', 'g-folio' ); ?></label>
			<?php
			wp_editor(
				wp_kses_post( $expand_content ),
				$editor_id,
				array(
					'textarea_name' => 'gfolio_expand_content',
					'textarea_rows' => 10,
					'teeny'         => false,
					'media_buttons' => true,
				)
			);
			?>
		</div>
	</div>
	<?php
}

/* =========================================================
   RENDER: PORTFOLIO SHORTCODE BOX
   ========================================================= */

function gfolio_render_shortcode_box( WP_Post $post ): void {
	// Only show shortcode if the portfolio is already published/saved
	if ( 'auto-draft' === $post->post_status ) {
		echo '<p class="gfolio-field-hint">' . esc_html__( 'Save this portfolio to generate its shortcode.', 'g-folio' ) . '</p>';
		return;
	}

	$shortcode = '[gfolio id="' . $post->ID . '"]';
	?>
	<div class="gfolio-meta-section">
		<p class="gfolio-section-desc">
			<?php esc_html_e( 'Paste this shortcode into any page or post to display this portfolio.', 'g-folio' ); ?>
		</p>
		<div class="gfolio-shortcode-display">
			<code id="gfolio-shortcode-text"><?php echo esc_html( $shortcode ); ?></code>
			<button type="button" class="button gfolio-copy-shortcode-btn" data-shortcode="<?php echo esc_attr( $shortcode ); ?>">
				<span class="dashicons dashicons-clipboard"></span>
				<?php esc_html_e( 'Copy', 'g-folio' ); ?>
			</button>
		</div>
	</div>
	<?php
}

/* =========================================================
   HELPER: 3-WAY SEGMENTED CONTROL  (inherit-global / off / on)
   ========================================================= */

function gfp_three_way( string $name, string $value, string $global_label ): void {
	?>
	<div class="gfolio-segmented-control">
		<label class="gfolio-segment <?php echo '' === $value ? 'is-active' : ''; ?>">
			<input type="radio" name="<?php echo esc_attr( $name ); ?>" value="" <?php checked( $value, '' ); ?> />
			<span class="dashicons dashicons-undo"></span>
			<?php echo esc_html( $global_label ); ?>
		</label>
		<label class="gfolio-segment <?php echo '0' === $value ? 'is-active' : ''; ?>">
			<input type="radio" name="<?php echo esc_attr( $name ); ?>" value="0" <?php checked( $value, '0' ); ?> />
			<?php esc_html_e( 'Off', 'g-folio' ); ?>
		</label>
		<label class="gfolio-segment <?php echo '1' === $value ? 'is-active' : ''; ?>">
			<input type="radio" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $value, '1' ); ?> />
			<?php esc_html_e( 'On', 'g-folio' ); ?>
		</label>
	</div>
	<?php
}

/* =========================================================
   HELPER: COLOR FIELD WITH GLOBAL / CUSTOM TOGGLE
   ========================================================= */

function gfp_color_field( string $field_id, string $name, string $value, string $global_color ): void {
	$has_custom = ( '' !== $value );
	?>
	<div class="gfp-color-field <?php echo $has_custom ? 'is-custom' : 'is-global'; ?>"
		data-global="<?php echo esc_attr( $global_color ); ?>">
		<!-- Actual submitted value: '' = use global, '#xxx' = custom -->
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>"
			class="gfp-color-actual"
			value="<?php echo esc_attr( $value ); ?>" />
		<!-- WP color picker (no name — JS syncs its change to the hidden input) -->
		<input type="text" id="<?php echo esc_attr( $field_id ); ?>"
			class="gfolio-color-picker gfp-picker-display"
			value="<?php echo esc_attr( $has_custom ? $value : $global_color ); ?>"
			data-default-color="<?php echo esc_attr( $global_color ); ?>" />
		<div class="gfp-color-actions">
			<span class="gfp-global-badge <?php echo $has_custom ? 'hidden' : ''; ?>">
				<span class="dashicons dashicons-admin-site"></span>
				<?php esc_html_e( 'Using global', 'g-folio' ); ?>
			</span>
			<button type="button"
				class="gfp-reset-color button button-small <?php echo $has_custom ? '' : 'hidden'; ?>">
				<span class="dashicons dashicons-undo"></span>
				<?php esc_html_e( 'Use Global', 'g-folio' ); ?>
			</button>
		</div>
	</div>
	<?php
}

/* =========================================================
   HELPER: FORMAT ASPECT RATIO AS READABLE LABEL
   ========================================================= */

function gfp_format_ratio_label( string $ratio ): string {
	if ( '' === $ratio ) {
		return '';
	}
	$r   = (float) $ratio;
	$map = array(
		0.25   => '1:4',
		0.5625 => '9:16',
		0.75   => '3:4',
		1.0    => '1:1',
		1.3333 => '4:3',
		1.7778 => '16:9',
		4.0    => '4:1',
	);
	foreach ( $map as $val => $label ) {
		if ( abs( $r - $val ) < 0.005 ) {
			return $label;
		}
	}
	return number_format( $r, 2 );
}

/* =========================================================
   RENDER: PORTFOLIO SETTINGS BOX
   ========================================================= */

function gfolio_render_portfolio_settings_box( WP_Post $post ): void {
	wp_nonce_field( 'gfolio_save_portfolio_meta', 'gfolio_portfolio_nonce' );

	$global = gfolio_get_settings();

	// Helper: get saved per-portfolio value (empty string = use global)
	$pm = function( string $key ) use ( $post ): string {
		return (string) get_post_meta( $post->ID, '_gfoliop_' . $key, true );
	};

	$grid_mode     = $pm( 'grid_mode' );
	$columns       = $pm( 'columns' );
	$padding       = $pm( 'thumbnail_padding' );
	$padding_size  = $pm( 'padding_size' );
	$border_radius = $pm( 'border_radius' );
	$aspect_ratio  = $pm( 'aspect_ratio' );
	$full_width    = $pm( 'full_width' );
	$enable_filter = $pm( 'enable_filter' );
	$show_all_btn  = $pm( 'show_all_button' );
	$filter_anim   = $pm( 'filter_animation' );
	$overlay_style = $pm( 'overlay_style' );
	$overlay_bg    = $pm( 'overlay_bg_color' );
	$overlay_op    = $pm( 'overlay_opacity' );
	$show_title    = $pm( 'show_title_overlay' );
	$show_sub      = $pm( 'show_subheading_overlay' );
	$show_desc     = $pm( 'show_desc_overlay' );
	$click_beh     = $pm( 'default_click_behavior' );
	$expand_bg     = $pm( 'expand_bg_color' );
	$expand_fg     = $pm( 'expand_text_color' );
	$expand_anim   = $pm( 'expand_animation' );
	$btn_label     = $pm( 'expand_btn_label' );
	$btn_style     = $pm( 'expand_btn_style' );
	$btn_align     = $pm( 'expand_btn_alignment' );
	$btn_bg        = $pm( 'expand_btn_bg_color' );
	$btn_fg        = $pm( 'expand_btn_text_color' );

	// Helper: format a boolean global for display
	$g_bool = function( string $key ): string {
		return '1' === $global[ $key ]
			? esc_html__( 'On', 'g-folio' )
			: esc_html__( 'Off', 'g-folio' );
	};
	?>
	<div class="gfolio-pbox">
		<!-- Tab navigation -->
		<nav class="gfp-tabs-nav" role="tablist">
			<button type="button" class="gfp-tab-btn is-active" data-tab="gfp-tab-layout" role="tab" aria-selected="true">
				<span class="dashicons dashicons-layout"></span>
				<?php esc_html_e( 'Layout', 'g-folio' ); ?>
			</button>
			<button type="button" class="gfp-tab-btn" data-tab="gfp-tab-filtering" role="tab" aria-selected="false">
				<span class="dashicons dashicons-filter"></span>
				<?php esc_html_e( 'Filter', 'g-folio' ); ?>
			</button>
			<button type="button" class="gfp-tab-btn" data-tab="gfp-tab-overlay" role="tab" aria-selected="false">
				<span class="dashicons dashicons-visibility"></span>
				<?php esc_html_e( 'Overlay', 'g-folio' ); ?>
			</button>
			<button type="button" class="gfp-tab-btn" data-tab="gfp-tab-click" role="tab" aria-selected="false">
				<span class="dashicons dashicons-cursor"></span>
				<?php esc_html_e( 'Click', 'g-folio' ); ?>
			</button>
			<button type="button" class="gfp-tab-btn" data-tab="gfp-tab-expand" role="tab" aria-selected="false">
				<span class="dashicons dashicons-editor-expand"></span>
				<?php esc_html_e( 'Expand', 'g-folio' ); ?>
			</button>
		</nav>

		<div class="gfp-tabs-content">

			<!-- ── LAYOUT ────────────────────────────────────── -->
			<div class="gfp-tab-panel is-active" id="gfp-tab-layout" role="tabpanel">

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Grid Mode', 'g-folio' ); ?></label>
					<div class="gfolio-segmented-control">
						<label class="gfolio-segment <?php echo '' === $grid_mode ? 'is-active' : ''; ?>">
							<input type="radio" name="gfoliop_grid_mode" value="" <?php checked( $grid_mode, '' ); ?> />
							<span class="dashicons dashicons-undo"></span>
							<?php printf( esc_html__( 'Global (%s)', 'g-folio' ), esc_html( $global['grid_mode'] ) ); ?>
						</label>
						<label class="gfolio-segment <?php echo 'grid' === $grid_mode ? 'is-active' : ''; ?>">
							<input type="radio" name="gfoliop_grid_mode" value="grid" <?php checked( $grid_mode, 'grid' ); ?> />
							<span class="dashicons dashicons-grid-view"></span>
							<?php esc_html_e( 'Grid', 'g-folio' ); ?>
						</label>
						<label class="gfolio-segment <?php echo 'masonry' === $grid_mode ? 'is-active' : ''; ?>">
							<input type="radio" name="gfoliop_grid_mode" value="masonry" <?php checked( $grid_mode, 'masonry' ); ?> />
							<span class="dashicons dashicons-columns"></span>
							<?php esc_html_e( 'Masonry', 'g-folio' ); ?>
						</label>
					</div>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label" for="gfoliop_columns"><?php esc_html_e( 'Columns', 'g-folio' ); ?></label>
					<select name="gfoliop_columns" id="gfoliop_columns" class="gfolio-select">
						<option value=""><?php printf( esc_html__( '↩ Global (%s)', 'g-folio' ), esc_html( $global['columns'] ) ); ?></option>
						<?php foreach ( range( 1, 6 ) as $c ) : ?>
							<option value="<?php echo $c; ?>" <?php selected( $columns, (string) $c ); ?>><?php echo $c; ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Thumbnail Gap', 'g-folio' ); ?></label>
					<?php gfp_three_way(
						'gfoliop_thumbnail_padding',
						$padding,
						sprintf( __( 'Global (%s)', 'g-folio' ), $g_bool( 'thumbnail_padding' ) )
					); ?>
				</div>

				<div class="gfp-padding-size-wrap gfolio-field-row <?php echo '1' !== $padding ? 'hidden' : ''; ?>">
					<label class="gfolio-field-label" for="gfoliop_padding_size"><?php esc_html_e( 'Gap Size', 'g-folio' ); ?></label>
					<div style="display:flex;align-items:center;gap:8px;">
						<input type="number" id="gfoliop_padding_size" name="gfoliop_padding_size"
							min="0" max="100"
							value="<?php echo esc_attr( '' !== $padding_size ? $padding_size : $global['padding_size'] ); ?>"
							class="gfolio-number-input" />
						<span class="gfolio-unit">px</span>
					</div>
				</div>

				<?php
				$has_custom_aspect = '' !== $aspect_ratio;
				?>
				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Aspect Ratio', 'g-folio' ); ?></label>
					<div class="gfp-slider-field gfp-aspect-field"
						data-global="<?php echo esc_attr( $global['aspect_ratio'] ); ?>">

						<!-- Global state -->
						<div class="gfp-slider-global <?php echo $has_custom_aspect ? 'hidden' : ''; ?>">
							<span class="gfp-global-badge">
								<span class="dashicons dashicons-admin-site"></span>
								<?php printf(
									/* translators: %s: ratio label e.g. 16:9 */
									esc_html__( 'Using global (%s)', 'g-folio' ),
									esc_html( gfp_format_ratio_label( $global['aspect_ratio'] ) )
								); ?>
							</span>
							<button type="button" class="gfp-customize-slider button button-small">
								<?php esc_html_e( 'Customize', 'g-folio' ); ?>
							</button>
						</div>

						<!-- Custom state -->
						<div class="gfp-slider-custom <?php echo $has_custom_aspect ? '' : 'hidden'; ?>">
							<div class="gfp-aspect-control">
								<input type="hidden" name="gfoliop_aspect_ratio"
									class="gfp-slider-actual gfp-aspect-actual"
									value="<?php echo esc_attr( $aspect_ratio ); ?>" />
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
											value="<?php echo esc_attr( $has_custom_aspect ? $aspect_ratio : $global['aspect_ratio'] ); ?>" />
										<span class="gfp-aspect-label"></span>
									</div>
								</div>
							</div>
							<button type="button" class="gfp-reset-slider button button-small">
								<span class="dashicons dashicons-undo"></span>
								<?php esc_html_e( 'Use Global', 'g-folio' ); ?>
							</button>
						</div>

					</div>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Full Width', 'g-folio' ); ?></label>
					<?php gfp_three_way(
						'gfoliop_full_width',
						$full_width,
						sprintf( __( 'Global (%s)', 'g-folio' ), $g_bool( 'full_width' ) )
					); ?>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label" for="gfoliop_border_radius"><?php esc_html_e( 'Border Radius', 'g-folio' ); ?></label>
					<div class="gfp-number-row">
						<input type="number" id="gfoliop_border_radius" name="gfoliop_border_radius"
							min="0" max="200" step="1"
							value="<?php echo esc_attr( $border_radius ); ?>"
							placeholder="<?php echo esc_attr( sprintf( __( 'Global: %s', 'g-folio' ), $global['border_radius'] ) ); ?>"
							class="gfolio-number-input" />
						<span class="gfolio-unit">px</span>
						<span class="gfolio-field-hint"><?php esc_html_e( 'Leave empty to use global', 'g-folio' ); ?></span>
					</div>
				</div>

			</div><!-- #gfp-tab-layout -->

			<!-- ── FILTERING ─────────────────────────────────── -->
			<div class="gfp-tab-panel" id="gfp-tab-filtering" role="tabpanel">

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Show Filter Bar', 'g-folio' ); ?></label>
					<?php gfp_three_way(
						'gfoliop_enable_filter',
						$enable_filter,
						sprintf( __( 'Global (%s)', 'g-folio' ), $g_bool( 'enable_filter' ) )
					); ?>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Show "All" Button', 'g-folio' ); ?></label>
					<?php gfp_three_way(
						'gfoliop_show_all_button',
						$show_all_btn,
						sprintf( __( 'Global (%s)', 'g-folio' ), $g_bool( 'show_all_button' ) )
					); ?>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label" for="gfoliop_filter_animation"><?php esc_html_e( 'Filter Animation', 'g-folio' ); ?></label>
					<select name="gfoliop_filter_animation" id="gfoliop_filter_animation" class="gfolio-select">
						<option value=""><?php printf( esc_html__( '↩ Global (%s)', 'g-folio' ), esc_html( $global['filter_animation'] ) ); ?></option>
						<option value="fade"     <?php selected( $filter_anim, 'fade' ); ?>><?php esc_html_e( 'Fade', 'g-folio' ); ?></option>
						<option value="scale"    <?php selected( $filter_anim, 'scale' ); ?>><?php esc_html_e( 'Scale', 'g-folio' ); ?></option>
						<option value="slide-up" <?php selected( $filter_anim, 'slide-up' ); ?>><?php esc_html_e( 'Slide Up', 'g-folio' ); ?></option>
						<option value="flip"     <?php selected( $filter_anim, 'flip' ); ?>><?php esc_html_e( 'Flip', 'g-folio' ); ?></option>
					</select>
				</div>

			</div><!-- #gfp-tab-filtering -->

			<!-- ── OVERLAY ───────────────────────────────────── -->
			<div class="gfp-tab-panel" id="gfp-tab-overlay" role="tabpanel">

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Show Title', 'g-folio' ); ?></label>
					<?php gfp_three_way(
						'gfoliop_show_title_overlay',
						$show_title,
						sprintf( __( 'Global (%s)', 'g-folio' ), $g_bool( 'show_title_overlay' ) )
					); ?>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Show Sub Heading', 'g-folio' ); ?></label>
					<?php gfp_three_way(
						'gfoliop_show_subheading_overlay',
						$show_sub,
						sprintf( __( 'Global (%s)', 'g-folio' ), $g_bool( 'show_subheading_overlay' ) )
					); ?>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Show Description', 'g-folio' ); ?></label>
					<?php gfp_three_way(
						'gfoliop_show_desc_overlay',
						$show_desc,
						sprintf( __( 'Global (%s)', 'g-folio' ), $g_bool( 'show_desc_overlay' ) )
					); ?>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label" for="gfoliop_overlay_style"><?php esc_html_e( 'Overlay Style', 'g-folio' ); ?></label>
					<select name="gfoliop_overlay_style" id="gfoliop_overlay_style" class="gfolio-select">
						<option value=""><?php printf( esc_html__( '↩ Global (%s)', 'g-folio' ), esc_html( $global['overlay_style'] ) ); ?></option>
						<option value="always" <?php selected( $overlay_style, 'always' ); ?>><?php esc_html_e( 'Always Visible', 'g-folio' ); ?></option>
						<option value="hover"  <?php selected( $overlay_style, 'hover' ); ?>><?php esc_html_e( 'Hover Reveal', 'g-folio' ); ?></option>
						<option value="fade"   <?php selected( $overlay_style, 'fade' ); ?>><?php esc_html_e( 'Fade on Hover', 'g-folio' ); ?></option>
					</select>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Overlay BG Color', 'g-folio' ); ?></label>
					<?php gfp_color_field( 'gfoliop_overlay_bg_color', 'gfoliop_overlay_bg_color', $overlay_bg, $global['overlay_bg_color'] ); ?>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Overlay Opacity', 'g-folio' ); ?></label>
					<?php
					$op_custom = ( '' !== $overlay_op );
					$op_disp   = $op_custom ? $overlay_op : $global['overlay_opacity'];
					?>
					<div class="gfp-slider-field <?php echo $op_custom ? 'is-custom' : 'is-global'; ?>"
						data-global="<?php echo esc_attr( $global['overlay_opacity'] ); ?>">
						<input type="hidden" name="gfoliop_overlay_opacity"
							class="gfp-slider-actual" value="<?php echo esc_attr( $overlay_op ); ?>" />
						<div class="gfp-slider-global <?php echo $op_custom ? 'hidden' : ''; ?>">
							<span class="gfp-global-badge">
								<span class="dashicons dashicons-admin-site"></span>
								<?php printf( esc_html__( 'Global: %s', 'g-folio' ), esc_html( $global['overlay_opacity'] ) ); ?>
							</span>
							<button type="button" class="gfp-customize-slider button button-small">
								<?php esc_html_e( 'Customize', 'g-folio' ); ?>
							</button>
						</div>
						<div class="gfp-slider-custom <?php echo $op_custom ? '' : 'hidden'; ?>">
							<div class="gfolio-slider-wrap">
								<input type="range" class="gfolio-slider gfp-slider-input"
									min="0" max="1" step="0.05"
									value="<?php echo esc_attr( $op_disp ); ?>" />
								<span class="gfolio-slider-value"><?php echo esc_html( $op_disp ); ?></span>
							</div>
							<button type="button" class="gfp-reset-slider button-link">
								<span class="dashicons dashicons-undo"></span>
								<?php esc_html_e( 'Use Global', 'g-folio' ); ?>
							</button>
						</div>
					</div>
				</div>

			</div><!-- #gfp-tab-overlay -->

			<!-- ── CLICK BEHAVIOR ────────────────────────────── -->
			<div class="gfp-tab-panel" id="gfp-tab-click" role="tabpanel">

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Default Click Action', 'g-folio' ); ?></label>
					<div class="gfolio-radio-group">
						<label class="gfolio-radio-option <?php echo '' === $click_beh ? 'is-active' : ''; ?>">
							<input type="radio" name="gfoliop_default_click_behavior" value="" <?php checked( $click_beh, '' ); ?> />
							<span class="gfolio-radio-icon"><span class="dashicons dashicons-undo"></span></span>
							<span class="gfolio-radio-label"><?php printf( esc_html__( 'Global (%s)', 'g-folio' ), esc_html( $global['default_click_behavior'] ) ); ?></span>
							<span class="gfolio-radio-desc"><?php esc_html_e( 'Use the global setting', 'g-folio' ); ?></span>
						</label>
						<label class="gfolio-radio-option <?php echo 'popup' === $click_beh ? 'is-active' : ''; ?>">
							<input type="radio" name="gfoliop_default_click_behavior" value="popup" <?php checked( $click_beh, 'popup' ); ?> />
							<span class="gfolio-radio-icon"><span class="dashicons dashicons-editor-expand"></span></span>
							<span class="gfolio-radio-label"><?php esc_html_e( 'Lightbox Popup', 'g-folio' ); ?></span>
							<span class="gfolio-radio-desc"><?php esc_html_e( 'Opens content in a lightbox overlay', 'g-folio' ); ?></span>
						</label>
						<label class="gfolio-radio-option <?php echo 'page' === $click_beh ? 'is-active' : ''; ?>">
							<input type="radio" name="gfoliop_default_click_behavior" value="page" <?php checked( $click_beh, 'page' ); ?> />
							<span class="gfolio-radio-icon"><span class="dashicons dashicons-admin-page"></span></span>
							<span class="gfolio-radio-label"><?php esc_html_e( 'WordPress Page', 'g-folio' ); ?></span>
							<span class="gfolio-radio-desc"><?php esc_html_e( 'Navigate to an existing WP page', 'g-folio' ); ?></span>
						</label>
						<label class="gfolio-radio-option <?php echo 'custom_url' === $click_beh ? 'is-active' : ''; ?>">
							<input type="radio" name="gfoliop_default_click_behavior" value="custom_url" <?php checked( $click_beh, 'custom_url' ); ?> />
							<span class="gfolio-radio-icon"><span class="dashicons dashicons-admin-links"></span></span>
							<span class="gfolio-radio-label"><?php esc_html_e( 'Custom URL', 'g-folio' ); ?></span>
							<span class="gfolio-radio-desc"><?php esc_html_e( 'Navigate to a custom URL', 'g-folio' ); ?></span>
						</label>
					</div>
				</div>

			</div><!-- #gfp-tab-click -->

			<!-- ── EXPAND PANEL ──────────────────────────────── -->
			<div class="gfp-tab-panel" id="gfp-tab-expand" role="tabpanel">

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Panel BG Color', 'g-folio' ); ?></label>
					<?php gfp_color_field( 'gfoliop_expand_bg_color', 'gfoliop_expand_bg_color', $expand_bg, $global['expand_bg_color'] ); ?>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Panel Text Color', 'g-folio' ); ?></label>
					<?php gfp_color_field( 'gfoliop_expand_text_color', 'gfoliop_expand_text_color', $expand_fg, $global['expand_text_color'] ); ?>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label" for="gfoliop_expand_animation"><?php esc_html_e( 'Panel Animation', 'g-folio' ); ?></label>
					<select name="gfoliop_expand_animation" id="gfoliop_expand_animation" class="gfolio-select">
						<option value=""><?php printf( esc_html__( '↩ Global (%s)', 'g-folio' ), esc_html( $global['expand_animation'] ) ); ?></option>
						<option value="slide" <?php selected( $expand_anim, 'slide' ); ?>><?php esc_html_e( 'Slide', 'g-folio' ); ?></option>
						<option value="fade"  <?php selected( $expand_anim, 'fade' ); ?>><?php esc_html_e( 'Fade', 'g-folio' ); ?></option>
					</select>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label" for="gfoliop_expand_btn_label"><?php esc_html_e( 'Button Label', 'g-folio' ); ?></label>
					<input type="text" id="gfoliop_expand_btn_label" name="gfoliop_expand_btn_label"
						class="gfolio-text-input"
						value="<?php echo esc_attr( $btn_label ); ?>"
						placeholder="<?php echo esc_attr( sprintf( __( 'Global: %s', 'g-folio' ), $global['expand_btn_label'] ) ); ?>" />
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Button Style', 'g-folio' ); ?></label>
					<div class="gfolio-btn-style-picker">
						<label class="gfolio-style-option <?php echo '' === $btn_style ? 'is-active' : ''; ?>">
							<input type="radio" name="gfoliop_expand_btn_style" value="" <?php checked( $btn_style, '' ); ?> />
							<span class="gfolio-style-preview gfolio-style-global">
								<span class="dashicons dashicons-undo"></span>
								<?php esc_html_e( 'Global', 'g-folio' ); ?>
							</span>
						</label>
						<label class="gfolio-style-option <?php echo 'filled' === $btn_style ? 'is-active' : ''; ?>">
							<input type="radio" name="gfoliop_expand_btn_style" value="filled" <?php checked( $btn_style, 'filled' ); ?> />
							<span class="gfolio-style-preview gfolio-style-filled"><?php esc_html_e( 'Filled', 'g-folio' ); ?></span>
						</label>
						<label class="gfolio-style-option <?php echo 'outlined' === $btn_style ? 'is-active' : ''; ?>">
							<input type="radio" name="gfoliop_expand_btn_style" value="outlined" <?php checked( $btn_style, 'outlined' ); ?> />
							<span class="gfolio-style-preview gfolio-style-outlined"><?php esc_html_e( 'Outlined', 'g-folio' ); ?></span>
						</label>
						<label class="gfolio-style-option <?php echo 'ghost' === $btn_style ? 'is-active' : ''; ?>">
							<input type="radio" name="gfoliop_expand_btn_style" value="ghost" <?php checked( $btn_style, 'ghost' ); ?> />
							<span class="gfolio-style-preview gfolio-style-ghost"><?php esc_html_e( 'Ghost', 'g-folio' ); ?></span>
						</label>
					</div>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Button Alignment', 'g-folio' ); ?></label>
					<div class="gfolio-segmented-control">
						<label class="gfolio-segment <?php echo '' === $btn_align ? 'is-active' : ''; ?>">
							<input type="radio" name="gfoliop_expand_btn_alignment" value="" <?php checked( $btn_align, '' ); ?> />
							<span class="dashicons dashicons-undo"></span>
							<?php printf( esc_html__( 'Global (%s)', 'g-folio' ), esc_html( $global['expand_btn_alignment'] ) ); ?>
						</label>
						<label class="gfolio-segment <?php echo 'left' === $btn_align ? 'is-active' : ''; ?>">
							<input type="radio" name="gfoliop_expand_btn_alignment" value="left" <?php checked( $btn_align, 'left' ); ?> />
							<span class="dashicons dashicons-editor-alignleft"></span>
							<?php esc_html_e( 'Left', 'g-folio' ); ?>
						</label>
						<label class="gfolio-segment <?php echo 'center' === $btn_align ? 'is-active' : ''; ?>">
							<input type="radio" name="gfoliop_expand_btn_alignment" value="center" <?php checked( $btn_align, 'center' ); ?> />
							<span class="dashicons dashicons-editor-aligncenter"></span>
							<?php esc_html_e( 'Center', 'g-folio' ); ?>
						</label>
						<label class="gfolio-segment <?php echo 'right' === $btn_align ? 'is-active' : ''; ?>">
							<input type="radio" name="gfoliop_expand_btn_alignment" value="right" <?php checked( $btn_align, 'right' ); ?> />
							<span class="dashicons dashicons-editor-alignright"></span>
							<?php esc_html_e( 'Right', 'g-folio' ); ?>
						</label>
					</div>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Button BG Color', 'g-folio' ); ?></label>
					<?php gfp_color_field( 'gfoliop_expand_btn_bg_color', 'gfoliop_expand_btn_bg_color', $btn_bg, $global['expand_btn_bg_color'] ); ?>
				</div>

				<div class="gfolio-field-row">
					<label class="gfolio-field-label"><?php esc_html_e( 'Button Text Color', 'g-folio' ); ?></label>
					<?php gfp_color_field( 'gfoliop_expand_btn_text_color', 'gfoliop_expand_btn_text_color', $btn_fg, $global['expand_btn_text_color'] ); ?>
				</div>

			</div><!-- #gfp-tab-expand -->

		</div><!-- .gfp-tabs-content -->
	</div><!-- .gfolio-pbox -->
	<?php
}

/* =========================================================
   SAVE META — PORTFOLIO ITEMS
   ========================================================= */

add_action( 'save_post_gfolio_item', 'gfolio_save_meta' );

function gfolio_save_meta( int $post_id ): void {
	if ( ! isset( $_POST['gfolio_meta_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gfolio_meta_nonce'] ) ), 'gfolio_save_meta' )
	) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// ---- Thumbnail ----
	$thumb_id = isset( $_POST['gfolio_thumbnail_id'] ) ? absint( $_POST['gfolio_thumbnail_id'] ) : 0;
	if ( $thumb_id ) {
		update_post_meta( $post_id, '_gfolio_thumbnail_id', $thumb_id );
	} else {
		delete_post_meta( $post_id, '_gfolio_thumbnail_id' );
	}

	// ---- Portfolio assignment ----
	// Store IDs as strings so serialized format produces "123" (quoted) rather than i:123;
	// This makes the LIKE '"id"' meta queries in the shortcode work correctly.
	$portfolio_ids = array();
	if ( isset( $_POST['gfolio_portfolio_ids'] ) && is_array( $_POST['gfolio_portfolio_ids'] ) ) {
		$portfolio_ids = array_values( array_filter( array_map(
			function ( $id ): string { return (string) absint( $id ); },
			$_POST['gfolio_portfolio_ids']
		) ) );
	}
	update_post_meta( $post_id, '_gfolio_portfolio_ids', $portfolio_ids );

	// ---- Categories (from our custom filtered box) ----
	// Only update terms if the custom box was submitted (nonce passed means form was submitted)
	$cat_ids = array();
	if ( isset( $_POST['gfolio_categories'] ) && is_array( $_POST['gfolio_categories'] ) ) {
		$cat_ids = array_unique( array_filter( array_map( 'absint', $_POST['gfolio_categories'] ) ) );
	}
	// wp_set_object_terms replaces all existing terms with this new list
	wp_set_object_terms( $post_id, $cat_ids, 'gfolio_category' );

	// ---- Details ----
	$subheading  = isset( $_POST['gfolio_subheading'] ) ? sanitize_text_field( wp_unslash( $_POST['gfolio_subheading'] ) ) : '';
	$description = isset( $_POST['gfolio_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['gfolio_description'] ) ) : '';
	update_post_meta( $post_id, '_gfolio_subheading',  $subheading );
	update_post_meta( $post_id, '_gfolio_description', $description );

	// ---- Click Behavior ----
	$allowed_click = array( 'popup', 'page', 'custom_url' );
	$click_type    = isset( $_POST['gfolio_click_type'] ) ? sanitize_text_field( wp_unslash( $_POST['gfolio_click_type'] ) ) : 'popup';
	if ( ! in_array( $click_type, $allowed_click, true ) ) {
		$click_type = 'popup';
	}
	update_post_meta( $post_id, '_gfolio_click_type', $click_type );

	$page_id = isset( $_POST['gfolio_link_page_id'] ) ? absint( $_POST['gfolio_link_page_id'] ) : 0;
	update_post_meta( $post_id, '_gfolio_link_page_id', $page_id );

	$custom_url = isset( $_POST['gfolio_link_custom_url'] ) ? esc_url_raw( wp_unslash( $_POST['gfolio_link_custom_url'] ) ) : '';
	update_post_meta( $post_id, '_gfolio_link_custom_url', $custom_url );

	$link_blank = isset( $_POST['gfolio_link_blank'] ) && '1' === $_POST['gfolio_link_blank'] ? '1' : '0';
	update_post_meta( $post_id, '_gfolio_link_blank', $link_blank );

	// ---- Project Button ----
	$btn_label = isset( $_POST['gfolio_btn_label'] ) ? sanitize_text_field( wp_unslash( $_POST['gfolio_btn_label'] ) ) : '';
	update_post_meta( $post_id, '_gfolio_btn_label', $btn_label );

	$btn_url = isset( $_POST['gfolio_btn_url'] ) ? esc_url_raw( wp_unslash( $_POST['gfolio_btn_url'] ) ) : '';
	update_post_meta( $post_id, '_gfolio_btn_url', $btn_url );

	$btn_blank = isset( $_POST['gfolio_btn_blank'] ) && '1' === $_POST['gfolio_btn_blank'] ? '1' : '0';
	update_post_meta( $post_id, '_gfolio_btn_blank', $btn_blank );

	$allowed_styles = array( 'filled', 'outlined', 'ghost' );
	$btn_style      = isset( $_POST['gfolio_btn_style'] ) ? sanitize_text_field( wp_unslash( $_POST['gfolio_btn_style'] ) ) : 'filled';
	if ( ! in_array( $btn_style, $allowed_styles, true ) ) {
		$btn_style = 'filled';
	}
	update_post_meta( $post_id, '_gfolio_btn_style', $btn_style );

	// ---- Expand Panel ----
	$expand_content = isset( $_POST['gfolio_expand_content'] ) ? wp_kses_post( wp_unslash( $_POST['gfolio_expand_content'] ) ) : '';
	update_post_meta( $post_id, '_gfolio_expand_content', $expand_content );

	$expand_show_title = isset( $_POST['gfolio_expand_show_title'] ) && '1' === $_POST['gfolio_expand_show_title'] ? '1' : '0';
	update_post_meta( $post_id, '_gfolio_expand_show_title', $expand_show_title );

	$expand_show_sub = isset( $_POST['gfolio_expand_show_sub'] ) && '1' === $_POST['gfolio_expand_show_sub'] ? '1' : '0';
	update_post_meta( $post_id, '_gfolio_expand_show_sub', $expand_show_sub );

	$expand_show_desc = isset( $_POST['gfolio_expand_show_desc'] ) && '1' === $_POST['gfolio_expand_show_desc'] ? '1' : '0';
	update_post_meta( $post_id, '_gfolio_expand_show_desc', $expand_show_desc );
}

/* =========================================================
   SAVE META — PORTFOLIOS
   ========================================================= */

add_action( 'save_post_gfolio_portfolio', 'gfolio_save_portfolio_meta' );

function gfolio_save_portfolio_meta( int $post_id ): void {
	if ( ! isset( $_POST['gfolio_portfolio_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gfolio_portfolio_nonce'] ) ), 'gfolio_save_portfolio_meta' )
	) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Map of form field name => meta key suffix (stored as _gfoliop_{key})
	$text_fields = array(
		'grid_mode'               => 'grid_mode',
		'columns'                 => 'columns',
		'thumbnail_padding'       => 'thumbnail_padding',
		'padding_size'            => 'padding_size',
		'border_radius'           => 'border_radius',
		'aspect_ratio'            => 'aspect_ratio',
		'full_width'              => 'full_width',
		'enable_filter'           => 'enable_filter',
		'show_all_button'         => 'show_all_button',
		'filter_animation'        => 'filter_animation',
		'overlay_style'           => 'overlay_style',
		'overlay_opacity'         => 'overlay_opacity',
		'default_click_behavior'  => 'default_click_behavior',
		'expand_animation'        => 'expand_animation',
		'expand_btn_label'        => 'expand_btn_label',
		'expand_btn_style'        => 'expand_btn_style',
		'expand_btn_alignment'    => 'expand_btn_alignment',
	);

	$color_fields = array(
		'expand_bg_color'       => 'expand_bg_color',
		'expand_text_color'     => 'expand_text_color',
		'overlay_bg_color'      => 'overlay_bg_color',
		'expand_btn_bg_color'   => 'expand_btn_bg_color',
		'expand_btn_text_color' => 'expand_btn_text_color',
	);

	foreach ( $text_fields as $field => $meta_key ) {
		$raw_key = 'gfoliop_' . $field;
		if ( array_key_exists( $raw_key, $_POST ) ) {
			$val = sanitize_text_field( wp_unslash( $_POST[ $raw_key ] ) );
			if ( '' === $val ) {
				delete_post_meta( $post_id, '_gfoliop_' . $meta_key );
			} else {
				update_post_meta( $post_id, '_gfoliop_' . $meta_key, $val );
			}
		}
	}

	foreach ( $color_fields as $field => $meta_key ) {
		$raw_key = 'gfoliop_' . $field;
		if ( array_key_exists( $raw_key, $_POST ) ) {
			$val = sanitize_hex_color( wp_unslash( $_POST[ $raw_key ] ) );
			if ( '' === $val || null === $val ) {
				delete_post_meta( $post_id, '_gfoliop_' . $meta_key );
			} else {
				update_post_meta( $post_id, '_gfoliop_' . $meta_key, $val );
			}
		}
	}
}

/* =========================================================
   AJAX: RETURN FILTERED CATEGORIES HTML
   ========================================================= */

add_action( 'wp_ajax_gfolio_get_filtered_cats', 'gfolio_ajax_get_filtered_cats' );

function gfolio_ajax_get_filtered_cats(): void {
	check_ajax_referer( 'gfolio-admin-nonce', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ) );
	}

	$portfolio_ids = isset( $_POST['portfolio_ids'] ) && is_array( $_POST['portfolio_ids'] )
		? array_map( 'absint', $_POST['portfolio_ids'] )
		: array();

	$checked_cats = isset( $_POST['checked_cats'] ) && is_array( $_POST['checked_cats'] )
		? array_map( 'absint', $_POST['checked_cats'] )
		: array();

	$portfolio_ids = array_filter( $portfolio_ids );

	ob_start();

	if ( empty( $portfolio_ids ) ) {
		echo '<div class="gfolio-meta-section"><p class="gfolio-field-hint">'
			. esc_html__( 'Assign this item to a portfolio first (see box above), then its categories will appear here.', 'g-folio' )
			. '</p></div>';
		wp_send_json_success( array( 'html' => ob_get_clean() ) );
		return;
	}

	$all_terms = get_terms( array(
		'taxonomy'   => 'gfolio_category',
		'hide_empty' => false,
	) );

	if ( is_wp_error( $all_terms ) || empty( $all_terms ) ) {
		echo '<div class="gfolio-meta-section"><p class="gfolio-field-hint">'
			. esc_html__( 'No categories found.', 'g-folio' )
			. ' <a href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=gfolio_category&post_type=gfolio_item' ) ) . '">'
			. esc_html__( 'Add categories →', 'g-folio' )
			. '</a></p></div>';
		wp_send_json_success( array( 'html' => ob_get_clean() ) );
		return;
	}

	// Filter terms matching the portfolio IDs
	$filtered_terms = array_filter( $all_terms, function( WP_Term $term ) use ( $portfolio_ids ): bool {
		$term_portfolio = (int) get_term_meta( $term->term_id, 'gfolio_cat_portfolio_id', true );
		if ( 0 === $term_portfolio ) return true;
		return in_array( $term_portfolio, $portfolio_ids, true );
	} );

	if ( empty( $filtered_terms ) ) {
		echo '<div class="gfolio-meta-section"><p class="gfolio-field-hint">'
			. esc_html__( 'No categories are assigned to the selected portfolio(s) yet.', 'g-folio' )
			. ' <a href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=gfolio_category&post_type=gfolio_item' ) ) . '">'
			. esc_html__( 'Manage categories →', 'g-folio' )
			. '</a></p></div>';
		wp_send_json_success( array( 'html' => ob_get_clean() ) );
		return;
	}

	// Build portfolios map
	$portfolios_map = array();
	foreach ( $portfolio_ids as $pid ) {
		$p = get_post( $pid );
		if ( $p ) $portfolios_map[ $pid ] = $p->post_title;
	}

	// Group
	$groups   = array();
	$unscoped = array();
	foreach ( $filtered_terms as $term ) {
		$tp = (int) get_term_meta( $term->term_id, 'gfolio_cat_portfolio_id', true );
		if ( 0 === $tp ) {
			$unscoped[] = $term;
		} else {
			$groups[ $tp ][] = $term;
		}
	}

	$show_labels = count( $portfolio_ids ) > 1 || ! empty( $unscoped );

	echo '<div class="gfolio-meta-section" id="gfolio-cat-box">';

	foreach ( $portfolio_ids as $pid ) {
		if ( empty( $groups[ $pid ] ) ) continue;
		if ( $show_labels && isset( $portfolios_map[ $pid ] ) ) {
			echo '<p class="gfolio-cat-group-label">' . esc_html( $portfolios_map[ $pid ] ) . '</p>';
		}
		echo '<div class="gfolio-checkbox-group">';
		foreach ( $groups[ $pid ] as $term ) {
			$chk = in_array( $term->term_id, $checked_cats, true ) ? 'checked' : '';
			echo '<label class="gfolio-checkbox-plain">'
				. '<input type="checkbox" name="gfolio_categories[]" value="' . esc_attr( $term->term_id ) . '" ' . $chk . ' />'
				. ' ' . esc_html( $term->name )
				. '</label>';
		}
		echo '</div>';
	}

	if ( ! empty( $unscoped ) ) {
		if ( $show_labels ) {
			echo '<p class="gfolio-cat-group-label">' . esc_html__( 'General', 'g-folio' ) . '</p>';
		}
		echo '<div class="gfolio-checkbox-group">';
		foreach ( $unscoped as $term ) {
			$chk = in_array( $term->term_id, $checked_cats, true ) ? 'checked' : '';
			echo '<label class="gfolio-checkbox-plain">'
				. '<input type="checkbox" name="gfolio_categories[]" value="' . esc_attr( $term->term_id ) . '" ' . $chk . ' />'
				. ' ' . esc_html( $term->name )
				. '</label>';
		}
		echo '</div>';
	}

	echo '<p class="gfolio-field-hint" style="margin-top:8px;">'
		. '<a href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=gfolio_category&post_type=gfolio_item' ) ) . '">'
		. esc_html__( '+ Add / manage categories', 'g-folio' )
		. '</a></p>';
	echo '</div>';

	wp_send_json_success( array( 'html' => ob_get_clean() ) );
}

/* =========================================================
   ADMIN JS: COPY SHORTCODE BUTTON
   ========================================================= */

add_action( 'admin_footer-post.php', 'gfolio_shortcode_copy_script' );
add_action( 'admin_footer-post-new.php', 'gfolio_shortcode_copy_script' );

function gfolio_shortcode_copy_script(): void {
	$screen = get_current_screen();
	if ( ! $screen || 'gfolio_portfolio' !== $screen->post_type ) {
		return;
	}
	?>
	<script>
	(function(){
		document.addEventListener('click', function(e){
			var btn = e.target.closest('.gfolio-copy-shortcode-btn');
			if (!btn) return;
			var sc = btn.dataset.shortcode;
			if (!sc) return;
			if (navigator.clipboard) {
				navigator.clipboard.writeText(sc).then(function(){
					btn.textContent = '✓ Copied!';
					setTimeout(function(){ btn.innerHTML = '<span class="dashicons dashicons-clipboard"></span> Copy'; }, 2000);
				});
			} else {
				var ta = document.createElement('textarea');
				ta.value = sc;
				document.body.appendChild(ta);
				ta.select();
				document.execCommand('copy');
				document.body.removeChild(ta);
				btn.textContent = '✓ Copied!';
				setTimeout(function(){ btn.innerHTML = '<span class="dashicons dashicons-clipboard"></span> Copy'; }, 2000);
			}
		});
	})();
	</script>
	<?php
}
