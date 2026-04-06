<?php
/**
 * CBP_Import – imports portfolios from a JSON file uploaded via AJAX.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CBP_Import {

    /** @var wpdb */
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;

        // Must be an XHR request.
        if ( empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) {
            echo 0; die();
        }

        // Validate the uploaded file.
        if ( ! isset( $_FILES['file'] ) || (int) $_FILES['file']['error'] > 0 ) { // phpcs:ignore
            echo 0; die();
        }

        $ext = strtolower( pathinfo( $_FILES['file']['name'], PATHINFO_EXTENSION ) ); // phpcs:ignore
        if ( 'json' !== $ext ) {
            echo 0; die();
        }

        $raw  = file_get_contents( $_FILES['file']['tmp_name'] ); // phpcs:ignore
        $data = json_decode( $raw, true );

        if ( ! $data ) {
            echo 0; die();
        }

        // Process and insert.
        $cbp_items = $this->process_items( $data['cbp_items'] );
        $cbp       = $this->process_cbp( $data['cbp'] );

        update_option( 'cubeportfolio_settings', $data['settings'] );

        foreach ( $cbp as $record ) {
            $exists = $this->wpdb->get_var(
                'SELECT id FROM ' . CBP_Main::$table_cbp . ' WHERE id = ' . (int) $record['id']
            );
            if ( ! $exists ) {
                $this->wpdb->insert( CBP_Main::$table_cbp, $record );
            }
        }

        foreach ( $cbp_items as $item ) {
            $exists = $this->wpdb->get_var(
                'SELECT id FROM ' . CBP_Main::$table_cbp_items . ' WHERE id = ' . (int) $item['id']
            );
            if ( ! $exists ) {
                $this->wpdb->insert( CBP_Main::$table_cbp_items, $item );
            }
        }

        // Backwards-compat migration for files exported from v<1.5.
        require_once CBP_PATH . 'php/deprecated/class-version-150.php';
        new CBP_Version150();

        echo 1;
        die();
    }

    private function process_items( array $items ): array {
        $home = get_home_url();

        foreach ( $items as $key => $item ) {
            preg_match_all( '/\{\{post_id (.*?)\}\}/', $item['items'], $matches );

            if ( ! empty( $matches[0] ) ) {
                foreach ( $matches[1] as $k => $post_id ) {
                    $post = get_post( (int) $post_id );
                    $url  = '';

                    if ( $post && 'attachment' === $post->post_type ) {
                        $url = wp_get_attachment_url( $post_id );
                    } elseif ( $post ) {
                        $url = $this->is_custom_post_type( $post )
                            ? get_post_permalink( $post_id )
                            : get_permalink( $post_id );
                    }

                    if ( ! $url ) {
                        $url = 'https://via.placeholder.com/400x300/' . $this->string_to_color( $post_id );
                    }

                    $matches[1][ $k ] = $url;
                }

                $items[ $key ]['items'] = str_replace( $matches[0], $matches[1], $item['items'] );
                $items[ $key ]['items'] = str_replace( '{{home_url}}', $home, $items[ $key ]['items'] );
            }
        }

        return $items;
    }

    private function process_cbp( array $cbp ): array {
        $home = get_home_url();

        foreach ( $cbp as $key => $portfolio ) {
            $popup = $portfolio['popup'] ?? '';
            preg_match_all( '/\{\{post_id (.*?)\}\}/', $popup, $matches );

            if ( ! empty( $matches[0] ) ) {
                foreach ( $matches[1] as $k => $post_id ) {
                    $post = get_post( (int) $post_id );
                    $url  = '';

                    if ( $post && 'attachment' === $post->post_type ) {
                        $url = wp_get_attachment_url( $post_id );
                    } elseif ( $post ) {
                        $url = $this->is_custom_post_type( $post )
                            ? get_post_permalink( $post_id )
                            : get_permalink( $post_id );
                    }

                    $matches[1][ $k ] = $url;
                }

                $cbp[ $key ]['popup'] = str_replace( $matches[0], $matches[1], $popup );
                $cbp[ $key ]['popup'] = str_replace( '{{home_url}}', $home, $cbp[ $key ]['popup'] );
            }
        }

        return $cbp;
    }

    private function is_custom_post_type( $post ): bool {
        $custom_types = array_keys( get_post_types( array( '_builtin' => false ) ) );
        if ( empty( $custom_types ) ) {
            return false;
        }
        $type = get_post_type( $post );
        return $type && in_array( $type, $custom_types, true );
    }

    private function string_to_color( string $str ): string {
        return substr( dechex( crc32( $str ) ), 0, 6 );
    }
}
