<?php
/*
* Plugin Name:       ACF Flexible Content Live Preview
* Description:       Provides real-time preview for ACF Flexible Content fields.
* Version:           0.0.2
* Author:            Ewan Lockwood
* Author URI:        https://plott.co.uk
* Text Domain:       acf-live-preview
*/

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

// Constants
define( 'ACF_LIVE_PREVIEW_VERSION', '0.0.2' );
define( 'ACF_LIVE_PREVIEW_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACF_LIVE_PREVIEW_URL',  plugin_dir_url( __FILE__ ) );

if ( ! defined( 'ACF_LIVE_PREVIEW_DEBUG' ) ) {
    define( 'ACF_LIVE_PREVIEW_DEBUG', true );
}

add_action( 'plugins_loaded', function () {
    if ( ! function_exists( 'acf' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-warning"><p><strong>ACF Flexible Content Live Preview</strong> needs Advanced Custom Fields to function.</p></div>';
        } );
    }

    // Require Classes
    require_once ACF_LIVE_PREVIEW_PATH . 'admin/class-admin-hooks.php';
    require_once ACF_LIVE_PREVIEW_PATH . 'admin/class-preview-ajax.php';
    require_once ACF_LIVE_PREVIEW_PATH . 'admin/class-settings.php';

    // Boot classes 
    if ( class_exists( '\\ACFLivePreview\\AdminHooks' ) ) {
        \ACFLivePreview\AdminHooks::boot();
    }

    if ( class_exists( '\\ACFLivePreview\\PreviewAjax' ) ) {
        \ACFLivePreview\PreviewAjax::boot();
    }

    if ( class_exists( '\\ACFLivePreview\\Settings' ) ) {
        \ACFLivePreview\Settings::boot();
    }
} );
