<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * =========================================================
 * Submission validator
 * =========================================================
 *
 * Two-stage validation:
 *
 * 1. repaf_get_missing_required_fields() — presence check.
 *    Returns labels of required fields that are empty.
 *
 * 2. repaf_validate_submission() — format check.
 *    Validates email format, date sanity (not future,
 *    not >120 years ago), radio allowlists, and positive
 *    numbers. Mirrors the JS wizard rules exactly so
 *    client and server agree on what is valid.
 *
 * Both functions return empty arrays on success.
 */

/**
 * Check that all required fields are present and non-empty.
 *
 * @param  array $data  Sanitized submission data.
 * @return array        Human-readable labels of missing fields.
 */
function repaf_get_missing_required_fields($data)
{
    $required_fields = array_merge(
        repaf_required_fields(),
        repaf_conditional_required_fields($data)
    );

    $missing = repaf_missing_required_fields($data, $required_fields);

    $reference_required_fields = array(
        'name'    => 'Name',
        'address' => 'Present Address',
        'contact' => 'Contact Number',
    );

    for ($idx = 1; $idx <= 3; $idx++) {
        $row = isset($data['fin_ref'][$idx]) && is_array($data['fin_ref'][$idx])
            ? $data['fin_ref'][$idx]
            : array();

        $row_required_fields = $reference_required_fields;
        if (trim((string) ($row['last_name'] ?? '')) !== '' || trim((string) ($row['first_name'] ?? '')) !== '' ||
            trim((string) ($row['middle_name'] ?? '')) !== '' || trim((string) ($row['suffix'] ?? '')) !== '' ||
            trim((string) ($row['name'] ?? '')) === '') {
            unset($row_required_fields['name']);
            $row_required_fields = array_merge(array(
                'last_name' => 'Last Name',
                'first_name' => 'First Name',
            ), $row_required_fields);
        }

        foreach ($row_required_fields as $field_key => $field_label) {
            if (trim((string) ($row[$field_key] ?? '')) === '') {
                $missing[] = 'Character Reference ' . $idx . ' ' . $field_label;
            }
        }
    }

    return $missing;
}

/** Return missing labels for a scoped set of required fields. */
function repaf_missing_required_fields($data, $required_fields)
{
    $missing = array();
    foreach ($required_fields as $key => $label) {
        if (!isset($data[$key]) || !is_scalar($data[$key]) || trim((string) $data[$key]) === '') {
            $missing[] = $label;
        }
    }
    return $missing;
}

/** Validate required property data before either salesperson save path writes. */
function repaf_get_missing_required_property_fields($data)
{
    // The date picker is not stored; require valid saved date components.
    $date_parts = array();
    foreach (array('prop_reservation_year', 'prop_reservation_month', 'prop_reservation_day') as $key) {
        $value = isset($data[$key]) && is_scalar($data[$key]) ? trim((string) $data[$key]) : '';
        $date_parts[] = preg_match('/^\d+$/', $value) ? (int) $value : 0;
    }
    list($year, $month, $day) = $date_parts;
    $data['prop_reservation_date'] = $year <= 9999 && checkdate($month, $day, $year)
        ? sprintf('%04d-%02d-%02d', $year, $month, $day)
        : '';

    $required = repaf_required_property_fields();
    if (($data['prop_house_finish'] ?? '') === 'Others') {
        $required['prop_house_finish_other'] = 'House Finish (Others)';
    }
    if (($data['prop_is_vatable'] ?? 'no') === 'yes') {
        $required['prop_vat_percent'] = 'VAT (%)';
    }
    return repaf_missing_required_fields($data, $required);
}

/**
 * Validate field formats for the full submission.
 *
 * @param  array $data  Sanitized submission data.
 * @return array        Human-readable validation error messages.
 */
function repaf_validate_submission($data)
{
    $errors = array();

    $validate_dob = function ($value, $label) use (&$errors) {
        if (empty($value)) {
            return;
        }
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            $errors[] = $label . ' is not a valid date.';
            return;
        }
        $now     = new \DateTime();
        $min_dob = new \DateTime('-120 years');
        if ($date > $now) {
            $errors[] = $label . ' cannot be in the future.';
        }
        if ($date < $min_dob) {
            $errors[] = $label . ' cannot be more than 120 years ago.';
        }
    };

    $validate_date = function ($value, $label) use (&$errors) {
        if (empty($value)) {
            return;
        }
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            $errors[] = $label . ' is not a valid date.';
            return;
        }
        if ($date > new \DateTime()) {
            $errors[] = $label . ' cannot be in the future.';
        }
    };

    $validate_email = function ($value, $label) use (&$errors) {
        if (empty($value)) {
            return;
        }
        if (!is_email($value)) {
            $errors[] = $label . ' is not a valid email address.';
        }
    };

    $validate_radio = function ($value, $label, $allowed) use (&$errors) {
        if (empty($value)) {
            return;
        }
        if (!in_array($value, $allowed, true)) {
            $errors[] = $label . ' contains an invalid value.';
        }
    };

    $validate_positive_number = function ($value, $label) use (&$errors) {
        if ($value === '' || $value === null) {
            return;
        }
        if (!is_numeric($value) || (float) $value < 0) {
            $errors[] = $label . ' must be a non-negative number.';
        }
    };

    $validate_work = function ($prefix, $label, $enabled = true) use (
        &$errors,
        $data,
        $validate_radio,
        $validate_date,
        $validate_email,
        $validate_positive_number
    ) {
        if (!$enabled) {
            return;
        }

        $occupation = $data[$prefix . '_occupation'] ?? '';
        $validate_radio(
            $occupation,
            $label . ' Employment Status',
            array(
                'Locally Employed - Private',
                'Locally Employed - Government',
                'Self Employed / Professional',
                'OFW',
                'Others',
            )
        );

        $industry = $data[$prefix . '_industry'] ?? '';
        if ($industry !== '' && !in_array($industry, repaf_industry_options(), true)) {
            $errors[] = $label . ' Industry / Type of Work contains an invalid value.';
        }

        $validate_email(
            $data[$prefix . '_employment_email'] ?? '',
            $label . ' Office Email Address'
        );

        if ($occupation === 'Self Employed / Professional') {
            $validate_positive_number(
                $data[$prefix . '_years_in_business'] ?? '',
                $label . ' No. of Years in Business'
            );
            $validate_radio(
                $data[$prefix . '_business_type'] ?? '',
                $label . ' Business Type',
                repaf_business_company_type_options()
            );
            $validate_date(
                $data[$prefix . '_business_established_date'] ?? '',
                $label . ' Date of Business Established'
            );
        } elseif (in_array($occupation, array(
            'Locally Employed - Private',
            'Locally Employed - Government',
            'OFW',
            'Others',
        ), true)) {
            $validate_positive_number(
                $data[$prefix . '_years_employed'] ?? '',
                $label . ' No. of Years Employed'
            );
            $validate_date(
                $data[$prefix . '_employed_date'] ?? '',
                $label . ' Date Employed'
            );
            $validate_radio(
                $data[$prefix . '_contract_status'] ?? '',
                $label . ' Employment Contract Status',
                repaf_contract_status_options()
            );
            $validate_radio(
                $data[$prefix . '_position_level'] ?? '',
                $label . ' Position / Level',
                repaf_position_level_options()
            );
        }
    };

    // Buyer
    $validate_radio(
        $data['privacy_policy_consent'] ?? '',
        'Privacy Policy consent',
        array('accepted')
    );
    $validate_radio(
        $data['data_privacy_consent'] ?? '',
        'Data Privacy Policy consent',
        array('accepted')
    );
    $validate_email($data['buyer_email'] ?? '', 'Buyer Email Address');
    $validate_dob($data['buyer_dob']     ?? '', 'Buyer Date of Birth');
    $validate_radio($data['buyer_gender'] ?? '', 'Buyer Gender', array('Male', 'Female'));
    $validate_radio(
        $data['buyer_civil_status'] ?? '',
        'Buyer Civil Status',
        array('Single', 'Married', 'Widow/er', 'Annulled', 'Legally Separated')
    );
    $validate_radio(
        $data['buyer_home_ownership'] ?? '',
        'Buyer Home Ownership',
        array('Owned', 'Company', 'Living with Relatives', 'Mortgage', 'Rented')
    );
    $validate_work('buyer', 'Buyer');
    $validate_positive_number($data['buyer_age']              ?? '', 'Buyer Age');
    $validate_positive_number($data['buyer_rent_amount']      ?? '', 'Buyer Monthly Rent Amount');
    $validate_radio(
        $data['has_representative'] ?? '',
        'Authorized Representative / Attorney-In-Fact selection',
        array('yes', 'no')
    );
    $validate_radio(
        $data['has_co_owner'] ?? '',
        'Co-Owner / Co-Borrower / Financier selection',
        array('yes', 'no')
    );

    // Spouse (married buyers only)
    if (($data['buyer_civil_status'] ?? '') === 'Married') {
        $validate_email($data['spouse_email'] ?? '', 'Spouse Email Address');
        $validate_dob($data['spouse_dob']     ?? '', 'Spouse Date of Birth');
        $validate_positive_number($data['spouse_age']        ?? '', 'Spouse Age');
        $validate_positive_number($data['spouse_dependents'] ?? '', 'Spouse No. of Dependents');
        $validate_work('spouse', 'Spouse');
    }

    // Representative (only when declared)
    if (($data['has_representative'] ?? '') === 'yes') {
        $validate_email($data['rep_email']  ?? '', 'Representative Email');
        $validate_dob($data['rep_dob']      ?? '', 'Representative Date of Birth');
        $validate_positive_number($data['rep_age'] ?? '', 'Representative Age');
    }

    // Co-owner (only when declared)
    if (($data['has_co_owner'] ?? '') === 'yes') {
        $validate_radio(
            $data['co_owner_role'] ?? '',
            'Co-Owner Role',
            array('Co-owner', 'Co-borrower', 'Financier')
        );
        $validate_email($data['co_email'] ?? '', 'Co-Owner Email Address');
        $validate_dob($data['co_dob']     ?? '', 'Co-Owner Date of Birth');
        $validate_radio($data['co_gender'] ?? '', 'Co-Owner Gender', array('Male', 'Female'));
        $validate_radio(
            $data['co_civil_status'] ?? '',
            'Co-Owner Civil Status',
            array('Single', 'Married', 'Widow/er', 'Annulled', 'Legally Separated')
        );
        $validate_radio(
            $data['co_home_ownership'] ?? '',
            'Co-Owner Home Ownership',
            array('Owned', 'Company', 'Living with Relatives', 'Mortgage', 'Rented')
        );
        $validate_work('co', 'Co-Owner');
        $validate_positive_number($data['co_age']              ?? '', 'Co-Owner Age');
        $validate_positive_number($data['co_rent_amount']      ?? '', 'Co-Owner Monthly Rent Amount');

        if (($data['co_civil_status'] ?? '') === 'Married') {
            $validate_email($data['co_spouse_email']               ?? '', 'Co-Owner Spouse Email');
            $validate_dob($data['co_spouse_dob']                   ?? '', 'Co-Owner Spouse Date of Birth');
            $validate_positive_number($data['co_spouse_age']        ?? '', 'Co-Owner Spouse Age');
            $validate_positive_number($data['co_spouse_dependents'] ?? '', 'Co-Owner Spouse No. of Dependents');
        }

        if (
            trim((string) ($data['co_rep_last_name'] ?? '')) !== '' ||
            trim((string) ($data['co_rep_first_name'] ?? '')) !== ''
        ) {
            $validate_email($data['co_rep_email']       ?? '', 'Co-Owner Representative Email');
            $validate_dob($data['co_rep_dob']           ?? '', 'Co-Owner Representative Date of Birth');
            $validate_positive_number($data['co_rep_age'] ?? '', 'Co-Owner Representative Age');
        }
    }

    // Financial Information (all optional — validate format only if present)
    $fin_scalars = array(
        'fin_buyer_salary'       => 'Buyer Salary',
        'fin_buyer_allowance'    => 'Buyer Allowance (COLA/PERA)',
        'fin_buyer_commission'   => 'Buyer Commissions',
        'fin_buyer_other_amount' => 'Buyer Other Income',
        'fin_spouse_salary'      => 'Spouse Salary',
        'fin_spouse_allowance'   => 'Spouse Allowance (COLA/PERA)',
        'fin_spouse_commission'  => 'Spouse Commissions',
        'fin_spouse_other_amount' => 'Spouse Other Income',
        'fin_co_salary'          => 'Co-Borrower Salary',
        'fin_co_allowance'       => 'Co-Borrower Allowance (COLA/PERA)',
        'fin_co_commission'      => 'Co-Borrower Commissions',
        'fin_co_other_amount'    => 'Co-Borrower Other Income',
        'fin_buyer_total'        => 'Buyer Total Monthly Income',
        'fin_spouse_total'       => 'Spouse Total Monthly Income',
        'fin_co_total'           => 'Co-Borrower Total Monthly Income',
        'fin_total_salary'       => 'Total Salary',
        'fin_total_allowance'    => 'Total Allowance',
        'fin_total_commission'   => 'Total Commissions',
        'fin_total_other'        => 'Total Other Income',
    );

    foreach ($fin_scalars as $field_key => $field_label) {
        $validate_positive_number($data[$field_key] ?? '', $field_label);
    }

    // Validate money fields inside bank and loan dynamic rows
    $row_money_fields = array('balance', 'original', 'outstanding', 'amortization');

    foreach (array('fin_bank', 'fin_loan') as $section) {
        if (!empty($data[$section]) && is_array($data[$section])) {
            foreach ($data[$section] as $idx => $row) {
                if (!is_array($row)) {
                    continue;
                }
                foreach ($row_money_fields as $mf) {
                    if (isset($row[$mf]) && $row[$mf] !== '') {
                        $label = ucfirst(str_replace('fin_', '', $section)) . ' row ' . $idx . ' ' . $mf;
                        $validate_positive_number($row[$mf], $label);
                    }
                }
            }
        }
    }

    // Validate email inside character reference rows
    if (!empty($data['fin_ref']) && is_array($data['fin_ref'])) {
        foreach ($data['fin_ref'] as $idx => $row) {
            if (!empty($row['email'])) {
                $validate_email($row['email'], 'Character Reference ' . $idx . ' Email');
            }
        }
    }

    return $errors;
}
