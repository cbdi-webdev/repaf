<?php

namespace Repaf;

/**
 * REPAF Admin Submissions — PDF Download
 *
 * The menu page callback fires after WordPress has already started
 * building the admin HTML wrapper, so setting Content-Type: application/pdf
 * there would corrupt the file. admin_init fires before any output —
 * repaf_admin_maybe_download_pdf() (hooked in admin-submissions.php)
 * intercepts generate_pdf requests here and streams the binary directly.
 */

if (!defined('ABSPATH')) {
    exit;
}

function repaf_admin_maybe_download_pdf()
{
    if (
        !isset($_GET['page'])   || sanitize_key($_GET['page'])   !== 'repaf-submissions' ||
        !isset($_GET['action']) || sanitize_key($_GET['action'])  !== 'generate_pdf'
    ) {
        return;
    }

    if (!current_user_can('manage_repaf_submissions')) {
        wp_die(__('You do not have permission to perform this action.'));
    }

    $post_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

    if ($post_id === 0) {
        wp_die(__('Invalid submission ID.'));
    }

    if (
        !isset($_GET['_wpnonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'repaf_genpdf_' . $post_id)
    ) {
        wp_die(__('Security check failed.'));
    }

    repaf_admin_handle_generate_pdf($post_id);
}

function repaf_admin_handle_generate_pdf($post_id)
{
    $post = get_post($post_id);

    if (!$post || $post->post_type !== 'repaf_submission') {
        wp_die(__('Submission not found.'));
    }

    // Load all meta and flatten
    $raw_meta = get_post_meta($post_id);
    $data     = array();

    foreach ($raw_meta as $key => $values) {
        $val        = isset($values[0]) ? $values[0] : '';
        $data[$key] = maybe_unserialize($val);
    }

    // Generate PDF
    try {
        $pdf_path = repaf_generate_buyer_pdf($post_id, $data);
    } catch (\Throwable $e) {
        error_log('[REPAF] PDF generation exception for post #' . $post_id . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        wp_die('PDF generation failed: ' . esc_html($e->getMessage()) . '<br><br>Check the PHP error log for details.');
    }

    if (is_wp_error($pdf_path)) {
        wp_die(esc_html($pdf_path->get_error_message()));
    }

    // Store the server path only. Raw upload URLs are intentionally avoided.
    update_post_meta($post_id, 'repaf_pdf_path', $pdf_path);
    delete_post_meta($post_id, 'repaf_pdf_url');

    if (!file_exists($pdf_path)) {
        wp_die(__('PDF file could not be found after generation.'));
    }

    $buyer_first = get_post_meta($post_id, 'buyer_first_name', true);
    $buyer_last  = get_post_meta($post_id, 'buyer_last_name', true);
    $filename    = 'REPAF - ' . trim($buyer_first . ' ' . $buyer_last) . '.pdf';

    $file_size = filesize($pdf_path);
    $max_size  = 50 * 1024 * 1024; // 50 MB
    if ($file_size === false || $file_size > $max_size) {
        wp_die('The generated PDF is too large to download. Please contact the administrator.', 'Download Error', array('response' => 500));
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: private, no-cache, no-store, must-revalidate');
    readfile($pdf_path);
    exit;
}
