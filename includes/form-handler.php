<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * =========================================================
 * Form submission handler
 * =========================================================
 *
 * Orchestrates the full submission pipeline:
 * nonce → rate limit → honeypot → sanitize → validate →
 * save private post → email → redirect.
 *
 * PDFs are generated on-demand from the admin panel.
 * Supporting concerns live in dedicated files:
 *   rate-limiter.php  — repaf_check_rate_limit()
 *   mailer.php        — repaf_send_buyer_email(), repaf_send_admin_email()
 */

add_action('admin_post_repaf_submit_form',        __NAMESPACE__ . '\\repaf_process_form_submission');
add_action('admin_post_nopriv_repaf_submit_form', __NAMESPACE__ . '\\repaf_process_form_submission');

function repaf_process_form_submission()
{
    // --- Salesperson edit mode: runs before normal new-submission logic ---
    if (isset($_POST['repaf_edit_id'], $_POST['repaf_edit_nonce'])) {
        $edit_id = absint($_POST['repaf_edit_id']);

        if (
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['repaf_edit_nonce'])),
                'repaf_front_edit_' . $edit_id
            ) ||
            !current_user_can('manage_repaf_submissions')
        ) {
            repaf_redirect_with_status('security_failed');
            exit;
        }

        $post = get_post($edit_id);
        if (!$post || $post->post_type !== 'repaf_submission') {
            repaf_redirect_with_status('error');
            exit;
        }

        // Buyer fields (salesperson may have corrected mistakes).
        // Optional repeatable sections disappear from $_POST when their final
        // row is removed, so provide an explicit empty value to clear old meta.
        $buyer_post_data = $_POST;
        foreach (array('fin_bank', 'fin_loan') as $repeatable_key) {
            if (!array_key_exists($repeatable_key, $buyer_post_data)) {
                $buyer_post_data[$repeatable_key] = array();
            }
        }
        $buyer_data = repaf_sanitize_submission($buyer_post_data);

        // Property fields required only while a salesperson/admin is editing.
        $property_data = repaf_sanitize_property_info($_POST);
        $missing_property_fields = array_merge(
            repaf_missing_required_fields($buyer_data, repaf_required_buyer_address_fields()),
            repaf_get_missing_required_property_fields($property_data)
        );

        if (!empty($missing_property_fields)) {
            repaf_redirect_with_status(
                'missing_fields',
                array('repaf_missing' => rawurlencode(implode(', ', $missing_property_fields)))
            );
        }

        foreach ($buyer_data as $key => $value) {
            update_post_meta($edit_id, $key, $value);
        }

        // Property fields
        foreach ($property_data as $key => $value) {
            update_post_meta($edit_id, $key, $value);
        }

        // Salesperson fields
        $sp_text_fields = array(
            'sp_agent_name', 'sp_manager_name', 'sp_broker_name', 'sp_realty_name',
            'sp_prepared_by', 'sp_verified_by', 'sp_checked_by', 'sp_approved_by',
        );
        foreach ($sp_text_fields as $key) {
            if (isset($_POST[$key])) {
                update_post_meta($edit_id, $key, sanitize_text_field(wp_unslash($_POST[$key])));
            }
        }
        if (isset($_POST['sp_remarks'])) {
            update_post_meta($edit_id, 'sp_remarks', sanitize_textarea_field(wp_unslash($_POST['sp_remarks'])));
        }

        // A normal edit remains a draft unless the salesperson explicitly confirms
        // the reservation. Already-confirmed records refresh their existing row.
        $already_confirmed = get_post_meta($edit_id, 'reservation_confirmed', true) === 'yes';
        $confirm_requested = isset($_POST['repaf_confirm_reservation']) &&
            sanitize_key(wp_unslash($_POST['repaf_confirm_reservation'])) === 'yes';
        $csv_error = false;
        $batched   = false;

        if ($already_confirmed || $confirm_requested) {
            $csv_path  = repaf_refresh_submission_csv($edit_id);
            $csv_error = is_wp_error($csv_path);
            $batched   = !$csv_error;
        }

        if ($csv_error) {
            error_log(sprintf(
                '[REPAF] Daily batch CSV refresh failed for post #%d. Error: %s',
                $edit_id,
                $csv_path->get_error_message()
            ));
        }

        // No new post, no email — redirect back to admin list
        wp_safe_redirect(add_query_arg(
            array(
                'page'      => 'repaf-submissions',
                'saved'     => 1,
                'csv_error' => $csv_error ? 1 : 0,
                'batched'   => $batched ? 1 : 0,
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    // 1. Nonce
    if (
        !isset($_POST['repaf_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['repaf_nonce'])),
            'repaf_submit'
        )
    ) {
        repaf_redirect_with_status('security_failed');
    }

    // 2. Rate limit
    if (!repaf_check_rate_limit()) {
        repaf_redirect_with_status('rate_limited');
    }

    // 3. Honeypot — silent exit so bots get no feedback
    if (!empty($_POST['repaf_website'])) {
        exit;
    }

    // 4. Sanitize
    $data = repaf_sanitize_submission($_POST);

    // 5. Required field check
    $missing = repaf_get_missing_required_fields($data);

    if (!empty($missing)) {
        repaf_redirect_with_status(
            'missing_fields',
            array('repaf_missing' => rawurlencode(implode(', ', $missing)))
        );
    }

    // 6. Format validation
    $errors = repaf_validate_submission($data);

    if (!empty($errors)) {
        error_log(sprintf(
            '[REPAF] Submission rejected — format validation failed (%d field(s) invalid).',
            count($errors)
        ));
        repaf_redirect_with_status('invalid_fields');
    }

    // Record when and against which notice version the customer consented.
    // These values are created server-side and cannot be supplied by the browser.
    $data['privacy_consent_timestamp'] = current_time('mysql', true);
    $data['privacy_notice_version']    = '2026-08-05';

    // 7. Save submission post
    $post_id = wp_insert_post(
        array(
            'post_type'   => 'repaf_submission',
            'post_title'  => repaf_build_submission_title($data),
            'post_status' => 'private',
        ),
        true
    );

    if (is_wp_error($post_id)) {
        error_log(sprintf(
            '[REPAF] Submission post could not be created. Error: %s',
            $post_id->get_error_message()
        ));
        repaf_redirect_with_status('save_failed');
    }

    // 8. Save post meta
    foreach ($data as $key => $value) {
        update_post_meta($post_id, $key, $value);
    }

    // 9. Send redirect headers to the browser NOW, before SMTP calls.
    // fastcgi_finish_request() (available on PHP-FPM / LiteSpeed) releases the
    // browser connection immediately so emails send in the background without
    // the user waiting for SMTP.
    $referer = wp_get_referer() ?: home_url('/');
    wp_safe_redirect(add_query_arg(array('repaf_status' => 'success'), $referer));

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
    }

    // 10. Send emails — browser has already received the redirect by now
    repaf_queue_submission_emails($post_id);

    exit;
}
