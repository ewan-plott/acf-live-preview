<?php
namespace ACFLivePreview;

if ( ! defined( 'ABSPATH' ) ) exit;

class Admin_Hooks {
    public static function boot(): void {
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_box' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
    }

    public static function add_meta_box(): void {
        add_meta_box(
            'acf_live_preview_meta',
            __( 'ACF Live Preview', 'acf-live-preview' ),
            [ __CLASS__, 'render_meta_box' ],
            null,        
            'side',
            'high'
        );
    }

    public static function render_meta_box( \WP_Post $post ): void { ?>
        <div id="acf-live-preview" style="padding:.5rem 0;">
            <div id="acf-live-preview__content" style="border:1px solid #e2e8f0;border-radius:8px;padding:12px;background:#fff;min-height:120px;">
                <em>Waiting for changes…</em>
            </div>
        </div>
    <?php }

public static function enqueue( string $hook ): void {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;

    // CSS
    $css_rel = 'assets/css/admin-preview.css';
    $css_abs = ACF_LIVE_PREVIEW_PATH . $css_rel;
    $css_url = ACF_LIVE_PREVIEW_URL  . $css_rel;

    wp_enqueue_style(
        'acf-live-preview-admin',
        $css_url,                                   // ✅ URL
        [],
        file_exists($css_abs) ? filemtime($css_abs) : ACF_LIVE_PREVIEW_VERSION
    );

    // JS: pick debug or prod
    $js_rel = ( defined('ACF_LIVE_PREVIEW_DEBUG') && ACF_LIVE_PREVIEW_DEBUG )
        ? 'assets/js/debug-admin-preview.js'
        : 'assets/js/admin-preview.js';

    $js_abs = ACF_LIVE_PREVIEW_PATH . $js_rel;
    $js_url = ACF_LIVE_PREVIEW_URL  . $js_rel;

    wp_enqueue_script(
        'acf-live-preview-admin',
        $js_url,                                   // ✅ URL (NOT $js_abs)
        [],
        file_exists($js_abs) ? filemtime($js_abs) : ACF_LIVE_PREVIEW_VERSION,
        true
    );

    wp_localize_script( 'acf-live-preview-admin', 'ACF_LIVE_PREVIEW', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('acf_live_preview_nonce'),
        'post_id'  => get_the_ID(),
        'debug'    => defined('ACF_LIVE_PREVIEW_DEBUG') && ACF_LIVE_PREVIEW_DEBUG,
    ] );
}


    // Localize AJAX (AJAX endpoint, nonce, current post id, etc.)
    wp_localize_script( 'acf-live-preview-admin', 'ACF_LIVE_PREVIEW', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'acf_live_preview_nonce' ),
        'post_id'  => get_the_ID(),
    ] );
}

}
