<?php

namespace Repaf;

/**
 * REPAF Admin Submissions — Edit View
 *
 * Renders the edit screen and its two tabs (customer info,
 * property info). repaf_admin_handle_save_submission() in
 * admin-submissions-save.php processes the form this renders.
 */

if (!defined('ABSPATH')) {
    exit;
}

function repaf_admin_render_edit($post_id)
{
    $post = get_post($post_id);

    if (!$post || $post->post_type !== 'repaf_submission') {
        wp_die(__('Submission not found.'));
    }

    $raw_meta = get_post_meta($post_id);
    $meta     = array();

    foreach ($raw_meta as $key => $values) {
        $val        = isset($values[0]) ? $values[0] : '';
        $meta[$key] = maybe_unserialize($val);
    }

    $saved     = isset($_GET['saved']) ? absint($_GET['saved']) : 0;
    $csv_error = isset($_GET['csv_error']) ? absint($_GET['csv_error']) : 0;
    $batched   = isset($_GET['batched']) ? absint($_GET['batched']) : 0;
    $list_url  = add_query_arg(array('page' => 'repaf-submissions'), admin_url('admin.php'));
    $save_url  = admin_url('admin-post.php');

    echo '<div class="wrap repaf-admin-wrap">';
    echo '<h1>Edit Submission #' . absint($post_id) . '</h1>';

    if ($csv_error === 1) {
        echo '<div class="repaf-admin-notice repaf-admin-notice--error"><p>Submission saved, but the daily batch CSV could not be updated. Please save the submission again.</p></div>';
    } elseif ($saved === 1 && $batched === 1) {
        echo '<div class="repaf-admin-notice repaf-admin-notice--success"><p>Submission saved and included in its daily reservation CSV.</p></div>';
    } elseif ($saved === 1) {
        echo '<div class="repaf-admin-notice repaf-admin-notice--success"><p>Changes saved as a draft. No daily CSV record was added.</p></div>';
    }

    echo '<div class="repaf-admin-tabs" role="tablist">';
    echo '<button class="repaf-admin-tab is-active" data-tab="customer" role="tab" aria-selected="true">Customer Information</button>';
    echo '<button class="repaf-admin-tab" data-tab="property" role="tab" aria-selected="false">Property Information</button>';
    echo '<button class="repaf-admin-tab" data-tab="salesperson" role="tab" aria-selected="false">Salesperson</button>';
    echo '</div>';

    echo '<form method="post" action="' . esc_url($save_url) . '">';
    wp_nonce_field('repaf_save_submission_' . $post_id);
    echo '<input type="hidden" name="action" value="repaf_admin_save_submission">';
    echo '<input type="hidden" name="repaf_submission_id" value="' . absint($post_id) . '">';

    // Customer tab
    echo '<div class="repaf-admin-tab-panel is-active" data-panel="customer">';
    repaf_admin_render_customer_tab($post_id, $meta);
    echo '</div>';

    // Property tab
    echo '<div class="repaf-admin-tab-panel" data-panel="property">';
    repaf_admin_render_property_tab($post_id, $meta);
    echo '</div>';

    // Salesperson tab
    echo '<div class="repaf-admin-tab-panel" data-panel="salesperson">';
    repaf_admin_render_salesperson_tab($post_id, $meta);
    echo '</div>';

    $pdf_url = add_query_arg(array(
        'page'     => 'repaf-submissions',
        'action'   => 'generate_pdf',
        'id'       => $post_id,
        '_wpnonce' => wp_create_nonce('repaf_genpdf_' . $post_id),
    ), admin_url('admin.php'));

    echo '<div class="repaf-admin-form-footer">';
    if (($meta['reservation_confirmed'] ?? '') === 'yes') {
        $batch_date = (string) ($meta['repaf_batch_date'] ?? '');
        echo '<p><strong>Daily CSV:</strong> Already confirmed for batch ' . esc_html($batch_date) . '. Saving changes updates the existing row.</p>';
    } else {
        echo '<p><label><input type="checkbox" name="repaf_confirm_reservation" value="yes"> <strong>Confirm this as a real reservation and add it to the daily CSV.</strong></label><br><span class="description">Leave unchecked to save corrections without adding a batch record.</span></p>';
    }
    echo '<button type="submit" class="button button-primary">Save Changes</button> ';
    echo '<a href="' . esc_url($pdf_url) . '" class="button button-secondary">Generate PDF</a> ';
    echo '<a href="' . esc_url($list_url) . '" class="button">Back to List</a>';
    echo '</div>';

    echo '</form>';
    echo '</div>';
}

function repaf_admin_render_customer_tab($post_id, $meta)
{
    $m = function ($key) use ($meta) {
        $val = $meta[$key] ?? '';
        return is_array($val) ? '' : esc_attr((string) $val);
    };

    $required_addresses = repaf_required_buyer_address_fields();
    $text = function ($key, $label, $type = 'text') use ($m, $required_addresses) {
        $required = isset($required_addresses[$key]);
        echo '<div class="repaf-admin-field">';
        echo '<label>' . esc_html($label) . ($required ? ' <span class="repaf-required">*</span>' : '') . '</label>';
        echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($key) . '" value="' . $m($key) . '"' . ($required ? ' required aria-required="true"' : '') . '>';
        echo '</div>';
    };

    $money = function ($key, $label) use ($m) {
        $raw     = $m($key);
        $display = ($raw !== '' && is_numeric($raw)) ? number_format((float) $raw, 2) : $raw;
        echo '<div class="repaf-admin-field">';
        echo '<label>' . esc_html($label) . '</label>';
        echo '<input type="text" inputmode="decimal" class="repaf-money-input" name="' . esc_attr($key) . '" value="' . esc_attr($display) . '">';
        echo '</div>';
    };

    $select = function ($key, $label, $options) use ($m) {
        $current = $m($key);
        echo '<div class="repaf-admin-field">';
        echo '<label>' . esc_html($label) . '</label>';
        echo '<select name="' . esc_attr($key) . '">';
        echo '<option value="">— Select —</option>';
        foreach ($options as $val => $lbl) {
            $sel = ($current === esc_attr($val)) ? ' selected' : '';
            echo '<option value="' . esc_attr($val) . '"' . $sel . '>' . esc_html($lbl) . '</option>';
        }
        echo '</select>';
        echo '</div>';
    };

    $industry_options = array_combine(
        repaf_industry_options(),
        repaf_industry_options()
    );
    $position_level_options = array_combine(
        repaf_position_level_options(),
        repaf_position_level_options()
    );
    $contract_status_options = array_combine(
        repaf_contract_status_options(),
        repaf_contract_status_options()
    );
    $business_type_options = array_combine(
        repaf_business_company_type_options(),
        repaf_business_company_type_options()
    );

    // --- Personal Information ---
    echo '<div class="repaf-admin-field-group"><h3>Personal Information</h3>';
    $text('buyer_first_name',  'First Name');
    $text('buyer_middle_name', 'Middle Name');
    $text('buyer_last_name',   'Last Name');
    $text('buyer_suffix',      'Suffix');
    $text('buyer_dob',         'Date of Birth', 'date');
    $text('buyer_age',         'Age', 'number');
    $select('buyer_gender', 'Gender', array(
        'Male'   => 'Male',
        'Female' => 'Female',
    ));
    $select('buyer_civil_status', 'Civil Status', array(
        'Single'            => 'Single',
        'Married'           => 'Married',
        'Widow/er'          => 'Widow/er',
        'Annulled'          => 'Annulled',
        'Legally Separated' => 'Legally Separated',
    ));
    $text('buyer_citizenship', 'Citizenship');
    $text('buyer_tin',         'TIN');
    $text('buyer_pagibig_mid', "Pag-IBIG MID");
    $text('buyer_home_tel',    'Home Tel');
    $text('buyer_mobile',      'Mobile');
    $text('buyer_email',       'Email', 'email');
    echo '</div>';

    // --- Permanent Address ---
    echo '<div class="repaf-admin-field-group"><h3>Permanent Address</h3>';
    $text('buyer_permanent_address_line',      'Address Line');
    $text('buyer_permanent_barangay',          'Barangay');
    $text('buyer_permanent_city_municipality', 'City/Municipality');
    $text('buyer_permanent_province_state',    'Province/State');
    $text('buyer_permanent_country',           'Country');
    $text('buyer_permanent_zip',               'ZIP Code');
    echo '</div>';

    // --- Present Address ---
    echo '<div class="repaf-admin-field-group"><h3>Present Address</h3>';
    $text('buyer_present_address_line',      'Address Line');
    $text('buyer_present_barangay',          'Barangay');
    $text('buyer_present_city_municipality', 'City/Municipality');
    $text('buyer_present_province_state',    'Province/State');
    $text('buyer_present_country',           'Country');
    $text('buyer_present_zip',               'ZIP Code');
    echo '</div>';

    // --- Employment ---
    echo '<div class="repaf-admin-field-group"><h3>Employment</h3>';
    $select('buyer_home_ownership', 'Home Ownership', array(
        'Owned'                 => 'Owned',
        'Company'               => 'Company',
        'Living with Relatives' => 'Living with Relatives',
        'Mortgage'              => 'Mortgage',
        'Rented'                => 'Rented',
    ));
    $money('buyer_rent_amount', 'Rent Amount');
    $select('buyer_occupation', 'Occupation', array(
        'Locally Employed - Private'    => 'Locally Employed - Private',
        'Locally Employed - Government' => 'Locally Employed - Government',
        'Self Employed / Professional'  => 'Self Employed / Professional',
        'OFW'                           => 'OFW',
        'Others'                        => 'Others',
    ));
    $text('buyer_occupation_other', 'Occupation (Other)');
    $text('buyer_employer_name',      'Employer/Business Name');
    $select('buyer_business_type',     'Business/Company Type', $business_type_options);
    $text('buyer_business_established_date', 'Date of Business Established', 'date');
    $text('buyer_years_in_business',   'No. of Years in Business', 'number');
    $text('buyer_employed_date',       'Date Employed', 'date');
    $text('buyer_years_employed',      'No. of Years Employed', 'number');
    $select('buyer_industry',         'Industry', $industry_options);
    $text('buyer_position_department', 'Position / Department');
    $select('buyer_contract_status', 'Employment Contract Status', $contract_status_options);
    $select('buyer_position_level', 'Position / Level', $position_level_options);
    $text('buyer_business_address_line', 'Business Address Line');
    $text('buyer_business_barangay',  'Business Barangay');
    $text('buyer_business_country',   'Business Country');
    $text('buyer_business_province_state',    'Business Province/State');
    $text('buyer_business_city_municipality', 'Business City/Municipality');
    $text('buyer_business_zip',       'Business Zip Code');
    $text('buyer_office_number',      'Office Number', 'tel');
    $text('buyer_employment_email',   'Office Email Address', 'email');
    $text('buyer_employment_mobile',  'Office Mobile Number', 'tel');
    echo '</div>';

    // --- Spouse ---
    echo '<div class="repaf-admin-field-group"><h3>Spouse Information</h3>';
    $text('spouse_first_name',  'First Name');
    $text('spouse_middle_name', 'Middle Name');
    $text('spouse_last_name',   'Last Name');
    $text('spouse_suffix',      'Suffix');
    $text('spouse_dob',         'Date of Birth', 'date');
    $text('spouse_age',         'Age', 'number');
    $text('spouse_citizenship', 'Citizenship');
    $text('spouse_tin',         'TIN');
    $text('spouse_dependents',  'Dependents', 'number');
    $text('spouse_home_tel',    'Home Tel');
    $text('spouse_mobile',      'Mobile');
    $text('spouse_email',       'Email', 'email');
    $select('spouse_occupation', 'Occupation', array(
        'Locally Employed - Private'    => 'Locally Employed - Private',
        'Locally Employed - Government' => 'Locally Employed - Government',
        'Self Employed / Professional'  => 'Self Employed / Professional',
        'OFW'                           => 'OFW',
        'Others'                        => 'Others',
    ));
    $text('spouse_occupation_other', 'Occupation (Other)');
    $text('spouse_employer_name',    'Company / Business Name');
    $select('spouse_industry',        'Industry / Type of Work', $industry_options);
    $text('spouse_years_in_business', 'No. of Years in Business', 'number');
    $text('spouse_years_employed',    'No. of Years Employed', 'number');
    $text('spouse_position_department', 'Position / Department');
    $select('spouse_contract_status', 'Employment Contract Status', $contract_status_options);
    $select('spouse_position_level', 'Position / Level', $position_level_options);
    $text('spouse_employed_date',     'Date Employed', 'date');
    $select('spouse_business_type',   'Business Type', $business_type_options);
    $text('spouse_business_established_date', 'Date of Business Established', 'date');
    $text('spouse_business_address_line',      'Business Address Line');
    $text('spouse_business_barangay',          'Barangay');
    $text('spouse_business_city_municipality', 'City / Municipality');
    $text('spouse_business_province_state',    'Province / State');
    $text('spouse_business_country',           'Country');
    $text('spouse_business_zip',               'Zip Code');
    $text('spouse_office_number',              'Office Number', 'tel');
    $text('spouse_employment_email',           'Office Email Address', 'email');
    $text('spouse_employment_mobile',          'Office Mobile Number', 'tel');
    echo '</div>';

    // --- Co-Owner Personal ---
    echo '<div class="repaf-admin-field-group"><h3>Co-Owner / Co-Borrower</h3>';
    $select('co_owner_role', 'Role', array(
        'Co-owner'    => 'Co-owner',
        'Co-borrower' => 'Co-borrower',
        'Financier'   => 'Financier',
    ));
    $text('co_last_name',   'Last Name');
    $text('co_first_name',  'First Name');
    $text('co_middle_name', 'Middle Name');
    $text('co_suffix',      'Suffix');
    $text('co_dob',         'Date of Birth', 'date');
    $text('co_age',         'Age', 'number');
    $select('co_gender', 'Gender', array(
        'Male'   => 'Male',
        'Female' => 'Female',
    ));
    $select('co_civil_status', 'Civil Status', array(
        'Single'             => 'Single',
        'Married'            => 'Married',
        'Widow/er'           => 'Widow/er',
        'Annulled'           => 'Annulled',
        'Legally Separated'  => 'Legally Separated',
    ));
    $text('co_citizenship', 'Citizenship');
    $text('co_tin',         'TIN');
    $text('co_pagibig_mid', 'Pag-IBIG MID');
    $text('co_home_tel',    'Home Tel');
    $text('co_mobile',      'Mobile');
    $text('co_email',       'Email', 'email');
    $select('co_home_ownership', 'Home Ownership', array(
        'Owned'                 => 'Owned',
        'Company'               => 'Company',
        'Living with Relatives' => 'Living with Relatives',
        'Mortgage'              => 'Mortgage',
        'Rented'                => 'Rented',
    ));
    $money('co_rent_amount', 'Rent Amount');
    $select('co_occupation', 'Occupation', array(
        'Locally Employed - Private'    => 'Locally Employed - Private',
        'Locally Employed - Government' => 'Locally Employed - Government',
        'Self Employed / Professional'  => 'Self Employed / Professional',
        'OFW'                           => 'OFW',
        'Others'                        => 'Others',
    ));
    $text('co_occupation_other', 'Occupation (Other)');
    echo '</div>';

    // --- Co-Owner Permanent Address ---
    echo '<div class="repaf-admin-field-group"><h3>Co-Owner Permanent Address</h3>';
    $text('co_permanent_address_line',      'Address Line');
    $text('co_permanent_barangay',          'Barangay');
    $text('co_permanent_city_municipality', 'City / Municipality');
    $text('co_permanent_province_state',    'Province / State');
    $text('co_permanent_country',           'Country');
    $text('co_permanent_zip',               'ZIP Code');
    echo '</div>';

    // --- Co-Owner Present Address ---
    echo '<div class="repaf-admin-field-group"><h3>Co-Owner Present Address</h3>';
    $text('co_present_address_line',      'Address Line');
    $text('co_present_barangay',          'Barangay');
    $text('co_present_city_municipality', 'City / Municipality');
    $text('co_present_province_state',    'Province / State');
    $text('co_present_country',           'Country');
    $text('co_present_zip',               'ZIP Code');
    echo '</div>';

    // --- Co-Owner Employment ---
    echo '<div class="repaf-admin-field-group"><h3>Co-Owner Employment</h3>';
    $text('co_employer_name',              'Present Employer / Business Name');
    $select('co_business_type',             'Business/Company Type', $business_type_options);
    $text('co_business_established_date',   'Date of Business Established', 'date');
    $text('co_years_in_business',           'No. of Years in Business', 'number');
    $text('co_employed_date',               'Date Employed', 'date');
    $text('co_years_employed',              'No. of Years Employed', 'number');
    $select('co_industry',                 'Nature of Business / Industry', $industry_options);
    $text('co_position_department',        'Position / Department');
    $select('co_contract_status', 'Employment Contract Status', $contract_status_options);
    $select('co_position_level', 'Position / Level', $position_level_options);
    $text('co_business_address_line',      'Business Address Line');
    $text('co_business_barangay',          'Barangay');
    $text('co_business_country',           'Country');
    $text('co_business_province_state',    'Province / State');
    $text('co_business_city_municipality', 'City / Municipality');
    $text('co_business_zip',               'Zip Code');
    $text('co_office_number',              'Office Number', 'tel');
    $text('co_employment_email',           'Office Email Address', 'email');
    $text('co_employment_mobile',          'Office Mobile Number', 'tel');
    echo '</div>';

    // --- Co-Owner Spouse ---
    echo '<div class="repaf-admin-field-group"><h3>Co-Owner Spouse</h3>';
    $text('co_spouse_last_name',           'Last Name');
    $text('co_spouse_first_name',          'First Name');
    $text('co_spouse_middle_name',         'Middle Name');
    $text('co_spouse_suffix',              'Suffix');
    $text('co_spouse_citizenship',         'Citizenship');
    $text('co_spouse_dob',                 'Date of Birth', 'date');
    $text('co_spouse_age',                 'Age', 'number');
    $text('co_spouse_tin',                 'TIN');
    $text('co_spouse_dependents',          'No. of Dependents', 'number');
    $text('co_spouse_home_office_number',  'Home / Office Number');
    $text('co_spouse_mobile',              'Mobile Number');
    $text('co_spouse_email',               'Email', 'email');
    echo '</div>';

    // --- Co-Owner Representative ---
    echo '<div class="repaf-admin-field-group"><h3>Co-Owner Representative / Attorney-in-Fact</h3>';
    $text('co_rep_last_name',           'Last Name');
    $text('co_rep_first_name',          'First Name');
    $text('co_rep_middle_name',         'Middle Name');
    $text('co_rep_suffix',              'Suffix');
    $text('co_rep_address_line',        'Address Line');
    $text('co_rep_barangay',            'Barangay');
    $text('co_rep_city_municipality',   'City / Municipality');
    $text('co_rep_province_state',      'Province / State');
    $text('co_rep_country',             'Country');
    $text('co_rep_zip',                 'ZIP Code');
    $text('co_rep_dob',                 'Date of Birth', 'date');
    $text('co_rep_age',                 'Age', 'number');
    $text('co_rep_home_tel',            'Home Tel');
    $text('co_rep_mobile',              'Mobile');
    $text('co_rep_email',               'Email', 'email');
    $text('co_rep_tin',                 'TIN');
    $text('co_rep_relationship',        'Relationship to Buyer');
    echo '</div>';

    // --- Financial Income ---
    echo '<div class="repaf-admin-field-group"><h3>Monthly Income</h3>';
    $money('fin_buyer_salary',        'Buyer Salary');
    $money('fin_buyer_allowance',     'Buyer Allowance (COLA/PERA)');
    $money('fin_buyer_commission',    'Buyer Commission');
    $money('fin_buyer_other_amount',  'Buyer Other Income');
    $money('fin_spouse_salary',       'Spouse Salary');
    $money('fin_spouse_allowance',    'Spouse Allowance (COLA/PERA)');
    $money('fin_spouse_commission',   'Spouse Commission');
    $money('fin_spouse_other_amount', 'Spouse Other Income');
    $money('fin_co_salary',           'Co-Borrower Salary');
    $money('fin_co_allowance',        'Co-Borrower Allowance (COLA/PERA)');
    $money('fin_co_commission',       'Co-Borrower Commission');
    $money('fin_co_other_amount',     'Co-Borrower Other Income');

    foreach (array(
        'fin_buyer_total'      => 'Buyer Total Monthly Income (auto-calculated)',
        'fin_spouse_total'     => 'Spouse Total Monthly Income (auto-calculated)',
        'fin_co_total'         => 'Co-Borrower Total Monthly Income (auto-calculated)',
        'fin_total_salary'     => 'Total Salary (auto-calculated)',
        'fin_total_allowance'  => 'Total Allowance (auto-calculated)',
        'fin_total_commission' => 'Total Commission (auto-calculated)',
        'fin_total_other'      => 'Total Other Income (auto-calculated)',
    ) as $tot_key => $tot_label) {
        $tot = $m($tot_key);
        $tot_display = ($tot !== '' && is_numeric($tot)) ? number_format((float) $tot, 2) : $tot;
        echo '<div class="repaf-admin-field">';
        echo '<label>' . esc_html($tot_label) . '</label>';
        echo '<input type="text" class="repaf-admin-field--disabled" data-display-for="' . esc_attr($tot_key) . '" value="' . esc_attr($tot_display) . '" disabled>';
        echo '<input type="hidden" name="' . esc_attr($tot_key) . '" value="' . esc_attr($tot) . '">';
        echo '</div>';
    }
    echo '</div>';

    // --- Bank Accounts ---
    echo '<div class="repaf-admin-field-group"><h3>Bank / Credit Card Accounts</h3>';
    $bank_account_types = repaf_bank_account_type_options();
    for ($i = 1; $i <= 5; $i++) {
        $banks = $meta['fin_bank'] ?? array();
        $row   = (is_array($banks) && isset($banks[$i])) ? $banks[$i] : array();
        $saved_account_type = (string) ($row['type'] ?? '');
        echo '<div class="repaf-admin-dynamic-row"><strong>Account ' . $i . '</strong>';
        echo '<div class="repaf-admin-field"><label>Bank Name</label><input type="text" name="fin_bank[' . $i . '][name]" value="' . esc_attr($row['name'] ?? '') . '"></div>';
        echo '<div class="repaf-admin-field"><label>Account Type</label><select name="fin_bank[' . $i . '][type]">';
        echo '<option value="">Select account type</option>';
        foreach ($bank_account_types as $account_type) {
            echo '<option value="' . esc_attr($account_type) . '"' . selected($saved_account_type, $account_type, false) . '>' . esc_html($account_type) . '</option>';
        }
        if ($saved_account_type !== '' && !in_array($saved_account_type, $bank_account_types, true)) {
            echo '<option value="' . esc_attr($saved_account_type) . '" selected>' . esc_html($saved_account_type) . '</option>';
        }
        echo '</select></div>';
        echo '<div class="repaf-admin-field"><label>Date Opened</label><input type="date" name="fin_bank[' . $i . '][date_opened]" value="' . esc_attr($row['date_opened'] ?? '') . '"></div>';
        $bal = $row['balance'] ?? '';
        $bal_display = ($bal !== '' && is_numeric($bal)) ? number_format((float) $bal, 2) : $bal;
        echo '<div class="repaf-admin-field"><label>Balance</label><input type="text" inputmode="decimal" class="repaf-money-input" name="fin_bank[' . $i . '][balance]" value="' . esc_attr($bal_display) . '"></div>';
        echo '</div>';
    }
    echo '</div>';

    // --- Loans ---
    echo '<div class="repaf-admin-field-group"><h3>Loans</h3>';
    for ($i = 1; $i <= 5; $i++) {
        $loans = $meta['fin_loan'] ?? array();
        $row   = (is_array($loans) && isset($loans[$i])) ? $loans[$i] : array();
        echo '<div class="repaf-admin-dynamic-row"><strong>Loan ' . $i . '</strong>';
        echo '<div class="repaf-admin-field"><label>Type of Credit</label><input type="text" name="fin_loan[' . $i . '][type]" value="' . esc_attr($row['type'] ?? '') . '"></div>';
        echo '<div class="repaf-admin-field"><label>Bank/Financial Institution</label><input type="text" name="fin_loan[' . $i . '][bank]" value="' . esc_attr($row['bank'] ?? '') . '"></div>';
        echo '<div class="repaf-admin-field"><label>Contact Number</label><input type="text" name="fin_loan[' . $i . '][contact]" value="' . esc_attr($row['contact'] ?? '') . '"></div>';
        $orig = $row['original'] ?? '';
        $orig_d = ($orig !== '' && is_numeric($orig)) ? number_format((float) $orig, 2) : $orig;
        $outstanding = $row['outstanding'] ?? '';
        $outstanding_d = ($outstanding !== '' && is_numeric($outstanding)) ? number_format((float) $outstanding, 2) : $outstanding;
        $amort = $row['amortization'] ?? '';
        $amort_d = ($amort !== '' && is_numeric($amort)) ? number_format((float) $amort, 2) : $amort;
        echo '<div class="repaf-admin-field"><label>Original Amount</label><input type="text" inputmode="decimal" class="repaf-money-input" name="fin_loan[' . $i . '][original]" value="' . esc_attr($orig_d) . '"></div>';
        echo '<div class="repaf-admin-field"><label>Outstanding Balance</label><input type="text" inputmode="decimal" class="repaf-money-input" name="fin_loan[' . $i . '][outstanding]" value="' . esc_attr($outstanding_d) . '"></div>';
        echo '<div class="repaf-admin-field"><label>Monthly Amortization</label><input type="text" inputmode="decimal" class="repaf-money-input" name="fin_loan[' . $i . '][amortization]" value="' . esc_attr($amort_d) . '"></div>';
        echo '<div class="repaf-admin-field"><label>Collateral</label><input type="text" name="fin_loan[' . $i . '][collateral]" value="' . esc_attr($row['collateral'] ?? '') . '"></div>';
        echo '</div>';
    }
    echo '</div>';

    // --- Character References ---
    echo '<div class="repaf-admin-field-group"><h3>Character References</h3>';
    for ($i = 1; $i <= 3; $i++) {
        $refs = $meta['fin_ref'] ?? array();
        $row  = (is_array($refs) && isset($refs[$i])) ? $refs[$i] : array();
        $name_parts = repaf_reference_name_parts($row);
        echo '<div class="repaf-admin-dynamic-row"><strong>Reference ' . $i . '</strong>';
        echo '<input type="hidden" name="fin_ref[' . $i . '][name]" value="' . esc_attr($name_parts === null ? ($row['name'] ?? '') : '') . '">';
        if ($name_parts === null) {
            echo '<p>Previously saved name: ' . esc_html($row['name'] ?? '') . '. Enter its name parts below to update the CSV format.</p>';
        }
        foreach (array('last_name' => 'Last Name', 'first_name' => 'First Name', 'middle_name' => 'Middle Name', 'suffix' => 'Suffix') as $key => $label) {
            echo '<div class="repaf-admin-field"><label>' . esc_html($label) . '</label><input type="text" name="fin_ref[' . $i . '][' . esc_attr($key) . ']" value="' . esc_attr($name_parts[$key] ?? '') . '"></div>';
        }
        echo '<div class="repaf-admin-field"><label>Present Address</label><input type="text" name="fin_ref[' . $i . '][address]" value="' . esc_attr($row['address'] ?? '') . '"></div>';
        echo '<div class="repaf-admin-field"><label>Email</label><input type="email" name="fin_ref[' . $i . '][email]" value="' . esc_attr($row['email'] ?? '') . '"></div>';
        echo '<div class="repaf-admin-field"><label>Contact Number</label><input type="text" name="fin_ref[' . $i . '][contact]" value="' . esc_attr($row['contact'] ?? '') . '"></div>';
        echo '</div>';
    }
    echo '</div>';
}

function repaf_admin_render_property_tab($post_id, $meta)
{
    $m = function ($key) use ($meta) {
        $val = $meta[$key] ?? '';
        return is_array($val) ? '' : esc_attr((string) $val);
    };

    $required_fields = repaf_required_property_fields();
    $text = function ($key, $label, $type = 'text') use ($m, $required_fields) {
        $required = isset($required_fields[$key]);
        echo '<div class="repaf-admin-field">';
        echo '<label>' . esc_html($label) . ($required ? ' <span class="repaf-required">*</span>' : '') . '</label>';
        echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($key) . '" value="' . $m($key) . '"' . ($required ? ' required aria-required="true"' : '') . '>';
        echo '</div>';
    };

    $money = function ($key, $label) use ($m, $required_fields) {
        $required = isset($required_fields[$key]);
        $raw = $m($key);
        $display = ($raw !== '' && is_numeric($raw)) ? number_format((float) $raw, 2) : $raw;
        echo '<div class="repaf-admin-field">';
        echo '<label>' . esc_html($label) . ($required ? ' <span class="repaf-required">*</span>' : '') . '</label>';
        echo '<input type="text" inputmode="decimal" class="repaf-money-input" name="' . esc_attr($key) . '" value="' . esc_attr($display) . '"' . ($required ? ' required aria-required="true"' : '') . '>';
        echo '</div>';
    };

    $select = function ($key, $label, $options) use ($m, $required_fields) {
        $required = isset($required_fields[$key]);
        $current = $m($key);
        echo '<div class="repaf-admin-field">';
        echo '<label>' . esc_html($label) . ($required ? ' <span class="repaf-required">*</span>' : '') . '</label>';
        echo '<select name="' . esc_attr($key) . '"' . ($required ? ' required aria-required="true"' : '') . '>';
        echo '<option value="">— Select —</option>';
        foreach ($options as $val => $lbl) {
            $sel = ($current === esc_attr($val)) ? ' selected' : '';
            echo '<option value="' . esc_attr($val) . '"' . $sel . '>' . esc_html($lbl) . '</option>';
        }
        echo '</select>';
        echo '</div>';
    };

    // --- Property Information ---
    echo '<div class="repaf-admin-field-group"><h3>Property Information</h3>';
    $reservation_day   = $m('prop_reservation_day');
    $reservation_month = $m('prop_reservation_month');
    $reservation_year  = $m('prop_reservation_year');
    $reservation_date_value = '';
    if ($reservation_day !== '' && $reservation_month !== '' && $reservation_year !== '') {
        $reservation_date_value = sprintf('%04d-%02d-%02d', (int) $reservation_year, (int) $reservation_month, (int) $reservation_day);
    }

    echo '<div class="repaf-admin-field">';
    echo '<label>Reservation Date <span class="repaf-required">*</span></label>';
    echo '<input type="date" name="prop_reservation_date" data-reservation-date value="' . esc_attr($reservation_date_value) . '" required aria-required="true">';
    echo '<input type="hidden" name="prop_reservation_day"   value="' . esc_attr($reservation_day)   . '">';
    echo '<input type="hidden" name="prop_reservation_month" value="' . esc_attr($reservation_month) . '">';
    echo '<input type="hidden" name="prop_reservation_year"  value="' . esc_attr($reservation_year)  . '">';
    echo '</div>';
    $project_names   = repaf_get_project_names();
    $current_project = $m('prop_project');
    if ($current_project !== '' && !in_array($current_project, $project_names, true)) {
        array_unshift($project_names, $current_project);
    }
    $project_options = !empty($project_names)
        ? array_combine($project_names, $project_names)
        : array();
    $select('prop_project', 'Project Name', $project_options);

    $select('prop_house_finish', 'House Finish', array(
        'Basic'    => 'Basic',
        'Complete' => 'Complete',
        'Others'   => 'Others',
    ));
    echo '<div class="repaf-admin-field repaf-admin-conditional" data-show-when="prop_house_finish=Others">';
    echo '<label>House Finish (Others) <span class="repaf-required">*</span></label>';
    echo '<input type="text" name="prop_house_finish_other" value="' . $m('prop_house_finish_other') . '" required aria-required="true" data-required-when-visible>';
    echo '</div>';

    $select('prop_house_status', 'House Status', array(
        'RFO'     => 'RFO',
        'NRFO'    => 'NRFO',
        'Ongoing' => 'Ongoing',
    ));

    $select('prop_place_of_reservation', 'Place of Reservation', array(
        'On-Site'       => 'On-Site',
        'Branch Office' => 'Branch Office',
        'Head Office'   => 'Head Office',
        'Others'        => 'Others',
    ));
    echo '<div class="repaf-admin-field repaf-admin-conditional" data-show-when="prop_place_of_reservation=Others">';
    echo '<label>Place of Reservation (Others)</label>';
    echo '<input type="text" name="prop_place_other" value="' . $m('prop_place_other') . '">';
    echo '</div>';

    $select('prop_product_type', 'Product Type', array(
        'House & Lot' => 'House & Lot',
        'Lot Only'    => 'Lot Only',
        'Others'      => 'Others',
    ));
    echo '<div class="repaf-admin-field repaf-admin-conditional" data-show-when="prop_product_type=Others">';
    echo '<label>Product Type (Others)</label>';
    echo '<input type="text" name="prop_product_other" value="' . $m('prop_product_other') . '">';
    echo '</div>';

    $text('prop_phase',      'Phase');
    $text('prop_block',      'Block');
    $text('prop_lot',        'Lot');
    $text('prop_lot_area',   'Lot Area (sqm)', 'number');
    $text('prop_floor_area', 'Floor Area (sqm)', 'number');

    $select('prop_payment_type', 'Payment Type', array(
        'Spot Cash'      => 'Spot Cash',
        'Deferred Cash'  => 'Deferred Cash',
        'With Financing' => 'With Financing',
    ));

    $select('prop_financing', 'Financing', array(
        'Bank'     => 'Bank',
        'In-House' => 'In-House',
        'HDMF'     => 'HDMF',
        'Others'   => 'Others',
    ));
    echo '<input type="hidden" name="prop_financing" value="Others" data-cash-payment-financing disabled>';

    echo '</div>';

    // --- Mode of Payment ---
    echo '<div class="repaf-admin-field-group"><h3>Mode of Payment</h3>';
    $select('prop_mode_of_payment', 'Mode of Payment', array(
        'Cash'                    => 'Cash',
        'Post Dated Check'        => 'Post Dated Check',
        'Bills Payment or Online' => 'Bills Payment or Online',
        'Other'                   => 'Other',
    ));
    echo '<div class="repaf-admin-field repaf-admin-conditional" data-show-when="prop_mode_of_payment=Other">';
    echo '<label>Mode of Payment (Other)</label>';
    echo '<input type="text" name="prop_mode_other" value="' . $m('prop_mode_other') . '">';
    echo '</div>';
    echo '</div>';

    // --- Bank Charges (loan / financing details) ---
    echo '<div class="repaf-admin-field-group"><h3>Bank Charges</h3>';
    $money('prop_loan_amount',   'Total Amount Due');
    $text('prop_loan_term',      'Terms (years)', 'number');
    $money('prop_loan_monthly',  'Monthly Due');
    $text('prop_loan_starts_on', 'Starts On', 'date');
    $text('prop_loan_ends_on',   'Ends On', 'date');
    echo '</div>';

    // --- Insurance (conditional — In-House only) ---
    echo '<div class="repaf-admin-field-group repaf-admin-conditional" data-show-when="prop_financing=In-House"><h3>Insurance</h3>';
    $money('prop_mri',              'Mortgage Redemption Insurance (MRI)');
    $money('prop_fi',               'Fire Insurance (FI)');
    echo '</div>';

    // --- Computation ---
    echo '<div class="repaf-admin-field-group"><h3>Computation</h3>';
    $money('prop_total_list_price', 'Total List Price');

    // Less Discount on List Price
    echo '<div class="repaf-admin-field"><label>Less Discount on List Price</label>';
    $discount_types = repaf_property_discount_types($meta);
    echo '<input type="hidden" name="prop_discount_types[]" value="">';
    echo '<div style="margin-top:6px;display:flex;flex-direction:column;gap:8px;">';

    echo '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">';
    echo '<label style="font-weight:normal;display:flex;align-items:center;gap:4px;min-width:90px;"><input type="checkbox" name="prop_discount_types[]" value="Full TCP"' . (in_array('Full TCP', $discount_types, true) ? ' checked' : '') . '> Full TCP</label>';
    echo '<input type="number" name="prop_discount_tcp_percent" placeholder="%" style="width:60px" value="' . $m('prop_discount_tcp_percent') . '">';
    echo '<span>% discount &mdash; if Full TCP paid within</span>';
    echo '<input type="number" name="prop_discount_tcp_days" placeholder="days" style="width:60px" value="' . $m('prop_discount_tcp_days') . '">';
    echo '<span>days</span>';
    echo '<span>Amount (&#8369;)</span>';
    echo '<input type="text" inputmode="decimal" class="repaf-money-input" name="prop_discount_tcp_amount" style="width:140px" value="' . $m('prop_discount_tcp_amount') . '">';
    echo '</div>';

    echo '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">';
    echo '<label style="font-weight:normal;display:flex;align-items:center;gap:4px;min-width:90px;"><input type="checkbox" name="prop_discount_types[]" value="Full DP"' . (in_array('Full DP', $discount_types, true) ? ' checked' : '') . '> Full DP</label>';
    echo '<input type="number" name="prop_discount_dp_percent" placeholder="%" style="width:60px" value="' . $m('prop_discount_dp_percent') . '">';
    echo '<span>% discount &mdash; if Full DP paid within</span>';
    echo '<input type="number" name="prop_discount_dp_days" placeholder="days" style="width:60px" value="' . $m('prop_discount_dp_days') . '">';
    echo '<span>days</span>';
    echo '<span>Amount (&#8369;)</span>';
    echo '<input type="text" inputmode="decimal" class="repaf-money-input" name="prop_discount_dp_amount" style="width:140px" value="' . $m('prop_discount_dp_amount') . '">';
    echo '</div>';

    echo '<div style="display:flex;align-items:center;gap:8px;">';
    echo '<label style="font-weight:normal;display:flex;align-items:center;gap:4px;min-width:90px;"><input type="checkbox" name="prop_discount_types[]" value="Others"' . (in_array('Others', $discount_types, true) ? ' checked' : '') . '> Others</label>';
    echo '<input type="text" name="prop_discount_others" style="flex:1" value="' . $m('prop_discount_others') . '">';
    echo '<span>Amount (&#8369;)</span>';
    echo '<input type="text" inputmode="decimal" class="repaf-money-input" name="prop_discount_others_amount" style="width:140px" value="' . $m('prop_discount_others_amount') . '">';
    echo '</div>';

    echo '</div></div>';

    $money('prop_total_selling_price',  'Total Selling Price');
    $text('prop_misc_fee_percent',      'Misc. Fee (%)', 'number');
    $raw_misc_fee = $m('prop_misc_fee');
    $misc_fee_display = ($raw_misc_fee !== '' && is_numeric($raw_misc_fee)) ? number_format((float) $raw_misc_fee, 2) : $raw_misc_fee;
    echo '<div class="repaf-admin-field">';
    echo '<label>Miscellaneous Fee (&#8369;) — auto-computed</label>';
    echo '<input type="text" inputmode="decimal" class="repaf-money-input" name="prop_misc_fee" value="' . esc_attr($misc_fee_display) . '" readonly>';
    echo '</div>';
    $is_vatable = $m('prop_is_vatable') === 'yes';
    echo '<div class="repaf-admin-field">';
    echo '<label>VATable?</label>';
    echo '<input type="hidden" name="prop_is_vatable" value="no">';
    echo '<label style="font-weight:normal;display:flex;align-items:center;gap:6px;"><input type="checkbox" name="prop_is_vatable" value="yes" data-vat-toggle' . ($is_vatable ? ' checked' : '') . '> Yes, apply VAT</label>';
    echo '</div>';
    echo '<div class="repaf-admin-field">';
    echo '<label>VAT (%)</label>';
    echo '<input type="number" name="prop_vat_percent" value="' . $m('prop_vat_percent') . '" min="0" max="100" step="0.01" placeholder="Enter VAT %"' . ($is_vatable ? ' required aria-required="true"' : ' disabled') . '>';
    echo '</div>';
    $raw_vat = $m('prop_vat');
    $vat_display = ($raw_vat !== '' && is_numeric($raw_vat)) ? number_format((float) $raw_vat, 2) : $raw_vat;
    echo '<div class="repaf-admin-field">';
    echo '<label>Value Added Tax (&#8369;) — auto-computed from VAT %</label>';
    echo '<input type="text" inputmode="decimal" class="repaf-money-input" name="prop_vat" value="' . esc_attr($vat_display) . '" readonly>';
    echo '</div>';

    $money('prop_total_contract_price', 'Total Contract Price');

    echo '<div class="repaf-admin-field">';
    echo '<label>Required Down Payment % (auto-calculated)</label>';
    echo '<input type="text" class="repaf-admin-field--disabled" data-display-for="prop_dp_percent" value="' . $m('prop_dp_percent') . '" disabled>';
    echo '<input type="hidden" name="prop_dp_percent" value="' . $m('prop_dp_percent') . '">';
    echo '</div>';

    echo '<div class="repaf-admin-field">';
    echo '<label>Down Payment Amount (auto-calculated)</label>';
    echo '<input type="text" class="repaf-admin-field--disabled" data-display-for="prop_dp_amount" value="' . esc_attr(is_numeric($m('prop_dp_amount')) && $m('prop_dp_amount') !== '' ? number_format((float) $m('prop_dp_amount'), 2) : $m('prop_dp_amount')) . '" disabled>';
    echo '<input type="hidden" name="prop_dp_amount" value="' . $m('prop_dp_amount') . '">';
    echo '</div>';

    $money('prop_reservation_fee', 'Reservation Fee');

    echo '<div class="repaf-admin-field">';
    echo '<label>Balance on DP (auto-calculated)</label>';
    echo '<input type="text" class="repaf-admin-field--disabled" data-display-for="prop_balance_on_dp" value="' . esc_attr(is_numeric($m('prop_balance_on_dp')) && $m('prop_balance_on_dp') !== '' ? number_format((float) $m('prop_balance_on_dp'), 2) : $m('prop_balance_on_dp')) . '" disabled>';
    echo '<input type="hidden" name="prop_balance_on_dp" value="' . $m('prop_balance_on_dp') . '">';
    echo '</div>';

    $text('prop_dp_months',            'No. of Months to Pay', 'number');
    $money('prop_dp_monthly',           'Monthly DP Amount');
    $text('prop_dp_starts_on',         'DP Starts On', 'date');

    echo '<div class="repaf-admin-field">';
    echo '<label>DP Ends On (auto-calculated)</label>';
    echo '<input type="date" class="repaf-admin-field--disabled" data-display-for="prop_dp_ends_on" value="' . $m('prop_dp_ends_on') . '" disabled>';
    echo '<input type="hidden" name="prop_dp_ends_on" value="' . $m('prop_dp_ends_on') . '">';
    echo '</div>';
    echo '<div class="repaf-admin-field">';
    echo '<label>% Balance / Loanable Amount (auto-calculated)</label>';
    echo '<input type="text" class="repaf-admin-field--disabled" data-display-for="prop_loan_percent" value="' . $m('prop_loan_percent') . '" disabled>';
    echo '<input type="hidden" name="prop_loan_percent" value="' . $m('prop_loan_percent') . '">';
    echo '</div>';

    $money('prop_loanable_amount', 'Loanable Amount');

    $text('prop_loan_interest',        'Interest Rate % (indicative)', 'number');
    echo '</div>';

    // --- In-House Financing (always visible, at bottom) ---
    echo '<div class="repaf-admin-field-group"><h3>In-House Financing</h3>';
    $raw_monthly_amort = $m('prop_inhouse_monthly_amort');
    $monthly_amort_display = ($raw_monthly_amort !== '' && is_numeric($raw_monthly_amort))
        ? number_format(round((float) $raw_monthly_amort), 2)
        : $raw_monthly_amort;
    echo '<div class="repaf-admin-field">';
    echo '<label>Monthly Amortization Amount (auto-calculated)</label>';
    echo '<input type="text" inputmode="decimal" class="repaf-money-input" data-whole-money name="prop_inhouse_monthly_amort" value="' . esc_attr($monthly_amort_display) . '" readonly>';
    echo '</div>';
    $text('prop_inhouse_loan_terms',      'Loan Terms (years)', 'number');
    $text('prop_inhouse_interest_rate',   'Interest Rate / Indicative (%)', 'number');
    $text('prop_inhouse_starts_on',       'Starts On', 'date');
    echo '</div>';
}

function repaf_admin_render_salesperson_tab($post_id, $meta)
{
    $m = function ($key) use ($meta) {
        $val = $meta[$key] ?? '';
        return is_array($val) ? '' : esc_attr((string) $val);
    };

    $text = function ($key, $label) use ($m) {
        echo '<div class="repaf-admin-field">';
        echo '<label>' . esc_html($label) . '</label>';
        echo '<input type="text" name="' . esc_attr($key) . '" value="' . $m($key) . '">';
        echo '</div>';
    };

    // --- Signatories ---
    echo '<div class="repaf-admin-field-group"><h3>Signatories</h3>';
    $text('sp_agent_name',   'Real Estate Sales Person (Agent)');
    $text('sp_manager_name', 'Manager');
    $text('sp_broker_name',  'Broker');
    $text('sp_realty_name',  'Realty');
    $text('sp_prepared_by',  'Prepared & Interviewed By - Sales Operations Assistant');
    $text('sp_verified_by',  'Verified By - Sales Operations Head');
    $text('sp_checked_by',   'Checked By - Reservation & Documentation Assistant');
    $text('sp_approved_by',  'Approved By - Reservation & Documentation Head');
    echo '</div>';

    // --- Remarks ---
    echo '<div class="repaf-admin-field-group"><h3>Remarks (FSD)</h3>';
    echo '<div class="repaf-admin-field">';
    echo '<label>Remarks</label>';
    echo '<textarea name="sp_remarks" rows="4" style="flex:1;max-width:420px;padding:6px 10px;border:1px solid #8c8f94;border-radius:3px;font-size:13px;resize:vertical;">' . esc_textarea($meta['sp_remarks'] ?? '') . '</textarea>';
    echo '</div>';
    echo '</div>';
}
