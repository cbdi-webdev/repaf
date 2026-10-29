<div id="repaf-co-representative-fields">
    <h4 class="repaf-section-title">Authorized Representative / Attorney-in-Fact (Optional)</h4>

    <div class="repaf-copy-row" id="repaf_co_spouse_rep_copy_row" style="display: none;">
        <label>
            <input
                type="checkbox"
                id="repaf_same_as_co_spouse_representative"
                name="use_co_spouse_as_representative"
                value="yes"
                <?php echo (($GLOBALS['_repaf_edit_data']['use_co_spouse_as_representative'] ?? '') === 'yes') ? 'checked' : ''; ?>
            >
            Use co-owner spouse information for Attorney-in-Fact
        </label>
    </div>

    <div class="repaf-form-grid">
        <?php
        echo repaf_render_field('Last Name', 'co_rep_last_name', 'text', false, $name_attrs);
        echo repaf_render_field('First Name', 'co_rep_first_name', 'text', false, $name_attrs);
        echo repaf_render_field('Middle Name', 'co_rep_middle_name', 'text', false, $name_attrs);
        echo repaf_render_field('Suffix', 'co_rep_suffix', 'text', false, [
            'pattern' => "^[A-Za-zÀ-ÿ\\s'.]+$",
            'title'   => 'Letters and periods only (e.g. Jr., Sr., III)',
        ]);
        echo repaf_render_field('Address Line (Unit/Building/Phase, Block/Lot, Street, Subdivision)', 'co_rep_address_line', 'text', false, $addr_attrs);
        echo repaf_render_field('Country', 'co_rep_country', 'text', false, $addr_attrs);
        echo repaf_render_field('Province / State', 'co_rep_province_state', 'text', false, $addr_attrs);
        echo repaf_render_field('City / Municipality', 'co_rep_city_municipality', 'text', false, $addr_attrs);
        echo repaf_render_field('Barangay', 'co_rep_barangay', 'text', false, $addr_attrs);
        echo repaf_render_field('Zip Code', 'co_rep_zip', 'text', false, $zip_attrs);
        echo repaf_render_field('Birthdate', 'co_rep_dob', 'date');
        echo repaf_render_field('Age', 'co_rep_age', 'number', false, [
            'min'   => '0',
            'title' => 'Enter a positive number',
        ]);
        echo repaf_render_field('Home Telephone Number', 'co_rep_home_tel', 'tel', false, $tel_attrs);
        echo repaf_render_field('Mobile Number', 'co_rep_mobile', 'tel', false, $mobile_attrs);
        echo repaf_render_field('Email', 'co_rep_email', 'email');
        echo repaf_render_field('TIN', 'co_rep_tin', 'text', false, $tin_attrs);
        echo repaf_render_field('Relationship to Buyer', 'co_rep_relationship', 'text', false, $text_attrs);
        ?>
    </div>
</div>
