<?php

namespace Repaf;

/**
 * REPAF PDF Generator
 *
 * Builds the multi-page submission PDF: the buyer page (drawn
 * here) followed by the co-owner, property, financial,
 * salesperson and terms pages (drawn in pdf-generator-pages.php).
 * Coordinate data lives in pdf-mapper.php; drawing/lookup
 * helpers live in pdf-generator-helpers.php; date/data prep
 * lives in pdf-generator-data.php.
 */

if (!defined('ABSPATH')) {
    exit;
}

function repaf_generate_buyer_pdf($post_id, $data)
{
    // Composer installs TCPDF under vendor/tecnickcom/tcpdf. Keep the legacy
    // bundled path as a fallback for deployments that still include it there.
    $tcpdf_paths = array(
        REPAF_PLUGIN_PATH . 'vendor/tecnickcom/tcpdf/tcpdf.php',
        REPAF_PLUGIN_PATH . 'vendor/tcpdf/tcpdf.php',
    );
    $tcpdf_path = null;

    foreach ($tcpdf_paths as $candidate) {
        if (file_exists($candidate)) {
            $tcpdf_path = $candidate;
            break;
        }
    }

    if ($tcpdf_path === null) {
        return new \WP_Error('repaf_pdf_missing', 'TCPDF library is missing.');
    }
    require_once $tcpdf_path;

    $pdf = new \TCPDF('P', 'pt', 'A4', true, 'UTF-8', false);

    $pdf->SetCreator('REPAF');
    $profile_config = repaf_get_site_profile_config();
    $pdf->SetAuthor($profile_config['pdf_author']);
    $pdf->SetTitle('REPAF Submission');

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->AddPage();

    $template = repaf_get_pdf_template_path('page-1.jpg');

    if (!file_exists($template)) {
        error_log('[REPAF] Missing PDF template: ' . $template);
        return new \WP_Error('repaf_pdf_missing_template', 'PDF template file is missing: ' . basename($template));
    }

    $pdf->Image(
        $template,
        0,
        0,
        595,
        842,
        'JPG',
        '',
        '',
        false,
        300,
        '',
        false,
        false,
        0,
        false,
        false,
        true
    );

    $pdf->SetFont('helvetica', '', 8);

    $coordinates = repaf_get_updated_page1_coordinates();
    $data = repaf_prepare_buyer_pdf_data($data);
    $data = repaf_prepare_coowner_pdf_data($data);

    // Gender checkbox
    $gender = sanitize_key($data['buyer_gender'] ?? '');

    if ($gender === 'male') {
        repaf_draw_pdf_checkmark($pdf, $coordinates['buyer_gender_male']['x'], $coordinates['buyer_gender_male']['y']);
    } elseif ($gender === 'female') {
        repaf_draw_pdf_checkmark($pdf, $coordinates['buyer_gender_female']['x'], $coordinates['buyer_gender_female']['y']);
    }

    // Civil status checkbox
    $civil_status_coordinate = repaf_get_buyer_civil_status_coordinate($data['buyer_civil_status'] ?? '', $coordinates);

    if (!empty($civil_status_coordinate)) {
        repaf_draw_pdf_checkmark($pdf, $civil_status_coordinate['x'], $civil_status_coordinate['y']);
    }

    // Home ownership checkbox
    $home_ownership_coordinate = repaf_get_buyer_home_ownership_coordinate($data['buyer_home_ownership'] ?? '', $coordinates);

    if (!empty($home_ownership_coordinate)) {
        repaf_draw_pdf_checkmark($pdf, $home_ownership_coordinate['x'], $home_ownership_coordinate['y']);
    }

    repaf_draw_pdf_coordinate_checkmark(
        $pdf,
        repaf_get_pdf_role_coordinate($data['co_owner_role'] ?? '', 'co', $coordinates)
    );
    repaf_draw_pdf_coordinate_checkmark(
        $pdf,
        repaf_get_pdf_gender_coordinate($data['co_gender'] ?? '', 'co', $coordinates)
    );
    repaf_draw_pdf_coordinate_checkmark(
        $pdf,
        repaf_get_pdf_civil_status_coordinate($data['co_civil_status'] ?? '', 'co', $coordinates)
    );

    // Draw all mapped fields
    $boxed_fields = array(
        'buyer_birth_month' => array('chars' => 2),
        'buyer_birth_day'   => array('chars' => 2),
        'buyer_birth_year'  => array('chars' => 4),
        'spouse_birth_month' => array('chars' => 2),
        'spouse_birth_day'   => array('chars' => 2),
        'spouse_birth_year'  => array('chars' => 4, 'box_width' => 16),
        'rep_birth_month'   => array('chars' => 2, 'box_width' => 15),
        'rep_birth_day'     => array('chars' => 2, 'box_width' => 15),
        'rep_birth_year'    => array('chars' => 4, 'box_width' => 15),
        'co_birth_month' => array('chars' => 2),
        'co_birth_day'   => array('chars' => 2),
        'co_birth_year'  => array('chars' => 4),
    );

    foreach ($coordinates as $field => $position) {
        if (!isset($data[$field]) || $data[$field] === '') {
            continue;
        }

        if (isset($boxed_fields[$field])) {
            $boxed_field = $boxed_fields[$field];

            repaf_draw_pdf_boxed_text(
                $pdf,
                $data[$field],
                $position['x'],
                $position['y'],
                $boxed_field['chars'],
                $boxed_field['box_width'] ?? 18
            );

            continue;
        }

        if (in_array($field, array(
            'buyer_permanent_address_line',
            'buyer_present_address_line',
            'rep_address_line',
            'co_permanent_address_line',
        ), true)) {
            repaf_draw_pdf_compact_address_text(
                $pdf,
                $data[$field],
                $position['x'],
                $position['y'],
                $position['width'] ?? 0
            );
            continue;
        }

        repaf_draw_pdf_text(
            $pdf,
            $data[$field],
            $position['x'],
            $position['y'],
            $position['width'] ?? 0
        );
    }

    repaf_draw_coowner_pdf_page($pdf, $data);

    // Financial Information and Property Information use the current named
    // REPAF page assets and include the submitted values where applicable.
    repaf_draw_financial_pdf_page($pdf, $data);
    repaf_draw_property_pdf_page($pdf, $data);

    // Terms and Conditions and Salesperson Information (pages 5 and 6).
    // Always included; overlays the e-signature and the buyer, spouse, and
    // co-borrower printed names onto their respective signature lines.
    repaf_draw_terms_pdf_page($pdf, $data);
    repaf_draw_salesperson_pdf_page($pdf, $data);

    $upload_dir = wp_upload_dir();
    $pdf_dir = trailingslashit($upload_dir['basedir']) . 'repaf-pdf';

    wp_mkdir_p($pdf_dir);
    repaf_protect_upload_dirs();

    $filename = repaf_generated_file_name($post_id, 'pdf');

    if (is_wp_error($filename)) {
        return $filename;
    }

    $pdf_path = trailingslashit($pdf_dir) . $filename;

    $pdf->Output($pdf_path, 'F');

    return $pdf_path;
}
