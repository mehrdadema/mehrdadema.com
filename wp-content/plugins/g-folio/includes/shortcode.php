<?php
/**
 * [gfolio] Shortcode — Grid, Filter, Lightbox & Expand Panel
 *
 * Usage: [gfolio id="123"]
 * The id attribute is the Post ID of a gfolio_portfolio post.
 *
 * @package G_Folio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'gfolio', 'gfolio_shortcode' );

/* =========================================================
   MAIN SHORTCODE HANDLER
   ========================================================= */

function gfolio_shortcode( array $atts ): string {

	$atts = shortcode_atts(
		array(
			'id' => 0,
		),
		$atts,
		'gfolio'
	);

	$portfolio_id = absint( $atts['id'] );

	// If no valid portfolio ID is given, output a helpful notice
	if ( ! $portfolio_id ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return '<p class="gfolio-empty">'
				. esc_html__( 'G Folio: Please specify a portfolio ID — e.g. [gfolio id="123"].', 'g-folio' )
				. '</p>';
		}
		return '';
	}

	// Verify the portfolio post exists and is published
	$portfolio = get_post( $portfolio_id );
	if ( ! $portfolio || 'gfolio_portfolio' !== $portfolio->post_type || 'publish' !== $portfolio->post_status ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return '<p class="gfolio-empty">'
				. sprintf(
					esc_html__( 'G Folio: No published portfolio found with ID %d.', 'g-folio' ),
					$portfolio_id
				)
				. '</p>';
		}
		return '';
	}

	// Load per-portfolio settings (falls back to global defaults)
	$s = gfolio_get_portfolio_settings( $portfolio_id );

	// Settings-driven values
	$columns     = max( 1, min( 6, absint( $s['columns'] ) ) );
	$is_masonry  = 'masonry' === $s['grid_mode'];
	$show_filter = in_array( (string) $s['enable_filter'], array( '1', 'true', 'yes' ), true );
	$has_padding = in_array( (string) $s['thumbnail_padding'], array( '1', 'true', 'yes' ), true );
	$has_outer_gap = $has_padding && in_array( (string) $s['outer_gap'], array( '1', 'true', 'yes' ), true );

	// Query only items assigned to this portfolio
	$query_args = array(
		'post_type'      => 'gfolio_item',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order date',
		'order'          => 'ASC',
		'meta_query'     => array(
			'relation' => 'OR',
			// String-serialized format: a:N:{...s:N:"123";...}  (current save format)
			array(
				'key'     => '_gfolio_portfolio_ids',
				'value'   => '"' . $portfolio_id . '"',
				'compare' => 'LIKE',
			),
			// Integer-serialized format: a:N:{...i:123;...}  (legacy save format)
			array(
				'key'     => '_gfolio_portfolio_ids',
				'value'   => 'i:' . $portfolio_id . ';',
				'compare' => 'LIKE',
			),
		),
	);

	$items = new WP_Query( $query_args );

	if ( ! $items->have_posts() ) {
		$msg = current_user_can( 'edit_posts' )
			? sprintf(
				/* translators: %d: portfolio ID */
				__( 'No portfolio items are assigned to this portfolio yet. <a href="%s">Add items →</a>', 'g-folio' ),
				esc_url( admin_url( 'post-new.php?post_type=gfolio_item' ) )
			)
			: __( 'No portfolio items found.', 'g-folio' );
		return '<p class="gfolio-empty">' . wp_kses_post( $msg ) . '</p>';
	}

	// Get categories that have at least one item in this portfolio
	$all_cats = gfolio_get_portfolio_categories( $portfolio_id );

	// CSS vars
	$gap_px        = $has_padding ? absint( $s['padding_size'] ) : 0;
	$radius_px     = absint( $s['border_radius'] );
	$aspect_ratio  = ! empty( $s['aspect_ratio'] ) ? $s['aspect_ratio'] : '1.7778';
	$full_width    = '1' === (string) $s['full_width'];
	$overlay_style = esc_attr( $s['overlay_style'] );
	$expand_bg     = esc_attr( $s['expand_bg_color'] );
	$expand_fg     = esc_attr( $s['expand_text_color'] );
	$expand_anim   = esc_attr( $s['expand_animation'] );
	$btn_align     = esc_attr( $s['expand_btn_alignment'] );
	$overlay_bg    = esc_attr( $s['overlay_bg_color'] );
	$overlay_op    = esc_attr( $s['overlay_opacity'] );

	// Unique instance ID (supports multiple shortcodes per page)
	static $instance = 0;
	$instance++;
	$uid = 'gfolio-' . $instance;

	ob_start();
	?>
	<div
		id="<?php echo esc_attr( $uid ); ?>"
		class="gfolio-portfolio-wrap <?php echo $full_width ? 'gfolio-full-width' : ''; ?> <?php echo $has_outer_gap ? 'gfolio-outer-gap' : ''; ?>"
		data-masonry="<?php echo $is_masonry ? 'true' : 'false'; ?>"
		data-columns="<?php echo esc_attr( $columns ); ?>"
		data-gap="<?php echo esc_attr( $gap_px ); ?>"
		data-overlay-style="<?php echo $overlay_style; ?>"
		data-expand-anim="<?php echo $expand_anim; ?>"
		style="--gfolio-columns:<?php echo esc_attr( $columns ); ?>;--gfolio-gap:<?php echo esc_attr( $gap_px ); ?>px;--gfolio-radius:<?php echo esc_attr( $radius_px ); ?>px;--gfolio-aspect-ratio:<?php echo esc_attr( $aspect_ratio ); ?>;--gfolio-overlay-bg:<?php echo $overlay_bg; ?>;--gfolio-overlay-op:<?php echo $overlay_op; ?>;--gfolio-expand-bg:<?php echo $expand_bg; ?>;--gfolio-expand-fg:<?php echo $expand_fg; ?>;"
	>

		<?php
		// ---- FILTER BAR ----
		if ( $show_filter && ! empty( $all_cats ) ) :
			$filter_anim = esc_attr( $s['filter_animation'] );
			?>
			<div class="gfolio-filter-bar" data-filter-anim="<?php echo $filter_anim; ?>">
				<?php if ( '1' === (string) $s['show_all_button'] ) : ?>
					<button type="button" class="gfolio-filter-btn is-active" data-filter="*">
						<?php esc_html_e( 'All', 'g-folio' ); ?>
					</button>
				<?php endif; ?>
				<?php foreach ( $all_cats as $cat ) : ?>
					<?php
					if ( is_wp_error( $cat ) ) continue;
					$cat_color = get_term_meta( $cat->term_id, 'gfolio_cat_color', true );
					$cat_icon  = get_term_meta( $cat->term_id, 'gfolio_cat_icon', true );
					$cat_style = $cat_color ? 'style="--cat-color:' . esc_attr( $cat_color ) . ';"' : '';
					?>
					<button type="button"
						class="gfolio-filter-btn"
						data-filter=".gfolio-cat-<?php echo esc_attr( $cat->slug ); ?>"
						<?php echo $cat_style; // Safe: pre-built with esc_attr ?>
					>
						<?php if ( $cat_icon ) : ?>
							<span class="dashicons <?php echo esc_attr( $cat_icon ); ?>"></span>
						<?php endif; ?>
						<?php echo esc_html( $cat->name ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<!-- ---- ISOTOPE GRID ---- -->
		<div class="gfolio-grid <?php echo $is_masonry ? 'gfolio-masonry' : 'gfolio-css-grid'; ?>" id="<?php echo esc_attr( $uid ); ?>-grid">

			<?php
			while ( $items->have_posts() ) :
				$items->the_post();
				$post_id = get_the_ID();

				// Fetch all meta
				$title       = get_the_title();
				$subheading  = get_post_meta( $post_id, '_gfolio_subheading', true );
				$description = get_post_meta( $post_id, '_gfolio_description', true );
				$thumb_id    = get_post_meta( $post_id, '_gfolio_thumbnail_id', true );
				$click_type  = get_post_meta( $post_id, '_gfolio_click_type', true ) ?: $s['default_click_behavior'];
				$page_id     = get_post_meta( $post_id, '_gfolio_link_page_id', true );
				$custom_url  = get_post_meta( $post_id, '_gfolio_link_custom_url', true );
				$link_blank  = get_post_meta( $post_id, '_gfolio_link_blank', true );

				// Expand panel meta
				$expand_content    = get_post_meta( $post_id, '_gfolio_expand_content', true );
				$expand_enabled    = ! empty( trim( wp_strip_all_tags( $expand_content ) ) );
				$expand_show_title = get_post_meta( $post_id, '_gfolio_expand_show_title', true );
				$expand_show_sub   = get_post_meta( $post_id, '_gfolio_expand_show_sub', true );
				$expand_show_desc  = get_post_meta( $post_id, '_gfolio_expand_show_desc', true );

				// Button meta
				$btn_url     = get_post_meta( $post_id, '_gfolio_btn_url', true );
				$btn_enabled = ! empty( $btn_url );
				$btn_label   = get_post_meta( $post_id, '_gfolio_btn_label', true ) ?: $s['expand_btn_label'];
				$btn_blank   = get_post_meta( $post_id, '_gfolio_btn_blank', true );
				$btn_style   = get_post_meta( $post_id, '_gfolio_btn_style', true ) ?: $s['expand_btn_style'];

				// Thumbnail
				$thumb_url = $thumb_id ? wp_get_attachment_image_url( (int) $thumb_id, 'large' ) : '';
				if ( ! $thumb_url ) {
					$thumb_url = has_post_thumbnail() ? get_the_post_thumbnail_url( $post_id, 'large' ) : '';
				}

				// Category classes for Isotope
				$item_cats   = get_the_terms( $post_id, 'gfolio_category' );
				$cat_classes = '';
				if ( $item_cats && ! is_wp_error( $item_cats ) ) {
					foreach ( $item_cats as $icat ) {
						$cat_classes .= ' gfolio-cat-' . esc_attr( $icat->slug );
					}
				}

				// Link href & target
				$link_href   = '#';
				$link_target = $link_blank ? '_blank' : '_self';
				if ( 'page' === $click_type && $page_id ) {
					$link_href = get_permalink( $page_id );
				} elseif ( 'custom_url' === $click_type && $custom_url ) {
					$link_href = $custom_url;
				}

				// Grid item mode
				$use_expand = $expand_enabled;
				$use_popup  = 'popup' === $click_type && ! $use_expand;
				$use_link   = in_array( $click_type, array( 'page', 'custom_url' ), true ) && ! $use_expand;
				?>

				<div class="gfolio-item<?php echo esc_attr( $cat_classes ); ?>"
					data-post-id="<?php echo esc_attr( $post_id ); ?>"
					data-mode="<?php echo $use_expand ? 'expand' : ( $use_popup ? 'popup' : 'link' ); ?>"
					data-href="<?php echo $use_link ? esc_url( $link_href ) : ''; ?>"
					data-target="<?php echo $link_blank ? '_blank' : '_self'; ?>"
				>
					<div class="gfolio-item-inner">
						<!-- Thumbnail -->
						<div class="gfolio-thumb">
							<?php if ( $thumb_url ) : ?>
								<img src="<?php echo esc_url( $thumb_url ); ?>"
									alt="<?php echo esc_attr( $title ); ?>"
									loading="lazy" />
							<?php else : ?>
								<div class="gfolio-no-thumb">
									<span class="dashicons dashicons-format-image"></span>
								</div>
							<?php endif; ?>

							<!-- Overlay -->
							<div class="gfolio-overlay gfolio-overlay-<?php echo $overlay_style; ?>">
								<div class="gfolio-overlay-content">
									<?php if ( '1' === $s['show_title_overlay'] && $title ) : ?>
										<h3 class="gfolio-overlay-title"><?php echo esc_html( $title ); ?></h3>
									<?php endif; ?>
									<?php if ( '1' === $s['show_subheading_overlay'] && $subheading ) : ?>
										<p class="gfolio-overlay-sub"><?php echo esc_html( $subheading ); ?></p>
									<?php endif; ?>
									<?php if ( '1' === $s['show_desc_overlay'] && $description ) : ?>
										<p class="gfolio-overlay-desc"><?php echo esc_html( $description ); ?></p>
									<?php endif; ?>
									<?php if ( $use_expand ) : ?>
										<span class="gfolio-overlay-action"><span class="dashicons dashicons-arrow-down-alt"></span></span>
									<?php elseif ( $use_popup ) : ?>
										<span class="gfolio-overlay-action"><span class="dashicons dashicons-editor-expand"></span></span>
									<?php elseif ( $use_link ) : ?>
										<span class="gfolio-overlay-action"><span class="dashicons dashicons-arrow-right-alt"></span></span>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>

					<!-- Popup / Expand data (hidden) -->
					<?php if ( $use_popup || $use_expand ) : ?>
						<div class="gfolio-content-data" style="display:none;"
							data-title="<?php echo $expand_show_title !== '0' ? esc_attr( $title ) : ''; ?>"
							data-subheading="<?php echo esc_attr( $subheading ); ?>"
							data-description="<?php echo esc_attr( $description ); ?>"
							data-show-title="<?php echo esc_attr( $expand_show_title !== '' ? $expand_show_title : '1' ); ?>"
							data-show-sub="<?php echo esc_attr( $expand_show_sub !== '' ? $expand_show_sub : '1' ); ?>"
							data-show-desc="<?php echo esc_attr( $expand_show_desc !== '' ? $expand_show_desc : '1' ); ?>"
							data-btn-enabled="<?php echo esc_attr( $btn_enabled ); ?>"
							data-btn-label="<?php echo esc_attr( $btn_label ); ?>"
							data-btn-url="<?php echo esc_url( $btn_url ); ?>"
							data-btn-blank="<?php echo esc_attr( $btn_blank ); ?>"
							data-btn-style="<?php echo esc_attr( $btn_style ); ?>"
							data-btn-align="<?php echo esc_attr( $btn_align ); ?>"
						>
							<?php if ( $expand_content ) : ?>
								<div class="gfolio-rich-content"><?php echo do_shortcode( wp_kses_post( $expand_content ) ); ?></div>
							<?php endif; ?>
						</div>
					<?php endif; ?>

				</div><!-- .gfolio-item -->

			<?php endwhile; wp_reset_postdata(); ?>

		</div><!-- .gfolio-grid -->

		<!-- ---- EXPAND PANEL PLACEHOLDER (inserted by JS) ---- -->
		<div class="gfolio-expand-panel" id="<?php echo esc_attr( $uid ); ?>-expand"
			style="display:none;--gfolio-expand-bg:<?php echo $expand_bg; ?>;--gfolio-expand-fg:<?php echo $expand_fg; ?>;"
			aria-hidden="true">
			<button type="button" class="gfolio-expand-close" aria-label="<?php esc_attr_e( 'Close panel', 'g-folio' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
			<div class="gfolio-expand-inner"></div>
		</div>

		<!-- ---- LIGHTBOX ---- -->
		<div class="gfolio-lightbox" id="<?php echo esc_attr( $uid ); ?>-lightbox" role="dialog" aria-modal="true" aria-hidden="true" style="display:none;">
			<div class="gfolio-lightbox-backdrop"></div>
			<div class="gfolio-lightbox-container">
				<button type="button" class="gfolio-lightbox-close" aria-label="<?php esc_attr_e( 'Close lightbox', 'g-folio' ); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
				<div class="gfolio-lightbox-body"></div>
			</div>
		</div>

	</div><!-- .gfolio-portfolio-wrap -->
	<?php
	return ob_get_clean();
}

/* =========================================================
   HELPER: GET CATEGORIES USED IN A SPECIFIC PORTFOLIO
   ========================================================= */

function gfolio_get_portfolio_categories( int $portfolio_id ): array {
	// Get all item IDs assigned to this portfolio
	$item_query = new WP_Query( array(
		'post_type'      => 'gfolio_item',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			'relation' => 'OR',
			array(
				'key'     => '_gfolio_portfolio_ids',
				'value'   => '"' . $portfolio_id . '"',
				'compare' => 'LIKE',
			),
			array(
				'key'     => '_gfolio_portfolio_ids',
				'value'   => 'i:' . $portfolio_id . ';',
				'compare' => 'LIKE',
			),
		),
	) );

	if ( empty( $item_query->posts ) ) {
		return array();
	}

	$cats = wp_get_object_terms( $item_query->posts, 'gfolio_category', array(
		'orderby' => 'name',
		'order'   => 'ASC',
	) );

	return is_wp_error( $cats ) ? array() : $cats;
}

/* =========================================================
   HELPER: BUILD BUTTON HTML
   ========================================================= */

function gfolio_build_button( string $label, string $url, string $style, string $align, bool $blank ): string {
	if ( ! $url ) {
		return '';
	}

	$target = $blank ? '_blank' : '_self';
	$rel    = $blank ? 'noopener noreferrer' : '';

	$classes = 'gfolio-project-btn gfolio-btn-' . esc_attr( $style );

	$content = esc_html( $label );
	if ( 'ghost' === $style ) {
		$content .= ' <span class="gfolio-ghost-arrow">&#8594;</span>';
	}

	return sprintf(
		'<div class="gfolio-btn-wrap gfolio-btn-align-%s"><a href="%s" class="%s" target="%s"%s>%s</a></div>',
		esc_attr( $align ),
		esc_url( $url ),
		esc_attr( $classes ),
		esc_attr( $target ),
		$rel ? ' rel="' . esc_attr( $rel ) . '"' : '',
		$content
	);
}
