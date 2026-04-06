<?php
/**
 * CBP_Main – bootstraps the plugin, registers hooks, handles shortcode & frontend.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CBP_Main {

    /** @var wpdb */
    public $wpdb;

    /** Portfolios table (prefixed) */
    public static $table_cbp = 'cubeportfolio';

    /** Portfolio items table (prefixed) */
    public static $table_cbp_items = 'cubeportfolio_items';

    /** Collected Google Fonts across all shortcodes on a page */
    public $googleFonts = array();

    /** Plugin-level settings stored in wp_options */
    public static $settings = array();

    /** Whether to enqueue CBP assets on this page */
    private $loadAssets = false;

    /** True when the current request is an AJAX popup fetch */
    private $request_from_ajax = false;

    // ── Constructor ──────────────────────────────────────────────────────────

    public function __construct( $main_plugin_file ) {
        global $wpdb;
        $this->wpdb = $wpdb;

        // Apply DB prefix to table names.
        self::$table_cbp       = $this->wpdb->prefix . self::$table_cbp;
        self::$table_cbp_items = $this->wpdb->prefix . self::$table_cbp_items;

        // Activation hook – flush rewrite rules so CPT URLs work immediately.
        register_activation_hook( $main_plugin_file, array( $this, 'activate' ) );

        // Load saved settings.
        $this->load_settings();

        // Handle AJAX popup request early (before headers are sent).
        $data = stripslashes_deep( $_POST ); // phpcs:ignore
        if ( isset( $data['source'] ) && 'cubeportfolio' === $data['source'] ) {
            $this->request_from_ajax = true;
            $this->process_frontend_popup( $data );
        }

        // Core WordPress hooks.
        add_action( 'init',           array( $this, 'init' ) );
        add_action( 'init',           array( $this, 'register_custom_post_type' ) );
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Shortcode.
        add_shortcode( 'cubeportfolio', array( $this, 'shortcode_handler' ) );

        // Visual Composer / WPBakery integration.
        add_action( 'vc_before_init', array( $this, 'integrate_with_vc' ) );

        // Single post template override.
        add_filter( 'template_include', array( $this, 'include_single_template' ), 1 );
    }

    // ── Settings ─────────────────────────────────────────────────────────────

    private function load_settings() {
        self::$settings = get_option( 'cubeportfolio_settings', array() );

        if ( ! isset( self::$settings['preload'] ) ) {
            self::$settings['preload'] = array( 'onPostsPage', 'onHomePage' );
        }
        if ( ! isset( self::$settings['postType'] ) ) {
            self::$settings['postType'] = 'cubeportfolio';
        }
        if ( ! isset( self::$settings['flush_rewrite_rules'] ) ) {
            self::$settings['flush_rewrite_rules'] = false;
        }
    }

    // ── Activation ───────────────────────────────────────────────────────────

    public function activate() {
        $this->register_custom_post_type();
        flush_rewrite_rules();
    }

    // ── Init ─────────────────────────────────────────────────────────────────

    public function init() {
        // Flush rewrite rules on demand (e.g. after post-type slug change).
        if ( self::$settings['flush_rewrite_rules'] ) {
            flush_rewrite_rules();
            self::$settings['flush_rewrite_rules'] = false;
            update_option( 'cubeportfolio_settings', self::$settings );
        }

        if ( is_admin() ) {
            $this->check_db();
            require_once CBP_PATH . 'php/class-backend.php';
            new CBP_Backend();
        } else {
            $this->register_public_assets();
            add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 9999 );
            add_action( 'wp_head',            array( $this, 'print_ajax_url' ) );
        }
    }

    // ── Database ─────────────────────────────────────────────────────────────

    public function check_db() {
        $current = get_option( 'cubeportfolio_version', false );

        if ( $current !== CBP_VERSION ) {
            $charset = '';
            if ( ! empty( $this->wpdb->charset ) ) {
                $charset .= ' DEFAULT CHARACTER SET ' . $this->wpdb->charset;
            }
            if ( ! empty( $this->wpdb->collate ) ) {
                $charset .= ' COLLATE ' . $this->wpdb->collate;
            }

            // Portfolios table.
            $sql = "CREATE TABLE IF NOT EXISTS " . self::$table_cbp . " (
                id           INT(10)      UNSIGNED AUTO_INCREMENT NOT NULL,
                active       TINYINT(1)   UNSIGNED NOT NULL DEFAULT 1,
                name         VARCHAR(255) NOT NULL,
                type         VARCHAR(255) NOT NULL,
                customcss    TEXT         NOT NULL,
                options      TEXT         NOT NULL,
                loadMorehtml TEXT,
                template     TEXT,
                filtershtml  TEXT,
                googlefonts  TEXT,
                popup        MEDIUMTEXT,
                jsondata     MEDIUMTEXT,
                PRIMARY KEY (id),
                INDEX(active)
            ){$charset};";
            $this->wpdb->query( $sql ); // phpcs:ignore

            // Items table.
            $sql = "CREATE TABLE IF NOT EXISTS " . self::$table_cbp_items . " (
                id               INT(10)  UNSIGNED AUTO_INCREMENT NOT NULL,
                cubeportfolio_id INT(10)  UNSIGNED NOT NULL,
                sort             TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
                page             TINYINT(2) UNSIGNED NOT NULL,
                items            TEXT     NOT NULL,
                isLoadMore       TEXT,
                isSinglePage     TEXT,
                PRIMARY KEY(id),
                INDEX(cubeportfolio_id)
            ){$charset};";
            $this->wpdb->query( $sql ); // phpcs:ignore

            // ── Migrations for databases created by older plugin versions ──

            // v1.2 – added popup column, removed mainjs.
            if ( $current && version_compare( $current, '1.2', '<' ) ) {
                $this->wpdb->query( 'ALTER TABLE ' . self::$table_cbp . ' ADD popup TEXT' ); // phpcs:ignore
                $this->wpdb->query( 'ALTER TABLE ' . self::$table_cbp . ' DROP COLUMN mainjs' ); // phpcs:ignore
            }

            // v1.5 – load-more button class rename.
            if ( $current && version_compare( $current, '1.5', '<' ) ) {
                require_once CBP_PATH . 'php/deprecated/class-version-150.php';
                new CBP_Version150();
            }

            // v1.11.0 – added jsondata column.
            if ( $current && version_compare( $current, '1.11.0', '<' ) ) {
                $this->wpdb->query( 'ALTER TABLE ' . self::$table_cbp . ' ADD jsondata TEXT' ); // phpcs:ignore
            }

            // v1.11.2 – increase column capacity to MEDIUMTEXT.
            if ( $current && version_compare( $current, '1.11.2', '<' ) ) {
                $this->wpdb->query( 'ALTER TABLE ' . self::$table_cbp . ' MODIFY popup MEDIUMTEXT' ); // phpcs:ignore
                $this->wpdb->query( 'ALTER TABLE ' . self::$table_cbp . ' MODIFY jsondata MEDIUMTEXT' ); // phpcs:ignore
            }

            // v1.13.0 – CSS selector rename.
            if ( $current && version_compare( $current, '1.13.0', '<' ) ) {
                require_once CBP_PATH . 'php/deprecated/class-version-1130.php';
                new CBP_Version1130();
            }

            update_option( 'cubeportfolio_version', CBP_VERSION );
        }
    }

    // ── Shortcode ────────────────────────────────────────────────────────────

    public function shortcode_handler( $atts ) {
        $atts = shortcode_atts( array( 'id' => -1 ), $atts );
        return $this->render_portfolio( (int) $atts['id'] );
    }

    public function render_portfolio( $id ) {
        $data = $this->get_portfolio_from_db( $id );

        if ( null === $data ) {
            return self::frontend_error( 'Incorrect Cube Portfolio ID or database query error. (1001)' );
        }

        require_once CBP_PATH . 'php/class-frontend.php';
        $portfolio = new CBP_Frontend( $data, $id );

        $this->collect_google_fonts( $portfolio );

        return $portfolio->style . $portfolio->html . $portfolio->script;
    }

    // ── Google Fonts collector ────────────────────────────────────────────────

    private function collect_google_fonts( CBP_Frontend $portfolio ) {
        $unique = array();

        foreach ( $portfolio->googleFonts as $font ) {
            $exists = false;
            foreach ( $this->googleFonts as $existing ) {
                if ( $existing->name === $font->name && $existing->weightStyle === $font->weightStyle ) {
                    $exists = true;
                    break;
                }
            }
            if ( ! $exists ) {
                $this->googleFonts[] = $font;
                $unique[]            = $font->slug . ':' . $font->weightStyle;
            }
        }

        if ( ! empty( $unique ) ) {
            $portfolio->style .= '<link rel="stylesheet" href="//fonts.googleapis.com/css?family='
                . implode( '%7C', $unique )
                . '" type="text/css" media="all">';
        }
    }

    // ── DB helpers ───────────────────────────────────────────────────────────

    public function get_portfolio_from_db( $id ) {
        $grid = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM %i WHERE id = %d",
                self::$table_cbp,
                $id
            ),
            ARRAY_A
        );

        if ( null === $grid ) {
            return null;
        }

        $grid['items'] = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM %i WHERE cubeportfolio_id = %d AND isLoadMore = 0 ORDER BY sort",
                self::$table_cbp_items,
                $id
            ),
            ARRAY_A
        );

        return $grid;
    }

    // ── Assets ───────────────────────────────────────────────────────────────

    private function register_public_assets() {
        wp_register_style(
            'cubeportfolio-css',
            CBP_URL . 'public/css/main.min.css',
            array(),
            CBP_VERSION,
            'all'
        );
        wp_register_script(
            'cubeportfolio-js',
            CBP_URL . 'public/js/main.min.js',
            array( 'jquery' ),
            CBP_VERSION,
            true
        );
    }

    public function maybe_enqueue_assets() {
        global $posts;

        $preload = self::$settings['preload'];

        if ( in_array( 'onAllPages', $preload, true ) ) {
            $this->loadAssets = true;
        }
        if ( in_array( 'onHomePage', $preload, true ) && is_front_page() ) {
            $this->loadAssets = true;
        }
        if ( in_array( 'onPostsPage', $preload, true ) && ! empty( $posts ) ) {
            foreach ( $posts as $post ) {
                if ( preg_match( '/cubeportfolio/s', $post->post_content ) ) {
                    $this->loadAssets = true;
                    break;
                }
            }
        }

        if ( $this->loadAssets ) {
            wp_enqueue_style( 'cubeportfolio-css' );
            wp_enqueue_script( 'cubeportfolio-js' );
        }
    }

    public function print_ajax_url() {
        ?>
        <script>if(typeof ajaxurl==="undefined"){var ajaxurl="<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>"}</script>
        <?php
    }

    // ── Custom Post Type ─────────────────────────────────────────────────────

    public function register_custom_post_type() {
        $post_type = self::$settings['postType'];
        $taxonomy  = $post_type . '_category';

        register_taxonomy(
            $taxonomy,
            array( $post_type ),
            array(
                'hierarchical'       => true,
                'label'              => __( 'Custom Categories', CBP_TEXTDOMAIN ),
                'singular_label'     => __( 'Custom Category', CBP_TEXTDOMAIN ),
                'rewrite'            => true,
                'public'             => true,
                'show_admin_column'  => true,
            )
        );

        register_post_type(
            $post_type,
            array(
                'label'           => __( 'Cube Posts', CBP_TEXTDOMAIN ),
                'singular_label'  => __( 'Cube Post', CBP_TEXTDOMAIN ),
                'public'          => true,
                'capability_type' => 'post',
                'hierarchical'    => false,
                'show_ui'         => true,
                'show_in_menu'    => true,
                'show_in_admin_bar' => false,
                'supports'        => array( 'title', 'editor', 'custom-fields' ),
                'taxonomies'      => array( $taxonomy ),
                'rewrite'         => array(
                    'slug'       => $post_type,
                    'with_front' => true,
                ),
            )
        );
    }

    // ── Single post template override ────────────────────────────────────────

    public function include_single_template( $template_path ) {
        if ( get_post_type() === self::$settings['postType'] && is_single() ) {
            if ( ! $this->request_from_ajax ) {
                wp_register_script(
                    'cubeportfolio-standalone-js',
                    CBP_URL . 'public/js/init-cbp-standalone.min.js',
                    array( 'cubeportfolio-js' ),
                    CBP_VERSION,
                    true
                );
                wp_enqueue_style( 'cubeportfolio-css' );
                wp_enqueue_script( 'cubeportfolio-js' );
                wp_enqueue_script( 'cubeportfolio-standalone-js' );
            }

            $template = get_metadata( 'post', get_the_ID(), 'cbp_project_page_attr', true );

            $theme_file = locate_template( array( $template . '.php' ) );
            if ( $theme_file ) {
                $template_path = $theme_file;
            } else {
                $template_path = CBP_PATH . 'public/partials/' . $template . '.php';
            }
        }

        return $template_path;
    }

    // ── Frontend AJAX popup ──────────────────────────────────────────────────

    private function process_frontend_popup( $data ) {
        $popup = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT popup FROM %i WHERE id = %d",
                self::$table_cbp,
                (int) $data['id']
            )
        );

        $popup = json_decode( $popup );
        if ( ! $popup ) {
            return;
        }

        $element = null;
        foreach ( $popup as $item ) {
            if ( $item->link === $data['link'] && $item->type === $data['type'] ) {
                $element = $item;
                break;
            }
        }

        if ( $element ) {
            if ( $element->html ) {
                echo $element->html; // phpcs:ignore
                die();
            }
            if ( isset( $data['selector'] ) && 'automatically' === $data['selector'] ) {
                add_filter( 'the_content', function( $content ) {
                    return '<div class="cbpw-ajax-block">' . $content . '</div>';
                } );
            }
        }
    }

    // ── Visual Composer / WPBakery ───────────────────────────────────────────

    public function integrate_with_vc() {
        if ( ! function_exists( 'vc_map' ) ) {
            return;
        }

        $cbp   = self::$table_cbp;
        $items = array( __( 'Select a portfolio', CBP_TEXTDOMAIN ) => '-1' );
        $rows  = $this->wpdb->get_results(
            $this->wpdb->prepare( "SELECT id, name FROM %i WHERE active = %d", $cbp, 1 )
        );
        foreach ( $rows as $row ) {
            $items[ $row->name . ' (id=' . $row->id . ')' ] = $row->id;
        }

        vc_map( array(
            'name'        => 'Cube Portfolio',
            'base'        => 'cubeportfolio',
            'category'    => 'Content',
            'description' => 'Responsive WordPress Grid Plugin',
            'params'      => array(
                array(
                    'type'         => 'dropdown',
                    'heading'      => 'Cube Portfolio',
                    'param_name'   => 'id',
                    'value'        => $items,
                    'admin_label'  => true,
                    'description'  => 'Select your Cube Portfolio',
                ),
            ),
        ) );
    }

    // ── Textdomain ───────────────────────────────────────────────────────────

    public function load_textdomain() {
        load_plugin_textdomain( CBP_TEXTDOMAIN, false, CBP_DIRNAME . '/languages/' );
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public static function frontend_error( $message ) {
        return '<p><strong>' . esc_html( $message ) . '</strong></p>';
    }
}
