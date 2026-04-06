<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
    <h1 class="wp-heading-inline">Cube Portfolio</h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=cubeportfolio&action=new' ) ); ?>" class="page-title-action">Add New</a>
    <hr class="wp-header-end">

    <?php if ( isset( $_GET['saved'] ) )   : ?><div class="notice notice-success is-dismissible"><p>Portfolio saved.</p></div><?php endif; ?>
    <?php if ( isset( $_GET['deleted'] ) ) : ?><div class="notice notice-success is-dismissible"><p>Portfolio deleted.</p></div><?php endif; ?>
    <?php if ( isset( $_GET['cloned'] ) )  : ?><div class="notice notice-success is-dismissible"><p>Portfolio cloned.</p></div><?php endif; ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:50px">ID</th>
                <th>Name</th>
                <th>Shortcode</th>
                <th style="width:90px">Status</th>
                <th style="width:220px">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ( empty( $portfolios ) ) : ?>
            <tr><td colspan="5">No portfolios yet. <a href="<?php echo esc_url( admin_url( 'admin.php?page=cubeportfolio&action=new' ) ); ?>">Create one.</a></td></tr>
        <?php else : foreach ( $portfolios as $p ) : ?>
            <tr>
                <td><?php echo (int) $p->id; ?></td>
                <td><strong><?php echo esc_html( $p->name ); ?></strong></td>
                <td><code>[cubeportfolio id=<?php echo (int) $p->id; ?>]</code></td>
                <td><?php echo $p->active ? '<span style="color:#00a32a">●&nbsp;Active</span>' : '<span style="color:#999">○&nbsp;Inactive</span>'; ?></td>
                <td>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=cubeportfolio&action=edit&id=' . $p->id ) ); ?>" class="button button-small">Edit</a>
                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cubeportfolio&action=clone&id=' . $p->id ), 'cbp_clone_' . $p->id ) ); ?>" class="button button-small">Clone</a>
                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cubeportfolio&action=toggle&id=' . $p->id ), 'cbp_toggle_' . $p->id ) ); ?>" class="button button-small"><?php echo $p->active ? 'Deactivate' : 'Activate'; ?></a>
                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cubeportfolio&action=delete&id=' . $p->id ), 'cbp_delete_' . $p->id ) ); ?>" class="button button-small" style="color:#b32d2e" onclick="return confirm('Delete this portfolio?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
