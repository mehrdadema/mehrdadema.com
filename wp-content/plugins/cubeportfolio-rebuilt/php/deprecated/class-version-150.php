<?php
/**
 * Migration: v1.5.0 – rename cbp-l-loadMore-button- to cbp-l-loadMore-.
 *
 * @deprecated Keep for backwards-compat with databases from older installs.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CBP_Version150 {

    public function __construct() {
        global $wpdb;

        $items = $wpdb->get_results( 'SELECT id, items FROM ' . CBP_Main::$table_cbp_items );

        foreach ( $items as $item ) {
            $updated = str_replace(
                'cbp-l-loadMore-button-',
                'cbp-l-loadMore-',
                $item->items
            );

            if ( $updated !== $item->items ) {
                $wpdb->update(
                    CBP_Main::$table_cbp_items,
                    array( 'items' => $updated ),
                    array( 'id' => $item->id )
                );
            }
        }

        // Fix loadMorehtml column too.
        $portfolios = $wpdb->get_results( 'SELECT id, loadMorehtml FROM ' . CBP_Main::$table_cbp );

        foreach ( $portfolios as $portfolio ) {
            $updated = str_replace(
                'cbp-l-loadMore-button-',
                'cbp-l-loadMore-',
                $portfolio->loadMorehtml
            );

            if ( $updated !== $portfolio->loadMorehtml ) {
                $wpdb->update(
                    CBP_Main::$table_cbp,
                    array( 'loadMorehtml' => $updated ),
                    array( 'id' => $portfolio->id )
                );
            }
        }
    }
}
