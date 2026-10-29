<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Server-side proxy for Philippine Standard Geographic Code lookups.
 *
 * Keeping third-party requests on the server avoids CORS failures and lets
 * WordPress cache the relatively stable location lists for 30 days.
 */

add_action('wp_ajax_repaf_address_lookup', __NAMESPACE__ . '\\repaf_address_lookup');
add_action('wp_ajax_nopriv_repaf_address_lookup', __NAMESPACE__ . '\\repaf_address_lookup');

/**
 * Fetch and cache one PSGC Cloud collection.
 *
 * @param  string $path      Allowlisted API path assembled by the caller.
 * @param  string $cache_key Short cache key suffix.
 * @param  int    $timeout   Upstream request timeout in seconds.
 * @return array|\WP_Error
 */
function repaf_fetch_psgc_collection($path, $cache_key, $timeout = 20)
{
    $transient_key = 'repaf_psgc_' . sanitize_key($cache_key);
    $cached        = get_transient($transient_key);

    if (is_array($cached)) {
        return $cached;
    }

    $response = wp_remote_get(
        'https://psgc.cloud/api/v2/' . ltrim($path, '/'),
        array(
            'timeout'     => max(1, (int) $timeout),
            'redirection' => 2,
            'headers'     => array('Accept' => 'application/json'),
        )
    );

    if (is_wp_error($response)) {
        return $response;
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    $body   = json_decode(wp_remote_retrieve_body($response), true);

    if ($status !== 200 || !is_array($body) || !isset($body['data']) || !is_array($body['data'])) {
        return new \WP_Error(
            'repaf_psgc_unavailable',
            'The Philippine address service returned an invalid response.'
        );
    }

    set_transient($transient_key, $body['data'], 30 * DAY_IN_SECONDS);

    return $body['data'];
}

/**
 * Repair UTF-8 text that an upstream source decoded as Windows-1252 first.
 *
 * Example: "DasmariÃ±as" becomes "Dasmariñas". Conversion is deliberately
 * limited to strings containing common mojibake markers so correctly encoded
 * Philippine names are left untouched.
 */
function repaf_repair_address_encoding($value)
{
    $value = (string) $value;

    if ($value === '' || !preg_match('/(?:Ã.|Â.|â.)/u', $value)) {
        return $value;
    }

    if (function_exists('mb_convert_encoding') && function_exists('mb_check_encoding')) {
        $repaired = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');

        if ($repaired !== '' && mb_check_encoding($repaired, 'UTF-8')) {
            return $repaired;
        }
    }

    return strtr($value, array(
        'Ã±' => 'ñ',
        'Ã‘' => 'Ñ',
        'Ã¡' => 'á',
        'Ã©' => 'é',
        'Ã­' => 'í',
        'Ã³' => 'ó',
        'Ãº' => 'ú',
        'Ã' => 'Á',
        'Ã‰' => 'É',
        'Ã' => 'Í',
        'Ã“' => 'Ó',
        'Ãš' => 'Ú',
    ));
}

/**
 * Return a compact, sanitized item for the browser dropdowns.
 */
function repaf_normalize_psgc_item($item, $include_location = false)
{
    $normalized = array(
        'code' => sanitize_text_field((string) ($item['code'] ?? '')),
        'name' => sanitize_text_field(
            repaf_repair_address_encoding($item['name'] ?? '')
        ),
        'zip'  => sanitize_text_field((string) ($item['zip_code'] ?? '')),
    );

    if ($include_location) {
        $location = $item['city_municipality'] ?? '';
        if (is_array($location)) {
            $location = $location['name'] ?? '';
        }
        $normalized['location'] = sanitize_text_field(
            repaf_repair_address_encoding($location)
        );
    }

    return $normalized;
}

/**
 * Extract a parent PSGC code from the structured v2 API response.
 */
function repaf_psgc_parent_code($item, $parent_key)
{
    $parent = $item[$parent_key] ?? null;

    if (is_array($parent)) {
        return sanitize_text_field((string) ($parent['code'] ?? ''));
    }

    if (!is_scalar($parent)) {
        return '';
    }

    $parent = sanitize_text_field((string) $parent);
    return preg_match('/^[0-9]{10}$/', $parent) ? $parent : '';
}

/**
 * Derive a hierarchy parent from a 10-digit PSGC child code when an API list
 * response contains only the parent's display name.
 */
function repaf_psgc_derived_parent_code($code, $parent_level)
{
    $code = (string) $code;
    if (!preg_match('/^[0-9]{10}$/', $code)) {
        return '';
    }

    if ($parent_level === 'province') {
        return strpos($code, '13') === 0
            ? '1300000000'
            : substr($code, 0, 5) . '00000';
    }

    if ($parent_level === 'city_municipality') {
        return strpos($code, '13806') === 0
            ? '1380600000'
            : substr($code, 0, 7) . '000';
    }

    return '';
}

/**
 * AJAX endpoint for province, city/municipality, and barangay dropdowns.
 */
function repaf_address_lookup()
{
    check_ajax_referer('repaf_address_lookup', 'nonce');

    $level = isset($_POST['level'])
        ? sanitize_key(wp_unslash($_POST['level']))
        : '';

    if (!in_array($level, array('regions', 'provinces', 'cities', 'barangays', 'all_cities', 'region_barangays'), true)) {
        wp_send_json_error(array('message' => 'Invalid address lookup level.'), 400);
    }

    if ($level === 'regions') {
        $items = repaf_fetch_psgc_collection('regions', 'regions');

        if (is_wp_error($items)) {
            wp_send_json_error(array('message' => $items->get_error_message()), 502);
        }

        $result = array();
        foreach ($items as $item) {
            $result[] = repaf_normalize_psgc_item($item);
        }

        wp_send_json_success($result);
    }

    if ($level === 'provinces') {
        $items = repaf_fetch_psgc_collection('provinces', 'provinces');

        if (is_wp_error($items)) {
            wp_send_json_error(array('message' => $items->get_error_message()), 502);
        }

        $result = array();
        foreach ($items as $item) {
            $result[] = repaf_normalize_psgc_item($item);
        }

        // NCR has no province layer. Present Metro Manila as the form's
        // Province / State choice and query its cities through the region path.
        $result[] = array(
            'code' => '1300000000',
            'name' => 'Metro Manila',
            'zip'  => '',
        );

        usort($result, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        wp_send_json_success($result);
    }

    if ($level === 'all_cities') {
        $items = repaf_fetch_psgc_collection(
            'cities-municipalities',
            'all_cities_municipalities',
            45
        );

        if (is_wp_error($items)) {
            wp_send_json_error(array('message' => $items->get_error_message()), 502);
        }

        $result = array();
        foreach ($items as $item) {
            if (!in_array((string) ($item['type'] ?? ''), array('City', 'Mun'), true)) {
                continue;
            }

            $normalized = repaf_normalize_psgc_item($item);
            $normalized['parent_code'] = repaf_psgc_parent_code($item, 'province');

            if ($normalized['parent_code'] === '') {
                $normalized['parent_code'] = repaf_psgc_derived_parent_code(
                    $normalized['code'],
                    'province'
                );
            }

            // NCR is represented as Metro Manila in the Province / State
            // dropdown even though its cities have no province parent.
            if (
                $normalized['parent_code'] === '' &&
                repaf_psgc_parent_code($item, 'region') === '1300000000'
            ) {
                $normalized['parent_code'] = '1300000000';
            }

            if ($normalized['parent_code'] !== '') {
                $result[] = $normalized;
            }
        }

        wp_send_json_success($result);
    }

    $parent_code = isset($_POST['parent_code'])
        ? sanitize_text_field(wp_unslash($_POST['parent_code']))
        : '';

    if (!preg_match('/^[0-9]{10}$/', $parent_code)) {
        wp_send_json_error(array('message' => 'Invalid parent location.'), 400);
    }

    if ($level === 'region_barangays') {
        $items = repaf_fetch_psgc_collection(
            'regions/' . rawurlencode($parent_code) . '/barangays',
            'barangays_region_' . $parent_code,
            45
        );

        if (is_wp_error($items)) {
            wp_send_json_error(array('message' => $items->get_error_message()), 502);
        }

        $result = array();
        foreach ($items as $item) {
            $normalized = repaf_normalize_psgc_item($item, true);
            $normalized['parent_code'] = repaf_psgc_parent_code($item, 'city_municipality');

            if ($normalized['parent_code'] === '') {
                $normalized['parent_code'] = repaf_psgc_derived_parent_code(
                    $normalized['code'],
                    'city_municipality'
                );
            }

            if ($normalized['parent_code'] !== '') {
                $result[] = $normalized;
            }
        }

        wp_send_json_success($result);
    }

    if ($level === 'cities') {
        $path = $parent_code === '1300000000'
            ? 'regions/1300000000/cities-municipalities'
            : 'provinces/' . rawurlencode($parent_code) . '/cities-municipalities';

        $items = repaf_fetch_psgc_collection($path, 'cities_' . $parent_code);

        if (is_wp_error($items)) {
            wp_send_json_error(array('message' => $items->get_error_message()), 502);
        }

        $result = array();
        foreach ($items as $item) {
            // Sub-municipalities and special geographic units are not the
            // city/municipality level requested by this form.
            if (!in_array((string) ($item['type'] ?? ''), array('City', 'Mun'), true)) {
                continue;
            }
            $result[] = repaf_normalize_psgc_item($item);
        }

        usort($result, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        wp_send_json_success($result);
    }

    if ($parent_code === '1380600000') {
        // Manila barangays sit below 14 PSGC sub-municipalities. Fetching the
        // NCR barangay collection and filtering the Manila code prefix keeps
        // the user-facing hierarchy at City of Manila -> Barangay.
        $items = repaf_fetch_psgc_collection(
            'regions/1300000000/barangays',
            'barangays_ncr'
        );

        if (!is_wp_error($items)) {
            $items = array_values(array_filter($items, function ($item) {
                return strpos((string) ($item['code'] ?? ''), '13806') === 0;
            }));
        }
    } else {
        $items = repaf_fetch_psgc_collection(
            'cities-municipalities/' . rawurlencode($parent_code) . '/barangays',
            'barangays_' . $parent_code
        );
    }

    if (is_wp_error($items)) {
        wp_send_json_error(array('message' => $items->get_error_message()), 502);
    }

    $result = array();
    foreach ($items as $item) {
        $result[] = repaf_normalize_psgc_item($item, true);
    }

    usort($result, function ($a, $b) {
        $name_compare = strcasecmp($a['name'], $b['name']);
        if ($name_compare !== 0) {
            return $name_compare;
        }
        return strcasecmp($a['location'], $b['location']);
    });

    wp_send_json_success($result);
}
