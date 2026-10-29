<?php
$repaf_text    = get_option('repaf_text_settings', array());
$repaf_company = \Repaf\repaf_get_site_company_name();
$repaf_sub     = !empty($repaf_text['subtitle']) ? $repaf_text['subtitle'] : 'Real Estate Purchase Agreement Form';

$repaf_privacy_policy_url = function_exists('get_privacy_policy_url')
    ? get_privacy_policy_url()
    : '';

if ($repaf_privacy_policy_url === '') {
    $repaf_privacy_policy_url = home_url('/privacy-policy/');
}

$repaf_privacy_policy_url = apply_filters(
    'repaf_privacy_policy_url',
    $repaf_privacy_policy_url
);
$repaf_data_privacy_policy_url = apply_filters(
    'repaf_data_privacy_policy_url',
    $repaf_privacy_policy_url
);
$repaf_submission_status = isset($_GET['repaf_status'])
    ? sanitize_key(wp_unslash($_GET['repaf_status']))
    : '';
$repaf_show_privacy_modal = empty($repaf_edit_mode) && $repaf_submission_status !== 'success';
?>

<?php if ($repaf_show_privacy_modal): ?>
<div
    class="repaf-privacy-consent-modal is-visible"
    id="repaf-privacy-consent-modal"
    role="dialog"
    aria-modal="true"
    aria-hidden="false"
    aria-labelledby="repaf-privacy-consent-title"
    aria-describedby="repaf-privacy-consent-description">
    <div class="repaf-privacy-consent-modal__backdrop" aria-hidden="true"></div>
    <div class="repaf-privacy-consent-modal__panel" role="document">
        <p class="repaf-privacy-consent-modal__eyebrow">Before you begin</p>
        <h2 id="repaf-privacy-consent-title">Privacy and Data Privacy Consent</h2>
        <p id="repaf-privacy-consent-description">
            Please review and accept both notices before accessing the REPAF form.
        </p>

        <div class="repaf-privacy-consent-modal__notice">
            <section aria-labelledby="repaf-privacy-policy-heading">
                <h3 id="repaf-privacy-policy-heading">Privacy Policy</h3>
                <p>
                    The Privacy Policy explains how <?php echo esc_html($repaf_company); ?> handles personal information,
                    including who may receive it, how long it may be retained, your privacy rights,
                    and how to contact the company about those rights.
                </p>
                <a href="<?php echo esc_url($repaf_privacy_policy_url); ?>" target="_blank" rel="noopener noreferrer">
                    Read the full Privacy Policy
                </a>
            </section>

            <section aria-labelledby="repaf-data-privacy-heading">
                <h3 id="repaf-data-privacy-heading">Data Privacy Policy</h3>
                <p>
                    This form collects the identification, contact, address, family, employment,
                    financial, reference, and transaction information that you provide. It will be
                    processed to evaluate and administer your REPAF application, verify submitted
                    details, prepare transaction documents, communicate with you, and maintain
                    related business records.
                </p>
                <p>
                    Information may be accessed by authorized personnel and transaction or service
                    partners only for the purposes and under the safeguards described in the full policy.
                </p>
                <a href="<?php echo esc_url($repaf_data_privacy_policy_url); ?>" target="_blank" rel="noopener noreferrer">
                    Read the full Data Privacy Policy
                </a>
            </section>
        </div>

        <div class="repaf-privacy-consent-modal__agreements">
            <label for="repaf_privacy_policy_consent">
                <input
                    type="checkbox"
                    id="repaf_privacy_policy_consent"
                    name="privacy_policy_consent"
                    value="accepted"
                    form="repaf-wizard-form"
                    required>
                <span>I have read and agree to the Privacy Policy.</span>
            </label>
            <label for="repaf_data_privacy_consent">
                <input
                    type="checkbox"
                    id="repaf_data_privacy_consent"
                    name="data_privacy_consent"
                    value="accepted"
                    form="repaf-wizard-form"
                    required>
                <span>I consent to the collection and processing described in the Data Privacy Policy.</span>
            </label>
        </div>

        <p class="repaf-privacy-consent-modal__help" aria-live="polite">
            Both boxes must be selected to continue.
        </p>
        <button type="button" class="repaf-privacy-consent-modal__continue" disabled>
            Agree and Continue
        </button>
    </div>
</div>
<?php endif; ?>

<form
    class="repaf-wizard<?php echo !empty($repaf_edit_mode) ? ' repaf-wizard--edit' : ''; ?>"
    id="repaf-wizard-form"
    method="post"
    enctype="multipart/form-data"
    action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
    <?php echo !empty($repaf_edit_mode) ? 'data-repaf-mode="edit"' : ''; ?>>

    <div class="repaf-wizard__header">
        <h2><?php echo esc_html($repaf_company); ?></h2>
        <p><?php echo esc_html($repaf_sub); ?></p>
        <?php if (!empty($repaf_edit_mode)): ?>
        <p class="repaf-edit-mode-badge">&#9998; Editing Submission #<?php echo esc_html($repaf_edit_post_id); ?></p>
        <?php endif; ?>
    </div>

    <?php
    $repaf_progress_steps = array(
        1  => 'Buyer Information',
        2  => 'Spouse Information',
        3  => 'Attorney-in-Fact Information',
        4  => 'Co-Owner Information',
        5  => 'Co-Owner Spouse Information',
        6  => 'Work and Occupation',
        7  => 'Financial Information',
        10 => 'Review and Confirm',
    );
    if (!empty($repaf_edit_mode)) {
        $repaf_progress_steps = array_slice($repaf_progress_steps, 0, 7, true)
            + array(
                8 => 'Property Information',
                9 => 'Salesperson Information',
            )
            + array_slice($repaf_progress_steps, 7, null, true);
    }
    $repaf_indicator_tag = !empty($repaf_edit_mode) ? 'button' : 'span';
    ?>
    <div class="repaf-wizard__progress" aria-label="Form progress">
        <?php foreach ($repaf_progress_steps as $repaf_step_number => $repaf_step_title): ?>
            <<?php echo $repaf_indicator_tag; ?>
                <?php echo $repaf_indicator_tag === 'button' ? 'type="button"' : ''; ?>
                class="repaf-step-indicator<?php echo $repaf_step_number === 1 ? ' is-active' : ''; ?>"
                data-step="<?php echo esc_attr($repaf_step_number); ?>"
                data-step-title="<?php echo esc_attr($repaf_step_title); ?>"
                aria-label="<?php echo esc_attr('Step ' . $repaf_step_number . ': ' . $repaf_step_title); ?>"
                title="<?php echo esc_attr($repaf_step_title); ?>"><?php echo esc_html($repaf_step_number); ?></<?php echo $repaf_indicator_tag; ?>>
        <?php endforeach; ?>
    </div>
    <div class="repaf-error-message" role="alert" aria-live="polite"></div>

    <?php include REPAF_PLUGIN_PATH . 'templates/step-1-buyer.php'; ?>
    <?php include REPAF_PLUGIN_PATH . 'templates/step-3-spouse.php'; ?>
    <?php include REPAF_PLUGIN_PATH . 'templates/step-5-representative.php'; ?>
    <?php include REPAF_PLUGIN_PATH . 'templates/step-3-co-owner-choice.php'; ?>
    <?php include REPAF_PLUGIN_PATH . 'templates/step-7-co-owner-additional.php'; ?>
    <?php include REPAF_PLUGIN_PATH . 'templates/step-4-employment.php'; ?>
    <?php include REPAF_PLUGIN_PATH . 'templates/step-8-financial.php'; ?>
    <?php if (!empty($repaf_edit_mode)): ?>
    <?php include REPAF_PLUGIN_PATH . 'templates/step-9-property.php'; ?>
    <?php include REPAF_PLUGIN_PATH . 'templates/step-10-salesperson.php'; ?>
    <?php endif; ?>
    <?php include REPAF_PLUGIN_PATH . 'templates/step-7-review.php'; ?>

    <input type="hidden" name="action" value="repaf_submit_form">
    <input type="hidden" name="repaf_form_submitted" value="1">
    <?php wp_nonce_field('repaf_submit', 'repaf_nonce'); ?>
    <?php if (!empty($repaf_edit_mode)): ?>
    <input type="hidden" name="repaf_edit_id" value="<?php echo esc_attr($repaf_edit_post_id); ?>">
    <input type="hidden" name="repaf_edit_nonce" value="<?php echo esc_attr(wp_create_nonce('repaf_front_edit_' . $repaf_edit_post_id)); ?>">
    <?php endif; ?>

    <?php
    /*
     * Honeypot anti-spam field.
     * Hidden from real users via CSS (not display:none — some
     * bots skip fields that are display:none). Must be empty
     * on submission; bots that auto-fill every field will
     * populate it and get silently rejected in form-handler.php.
     */
    ?>
    <div class="repaf-hp" aria-hidden="true" tabindex="-1">
        <label for="repaf_website">Website</label>
        <input
            type="text"
            id="repaf_website"
            name="repaf_website"
            value=""
            autocomplete="off"
            tabindex="-1">
    </div>

    <div id="repaf-loading-overlay" role="status" aria-label="Submitting form" aria-hidden="true">
        <div class="repaf-loading-box">
            <div class="repaf-spinner" aria-hidden="true"></div>
            <p>Uploading and processing your form&hellip;<br>Please do not close this tab.</p>
        </div>
    </div>
</form>
