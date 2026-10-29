<?php

/** Build an installable ZIP without changing the working plugin or its settings. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

function repaf_build_fail($message)
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

$options = getopt('', array('repository:', 'output:', 'tag:'));
$root = dirname(__DIR__);
$main = file_get_contents($root . '/repaf-multi-step-form.php');
if (!preg_match('/^\s*\* Version:\s*([0-9]+\.[0-9]+\.[0-9]+)\s*$/m', $main, $match)) {
    repaf_build_fail('The plugin must have a stable X.Y.Z Version header.');
}
$version = $match[1];
if (!preg_match("/define\('REPAF_VERSION', '" . preg_quote($version, '/') . "'\);/", $main)) {
    repaf_build_fail('Version header and REPAF_VERSION must match.');
}
$readme = file_get_contents($root . '/readme.txt');
if (!preg_match('/^Stable tag:\s*' . preg_quote($version, '/') . '\s*$/m', $readme)) {
    repaf_build_fail('The readme.txt Stable tag must match the plugin version.');
}
if (isset($options['tag']) && $options['tag'] !== 'v' . $version) {
    repaf_build_fail('The Git tag must be v' . $version . '.');
}
if (!class_exists('ZipArchive')) {
    repaf_build_fail('Enable the PHP zip extension to build a release.');
}

// Load only the URL validation helper, with no WordPress bootstrap or database.
define('ABSPATH', $root . '/');
function add_action() {}
require $root . '/includes/plugin-updates.php';
$repository = isset($options['repository'])
    ? Repaf\repaf_normalize_update_repository($options['repository'])
    : Repaf\repaf_normalize_update_repository(require $root . '/includes/update-source.php');
if (isset($options['repository']) && $repository === '') {
    repaf_build_fail('--repository must be an HTTPS public GitHub repository root URL.');
}

$required = array(
    'includes/plugin-updates.php', 'includes/update-source.php',
    'libraries/plugin-update-checker/plugin-update-checker.php',
    'libraries/plugin-update-checker/load-v5p7.php',
    'libraries/plugin-update-checker/license.txt',
    'composer.lock', 'readme.txt',
);
$profileAssets = array();
foreach (array('cbdi', 'kaia') as $profile) {
    foreach (array('page-1', 'page-2', 'financial-information', 'property-information', 'salesperson-information', 'terms-and-conditions') as $page) {
        $profileAssets[] = 'assets/pdf/profiles/' . $profile . '/' . $page . '.jpg';
    }
}
// Every release carries complete profiles, so legacy root artwork is redundant.
$required = array_merge($required, $profileAssets);
foreach ($required as $file) {
    if (!is_file($root . '/' . $file)) {
        repaf_build_fail('Missing required release file: ' . $file);
    }
}

// Both existing manual installs and fresh Composer installs are supported.
$tcpdf = is_file($root . '/vendor/tecnickcom/tcpdf/tcpdf.php')
    ? 'vendor/tecnickcom/tcpdf' : 'vendor/tcpdf';
$requiredTcpdf = array('tcpdf.php', 'include/tcpdf_static.php', 'LICENSE.TXT');
$fontFiles = array('symbol.php', 'zapfdingbats.php');
foreach (array('helvetica', 'courier', 'times') as $family) {
    foreach (array('', 'b', 'i', 'bi') as $style) {
        $fontFiles[] = $family . $style . '.php';
    }
}
foreach (array('', 'b', 'i', 'bi') as $style) {
    foreach (array('php', 'z', 'ctg.z') as $extension) {
        $fontFiles[] = 'dejavusans' . $style . '.' . $extension;
    }
}
foreach ($fontFiles as $font) {
    $requiredTcpdf[] = 'fonts/' . $font;
}
foreach ($requiredTcpdf as $file) {
    if (!is_file($root . '/' . $tcpdf . '/' . $file)) {
        repaf_build_fail('TCPDF is incomplete. Run composer install --no-dev --prefer-dist first. Missing: ' . $file);
    }
}
$lock = json_decode(file_get_contents($root . '/composer.lock'), true);
$lockedVersion = '';
foreach ($lock['packages'] as $package) {
    if ($package['name'] === 'tecnickcom/tcpdf') {
        $lockedVersion = ltrim($package['version'], 'v');
    }
}
$tcpdfStatic = file_get_contents($root . '/' . $tcpdf . '/include/tcpdf_static.php');
if (!$lockedVersion || !preg_match('/\$tcpdf_version\s*=\s*[\'"]' . preg_quote($lockedVersion, '/') . '[\'"]/', $tcpdfStatic)) {
    repaf_build_fail('Installed TCPDF does not match composer.lock. Run composer install --no-dev --prefer-dist.');
}

$output = isset($options['output']) ? $options['output'] : $root . '/dist';
if (!is_dir($output) && !mkdir($output, 0775, true)) {
    repaf_build_fail('Cannot create the output directory.');
}
$destination = rtrim($output, '/\\') . '/repaf-multi-step-form-' . $version . '.zip';
if (file_exists($destination)) {
    repaf_build_fail('Release ZIP already exists: ' . $destination . '. Use a different output directory or version.');
}
$zip = new ZipArchive();
if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
    repaf_build_fail('Cannot open the release ZIP.');
}
$prefix = 'repaf-multi-step-form/';
$files = array('repaf-multi-step-form.php', 'uninstall.php', 'README.md', 'readme.txt', 'RELEASES.md', 'SECURITY-HARDENING.md', 'composer.json', 'composer.lock');
foreach (array('assets', 'includes', 'integration', 'templates', 'libraries', 'vendor') as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS),
        function ($entry) {
            return !$entry->isLink()
                && !in_array($entry->getFilename(), array('.git', '.github', 'node_modules', 'tests', 'tools', 'examples', 'cache'), true)
                && !preg_match('/^(\.env|\.DS_Store|Thumbs\.db)|\.(log|bak|zip|sql)$/i', $entry->getFilename());
        }
    ));
    foreach ($iterator as $entry) {
        if ($entry->isFile()) {
            $file = str_replace('\\', '/', substr($entry->getPathname(), strlen($root) + 1));
            if (strpos($file, 'assets/pdf/') === 0 && !in_array($file, $profileAssets, true)) {
                continue;
            }
            // A source checkout can contain both old and Composer vendor layouts.
            if ($tcpdf !== 'vendor/tcpdf' && strpos($file, 'vendor/tcpdf/') === 0) {
                continue;
            }
            $fontPrefix = $tcpdf . '/fonts/';
            if (strpos($file, $fontPrefix) === 0) {
                $font = substr($file, strlen($fontPrefix));
                if (!in_array($font, $fontFiles, true) && !preg_match('/(?:license|copying|readme)/i', $font)) {
                    continue;
                }
            }
            $files[] = $file;
        }
    }
}
sort($files);
foreach ($files as $file) {
    $added = $file === 'includes/update-source.php'
        ? $zip->addFromString($prefix . $file, "<?php\n\n// Default public release repository. Site settings and wp-config.php override it.\nreturn " . var_export($repository, true) . ";\n")
        : $zip->addFile($root . '/' . $file, $prefix . $file);
    if (!$added) {
        repaf_build_fail('Cannot add file to release: ' . $file);
    }
}
if (!$zip->close()) {
    repaf_build_fail('Could not finish writing the release ZIP.');
}
fwrite(STDOUT, 'Built ' . realpath($destination) . ' (' . count($files) . ' files)' . PHP_EOL);
fwrite(STDOUT, 'ZIP size: ' . round(filesize($destination) / 1048576, 2) . ' MiB; original template image bytes preserved.' . PHP_EOL);
if ($repository === '') {
    fwrite(STDOUT, 'No default repository embedded. Configure it under Settings > REPAF Form Settings after installation.' . PHP_EOL);
} else {
    fwrite(STDOUT, 'Default release repository: ' . $repository . PHP_EOL);
}
