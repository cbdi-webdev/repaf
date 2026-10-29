<?php

/** Inspect an actual release ZIP and smoke-test its bundled PDF engine. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
if (!isset($argv[1]) || !is_file($argv[1])) {
    fwrite(STDERR, "Usage: php tests/release-package-test.php /path/to/release.zip\n");
    exit(1);
}
$zip = new ZipArchive();
if ($zip->open($argv[1]) !== true) {
    throw new RuntimeException('Cannot open release ZIP.');
}
$prefix = 'repaf-multi-step-form/';
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if (strpos($name, $prefix) !== 0 || strpos($name, '..') !== false || strpos($name, '\\') !== false
        || preg_match('~/(?:\.git|\.github|tests|tools|uploads|dist|node_modules)/|(?:^|/)\.env|wp-config\.php~', $name)) {
        throw new RuntimeException('Unexpected release entry: ' . $name);
    }
    if (preg_match('~^' . preg_quote($prefix, '~') . 'assets/pdf/[^/]+\.(?:jpg|png)$~i', $name)) {
        throw new RuntimeException('Redundant legacy artwork in release: ' . $name);
    }
    if (preg_match('~/fonts/(?:freeserif|freesans|cid0|dejavusanscondensed)~', $name)) {
        throw new RuntimeException('Unused font in release: ' . $name);
    }
}
if (filesize($argv[1]) >= 24 * 1048576) {
    throw new RuntimeException('Release exceeds the 24 MiB packaging budget.');
}
foreach (array('repaf-multi-step-form.php', 'includes/plugin-updates.php', 'includes/update-source.php',
    'libraries/plugin-update-checker/plugin-update-checker.php', 'libraries/plugin-update-checker/license.txt',
    'libraries/plugin-update-checker/vendor/Parsedown-LICENSE.txt',
    'templates/form-wrapper.php', 'assets/js/repaf-form.js', 'composer.lock') as $required) {
    if ($zip->locateName($prefix . $required) === false) {
        throw new RuntimeException('Missing runtime file: ' . $required);
    }
}
foreach (array('cbdi', 'kaia') as $profile) {
    foreach (array('page-1', 'page-2', 'property-information', 'financial-information', 'salesperson-information', 'terms-and-conditions') as $page) {
        $required = $prefix . 'assets/pdf/profiles/' . $profile . '/' . $page . '.jpg';
        if ($zip->locateName($required) === false) {
            throw new RuntimeException('Missing brand template: ' . $required);
        }
    }
}
$temporary = sys_get_temp_dir() . '/repaf-release-test-' . bin2hex(random_bytes(8));
if (!mkdir($temporary, 0700)) {
    throw new RuntimeException('Cannot create package smoke-test directory.');
}
try {
    if (!$zip->extractTo($temporary)) {
        throw new RuntimeException('Cannot extract release package.');
    }
    $count = $zip->numFiles;
    $zip->close();
    $root = $temporary . '/' . $prefix;
    require __DIR__ . '/pdf-package-fixture.php';
    repaf_verify_packaged_forms($root, $temporary, isset($argv[2]) ? $argv[2] : null);
    fwrite(STDOUT, 'Verified ' . $count . ' package entries and both complete branded forms.' . PHP_EOL);
} finally {
    // Delete only the unique temporary directory this test created.
    $resolved = realpath($temporary);
    $base = realpath(sys_get_temp_dir());
    if ($resolved && $base && strpos($resolved, $base . DIRECTORY_SEPARATOR . 'repaf-release-test-') === 0) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $entry) {
            if ($entry->isDir() && !$entry->isLink()) { rmdir($entry->getPathname()); }
            else { unlink($entry->getPathname()); }
        }
        rmdir($resolved);
    }
}
