<?php

namespace Repaf;

if (!defined('ABSPATH')) {
  exit;
}

/**
 * =========================================================
 * Mailer
 * =========================================================
 *
 * Sends two emails on successful submission:
 *
 * 1. Buyer confirmation — HTML email to the buyer with the
 *    filled PDF attached as "REPAF - Buyer Name.pdf".
 *
 * 2. Admin notification — plain-text email to the site admin
 *    with a submission summary and the PDF attached.
 *
 * NOTE: wp_mail() uses PHP sendmail by default, which is
 * blocked or spam-filtered on most managed hosts. Install
 * and configure WP Mail SMTP for reliable delivery.
 */

// ---------------------------------------------------------------------------
// Contact details shown in the buyer email footer.
// Edit these two constants to update the footer across all future emails.
// ---------------------------------------------------------------------------
define('REPAF_CONTACT_PHONE',   '+63 464 161 266');
define('REPAF_CONTACT_EMAIL',   'customer_service@mycitihomes.com.ph');
define('REPAF_CONTACT_ADDRESS', 'Philippines');

/**
 * Convert a WordPress upload URL to its local filesystem path.
 */
function repaf_url_to_path($url)
{
  if (is_wp_error($url) || empty($url)) {
    return '';
  }

  if (is_string($url) && file_exists($url)) {
    return $url;
  }

  $upload_dir = wp_upload_dir();

  return str_replace(
    trailingslashit($upload_dir['baseurl']),
    trailingslashit($upload_dir['basedir']),
    $url
  );
}

/**
 * Build the buyer's full display name from submission data.
 */
function repaf_get_buyer_full_name($data)
{
  $parts = array_filter(array(
    $data['buyer_first_name']  ?? '',
    $data['buyer_middle_name'] ?? '',
    $data['buyer_last_name']   ?? '',
    $data['buyer_suffix']      ?? '',
  ));

  return implode(' ', $parts) ?: 'Buyer';
}

/**
 * Build the branded HTML email body for the buyer confirmation.
 */
function repaf_build_buyer_email_html($buyer_name, $site_name, $site_url)
{
  $color_defaults = array(
    'primary'      => '#c2187a',
    'primary_dark' => '#9c145f',
    'primary_soft' => '#fff1f7',
    'border'       => '#f3c5dc',
  );
  $saved        = get_option('repaf_color_settings', $color_defaults);
  $colors       = wp_parse_args($saved, $color_defaults);
  $primary      = sanitize_hex_color($colors['primary'])      ?: $color_defaults['primary'];
  $primary_dk   = sanitize_hex_color($colors['primary_dark']) ?: $color_defaults['primary_dark'];
  $primary_soft = sanitize_hex_color($colors['primary_soft']) ?: $color_defaults['primary_soft'];
  $border       = sanitize_hex_color($colors['border'])       ?: $color_defaults['border'];

  $phone          = esc_html(REPAF_CONTACT_PHONE);
  $contact_email  = esc_html(REPAF_CONTACT_EMAIL);
  $address        = esc_html(REPAF_CONTACT_ADDRESS);
  $site_url_esc   = esc_url($site_url);
  $site_name_esc  = esc_html($site_name);
  $buyer_name_esc = esc_html($buyer_name);
  $year           = gmdate('Y');

  return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Your REPAF Form Submission</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:Arial,Helvetica,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;padding:36px 16px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,0.09);">

          <!-- ── Header ─────────────────────────────────────── -->
          <tr>
            <td style="background:linear-gradient(135deg,{$primary_dk},{$primary});padding:36px 40px;text-align:center;">
              <h1 style="margin:0;color:#ffffff;font-size:26px;font-weight:700;letter-spacing:-0.3px;">
                {$site_name_esc}
              </h1>
              <p style="margin:8px 0 0;color:rgba(255,255,255,0.82);font-size:13px;">
                Real Estate Purchase Agreement Form
              </p>
            </td>
          </tr>

          <!-- ── Greeting ───────────────────────────────────── -->
          <tr>
            <td style="padding:40px 40px 0;">
              <p style="margin:0 0 6px;font-size:17px;font-weight:700;color:#1a1a2e;">
                Dear {$buyer_name_esc},
              </p>
            </td>
          </tr>

          <!-- ── Body ──────────────────────────────────────── -->
          <tr>
            <td style="padding:20px 40px 32px;">
              <p style="margin:0 0 16px;font-size:15px;color:#374151;line-height:1.75;">
                Thank you for completing your
                <strong style="color:#1a1a2e;">Real Estate Purchase Agreement Form (REPAF)</strong>
                with {$site_name_esc}.
              </p>
              <p style="margin:0 0 16px;font-size:15px;color:#374151;line-height:1.75;">
                Your submission has been <strong>successfully received</strong>.
                Our team will review your information and one of our salespersons will contact you to schedule a meeting and complete the transaction in person.
              </p>
              <p style="margin:0 0 24px;font-size:15px;color:#374151;line-height:1.75;">
                If you have any questions in the meantime, please do not hesitate to contact us using the details below.
              </p>

              <!-- CTA box -->
              <table width="100%" cellpadding="0" cellspacing="0"
                     style="background:{$primary_soft};border:1px solid {$border};border-radius:8px;margin-bottom:28px;">
                <tr>
                  <td style="padding:18px 22px;">
                    <p style="margin:0;font-size:14px;color:{$primary_dk};font-weight:700;">
                      ✔ &nbsp;Your submission has been recorded successfully.
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 4px;font-size:15px;color:#374151;">Warm regards,</p>
              <p style="margin:0;font-size:16px;font-weight:700;color:{$primary};">
                {$site_name_esc}
              </p>
            </td>
          </tr>

          <!-- ── Divider ────────────────────────────────────── -->
          <tr>
            <td style="padding:0 40px;">
              <hr style="border:none;border-top:1px solid #e5e7eb;margin:0;">
            </td>
          </tr>

          <!-- ── Footer ─────────────────────────────────────── -->
          <tr>
            <td style="background:#f9f9fb;padding:28px 40px;border-top:1px solid #e5e7eb;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="font-size:13px;color:#6b7280;line-height:2;">
                    <strong style="font-size:14px;color:#1a1a2e;">{$site_name_esc}</strong><br>
                    &#128222;&nbsp;<a href="tel:{$phone}" style="color:{$primary};text-decoration:none;">{$phone}</a><br>
                    &#9993;&nbsp;<a href="mailto:{$contact_email}" style="color:{$primary};text-decoration:none;">{$contact_email}</a><br>
                    &#127760;&nbsp;<a href="{$site_url_esc}" style="color:{$primary};text-decoration:none;">{$site_url_esc}</a><br>
                    &#128205;&nbsp;{$address}
                  </td>
                </tr>
                <tr>
                  <td style="padding-top:18px;font-size:11px;color:#9ca3af;line-height:1.6;border-top:1px solid #e5e7eb;margin-top:18px;">
                    <br>This is an automated email. Please do not reply directly to this message.
                    For assistance, contact us at
                    <a href="mailto:{$contact_email}" style="color:{$primary};text-decoration:none;">{$contact_email}</a>.
                    <br>&copy; {$year} {$site_name_esc}. All rights reserved.
                  </td>
                </tr>
              </table>
            </td>
          </tr>

        </table>

        <!-- Pre-footer note -->
        <p style="margin:16px 0 0;font-size:11px;color:#9ca3af;text-align:center;">
          You received this email because you submitted a REPAF form at {$site_url_esc}.
        </p>

      </td>
    </tr>
  </table>

</body>
</html>
HTML;
}

/**
 * Send a confirmation email to the buyer with the PDF attached.
 */
function repaf_send_buyer_email($data, $pdf_path)
{
  $buyer_email = sanitize_email($data['buyer_email'] ?? '');

  if (empty($buyer_email)) {
    return false;
  }

  $buyer_name = sanitize_text_field(repaf_get_buyer_full_name($data));
  $site_name  = get_bloginfo('name');
  $site_url   = get_bloginfo('url');

  $subject = sprintf('[%s] Your REPAF Form Submission', $site_name);

  $message = repaf_build_buyer_email_html($buyer_name, $site_name, $site_url);

  $headers = array(
    'Content-Type: text/html; charset=UTF-8',
    'From: ' . $site_name . ' <' . get_option('admin_email') . '>',
  );

  $sent = wp_mail($buyer_email, $subject, $message, $headers);

  if (!$sent) {
    error_log(sprintf(
      '[REPAF] Buyer confirmation email failed to send to %s. Check SMTP configuration.',
      $buyer_email
    ));
  }

  return $sent;
}

/**
 * Send a notification email to the site admin with the PDF attached.
 */
function repaf_send_admin_email($data, $post_id, $pdf_path)
{
  $admin_email = get_option('admin_email');

  if (empty($admin_email)) {
    return false;
  }

  $buyer_name   = sanitize_text_field(repaf_get_buyer_full_name($data));
  $buyer_email  = sanitize_email($data['buyer_email']  ?? '');
  $buyer_mobile = sanitize_text_field($data['buyer_mobile'] ?? 'N/A');
  $site_name    = get_bloginfo('name');
  $admin_url    = admin_url('admin.php?page=repaf-submissions');
  $submitted    = current_time('F j, Y \a\t g:i A');

  $subject = sprintf('[%s] New REPAF Submission — %s', $site_name, $buyer_name);

  $message  = "A new REPAF form submission has been received.\n\n";
  $message .= "--- Submission Details ---\n";
  $message .= "Submission ID : #{$post_id}\n";
  $message .= "Buyer Name    : {$buyer_name}\n";
  $message .= "Email         : {$buyer_email}\n";
  $message .= "Mobile        : {$buyer_mobile}\n";
  $message .= "Submitted     : {$submitted}\n\n";
  $message .= "View and manage this submission in the REPAF admin panel:\n";
  $message .= $admin_url . "\n\n";
  $message .= "Open the submission, review the information, and generate the PDF once the buyer has been contacted.\n";

  $headers = array(
    'Content-Type: text/plain; charset=UTF-8',
    'From: '     . $site_name  . ' <' . $admin_email . '>',
    'Reply-To: ' . $buyer_name . ' <' . $buyer_email . '>',
  );

  $sent = wp_mail($admin_email, $subject, $message, $headers);

  if (!$sent) {
    error_log(sprintf(
      '[REPAF] Admin notification email failed to send to %s for post #%d. Check SMTP configuration.',
      $admin_email,
      absint($post_id)
    ));
  }

  return $sent;
}
