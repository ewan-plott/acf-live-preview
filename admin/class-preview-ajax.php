<?php
namespace ACFLivePreview;

if ( ! defined( 'ABSPATH' ) ) exit;

class PreviewAjax {
    public static function boot(): void {
        add_action( 'wp_ajax_acf_live_preview_render', [ __CLASS__, 'handle' ] );
    }

public static function handle(): void {
    check_ajax_referer( 'acf_live_preview_nonce', 'nonce' );

    // Security: Check payload size to prevent DoS
    $max_payload_size = 1024 * 1024; // 1MB limit
    $payload = isset($_POST['flex']) ? wp_unslash($_POST['flex']) : '[]';
    if ( strlen($payload) > $max_payload_size ) {
        wp_send_json_error([ 'html' => '<p>Payload too large.</p>' ], 413);
    }

    // Parse JSON with depth limit for security
    $rows = json_decode($payload, true, 10); // Max depth: 10
    if ( json_last_error() !== JSON_ERROR_NONE || ! is_array($rows) ) {
        wp_send_json_error([ 'html' => '<p>Invalid payload format.</p>' ], 400);
    }

    $debug   = ( isset($_POST['_acflp_debug']) && (string)$_POST['_acflp_debug'] === '1' )
            || ( defined('ACF_LIVE_PREVIEW_DEBUG') && ACF_LIVE_PREVIEW_DEBUG );
    
    if ( $debug ) {
        error_log('[ACF LP] Raw rows data: ' . print_r($rows, true));
    }

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    
    // Stronger capability checks
    if ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error([ 'html' => '<p>Permission denied.</p>' ], 403);
    }
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error([ 'html' => '<p>Insufficient permissions.</p>' ], 403);
    }

    $post = $post_id ? get_post($post_id) : null;
    if ( $post ) setup_postdata($post);

    $assets = [ 'css' => [], 'js' => [] ];
    $dbg    = [];

    ob_start();

    foreach ( $rows as $i => $row ) {
        $layout = isset($row['layout']) ? sanitize_key($row['layout']) : '';
        $fields = ( isset($row['fields']) && is_array($row['fields']) ) ? $row['fields'] : [];
        $types  = ( isset($row['types'])  && is_array($row['types'])  ) ? $row['types']  : [];

        if ( ! $layout ) continue;

        // Transform raw field data into template-ready formats (images, galleries, etc.)
        foreach ( $fields as $k => $v ) {
            $field_type = $types[$k] ?? self::guess_type_from_value($v);
            
            if ( $debug && $field_type === 'repeater' ) {
                error_log('[ACF LP] Field "' . $k . '" - Type: ' . $field_type . ' - Raw value: "' . print_r($v, true) . '" - Value length: ' . (is_string($v) ? strlen($v) : 'N/A'));
            }
            
            $fields[$k] = self::format_field_value($field_type, $v);
        }

        // Store debug info for later output
        if ( $debug ) {
            $dbg[] = [ 'row' => $i, 'layout' => $layout, 'fields_count' => count($fields) ];
        }

        $slug = str_replace('_', '-', $layout);
        
        // Get template candidates from settings or use defaults
        $template = self::locate_template($layout, $slug, $post);
        
        // Debug template resolution
        if ( $debug ) {
            error_log('[ACF LP] Layout: ' . $layout . ', Slug: ' . $slug . ', Template found: ' . ($template ?: 'NONE'));
        }

        // Use fallback template if none found
        if ( ! $template ) {
            $template = ACF_LIVE_PREVIEW_PATH . 'templates/fallback-layout.php';
            if ( $debug ) {
                error_log('[ACF LP] Using fallback template: ' . $template);
            }
        }
        
        $vars = apply_filters('acf_live_preview_template_vars', [
            'fields'  => $fields,
            'layout'  => $layout,
            'slug'    => $slug,
            'post'    => $post,
            'post_id' => $post_id,
        ], $layout, $slug, $post);

        // Handle plott_get_part vs regular template inclusion
        try {
            if ( function_exists('plott_get_part') && !str_ends_with($template, '.php') ) {
                // This is a plott_get_part template slug - merge vars for direct field access
                $args = array_merge($vars, is_array($fields) ? $fields : []);
                plott_get_part( $template, $args );
            } else {
                // Standard PHP template inclusion
                if ( !file_exists($template) ) {
                    echo '<div class="preview-error">Template file not found: ' . esc_html($template) . '</div>';
                    continue;
                }
                
                (function($__template, $__vars){
                    $fields  = $__vars['fields'];
                    
                    // Extract fields as variables so templates can use $background_image, $content, etc.
                    if ( is_array($fields) ) {
                        extract( $fields, EXTR_OVERWRITE );
                    }

                    $layout  = $__vars['layout'];
                    $slug    = $__vars['slug'];
                    $post    = $__vars['post'];
                    $post_id = $__vars['post_id'];
                    include $__template;
                })($template, $vars);
            }
        } catch ( \Throwable $e ) {
            echo '<div class="preview-error">Template error: ' . esc_html($e->getMessage()) . '</div>';
            if ( $debug ) {
                error_log('[ACF LP] Template inclusion error: ' . $e->getMessage());
            }
        }
    }

    $html = ob_get_clean();
    if ( $post ) wp_reset_postdata();

    $resp = [
        'html'   => $html ?: '<em>No output.</em>',
        'assets' => $assets,
    ];
    if ( $debug ) $resp['debug'] = $dbg;

    wp_send_json_success( $resp );
}

/**
 * Expand raw editor values into template-ready arrays for special types.
 */
private static function format_field_value( string $type, $value ) {
    error_log($type);
    switch ($type) {
        case 'image':
            // Return just the ID to match ACF's default behavior
            $id = is_numeric($value) ? (int)$value : 0;
            return $id ?: null;

        case 'file':
            $id = is_numeric($value) ? (int)$value : 0;
            if ( ! $id ) return null;
            if ( function_exists('acf_get_attachment') ) {
                $arr = acf_get_attachment($id);
                return is_array($arr) ? $arr : null;
            }
            $url = wp_get_attachment_url($id);
            if ( ! $url ) return null;
            return [
                'id'    => $id,
                'url'   => $url,
                'title' => get_the_title($id),
            ];

        case 'gallery':
            // Expect an array of IDs; expand each to ACF attachment array if possible
            $ids = is_array($value) ? array_filter(array_map('intval', $value)) : [];
            $out = [];
            foreach ($ids as $id) {
                if ( function_exists('acf_get_attachment') ) {
                    $arr = acf_get_attachment($id);
                    if ( is_array($arr) ) $out[] = $arr;
                } else {
                    $url = wp_get_attachment_url($id);
                    if ($url) {
                        $out[] = [
                            'id'    => $id,
                            'url'   => $url,
                            'alt'   => get_post_meta($id, '_wp_attachment_image_alt', true) ?: '',
                            'title' => get_the_title($id),
                        ];
                    }
                }
            }
            return $out;

        case 'repeater':
            if ( defined('ACF_LIVE_PREVIEW_DEBUG') && ACF_LIVE_PREVIEW_DEBUG ) {
                error_log('[ACF LP] Repeater field value (raw): ' . print_r($value, true));
                error_log('[ACF LP] Repeater field type: ' . gettype($value));
            }
            
            // If value is a JSON string, decode it
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    if ( defined('ACF_LIVE_PREVIEW_DEBUG') && ACF_LIVE_PREVIEW_DEBUG ) {
                        error_log('[ACF LP] Repeater decoded successfully. Row count: ' . count($decoded));
                    }
                    
                    // Format nested field values (images, etc.)
                    foreach ($decoded as $rowIdx => $row) {
                        if (is_array($row)) {
                            foreach ($row as $fieldKey => $fieldVal) {
                                // Detect if value is an image/file ID
                                $nestedType = self::guess_type_from_value($fieldVal);
                                $decoded[$rowIdx][$fieldKey] = self::format_field_value($nestedType, $fieldVal);
                                
                                if ( defined('ACF_LIVE_PREVIEW_DEBUG') && ACF_LIVE_PREVIEW_DEBUG ) {
                                    error_log('[ACF LP] Repeater row ' . $rowIdx . ', field "' . $fieldKey . '": type=' . $nestedType . ', formatted=' . print_r($decoded[$rowIdx][$fieldKey], true));
                                }
                            }
                        }
                    }
                    
                    return $decoded;
                }
            }
            
            // Already an array
            if (is_array($value)) {
                if ( defined('ACF_LIVE_PREVIEW_DEBUG') && ACF_LIVE_PREVIEW_DEBUG ) {
                    error_log('[ACF LP] Repeater row count: ' . count($value));
                }
                return $value;
            }
            
            // Fallback to empty array
            return [];


        // Add more cases if you need (relationship, post_object, taxonomy etc.)

        default:
            return $value;
    }
}


/**
 * Locate template using configurable hierarchy from settings
 */
private static function locate_template(string $layout, string $slug, ?\WP_Post $post): ?string {
    $opts = get_option('acf_live_preview_options', []);
    $template_paths = $opts['template_paths'] ?? "templates/blocks/{layout}/{layout}.php\ntemplates/blocks/{layout}.php\nblocks/{layout}/{layout}.php\nblocks/{layout}.php";
    
    $candidates = array_filter(array_map('trim', explode("\n", $template_paths)));
    
    // Debug logging
    $is_debug = defined('ACF_LIVE_PREVIEW_DEBUG') && ACF_LIVE_PREVIEW_DEBUG;
    if ( $is_debug ) {
        error_log('[ACF LP] Template paths from settings: ' . $template_paths);
        error_log('[ACF LP] Candidates: ' . implode(', ', $candidates));
    }
    
    // Replace placeholders in each candidate path
    foreach ($candidates as $candidate) {
        $path = str_replace(['{layout}', '{slug}'], [$slug, $slug], $candidate);
        
        if ( $is_debug ) {
            error_log('[ACF LP] Trying path: ' . $path);
        }
        
        // Try theme template first
        $template = locate_template($path);
        if ($template) {
            if ( $is_debug ) error_log('[ACF LP] Found theme template: ' . $template);
            return $template;
        }
        
        // Try plott_get_part compatibility (without .php extension)
        if (function_exists('plott_get_part') && str_ends_with($path, '.php')) {
            $tpl_slug = substr($path, 0, -4); // Remove .php
            if ( $is_debug ) error_log('[ACF LP] Using plott_get_part slug: ' . $tpl_slug);
            // plott_get_part handles its own template location
            return $tpl_slug; // Special marker for plott_get_part
        }
    }
    
    // Final fallback to plugin's default template
    $fallback = ACF_LIVE_PREVIEW_PATH . 'templates/fallback-layout.php';
    if ( $is_debug ) {
        error_log('[ACF LP] Trying fallback: ' . $fallback . ' (exists: ' . (file_exists($fallback) ? 'yes' : 'no') . ')');
    }
    return file_exists($fallback) ? $fallback : null;
}

private static function guess_type_from_value($v): string {
    if (is_numeric($v)) {
        $mime = get_post_mime_type((int)$v);
        if (is_string($mime)) {
            return str_starts_with($mime, 'image/') ? 'image' : 'file';
        }
        // Unknown attachment; default to file
        return 'file';
    }
    if (is_array($v) && $v && is_int(reset($v))) {
        return 'gallery';
    }
    return '';
}

}
