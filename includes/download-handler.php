<?php

namespace Repaf;

/**
 * =========================================================
 * REPAF Secure File Download Handler
 * =========================================================
 *
 * Serves CSV and PDF files through a gated WordPress
 * endpoint instead of exposing direct upload URLs.
 *
 * How it works:
 * - A signed token (HMAC) is generated when an authorized
 *   download link is rendered.
 * - The download link includes ?repaf_download=1&file=...
 *   &token=...&expires=...
 * - On request, the token and expiry are verified before
 *   the file is streamed. Direct access to the upload
 *   folder is blocked by .htaccess (Apache) or the Nginx
 *   server block below (Nginx).
 *
 * Token lifetime: 2 hours (configurable via constant).
 *
 * =========================================================
 * NGINX SETUP — REQUIRED ON NGINX SERVERS
 * =========================================================
 *
 * Apache reads .htaccess automatically. Nginx does NOT.
 * On any Nginx-based host (WP Engine, Kinsta, Cloudways,
 * Forge, etc.) you MUST add the following three location
 * blocks to your site's server {} config and reload Nginx.
 * Without this, the repaf-pdf/, repaf-csv/, and
 * repaf-signatures/ directories are publicly accessible
 * regardless of .htaccess.
 *
 * Add inside your server {} block:
 *
 *   # REPAF — block direct access to generated files
 *   location ~* /wp-content/uploads/repaf-pdf/ {
 *       deny all;
 *       return 403;
 *   }
 *
 *   location ~* /wp-content/uploads/repaf-csv/ {
 *       deny all;
 *       return 403;
 *   }
 *
 *   location ~* /wp-content/uploads/repaf-signatures/ {
 *       deny all;
 *       return 403;
 *   }
 *
 * After adding, reload Nginx:
 *   sudo nginx -t && sudo systemctl reload nginx
 *
 * On managed hosts (WP Engine, Kinsta, Cloudways) open a
 * support ticket with the three location blocks above and
 * ask them to add the rules to your site's config.
 *
 * The plugin will show an admin notice until it can confirm
 * the directories are protected (see repaf_nginx_notice()).
 * =========================================================
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * How long a download token stays valid (in seconds).
 * Default: 2 hours.
 */
if (!defined('REPAF_DOWNLOAD_TTL')) {
    define('REPAF_DOWNLOAD_TTL', 2 * HOUR_IN_SECONDS);
}

/**
 * Downloads are admin/salesperson-only by default because generated files
 * contain sensitive buyer and financial data. Sites that explicitly need
 * public signed links can opt in from wp-config.php.
 */
if (!defined('REPAF_ALLOW_PUBLIC_SIGNED_DOWNLOADS')) {
    define('REPAF_ALLOW_PUBLIC_SIGNED_DOWNLOADS', false);
}

/**
 * Generate a stable, non-guessable filename for a submission export.
 *
 * The token is stored per submission and file type, so regenerated files
 * replace the same private filename instead of creating predictable public
 * URLs such as repaf-submission-123.pdf.
 *
 * @param  int    $post_id  Submission post ID.
 * @param  string $ext      'pdf' or 'csv'.
 * @return string|\WP_Error
 */
function repaf_generated_file_name($post_id, $ext)
{
    $post_id = absint($post_id);
    $ext     = strtolower(sanitize_key($ext));

    if ($post_id === 0 || !in_array($ext, array('pdf', 'csv'), true)) {
        return new \WP_Error('repaf_invalid_file_name', 'Invalid generated file name request.');
    }

    $meta_key = 'repaf_' . $ext . '_file_token';
    $token    = get_post_meta($post_id, $meta_key, true);

    if (!is_string($token) || !preg_match('/^[A-Za-z0-9]{20,64}$/', $token)) {
        $token = wp_generate_password(24, false, false);

        if (get_post($post_id)) {
            update_post_meta($post_id, $meta_key, $token);
        }
    }

    return sprintf('repaf-submission-%d-%s.%s', $post_id, $token, $ext);
}

/**
 * =========================================================
 * Generate a signed, time-limited download URL
 * =========================================================
 *
 * @param  string $file_path  Absolute server path to the file.
 * @param  string $type       'pdf' or 'csv'
 * @return string             Signed download URL.
 */
function repaf_signed_download_url($file_path, $type = 'pdf')
{
    $expires   = time() + REPAF_DOWNLOAD_TTL;
    $file_name = basename($file_path);

    /**
     * HMAC signs the filename + expiry so neither can be
     * tampered with independently.
     */
    $token = hash_hmac(
        'sha256',
        $file_name . '|' . $expires,
        wp_salt('auth')
    );

    return add_query_arg(
        array(
            'repaf_download' => '1',
            'type'           => sanitize_key($type),
            'file'           => rawurlencode($file_name),
            'expires'        => $expires,
            'token'          => $token,
        ),
        home_url('/')
    );
}

/**
 * =========================================================
 * Verify a download token
 * =========================================================
 *
 * @param  string $file_name  Bare filename (no path).
 * @param  int    $expires    Unix timestamp from the URL.
 * @param  string $token      HMAC from the URL.
 * @return bool
 */
function repaf_verify_download_token($file_name, $expires, $token)
{
    /**
     * Reject expired links.
     */
    if (time() > (int) $expires) {
        return false;
    }

    $expected = hash_hmac(
        'sha256',
        $file_name . '|' . $expires,
        wp_salt('auth')
    );

    /**
     * Constant-time comparison prevents timing attacks.
     */
    return hash_equals($expected, $token);
}

/**
 * =========================================================
 * Resolve a safe absolute file path from a bare filename
 * =========================================================
 *
 * Only files inside the repaf-csv or repaf-pdf upload
 * subdirectories are ever served. Path traversal attempts
 * (e.g. ../../wp-config.php) are rejected.
 *
 * @param  string $file_name  Bare filename from the URL.
 * @param  string $type       'pdf' or 'csv'
 * @return string|false       Absolute path, or false if invalid.
 */
function repaf_resolve_download_path($file_name, $type)
{
    /**
     * Whitelist allowed types → upload subdirectory.
     */
    $allowed_dirs = array(
        'pdf' => 'repaf-pdf',
        'csv' => 'repaf-csv',
    );

    if (!isset($allowed_dirs[$type])) {
        return false;
    }

    $upload_dir = wp_upload_dir();

    $base_dir = trailingslashit($upload_dir['basedir'])
        . $allowed_dirs[$type];

    /**
     * Sanitize filename — strip any directory components.
     */
    $safe_name = basename($file_name);

    /**
     * Only allow our generated submission PDFs/legacy CSVs or the protected
     * date-named daily reservation batches.
     */
    $is_submission_file = preg_match(
        '/^repaf-submission-\d+(?:-[A-Za-z0-9]{20,64})?\.(pdf|csv)$/',
        $safe_name
    );
    $is_daily_batch = $type === 'csv' && preg_match(
        '/^repaf-reservations-\d{4}-\d{2}-\d{2}\.csv$/',
        $safe_name
    );

    if (!$is_submission_file && !$is_daily_batch) {
        return false;
    }

    $full_path = trailingslashit($base_dir) . $safe_name;

    /**
     * Final path traversal guard — resolved path must still
     * sit inside the expected base directory.
     */
    if (strpos(realpath(dirname($full_path)), realpath($base_dir)) !== 0) {
        return false;
    }

    return $full_path;
}

/**
 * =========================================================
 * Handle download requests
 * =========================================================
 *
 * Hooked to 'init' — fires on every request but exits
 * immediately unless ?repaf_download=1 is present.
 */
function repaf_handle_download_request()
{
    if (empty($_GET['repaf_download'])) {
        return;
    }

    if (
        !REPAF_ALLOW_PUBLIC_SIGNED_DOWNLOADS &&
        !current_user_can('manage_repaf_submissions')
    ) {
        wp_die(
            'You do not have permission to download this file.',
            'Download Permission Required',
            array('response' => 403)
        );
    }

    $type      = isset($_GET['type'])    ? sanitize_key(wp_unslash($_GET['type']))           : '';
    $file_name = isset($_GET['file'])    ? sanitize_file_name(wp_unslash($_GET['file']))      : '';
    $expires   = isset($_GET['expires']) ? absint(wp_unslash($_GET['expires']))               : 0;
    $token     = isset($_GET['token'])   ? sanitize_text_field(wp_unslash($_GET['token']))    : '';

    /**
     * Verify token.
     */
    if (!repaf_verify_download_token($file_name, $expires, $token)) {
        wp_die(
            'This download link has expired or is invalid. Return to REPAF Submissions and request a new download.',
            'Download Link Invalid',
            array('response' => 403)
        );
    }

    /**
     * Resolve safe path.
     */
    $file_path = repaf_resolve_download_path($file_name, $type);

    if (!$file_path) {
        wp_die(
            'The requested file could not be found.',
            'File Not Found',
            array('response' => 404)
        );
    }

    /**
     * Rebuild daily batches immediately before download. This guarantees an
     * existing file created under an older CSV schema is refreshed to the
     * current CIS layout even when no reservation was edited after an update.
     */
    if (
        $type === 'csv' &&
        preg_match('/^repaf-reservations-(\d{4}-\d{2}-\d{2})\.csv$/', $file_name, $matches)
    ) {
        $rebuilt_path = repaf_rebuild_daily_batch_csv($matches[1]);

        if (is_wp_error($rebuilt_path)) {
            wp_die(
                esc_html($rebuilt_path->get_error_message()),
                'CSV Generation Failed',
                array('response' => 500)
            );
        }

        $file_path = $rebuilt_path;
    }

    if (!file_exists($file_path)) {
        wp_die(
            'The requested file could not be found.',
            'File Not Found',
            array('response' => 404)
        );
    }

    /**
     * Stream the file.
     */
    $mime_types = array(
        'pdf' => 'application/pdf',
        'csv' => 'text/csv',
    );

    $mime = $mime_types[$type] ?? 'application/octet-stream';

    nocache_headers();

    header('Content-Type: '        . $mime);
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Content-Length: '      . filesize($file_path));
    header('X-Content-Type-Options: nosniff');

    readfile($file_path);
    exit;
}
add_action('init', __NAMESPACE__ . '\\repaf_handle_download_request');
