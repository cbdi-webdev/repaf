<?php

namespace Repaf;

/**
 * REPAF PDF Data Prep
 *
 * Normalizes raw submission data into the shape the PDF
 * drawing functions expect (mainly splitting dates into
 * separate month/day/year fields for the boxed-character
 * date fields on the templates).
 */

if (!defined('ABSPATH')) {
    exit;
}

function repaf_prepare_buyer_pdf_data($data)
{
    $data = repaf_prepare_pdf_date_parts($data, 'buyer_dob', 'buyer_birth');
    $data = repaf_prepare_pdf_date_parts($data, 'spouse_dob', 'spouse_birth');
    $data = repaf_prepare_pdf_date_parts($data, 'rep_dob', 'rep_birth');
    $data = repaf_prepare_pdf_date_parts(
        $data,
        'buyer_business_established_date',
        'buyer_business_established'
    );
    $data = repaf_prepare_pdf_date_parts($data, 'buyer_employed_date', 'buyer_employed');
    $data = repaf_prepare_pdf_date_parts(
        $data,
        'spouse_business_established_date',
        'spouse_business_established'
    );
    $data = repaf_prepare_pdf_date_parts($data, 'spouse_employed_date', 'spouse_employed');

    foreach (array('buyer', 'spouse') as $prefix) {
        $is_business = ($data[$prefix . '_occupation'] ?? '') === 'Self Employed / Professional';
        if ($is_business) {
            // Existing PDF artwork has one years field; use it for business
            // tenure when the person is self-employed.
            $data[$prefix . '_years_employed'] = $data[$prefix . '_years_in_business'] ?? '';
        }
        $data[$prefix . '_profession'] = '';
    }

    $representative_name = array_filter(
        array(
            $data['rep_first_name'] ?? '',
            $data['rep_middle_name'] ?? '',
            $data['rep_last_name'] ?? '',
            $data['rep_suffix'] ?? '',
        ),
        'strlen'
    );
    $data['rep_full_name'] = implode(' ', $representative_name);
    $data['buyer_permanent_address_line'] = repaf_format_pdf_address_line($data, 'buyer_permanent');
    $data['buyer_present_address_line'] = repaf_format_pdf_address_line($data, 'buyer_present');
    $data['rep_address_line'] = repaf_format_pdf_address_line($data, 'rep');

    return $data;
}

function repaf_prepare_coowner_pdf_data($data)
{
    $data = repaf_prepare_pdf_date_parts($data, 'co_dob', 'co_birth');
    $data = repaf_prepare_pdf_date_parts($data, 'co_spouse_dob', 'co_spouse_birth');
    $data = repaf_prepare_pdf_date_parts($data, 'co_rep_dob', 'co_rep_birth');
    $data = repaf_prepare_pdf_date_parts(
        $data,
        'co_business_established_date',
        'co_business_established'
    );
    $data = repaf_prepare_pdf_date_parts($data, 'co_employed_date', 'co_employed');
    if (($data['co_occupation'] ?? '') === 'Self Employed / Professional') {
        $data['co_years_employed'] = $data['co_years_in_business'] ?? '';
    }
    $data['co_profession'] = '';
    $data['co_permanent_address_line'] = repaf_format_pdf_address_line($data, 'co_permanent');
    $data['co_present_address_line'] = repaf_format_pdf_address_line($data, 'co_present');
    $data['co_rep_address_line'] = repaf_format_pdf_address_line($data, 'co_rep');

    return $data;
}

function repaf_format_pdf_address_line($data, $prefix)
{
    $address_line = trim((string) ($data[$prefix . '_address_line'] ?? ''));

    if ($address_line !== '') {
        return $address_line;
    }

    $parts = array();

    // Keep PDFs generated from older submissions readable.
    foreach (array('unit', 'block_lot', 'street', 'subdivision') as $suffix) {
        $value = trim((string) ($data[$prefix . '_' . $suffix] ?? ''));
        if ($value !== '') {
            $parts[] = $value;
        }
    }

    return implode('    ', $parts);
}

function repaf_prepare_pdf_date_parts($data, $source_key, $target_prefix)
{
    if (empty($data[$source_key])) {
        return $data;
    }

    $date_parts = repaf_parse_buyer_dob($data[$source_key]);

    if (empty($date_parts)) {
        return $data;
    }

    $data[$target_prefix . '_month'] = $date_parts['month'];
    $data[$target_prefix . '_day'] = $date_parts['day'];
    $data[$target_prefix . '_year'] = $date_parts['year'];

    return $data;
}

function repaf_parse_buyer_dob($dob)
{
    $dob = trim((string) $dob);

    if ($dob === '') {
        return array();
    }

    $formats = array(
        'Y-m-d',
        'm-d-Y',
        'm/d/Y',
        'Y/m/d',
    );

    foreach ($formats as $format) {
        $date = \DateTime::createFromFormat('!' . $format, $dob);
        $date_errors = \DateTime::getLastErrors();

        if (
            $date instanceof \DateTime &&
            (
                $date_errors === false ||
                (
                    $date_errors['warning_count'] === 0 &&
                    $date_errors['error_count'] === 0
                )
            )
        ) {
            return array(
                'month' => $date->format('m'),
                'day'   => $date->format('d'),
                'year'  => $date->format('Y'),
            );
        }
    }

    return array();
}
