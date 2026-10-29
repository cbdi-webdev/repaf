<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * =========================================================
 * Plugin activation callback
 * =========================================================
 * - Creates upload subdirectories with .htaccess protection
 * - Schedules the daily file cleanup cron event
 */
function repaf_on_activation()
{
    repaf_protect_upload_dirs();

    if (!wp_next_scheduled('repaf_cleanup_files')) {
        wp_schedule_event(time(), 'daily', 'repaf_cleanup_files');
    }

    repaf_provision_salesperson_role();
}

/**
 * Creates the repaf_salesperson role and grants the
 * manage_repaf_submissions cap to administrators.
 * Called both on activation and on init (idempotent) so
 * already-activated installs pick it up without a
 * deactivate/reactivate cycle.
 */
function repaf_provision_salesperson_role()
{
    if (!get_role('repaf_salesperson')) {
        add_role('repaf_salesperson', 'REPAF Salesperson', array(
            'read'                     => true,
            'manage_repaf_submissions' => true,
        ));
    }

    $admin_role = get_role('administrator');
    if ($admin_role && !$admin_role->has_cap('manage_repaf_submissions')) {
        $admin_role->add_cap('manage_repaf_submissions');
    }
}
add_action('init', __NAMESPACE__ . '\\repaf_provision_salesperson_role');

/**
 * =========================================================
 * Security upgrade tasks
 * =========================================================
 *
 * Removes legacy raw upload URL metadata and refreshes upload
 * directory guards for already-active installs.
 */
function repaf_run_security_upgrade()
{
    $target_version = '1.2.2';

    if (get_option('repaf_security_schema_version') === $target_version) {
        return;
    }

    delete_post_meta_by_key('repaf_pdf_url');
    delete_post_meta_by_key('repaf_csv_url');
    repaf_protect_upload_dirs();

    update_option('repaf_security_schema_version', $target_version, false);
}
add_action('init', __NAMESPACE__ . '\\repaf_run_security_upgrade');

/**
 * =========================================================
 * Plugin deactivation callback
 * =========================================================
 * Unschedules the cleanup cron so it doesn't fire after
 * the plugin is deactivated.
 */
function repaf_on_deactivation()
{
    $timestamp = wp_next_scheduled('repaf_cleanup_files');

    if ($timestamp) {
        wp_unschedule_event($timestamp, 'repaf_cleanup_files');
    }

    if (defined(__NAMESPACE__ . '\\REPAF_EMAIL_CRON_HOOK')) {
        wp_clear_scheduled_hook(REPAF_EMAIL_CRON_HOOK);
    }
}

/**
 * =========================================================
 * File cleanup cron job
 * =========================================================
 *
 * Runs daily. Deletes PDF and CSV files older than the
 * download token TTL (default 2 hours), preventing
 * unbounded disk usage in the uploads directory.
 */
function repaf_cleanup_old_files()
{
    $upload_dir = wp_upload_dir();

    $dirs = array(
        trailingslashit($upload_dir['basedir']) . 'repaf-pdf',
        trailingslashit($upload_dir['basedir']) . 'repaf-csv',
    );

    $cutoff = time() - (defined('REPAF_DOWNLOAD_TTL')
        ? REPAF_DOWNLOAD_TTL
        : 2 * HOUR_IN_SECONDS);

    $deleted = 0;

    foreach ($dirs as $dir) {

        if (!is_dir($dir)) {
            continue;
        }

        $files = glob(trailingslashit($dir) . 'repaf-submission-*.{pdf,csv}', GLOB_BRACE);

        if (!is_array($files)) {
            continue;
        }

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                wp_delete_file($file);
                $deleted++;
            }
        }
    }

    if ($deleted > 0) {
        error_log(sprintf('[REPAF] Cleanup cron: deleted %d expired file(s).', $deleted));
    }
}
add_action('repaf_cleanup_files', __NAMESPACE__ . '\\repaf_cleanup_old_files');

/**
 * =========================================================
 * Register Submission Custom Post Type
 * =========================================================
 */
function repaf_register_submission_post_type()
{
    register_post_type(
        'repaf_submission',
        array(
            'labels' => array(
                'name'          => 'REPAF Submissions',
                'singular_name' => 'REPAF Submission',
            ),
            'public'       => false,
            'show_ui'      => false,
            'menu_icon'    => 'dashicons-forms',
            'supports'     => array('title'),
            'show_in_rest' => false,
        )
    );
}
add_action('init', __NAMESPACE__ . '\\repaf_register_submission_post_type');

/**
 * =========================================================
 * Enqueue frontend assets
 * =========================================================
 *
 * Only loads on pages containing [repaf_form].
 * Serves .min files in production, source files in dev.
 */
function repaf_enqueue_assets()
{
    global $post;

    if (
        !is_a($post, 'WP_Post') ||
        !has_shortcode($post->post_content, 'repaf_form')
    ) {
        return;
    }

    /**
     * Use minified assets in production (WP_DEBUG = false),
     * unminified in development (WP_DEBUG = true).
     * Falls back to unminified if .min file doesn't exist.
     */
    $use_min  = !(defined('WP_DEBUG') && WP_DEBUG);
    $css_min  = REPAF_PLUGIN_PATH . 'assets/css/repaf-form.min.css';
    $js_min   = REPAF_PLUGIN_PATH . 'assets/js/repaf-form.min.js';
    $css_file = ($use_min && file_exists($css_min)) ? 'assets/css/repaf-form.min.css' : 'assets/css/repaf-form.css';
    $js_file  = ($use_min && file_exists($js_min))  ? 'assets/js/repaf-form.min.js'  : 'assets/js/repaf-form.js';

    $css_fpath = REPAF_PLUGIN_PATH . $css_file;
    $js_fpath  = REPAF_PLUGIN_PATH . $js_file;

    wp_enqueue_style(
        'repaf-form-css',
        REPAF_PLUGIN_URL . $css_file,
        array(),
        file_exists($css_fpath) ? filemtime($css_fpath) : REPAF_VERSION
    );

    wp_enqueue_script(
        'repaf-address-js',
        REPAF_PLUGIN_URL . 'assets/js/repaf-address.js',
        array(),
        filemtime(REPAF_PLUGIN_PATH . 'assets/js/repaf-address.js'),
        true
    );

    wp_localize_script(
        'repaf-address-js',
        'repafAddressConfig',
        array(
            'ajaxUrl'         => admin_url('admin-ajax.php'),
            'nonce'           => wp_create_nonce('repaf_address_lookup'),
            'postalModuleUrl' => 'https://cdn.jsdelivr.net/npm/use-postal-ph@1.1.14/dist/index.mjs',
            'countries'       => repaf_country_options(),
        )
    );

    wp_enqueue_style(
        'repaf-address-css',
        REPAF_PLUGIN_URL . 'assets/css/repaf-address.css',
        array('repaf-form-css'),
        filemtime(REPAF_PLUGIN_PATH . 'assets/css/repaf-address.css')
    );

    wp_enqueue_script(
        'repaf-form-js',
        REPAF_PLUGIN_URL . $js_file,
        array('repaf-address-js'),
        file_exists($js_fpath) ? filemtime($js_fpath) : REPAF_VERSION,
        true
    );

    if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
        wp_localize_script(
            'repaf-form-js',
            'repafPdfTest',
            array(
                'url' => wp_nonce_url(
                    add_query_arg(
                        array('repaf_test_pdf' => 1),
                        home_url('/')
                    ),
                    'repaf_test_pdf',
                    'repaf_test_pdf_nonce'
                ),
            )
        );
    }
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\repaf_enqueue_assets');
