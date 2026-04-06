<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
    <h1>Cube Portfolio — Import / Export</h1>

    <?php if ( isset( $_GET['imported'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p>Import complete.</p></div>
    <?php endif; ?>
    <?php if ( isset( $_GET['import_error'] ) ) : ?>
        <div class="notice notice-error"><p><?php echo esc_html( urldecode( $_GET['import_error'] ) ); ?></p></div>
    <?php endif; ?>

    <h2>Export</h2>
    <p>Download all portfolio data as a JSON file.</p>
    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cubeportfolio&action=export' ), 'cbp_export' ) ); ?>"
       class="button button-primary">Download Export File</a>

    <hr style="margin:30px 0">

    <h2>Import</h2>
    <p>Upload a previously exported <code>.json</code> file to restore portfolio data.</p>
    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field( 'cbp_import', 'cbp_nonce' ); ?>
        <input type="hidden" name="cbp_action" value="import">
        <input type="file" name="cbp_import_file" accept=".json" required style="margin-right:10px">
        <?php submit_button( 'Import', 'primary', 'submit', false ); ?>
    </form>
</div>
