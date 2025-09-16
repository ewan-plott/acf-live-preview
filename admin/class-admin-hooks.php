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
    // Only on post editor screens
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;

    // Styles
    wp_enqueue_style(
        'acf-live-preview-admin',
        ACF_LIVE_PREVIEW_URL . 'assets/css/admin-preview.css',
        [],
        ACF_LIVE_PREVIEW_VERSION
    );

    // Scripts
    wp_enqueue_script(
        'acf-live-preview-admin',
        ACF_LIVE_PREVIEW_URL . 'assets/js/admin-preview.js',
        [],
        ACF_LIVE_PREVIEW_VERSION,
        true
    );

    // Localize AJAX (AJAX endpoint, nonce, current post id, etc.)
    wp_localize_script( 'acf-live-preview-admin', 'ACF_LIVE_PREVIEW', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'acf_live_preview_nonce' ),
        'post_id'  => get_the_ID(),
    ] );
}

}
