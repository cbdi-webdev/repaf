<?php

namespace Repaf;

/**
 * =========================================================
 * REPAF PDF Coordinate Mapper
 * =========================================================
 *
 * Contains all printable coordinates for:
 * - Group 87 (Buyer Form)
 * - Group 92 (Co-Borrower / Financier Form)
 *
 * Coordinates can be maintained in two ways:
 *
 * 1. Profile JSON file (recommended for maintainability):
 *    assets/pdf/profiles/{cbdi|kaia}/coordinates.json
 *    Generate this locally with a development-only helper,
 *    then edit the JSON directly when the PDF template changes.
 *
 * 2. PHP fallback (current):
 *    The raw PHP arrays below are used when the JSON file
 *    does not exist. This keeps the plugin working
 *    out-of-the-box with no extra setup step.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * =========================================================
 * JSON-based coordinate loader
 * =========================================================
 *
 * Loads coordinates from the active site's profile directory when present,
 * then checks the legacy shared file. Returns null when neither file exists
 * or the selected file is invalid, causing callers to use the PHP arrays.
 *
 * The result is cached in a static variable so the file is
 * only read and decoded once per request.
 *
 * @return array|null  Full coordinate map, or null on failure.
 */
function repaf_load_coordinates_from_json()
{
    static $cache = [];
    $profile = repaf_get_site_profile();

    if (array_key_exists($profile, $cache)) {
        return $cache[$profile];
    }

    $profile_path = REPAF_PLUGIN_PATH . 'assets/pdf/profiles/'
        . $profile
        . '/coordinates.json';
    $legacy_path = REPAF_PLUGIN_PATH . 'assets/pdf/coordinates.json';
    $json_path = file_exists($profile_path) ? $profile_path : $legacy_path;

    if (!file_exists($json_path)) {
        $cache[$profile] = null;
        return null;
    }

    $raw = file_get_contents($json_path);

    if ($raw === false) {
        $cache[$profile] = null;
        return null;
    }

    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        error_log('[REPAF] coordinates.json is invalid: ' . json_last_error_msg());
        $cache[$profile] = null;
        return null;
    }

    $cache[$profile] = $data;
    return $data;
}

/**
 * =========================================================
 * Buyer PDF Coordinates
 * =========================================================
 */

function repaf_coord($x, $y, $width = 0)
{
    $coordinate = array(
        'x' => $x,
        'y' => $y,
    );

    if ($width > 0) {
        $coordinate['width'] = $width;
    }

    return $coordinate;
}
function repaf_get_buyer_pdf_coordinates()
{
    $json = repaf_load_coordinates_from_json();

    if ($json !== null && isset($json['buyer'])) {
        return $json['buyer'];
    }

    // PHP fallback — raw coordinate arrays below
    return array(

        /**
         * -------------------------------------------------
         * Buyer Personal Information
         * -------------------------------------------------
         */
        'account_code'       => repaf_coord(145, 62, 420),
        'buyer_last_name'    => repaf_coord(30, 118, 155),
        'buyer_first_name'   => repaf_coord(200, 118, 170),
        'buyer_middle_name'  => repaf_coord(390, 118, 120),
        'buyer_suffix'       => repaf_coord(530, 118, 48),


        // Birthdate
        'buyer_birth_month'  => repaf_coord(20, 160),
        'buyer_birth_day'    => repaf_coord(60, 160),
        'buyer_birth_year'   => repaf_coord(100, 160),


        // Age / Citizenship
        'buyer_age'          => repaf_coord(193, 159, 28),
        'buyer_citizenship'  => repaf_coord(450, 147, 128),


        // Contact Information
        'buyer_home_tel'     => repaf_coord(30, 194, 112),
        'buyer_mobile'       => repaf_coord(155, 194, 112),
        'buyer_email'        => repaf_coord(280, 194, 150),
        'buyer_pagibig_mid'  => repaf_coord(450, 194, 128),
        'buyer_tin'          => repaf_coord(450, 170, 128),


        // Gender Checkboxes
        'buyer_gender_male'   => repaf_coord(225, 153),
        'buyer_gender_female' => repaf_coord(226, 166),


        // Civil Status Checkboxes
        'buyer_civil_single'     => repaf_coord(274, 153),
        'buyer_civil_married'    => repaf_coord(322, 153),
        'buyer_civil_widow'      => repaf_coord(375, 153),
        'buyer_civil_annulled'   => repaf_coord(274, 166),
        'buyer_civil_separated'  => repaf_coord(322, 166),


        // Permanent Address
        'buyer_permanent_unit'              => repaf_coord(30, 222, 168),
        'buyer_permanent_block_lot'         => repaf_coord(210, 222, 88),
        'buyer_permanent_subdivision'        => repaf_coord(310, 222, 83),
        'buyer_permanent_street'            => repaf_coord(405, 222, 76),
        'buyer_permanent_barangay'          => repaf_coord(493, 222, 85),
        'buyer_permanent_city_municipality' => repaf_coord(30, 245, 138),
        'buyer_permanent_province_state'    => repaf_coord(180, 245, 148),
        'buyer_permanent_country'           => repaf_coord(340, 245, 148),
        'buyer_permanent_zip'               => repaf_coord(500, 245, 78),


        // Present Address
        'buyer_present_unit'              => repaf_coord(30, 277, 168),
        'buyer_present_block_lot'         => repaf_coord(210, 277, 88),
        'buyer_present_subdivision'       => repaf_coord(310, 277, 83),
        'buyer_present_street'            => repaf_coord(405, 277, 76),
        'buyer_present_barangay'          => repaf_coord(493, 277, 85),
        'buyer_present_city_municipality' => repaf_coord(30, 302, 138),
        'buyer_present_province_state'    => repaf_coord(180, 302, 148),
        'buyer_present_country'           => repaf_coord(340, 302, 148),
        'buyer_present_zip'               => repaf_coord(500, 302, 78),


        // Home Ownership Checkboxes
        'buyer_home_owned'                 => repaf_coord(22, 326),
        'buyer_home_company'               => repaf_coord(77, 326),
        'buyer_home_living_with_relatives' => repaf_coord(141, 326),
        'buyer_home_mortgage'              => repaf_coord(22, 342),
        'buyer_home_rented'                => repaf_coord(77, 342),
        'buyer_rent_amount'                => repaf_coord(135, 345, 90),


        // Occupation Checkboxes
        'buyer_occupation_private'       => repaf_coord(265, 327),
        'buyer_occupation_government'    => repaf_coord(265, 340),
        'buyer_occupation_self_employed' => repaf_coord(381, 327),
        'buyer_occupation_ofw'           => repaf_coord(502, 327),
        'buyer_occupation_others'        => repaf_coord(381, 340),
        'buyer_occupation_other'         => repaf_coord(443, 342, 75),


        // Buyer Employment Details
        'buyer_employer_name'                        => repaf_coord(30, 403, 155),
        'buyer_industry'                             => repaf_coord(205, 403, 155),
        'buyer_profession'                           => repaf_coord(380, 403, 108),
        'buyer_years_employed'                       => repaf_coord(505, 403, 73),
        'buyer_business_unit'                        => repaf_coord(30, 432, 168),
        'buyer_business_block_lot'                   => repaf_coord(210, 432, 88),
        'buyer_business_subdivision'                 => repaf_coord(310, 432, 83),
        'buyer_business_street'                      => repaf_coord(405, 432, 76),
        'buyer_business_barangay'                    => repaf_coord(493, 432, 85),
        'buyer_business_city_municipality'           => repaf_coord(30, 455, 138),
        'buyer_business_province_state'              => repaf_coord(180, 455, 148),
        'buyer_business_country'                     => repaf_coord(340, 455, 148),
        'buyer_business_zip'                         => repaf_coord(500, 455, 78),
        'buyer_office_number'                        => repaf_coord(30, 480, 135),
        'buyer_business_established_month'           => repaf_coord(171, 480),
        'buyer_business_established_day'             => repaf_coord(209, 480),
        'buyer_business_established_year'            => repaf_coord(247, 480),
        'buyer_position_department'                  => repaf_coord(330, 480, 158),
        'buyer_contract_status'                      => repaf_coord(500, 480, 78),
        'buyer_employment_email'                     => repaf_coord(30, 507, 135),
        'buyer_employment_mobile'                    => repaf_coord(180, 507, 135),
        'buyer_position_level'                       => repaf_coord(330, 507, 112),
        'buyer_business_type'                        => repaf_coord(468, 507, 110),


        // Spouse Personal Information
        'spouse_last_name'    => repaf_coord(30, 568, 155),
        'spouse_first_name'   => repaf_coord(200, 568, 170),
        'spouse_middle_name'  => repaf_coord(390, 568, 120),
        'spouse_suffix'       => repaf_coord(530, 568, 48),
        'spouse_birth_month'  => repaf_coord(160, 596),
        'spouse_birth_day'    => repaf_coord(200, 596),
        'spouse_birth_year'   => repaf_coord(237, 596),
        'spouse_age'          => repaf_coord(333, 596, 34),
        'spouse_citizenship'  => repaf_coord(30, 596, 118),
        'spouse_tin'          => repaf_coord(385, 596, 100),
        'spouse_dependents'   => repaf_coord(505, 596, 73),
        'spouse_home_tel'     => repaf_coord(30, 621, 120),
        'spouse_mobile'       => repaf_coord(165, 621, 145),
        'spouse_email'        => repaf_coord(328, 621, 250),


        // Spouse Checkboxes
        'spouse_occupation_private'   => repaf_coord(23, 648),
        'spouse_occupation_government' => repaf_coord(23, 662),
        'spouse_occupation_self_employed' => repaf_coord(145, 649),
        'spouse_occupation_ofw'       => repaf_coord(261, 649),
        'spouse_occupation_others'    => repaf_coord(145, 662),
        'spouse_occupation_other'     => repaf_coord(182, 664, 70),


        // Spouse Employment Details
        'spouse_employer_name'     => repaf_coord(328, 645, 250),
        'spouse_business_address'  => repaf_coord(328, 669, 250),


        // Representative Information
        'rep_full_name'          => repaf_coord(30, 700, 548),
        'rep_birth_month'        => repaf_coord(64, 774),
        'rep_birth_day'          => repaf_coord(99, 774),
        'rep_birth_year'         => repaf_coord(134, 774),
        'rep_age'                => repaf_coord(210, 780, 30),
        'rep_home_tel'           => repaf_coord(257, 780, 143),
        'rep_mobile'             => repaf_coord(418, 780, 160),
        'rep_email'              => repaf_coord(30, 806, 160),
        'rep_tin'                => repaf_coord(210, 806, 190),
        'rep_relationship'       => repaf_coord(418, 806, 160),


        // Representative Permanent Address
        'rep_unit'                  => repaf_coord(30, 728, 168),
        'rep_block_lot'             => repaf_coord(210, 728, 88),
        'rep_subdivision'           => repaf_coord(310, 728, 88),
        'rep_street'                => repaf_coord(410, 728, 71),
        'rep_barangay'              => repaf_coord(493, 728, 85),
        'rep_city_municipality'     => repaf_coord(30, 755, 138),
        'rep_province_state'        => repaf_coord(180, 755, 148),
        'rep_country'               => repaf_coord(340, 755, 148),
        'rep_zip'                   => repaf_coord(500, 755, 78),
    );
}

/**
 * =========================================================
 * Financial Information PDF Coordinates (Group 85.jpg)
 * =========================================================
 *
 * Coordinates are placeholders (0,0) until Group 85.jpg is
 * measured with the coordinate
 * tool at the plugin root. Use dot-notation keys for dynamic
 * row fields: e.g. 'fin_bank.1.name' resolves via
 * repaf_get_fin_pdf_value($data, 'fin_bank.1.name').
 */
function repaf_get_financial_pdf_coordinates()
{
    $json = repaf_load_coordinates_from_json();

    if ($json !== null && isset($json['financial'])) {
        return $json['financial'];
    }

    // PHP fallback — populate x/y after measuring Group_94.jpg
    $coords = array(
        // Monthly Income — Borrower column
        'fin_buyer_salary'       => repaf_coord(145, 78),
        'fin_buyer_allowance'    => repaf_coord(145, 100),
        'fin_buyer_commission'   => repaf_coord(145, 153),
        'fin_buyer_other_amount' => repaf_coord(145, 180),

        // Monthly Income — Spouse column
        'fin_spouse_salary'       => repaf_coord(257, 78),
        'fin_spouse_allowance'    => repaf_coord(257, 100),
        'fin_spouse_commission'   => repaf_coord(257, 153),
        'fin_spouse_other_amount' => repaf_coord(257, 180),

        // Monthly Income — Co-Borrower/Financer column
        'fin_co_salary'       => repaf_coord(370, 78),
        'fin_co_allowance'    => repaf_coord(370, 100),
        'fin_co_commission'   => repaf_coord(370, 153),
        'fin_co_other_amount' => repaf_coord(370, 180),

        // Monthly Income — Total column
        'fin_total_salary'     => repaf_coord(480, 78),
        'fin_total_allowance'  => repaf_coord(480, 100),
        'fin_total_commission' => repaf_coord(480, 153),
        'fin_total_other'      => repaf_coord(480, 180),

        // Bank Deposit / Credit Card Accounts (rows 1–5)
        'fin_bank.1.name'        => repaf_coord(20, 265),
        'fin_bank.1.type'        => repaf_coord(218, 265),
        'fin_bank.1.date_opened' => repaf_coord(375, 265),
        'fin_bank.1.balance'     => repaf_coord(472, 265),

        'fin_bank.2.name'        => repaf_coord(20, 290),
        'fin_bank.2.type'        => repaf_coord(218, 290),
        'fin_bank.2.date_opened' => repaf_coord(375, 290),
        'fin_bank.2.balance'     => repaf_coord(472, 290),

        'fin_bank.3.name'        => repaf_coord(20, 315),
        'fin_bank.3.type'        => repaf_coord(218, 315),
        'fin_bank.3.date_opened' => repaf_coord(375, 315),
        'fin_bank.3.balance'     => repaf_coord(472, 315),

        'fin_bank.4.name'        => repaf_coord(20, 337),
        'fin_bank.4.type'        => repaf_coord(218, 337),
        'fin_bank.4.date_opened' => repaf_coord(375, 337),
        'fin_bank.4.balance'     => repaf_coord(472, 337),

        'fin_bank.5.name'        => repaf_coord(20, 362),
        'fin_bank.5.type'        => repaf_coord(218, 362),
        'fin_bank.5.date_opened' => repaf_coord(375, 362),
        'fin_bank.5.balance'     => repaf_coord(472, 362),

        // Loans (rows 1–5)
        'fin_loan.1.type'         => repaf_coord(20, 448),
        'fin_loan.1.bank'         => repaf_coord(100, 448),
        'fin_loan.1.contact'      => repaf_coord(180, 448),
        'fin_loan.1.original'     => repaf_coord(260, 448),
        'fin_loan.1.outstanding'  => repaf_coord(342, 448),
        'fin_loan.1.amortization' => repaf_coord(423, 448),
        'fin_loan.1.collateral'   => repaf_coord(504, 448),

        'fin_loan.2.type'         => repaf_coord(20, 468),
        'fin_loan.2.bank'         => repaf_coord(100, 468),
        'fin_loan.2.contact'      => repaf_coord(180, 468),
        'fin_loan.2.original'     => repaf_coord(260, 468),
        'fin_loan.2.outstanding'  => repaf_coord(342, 468),
        'fin_loan.2.amortization' => repaf_coord(423, 468),
        'fin_loan.2.collateral'   => repaf_coord(504, 468),

        'fin_loan.3.type'         => repaf_coord(20, 488),
        'fin_loan.3.bank'         => repaf_coord(100, 488),
        'fin_loan.3.contact'      => repaf_coord(180, 488),
        'fin_loan.3.original'     => repaf_coord(260, 488),
        'fin_loan.3.outstanding'  => repaf_coord(342, 488),
        'fin_loan.3.amortization' => repaf_coord(423, 488),
        'fin_loan.3.collateral'   => repaf_coord(504, 488),

        'fin_loan.4.type'         => repaf_coord(20, 515),
        'fin_loan.4.bank'         => repaf_coord(100, 515),
        'fin_loan.4.contact'      => repaf_coord(180, 515),
        'fin_loan.4.original'     => repaf_coord(260, 515),
        'fin_loan.4.outstanding'  => repaf_coord(342, 515),
        'fin_loan.4.amortization' => repaf_coord(423, 515),
        'fin_loan.4.collateral'   => repaf_coord(504, 515),

        'fin_loan.5.type'         => repaf_coord(20, 538),
        'fin_loan.5.bank'         => repaf_coord(100, 538),
        'fin_loan.5.contact'      => repaf_coord(180, 538),
        'fin_loan.5.original'     => repaf_coord(260, 538),
        'fin_loan.5.outstanding'  => repaf_coord(342, 538),
        'fin_loan.5.amortization' => repaf_coord(423, 538),
        'fin_loan.5.collateral'   => repaf_coord(504, 538),

        // Character References (rows 1–3)
        'fin_ref.1.name'    => repaf_coord(20, 625),
        'fin_ref.1.address' => repaf_coord(210, 625),
        'fin_ref.1.email'   => repaf_coord(384, 625),
        'fin_ref.1.contact' => repaf_coord(496, 625),

        'fin_ref.2.name'    => repaf_coord(20, 648),
        'fin_ref.2.address' => repaf_coord(210, 648),
        'fin_ref.2.email'   => repaf_coord(384, 648),
        'fin_ref.2.contact' => repaf_coord(496, 648),

        'fin_ref.3.name'    => repaf_coord(20, 672),
        'fin_ref.3.address' => repaf_coord(210, 672),
        'fin_ref.3.email'   => repaf_coord(384, 672),
        'fin_ref.3.contact' => repaf_coord(496, 672),
    );

    return $coords;
}

/**
 * Resolve a dot-notation key against a nested data array.
 * e.g. 'fin_bank.1.name' → $data['fin_bank'][1]['name']
 *
 * @param  array  $data   Full sanitized submission data.
 * @param  string $key    Dot-notation field path.
 * @return string         Value as string, or empty string if not found.
 */
function repaf_get_fin_pdf_value($data, $key)
{
    if (strpos($key, '.') === false) {
        return (string) ($data[$key] ?? '');
    }

    $parts = explode('.', $key);
    $val   = $data;

    foreach ($parts as $part) {
        if (!is_array($val) || !isset($val[$part])) {
            return '';
        }
        $val = $val[$part];
    }

    return (string) $val;
}

/**
 * =========================================================
 * Terms and Conditions PDF Coordinates (Group 83.jpg)
 * =========================================================
 *
 * The three printed-name lines belong to the buyer, buyer's spouse or
 * Attorney-in-Fact, and co-owner/co-borrower or representative respectively.
 * Keeping names and signed dates here makes positioning adjustable without
 * changing the PDF drawing logic.
 */
function repaf_get_terms_pdf_coordinates()
{
    $json = repaf_load_coordinates_from_json();

    if ($json !== null && isset($json['terms'])) {
        return $json['terms'];
    }

    return array(
        'buyer_signature' => array(
            'x' => 38,
            'y' => 722,
            'width' => 120,
            'height' => 45,
        ),
        'buyer_printed_name' => array(
            'x' => 33,
            'y' => 759,
            'width' => 135,
        ),
        'spouse_printed_name' => array(
            'x' => 209,
            'y' => 759,
            'width' => 170,
        ),
        'co_borrower_printed_name' => array(
            'x' => 414,
            'y' => 759,
            'width' => 150,
        ),
        'buyer_date_signed' => array(
            'x' => 100,
            'y' => 790,
        ),
        'spouse_date_signed' => array(
            'x' => 286,
            'y' => 790,
        ),
        'co_borrower_date_signed' => array(
            'x' => 480,
            'y' => 790,
        ),
    );
}

/**
 * =========================================================
 * Salesperson Information PDF Coordinates
 * =========================================================
 *
 * Coordinates match repaf-salesperson-information.jpg.
 */
function repaf_get_salesperson_pdf_coordinates()
{
    $json = repaf_load_coordinates_from_json();

    if ($json !== null && isset($json['salesperson'])) {
        return $json['salesperson'];
    }

    return array(
        // Top row — signature / printed name lines
        'sp_agent_name'   => repaf_coord(76, 242, 155), // Real Estate Sales Person (Agent)
        'sp_manager_name' => repaf_coord(76, 282, 155), // Manager
        'sp_broker_name'  => repaf_coord(353, 242, 155), // Broker
        'sp_realty_name'  => repaf_coord(353, 282, 155), // Realty

        // Prepared & Interviewed By
        'sp_prepared_by'  => repaf_coord(76, 369, 155), // Sales Operations Assistant

        // Verified By
        'sp_verified_by'  => repaf_coord(354, 369, 155), // Sales Operations Head

        // Checked By
        'sp_checked_by'   => repaf_coord(71, 455, 155), // Reservation & Documentation Assistant

        // Approved By
        'sp_approved_by'  => repaf_coord(354, 455, 155), // Reservation & Documentation Head

        // Remarks (FSD)
        'sp_remarks'      => repaf_coord(50, 540, 495),
    );
}

/**
 * =========================================================
 * Property Information PDF Coordinates (Fourth Page (3).jpg)
 * =========================================================
 *
 * Coordinates target the active fourth-page artwork. Update
 * coordinates.json under key 'property' to override without editing PHP.
 */
function repaf_get_property_pdf_coordinates()
{
    $json = repaf_load_coordinates_from_json();

    if ($json !== null && isset($json['property'])) {
        return $json['property'];
    }

    return array(

        // ===== PROPERTY INFORMATION (top section) =====
        'prop_reservation_day'     => repaf_coord(142, 65),    // Reservation Date — DD box
        'prop_reservation_month'   => repaf_coord(95, 65),    // Reservation Date — MM box
        'prop_reservation_year'    => repaf_coord(185, 65),    // Reservation Date — YYYY box
        'prop_project'             => repaf_coord(273, 65, 116), // Project (text line)

        'prop_house_finish_basic'     => repaf_coord(397, 71), // House Finish — "Basic" checkbox
        'prop_house_finish_complete'  => repaf_coord(431, 71), // House Finish — "Complete" checkbox
        'prop_house_finish_other'     => repaf_coord(505, 70), // House Finish — "Others" text line

        'prop_place_on_site'        => repaf_coord(26, 111),   // Place of Reservation — "On-Site" checkbox
        'prop_place_branch_office'  => repaf_coord(26, 122),   // Place of Reservation — "Branch Office" checkbox
        'prop_place_head_office'    => repaf_coord(26, 134),   // Place of Reservation — "Head Office" checkbox
        'prop_place_others'         => repaf_coord(26, 146),   // Place of Reservation — "Others" checkbox
        'prop_place_other'          => repaf_coord(40, 157),   // Place of Reservation — "Others" text line

        'prop_product_house_and_lot' => repaf_coord(149, 112), // Product Type — "House & Lot" checkbox
        'prop_product_lot_only'      => repaf_coord(149, 124), // Product Type — "Lot Only" checkbox
        'prop_product_others'        => repaf_coord(149, 136), // Product Type — "Others" checkbox
        'prop_product_other'         => repaf_coord(162, 148), // Product Type — "Others" text line

        'prop_phase'               => repaf_coord(270, 107),   // Phase (text line)
        'prop_block'               => repaf_coord(335, 107),   // Block (text line)
        'prop_lot'                 => repaf_coord(400, 107),   // Lot (text line)
        'prop_lot_area'            => repaf_coord(460, 107),   // Lot Area (sqm) (text line)
        'prop_floor_area'          => repaf_coord(520, 107),   // Floor Area (sqm) (text line)
        'prop_house_model'         => repaf_coord(0, 0),       // House Model — admin-only field, not on PDF

        // Payment Type (top-right group)
        'prop_payment_type_spot_cash'      => repaf_coord(283, 145), // Payment Type — "Spot Cash" checkbox
        'prop_payment_type_deferred_cash'  => repaf_coord(283, 157), // Payment Type — "Deferred Cash" checkbox
        'prop_payment_type_with_financing' => repaf_coord(283, 169), // Payment Type — "With Financing" checkbox

        // Financing (top-right group). Offset the TCPDF glyph anchor up and
        // left so the rendered check sits inside each printed circle.
        'prop_financing_bank'      => repaf_coord(406, 134),   // Financing — "Bank" checkbox
        'prop_financing_in_house'  => repaf_coord(406, 146),   // Financing — "In-House" checkbox
        'prop_financing_hdmf'      => repaf_coord(406, 158),   // Financing — "HDMF" checkbox
        'prop_financing_others'    => repaf_coord(406, 170),   // Financing — "Others" checkbox

        // TCPDF's check glyph renders down/right from its anchor, so these
        // positions are offset to center it inside the printed circles.
        'prop_house_status_rfo'     => repaf_coord(497, 143),
        'prop_house_status_nrfo'    => repaf_coord(497, 155),
        'prop_house_status_ongoing' => repaf_coord(497, 167),

        'prop_price_per_sqm'       => repaf_coord(0, 0),       // Price per sqm — admin-only field, not on PDF
        'prop_starts_on'           => repaf_coord(0, 0),       // Starts On — admin-only field, not on PDF
        'prop_ends_on'             => repaf_coord(0, 0),       // Ends On — admin-only field, not on PDF

        // ===== MODE OF PAYMENT (left column) =====
        'prop_mode_cash'                    => repaf_coord(23, 247), // Mode of Payment — "Cash" checkbox
        'prop_mode_post_dated_check'        => repaf_coord(23, 259), // Mode of Payment — "Post Dated Check" checkbox
        'prop_mode_bills_payment_or_online' => repaf_coord(23, 271), // Mode of Payment — "Bills Payment or Online" checkbox
        'prop_mode_other_check'             => repaf_coord(23, 283), // Mode of Payment — "Other" checkbox
        'prop_mode_other'                   => repaf_coord(34, 293), // Mode of Payment — "Other" text line

        // ===== BANK CHARGES (left column) — same value also appears in the Computation loan rows below =====
        'prop_loan_amount'         => repaf_coord(34, 350),       // Bank Charges "Total Amount Due" + Computation "___% Balance / Loanable Amount"
        'prop_loan_term'           => repaf_coord(60, 368),       // Bank Charges "Terms ___ yrs" + Computation "Loan Terms"
        'prop_loan_monthly'        => repaf_coord(34, 402),       // Bank Charges "Monthly Due" + Computation "Monthly Amortization Amount"
        // "Starts on" / "Ends on" — split into separate month/day/year boxes, same pattern as Reservation Date
        'prop_loan_starts_on_month' => repaf_coord(24, 436),       // Bank Charges "Starts on" — MM box
        'prop_loan_starts_on_day'   => repaf_coord(54, 436),       // Bank Charges "Starts on" — DD box
        'prop_loan_starts_on_year'  => repaf_coord(83, 436),       // Bank Charges "Starts on" — YYYY box
        'prop_loan_ends_on_month'   => repaf_coord(24, 473),       // Bank Charges "Ends on" — MM box
        'prop_loan_ends_on_day'     => repaf_coord(54, 473),       // Bank Charges "Ends on" — DD box
        'prop_loan_ends_on_year'    => repaf_coord(83, 473),       // Bank Charges "Ends on" — YYYY box

        // ===== INSURANCE / S (left column) =====
        'prop_mri'                 => repaf_coord(34, 555),       // Insurance/s — "Mortgage Redemption Insurance (MRI)"
        'prop_fi'                  => repaf_coord(34, 630),       // Insurance/s — "Fire Insurance (FI)"
        'prop_insurance_amount'    => repaf_coord(34, 585),       // Insurance/s — "Computed Amount for 1 year only"

        // ===== COMPUTATION (center column, top to bottom) =====
        'prop_total_list_price'    => repaf_coord(330, 247),   // Computation — "Total List Price" (highlighted row)

        // Less Discount on List Price
        'prop_discount_full_tcp'      => repaf_coord(147, 290), // Discount — "Full TCP" radio
        'prop_discount_tcp_percent'   => repaf_coord(193, 287), // Discount — Full TCP "___% discount"
        'prop_discount_tcp_days'      => repaf_coord(237, 300), // Discount — Full TCP "paid within ___ days"
        'prop_discount_full_dp'       => repaf_coord(147, 327), // Discount — "Full DP" radio
        'prop_discount_dp_percent'    => repaf_coord(193, 326), // Discount — Full DP "___% discount"
        'prop_discount_dp_days'       => repaf_coord(237, 338), // Discount — Full DP "paid within ___ days"
        'prop_discount_others_check'  => repaf_coord(147, 360), // Discount — "Others" radio
        'prop_discount_others'        => repaf_coord(198, 357), // Discount — "Others" text line
        'prop_discount_tcp_amount'    => repaf_coord(330, 290), // Full TCP discount amount — first right-side cell
        'prop_discount_dp_amount'     => repaf_coord(330, 327), // Full DP discount amount — second right-side cell
        'prop_discount_others_amount' => repaf_coord(330, 360), // Other discount amount — third right-side cell

        'prop_total_selling_price' => repaf_coord(330, 384),   // Computation — "Total Selling Price" (highlighted row, after discount)
        'prop_add_amount'          => repaf_coord(330, 402),       // Computation — "Add :" row (its own fillable value, separate from Miscellaneous Fee below it)
        'prop_misc_fee'            => repaf_coord(330, 420),       // Computation — "Miscellaneous Fee" row
        'prop_vat'                 => repaf_coord(330, 440),       // Computation — "Value Added Tax (VAT)"
        'prop_total_contract_price'   => repaf_coord(330, 460), // Computation — "Total Contract Price" (highlighted row)

        'prop_dp_percent'          => repaf_coord(150, 475),       // Computation — "___% Required Downpayment (DP)"
        'prop_dp_amount'           => repaf_coord(330, 478),       // Computation — Down Payment Amount, right side of "___% Required Downpayment (DP)" row
        'prop_reservation_fee'     => repaf_coord(330, 499),       // Computation — "Reservation Fee" row
        'prop_balance_on_dp'       => repaf_coord(330, 520),       // Computation — "Balance on DP" (auto-calculated: TCP - Down Payment Amount)
        'prop_dp_months'           => repaf_coord(207, 534),       // Computation — "Installment ___ No. of month(s) to pay"
        'prop_dp_monthly'          => repaf_coord(330, 555),       // Computation — "Monthly DP Amount"
        'prop_dp_starts_on'        => repaf_coord(330, 575),       // Computation — DP "Starts on"
        'prop_dp_ends_on'          => repaf_coord(330, 595),       // Computation — DP "Ends on" 

        // ===== Loan / Financing rows (printed below "DP Ends On") =====
        'prop_loan_percent'              => repaf_coord(144, 608), // Computation — "___% Balance / Loanable Amount" (auto-calculated: 100% - Required DP %)
        'prop_loanable_amount'           => repaf_coord(330, 612), // Computation — Loanable Amount value, right side of "___% Balance / Loanable Amount" row (auto-calculated: TCP - Down Payment Amount)
        'prop_loan_monthly_computation'  => repaf_coord(330, 632), // Computation — "Monthly Amortization Amount" (duplicate of Bank Charges "Monthly Due")
        'prop_loan_term_computation'     => repaf_coord(330, 652), // Computation — "Loan Terms" (duplicate of Bank Charges "Terms ___ yrs")
        'prop_loan_interest'             => repaf_coord(330, 670), // Computation — "Interest rate (indicative)"
        'prop_loan_starts_on_computation' => repaf_coord(330, 689), // Computation — "Starts on" (duplicate of Bank Charges loan "Starts on")
    );
}

/**
 * =========================================================
 * Co-Borrower PDF Coordinates
 * =========================================================
 */
function repaf_get_coowner_pdf_coordinates()
{
    $json = repaf_load_coordinates_from_json();

    if ($json !== null && isset($json['coowner'])) {
        return $json['coowner'];
    }

    // PHP fallback — raw coordinate arrays below
    return array(
        'co_role_co_owner'              => repaf_coord(140, 58),
        'co_role_co_borrower'           => repaf_coord(256, 58),
        'co_role_financier'             => repaf_coord(386, 58),

        'co_last_name'                  => repaf_coord(30, 118, 155),
        'co_first_name'                 => repaf_coord(200, 118, 170),
        'co_middle_name'                => repaf_coord(390, 118, 120),
        'co_suffix'                     => repaf_coord(530, 118, 48),
        'co_birth_month'                => repaf_coord(20, 160),
        'co_birth_day'                  => repaf_coord(60, 160),
        'co_birth_year'                 => repaf_coord(100, 160),
        'co_age'                        => repaf_coord(193, 159, 28),
        'co_gender_male'                => repaf_coord(225, 153),
        'co_gender_female'              => repaf_coord(226, 166),
        'co_civil_single'               => repaf_coord(274, 153),
        'co_civil_married'              => repaf_coord(322, 153),
        'co_civil_widow'                => repaf_coord(375, 153),
        'co_civil_annulled'             => repaf_coord(274, 166),
        'co_civil_separated'            => repaf_coord(322, 166),
        'co_citizenship'                => repaf_coord(450, 147, 128),
        'co_tin'                        => repaf_coord(450, 170, 128),
        'co_home_tel'                   => repaf_coord(30, 194, 112),
        'co_mobile'                     => repaf_coord(155, 194, 112),
        'co_email'                      => repaf_coord(280, 194, 150),
        'co_pagibig_mid'                => repaf_coord(450, 194, 128),

        'co_permanent_unit'              => repaf_coord(30, 222, 168),
        'co_permanent_block_lot'         => repaf_coord(210, 222, 88),
        'co_permanent_subdivision'       => repaf_coord(310, 222, 83),
        'co_permanent_street'            => repaf_coord(405, 222, 76),
        'co_permanent_barangay'          => repaf_coord(493, 222, 85),
        'co_permanent_city_municipality' => repaf_coord(30, 245, 138),
        'co_permanent_province_state'    => repaf_coord(180, 245, 148),
        'co_permanent_country'           => repaf_coord(340, 245, 148),
        'co_permanent_zip'               => repaf_coord(500, 245, 78),

        'co_present_unit'              => repaf_coord(30, 277, 168),
        'co_present_block_lot'         => repaf_coord(210, 277, 88),
        'co_present_subdivision'       => repaf_coord(310, 277, 83),
        'co_present_street'            => repaf_coord(405, 277, 76),
        'co_present_barangay'          => repaf_coord(493, 277, 85),
        'co_present_city_municipality' => repaf_coord(30, 302, 138),
        'co_present_province_state'    => repaf_coord(180, 302, 148),
        'co_present_country'           => repaf_coord(340, 302, 148),
        'co_present_zip'               => repaf_coord(500, 302, 78),

        'co_home_owned'                 => repaf_coord(22, 326),
        'co_home_company'               => repaf_coord(77, 326),
        'co_home_living_with_relatives' => repaf_coord(141, 326),
        'co_home_mortgage'              => repaf_coord(22, 342),
        'co_home_rented'                => repaf_coord(77, 342),
        'co_rent_amount'                => repaf_coord(135, 345, 90),
        'co_occupation_private'         => repaf_coord(265, 327),
        'co_occupation_government'      => repaf_coord(265, 340),
        'co_occupation_self_employed'   => repaf_coord(381, 327),
        'co_occupation_ofw'             => repaf_coord(502, 327),
        'co_occupation_others'          => repaf_coord(381, 340),
        'co_occupation_other'           => repaf_coord(443, 342, 75),


        'co_spouse_last_name'          => repaf_coord(30, 568, 155),
        'co_spouse_first_name'         => repaf_coord(200, 568, 170),
        'co_spouse_middle_name'        => repaf_coord(390, 568, 120),
        'co_spouse_suffix'             => repaf_coord(530, 568, 48),
        'co_spouse_citizenship'        => repaf_coord(30, 596, 118),
        'co_spouse_birth_month'        => repaf_coord(160, 596),
        'co_spouse_birth_day'          => repaf_coord(200, 596),
        'co_spouse_birth_year'         => repaf_coord(237, 596),
        'co_spouse_age'                => repaf_coord(333, 596, 34),
        'co_spouse_tin'                => repaf_coord(385, 596, 100),
        'co_spouse_dependents'         => repaf_coord(505, 596, 73),
        'co_spouse_home_office_number' => repaf_coord(30, 621, 120),
        'co_spouse_mobile'             => repaf_coord(165, 621, 145),
        'co_spouse_email'              => repaf_coord(328, 621, 250),


        'co_spouse_occupation_private' => repaf_coord(23, 648),
        'co_spouse_occupation_government' => repaf_coord(23, 662),
        'co_spouse_occupation_self_employed' => repaf_coord(145, 649),
        'co_spouse_occupation_ofw'     => repaf_coord(261, 649),
        'co_spouse_occupation_others'  => repaf_coord(145, 662),
        'co_spouse_occupation_other'   => repaf_coord(182, 664, 70),
        'co_spouse_employer_name'      => repaf_coord(328, 645, 250),
        'co_spouse_employer_address'   => repaf_coord(328, 669, 250),


        'co_employer_name'              => repaf_coord(30, 403, 155),
        'co_industry'                   => repaf_coord(205, 403, 155),
        'co_profession'                 => repaf_coord(380, 403, 108),
        'co_years_employed'             => repaf_coord(505, 403, 73),
        'co_business_unit'              => repaf_coord(30, 432, 168),
        'co_business_block_lot'         => repaf_coord(210, 432, 88),
        'co_business_subdivision'       => repaf_coord(310, 432, 83),
        'co_business_street'            => repaf_coord(405, 432, 76),
        'co_business_barangay'          => repaf_coord(493, 432, 85),
        'co_business_city_municipality' => repaf_coord(30, 455, 138),
        'co_business_province_state'    => repaf_coord(180, 455, 148),
        'co_business_country'           => repaf_coord(340, 455, 148),
        'co_business_zip'               => repaf_coord(500, 455, 78),
        'co_office_number'              => repaf_coord(30, 480, 135),
        'co_business_established_month' => repaf_coord(171, 480),
        'co_business_established_day'   => repaf_coord(209, 480),
        'co_business_established_year'  => repaf_coord(247, 480),
        'co_position_department'        => repaf_coord(330, 480, 158),
        'co_contract_status'            => repaf_coord(500, 480, 78),
        'co_employment_email'           => repaf_coord(30, 507, 135),
        'co_employment_mobile'          => repaf_coord(180, 507, 135),
        'co_position_level'             => repaf_coord(330, 507, 112),
        'co_business_type'              => repaf_coord(468, 507, 110),

        'co_rep_full_name'              => repaf_coord(30, 700, 548),
        'co_rep_unit'                   => repaf_coord(30, 728, 168),
        'co_rep_block_lot'              => repaf_coord(210, 728, 88),
        'co_rep_subdivision'            => repaf_coord(310, 728, 88),
        'co_rep_street'                 => repaf_coord(410, 728, 71),
        'co_rep_barangay'               => repaf_coord(493, 728, 85),
        'co_rep_city_municipality'      => repaf_coord(30, 755, 138),
        'co_rep_province_state'         => repaf_coord(180, 755, 148),
        'co_rep_country'                => repaf_coord(340, 755, 148),
        'co_rep_zip'                    => repaf_coord(500, 755, 78),
        'co_rep_birth_month'            => repaf_coord(64, 774),
        'co_rep_birth_day'              => repaf_coord(99, 774),
        'co_rep_birth_year'             => repaf_coord(134, 774),
        'co_rep_age'                    => repaf_coord(210, 780, 30),
        'co_rep_home_tel'               => repaf_coord(257, 780, 143),
        'co_rep_mobile'                 => repaf_coord(418, 780, 160),
        'co_rep_email'                  => repaf_coord(30, 806, 160),
        'co_rep_tin'                    => repaf_coord(210, 806, 190),
        'co_rep_relationship'           => repaf_coord(418, 806, 160),
    );
}
