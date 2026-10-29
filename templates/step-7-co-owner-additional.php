<section class="repaf-step" data-step="5">
    <h3>Co-Owner Spouse Information</h3>

    <?php
    $name_attrs = [
        'pattern' => "^[A-Za-zÀ-ÿ\\s'\\-.]+$",
        'title'   => 'Letters, spaces, hyphens, and apostrophes only',
    ];
    $tel_attrs = [
        'pattern'     => '^[0-9 ()+\\-]+$',
        'inputmode'   => 'numeric',
        'placeholder' => 'e.g. (02) 1234-5678',
        'title'       => 'Digits, spaces, hyphens, and parentheses only',
    ];
    $mobile_attrs = [
        'pattern'     => '^(09|\\+639)[0-9]{9}$',
        'inputmode'   => 'numeric',
        'placeholder' => '09XXXXXXXXX',
        'title'       => '09XXXXXXXXX or +639XXXXXXXXX format',
    ];
    $tin_attrs = [
        'pattern'     => '^[0-9\\-]+$',
        'inputmode'   => 'numeric',
        'placeholder' => 'XXX-XXX-XXX',
        'title'       => 'Digits and hyphens only (e.g. 123-456-789)',
    ];
    ?>

    <div id="repaf-co-spouse-fields">
        <h4 class="repaf-section-title">Spouse Personal Information</h4>

        <div class="repaf-form-grid">
            <?php
            echo repaf_render_field('Last Name', 'co_spouse_last_name', 'text', false, $name_attrs);
            echo repaf_render_field('First Name', 'co_spouse_first_name', 'text', false, $name_attrs);
            echo repaf_render_field('Middle Name', 'co_spouse_middle_name', 'text', false, $name_attrs);
            echo repaf_render_field('Suffix', 'co_spouse_suffix', 'text', false, [
                'pattern' => "^[A-Za-zÀ-ÿ\\s'.]+$",
                'title'   => 'Letters and periods only (e.g. Jr., Sr., III)',
            ]);
            echo repaf_render_citizenship_field('co_spouse_citizenship');
            echo repaf_render_field('Date of Birth', 'co_spouse_dob', 'date');
            echo repaf_render_field('Age', 'co_spouse_age', 'number', false, [
                'min'   => '0',
                'title' => 'Enter a positive number',
            ]);
            echo repaf_render_field('TIN', 'co_spouse_tin', 'text', false, $tin_attrs);
            echo repaf_render_field('No. of Dependents', 'co_spouse_dependents', 'number', false, [
                'min'   => '0',
                'title' => 'Enter a positive number',
            ]);
            echo repaf_render_field('Home Telephone Number', 'co_spouse_home_office_number', 'tel', false, $tel_attrs);
            echo repaf_render_field('Mobile Number', 'co_spouse_mobile', 'tel', false, $mobile_attrs);
            echo repaf_render_field('Email Address', 'co_spouse_email', 'email');
            ?>
        </div>
    </div>

    <div id="repaf-co-representative-after-spouse"></div>

    <div class="repaf-actions">
        <button type="button" class="repaf-back">Back</button>
        <button type="button" class="repaf-next">Next</button>
    </div>
</section>
