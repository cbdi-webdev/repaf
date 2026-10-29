<?php

namespace Repaf;

/**
 * REPAF PDF Drawing & Coordinate Helpers
 *
 * Shared low-level helpers used by every page-drawing function:
 * text/checkmark drawing primitives, option-to-coordinate
 * lookups for the various checkbox groups, and the signature
 * background-removal routine.
 */

if (!defined('ABSPATH')) {
    exit;
}

function repaf_draw_pdf_checkmark($pdf, $x, $y)
{
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->SetXY($x, $y);
    $pdf->Write(0, "\u{2713}");
}

function repaf_draw_pdf_coordinate_checkmark($pdf, $coordinate)
{
    if (empty($coordinate)) {
        return;
    }

    repaf_draw_pdf_checkmark($pdf, $coordinate['x'], $coordinate['y']);
}

function repaf_draw_pdf_text($pdf, $text, $x, $y, $width = 0, $height = 13)
{
    $pdf->SetXY($x, $y);
    $text = wp_strip_all_tags($text);
    $font_size = 8.0;
    $pdf->SetFont('helvetica', '', $font_size);

    if ($width > 0) {
        $text_width = max(1, $width - 2);

        if ($pdf->GetStringWidth($text) <= $text_width) {
            $pdf->Cell($text_width, 0, $text, 0, 0, 'L');
            return;
        }

        // Long values use up to two compact lines and are reduced further by
        // TCPDF only when necessary to stay inside the assigned cell.
        $pdf->SetFont('helvetica', '', 7);
        $pdf->MultiCell(
            $text_width,
            $height,
            $text,
            0,
            'L',
            false,
            0,
            $x,
            $y - 1,
            true,
            0,
            false,
            false,
            $height,
            'M',
            true
        );
        return;
    }

    $pdf->Write(0, $text);
}

function repaf_draw_pdf_boxed_text($pdf, $text, $x, $y, $max_chars, $box_width = 18)
{
    $text = preg_replace('/\D/', '', (string) $text);
    $text = substr($text, 0, $max_chars);

    $pdf->SetFont('helvetica', '', 8);

    for ($index = 0; $index < $max_chars; $index++) {
        $character = substr($text, $index, 1);

        if ($character === '') {
            continue;
        }

        $pdf->SetXY($x + ($index * $box_width), $y);
        $pdf->Cell($box_width, 0, $character, 0, 0, 'C');
    }
}

/**
 * Draw the compact present-address line on the updated REPAF pages. It uses
 * a smaller leading than the generic MultiCell helper so a wrapped address
 * remains comfortably above the next row border.
 */
function repaf_draw_pdf_compact_address_text($pdf, $text, $x, $y, $width)
{
    $text = wp_strip_all_tags($text);
    if ($text === '') {
        return;
    }

    $pdf->SetFont('helvetica', '', 6.8);
    $pdf->MultiCell(
        max(1, $width - 2),
        7.5,
        $text,
        0,
        'L',
        false,
        0,
        $x,
        $y,
        true,
        0,
        false,
        false,
        7.5,
        'M',
        true
    );
}

function repaf_normalize_pdf_option($value)
{
    $value = strtolower(trim((string) $value));
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);

    return trim($value, '_');
}

function repaf_get_mapped_pdf_coordinate($value, $map, $coordinates)
{
    if (!isset($map[$value])) {
        return array();
    }

    $coord_key = $map[$value];

    if (!isset($coordinates[$coord_key])) {
        return array();
    }

    return $coordinates[$coord_key];
}

function repaf_get_buyer_civil_status_coordinate($civil_status, $coordinates)
{
    $civil_status = repaf_normalize_pdf_option($civil_status);

    $civil_status_map = array(
        'single'            => 'buyer_civil_single',
        'married'           => 'buyer_civil_married',
        'widow_er'          => 'buyer_civil_widow',
        'annulled'          => 'buyer_civil_annulled',
        'legally_separated' => 'buyer_civil_separated',
    );

    if (!isset($civil_status_map[$civil_status])) {
        return array();
    }

    $coord_key = $civil_status_map[$civil_status];

    if (!isset($coordinates[$coord_key])) {
        return array();
    }

    return $coordinates[$coord_key];
}

function repaf_get_buyer_home_ownership_coordinate($home_ownership, $coordinates)
{
    $home_ownership = repaf_normalize_pdf_option($home_ownership);

    $home_ownership_map = array(
        'owned'                 => 'buyer_home_owned',
        'company'               => 'buyer_home_company',
        'living_with_relatives' => 'buyer_home_living_with_relatives',
        'mortgage'              => 'buyer_home_mortgage',
        'rented'                => 'buyer_home_rented',
    );

    return repaf_get_mapped_pdf_coordinate($home_ownership, $home_ownership_map, $coordinates);
}

function repaf_get_buyer_occupation_coordinate($occupation, $coordinates)
{
    $occupation = repaf_normalize_pdf_option($occupation);

    $occupation_map = array(
        'locally_employed_private'    => 'buyer_occupation_private',
        'locally_employed_government' => 'buyer_occupation_government',
        'self_employed_professional'  => 'buyer_occupation_self_employed',
        'ofw'                         => 'buyer_occupation_ofw',
        'unemployed'                  => 'buyer_occupation_unemployed',
        'others'                      => 'buyer_occupation_others',
    );

    return repaf_get_mapped_pdf_coordinate($occupation, $occupation_map, $coordinates);
}

function repaf_get_pdf_gender_coordinate($gender, $field_prefix, $coordinates)
{
    $gender = repaf_normalize_pdf_option($gender);

    $gender_map = array(
        'male'   => $field_prefix . '_gender_male',
        'female' => $field_prefix . '_gender_female',
    );

    return repaf_get_mapped_pdf_coordinate($gender, $gender_map, $coordinates);
}

function repaf_get_pdf_occupation_coordinate($occupation, $field_prefix, $coordinates)
{
    $occupation = repaf_normalize_pdf_option($occupation);

    $occupation_map = array(
        'locally_employed_private'    => $field_prefix . '_occupation_private',
        'locally_employed_government' => $field_prefix . '_occupation_government',
        'self_employed_professional'  => $field_prefix . '_occupation_self_employed',
        'ofw'                         => $field_prefix . '_occupation_ofw',
        'unemployed'                  => $field_prefix . '_occupation_unemployed',
        'others'                      => $field_prefix . '_occupation_others',
    );

    return repaf_get_mapped_pdf_coordinate($occupation, $occupation_map, $coordinates);
}

function repaf_get_pdf_role_coordinate($role, $field_prefix, $coordinates)
{
    $role = repaf_normalize_pdf_option($role);

    $role_map = array(
        'co_owner'    => $field_prefix . '_role_co_owner',
        'co_borrower' => $field_prefix . '_role_co_borrower',
        'financier'   => $field_prefix . '_role_financier',
    );

    return repaf_get_mapped_pdf_coordinate($role, $role_map, $coordinates);
}

function repaf_get_pdf_civil_status_coordinate($civil_status, $field_prefix, $coordinates)
{
    $civil_status = repaf_normalize_pdf_option($civil_status);

    $civil_status_map = array(
        'single'            => $field_prefix . '_civil_single',
        'married'           => $field_prefix . '_civil_married',
        'widow_er'          => $field_prefix . '_civil_widow',
        'annulled'          => $field_prefix . '_civil_annulled',
        'legally_separated' => $field_prefix . '_civil_separated',
    );

    return repaf_get_mapped_pdf_coordinate($civil_status, $civil_status_map, $coordinates);
}

function repaf_get_pdf_home_ownership_coordinate($home_ownership, $field_prefix, $coordinates)
{
    $home_ownership = repaf_normalize_pdf_option($home_ownership);

    $home_ownership_map = array(
        'owned'                 => $field_prefix . '_home_owned',
        'company'               => $field_prefix . '_home_company',
        'living_with_relatives' => $field_prefix . '_home_living_with_relatives',
        'mortgage'              => $field_prefix . '_home_mortgage',
        'rented'                => $field_prefix . '_home_rented',
    );

    return repaf_get_mapped_pdf_coordinate($home_ownership, $home_ownership_map, $coordinates);
}

/**
 * Loads the uploaded signature (PNG or JPG), converts any pixel
 * whose R, G and B channels are all >= 230 to fully transparent,
 * and saves the result as a 32-bit PNG.
 *
 * If the input was a JPG a new .png file is created and the
 * original .jpg is deleted. Returns the path to the processed
 * PNG, or the original path when GD is absent.
 *
 * @param  string $path  Absolute path to the uploaded file.
 * @return string        Absolute path to the processed PNG.
 */
function repaf_process_signature_transparency($path)
{
    if (!function_exists('imagecreatetruecolor')) {
        return $path;
    }

    $info = getimagesize($path);

    if (!$info) {
        return $path;
    }

    $mime = $info['mime'];

    if ($mime === 'image/png') {
        $src = imagecreatefrompng($path);
    } elseif ($mime === 'image/jpeg') {
        $src = imagecreatefromjpeg($path);
    } else {
        return $path;
    }

    if (!$src) {
        return $path;
    }

    imagepalettetotruecolor($src);

    $w = imagesx($src);
    $h = imagesy($src);

    $out = imagecreatetruecolor($w, $h);
    imagealphablending($out, false);
    imagesavealpha($out, true);

    $fill = imagecolorallocatealpha($out, 255, 255, 255, 127);
    imagefill($out, 0, 0, $fill);

    $threshold = 230;

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgba  = imagecolorat($src, $x, $y);
            $a     = ($rgba >> 24) & 0x7F;
            $r     = ($rgba >> 16) & 0xFF;
            $g     = ($rgba >> 8)  & 0xFF;
            $b     =  $rgba        & 0xFF;

            if ($a > 0) {
                $out_a = $a;
            } elseif ($r >= $threshold && $g >= $threshold && $b >= $threshold) {
                $out_a = 127;
            } else {
                $out_a = 0;
            }

            imagesetpixel(
                $out,
                $x,
                $y,
                imagecolorallocatealpha($out, $r, $g, $b, $out_a)
            );
        }
    }

    $png_path = preg_replace('/\.(jpe?g)$/i', '.png', $path);
    imagepng($out, $png_path);

    if ($png_path !== $path) {
        wp_delete_file($path);
    }

    return $png_path;
}
