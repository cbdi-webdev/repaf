<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Branding and PDF-template profiles supported by this shared plugin.
 */
function repaf_site_profiles()
{
    return array(
        'cbdi' => array(
            'label'        => 'CBDI / CitiHomes',
            'company_name' => 'Citihomes Builder and Development, Inc.',
            'pdf_author'   => 'CBDI',
        ),
        'kaia' => array(
            'label'        => 'KAIA Homes',
            'company_name' => 'KAIA Homes, Inc.',
            'pdf_author'   => 'KAIA Homes',
        ),
    );
}

function repaf_sanitize_site_profile($value)
{
    $value = strtolower(trim((string) $value));
    return in_array($value, array('auto', 'cbdi', 'kaia'), true) ? $value : 'auto';
}

/**
 * Resolve this installation's profile. An explicit wp-config.php constant or
 * saved setting wins; Auto then checks the URL, site name, company text, and
 * local WordPress path. Unknown legacy installations retain the KAIA default.
 */
function repaf_get_site_profile()
{
    if (defined('REPAF_SITE_PROFILE')) {
        $configured = repaf_sanitize_site_profile(constant('REPAF_SITE_PROFILE'));
    } else {
        $configured = function_exists('get_option')
            ? repaf_sanitize_site_profile(get_option('repaf_site_profile', 'auto'))
            : 'auto';
    }

    if ($configured !== 'auto') {
        $profile = $configured;
    } else {
        $location_signals = array(ABSPATH);
        $label_signals = array();

        if (function_exists('home_url')) {
            $location_signals[] = home_url('/');
        }
        if (function_exists('get_site_url')) {
            $location_signals[] = get_site_url();
        }
        if (function_exists('get_bloginfo')) {
            $label_signals[] = get_bloginfo('name');
        }
        if (function_exists('get_option')) {
            $text_settings = get_option('repaf_text_settings', array());
            if (is_array($text_settings)) {
                $label_signals[] = $text_settings['company_name'] ?? '';
            }
        }

        $site_location = strtolower(implode(' ', array_map('strval', $location_signals)));
        $site_labels = strtolower(implode(' ', array_map('strval', $label_signals)));

        if (preg_match('/\bcbdi\b|citihomes|mycitihomes/', $site_location)) {
            $profile = 'cbdi';
        } elseif (strpos($site_location, 'kaia') !== false) {
            $profile = 'kaia';
        } elseif (preg_match('/\bcbdi\b|citihomes|mycitihomes/', $site_labels)) {
            $profile = 'cbdi';
        } elseif (strpos($site_labels, 'kaia') !== false) {
            $profile = 'kaia';
        } else {
            $profile = 'kaia';
        }
    }

    if (function_exists('apply_filters')) {
        $profile = apply_filters('repaf_site_profile', $profile);
    }

    return isset(repaf_site_profiles()[$profile]) ? $profile : 'kaia';
}

function repaf_get_site_profile_config($profile = null)
{
    $profiles = repaf_site_profiles();
    $profile = $profile ?: repaf_get_site_profile();

    return $profiles[$profile] ?? $profiles['kaia'];
}

/**
 * Use the saved company label unless it is the known default of the other
 * profile, which can remain behind after moving a database or plugin package.
 */
function repaf_get_site_company_name()
{
    $active_profile = repaf_get_site_profile();
    $active_config = repaf_get_site_profile_config($active_profile);
    $saved = function_exists('get_option')
        ? get_option('repaf_text_settings', array())
        : array();
    $company_name = is_array($saved) ? trim((string) ($saved['company_name'] ?? '')) : '';

    foreach (repaf_site_profiles() as $profile => $config) {
        if ($profile !== $active_profile && $company_name === $config['company_name']) {
            return $active_config['company_name'];
        }
    }

    return $company_name !== '' ? $company_name : $active_config['company_name'];
}

/**
 * Return one template from the active site's isolated asset directory.
 */
function repaf_get_pdf_template_path($template)
{
    $template = basename((string) $template);
    $profile = repaf_get_site_profile();
    $path = REPAF_PLUGIN_PATH . 'assets/pdf/profiles/' . $profile . '/' . $template;

    if (file_exists($path)) {
        return function_exists('apply_filters')
            ? apply_filters('repaf_pdf_template_path', $path, $template, $profile)
            : $path;
    }

    // Upgrade fallback for installations that have not copied profile assets.
    $legacy_names = array(
        'page-1.jpg'                  => 'repaf-page-1.jpg',
        'page-2.jpg'                  => 'repaf-page-2.jpg',
        'financial-information.jpg'   => 'repaf-financial-information.jpg',
        'property-information.jpg'    => 'repaf-property-information.jpg',
        'salesperson-information.jpg' => 'repaf-salesperson-information.jpg',
        'terms-and-conditions.jpg'    => $profile === 'cbdi' ? 'Group 83.jpg' : 'Group 89.jpg',
    );

    return REPAF_PLUGIN_PATH . 'assets/pdf/' . ($legacy_names[$template] ?? $template);
}

/**
 * Public URL for displaying the same profile-specific artwork in the form UI.
 */
function repaf_get_pdf_template_url($template)
{
    $template = basename((string) $template);
    $profile = repaf_get_site_profile();
    $profile_path = REPAF_PLUGIN_PATH . 'assets/pdf/profiles/' . $profile . '/' . $template;

    if (file_exists($profile_path)) {
        $public_path = $profile_path;
        $url = REPAF_PLUGIN_URL . 'assets/pdf/profiles/' . $profile . '/' . rawurlencode($template);
    } else {
        $path = repaf_get_pdf_template_path($template);
        $public_path = $path;
        $relative_path = ltrim(str_replace(REPAF_PLUGIN_PATH, '', $path), '/\\');
        $url = REPAF_PLUGIN_URL . str_replace('\\', '/', $relative_path);
    }

    if (file_exists($public_path)) {
        $url .= '?ver=' . filemtime($public_path);
    }

    return function_exists('apply_filters')
        ? apply_filters('repaf_pdf_template_url', $url, $template, $profile)
        : $url;
}
