<?php

/** Standalone integration checks: real bundled PUC, mocked WordPress and HTTP. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});

define('ABSPATH', dirname(__DIR__) . '/');
define('REPAF_PLUGIN_PATH', ABSPATH);
define('WP_PLUGIN_DIR', dirname(dirname(__DIR__)));
define('WPMU_PLUGIN_DIR', WP_PLUGIN_DIR . '/mu-plugins');
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);
define('WP_DEBUG', false);
$GLOBALS['wp_version'] = '7.0';
$GLOBALS['repaf_test'] = array('hooks' => array(), 'options' => array(), 'site_options' => array(),
    'transients' => array(), 'cron' => array(), 'http' => array(), 'fixture' => null, 'errors' => array(),
    'live' => isset($argv[1]) && $argv[1] === '--live');

// Small in-memory substitutes; never load wp-config.php or connect to a database.
function add_filter($tag, $callback, $priority = 10, $accepted = 1) {
    $GLOBALS['repaf_test']['hooks'][$tag][$priority][] = array($callback, $accepted);
}
function add_action($tag, $callback, $priority = 10, $accepted = 1) { add_filter($tag, $callback, $priority, $accepted); }
function apply_filters($tag, $value, ...$args) {
    $hooks = isset($GLOBALS['repaf_test']['hooks'][$tag]) ? $GLOBALS['repaf_test']['hooks'][$tag] : array();
    ksort($hooks);
    foreach ($hooks as $callbacks) {
        foreach ($callbacks as $entry) {
            $value = call_user_func_array($entry[0], array_slice(array_merge(array($value), $args), 0, $entry[1]));
        }
    }
    return $value;
}
function do_action($tag, ...$args) { apply_filters($tag, isset($args[0]) ? $args[0] : null, ...array_slice($args, 1)); }
function remove_filter($tag, $callback, $priority = 10) {
    if (!isset($GLOBALS['repaf_test']['hooks'][$tag][$priority])) { return; }
    foreach ($GLOBALS['repaf_test']['hooks'][$tag][$priority] as $key => $entry) {
        if ($entry[0] === $callback) { unset($GLOBALS['repaf_test']['hooks'][$tag][$priority][$key]); }
    }
}
function remove_action($tag, $callback, $priority = 10) { remove_filter($tag, $callback, $priority); }
function did_action($tag) { return 0; }
function register_deactivation_hook($file, $callback) { add_action('deactivate_' . plugin_basename($file), $callback); }
function plugin_basename($file) { return 'repaf-multi-step-form/' . basename($file); }
function wp_normalize_path($path) { return str_replace('\\', '/', $path); }
function trailingslashit($path) { return rtrim($path, '/\\') . '/'; }
function plugins_url($path = '', $plugin = '') { return 'https://site.example/wp-content/plugins/repaf-multi-step-form/' . $path; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function _cleanup_header_comment($value) { return trim($value); }
function get_option($key, $default = false) { return array_key_exists($key, $GLOBALS['repaf_test']['options']) ? $GLOBALS['repaf_test']['options'][$key] : $default; }
function get_site_option($key, $default = false) { return isset($GLOBALS['repaf_test']['site_options'][$key]) ? $GLOBALS['repaf_test']['site_options'][$key] : $default; }
function update_site_option($key, $value) { $GLOBALS['repaf_test']['site_options'][$key] = $value; }
function delete_site_option($key) { unset($GLOBALS['repaf_test']['site_options'][$key]); }
function get_site_transient($key) { return apply_filters('site_transient_' . $key, isset($GLOBALS['repaf_test']['transients'][$key]) ? $GLOBALS['repaf_test']['transients'][$key] : false); }
function set_site_transient($key, $value, $ttl = 0) { $GLOBALS['repaf_test']['transients'][$key] = $value; }
function wp_clear_scheduled_hook($key) { unset($GLOBALS['repaf_test']['cron'][$key]); }
function wp_next_scheduled($key) { return isset($GLOBALS['repaf_test']['cron'][$key]) ? $GLOBALS['repaf_test']['cron'][$key][0] : false; }
function wp_schedule_event($time, $period, $key) { $GLOBALS['repaf_test']['cron'][$key] = array($time, $period); }
function wp_rand($min, $max) { return $min; }
function wp_doing_cron() { return false; }
function is_admin() { return false; }
function is_multisite() { return false; }
function get_available_languages() { return array(); }
function get_locale() { return 'en_US'; }
function current_filter() { return ''; }
function __($value, $domain = '') { return $value; }
function add_settings_error($setting, $code, $message) { $GLOBALS['repaf_test']['errors'][] = $code; }
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function balanceTags($text, $force = false) { return $text; }
function wp_kses($text, $allowed = array(), $protocols = array()) { return $text; }
function get_plugin_data($path, $markup = false, $translate = false) {
    $headers = array('Name' => 'Plugin Name', 'Version' => 'Version', 'Author' => 'Author',
        'Description' => 'Description', 'RequiresWP' => 'Requires at least', 'RequiresPHP' => 'Requires PHP',
        'PluginURI' => 'Plugin URI', 'TextDomain' => 'Text Domain', 'AuthorURI' => 'Author URI');
    $contents = file_get_contents($path);
    $result = array();
    foreach ($headers as $key => $label) {
        $result[$key] = preg_match('/^[ \t\/*#@]*' . preg_quote($label, '/') . ':(.*)$/mi', $contents, $match) ? trim($match[1]) : '';
    }
    // Keep synthetic release fixtures independent of the current source version.
    $result['Version'] = $GLOBALS['repaf_test']['live'] ? '1.6.9' : '1.7.0';
    return $result;
}
class WP_Error {
    private $code;
    private $message;
    public function __construct($code, $message = '') { $this->code = $code; $this->message = $message; }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
    public function get_error_messages() { return array($this->message); }
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function wp_remote_get($url, $options = array()) {
    $GLOBALS['repaf_test']['http'][] = $url;
    if ($GLOBALS['repaf_test']['live']) {
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_HOST) !== 'api.github.com') {
            throw new RuntimeException('Live check only permits the public GitHub API.');
        }
        $curl = curl_init($url);
        curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'REPAF-live-update-verification', CURLOPT_HTTPHEADER => array('Accept: application/vnd.github+json')));
        $body = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        return $body === false ? new WP_Error('http_request_failed', curl_error($curl))
            : array('response' => array('code' => $status), 'body' => $body);
    }
    return call_user_func($GLOBALS['repaf_test']['fixture'], $url);
}
function wp_remote_retrieve_response_code($response) { return $response['response']['code']; }
function wp_remote_retrieve_body($response) { return $response['body']; }

$passed = 0;
function repaf_assert($condition, $message) {
    if (!$condition) { throw new RuntimeException('FAIL: ' . $message); }
    $GLOBALS['passed']++;
}
function repaf_http_json($value, $status = 200) {
    return array('response' => array('code' => $status), 'body' => json_encode($value));
}
function repaf_release_fixture($changes = array(), $headerVersion = '1.7.1') {
    $release = array_merge(array(
        'tag_name' => 'v1.7.1', 'draft' => false, 'prerelease' => false,
        'created_at' => '2026-10-29T00:00:00Z', 'body' => 'REPAF release notes.',
        'zipball_url' => 'https://api.github.com/repos/example/repaf/zipball/v1.7.1',
        'assets' => array(array('name' => 'repaf-multi-step-form-1.7.1.zip', 'download_count' => 1,
            'browser_download_url' => 'https://github.com/example/repaf/releases/download/v1.7.1/repaf-multi-step-form-1.7.1.zip')),
    ), $changes);
    $GLOBALS['repaf_test']['http'] = array();
    $GLOBALS['repaf_test']['fixture'] = function ($url) use ($release, $headerVersion) {
        if (strpos($url, '/releases/latest') !== false) { return repaf_http_json($release); }
        if (strpos($url, '/contents/repaf-multi-step-form.php') !== false) {
            return repaf_http_json(array('encoding' => 'base64', 'content' => base64_encode(
                "<?php\n/*\nPlugin Name: REPAF Multi-Step Form\nVersion: " . $headerVersion
                . "\nRequires at least: 5.8\nRequires PHP: 7.4\n*/\n"
            )));
        }
        if (strpos($url, '/contents/readme.txt') !== false) {
            return repaf_http_json(array('encoding' => 'base64', 'content' => base64_encode(
                "=== REPAF Multi-Step Form ===\nRequires at least: 5.8\nRequires PHP: 7.4\nStable tag: " . $headerVersion
                . "\n\n== Description ==\nREPAF form.\n\n== Changelog ==\n\n= " . $headerVersion . " =\n* New release.\n"
            )));
        }
        throw new RuntimeException('Unexpected HTTP request (possible tag/branch fallback): ' . $url);
    };
}

require dirname(__DIR__) . '/includes/plugin-updates.php';

if ($GLOBALS['repaf_test']['live']) {
    // Simulate an old installation only in memory; never edit plugin/site data.
    $GLOBALS['repaf_test']['options']['repaf_update_repository'] = 'https://github.com/cbdi-webdev/repaf';
    $checker = Repaf\repaf_initialize_updates();
    $update = $checker->checkForUpdates();
    repaf_assert($update !== null && version_compare($update->version, '1.6.9', '>'), 'Public release is discoverable by an older installation');
    if (isset($argv[2])) {
        repaf_assert($update->version === $argv[2], 'Expected public release version ' . $argv[2]);
    }
    $native = $checker->injectUpdate((object) array('response' => array()));
    $key = 'repaf-multi-step-form/repaf-multi-step-form.php';
    repaf_assert(isset($native->response[$key]->package), 'Public release reaches WordPress update data');
    fwrite(STDOUT, 'Live public GitHub check passed: simulated 1.6.9 -> ' . $update->version . PHP_EOL
        . 'Package: ' . $native->response[$key]->package . PHP_EOL);
    exit;
}

foreach (array(
    'https://github.com/example/repaf' => 'https://github.com/example/repaf',
    ' https://GitHub.com/example/repaf.git/ ' => 'https://github.com/example/repaf',
    'https://github.com/Example/REPAF' => 'https://github.com/example/repaf',
    'http://github.com/example/repaf' => '',
    'https://github.com.evil.test/example/repaf' => '',
    'https://token@github.com/example/repaf' => '',
    'https://github.com/example/repaf?token=secret' => '',
    'https://github.com/example/repaf/releases' => '',
    'https://github.com/example/..' => '',
    '' => '',
) as $input => $expected) {
    repaf_assert(Repaf\repaf_normalize_update_repository($input) === $expected, 'Validate repository: ' . $input);
}
repaf_assert(Repaf\repaf_normalize_update_repository(array('bad')) === '', 'Reject non-string repository');
repaf_assert(Repaf\repaf_get_update_repository() === 'https://github.com/cbdi-webdev/repaf', 'Default release repository is configured');
$GLOBALS['repaf_test']['options']['repaf_update_repository'] = '';
repaf_assert(Repaf\repaf_initialize_updates() === null, 'Unconfigured updater stays disabled');
repaf_assert(count($GLOBALS['repaf_test']['http']) === 0, 'Unconfigured updater sends no requests');
repaf_assert(!class_exists('YahnisElsts\\PluginUpdateChecker\\v5p7\\PucFactory', false), 'No library loaded without configuration');

$GLOBALS['repaf_test']['options']['repaf_update_repository'] = 'https://github.com/example/repaf';
repaf_assert(Repaf\repaf_sanitize_update_repository('https://malicious.test/update') === 'https://github.com/example/repaf', 'Invalid save preserves trusted source');
repaf_assert(count($GLOBALS['repaf_test']['errors']) === 1, 'Invalid save reports settings error');
repaf_assert(Repaf\repaf_sanitize_update_repository('  ') === '', 'Blank source disables checks');

$key = 'repaf-multi-step-form/repaf-multi-step-form.php';
$other = (object) array('new_version' => '2.0.0');
$GLOBALS['repaf_test']['transients']['update_plugins'] = (object) array(
    'response' => array($key => (object) array('package' => 'https://old.test/archive.zip'), 'other/plugin.php' => $other),
    'no_update' => array($key => new stdClass(), 'other2/plugin.php' => $other),
    'checked' => array($key => '1.7.0'),
);
$GLOBALS['repaf_test']['site_options']['repaf_update_state'] = 'stale-source-state';
$GLOBALS['repaf_test']['site_options']['repaf_update_source'] = 'https://github.com/example/old';
$checker = Repaf\repaf_initialize_updates();
repaf_assert($checker instanceof YahnisElsts\PluginUpdateChecker\v5p7\Vcs\PluginUpdateChecker, 'Real PUC GitHub checker initializes');
repaf_assert(Repaf\repaf_initialize_updates() === $checker, 'Initialization is idempotent');
repaf_assert(!isset($GLOBALS['repaf_test']['site_options']['repaf_update_state']), 'Changing repository discards old update state');
repaf_assert(!isset($GLOBALS['repaf_test']['transients']['update_plugins']->response[$key]), 'Changing repository clears old package');
repaf_assert($GLOBALS['repaf_test']['transients']['update_plugins']->response['other/plugin.php'] === $other, 'Other plugin update survives');
repaf_assert($GLOBALS['repaf_test']['transients']['update_plugins']->no_update['other2/plugin.php'] === $other, 'Other plugin no-update entry survives');
repaf_assert($GLOBALS['repaf_test']['cron']['puc_cron_check_updates-repaf-multi-step-form'][1] === 'twicedaily', 'Background checks run twice daily');

repaf_release_fixture();
$update = $checker->checkForUpdates();
repaf_assert($update !== null && $update->version === '1.7.1', 'Newer stable release is discovered');
repaf_assert($update->download_url === 'https://github.com/example/repaf/releases/download/v1.7.1/repaf-multi-step-form-1.7.1.zip', 'Download uses complete release asset');
$native = $checker->injectUpdate((object) array('response' => array('other/plugin.php' => $other)));
repaf_assert($native->response[$key]->new_version === '1.7.1', 'Update is exposed to native WordPress UI');
repaf_assert($native->response[$key]->plugin === $key, 'WordPress identifies the correct installed plugin');
repaf_assert($native->response['other/plugin.php'] === $other, 'Update injection preserves other plugins');
repaf_assert($native->response[$key]->autoupdate === false, 'Does not force automatic installation');
repaf_assert($native->response[$key]->requires_php === '7.4', 'PHP requirement is passed to WordPress');

$mixedCaseInfo = (object) array('version' => '1.7.1', 'download_url' => 'https://github.com/Example/REPAF/releases/download/v1.7.1/repaf-multi-step-form-1.7.1.zip');
repaf_assert(Repaf\repaf_validate_release_info($mixedCaseInfo) === $mixedCaseInfo, 'Canonical GitHub repository casing is accepted');

$info = $checker->injectInfo(false, 'plugin_information', (object) array('slug' => 'repaf-multi-step-form'));
repaf_assert($info->version === '1.7.1' && !empty($info->sections['changelog']), 'Version details and changelog are exposed');

foreach (array(
    array('assets' => array()),
    array('draft' => true),
    array('prerelease' => true),
    array('assets' => array(array('name' => 'source.zip', 'download_count' => 0,
        'browser_download_url' => 'https://github.com/example/repaf/archive/v1.7.1.zip'))),
    array('assets' => array(array('name' => 'repaf-multi-step-form-1.7.1.zip', 'download_count' => 0,
        'browser_download_url' => 'https://malicious.test/repaf-multi-step-form-1.7.1.zip'))),
) as $changes) {
    repaf_release_fixture($changes);
    repaf_assert($checker->checkForUpdates() === null, 'Reject invalid release: ' . json_encode($changes));
    repaf_assert(count($GLOBALS['repaf_test']['http']) >= 1, 'Release validation performs a check');
}
repaf_release_fixture(array(), '1.7.2');
repaf_assert($checker->checkForUpdates() === null, 'Reject mismatched remote header and ZIP version');
repaf_release_fixture(array(), '1.7.1-beta');
repaf_assert($checker->checkForUpdates() === null, 'Reject non-stable version even if release flag is wrong');

foreach (array('1.7.0', '1.6.9') as $version) {
    repaf_release_fixture(array('tag_name' => 'v' . $version, 'assets' => array(array(
        'name' => 'repaf-multi-step-form-' . $version . '.zip', 'download_count' => 0,
        'browser_download_url' => 'https://github.com/example/repaf/releases/download/v' . $version . '/repaf-multi-step-form-' . $version . '.zip',
    ))), $version);
    repaf_assert($checker->checkForUpdates() === null, 'Do not offer same or older version ' . $version);
    $native = $checker->injectUpdate((object) array('response' => array()));
    repaf_assert(isset($native->no_update[$key]), 'Native auto-update preference remains available without an update');
}
foreach (array(404, 403, 500) as $status) {
    $GLOBALS['repaf_test']['fixture'] = function () use ($status) { return repaf_http_json(array('message' => 'Unavailable'), $status); };
    repaf_assert($checker->checkForUpdates() === null, 'HTTP ' . $status . ' fails without offering an archive');
}
$GLOBALS['repaf_test']['fixture'] = function () { return new WP_Error('http_request_failed', 'Offline'); };
repaf_assert($checker->checkForUpdates() === null, 'Network errors do not break update checks');

$checker->removeHooks();
$GLOBALS['repaf_test']['options']['repaf_update_repository'] = '';
Repaf\repaf_clear_update_cache();
repaf_assert(!wp_next_scheduled('puc_cron_check_updates-repaf-multi-step-form'), 'Disabling clears background schedule');
repaf_assert(!isset($GLOBALS['repaf_test']['site_options']['repaf_update_state']), 'Disabling clears cached release state');
define('REPAF_UPDATE_REPOSITORY', 'https://github.com/example/locked');
repaf_assert(Repaf\repaf_get_update_repository() === 'https://github.com/example/locked', 'wp-config constant takes precedence over settings');

fwrite(STDOUT, 'Passed ' . $passed . ' updater checks using real PUC with mocked HTTP and WordPress state.' . PHP_EOL);
