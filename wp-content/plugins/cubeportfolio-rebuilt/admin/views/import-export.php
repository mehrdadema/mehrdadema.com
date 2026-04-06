<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap cbp-admin-wrap">
    <h1>Cube Portfolio – Import / Export</h1>
    <hr class="wp-header-end">

    <?php if ( isset( $_GET['imported'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p>Portfolios imported successfully.</p></div>
    <?php endif; ?>
    <?php if ( isset( $_GET['import_error'] ) ) : ?>
        <div class="notice notice-error is-dismissible"><p>Import failed: <?php echo esc_html( urldecode( $_GET['import_error'] ) ); ?></p></div>
    <?php endif; ?>

    <div style="max-width:720px">

        <!-- Export -->
        <div class="postbox">
            <div class="postbox-header"><h2>Export</h2></div>
            <div class="inside">
                <p>Download all your Cube Portfolio data (portfolios, items, and settings) as a JSON file. Use this to back up your portfolios or migrate them to another site.</p>
                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cubeportfolio-import-export&action=export' ), 'cbp_export' ) ); ?>"
                   class="button button-primary">
                    ⬇ Download Export File
                </a>
            </div>
        </div>

        <!-- Import -->
        <div class="postbox">
            <div class="postbox-header"><h2>Import</h2></div>
            <div class="inside">
                <p>Upload a JSON file previously exported from Cube Portfolio. <strong>This will not overwrite existing portfolios</strong> — it only adds records that don't already exist in the database.</p>
                <form method="post" enctype="multipart/form-data" action="">
                    <?php wp_nonce_field( 'cbp_import', 'cbp_nonce' ); ?>
                    <input type="hidden" name="cbp_action" value="import">
                    <table class="form-table">
                        <tr>
                            <th><label for="cbp_import_file">JSON File</label></th>
                            <td><input type="file" name="cbp_import_file" id="cbp_import_file" accept=".json"></td>
                        </tr>
                    </table>
                    <?php submit_button( 'Import', 'primary', 'submit', false ); ?>
                </form>
            </div>
        </div>

    </div>
</div>
