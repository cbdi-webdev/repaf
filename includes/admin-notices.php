<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * =========================================================
 * Admin notices and upload directory protection
 * =========================================================
 *
 * repaf_protect_upload_dirs()  — called on plugin activation.
 *   Writes .htaccess and index.php files into protected upload
 *   subdirectories to block direct Apache access and listings.
 *
 * repaf_is_nginx_unprotected() — detects Nginx or missing
 *   .htaccess enforcement via a live HTTP probe.
 *
 * repaf_nginx_notice()         — WP admin notice shown when
 *   the upload directories are not returning 403. Includes
 *   the exact Nginx config block to copy and a dismiss button.
 *
 * See README.md for the Nginx location block to add to your
 * server config.
 */

/**
 * Write .htaccess protection into upload subdirectories.
 * Called on plugin activation. Apache-only — Nginx ignores
 * .htaccess; see the admin notice and README.
 */
function repaf_protect_upload_dirs()
{
    $upload_dir = wp_upload_dir();

    $dirs = array(
        trailingslashit($upload_dir['basedir']) . 'repaf-pdf',
        trailingslashit($upload_dir['basedir']) . 'repaf-signatures',
    );

    // The CSV directory is created on demand only after a salesperson/admin
    // saves a completed reservation. Protect it when present without creating
    // an otherwise unused directory during plugin activation.
    $legacy_csv_dir = trailingslashit($upload_dir['basedir']) . 'repaf-csv';
    if (is_dir($legacy_csv_dir)) {
        $dirs[] = $legacy_csv_dir;
    }

    $htaccess_content = "# REPAF - block direct file access\n"
        . "Options -Indexes\n"
        . "<IfModule mod_authz_core.c>\n"
        . "    Require all denied\n"
        . "</IfModule>\n"
        . "<IfModule !mod_authz_core.c>\n"
        . "    Order deny,allow\n"
        . "    Deny from all\n"
        . "</IfModule>\n";

    foreach ($dirs as $dir) {
        wp_mkdir_p($dir);

        $htaccess = trailingslashit($dir) . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, $htaccess_content);
        }

        $index = trailingslashit($dir) . 'index.php';
        if (!file_exists($index)) {
            file_put_contents($index, "<?php\n// Silence is golden.\n");
        }
    }
}

/**
 * Detect whether upload directories are unprotected.
 *
 * Uses two checks:
 * 1. SERVER_SOFTWARE header for "nginx"
 * 2. Live HTTP probe — if repaf-pdf/.htaccess returns
 *    anything other than 403, the rules aren't enforced.
 *
 * Result is cached for 12 hours via transient.
 *
 * @return bool  true = unprotected (show notice).
 */
function repaf_is_nginx_unprotected()
{
    $server_software = isset($_SERVER['SERVER_SOFTWARE'])
        ? strtolower(sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])))
        : '';

    $is_nginx  = strpos($server_software, 'nginx') !== false;
    $cache_key = 'repaf_dir_protected';
    $cached    = get_transient($cache_key);

    if ($cached !== false) {
        return (bool) $cached === false;
    }

    $upload_dir = wp_upload_dir();
    $probe_url  = trailingslashit($upload_dir['baseurl']) . 'repaf-pdf/.htaccess';

    $response = wp_remote_head(
        $probe_url,
        array('timeout' => 5, 'sslverify' => true)
    );

    $is_protected = !is_wp_error($response)
        && wp_remote_retrieve_response_code($response) === 403;

    set_transient($cache_key, $is_protected ? '1' : '0', 12 * HOUR_IN_SECONDS);

    return $is_nginx || !$is_protected;
}

/**
 * Render the Nginx protection admin notice.
 * Hooked to admin_notices in the main plugin file.
 */
function repaf_nginx_notice()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $dismissed = get_user_meta(
        get_current_user_id(),
        'repaf_nginx_notice_dismissed',
        true
    );

    if ($dismissed) {
        return;
    }

    if (
        isset($_GET['repaf_dismiss_nginx_notice']) &&
        wp_verify_nonce(
            sanitize_text_field(wp_unslash($_GET['repaf_nginx_nonce'] ?? '')),
            'repaf_dismiss_nginx'
        )
    ) {
        update_user_meta(get_current_user_id(), 'repaf_nginx_notice_dismissed', '1');
        delete_transient('repaf_dir_protected');
        return;
    }

    if (!repaf_is_nginx_unprotected()) {
        return;
    }

    $dismiss_url = wp_nonce_url(
        add_query_arg('repaf_dismiss_nginx_notice', '1'),
        'repaf_dismiss_nginx',
        'repaf_nginx_nonce'
    );

    ?>
    <div class="notice notice-error">
        <p>
            <strong>REPAF Plugin — Action Required: Upload Directory Not Protected</strong>
        </p>
        <p>
            Your server appears to be running <strong>Nginx</strong>, which does not
            read <code>.htaccess</code> files. The directories that store generated
            PDFs, CSVs, and signatures (<code>wp-content/uploads/repaf-pdf/</code>,
            <code>wp-content/uploads/repaf-csv/</code>, and
            <code>wp-content/uploads/repaf-signatures/</code>) may be <strong>publicly
            accessible</strong> via direct URL.
        </p>
        <p>Add the following blocks inside your Nginx <code>server {}</code> config
        and reload Nginx (<code>sudo nginx -t && sudo systemctl reload nginx</code>).
        On managed hosts (WP Engine, Kinsta, Cloudways), send these rules to support:</p>
        <pre style="background:#f6f7f7;padding:12px;border-left:4px solid #d63638;overflow-x:auto;font-size:12px;"># REPAF — block direct access to generated files
location ~* /wp-content/uploads/repaf-pdf/ {
    deny all;
    return 403;
}

location ~* /wp-content/uploads/repaf-csv/ {
    deny all;
    return 403;
}

location ~* /wp-content/uploads/repaf-signatures/ {
    deny all;
    return 403;
}</pre>
        <p>
            <a href="<?php echo esc_url($dismiss_url); ?>">Dismiss this notice</a>
            (only dismiss after applying the config above)
        </p>
    </div>
    <?php
}
