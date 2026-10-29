<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * =========================================================
 * Rate limiter
 * =========================================================
 *
 * Limits form submissions per IP address using WP transients.
 * Enabled by default in every environment. Local development can
 * opt out explicitly with REPAF_DISABLE_RATE_LIMIT.
 *
 * Override defaults in wp-config.php:
 *   define('REPAF_RATE_LIMIT_MAX',    5);
 *   define('REPAF_RATE_LIMIT_WINDOW', 600); // seconds
 *   define('REPAF_DISABLE_RATE_LIMIT', true); // local testing only
 */

if (!defined('REPAF_RATE_LIMIT_MAX')) {
    define('REPAF_RATE_LIMIT_MAX', 5);
}

if (!defined('REPAF_RATE_LIMIT_WINDOW')) {
    define('REPAF_RATE_LIMIT_WINDOW', 10 * MINUTE_IN_SECONDS);
}

/**
 * Check whether the current request is within the rate limit.
 *
 * Increments the counter on every call — invoke once per
 * submission, immediately after nonce verification.
 *
 * @return bool  true = within limit, false = limit exceeded.
 */
function repaf_check_rate_limit()
{
    if (defined('REPAF_DISABLE_RATE_LIMIT') && REPAF_DISABLE_RATE_LIMIT) {
        return true;
    }

    // Prefer the real client IP when behind a trusted reverse proxy (e.g. XAMPP + nginx).
    // X-Forwarded-For is only trusted when REPAF_TRUSTED_PROXY is defined in wp-config.php,
    // because the header can be spoofed on direct connections.
    $raw_ip = 'unknown';
    if (
        defined('REPAF_TRUSTED_PROXY') && REPAF_TRUSTED_PROXY &&
        isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    ) {
        $forwarded = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        $raw_ip    = trim(explode(',', $forwarded)[0]);
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $raw_ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    }

    $transient_key = 'repaf_rl_' . md5($raw_ip);
    $hits          = (int) get_transient($transient_key);

    if ($hits >= REPAF_RATE_LIMIT_MAX) {
        return false;
    }

    set_transient(
        $transient_key,
        $hits + 1,
        REPAF_RATE_LIMIT_WINDOW
    );

    return true;
}
