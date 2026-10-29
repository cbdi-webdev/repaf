<?php
if (!defined('ABSPATH')) exit;

/**
 * Step 9 — Property Information
 *
 * In buyer mode  : all inputs are disabled (visible but not fillable).
 * In edit mode   : all inputs are enabled and pre-populated from $repaf_edit_data.
 *
 * $repaf_edit_mode (bool)  and $repaf_edit_data (array) are set by form-render.php
 * before this file is included.
 */

$repaf_edit_mode = $repaf_edit_mode ?? false;
$repaf_edit_data = $repaf_edit_data ?? [];

$_sp  = !empty($repaf_edit_mode);
$_dis = $_sp ? '' : 'disabled';
$_req = $_sp ? 'required aria-required="true"' : '';
$_v   = function ($key) use ($repaf_edit_data) {
    $val = $repaf_edit_data[$key] ?? '';
    return esc_attr(is_array($val) ? '' : (string) $val);
};
$_sel = function ($key, $value) use ($repaf_edit_data) {
    $saved = $repaf_edit_data[$key] ?? '';
    return (string) $saved === (string) $value ? ' selected' : '';
};
$_chk = function ($key, $value) use ($repaf_edit_data) {
    $saved = $repaf_edit_data[$key] ?? '';
    return (string) $saved === (string) $value ? ' checked' : '';
};
$vatable = (string) ($repaf_edit_data['prop_is_vatable'] ?? '') === 'yes';
$selected_discounts = \Repaf\repaf_property_discount_types($repaf_edit_data);
$vat_percent_state = ($_sp && $vatable) ? 'required aria-required="true"' : 'disabled';

// Build reservation date value from stored day/month/year
$_res_day   = $repaf_edit_data['prop_reservation_day']   ?? '';
$_res_month = $repaf_edit_data['prop_reservation_month'] ?? '';
$_res_year  = $repaf_edit_data['prop_reservation_year']  ?? '';
$_res_date  = '';
if ($_res_day !== '' && $_res_month !== '' && $_res_year !== '') {
    $_res_date = sprintf('%04d-%02d-%02d', (int) $_res_year, (int) $_res_month, (int) $_res_day);
}

$_money = function ($key) use ($repaf_edit_data) {
    $val = $repaf_edit_data[$key] ?? '';
    if ($val !== '' && is_numeric($val)) {
        return esc_attr(number_format((float) $val, 2));
    }
    return esc_attr((string) $val);
};
$_whole_money = function ($key) use ($repaf_edit_data) {
    $val = $repaf_edit_data[$key] ?? '';
    if ($val !== '' && is_numeric($val)) {
        return esc_attr(number_format(round((float) $val), 0, '.', ','));
    }
    return esc_attr((string) $val);
};

$_project_names  = function_exists('Repaf\\repaf_get_project_names')
    ? \Repaf\repaf_get_project_names()
    : [];
$_current_project = (string) ($repaf_edit_data['prop_project'] ?? '');
if ($_current_project !== '' && !in_array($_current_project, $_project_names, true)) {
    array_unshift($_project_names, $_current_project);
}
?>

<section class="repaf-step<?= $_sp ? '' : ' repaf-step--sp-only' ?>" data-step="8">
    <h3>Property Information</h3>

    <?php if (!$_sp): ?>
        <div class="repaf-sp-only-banner">
            &#128274; This section will be completed by your sales representative.
        </div>
    <?php endif; ?>

    <!-- Property Details -->
    <fieldset class="repaf-fieldset">
        <legend>Property Details</legend>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Reservation Date<?php if ($_sp): ?> <span class="repaf-required">*</span><?php endif; ?></label>
                <input type="date" name="prop_reservation_date" data-reservation-date data-validation-label="Reservation Date" value="<?= esc_attr($_res_date) ?>" <?= $_req ?> <?= $_dis ?>>
                <input type="hidden" name="prop_reservation_day" value="<?= esc_attr($_res_day) ?>">
                <input type="hidden" name="prop_reservation_month" value="<?= esc_attr($_res_month) ?>">
                <input type="hidden" name="prop_reservation_year" value="<?= esc_attr($_res_year) ?>">
            </div>
            <div class="repaf-field">
                <label>Project Name<?php if ($_sp): ?> <span class="repaf-required">*</span><?php endif; ?></label>
                <select name="prop_project" <?= $_req ?> <?= $_dis ?>>
                    <option value="">&mdash; Select project &mdash;</option>
                    <?php foreach ($_project_names as $_project_name): ?>
                        <option value="<?= esc_attr($_project_name) ?>"<?= $_sel('prop_project', $_project_name) ?>><?= esc_html($_project_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Phase<?php if ($_sp): ?> <span class="repaf-required">*</span><?php endif; ?></label>
                <input type="text" name="prop_phase" value="<?= $_v('prop_phase') ?>" <?= $_req ?> <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Block<?php if ($_sp): ?> <span class="repaf-required">*</span><?php endif; ?></label>
                <input type="text" name="prop_block" value="<?= $_v('prop_block') ?>" <?= $_req ?> <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Lot<?php if ($_sp): ?> <span class="repaf-required">*</span><?php endif; ?></label>
                <input type="text" name="prop_lot" value="<?= $_v('prop_lot') ?>" <?= $_req ?> <?= $_dis ?>>
            </div>
        </div>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Lot Area (sqm)</label>
                <input type="number" name="prop_lot_area" value="<?= $_v('prop_lot_area') ?>" min="0" step="0.01" <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Floor Area (sqm)</label>
                <input type="number" name="prop_floor_area" value="<?= $_v('prop_floor_area') ?>" min="0" step="0.01" <?= $_dis ?>>
            </div>
        </div>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>House Finish</label>
                <select name="prop_house_finish" <?= $_dis ?>>
                    <option value="">— Select —</option>
                    <option value="Basic" <?= $_sel('prop_house_finish', 'Basic') ?>>Basic</option>
                    <option value="Complete" <?= $_sel('prop_house_finish', 'Complete') ?>>Complete</option>
                    <option value="Others" <?= $_sel('prop_house_finish', 'Others') ?>>Others</option>
                </select>
            </div>
            <div class="repaf-field repaf-field--conditional" data-show-when="prop_house_finish=Others">
                <label>House Finish (Others)<?php if ($_sp): ?> <span class="repaf-required">*</span><?php endif; ?></label>
                <input
                    type="text"
                    name="prop_house_finish_other"
                    value="<?= $_v('prop_house_finish_other') ?>"
                    data-validation-label="House Finish (Others)"
                    <?= $_sp ? 'required aria-required="true" data-required-when-visible' : 'disabled' ?>
                >
            </div>
            <div class="repaf-field">
                <label>House Status</label>
                <select name="prop_house_status" <?= $_dis ?>>
                    <option value="">&mdash; Select house status &mdash;</option>
                    <option value="RFO"<?= $_sel('prop_house_status', 'RFO') ?>>RFO</option>
                    <option value="NRFO"<?= $_sel('prop_house_status', 'NRFO') ?>>NRFO</option>
                    <option value="Ongoing"<?= $_sel('prop_house_status', 'Ongoing') ?>>Ongoing</option>
                </select>
            </div>
            <div class="repaf-field">
                <label>Place of Reservation</label>
                <select name="prop_place_of_reservation" <?= $_dis ?>>
                    <option value="">— Select —</option>
                    <option value="On-Site" <?= $_sel('prop_place_of_reservation', 'On-Site') ?>>On-Site</option>
                    <option value="Branch Office" <?= $_sel('prop_place_of_reservation', 'Branch Office') ?>>Branch Office</option>
                    <option value="Head Office" <?= $_sel('prop_place_of_reservation', 'Head Office') ?>>Head Office</option>
                    <option value="Others" <?= $_sel('prop_place_of_reservation', 'Others') ?>>Others</option>
                </select>
            </div>
            <div class="repaf-field repaf-field--conditional" data-show-when="prop_place_of_reservation=Others">
                <label>Place (Others)</label>
                <input type="text" name="prop_place_other" value="<?= $_v('prop_place_other') ?>" <?= $_dis ?>>
            </div>
        </div>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Product Type</label>
                <select name="prop_product_type" <?= $_dis ?>>
                    <option value="">— Select —</option>
                    <option value="House &amp; Lot" <?= $_sel('prop_product_type', 'House & Lot') ?>>House &amp; Lot</option>
                    <option value="Lot Only" <?= $_sel('prop_product_type', 'Lot Only') ?>>Lot Only</option>
                    <option value="Others" <?= $_sel('prop_product_type', 'Others') ?>>Others</option>
                </select>
            </div>
            <div class="repaf-field repaf-field--conditional" data-show-when="prop_product_type=Others">
                <label>Product Type (Others)</label>
                <input type="text" name="prop_product_other" value="<?= $_v('prop_product_other') ?>" <?= $_dis ?>>
            </div>
        </div>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Payment Type<?php if ($_sp): ?> <span class="repaf-required">*</span><?php endif; ?></label>
                <select name="prop_payment_type" data-payment-type-select <?= $_req ?> <?= $_dis ?>>
                    <option value="">— Select —</option>
                    <option value="Spot Cash" <?= $_sel('prop_payment_type', 'Spot Cash') ?>>Spot Cash</option>
                    <option value="Deferred Cash" <?= $_sel('prop_payment_type', 'Deferred Cash') ?>>Deferred Cash</option>
                    <option value="With Financing" <?= $_sel('prop_payment_type', 'With Financing') ?>>With Financing</option>
                </select>
            </div>
            <div class="repaf-field">
                <label>Financing<?php if ($_sp): ?> <span class="repaf-required">*</span><?php endif; ?></label>
                <select name="prop_financing" data-financing-select <?= $_req ?> <?= $_dis ?>>
                    <option value="">— Select —</option>
                    <option value="Bank" <?= $_sel('prop_financing', 'Bank') ?>>Bank</option>
                    <option value="In-House" <?= $_sel('prop_financing', 'In-House') ?>>In-House</option>
                    <option value="HDMF" <?= $_sel('prop_financing', 'HDMF') ?>>HDMF</option>
                    <option value="Others" <?= $_sel('prop_financing', 'Others') ?>>Others</option>
                </select>
                <input type="hidden" name="prop_financing" value="Others" data-cash-payment-financing disabled>
            </div>
        </div>

    </fieldset>

    <!-- Mode of Payment -->
    <fieldset class="repaf-fieldset">
        <legend>Mode of Payment</legend>
        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Mode of Payment</label>
                <select name="prop_mode_of_payment" <?= $_dis ?>>
                    <option value="">— Select —</option>
                    <option value="Cash" <?= $_sel('prop_mode_of_payment', 'Cash') ?>>Cash</option>
                    <option value="Post Dated Check" <?= $_sel('prop_mode_of_payment', 'Post Dated Check') ?>>Post Dated Check</option>
                    <option value="Bills Payment or Online" <?= $_sel('prop_mode_of_payment', 'Bills Payment or Online') ?>>Bills Payment or Online</option>
                    <option value="Other" <?= $_sel('prop_mode_of_payment', 'Other') ?>>Other</option>
                </select>
            </div>
            <div class="repaf-field repaf-field--conditional" data-show-when="prop_mode_of_payment=Other">
                <label>Mode of Payment (Other)</label>
                <input type="text" name="prop_mode_other" value="<?= $_v('prop_mode_other') ?>" <?= $_dis ?>>
            </div>
        </div>
    </fieldset>

    <!-- Bank Charges -->
    <fieldset class="repaf-fieldset">
        <legend>Bank Charges</legend>
        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Total Amount Due (&#8369;)</label>
                <input type="text" inputmode="decimal" name="prop_loan_amount" value="<?= $_money('prop_loan_amount') ?>" class="repaf-money-input" <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Terms (years)</label>
                <input type="number" name="prop_loan_term" value="<?= $_v('prop_loan_term') ?>" min="0" <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Monthly Due (&#8369;)</label>
                <input type="text" inputmode="decimal" name="prop_loan_monthly" value="<?= $_money('prop_loan_monthly') ?>" class="repaf-money-input" <?= $_dis ?>>
            </div>
        </div>
        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Starts On</label>
                <input type="date" name="prop_loan_starts_on" value="<?= $_v('prop_loan_starts_on') ?>" <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Ends On</label>
                <input type="date" name="prop_loan_ends_on" value="<?= $_v('prop_loan_ends_on') ?>" <?= $_dis ?>>
            </div>
        </div>
    </fieldset>

    <!-- Insurance (conditional — In-House only) -->
    <div class="repaf-field--conditional" data-show-when="prop_financing=In-House">
        <fieldset class="repaf-fieldset">
            <legend>Insurance</legend>
            <div class="repaf-field-row">
                <div class="repaf-field">
                    <label>MRI (&#8369;)</label>
                    <input type="text" inputmode="decimal" name="prop_mri" value="<?= $_money('prop_mri') ?>" class="repaf-money-input" <?= $_dis ?>>
                </div>
                <div class="repaf-field">
                    <label>Fire Insurance (&#8369;)</label>
                    <input type="text" inputmode="decimal" name="prop_fi" value="<?= $_money('prop_fi') ?>" class="repaf-money-input" <?= $_dis ?>>
                </div>
            </div>
        </fieldset>
    </div>

    <!-- Computation -->
    <fieldset class="repaf-fieldset">
        <legend>Computation</legend>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Total List Price (&#8369;)</label>
                <input type="text" inputmode="decimal" name="prop_total_list_price" value="<?= $_money('prop_total_list_price') ?>" class="repaf-money-input" <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Total Selling Price (&#8369;)</label>
                <input type="text" inputmode="decimal" name="prop_total_selling_price" value="<?= $_money('prop_total_selling_price') ?>" class="repaf-money-input" <?= $_dis ?>>
            </div>
        </div>

        <div class="repaf-field">
            <label>Less Discount on List Price</label>
            <input type="hidden" name="prop_discount_types[]" value="" <?= $_dis ?>>
            <div class="repaf-discount-options">
                <label class="repaf-inline-radio">
                    <input type="checkbox" name="prop_discount_types[]" value="Full TCP" <?= in_array('Full TCP', $selected_discounts, true) ? 'checked' : '' ?> <?= $_dis ?>>
                    Full TCP
                </label>
                <input type="number" name="prop_discount_tcp_percent" placeholder="%" value="<?= $_v('prop_discount_tcp_percent') ?>" style="width:60px" <?= $_dis ?>>
                <span>% if paid within</span>
                <input type="number" name="prop_discount_tcp_days" placeholder="days" value="<?= $_v('prop_discount_tcp_days') ?>" style="width:60px" <?= $_dis ?>>
                <span>days</span>
                <span>Amount (&#8369;)</span>
                <input type="text" inputmode="decimal" name="prop_discount_tcp_amount" value="<?= $_money('prop_discount_tcp_amount') ?>" class="repaf-money-input repaf-discount-amount" aria-label="Full TCP discount amount" <?= $_dis ?>>
            </div>
            <div class="repaf-discount-options">
                <label class="repaf-inline-radio">
                    <input type="checkbox" name="prop_discount_types[]" value="Full DP" <?= in_array('Full DP', $selected_discounts, true) ? 'checked' : '' ?> <?= $_dis ?>>
                    Full DP
                </label>
                <input type="number" name="prop_discount_dp_percent" placeholder="%" value="<?= $_v('prop_discount_dp_percent') ?>" style="width:60px" <?= $_dis ?>>
                <span>% if paid within</span>
                <input type="number" name="prop_discount_dp_days" placeholder="days" value="<?= $_v('prop_discount_dp_days') ?>" style="width:60px" <?= $_dis ?>>
                <span>days</span>
                <span>Amount (&#8369;)</span>
                <input type="text" inputmode="decimal" name="prop_discount_dp_amount" value="<?= $_money('prop_discount_dp_amount') ?>" class="repaf-money-input repaf-discount-amount" aria-label="Full DP discount amount" <?= $_dis ?>>
            </div>
            <div class="repaf-discount-options">
                <label class="repaf-inline-radio">
                    <input type="checkbox" name="prop_discount_types[]" value="Others" <?= in_array('Others', $selected_discounts, true) ? 'checked' : '' ?> <?= $_dis ?>>
                    Others
                </label>
                <input type="text" name="prop_discount_others" value="<?= $_v('prop_discount_others') ?>" style="flex:1" <?= $_dis ?>>
                <span>Amount (&#8369;)</span>
                <input type="text" inputmode="decimal" name="prop_discount_others_amount" value="<?= $_money('prop_discount_others_amount') ?>" class="repaf-money-input repaf-discount-amount" aria-label="Other discount amount" <?= $_dis ?>>
            </div>
        </div>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Misc. Fee (%)</label>
                <input type="number" name="prop_misc_fee_percent" value="<?= $_v('prop_misc_fee_percent') ?>" min="0" max="100" step="0.01" placeholder="e.g. 12" <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Misc. Fee (&#8369;)</label>
                <input type="text" inputmode="decimal" name="prop_misc_fee" value="<?= $_money('prop_misc_fee') ?>" class="repaf-money-input" placeholder="Auto-computed" readonly <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>VATable?</label>
                <input type="hidden" name="prop_is_vatable" value="no" <?= $_dis ?>>
                <label class="repaf-inline-radio">
                    <input type="checkbox" name="prop_is_vatable" value="yes" data-vat-toggle <?= $_chk('prop_is_vatable', 'yes') ?> <?= $_dis ?>>
                    Yes, apply VAT
                </label>
            </div>
            <div class="repaf-field">
                <label>VAT (%)</label>
                <input type="number" name="prop_vat_percent" value="<?= $_v('prop_vat_percent') ?>" min="0" max="100" step="0.01" placeholder="Enter VAT %" data-validation-label="VAT (%)" <?= $vat_percent_state ?>>
            </div>
            <div class="repaf-field">
                <label>VAT (&#8369;)</label>
                <input type="text" inputmode="decimal" name="prop_vat" value="<?= $_money('prop_vat') ?>" class="repaf-money-input" placeholder="Auto-computed" readonly <?= $_dis ?>>
            </div>
        </div>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Total Contract Price (&#8369;)<?php if ($_sp): ?> <span class="repaf-required">*</span><?php endif; ?></label>
                <input type="text" inputmode="decimal" name="prop_total_contract_price" value="<?= $_money('prop_total_contract_price') ?>" class="repaf-money-input" data-validation-label="Total Contract Price" <?= $_req ?> <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Required Down Payment (%)</label>
                <input type="number" name="prop_dp_percent" value="<?= $_v('prop_dp_percent') ?>" min="0" max="100" step="0.01" placeholder="Auto-computed" readonly <?= $_dis ?>>
            </div>
        </div>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Down Payment Amount (&#8369;)</label>
                <input type="text" inputmode="decimal" name="prop_dp_amount" value="<?= $_money('prop_dp_amount') ?>" class="repaf-money-input" placeholder="Auto-computed" readonly <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Reservation Fee (&#8369;)</label>
                <input type="text" inputmode="decimal" name="prop_reservation_fee" value="<?= $_money('prop_reservation_fee') ?>" class="repaf-money-input" <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Balance on DP (&#8369;)</label>
                <input type="text" inputmode="decimal" name="prop_balance_on_dp" value="<?= $_money('prop_balance_on_dp') ?>" class="repaf-money-input" <?= $_dis ?>>
            </div>
        </div>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>No. of Months to Pay<?php if ($_sp): ?> <span class="repaf-required">*</span><?php endif; ?></label>
                <input type="number" name="prop_dp_months" value="<?= $_v('prop_dp_months') ?>" min="0" <?= $_req ?> <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Monthly DP Amount (&#8369;)</label>
                <input type="text" inputmode="decimal" name="prop_dp_monthly" value="<?= $_money('prop_dp_monthly') ?>" class="repaf-money-input" <?= $_dis ?>>
            </div>
        </div>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>DP Starts On<?php if ($_sp): ?> <span class="repaf-required">*</span><?php endif; ?></label>
                <input type="date" name="prop_dp_starts_on" value="<?= $_v('prop_dp_starts_on') ?>" <?= $_req ?> <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>DP Ends On</label>
                <input type="date" name="prop_dp_ends_on" value="<?= $_v('prop_dp_ends_on') ?>" readonly <?= $_dis ?>>
            </div>
        </div>

        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>% Balance / Loanable</label>
                <input type="number" name="prop_loan_percent" value="<?= $_v('prop_loan_percent') ?>" min="0" step="0.01" placeholder="Auto-computed" readonly <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Loanable Amount (&#8369;)<?php if ($_sp): ?> <span class="repaf-required">*</span><?php endif; ?></label>
                <input type="text" inputmode="decimal" name="prop_loanable_amount" value="<?= $_money('prop_loanable_amount') ?>" class="repaf-money-input" placeholder="Enter loanable amount" <?= $_req ?> <?= $_dis ?>>
            </div>
        </div>
    </fieldset>

    <!-- Financing -->
    <fieldset class="repaf-fieldset">
        <legend>Financing</legend>
        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Monthly Amortization Amount (&#8369;)</label>
                <input type="text" inputmode="decimal" name="prop_inhouse_monthly_amort" value="<?= $_money('prop_inhouse_monthly_amort') ?>" class="repaf-money-input" placeholder="Auto-computed" readonly <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Loan Terms (years)<?php if ($_sp): ?> <span class="repaf-required">*</span><?php endif; ?></label>
                <input type="number" name="prop_inhouse_loan_terms" value="<?= $_v('prop_inhouse_loan_terms') ?>" min="0" data-validation-label="Loan Terms (years)" <?= $_req ?> <?= $_dis ?>>
            </div>
        </div>
        <div class="repaf-field-row">
            <div class="repaf-field">
                <label>Interest Rate / Indicative (%)<?php if ($_sp): ?> <span class="repaf-required">*</span><?php endif; ?></label>
                <input type="number" name="prop_inhouse_interest_rate" value="<?= $_v('prop_inhouse_interest_rate') ?>" min="0" step="0.01" data-validation-label="Interest Rate / Indicative (%)" <?= $_req ?> <?= $_dis ?>>
            </div>
            <div class="repaf-field">
                <label>Starts On</label>
                <input type="date" name="prop_inhouse_starts_on" value="<?= $_v('prop_inhouse_starts_on') ?>" <?= $_dis ?>>
            </div>
        </div>
    </fieldset>

    <div class="repaf-actions">
        <button type="button" class="repaf-back">Back</button>
        <button type="button" class="repaf-next">Next</button>
    </div>
</section>
