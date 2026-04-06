<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
    <h1>Cube Portfolio — Settings</h1>
    <?php if ( isset( $_GET['saved'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field( 'cbp_save_settings', 'cbp_nonce' ); ?>
        <input type="hidden" name="cbp_action" value="save_settings">

        <table class="form-table">
            <tr>
                <th><label>Preload Assets</label></th>
                <td>
                    <?php
                    $preload = $settings['preload'] ?? array();
                    $options = array(
                        'onAllPages'  => 'On all pages',
                        'onHomePage'  => 'On home page',
                        'onPostsPage' => 'On pages that contain a [cubeportfolio] shortcode',
                    );
                    foreach ( $options as $val => $label ) :
                    ?>
                        <label style="display:block;margin-bottom:6px">
                            <input type="checkbox" name="cbp_preload[]" value="<?php echo esc_attr( $val ); ?>"
                                <?php checked( in_array( $val, $preload, true ) ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <th><label for="cbp_post_type">Custom Post Type Slug</label></th>
                <td>
                    <input type="text" id="cbp_post_type" name="cbp_post_type" class="regular-text"
                           value="<?php echo esc_attr( $settings['postType'] ?? 'cubeportfolio' ); ?>">
                    <p class="description">The slug used for the Cube Portfolio custom post type.</p>
                </td>
            </tr>
        </table>

        <?php submit_button( 'Save Settings' ); ?>
    </form>
</div>
