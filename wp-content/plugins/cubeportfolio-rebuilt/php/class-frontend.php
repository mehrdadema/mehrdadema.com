<?php
/**
 * CBP_Frontend – generates the shortcode HTML/CSS/JS output.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CBP_Frontend {

    /** Inline <style> block for this portfolio */
    public $style = '';

    /** The rendered grid HTML */
    public $html = '';

    /** Inline <script> block that initialises the cbp instance */
    public $script = '';

    /** Array of Google Font objects to be added by CBP_Main */
    public $googleFonts = array();

    public function __construct( array $data, int $id ) {
        $this->googleFonts = json_decode( $data['googlefonts'] ) ?: array();

        $this->html   = $this->build_html( $data );
        $this->style  = '<style type="text/css">'
            . implode( '', json_decode( $data['customcss'], true ) ?: array() )
            . '</style>';
        $this->script = '<script type="text/javascript">'
            . 'this.initCubePortfolio = this.initCubePortfolio || [];'
            . 'this.initCubePortfolio.push({id:' . (int) $id . ',options:' . $data['options'] . '});'
            . '</script>';
    }

    private function build_html( array $data ): string {
        // Concatenate all item HTML fragments.
        // do_shortcode() lets users embed [real3dflipbook], [gallery], etc. inside items.
        $items_html = '';
        foreach ( $data['items'] as $item ) {
            $items_html .= do_shortcode( $item['items'] );
        }

        $tpl = $data['template'];
        $tpl = str_replace( '{{filtersContent}}', $data['filtershtml'],  $tpl );
        $tpl = str_replace( '{{gridContent}}',    $items_html,           $tpl );
        $tpl = str_replace( '{{loadMoreContent}}', $data['loadMorehtml'], $tpl );

        // Optional extra wrapper class.
        $json      = json_decode( $data['jsondata'] );
        $customCls = ( $json && isset( $json->customCls ) ) ? ' ' . esc_attr( $json->customCls ) : '';
        $tpl       = str_replace( '{{customCls}}', $customCls, $tpl );

        // Full-width wrapper.
        if ( $json && ! empty( $json->forceFullWidth ) ) {
            $tpl = '<div class="cbpw-fullWidth-force">' . $tpl . '</div>';
        }

        return $tpl;
    }
}
