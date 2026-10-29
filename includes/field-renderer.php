<?php

namespace Repaf {

/**
 * =========================================================
 * Field renderers
 * =========================================================
 *
 * Generates HTML for individual form fields and radio
 * groups. Called from templates which have no namespace,
 * so global aliases are provided at the bottom of this
 * file.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render a standard text/email/date/number input field.
 *
 * In salesperson edit mode, pre-populates the value from the global
 * $_repaf_edit_data array set by form-render.php.
 */
function repaf_render_field($label, $name, $type = 'text', $required = false, $attrs = [])
{
    $required_attr = $required ? ' required aria-required="true"' : '';
    $required_mark = $required ? ' <span class="repaf-required">*</span>' : '';
    $wrapper_class = 'repaf-field';

    if (!empty($attrs['wrapper_class'])) {
        $wrapper_class .= ' ' . sanitize_html_class($attrs['wrapper_class']);
        unset($attrs['wrapper_class']);
    }

    $extra_attrs = '';
    foreach ($attrs as $attr_name => $attr_value) {
        $extra_attrs .= ' ' . esc_attr($attr_name) . '="' . esc_attr($attr_value) . '"';
    }

    $value_attr = '';
    if (
        isset($GLOBALS['_repaf_edit_data']) &&
        array_key_exists($name, $GLOBALS['_repaf_edit_data']) &&
        $GLOBALS['_repaf_edit_data'][$name] !== ''
    ) {
        $value_attr = ' value="' . esc_attr($GLOBALS['_repaf_edit_data'][$name]) . '"';
    }

    return '
        <div class="' . esc_attr($wrapper_class) . '">
            <label for="' . esc_attr($name) . '">' . esc_html($label) . $required_mark . '</label>
            <input
                id="' . esc_attr($name) . '"
                name="' . esc_attr($name) . '"
                type="' . esc_attr($type) . '"' . $required_attr . $extra_attrs . $value_attr . '
            >
        </div>
    ';
}

/**
 * Render a searchable, browser-native datalist restricted to known options.
 */
function repaf_render_datalist_field($label, $name, $options, $required = false, $attrs = [])
{
    $required_attr = $required ? ' required aria-required="true"' : '';
    $required_mark = $required ? ' <span class="repaf-required">*</span>' : '';
    $saved_value   = $GLOBALS['_repaf_edit_data'][$name] ?? '';
    $list_id       = $name . '_options';
    $wrapper_class = 'repaf-field';

    if (!empty($attrs['wrapper_class'])) {
        $wrapper_class .= ' ' . sanitize_html_class($attrs['wrapper_class']);
        unset($attrs['wrapper_class']);
    }

    $extra_attrs = '';
    foreach ($attrs as $attr_name => $attr_value) {
        $extra_attrs .= ' ' . esc_attr($attr_name) . '="' . esc_attr($attr_value) . '"';
    }

    $html = '
        <div class="' . esc_attr($wrapper_class) . '">
            <label for="' . esc_attr($name) . '">' . esc_html($label) . $required_mark . '</label>
            <input
                id="' . esc_attr($name) . '"
                name="' . esc_attr($name) . '"
                type="text"
                list="' . esc_attr($list_id) . '"
                value="' . esc_attr((string) $saved_value) . '"
                placeholder="Select or search industry"
                autocomplete="off"
                data-repaf-restricted-list="true"' . $required_attr . $extra_attrs . '
            >
            <datalist id="' . esc_attr($list_id) . '">
    ';

    foreach ($options as $option) {
        $html .= '<option value="' . esc_attr($option) . '"></option>';
    }

    $html .= '
            </datalist>
        </div>
    ';

    return $html;
}

/**
 * Render a standard select field and restore its saved value in edit mode.
 */
function repaf_render_select_field($label, $name, $options, $required = false, $placeholder = 'Select', $attrs = [])
{
    $required_attr = $required ? ' required aria-required="true"' : '';
    $required_mark = $required ? ' <span class="repaf-required">*</span>' : '';
    $saved_value   = $GLOBALS['_repaf_edit_data'][$name] ?? '';
    $wrapper_class = 'repaf-field';

    if (!empty($attrs['wrapper_class'])) {
        $wrapper_class .= ' ' . sanitize_html_class($attrs['wrapper_class']);
        unset($attrs['wrapper_class']);
    }

    $extra_attrs   = '';
    foreach ($attrs as $attr_name => $attr_value) {
        $extra_attrs .= ' ' . esc_attr($attr_name) . '="' . esc_attr($attr_value) . '"';
    }

    $html = '
        <div class="' . esc_attr($wrapper_class) . '">
            <label for="' . esc_attr($name) . '">' . esc_html($label) . $required_mark . '</label>
            <select id="' . esc_attr($name) . '" name="' . esc_attr($name) . '"' . $required_attr . $extra_attrs . '>
                <option value="">' . esc_html($placeholder) . '</option>
    ';

    foreach ($options as $option) {
        $selected = ((string) $saved_value === (string) $option) ? ' selected' : '';
        $html .= '<option value="' . esc_attr($option) . '"' . $selected . '>' . esc_html($option) . '</option>';
    }

    $option_values = array_map('strval', $options);
    if ($saved_value !== '' && !in_array((string) $saved_value, $option_values, true)) {
        $html .= '<option value="' . esc_attr($saved_value) . '" selected>' . esc_html($saved_value) . '</option>';
    }

    $html .= '
            </select>
        </div>
    ';

    return $html;
}

/**
 * Render a citizenship dropdown with a conditionally required free-text option.
 */
function repaf_render_citizenship_field($name, $required = true)
{
    $options       = repaf_citizenship_options();
    $saved         = (string) ($GLOBALS['_repaf_edit_data'][$name] ?? '');
    $other_name    = $name . '_other';
    $is_custom     = $saved !== '' && !in_array($saved, $options, true);
    $original      = $GLOBALS['_repaf_edit_data'][$name] ?? null;
    $had_original  = array_key_exists($name, $GLOBALS['_repaf_edit_data'] ?? array());

    if ($is_custom) {
        $GLOBALS['_repaf_edit_data'][$name] = 'Others';
        $GLOBALS['_repaf_edit_data'][$other_name] = $saved;
    }

    $html = repaf_render_select_field(
        'Citizenship',
        $name,
        $options,
        $required,
        'Select citizenship',
        array('data-citizenship-select' => 'true')
    );
    $html .= repaf_render_field(
        'Please specify citizenship',
        $other_name,
        'text',
        false,
        array(
            'wrapper_class'             => 'repaf-field--citizenship-other',
            'data-citizenship-other-for' => $name,
        )
    );

    if ($is_custom) {
        if ($had_original) {
            $GLOBALS['_repaf_edit_data'][$name] = $original;
        } else {
            unset($GLOBALS['_repaf_edit_data'][$name]);
        }
        unset($GLOBALS['_repaf_edit_data'][$other_name]);
    }

    return $html;
}

/**
 * Render a radio button group.
 *
 * In salesperson edit mode, pre-checks the saved value from
 * the global $_repaf_edit_data array.
 */
function repaf_render_radio_group($label, $name, $options, $required = false)
{
    $required_mark = $required ? ' <span class="repaf-required">*</span>' : '';
    $required_attr = $required ? ' required aria-required="true"' : '';
    $saved_value   = $GLOBALS['_repaf_edit_data'][$name] ?? '';

    $html = '
        <div class="repaf-field">
            <p class="repaf-radio-label">' . esc_html($label) . $required_mark . '</p>
            <div class="repaf-radio-group">
    ';

    foreach ($options as $index => $option) {
        $checked = ($saved_value !== '' && (string) $saved_value === (string) $option) ? ' checked' : '';
        $html .= '
            <label>
                <input
                    type="radio"
                    name="' . esc_attr($name) . '"
                    value="' . esc_attr($option) . '"' . ($index === 0 ? $required_attr : '') . $checked . '
                >
                ' . esc_html($option) . '
            </label>
        ';
    }

    $html .= '
            </div>
        </div>
    ';

    return $html;
}

}  // end namespace Repaf

namespace {

/**
 * =========================================================
 * Global aliases for template access
 * =========================================================
 *
 * Templates are plain PHP includes with no namespace
 * declaration. These thin wrappers in global scope proxy
 * to the namespaced versions so templates work without
 * modification.
 */

if (!function_exists('repaf_render_field')) {
    function repaf_render_field($label, $name, $type = 'text', $required = false, $attrs = [])
    {
        return \Repaf\repaf_render_field($label, $name, $type, $required, $attrs);
    }
}

if (!function_exists('repaf_render_radio_group')) {
    function repaf_render_radio_group($label, $name, $options, $required = false)
    {
        return \Repaf\repaf_render_radio_group($label, $name, $options, $required);
    }
}

if (!function_exists('repaf_render_datalist_field')) {
    function repaf_render_datalist_field($label, $name, $options, $required = false, $attrs = [])
    {
        return \Repaf\repaf_render_datalist_field($label, $name, $options, $required, $attrs);
    }
}

if (!function_exists('repaf_render_select_field')) {
    function repaf_render_select_field($label, $name, $options, $required = false, $placeholder = 'Select', $attrs = [])
    {
        return \Repaf\repaf_render_select_field($label, $name, $options, $required, $placeholder, $attrs);
    }
}

if (!function_exists('repaf_render_citizenship_field')) {
    function repaf_render_citizenship_field($name, $required = true)
    {
        return \Repaf\repaf_render_citizenship_field($name, $required);
    }
}

}  // end global namespace
