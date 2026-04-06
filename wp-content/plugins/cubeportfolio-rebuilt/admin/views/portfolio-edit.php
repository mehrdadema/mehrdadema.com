<?php if ( ! defined( 'ABSPATH' ) ) exit;

$is_new = ( $portfolio === null );
$pid    = $is_new ? 0 : (int) $portfolio['id'];
$items  = $portfolio['items'] ?? [];

// Parse existing item HTML into structured data for the visual editor
function cbp_parse_item_html( $html ) {
    if ( empty( trim( $html ) ) ) return null;
    $data = array(
        'item_type'   => 'image',   // 'image' or 'shortcode'
        'image_url'   => '',
        'image_id'    => '',
        'title'       => '',
        'desc'        => '',
        'categories'  => '',
        'link'        => '',
        'link_target' => '_self',
        'link_type'   => 'lightbox',
        'size'        => '1x1',
        'shortcode'   => '',
        'raw_html'    => $html,
    );

    // Categories from outer div class
    if ( preg_match( '/class=["\'][^"\']*cbp-item([^"\']*)["\']/', $html, $m ) ) {
        $cats = preg_replace( '/\s+/', ' ', trim( $m[1] ) );
        $cats = str_replace( ' ', ',', $cats );
        $data['categories'] = trim( $cats, ',' );
    }
    // Size
    if ( preg_match( '/data-cbp-size=["\'](\w+)["\']/', $html, $m ) ) {
        $data['size'] = $m[1];
    }

    // Detect shortcode item: a cbp-item div whose inner content is a shortcode (no <img>/<a>)
    $inner = preg_replace( '/^<div[^>]*>|<\/div>$/s', '', trim( $html ) );
    $inner = trim( $inner );
    if ( preg_match( '/^\[/', $inner ) && strpos( $inner, '<img' ) === false ) {
        $data['item_type'] = 'shortcode';
        $data['shortcode'] = $inner;
        return $data;
    }

    // Image item parsing
    if ( preg_match( '/(?:data-cbp-src|src)=["\']([^"\']+)["\']/', $html, $m ) ) {
        $data['image_url'] = $m[1];
    }
    if ( preg_match( '/cbp-l-caption-title[^>]*>([^<]*)</', $html, $m ) ) {
        $data['title'] = trim( strip_tags( $m[1] ) );
    }
    if ( preg_match( '/cbp-l-caption-desc[^>]*>([^<]*)</', $html, $m ) ) {
        $data['desc'] = trim( strip_tags( $m[1] ) );
    }
    if ( preg_match( '/<a[^>]+href=["\']([^"\']+)["\']/', $html, $m ) ) {
        $data['link'] = $m[1];
    }
    if ( strpos( $html, 'cbp-lightbox' ) !== false ) {
        $data['link_type'] = 'lightbox';
    } elseif ( strpos( $html, 'cbp-singlePage' ) !== false ) {
        $data['link_type'] = 'singlePage';
    } else {
        $data['link_type'] = 'external';
    }
    return $data;
}

$parsed_items = array();
foreach ( $items as $item ) {
    $parsed = cbp_parse_item_html( $item['items'] );
    if ( $parsed ) {
        $parsed['_raw_item'] = $item;
        $parsed_items[] = $parsed;
    }
}
?>
<div class="wrap cbp-admin-wrap">
    <h1><?php echo $is_new ? 'Add New Portfolio' : 'Edit Portfolio: <em>' . esc_html( $portfolio['name'] ) . '</em>'; ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=cubeportfolio' ) ); ?>">&larr; Back to all portfolios</a>
    <hr class="wp-header-end">

    <?php if ( isset( $_GET['saved'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p>Portfolio saved successfully.</p></div>
    <?php endif; ?>

    <form method="post" id="cbp-edit-form">
        <?php wp_nonce_field( 'cbp_save_portfolio', 'cbp_nonce' ); ?>
        <input type="hidden" name="cbp_action"       value="save_portfolio">
        <input type="hidden" name="cbp_portfolio_id" value="<?php echo $pid; ?>">
        <!-- Items are serialized here before submit -->
        <input type="hidden" name="cbp_items_json" id="cbp-items-json" value="">

        <div class="cbp-edit-layout">

            <!-- ── LEFT: Main content ── -->
            <div class="cbp-edit-main">

                <!-- Portfolio Name -->
                <div class="postbox">
                    <div class="postbox-header"><h2 class="hndle">Portfolio Name</h2></div>
                    <div class="inside">
                        <input type="text" name="cbp_name" id="cbp_name" class="widefat"
                               value="<?php echo esc_attr( $portfolio['name'] ?? '' ); ?>"
                               placeholder="e.g. My Main Portfolio" style="font-size:16px;padding:8px">
                    </div>
                </div>

                <!-- Portfolio Items -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Portfolio Items
                            <span id="cbp-item-count" class="cbp-item-count"><?php echo count( $parsed_items ); ?> items</span>
                        </h2>
                    </div>
                    <div class="inside">

                        <!-- Item grid -->
                        <div id="cbp-item-grid">
                            <?php if ( empty( $parsed_items ) ) : ?>
                                <div class="cbp-empty-state" id="cbp-empty-state">
                                    <p>🖼 No items yet. Click <strong>Add Item</strong> to get started.</p>
                                </div>
                            <?php else : ?>
                                <?php foreach ( $parsed_items as $idx => $item ) : ?>
                                <div class="cbp-item-card" data-index="<?php echo $idx; ?>">
                                    <div class="cbp-item-thumb">
                                        <?php if ( $item['item_type'] === 'shortcode' ) : ?>
                                            <div class="cbp-item-thumb-shortcode">[sc]</div>
                                        <?php elseif ( $item['image_url'] ) : ?>
                                            <img src="<?php echo esc_url( $item['image_url'] ); ?>" alt="">
                                        <?php else : ?>
                                            <div class="cbp-item-thumb-placeholder">No image</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="cbp-item-info">
                                        <?php if ( $item['item_type'] === 'shortcode' ) : ?>
                                            <div class="cbp-item-title"><code style="font-size:10px"><?php echo esc_html( substr( $item['shortcode'], 0, 28 ) ); ?></code></div>
                                        <?php else : ?>
                                        <div class="cbp-item-title"><?php echo $item['title'] ? esc_html( $item['title'] ) : '<em style="color:#999">No title</em>'; ?></div>
                                        <?php endif; ?>
                                        <?php if ( $item['categories'] ) : ?>
                                            <div class="cbp-item-cats"><?php echo esc_html( str_replace( ',', ' · ', $item['categories'] ) ); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="cbp-item-actions">
                                        <button type="button" class="button button-small cbp-edit-item" data-index="<?php echo $idx; ?>">Edit</button>
                                        <button type="button" class="button button-small cbp-delete-item" data-index="<?php echo $idx; ?>">✕</button>
                                    </div>
                                    <!-- Hidden raw data -->
                                    <script type="application/json" class="cbp-item-data"><?php echo wp_json_encode( $item ); ?></script>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <p style="margin-top:16px">
                            <button type="button" id="cbp-add-item-btn" class="button button-primary">＋ Add Item</button>
                        </p>

                    </div>
                </div>

                <!-- Custom CSS -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Custom CSS <small style="font-weight:normal;font-size:12px">(optional — scoped to this portfolio)</small></h2>
                    </div>
                    <div class="inside">
                        <?php
                        $raw_css = $portfolio['customcss'] ?? '[]';
                        $css_arr = json_decode( $raw_css, true );
                        $css_str = is_array( $css_arr ) ? implode( "\n", array_filter( $css_arr ) ) : $raw_css;
                        ?>
                        <textarea name="cbp_customcss_text" rows="8" class="widefat code"
                                  placeholder="/* e.g. #cbpw-wrap<?php echo $pid; ?> { background: #fff; } */"
                        ><?php echo esc_textarea( $css_str ); ?></textarea>
                    </div>
                </div>

                <!-- Advanced (hidden by default) -->
                <details class="postbox cbp-advanced-box">
                    <summary class="postbox-header" style="cursor:pointer;list-style:none">
                        <h2 class="hndle">⚙ Advanced Settings <small style="font-weight:normal;font-size:12px">(grid template &amp; JS options — only edit if you know what you're doing)</small></h2>
                    </summary>
                    <div class="inside">
                        <p><strong>JS Options</strong> <span class="description">(JSON object passed to the CBP grid engine)</span></p>
                        <textarea name="cbp_options" rows="6" class="widefat code"><?php echo esc_textarea( $portfolio['options'] ?? '' ); ?></textarea>
                        <p><strong>Grid Template HTML</strong></p>
                        <textarea name="cbp_template" rows="6" class="widefat code"><?php echo esc_textarea( $portfolio['template'] ?? '' ); ?></textarea>
                        <p><strong>Filters HTML</strong></p>
                        <textarea name="cbp_filtershtml" rows="5" class="widefat code"><?php echo esc_textarea( $portfolio['filtershtml'] ?? '' ); ?></textarea>
                        <p><strong>Load More HTML</strong></p>
                        <textarea name="cbp_loadMorehtml" rows="3" class="widefat code"><?php echo esc_textarea( $portfolio['loadMorehtml'] ?? '' ); ?></textarea>
                        <p><strong>Google Fonts (JSON)</strong></p>
                        <textarea name="cbp_googlefonts" rows="2" class="widefat code"><?php echo esc_textarea( $portfolio['googlefonts'] ?? '[]' ); ?></textarea>
                        <p><strong>JSON Data</strong></p>
                        <textarea name="cbp_jsondata" rows="2" class="widefat code"><?php echo esc_textarea( $portfolio['jsondata'] ?? '{}' ); ?></textarea>
                    </div>
                </details>

            </div><!-- .cbp-edit-main -->

            <!-- ── RIGHT: Sidebar ── -->
            <div class="cbp-edit-sidebar">
                <div class="postbox">
                    <div class="postbox-header"><h2 class="hndle">Publish</h2></div>
                    <div class="inside cbp-sidebar-inside">
                        <?php if ( ! $is_new ) : ?>
                            <div class="cbp-shortcode-box">
                                <label>Shortcode</label>
                                <input type="text" value="[cubeportfolio id=<?php echo $pid; ?>]" readonly onclick="this.select()" class="widefat">
                            </div>
                        <?php endif; ?>
                        <button type="submit" class="button button-primary button-hero cbp-save-btn">
                            💾 <?php echo $is_new ? 'Create Portfolio' : 'Save Changes'; ?>
                        </button>
                    </div>
                </div>
            </div>

        </div><!-- .cbp-edit-layout -->
    </form>
</div>

<!-- ══════════════════════════════════════════
     ADD / EDIT ITEM MODAL
══════════════════════════════════════════════ -->
<div id="cbp-item-modal" style="display:none">
    <div id="cbp-item-modal-inner">
        <div class="cbp-modal-header">
            <h2 id="cbp-modal-title">Add Item</h2>
            <button type="button" id="cbp-modal-close">&times;</button>
        </div>
        <div class="cbp-modal-body">

            <!-- Item Type -->
            <div class="cbp-field-row" style="margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #eee">
                <label class="cbp-field-label">Item Type</label>
                <label style="margin-right:20px;font-weight:normal">
                    <input type="radio" name="cbp_item_type_radio" id="cbp-type-image" value="image" checked> Image / Media
                </label>
                <label style="font-weight:normal">
                    <input type="radio" name="cbp_item_type_radio" id="cbp-type-shortcode" value="shortcode"> Shortcode / Embed
                </label>
            </div>

            <!-- Shortcode field (shown when type = shortcode) -->
            <div id="cbp-shortcode-fields" style="display:none">
                <div class="cbp-field-row">
                    <label class="cbp-field-label" for="cbp-modal-shortcode-field">Shortcode or HTML</label>
                    <input type="text" id="cbp-modal-shortcode-field" class="widefat code"
                           placeholder='e.g. [real3dflipbook id="2"]' style="font-family:monospace;font-size:14px;padding:8px">
                    <p class="description" style="margin-top:6px">Enter any WordPress shortcode. It will be rendered inside a portfolio grid cell.</p>
                </div>
                <div class="cbp-field-row cbp-field-row-half">
                    <div>
                        <label class="cbp-field-label" for="cbp-modal-sc-cats">Categories <small>(comma-separated)</small></label>
                        <input type="text" id="cbp-modal-sc-cats" class="widefat" placeholder="web, design">
                    </div>
                    <div>
                        <label class="cbp-field-label" for="cbp-modal-sc-size">Item Size</label>
                        <select id="cbp-modal-sc-size" class="widefat">
                            <option value="1x1">1×1 (default)</option>
                            <option value="2x1">2×1 (wide)</option>
                            <option value="1x2">1×2 (tall)</option>
                            <option value="2x2">2×2 (large)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Image fields (shown when type = image) -->
            <div id="cbp-image-fields">
            <div class="cbp-modal-cols">
                <!-- Image -->
                <div class="cbp-modal-image-col">
                    <label class="cbp-field-label">Image</label>
                    <div id="cbp-modal-thumb-wrap">
                        <div id="cbp-modal-thumb"><span>No image selected</span></div>
                        <button type="button" id="cbp-pick-image" class="button">Choose Image</button>
                        <button type="button" id="cbp-remove-image" class="button" style="display:none">Remove</button>
                        <input type="hidden" id="cbp-modal-img-url" value="">
                        <input type="hidden" id="cbp-modal-img-id"  value="">
                    </div>
                </div>

                <!-- Fields -->
                <div class="cbp-modal-fields-col">
                    <div class="cbp-field-row">
                        <label class="cbp-field-label" for="cbp-modal-title-field">Title</label>
                        <input type="text" id="cbp-modal-title-field" class="widefat" placeholder="Project title">
                    </div>
                    <div class="cbp-field-row">
                        <label class="cbp-field-label" for="cbp-modal-desc-field">Description</label>
                        <input type="text" id="cbp-modal-desc-field" class="widefat" placeholder="Short description">
                    </div>
                    <div class="cbp-field-row">
                        <label class="cbp-field-label" for="cbp-modal-cats-field">Categories
                            <small>(comma-separated, e.g. <code>web, design</code>)</small>
                        </label>
                        <input type="text" id="cbp-modal-cats-field" class="widefat" placeholder="web, design, print">
                    </div>
                    <div class="cbp-field-row">
                        <label class="cbp-field-label" for="cbp-modal-link-field">Link URL <small>(optional)</small></label>
                        <input type="text" id="cbp-modal-link-field" class="widefat" placeholder="https://...">
                    </div>
                    <div class="cbp-field-row cbp-field-row-half">
                        <div>
                            <label class="cbp-field-label" for="cbp-modal-link-type">Link Type</label>
                            <select id="cbp-modal-link-type" class="widefat">
                                <option value="lightbox">Lightbox</option>
                                <option value="singlePage">Single Page</option>
                                <option value="singlePageInline">Single Page Inline</option>
                                <option value="external">External Link</option>
                            </select>
                        </div>
                        <div>
                            <label class="cbp-field-label" for="cbp-modal-size">Item Size</label>
                            <select id="cbp-modal-size" class="widefat">
                                <option value="1x1">1×1 (default)</option>
                                <option value="2x1">2×1 (wide)</option>
                                <option value="1x2">1×2 (tall)</option>
                                <option value="2x2">2×2 (large)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            </div><!-- #cbp-image-fields -->

        </div>
        <div class="cbp-modal-footer">
            <button type="button" id="cbp-modal-cancel" class="button">Cancel</button>
            <button type="button" id="cbp-modal-save"   class="button button-primary">Add to Portfolio</button>
        </div>
    </div>
</div>
<div id="cbp-modal-overlay" style="display:none"></div>

<style>
/* ── Item grid ── */
#cbp-item-grid { display:flex; flex-wrap:wrap; gap:12px; min-height:60px; }
.cbp-empty-state { width:100%; padding:30px; text-align:center; background:#f6f7f7; border:2px dashed #c3c4c7; border-radius:6px; color:#666; }
.cbp-item-card { width:160px; border:1px solid #c3c4c7; border-radius:6px; background:#fff; overflow:hidden; position:relative; box-shadow:0 1px 3px rgba(0,0,0,.06); transition:box-shadow .15s; }
.cbp-item-card:hover { box-shadow:0 2px 8px rgba(0,0,0,.12); }
.cbp-item-thumb { height:100px; background:#f0f0f1; overflow:hidden; }
.cbp-item-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
.cbp-item-thumb-placeholder { height:100%; display:flex; align-items:center; justify-content:center; color:#999; font-size:12px; }
.cbp-item-thumb-shortcode { height:100%; display:flex; align-items:center; justify-content:center; background:#e8f0fe; color:#2271b1; font-size:20px; font-weight:700; font-family:monospace; }
.cbp-item-info { padding:8px 8px 4px; }
.cbp-item-title { font-size:12px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cbp-item-cats  { font-size:11px; color:#888; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cbp-item-actions { display:flex; gap:4px; padding:4px 8px 8px; }
.cbp-item-count { font-size:12px; font-weight:normal; background:#2271b1; color:#fff; padding:2px 8px; border-radius:20px; margin-left:8px; }
.cbp-shortcode-box { margin-bottom:12px; }
.cbp-shortcode-box label { display:block; font-weight:600; margin-bottom:4px; }
.cbp-shortcode-box input { background:#f6f7f7; cursor:pointer; font-family:monospace; }
.cbp-save-btn { width:100%; justify-content:center; margin-top:4px; }
.cbp-sidebar-inside { padding-bottom:14px; }
.cbp-advanced-box summary::-webkit-details-marker { display:none; }
.cbp-advanced-box .inside { padding-top:0; }

/* ── Modal ── */
#cbp-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:99998; }
#cbp-item-modal { position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); width:min(780px,95vw); max-height:90vh; overflow-y:auto; background:#fff; border-radius:8px; z-index:99999; box-shadow:0 8px 40px rgba(0,0,0,.25); }
#cbp-item-modal-inner { display:flex; flex-direction:column; }
.cbp-modal-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #ddd; }
.cbp-modal-header h2 { margin:0; font-size:18px; }
#cbp-modal-close { background:none; border:none; font-size:24px; cursor:pointer; color:#666; line-height:1; padding:0; }
.cbp-modal-body { padding:20px; }
.cbp-modal-cols { display:flex; gap:20px; }
.cbp-modal-image-col { width:180px; flex-shrink:0; }
.cbp-modal-fields-col { flex:1; min-width:0; }
#cbp-modal-thumb { width:160px; height:120px; background:#f0f0f1; border:2px dashed #c3c4c7; border-radius:4px; display:flex; align-items:center; justify-content:center; margin-bottom:8px; overflow:hidden; font-size:12px; color:#888; text-align:center; }
#cbp-modal-thumb img { width:100%; height:100%; object-fit:cover; }
.cbp-field-row { margin-bottom:14px; }
.cbp-field-row-half { display:flex; gap:12px; }
.cbp-field-row-half > div { flex:1; }
.cbp-field-label { display:block; font-weight:600; margin-bottom:4px; font-size:13px; }
.cbp-modal-footer { display:flex; justify-content:flex-end; gap:8px; padding:14px 20px; border-top:1px solid #ddd; background:#f6f7f7; border-radius:0 0 8px 8px; }
</style>

<script>
(function($){
    /* ── Item store ──────────────────────────────────────────────────────── */
    var items = <?php echo wp_json_encode( $parsed_items ); ?>;
    var editingIndex = -1;
    var mediaUploader;

    /* ── Render grid ─────────────────────────────────────────────────────── */
    function renderGrid() {
        var $grid = $('#cbp-item-grid');
        $grid.empty();
        if ( items.length === 0 ) {
            $grid.html('<div class="cbp-empty-state" id="cbp-empty-state"><p>🖼 No items yet. Click <strong>Add Item</strong> to get started.</p></div>');
        } else {
            $.each(items, function(i, item){
                var thumb, titleHtml;
                var cats = item.categories ? item.categories.replace(/,/g,' · ') : '';

                if ( item.item_type === 'shortcode' ) {
                    thumb     = '<div class="cbp-item-thumb-shortcode">[sc]</div>';
                    var scLabel = item.shortcode ? item.shortcode.substring(0, 28) : '[shortcode]';
                    titleHtml = '<div class="cbp-item-title"><code style="font-size:10px">'+escHtml(scLabel)+'</code></div>';
                } else {
                    thumb = item.image_url
                        ? '<img src="'+item.image_url+'" alt="">'
                        : '<span>No image</span>';
                    titleHtml = item.title
                        ? '<div class="cbp-item-title">'+escHtml(item.title)+'</div>'
                        : '<div class="cbp-item-title"><em style="color:#999">No title</em></div>';
                }

                $grid.append(
                    '<div class="cbp-item-card" data-index="'+i+'">'
                    + '<div class="cbp-item-thumb">'+thumb+'</div>'
                    + '<div class="cbp-item-info">'+titleHtml
                    + (cats ? '<div class="cbp-item-cats">'+escHtml(cats)+'</div>' : '')
                    + '</div>'
                    + '<div class="cbp-item-actions">'
                    + '<button type="button" class="button button-small cbp-edit-item" data-index="'+i+'">Edit</button>'
                    + '<button type="button" class="button button-small cbp-delete-item" data-index="'+i+'">✕</button>'
                    + '</div></div>'
                );
            });
        }
        $('#cbp-item-count').text(items.length + ' item' + (items.length===1?'':'s'));
    }

    /* ── Item type toggle ────────────────────────────────────────────────── */
    function setItemType( type ) {
        if ( type === 'shortcode' ) {
            $('#cbp-image-fields').hide();
            $('#cbp-shortcode-fields').show();
            $('input[name="cbp_item_type_radio"][value="shortcode"]').prop('checked', true);
        } else {
            $('#cbp-shortcode-fields').hide();
            $('#cbp-image-fields').show();
            $('input[name="cbp_item_type_radio"][value="image"]').prop('checked', true);
        }
    }

    $('input[name="cbp_item_type_radio"]').on('change', function(){
        setItemType( $(this).val() );
        if ( $(this).val() === 'shortcode' ) {
            $('#cbp-modal-shortcode-field').focus();
        } else {
            $('#cbp-modal-title-field').focus();
        }
    });

    /* ── Modal open / close ──────────────────────────────────────────────── */
    function openModal( idx ) {
        editingIndex = (idx === undefined) ? -1 : idx;
        var item = (editingIndex >= 0) ? items[editingIndex] : {};

        $('#cbp-modal-title').text( editingIndex >= 0 ? 'Edit Item' : 'Add Item' );
        $('#cbp-modal-save').text( editingIndex >= 0 ? 'Save Changes' : 'Add to Portfolio' );

        var type = item.item_type || 'image';
        setItemType( type );

        if ( type === 'shortcode' ) {
            $('#cbp-modal-shortcode-field').val( item.shortcode || '' );
            $('#cbp-modal-sc-cats').val( item.categories || '' );
            $('#cbp-modal-sc-size').val( item.size || '1x1' );
        } else {
            $('#cbp-modal-img-url').val( item.image_url || '' );
            $('#cbp-modal-img-id').val(  item.image_id  || '' );
            $('#cbp-modal-title-field').val( item.title      || '' );
            $('#cbp-modal-desc-field').val(  item.desc       || '' );
            $('#cbp-modal-cats-field').val(  item.categories || '' );
            $('#cbp-modal-link-field').val(  item.link       || '' );
            $('#cbp-modal-link-type').val(   item.link_type  || 'lightbox' );
            $('#cbp-modal-size').val(        item.size       || '1x1' );
            updateThumbPreview( item.image_url || '' );
        }

        $('#cbp-modal-overlay, #cbp-item-modal').fadeIn(150);
    }

    function closeModal() {
        $('#cbp-modal-overlay, #cbp-item-modal').fadeOut(150);
        editingIndex = -1;
    }

    function updateThumbPreview( url ) {
        if ( url ) {
            $('#cbp-modal-thumb').html('<img src="'+url+'" alt="">');
            $('#cbp-remove-image').show();
        } else {
            $('#cbp-modal-thumb').html('<span>No image selected</span>');
            $('#cbp-remove-image').hide();
        }
    }

    /* ── Media picker ────────────────────────────────────────────────────── */
    $('#cbp-pick-image').on('click', function(){
        if ( mediaUploader ) {
            mediaUploader.open(); return;
        }
        mediaUploader = wp.media({
            title: 'Select Image',
            button: { text: 'Use this image' },
            multiple: false,
            library: { type: 'image' }
        });
        mediaUploader.on('select', function(){
            var att = mediaUploader.state().get('selection').first().toJSON();
            $('#cbp-modal-img-url').val( att.url );
            $('#cbp-modal-img-id').val(  att.id  );
            updateThumbPreview( att.url );
        });
        mediaUploader.open();
    });

    $('#cbp-remove-image').on('click', function(){
        $('#cbp-modal-img-url').val('');
        $('#cbp-modal-img-id').val('');
        updateThumbPreview('');
    });

    /* ── Save item ───────────────────────────────────────────────────────── */
    $('#cbp-modal-save').on('click', function(){
        var type = $('input[name="cbp_item_type_radio"]:checked').val();
        var rawItem = (editingIndex >= 0 && items[editingIndex]._raw_item) ? items[editingIndex]._raw_item : {};
        var item;

        if ( type === 'shortcode' ) {
            var sc   = $('#cbp-modal-shortcode-field').val().trim();
            var cats = $('#cbp-modal-sc-cats').val().trim();
            var size = $('#cbp-modal-sc-size').val();

            if ( ! sc ) {
                alert('Please enter a shortcode.');
                $('#cbp-modal-shortcode-field').focus();
                return;
            }

            item = {
                item_type:  'shortcode',
                shortcode:  sc,
                categories: cats,
                size:       size,
                image_url:  '',
                title:      '',
                raw_html:   buildShortcodeItemHtml( sc, cats, size ),
                _raw_item:  rawItem
            };
        } else {
            var imgUrl = $('#cbp-modal-img-url').val().trim();
            var imgId  = $('#cbp-modal-img-id').val().trim();
            var title  = $('#cbp-modal-title-field').val().trim();
            var desc   = $('#cbp-modal-desc-field').val().trim();
            var cats   = $('#cbp-modal-cats-field').val().trim();
            var link   = $('#cbp-modal-link-field').val().trim();
            var ltype  = $('#cbp-modal-link-type').val();
            var size   = $('#cbp-modal-size').val();

            item = {
                item_type:  'image',
                image_url:  imgUrl,
                image_id:   imgId,
                title:      title,
                desc:       desc,
                categories: cats,
                link:       link,
                link_type:  ltype,
                size:       size,
                raw_html:   buildItemHtml( imgUrl, title, desc, cats, link, ltype, size ),
                _raw_item:  rawItem
            };
        }

        if ( editingIndex >= 0 ) {
            items[editingIndex] = item;
        } else {
            items.push(item);
        }

        renderGrid();
        closeModal();
    });

    /* ── Build shortcode item HTML ───────────────────────────────────────── */
    function buildShortcodeItemHtml( shortcode, cats, size ) {
        var catClasses = '';
        if ( cats ) {
            catClasses = ' ' + $.trim( cats ).replace(/\s*,\s*/g, ' ').replace(/\s+/g, ' ');
        }
        var sizeAttr = (size && size !== '1x1') ? ' data-cbp-size="'+size+'"' : '';
        return '<div class="cbp-item'+catClasses+'"'+sizeAttr+'>'+shortcode+'</div>';
    }

    /* ── Build image item HTML ───────────────────────────────────────────── */
    function buildItemHtml( imgUrl, title, desc, cats, link, ltype, size ) {
        var catClasses = '';
        if ( cats ) {
            catClasses = ' ' + $.trim( cats ).replace(/\s*,\s*/g, ' ').replace(/\s+/g, ' ');
        }
        var sizeAttr = (size && size !== '1x1') ? ' data-cbp-size="'+size+'"' : '';

        var captionWrap = '';
        if ( title || desc ) {
            captionWrap  = '<div class="cbp-caption-activeWrap">';
            captionWrap += '<div class="cbp-l-caption-alignCenter">';
            captionWrap += '<div class="cbp-l-caption-body">';
            if ( title ) captionWrap += '<div class="cbp-l-caption-title">'+escHtml(title)+'</div>';
            if ( desc  ) captionWrap += '<div class="cbp-l-caption-desc">'+escHtml(desc)+'</div>';
            captionWrap += '</div></div></div>';
        }

        var imgTag = imgUrl ? '<img src="'+imgUrl+'" alt="'+escHtml(title)+'">' : '';
        var defaultWrap = '<div class="cbp-caption-defaultWrap">'+imgTag+'</div>';

        var innerCaption = '<div class="cbp-caption">'+defaultWrap+captionWrap+'</div>';

        if ( link ) {
            var linkClass = '';
            if ( ltype === 'lightbox' ) linkClass = ' class="cbp-lightbox"';
            else if ( ltype === 'singlePage' ) linkClass = ' class="cbp-singlePage"';
            else if ( ltype === 'singlePageInline' ) linkClass = ' class="cbp-singlePageInline"';
            var target = (ltype === 'external') ? ' target="_blank"' : '';
            innerCaption = '<a href="'+link+'"'+linkClass+target+'>'+innerCaption+'</a>';
        }

        return '<div class="cbp-item'+catClasses+'"'+sizeAttr+'>'+innerCaption+'</div>';
    }

    /* ── Delete item ─────────────────────────────────────────────────────── */
    $(document).on('click', '.cbp-delete-item', function(){
        var idx = parseInt( $(this).data('index'), 10 );
        if ( confirm('Remove this item?') ) {
            items.splice(idx, 1);
            renderGrid();
        }
    });

    /* ── Edit item ───────────────────────────────────────────────────────── */
    $(document).on('click', '.cbp-edit-item', function(){
        openModal( parseInt( $(this).data('index'), 10 ) );
    });

    /* ── Open add modal ──────────────────────────────────────────────────── */
    $('#cbp-add-item-btn').on('click', function(){ openModal(); });
    $('#cbp-modal-close, #cbp-modal-cancel, #cbp-modal-overlay').on('click', closeModal);

    /* ── Keyboard close ──────────────────────────────────────────────────── */
    $(document).on('keydown', function(e){ if (e.key==='Escape') closeModal(); });

    /* ── Serialize before submit ─────────────────────────────────────────── */
    $('#cbp-edit-form').on('submit', function(){
        var toSave = items.map(function(item, i){
            var raw = item._raw_item || {};
            return {
                id:           raw.id           || '',
                sort:         i,
                page:         raw.page         || 0,
                items:        item.raw_html    || '',
                isLoadMore:   raw.isLoadMore   || '0',
                isSinglePage: raw.isSinglePage || ''
            };
        });
        $('#cbp-items-json').val( JSON.stringify(toSave) );
    });

    /* ── Utility ─────────────────────────────────────────────────────────── */
    function escHtml(s){
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── Initial render ──────────────────────────────────────────────────── */
    renderGrid();

})(jQuery);
</script>
