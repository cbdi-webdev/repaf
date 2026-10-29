<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * =========================================================
 * Submission sanitizer
 * =========================================================
 *
 * Cleans raw POST data before it is validated or saved.
 * Each field type gets the appropriate WordPress sanitizer:
 * - email fields    → sanitize_email()
 * - numeric fields  → floatval()
 * - everything else → sanitize_text_field()
 *
 * Internal system fields (nonce, action, honeypot) are
 * stripped entirely. Representative and co-owner fields are
 * stripped when the buyer answered No to those sections,
 * mirroring the JS conditional step logic.
 */
function repaf_sanitize_submission($data)
{
    $clean = array();

    foreach ($data as $key => $value) {

        if (in_array($key, repaf_internal_fields(), true)) {
            continue;
        }

        $key = sanitize_key($key);

        // Handle financial dynamic-row nested arrays (fin_bank, fin_loan, fin_ref)
        if (in_array($key, array('fin_bank', 'fin_loan', 'fin_ref'), true)) {
            $max = ($key === 'fin_ref') ? 3 : 5;
            $clean[$key] = repaf_sanitize_financial_rows($value, $max);
            if ($key === 'fin_ref') {
                foreach ($clean[$key] as &$reference) {
                    $parts = repaf_reference_name_parts($reference);
                    if ($parts !== null) {
                        $reference = array_merge($reference, $parts);
                        // Preserve the full-name source used by both PDF mappers.
                        $reference['name'] = implode(' ', array_filter(array(
                            $parts['first_name'], $parts['middle_name'],
                            $parts['last_name'], $parts['suffix'],
                        ), static function ($part) {
                            return $part !== '';
                        }));
                    }
                }
                unset($reference);
            }
            continue;
        }

        if (is_array($value)) {
            $clean[$key] = array_map(
                'sanitize_text_field',
                wp_unslash($value)
            );
            continue;
        }

        $value = wp_unslash($value);

        if (strpos($key, 'email') !== false) {
            $clean[$key] = sanitize_email($value);
            continue;
        }

        if (
            strpos($key, 'amount')      !== false ||
            strpos($key, 'income')      !== false ||
            strpos($key, 'age')         !== false ||
            strpos($key, 'years')       !== false ||
            strpos($key, 'dependents')  !== false ||
            strpos($key, '_salary')     !== false ||
            strpos($key, '_allowance')  !== false ||
            strpos($key, '_commission') !== false ||
            strpos($key, '_gross')      !== false ||
            strpos($key, '_net')        !== false ||
            (strpos($key, 'fin_') === 0 && strpos($key, '_total') !== false)
        ) {
            $numeric_value = trim(str_replace(',', '', (string) $value));
            $clean[$key] = ($numeric_value === '') ? '' : floatval($numeric_value);
            continue;
        }

        $clean[$key] = sanitize_text_field($value);
    }

    foreach (array('buyer', 'spouse', 'co', 'co_spouse') as $person_prefix) {
        $citizenship_key = $person_prefix . '_citizenship';
        $other_key       = $citizenship_key . '_other';

        if (($clean[$citizenship_key] ?? '') === 'Others') {
            $clean[$citizenship_key] = trim((string) ($clean[$other_key] ?? ''));
        }

        unset($clean[$other_key]);
    }

    /**
     * When explicitly selected, copy a married buyer's spouse into the
     * Attorney-in-Fact fields server-side. Otherwise preserve the independent
     * representative entered by the user.
     */
    $use_spouse_as_representative =
        ($clean['buyer_civil_status'] ?? '') === 'Married' &&
        ($clean['has_representative'] ?? '') === 'yes' &&
        ($clean['use_spouse_as_representative'] ?? '') === 'yes';

    if ($use_spouse_as_representative) {
        $spouse_representative_fields = array(
            'spouse_last_name'                  => 'rep_last_name',
            'spouse_first_name'                 => 'rep_first_name',
            'spouse_middle_name'                => 'rep_middle_name',
            'spouse_suffix'                     => 'rep_suffix',
            'spouse_dob'                        => 'rep_dob',
            'spouse_age'                        => 'rep_age',
            'spouse_home_tel'                   => 'rep_home_tel',
            'spouse_mobile'                     => 'rep_mobile',
            'spouse_email'                      => 'rep_email',
            'spouse_tin'                        => 'rep_tin',
            'buyer_permanent_address_line'      => 'rep_address_line',
            'buyer_permanent_barangay'          => 'rep_barangay',
            'buyer_permanent_city_municipality' => 'rep_city_municipality',
            'buyer_permanent_province_state'    => 'rep_province_state',
            'buyer_permanent_country'           => 'rep_country',
            'buyer_permanent_zip'               => 'rep_zip',
        );

        foreach ($spouse_representative_fields as $source => $target) {
            $clean[$target] = $clean[$source] ?? '';
        }

        $clean['rep_relationship'] = 'Spouse';
    } else {
        unset($clean['use_spouse_as_representative']);
    }

    /**
     * Co-owner spouse data applies only when the co-owner is married. Keep
     * the Attorney-in-Fact independent unless the user explicitly chooses
     * to copy the spouse information into that section.
     */
    if (($clean['has_co_owner'] ?? '') === 'yes') {
        $use_co_spouse_as_representative =
            ($clean['co_civil_status'] ?? '') === 'Married' &&
            ($clean['use_co_spouse_as_representative'] ?? '') === 'yes';

        if ($use_co_spouse_as_representative) {
            $co_spouse_representative_fields = array(
                'co_spouse_last_name'               => 'co_rep_last_name',
                'co_spouse_first_name'              => 'co_rep_first_name',
                'co_spouse_middle_name'             => 'co_rep_middle_name',
                'co_spouse_suffix'                  => 'co_rep_suffix',
                'co_spouse_dob'                     => 'co_rep_dob',
                'co_spouse_age'                     => 'co_rep_age',
                'co_spouse_home_office_number'      => 'co_rep_home_tel',
                'co_spouse_mobile'                  => 'co_rep_mobile',
                'co_spouse_email'                   => 'co_rep_email',
                'co_spouse_tin'                     => 'co_rep_tin',
                'co_permanent_address_line'         => 'co_rep_address_line',
                'co_permanent_barangay'             => 'co_rep_barangay',
                'co_permanent_city_municipality'    => 'co_rep_city_municipality',
                'co_permanent_province_state'       => 'co_rep_province_state',
                'co_permanent_country'              => 'co_rep_country',
                'co_permanent_zip'                  => 'co_rep_zip',
            );

            foreach ($co_spouse_representative_fields as $source => $target) {
                $clean[$target] = $clean[$source] ?? '';
            }

            $clean['co_rep_relationship'] = 'Spouse';
        } else {
            unset($clean['use_co_spouse_as_representative']);
        }

        if (($clean['co_civil_status'] ?? '') !== 'Married') {
            foreach (array_keys($clean) as $key) {
                if (strpos($key, 'co_spouse_') === 0) {
                    unset($clean[$key]);
                }
            }
        }
    } else {
        unset($clean['use_co_spouse_as_representative']);
    }

    /**
     * Strip representative fields when buyer declared no representative.
     * This also prevents hidden/stale values from affecting validation.
     */
    if (
        !isset($clean['has_representative']) ||
        $clean['has_representative'] !== 'yes'
    ) {
        foreach (array_keys($clean) as $key) {
            if (strpos($key, 'rep_') === 0) {
                unset($clean[$key]);
            }
        }
    }

    /**
     * Strip co-owner fields when buyer declared no co-owner.
     * Prevents stale co-owner data being saved to the DB.
     */
    if (
        !isset($clean['has_co_owner']) ||
        $clean['has_co_owner'] !== 'yes'
    ) {
        foreach (array_keys($clean) as $key) {
            if (
                strpos($key, 'co_') === 0 &&
                $key !== 'co_owner_role'
            ) {
                unset($clean[$key]);
            }
        }
    }

    // Keep only the fields that belong to the selected employment status.
    $employee_statuses = array(
        'Locally Employed - Private',
        'Locally Employed - Government',
        'OFW',
        'Others',
    );
    $work_prefixes = array('buyer');

    if (($clean['buyer_civil_status'] ?? '') === 'Married') {
        $work_prefixes[] = 'spouse';
    }
    if (($clean['has_co_owner'] ?? '') === 'yes') {
        $work_prefixes[] = 'co';
    }

    foreach ($work_prefixes as $prefix) {
        $occupation = $clean[$prefix . '_occupation'] ?? '';

        // Profession was removed from the current form. Clear any submitted or
        // legacy value so it cannot reappear in generated output.
        $clean[$prefix . '_profession'] = '';

        if ($occupation === 'Self Employed / Professional') {
            $clean[$prefix . '_years_employed'] = '';
            $clean[$prefix . '_employed_date'] = '';
            $clean[$prefix . '_position_department'] = '';
            $clean[$prefix . '_contract_status'] = '';
            $clean[$prefix . '_position_level'] = '';
        } elseif (in_array($occupation, $employee_statuses, true)) {
            $clean[$prefix . '_years_in_business']          = '';
            $clean[$prefix . '_business_type']             = '';
            $clean[$prefix . '_business_established_date'] = '';
        } else {
            foreach (array(
                'employer_name',
                'industry',
                'profession',
                'years_in_business',
                'years_employed',
                'employed_date',
                'position_department',
                'contract_status',
                'position_level',
                'business_address_line',
                'business_barangay',
                'business_city_municipality',
                'business_province_state',
                'business_country',
                'business_zip',
                'business_type',
                'business_established_date',
                'office_number',
                'employment_email',
                'employment_mobile',
            ) as $suffix) {
                $clean[$prefix . '_' . $suffix] = '';
            }
        }
    }

    return $clean;
}

/**
 * Sanitize a nested financial rows array (fin_bank, fin_loan, fin_ref).
 * Enforces a maximum row count and applies type-aware sanitizers per field.
 *
 * @param  mixed $rows  Raw nested array from $_POST.
 * @param  int   $max   Maximum allowed row index (3 for refs, 5 for others).
 * @return array        Sanitized nested array keyed by sequential 1-based index.
 */
function repaf_sanitize_financial_rows($rows, $max)
{
    if (!is_array($rows)) {
        return array();
    }

    $money_fields = array('balance', 'original', 'outstanding', 'amortization');
    $sanitized    = array();

    foreach ($rows as $index => $row) {
        if (!is_array($row)) {
            continue;
        }

        $n = absint($index);

        if ($n < 1 || $n > $max) {
            continue;
        }

        $clean_row = array();

        foreach ($row as $field => $val) {
            $field = sanitize_key($field);
            $val   = wp_unslash($val);

            if (strpos($field, 'email') !== false) {
                $clean_row[$field] = sanitize_email($val);
            } elseif (in_array($field, $money_fields, true)) {
                $numeric_value = trim(str_replace(',', '', (string) $val));
                $clean_row[$field] = ($numeric_value === '') ? '' : floatval($numeric_value);
            } else {
                $clean_row[$field] = sanitize_text_field($val);
            }
        }

        $sanitized[$n] = $clean_row;
    }

    return $sanitized;
}

/**
 * Resolve reference name parts without guessing from a legacy full name.
 * Explicit comma-separated legacy names use last, first, middle, suffix order.
 *
 * @param  array $reference Stored character reference.
 * @return array<string, string>|null Null means an unstructured legacy name.
 */
function repaf_reference_name_parts($reference)
{
    $keys = array('last_name', 'first_name', 'middle_name', 'suffix');
    $parts = array();
    foreach ($keys as $key) {
        $parts[$key] = trim((string) ($reference[$key] ?? ''));
    }

    if (implode('', $parts) !== '') {
        return $parts;
    }

    $legacy_name = trim((string) ($reference['name'] ?? ''));
    if ($legacy_name === '') {
        return $parts;
    }

    $legacy_parts = array_map('trim', explode(',', $legacy_name));
    if (count($legacy_parts) >= 2 && count($legacy_parts) <= 4) {
        return array_combine($keys, array_pad($legacy_parts, 4, ''));
    }

    return null;
}
