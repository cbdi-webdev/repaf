<?php
    $addr_attrs = [
        'pattern' => "^[A-Za-zÀ-ÿ0-9 .,#/'&\\-]+\$",
        'title'   => 'Letters, digits, spaces, and common punctuation only',
    ];
    $zip_attrs = [
        'pattern'     => '^[0-9]+$',
        'inputmode'   => 'numeric',
        'placeholder' => '0000',
        'title'       => 'Digits only',
    ];
?>

    <h4 class="repaf-section-title">Permanent Address</h4>

    <div class="repaf-form-grid">
        <?php
        echo repaf_render_field('Address Line (Unit/Building/Phase, Block/Lot, Street, Subdivision)', 'buyer_permanent_address_line', 'text', true, $addr_attrs);
        echo repaf_render_field('Country', 'buyer_permanent_country', 'text', true, $addr_attrs);
        echo repaf_render_field('Province / State', 'buyer_permanent_province_state', 'text', true, $addr_attrs);
        echo repaf_render_field('City / Municipality', 'buyer_permanent_city_municipality', 'text', true, $addr_attrs);
        echo repaf_render_field('Barangay', 'buyer_permanent_barangay', 'text', false, $addr_attrs);
        echo repaf_render_field('Zip Code', 'buyer_permanent_zip', 'text', false, $zip_attrs);
        ?>
    </div>

    <h4 class="repaf-section-title">Present Address</h4>

    <div class="repaf-copy-row">
        <label>
            <input type="checkbox" id="repaf_same_address">
            Same as Permanent Address
        </label>
    </div>

    <div class="repaf-form-grid">
        <?php
        echo repaf_render_field('Address Line (Unit/Building/Phase, Block/Lot, Street, Subdivision)', 'buyer_present_address_line', 'text', true, $addr_attrs);
        echo repaf_render_field('Country', 'buyer_present_country', 'text', false, $addr_attrs);
        echo repaf_render_field('Province / State', 'buyer_present_province_state', 'text', false, $addr_attrs);
        echo repaf_render_field('City / Municipality', 'buyer_present_city_municipality', 'text', false, $addr_attrs);
        echo repaf_render_field('Barangay', 'buyer_present_barangay', 'text', false, $addr_attrs);
        echo repaf_render_field('Zip Code', 'buyer_present_zip', 'text', false, $zip_attrs);
        ?>
    </div>

    <h4 class="repaf-section-title">Home Ownership</h4>

    <div class="repaf-form-grid">
        <?php
        echo repaf_render_radio_group('Home Ownership', 'buyer_home_ownership', array(
            'Owned',
            'Company',
            'Living with Relatives',
            'Mortgage',
            'Rented',
        ), true);

        echo repaf_render_field(
            'Monthly Rent Amount',
            'buyer_rent_amount',
            'number',
            false
        );
        ?>
    </div>
