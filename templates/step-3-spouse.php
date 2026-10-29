<section class="repaf-step" data-step="2">
    <h3>Spouse Information</h3>

    <?php
    $name_attrs = [
        'pattern' => "^[A-Za-zÀ-ÿ\\s'\\-.]+$",
        'title'   => 'Letters, spaces, hyphens, and apostrophes only',
    ];
    ?>

    <h4 class="repaf-section-title">Personal Information</h4>

    <div class="repaf-form-grid">
        <?php
        echo repaf_render_field('Last Name', 'spouse_last_name', 'text', true, $name_attrs);
        echo repaf_render_field('First Name', 'spouse_first_name', 'text', true, $name_attrs);
        echo repaf_render_field('Middle Name', 'spouse_middle_name', 'text', false, $name_attrs);
        echo repaf_render_field('Suffix', 'spouse_suffix', 'text', false, [
            'pattern' => "^[A-Za-zÀ-ÿ\\s'.]+$",
            'title'   => 'Letters and periods only (e.g. Jr., Sr., III)',
        ]);
        echo repaf_render_citizenship_field('spouse_citizenship');
        echo repaf_render_field('Date of Birth', 'spouse_dob', 'date', true);
        echo repaf_render_field('Age', 'spouse_age', 'number');
        echo repaf_render_field('TIN', 'spouse_tin', 'text', false, [
            'pattern'     => '^[0-9\\-]+$',
            'inputmode'   => 'numeric',
            'placeholder' => 'XXX-XXX-XXX',
            'title'       => 'Digits and hyphens only (e.g. 123-456-789)',
        ]);
        echo repaf_render_field('No. of Dependents', 'spouse_dependents', 'number');
        ?>
    </div>

    <h4 class="repaf-section-title">Contact Details</h4>

    <div class="repaf-form-grid">
        <?php
        echo repaf_render_field('Home Tel. Number', 'spouse_home_tel', 'tel', false, [
            'pattern'     => '^[0-9 ()+\\-]+$',
            'inputmode'   => 'numeric',
            'placeholder' => 'e.g. (02) 1234-5678',
            'title'       => 'Digits, spaces, hyphens, and parentheses only',
        ]);
        echo repaf_render_field('Mobile Number', 'spouse_mobile', 'tel', true, [
            'pattern'     => '^(09|\\+639)[0-9]{9}$',
            'inputmode'   => 'numeric',
            'placeholder' => '09XXXXXXXXX',
        ]);
        echo repaf_render_field('Email Address', 'spouse_email', 'email', true);
        ?>
    </div>

    <div class="repaf-actions">
        <button type="button" class="repaf-back">Back</button>
        <button type="button" class="repaf-next">Next</button>
    </div>
</section>
