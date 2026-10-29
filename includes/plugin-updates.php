<?php

namespace Repaf;

use YahnisElsts\PluginUpdateChecker\v5p7\PucFactory;
use YahnisElsts\PluginUpdateChecker\v5p7\Vcs\Api;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Accept a public GitHub repository root, never credentials or arbitrary hosts.
 */
function repaf_normalize_update_repository($value)
{
    if (!is_string($value)) {
        return '';
    }

    $value = trim($value);
    if (!preg_match('~^https://github\.com/([a-z0-9][a-z0-9-]*)/([a-z0-9_.-]+)/?$~iD', $value, $matches)) {
        return '';
    }

    $repository = preg_replace('/\.git$/i', '', $matches[2]);
    if ($repository === '' || $repository === '.' || $repository === '..') {
        return '';
    }

    return 'https://github.com/' . strtolower($matches[1] . '/' . $repository);
}

function repaf_get_update_repository()
{
    if (defined('REPAF_UPDATE_REPOSITORY')) {
        return repaf_normalize_update_repository(constant('REPAF_UPDATE_REPOSITORY'));
    }

    $default = require REPAF_PLUGIN_PATH . 'includes/update-source.php';
    return repaf_normalize_update_repository(get_option('repaf_update_repository', $default));
}

/**
 * Preserve other plugins' update data when switching or disabling our source.
 */
function repaf_clear_update_cache()
{
    delete_site_option('repaf_update_state');
    wp_clear_scheduled_hook('puc_cron_check_updates-repaf-multi-step-form');

    $updates = get_site_transient('update_plugins');
    if (is_object($updates)) {
        $plugin = plugin_basename(REPAF_PLUGIN_PATH . 'repaf-multi-step-form.php');
        unset($updates->response[$plugin], $updates->no_update[$plugin]);
        set_site_transient('update_plugins', $updates);
    }
}

/**
 * Never fall back to tags/branches, whose source ZIPs omit bundled dependencies.
 */
function repaf_release_update_strategies($strategies)
{
    return isset($strategies[Api::STRATEGY_LATEST_RELEASE])
        ? array(Api::STRATEGY_LATEST_RELEASE => $strategies[Api::STRATEGY_LATEST_RELEASE])
        : array();
}

/** Reject mismatched release versions/assets and unexpected download hosts. */
function repaf_validate_release_info($info)
{
    if (!is_object($info) || !isset($info->version, $info->download_url)
        || !preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/D', $info->version)) {
        return null;
    }

    $base = preg_quote(repaf_get_update_repository(), '~');
    $version = preg_quote($info->version, '~');
    return preg_match('~^(?i:' . $base . ')/releases/download/v?' . $version
        . '/repaf-multi-step-form-' . $version . '\.zip$~D', $info->download_url)
        ? $info : null;
}

function repaf_initialize_updates()
{
    static $checker = null;
    if ($checker !== null) {
        return $checker;
    }

    $repository = repaf_get_update_repository();
    if (get_site_option('repaf_update_source', '') !== $repository) {
        repaf_clear_update_cache();
        update_site_option('repaf_update_source', $repository);
    }

    // An unconfigured installation performs no GitHub requests.
    if ($repository === '') {
        return null;
    }

    require_once REPAF_PLUGIN_PATH . 'libraries/plugin-update-checker/plugin-update-checker.php';

    add_filter(
        'puc_vcs_update_detection_strategies-repaf-multi-step-form',
        __NAMESPACE__ . '\\repaf_release_update_strategies'
    );

    $checker = PucFactory::buildUpdateChecker(
        $repository,
        REPAF_PLUGIN_PATH . 'repaf-multi-step-form.php',
        'repaf-multi-step-form',
        12,
        'repaf_update_state'
    );
    $checker->setBranch('main');
    $checker->getVcsApi()->enableReleaseAssets(
        '/^repaf-multi-step-form-[0-9]+\.[0-9]+\.[0-9]+\.zip$/D',
        Api::REQUIRE_RELEASE_ASSETS
    );
    $checker->addResultFilter(__NAMESPACE__ . '\\repaf_validate_release_info');

    return $checker;
}
// Register outside admin hooks so cron, WP-CLI and automatic updates also work.
add_action('plugins_loaded', __NAMESPACE__ . '\\repaf_initialize_updates', 20);

function repaf_sanitize_update_repository($input)
{
    $value = repaf_normalize_update_repository($input);
    if ($value === '' && (!is_string($input) || trim($input) !== '')) {
        add_settings_error(
            'repaf_update_repository',
            'repaf_invalid_update_repository',
            'Enter a public GitHub repository URL such as https://github.com/your-account/repaf-multi-step-form.'
        );
        return repaf_get_update_repository();
    }
    return $value;
}

function repaf_register_update_settings()
{
    if (!defined('REPAF_UPDATE_REPOSITORY')) {
        register_setting('repaf_color_settings_group', 'repaf_update_repository', array(
            'type'              => 'string',
            'sanitize_callback' => __NAMESPACE__ . '\\repaf_sanitize_update_repository',
        ));
    }
    add_settings_section('repaf_updates_section', 'Plugin Updates', '__return_false', 'repaf-form-settings');
    add_settings_field(
        'repaf_update_repository',
        'GitHub Releases Repository',
        __NAMESPACE__ . '\\repaf_render_update_repository_field',
        'repaf-form-settings',
        'repaf_updates_section'
    );
}
add_action('admin_init', __NAMESPACE__ . '\\repaf_register_update_settings', 20);

function repaf_render_update_repository_field()
{
    printf(
        '<input type="url" name="repaf_update_repository" value="%s" class="large-text code" placeholder="https://github.com/your-account/repaf-multi-step-form" %s />',
        esc_attr(repaf_get_update_repository()),
        defined('REPAF_UPDATE_REPOSITORY') ? 'disabled' : ''
    );
    if (defined('REPAF_UPDATE_REPOSITORY')) {
        echo '<p class="description">The release repository is controlled by REPAF_UPDATE_REPOSITORY in wp-config.php.</p>';
    } else {
        echo '<p class="description">Use the public repository containing REPAF releases. Leave blank to disable update checks. Only use a repository you trust to supply executable plugin code.</p>';
    }
    echo '<p class="description">Checks run approximately every 12 hours. After saving, open Plugins and use REPAF\'s Check for updates link. Publish a stable release with a repaf-multi-step-form-X.Y.Z.zip asset; source archives, tags alone and prereleases are not offered.</p>';
}

function repaf_update_setup_notice()
{
    global $pagenow;
    if ($pagenow !== 'plugins.php' || !current_user_can('update_plugins') || repaf_get_update_repository() !== '') {
        return;
    }
    printf(
        '<div class="notice notice-info"><p>REPAF update checks are disabled. Configure the GitHub Releases Repository in <a href="%s">REPAF Form Settings</a> to receive plugin updates.</p></div>',
        esc_url(admin_url('options-general.php?page=repaf-form-settings'))
    );
}
add_action('admin_notices', __NAMESPACE__ . '\\repaf_update_setup_notice');
