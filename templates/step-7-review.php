<section class="repaf-step" data-step="10">
    <h3>Review &amp; Confirm</h3>

    <p class="repaf-review-intro">
        Please review all information before submitting. Use the edit buttons to go back to a section if changes are needed.
    </p>

    <div class="repaf-review-summary" id="repaf-review-summary">
        <!-- JavaScript will populate the review cards here. -->
    </div>

    <div class="repaf-terms-section">
        <h4 class="repaf-terms-heading">Terms &amp; Conditions</h4>
        <p class="repaf-terms-instruction">Please read the full Terms and Conditions below before submitting.</p>

        <div class="repaf-terms-viewer" id="repaf-terms-viewer" tabindex="0" role="region" aria-label="Terms and Conditions">
            <img
                src="<?php echo esc_url(\Repaf\repaf_get_pdf_template_url('terms-and-conditions.jpg')); ?>"
                alt="Terms and Conditions"
                class="repaf-terms-image"
                draggable="false"
            >
        </div>

        <label class="repaf-terms-confirm-label" id="repaf-terms-confirm-label">
            <input
                type="checkbox"
                id="repaf_terms_read"
                name="repaf_terms_read"
            >
            <span>I have read and understood the Terms and Conditions above.</span>
        </label>
    </div>

    <div class="repaf-review-confirmation">
        <label class="repaf-confirmation-check">
            <input type="checkbox" name="repaf_terms_confirmed" required>
            I confirm that all information provided is true and correct.
            <span class="repaf-required">*</span>
        </label>
    </div>

    <?php if (!empty($repaf_edit_mode)): ?>
    <div class="repaf-review-confirmation repaf-batch-confirmation">
        <?php if (get_post_meta($repaf_edit_post_id, 'reservation_confirmed', true) === 'yes'): ?>
            <p>
                This reservation is already included in daily batch
                <strong><?php echo esc_html(get_post_meta($repaf_edit_post_id, 'repaf_batch_date', true)); ?></strong>.
                Saving changes will update its existing CSV row.
            </p>
        <?php else: ?>
            <label class="repaf-confirmation-check">
                <input type="checkbox" name="repaf_confirm_reservation" value="yes">
                Confirm this as a real reservation and add it to the daily CSV.
            </label>
            <p>Leave this unchecked to save corrections without adding a batch record.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="repaf-actions">
        <button type="button" class="repaf-back">Back</button>
        <button type="submit">Submit Form</button>
    </div>
</section>
