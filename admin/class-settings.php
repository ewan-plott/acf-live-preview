<?php
namespace ACFLivePreview;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Settings page for ACF Live Preview plugin
 */
class Settings {
    public const PAGE       = 'acf-live-preview';
    public const OPTION_KEY = 'acf_live_preview_options';

    /**
     * Initialize settings page hooks
     */
    public static function boot(): void {
        add_action('admin_menu',  [__CLASS__, 'menu']);
        add_action('admin_init',  [__CLASS__, 'register']);
    }

    /**
     * Register the options page in the WP admin menu
     */
    public static function menu(): void {
        add_options_page(
            __('ACF Live Preview', 'acf-live-preview'),
            __('ACF Live Preview', 'acf-live-preview'),
            'manage_options',
            self::PAGE,
            [__CLASS__, 'render']
        );
    }

    /**
     * Register settings, sections, and fields
     */
    public static function register(): void {
        // Register the main setting with defaults
        register_setting(self::PAGE, self::OPTION_KEY, [
            'type'              => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize'],
            'default'           => [
                'css_url' => '', 
                'js_url' => '',
                'template_paths' => "templates/blocks/{layout}/{layout}.php\ntemplates/blocks/{layout}.php\nblocks/{layout}/{layout}.php\nblocks/{layout}.php"
            ],
        ]);

        // Assets section
        add_settings_section('assets', __('Global Theme Assets', 'acf-live-preview'), '__return_false', self::PAGE);

        add_settings_field('css_url', __('CSS bundle URL', 'acf-live-preview'), [__CLASS__, 'field_text'], self::PAGE, 'assets', [
            'key' => 'css_url',
            'placeholder' => '/assets/dist/css/style.min.css',
            'desc' => __('Absolute URL or theme-relative path.', 'acf-live-preview'),
        ]);

        add_settings_field('js_url', __('JS bundle URL', 'acf-live-preview'), [__CLASS__, 'field_text'], self::PAGE, 'assets', [
            'key' => 'js_url',
            'placeholder' => '/assets/dist/js/app.min.js',
            'desc' => __('Absolute URL or theme-relative path. Leave empty if not needed.', 'acf-live-preview'),
        ]);

        // Templates section
        add_settings_section('templates', __('Template Search Paths', 'acf-live-preview'), '__return_false', self::PAGE);
        
        add_settings_field('template_paths', __('Template hierarchy', 'acf-live-preview'), [__CLASS__, 'field_textarea'], self::PAGE, 'templates', [
            'key' => 'template_paths',
            'placeholder' => "templates/blocks/{layout}/{layout}.php\ntemplates/blocks/{layout}.php",
            'desc' => __('One path per line. Use {layout} placeholder for layout name. Searched in order.', 'acf-live-preview'),
        ]);
    }

    /**
     * Sanitize settings input
     */
    public static function sanitize($input): array {
        $out = [];
        $out['css_url'] = isset($input['css_url']) ? esc_url_raw(trim($input['css_url'])) : '';
        $out['js_url']  = isset($input['js_url'])  ? esc_url_raw(trim($input['js_url']))  : '';
        $out['template_paths'] = isset($input['template_paths']) ? sanitize_textarea_field($input['template_paths']) : '';
        return $out;
    }

    /**
     * Render text input field
     */
    public static function field_text(array $args): void {
        $opts = get_option(self::OPTION_KEY, []);
        $key  = $args['key'];
        $val  = isset($opts[$key]) ? esc_attr($opts[$key]) : '';
        $ph   = isset($args['placeholder']) ? esc_attr($args['placeholder']) : '';
        $desc = isset($args['desc']) ? esc_html($args['desc']) : '';
        echo '<input type="text" class="regular-text code" name="'.self::OPTION_KEY.'['.$key.']" value="'.$val.'" placeholder="'.$ph.'">';
        if ($desc) echo '<p class="description">'.$desc.'</p>';
    }

    /**
     * Render textarea input field
     */
    public static function field_textarea(array $args): void {
        $opts = get_option(self::OPTION_KEY, []);
        $key  = $args['key'];
        $val  = isset($opts[$key]) ? esc_textarea($opts[$key]) : '';
        $ph   = isset($args['placeholder']) ? esc_attr($args['placeholder']) : '';
        $desc = isset($args['desc']) ? esc_html($args['desc']) : '';
        echo '<textarea class="large-text code" rows="5" name="'.self::OPTION_KEY.'['.$key.']" placeholder="'.$ph.'">'.$val.'</textarea>';
        if ($desc) echo '<p class="description">'.$desc.'</p>';
    }

    /**
     * Render the settings page
     */
    public static function render(): void {
        echo '<div class="wrap"><h1>'.esc_html__('ACF Live Preview', 'acf-live-preview').'</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields(self::PAGE);
        do_settings_sections(self::PAGE);
        submit_button();
        echo '</form></div>';
    }
}
