<?php
$work_block_id = 'repaf-' . $work_prefix . '-work-fields';
$common_attrs  = [
    'wrapper_class'             => 'repaf-field--work-details',
    'data-work-detail-field'    => $work_prefix,
    'data-required-when-active' => 'true',
];
$optional_common_attrs = array_merge($common_attrs, [
    'data-required-when-active' => 'false',
]);
$employee_attrs = [
    'wrapper_class'               => 'repaf-field--employee-only',
    'data-employee-only-field'    => $work_prefix,
    'data-required-when-employed' => 'true',
];
$optional_employee_attrs = array_merge($employee_attrs, [
    'data-required-when-employed' => 'false',
]);
$self_employed_attrs = [
    'wrapper_class'                    => 'repaf-field--self-employed',
    'data-self-employed-field'         => $work_prefix,
    'data-required-when-self-employed' => 'true',
];
$optional_self_employed_attrs = array_merge($self_employed_attrs, [
    'data-required-when-self-employed' => 'false',
]);
$optional_contact_attrs = array_merge($optional_common_attrs, [
    'pattern'     => '^[0-9 ()+\\-]+$',
    'inputmode'   => 'numeric',
    'placeholder' => 'e.g. (02) 1234-5678',
    'title'       => 'Digits, spaces, hyphens, and parentheses only',
]);
$mobile_contact_attrs = array_merge($optional_common_attrs, [
    'pattern'     => '^(09|\\+639)[0-9]{9}$',
    'inputmode'   => 'numeric',
    'placeholder' => '09XXXXXXXXX',
    'title'       => '09XXXXXXXXX or +639XXXXXXXXX format',
]);
?>

<div id="<?php echo esc_attr($work_block_id); ?>" class="repaf-work-person" data-work-person="<?php echo esc_attr($work_prefix); ?>">
    <h4 class="repaf-section-title"><?php echo esc_html($work_title); ?></h4>

    <div class="repaf-form-grid">
        <?php
        echo repaf_render_radio_group(
            'Employment Status',
            $work_prefix . '_occupation',
            [
                'Locally Employed - Private',
                'Locally Employed - Government',
                'Self Employed / Professional',
                'OFW',
                'Others',
            ],
            true
        );

        echo repaf_render_field(
            'Other Employment Status',
            $work_prefix . '_occupation_other',
            'text',
            false,
            [
                'pattern' => "^[A-Za-zÀ-ÿ0-9 .,()'/\\-]+$",
                'title'   => 'Letters, digits, spaces, and common punctuation only',
            ]
        );
        ?>
    </div>

    <div class="repaf-work-person__details">
        <h5
            class="repaf-section-title repaf-work-information-title"
            id="repaf-<?php echo esc_attr($work_prefix); ?>-work-information-title"
        >Employment / Business Information</h5>

        <div class="repaf-form-grid">
            <?php
            echo repaf_render_field(
                'Company / Business Name',
                $work_prefix . '_employer_name',
                'text',
                false,
                array_merge($text_attrs, $common_attrs)
            );
            echo repaf_render_select_field(
                'Industry / Type of Work',
                $work_prefix . '_industry',
                \Repaf\repaf_industry_options(),
                false,
                'Select industry / type of work',
                $common_attrs
            );
            echo repaf_render_field(
                'No. of Years in Business',
                $work_prefix . '_years_in_business',
                'number',
                false,
                array_merge($optional_self_employed_attrs, [
                    'min'   => '0',
                    'step'  => '1',
                    'title' => 'Enter zero or a positive whole number',
                ])
            );
            echo repaf_render_field(
                'No. of Years Employed',
                $work_prefix . '_years_employed',
                'number',
                false,
                array_merge($optional_employee_attrs, [
                    'min'   => '0',
                    'step'  => '1',
                    'title' => 'Enter zero or a positive whole number',
                ])
            );
            echo repaf_render_field('Date Employed', $work_prefix . '_employed_date', 'date', false, $optional_employee_attrs);
            echo repaf_render_field(
                'Position / Department',
                $work_prefix . '_position_department',
                'text',
                false,
                array_merge($text_attrs, $employee_attrs)
            );
            echo repaf_render_select_field(
                'Employment Contract Status',
                $work_prefix . '_contract_status',
                \Repaf\repaf_contract_status_options(),
                false,
                'Select',
                $employee_attrs
            );
            echo repaf_render_select_field(
                'Position / Level',
                $work_prefix . '_position_level',
                \Repaf\repaf_position_level_options(),
                false,
                'Select',
                $employee_attrs
            );

            echo repaf_render_field('Address Line (Unit/Building/Phase, Block/Lot, Street, Subdivision)', $work_prefix . '_business_address_line', 'text', false, array_merge($addr_attrs, $optional_common_attrs));
            echo repaf_render_select_field(
                'Country',
                $work_prefix . '_business_country',
                \Repaf\repaf_country_options(),
                false,
                'Select country',
                array_merge($addr_attrs, $optional_common_attrs)
            );
            echo repaf_render_field('Province / State', $work_prefix . '_business_province_state', 'text', false, array_merge($addr_attrs, $optional_common_attrs));
            echo repaf_render_field('City / Municipality', $work_prefix . '_business_city_municipality', 'text', false, array_merge($addr_attrs, $optional_common_attrs));
            echo repaf_render_field('Barangay', $work_prefix . '_business_barangay', 'text', false, array_merge($addr_attrs, $optional_common_attrs));
            echo repaf_render_field('Zip Code', $work_prefix . '_business_zip', 'text', false, array_merge($zip_attrs, $optional_common_attrs));

            echo repaf_render_field('Office Number', $work_prefix . '_office_number', 'tel', false, $optional_contact_attrs);
            echo repaf_render_field("Office Email Address", $work_prefix . '_employment_email', 'email', false, $optional_common_attrs);
            echo repaf_render_field('Office Mobile Number', $work_prefix . '_employment_mobile', 'tel', false, $mobile_contact_attrs);

            echo repaf_render_select_field(
                'Company/Business Type',
                $work_prefix . '_business_type',
                \Repaf\repaf_business_company_type_options(),
                false,
                'Select',
                $self_employed_attrs
            );
            echo repaf_render_field(
                'Date of Business Established',
                $work_prefix . '_business_established_date',
                'date',
                false,
                $self_employed_attrs
            );
            ?>
        </div>
    </div>
</div>
