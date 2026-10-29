<?php

namespace Repaf;

/**
 * REPAF PDF Page Drawers
 *
 * Each function here draws one full page of the multi-page
 * submission PDF (co-owner, property, financial, salesperson,
 * terms). repaf_generate_buyer_pdf() in pdf-generator.php calls
 * these in sequence after drawing the buyer page itself.
 */

if (!defined('ABSPATH')) {
    exit;
}

function repaf_draw_coowner_pdf_page($pdf, $data)
{
    $coordinates = repaf_get_updated_page2_coordinates();
    $pdf->AddPage();

    $template = repaf_get_pdf_template_path('page-2.jpg');

    if (!file_exists($template)) {
        error_log('[REPAF] Missing PDF template: ' . $template);
        return;
    }

    $pdf->Image(
        $template,
        0,
        0,
        595,
        842,
        'JPG',
        '',
        '',
        false,
        300,
        '',
        false,
        false,
        0,
        false,
        false,
        true
    );

    $data = repaf_prepare_buyer_pdf_data($data);
    $data = repaf_prepare_coowner_pdf_data($data);

    repaf_draw_pdf_coordinate_checkmark(
        $pdf,
        repaf_get_pdf_home_ownership_coordinate($data['co_home_ownership'] ?? '', 'co', $coordinates)
    );

    foreach (array('buyer', 'spouse', 'co') as $prefix) {
        repaf_draw_pdf_coordinate_checkmark(
            $pdf,
            repaf_get_pdf_occupation_coordinate($data[$prefix . '_occupation'] ?? '', $prefix, $coordinates)
        );
    }

    $boxed_fields = array(
        'co_spouse_birth_month'  => array('chars' => 2),
        'co_spouse_birth_day'    => array('chars' => 2),
        'co_spouse_birth_year'   => array('chars' => 4),
        'co_rep_birth_month'     => array('chars' => 2, 'box_width' => 15),
        'co_rep_birth_day'       => array('chars' => 2, 'box_width' => 15),
        'co_rep_birth_year'      => array('chars' => 4, 'box_width' => 15),
    );

    foreach ($coordinates as $field => $position) {
        if (strpos($field, 'work_') === 0) {
            continue;
        }

        if (!isset($data[$field]) || $data[$field] === '') {
            continue;
        }

        if (isset($boxed_fields[$field])) {
            $boxed_field = $boxed_fields[$field];

            repaf_draw_pdf_boxed_text(
                $pdf,
                $data[$field],
                $position['x'],
                $position['y'],
                $boxed_field['chars'],
                $boxed_field['box_width'] ?? 18
            );

            continue;
        }

        if (in_array($field, array('co_present_address_line', 'co_rep_address_line'), true)) {
            repaf_draw_pdf_compact_address_text(
                $pdf,
                $data[$field],
                $position['x'],
                $position['y'],
                $position['width'] ?? 0
            );
            continue;
        }

        repaf_draw_pdf_text(
            $pdf,
            $data[$field],
            $position['x'],
            $position['y'],
            $position['width'] ?? 0
        );
    }

    repaf_draw_updated_work_page2($pdf, $data, $coordinates);
}

function repaf_draw_updated_work_page2($pdf, $data, $coordinates)
{
    foreach (array('buyer', 'spouse', 'co') as $prefix) {
        $occupation = repaf_normalize_pdf_option($data[$prefix . '_occupation'] ?? '');
        $is_business = $occupation === 'self_employed_professional';
        $is_employee = in_array(
            $occupation,
            array('locally_employed_private', 'locally_employed_government', 'ofw', 'others'),
            true
        );

        if (!$is_business && !$is_employee) {
            continue;
        }

        repaf_draw_updated_work_text($pdf, $coordinates, 'work_' . $prefix . '_employer_name', $data[$prefix . '_employer_name'] ?? '');

        list($unit_block, $street_subdivision) = repaf_split_updated_work_address(
            $data[$prefix . '_business_address_line'] ?? ''
        );
        repaf_draw_updated_work_compact_text($pdf, $coordinates, 'work_' . $prefix . '_unit_block', $unit_block);
        repaf_draw_updated_work_compact_text($pdf, $coordinates, 'work_' . $prefix . '_street_subdivision', $street_subdivision);
        repaf_draw_updated_work_text($pdf, $coordinates, 'work_' . $prefix . '_barangay', $data[$prefix . '_business_barangay'] ?? '');
        repaf_draw_updated_work_text($pdf, $coordinates, 'work_' . $prefix . '_city', $data[$prefix . '_business_city_municipality'] ?? '');
        repaf_draw_updated_work_text($pdf, $coordinates, 'work_' . $prefix . '_province', $data[$prefix . '_business_province_state'] ?? '');
        repaf_draw_updated_work_text($pdf, $coordinates, 'work_' . $prefix . '_country', $data[$prefix . '_business_country'] ?? '');
        repaf_draw_updated_work_text($pdf, $coordinates, 'work_' . $prefix . '_zip', $data[$prefix . '_business_zip'] ?? '');
        repaf_draw_updated_work_text($pdf, $coordinates, 'work_' . $prefix . '_industry', $data[$prefix . '_industry'] ?? '');
        repaf_draw_updated_work_text($pdf, $coordinates, 'work_' . $prefix . '_office_number', $data[$prefix . '_office_number'] ?? '');
        repaf_draw_updated_work_text($pdf, $coordinates, 'work_' . $prefix . '_office_email', $data[$prefix . '_employment_email'] ?? '');
        repaf_draw_updated_work_text($pdf, $coordinates, 'work_' . $prefix . '_office_mobile', $data[$prefix . '_employment_mobile'] ?? '');

        if ($is_business) {
            $business_type = repaf_normalize_pdf_option($data[$prefix . '_business_type'] ?? '');
            $business_type_key = array(
                'single_proprietorship' => 'single',
                'partnership' => 'partnership',
                'corporation' => 'corporation',
            )[$business_type] ?? '';
            if ($business_type_key !== '') {
                repaf_draw_pdf_coordinate_checkmark(
                    $pdf,
                    $coordinates['work_' . $prefix . '_business_type_' . $business_type_key] ?? array()
                );
            }
            repaf_draw_updated_work_date($pdf, $data, $coordinates, $prefix . '_business_established', 'work_' . $prefix . '_date');
            repaf_draw_updated_work_text($pdf, $coordinates, 'work_' . $prefix . '_years', $data[$prefix . '_years_in_business'] ?? '');
        }

        if ($is_employee) {
            repaf_draw_updated_work_date($pdf, $data, $coordinates, $prefix . '_employed', 'work_' . $prefix . '_date');
            repaf_draw_updated_work_text($pdf, $coordinates, 'work_' . $prefix . '_years', $data[$prefix . '_years_employed'] ?? '');
        }
    }
}

function repaf_split_updated_work_address($address_line)
{
    $parts = array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/', (string) $address_line))));

    if (count($parts) < 2) {
        return array((string) $address_line, '');
    }

    $first_row_parts = count($parts) >= 3 ? 2 : 1;

    return array(
        implode(', ', array_slice($parts, 0, $first_row_parts)),
        implode(', ', array_slice($parts, $first_row_parts)),
    );
}

function repaf_draw_updated_work_compact_text($pdf, $coordinates, $coordinate_key, $value)
{
    if ($value === '' || empty($coordinates[$coordinate_key])) {
        return;
    }

    $position = $coordinates[$coordinate_key];
    repaf_draw_pdf_compact_address_text(
        $pdf,
        $value,
        $position['x'],
        $position['y'],
        $position['width'] ?? 0
    );
}

function repaf_draw_updated_work_text($pdf, $coordinates, $coordinate_key, $value)
{
    if ($value === '' || empty($coordinates[$coordinate_key])) {
        return;
    }
    $position = $coordinates[$coordinate_key];
    repaf_draw_pdf_text($pdf, $value, $position['x'], $position['y'], $position['width'] ?? 0, 11);
}

function repaf_draw_updated_work_date($pdf, $data, $coordinates, $data_prefix, $coordinate_prefix)
{
    foreach (array('month' => 2, 'day' => 2, 'year' => 4) as $part => $characters) {
        $value_key = $data_prefix . '_' . $part;
        $coordinate_key = $coordinate_prefix . '_' . $part;
        if (($data[$value_key] ?? '') === '' || empty($coordinates[$coordinate_key])) {
            continue;
        }
        $position = $coordinates[$coordinate_key];
        // Employment dates use compact individual boxes on the updated form.
        // A 15pt character step keeps MM, DD, and YYYY centered within their
        // own printed boxes without spilling into the next date group.
        repaf_draw_pdf_boxed_text($pdf, $data[$value_key], $position['x'], $position['y'], $characters, 15);
    }
}

/**
 * Blank template page — filled in by the seller/agent after
 * printing for fields with no submitted data; overlays the
 * computed/checkbox fields that are present.
 */
function repaf_draw_property_pdf_page($pdf, $data = array())
{
    $pdf->AddPage();

    $template = repaf_get_pdf_template_path('property-information.jpg');

    if (!file_exists($template)) {
        return;
    }

    $pdf->Image(
        $template,
        0,
        0,
        595,
        842,
        'JPG',
        '',
        '',
        false,
        300,
        '',
        false,
        false,
        0,
        false,
        false,
        true
    );

    if (empty($data)) {
        return;
    }

    $pdf->SetFont('helvetica', '', 8);

    $coordinates = repaf_get_property_pdf_coordinates();

    // Zero-pad Reservation Date day/month so single digits print as "01", "02", etc.
    if (($data['prop_reservation_day'] ?? '') !== '') {
        $data['prop_reservation_day'] = sprintf('%02d', (int) $data['prop_reservation_day']);
    }
    if (($data['prop_reservation_month'] ?? '') !== '') {
        $data['prop_reservation_month'] = sprintf('%02d', (int) $data['prop_reservation_month']);
    }

    // Bank Charges "Starts on" / "Ends on" — split into separate month/day/year
    // boxes (same pattern as Reservation Date), while keeping the raw value
    // around for the plain-text Computation-column duplicate further below.
    foreach (array('prop_loan_starts_on', 'prop_loan_ends_on') as $loan_date_field) {
        if (($data[$loan_date_field] ?? '') === '') {
            continue;
        }
        $timestamp = strtotime($data[$loan_date_field]);
        if ($timestamp === false) {
            continue;
        }
        $data[$loan_date_field . '_month'] = date('m', $timestamp);
        $data[$loan_date_field . '_day']   = date('d', $timestamp);
        $data[$loan_date_field . '_year']  = date('Y', $timestamp);
    }

    // Loanable Amount is the form's exact, manually entered source value.
    // Derive the down payment and display percentages from that amount so a
    // rounded percentage (for example, 16.67%) cannot change the money values.
    // Retain the percentage calculation only as a fallback for older records
    // that do not contain either exact amount.
    $parse_money = static function ($value) {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = str_replace(',', '', trim((string) $value));
        return $normalized !== '' && is_numeric($normalized)
            ? (float) $normalized
            : null;
    };

    $tcp             = floatval($data['prop_total_contract_price'] ?? 0);
    $dp_percent      = floatval($data['prop_dp_percent'] ?? 0);
    $reservation_fee = floatval($data['prop_reservation_fee'] ?? 0);
    $loanable_amount = $parse_money($data['prop_loanable_amount'] ?? null);
    $dp_amount       = $parse_money($data['prop_dp_amount'] ?? null);

    if ($tcp > 0) {
        if ($loanable_amount !== null) {
            $loanable_amount = min(max(0, $loanable_amount), $tcp);
            $dp_amount = $tcp - $loanable_amount;
        } elseif ($dp_amount !== null) {
            $dp_amount = min(max(0, $dp_amount), $tcp);
            $loanable_amount = $tcp - $dp_amount;
        } elseif ($dp_percent > 0) {
            $dp_amount = ($tcp * $dp_percent) / 100;
            $loanable_amount = $tcp - $dp_amount;
        }
    }

    if ($tcp > 0 && $dp_amount !== null && $loanable_amount !== null) {
        $data['prop_dp_amount']       = number_format($dp_amount, 2, '.', '');
        $data['prop_balance_on_dp']   = number_format(max(0, $dp_amount - $reservation_fee), 2, '.', '');
        $data['prop_dp_percent']      = number_format($dp_amount / $tcp * 100, 2, '.', '');
        $data['prop_loan_percent']    = number_format($loanable_amount / $tcp * 100, 2, '.', '');
        $data['prop_loanable_amount'] = number_format($loanable_amount, 2, '.', '');
    }

    // The start date is payment month 1, so an N-month DP schedule ends
    // N - 1 months after it begins (for example, May through December is
    // eight payments). Mirror the form and admin calculation exactly.
    $dp_starts_on = $data['prop_dp_starts_on'] ?? '';
    $dp_months    = intval($data['prop_dp_months'] ?? 0);
    if ($dp_starts_on !== '' && $dp_months > 0) {
        $start_date = \DateTimeImmutable::createFromFormat('!Y-m-d', $dp_starts_on);
        if ($start_date) {
            $payment_day = (int) $start_date->format('j');
            $end_month = $start_date
                ->modify('first day of this month')
                ->modify('+' . max(0, $dp_months - 1) . ' months');
            $last_day = (int) $end_month->format('t');

            $data['prop_dp_ends_on'] = $end_month
                ->setDate(
                    (int) $end_month->format('Y'),
                    (int) $end_month->format('n'),
                    min($payment_day, $last_day)
                )
                ->format('Y-m-d');
        }
    }

    // Financing values print in the Computation column below "DP Ends On".
    // Prefer the PMT-driven in-house fields and retain old records as fallback.
    $data['prop_loan_monthly_computation'] = $data['prop_inhouse_monthly_amort']
        ?? ($data['prop_loan_monthly'] ?? '');
    $data['prop_loan_term_computation'] = $data['prop_inhouse_loan_terms']
        ?? ($data['prop_loan_term'] ?? '');
    $data['prop_loan_starts_on_computation'] = $data['prop_inhouse_starts_on']
        ?? ($data['prop_loan_starts_on'] ?? '');
    if (isset($data['prop_inhouse_interest_rate'])) {
        $data['prop_loan_interest'] = $data['prop_inhouse_interest_rate'];
    }

    // House Finish — two checkbox positions, only one gets marked
    $house_finish = sanitize_key($data['prop_house_finish'] ?? '');
    if ($house_finish === 'basic') {
        repaf_draw_pdf_coordinate_checkmark($pdf, $coordinates['prop_house_finish_basic'] ?? null);
    } elseif ($house_finish === 'complete') {
        repaf_draw_pdf_coordinate_checkmark($pdf, $coordinates['prop_house_finish_complete'] ?? null);
    }

    // Place of Reservation — four checkbox positions, only one gets marked
    $place_map = array(
        'on-site'       => 'prop_place_on_site',
        'branch office' => 'prop_place_branch_office',
        'head office'   => 'prop_place_head_office',
        'others'        => 'prop_place_others',
    );
    $place = strtolower($data['prop_place_of_reservation'] ?? '');
    if (isset($place_map[$place])) {
        repaf_draw_pdf_coordinate_checkmark($pdf, $coordinates[$place_map[$place]] ?? null);
    }

    // Product Type — three checkbox positions, only one gets marked
    $product_map = array(
        'house & lot' => 'prop_product_house_and_lot',
        'lot only'    => 'prop_product_lot_only',
        'others'      => 'prop_product_others',
    );
    $product = strtolower($data['prop_product_type'] ?? '');
    if (isset($product_map[$product])) {
        repaf_draw_pdf_coordinate_checkmark($pdf, $coordinates[$product_map[$product]] ?? null);
    }

    // Payment Type — three checkbox positions, only one gets marked
    $payment_map = array(
        'spot cash'      => 'prop_payment_type_spot_cash',
        'deferred cash'  => 'prop_payment_type_deferred_cash',
        'with financing' => 'prop_payment_type_with_financing',
    );
    $payment = strtolower($data['prop_payment_type'] ?? '');
    if (isset($payment_map[$payment])) {
        repaf_draw_pdf_coordinate_checkmark($pdf, $coordinates[$payment_map[$payment]] ?? null);
    }

    // Financing — four checkbox positions, only one gets marked
    $financing_map = array(
        'bank'     => 'prop_financing_bank',
        'in-house' => 'prop_financing_in_house',
        'hdmf'     => 'prop_financing_hdmf',
        'others'   => 'prop_financing_others',
    );
    $financing = strtolower($data['prop_financing'] ?? '');
    if (isset($financing_map[$financing])) {
        repaf_draw_pdf_coordinate_checkmark($pdf, $coordinates[$financing_map[$financing]] ?? null);
    }

    $house_status_map = array(
        'rfo'     => 'prop_house_status_rfo',
        'nrfo'    => 'prop_house_status_nrfo',
        'ongoing' => 'prop_house_status_ongoing',
    );
    $house_status = strtolower($data['prop_house_status'] ?? '');
    if (isset($house_status_map[$house_status])) {
        repaf_draw_pdf_coordinate_checkmark($pdf, $coordinates[$house_status_map[$house_status]] ?? null);
    }

    // Mode of Payment — four checkbox positions, only one gets marked
    $mode_map = array(
        'cash'                    => 'prop_mode_cash',
        'post dated check'        => 'prop_mode_post_dated_check',
        'bills payment or online' => 'prop_mode_bills_payment_or_online',
        'other'                   => 'prop_mode_other_check',
    );
    $mode = strtolower($data['prop_mode_of_payment'] ?? '');
    if (isset($mode_map[$mode])) {
        repaf_draw_pdf_coordinate_checkmark($pdf, $coordinates[$mode_map[$mode]] ?? null);
    }

    // Draw all mapped fields
    $boxed_fields = array(
        'prop_reservation_day'         => array('chars' => 2, 'box_width' => 16),
        'prop_reservation_month'       => array('chars' => 2, 'box_width' => 16),
        'prop_reservation_year'        => array('chars' => 4, 'box_width' => 20),
        'prop_loan_term'               => array('chars' => 2, 'box_width' => 16),
        'prop_loan_starts_on_month'    => array('chars' => 2, 'box_width' => 12),
        'prop_loan_starts_on_day'      => array('chars' => 2, 'box_width' => 12),
        'prop_loan_starts_on_year'     => array('chars' => 4, 'box_width' => 12),
        'prop_loan_ends_on_month'      => array('chars' => 2, 'box_width' => 12),
        'prop_loan_ends_on_day'        => array('chars' => 2, 'box_width' => 12),
        'prop_loan_ends_on_year'       => array('chars' => 4, 'box_width' => 12),
    );

    // Skip checkbox-only fields from the generic text loop
    $checkbox_fields = array(
        'prop_house_finish_basic',
        'prop_house_finish_complete',
        'prop_place_on_site',
        'prop_place_branch_office',
        'prop_place_head_office',
        'prop_place_others',
        'prop_product_house_and_lot',
        'prop_product_lot_only',
        'prop_product_others',
        'prop_payment_type_spot_cash',
        'prop_payment_type_deferred_cash',
        'prop_payment_type_with_financing',
        'prop_financing_bank',
        'prop_financing_in_house',
        'prop_financing_hdmf',
        'prop_financing_others',
        'prop_house_status_rfo',
        'prop_house_status_nrfo',
        'prop_house_status_ongoing',
        'prop_mode_cash',
        'prop_mode_post_dated_check',
        'prop_mode_bills_payment_or_online',
        'prop_mode_other_check',
        'prop_discount_full_tcp',
        'prop_discount_full_dp',
        'prop_discount_others_check',
    );

    // Every selected discount can coexist and receives its own checkmark.
    $discount_coordinates = array(
        'Full TCP' => 'prop_discount_full_tcp',
        'Full DP'  => 'prop_discount_full_dp',
        'Others'   => 'prop_discount_others_check',
    );
    foreach (repaf_property_discount_types($data) as $discount_type) {
        repaf_draw_pdf_coordinate_checkmark($pdf, $coordinates[$discount_coordinates[$discount_type]] ?? null);
    }

    // Peso-amount fields — printed with thousands separators (e.g. 1000000 -> 1,000,000.00)
    $money_fields = array(
        'prop_price_per_sqm',
        'prop_total_list_price',
        'prop_discount_tcp_amount',
        'prop_discount_dp_amount',
        'prop_discount_others_amount',
        'prop_total_selling_price',
        'prop_add_amount',
        'prop_misc_fee',
        'prop_vat',
        'prop_total_contract_price',
        'prop_dp_amount',
        'prop_reservation_fee',
        'prop_balance_on_dp',
        'prop_dp_monthly',
        'prop_loan_amount',
        'prop_loan_monthly',
        'prop_loan_monthly_computation',
        'prop_mri',
        'prop_fi',
        'prop_insurance_amount',
        'prop_loanable_amount',
    );

    // Date fields — stored as yyyy-mm-dd (HTML date input), printed as mm-dd-yyyy
    $date_fields = array(
        'prop_starts_on',
        'prop_ends_on',
        'prop_dp_starts_on',
        'prop_dp_ends_on',
    );

    foreach ($coordinates as $field => $position) {
        if (in_array($field, $checkbox_fields, true)) {
            continue;
        }

        if (empty($position['x']) && empty($position['y'])) {
            continue;
        }

        $value = (string) ($data[$field] ?? '');

        if ($value === '') {
            continue;
        }

        if (isset($boxed_fields[$field])) {
            $boxed_field = $boxed_fields[$field];

            repaf_draw_pdf_boxed_text(
                $pdf,
                $value,
                $position['x'],
                $position['y'],
                $boxed_field['chars'],
                $boxed_field['box_width'] ?? 18
            );

            continue;
        }

        if (in_array($field, $money_fields, true) && is_numeric($value)) {
            $value = number_format((float) $value, 2);
        }

        if (in_array($field, $date_fields, true)) {
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                $value = date('m-d-Y', $timestamp);
            }
        }

        repaf_draw_pdf_text($pdf, $value, $position['x'], $position['y'], $position['width'] ?? 0);
    }
}

/**
 * Overlays the buyer's monthly income, bank/credit accounts,
 * loan details, and character references onto the template.
 * Coordinate values are managed in pdf-mapper.php.
 */
function repaf_draw_financial_pdf_page($pdf, $data = array())
{
    $pdf->AddPage();

    $template = repaf_get_pdf_template_path('financial-information.jpg');

    if (!file_exists($template)) {
        return;
    }

    $pdf->Image(
        $template,
        0,
        0,
        595,
        842,
        'JPG',
        '',
        '',
        false,
        300,
        '',
        false,
        false,
        0,
        false,
        false,
        true
    );

    $pdf->SetFont('helvetica', '', 8);

    $coordinates = repaf_get_updated_page3_financial_coordinates();

    repaf_draw_updated_work_page3($pdf, $data);

    $fin_money_fields = array(
        'fin_buyer_salary', 'fin_buyer_allowance', 'fin_buyer_commission', 'fin_buyer_other_amount',
        'fin_spouse_salary', 'fin_spouse_allowance', 'fin_spouse_commission', 'fin_spouse_other_amount',
        'fin_co_salary', 'fin_co_allowance', 'fin_co_commission', 'fin_co_other_amount',
        'fin_buyer_total', 'fin_spouse_total', 'fin_co_total',
        'fin_bank.1.balance', 'fin_bank.2.balance', 'fin_bank.3.balance', 'fin_bank.4.balance', 'fin_bank.5.balance',
        'fin_loan.1.original', 'fin_loan.1.outstanding', 'fin_loan.1.amortization',
        'fin_loan.2.original', 'fin_loan.2.outstanding', 'fin_loan.2.amortization',
        'fin_loan.3.original', 'fin_loan.3.outstanding', 'fin_loan.3.amortization',
        'fin_loan.4.original', 'fin_loan.4.outstanding', 'fin_loan.4.amortization',
        'fin_loan.5.original', 'fin_loan.5.outstanding', 'fin_loan.5.amortization',
    );

    foreach ($coordinates as $field => $position) {
        if (empty($position['x']) && empty($position['y'])) {
            continue;
        }

        $value = repaf_get_fin_pdf_value($data, $field);

        if ($value === '') {
            continue;
        }

        if (in_array($field, $fin_money_fields, true) && is_numeric($value)) {
            // Older submissions stored blank optional money fields as numeric
            // zero. Keep those legacy empty values blank on the generated PDF.
            if ((float) $value === 0.0) {
                continue;
            }
            $value = number_format((float) $value, 2);
        }

        repaf_draw_pdf_text($pdf, $value, $position['x'], $position['y'], $position['width'] ?? 0, 11);
    }
}

function repaf_draw_updated_work_page3($pdf, $data)
{
    $coordinates = repaf_get_updated_page3_work_coordinates();

    foreach (array('buyer', 'spouse', 'co') as $prefix) {
        $occupation = repaf_normalize_pdf_option($data[$prefix . '_occupation'] ?? '');
        if (!in_array($occupation, array('locally_employed_private', 'locally_employed_government', 'ofw', 'others'), true)) {
            continue;
        }

        repaf_draw_updated_work_text(
            $pdf,
            $coordinates,
            'work_' . $prefix . '_position_department',
            $data[$prefix . '_position_department'] ?? ''
        );

        $contract_status = repaf_normalize_pdf_option($data[$prefix . '_contract_status'] ?? '');
        if (in_array($contract_status, array('regular', 'contractual'), true)) {
            repaf_draw_pdf_coordinate_checkmark(
                $pdf,
                $coordinates['work_' . $prefix . '_contract_' . $contract_status] ?? array()
            );
        }

        $position_level = repaf_normalize_pdf_option($data[$prefix . '_position_level'] ?? '');
        $position_key = array(
            'rank_and_file_staff_clerk' => 'rank_file',
            'supervisor_team_lead' => 'supervisor',
            'manager_director' => 'manager',
            'executive_officer' => 'executive',
            'professional_doctor_lawyer_engineer_architect' => 'professional',
        )[$position_level] ?? '';

        if ($position_key !== '') {
            repaf_draw_pdf_coordinate_checkmark(
                $pdf,
                $coordinates['work_' . $prefix . '_position_' . $position_key] ?? array()
            );
        }
    }
}

/**
 * Overlays salesperson printed names and remarks onto the
 * Sales Person's Information page.
 */
function repaf_draw_salesperson_pdf_page($pdf, $data = array())
{
    $pdf->AddPage();

    $template = repaf_get_pdf_template_path('salesperson-information.jpg');

    if (!file_exists($template)) {
        return;
    }

    $pdf->Image(
        $template,
        0,
        0,
        595,
        842,
        'JPG',
        '',
        '',
        false,
        300,
        '',
        false,
        false,
        0,
        false,
        false,
        true
    );

    if (empty($data)) {
        return;
    }

    $pdf->SetFont('helvetica', '', 8);

    $coordinates = repaf_get_salesperson_pdf_coordinates();

    $centered_name_fields = array(
        'sp_agent_name', 'sp_manager_name', 'sp_broker_name', 'sp_realty_name',
        'sp_prepared_by', 'sp_verified_by', 'sp_checked_by', 'sp_approved_by',
    );

    foreach ($coordinates as $field => $position) {
        if (empty($position['x']) && empty($position['y'])) {
            continue;
        }

        $value = (string) ($data[$field] ?? '');

        if ($value === '') {
            continue;
        }

        if (in_array($field, $centered_name_fields, true)) {
            $value = strtoupper(wp_strip_all_tags($value));
            $width = (float) ($position['width'] ?? 0);
            if ($width <= 0) {
                continue;
            }

            $font_size = 8.0;
            $pdf->SetFont('helvetica', '', $font_size);
            while ($font_size > 5.0 && $pdf->GetStringWidth($value) > $width - 4) {
                $font_size -= 0.5;
                $pdf->SetFont('helvetica', '', $font_size);
            }

            $pdf->SetXY((float) $position['x'], (float) $position['y']);
            $pdf->Cell($width, 0, $value, 0, 0, 'C');
            continue;
        }

        repaf_draw_pdf_text($pdf, $value, $position['x'], $position['y'], $position['width'] ?? 0);
    }
}

/**
 * Prints the background image, then overlays the buyer's e-signature plus
 * the available buyer, spouse/Attorney-in-Fact, and co-owner/representative
 * printed names and conditional signed dates.
 *
 * Adjust $sig_x / $sig_y below to reposition.
 */
function repaf_draw_terms_pdf_page($pdf, $data = array())
{
    $pdf->AddPage();

    $template = repaf_get_pdf_template_path('terms-and-conditions.jpg');

    if (!file_exists($template)) {
        return;
    }

    $pdf->Image(
        $template,
        0,
        0,
        595,
        842,
        'JPG',
        '',
        '',
        false,
        300,
        '',
        false,
        false,
        0,
        false,
        false,
        true
    );

    $coordinates = repaf_get_terms_pdf_coordinates();
    $signature_position = $coordinates['buyer_signature'] ?? array();
    $sig_x = (float) ($signature_position['x'] ?? 38);
    $sig_y = (float) ($signature_position['y'] ?? 722);
    $sig_w = (float) ($signature_position['width'] ?? 120);
    $sig_h = (float) ($signature_position['height'] ?? 45);

    $sig_path = $data['buyer_esignature_path'] ?? '';

    if (!empty($sig_path) && file_exists($sig_path)) {
        $sig_ext = strtoupper(pathinfo($sig_path, PATHINFO_EXTENSION));
        $sig_ext = ($sig_ext === 'JPG') ? 'JPEG' : $sig_ext;

        // Multiply blend mode: white pixels (255,255,255) x background = background
        // (i.e. white disappears), dark ink stays — reliable on any PDF viewer.
        $pdf->setAlpha(1, 'Multiply');
        $pdf->Image(
            $sig_path,
            $sig_x,
            $sig_y,
            $sig_w,
            $sig_h,
            $sig_ext,
            '',
            '',
            false,
            300,
            '',
            false,
            false,
            0,
            false,
            false,
            false
        );
        $pdf->setAlpha(1, 'Normal');
    }

    $build_name = function ($prefix) use ($data) {
        return strtoupper(trim(implode(' ', array_filter(array(
            trim((string) ($data[$prefix . '_first_name'] ?? '')),
            trim((string) ($data[$prefix . '_middle_name'] ?? '')),
            trim((string) ($data[$prefix . '_last_name'] ?? '')),
        ), 'strlen'))));
    };

    $draw_printed_name = function ($name, $position) use ($pdf) {
        if ($name === '' || empty($position)) {
            return;
        }

        $width = (float) ($position['width'] ?? 140);
        $font_size = 8.0;
        $pdf->SetFont('helvetica', 'B', $font_size);

        while ($font_size > 5.0 && $pdf->GetStringWidth($name) > $width - 4) {
            $font_size -= 0.5;
            $pdf->SetFont('helvetica', 'B', $font_size);
        }

        $pdf->SetXY(
            (float) ($position['x'] ?? 0),
            (float) ($position['y'] ?? 759)
        );
        $pdf->Cell($width, 0, $name, 0, 0, 'C');
    };

    $buyer_printed_name = $build_name('buyer');
    $spouse_printed_name = $build_name('spouse');
    if ($spouse_printed_name === '') {
        $spouse_printed_name = $build_name('rep');
    }
    $co_borrower_printed_name = $build_name('co');
    if ($co_borrower_printed_name === '') {
        $co_borrower_printed_name = $build_name('co_rep');
    }

    $draw_printed_name(
        $buyer_printed_name,
        $coordinates['buyer_printed_name'] ?? array()
    );
    $draw_printed_name(
        $spouse_printed_name,
        $coordinates['spouse_printed_name'] ?? array()
    );
    $draw_printed_name(
        $co_borrower_printed_name,
        $coordinates['co_borrower_printed_name'] ?? array()
    );

    $signed_date = date('F j, Y');
    $draw_signed_date = function ($name, $position) use ($pdf, $signed_date) {
        if ($name === '' || empty($position)) {
            return;
        }

        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY(
            (float) ($position['x'] ?? 0),
            (float) ($position['y'] ?? 790)
        );
        $pdf->Write(0, $signed_date);
    };

    $draw_signed_date(
        $buyer_printed_name,
        $coordinates['buyer_date_signed'] ?? array()
    );
    $draw_signed_date(
        $spouse_printed_name,
        $coordinates['spouse_date_signed'] ?? array()
    );
    $draw_signed_date(
        $co_borrower_printed_name,
        $coordinates['co_borrower_date_signed'] ?? array()
    );
}
