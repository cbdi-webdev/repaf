<?php

namespace Repaf;

/**
 * Coordinates for the August 2026 three-page REPAF layout.
 *
 * The supplied source pages are 2552 x 3900 PNG files and are rendered to
 * A4 (595 x 842 points). Keeping these maps separate from the legacy maps
 * leaves the unchanged property, salesperson and terms pages untouched.
 */

if (!defined('ABSPATH')) {
    exit;
}

function repaf_get_updated_page1_coordinates()
{
    return array(
        'account_code' => repaf_coord(108, 65, 265),

        'buyer_last_name' => repaf_coord(28, 111, 155),
        'buyer_first_name' => repaf_coord(196, 111, 170),
        'buyer_middle_name' => repaf_coord(389, 111, 120),
        'buyer_suffix' => repaf_coord(530, 111, 45),
        'buyer_birth_month' => repaf_coord(20, 163),
        'buyer_birth_day' => repaf_coord(61, 163),
        'buyer_birth_year' => repaf_coord(101, 163),
        'buyer_age' => repaf_coord(182, 162, 28),
        'buyer_citizenship' => repaf_coord(423, 147, 150),
        'buyer_tin' => repaf_coord(423, 170, 150),
        'buyer_home_tel' => repaf_coord(22, 193, 125),
        'buyer_mobile' => repaf_coord(160, 193, 96),
        'buyer_email' => repaf_coord(269, 193, 145),
        'buyer_pagibig_mid' => repaf_coord(423, 193, 150),
        'buyer_gender_male' => repaf_coord(222, 153),
        'buyer_gender_female' => repaf_coord(222, 169),
        'buyer_civil_single' => repaf_coord(270, 153),
        'buyer_civil_married' => repaf_coord(319, 153),
        'buyer_civil_widow' => repaf_coord(372, 153),
        'buyer_civil_annulled' => repaf_coord(270, 169),
        'buyer_civil_separated' => repaf_coord(319, 169),

        'buyer_permanent_address_line' => repaf_coord(18, 219, 463),
        'buyer_permanent_barangay' => repaf_coord(488, 219, 86),
        'buyer_permanent_city_municipality' => repaf_coord(21, 245, 138),
        'buyer_permanent_province_state' => repaf_coord(169, 245, 138),
        'buyer_permanent_country' => repaf_coord(318, 245, 156),
        'buyer_permanent_zip' => repaf_coord(488, 245, 86),

        'buyer_present_address_line' => repaf_coord(18, 276, 267),
        'buyer_present_barangay' => repaf_coord(291, 276, 50),
        'buyer_present_city_municipality' => repaf_coord(18, 299, 82),
        'buyer_present_province_state' => repaf_coord(107, 299, 80),
        'buyer_present_country' => repaf_coord(196, 299, 88),
        'buyer_present_zip' => repaf_coord(299, 299, 45),
        'buyer_home_owned' => repaf_coord(353, 274),
        'buyer_home_company' => repaf_coord(408, 274),
        'buyer_home_living_with_relatives' => repaf_coord(472, 274),
        'buyer_home_mortgage' => repaf_coord(353, 292),
        'buyer_home_rented' => repaf_coord(408, 292),
        'buyer_rent_amount' => repaf_coord(472, 293, 74),

        'spouse_last_name' => repaf_coord(28, 345, 155),
        'spouse_first_name' => repaf_coord(201, 345, 170),
        'spouse_middle_name' => repaf_coord(395, 345, 120),
        'spouse_suffix' => repaf_coord(531, 345, 43),
        'spouse_citizenship' => repaf_coord(18, 375, 126),
        'spouse_birth_month' => repaf_coord(160, 374),
        'spouse_birth_day' => repaf_coord(200, 374),
        'spouse_birth_year' => repaf_coord(239, 374),
        'spouse_age' => repaf_coord(321, 375, 31),
        'spouse_tin' => repaf_coord(369, 375, 117),
        'spouse_dependents' => repaf_coord(504, 375, 69),
        'spouse_home_tel' => repaf_coord(18, 399, 126),
        'spouse_mobile' => repaf_coord(160, 399, 145),
        'spouse_email' => repaf_coord(320, 399, 253),

        'rep_last_name' => repaf_coord(28, 447, 155),
        'rep_first_name' => repaf_coord(201, 447, 170),
        'rep_middle_name' => repaf_coord(395, 447, 120),
        'rep_suffix' => repaf_coord(531, 447, 43),
        'rep_address_line' => repaf_coord(18, 474, 473),
        'rep_barangay' => repaf_coord(498, 474, 75),
        'rep_city_municipality' => repaf_coord(18, 500, 142),
        'rep_province_state' => repaf_coord(168, 500, 142),
        'rep_country' => repaf_coord(318, 500, 168),
        'rep_zip' => repaf_coord(499, 500, 74),
        'rep_birth_month' => repaf_coord(62, 522),
        'rep_birth_day' => repaf_coord(98, 522),
        'rep_birth_year' => repaf_coord(134, 522),
        'rep_age' => repaf_coord(202, 526, 35),
        'rep_home_tel' => repaf_coord(252, 526, 145),
        'rep_mobile' => repaf_coord(412, 526, 160),
        'rep_email' => repaf_coord(18, 550, 170),
        'rep_tin' => repaf_coord(201, 550, 195),
        'rep_relationship' => repaf_coord(412, 550, 160),

        'co_role_co_owner' => repaf_coord(138, 607),
        'co_role_co_borrower' => repaf_coord(254, 607),
        'co_role_financier' => repaf_coord(384, 607),
        'co_last_name' => repaf_coord(28, 663, 155),
        'co_first_name' => repaf_coord(199, 663, 170),
        'co_middle_name' => repaf_coord(391, 663, 120),
        'co_suffix' => repaf_coord(530, 663, 44),
        'co_birth_month' => repaf_coord(21, 716),
        'co_birth_day' => repaf_coord(62, 716),
        'co_birth_year' => repaf_coord(103, 716),
        'co_age' => repaf_coord(184, 715, 29),
        'co_gender_male' => repaf_coord(225, 705),
        'co_gender_female' => repaf_coord(225, 718),
        'co_civil_single' => repaf_coord(273, 705),
        'co_civil_married' => repaf_coord(322, 705),
        'co_civil_widow' => repaf_coord(375, 705),
        'co_civil_annulled' => repaf_coord(273, 718),
        'co_civil_separated' => repaf_coord(322, 718),
        'co_citizenship' => repaf_coord(424, 696, 150),
        'co_tin' => repaf_coord(424, 720, 150),
        'co_home_tel' => repaf_coord(22, 744, 128),
        'co_mobile' => repaf_coord(160, 744, 98),
        'co_email' => repaf_coord(270, 748, 145),
        'co_pagibig_mid' => repaf_coord(424, 744, 150),
        'co_permanent_address_line' => repaf_coord(18, 775, 464),
        'co_permanent_barangay' => repaf_coord(490, 775, 84),
        'co_permanent_city_municipality' => repaf_coord(22, 795, 138),
        'co_permanent_province_state' => repaf_coord(170, 795, 138),
        'co_permanent_country' => repaf_coord(319, 795, 157),
        'co_permanent_zip' => repaf_coord(490, 795, 84),
    );
}

function repaf_get_updated_page2_coordinates()
{
    $coords = array(
        // The current PNG moves the page-2 content 50px upward from the
        // preceding artwork, equivalent to about 11pt at A4 output size.
        'co_present_address_line' => repaf_coord(18, 51, 267),
        'co_present_barangay' => repaf_coord(291, 51, 50),
        'co_present_city_municipality' => repaf_coord(18, 78, 82),
        'co_present_province_state' => repaf_coord(107, 78, 80),
        'co_present_country' => repaf_coord(196, 78, 88),
        'co_present_zip' => repaf_coord(299, 78, 45),
        'co_home_owned' => repaf_coord(353, 55),
        'co_home_company' => repaf_coord(408, 55),
        'co_home_living_with_relatives' => repaf_coord(472, 55),
        'co_home_mortgage' => repaf_coord(353, 71),
        'co_home_rented' => repaf_coord(408, 71),
        'co_rent_amount' => repaf_coord(472, 72, 74),

        'co_spouse_last_name' => repaf_coord(28, 121, 155),
        'co_spouse_first_name' => repaf_coord(201, 121, 170),
        'co_spouse_middle_name' => repaf_coord(395, 121, 120),
        'co_spouse_suffix' => repaf_coord(531, 121, 43),
        'co_spouse_citizenship' => repaf_coord(18, 151, 126),
        'co_spouse_birth_month' => repaf_coord(160, 153),
        'co_spouse_birth_day' => repaf_coord(200, 153),
        'co_spouse_birth_year' => repaf_coord(239, 153),
        'co_spouse_age' => repaf_coord(321, 151, 31),
        'co_spouse_tin' => repaf_coord(369, 151, 117),
        'co_spouse_dependents' => repaf_coord(504, 151, 69),
        'co_spouse_home_office_number' => repaf_coord(18, 178, 126),
        'co_spouse_mobile' => repaf_coord(160, 178, 145),
        'co_spouse_email' => repaf_coord(320, 178, 253),

        'co_rep_last_name' => repaf_coord(28, 223, 155),
        'co_rep_first_name' => repaf_coord(201, 223, 170),
        'co_rep_middle_name' => repaf_coord(395, 223, 120),
        'co_rep_suffix' => repaf_coord(531, 223, 43),
        'co_rep_address_line' => repaf_coord(18, 250, 473),
        'co_rep_barangay' => repaf_coord(498, 250, 75),
        'co_rep_city_municipality' => repaf_coord(18, 276, 142),
        'co_rep_province_state' => repaf_coord(215, 276, 95),
        'co_rep_country' => repaf_coord(318, 276, 168),
        'co_rep_zip' => repaf_coord(499, 276, 74),
        'co_rep_birth_month' => repaf_coord(62, 303),
        'co_rep_birth_day' => repaf_coord(98, 303),
        'co_rep_birth_year' => repaf_coord(134, 303),
        'co_rep_age' => repaf_coord(202, 306, 35),
        'co_rep_home_tel' => repaf_coord(252, 306, 145),
        'co_rep_mobile' => repaf_coord(412, 306, 160),
        'co_rep_email' => repaf_coord(18, 330, 170),
        'co_rep_tin' => repaf_coord(201, 330, 195),
        'co_rep_relationship' => repaf_coord(412, 330, 160),
    );

    $column_x = array('buyer' => 163, 'spouse' => 309, 'co' => 449);
    foreach ($column_x as $prefix => $x) {
        $coords[$prefix . '_occupation_private'] = repaf_coord($x, 377);
        $coords[$prefix . '_occupation_government'] = repaf_coord($x, 389);
        $coords[$prefix . '_occupation_self_employed'] = repaf_coord($x, 401);
        $coords[$prefix . '_occupation_ofw'] = repaf_coord($x, 413);
        $coords[$prefix . '_occupation_others'] = repaf_coord($x, 425);
        $coords[$prefix . '_occupation_other'] = repaf_coord($x + 39, 439, 67);

        $coords['work_' . $prefix . '_employer_name'] = repaf_coord($x, 455, 128);
        $coords['work_' . $prefix . '_unit_block'] = repaf_coord($x, 468, 128);
        $coords['work_' . $prefix . '_street_subdivision'] = repaf_coord($x, 488, 128);
        $coords['work_' . $prefix . '_barangay'] = repaf_coord($x, 512, 128);
        $coords['work_' . $prefix . '_city'] = repaf_coord($x, 532, 128);
        $coords['work_' . $prefix . '_province'] = repaf_coord($x, 552, 128);
        $coords['work_' . $prefix . '_country'] = repaf_coord($x, 572, 128);
        $coords['work_' . $prefix . '_zip'] = repaf_coord($x, 592, 128);
        $coords['work_' . $prefix . '_industry'] = repaf_coord($x, 611, 128);
        $coords['work_' . $prefix . '_office_number'] = repaf_coord($x, 631, 128);
        $coords['work_' . $prefix . '_office_email'] = repaf_coord($x, 648, 128);
        $coords['work_' . $prefix . '_office_mobile'] = repaf_coord($x, 666, 128);
        $coords['work_' . $prefix . '_years'] = repaf_coord($x, 704, 128);
    }

    $date_x = array(
        'buyer' => array(165, 199, 233),
        'spouse' => array(306, 340, 374),
        'co' => array(448, 482, 516),
    );
    foreach ($date_x as $prefix => $positions) {
        $coords['work_' . $prefix . '_date_month'] = repaf_coord($positions[0], 684);
        $coords['work_' . $prefix . '_date_day'] = repaf_coord($positions[1], 684);
        $coords['work_' . $prefix . '_date_year'] = repaf_coord($positions[2], 684);
    }

    $business_type_x = array('buyer' => 163, 'spouse' => 305, 'co' => 447);
    foreach ($business_type_x as $prefix => $x) {
        $coords['work_' . $prefix . '_business_type_single'] = repaf_coord($x, 722);
        $coords['work_' . $prefix . '_business_type_partnership'] = repaf_coord($x, 734);
        $coords['work_' . $prefix . '_business_type_corporation'] = repaf_coord($x, 746);
    }

    return $coords;
}

function repaf_get_updated_page3_work_coordinates()
{
    $coords = array();
    $column_x = array('buyer' => 163, 'spouse' => 303, 'co' => 444);
    $position_check_x = array('buyer' => 171, 'spouse' => 312, 'co' => 453);
    foreach ($column_x as $prefix => $x) {
        $coords['work_' . $prefix . '_position_department'] = repaf_coord($x, 54, 128);
        $coords['work_' . $prefix . '_contract_regular'] = repaf_coord($x + 5, 73);
        $coords['work_' . $prefix . '_contract_contractual'] = repaf_coord($x + 74, 73);

        $check_x = $position_check_x[$prefix];
        $coords['work_' . $prefix . '_position_rank_file'] = repaf_coord($check_x, 99);
        $coords['work_' . $prefix . '_position_supervisor'] = repaf_coord($check_x, 110);
        $coords['work_' . $prefix . '_position_manager'] = repaf_coord($check_x, 121);
        $coords['work_' . $prefix . '_position_executive'] = repaf_coord($check_x, 132);
        $coords['work_' . $prefix . '_position_professional'] = repaf_coord($check_x, 143);
    }
    return $coords;
}

function repaf_get_updated_page3_financial_coordinates()
{
    $coords = array();
    // The current PNG moves the financial section 61px upward from the
    // preceding artwork, equivalent to about 13pt at A4 output size.
    $income_y = array('salary' => 229, 'allowance' => 254, 'commission' => 279, 'other_amount' => 304, 'total' => 329);
    $income_x = array('buyer' => 171, 'spouse' => 312, 'co' => 452);
    foreach ($income_x as $prefix => $x) {
        foreach ($income_y as $type => $y) {
            $coords['fin_' . $prefix . '_' . $type] = repaf_coord($x, $y, 128);
        }
    }

    $bank_y = array(399, 423, 446, 470, 493);
    foreach ($bank_y as $index => $y) {
        $row = $index + 1;
        $coords['fin_bank.' . $row . '.name'] = repaf_coord(20, $y, 190);
        $coords['fin_bank.' . $row . '.type'] = repaf_coord(217, $y, 145);
        $coords['fin_bank.' . $row . '.date_opened'] = repaf_coord(371, $y, 94);
        $coords['fin_bank.' . $row . '.balance'] = repaf_coord(475, $y, 100);
    }

    $loan_y = array(562, 585, 609, 632, 655);
    foreach ($loan_y as $index => $y) {
        $row = $index + 1;
        $coords['fin_loan.' . $row . '.type'] = repaf_coord(19, $y, 74);
        $coords['fin_loan.' . $row . '.bank'] = repaf_coord(99, $y, 75);
        $coords['fin_loan.' . $row . '.contact'] = repaf_coord(181, $y, 72);
        $coords['fin_loan.' . $row . '.original'] = repaf_coord(260, $y, 74);
        $coords['fin_loan.' . $row . '.outstanding'] = repaf_coord(341, $y, 74);
        $coords['fin_loan.' . $row . '.amortization'] = repaf_coord(422, $y, 74);
        $coords['fin_loan.' . $row . '.collateral'] = repaf_coord(503, $y, 72);
    }

    $ref_y = array(724, 748, 772);
    foreach ($ref_y as $index => $y) {
        $row = $index + 1;
        $coords['fin_ref.' . $row . '.name'] = repaf_coord(19, $y, 180);
        $coords['fin_ref.' . $row . '.address'] = repaf_coord(207, $y, 170);
        $coords['fin_ref.' . $row . '.email'] = repaf_coord(383, $y, 106);
        $coords['fin_ref.' . $row . '.contact'] = repaf_coord(496, $y, 79);
    }

    return $coords;
}
