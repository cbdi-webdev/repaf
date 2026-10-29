<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

const REPAF_PSOC_CACHE_KEY = 'repaf_psoc_minor_occupations_v1';
const REPAF_PSOC_ERROR_KEY = 'repaf_psoc_minor_occupations_error';
const REPAF_PSOC_LOCK_KEY  = 'repaf_psoc_minor_occupations_lock';

/**
 * Convert all-uppercase PSOC titles into readable title case while keeping
 * common connector words lowercase and familiar abbreviations uppercase.
 */
function repaf_format_psoc_title($title)
{
    $title = trim((string) $title);
    if ($title === '') {
        return '';
    }

    $formatted = function_exists('mb_convert_case')
        ? mb_convert_case($title, MB_CASE_TITLE, 'UTF-8')
        : ucwords(strtolower($title));

    $formatted = preg_replace_callback(
        '/\b(And|Of|The|For|To|In|On|With|As|By|At|From)\b/u',
        static function ($matches) {
            return strtolower($matches[0]);
        },
        $formatted
    );

    $formatted = preg_replace_callback(
        '/\b(Ict|It|Tv|Hr|Pr)\b/u',
        static function ($matches) {
            return strtoupper($matches[0]);
        },
        $formatted
    );

    return function_exists('mb_strtoupper')
        ? mb_strtoupper(mb_substr($formatted, 0, 1, 'UTF-8'), 'UTF-8')
            . mb_substr($formatted, 1, null, 'UTF-8')
        : ucfirst($formatted);
}

/**
 * Normalize, deduplicate, and sort PSOC profession titles.
 *
 * @param  array $professions Raw or cached profession titles.
 * @return string[]
 */
function repaf_format_psoc_professions($professions)
{
    $formatted = array();

    foreach ($professions as $profession) {
        if (!is_scalar($profession)) {
            continue;
        }

        $title = repaf_format_psoc_title($profession);
        if ($title !== '') {
            $formatted[$title] = true;
        }
    }

    $titles = array_keys($formatted);
    natcasesort($titles);
    return array_values($titles);
}

/**
 * Keep the PSOC API token on the WordPress server. A blank settings field
 * means "retain the saved token", preventing it from being rendered back
 * into the settings page HTML.
 */
function repaf_sanitize_psoc_token($value)
{
    $value   = trim((string) $value);
    $current = (string) get_option('repaf_psoc_token', '');

    if ($value === '') {
        return $current;
    }

    $clean = sanitize_text_field($value);

    if (!hash_equals($current, $clean)) {
        delete_transient(REPAF_PSOC_CACHE_KEY);
        delete_transient(REPAF_PSOC_ERROR_KEY);
    }

    return $clean;
}

/**
 * Recursively collect occupation titles from the PSA response. The API has
 * used both direct arrays and nested data containers, so this remains
 * deliberately tolerant while accepting only scalar title values.
 */
function repaf_collect_psoc_titles($node, &$titles)
{
    if (!is_array($node)) {
        return;
    }

    if (isset($node['title']) && is_scalar($node['title'])) {
        $title = sanitize_text_field((string) $node['title']);
        if ($title !== '') {
            $titles[$title] = true;
        }
    }

    foreach ($node as $child) {
        if (is_array($child)) {
            repaf_collect_psoc_titles($child, $titles);
        }
    }
}

/**
 * Download and cache the PSOC 2012 minor-group titles. This provides more
 * specific choices than sub-major groups without restoring the complete
 * 456-option unit-group list.
 *
 * @return string[]|\WP_Error
 */
function repaf_get_psoc_professions()
{
    $cached = get_transient(REPAF_PSOC_CACHE_KEY);
    if (is_array($cached) && $cached) {
        return repaf_format_psoc_professions($cached);
    }

    $cached_error = get_transient(REPAF_PSOC_ERROR_KEY);
    if (is_string($cached_error) && $cached_error !== '') {
        return new \WP_Error('psoc_temporarily_unavailable', $cached_error);
    }

    $token = trim((string) get_option('repaf_psoc_token', ''));
    if ($token === '') {
        return new \WP_Error(
            'psoc_not_configured',
            'PSOC profession options are not configured. Contact the administrator.'
        );
    }

    if (get_transient(REPAF_PSOC_LOCK_KEY)) {
        return new \WP_Error(
            'psoc_refresh_in_progress',
            'The profession list is refreshing. Please try again shortly.'
        );
    }

    set_transient(REPAF_PSOC_LOCK_KEY, 1, 30);

    $titles    = array();
    $page_size = 1000;
    $error     = null;

    for ($page = 1; $page <= 5; $page++) {
        $url = add_query_arg(
            array(
                'token'     => $token,
                'page'      => $page,
                'page_size' => $page_size,
            ),
            'https://classification.psa.gov.ph/psoc/2012/minor'
        );

        $response = wp_remote_get(
            $url,
            array(
                'timeout'     => 15,
                'redirection' => 2,
                'sslverify'   => true,
                'headers'     => array('Accept' => 'application/json'),
            )
        );

        if (is_wp_error($response)) {
            $error = 'The PSOC profession list could not be loaded. Please try again later.';
            break;
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            $error = $status === 401 || $status === 403
                ? 'The saved PSOC API token was not accepted. Contact the administrator.'
                : 'The PSOC profession list is temporarily unavailable. Please try again later.';
            break;
        }

        $payload = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($payload)) {
            $error = 'The PSOC service returned an invalid profession list. Please try again later.';
            break;
        }

        $before = count($titles);
        repaf_collect_psoc_titles($payload, $titles);
        $added = count($titles) - $before;

        if ($added < $page_size) {
            break;
        }
    }

    delete_transient(REPAF_PSOC_LOCK_KEY);

    if ($error !== null) {
        set_transient(REPAF_PSOC_ERROR_KEY, $error, 5 * MINUTE_IN_SECONDS);
        return new \WP_Error('psoc_request_failed', $error);
    }

    // This commonly used real-estate role is a PSOC unit group rather than a
    // minor group. Include it explicitly so real-estate applicants are not
    // forced into the broader "Business Services Agents" category.
    $titles['REAL ESTATE AGENTS AND PROPERTY MANAGERS'] = true;

    $professions = repaf_format_psoc_professions(array_keys($titles));

    if (!$professions) {
        $message = 'No professions were returned by PSOC. Please try again later.';
        set_transient(REPAF_PSOC_ERROR_KEY, $message, 5 * MINUTE_IN_SECONDS);
        return new \WP_Error('psoc_empty_response', $message);
    }

    set_transient(REPAF_PSOC_CACHE_KEY, $professions, DAY_IN_SECONDS);
    return $professions;
}

/**
 * Public AJAX endpoint for the complete dropdown list. The PSOC token never
 * leaves the server.
 */
function repaf_get_psoc_profession_options()
{
    if (!check_ajax_referer('repaf_psoc_professions', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Invalid profession list request.'), 403);
    }

    $professions = repaf_get_psoc_professions();
    if (is_wp_error($professions)) {
        wp_send_json_error(
            array('message' => $professions->get_error_message()),
            $professions->get_error_code() === 'psoc_not_configured' ? 503 : 502
        );
    }

    wp_send_json_success($professions);
}
add_action('wp_ajax_repaf_get_psoc_professions', __NAMESPACE__ . '\\repaf_get_psoc_profession_options');
add_action('wp_ajax_nopriv_repaf_get_psoc_professions', __NAMESPACE__ . '\\repaf_get_psoc_profession_options');
