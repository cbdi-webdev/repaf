<?php

/**
 * =========================================================
 * REPAF helpers loader
 * =========================================================
 *
 * This file previously contained all helper functions.
 * They have been split into single-responsibility files
 * for easier maintenance. This loader is kept so existing
 * require_once calls to helpers.php continue to work.
 *
 * File map:
 *   field-renderer.php  — repaf_render_field, repaf_render_radio_group
 *   field-config.php    — repaf_required_fields, repaf_internal_fields
 *   sanitizer.php       — repaf_sanitize_submission
 *   validator.php       — repaf_get_missing_required_fields, repaf_validate_submission
 *   csv-generator.php   — date-named daily CSV batching after salesperson save
 *   response-helper.php — repaf_redirect_with_status, repaf_render_status_notice
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/field-renderer.php';
require_once __DIR__ . '/field-config.php';
require_once __DIR__ . '/sanitizer.php';
require_once __DIR__ . '/validator.php';
require_once __DIR__ . '/csv-generator.php';
require_once __DIR__ . '/response-helper.php';
