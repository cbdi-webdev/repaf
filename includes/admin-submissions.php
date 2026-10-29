<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REPAF Admin Submissions Panel
 *
 * Provides a custom admin menu for the repaf_salesperson role
 * (and administrators) to view, edit, and generate PDFs for
 * REPAF submissions. All actions gate on the
 * manage_repaf_submissions capability.
 *
 * This file owns the hooks, menu registration, asset enqueue,
 * and page dispatcher. The views/handlers themselves live in:
 *   admin-submissions-list.php     — list view
 *   admin-submissions-edit.php     — edit view (customer + property tabs)
 *   admin-submissions-save.php     — save handler + property sanitizer
 *   admin-submissions-download.php — PDF download interceptor + handler
 */

add_action('admin_menu',            __NAMESPACE__ . '\\repaf_admin_submissions_menu');
add_action('admin_menu',            __NAMESPACE__ . '\\repaf_salesperson_strip_menus', 999);
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\repaf_enqueue_admin_submissions_assets');
add_action('admin_post_repaf_admin_save_submission', __NAMESPACE__ . '\\repaf_admin_handle_save_submission');

// Intercept PDF downloads early — before WordPress outputs any admin HTML.
add_action('admin_init', __NAMESPACE__ . '\\repaf_admin_maybe_download_pdf');
add_action('admin_init', __NAMESPACE__ . '\\repaf_salesperson_redirect_dashboard');

add_filter('login_redirect', __NAMESPACE__ . '\\repaf_salesperson_login_redirect', 10, 3);

function repaf_admin_submissions_menu()
{
    add_menu_page(
        'REPAF Submissions',
        'REPAF Submissions',
        'manage_repaf_submissions',
        'repaf-submissions',
        __NAMESPACE__ . '\\repaf_admin_submissions_page',
        'dashicons-feedback',
        25
    );
}

function repaf_enqueue_admin_submissions_assets($hook)
{
    if ($hook !== 'toplevel_page_repaf-submissions') {
        return;
    }

    wp_enqueue_style(
        'repaf-admin-css',
        REPAF_PLUGIN_URL . 'assets/css/repaf-admin.css',
        array(),
        REPAF_VERSION
    );

    // Inject theme color CSS variables from saved settings
    $defaults = array(
        'primary'      => '#c2187a',
        'primary_dark' => '#9c145f',
        'primary_soft' => '#fff1f7',
        'success'      => '#914b74',
        'border'       => '#f3c5dc',
        'border_soft'  => '#f9d7e8',
        'bg_soft'      => '#fff8fc',
    );
    $saved   = get_option('repaf_color_settings', $defaults);
    $colors  = wp_parse_args($saved, $defaults);
    $primary = sanitize_hex_color($colors['primary'])      ?: $defaults['primary'];
    $p_dark  = sanitize_hex_color($colors['primary_dark']) ?: $defaults['primary_dark'];
    $p_soft  = sanitize_hex_color($colors['primary_soft']) ?: $defaults['primary_soft'];
    $border  = sanitize_hex_color($colors['border'])       ?: $defaults['border'];

    $inline = sprintf(
        '.repaf-admin-wrap { --ra-primary: %s; --ra-primary-dark: %s; --ra-primary-soft: %s; --ra-border: %s; }',
        esc_attr($primary),
        esc_attr($p_dark),
        esc_attr($p_soft),
        esc_attr($border)
    );
    wp_add_inline_style('repaf-admin-css', $inline);

    wp_enqueue_script(
        'repaf-admin-js',
        REPAF_PLUGIN_URL . 'assets/js/repaf-admin.js',
        array(),
        REPAF_VERSION,
        true
    );
}

function repaf_admin_submissions_page()
{
    if (!current_user_can('manage_repaf_submissions')) {
        wp_die(__('You do not have permission to access this page.'));
    }

    $action  = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
    $post_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

    if ($action === 'edit' && $post_id > 0) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'repaf_edit_submission_' . $post_id)) {
            wp_die(__('Security check failed.'));
        }
        repaf_admin_render_edit($post_id);
        return;
    }

    repaf_admin_render_list();
}

/**
 * Send salesperson users straight to the submissions list
 * instead of the default WP dashboard after login.
 */
function repaf_salesperson_login_redirect($redirect_to, $requested_redirect_to, $user)
{
    if (is_wp_error($user)) {
        return $redirect_to;
    }

    if (in_array('repaf_salesperson', (array) $user->roles, true)) {
        return admin_url('admin.php?page=repaf-submissions');
    }

    return $redirect_to;
}

/**
 * Redirect salesperson away from the WP dashboard
 * (e.g. if they navigate to /wp-admin/ directly).
 */
function repaf_salesperson_redirect_dashboard()
{
    if (current_user_can('manage_options')) {
        return;
    }

    if (!current_user_can('manage_repaf_submissions')) {
        return;
    }

    $screen = get_current_screen();
    if ($screen && $screen->id === 'dashboard') {
        wp_safe_redirect(admin_url('admin.php?page=repaf-submissions'));
        exit;
    }
}

/**
 * Strip every admin menu item except REPAF Submissions
 * for the repaf_salesperson role. Administrators keep
 * their full menu because they have manage_options.
 */
function repaf_salesperson_strip_menus()
{
    if (current_user_can('manage_options')) {
        return;
    }

    if (!current_user_can('manage_repaf_submissions')) {
        return;
    }

    global $menu, $submenu;

    $allowed = array('repaf-submissions', 'profile.php');

    foreach (array_keys($menu) as $key) {
        $slug = isset($menu[$key][2]) ? $menu[$key][2] : '';
        if (!in_array($slug, $allowed, true)) {
            unset($menu[$key]);
        }
    }

    foreach (array_keys($submenu) as $parent) {
        if (!in_array($parent, $allowed, true)) {
            unset($submenu[$parent]);
        }
    }
}
