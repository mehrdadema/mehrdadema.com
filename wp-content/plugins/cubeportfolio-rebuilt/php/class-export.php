<?php
/**
 * CBP_Export – streams portfolios and items as a JSON file download.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CBP_Export {

    /** @var wpdb */
    private $wpdb;

    /** Mapping href → {{post_id N}} / {{home_url}} built during item parse. */
    private $link_href = array();
    private $link_id   = array();

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;

        $cbp   = $this->wpdb->get_results( 'SELECT * FROM ' . CBP_Main::$table_cbp );
        $items = $this->wpdb->get_results( 'SELECT * FROM ' . CBP_Main::$table_cbp_items );

        $result = array(
            'cbp_items' => $this->parse_items( $items ),
            'cbp'       => $this->parse_cbp( $cbp ),
            'settings'  => get_option( 'cubeportfolio_settings' ),
        );

        $filename = 'cubeportfolio-' . gmdate( 'Y-m-d-H-i-s' ) . '.json';

        header( 'Content-Type: application/json' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        echo json_encode( $result );
        die();
    }

    private function parse_items( array $items ): array {
        $dom = new DomDocument();

        foreach ( $items as $key => $item ) {
            $html = $item->items;
            @$dom->loadHTML( $html ); // phpcs:ignore
            $xpath = new DOMXpath( $dom );

            $html = $this->replace_img_srcs( $xpath->query( '//img' ), $html );
            $html = $this->replace_hrefs( $xpath->query( '//a' ),      $html );

            $items[ $key ]->items = $html;
        }

        return $items;
    }

    private function parse_cbp( array $cbp ): array {
        foreach ( $cbp as $key => $portfolio ) {
            $cbp[ $key ]->popup = str_replace( $this->link_href, $this->link_id, $portfolio->popup );
        }
        return $cbp;
    }

    private function replace_img_srcs( $nodes, string $html ): string {
        $old = array();
        $new = array();

        for ( $i = 0; $i < $nodes->length; $i++ ) {
            $img = $nodes->item( $i );
            $src = $img->getAttribute( 'data-cbp-src' ) ?: $img->getAttribute( 'src' );
            $id  = $this->attachment_id_from_url( $src );

            if ( $id > 0 ) {
                $old[] = $src;
                $new[] = '{{post_id ' . $id . '}}';
            }
        }

        return str_replace( $old, $new, $html );
    }

    private function replace_hrefs( $nodes, string $html ): string {
        for ( $i = 0; $i < $nodes->length; $i++ ) {
            $href = $nodes->item( $i )->getAttribute( 'href' );
            $id   = $this->resolve_href( $href );

            if ( $id > 0 ) {
                $this->link_href[] = $href;
                $this->link_id[]   = '{{post_id ' . $id . '}}';
            } elseif ( is_string( $id ) ) {
                $this->link_href[] = $href;
                $this->link_id[]   = $id;
            }
        }

        return str_replace( $this->link_href, $this->link_id, $html );
    }

    private function attachment_id_from_url( string $src ): int {
        if ( '' === $src ) {
            return 0;
        }

        $upload = wp_upload_dir();

        if ( strpos( $src, $upload['baseurl'] ) === false ) {
            return 0;
        }

        // Strip thumbnail suffix, then strip base URL.
        $src = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $src );
        $src = str_replace( $upload['baseurl'] . '/', '', $src );

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT wposts.ID
                 FROM {$this->wpdb->posts} wposts
                 INNER JOIN {$this->wpdb->postmeta} wpostmeta ON wposts.ID = wpostmeta.post_id
                 WHERE wpostmeta.meta_key = '_wp_attached_file'
                   AND wpostmeta.meta_value = %s
                   AND wposts.post_type = 'attachment'",
                $src
            )
        );
    }

    private function resolve_href( string $href ) {
        if ( '' === $href ) {
            return false;
        }

        $id = url_to_postid( $href );

        if ( 0 === $id ) {
            $id = $this->attachment_id_from_url( $href );
        }

        if ( 0 === $id ) {
            $home = get_home_url();
            if ( strpos( $href, $home ) !== false ) {
                return str_replace( $home, '{{home_url}}', $href );
            }
        }

        return $id;
    }
}
