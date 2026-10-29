<div id="repaf-co-owner-fields" class="repaf-co-owner-fields" hidden>
    <h4 class="repaf-section-title">Co-Owner/ Co-Borrower/ Financier Information</h4>

    <?php
    $name_attrs = [
        'pattern' => "^[A-Za-zÀ-ÿ\\s'\\-.]+$",
        'title'   => 'Letters, spaces, hyphens, and apostrophes only',
    ];
    $text_attrs = [
        'pattern' => "^[A-Za-zÀ-ÿ0-9 .,#/'&\\-]+$",
        'title'   => 'Letters, digits, spaces, and common punctuation only',
    ];
    $addr_attrs = $text_attrs;
    $zip_attrs  = [
        'pattern'     => '^[0-9]+$',
        'inputmode'   => 'numeric',
        'placeholder' => '0000',
        'title'       => 'Digits only',
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
    $pagibig_attrs = [
        'pattern'     => '^[0-9]+$',
        'inputmode'   => 'numeric',
        'placeholder' => '12-digit MID number',
        'title'       => 'Digits only',
    ];
    ?>

    <h4 class="repaf-section-title">Role Selection</h4>
    <div class="repaf-form-grid">
        <?php
        echo repaf_render_radio_group('Select Role', 'co_owner_role', [
            'Co-owner',
            'Co-borrower',
            'Financier',
        ], true);
        ?>
    </div>

    <h4 class="repaf-section-title">Personal Information</h4>
    <div class="repaf-form-grid">
        <?php
        echo repaf_render_field('Last Name', 'co_last_name', 'text', true, $name_attrs);
        echo repaf_render_field('First Name', 'co_first_name', 'text', true, $name_attrs);
        echo repaf_render_field('Middle Name', 'co_middle_name', 'text', false, $name_attrs);
        echo repaf_render_field('Suffix', 'co_suffix', 'text', false, [
            'pattern' => "^[A-Za-zÀ-ÿ\\s'.]+$",
            'title'   => 'Letters and periods only (e.g. Jr., Sr., III)',
        ]);
        echo repaf_render_field('Date of Birth', 'co_dob', 'date', true);
        echo repaf_render_field('Age', 'co_age', 'number', false, ['min' => '0']);
        echo repaf_render_radio_group('Gender', 'co_gender', ['Male', 'Female'], true);
        echo repaf_render_radio_group('Civil Status', 'co_civil_status', [
            'Single',
            'Married',
            'Widow/er',
            'Annulled',
            'Legally Separated',
        ], true);
        echo repaf_render_citizenship_field('co_citizenship');
        echo repaf_render_field('TIN', 'co_tin', 'text', false, $tin_attrs);
        echo repaf_render_field('Home Telephone Number', 'co_home_tel', 'tel', false, $tel_attrs);
        echo repaf_render_field('Mobile Number', 'co_mobile', 'tel', true, $mobile_attrs);
        echo repaf_render_field('Email Address', 'co_email', 'email', true);
        echo repaf_render_field('Pag-IBIG MID Number', 'co_pagibig_mid', 'text', false, $pagibig_attrs);
        ?>
    </div>

    <h4 class="repaf-section-title">Permanent Home Address</h4>
    <div class="repaf-form-grid">
        <?php
        echo repaf_render_field('Address Line (Unit/Building/Phase, Block/Lot, Street, Subdivision)', 'co_permanent_address_line', 'text', false, $addr_attrs);
        echo repaf_render_field('Country', 'co_permanent_country', 'text', true, $addr_attrs);
        echo repaf_render_field('Province / State', 'co_permanent_province_state', 'text', true, $addr_attrs);
        echo repaf_render_field('City / Municipality', 'co_permanent_city_municipality', 'text', true, $addr_attrs);
        echo repaf_render_field('Barangay', 'co_permanent_barangay', 'text', false, $addr_attrs);
        echo repaf_render_field('Zip Code', 'co_permanent_zip', 'text', false, $zip_attrs);
        ?>
    </div>

    <h4 class="repaf-section-title">Present Home Address</h4>
    <div class="repaf-copy-row">
        <label><input type="checkbox" id="repaf_co_same_address"> Same as Permanent Address</label>
    </div>
    <div class="repaf-form-grid">
        <?php
        echo repaf_render_field('Address Line (Unit/Building/Phase, Block/Lot, Street, Subdivision)', 'co_present_address_line', 'text', false, $addr_attrs);
        echo repaf_render_field('Country', 'co_present_country', 'text', false, $addr_attrs);
        echo repaf_render_field('Province / State', 'co_present_province_state', 'text', false, $addr_attrs);
        echo repaf_render_field('City / Municipality', 'co_present_city_municipality', 'text', false, $addr_attrs);
        echo repaf_render_field('Barangay', 'co_present_barangay', 'text', false, $addr_attrs);
        echo repaf_render_field('Zip Code', 'co_present_zip', 'text', false, $zip_attrs);
        ?>
    </div>

    <h4 class="repaf-section-title">Home Ownership</h4>
    <div class="repaf-form-grid">
        <?php
        echo repaf_render_radio_group('Home Ownership', 'co_home_ownership', [
            'Owned',
            'Company',
            'Living with Relatives',
            'Mortgage',
            'Rented',
        ], true);
        echo repaf_render_field('Monthly Rental Amount', 'co_rent_amount', 'number', false, [
            'min'   => '0',
            'title' => 'Enter a positive amount',
        ]);
        ?>
    </div>

    <div id="repaf-co-representative-home">
        <?php include REPAF_PLUGIN_PATH . 'templates/partial-co-owner-representative.php'; ?>
    </div>

</div>
