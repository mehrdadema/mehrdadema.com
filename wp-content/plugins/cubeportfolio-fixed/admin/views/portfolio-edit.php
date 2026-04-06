<?php
/**
 * Cube Portfolio - Portfolio Edit View
 *
 * Admin view for editing/creating portfolios in the Cube Portfolio plugin.
 *
 * @package CubePortfolio
 * @subpackage Admin\Views
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parse item HTML and extract item data
 *
 * @param string $html Raw item HTML from portfolio
 * @return array Item data with keys: item_type, image_url, image_id, title, desc, categories, link, link_type, size, shortcode, raw_html
 */
function cbp_parse_item_html( $html ) {
	$item = array(
		'item_type'   => 'image',
		'image_url'   => '',
		'image_id'    => '',
		'title'       => '',
		'desc'        => '',
		'categories'  => '',
		'link'        => '',
		'link_type'   => 'lightbox',
		'size'        => '1x1',
		'shortcode'   => '',
		// 'items' carries the raw DB HTML so it round-trips correctly through save.
		'items'       => $html,
		// DB metadata – populated when loading existing items.
		'db_id'       => 0,
		'page'        => 0,
		'isLoadMore'  => '0',
		'isSinglePage' => '',
	);

	// Check if it's a shortcode/embed item (no img tag, starts with [ or <iframe/<embed)
	if ( ! preg_match( '/<img/', $html ) && preg_match( '/^\s*[\[<]/', trim( $html ) ) ) {
		$item['item_type'] = 'shortcode';
		$item['shortcode'] = trim( $html );
		// Extract categories from data attribute if present
		if ( preg_match( '/data-categories="([^"]*)"/', $html, $matches ) ) {
			$item['categories'] = $matches[1];
		}
		// Extract size from class
		if ( preg_match( '/cbp-item-\w+-(\d+x\d+)/', $html, $matches ) ) {
			$item['size'] = $matches[1];
		}
		return $item;
	}

	// Parse image item
	// Extract image URL
	if ( preg_match( '/<img[^>]+src="([^"]*)"/', $html, $matches ) ) {
		$item['image_url'] = $matches[1];
	}

	// Extract image ID from data-id attribute
	if ( preg_match( '/data-id="([^"]*)"/', $html, $matches ) ) {
		$item['image_id'] = $matches[1];
	}

	// Extract title from data-title attribute
	if ( preg_match( '/data-title="([^"]*)"/', $html, $matches ) ) {
		$item['title'] = html_entity_decode( $matches[1] );
	}

	// Extract description from data-description attribute
	if ( preg_match( '/data-description="([^"]*)"/', $html, $matches ) ) {
		$item['desc'] = html_entity_decode( $matches[1] );
	}

	// Extract categories from data-categories attribute
	if ( preg_match( '/data-categories="([^"]*)"/', $html, $matches ) ) {
		$item['categories'] = $matches[1];
	}

	// Extract link URL from href attribute
	if ( preg_match( '/href="([^"]*)"/', $html, $matches ) ) {
		$item['link'] = $matches[1];
	}

	// Extract link type from data-link-type attribute
	if ( preg_match( '/data-link-type="([^"]*)"/', $html, $matches ) ) {
		$item['link_type'] = $matches[1];
	}

	// Extract size from class (e.g., cbp-item-width-2x1)
	if ( preg_match( '/cbp-item-width-(\d+x\d+)/', $html, $matches ) ) {
		$item['size'] = $matches[1];
	}

	return $item;
}

/**
 * Parse ratio from template class
 *
 * @param string $template HTML template string
 * @return string Current ratio (masonry, 1-1, 3-2, 4-3, 16-9, 2-1)
 */
function cbp_get_current_ratio( $template ) {
	$ratio = 'masonry'; // default
	if ( preg_match( '/cbp-ratio-([a-z0-9\-]+)/', $template, $matches ) ) {
		$ratio = $matches[1];
	}
	return $ratio;
}

/**
 * Parse layout mode from type or options
 *
 * @param array $data Portfolio data
 * @return string Current layout mode
 */
function cbp_get_current_layout_mode( $data ) {
	// First check options.layoutMode
	if ( ! empty( $data['options'] ) ) {
		$options = is_string( $data['options'] ) ? json_decode( $data['options'], true ) : $data['options'];
		if ( is_array( $options ) && ! empty( $options['layoutMode'] ) ) {
			return $options['layoutMode'];
		}
	}
	// Fall back to type field
	return ! empty( $data['type'] ) ? $data['type'] : 'mosaic';
}

// Determine if this is a new portfolio or edit
$is_new = ( $portfolio === null );

// Get parsed portfolio data
$parsed_items        = array();
$current_ratio       = 'masonry';
$current_layout_mode = 'mosaic';

if ( ! $is_new ) {
	// Parse items.
	// Each element of $portfolio['items'] is an associative array from the DB row;
	// the HTML content we need to parse is in the 'items' key of that row.
	if ( ! empty( $portfolio['items'] ) ) {
		foreach ( $portfolio['items'] as $item_row ) {
			$html_content   = is_array( $item_row ) ? ( $item_row['items'] ?? '' ) : (string) $item_row;
			$parsed         = cbp_parse_item_html( $html_content );
			// Preserve DB metadata so the row can be updated (not re-inserted) on save.
			$parsed['db_id']        = (int) ( is_array( $item_row ) ? ( $item_row['id']          ?? 0   ) : 0 );
			$parsed['page']         = (int) ( is_array( $item_row ) ? ( $item_row['page']         ?? 0   ) : 0 );
			$parsed['isLoadMore']   =        is_array( $item_row ) ? ( $item_row['isLoadMore']    ?? '0' ) : '0';
			$parsed['isSinglePage'] =        is_array( $item_row ) ? ( $item_row['isSinglePage']  ?? ''  ) : '';
			$parsed_items[] = $parsed;
		}
	}

	// Parse current ratio and layout mode
	$current_ratio       = cbp_get_current_ratio( $portfolio['template'] ?? '' );
	$current_layout_mode = cbp_get_current_layout_mode( $portfolio );
}

// Prepare template for display
$current_template = $portfolio['template'] ?? '';

// Prepare options for display
$current_options = $portfolio['options'] ?? '{}';
if ( ! is_string( $current_options ) ) {
	$current_options = wp_json_encode( $current_options );
}

// Prepare other fields
$current_filtershtml  = $portfolio['filtershtml'] ?? '';
$current_loadMorehtml = $portfolio['loadMorehtml'] ?? '';
$current_googlefonts  = $portfolio['googlefonts'] ?? '';
$current_jsondata     = $portfolio['jsondata'] ?? '';
$current_customcss    = $portfolio['customcss'] ?? '';
$current_name         = $portfolio['name'] ?? '';
$current_id           = $portfolio['id'] ?? 0;
?>

<style>
	.cbp-admin-header {
		margin-bottom: 20px;
		padding-bottom: 15px;
		border-bottom: 1px solid #ccc;
	}

	.cbp-admin-header a.back-link {
		display: inline-block;
		margin-bottom: 10px;
		text-decoration: none;
		color: #0073aa;
	}

	.cbp-admin-header a.back-link:hover {
		text-decoration: underline;
	}

	.cbp-admin-header h1 {
		margin: 10px 0 0 0;
	}

	.cbp-notice {
		display: none;
		padding: 12px;
		margin-bottom: 20px;
		background: #d4edda;
		border: 1px solid #c3e6cb;
		border-radius: 4px;
		color: #155724;
	}

	.cbp-notice.visible {
		display: block;
	}

	.cbp-container {
		display: grid;
		grid-template-columns: 1fr 350px;
		gap: 20px;
		margin-top: 20px;
	}

	.cbp-main {
		grid-column: 1;
	}

	.cbp-sidebar {
		grid-column: 2;
	}

	.postbox {
		background: #fff;
		border: 1px solid #ccc;
		border-radius: 4px;
		margin-bottom: 20px;
		box-shadow: 0 1px 1px rgba( 0, 0, 0, 0.04 );
	}

	.postbox h2 {
		margin: 0;
		padding: 12px 15px;
		background: #f5f5f5;
		border-bottom: 1px solid #eee;
		font-size: 14px;
		font-weight: 600;
		cursor: pointer;
	}

	.postbox-content {
		padding: 15px;
	}

	.postbox h2.closed + .postbox-content {
		display: none;
	}

	/* Layout Controls */
	.cbp-layout-row {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 15px;
	}

	.cbp-form-group {
		display: flex;
		flex-direction: column;
	}

	.cbp-form-group label {
		display: block;
		margin-bottom: 6px;
		font-weight: 500;
		font-size: 13px;
	}

	.cbp-form-group select,
	.cbp-form-group input,
	.cbp-form-group textarea {
		padding: 8px 10px;
		border: 1px solid #ddd;
		border-radius: 4px;
		font-size: 13px;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
	}

	.cbp-form-group textarea {
		resize: vertical;
		font-family: 'Courier New', monospace;
	}

	/* Portfolio Items Grid */
	.cbp-items-grid {
		display: grid;
		grid-template-columns: repeat( auto-fill, minmax( 150px, 1fr ) );
		gap: 10px;
		margin-bottom: 15px;
	}

	.cbp-item-card {
		position: relative;
		aspect-ratio: 1;
		background: #f5f5f5;
		border: 2px solid #ddd;
		border-radius: 4px;
		cursor: pointer;
		overflow: hidden;
		transition: all 0.2s ease;
	}

	.cbp-item-card:hover {
		border-color: #0073aa;
		box-shadow: 0 2px 8px rgba( 0, 115, 170, 0.2 );
	}

	.cbp-item-card img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.cbp-item-card .cbp-item-shortcode-label {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 100%;
		height: 100%;
		background: #e8e8e8;
		font-size: 11px;
		font-weight: 600;
		color: #666;
		text-align: center;
		padding: 8px;
		box-sizing: border-box;
	}

	.cbp-item-card .cbp-item-remove {
		position: absolute;
		top: 5px;
		right: 5px;
		background: rgba( 220, 53, 69, 0.9 );
		color: white;
		border: none;
		border-radius: 3px;
		width: 24px;
		height: 24px;
		padding: 0;
		cursor: pointer;
		display: none;
		align-items: center;
		justify-content: center;
		font-size: 12px;
		line-height: 1;
		transition: background 0.2s ease;
	}

	.cbp-item-card:hover .cbp-item-remove {
		display: flex;
	}

	.cbp-item-card .cbp-item-remove:hover {
		background: rgba( 220, 53, 69, 1 );
	}

	.cbp-add-item-btn {
		display: inline-block;
		padding: 8px 15px;
		background: #0073aa;
		color: white;
		border: none;
		border-radius: 4px;
		cursor: pointer;
		font-size: 13px;
		transition: background 0.2s ease;
	}

	.cbp-add-item-btn:hover {
		background: #005a87;
	}

	/* Modal Styles */
	.cbp-modal-overlay {
		display: none;
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: rgba( 0, 0, 0, 0.5 );
		z-index: 100000;
		align-items: center;
		justify-content: center;
	}

	.cbp-modal-overlay.open {
		display: flex;
	}

	.cbp-modal {
		background: white;
		border-radius: 8px;
		box-shadow: 0 5px 40px rgba( 0, 0, 0, 0.3 );
		width: 90%;
		max-width: 600px;
		max-height: 90vh;
		overflow-y: auto;
	}

	.cbp-modal-header {
		padding: 20px;
		border-bottom: 1px solid #eee;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.cbp-modal-header h2 {
		margin: 0;
		font-size: 18px;
	}

	.cbp-modal-close {
		background: none;
		border: none;
		font-size: 24px;
		cursor: pointer;
		padding: 0;
		width: 32px;
		height: 32px;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.cbp-modal-body {
		padding: 20px;
	}

	.cbp-modal-footer {
		padding: 15px 20px;
		border-top: 1px solid #eee;
		display: flex;
		justify-content: flex-end;
		gap: 10px;
	}

	.cbp-modal-footer button {
		padding: 8px 15px;
		border: 1px solid #ddd;
		border-radius: 4px;
		cursor: pointer;
		font-size: 13px;
		background: white;
		transition: all 0.2s ease;
	}

	.cbp-modal-footer button.primary {
		background: #0073aa;
		color: white;
		border-color: #0073aa;
	}

	.cbp-modal-footer button.primary:hover {
		background: #005a87;
		border-color: #005a87;
	}

	.cbp-modal-footer button:hover {
		background: #f5f5f5;
	}

	/* Item Type Toggle */
	.cbp-item-type-toggle {
		display: flex;
		gap: 10px;
		margin-bottom: 20px;
	}

	.cbp-item-type-toggle button {
		flex: 1;
		padding: 10px;
		border: 2px solid #ddd;
		background: white;
		border-radius: 4px;
		cursor: pointer;
		font-size: 13px;
		font-weight: 500;
		transition: all 0.2s ease;
	}

	.cbp-item-type-toggle button.active {
		border-color: #0073aa;
		background: #0073aa;
		color: white;
	}

	.cbp-item-type-toggle button:hover:not( .active ) {
		border-color: #0073aa;
	}

	.cbp-item-section {
		display: none;
	}

	.cbp-item-section.active {
		display: block;
	}

	/* Sidebar */
	.cbp-sidebar-section {
		background: white;
		border: 1px solid #ccc;
		border-radius: 4px;
		padding: 15px;
		margin-bottom: 20px;
	}

	.cbp-sidebar-section h3 {
		margin: 0 0 15px 0;
		font-size: 14px;
	}

	.cbp-shortcode-display {
		background: #f5f5f5;
		border: 1px solid #ddd;
		border-radius: 4px;
		padding: 12px;
		font-family: 'Courier New', monospace;
		font-size: 12px;
		word-break: break-all;
		margin-bottom: 15px;
	}

	.cbp-save-btn {
		width: 100%;
		padding: 10px;
		background: #28a745;
		color: white;
		border: none;
		border-radius: 4px;
		cursor: pointer;
		font-size: 14px;
		font-weight: 600;
		transition: background 0.2s ease;
	}

	.cbp-save-btn:hover {
		background: #218838;
	}

	.cbp-save-btn:disabled {
		background: #ccc;
		cursor: not-allowed;
	}

	/* Advanced Settings */
	details {
		margin-bottom: 20px;
		background: white;
		border: 1px solid #ccc;
		border-radius: 4px;
	}

	details summary {
		padding: 15px;
		cursor: pointer;
		font-weight: 600;
		user-select: none;
		background: #f5f5f5;
		border-radius: 4px 4px 0 0;
	}

	details summary:hover {
		background: #ebebeb;
	}

	details[open] summary {
		border-radius: 4px 4px 0 0;
		border-bottom: 1px solid #ddd;
	}

	details-content {
		padding: 15px;
	}

	/* Responsive */
	@media ( max-width: 782px ) {
		.cbp-container {
			grid-template-columns: 1fr;
		}

		.cbp-sidebar {
			grid-column: 1;
		}

		.cbp-layout-row {
			grid-template-columns: 1fr;
		}

		.cbp-items-grid {
			grid-template-columns: repeat( auto-fill, minmax( 120px, 1fr ) );
		}
	}
</style>

<div class="cbp-admin-header">
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=cubeportfolio' ) ); ?>" class="back-link">
		&larr; <?php esc_html_e( 'Back to Portfolios', CUBEPORTFOLIO_TEXTDOMAIN ); ?>
	</a>
	<h1><?php echo $is_new ? esc_html__( 'Create New Portfolio', CUBEPORTFOLIO_TEXTDOMAIN ) : esc_html__( 'Edit Portfolio', CUBEPORTFOLIO_TEXTDOMAIN ); ?></h1>
	<div class="cbp-notice" id="cbp-saved-notice">
		<?php esc_html_e( 'Portfolio saved successfully!', CUBEPORTFOLIO_TEXTDOMAIN ); ?>
	</div>
</div>

<form id="cbp-portfolio-form" method="POST">
	<?php wp_nonce_field( 'cbp_save_portfolio', 'cbp_nonce' ); ?>
	<input type="hidden" name="cbp_action" value="save_portfolio">
	<input type="hidden" id="cbp-portfolio-id" name="cbp_portfolio_id" value="<?php echo absint( $current_id ); ?>">
	<input type="hidden" id="cbp-items-json" name="cbp_items_json" value="">

	<div class="cbp-container">
		<!-- Main Content -->
		<div class="cbp-main">
			<!-- Portfolio Name -->
			<div class="postbox">
				<h2><?php esc_html_e( 'Portfolio Name', CUBEPORTFOLIO_TEXTDOMAIN ); ?></h2>
				<div class="postbox-content">
					<div class="cbp-form-group">
						<input type="text" id="cbp-name" name="cbp_name" value="<?php echo esc_attr( $current_name ); ?>" placeholder="<?php esc_attr_e( 'e.g., Recent Projects', CUBEPORTFOLIO_TEXTDOMAIN ); ?>" required>
					</div>
				</div>
			</div>

			<!-- Layout Settings -->
			<div class="postbox">
				<h2><?php esc_html_e( 'Layout Settings', CUBEPORTFOLIO_TEXTDOMAIN ); ?></h2>
				<div class="postbox-content">
					<div class="cbp-layout-row">
						<div class="cbp-form-group">
							<label for="cbp-layout-mode"><?php esc_html_e( 'Layout Mode', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
							<select id="cbp-layout-mode" name="cbp_layout_mode">
								<option value="mosaic" <?php selected( $current_layout_mode, 'mosaic' ); ?>>Mosaic</option>
								<option value="grid" <?php selected( $current_layout_mode, 'grid' ); ?>>Grid</option>
								<option value="slider" <?php selected( $current_layout_mode, 'slider' ); ?>>Slider</option>
								<option value="hexagonal" <?php selected( $current_layout_mode, 'hexagonal' ); ?>>Hexagonal</option>
								<option value="magazine" <?php selected( $current_layout_mode, 'magazine' ); ?>>Magazine</option>
							</select>
						</div>
						<div class="cbp-form-group">
							<label for="cbp-ratio"><?php esc_html_e( 'Item Ratio', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
							<select id="cbp-ratio" name="cbp_ratio">
								<option value="masonry" <?php selected( $current_ratio, 'masonry' ); ?>>Masonry (Variable)</option>
								<option value="1-1" <?php selected( $current_ratio, '1-1' ); ?>>1:1 (Square)</option>
								<option value="3-2" <?php selected( $current_ratio, '3-2' ); ?>>3:2</option>
								<option value="4-3" <?php selected( $current_ratio, '4-3' ); ?>>4:3</option>
								<option value="16-9" <?php selected( $current_ratio, '16-9' ); ?>>16:9 (Widescreen)</option>
								<option value="2-1" <?php selected( $current_ratio, '2-1' ); ?>>2:1 (Ultra Wide)</option>
							</select>
						</div>
					</div>
				</div>
			</div>

			<!-- Portfolio Items -->
			<div class="postbox">
				<h2><?php esc_html_e( 'Portfolio Items', CUBEPORTFOLIO_TEXTDOMAIN ); ?></h2>
				<div class="postbox-content">
					<div class="cbp-items-grid" id="cbp-items-grid">
						<!-- Items will be rendered here by JavaScript -->
					</div>
					<button type="button" class="cbp-add-item-btn" id="cbp-add-item-btn">
						+ <?php esc_html_e( 'Add Item', CUBEPORTFOLIO_TEXTDOMAIN ); ?>
					</button>
				</div>
			</div>

			<!-- Custom CSS -->
			<div class="postbox">
				<h2><?php esc_html_e( 'Custom CSS', CUBEPORTFOLIO_TEXTDOMAIN ); ?></h2>
				<div class="postbox-content">
					<div class="cbp-form-group">
						<textarea id="cbp-customcss-text" name="cbp_customcss_text" rows="10" placeholder="<?php esc_attr_e( 'Enter custom CSS rules here...', CUBEPORTFOLIO_TEXTDOMAIN ); ?>"><?php echo esc_textarea( $current_customcss ); ?></textarea>
					</div>
				</div>
			</div>

			<!-- Advanced Settings -->
			<details id="cbp-advanced-settings">
				<summary><?php esc_html_e( 'Advanced Settings', CUBEPORTFOLIO_TEXTDOMAIN ); ?></summary>
				<div style="padding: 15px;">
					<div class="cbp-form-group" style="margin-bottom: 15px;">
						<label for="cbp-options"><?php esc_html_e( 'Options (JSON)', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
						<textarea id="cbp-options" name="cbp_options" rows="6" placeholder="{}"><?php echo esc_textarea( $current_options ); ?></textarea>
					</div>

					<div class="cbp-form-group" style="margin-bottom: 15px;">
						<label for="cbp-template"><?php esc_html_e( 'Template (HTML)', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
						<textarea id="cbp-template" name="cbp_template" rows="6"><?php echo esc_textarea( $current_template ); ?></textarea>
					</div>

					<div class="cbp-form-group" style="margin-bottom: 15px;">
						<label for="cbp-filtershtml"><?php esc_html_e( 'Filters HTML', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
						<textarea id="cbp-filtershtml" name="cbp_filtershtml" rows="4"><?php echo esc_textarea( $current_filtershtml ); ?></textarea>
					</div>

					<div class="cbp-form-group" style="margin-bottom: 15px;">
						<label for="cbp-loadmorehtml"><?php esc_html_e( 'Load More HTML', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
						<textarea id="cbp-loadmorehtml" name="cbp_loadMorehtml" rows="4"><?php echo esc_textarea( $current_loadMorehtml ); ?></textarea>
					</div>

					<div class="cbp-form-group" style="margin-bottom: 15px;">
						<label for="cbp-googlefonts"><?php esc_html_e( 'Google Fonts', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
						<textarea id="cbp-googlefonts" name="cbp_googlefonts" rows="4"><?php echo esc_textarea( $current_googlefonts ); ?></textarea>
					</div>

					<div class="cbp-form-group">
						<label for="cbp-jsondata"><?php esc_html_e( 'JSON Data', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
						<textarea id="cbp-jsondata" name="cbp_jsondata" rows="6"><?php echo esc_textarea( $current_jsondata ); ?></textarea>
					</div>
				</div>
			</details>
		</div>

		<!-- Sidebar -->
		<div class="cbp-sidebar">
			<?php if ( ! $is_new ) : ?>
				<div class="cbp-sidebar-section">
					<h3><?php esc_html_e( 'Shortcode', CUBEPORTFOLIO_TEXTDOMAIN ); ?></h3>
					<div class="cbp-shortcode-display" id="cbp-shortcode-display">
						[cubeportfolio id="<?php echo absint( $current_id ); ?>"]
					</div>
					<small><?php esc_html_e( 'Copy and paste this shortcode into any post or page.', CUBEPORTFOLIO_TEXTDOMAIN ); ?></small>
				</div>
			<?php endif; ?>

			<button type="submit" class="cbp-save-btn" id="cbp-save-btn">
				<?php esc_html_e( 'Save Portfolio', CUBEPORTFOLIO_TEXTDOMAIN ); ?>
			</button>
		</div>
	</div>
</form>

<!-- Add/Edit Item Modal -->
<div class="cbp-modal-overlay" id="cbp-modal-overlay">
	<div class="cbp-modal" id="cbp-modal">
		<div class="cbp-modal-header">
			<h2 id="cbp-modal-title"><?php esc_html_e( 'Add Item', CUBEPORTFOLIO_TEXTDOMAIN ); ?></h2>
			<button type="button" class="cbp-modal-close" id="cbp-modal-close">&times;</button>
		</div>
		<div class="cbp-modal-body">
			<!-- Item Type Toggle -->
			<div class="cbp-item-type-toggle">
				<button type="button" class="cbp-item-type-btn active" data-type="image">
					<?php esc_html_e( 'Image', CUBEPORTFOLIO_TEXTDOMAIN ); ?>
				</button>
				<button type="button" class="cbp-item-type-btn" data-type="shortcode">
					<?php esc_html_e( 'Shortcode/Embed', CUBEPORTFOLIO_TEXTDOMAIN ); ?>
				</button>
			</div>

			<!-- Image Item Section -->
			<div class="cbp-item-section active" data-section="image">
				<div class="cbp-form-group" style="margin-bottom: 15px;">
					<label><?php esc_html_e( 'Image', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
					<button type="button" class="cbp-add-item-btn" id="cbp-media-picker" style="width: 100%;">
						<?php esc_html_e( 'Choose Image from Library', CUBEPORTFOLIO_TEXTDOMAIN ); ?>
					</button>
					<input type="hidden" id="cbp-item-image-id" value="">
					<input type="hidden" id="cbp-item-image-url" value="">
					<div id="cbp-image-preview" style="margin-top: 10px;">
						<!-- Preview image will appear here -->
					</div>
				</div>

				<div class="cbp-form-group" style="margin-bottom: 15px;">
					<label for="cbp-item-title"><?php esc_html_e( 'Title', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
					<input type="text" id="cbp-item-title" placeholder="<?php esc_attr_e( 'Item title', CUBEPORTFOLIO_TEXTDOMAIN ); ?>">
				</div>

				<div class="cbp-form-group" style="margin-bottom: 15px;">
					<label for="cbp-item-description"><?php esc_html_e( 'Description', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
					<textarea id="cbp-item-description" rows="4" placeholder="<?php esc_attr_e( 'Item description', CUBEPORTFOLIO_TEXTDOMAIN ); ?>"></textarea>
				</div>

				<div class="cbp-form-group" style="margin-bottom: 15px;">
					<label for="cbp-item-categories"><?php esc_html_e( 'Categories', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
					<input type="text" id="cbp-item-categories" placeholder="<?php esc_attr_e( 'e.g., design, web (comma-separated)', CUBEPORTFOLIO_TEXTDOMAIN ); ?>">
				</div>

				<div class="cbp-form-group" style="margin-bottom: 15px;">
					<label for="cbp-item-link"><?php esc_html_e( 'Link URL', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
					<input type="url" id="cbp-item-link" placeholder="<?php esc_attr_e( 'https://example.com', CUBEPORTFOLIO_TEXTDOMAIN ); ?>">
				</div>

				<div class="cbp-form-group" style="margin-bottom: 15px;">
					<label for="cbp-item-link-type"><?php esc_html_e( 'Link Type', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
					<select id="cbp-item-link-type">
						<option value="lightbox"><?php esc_html_e( 'Lightbox', CUBEPORTFOLIO_TEXTDOMAIN ); ?></option>
						<option value="single-page"><?php esc_html_e( 'Single Page', CUBEPORTFOLIO_TEXTDOMAIN ); ?></option>
						<option value="single-page-inline"><?php esc_html_e( 'Single Page (Inline)', CUBEPORTFOLIO_TEXTDOMAIN ); ?></option>
						<option value="external"><?php esc_html_e( 'External Link', CUBEPORTFOLIO_TEXTDOMAIN ); ?></option>
					</select>
				</div>

				<div class="cbp-form-group">
					<label for="cbp-item-size"><?php esc_html_e( 'Item Size', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
					<select id="cbp-item-size">
						<option value="1x1"><?php esc_html_e( '1x1', CUBEPORTFOLIO_TEXTDOMAIN ); ?></option>
						<option value="2x1"><?php esc_html_e( '2x1', CUBEPORTFOLIO_TEXTDOMAIN ); ?></option>
						<option value="1x2"><?php esc_html_e( '1x2', CUBEPORTFOLIO_TEXTDOMAIN ); ?></option>
						<option value="2x2"><?php esc_html_e( '2x2', CUBEPORTFOLIO_TEXTDOMAIN ); ?></option>
					</select>
				</div>
			</div>

			<!-- Shortcode Item Section -->
			<div class="cbp-item-section" data-section="shortcode">
				<div class="cbp-form-group" style="margin-bottom: 15px;">
					<label for="cbp-item-shortcode"><?php esc_html_e( 'Shortcode/Embed Code', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
					<textarea id="cbp-item-shortcode" rows="6" placeholder="<?php esc_attr_e( 'e.g., [gallery id="123"] or <iframe>...</iframe>', CUBEPORTFOLIO_TEXTDOMAIN ); ?>"></textarea>
				</div>

				<div class="cbp-form-group" style="margin-bottom: 15px;">
					<label for="cbp-item-sc-categories"><?php esc_html_e( 'Categories', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
					<input type="text" id="cbp-item-sc-categories" placeholder="<?php esc_attr_e( 'e.g., design, web (comma-separated)', CUBEPORTFOLIO_TEXTDOMAIN ); ?>">
				</div>

				<div class="cbp-form-group">
					<label for="cbp-item-sc-size"><?php esc_html_e( 'Item Size', CUBEPORTFOLIO_TEXTDOMAIN ); ?></label>
					<select id="cbp-item-sc-size">
						<option value="1x1"><?php esc_html_e( '1x1', CUBEPORTFOLIO_TEXTDOMAIN ); ?></option>
						<option value="2x1"><?php esc_html_e( '2x1', CUBEPORTFOLIO_TEXTDOMAIN ); ?></option>
						<option value="1x2"><?php esc_html_e( '1x2', CUBEPORTFOLIO_TEXTDOMAIN ); ?></option>
						<option value="2x2"><?php esc_html_e( '2x2', CUBEPORTFOLIO_TEXTDOMAIN ); ?></option>
					</select>
				</div>
			</div>
		</div>

		<div class="cbp-modal-footer">
			<button type="button" id="cbp-modal-cancel" class="secondary">
				<?php esc_html_e( 'Cancel', CUBEPORTFOLIO_TEXTDOMAIN ); ?>
			</button>
			<button type="button" id="cbp-modal-save" class="primary">
				<?php esc_html_e( 'Save Item', CUBEPORTFOLIO_TEXTDOMAIN ); ?>
			</button>
		</div>
	</div>
</div>

<script>
	( function( $ ) {
		'use strict';

		// Item storage
		let itemStore = <?php echo wp_json_encode( $parsed_items ); ?>;
		let editingIndex = null;
		let mediaFrame;

		// ========== Grid Rendering ==========
		function renderGrid() {
			const grid = $( '#cbp-items-grid' );
			grid.empty();

			if ( itemStore.length === 0 ) {
				grid.html( '<p style="grid-column: 1/-1; padding: 20px; text-align: center; color: #999;"><?php esc_html_e( 'No items yet. Add one to get started!', CUBEPORTFOLIO_TEXTDOMAIN ); ?></p>' );
				return;
			}

			itemStore.forEach( ( item, index ) => {
				const card = $( '<div class="cbp-item-card"></div>' );

				if ( item.item_type === 'shortcode' ) {
					card.append( $( '<div class="cbp-item-shortcode-label">[sc]</div>' ) );
				} else if ( item.image_url ) {
					card.append( $( '<img />' ).attr( 'src', item.image_url ).attr( 'alt', item.title || 'Portfolio item' ) );
				} else {
					card.append( $( '<div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; background: #ddd; color: #999;">No image</div>' ) );
				}

				const removeBtn = $( '<button type="button" class="cbp-item-remove" aria-label="Remove item">×</button>' );
				removeBtn.on( 'click', function( e ) {
					e.preventDefault();
					itemStore.splice( index, 1 );
					renderGrid();
				} );

				card.append( removeBtn );
				card.on( 'click', function() {
					openModal( index );
				} );

				grid.append( card );
			} );
		}

		// ========== Modal Functions ==========
		function openModal( index ) {
			editingIndex = index;
			const overlay = $( '#cbp-modal-overlay' );
			const modal = $( '#cbp-modal' );
			const title = $( '#cbp-modal-title' );

			// Reset form
			$( '#cbp-item-image-id' ).val( '' );
			$( '#cbp-item-image-url' ).val( '' );
			$( '#cbp-item-title' ).val( '' );
			$( '#cbp-item-description' ).val( '' );
			$( '#cbp-item-categories' ).val( '' );
			$( '#cbp-item-link' ).val( '' );
			$( '#cbp-item-link-type' ).val( 'lightbox' );
			$( '#cbp-item-size' ).val( '1x1' );
			$( '#cbp-item-shortcode' ).val( '' );
			$( '#cbp-item-sc-categories' ).val( '' );
			$( '#cbp-item-sc-size' ).val( '1x1' );
			$( '#cbp-image-preview' ).empty();

			if ( index !== null && itemStore[ index ] ) {
				const item = itemStore[ index ];
				title.text( '<?php esc_html_e( 'Edit Item', CUBEPORTFOLIO_TEXTDOMAIN ); ?>' );

				if ( item.item_type === 'shortcode' ) {
					setItemType( 'shortcode' );
					$( '#cbp-item-shortcode' ).val( item.shortcode );
					$( '#cbp-item-sc-categories' ).val( item.categories );
					$( '#cbp-item-sc-size' ).val( item.size );
				} else {
					setItemType( 'image' );
					$( '#cbp-item-image-id' ).val( item.image_id );
					$( '#cbp-item-image-url' ).val( item.image_url );
					$( '#cbp-item-title' ).val( item.title );
					$( '#cbp-item-description' ).val( item.desc );
					$( '#cbp-item-categories' ).val( item.categories );
					$( '#cbp-item-link' ).val( item.link );
					$( '#cbp-item-link-type' ).val( item.link_type );
					$( '#cbp-item-size' ).val( item.size );

					if ( item.image_url ) {
						$( '#cbp-image-preview' ).html( '<img src="' + item.image_url + '" style="max-width: 100%; max-height: 200px;" />' );
					}
				}
			} else {
				editingIndex = null;
				title.text( '<?php esc_html_e( 'Add Item', CUBEPORTFOLIO_TEXTDOMAIN ); ?>' );
				setItemType( 'image' );
			}

			overlay.addClass( 'open' );
		}

		function closeModal() {
			$( '#cbp-modal-overlay' ).removeClass( 'open' );
			editingIndex = null;
		}

		function setItemType( type ) {
			$( '.cbp-item-type-btn' ).removeClass( 'active' );
			$( '.cbp-item-type-btn[data-type="' + type + '"]' ).addClass( 'active' );
			$( '.cbp-item-section' ).removeClass( 'active' );
			$( '.cbp-item-section[data-section="' + type + '"]' ).addClass( 'active' );
		}

		// ========== Media Picker ==========
		$( '#cbp-media-picker' ).on( 'click', function( e ) {
			e.preventDefault();

			if ( mediaFrame ) {
				mediaFrame.open();
				return;
			}

			mediaFrame = wp.media( {
				title: '<?php esc_js( __( 'Select Image', CUBEPORTFOLIO_TEXTDOMAIN ) ); ?>',
				button: {
					text: '<?php esc_js( __( 'Use Image', CUBEPORTFOLIO_TEXTDOMAIN ) ); ?>'
				},
				multiple: false,
				library: {
					type: 'image'
				}
			} );

			mediaFrame.on( 'select', function() {
				const attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
				$( '#cbp-item-image-id' ).val( attachment.id );
				$( '#cbp-item-image-url' ).val( attachment.url );
				$( '#cbp-image-preview' ).html( '<img src="' + attachment.url + '" style="max-width: 100%; max-height: 200px;" />' );
			} );

			mediaFrame.open();
		} );

		// ========== Modal Events ==========
		$( '.cbp-item-type-btn' ).on( 'click', function( e ) {
			e.preventDefault();
			const type = $( this ).data( 'type' );
			setItemType( type );
		} );

		$( '#cbp-modal-close, #cbp-modal-cancel' ).on( 'click', function( e ) {
			e.preventDefault();
			closeModal();
		} );

		$( '#cbp-modal-save' ).on( 'click', function( e ) {
			e.preventDefault();
			saveItem();
		} );

		$( '#cbp-add-item-btn' ).on( 'click', function( e ) {
			e.preventDefault();
			openModal( null );
		} );

		// ========== Item Save ==========
		function saveItem() {
			const itemType = $( '.cbp-item-type-btn.active' ).data( 'type' );
			let item;

			if ( itemType === 'shortcode' ) {
				const shortcode = $( '#cbp-item-shortcode' ).val().trim();
				if ( ! shortcode ) {
					alert( '<?php esc_js( __( 'Please enter a shortcode or embed code.', CUBEPORTFOLIO_TEXTDOMAIN ) ); ?>' );
					return;
				}
				item = buildShortcodeItemHtml( shortcode, $( '#cbp-item-sc-categories' ).val(), $( '#cbp-item-sc-size' ).val() );
			} else {
				const imageUrl = $( '#cbp-item-image-url' ).val().trim();
				if ( ! imageUrl ) {
					alert( '<?php esc_js( __( 'Please select an image.', CUBEPORTFOLIO_TEXTDOMAIN ) ); ?>' );
					return;
				}
				item = buildItemHtml(
					imageUrl,
					$( '#cbp-item-title' ).val(),
					$( '#cbp-item-description' ).val(),
					$( '#cbp-item-categories' ).val(),
					$( '#cbp-item-link' ).val(),
					$( '#cbp-item-link-type' ).val(),
					$( '#cbp-item-size' ).val(),
					$( '#cbp-item-image-id' ).val()
				);
			}

			if ( editingIndex !== null ) {
				// Preserve DB metadata from the original item so we UPDATE, not INSERT.
				var prev = itemStore[ editingIndex ];
				item.db_id        = prev.db_id        || 0;
				item.page         = prev.page         || 0;
				item.isLoadMore   = prev.isLoadMore   || '0';
				item.isSinglePage = prev.isSinglePage || '';
				itemStore[ editingIndex ] = item;
			} else {
				itemStore.push( item );
			}

			closeModal();
			renderGrid();
		}

		// ========== Item HTML Builders ==========
		// Minimal helper to escape HTML attribute values.
		function escAttr( s ) {
			return String( s || '' )
				.replace( /&/g, '&amp;' )
				.replace( /"/g, '&quot;' )
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' );
		}

		function buildItemHtml( imgUrl, title, desc, cats, link, ltype, size, imageId ) {
			// Build the CBP-compatible item HTML that goes into the portfolio grid.
			var catClass = cats ? ' ' + cats.trim().replace( /[,]+/g, ' ' ).replace( /\s+/g, ' ' ) : '';
			var href     = link || imgUrl;
			var linkClass = 'cbp-lightbox';
			if ( ltype === 'single-page' )        linkClass = 'cbp-singlePage';
			if ( ltype === 'single-page-inline' ) linkClass = 'cbp-singlePageInline';
			if ( ltype === 'external' )           linkClass = '';

			var html = '<div class="cbp-item' + catClass + '">' +
				'<a class="' + linkClass + '" href="' + escAttr( href ) + '" data-title="' + escAttr( title ) + '" data-description="' + escAttr( desc ) + '">' +
				'<div class="cbp-caption">' +
				'<div class="cbp-caption-defaultWrap">' +
				'<img src="' + escAttr( imgUrl ) + '" alt="' + escAttr( title ) + '">' +
				'</div>' +
				'</div>' +
				'</a>' +
				'</div>';

			return {
				item_type:   'image',
				image_url:   imgUrl,
				image_id:    imageId || '',
				title:       title,
				desc:        desc,
				categories:  cats,
				link:        link,
				link_type:   ltype,
				size:        size,
				shortcode:   '',
				items:       html,   // HTML stored in DB 'items' column
				db_id:       0,
				page:        0,
				isLoadMore:  '0',
				isSinglePage: ''
			};
		}

		function buildShortcodeItemHtml( shortcode, cats, size ) {
			return {
				item_type:   'shortcode',
				image_url:   '',
				image_id:    '',
				title:       '',
				desc:        '',
				categories:  cats,
				link:        '',
				link_type:   '',
				size:        size,
				shortcode:   shortcode,
				items:       shortcode,   // shortcode IS the items content for DB
				db_id:       0,
				page:        0,
				isLoadMore:  '0',
				isSinglePage: ''
			};
		}

		// ========== Form Submit ==========
		$( '#cbp-portfolio-form' ).on( 'submit', function( e ) {
			const name = $( '#cbp-name' ).val().trim();
			if ( ! name ) {
				e.preventDefault();
				alert( '<?php esc_js( __( 'Please enter a portfolio name.', CUBEPORTFOLIO_TEXTDOMAIN ) ); ?>' );
				return;
			}

			// Serialize items to the shape expected by handle_save_portfolio_php():
			// { id, page, isLoadMore, isSinglePage, items }
			// where 'items' is the raw HTML/shortcode stored in the DB column.
			const serialized = itemStore.map( function( item ) {
				return {
					id:           item.db_id        || 0,
					page:         item.page         || 0,
					isLoadMore:   item.isLoadMore   || '0',
					isSinglePage: item.isSinglePage || '',
					items:        item.items        || ''
				};
			} );
			$( '#cbp-items-json' ).val( JSON.stringify( serialized ) );

			// Sync layout mode and ratio into options/template
			const layoutMode = $( '#cbp-layout-mode' ).val();
			const ratio = $( '#cbp-ratio' ).val();

			// Update options JSON
			let options = {};
			try {
				const optionsText = $( '#cbp-options' ).val().trim();
				if ( optionsText ) {
					options = JSON.parse( optionsText );
				}
			} catch ( e ) {
				console.warn( 'Invalid JSON in options field', e );
			}
			options.layoutMode = layoutMode;
			$( '#cbp-options' ).val( JSON.stringify( options ) );

			// Update template with ratio class
			let template = $( '#cbp-template' ).val();
			// Remove old ratio class
			template = template.replace( /cbp-ratio-[\w\-]+/g, 'cbp-ratio-' + ratio );
			$( '#cbp-template' ).val( template );
		} );

		// ========== Initialize ==========
		renderGrid();

		// Close modal on overlay click
		$( '#cbp-modal-overlay' ).on( 'click', function( e ) {
			if ( e.target === this ) {
				closeModal();
			}
		} );
	} )( jQuery );
</script>
