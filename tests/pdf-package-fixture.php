<?php

namespace Repaf {
    // Isolate only WordPress storage; use the package's real form/PDF functions.
    function repaf_protect_upload_dirs() {}
    function repaf_generated_file_name($post_id, $extension) {
        return 'repaf-' . $GLOBALS['repaf_pdf_profile'] . '.' . $extension;
    }
}

namespace {
    if (PHP_SAPI !== 'cli') {
        http_response_code(403);
        exit;
    }

    function get_option($name, $default = false) {
        return $name === 'repaf_site_profile' ? $GLOBALS['repaf_pdf_profile'] : $default;
    }
    function apply_filters($name, $value) { return $value; }
    function wp_strip_all_tags($text) { return strip_tags((string) $text); }
    function sanitize_key($text) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $text)); }
    function trailingslashit($path) { return rtrim($path, '/\\') . '/'; }
    function wp_upload_dir() { return array('basedir' => $GLOBALS['repaf_pdf_uploads']); }
    function wp_mkdir_p($path) { return is_dir($path) || mkdir($path, 0700, true); }
    function is_wp_error($value) { return $value instanceof WP_Error; }
    class WP_Error {
        public $message;
        public function __construct($code, $message = '') { $this->message = $message; }
    }

    function repaf_verify_packaged_forms($root, $temporary, $exportDirectory = null)
    {
        define('ABSPATH', $root);
        define('REPAF_PLUGIN_PATH', $root);
        $GLOBALS['repaf_pdf_uploads'] = $temporary . '/generated';
        foreach (array('site-profile', 'field-config', 'pdf-mapper', 'pdf-mapper-updated', 'pdf-generator-data',
            'pdf-generator-helpers', 'pdf-generator-pages', 'pdf-generator') as $include) {
            require_once $root . 'includes/' . $include . '.php';
        }

        // All names/values are synthetic. Exercise checkmarks, boxed dates, long
        // text, Helvetica bold terms names, employment and financial overlays.
        $data = array(
            'buyer_first_name' => 'MAYA', 'buyer_middle_name' => 'TEST', 'buyer_last_name' => 'SAMPLE',
            'buyer_dob' => '1990-03-15', 'buyer_gender' => 'female', 'buyer_civil_status' => 'married',
            'buyer_citizenship' => 'Filipino', 'buyer_home_ownership' => 'rented',
            'buyer_occupation' => 'Locally Employed', 'buyer_mobile' => '09000000000',
            'buyer_email' => 'buyer@example.test', 'buyer_tin' => '000000000',
            'buyer_pagibig_mid' => '000000000000',
            'buyer_permanent_address_line' => 'BLOCK 1 LOT 2 SAMPLE STREET, SAMPLE SUBDIVISION',
            'buyer_present_address_line' => 'BLOCK 1 LOT 2 SAMPLE STREET, SAMPLE SUBDIVISION',
            'buyer_employer_name' => 'SAMPLE COMPANY', 'buyer_employed_date' => '2020-01-15',
            'buyer_position_department' => 'MANAGER / SALES',
            'spouse_first_name' => 'ALEX', 'spouse_last_name' => 'SAMPLE', 'spouse_dob' => '1991-04-02',
            'co_first_name' => 'JAMIE', 'co_last_name' => 'SAMPLE', 'co_dob' => '1992-05-10',
            'co_gender' => 'male', 'co_civil_status' => 'single', 'co_owner_role' => 'co_borrower',
            'co_home_ownership' => 'owned', 'co_occupation' => 'Locally Employed',
            'prop_project_name' => 'SAMPLE PROJECT', 'prop_house_finish' => 'complete',
            'prop_reservation_day' => '1', 'prop_reservation_month' => '10', 'prop_reservation_year' => '2026',
            'prop_total_contract_price' => '2500000', 'prop_loanable_amount' => '2000000',
        );
        if ($exportDirectory !== null && !is_dir($exportDirectory) && !mkdir($exportDirectory, 0775, true)) {
            throw new RuntimeException('Cannot create PDF comparison directory.');
        }
        foreach (array('cbdi', 'kaia') as $profile) {
            $GLOBALS['repaf_pdf_profile'] = $profile;
            $path = Repaf\repaf_generate_buyer_pdf(1, $data);
            if (is_wp_error($path) || !is_string($path) || !is_file($path)) {
                throw new RuntimeException('Packaged ' . $profile . ' form generation failed.');
            }
            $bytes = file_get_contents($path);
            if (strpos($bytes, '%PDF-') !== 0 || preg_match_all('~/Type\s*/Page\b~', $bytes) !== 6) {
                throw new RuntimeException('Expected all six pages in the ' . $profile . ' form.');
            }
            if (strpos($bytes, 'DejaVuSans') === false || strpos($bytes, 'Helvetica-Bold') === false) {
                throw new RuntimeException('Checkmark or bold text font missing from ' . $profile . ' form.');
            }
            if ($exportDirectory !== null && !copy($path, $exportDirectory . '/repaf-' . $profile . '.pdf')) {
                throw new RuntimeException('Cannot retain PDF for comparison.');
            }
            fwrite(STDOUT, 'Verified ' . strtoupper($profile) . ': six form pages, checkmarks and bold text generated from the package.' . PHP_EOL);
        }
    }
}
