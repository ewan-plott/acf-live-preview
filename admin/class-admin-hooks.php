<?php
namespace ACFLivePreview;

if ( ! defined( 'ABSPATH' ) ) exit;

class AdminHooks {
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

    public static function render_meta_box( \WP_Post $post ): void { 
        ?>
            <div id="acf-live-preview" style="padding:.5rem 0;">
            <div id="acf-live-preview__content" style="border:1px solid #e2e8f0;border-radius:8px;padding:12px;background:#fff;min-height:120px;">
                <em>Waiting for changes…</em>
            </div>
            </div>
        <?php 
    }


    public static function enqueue( string $hook ): void {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;

        // --- CSS
        $css_rel = 'assets/css/admin-preview.css';
        $css_abs = ACF_LIVE_PREVIEW_PATH . $css_rel;
        wp_enqueue_style(
            'acf-live-preview-admin',
            ACF_LIVE_PREVIEW_URL . $css_rel,
            [],
            file_exists($css_abs) ? filemtime($css_abs) : ACF_LIVE_PREVIEW_VERSION
        );

        // --- JS (production module always)
        $prod_rel = 'assets/js/admin-preview.js';
        $prod_abs = ACF_LIVE_PREVIEW_PATH . $prod_rel;
        $prod_src = ACF_LIVE_PREVIEW_URL . $prod_rel;
        $prod_ver = file_exists($prod_abs) ? filemtime($prod_abs) : ACF_LIVE_PREVIEW_VERSION;

        // Register then add type=module (more reliable)
        wp_register_script('acf-live-preview-admin', $prod_src, [], $prod_ver, true);
        if ( function_exists('wp_script_add_data') ) {
            wp_script_add_data('acf-live-preview-admin', 'type', 'module');
        }
        // Belt & braces: re-add type attribute if something strips it
        add_filter('script_loader_tag', function($tag, $handle, $src) {
            if ($handle !== 'acf-live-preview-admin') return $tag;
            if (strpos($tag, ' type=') === false) {
                $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
            }
            return $tag;
        }, 100, 3);

        wp_enqueue_script('acf-live-preview-admin');

        // --- Optional debug sidebar (IIFE) in addition to the module
        $is_debug = defined('ACF_LIVE_PREVIEW_DEBUG') && ACF_LIVE_PREVIEW_DEBUG;
        if ( $is_debug ) {
            $dbg_rel = 'assets/js/debug-sidebar.js'; // your JSON-in-sidebar IIFE
            $dbg_abs = ACF_LIVE_PREVIEW_PATH . $dbg_rel;
            $dbg_src = ACF_LIVE_PREVIEW_URL . $dbg_rel;
            $dbg_ver = file_exists($dbg_abs) ? filemtime($dbg_abs) : ACF_LIVE_PREVIEW_VERSION;

            wp_enqueue_script('acf-live-preview-debug-sidebar', $dbg_src, [], $dbg_ver, true);
        }

        // --- Compute post ID
        $post_id = 0;
        if ( isset($_GET['post']) ) {
            $post_id = absint($_GET['post']);
        } elseif ( isset($_POST['post_ID']) ) {
            $post_id = absint($_POST['post_ID']);
        } elseif ( isset($GLOBALS['post']->ID) ) {
            $post_id = (int) $GLOBALS['post']->ID;
        }

        // --- Global assets from options
        $opts   = get_option('acf_live_preview_options', []); // ['css_url' => '', 'js_url' => '']
        $cssOpt = isset($opts['css_url']) ? trim($opts['css_url']) : '';
        $jsOpt  = isset($opts['js_url'])  ? trim($opts['js_url'])  : '';

        $normalize = static function (string $url): string {
            if ($url === '') return '';
            if (parse_url($url, PHP_URL_SCHEME)) return $url; // already absolute
            $rel = ltrim($url, '/');
            return get_theme_file_uri($rel);                  // resolve against current theme
        };

        $global_assets = ['css' => [], 'js' => []];
        if ($u = $normalize($cssOpt)) $global_assets['css'][] = $u;
        if ($u = $normalize($jsOpt))  $global_assets['js'][]  = $u;

        // Allow overrides
        $global_assets = apply_filters('acf_live_preview_global_assets', $global_assets, $post_id);

        // --- Localize config for both scripts
        wp_localize_script('acf-live-preview-admin', 'ACF_LIVE_PREVIEW', [
            'ajax_url'      => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('acf_live_preview_nonce'),
            'post_id'       => $post_id,
            'debug'         => $is_debug,
            'global_assets' => $global_assets,
        ]);

        if ( $is_debug ) {
            // Optional: also localize for the debug sidebar (same object)
            wp_localize_script('acf-live-preview-debug-sidebar', 'ACF_LIVE_PREVIEW', [
                'ajax_url'      => admin_url('admin-ajax.php'),
                'nonce'         => wp_create_nonce('acf_live_preview_nonce'),
                'post_id'       => $post_id,
                'debug'         => $is_debug,
                'global_assets' => $global_assets,
            ]);
        }
    }


}