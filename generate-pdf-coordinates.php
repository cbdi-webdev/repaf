<?php

/**
 * =========================================================
 * REPAF — PDF Coordinate Map Generator
 * =========================================================
 *
 * Run this script ONCE from the command line or by visiting
 * it directly in your browser (local only) to generate the
 * JSON coordinate file:
 *
 *   php generate-pdf-coordinates.php
 *
 * Or visit in browser:
 *   http://localhost/kaia/wp-content/plugins/repaf-multi-step-form/generate-pdf-coordinates.php
 *
 * Output: assets/pdf/coordinates.json
 *
 * DELETE this file after running it — it should never be
 * accessible on a production server.
 * =========================================================
 */

/**
 * Basic security: only allow local access.
 */
if (
    php_sapi_name() !== 'cli' &&
    !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
) {
    http_response_code(403);
    exit('Access denied — run this script locally only.');
}

/**
 * Bootstrap just enough to call the coordinate functions.
 * We don't need a full WordPress load.
 */
define('ABSPATH', true);

function repaf_coord($x, $y)
{
    return ['x' => $x, 'y' => $y];
}

/**
 * Load the coordinate functions directly.
 * We call them from global scope here, so we use the
 * fully-qualified names.
 */
require_once __DIR__ . '/includes/pdf-mapper.php';

$coordinates = [
    'buyer'   => \Repaf\repaf_get_buyer_pdf_coordinates(),
    'coowner' => \Repaf\repaf_get_coowner_pdf_coordinates(),
];

$output_path = __DIR__ . '/assets/pdf/coordinates.json';

$json = json_encode($coordinates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if ($json === false) {
    exit('JSON encoding failed: ' . json_last_error_msg());
}

$bytes = file_put_contents($output_path, $json);

if ($bytes === false) {
    exit('Could not write to ' . $output_path . ' — check directory permissions.');
}

echo "Success! Written " . number_format($bytes) . " bytes to:\n";
echo $output_path . "\n\n";
echo "Next steps:\n";
echo "  1. Verify the file exists and looks correct.\n";
echo "  2. DELETE this generator script — it must not exist on production.\n";
