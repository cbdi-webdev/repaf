<section class="repaf-step" data-step="3">
    <h3>Attorney-in-Fact /Authorized Representative Information</h3>

    <?php
    $name_attrs = [
        'pattern' => "^[A-Za-zÀ-ÿ\\s'\\-.]+\$",
        'title'   => 'Letters, spaces, hyphens, and apostrophes only',
    ];
    $text_attrs = [
        'pattern' => "^[A-Za-zÀ-ÿ0-9 .,#/'&\\-]+\$",
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
    ?>

    <div class="repaf-co-owner-toggle">
        <h4 class="repaf-section-title">Authorized Representative / Attorney-In-Fact</h4>

        <p class="repaf-co-owner-toggle__question">
            Does this buyer have an Authorized Representative / Attorney-In-Fact?
        </p>

        <?php
        $_rep_saved = $GLOBALS['_repaf_edit_data']['has_representative'] ?? 'no';
        $_rep_saved = in_array($_rep_saved, ['yes', 'no'], true) ? $_rep_saved : 'no';
        ?>
        <div class="repaf-radio-group">
            <label>
                <input
                    type="radio"
                    name="has_representative"
                    value="yes"
                    required
                    aria-required="true"
                    <?php echo ($_rep_saved === 'yes') ? 'checked' : ''; ?>
                >
                Yes
            </label>
            <label>
                <input
                    type="radio"
                    name="has_representative"
                    value="no"
                    <?php echo ($_rep_saved === 'no') ? 'checked' : ''; ?>
                >
                No
            </label>
        </div>
    </div>

    <div id="repaf-rep-fields">
        <h4 class="repaf-section-title">Personal Details</h4>

        <div class="repaf-copy-row" id="repaf_spouse_rep_copy_row" style="display: none;">
            <label>
                <input
                    type="checkbox"
                    id="repaf_same_as_spouse_representative"
                    name="use_spouse_as_representative"
                    value="yes"
                    <?php echo (($GLOBALS['_repaf_edit_data']['use_spouse_as_representative'] ?? '') === 'yes') ? 'checked' : ''; ?>
                >
                Use spouse information for Attorney-in-Fact
            </label>
        </div>

        <div class="repaf-form-grid">
            <?php
            echo repaf_render_field('Last Name', 'rep_last_name', 'text', false, $name_attrs);
            echo repaf_render_field('First Name', 'rep_first_name', 'text', false, $name_attrs);
            echo repaf_render_field('Middle Name', 'rep_middle_name', 'text', false, $name_attrs);
            echo repaf_render_field('Suffix', 'rep_suffix', 'text', false, [
                'pattern' => "^[A-Za-zÀ-ÿ\\s'.]+\$",
                'title'   => 'Letters and periods only (e.g. Jr., Sr., III)',
            ]);

            echo repaf_render_field('Birthdate', 'rep_dob', 'date');
            echo repaf_render_field('Age', 'rep_age', 'number', false, [
                'min'   => '0',
                'title' => 'Enter a positive number',
            ]);
            echo repaf_render_field('Home Tel. No.', 'rep_home_tel', 'tel', false, $tel_attrs);
            echo repaf_render_field('Mobile No.', 'rep_mobile', 'tel', true, $mobile_attrs);
            echo repaf_render_field('Email', 'rep_email', 'email', true);
            echo repaf_render_field('TIN', 'rep_tin', 'text', false, $tin_attrs);
            echo repaf_render_field('Relationship to Buyer', 'rep_relationship', 'text', false, $text_attrs);
            ?>
        </div>

        <h4 class="repaf-section-title">Permanent Address</h4>

        <div class="repaf-form-grid">
            <?php
            echo repaf_render_field('Address Line (Unit/Building/Phase, Block/Lot, Street, Subdivision)', 'rep_address_line', 'text', false, $addr_attrs);
            echo repaf_render_field('Country', 'rep_country', 'text', false, $addr_attrs);
            echo repaf_render_field('Province / State', 'rep_province_state', 'text', false, $addr_attrs);
            echo repaf_render_field('City / Municipality', 'rep_city_municipality', 'text', false, $addr_attrs);
            echo repaf_render_field('Barangay', 'rep_barangay', 'text', false, $addr_attrs);
            echo repaf_render_field('Zip Code', 'rep_zip', 'text', false, $zip_attrs);
            ?>
        </div>
    </div>

    <div class="repaf-actions">
        <button type="button" class="repaf-back">Back</button>
        <button type="button" class="repaf-next">Next</button>
    </div>
</section>
