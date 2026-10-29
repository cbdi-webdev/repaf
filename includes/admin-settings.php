<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

// =========================================================
// Defaults
// =========================================================

function repaf_color_defaults() {
    return array(
        'primary'      => '#c2187a',
        'primary_dark' => '#9c145f',
        'primary_soft' => '#fff1f7',
        'success'      => '#914b74',
        'border'       => '#f3c5dc',
        'border_soft'  => '#f9d7e8',
        'bg_soft'      => '#fff8fc',
    );
}

// Converts a 6-char hex color to [r, g, b] integers.
function repaf_hex_to_rgb($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    return array(
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    );
}

// =========================================================
// Sanitization
// =========================================================

function repaf_sanitize_color_settings($input) {
    $defaults = repaf_color_defaults();
    $output   = array();

    foreach (array_keys($defaults) as $key) {
        $raw           = isset($input[$key]) ? trim($input[$key]) : '';
        $cleaned       = sanitize_hex_color($raw);
        $output[$key]  = $cleaned !== '' ? $cleaned : $defaults[$key];
    }

    return $output;
}

// =========================================================
// Text defaults & sanitization
// =========================================================

function repaf_text_defaults() {
    $profile = repaf_get_site_profile_config();

    return array(
        'company_name' => $profile['company_name'],
        'subtitle'     => 'Real Estate Purchase Agreement Form',
    );
}

function repaf_sanitize_text_settings($input) {
    $defaults = repaf_text_defaults();
    $output   = array();

    foreach (array_keys($defaults) as $key) {
        $raw          = isset($input[$key]) ? $input[$key] : '';
        $output[$key] = sanitize_text_field($raw) ?: $defaults[$key];
    }

    return $output;
}

/**
 * Sanitize the portable project dropdown list.
 * Project names are stored one per line so each website can maintain its own list.
 */
function repaf_sanitize_project_names($input) {
    $lines    = preg_split('/\r\n|\r|\n/', (string) $input);
    $projects = array();

    foreach ($lines as $line) {
        $project = sanitize_text_field(trim($line));
        if ($project !== '' && !in_array($project, $projects, true)) {
            $projects[] = $project;
        }
    }

    return implode("\n", $projects);
}

function repaf_get_project_names() {
    $stored = (string) get_option('repaf_project_names', '');
    if ($stored === '') {
        return array();
    }

    return array_values(array_filter(array_map(
        'trim',
        preg_split('/\r\n|\r|\n/', $stored)
    )));
}

// =========================================================
// Admin menu
// =========================================================

function repaf_add_settings_menu() {
    add_options_page(
        'REPAF Form Settings',
        'REPAF Form Settings',
        'manage_options',
        'repaf-form-settings',
        __NAMESPACE__ . '\\repaf_render_settings_page'
    );
}
add_action('admin_menu', __NAMESPACE__ . '\\repaf_add_settings_menu');

// =========================================================
// Settings registration
// =========================================================

function repaf_get_form_page_url()
{
    $page_id = (int) get_option('repaf_form_page_id', 0);
    return $page_id ? get_permalink($page_id) : home_url('/');
}

function repaf_register_settings() {
    register_setting(
        'repaf_color_settings_group',
        'repaf_site_profile',
        array(
            'sanitize_callback' => __NAMESPACE__ . '\\repaf_sanitize_site_profile',
            'default'           => 'auto',
        )
    );

    register_setting(
        'repaf_color_settings_group',
        'repaf_color_settings',
        array(
            'sanitize_callback' => __NAMESPACE__ . '\\repaf_sanitize_color_settings',
            'default'           => repaf_color_defaults(),
        )
    );

    // --- Text settings ---
    register_setting(
        'repaf_color_settings_group',
        'repaf_text_settings',
        array(
            'sanitize_callback' => __NAMESPACE__ . '\\repaf_sanitize_text_settings',
            'default'           => repaf_text_defaults(),
        )
    );

    // --- Form page setting ---
    register_setting(
        'repaf_color_settings_group',
        'repaf_form_page_id',
        array(
            'sanitize_callback' => 'absint',
            'default'           => 0,
        )
    );

    register_setting(
        'repaf_color_settings_group',
        'repaf_project_names',
        array(
            'sanitize_callback' => __NAMESPACE__ . '\\repaf_sanitize_project_names',
            'default'           => '',
        )
    );

    add_settings_section(
        'repaf_general_section',
        'General',
        '__return_false',
        'repaf-form-settings'
    );

    add_settings_field(
        'repaf_site_profile',
        'Website / PDF Brand',
        __NAMESPACE__ . '\\repaf_render_site_profile_field',
        'repaf-form-settings',
        'repaf_general_section'
    );

    add_settings_field(
        'repaf_form_page_id',
        'REPAF Form Page',
        __NAMESPACE__ . '\\repaf_render_form_page_field',
        'repaf-form-settings',
        'repaf_general_section'
    );

    add_settings_field(
        'repaf_project_names',
        'Project Names',
        __NAMESPACE__ . '\\repaf_render_project_names_field',
        'repaf-form-settings',
        'repaf_general_section'
    );

    add_settings_section(
        'repaf_text_section',
        'Header Text',
        '__return_false',
        'repaf-form-settings'
    );

    $text_fields = array(
        array('key' => 'company_name', 'label' => 'Company Name',  'default' => repaf_get_site_profile_config()['company_name']),
        array('key' => 'subtitle',     'label' => 'Form Subtitle', 'default' => 'Real Estate Purchase Agreement Form'),
    );

    foreach ($text_fields as $field) {
        add_settings_field(
            'repaf_text_' . $field['key'],
            $field['label'],
            __NAMESPACE__ . '\\repaf_render_text_field',
            'repaf-form-settings',
            'repaf_text_section',
            $field
        );
    }

    // --- Color settings ---
    add_settings_section(
        'repaf_colors_section',
        'Theme Colors',
        '__return_false',
        'repaf-form-settings'
    );

    $fields = array(
        array('key' => 'primary',      'label' => 'Primary Color',       'default' => '#c2187a'),
        array('key' => 'primary_dark', 'label' => 'Primary Dark Color',  'default' => '#9c145f'),
        array('key' => 'primary_soft', 'label' => 'Primary Soft Color',  'default' => '#fff1f7'),
        array('key' => 'success',      'label' => 'Success Color',       'default' => '#914b74'),
        array('key' => 'border',       'label' => 'Border Color',        'default' => '#f3c5dc'),
        array('key' => 'border_soft',  'label' => 'Border Soft Color',   'default' => '#f9d7e8'),
        array('key' => 'bg_soft',      'label' => 'Background Soft',     'default' => '#fff8fc'),
    );

    foreach ($fields as $field) {
        add_settings_field(
            'repaf_color_' . $field['key'],
            $field['label'],
            __NAMESPACE__ . '\\repaf_render_color_field',
            'repaf-form-settings',
            'repaf_colors_section',
            $field
        );
    }
}
add_action('admin_init', __NAMESPACE__ . '\\repaf_register_settings');

// =========================================================
// Admin asset enqueue (color picker — settings page only)
// =========================================================

function repaf_enqueue_admin_assets($hook_suffix) {
    if ($hook_suffix !== 'settings_page_repaf-form-settings') {
        return;
    }

    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');

    wp_add_inline_script(
        'wp-color-picker',
        "jQuery(function($){ $('.repaf-color-field').wpColorPicker(); });"
    );
}
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\repaf_enqueue_admin_assets');

// =========================================================
// Field render callback
// =========================================================

function repaf_render_site_profile_field()
{
    $saved = repaf_sanitize_site_profile(get_option('repaf_site_profile', 'auto'));

    echo '<select name="repaf_site_profile">';
    echo '<option value="auto"' . selected($saved, 'auto', false) . '>Auto-detect from this website</option>';
    foreach (repaf_site_profiles() as $key => $profile) {
        echo '<option value="' . esc_attr($key) . '"' . selected($saved, $key, false) . '>'
            . esc_html($profile['label']) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">Stored separately by each WordPress website. Select an explicit brand to prevent a copied plugin update from changing the wrong PDF artwork.</p>';
}

function repaf_render_form_page_field()
{
    $saved = (int) get_option('repaf_form_page_id', 0);
    $pages = get_pages(array('post_status' => 'publish'));

    echo '<select name="repaf_form_page_id">';
    echo '<option value="0">— Select a page —</option>';
    foreach ($pages as $page) {
        $sel = ($page->ID === $saved) ? ' selected' : '';
        echo '<option value="' . esc_attr($page->ID) . '"' . $sel . '>' . esc_html($page->post_title) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">Select the page that contains the <code>[repaf_form]</code> shortcode. Used to build the salesperson edit link.</p>';
}

function repaf_render_project_names_field()
{
    $saved = (string) get_option('repaf_project_names', '');

    printf(
        '<textarea name="repaf_project_names" rows="8" class="large-text code" placeholder="Project One&#10;Project Two&#10;Project Three">%s</textarea>',
        esc_textarea($saved)
    );
    echo '<p class="description">Enter one project name per line. These values populate the Project Name dropdown in the REPAF property section for this website.</p>';
}

function repaf_render_color_field($args) {
    $saved   = get_option('repaf_color_settings', repaf_color_defaults());
    $key     = $args['key'];
    $value   = isset($saved[$key]) ? esc_attr($saved[$key]) : esc_attr($args['default']);
    $default = esc_attr($args['default']);

    printf(
        '<input type="text" name="repaf_color_settings[%s]" value="%s" class="repaf-color-field" data-default-color="%s" />',
        esc_attr($key),
        $value,
        $default
    );
}

// =========================================================
// Text field render callback
// =========================================================

function repaf_render_text_field($args) {
    $saved   = get_option('repaf_text_settings', repaf_text_defaults());
    $key     = $args['key'];
    $value   = isset($saved[$key]) ? esc_attr($saved[$key]) : esc_attr($args['default']);

    printf(
        '<input type="text" name="repaf_text_settings[%s]" value="%s" class="regular-text" />',
        esc_attr($key),
        $value
    );
}

// =========================================================
// Settings page render
// =========================================================

function repaf_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>REPAF Form Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('repaf_color_settings_group');
            do_settings_sections('repaf-form-settings');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

// =========================================================
// Frontend inline CSS override (shortcode pages only)
// =========================================================

function repaf_output_theme_css() {
    global $post;

    if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'repaf_form')) {
        return;
    }

    $defaults = repaf_color_defaults();
    $saved    = get_option('repaf_color_settings', $defaults);
    $colors   = wp_parse_args($saved, $defaults);

    $primary      = sanitize_hex_color($colors['primary'])      ?: $defaults['primary'];
    $primary_dark = sanitize_hex_color($colors['primary_dark']) ?: $defaults['primary_dark'];
    $primary_soft = sanitize_hex_color($colors['primary_soft']) ?: $defaults['primary_soft'];
    $success      = sanitize_hex_color($colors['success'])      ?: $defaults['success'];
    $border       = sanitize_hex_color($colors['border'])       ?: $defaults['border'];
    $border_soft  = sanitize_hex_color($colors['border_soft'])  ?: $defaults['border_soft'];
    $bg_soft      = sanitize_hex_color($colors['bg_soft'])      ?: $defaults['bg_soft'];

    // Skip output if every value matches defaults (no customization saved)
    if (
        $primary      === $defaults['primary']      &&
        $primary_dark === $defaults['primary_dark'] &&
        $primary_soft === $defaults['primary_soft'] &&
        $success      === $defaults['success']      &&
        $border       === $defaults['border']       &&
        $border_soft  === $defaults['border_soft']  &&
        $bg_soft      === $defaults['bg_soft']
    ) {
        return;
    }

    // Compute shadows from the primary color's RGB components.
    $rgb          = repaf_hex_to_rgb($primary);
    $shadow       = sprintf('0 16px 40px rgba(%d, %d, %d, 0.08)',  $rgb[0], $rgb[1], $rgb[2]);
    $shadow_hover = sprintf('0 8px 20px rgba(%d, %d, %d, 0.18)',   $rgb[0], $rgb[1], $rgb[2]);

    echo "<style id=\"repaf-theme\">\n";
    echo ".repaf-wizard, #repaf-success-modal, #repaf-privacy-consent-modal {\n";
    printf("  --repaf-primary: %s;\n",      esc_attr($primary));
    printf("  --repaf-primary-dark: %s;\n", esc_attr($primary_dark));
    printf("  --repaf-primary-soft: %s;\n", esc_attr($primary_soft));
    printf("  --repaf-success: %s;\n",      esc_attr($success));
    printf("  --repaf-border: %s;\n",       esc_attr($border));
    printf("  --repaf-border-soft: %s;\n",  esc_attr($border_soft));
    printf("  --repaf-bg-soft: %s;\n",      esc_attr($bg_soft));
    printf("  --repaf-shadow: %s;\n",       esc_attr($shadow));
    printf("  --repaf-shadow-hover: %s;\n", esc_attr($shadow_hover));
    echo "}\n";
    echo "</style>\n";
}
add_action('wp_head', __NAMESPACE__ . '\\repaf_output_theme_css', 20);
