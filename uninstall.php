<?php

/**
 * =========================================================
 * REPAF Multi-Step Form — Uninstall
 * =========================================================
 *
 * Runs automatically when the plugin is deleted from the
 * WordPress admin (Plugins → Delete). NOT run on deactivate.
 *
 * Cleans up:
 * - All repaf_submission custom posts and their post meta
 * - All repaf_* transients (rate limit, download tokens,
 *   Nginx probe cache)
 * - The repaf_nginx_notice_dismissed user meta flag
 * - Generated PDF and CSV files in wp-content/uploads
 * - The repaf-pdf/ and repaf-csv/ upload subdirectories
 *
 * Does NOT delete:
 * - wp-config.php overrides (REPAF_RATE_LIMIT_MAX etc.)
 *   — those are the site owner's responsibility to remove.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * =========================================================
 * 0. Remove custom role and capability
 * =========================================================
 */
remove_role('repaf_salesperson');

$admin_role = get_role('administrator');
if ($admin_role) {
    $admin_role->remove_cap('manage_repaf_submissions');
}

/**
 * =========================================================
 * 0b. Unschedule cleanup cron
 * =========================================================
 */
$timestamp = wp_next_scheduled('repaf_cleanup_files');
if ($timestamp) {
    wp_unschedule_event($timestamp, 'repaf_cleanup_files');
}

/**
 * =========================================================
 * 1. Delete all repaf_submission posts and their meta
 * =========================================================
 */
$submission_ids = get_posts(array(
    'post_type'      => 'repaf_submission',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
));

foreach ($submission_ids as $post_id) {
    wp_delete_post($post_id, true);
}

/**
 * =========================================================
 * 2. Delete all repaf_* transients
 * =========================================================
 *
 * Covers:
 * - repaf_rl_*       rate limit counters
 * - repaf_dl_*       one-time download tokens
 * - repaf_dir_protected  Nginx probe cache
 */
global $wpdb;

$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_repaf_%'
        OR option_name LIKE '_transient_timeout_repaf_%'"
);

/**
 * =========================================================
 * 3. Delete nginx notice dismissal flag from all users
 * =========================================================
 */
$wpdb->delete(
    $wpdb->usermeta,
    array('meta_key' => 'repaf_nginx_notice_dismissed'),
    array('%s')
);

/**
 * =========================================================
 * 4. Delete color settings option
 * =========================================================
 */
delete_option('repaf_color_settings');
delete_option('repaf_text_settings');
delete_option('repaf_update_repository');
delete_site_option('repaf_update_state');
delete_site_option('repaf_update_source');
wp_clear_scheduled_hook('puc_cron_check_updates-repaf-multi-step-form');

/**
 * =========================================================
 * 5. Delete generated files and upload subdirectories
 * =========================================================
 */
$upload_dir = wp_upload_dir();

$repaf_dirs = array(
    trailingslashit($upload_dir['basedir']) . 'repaf-pdf',
    trailingslashit($upload_dir['basedir']) . 'repaf-csv',
);

foreach ($repaf_dirs as $dir) {

    if (!is_dir($dir)) {
        continue;
    }

    /**
     * Delete every file inside the directory first,
     * then remove the directory itself.
     */
    $files = glob(trailingslashit($dir) . '*');

    if (is_array($files)) {
        foreach ($files as $file) {
            if (is_file($file)) {
                wp_delete_file($file);
            }
        }
    }

    @rmdir($dir);
}
