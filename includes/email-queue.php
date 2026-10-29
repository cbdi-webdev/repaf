<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * =========================================================
 * Background email queue
 * =========================================================
 *
 * Schedules submission confirmation/notification emails through
 * WP-Cron so the browser request can finish without waiting for
 * SMTP. If scheduling fails, the mailer falls back to immediate
 * sending after the response has already been flushed.
 */

const REPAF_EMAIL_CRON_HOOK = 'repaf_send_submission_emails';

function repaf_queue_submission_emails($post_id)
{
    $post_id = absint($post_id);

    if ($post_id === 0) {
        return false;
    }

    update_post_meta($post_id, 'repaf_email_status', 'queued');
    update_post_meta($post_id, 'repaf_email_queued_at', current_time('mysql'));
    delete_post_meta($post_id, 'repaf_email_last_error');

    $scheduled = wp_schedule_single_event(
        time() + 5,
        REPAF_EMAIL_CRON_HOOK,
        array($post_id)
    );

    if ($scheduled) {
        return true;
    }

    error_log(sprintf(
        '[REPAF] Email queue scheduling failed for post #%d. Sending immediately after response flush.',
        $post_id
    ));

    repaf_handle_queued_submission_emails($post_id);

    return false;
}

function repaf_handle_queued_submission_emails($post_id)
{
    $post_id = absint($post_id);
    $post    = get_post($post_id);

    if (!$post || $post->post_type !== 'repaf_submission') {
        return;
    }

    update_post_meta($post_id, 'repaf_email_status', 'processing');

    $data = repaf_get_submission_email_data($post_id);

    $buyer_sent = repaf_send_buyer_email($data, '');
    $admin_sent = repaf_send_admin_email($data, $post_id, '');

    if ($buyer_sent && $admin_sent) {
        update_post_meta($post_id, 'repaf_email_status', 'sent');
        update_post_meta($post_id, 'repaf_email_sent_at', current_time('mysql'));
        delete_post_meta($post_id, 'repaf_email_last_error');
        return;
    }

    $failed_parts = array();

    if (!$buyer_sent) {
        $failed_parts[] = 'buyer';
    }

    if (!$admin_sent) {
        $failed_parts[] = 'admin';
    }

    $message = 'Failed to send ' . implode(' and ', $failed_parts) . ' email.';

    update_post_meta($post_id, 'repaf_email_status', 'failed');
    update_post_meta($post_id, 'repaf_email_last_error', $message);

    error_log(sprintf('[REPAF] %s Submission #%d.', $message, $post_id));
}
add_action(REPAF_EMAIL_CRON_HOOK, __NAMESPACE__ . '\\repaf_handle_queued_submission_emails');

function repaf_get_submission_email_data($post_id)
{
    $raw_meta = get_post_meta($post_id);
    $data     = array();

    foreach ($raw_meta as $key => $values) {
        $val        = isset($values[0]) ? $values[0] : '';
        $data[$key] = maybe_unserialize($val);
    }

    return $data;
}
