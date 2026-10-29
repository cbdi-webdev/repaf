<?php if (!defined('ABSPATH')) exit;

$_f_ed  = $GLOBALS['_repaf_edit_data'] ?? [];
$_f_em  = !empty($GLOBALS['_repaf_edit_mode']);
$_fv    = function ($key) use ($_f_ed) {
    $v = $_f_ed[$key] ?? '';
    return $v !== '' ? ' value="' . esc_attr((string) $v) . '"' : '';
};

$_fin_banks = $_f_em && !empty($_f_ed['fin_bank']) && is_array($_f_ed['fin_bank'])
    ? $_f_ed['fin_bank'] : [];
$_fin_loans = $_f_em && !empty($_f_ed['fin_loan']) && is_array($_f_ed['fin_loan'])
    ? $_f_ed['fin_loan'] : [];
$_fin_refs  = $_f_em && !empty($_f_ed['fin_ref'])  && is_array($_f_ed['fin_ref'])
    ? $_f_ed['fin_ref']  : [];
$_fin_bank_account_types = \Repaf\repaf_bank_account_type_options();
?>

<section class="repaf-step" data-step="7">
    <h3>Your Financial Information</h3>

    <!-- =====================================================
         Section A: Monthly Income
         ===================================================== -->
    <div class="repaf-fin-section">
        <h4 class="repaf-fin-heading">Monthly Income (Income Should be in PHP)</h4>

        <div class="repaf-fin-table-wrapper">
            <table class="repaf-fin-table repaf-fin-income-table">
                <thead>
                    <tr>
                        <th class="repaf-fin-col-source">Sources</th>
                        <th class="repaf-fin-col-amount">Borrower</th>
                        <th class="repaf-fin-col-spouse">Spouse</th>
                        <th class="repaf-fin-col-co">Co-Borrower / Financer</th>
                        <th class="repaf-fin-col-amount">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="repaf-fin-col-source">Basic Salary / Business Income</td>
                        <td class="repaf-fin-col-amount">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_buyer_salary"                                    class="repaf-fin-amount repaf-money-input" placeholder="0.00"
                                    data-fin-row="salary" data-fin-col="buyer"<?= $_fv('fin_buyer_salary') ?>>
                                <span class="repaf-fin-monthly">/ monthly</span>
                            </div>
                        </td>
                        <td class="repaf-fin-col-spouse">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_spouse_salary"                                    class="repaf-fin-amount repaf-money-input" placeholder="0.00"
                                    data-fin-row="salary" data-fin-col="spouse"<?= $_fv('fin_spouse_salary') ?>>
                                <span class="repaf-fin-monthly">/ monthly</span>
                            </div>
                        </td>
                        <td class="repaf-fin-col-co">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_co_salary"                                    class="repaf-fin-amount repaf-money-input" placeholder="0.00"
                                    data-fin-row="salary" data-fin-col="co"<?= $_fv('fin_co_salary') ?>>
                                <span class="repaf-fin-monthly">/ monthly</span>
                            </div>
                        </td>
                        <td class="repaf-fin-col-amount">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_total_salary"                                    class="repaf-fin-amount repaf-fin-total repaf-money-input" placeholder="0.00"
                                    data-fin-row="salary" readonly>
                                <span class="repaf-fin-monthly">/ monthly</span>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="repaf-fin-col-source">Allowance (COLA/PERA)</td>
                        <td class="repaf-fin-col-amount">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_buyer_allowance" class="repaf-fin-amount repaf-money-input" placeholder="0.00"
                                    data-fin-row="allowance" data-fin-col="buyer"<?= $_fv('fin_buyer_allowance') ?>>
                                <span class="repaf-fin-monthly">/ monthly</span>
                            </div>
                        </td>
                        <td class="repaf-fin-col-spouse">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_spouse_allowance" class="repaf-fin-amount repaf-money-input" placeholder="0.00"
                                    data-fin-row="allowance" data-fin-col="spouse"<?= $_fv('fin_spouse_allowance') ?>>
                                <span class="repaf-fin-monthly">/ monthly</span>
                            </div>
                        </td>
                        <td class="repaf-fin-col-co">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_co_allowance" class="repaf-fin-amount repaf-money-input" placeholder="0.00"
                                    data-fin-row="allowance" data-fin-col="co"<?= $_fv('fin_co_allowance') ?>>
                                <span class="repaf-fin-monthly">/ monthly</span>
                            </div>
                        </td>
                        <td class="repaf-fin-col-amount">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_total_allowance" class="repaf-fin-amount repaf-fin-total repaf-money-input" placeholder="0.00"
                                    data-fin-row="allowance" readonly>
                                <span class="repaf-fin-monthly">/ monthly</span>
                            </div>
                        </td>
                    </tr>

                    <tr class="repaf-fin-row-bold">
                        <td class="repaf-fin-col-source"><strong>Commissions</strong></td>
                        <td class="repaf-fin-col-amount">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_buyer_commission" class="repaf-fin-amount repaf-money-input" placeholder="0.00"
                                    data-fin-row="commission" data-fin-col="buyer"<?= $_fv('fin_buyer_commission') ?>>
                            </div>
                        </td>
                        <td class="repaf-fin-col-spouse">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_spouse_commission" class="repaf-fin-amount repaf-money-input" placeholder="0.00"
                                    data-fin-row="commission" data-fin-col="spouse"<?= $_fv('fin_spouse_commission') ?>>
                            </div>
                        </td>
                        <td class="repaf-fin-col-co">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_co_commission" class="repaf-fin-amount repaf-money-input" placeholder="0.00"
                                    data-fin-row="commission" data-fin-col="co"<?= $_fv('fin_co_commission') ?>>
                            </div>
                        </td>
                        <td class="repaf-fin-col-amount">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_total_commission" class="repaf-fin-amount repaf-fin-total repaf-money-input" placeholder="0.00"
                                    data-fin-row="commission" readonly>
                            </div>
                        </td>
                    </tr>

                    <tr class="repaf-fin-row-bold">
                        <td class="repaf-fin-col-source"><strong>Other Income</strong></td>
                        <td class="repaf-fin-col-amount">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_buyer_other_amount" class="repaf-fin-amount repaf-money-input" placeholder="0.00"
                                    data-fin-row="other" data-fin-col="buyer"<?= $_fv('fin_buyer_other_amount') ?>>
                            </div>
                        </td>
                        <td class="repaf-fin-col-spouse">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_spouse_other_amount" class="repaf-fin-amount repaf-money-input" placeholder="0.00"
                                    data-fin-row="other" data-fin-col="spouse"<?= $_fv('fin_spouse_other_amount') ?>>
                            </div>
                        </td>
                        <td class="repaf-fin-col-co">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_co_other_amount" class="repaf-fin-amount repaf-money-input" placeholder="0.00"
                                    data-fin-row="other" data-fin-col="co"<?= $_fv('fin_co_other_amount') ?>>
                            </div>
                        </td>
                        <td class="repaf-fin-col-amount">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_total_other" class="repaf-fin-amount repaf-fin-total repaf-money-input" placeholder="0.00"
                                    data-fin-row="other" readonly>
                            </div>
                        </td>
                    </tr>
                    <tr class="repaf-fin-row-bold repaf-fin-person-total-row">
                        <td class="repaf-fin-col-source"><strong>Total Monthly Income</strong></td>
                        <td class="repaf-fin-col-amount">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_buyer_total"
                                    class="repaf-fin-amount repaf-fin-person-total repaf-money-input" placeholder="0.00" readonly>
                            </div>
                        </td>
                        <td class="repaf-fin-col-spouse">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_spouse_total"
                                    class="repaf-fin-amount repaf-fin-person-total repaf-money-input" placeholder="0.00" readonly>
                            </div>
                        </td>
                        <td class="repaf-fin-col-co">
                            <div class="repaf-field">
                                <div class="repaf-fin-prefix">&#8369;</div>
                                <input type="text" inputmode="decimal" name="fin_co_total"
                                    class="repaf-fin-amount repaf-fin-person-total repaf-money-input" placeholder="0.00" readonly>
                            </div>
                        </td>
                        <td class="repaf-fin-col-amount"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- =====================================================
         Section B: Bank Deposit / Credit Card Accounts
         ===================================================== -->
    <div class="repaf-fin-section">
        <h4 class="repaf-fin-heading">Bank Deposit Accounts/ Credit Card Accounts ( Please provide the following information on your existing Bank/Credit Card Accounts)</h4>

        <div class="repaf-fin-banks-header repaf-fin-row-header">
            <span class="repaf-fin-col-bank-name">Bank</span>
            <span class="repaf-fin-col-bank-type">Account Type</span>
            <span class="repaf-fin-col-bank-date">Date Opened</span>
            <span class="repaf-fin-col-bank-balance">Average Monthly Balance</span>
            <span class="repaf-fin-col-remove"></span>
        </div>

        <div id="repaf-fin-banks">
            <?php
            $rows_to_render = $_fin_banks;
            foreach ($rows_to_render as $n => $r):
                $n = (int) $n;
            ?>
            <div class="repaf-fin-row repaf-fin-bank-row" data-row-index="<?= $n ?>">
                <div class="repaf-field repaf-fin-col-bank-name">
                    <input type="text" name="fin_bank[<?= $n ?>][name]" placeholder="Bank name"
                        value="<?= esc_attr($r['name'] ?? '') ?>">
                </div>
                <div class="repaf-field repaf-fin-col-bank-type">
                    <?php $saved_account_type = (string) ($r['type'] ?? ''); ?>
                    <select name="fin_bank[<?= $n ?>][type]">
                        <option value="">Select account type</option>
                        <?php foreach ($_fin_bank_account_types as $account_type): ?>
                            <option value="<?= esc_attr($account_type) ?>"<?= selected($saved_account_type, $account_type, false) ?>><?= esc_html($account_type) ?></option>
                        <?php endforeach; ?>
                        <?php if ($saved_account_type !== '' && !in_array($saved_account_type, $_fin_bank_account_types, true)): ?>
                            <option value="<?= esc_attr($saved_account_type) ?>" selected><?= esc_html($saved_account_type) ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="repaf-field repaf-fin-col-bank-date">
                    <input type="date" name="fin_bank[<?= $n ?>][date_opened]"
                        value="<?= esc_attr($r['date_opened'] ?? '') ?>">
                </div>
                <div class="repaf-field repaf-fin-col-bank-balance">
                    <input type="text" inputmode="decimal" name="fin_bank[<?= $n ?>][balance]" placeholder="0.00" class="repaf-money-input"
                        value="<?= esc_attr($r['balance'] ?? '') ?>">
                </div>
                <div class="repaf-fin-col-remove">
                    <button type="button" class="repaf-fin-remove" aria-label="Remove row">&times;</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="button" id="repaf-fin-add-bank" class="repaf-fin-add-btn">
            + Add Bank / Credit Card Account
        </button>
        <p class="repaf-fin-limit-note">Maximum 5 entries</p>
    </div>

    <!-- =====================================================
         Section C: Loans
         ===================================================== -->
    <div class="repaf-fin-section">
        <h4 class="repaf-fin-heading">Loans (Please provide the following information on your existing loans)</h4>

        <div class="repaf-fin-loans-header repaf-fin-row-header">
            <span class="repaf-fin-col-loan-type">Type of Credit</span>
            <span class="repaf-fin-col-loan-bank">Bank / Financial Institution</span>
            <span class="repaf-fin-col-loan-contact">Contact Number</span>
            <span class="repaf-fin-col-loan-original">Original Amount</span>
            <span class="repaf-fin-col-loan-outstanding">Outstanding Balance</span>
            <span class="repaf-fin-col-loan-amort">Monthly Amortization</span>
            <span class="repaf-fin-col-loan-collateral">Collateral</span>
            <span class="repaf-fin-col-remove"></span>
        </div>

        <div id="repaf-fin-loans">
            <?php
            $rows_to_render = !empty($_fin_loans) ? $_fin_loans : [1 => []];
            foreach ($rows_to_render as $n => $r):
                $n = (int) $n;
            ?>
            <div class="repaf-fin-row repaf-fin-loan-row" data-row-index="<?= $n ?>">
                <div class="repaf-field repaf-fin-col-loan-type">
                    <input type="text" name="fin_loan[<?= $n ?>][type]" placeholder="Type of credit"
                        value="<?= esc_attr($r['type'] ?? '') ?>">
                </div>
                <div class="repaf-field repaf-fin-col-loan-bank">
                    <input type="text" name="fin_loan[<?= $n ?>][bank]" placeholder="Bank / institution"
                        value="<?= esc_attr($r['bank'] ?? '') ?>">
                </div>
                <div class="repaf-field repaf-fin-col-loan-contact">
                    <input type="tel" name="fin_loan[<?= $n ?>][contact]" placeholder="Contact number"
                        pattern="^(09|\+639|\+63[2-9]|0[2-9])[0-9]{7,10}$"
                        value="<?= esc_attr($r['contact'] ?? '') ?>">
                </div>
                <div class="repaf-field repaf-fin-col-loan-original">
                    <input type="text" inputmode="decimal" name="fin_loan[<?= $n ?>][original]" placeholder="0.00" class="repaf-money-input"
                        value="<?= esc_attr($r['original'] ?? '') ?>">
                </div>
                <div class="repaf-field repaf-fin-col-loan-outstanding">
                    <input type="text" inputmode="decimal" name="fin_loan[<?= $n ?>][outstanding]" placeholder="0.00" class="repaf-money-input"
                        value="<?= esc_attr($r['outstanding'] ?? '') ?>">
                </div>
                <div class="repaf-field repaf-fin-col-loan-amort">
                    <input type="text" inputmode="decimal" name="fin_loan[<?= $n ?>][amortization]" placeholder="0.00" class="repaf-money-input"
                        value="<?= esc_attr($r['amortization'] ?? '') ?>">
                </div>
                <div class="repaf-field repaf-fin-col-loan-collateral">
                    <input type="text" name="fin_loan[<?= $n ?>][collateral]" placeholder="Collateral"
                        value="<?= esc_attr($r['collateral'] ?? '') ?>">
                </div>
                <div class="repaf-fin-col-remove">
                    <button type="button" class="repaf-fin-remove" aria-label="Remove row">&times;</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="button" id="repaf-fin-add-loan" class="repaf-fin-add-btn">
            + Add Loan
        </button>
        <p class="repaf-fin-limit-note">Maximum 5 entries</p>
    </div>

    <!-- =====================================================
         Character References
         ===================================================== -->
    <div class="repaf-fin-section">
        <h4 class="repaf-fin-heading">Character References (Please provide 3 persons as references)</h4>

        <div id="repaf-fin-refs">
            <?php
            $saved_ref_rows = array_values($_fin_refs);
            for ($n = 1; $n <= 3; $n++):
                $r = $saved_ref_rows[$n - 1] ?? [];
                $name_parts = \Repaf\repaf_reference_name_parts($r);
                $legacy_name = $name_parts === null ? ($r['name'] ?? '') : '';
            ?>
            <div class="repaf-fin-row repaf-fin-ref-row" data-row-index="<?= $n ?>">
                <div class="repaf-field repaf-fin-col-ref-name">
                    <strong>Reference <?= $n ?></strong>
                    <input type="hidden" name="fin_ref[<?= $n ?>][name]" value="<?= esc_attr($legacy_name) ?>">
                    <?php if ($legacy_name !== ''): ?>
                        <p class="repaf-fin-limit-note">Previously saved name: <?= esc_html($legacy_name) ?>. Please enter its name parts below.</p>
                    <?php endif; ?>
                    <div class="repaf-fin-ref-name-fields">
                        <?php foreach (['last_name' => 'Last Name', 'first_name' => 'First Name', 'middle_name' => 'Middle Name', 'suffix' => 'Suffix'] as $key => $label):
                            $required = in_array($key, ['last_name', 'first_name'], true);
                            $input_id = 'repaf-ref-' . $n . '-' . $key;
                        ?>
                            <div class="repaf-field">
                                <label for="<?= esc_attr($input_id) ?>"><?= esc_html($label) ?><?php if ($required): ?> <span class="repaf-required">*</span><?php endif; ?></label>
                                <input type="text" id="<?= esc_attr($input_id) ?>" name="fin_ref[<?= $n ?>][<?= esc_attr($key) ?>]" placeholder="<?= esc_attr($label) ?>"
                                    <?php if ($required): ?>required aria-required="true"<?php endif; ?>
                                    data-validation-label="Character Reference <?= $n ?> <?= esc_attr($label) ?>" value="<?= esc_attr($name_parts[$key] ?? '') ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="repaf-field repaf-fin-col-ref-address">
                    <label for="repaf-ref-<?= $n ?>-address">Present Address <span class="repaf-required">*</span></label>
                    <input type="text" id="repaf-ref-<?= $n ?>-address" name="fin_ref[<?= $n ?>][address]" placeholder="Present address" required aria-required="true" data-validation-label="Character Reference <?= $n ?> Present Address"
                        value="<?= esc_attr($r['address'] ?? '') ?>">
                </div>
                <div class="repaf-field repaf-fin-col-ref-email">
                    <label for="repaf-ref-<?= $n ?>-email">Email Address</label>
                    <input type="email" id="repaf-ref-<?= $n ?>-email" name="fin_ref[<?= $n ?>][email]" placeholder="email@example.com" data-validation-label="Character Reference <?= $n ?> Email Address"
                        value="<?= esc_attr($r['email'] ?? '') ?>">
                </div>
                <div class="repaf-field repaf-fin-col-ref-contact">
                    <label for="repaf-ref-<?= $n ?>-contact">Contact Number <span class="repaf-required">*</span></label>
                    <input type="tel" id="repaf-ref-<?= $n ?>-contact" name="fin_ref[<?= $n ?>][contact]" placeholder="Contact number" required aria-required="true" data-validation-label="Character Reference <?= $n ?> Contact Number"
                        pattern="^(09|\+639|\+63[2-9]|0[2-9])[0-9]{7,10}$"
                        value="<?= esc_attr($r['contact'] ?? '') ?>">
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="repaf-actions">
        <button type="button" class="repaf-back">Back</button>
        <button type="button" class="repaf-next">Next</button>
    </div>
</section>
