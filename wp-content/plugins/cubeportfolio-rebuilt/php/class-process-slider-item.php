<?php
/**
 * CBP_ProcessSliderItem – resolves a saved media item (image/video/audio)
 * into a URL or HTML fragment for use in single-post templates.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CBP_ProcessSliderItem {

    /** @var object The media item object (id, type, …) */
    private $item;

    public function __construct( $item ) {
        if ( ! isset( $item->type ) ) {
            $item->type = 'image';
        }
        $this->item = $item;
    }

    // ── Public API ───────────────────────────────────────────────────────────

    /** Returns the resolved URL (string or array for self-hosted video). */
    public function getURL() {
        return $this->{ $this->item->type . '_url' }();
    }

    /** Returns an HTML string ready to embed in a slider item. */
    public function getHTML(): string {
        $url = $this->getURL();
        return $this->{ $this->item->type . '_html' }( $url );
    }

    // ── URL resolvers ────────────────────────────────────────────────────────

    private function image_url(): string {
        return (string) wp_get_attachment_url( $this->item->id );
    }

    private function youtube_url(): string {
        $pos  = strrpos( $this->item->id, 'v=' ) + 2;
        $link = substr( $this->item->id, $pos );
        $link = preg_replace( '/[?&]/', '?', $link );
        return '//www.youtube.com/embed/' . $link;
    }

    private function vimeo_url(): string {
        $pos  = strrpos( $this->item->id, '/' ) + 1;
        $link = substr( $this->item->id, $pos );
        $link = preg_replace( '/[?&]/', '?', $link );
        return '//player.vimeo.com/video/' . $link;
    }

    private function ted_url(): string {
        $pos  = strrpos( $this->item->id, '/' ) + 1;
        $link = substr( $this->item->id, $pos );
        return 'https://embed.ted.com/talks/' . $link . '.html';
    }

    private function soundcloud_url(): string {
        return (string) $this->item->id;
    }

    private function selfhostedvideo_url() {
        $raw = $this->item->id;
        if ( strpos( $raw, '|' ) !== false ) {
            return explode( '|', $raw );
        }
        return explode( '%7C', $raw );
    }

    private function selfhostedaudio_url(): string {
        return (string) $this->item->id;
    }

    // ── HTML builders ────────────────────────────────────────────────────────

    private function image_html( string $url ): string {
        $alt = get_post_meta( $this->item->id, '_wp_attachment_image_alt', true );
        return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '">';
    }

    private function youtube_html( string $url ): string {
        return '<div class="cbp-misc-video"><iframe src="' . esc_url( $url ) . '" frameborder="0" allowfullscreen scrolling="no"></iframe></div>';
    }

    private function vimeo_html( string $url ): string {
        return '<div class="cbp-misc-video"><iframe src="' . esc_url( $url ) . '" frameborder="0" allowfullscreen scrolling="no"></iframe></div>';
    }

    private function ted_html( string $url ): string {
        return '<div class="cbp-misc-video"><iframe src="' . esc_url( $url ) . '" frameborder="0" allowfullscreen scrolling="no"></iframe></div>';
    }

    private function soundcloud_html( string $url ): string {
        return '<div class="cbp-misc-video"><iframe src="' . esc_url( $url ) . '" frameborder="0" allowfullscreen scrolling="no"></iframe></div>';
    }

    private function selfhostedvideo_html( $urls ): string {
        $html = '<div class="cbp-misc-video"><video controls height="auto" style="width:100%">';
        $mime_map = array(
            'mp4'  => 'video/mp4',
            'ogg'  => 'video/ogg',
            'ogv'  => 'video/ogg',
            'webm' => 'video/webm',
        );
        foreach ( (array) $urls as $src ) {
            $ext  = strtolower( pathinfo( $src, PATHINFO_EXTENSION ) );
            $mime = isset( $mime_map[ $ext ] ) ? $mime_map[ $ext ] : 'video/' . $ext;
            $html .= '<source src="' . esc_url( $src ) . '" type="' . esc_attr( $mime ) . '">';
        }
        $html .= 'Your browser does not support the video tag.</video></div>';
        return $html;
    }

    private function selfhostedaudio_html( string $url ): string {
        return '<div class="cbp-misc-video"><audio controls style="margin-top:26%;width:75%">'
            . '<source src="' . esc_url( $url ) . '" type="audio/mpeg">'
            . 'Your browser does not support the audio tag.</audio></div>';
    }
}
