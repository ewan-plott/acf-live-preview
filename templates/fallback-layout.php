<?php
/**
 * Fallback template for layouts without specific templates
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="acf-live-preview-fallback" style="padding: 1rem; border: 2px dashed #ccc; border-radius: 4px; background: #f9f9f9;">
    <h4 style="margin: 0 0 0.5rem; color: #666;">
        Layout: <?php echo esc_html( $layout ?? 'Unknown' ); ?>
    </h4>
    
    <?php if ( ! empty( $fields ) && is_array( $fields ) ): ?>
        <div class="field-preview">
            <?php foreach ( $fields as $key => $value ): ?>
                <div style="margin-bottom: 0.5rem;">
                    <strong><?php echo esc_html( $key ); ?>:</strong>
                    <?php if ( is_array( $value ) ): ?>
                        <pre style="font-size: 11px; background: #fff; padding: 4px; margin: 2px 0;"><?php echo esc_html( json_encode( $value, JSON_PRETTY_PRINT ) ); ?></pre>
                    <?php else: ?>
                        <span><?php echo esc_html( (string) $value ); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="margin: 0; color: #999; font-style: italic;">No fields to display</p>
    <?php endif; ?>
    
    <p style="margin: 0.5rem 0 0; font-size: 11px; color: #999;">
        Create a template at: <code>templates/blocks/<?php echo esc_html( str_replace('_', '-', $layout ?? 'layout') ); ?>.php</code>
    </p>
</div>
