<?php
/**
 * Post Types & Taxonomy Registration + Admin Menu
 *
 * @package G_Folio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================
   REGISTER CPTs & TAXONOMY
   ========================================================= */

add_action( 'init', 'gfolio_register_post_types' );

function gfolio_register_post_types(): void {

	// ----- Portfolio (top-level container) -----
	$portfolio_labels = array(
		'name'               => _x( 'Portfolios', 'post type general name', 'g-folio' ),
		'singular_name'      => _x( 'Portfolio', 'post type singular name', 'g-folio' ),
		'menu_name'          => __( 'Portfolios', 'g-folio' ),
		'add_new'            => __( 'Add New', 'g-folio' ),
		'add_new_item'       => __( 'Add New Portfolio', 'g-folio' ),
		'edit_item'          => __( 'Edit Portfolio', 'g-folio' ),
		'new_item'           => __( 'New Portfolio', 'g-folio' ),
		'view_item'          => __( 'View Portfolio', 'g-folio' ),
		'search_items'       => __( 'Search Portfolios', 'g-folio' ),
		'not_found'          => __( 'No portfolios found.', 'g-folio' ),
		'not_found_in_trash' => __( 'No portfolios found in trash.', 'g-folio' ),
		'all_items'          => __( 'All Portfolios', 'g-folio' ),
	);

	register_post_type( 'gfolio_portfolio', array(
		'labels'             => $portfolio_labels,
		'description'        => __( 'Portfolio containers for G Folio.', 'g-folio' ),
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => false,
		'show_in_nav_menus'  => false,
		'show_in_admin_bar'  => true,
		'supports'           => array( 'title' ),
		'has_archive'        => false,
		'rewrite'            => false,
		'show_in_rest'       => false,
		'capability_type'    => 'post',
		'map_meta_cap'       => true,
		'hierarchical'       => false,
		'menu_icon'          => 'dashicons-portfolio',
	) );

	// ----- Portfolio Item -----
	$labels = array(
		'name'                  => _x( 'Portfolio Items', 'post type general name', 'g-folio' ),
		'singular_name'         => _x( 'Portfolio Item', 'post type singular name', 'g-folio' ),
		'menu_name'             => __( 'Portfolio Items', 'g-folio' ),
		'add_new'               => __( 'Add New', 'g-folio' ),
		'add_new_item'          => __( 'Add New Portfolio Item', 'g-folio' ),
		'edit_item'             => __( 'Edit Portfolio Item', 'g-folio' ),
		'new_item'              => __( 'New Portfolio Item', 'g-folio' ),
		'view_item'             => __( 'View Portfolio Item', 'g-folio' ),
		'search_items'          => __( 'Search Portfolio Items', 'g-folio' ),
		'not_found'             => __( 'No portfolio items found.', 'g-folio' ),
		'not_found_in_trash'    => __( 'No portfolio items found in trash.', 'g-folio' ),
		'all_items'             => __( 'All Portfolio Items', 'g-folio' ),
		'archives'              => __( 'Portfolio Archives', 'g-folio' ),
		'attributes'            => __( 'Portfolio Item Attributes', 'g-folio' ),
		'insert_into_item'      => __( 'Insert into portfolio item', 'g-folio' ),
		'uploaded_to_this_item' => __( 'Uploaded to this portfolio item', 'g-folio' ),
		'featured_image'        => __( 'Thumbnail Image', 'g-folio' ),
		'set_featured_image'    => __( 'Set thumbnail image', 'g-folio' ),
		'remove_featured_image' => __( 'Remove thumbnail image', 'g-folio' ),
		'use_featured_image'    => __( 'Use as thumbnail image', 'g-folio' ),
		'filter_items_list'     => __( 'Filter portfolio items list', 'g-folio' ),
		'items_list_navigation' => __( 'Portfolio items list navigation', 'g-folio' ),
		'items_list'            => __( 'Portfolio items list', 'g-folio' ),
	);

	$args = array(
		'labels'             => $labels,
		'description'        => __( 'Portfolio items for G Folio.', 'g-folio' ),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => false,
		'show_in_nav_menus'  => false,
		'show_in_admin_bar'  => true,
		'supports'           => array( 'title', 'thumbnail' ),
		'has_archive'        => false,
		'rewrite'            => array( 'slug' => 'portfolio-item', 'with_front' => false ),
		'show_in_rest'       => false,
		'capability_type'    => 'post',
		'map_meta_cap'       => true,
		'hierarchical'       => false,
		'menu_icon'          => 'dashicons-portfolio',
	);

	register_post_type( 'gfolio_item', $args );

	// ----- Portfolio Category (Taxonomy) -----
	$tax_labels = array(
		'name'                       => _x( 'Portfolio Categories', 'taxonomy general name', 'g-folio' ),
		'singular_name'              => _x( 'Portfolio Category', 'taxonomy singular name', 'g-folio' ),
		'search_items'               => __( 'Search Categories', 'g-folio' ),
		'popular_items'              => __( 'Popular Categories', 'g-folio' ),
		'all_items'                  => __( 'All Categories', 'g-folio' ),
		'parent_item'                => __( 'Parent Category', 'g-folio' ),
		'parent_item_colon'          => __( 'Parent Category:', 'g-folio' ),
		'edit_item'                  => __( 'Edit Category', 'g-folio' ),
		'update_item'                => __( 'Update Category', 'g-folio' ),
		'add_new_item'               => __( 'Add New Category', 'g-folio' ),
		'new_item_name'              => __( 'New Category Name', 'g-folio' ),
		'separate_items_with_commas' => __( 'Separate categories with commas', 'g-folio' ),
		'add_or_remove_items'        => __( 'Add or remove categories', 'g-folio' ),
		'choose_from_most_used'      => __( 'Choose from the most used categories', 'g-folio' ),
		'not_found'                  => __( 'No categories found.', 'g-folio' ),
		'menu_name'                  => __( 'Categories', 'g-folio' ),
	);

	$tax_args = array(
		'hierarchical'      => true,
		'labels'            => $tax_labels,
		'show_ui'           => true,
		'show_in_menu'      => false,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'portfolio-category' ),
		'show_in_rest'      => false,
		'show_tag_cloud'    => false,
	);

	register_taxonomy( 'gfolio_category', array( 'gfolio_item' ), $tax_args );
}

/* =========================================================
   ADMIN MENU
   ========================================================= */

add_action( 'admin_menu', 'gfolio_admin_menu' );

function gfolio_admin_menu(): void {

	// Top-level parent page
	add_menu_page(
		__( 'G Folio', 'g-folio' ),
		__( 'G Folio', 'g-folio' ),
		'edit_posts',
		'gfolio-portfolio',
		'gfolio_redirect_to_portfolios',
		'dashicons-portfolio',
		25
	);

	// Submenu: All Portfolios
	add_submenu_page(
		'gfolio-portfolio',
		__( 'Portfolios', 'g-folio' ),
		__( 'Portfolios', 'g-folio' ),
		'edit_posts',
		'edit.php?post_type=gfolio_portfolio'
	);

	// Submenu: All Portfolio Items
	add_submenu_page(
		'gfolio-portfolio',
		__( 'Portfolio Items', 'g-folio' ),
		__( 'Portfolio Items', 'g-folio' ),
		'edit_posts',
		'edit.php?post_type=gfolio_item'
	);

	// Submenu: Categories
	add_submenu_page(
		'gfolio-portfolio',
		__( 'Categories', 'g-folio' ),
		__( 'Categories', 'g-folio' ),
		'manage_categories',
		'edit-tags.php?taxonomy=gfolio_category&post_type=gfolio_item'
	);

	// Submenu: Settings
	add_submenu_page(
		'gfolio-portfolio',
		__( 'G Folio Settings', 'g-folio' ),
		__( 'Settings', 'g-folio' ),
		'manage_options',
		'gfolio-settings',
		'gfolio_settings_page_render'
	);
}

function gfolio_redirect_to_portfolios(): void {
	echo '<div class="wrap"><p>' . esc_html__( 'Redirecting…', 'g-folio' ) . '</p></div>';
}

// Redirect the top-level menu slug BEFORE any output is sent
add_action( 'admin_init', 'gfolio_maybe_redirect_to_portfolios' );
function gfolio_maybe_redirect_to_portfolios(): void {
	if (
		isset( $_GET['page'] ) &&
		'gfolio-portfolio' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) &&
		current_user_can( 'edit_posts' )
	) {
		wp_safe_redirect( admin_url( 'edit.php?post_type=gfolio_portfolio' ) );
		exit;
	}
}

/* =========================================================
   FIX ACTIVE MENU HIGHLIGHTS
   ========================================================= */

add_filter( 'parent_file', 'gfolio_fix_parent_file' );
function gfolio_fix_parent_file( string $parent_file ): string {
	global $current_screen;
	if ( isset( $current_screen->post_type ) && in_array( $current_screen->post_type, array( 'gfolio_item', 'gfolio_portfolio' ), true ) ) {
		return 'gfolio-portfolio';
	}
	if ( isset( $current_screen->taxonomy ) && 'gfolio_category' === $current_screen->taxonomy ) {
		return 'gfolio-portfolio';
	}
	return $parent_file;
}

add_filter( 'submenu_file', 'gfolio_fix_submenu_file' );
function gfolio_fix_submenu_file( ?string $submenu_file ): ?string {
	global $current_screen, $pagenow;

	if ( isset( $current_screen->post_type ) ) {
		if ( 'gfolio_portfolio' === $current_screen->post_type ) {
			if ( 'post-new.php' === $pagenow ) {
				return 'post-new.php?post_type=gfolio_portfolio';
			}
			return 'edit.php?post_type=gfolio_portfolio';
		}
		if ( 'gfolio_item' === $current_screen->post_type ) {
			if ( 'post-new.php' === $pagenow ) {
				return 'post-new.php?post_type=gfolio_item';
			}
			return 'edit.php?post_type=gfolio_item';
		}
	}
	if ( isset( $current_screen->taxonomy ) && 'gfolio_category' === $current_screen->taxonomy ) {
		return 'edit-tags.php?taxonomy=gfolio_category&post_type=gfolio_item';
	}
	return $submenu_file;
}

/* =========================================================
   PORTFOLIO LIST TABLE — SHORTCODE COLUMN
   ========================================================= */

add_filter( 'manage_gfolio_portfolio_posts_columns', 'gfolio_portfolio_columns' );
function gfolio_portfolio_columns( array $columns ): array {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['shortcode'] = __( 'Shortcode', 'g-folio' );
		}
	}
	return $new;
}

add_action( 'manage_gfolio_portfolio_posts_custom_column', 'gfolio_portfolio_column_content', 10, 2 );
function gfolio_portfolio_column_content( string $column, int $post_id ): void {
	if ( 'shortcode' !== $column ) {
		return;
	}
	$shortcode = '[gfolio id="' . $post_id . '"]';
	echo '<code class="gfolio-shortcode-chip" '
		. 'data-shortcode="' . esc_attr( $shortcode ) . '" '
		. 'title="' . esc_attr__( 'Click to copy', 'g-folio' ) . '">'
		. esc_html( $shortcode )
		. '</code>';
}

/* =========================================================
   CATEGORY LIST TABLE — PORTFOLIO COLUMN
   ========================================================= */

add_filter( 'manage_edit-gfolio_category_columns', 'gfolio_category_columns' );
function gfolio_category_columns( array $columns ): array {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'name' === $key ) {
			$new['portfolio'] = __( 'Portfolio', 'g-folio' );
		}
	}
	return $new;
}

add_filter( 'manage_gfolio_category_custom_column', 'gfolio_category_column_content', 10, 3 );
function gfolio_category_column_content( string $content, string $column, int $term_id ): string {
	if ( 'portfolio' !== $column ) {
		return $content;
	}
	$portfolio_id = (int) get_term_meta( $term_id, 'gfolio_cat_portfolio_id', true );
	if ( ! $portfolio_id ) {
		return '<span style="color:#a7aaad;">—</span>';
	}
	$portfolio = get_post( $portfolio_id );
	if ( ! $portfolio || 'gfolio_portfolio' !== $portfolio->post_type ) {
		return '<span style="color:#a7aaad;">—</span>';
	}
	$edit_url = get_edit_post_link( $portfolio_id );
	return '<a href="' . esc_url( $edit_url ) . '">' . esc_html( $portfolio->post_title ) . '</a>';
}

/* =========================================================
   TAXONOMY TERM META — PORTFOLIO, COLOR & ICON
   ========================================================= */

/**
 * Helper: return published portfolios for use in term meta forms.
 */
function gfolio_get_all_portfolios(): array {
	return get_posts( array(
		'post_type'      => 'gfolio_portfolio',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );
}

// Add form fields on taxonomy add screen
add_action( 'gfolio_category_add_form_fields', 'gfolio_category_add_fields' );
function gfolio_category_add_fields(): void {
	$portfolios = gfolio_get_all_portfolios();
	wp_nonce_field( 'gfolio_save_term_meta', 'gfolio_term_nonce' );
	?>
	<div class="form-field gfolio-term-field">
		<label for="gfolio_cat_portfolio_id"><?php esc_html_e( 'Portfolio', 'g-folio' ); ?></label>
		<select name="gfolio_cat_portfolio_id" id="gfolio_cat_portfolio_id">
			<option value=""><?php esc_html_e( '— Select a Portfolio —', 'g-folio' ); ?></option>
			<?php foreach ( $portfolios as $p ) : ?>
				<option value="<?php echo esc_attr( $p->ID ); ?>"><?php echo esc_html( $p->post_title ); ?></option>
			<?php endforeach; ?>
		</select>
		<p><?php esc_html_e( 'This category will only appear in the selected portfolio.', 'g-folio' ); ?></p>
		<?php if ( empty( $portfolios ) ) : ?>
			<p><em><?php esc_html_e( 'No portfolios exist yet. Create a portfolio first.', 'g-folio' ); ?></em></p>
		<?php endif; ?>
	</div>
	<div class="form-field gfolio-term-field">
		<label for="gfolio_cat_color"><?php esc_html_e( 'Filter Button Color', 'g-folio' ); ?></label>
		<input type="text" name="gfolio_cat_color" id="gfolio_cat_color" value="" class="gfolio-color-picker" data-default-color="#4a90e2" />
		<p><?php esc_html_e( 'Choose a highlight color for this category\'s filter button.', 'g-folio' ); ?></p>
	</div>
	<div class="form-field gfolio-term-field">
		<label for="gfolio_cat_icon"><?php esc_html_e( 'Category Icon (Dashicon)', 'g-folio' ); ?></label>
		<input type="text" name="gfolio_cat_icon" id="gfolio_cat_icon" value="" placeholder="dashicons-camera" />
		<p><?php esc_html_e( 'Optional dashicon class, e.g. dashicons-camera, dashicons-star-filled.', 'g-folio' ); ?></p>
	</div>
	<?php
}

// Edit form fields on taxonomy edit screen
add_action( 'gfolio_category_edit_form_fields', 'gfolio_category_edit_fields', 10, 2 );
function gfolio_category_edit_fields( WP_Term $term ): void {
	$portfolios   = gfolio_get_all_portfolios();
	$portfolio_id = (int) get_term_meta( $term->term_id, 'gfolio_cat_portfolio_id', true );
	$color        = get_term_meta( $term->term_id, 'gfolio_cat_color', true );
	$icon         = get_term_meta( $term->term_id, 'gfolio_cat_icon', true );
	wp_nonce_field( 'gfolio_save_term_meta', 'gfolio_term_nonce' );
	?>
	<tr class="form-field gfolio-term-row">
		<th scope="row">
			<label for="gfolio_cat_portfolio_id"><?php esc_html_e( 'Portfolio', 'g-folio' ); ?></label>
		</th>
		<td>
			<select name="gfolio_cat_portfolio_id" id="gfolio_cat_portfolio_id">
				<option value=""><?php esc_html_e( '— Select a Portfolio —', 'g-folio' ); ?></option>
				<?php foreach ( $portfolios as $p ) : ?>
					<option value="<?php echo esc_attr( $p->ID ); ?>" <?php selected( $portfolio_id, $p->ID ); ?>>
						<?php echo esc_html( $p->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="description"><?php esc_html_e( 'This category will only appear in the selected portfolio.', 'g-folio' ); ?></p>
		</td>
	</tr>
	<tr class="form-field gfolio-term-row">
		<th scope="row">
			<label for="gfolio_cat_color"><?php esc_html_e( 'Filter Button Color', 'g-folio' ); ?></label>
		</th>
		<td>
			<input type="text" name="gfolio_cat_color" id="gfolio_cat_color"
				value="<?php echo esc_attr( $color ); ?>"
				class="gfolio-color-picker"
				data-default-color="#4a90e2" />
			<p class="description"><?php esc_html_e( 'Choose a highlight color for this category\'s filter button.', 'g-folio' ); ?></p>
		</td>
	</tr>
	<tr class="form-field gfolio-term-row">
		<th scope="row">
			<label for="gfolio_cat_icon"><?php esc_html_e( 'Category Icon (Dashicon)', 'g-folio' ); ?></label>
		</th>
		<td>
			<input type="text" name="gfolio_cat_icon" id="gfolio_cat_icon"
				value="<?php echo esc_attr( $icon ); ?>"
				placeholder="dashicons-camera" />
			<p class="description"><?php esc_html_e( 'Optional dashicon class, e.g. dashicons-camera.', 'g-folio' ); ?></p>
		</td>
	</tr>
	<?php
}

// Save taxonomy term meta
add_action( 'created_gfolio_category', 'gfolio_save_term_meta' );
add_action( 'edited_gfolio_category', 'gfolio_save_term_meta' );
function gfolio_save_term_meta( int $term_id ): void {
	if ( ! isset( $_POST['gfolio_term_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gfolio_term_nonce'] ) ), 'gfolio_save_term_meta' )
	) {
		return;
	}

	// Portfolio assignment
	if ( array_key_exists( 'gfolio_cat_portfolio_id', $_POST ) ) {
		$pid = absint( $_POST['gfolio_cat_portfolio_id'] );
		if ( $pid ) {
			update_term_meta( $term_id, 'gfolio_cat_portfolio_id', $pid );
		} else {
			delete_term_meta( $term_id, 'gfolio_cat_portfolio_id' );
		}
	}

	if ( isset( $_POST['gfolio_cat_color'] ) ) {
		$color = sanitize_hex_color( wp_unslash( $_POST['gfolio_cat_color'] ) );
		update_term_meta( $term_id, 'gfolio_cat_color', $color );
	}

	if ( isset( $_POST['gfolio_cat_icon'] ) ) {
		$icon = sanitize_text_field( wp_unslash( $_POST['gfolio_cat_icon'] ) );
		update_term_meta( $term_id, 'gfolio_cat_icon', $icon );
	}
}
