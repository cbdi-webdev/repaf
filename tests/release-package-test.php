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
}
foreach (array('repaf-multi-step-form.php', 'includes/plugin-updates.php', 'includes/update-source.php',
    'libraries/plugin-update-checker/plugin-update-checker.php', 'libraries/plugin-update-checker/license.txt',
    'libraries/plugin-update-checker/vendor/Parsedown-LICENSE.txt',
    'templates/form-wrapper.php', 'assets/js/repaf-form.js', 'assets/pdf/repaf-page-1.jpg', 'composer.lock') as $required) {
    if ($zip->locateName($prefix . $required) === false) {
        throw new RuntimeException('Missing runtime file: ' . $required);
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
    $tcpdf = is_file($root . 'vendor/tecnickcom/tcpdf/tcpdf.php')
        ? $root . 'vendor/tecnickcom/tcpdf/tcpdf.php' : $root . 'vendor/tcpdf/tcpdf.php';
    require $tcpdf;
    $pdf = new TCPDF();
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Write(0, 'REPAF packaged PDF engine smoke test');
    $pdf->Image($root . 'assets/pdf/repaf-page-1.jpg', 10, 30, 50);
    $bytes = $pdf->Output('', 'S');
    if (strpos($bytes, '%PDF-') !== 0 || strlen($bytes) < 1000) {
        throw new RuntimeException('Packaged PDF engine did not produce a PDF.');
    }
    fwrite(STDOUT, 'Verified ' . $count . ' package entries; bundled TCPDF generated a PDF with packaged artwork.' . PHP_EOL);
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
