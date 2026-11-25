<?php
/**
 * Bricks element for The Events Calendar Shortcode.
 *
 * Controls are generated from the shared builder option registry
 * (includes/builder-options.php). Legacy control names/values are preserved
 * (including the 'free' design key) so existing saved pages keep working.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TECS_Element extends \Bricks\Element {

    public $category = 'general';
    public $name = 'the-events-calendar-shortcode';
    public $icon = 'bricks-icon-ecs';
    public $css_selector = '.ecs-wrapper';
    public $scripts = [];
    public $nestable = false;

    public function get_label() {
        return esc_html__( 'The Events Calendar Shortcode', 'the-events-calendar-shortcode' );
    }

    public function set_control_groups() {
        $options = ecs_builder_options();
        $sections = ecs_builder_option_sections();
        $used = [];

        foreach ( $options as $option ) {
            $section = isset( $option['section'] ) ? $option['section'] : 'advanced';

            if ( isset( $used[ $section ] ) || ! isset( $sections[ $section ] ) ) {
                continue;
            }

            $used[ $section ] = true;

            $this->control_groups[ 'ecs_' . $section ] = [
                'tab' => 'content',
                'title' => $sections[ $section ],
            ];
        }
    }

    /**
     * Convert a registry condition into Bricks' `required` format.
     */
    protected function convert_condition( $option, $options ) {
        $required = [];

        foreach ( (array) ( isset( $option['condition'] ) ? $option['condition'] : [] ) as $rule ) {
            list( $field, $op, $expected ) = $rule;

            if ( ! isset( $options[ $field ] ) ) {
                continue;
            }

            $field_option = $options[ $field ];
            $name = ecs_builder_option_control_name( $field, $field_option, 'bricks' );

            if ( 'toggle' === $field_option['type'] ) {
                $wants_on = ( '==' === $op ) ? (bool) $expected : ! $expected;
                $required[] = $wants_on ? [ $name, '=', true ] : [ $name, '!=', true ];
                continue;
            }

            switch ( $op ) {
                case '==':
                case 'in':
                    $required[] = [ $name, '=', is_array( $expected ) ? array_values( $expected ) : $expected ];
                    break;
                case '!=':
                case '!in':
                    $required[] = [ $name, '!=', is_array( $expected ) ? array_values( $expected ) : $expected ];
                    break;
            }
        }

        if ( ! empty( $option['condition_filters']['bricks'] ) ) {
            $required = apply_filters( $option['condition_filters']['bricks'], $required );
        }

        return $required;
    }

    /**
     * Convert a registry option definition into a Bricks control.
     */
    protected function convert_option( $key, $option, $options, $in_repeater = false ) {
        $control = [];

        if ( ! $in_repeater ) {
            $control['tab'] = 'content';
            $control['group'] = 'ecs_' . ( isset( $option['section'] ) ? $option['section'] : 'advanced' );

            $required = $this->convert_condition( $option, $options );

            if ( ! empty( $required ) ) {
                $control['required'] = $required;
            }
        }

        if ( ! empty( $option['label'] ) ) {
            $control['label'] = $option['label'];
        }

        if ( ! empty( $option['description'] ) ) {
            $control['description'] = $option['description'];
        }

        $default = ecs_builder_option_default( $option, 'bricks' );

        switch ( $option['type'] ) {
            case 'select':
                $control['type'] = 'select';
                $control['options'] = ecs_builder_option_choices( $option, 'bricks' );
                $control['inline'] = true;
                $control['clearable'] = false;
                $control['pasteStyles'] = false;
                $control['default'] = (string) $default;
                break;

            case 'toggle':
                $control['type'] = 'checkbox';
                $control['inline'] = true;
                $control['default'] = (bool) $default;
                break;

            case 'number':
                $control['type'] = 'number';
                $control['inline'] = true;

                if ( isset( $option['min'] ) ) {
                    $control['min'] = $option['min'];
                }

                if ( '' !== $default && null !== $default ) {
                    $control['default'] = $default;
                }
                break;

            case 'text':
                $control['type'] = 'text';
                $control['inline'] = true;

                if ( ! empty( $option['placeholder'] ) ) {
                    $control['placeholder'] = $option['placeholder'];
                }

                if ( '' !== $default && null !== $default ) {
                    $control['default'] = (string) $default;
                }
                break;

            case 'multiselect':
                $control['type'] = 'select';
                $control['multiple'] = true;
                $control['searchable'] = true;
                $control['options'] = ecs_builder_option_choices( $option, 'bricks' );
                break;

            case 'repeater':
                $control['type'] = 'repeater';
                $control['fields'] = [];

                foreach ( $option['fields'] as $field_key => $field ) {
                    $control['fields'][ $field_key ] = $this->convert_option( $field_key, $field, $options, true );
                }

                if ( ! empty( $option['title_field'] ) ) {
                    $control['titleProperty'] = $option['title_field'];
                }

                $default_rows = [];

                foreach ( (array) $default as $row ) {
                    $default_row = [];

                    foreach ( $option['fields'] as $field_key => $field ) {
                        $value = isset( $row[ $field_key ] ) ? $row[ $field_key ] : null;

                        if ( 'toggle' === $field['type'] ) {
                            $value = (bool) $value;
                        } elseif ( null === $value ) {
                            $value = '';
                        }

                        $default_row[ $field_key ] = $value;
                    }

                    $default_rows[] = $default_row;
                }

                $control['default'] = $default_rows;
                break;

            case 'notice':
                $control['type'] = 'info';
                $control['content'] = '<div class="ecs-pro-notice">' .
                    ( ! empty( $option['label'] ) ? '<h5>' . esc_html( $option['label'] ) . '</h5>' : '' ) .
                    '<p>' . ( isset( $option['content'] ) ? $option['content'] : '' ) . '</p></div>';
                unset( $control['label'] );
                break;
        }

        return $control;
    }

    public function set_controls() {
        $options = ecs_builder_options();

        foreach ( $options as $key => $option ) {
            $control_name = ecs_builder_option_control_name( $key, $option, 'bricks' );

            // The design select keeps its legacy 'free' key for the Standard design.
            if ( 'design' === $key ) {
                $control = $this->convert_option( $key, $option, $options );
                $control['options'] = apply_filters( 'ecs_bricks_designs', [
                    'free' => esc_html__( 'Standard', 'the-events-calendar-shortcode' ),
                ] );
                $default = ecs_builder_option_default( $option, 'bricks' );
                $control['default'] = '' === (string) $default ? 'free' : (string) $default;
                $this->controls[ $control_name ] = $control;
                continue;
            }

            $this->controls[ $control_name ] = $this->convert_option( $key, $option, $options );
        }

        $this->controls = apply_filters( 'ecs_bricks_controls', $this->controls );
    }

    public function enqueue_scripts() {
    }

    /**
     * Convert raw Bricks settings into canonical values for the shared mapper.
     */
    protected function normalize_settings( $settings ) {
        $options = ecs_builder_options();
        $values = [];

        // Bricks copies every control's default into the settings when the element
        // is added, and deletes a checkbox's key when it is unchecked, so a missing
        // checkbox means unchecked. Elements added before the shared option registry
        // only have the legacy controls; 'orderby' is a newer select that Bricks
        // always stores and that can't be cleared, so its absence marks those.
        $is_legacy_element = ! isset( $settings['orderby'] );

        foreach ( $options as $key => $option ) {
            if ( 'notice' === $option['type'] ) {
                continue;
            }

            $name = ecs_builder_option_control_name( $key, $option, 'bricks' );
            $raw = isset( $settings[ $name ] ) ? $settings[ $name ] : null;
            $default = ecs_builder_option_default( $option, 'bricks' );

            switch ( $option['type'] ) {
                case 'toggle':
                    if ( null === $raw ) {
                        // Legacy elements never stored the newer checkboxes (such as
                        // Show Date), so those keep their registry default. The legacy
                        // checkboxes (flagged via builder_defaults) were always off when absent.
                        $values[ $key ] = $is_legacy_element && ! isset( $option['builder_defaults']['bricks'] ) ? (bool) $default : false;
                    } else {
                        $values[ $key ] = (bool) $raw;
                    }
                    break;

                case 'multiselect':
                    $values[ $key ] = is_array( $raw ) ? $raw : [];
                    break;

                case 'repeater':
                    $rows = is_array( $raw ) ? $raw : (array) $default;
                    $normalized_rows = [];

                    foreach ( $rows as $row ) {
                        $normalized_row = [];

                        foreach ( $option['fields'] as $field_key => $field ) {
                            if ( 'toggle' === $field['type'] ) {
                                // Unchecked checkboxes are deleted from the row too.
                                $value = ! empty( $row[ $field_key ] );
                            } else {
                                $value = isset( $row[ $field_key ] ) ? $row[ $field_key ] : ( isset( $field['default'] ) ? $field['default'] : '' );
                            }

                            $normalized_row[ $field_key ] = $value;
                        }

                        $normalized_rows[] = $normalized_row;
                    }

                    $values[ $key ] = $normalized_rows;
                    break;

                default:
                    if ( null === $raw ) {
                        $values[ $key ] = null === $default ? '' : (string) $default;
                    } else {
                        $values[ $key ] = is_scalar( $raw ) ? (string) $raw : '';
                    }
                    break;
            }
        }

        // Legacy 'free' value maps to the standard (empty) design.
        if ( isset( $values['design'] ) && 'free' === $values['design'] ) {
            $values['design'] = '';
        }

        return $values;
    }

    public function render() {
        $root_classes[] = 'ecs-bricks-wrapper';

        $values = $this->normalize_settings( $this->settings );

        $atts = ecs_builder_settings_to_atts( $values, [ 'skip' => [ 'advanced' ] ] );
        $atts = apply_filters( 'ecs_bricks_atts', $atts, $this->settings );
        $atts = ecs_builder_apply_advanced_atts( $atts, isset( $values['advanced'] ) ? $values['advanced'] : [] );

        $this->set_attribute( '_root', 'class', $root_classes );

        echo "<div {$this->render_attributes( '_root' )}>"; // Element root attributes

        echo do_shortcode(
            '[ecs-list-events ' . implode( ' ', array_map( function ( $key, $value ) {
                return esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
            }, array_keys( $atts ), $atts ) ) . ']'
        );

        echo '</div>';
    }
}
