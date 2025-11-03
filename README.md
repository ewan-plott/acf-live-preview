🚧  *Intended for internal use @ [PLOTT](https://plott.co.uk/)* 🚧 


# ACF Flexible Content Live Preview

A WordPress plugin that provides live previews of ACF Flexible Content layouts directly in the post editor.

## Features
- Real-time preview of ACF Flexible Content blocks
- Modal preview window with responsive iframe
- Configurable template search paths
- Support for both standard PHP templates and custom template loaders
- Automatic field type detection and formatting
- Debug mode for development
- Security hardened AJAX endpoints

## Requirements
- WordPress 5.0+
- Advanced Custom Fields (ACF) 5.8.0+
- PHP 7.4+

## Installation
1. Upload the plugin files to /wp-content/plugins/acf-live-preview/ ( or any relative location ) 
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure template paths in Settings > ACF Live Preview

## Usage
1. Edit any post with ACF Flexible Content fields
2. Click the "more actions" button (three dots) on any layout row
3. Select "Preview layout" from the dropdown menu
4. View your content rendered in the preview modal

## Settings

Navigate to Settings > ACF Live Preview to configure:

Global Theme Assets
- CSS bundle URL: Path to your theme's main CSS file
- JS bundle URL: Path to your theme's main JavaScript file (optional)

Template Search Paths
Define where the plugin should look for layout templates. One path per line, searched in order:
Placeholders:
- {layout}: The ACF layout name (converted from snake_case to kebab-case)
- {slug}: Same as {layout} for backwards compatibility

## Template Structure

Create templates in your active theme following the configured paths. For a layout named hero_section, create:
Template Variables:
- $layout: Layout name
- $fields: Array of all field values
- $post: Current post object
- $post_id: Current post ID
- Field variables are extracted directly: $title, $background_image, etc.

## Template Loader Integration

If you're using a custom template loader like plott_get_part(), the plugin will automatically detect and use it.

## Debugging

Enable debug mode by setting in acf-live-preview.php:
php
Debug information appears in:
- PHP error logs
- Browser console
- Preview modal debug panel (when available)
  
## Changelog

0.0.2
- Added configurable template search paths
- Enhanced security with payload validation and capability checks
- Improved error handling and debugging
- Added fallback template system
- Settings page enhancements

0.0.1
- Initial release
- Basic live preview functionality
- Modal preview interface

## Development

ACF Fields Checklist:

- [ ] Text
- [ ] Text Area
- [ ] Number
- [ ] Range
- [ ] Email
- [ ] URL
- [ ] Password
- [x] Image
- [x] File
- [ ] WYSIWYG Editor
- [ ] oEmbed
- [x] Gallery
- [ ] Select
- [ ] Checkbox
- [ ] Radio Button
- [ ] Button Group
- [ ] True / False
- [ ] Link
- [ ] Post Object
- [ ] Page Link
- [ ] Relationship
- [ ] Taxonomy
- [ ] User
- [ ] Google Map
- [ ] Date Picker
- [ ] Date Time Picker
- [ ] Time Picker
- [ ] Colour Picker
- [ ] Icon Picker
- [ ] Message
- [ ] Tab
- [ ] Group
- [x] Repeater
- [ ] Flexible Content
- [ ] Clone

This plugin is designed for internal use with page builders but can be extended for general ACF Flexible Content implementations.
