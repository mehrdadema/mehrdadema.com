<?php
/**
 * Migration: v1.13.0 – change .cbp-filter-counter:before to :after in customcss.
 *
 * @deprecated Keep for backwards-compat with databases from older installs.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CBP_Version1130 {

    public function __construct() {
        global $wpdb;

        $portfolios = $wpdb->get_results( 'SELECT id, customcss FROM ' . CBP_Main::$table_cbp );

        foreach ( $portfolios as $portfolio ) {
            $updated = str_replace(
                '.cbp-filter-counter:before',
                '.cbp-filter-counter:after',
                $portfolio->customcss
            );

            if ( $updated !== $portfolio->customcss ) {
                $wpdb->update(
                    CBP_Main::$table_cbp,
                    array( 'customcss' => $updated ),
                    array( 'id' => $portfolio->id )
                );
            }
        }
    }
}
