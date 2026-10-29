<?php

namespace Repaf;

/**
 * REPAF Admin Submissions — Save Handler
 *
 * Processes the form rendered by admin-submissions-edit.php
 * (admin_post_repaf_admin_save_submission, hooked in
 * admin-submissions.php).
 */

if (!defined('ABSPATH')) {
    exit;
}

function repaf_admin_handle_save_submission()
{
    if (!current_user_can('manage_repaf_submissions')) {
        wp_die(__('You do not have permission to perform this action.'));
    }

    $post_id = isset($_POST['repaf_submission_id']) ? absint($_POST['repaf_submission_id']) : 0;

    if ($post_id === 0) {
        wp_die(__('Invalid submission ID.'));
    }

    check_admin_referer('repaf_save_submission_' . $post_id);

    $post = get_post($post_id);

    if (!$post || $post->post_type !== 'repaf_submission') {
        wp_die(__('Submission not found.'));
    }

    // Sanitize and save customer fields.
    // Force has_co_owner='yes' so the sanitizer does not strip co_* fields —
    // the admin can always edit co-owner data regardless of the buyer's original answer.
    $post_data = array_merge($_POST, array('has_co_owner' => 'yes'));
    // Removing the final optional row means the browser submits no field for
    // that collection. Store an empty array so previously saved rows are cleared.
    foreach (array('fin_bank', 'fin_loan') as $repeatable_key) {
        if (!array_key_exists($repeatable_key, $post_data)) {
            $post_data[$repeatable_key] = array();
        }
    }
    $customer_data = repaf_sanitize_submission($post_data);
    $property_data = repaf_sanitize_property_info($_POST);
    $missing = array_merge(
        repaf_missing_required_fields($customer_data, repaf_required_buyer_address_fields()),
        repaf_get_missing_required_property_fields($property_data)
    );
    if (!empty($missing)) {
        wp_die(
            esc_html('Please complete all required fields. Missing: ' . implode(', ', $missing) . '.'),
            'Missing Required Fields',
            array('response' => 400, 'back_link' => true)
        );
    }

    foreach ($customer_data as $key => $value) {
        update_post_meta($post_id, $key, $value);
    }

    // Sanitize and save property fields
    foreach ($property_data as $key => $value) {
        update_post_meta($post_id, $key, $value);
    }

    // Salesperson fields — sp_remarks uses textarea sanitizer to preserve line breaks
    $sp_text_fields = array(
        'sp_agent_name', 'sp_manager_name', 'sp_broker_name', 'sp_realty_name',
        'sp_prepared_by', 'sp_verified_by', 'sp_checked_by', 'sp_approved_by',
    );
    foreach ($sp_text_fields as $key) {
        if (isset($_POST[$key])) {
            update_post_meta($post_id, $key, sanitize_text_field(wp_unslash($_POST[$key])));
        }
    }
    if (isset($_POST['sp_remarks'])) {
        update_post_meta($post_id, 'sp_remarks', sanitize_textarea_field(wp_unslash($_POST['sp_remarks'])));
    }

    // A normal edit remains a draft unless the salesperson explicitly confirms
    // the reservation. Already-confirmed records refresh their existing row.
    $already_confirmed = get_post_meta($post_id, 'reservation_confirmed', true) === 'yes';
    $confirm_requested = isset($_POST['repaf_confirm_reservation']) &&
        sanitize_key(wp_unslash($_POST['repaf_confirm_reservation'])) === 'yes';
    $csv_error = false;
    $batched   = false;

    if ($already_confirmed || $confirm_requested) {
        $csv_path  = repaf_refresh_submission_csv($post_id);
        $csv_error = is_wp_error($csv_path);
        $batched   = !$csv_error;
    }

    if ($csv_error) {
        error_log(sprintf(
            '[REPAF] Daily batch CSV refresh failed for post #%d. Error: %s',
            $post_id,
            $csv_path->get_error_message()
        ));
    }

    $redirect = add_query_arg(array(
        'page'      => 'repaf-submissions',
        'action'    => 'edit',
        'id'        => $post_id,
        '_wpnonce'  => wp_create_nonce('repaf_edit_submission_' . $post_id),
        'saved'     => 1,
        'csv_error' => $csv_error ? 1 : 0,
        'batched'   => $batched ? 1 : 0,
    ), admin_url('admin.php'));

    wp_safe_redirect($redirect);
    exit;
}

function repaf_sanitize_property_info($data)
{
    // The named date picker is authoritative when present. Clearing it must
    // also clear stale hidden date components, even without JavaScript.
    if (array_key_exists('prop_reservation_date', $data)) {
        $value = is_scalar($data['prop_reservation_date'])
            ? trim((string) wp_unslash($data['prop_reservation_date']))
            : '';
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $value)
            : false;
        $valid = $date && $date->format('Y-m-d') === $value;
        $data['prop_reservation_year'] = $valid ? $date->format('Y') : '';
        $data['prop_reservation_month'] = $valid ? $date->format('m') : '';
        $data['prop_reservation_day'] = $valid ? $date->format('d') : '';
    }

    $text_fields = array(
        'prop_project', 'prop_phase', 'prop_block', 'prop_lot',
        'prop_house_model', 'prop_house_finish', 'prop_house_finish_other', 'prop_house_status', 'prop_place_of_reservation', 'prop_place_other',
        // prop_place_of_reservation kept for storage; checkbox coords use prop_place_* keys in pdf-mapper
        'prop_payment_type', 'prop_financing',
        'prop_product_type', 'prop_product_other', 'prop_mode_of_payment', 'prop_mode_other',
        'prop_discount_others',
        'prop_is_vatable',
    );

    $number_fields = array(
        'prop_reservation_day', 'prop_reservation_month', 'prop_reservation_year',
        'prop_lot_area', 'prop_floor_area', 'prop_price_per_sqm', 'prop_total_list_price',
        'prop_dp_percent', 'prop_dp_amount', 'prop_reservation_fee', 'prop_balance_on_dp', 'prop_dp_months', 'prop_dp_monthly',
        'prop_add_amount', 'prop_misc_fee_percent', 'prop_misc_fee',
        'prop_loan_amount', 'prop_loan_term', 'prop_loan_interest', 'prop_loan_monthly', 'prop_loan_percent', 'prop_loanable_amount',
        'prop_inhouse_monthly_amort', 'prop_inhouse_loan_terms', 'prop_inhouse_interest_rate',
        'prop_mri', 'prop_fi', 'prop_insurance_amount',
        'prop_discount_tcp_percent', 'prop_discount_tcp_days', 'prop_discount_tcp_amount',
        'prop_discount_dp_percent', 'prop_discount_dp_days', 'prop_discount_dp_amount',
        'prop_discount_others_amount',
        'prop_total_contract_price', 'prop_total_selling_price',
        'prop_vat', 'prop_vat_percent',
    );

    $date_fields = array(
        'prop_starts_on', 'prop_ends_on',
        'prop_dp_starts_on', 'prop_dp_ends_on',
        'prop_loan_starts_on', 'prop_loan_ends_on',
        'prop_inhouse_starts_on',
    );

    $clean = array();

    foreach ($text_fields as $key) {
        if (isset($data[$key])) {
            $clean[$key] = sanitize_text_field(wp_unslash($data[$key]));
        }
    }

    if (array_key_exists('prop_discount_types', $data) || array_key_exists('prop_discount_type', $data)) {
        $clean['prop_discount_types'] = repaf_property_discount_types($data);
        // Retain a scalar for older integrations; current readers use the list.
        $clean['prop_discount_type'] = $clean['prop_discount_types'][0] ?? '';
    }

    // Spot Cash and Deferred Cash do not use a financing institution. Keep
    // storage and the corresponding PDF checkbox consistent with the lock.
    if (in_array(($clean['prop_payment_type'] ?? ''), array('Spot Cash', 'Deferred Cash'), true)) {
        $clean['prop_financing'] = 'Others';
    }

    if (($clean['prop_house_finish'] ?? '') !== 'Others') {
        $clean['prop_house_finish_other'] = '';
    }

    foreach ($number_fields as $key) {
        if (isset($data[$key]) && is_scalar($data[$key])) {
            $raw = trim(str_replace(',', '', (string) wp_unslash($data[$key])));
            if ($raw === '' || !is_numeric($raw)) {
                continue;
            }
            $clean[$key] = (string) floatval($raw);
        }
    }

    // These independent amount inputs must also clear previously saved values
    // when a salesperson intentionally empties one of the rows.
    foreach (array('prop_discount_tcp_amount', 'prop_discount_dp_amount', 'prop_discount_others_amount') as $key) {
        if (array_key_exists($key, $data) && trim((string) wp_unslash($data[$key])) === '') {
            $clean[$key] = '';
        }
    }

    foreach ($date_fields as $key) {
        if (isset($data[$key])) {
            $clean[$key] = sanitize_text_field(wp_unslash($data[$key]));
        }
    }

    // Miscellaneous Fee and VAT are optional calculations based on Total
    // Selling Price. Total Contract Price is supplied manually.
    $is_vatable = ($clean['prop_is_vatable'] ?? 'no') === 'yes';
    if (isset($clean['prop_total_selling_price'])) {
        $selling_price = (float) $clean['prop_total_selling_price'];
        $misc_percent  = (float) ($clean['prop_misc_fee_percent'] ?? 0);
        $vat_percent   = $is_vatable ? (float) ($clean['prop_vat_percent'] ?? 0) : 0;
        $misc_fee      = $selling_price * $misc_percent / 100;
        $vat           = $selling_price * $vat_percent / 100;

        $clean['prop_misc_fee'] = number_format($misc_fee, 2, '.', '');
        $clean['prop_vat']      = $is_vatable ? number_format($vat, 2, '.', '') : '';
    } else {
        $clean['prop_misc_fee'] = '';
        $clean['prop_vat']      = '';
    }

    if (!$is_vatable) {
        $clean['prop_vat_percent'] = '';
    }

    if (isset($clean['prop_total_contract_price'], $clean['prop_loanable_amount'])) {
        // Excel equivalent: PMT(interest / 12, loan years * 12, -loanable amount).
        // Percentage inputs are stored as whole percentages, so 6 means 6%.
        $total_contract_price = (float) $clean['prop_total_contract_price'];
        $loanable_amount      = min(
            max(0, (float) ($clean['prop_loanable_amount'] ?? 0)),
            max(0, $total_contract_price)
        );
        $dp_amount            = max(0, $total_contract_price - $loanable_amount);
        $dp_percent           = $total_contract_price > 0
            ? $dp_amount / $total_contract_price * 100
            : 0;
        $loan_percent         = $total_contract_price > 0
            ? $loanable_amount / $total_contract_price * 100
            : 0;
        $reservation_fee      = (float) ($clean['prop_reservation_fee'] ?? 0);
        $balance_on_dp        = max(0, $dp_amount - $reservation_fee);
        $dp_months            = (float) ($clean['prop_dp_months'] ?? 0);

        $clean['prop_dp_percent']       = number_format($dp_percent, 2, '.', '');
        $clean['prop_dp_amount']       = number_format($dp_amount, 2, '.', '');
        $clean['prop_loanable_amount'] = number_format($loanable_amount, 2, '.', '');
        $clean['prop_loan_percent']    = number_format($loan_percent, 2, '.', '');
        $clean['prop_balance_on_dp']   = number_format($balance_on_dp, 2, '.', '');
        $clean['prop_dp_monthly']      = $dp_months > 0
            ? number_format($balance_on_dp / $dp_months, 2, '.', '')
            : '';

        $has_interest_rate = array_key_exists('prop_inhouse_interest_rate', $clean);
        $loan_years        = (float) ($clean['prop_inhouse_loan_terms'] ?? 0);
        $annual_interest   = $has_interest_rate
            ? (float) $clean['prop_inhouse_interest_rate'] / 100
            : 0;
        $payment_months    = $loan_years * 12;
        $monthly_payment   = 0;

        if ($loanable_amount > 0 && $payment_months > 0 && $has_interest_rate) {
            $monthly_rate = $annual_interest / 12;
            $monthly_payment = $monthly_rate == 0.0
                ? $loanable_amount / $payment_months
                : $loanable_amount * $monthly_rate /
                    (1 - pow(1 + $monthly_rate, -$payment_months));
        }

        $clean['prop_inhouse_monthly_amort'] = $monthly_payment > 0
            ? number_format(round($monthly_payment), 2, '.', '')
            : '';
    } else {
        $clean['prop_dp_percent']             = '';
        $clean['prop_dp_amount']              = '';
        $clean['prop_loan_percent']           = '';
        $clean['prop_balance_on_dp']          = '';
        $clean['prop_dp_monthly']             = '';
        $clean['prop_inhouse_monthly_amort']  = '';
    }

    // The DP start date is the first payment, so an N-month schedule ends
    // N - 1 months after it begins.
    $dp_start  = (string) ($clean['prop_dp_starts_on'] ?? '');
    $dp_months = (int) ($clean['prop_dp_months'] ?? 0);
    if ($dp_start !== '' && $dp_months > 0) {
        $start_date = \DateTimeImmutable::createFromFormat('!Y-m-d', $dp_start);
        if ($start_date) {
            $payment_day = (int) $start_date->format('j');
            $end_month = $start_date
                ->modify('first day of this month')
                ->modify('+' . max(0, $dp_months - 1) . ' months');
            $last_day = (int) $end_month->format('t');
            $clean['prop_dp_ends_on'] = $end_month
                ->setDate(
                    (int) $end_month->format('Y'),
                    (int) $end_month->format('n'),
                    min($payment_day, $last_day)
                )
                ->format('Y-m-d');
        } else {
            $clean['prop_dp_ends_on'] = '';
        }
    } else {
        $clean['prop_dp_ends_on'] = '';
    }

    return $clean;
}
