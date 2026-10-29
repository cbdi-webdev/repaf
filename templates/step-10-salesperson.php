<?php
if (!defined('ABSPATH')) exit;

/**
 * Step 10 — Salesperson Information
 *
 * In buyer mode  : all inputs are disabled (visible but not fillable).
 * In edit mode   : all inputs are enabled and pre-populated from $repaf_edit_data.
 *
 * $repaf_edit_mode (bool) and $repaf_edit_data (array) are set by form-render.php
 * before this file is included.
 */

$repaf_edit_mode = $repaf_edit_mode ?? false;
$repaf_edit_data = $repaf_edit_data ?? [];

$_sp  = !empty($repaf_edit_mode);
$_dis = $_sp ? '' : 'disabled';
$_v   = function ($key) use ($repaf_edit_data) {
    $val = $repaf_edit_data[$key] ?? '';
    return esc_attr(is_array($val) ? '' : (string) $val);
};
?>

<section class="repaf-step<?= $_sp ? '' : ' repaf-step--sp-only' ?>" data-step="9">
    <h3>Salesperson Information</h3>

    <?php if (!$_sp): ?>
    <div class="repaf-sp-only-banner">
        &#128274; This section will be completed by your sales representative.
    </div>
    <?php endif; ?>

    <fieldset class="repaf-fieldset">
        <legend>Sales Team</legend>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Agent Name</label>
                <input type="text" name="sp_agent_name" value="<?= $_v('sp_agent_name') ?>" <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Manager Name</label>
                <input type="text" name="sp_manager_name" value="<?= $_v('sp_manager_name') ?>" <?= $_dis ?>>
            </div>
        </div>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Broker Name</label>
                <input type="text" name="sp_broker_name" value="<?= $_v('sp_broker_name') ?>" <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Realty Name</label>
                <input type="text" name="sp_realty_name" value="<?= $_v('sp_realty_name') ?>" <?= $_dis ?>>
            </div>
        </div>
    </fieldset>

    <fieldset class="repaf-fieldset">
        <legend>Approval Signatories</legend>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Prepared &amp; Interviewed By - Sales Operations Assistant</label>
                <input type="text" name="sp_prepared_by" value="<?= $_v('sp_prepared_by') ?>" <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Verified By - Sales Operations Head</label>
                <input type="text" name="sp_verified_by" value="<?= $_v('sp_verified_by') ?>" <?= $_dis ?>>
            </div>
        </div>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Checked By - Reservation &amp; Documentation Assistant</label>
                <input type="text" name="sp_checked_by" value="<?= $_v('sp_checked_by') ?>" <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Approved By - Reservation &amp; Documentation Head</label>
                <input type="text" name="sp_approved_by" value="<?= $_v('sp_approved_by') ?>" <?= $_dis ?>>
            </div>
        </div>
    </fieldset>

    <fieldset class="repaf-fieldset">
        <legend>Remarks</legend>
        <div class="repaf-field">
            <label>Remarks</label>
            <textarea name="sp_remarks" rows="4" <?= $_dis ?>><?= esc_textarea($repaf_edit_data['sp_remarks'] ?? '') ?></textarea>
        </div>
    </fieldset>

    <div class="repaf-actions">
        <button type="button" class="repaf-back">Back</button>
        <button type="button" class="repaf-next">Next</button>
    </div>
</section>
