<?php
/**
 * Custom admin page: Portfolios list
 *
 * @package G_Folio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$portfolios = gfolio_get_all_portfolios();

// Item counts per portfolio
$item_counts = array();
foreach ( $portfolios as $p ) {
	$q = new WP_Query( array(
		'post_type'      => 'gfolio_item',
		'post_status'    => array( 'publish', 'draft' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array(
				'key'     => '_gfolio_portfolio_ids',
				'value'   => '"' . $p->ID . '"',
				'compare' => 'LIKE',
			),
		),
	) );
	$item_counts[ $p->ID ] = $q->found_posts;
}

$new_url = admin_url( 'post-new.php?post_type=gfolio_portfolio' );
?>
<div class="gfolio-settings-wrap">

	<!-- Header -->
	<div class="gfolio-settings-header">
		<div class="gfolio-settings-brand">
			<span class="dashicons dashicons-portfolio gfolio-brand-icon"></span>
			<div>
				<h1 class="gfolio-settings-title"><?php esc_html_e( 'Portfolios', 'g-folio' ); ?></h1>
				<p class="gfolio-settings-subtitle">
					<?php
					$count = count( $portfolios );
					echo esc_html( sprintf( _n( '%d portfolio', '%d portfolios', $count, 'g-folio' ), $count ) );
					?>
				</p>
			</div>
		</div>
		<a href="<?php echo esc_url( $new_url ); ?>" class="gfolio-header-action-btn">
			<span class="dashicons dashicons-plus-alt2"></span>
			<?php esc_html_e( 'New Portfolio', 'g-folio' ); ?>
		</a>
	</div>

	<?php if ( empty( $portfolios ) ) : ?>

		<!-- Empty state -->
		<div class="gfolio-empty-state">
			<span class="dashicons dashicons-portfolio gfolio-empty-icon"></span>
			<h2 class="gfolio-empty-heading"><?php esc_html_e( 'No portfolios yet', 'g-folio' ); ?></h2>
			<p class="gfolio-empty-desc"><?php esc_html_e( 'Create your first portfolio to start showcasing your work.', 'g-folio' ); ?></p>
			<a href="<?php echo esc_url( $new_url ); ?>" class="gfolio-header-action-btn">
				<span class="dashicons dashicons-plus-alt2"></span>
				<?php esc_html_e( 'Create Portfolio', 'g-folio' ); ?>
			</a>
		</div>

	<?php else : ?>

		<!-- Portfolio cards grid -->
		<div class="gfolio-portfolio-grid">
			<?php foreach ( $portfolios as $portfolio ) :
				$edit_url   = get_edit_post_link( $portfolio->ID );
				$trash_url  = wp_nonce_url(
					admin_url( 'post.php?post=' . $portfolio->ID . '&action=trash' ),
					'trash-post_' . $portfolio->ID
				);
				$items_url  = admin_url( 'admin.php?page=gfolio-items&portfolio=' . $portfolio->ID );
				$shortcode  = '[gfolio id="' . $portfolio->ID . '"]';
				$item_count = $item_counts[ $portfolio->ID ] ?? 0;
			?>
				<div class="gfolio-pcard">

					<div class="gfolio-pcard-top">
						<div class="gfolio-pcard-icon-wrap">
							<span class="dashicons dashicons-images-alt2"></span>
						</div>
						<div class="gfolio-pcard-info">
							<h3 class="gfolio-pcard-name"><?php echo esc_html( $portfolio->post_title ); ?></h3>
							<span class="gfolio-pcard-count">
								<?php echo esc_html( sprintf( _n( '%d item', '%d items', $item_count, 'g-folio' ), $item_count ) ); ?>
							</span>
						</div>
					</div>

					<div class="gfolio-pcard-shortcode-row">
						<code class="gfolio-pcard-code"><?php echo esc_html( $shortcode ); ?></code>
						<button
							type="button"
							class="gfolio-copy-btn"
							data-copy="<?php echo esc_attr( $shortcode ); ?>"
							title="<?php esc_attr_e( 'Copy shortcode', 'g-folio' ); ?>"
						>
							<span class="dashicons dashicons-clipboard"></span>
						</button>
					</div>

					<div class="gfolio-pcard-actions">
						<a href="<?php echo esc_url( $edit_url ); ?>" class="gfolio-pcard-action gfolio-pcard-action--edit">
							<span class="dashicons dashicons-edit"></span>
							<?php esc_html_e( 'Settings', 'g-folio' ); ?>
						</a>
						<a href="<?php echo esc_url( $items_url ); ?>" class="gfolio-pcard-action gfolio-pcard-action--items">
							<span class="dashicons dashicons-grid-view"></span>
							<?php esc_html_e( 'Items', 'g-folio' ); ?>
						</a>
						<a
							href="<?php echo esc_url( $trash_url ); ?>"
							class="gfolio-pcard-action gfolio-pcard-action--delete"
							title="<?php esc_attr_e( 'Move to trash', 'g-folio' ); ?>"
							onclick="return confirm( '<?php echo esc_js( __( 'Move this portfolio to trash?', 'g-folio' ) ); ?>' )"
						>
							<span class="dashicons dashicons-trash"></span>
						</a>
					</div>

				</div>
			<?php endforeach; ?>
		</div>

	<?php endif; ?>

</div>
