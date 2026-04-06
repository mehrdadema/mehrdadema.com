<?php
/**
 * CBP_Backend – PHP-rendered admin panel, meta boxes, and AJAX endpoints.
 *
 * The admin interface is fully PHP-rendered (no external SPA dependency).
 * Legacy AJAX action names are preserved so any existing JS integrations
 * continue to work unchanged.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CBP_Backend {

    /** @var wpdb */
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;

        // ── Admin menu ───────────────────────────────────────────────────────
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );

        // ── Admin POST / GET handler ─────────────────────────────────────────
        add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );

        // ── Meta boxes ───────────────────────────────────────────────────────
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post',      array( $this, 'save_meta_boxes' ), 10, 2 );

        // ── Assets ───────────────────────────────────────────────────────────
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // ── Legacy AJAX endpoints (kept for API/JS compatibility) ─────────────
        $admin_actions = array(
            'listPortfolios'        => 'ajax_list_portfolios',
            'deletePortfolio'       => 'ajax_delete_portfolio',
            'clonePortfolio'        => 'ajax_clone_portfolio',
            'toggleActivePortfolio' => 'ajax_toggle_active',
            'getPortfolioById'      => 'ajax_get_by_id',
            'getSettings'           => 'ajax_get_settings',
            'updateSettings'        => 'ajax_update_settings',
            'savePortfolio'         => 'ajax_save_portfolio',
            'editPortfolio'         => 'ajax_edit_portfolio',
            'setPopupTypeAdmin'     => 'ajax_set_popup_type',
            'exportCubePosts'       => 'ajax_export',
            'importCubePosts'       => 'ajax_import',
        );
        foreach ( $admin_actions as $action => $method ) {
            add_action( 'wp_ajax_' . $action, array( $this, $method ) );
        }

        // Public load-more (works for logged-out visitors too).
        add_action( 'wp_ajax_getLoadMoreItems',        array( $this, 'ajax_get_load_more' ) );
        add_action( 'wp_ajax_nopriv_getLoadMoreItems', array( $this, 'ajax_get_load_more' ) );
    }

    // ── Admin menu ───────────────────────────────────────────────────────────

    public function register_admin_menu() {
        add_menu_page(
            'Cube Portfolio',
            'Cube Portfolio',
            'publish_posts',
            'cubeportfolio',
            array( $this, 'page_portfolio_list' ),
            CBP_URL . 'admin/img/icon.png'
        );
        add_submenu_page( 'cubeportfolio', 'Edit Portfolio',  'Edit Portfolio',  'publish_posts',  'cubeportfolio',                  array( $this, 'page_portfolio_list' ) );
        add_submenu_page( 'cubeportfolio', 'Settings',        'Settings',        'manage_options', 'cubeportfolio-settings',         array( $this, 'page_settings' ) );
        add_submenu_page( 'cubeportfolio', 'Import / Export', 'Import / Export', 'manage_options', 'cubeportfolio-import-export',    array( $this, 'page_import_export' ) );
    }

    // ── Page controllers ─────────────────────────────────────────────────────

    /** Portfolio list / edit router. */
    public function page_portfolio_list() {
        $action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';
        $id     = isset( $_GET['id'] )     ? (int) $_GET['id']               : 0;

        if ( in_array( $action, array( 'edit', 'new' ), true ) ) {
            $portfolio = ( $action === 'edit' && $id ) ? $this->get_portfolio_for_edit( $id ) : null;
            include CBP_PATH . 'admin/views/portfolio-edit.php';
        } else {
            $portfolios = $this->wpdb->get_results( 'SELECT * FROM ' . CBP_Main::$table_cbp . ' ORDER BY id' );
            include CBP_PATH . 'admin/views/portfolio-list.php';
        }
    }

    public function page_settings() {
        $settings = CBP_Main::$settings;
        include CBP_PATH . 'admin/views/settings.php';
    }

    public function page_import_export() {
        include CBP_PATH . 'admin/views/import-export.php';
    }

    // ── Admin action handler (form POST + redirect) ──────────────────────────

    public function handle_admin_actions() {
        if ( ! isset( $_REQUEST['cbp_action'] ) ) {
            // Handle GET-based actions (delete, clone, toggle) with nonce.
            $get_action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
            $id         = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

            if ( ! $id || ! in_array( $get_action, array( 'delete', 'clone', 'toggle', 'export' ), true ) ) {
                return;
            }

            switch ( $get_action ) {
                case 'delete':
                    check_admin_referer( 'cbp_delete_' . $id );
                    $this->wpdb->delete( CBP_Main::$table_cbp,       array( 'id' => $id ), array( '%d' ) );
                    $this->wpdb->delete( CBP_Main::$table_cbp_items, array( 'cubeportfolio_id' => $id ), array( '%d' ) );
                    wp_redirect( admin_url( 'admin.php?page=cubeportfolio&deleted=1' ) );
                    exit;

                case 'clone':
                    check_admin_referer( 'cbp_clone_' . $id );
                    $this->clone_portfolio( $id );
                    wp_redirect( admin_url( 'admin.php?page=cubeportfolio&cloned=1' ) );
                    exit;

                case 'toggle':
                    check_admin_referer( 'cbp_toggle_' . $id );
                    $current = (int) $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT active FROM ' . CBP_Main::$table_cbp . ' WHERE id = %d', $id ) );
                    $this->wpdb->update( CBP_Main::$table_cbp, array( 'active' => $current ? 0 : 1 ), array( 'id' => $id ) );
                    wp_redirect( admin_url( 'admin.php?page=cubeportfolio' ) );
                    exit;

                case 'export':
                    check_admin_referer( 'cbp_export' );
                    require_once CBP_PATH . 'php/class-export.php';
                    new CBP_Export(); // exits after sending file.
            }
            return;
        }

        $cbp_action = sanitize_key( $_REQUEST['cbp_action'] );

        switch ( $cbp_action ) {

            case 'save_portfolio':
                check_admin_referer( 'cbp_save_portfolio', 'cbp_nonce' );
                if ( ! current_user_can( 'publish_posts' ) ) {
                    wp_die( 'Permission denied.' );
                }
                $pid = $this->handle_save_portfolio();
                wp_redirect( admin_url( 'admin.php?page=cubeportfolio&action=edit&id=' . $pid . '&saved=1' ) );
                exit;

            case 'save_settings':
                check_admin_referer( 'cbp_save_settings', 'cbp_nonce' );
                if ( ! current_user_can( 'manage_options' ) ) {
                    wp_die( 'Permission denied.' );
                }
                $error = $this->handle_save_settings();
                if ( $error ) {
                    wp_redirect( admin_url( 'admin.php?page=cubeportfolio-settings&error=' . urlencode( $error ) ) );
                } else {
                    wp_redirect( admin_url( 'admin.php?page=cubeportfolio-settings&saved=1' ) );
                }
                exit;

            case 'import':
                check_admin_referer( 'cbp_import', 'cbp_nonce' );
                if ( ! current_user_can( 'manage_options' ) ) {
                    wp_die( 'Permission denied.' );
                }
                $error = $this->handle_import();
                if ( $error ) {
                    wp_redirect( admin_url( 'admin.php?page=cubeportfolio-import-export&import_error=' . urlencode( $error ) ) );
                } else {
                    wp_redirect( admin_url( 'admin.php?page=cubeportfolio-import-export&imported=1' ) );
                }
                exit;
        }
    }

    // ── Form handlers ────────────────────────────────────────────────────────

    private function handle_save_portfolio(): int {
        $pid  = (int) ( $_POST['cbp_portfolio_id'] ?? 0 );
        $name = sanitize_text_field( $_POST['cbp_name'] ?? '' );

        // CSS: stored as JSON array of lines. Accept either cbp_customcss_text (new visual editor)
        // or the legacy cbp_customcss / cbp_customcss_raw fields.
        if ( isset( $_POST['cbp_customcss_text'] ) ) {
            $css_lines = array_map( 'trim', explode( "\n", wp_unslash( $_POST['cbp_customcss_text'] ) ) );
            $customcss = wp_json_encode( $css_lines );
        } else {
            $css_raw = $_POST['cbp_customcss_raw'] ?? '';
            if ( '' !== $css_raw ) {
                $decoded   = json_decode( stripslashes( $css_raw ), true );
                $customcss = $decoded ? wp_json_encode( $decoded ) : wp_json_encode( array() );
            } else {
                $css_lines = array_map( 'trim', explode( "\n", wp_unslash( $_POST['cbp_customcss'] ?? '' ) ) );
                $customcss = wp_json_encode( $css_lines );
            }
        }

        $data = array(
            'name'         => $name ?: ( $pid ? 'Untitled Portfolio #' . $pid : 'Untitled Portfolio' ),
            'type'         => sanitize_text_field( $_POST['cbp_type'] ?? 'mosaic' ),
            'customcss'    => $customcss,
            'options'      => wp_unslash( $_POST['cbp_options']      ?? '{}' ),
            'template'     => wp_unslash( $_POST['cbp_template']     ?? '' ),
            'filtershtml'  => wp_unslash( $_POST['cbp_filtershtml']  ?? '' ),
            'loadMorehtml' => wp_unslash( $_POST['cbp_loadMorehtml'] ?? '' ),
            'googlefonts'  => wp_unslash( $_POST['cbp_googlefonts']  ?? '[]' ),
            'jsondata'     => wp_unslash( $_POST['cbp_jsondata']      ?? '{}' ),
        );

        if ( $pid ) {
            // When editing, preserve existing popup configuration and active state so
            // saving the form never destroys lightbox/popup settings or reactivates a
            // portfolio that was deliberately deactivated.
            $existing = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    'SELECT popup, active FROM ' . CBP_Main::$table_cbp . ' WHERE id = %d',
                    $pid
                ),
                ARRAY_A
            );
            $data['popup']  = $existing['popup']  ?? '[]';
            $data['active'] = (int) ( $existing['active'] ?? 1 );

            // Update existing.
            $this->wpdb->update( CBP_Main::$table_cbp, $data, array( 'id' => $pid ) );
        } else {
            // New portfolio: default popup to empty array, default active to 1.
            $data['popup']  = '[]';
            $data['active'] = 1;

            // Insert new.
            $this->wpdb->insert( CBP_Main::$table_cbp, $data );
            $pid = $this->wpdb->insert_id;

            // Patch placeholder IDs.
            $data = $this->patch_placeholder_ids( $data, 0, $pid );
            $this->wpdb->update( CBP_Main::$table_cbp, array(
                'name'        => $pid ? $data['name'] : 'Untitled Portfolio #' . $pid,
                'filtershtml' => $data['filtershtml'],
                'customcss'   => $data['customcss'],
                'template'    => $data['template'],
                'options'     => $data['options'],
            ), array( 'id' => $pid ) );
        }

        // ── Sync items ───────────────────────────────────────────────────────
        // Accept JSON blob from visual editor (cbp_items_json) or legacy array (cbp_items).
        if ( ! empty( $_POST['cbp_items_json'] ) ) {
            $posted_items = json_decode( wp_unslash( $_POST['cbp_items_json'] ), true ) ?: array();
        } else {
            $posted_items = isset( $_POST['cbp_items'] ) ? (array) $_POST['cbp_items'] : array();
        }

        // Fetch existing item IDs.
        $existing_ids = array_column(
            $this->wpdb->get_results( $this->wpdb->prepare( 'SELECT id FROM ' . CBP_Main::$table_cbp_items . ' WHERE cubeportfolio_id = %d', $pid ), ARRAY_A ),
            'id'
        );
        $seen_ids = array();

        foreach ( $posted_items as $idx => $item ) {
            $item_id     = isset( $item['id'] ) && $item['id'] !== '' ? (int) $item['id'] : 0;
            $item_data   = array(
                'cubeportfolio_id' => $pid,
                'sort'             => (int) $idx,
                'page'             => (int) ( $item['page'] ?? 0 ),
                'items'            => wp_unslash( $item['items'] ?? '' ),
                'isLoadMore'       => $item['isLoadMore'] ?? '0',
                'isSinglePage'     => $item['isSinglePage'] ?? '',
            );

            if ( $item_id && in_array( $item_id, $existing_ids, false ) ) {
                $this->wpdb->update( CBP_Main::$table_cbp_items, $item_data, array( 'id' => $item_id ) );
                $seen_ids[] = $item_id;
            } else {
                $this->wpdb->insert( CBP_Main::$table_cbp_items, $item_data );
                $seen_ids[] = $this->wpdb->insert_id;
            }
        }

        // Delete items that were removed.
        foreach ( $existing_ids as $eid ) {
            if ( ! in_array( (int) $eid, $seen_ids, true ) ) {
                $this->wpdb->delete( CBP_Main::$table_cbp_items, array( 'id' => $eid ) );
            }
        }

        return $pid;
    }

    private function handle_save_settings(): string {
        $preload   = array_map( 'sanitize_key', (array) ( $_POST['cbp_preload'] ?? array() ) );
        $post_type = sanitize_key( $_POST['cbp_post_type'] ?? 'cubeportfolio' );

        $settings = CBP_Main::$settings;

        if ( $settings['postType'] !== $post_type ) {
            if ( post_type_exists( $post_type ) ) {
                return 'The slug "' . $post_type . '" is already used by another post type.';
            }

            $query = new WP_Query( array( 'post_type' => $settings['postType'], 'posts_per_page' => -1 ) );
            $old_permalinks = array();

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    $pid              = get_the_ID();
                    $old_permalinks[] = get_permalink( $pid );
                    set_post_type( $pid, $post_type );
                }
                $settings['flush_rewrite_rules'] = true;
            }

            $this->update_item_links( $old_permalinks, $settings['postType'], $post_type );

            $this->wpdb->update(
                $this->wpdb->prefix . 'term_taxonomy',
                array( 'taxonomy' => $post_type . '_category' ),
                array( 'taxonomy' => $settings['postType'] . '_category' )
            );

            $settings['postType'] = $post_type;
        }

        $settings['preload'] = $preload;
        update_option( 'cubeportfolio_settings', $settings );

        return '';
    }

    private function handle_import(): string {
        if ( empty( $_FILES['cbp_import_file'] ) || (int) $_FILES['cbp_import_file']['error'] > 0 ) {
            return 'No file uploaded or upload error.';
        }
        $ext = strtolower( pathinfo( $_FILES['cbp_import_file']['name'], PATHINFO_EXTENSION ) );
        if ( 'json' !== $ext ) {
            return 'Only .json files are accepted.';
        }

        $raw  = file_get_contents( $_FILES['cbp_import_file']['tmp_name'] );
        $data = json_decode( $raw, true );
        if ( ! $data ) {
            return 'Invalid or empty JSON file.';
        }

        require_once CBP_PATH . 'php/class-import.php';
        // Simulate the AJAX import by directly calling the processor methods.
        // We reuse the import logic inline to avoid the XHR header check.
        $importer = new CBP_Import_Processor( $data );
        $importer->run();

        return '';
    }

    // ── DB helpers ────────────────────────────────────────────────────────────

    private function get_portfolio_for_edit( int $id ): ?array {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare( 'SELECT * FROM ' . CBP_Main::$table_cbp . ' WHERE id = %d', $id ),
            ARRAY_A
        );
        if ( ! $row ) {
            return null;
        }
        $row['items'] = $this->wpdb->get_results(
            $this->wpdb->prepare( 'SELECT * FROM ' . CBP_Main::$table_cbp_items . ' WHERE cubeportfolio_id = %d ORDER BY sort', $id ),
            ARRAY_A
        );
        return $row;
    }

    private function clone_portfolio( int $id ) {
        $record = $this->wpdb->get_row(
            $this->wpdb->prepare( 'SELECT * FROM ' . CBP_Main::$table_cbp . ' WHERE id = %d', $id ),
            ARRAY_A
        );
        if ( ! $record ) {
            return;
        }
        unset( $record['id'] );
        $record['name'] = 'Copy of ' . $record['name'];
        $this->wpdb->insert( CBP_Main::$table_cbp, $record );
        $new_id = $this->wpdb->insert_id;

        $record = $this->patch_placeholder_ids( $record, $id, $new_id );
        $this->wpdb->update( CBP_Main::$table_cbp, $record, array( 'id' => $new_id ) );

        $items = $this->wpdb->get_results(
            $this->wpdb->prepare( 'SELECT * FROM ' . CBP_Main::$table_cbp_items . ' WHERE cubeportfolio_id = %d', $id ),
            ARRAY_A
        );
        foreach ( $items as $item ) {
            unset( $item['id'] );
            $item['cubeportfolio_id'] = $new_id;
            $this->wpdb->insert( CBP_Main::$table_cbp_items, $item );
        }
    }

    private function patch_placeholder_ids( array $data, int $old, int $new ): array {
        foreach ( array( 'filters', 'grid', 'loadMore', 'wrap', 'singlePage' ) as $p ) {
            foreach ( array( 'filtershtml', 'template', 'customcss', 'options' ) as $f ) {
                if ( isset( $data[ $f ] ) ) {
                    $data[ $f ] = str_replace( 'cbpw-' . $p . $old, 'cbpw-' . $p . $new, $data[ $f ] );
                }
            }
        }
        return $data;
    }

    private function update_item_links( array $old_urls, string $old_type, string $new_type ) {
        $items = $this->wpdb->get_results( 'SELECT id, items FROM ' . CBP_Main::$table_cbp_items );
        $dom   = new DomDocument();
        foreach ( $items as $item ) {
            @$dom->loadHTML( $item->items ); // phpcs:ignore
            $xpath = new DOMXpath( $dom );
            $html  = $item->items;
            $links = $xpath->query( '//a' );
            for ( $i = 0; $i < $links->length; $i++ ) {
                $href = $links->item( $i )->getAttribute( 'href' );
                if ( in_array( $href, $old_urls, true ) ) {
                    $html = str_replace( $href, str_replace( '/' . $old_type . '/', '/' . $new_type . '/', $href ), $html );
                }
            }
            $this->wpdb->update( CBP_Main::$table_cbp_items, array( 'items' => $html ), array( 'id' => $item->id ) );
        }
    }

    // ── Assets ───────────────────────────────────────────────────────────────

    public function enqueue_assets( $hook ) {
        // Only load on our plugin pages.
        $our_pages = array(
            'toplevel_page_cubeportfolio',
            'cube-portfolio_page_cubeportfolio-settings',
            'cube-portfolio_page_cubeportfolio-import-export',
        );
        if ( in_array( $hook, $our_pages, true ) ) {
            wp_enqueue_style( 'cbp-admin-ui', CBP_URL . 'admin/css/admin-ui.css', array(), CBP_VERSION );
            wp_enqueue_script( 'jquery-ui-sortable' );
        }

        // Meta box assets for the CBP custom post type editor.
        if ( get_post_type() === CBP_Main::$settings['postType'] ) {
            wp_enqueue_media();
            wp_enqueue_style( 'wp-jquery-ui-dialog' );
            wp_register_script(
                'cbp-meta-box-image',
                CBP_URL . 'public/js/meta-box-image.js',
                array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-sortable', 'jquery-ui-dialog', 'jquery-ui-tabs' ),
                CBP_VERSION,
                true
            );
            wp_enqueue_script( 'cbp-meta-box-image' );
            wp_enqueue_style( 'cubeportfolio-admin-css', CBP_URL . 'admin/css/main.min.css', array(), CBP_VERSION );
        }
    }

    // ── Legacy AJAX endpoints ────────────────────────────────────────────────

    private function check_ajax_cap() {
        if ( ! current_user_can( 'publish_posts' ) ) {
            wp_die( -1 );
        }
    }

    public function ajax_list_portfolios() {
        $this->check_ajax_cap();
        wp_send_json( $this->wpdb->get_results( 'SELECT * FROM ' . CBP_Main::$table_cbp . ' ORDER BY id' ) );
    }

    public function ajax_delete_portfolio() {
        $this->check_ajax_cap();
        $id = (int) $_POST['id']; // phpcs:ignore
        $this->wpdb->delete( CBP_Main::$table_cbp,       array( 'id' => $id ), array( '%d' ) );
        $this->wpdb->delete( CBP_Main::$table_cbp_items, array( 'cubeportfolio_id' => $id ), array( '%d' ) );
        echo 1; exit;
    }

    public function ajax_clone_portfolio() {
        $this->check_ajax_cap();
        $id = (int) $_POST['id']; // phpcs:ignore
        $this->clone_portfolio( $id );
        $record = $this->wpdb->get_row( $this->wpdb->prepare( 'SELECT * FROM ' . CBP_Main::$table_cbp . ' ORDER BY id DESC LIMIT 1' ), ARRAY_A );
        $record['status'] = 1;
        wp_send_json( $record );
    }

    public function ajax_toggle_active() {
        $this->check_ajax_cap();
        $id     = (int) $_POST['id'];     // phpcs:ignore
        $active = (int) $_POST['active']; // phpcs:ignore
        $this->wpdb->update( CBP_Main::$table_cbp, array( 'active' => $active ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
        echo 1; exit;
    }

    public function ajax_get_by_id() {
        $this->check_ajax_cap();
        $id  = (int) $_POST['id']; // phpcs:ignore
        $cbp = $this->wpdb->get_row( $this->wpdb->prepare( 'SELECT * FROM ' . CBP_Main::$table_cbp . ' WHERE id = %d', $id ), ARRAY_A );
        $cbp['items'] = $this->wpdb->get_results( $this->wpdb->prepare( 'SELECT * FROM ' . CBP_Main::$table_cbp_items . ' WHERE cubeportfolio_id = %d ORDER BY sort', $id ), ARRAY_A );
        wp_send_json( $cbp );
    }

    public function ajax_get_settings() {
        $this->check_ajax_cap();
        wp_send_json( CBP_Main::$settings );
    }

    public function ajax_update_settings() {
        $this->check_ajax_cap();
        $error = $this->handle_save_settings_from_post( $_POST ); // phpcs:ignore
        echo $error ? 0 : 1; exit;
    }

    public function ajax_save_portfolio() {
        $this->check_ajax_cap();
        $_POST = stripslashes_deep( $_POST ); // phpcs:ignore
        $pid   = $this->handle_save_portfolio();
        wp_send_json( array( 'id' => $pid, 'status' => 1 ) );
    }

    public function ajax_edit_portfolio() {
        $this->check_ajax_cap();
        $_POST = stripslashes_deep( $_POST ); // phpcs:ignore
        $pid   = $this->handle_save_portfolio();
        wp_send_json( array( 'id' => $pid, 'status' => 1 ) );
    }

    public function ajax_set_popup_type() {
        $this->check_ajax_cap();
        $_POST = stripslashes_deep( $_POST ); // phpcs:ignore
        $id    = (int) $_POST['id'];          // phpcs:ignore
        $popup = $_POST['popup'];             // phpcs:ignore
        $this->wpdb->update( CBP_Main::$table_cbp_items, array( 'isSinglePage' => $popup ), array( 'id' => $id ) );
        print_r( $popup ); exit;
    }

    public function ajax_export() {
        require_once CBP_PATH . 'php/class-export.php';
        new CBP_Export();
    }

    public function ajax_import() {
        require_once CBP_PATH . 'php/class-import.php';
        new CBP_Import();
    }

    public function ajax_get_load_more() {
        $_POST  = stripslashes_deep( $_POST ); // phpcs:ignore
        $id     = (int) $_POST['id'];          // phpcs:ignore
        $limit  = (int) $_POST['limit'];       // phpcs:ignore
        $offset = (int) $_POST['offset'];      // phpcs:ignore
        $items  = $this->wpdb->get_results( $this->wpdb->prepare( 'SELECT * FROM ' . CBP_Main::$table_cbp_items . ' WHERE cubeportfolio_id = %d AND isLoadMore = %d ORDER BY sort LIMIT %d OFFSET %d', $id, 1, $limit, $offset ), ARRAY_A );
        $total  = (int) $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT COUNT(id) FROM ' . CBP_Main::$table_cbp_items . ' WHERE cubeportfolio_id = %d AND isLoadMore = %d', $id, 1 ) );
        wp_send_json( array( 'items' => $items, 'itemsRemain' => $total - ( $limit + $offset ) ) );
    }

    // ── Meta Boxes ───────────────────────────────────────────────────────────

    public function register_meta_boxes() {
        $pt = CBP_Main::$settings['postType'];
        add_meta_box( 'cbp_project_subtitle_meta_box',  __( 'Project Subtitle',       CBP_TEXTDOMAIN ), array( $this, 'meta_box_subtitle' ),  $pt, 'normal', 'high' );
        add_meta_box( 'cbp_project_page_attr_meta_box', __( 'Page Attributes',        CBP_TEXTDOMAIN ), array( $this, 'meta_box_page_attr' ), $pt, 'side',   'low' );
        add_meta_box( 'cbp_project_details_meta_box',   __( 'Project Details',        CBP_TEXTDOMAIN ), array( $this, 'meta_box_details' ),   $pt, 'side',   'low' );
        add_meta_box( 'cbp_project_link_meta_box',      __( 'Project Link',           CBP_TEXTDOMAIN ), array( $this, 'meta_box_link' ),      $pt, 'normal', 'high' );
        add_meta_box( 'cbp_project_social_meta_box',    __( 'Social Links',           CBP_TEXTDOMAIN ), array( $this, 'meta_box_social' ),    $pt, 'side',   'low' );
        add_meta_box( 'cbp_project_images_meta_box',    __( 'Add/Edit Project Media', CBP_TEXTDOMAIN ), array( $this, 'meta_box_images' ),    $pt, 'normal', 'high' );
    }

    public function meta_box_subtitle( $post ) {
        $val = get_post_meta( $post->ID, 'cbp_project_subtitle', true );
        echo '<table><tr><td width="200"><label for="cbp_project_subtitle">' . esc_html__( 'Subtitle', CBP_TEXTDOMAIN ) . '</label></td>';
        echo '<td><input type="text" size="60" name="cbp_project_subtitle" id="cbp_project_subtitle" value="' . esc_attr( $val ) . '"></td></tr></table>';
    }

    public function meta_box_page_attr( $post ) {
        $current   = get_post_meta( $post->ID, 'cbp_project_page_attr', true );
        $templates = array(
            'single-cbp-singlePage'       => __( 'SinglePage',       CBP_TEXTDOMAIN ),
            'single-cbp-singlePageInline' => __( 'SinglePageInline', CBP_TEXTDOMAIN ),
        );
        echo '<table><tr><td width="72"><label>' . esc_html__( 'Template', CBP_TEXTDOMAIN ) . '</label></td><td><select name="cbp_project_page_attr">';
        foreach ( $templates as $key => $label ) {
            echo '<option value="' . esc_attr( $key ) . '" ' . selected( $current, $key, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select></td></tr></table>';
    }

    public function meta_box_details( $post ) {
        $client = esc_html( get_post_meta( $post->ID, 'cbp_project_details_client', true ) );
        $date   = esc_html( get_post_meta( $post->ID, 'cbp_project_details_date',   true ) );
        echo '<table>
            <tr><td><label>' . esc_html__( 'Client Name', CBP_TEXTDOMAIN ) . '</label></td><td><input type="text" size="19" name="cbp_project_details_client" value="' . $client . '"></td></tr>
            <tr><td><label>' . esc_html__( 'Project Date', CBP_TEXTDOMAIN ) . '</label></td><td><input type="text" size="19" name="cbp_project_details_date" value="' . $date . '"></td></tr>
        </table>';
    }

    public function meta_box_link( $post ) {
        $link   = esc_html( get_post_meta( $post->ID, 'cbp_project_link',        true ) );
        $target = get_post_meta( $post->ID, 'cbp_project_link_target', true ) ?: '_blank';
        ?>
        <table>
            <tr><td width="200"><label><?php esc_html_e( 'Project Link', CBP_TEXTDOMAIN ); ?></label></td><td><input type="text" size="60" name="cbp_project_link" value="<?php echo $link; ?>"></td></tr>
            <tr><td><label><?php esc_html_e( 'Target', CBP_TEXTDOMAIN ); ?></label></td><td>
                <input type="radio" name="cbp_project_link_target" value="_blank" <?php checked( $target, '_blank' ); ?>> <label>Blank</label> &nbsp;
                <input type="radio" name="cbp_project_link_target" value="_self"  <?php checked( $target, '_self' ); ?>>  <label>Self</label>
            </td></tr>
        </table>
        <?php
    }

    public function meta_box_social( $post ) {
        $networks = array( 'fb' => 'Facebook', 'twitter' => 'Twitter', 'google' => 'Google+', 'pinterest' => 'Pinterest' );
        echo '<table>';
        foreach ( $networks as $key => $label ) {
            $val = get_post_meta( $post->ID, 'cbp_project_social_' . $key, true );
            echo '<tr><td><input type="checkbox" name="cbp_project_social_' . esc_attr( $key ) . '" ' . checked( $val, 'on', false ) . '></td><td><label>' . esc_html( $label ) . '</label></td></tr>';
        }
        echo '</table>';
    }

    public function meta_box_images( $post ) {
        $raw_images  = get_post_meta( $post->ID, 'cbp_project_images', true );
        $is_slider   = get_post_meta( $post->ID, 'cbp_project_images_slider',  true ) ?: 'on';
        $is_lightbox = get_post_meta( $post->ID, 'cbp_project_images_lightbox', true ) ?: 'on';

        $images = json_decode( $raw_images );
        if ( $images && count( $images ) ) {
            require_once CBP_PATH . 'php/class-process-slider-item.php';
            $arr = array();
            foreach ( $images as $img ) {
                if ( ! isset( $img->type ) ) $img->type = 'image';
                $obj   = new CBP_ProcessSliderItem( $img );
                $arr[] = array( 'url' => $obj->getURL(), 'id' => $img->id, 'type' => $img->type );
            }
            $raw_images = json_encode( $arr );
        } else {
            $raw_images = '';
        }
        ?>
        <input type="hidden" name="cbp_project_images" id="cbp_project_images" value='<?php echo esc_attr( $raw_images ); ?>'>
        <div class="meta-box-image-wrap">
            <div id="meta-box-image-cbpw"></div>
            <div id="meta-box-image-add-cbpw">
                <div class="meta-box-image-add-button-cbpw">add image</div>
                <div class="meta-box-image-add-video-cbpw">add video</div>
            </div>
        </div>
        <div id="modal-content" style="display:none;">
            <div><label>Video/audio link <input class="modal-content-input" type="text" name="" value=""></label></div>
            <div id="tabs">
                <ul><li><a href="#mc-1">YouTube</a></li><li><a href="#mc-2">Vimeo</a></li><li><a href="#mc-3">Ted</a></li><li><a href="#mc-4">SoundCloud</a></li><li><a href="#mc-5">Self-Hosted Audio</a></li><li><a href="#mc-6">Self-Hosted Video</a></li></ul>
                <div id="mc-1"><p>Format: <strong>https://www.youtube.com/watch?v=LLgC0ZzEj54</strong></p></div>
                <div id="mc-2"><p>Format: <strong>https://vimeo.com/24302498</strong></p></div>
                <div id="mc-3"><p>Format: <strong>http://www.ted.com/talks/…</strong></p></div>
                <div id="mc-4"><p>Copy the <code>src</code> from the SoundCloud embed iframe.</p></div>
                <div id="mc-5"><p>Format: <strong>http://example.com/audio.mp3</strong></p></div>
                <div id="mc-6"><p>Use | to separate formats: <strong>video.mp4|video.ogg</strong></p></div>
            </div>
            <script>jQuery(function(){ jQuery("#tabs").tabs(); });</script>
        </div>
        <br>
        <div><input type="checkbox" name="cbp_project_images_slider" <?php checked( $is_slider, 'on' ); ?>> <label><?php esc_html_e( 'Wrap images in a slider', CBP_TEXTDOMAIN ); ?></label></div>
        <div><input type="checkbox" name="cbp_project_images_lightbox" <?php checked( $is_lightbox, 'on' ); ?>> <label><?php esc_html_e( 'Add support for Lightbox Gallery', CBP_TEXTDOMAIN ); ?></label></div>
        <?php
    }

    public function save_meta_boxes( $id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( $post->post_type !== CBP_Main::$settings['postType'] ) return;

        foreach ( array( 'cbp_project_subtitle', 'cbp_project_details_client', 'cbp_project_details_date', 'cbp_project_link' ) as $f ) {
            if ( isset( $_POST[ $f ] ) ) update_post_meta( $id, $f, sanitize_text_field( $_POST[ $f ] ) ); // phpcs:ignore
        }
        if ( isset( $_POST['cbp_project_page_attr'] ) && '' !== $_POST['cbp_project_page_attr'] ) { // phpcs:ignore
            update_post_meta( $id, 'cbp_project_page_attr', sanitize_key( $_POST['cbp_project_page_attr'] ) ); // phpcs:ignore
        }
        if ( isset( $_POST['cbp_project_link_target'] ) ) { // phpcs:ignore
            update_post_meta( $id, 'cbp_project_link_target', sanitize_key( $_POST['cbp_project_link_target'] ) ); // phpcs:ignore
        }
        foreach ( array( 'fb', 'twitter', 'google', 'pinterest' ) as $n ) {
            $key = 'cbp_project_social_' . $n;
            update_post_meta( $id, $key, ( isset( $_POST[ $key ] ) && $_POST[ $key ] ) ? 'on' : 'off' ); // phpcs:ignore
        }
        $raw    = isset( $_POST['cbp_project_images'] ) ? stripslashes( $_POST['cbp_project_images'] ) : ''; // phpcs:ignore
        $images = json_decode( $raw );
        if ( $images ) { foreach ( $images as $img ) { unset( $img->url ); } }
        update_post_meta( $id, 'cbp_project_images', json_encode( $images ) );
        update_post_meta( $id, 'cbp_project_images_slider',  ( isset( $_POST['cbp_project_images_slider'] )   && $_POST['cbp_project_images_slider']   ) ? 'on' : 'off' ); // phpcs:ignore
        update_post_meta( $id, 'cbp_project_images_lightbox', ( isset( $_POST['cbp_project_images_lightbox'] ) && $_POST['cbp_project_images_lightbox'] ) ? 'on' : 'off' ); // phpcs:ignore
    }
}

// ── Inline import processor (used by PHP form, avoids XHR header requirement) ─

class CBP_Import_Processor {
    private $data;
    public function __construct( array $data ) { $this->data = $data; }

    public function run() {
        global $wpdb;
        $home  = get_home_url();
        $items = $this->data['cbp_items'] ?? array();
        $cbp   = $this->data['cbp']       ?? array();

        foreach ( $items as $key => $item ) {
            preg_match_all( '/\{\{post_id (.*?)\}\}/', $item['items'], $m );
            if ( ! empty( $m[0] ) ) {
                foreach ( $m[1] as $k => $pid ) {
                    $post = get_post( (int) $pid );
                    $url  = $post ? ( $post->post_type === 'attachment' ? wp_get_attachment_url( $pid ) : get_permalink( $pid ) ) : '';
                    $m[1][ $k ] = $url ?: '';
                }
                $items[ $key ]['items'] = str_replace( $m[0], $m[1], $item['items'] );
                $items[ $key ]['items'] = str_replace( '{{home_url}}', $home, $items[ $key ]['items'] );
            }
        }

        foreach ( $cbp as $key => $portfolio ) {
            preg_match_all( '/\{\{post_id (.*?)\}\}/', $portfolio['popup'] ?? '', $m );
            if ( ! empty( $m[0] ) ) {
                foreach ( $m[1] as $k => $pid ) {
                    $post = get_post( (int) $pid );
                    $m[1][ $k ] = $post ? get_permalink( $pid ) : '';
                }
                $cbp[ $key ]['popup'] = str_replace( $m[0], $m[1], $portfolio['popup'] );
                $cbp[ $key ]['popup'] = str_replace( '{{home_url}}', $home, $cbp[ $key ]['popup'] );
            }
        }

        if ( isset( $this->data['settings'] ) ) {
            update_option( 'cubeportfolio_settings', $this->data['settings'] );
        }

        foreach ( $cbp as $record ) {
            $exists = $wpdb->get_var( 'SELECT id FROM ' . CBP_Main::$table_cbp . ' WHERE id = ' . (int) $record['id'] );
            if ( ! $exists ) $wpdb->insert( CBP_Main::$table_cbp, $record );
        }
        foreach ( $items as $item ) {
            $exists = $wpdb->get_var( 'SELECT id FROM ' . CBP_Main::$table_cbp_items . ' WHERE id = ' . (int) $item['id'] );
            if ( ! $exists ) $wpdb->insert( CBP_Main::$table_cbp_items, $item );
        }
    }
}
