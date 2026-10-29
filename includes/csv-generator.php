<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Build the WordPress post title for a submission.
 *
 * @param  array $data Sanitized submission data.
 * @return string
 */
function repaf_build_submission_title($data)
{
    $buyer_name = trim(
        ($data['buyer_first_name'] ?? '') .
        ' ' .
        ($data['buyer_last_name'] ?? '')
    );

    if ($buyer_name === '') {
        $buyer_name = 'Unknown Buyer';
    }

    return sprintf('REPAF - %s - %s', $buyer_name, current_time('mysql'));
}

/**
 * Neutralize spreadsheet formulas before writing user-controlled CSV cells.
 *
 * @param  mixed $value CSV cell value.
 * @return mixed
 */
function repaf_escape_csv_cell($value)
{
    if (!is_scalar($value) || $value === '') {
        return $value;
    }

    $value = (string) $value;

    if (preg_match('/^[=+\-@\t\r]/', $value)) {
        return "'" . $value;
    }

    return $value;
}

/**
 * Return the ordered CIS import columns and their REPAF data sources.
 *
 * The labels and order follow the supplied CIS field list. A null source is
 * intentional: the CIS field remains in the file, but its value stays blank
 * because the current REPAF form has no corresponding input. Duplicate CIS
 * descriptions are preserved because position identifies their sections.
 *
 * @return array<int, array<string, mixed>>
 */
function repaf_daily_batch_cis_columns()
{
    $buyer_name = array('buyer_last_name', 'buyer_first_name', 'buyer_middle_name', 'buyer_suffix');
    $co_name    = array('co_last_name', 'co_first_name', 'co_middle_name', 'co_suffix');
    $rep_name   = array('rep_last_name', 'rep_first_name', 'rep_middle_name', 'rep_suffix');
    $spouse_name = array('spouse_last_name', 'spouse_first_name', 'spouse_middle_name', 'spouse_suffix');

    $columns = array(
        array('header' => 'Project', 'field' => 'prop_project'),
        array('header' => 'Account', 'field' => null),
        array('header' => 'Phase', 'field' => 'prop_phase'),
        array('header' => 'Block', 'field' => 'prop_block'),
        array('header' => 'Lot', 'field' => 'prop_lot'),
        array('header' => 'PCHS#', 'field' => null),
        array('header' => 'House Model', 'field' => 'prop_house_finish'),
        array('header' => 'Lot Area', 'field' => 'prop_lot_area'),
        array('header' => 'Floor Area', 'field' => 'prop_floor_area'),
        array('header' => 'Account Name', 'name_fields' => $buyer_name),
        array('header' => 'Account Officer', 'field' => 'sp_agent_name'),
        array('header' => 'Lastname, Firstname, Middlename, Suffix', 'name_fields' => $buyer_name),
        array('header' => 'Home Address', 'field' => 'buyer_permanent_address_line'),
        array('header' => 'Home Address Country', 'field' => 'buyer_permanent_country'),
        array('header' => 'Home Address City', 'field' => 'buyer_permanent_city_municipality'),
        array('header' => 'Town', 'field' => 'buyer_permanent_barangay'),
        array('header' => 'Zip Code', 'field' => 'buyer_permanent_zip'),
        array('header' => 'OwnerShip', 'field' => 'buyer_home_ownership'),
        array('header' => 'Yrs of Stay', 'field' => null),
        array('header' => 'Birthdate', 'field' => 'buyer_dob'),
        array('header' => 'Gender', 'field' => 'buyer_gender'),
        array('header' => 'Civil Status', 'field' => 'buyer_civil_status'),
        array('header' => 'Email Address', 'field' => 'buyer_email'),
        array('header' => 'Citizenship', 'field' => 'buyer_citizenship'),
        array('header' => 'Mailing Address', 'field' => 'buyer_present_address_line'),
        array('header' => 'Country', 'field' => 'buyer_present_country'),
        array('header' => 'Mailing Adress City', 'field' => 'buyer_present_city_municipality'),
        array('header' => 'Mailing Address Town', 'field' => 'buyer_present_barangay'),
        array('header' => 'Employer', 'field' => 'buyer_employer_name'),
        array('header' => 'Employer Address', 'field' => 'buyer_business_address_line'),
        array('header' => 'Employer Country', 'field' => 'buyer_business_country'),
        array('header' => 'Employer City', 'field' => 'buyer_business_city_municipality'),
        array('header' => 'Position', 'field' => 'buyer_position_department'),
        array('header' => 'Employment Type', 'field' => 'buyer_occupation'),
        array('header' => 'Personal Contact', 'field' => 'buyer_mobile'),
        array('header' => 'Pagibig No', 'field' => 'buyer_pagibig_mid'),
        array('header' => 'Employer Contact', 'field' => null),
        array('header' => 'Years of stay / Current Employer', 'field' => 'buyer_employed_date'),
        array('header' => 'Membership GSIS/SSS', 'field' => null),
        array('header' => 'SSS/GSIS', 'field' => null),
        array('header' => 'Housing Availment', 'field' => null),
        array('header' => 'TIN', 'field' => 'buyer_tin'),
        array('header' => 'Passport #', 'field' => null),
        array('header' => 'Passport Validity', 'field' => null),
        array('header' => 'Commisionable Account For', 'field' => 'sp_broker_name'),
        array('header' => 'SOO Account', 'field' => null),
        array('header' => 'Financial Info', 'field' => 'fin_buyer_total'),
    );

    $co_sources = array(
        'name'                      => array('name_fields' => $co_name),
        'role'                      => array('field' => 'co_owner_role'),
        'home_address'              => array('field' => 'co_permanent_address_line'),
        'country'                   => array('field' => 'co_permanent_country'),
        'home_address_city'         => array('field' => 'co_permanent_city_municipality'),
        'town'                      => array('field' => 'co_permanent_barangay'),
        'zip_code'                  => array('field' => 'co_permanent_zip'),
        'home_phone'                => array('field' => 'co_home_tel'),
        'mobile_number'             => array('field' => 'co_mobile'),
        'citizenship'               => array('field' => 'co_citizenship'),
        'birthdate'                 => array('field' => 'co_dob'),
        'age'                       => array('field' => 'co_age'),
        'civil_status'              => array('field' => 'co_civil_status'),
        'gender'                    => array('field' => 'co_gender'),
        'email_address'             => array('field' => 'co_email'),
        'tin'                       => array('field' => 'co_tin'),
        'hdmf_id'                   => array('field' => 'co_pagibig_mid'),
        'employer'                  => array('field' => 'co_employer_name'),
        'employment_type'           => array('field' => 'co_occupation'),
        'occupation'                => array('field' => 'co_position_department'),
        'office_number'             => array('field' => 'co_office_number'),
        'years_at_current_employer' => array(
            'first_nonempty_fields' => array('co_years_employed', 'co_years_in_business'),
        ),
        'office_address'            => array('field' => 'co_business_address_line'),
        'office_country'            => array('field' => 'co_business_country'),
        'office_city'               => array('field' => 'co_business_city_municipality'),
        'office_town'               => array('field' => 'co_business_barangay'),
        'office_zip_code'           => array('field' => 'co_business_zip'),
        'mailing_address'           => array('field' => 'co_present_address_line'),
        'mailing_country'           => array('field' => 'co_present_country'),
        'mailing_city'              => array('field' => 'co_present_city_municipality'),
        'mailing_town'              => array('field' => 'co_present_barangay'),
        'mailing_zip_code'          => array('field' => 'co_present_zip'),
    );

    $attorney_sources = array(
        'name'                  => array('name_fields' => $rep_name),
        'relationship_to_buyer' => array('field' => 'rep_relationship'),
        'home_address'          => array('field' => 'rep_address_line'),
        'country'               => array('field' => 'rep_country'),
        'home_address_city'     => array('field' => 'rep_city_municipality'),
        'town'                  => array('field' => 'rep_barangay'),
        'zip_code'              => array('field' => 'rep_zip'),
        'home_phone'            => array('field' => 'rep_home_tel'),
        'mobile_number'         => array('field' => 'rep_mobile'),
        'birthdate'             => array('field' => 'rep_dob'),
        'age'                   => array('field' => 'rep_age'),
        'email_address'         => array('field' => 'rep_email'),
        'tin'                   => array('field' => 'rep_tin'),
    );

    $spouse_sources = array(
        'name'          => array('name_fields' => $spouse_name),
        'home_phone'    => array('field' => 'spouse_home_tel'),
        'mobile_number' => array('field' => 'spouse_mobile'),
        'citizenship'   => array('field' => 'spouse_citizenship'),
        'birthdate'     => array('field' => 'spouse_dob'),
        'age'           => array('field' => 'spouse_age'),
        'number_of_dependents' => array('field' => 'spouse_dependents'),
        'email_address' => array('field' => 'spouse_email'),
        'tin'           => array('field' => 'spouse_tin'),
    );

    $columns = array_merge(
        $columns,
        repaf_daily_batch_person_columns('co_borrower', $co_sources),
        repaf_daily_batch_person_columns('attorney_in_fact', $attorney_sources),
        repaf_daily_batch_person_columns('spouse', $spouse_sources)
    );

    for ($reference = 1; $reference <= 3; $reference++) {
        $prefix = 'character_reference_' . $reference . '_';
        $columns[] = array('header' => $prefix . 'name', 'reference_name_path' => array('fin_ref', $reference));
        $columns[] = array('header' => $prefix . 'phone_number', 'path' => array('fin_ref', $reference, 'contact'));
        $columns[] = array('header' => $prefix . 'email_address', 'path' => array('fin_ref', $reference, 'email'));
        $columns[] = array('header' => $prefix . 'present_address', 'path' => array('fin_ref', $reference, 'address'));
    }

    // Append payment details without moving existing CIS/person columns.
    $columns = array_merge($columns, array(
        array('header' => 'Financing Scheme', 'financing_scheme' => true),
        array('header' => 'Reservation Date', 'date_fields' => array('prop_reservation_year', 'prop_reservation_month', 'prop_reservation_day')),
        array('header' => 'Reservation Fee', 'field' => 'prop_reservation_fee'),
        array('header' => 'DP Terms', 'field' => 'prop_dp_months'),
        array('header' => 'Interest Rate', 'first_nonempty_fields' => array('prop_inhouse_interest_rate', 'prop_loan_interest')),
        array('header' => 'Down Payment', 'field' => 'prop_dp_amount'),
        array('header' => 'First Down Payment', 'field' => 'prop_dp_starts_on'),
        array('header' => 'Loanable Amount', 'field' => 'prop_loanable_amount'),
        array('header' => 'Total Contract Price', 'field' => 'prop_total_contract_price'),
        array('header' => 'VAT', 'field' => 'prop_vat'),
        array('header' => 'Miscellaneous Fee', 'field' => 'prop_misc_fee'),
        array('header' => 'tcp_discount', 'field' => 'prop_discount_tcp_amount'),
        array('header' => 'dp_discount', 'field' => 'prop_discount_dp_amount'),
        array('header' => 'others_discount', 'field' => 'prop_discount_others_amount'),
    ));

    // Keep the internal REPAF record identifier as the final import field.
    $columns[] = array('header' => 'reservation_id', 'post_id' => true);

    return $columns;
}

/**
 * Return one consistently ordered, uniquely named CIS person block.
 * Missing sources intentionally remain blank for that person.
 *
 * @param  string                     $prefix  CSV header prefix.
 * @param  array<string, array>       $sources REPAF source definitions.
 * @return array<int, array<string, mixed>>
 */
function repaf_daily_batch_person_columns($prefix, $sources)
{
    $field_order = array(
        'name', 'role', 'relationship_to_buyer', 'home_address', 'country',
        'home_address_city', 'town',
        'zip_code', 'home_phone', 'mobile_number', 'years_of_stay',
        'citizenship', 'birthdate', 'age', 'civil_status', 'gender',
        'number_of_dependents', 'email_address', 'membership_sss_gsis', 'tin',
        'hdmf_id', 'employer', 'employment_type',
        'occupation', 'office_number', 'years_at_current_employer',
        'office_address', 'office_country', 'office_city', 'office_town',
        'office_zip_code', 'mailing_address', 'mailing_country', 'mailing_city',
        'mailing_town', 'mailing_zip_code',
    );
    $columns = array();

    foreach ($field_order as $field_name) {
        $column = array('header' => $prefix . '_' . $field_name, 'field' => null);

        if (isset($sources[$field_name])) {
            $column = array_merge($column, $sources[$field_name]);
        }

        $columns[] = $column;
    }

    return $columns;
}

/**
 * Return all daily batch headers in the supplied CIS import order.
 *
 * @return array<int, string>
 */
function repaf_daily_batch_headers()
{
    return array_map(
        static function ($column) {
            return (string) $column['header'];
        },
        repaf_daily_batch_cis_columns()
    );
}

/**
 * Format one person's name for CIS imports.
 *
 * @param  array<int, string>    $fields Ordered last, first, middle, suffix keys.
 * @param  array<string, mixed> $meta   Stored submission values.
 * @return string
 */
function repaf_daily_batch_format_name($fields, $meta)
{
    $parts = array();

    foreach ($fields as $field) {
        $parts[] = trim((string) ($meta[$field] ?? ''));
    }

    if (implode('', $parts) === '') {
        return '';
    }

    // Always preserve the four fixed positions: last, first, middle, suffix.
    return implode(', ', $parts);
}

/**
 * Map the saved payment type and financing selection to the import scheme ID.
 * Cash selections take precedence over any stale financing value.
 * Missing or unsupported selections remain blank because no ID was assigned.
 *
 * @param  array<string, mixed> $meta Stored submission values.
 * @return string Financing scheme ID or blank.
 */
function repaf_daily_batch_financing_scheme($meta)
{
    $payment_type = strtoupper(trim((string) ($meta['prop_payment_type'] ?? '')));

    if ($payment_type === 'SPOT CASH') {
        return '4';
    }
    if ($payment_type === 'DEFERRED CASH') {
        return '16';
    }
    if ($payment_type !== 'WITH FINANCING') {
        return '';
    }

    $financing = strtoupper(trim((string) ($meta['prop_financing'] ?? '')));
    $scheme_ids = array('BANK' => '3', 'IN-HOUSE' => '8', 'HDMF' => '10');

    return $scheme_ids[$financing] ?? '';
}

/**
 * Resolve a nested stored value such as fin_ref -> row -> field.
 *
 * @param  array<string, mixed> $meta Stored submission values.
 * @param  array<int, mixed>    $path Nested array path.
 * @return mixed
 */
function repaf_daily_batch_nested_value($meta, $path)
{
    $value = $meta;

    foreach ($path as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return '';
        }

        $value = $value[$segment];
    }

    return $value;
}

/**
 * Combine saved year, month, and day fields into an ISO import date.
 * Missing or invalid dates stay blank rather than using the batch date.
 *
 * @param  array<int, string>    $fields Ordered year, month, day keys.
 * @param  array<string, mixed> $meta   Stored submission values.
 * @return string YYYY-MM-DD or blank.
 */
function repaf_daily_batch_format_date($fields, $meta)
{
    $parts = array();
    foreach ($fields as $field) {
        $value = trim((string) ($meta[$field] ?? ''));
        if (!preg_match('/^\d+$/', $value)) {
            return '';
        }
        $parts[] = (int) $value;
    }

    list($year, $month, $day) = $parts;
    if ($year > 9999 || !checkdate($month, $day, $year)) {
        return '';
    }

    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

/**
 * Assign a confirmation to the Philippine business date whose cutoff receives it.
 * Confirmations after 6:30 PM are placed in the following day's batch.
 *
 * @param  \DateTimeInterface|null $moment Optional time, primarily for tests.
 * @return string YYYY-MM-DD.
 */
function repaf_daily_batch_date($moment = null)
{
    $timezone = new \DateTimeZone('Asia/Manila');
    $local    = $moment instanceof \DateTimeInterface
        ? \DateTimeImmutable::createFromInterface($moment)->setTimezone($timezone)
        : new \DateTimeImmutable('now', $timezone);
    $cutoff   = (string) apply_filters('repaf_daily_batch_cutoff', '18:30');

    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $cutoff)) {
        $cutoff = '18:30';
    }

    list($hour, $minute) = array_map('intval', explode(':', $cutoff));
    $cutoff_time = $local->setTime($hour, $minute, 0);

    if ($local > $cutoff_time) {
        $local = $local->modify('+1 day');
    }

    return $local->format('Y-m-d');
}

/**
 * Return the protected path for one daily batch.
 *
 * @param  string $batch_date YYYY-MM-DD.
 * @return string|\WP_Error
 */
function repaf_daily_batch_path($batch_date)
{
    $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $batch_date);

    if (!$date || $date->format('Y-m-d') !== $batch_date) {
        return new \WP_Error('repaf_csv_invalid_batch_date', 'Invalid CSV batch date.');
    }

    $upload_dir = wp_upload_dir();

    if (!empty($upload_dir['error'])) {
        return new \WP_Error('repaf_csv_upload_error', (string) $upload_dir['error']);
    }

    $csv_dir = trailingslashit($upload_dir['basedir']) . 'repaf-csv';

    if (!wp_mkdir_p($csv_dir)) {
        return new \WP_Error('repaf_csv_dir_failed', 'CSV directory could not be created.');
    }

    repaf_protect_upload_dirs();

    return trailingslashit($csv_dir) . 'repaf-reservations-' . $batch_date . '.csv';
}

/**
 * Convert a stored value to one safe CSV cell.
 *
 * @param  mixed $value Stored meta value.
 * @return string
 */
function repaf_daily_batch_cell($value)
{
    if (is_array($value) || is_object($value)) {
        $value = wp_json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    return (string) repaf_escape_csv_cell($value);
}

/**
 * Build one row for a confirmed reservation.
 *
 * @param  int    $post_id     Submission post ID.
 * @param  string $batch_date  Assigned batch date.
 * @param  string $generated_at UTC generation timestamp.
 * @return array<int, string|int>
 */
function repaf_daily_batch_row($post_id, $batch_date, $generated_at)
{
    $raw_meta = get_post_meta($post_id);
    $meta     = array();

    foreach ($raw_meta as $key => $values) {
        $meta[$key] = maybe_unserialize($values[0] ?? '');
    }

    $row = array();

    foreach (repaf_daily_batch_cis_columns() as $column) {
        if (!empty($column['post_id'])) {
            $value = abs((int) $post_id);
        } elseif (!empty($column['financing_scheme'])) {
            $value = repaf_daily_batch_financing_scheme($meta);
        } elseif (isset($column['name_fields'])) {
            $value = repaf_daily_batch_format_name($column['name_fields'], $meta);
        } elseif (isset($column['date_fields'])) {
            $value = repaf_daily_batch_format_date($column['date_fields'], $meta);
        } elseif (isset($column['reference_name_path'])) {
            $reference = repaf_daily_batch_nested_value($meta, $column['reference_name_path']);
            $reference = is_array($reference) ? $reference : array();
            $parts = repaf_reference_name_parts($reference);
            $value = $parts === null
                ? ($reference['name'] ?? '')
                : repaf_daily_batch_format_name(array('last_name', 'first_name', 'middle_name', 'suffix'), $parts);
        } elseif (isset($column['path'])) {
            $value = repaf_daily_batch_nested_value($meta, $column['path']);
        } elseif (isset($column['first_nonempty_fields'])) {
            $value = '';

            foreach ($column['first_nonempty_fields'] as $field) {
                if (trim((string) ($meta[$field] ?? '')) !== '') {
                    $value = $meta[$field];
                    break;
                }
            }
        } elseif (!empty($column['field'])) {
            $value = $meta[$column['field']] ?? '';
        } else {
            $value = '';
        }

        $row[] = repaf_daily_batch_cell($value);
    }

    return $row;
}

/**
 * Rebuild one batch from all reservations assigned to its date.
 * Rebuilding instead of appending guarantees one current row per submission.
 *
 * @param  string $batch_date YYYY-MM-DD.
 * @return string|\WP_Error Absolute CSV path or error.
 */
function repaf_rebuild_daily_batch_csv($batch_date)
{
    $csv_path = repaf_daily_batch_path($batch_date);
    if (is_wp_error($csv_path)) {
        return $csv_path;
    }

    // Hold a batch-level lock across both the database read and file write so
    // two salespeople saving at the same time cannot overwrite each other's row.
    $lock = fopen($csv_path . '.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX)) {
        if (is_resource($lock)) {
            fclose($lock);
        }
        return new \WP_Error('repaf_csv_lock_failed', 'Daily batch CSV could not be locked.');
    }

    $post_ids = get_posts(array(
        'post_type'      => 'repaf_submission',
        'post_status'    => 'private',
        'numberposts'    => -1,
        'fields'         => 'ids',
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'meta_key'       => 'repaf_batch_date',
        'meta_value'     => $batch_date,
        'suppress_filters' => false,
    ));

    $stream = fopen('php://temp', 'w+b');
    if (!$stream) {
        flock($lock, LOCK_UN);
        fclose($lock);
        return new \WP_Error('repaf_csv_memory_failed', 'CSV buffer could not be created.');
    }

    fputcsv($stream, repaf_daily_batch_headers());
    $generated_at = current_time('mysql', true);

    foreach ($post_ids as $post_id) {
        fputcsv($stream, repaf_daily_batch_row($post_id, $batch_date, $generated_at));
    }

    rewind($stream);
    $contents = stream_get_contents($stream);
    fclose($stream);

    $written = $contents !== false
        ? file_put_contents($csv_path, $contents, LOCK_EX)
        : false;

    flock($lock, LOCK_UN);
    fclose($lock);

    if ($written === false) {
        return new \WP_Error('repaf_csv_write_failed', 'Daily batch CSV could not be written.');
    }

    return $csv_path;
}

/**
 * Confirm a salesperson-saved reservation and refresh its one daily batch.
 * The first confirmation fixes its batch date; later edits refresh that row
 * without duplicating or moving it to another file.
 *
 * @param  int $post_id Submission post ID.
 * @return string|\WP_Error Absolute daily CSV path or error.
 */
function repaf_refresh_submission_csv($post_id)
{
    $post_id = absint($post_id);
    $post    = get_post($post_id);

    if (!$post || $post->post_type !== 'repaf_submission') {
        return new \WP_Error('repaf_csv_invalid_submission', 'Invalid REPAF submission.');
    }

    $batch_date = (string) get_post_meta($post_id, 'repaf_batch_date', true);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $batch_date)) {
        $batch_date = repaf_daily_batch_date();
        update_post_meta($post_id, 'repaf_batch_date', $batch_date);
    }

    if (get_post_meta($post_id, 'reservation_confirmed', true) !== 'yes') {
        $user = wp_get_current_user();
        update_post_meta($post_id, 'reservation_confirmed', 'yes');
        update_post_meta($post_id, 'reservation_confirmed_at', current_time('mysql', true));
        update_post_meta($post_id, 'reservation_confirmed_by_user_id', absint($user->ID));
        update_post_meta($post_id, 'reservation_confirmed_by_name', sanitize_text_field($user->display_name));
    }

    $csv_path = repaf_rebuild_daily_batch_csv($batch_date);
    if (is_wp_error($csv_path)) {
        return $csv_path;
    }

    update_post_meta($post_id, 'repaf_csv_path', $csv_path);
    delete_post_meta($post_id, 'repaf_csv_url');

    return $csv_path;
}
