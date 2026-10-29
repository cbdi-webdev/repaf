<section class="repaf-step" data-step="4">
    <div class="repaf-co-owner-toggle">
        <h3>Co-Owner / Co-Borrower / Financier</h3>
        <p class="repaf-co-owner-toggle__question">
            Does this transaction have a Co-Owner, Co-Borrower, or Financier?
        </p>

        <?php
        $_co_saved = $GLOBALS['_repaf_edit_data']['has_co_owner'] ?? 'no';
        $_co_saved = in_array($_co_saved, ['yes', 'no'], true) ? $_co_saved : 'no';
        ?>
        <div class="repaf-radio-group">
            <label>
                <input type="radio" name="has_co_owner" value="yes" required aria-required="true"
                    <?php echo ($_co_saved === 'yes') ? 'checked' : ''; ?>>
                Yes
            </label>
            <label>
                <input type="radio" name="has_co_owner" value="no"
                    <?php echo ($_co_saved === 'no') ? 'checked' : ''; ?>>
                No
            </label>
        </div>
    </div>

    <?php include REPAF_PLUGIN_PATH . 'templates/step-6-co-owner.php'; ?>

    <div class="repaf-actions">
        <button type="button" class="repaf-back">Back</button>
        <button type="button" class="repaf-next">Next</button>
    </div>
</section>
