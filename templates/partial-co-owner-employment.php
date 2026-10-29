<div id="repaf-co-employment-fields">
    <h4 class="repaf-section-title">Employment Details</h4>

    <div class="repaf-form-grid">
        <?php
        echo repaf_render_field('Present Employer / Business Name', 'co_employer_name', 'text', false, $text_attrs);
        echo repaf_render_datalist_field(
            'Nature of Business / Industry',
            'co_industry',
            \Repaf\repaf_industry_options()
        );
        echo repaf_render_field('No. of Years in Business', 'co_years_in_business', 'number', false, [
            'min'                              => '0',
            'wrapper_class'                    => 'repaf-field--self-employed',
            'data-self-employed-field'         => 'co',
            'data-required-when-self-employed' => 'false',
        ]);
        echo repaf_render_field('No. of Years Employed', 'co_years_employed', 'number', false, [
            'min'                         => '0',
            'title'                       => 'Enter a positive number',
            'wrapper_class'               => 'repaf-field--employee-only',
            'data-employee-only-field'    => 'co',
            'data-required-when-employed' => 'false',
        ]);
        echo repaf_render_field('Address Line (Unit/Building/Phase, Block/Lot, Street, Subdivision)', 'co_business_address_line', 'text', false, $addr_attrs);
        echo repaf_render_select_field(
            'Country',
            'co_business_country',
            \Repaf\repaf_country_options(),
            false,
            'Select country',
            $addr_attrs
        );
        echo repaf_render_field('Province / State', 'co_business_province_state', 'text', false, $addr_attrs);
        echo repaf_render_field('City / Municipality', 'co_business_city_municipality', 'text', false, $addr_attrs);
        echo repaf_render_field('Barangay', 'co_business_barangay', 'text', false, $addr_attrs);
        echo repaf_render_field('Zip Code', 'co_business_zip', 'text', false, $zip_attrs);
        echo repaf_render_field('Office Number', 'co_office_number', 'tel', false, $tel_attrs);
        echo repaf_render_field(
            'Date of Business Established',
            'co_business_established_date',
            'date',
            false,
            [
                'wrapper_class'            => 'repaf-field--self-employed',
                'data-self-employed-field' => 'co',
            ]
        );
        echo repaf_render_field(
            'Position / Department',
            'co_position_department',
            'text',
            false,
            array_merge($text_attrs, [
                'wrapper_class'               => 'repaf-field--employee-only',
                'data-employee-only-field'    => 'co',
                'data-required-when-employed' => 'false',
            ])
        );
        echo repaf_render_select_field(
            'Employment Contract Status',
            'co_contract_status',
            \Repaf\repaf_contract_status_options(),
            false,
            'Select',
            [
                'wrapper_class'               => 'repaf-field--employee-only',
                'data-employee-only-field'    => 'co',
                'data-required-when-employed' => 'false',
            ]
        );
        echo repaf_render_field(
            'Office Email Address',
            'co_employment_email',
            'email',
            false,
            [
                'wrapper_class'               => 'repaf-field--employee-only',
                'data-employee-only-field'    => 'co',
                'data-required-when-employed' => 'false',
            ]
        );
        echo repaf_render_field('Office Mobile Number', 'co_employment_mobile', 'tel', false, $mobile_attrs);
        echo repaf_render_select_field(
            'Position / Level',
            'co_position_level',
            \Repaf\repaf_position_level_options(),
            false,
            'Select',
            [
                'wrapper_class'               => 'repaf-field--employee-only',
                'data-employee-only-field'    => 'co',
                'data-required-when-employed' => 'false',
            ]
        );
        echo repaf_render_select_field(
            'Business/Company Type',
            'co_business_type',
            \Repaf\repaf_business_company_type_options(),
            false,
            'Select',
            [
                'wrapper_class'            => 'repaf-field--self-employed',
                'data-self-employed-field' => 'co',
            ]
        );
        ?>
    </div>
</div>
