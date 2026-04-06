<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap cbp-admin-wrap">
    <h1>Cube Portfolio – Settings</h1>
    <hr class="wp-header-end">

    <?php if ( isset( $_GET['saved'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
    <?php endif; ?>
    <?php if ( isset( $_GET['error'] ) ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html( urldecode( $_GET['error'] ) ); ?></p></div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field( 'cbp_save_settings', 'cbp_nonce' ); ?>
        <input type="hidden" name="cbp_action" value="save_settings">

        <table class="form-table" role="presentation">

            <tr>
                <th scope="row"><label>Asset Preloading</label></th>
                <td>
                    <?php
                    $preload = $settings['preload'] ?? array( 'onPostsPage', 'onHomePage' );
                    $options = array(
                        'onAllPages'  => 'Load on all pages',
                        'onHomePage'  => 'Load on the home page',
                        'onPostsPage' => 'Load only on pages that use the [cubeportfolio] shortcode',
                    );
                    foreach ( $options as $val => $label ) : ?>
                        <label style="display:block;margin-bottom:6px">
                            <input type="checkbox" name="cbp_preload[]" value="<?php echo esc_attr( $val ); ?>"
                                   <?php checked( in_array( $val, $preload, true ) ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </label>
                    <?php endforeach; ?>
                    <p class="description">Controls when the CBP CSS/JS files are loaded on the front end.</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="cbp_post_type">Portfolio Post Type Slug</label></th>
                <td>
                    <input type="text" name="cbp_post_type" id="cbp_post_type" class="regular-text"
                           value="<?php echo esc_attr( $settings['postType'] ?? 'cubeportfolio' ); ?>">
                    <p class="description">
                        The custom post type used for portfolio project pages. Default: <code>cubeportfolio</code>.<br>
                        <strong>Warning:</strong> changing this will migrate all existing Cube Posts to the new slug.
                    </p>
                </td>
            </tr>

        </table>

        <?php submit_button( 'Save Settings' ); ?>
    </form>
</div>
