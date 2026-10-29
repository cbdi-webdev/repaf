<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * =========================================================
 * Field configuration
 * =========================================================
 *
 * Single source of truth for which fields are required
 * and which are internal system fields that should never
 * be stored as submission data.
 *
 * Both the server-side validator and the sanitizer read
 * from these definitions, so adding a new required field
 * means changing it in one place only.
 */

/**
 * Fields that must be present and non-empty for a
 * submission to be accepted.
 *
 * Key   = POST field name
 * Value = Human-readable label used in error messages
 *
 * @return array<string, string>
 */
function repaf_required_fields()
{
    return array_merge(array(
        'privacy_policy_consent'            => 'Privacy Policy consent',
        'data_privacy_consent'              => 'Data Privacy Policy consent',
        'buyer_last_name'                   => 'Buyer Last Name',
        'buyer_first_name'                  => 'Buyer First Name',
        'buyer_dob'                         => 'Buyer Date of Birth',
        'buyer_gender'                      => 'Buyer Gender',
        'buyer_civil_status'                => 'Buyer Civil Status',
        'buyer_citizenship'                 => 'Buyer Citizenship',
        'buyer_mobile'                      => 'Buyer Mobile Number',
        'buyer_email'                       => 'Buyer Email Address',
        'buyer_occupation'                  => 'Buyer Employment Status',
        'buyer_permanent_city_municipality' => 'Buyer Permanent Address City / Municipality',
        'buyer_permanent_province_state'    => 'Buyer Permanent Address Province / State',
        'buyer_permanent_country'           => 'Buyer Permanent Address Country',
        'has_representative'                => 'Authorized Representative / Attorney-In-Fact selection',
        'has_co_owner'                      => 'Co-Owner / Co-Borrower / Financier selection',
    ), repaf_required_buyer_address_fields());
}

/** Required buyer residential address lines. */
function repaf_required_buyer_address_fields()
{
    return array(
        'buyer_permanent_address_line' => 'Buyer Permanent Address Line',
        'buyer_present_address_line'   => 'Buyer Present Address Line',
    );
}

/**
 * Resolve independently selected discounts, including legacy single-choice data.
 * An explicit empty list takes precedence over a stale legacy selection.
 */
function repaf_property_discount_types($data)
{
    $allowed = array('Full TCP', 'Full DP', 'Others');
    if (array_key_exists('prop_discount_types', $data)) {
        $selected = is_array($data['prop_discount_types']) ? $data['prop_discount_types'] : array();
    } else {
        $selected = array($data['prop_discount_type'] ?? '');
    }

    $selected = array_map('trim', array_filter($selected, 'is_string'));
    return array_values(array_intersect($allowed, $selected));
}

/** Required property fields, completed only by a salesperson/admin. */
function repaf_required_property_fields()
{
    return array(
        'prop_reservation_date'       => 'Reservation Date',
        'prop_phase'                  => 'Phase',
        'prop_block'                  => 'Block',
        'prop_project'                => 'Project Name',
        'prop_lot'                    => 'Lot',
        'prop_payment_type'           => 'Payment Type',
        'prop_financing'              => 'Financing',
        'prop_dp_months'              => 'No. of Months to Pay',
        'prop_dp_starts_on'           => 'DP Starts On',
        'prop_loanable_amount'        => 'Loanable Amount',
        'prop_total_contract_price'   => 'Total Contract Price',
        'prop_inhouse_loan_terms'     => 'Loan Terms (years)',
        'prop_inhouse_interest_rate'  => 'Interest Rate / Indicative (%)',
    );
}

/**
 * Approved Nature of Business / Industry choices.
 *
 * @return array<int, string>
 */
function repaf_industry_options()
{
    return array(
        'Automotive',
        'Aviation',
        'Clothing & Fashion',
        'Education/Legal/Consultancy/Theological/NGOs',
        'Entertainment',
        'Financials/Banking/Insurance',
        'Food & Agriculture/Veterinary/Horticulture',
        'General Services',
        'Government',
        'IT/BPO',
        'Manufacturing',
        'Manufacturing & Industrial',
        'Media/Entertainment/Advertising/Events & Athletes',
        'Medical/Fitness/Health & Wellness',
        'Real Estate/Construction/Design',
        'Retail/Trading & Direct Selling',
        'Retirees/Pensioner/with Financier/Housewife',
        'Sea-based/Shipping/Freight',
        'Telecommunications',
        'Tourism & Hospitality (Restaurant, Hotels & Gaming)',
        'Trading',
        'Utilities/Mining/Energy',
    );
}

/**
 * Country choices used by every address country dropdown.
 *
 * The list is stored locally so the form does not depend on another API just
 * to render or submit an address. Philippines is kept first because it is the
 * primary address country for the form.
 *
 * @return array<int, string>
 */
function repaf_country_options()
{
    return array(
        'Philippines',
        'Afghanistan',
        'Albania',
        'Algeria',
        'Andorra',
        'Angola',
        'Antigua and Barbuda',
        'Argentina',
        'Armenia',
        'Australia',
        'Austria',
        'Azerbaijan',
        'Bahamas',
        'Bahrain',
        'Bangladesh',
        'Barbados',
        'Belarus',
        'Belgium',
        'Belize',
        'Benin',
        'Bhutan',
        'Bolivia',
        'Bosnia and Herzegovina',
        'Botswana',
        'Brazil',
        'Brunei',
        'Bulgaria',
        'Burkina Faso',
        'Burundi',
        'Cabo Verde',
        'Cambodia',
        'Cameroon',
        'Canada',
        'Central African Republic',
        'Chad',
        'Chile',
        'China',
        'Colombia',
        'Comoros',
        'Congo',
        'Costa Rica',
        "Cote d'Ivoire",
        'Croatia',
        'Cuba',
        'Cyprus',
        'Czechia',
        'Democratic Republic of the Congo',
        'Denmark',
        'Djibouti',
        'Dominica',
        'Dominican Republic',
        'Ecuador',
        'Egypt',
        'El Salvador',
        'Equatorial Guinea',
        'Eritrea',
        'Estonia',
        'Eswatini',
        'Ethiopia',
        'Fiji',
        'Finland',
        'France',
        'Gabon',
        'Gambia',
        'Georgia',
        'Germany',
        'Ghana',
        'Greece',
        'Grenada',
        'Guatemala',
        'Guinea',
        'Guinea-Bissau',
        'Guyana',
        'Haiti',
        'Holy See',
        'Honduras',
        'Hungary',
        'Iceland',
        'India',
        'Indonesia',
        'Iran',
        'Iraq',
        'Ireland',
        'Israel',
        'Italy',
        'Jamaica',
        'Japan',
        'Jordan',
        'Kazakhstan',
        'Kenya',
        'Kiribati',
        'Kuwait',
        'Kyrgyzstan',
        'Laos',
        'Latvia',
        'Lebanon',
        'Lesotho',
        'Liberia',
        'Libya',
        'Liechtenstein',
        'Lithuania',
        'Luxembourg',
        'Madagascar',
        'Malawi',
        'Malaysia',
        'Maldives',
        'Mali',
        'Malta',
        'Marshall Islands',
        'Mauritania',
        'Mauritius',
        'Mexico',
        'Micronesia',
        'Moldova',
        'Monaco',
        'Mongolia',
        'Montenegro',
        'Morocco',
        'Mozambique',
        'Myanmar',
        'Namibia',
        'Nauru',
        'Nepal',
        'Netherlands',
        'New Zealand',
        'Nicaragua',
        'Niger',
        'Nigeria',
        'North Korea',
        'North Macedonia',
        'Norway',
        'Oman',
        'Pakistan',
        'Palau',
        'Palestine',
        'Panama',
        'Papua New Guinea',
        'Paraguay',
        'Peru',
        'Poland',
        'Portugal',
        'Qatar',
        'Romania',
        'Russia',
        'Rwanda',
        'Saint Kitts and Nevis',
        'Saint Lucia',
        'Saint Vincent and the Grenadines',
        'Samoa',
        'San Marino',
        'Sao Tome and Principe',
        'Saudi Arabia',
        'Senegal',
        'Serbia',
        'Seychelles',
        'Sierra Leone',
        'Singapore',
        'Slovakia',
        'Slovenia',
        'Solomon Islands',
        'Somalia',
        'South Africa',
        'South Korea',
        'South Sudan',
        'Spain',
        'Sri Lanka',
        'Sudan',
        'Suriname',
        'Sweden',
        'Switzerland',
        'Syria',
        'Taiwan',
        'Tajikistan',
        'Tanzania',
        'Thailand',
        'Timor-Leste',
        'Togo',
        'Tonga',
        'Trinidad and Tobago',
        'Tunisia',
        'Turkey',
        'Turkmenistan',
        'Tuvalu',
        'Uganda',
        'Ukraine',
        'United Arab Emirates',
        'United Kingdom',
        'United States',
        'Uruguay',
        'Uzbekistan',
        'Vanuatu',
        'Venezuela',
        'Vietnam',
        'Yemen',
        'Zambia',
        'Zimbabwe',
    );
}

/**
 * Common citizenship choices. "Others" reveals a free-text field so every
 * citizenship remains supported without making the dropdown excessively long.
 *
 * @return array<int, string>
 */
function repaf_citizenship_options()
{
    return array(
        'Filipino',
        'American',
        'Australian',
        'British',
        'Canadian',
        'Chinese',
        'Indian',
        'Indonesian',
        'Japanese',
        'Korean',
        'Malaysian',
        'Singaporean',
        'Thai',
        'Vietnamese',
        'Others',
    );
}

/**
 * Approved Position / Level choices.
 *
 * @return array<int, string>
 */
function repaf_position_level_options()
{
    return array(
        'Rank and File/Staff/Clerk',
        'Supervisor/Team Lead',
        'Manager/Director',
        'Executive Officer',
        'Professional(Doctor, Lawyer, Engineer, Architect)',
    );
}

/**
 * Approved Employment Contract Status choices.
 *
 * @return array<int, string>
 */
function repaf_contract_status_options()
{
    return array(
        'Regular',
        'Contractual',
    );
}

/**
 * Approved Business / Company Type choices for self-employed buyers.
 *
 * @return array<int, string>
 */
function repaf_business_company_type_options()
{
    return array(
        'Single Proprietorship',
        'Partnership',
        'Corporation',
    );
}

/**
 * Common bank deposit and credit account types used by the financial section.
 */
function repaf_bank_account_type_options()
{
    return array(
        'Savings Account',
        'Current / Checking Account',
        'Time Deposit',
        'Payroll Account',
        'Credit Card',
        'Other',
    );
}

/**
 * Required work fields for one person based on the selected employment status.
 */
function repaf_work_required_fields($prefix, $label, $occupation)
{
    $required = array(
        $prefix . '_occupation' => $label . ' Employment Status',
    );

    if ($occupation === 'Self Employed / Professional') {
        return $required + array(
            $prefix . '_employer_name'                    => $label . ' Business Name',
            $prefix . '_industry'                         => $label . ' Industry',
            $prefix . '_business_type'                    => $label . ' Business Type',
            $prefix . '_business_established_date'        => $label . ' Date of Business Established',
        );
    }

    // "Others" still requires the salesperson/buyer to describe the selected
    // employment status, but none of the company-information fields should
    // block submission.
    if ($occupation === 'Others') {
        return $required + array(
            $prefix . '_occupation_other' => $label . ' Other Employment Status',
        );
    }

    if (in_array($occupation, array(
        'Locally Employed - Private',
        'Locally Employed - Government',
        'OFW',
    ), true)) {
        return $required + array(
            $prefix . '_employer_name'              => $label . ' Company Name',
            $prefix . '_industry'                   => $label . ' Industry / Type of Work',
            $prefix . '_position_department'        => $label . ' Position / Department',
            $prefix . '_contract_status'            => $label . ' Employment Contract Status',
            $prefix . '_position_level'             => $label . ' Position / Level',
        );
    }

    return $required;
}

/**
 * Required fields that only apply when the related person exists.
 *
 * @param  array $data Sanitized submission data.
 * @return array<string, string>
 */
function repaf_conditional_required_fields($data)
{
    $required = array();

    $required += repaf_work_required_fields(
        'buyer',
        'Buyer',
        $data['buyer_occupation'] ?? ''
    );

    if (($data['has_representative'] ?? '') === 'yes') {
        $required += array(
            'rep_mobile'                => 'Representative Mobile Number',
            'rep_email'                 => 'Representative Email Address',
            'rep_city_municipality'     => 'Representative Permanent Address City / Municipality',
            'rep_province_state'        => 'Representative Permanent Address Province / State',
            'rep_country'               => 'Representative Permanent Address Country',
        );
    }

    if (($data['has_co_owner'] ?? '') === 'yes') {
        $required += array(
            'co_citizenship'                => 'Co-Owner Citizenship',
            'co_mobile'                     => 'Co-Owner Mobile Number',
            'co_email'                      => 'Co-Owner Email Address',
            'co_permanent_city_municipality' => 'Co-Owner Permanent Address City / Municipality',
            'co_permanent_province_state'    => 'Co-Owner Permanent Address Province / State',
            'co_permanent_country'           => 'Co-Owner Permanent Address Country',
        );

        $required += repaf_work_required_fields(
            'co',
            'Co-Owner',
            $data['co_occupation'] ?? ''
        );

        // A name indicates that an optional co-owner representative exists.
        // This remains independent of the co-owner's civil status and spouse.
        if (
            trim((string) ($data['co_rep_last_name'] ?? '')) !== '' ||
            trim((string) ($data['co_rep_first_name'] ?? '')) !== ''
        ) {
            $required += array(
                'co_rep_city_municipality' => 'Co-Owner Representative Permanent Address City / Municipality',
                'co_rep_province_state'    => 'Co-Owner Representative Permanent Address Province / State',
                'co_rep_country'           => 'Co-Owner Representative Permanent Address Country',
            );
        }
    }

    if (($data['buyer_civil_status'] ?? '') === 'Married') {
        $required += array(
            'spouse_citizenship' => 'Spouse Citizenship',
            'spouse_mobile' => 'Spouse Mobile Number',
            'spouse_email'  => 'Spouse Email Address',
        );
        $required += repaf_work_required_fields(
            'spouse',
            'Spouse',
            $data['spouse_occupation'] ?? ''
        );
    }

    if (
        ($data['has_co_owner'] ?? '') === 'yes' &&
        ($data['co_civil_status'] ?? '') === 'Married'
    ) {
        $required['co_spouse_citizenship'] = 'Co-Owner Spouse Citizenship';
    }

    return $required;
}

/**
 * Internal WordPress / plugin fields that are present in
 * every POST but must never be saved as submission meta.
 *
 * @return array<int, string>
 */
function repaf_internal_fields()
{
    return array(
        'action',
        'repaf_nonce',
        'repaf_form_submitted',
        'repaf_website',
        'repaf_edit_id',
        'repaf_edit_nonce',
        'repaf_submission_id',
        'repaf_confirm_reservation',
        'prop_reservation_date', // Date picker only; storage uses day/month/year.
        '_wpnonce',
        '_wp_http_referer',
    );
}
