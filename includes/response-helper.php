<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * =========================================================
 * Response helpers
 * =========================================================
 *
 * Handles the two ways the plugin communicates outcomes
 * back to the user:
 *
 * 1. repaf_redirect_with_status() — after form processing,
 *    redirect back to the form page with a ?repaf_status=
 *    query param so the shortcode can render a notice.
 *
 * 2. repaf_render_status_notice() — reads ?repaf_status
 *    and renders the appropriate success/error message.
 *    On success, shows a professional confirmation card and
 *    consumes the one-time download token (no links shown).
 */

/**
 * Redirect back to the referring page with a status code.
 *
 * @param string $status  Status key (e.g. 'success', 'save_failed').
 * @param array  $args    Additional query args to append.
 */
function repaf_redirect_with_status($status, $args = array())
{
    $fallback_url = home_url('/');
    $referer      = wp_get_referer() ? wp_get_referer() : $fallback_url;

    wp_safe_redirect(
        add_query_arg(
            array_merge(array('repaf_status' => $status), $args),
            $referer
        )
    );

    exit;
}

/**
 * Render the status notice HTML for the current request.
 * Returns an empty string when no status is present.
 *
 * @return string  HTML notice markup.
 */
function repaf_render_status_notice()
{
    if (empty($_GET['repaf_status'])) {
        return '';
    }

    $status = sanitize_key(wp_unslash($_GET['repaf_status']));

    $messages = array(
        'success'         => array('success', 'Form submitted successfully.'),
        'security_failed' => array('error',   'Security check failed. Please refresh and try again.'),
        'save_failed'     => array('error',   'Submission could not be saved.'),
        'missing_fields'  => array('error',   'Please complete all required fields.'),
        'invalid_fields'  => array('error',   'Some fields contain invalid values. Please review your entries and try again.'),
        'rate_limited'           => array('error', 'Too many submissions. Please wait a few minutes and try again.'),
        'signature_upload_error' => array('error', 'Signature upload failed. Please try again.'),
        'signature_invalid_type' => array('error', 'Signature must be a PNG or JPG image.'),
        'signature_too_large'    => array('error', 'Signature image must be 2MB or smaller.'),
    );

    if (!isset($messages[$status])) {
        return '';
    }

    list($type, $message) = $messages[$status];

    if ($status === 'missing_fields' && !empty($_GET['repaf_missing'])) {
        $missing = sanitize_text_field(wp_unslash($_GET['repaf_missing']));
        if ($missing !== '') {
            $message .= ' Missing: ' . $missing . '.';
        }
    }

    if ($status === 'success') {
        // Consume the one-time download token immediately so it cannot be replayed.
        $raw_token = isset($_GET['repaf_dl'])
            ? sanitize_text_field(wp_unslash($_GET['repaf_dl']))
            : '';

        if (!empty($raw_token)) {
            delete_transient('repaf_dl_' . $raw_token);
        }

        // Render a hidden modal — JS will show it on page load.
        $home_url = esc_url(home_url('/'));

        $html  = '<div id="repaf-success-modal" class="repaf-success-modal" role="dialog" aria-modal="true" aria-labelledby="repaf-success-modal-title" aria-hidden="true">';
        $html .= '<div class="repaf-success-modal__box">';
        $html .= '<div class="repaf-success-modal__icon" aria-hidden="true">&#10003;</div>';
        $html .= '<h3 class="repaf-success-modal__title" id="repaf-success-modal-title">Thank You!</h3>';
        $html .= '<p class="repaf-success-modal__subtitle">Your Form Has Been Successfully Submitted.</p>';
        $html .= '<p class="repaf-success-modal__body">We have received your Real Estate Purchase Agreement Form (REPAF). A confirmation email has been sent to your registered email address.</p>';
        $html .= '<p class="repaf-success-modal__body">Please check your inbox — and your spam folder if you do not see it within a few minutes.</p>';
        $html .= '<p class="repaf-success-modal__note">&#9993;&nbsp; Your sales representative will follow up with you to complete the remaining details.</p>';
        $html .= '<a href="' . $home_url . '" class="repaf-success-modal__btn">Back to Home</a>';
        $html .= '</div>';
        $html .= '</div>';
    } else {
        $html  = '<div class="repaf-status repaf-status--' . esc_attr($type) . '">';
        $html .= esc_html($message);
        $html .= '</div>';
    }

    return $html;
}
