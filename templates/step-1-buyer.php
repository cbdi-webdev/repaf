<section class="repaf-step is-active" data-step="1">
    <h3>Buyer&rsquo;s Personal Information</h3>

    <div class="repaf-form-grid">
        <?php
        echo repaf_render_field('Last Name', 'buyer_last_name', 'text', true, [
            'pattern' => "^[A-Za-zÀ-ÿ\\s'\\-.]+$",
            'title'   => 'Letters, spaces, hyphens, and apostrophes only',
        ]);
        echo repaf_render_field('First Name', 'buyer_first_name', 'text', true, [
            'pattern' => "^[A-Za-zÀ-ÿ\\s'\\-.]+$",
            'title'   => 'Letters, spaces, hyphens, and apostrophes only',
        ]);
        echo repaf_render_field('Middle Name', 'buyer_middle_name', 'text', false, [
            'pattern' => "^[A-Za-zÀ-ÿ\\s'\\-.]+\$",
            'title'   => 'Letters, spaces, hyphens, and apostrophes only',
        ]);
        echo repaf_render_field('Suffix', 'buyer_suffix', 'text', false, [
            'pattern' => "^[A-Za-zÀ-ÿ\\s'.]+\$",
            'title'   => 'Letters and periods only (e.g. Jr., Sr., III)',
        ]);

        echo repaf_render_field('Date of Birth', 'buyer_dob', 'date', true);
        echo repaf_render_field('Age', 'buyer_age', 'number');

        echo repaf_render_radio_group('Gender', 'buyer_gender', array(
            'Male',
            'Female',
        ), true);

        echo repaf_render_radio_group('Civil Status', 'buyer_civil_status', array(
            'Single',
            'Married',
            'Widow/er',
            'Annulled',
            'Legally Separated',
        ), true);

        echo repaf_render_citizenship_field('buyer_citizenship');
        echo repaf_render_field('TIN', 'buyer_tin', 'text', false, [
            'pattern'     => '^[0-9\\-]+$',
            'inputmode'   => 'numeric',
            'placeholder' => 'XXX-XXX-XXX',
            'title'       => 'Digits and hyphens only (e.g. 123-456-789)',
        ]);
        echo repaf_render_field('Home Tel. Number', 'buyer_home_tel', 'tel', false, [
            'pattern'     => '^[0-9 ()+\\-]+$',
            'inputmode'   => 'numeric',
            'placeholder' => 'e.g. (02) 1234-5678',
            'title'       => 'Digits, spaces, hyphens, and parentheses only',
        ]);
        echo repaf_render_field('Mobile Number', 'buyer_mobile', 'tel', true, [
            'pattern'     => '^(09|\+639)[0-9]{9}$',
            'inputmode'   => 'numeric',
            'placeholder' => '09XXXXXXXXX',
        ]);
        echo repaf_render_field('Email Address', 'buyer_email', 'email', true);
        echo repaf_render_field('Pag-IBIG MID No.', 'buyer_pagibig_mid', 'text', false, [
            'pattern'     => '^[0-9]+$',
            'inputmode'   => 'numeric',
            'placeholder' => '12-digit MID number',
            'title'       => 'Digits only',
        ]);
        ?>
    </div>
    <?php include REPAF_PLUGIN_PATH . 'templates/step-2-address.php'; ?>

    <div class="repaf-actions">
        <button type="button" class="repaf-next">Next</button>
    </div>
</section>
