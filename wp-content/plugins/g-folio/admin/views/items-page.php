<?php
/**
 * Custom admin page: Portfolio Items list
 *
 * @package G_Folio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$portfolio_filter = isset( $_GET['portfolio'] ) ? absint( $_GET['portfolio'] ) : 0;
$portfolios       = gfolio_get_all_portfolios();

// Query items
$query_args = array(
	'post_type'      => 'gfolio_item',
	'post_status'    => array( 'publish', 'draft' ),
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
);

if ( $portfolio_filter ) {
	$query_args['meta_query'] = array(
		array(
			'key'     => '_gfolio_portfolio_ids',
			'value'   => '"' . $portfolio_filter . '"',
			'compare' => 'LIKE',
		),
	);
}

$items    = new WP_Query( $query_args );
$new_url  = admin_url( 'post-new.php?post_type=gfolio_item' );
$base_url = admin_url( 'admin.php?page=gfolio-items' );

// Name of filtered portfolio
$filter_name = '';
if ( $portfolio_filter ) {
	$filter_post = get_post( $portfolio_filter );
	if ( $filter_post ) {
		$filter_name = $filter_post->post_title;
	}
}

// Build portfolio lookup map
$portfolio_map = array();
foreach ( $portfolios as $p ) {
	$portfolio_map[ $p->ID ] = $p->post_title;
}
?>
<div class="gfolio-settings-wrap">

	<!-- Header -->
	<div class="gfolio-settings-header">
		<div class="gfolio-settings-brand">
			<span class="dashicons dashicons-grid-view gfolio-brand-icon"></span>
			<div>
				<h1 class="gfolio-settings-title">
					<?php echo $filter_name ? esc_html( $filter_name ) : esc_html__( 'Portfolio Items', 'g-folio' ); ?>
				</h1>
				<p class="gfolio-settings-subtitle">
					<?php echo esc_html( sprintf( _n( '%d item', '%d items', $items->found_posts, 'g-folio' ), $items->found_posts ) ); ?>
					<?php if ( $portfolio_filter ) : ?>
						&nbsp;·&nbsp;
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=gfolio-portfolios' ) ); ?>" class="gfolio-header-back-link">
							← <?php esc_html_e( 'All Portfolios', 'g-folio' ); ?>
						</a>
					<?php endif; ?>
				</p>
			</div>
		</div>

		<div class="gfolio-header-controls">
			<?php if ( ! empty( $portfolios ) ) : ?>
				<select class="gfolio-filter-select" onchange="if(this.value) window.location=this.value; else window.location='<?php echo esc_js( $base_url ); ?>'">
					<option value=""><?php esc_html_e( 'All portfolios', 'g-folio' ); ?></option>
					<?php foreach ( $portfolios as $p ) : ?>
						<option
							value="<?php echo esc_url( $base_url . '&portfolio=' . $p->ID ); ?>"
							<?php selected( $portfolio_filter, $p->ID ); ?>
						>
							<?php echo esc_html( $p->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
			<a href="<?php echo esc_url( $new_url ); ?>" class="gfolio-header-action-btn">
				<span class="dashicons dashicons-plus-alt2"></span>
				<?php esc_html_e( 'New Item', 'g-folio' ); ?>
			</a>
		</div>
	</div>

	<?php if ( ! $items->have_posts() ) : ?>

		<!-- Empty state -->
		<div class="gfolio-empty-state">
			<span class="dashicons dashicons-grid-view gfolio-empty-icon"></span>
			<h2 class="gfolio-empty-heading"><?php esc_html_e( 'No items yet', 'g-folio' ); ?></h2>
			<p class="gfolio-empty-desc"><?php esc_html_e( 'Add your first portfolio item to get started.', 'g-folio' ); ?></p>
			<a href="<?php echo esc_url( $new_url ); ?>" class="gfolio-header-action-btn">
				<span class="dashicons dashicons-plus-alt2"></span>
				<?php esc_html_e( 'Add First Item', 'g-folio' ); ?>
			</a>
		</div>

	<?php else : ?>

		<!-- Items table -->
		<div class="gfolio-items-table-wrap">
			<table class="gfolio-items-table">
				<thead>
					<tr>
						<th class="gfit-col-thumb"><?php esc_html_e( 'Image', 'g-folio' ); ?></th>
						<th class="gfit-col-title"><?php esc_html_e( 'Title', 'g-folio' ); ?></th>
						<?php if ( ! $portfolio_filter ) : ?>
							<th class="gfit-col-portfolios"><?php esc_html_e( 'Portfolios', 'g-folio' ); ?></th>
						<?php endif; ?>
						<th class="gfit-col-cats"><?php esc_html_e( 'Categories', 'g-folio' ); ?></th>
						<th class="gfit-col-status"><?php esc_html_e( 'Status', 'g-folio' ); ?></th>
						<th class="gfit-col-actions"></th>
					</tr>
				</thead>
				<tbody>
					<?php while ( $items->have_posts() ) : $items->the_post(); ?>
						<?php
						$item_id      = get_the_ID();
						$edit_url     = get_edit_post_link( $item_id );
						$trash_url    = wp_nonce_url(
							admin_url( 'post.php?post=' . $item_id . '&action=trash' ),
							'trash-post_' . $item_id
						);
						$thumb_id     = get_post_meta( $item_id, '_gfolio_thumbnail_id', true );
						$thumb_url    = $thumb_id ? wp_get_attachment_image_url( (int) $thumb_id, 'thumbnail' ) : '';
						$port_ids     = get_post_meta( $item_id, '_gfolio_portfolio_ids', true );
						if ( ! is_array( $port_ids ) ) $port_ids = array();
						$terms        = wp_get_object_terms( $item_id, 'gfolio_category', array( 'fields' => 'names' ) );
						if ( is_wp_error( $terms ) ) $terms = array();
						$status       = get_post_status();
						?>
						<tr>

							<!-- Thumbnail -->
							<td class="gfit-col-thumb">
								<div class="gfit-thumb">
									<?php if ( $thumb_url ) : ?>
										<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" />
									<?php else : ?>
										<span class="dashicons dashicons-format-image gfit-thumb-placeholder"></span>
									<?php endif; ?>
								</div>
							</td>

							<!-- Title -->
							<td class="gfit-col-title">
								<a href="<?php echo esc_url( $edit_url ); ?>" class="gfit-title-link">
									<?php the_title(); ?>
								</a>
							</td>

							<!-- Portfolios (only shown when not filtered) -->
							<?php if ( ! $portfolio_filter ) : ?>
								<td class="gfit-col-portfolios">
									<?php if ( ! empty( $port_ids ) ) : ?>
										<?php foreach ( $port_ids as $pid ) :
											$pname = $portfolio_map[ (int) $pid ] ?? null;
											if ( ! $pname ) continue;
										?>
											<a href="<?php echo esc_url( $base_url . '&portfolio=' . $pid ); ?>" class="gfit-tag gfit-tag--portfolio">
												<?php echo esc_html( $pname ); ?>
											</a>
										<?php endforeach; ?>
									<?php else : ?>
										<span class="gfit-tag gfit-tag--none">—</span>
									<?php endif; ?>
								</td>
							<?php endif; ?>

							<!-- Categories -->
							<td class="gfit-col-cats">
								<?php if ( ! empty( $terms ) ) : ?>
									<?php foreach ( $terms as $t ) : ?>
										<span class="gfit-tag gfit-tag--cat"><?php echo esc_html( $t ); ?></span>
									<?php endforeach; ?>
								<?php else : ?>
									<span class="gfit-tag gfit-tag--none">—</span>
								<?php endif; ?>
							</td>

							<!-- Status -->
							<td class="gfit-col-status">
								<span class="gfit-status gfit-status--<?php echo esc_attr( $status ); ?>">
									<?php echo 'publish' === $status ? esc_html__( 'Published', 'g-folio' ) : esc_html__( 'Draft', 'g-folio' ); ?>
								</span>
							</td>

							<!-- Actions -->
							<td class="gfit-col-actions">
								<div class="gfit-actions">
									<a
										href="<?php echo esc_url( $edit_url ); ?>"
										class="gfit-action-btn gfit-action-btn--edit"
										title="<?php esc_attr_e( 'Edit', 'g-folio' ); ?>"
									>
										<span class="dashicons dashicons-edit"></span>
									</a>
									<a
										href="<?php echo esc_url( $trash_url ); ?>"
										class="gfit-action-btn gfit-action-btn--delete"
										title="<?php esc_attr_e( 'Move to trash', 'g-folio' ); ?>"
										onclick="return confirm( '<?php echo esc_js( __( 'Move this item to trash?', 'g-folio' ) ); ?>' )"
									>
										<span class="dashicons dashicons-trash"></span>
									</a>
								</div>
							</td>

						</tr>
					<?php endwhile; wp_reset_postdata(); ?>
				</tbody>
			</table>
		</div>

	<?php endif; ?>

</div>
