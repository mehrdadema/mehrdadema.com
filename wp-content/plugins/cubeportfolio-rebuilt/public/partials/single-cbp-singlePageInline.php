<?php
/**
 * Template: Cube Portfolio – SinglePageInline popup template (two-column layout).
 * Can be overridden by placing a file with the same name in your theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<div class="cbp-popup-singlePage cbpw-page-popup">
    <?php while ( have_posts() ) : the_post();
        $metadata = get_metadata( 'post', get_the_ID() ); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <div class="cbp-l-inline">

                <?php
                $images    = json_decode( $metadata['cbp_project_images'][0] ?? '[]' );
                $is_slider = ( ( $metadata['cbp_project_images_slider'][0] ?? '' ) === 'on' );
                $is_lb     = ( ( $metadata['cbp_project_images_lightbox'][0] ?? '' ) === 'on' );

                if ( $images && count( $images ) ) :
                    require_once CBP_PATH . 'php/class-process-slider-item.php';
                ?>
                <div class="cbp-l-inline-left">
                    <div <?php echo $is_slider ? 'class="cbp-slider"' : ''; ?>>
                        <div class="cbp-slider-wrap">
                            <?php foreach ( $images as $media ) :
                                $obj = new CBP_ProcessSliderItem( $media ); ?>
                                <div class="cbp-slider-item">
                                    <?php if ( ( $media->type ?? 'image' ) === 'image' ) : ?>
                                        <?php if ( $is_lb ) : ?>
                                            <a href="<?php echo esc_url( wp_get_attachment_url( $media->id ) ); ?>"
                                               class="cbp-lightbox"
                                               data-cbp-lightbox="gallery_<?php the_ID(); ?>"
                                               data-title="<?php echo esc_attr( get_post( $media->id )->post_title ?? '' ); ?>">
                                                <?php echo $obj->getHTML(); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo $obj->getHTML(); ?>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <?php echo $obj->getHTML(); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="cbp-l-inline-right" <?php echo ( $images && count( $images ) ) ? '' : 'style="width:100%;padding-left:0;"'; ?>>
                    <div class="cbp-l-inline-title"><?php the_title(); ?></div>
                    <div class="cbp-l-inline-subtitle"><?php echo esc_html( $metadata['cbp_project_subtitle'][0] ?? '' ); ?></div>
                    <div class="cbp-l-inline-desc"><?php the_content(); ?></div>

                    <?php
                    $categories  = get_the_terms( get_the_ID(), CBP_Main::$settings['postType'] . '_category' );
                    $has_details = ( ! empty( $metadata['cbp_project_details_client'][0] )
                                  || ! empty( $metadata['cbp_project_details_date'][0] )
                                  || ! empty( $categories ) );
                    ?>

                    <?php if ( $has_details ) : ?>
                    <div class="cbp-l-inline-details">
                        <?php if ( ! empty( $metadata['cbp_project_details_client'][0] ) ) : ?>
                            <div><strong><?php esc_html_e( 'Client:', CBP_TEXTDOMAIN ); ?></strong> <?php echo esc_html( $metadata['cbp_project_details_client'][0] ); ?></div>
                        <?php endif; ?>
                        <?php if ( ! empty( $metadata['cbp_project_details_date'][0] ) ) : ?>
                            <div><strong><?php esc_html_e( 'Date:', CBP_TEXTDOMAIN ); ?></strong> <?php echo esc_html( $metadata['cbp_project_details_date'][0] ); ?></div>
                        <?php endif; ?>
                        <?php if ( $categories ) : ?>
                            <div><strong><?php esc_html_e( 'Categories:', CBP_TEXTDOMAIN ); ?></strong> <?php the_terms( get_the_ID(), CBP_Main::$settings['postType'] . '_category' ); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php
                    $fb        = ( ( $metadata['cbp_project_social_fb'][0]        ?? '' ) === 'on' );
                    $twitter   = ( ( $metadata['cbp_project_social_twitter'][0]   ?? '' ) === 'on' );
                    $pinterest = ( ( $metadata['cbp_project_social_pinterest'][0] ?? '' ) === 'on' );
                    ?>

                    <?php if ( $fb || $twitter || $pinterest ) : ?>
                    <div class="cbp-l-project-social">
                        <?php if ( $fb ) : ?>
                            <a href="#" class="cbp-social-fb" title="<?php esc_attr_e( 'Share on Facebook', CBP_TEXTDOMAIN ); ?>" rel="nofollow">
                                <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 486.392 486.392"><path d="M243.196 0C108.89 0 0 108.89 0 243.196s108.89 243.196 243.196 243.196 243.196-108.89 243.196-243.196C486.392 108.86 377.502 0 243.196 0zm62.866 243.165l-39.854.03-.03 145.917h-54.69V243.196H175.01v-50.28l36.48-.03-.062-29.61c0-41.04 11.126-65.997 59.43-65.997h40.25v50.31h-25.17c-18.818 0-19.73 7.02-19.73 20.122l-.06 25.17h45.233l-5.316 50.28z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if ( $twitter ) : ?>
                            <a href="#" class="cbp-social-twitter" title="<?php esc_attr_e( 'Share on Twitter', CBP_TEXTDOMAIN ); ?>" rel="nofollow">
                                <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 486.392 486.392"><path d="M243.196 0C108.891 0 0 108.891 0 243.196s108.891 243.196 243.196 243.196 243.196-108.891 243.196-243.196C486.392 108.861 377.501 0 243.196 0zm120.99 188.598l.182 7.752c0 79.16-60.221 170.359-170.359 170.359-33.804 0-65.268-9.91-91.776-26.904 4.682.547 9.454.851 14.288.851 28.059 0 53.868-9.576 74.357-25.627-26.204-.486-48.305-17.814-55.935-41.586 3.678.699 7.387 1.034 11.278 1.034 5.472 0 10.761-.699 15.777-2.067-27.39-5.533-48.031-29.7-48.031-58.701v-.76c8.086 4.499 17.297 7.174 27.116 7.509-16.051-10.731-26.63-29.062-26.63-49.825 0-10.974 2.949-21.249 8.086-30.095 29.518 36.236 73.658 60.069 123.422 62.562-1.034-4.378-1.55-8.968-1.55-13.649 0-33.044 26.812-59.857 59.887-59.857 17.206 0 32.771 7.265 43.714 18.908 13.619-2.706 26.448-7.691 38.03-14.531-4.469 13.984-13.953 25.718-26.326 33.135 12.069-1.429 23.651-4.682 34.382-9.424-8.025 11.977-18.209 22.526-29.912 30.916z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if ( $pinterest ) : ?>
                            <a href="#" class="cbp-social-pinterest" title="<?php esc_attr_e( 'Share on Pinterest', CBP_TEXTDOMAIN ); ?>" rel="nofollow">
                                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 32 32"><path d="M16 0C7.164 0 0 7.164 0 16c0 8.838 7.164 16 16 16s16-7.162 16-16c0-8.836-7.164-16-16-16zm1.652 19.562c-.992 0-1.926-.535-2.247-1.146 0 0-.534 2.117-.646 2.524-.398 1.444-1.569 2.892-1.659 3.009-.063.082-.204.057-.219-.054-.025-.186-.324-2.006.027-3.491.177-.746 1.183-5.009 1.183-5.009s-.294-.586-.294-1.453c0-1.362.79-2.379 1.773-2.379.836 0 1.24.627 1.24 1.379 0 .841-.535 2.099-.812 3.263-.23.976.488 1.771 1.45 1.771 1.739 0 2.913-2.236 2.913-4.888 0-2.014-1.355-3.521-3.823-3.521-2.787 0-4.524 2.08-4.524 4.402 0 .801.236 1.365.606 1.801.17.201.193.284.132.514-.045.168-.145.576-.188.736-.061.233-.25.315-.46.229-1.284-.522-1.882-1.93-1.882-3.512 0-2.609 2.201-5.74 6.568-5.74 3.509 0 5.817 2.539 5.817 5.266.004 3.606-2 6.299-4.955 6.299z"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $metadata['cbp_project_link'][0] ) ) : ?>
                        <div class="cbp-l-inline-view-wrap">
                            <a href="<?php echo esc_url( $metadata['cbp_project_link'][0] ); ?>"
                               target="<?php echo esc_attr( $metadata['cbp_project_link_target'][0] ?? '_blank' ); ?>"
                               class="cbp-l-inline-view">
                                <?php esc_html_e( 'VIEW PROJECT', CBP_TEXTDOMAIN ); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <br>
                </div><!-- .cbp-l-inline-right -->
            </div><!-- .cbp-l-inline -->
        </article>
    <?php endwhile; ?>
</div>
<?php wp_reset_query(); ?>
<?php get_footer(); ?>
