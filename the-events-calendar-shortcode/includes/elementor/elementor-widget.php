<?php
/**
 * Elementor widget for The Events Calendar Shortcode.
 *
 * Controls are generated from the shared builder option registry
 * (includes/builder-options.php). Legacy control names/values are preserved
 * via the registry's per-builder key overrides so existing saved pages keep
 * working.
 */

use Elementor\Repeater;

class TECS_Elementor_Widget extends \Elementor\Widget_Base {

    public function __construct( $data = [], $args = null ) {
        parent::__construct( $data, $args );
    }

    public function get_script_depends() {
        return apply_filters( 'ecs_elementor_widget_script_depends', [] );
    }

    public function get_name() {
        return 'tecs_widget';
    }

    public function get_title() {
        return __( 'The Events Calendar Shortcode & Block', 'the-events-calendar-shortcode' );
    }

    public function get_icon() {
        return 'elementor-icon-ecs';
    }

    /**
     * Convert a registry condition into Elementor's condition format,
     * translating canonical option keys/values to control names/values.
     */
    protected function convert_condition( $option, $options ) {
        $condition = [];

        foreach ( (array) ( isset( $option['condition'] ) ? $option['condition'] : [] ) as $rule ) {
            list( $field, $op, $expected ) = $rule;

            if ( ! isset( $options[ $field ] ) ) {
                continue;
            }

            $field_option = $options[ $field ];
            $name = ecs_builder_option_control_name( $field, $field_option, 'elementor' );

            if ( 'toggle' === $field_option['type'] ) {
                $wants_on = ( '==' === $op ) ? (bool) $expected : ! $expected;
                $condition[ $wants_on ? $name : $name . '!' ] = 'yes';
                continue;
            }

            switch ( $op ) {
                case '==':
                    $condition[ $name ] = $expected;
                    break;
                case '!=':
                    $condition[ $name . '!' ] = $expected;
                    break;
                case 'in':
                    $condition[ $name ] = array_values( (array) $expected );
                    break;
                case '!in':
                    $condition[ $name . '!' ] = array_values( (array) $expected );
                    break;
            }
        }

        if ( ! empty( $option['condition_filters']['elementor'] ) ) {
            $condition = apply_filters( $option['condition_filters']['elementor'], $condition );
        }

        return $condition;
    }

    /**
     * Convert a registry option definition into Elementor control args.
     */
    protected function convert_option( $key, $option, $options, $in_repeater = false ) {
        $args = [
            'label' => isset( $option['label'] ) ? $option['label'] : '',
        ];

        if ( ! empty( $option['description'] ) ) {
            $args['description'] = $option['description'];
        }

        if ( ! $in_repeater ) {
            $condition = $this->convert_condition( $option, $options );

            if ( ! empty( $condition ) ) {
                $args['condition'] = $condition;
            }
        } elseif ( ! empty( $option['condition'] ) ) {
            // Repeater sub-fields reference sibling fields directly.
            $condition = [];

            foreach ( $option['condition'] as $rule ) {
                list( $field, $op, $expected ) = $rule;

                switch ( $op ) {
                    case '==':
                    case 'in':
                        $condition[ $field ] = is_array( $expected ) ? array_values( $expected ) : $expected;
                        break;
                    case '!=':
                    case '!in':
                        $condition[ $field . '!' ] = is_array( $expected ) ? array_values( $expected ) : $expected;
                        break;
                }
            }

            $args['condition'] = $condition;
        }

        $default = ecs_builder_option_default( $option, 'elementor' );

        switch ( $option['type'] ) {
            case 'select':
                $args['type'] = \Elementor\Controls_Manager::SELECT;
                $args['options'] = ecs_builder_option_choices( $option, 'elementor' );
                $args['default'] = (string) $default;
                break;

            case 'toggle':
                $args['type'] = \Elementor\Controls_Manager::SWITCHER;
                $args['default'] = $default ? 'yes' : 'no';
                break;

            case 'number':
                $args['type'] = \Elementor\Controls_Manager::NUMBER;
                $args['default'] = '' === $default || null === $default ? '' : $default;

                if ( isset( $option['min'] ) ) {
                    $args['min'] = $option['min'];
                }
                break;

            case 'text':
                $args['type'] = \Elementor\Controls_Manager::TEXT;
                $args['default'] = (string) $default;
                $args['label_block'] = $in_repeater;

                if ( ! empty( $option['placeholder'] ) ) {
                    $args['placeholder'] = $option['placeholder'];
                }
                break;

            case 'multiselect':
                $args['type'] = \Elementor\Controls_Manager::SELECT2;
                $args['multiple'] = true;
                $args['label_block'] = true;
                $args['options'] = ecs_builder_option_choices( $option, 'elementor' );
                $args['default'] = [];
                break;

            case 'repeater':
                $repeater = new Repeater();

                foreach ( $option['fields'] as $field_key => $field ) {
                    $repeater->add_control( $field_key, $this->convert_option( $field_key, $field, $options, true ) );
                }

                $default_rows = [];

                foreach ( (array) $default as $row ) {
                    $default_row = [];

                    foreach ( $option['fields'] as $field_key => $field ) {
                        $value = isset( $row[ $field_key ] ) ? $row[ $field_key ] : null;

                        if ( 'toggle' === $field['type'] ) {
                            $value = $value ? 'yes' : '';
                        }

                        $default_row[ $field_key ] = null === $value ? '' : $value;
                    }

                    $default_rows[] = $default_row;
                }

                $args['type'] = \Elementor\Controls_Manager::REPEATER;
                $args['fields'] = $repeater->get_controls();
                $args['default'] = $default_rows;
                $args['prevent_empty'] = false;

                if ( ! empty( $option['title_field'] ) ) {
                    $args['title_field'] = '{{{ ' . $option['title_field'] . ' }}}';
                }
                break;

            case 'notice':
                $args['type'] = \Elementor\Controls_Manager::RAW_HTML;
                $args['raw'] = isset( $option['content'] ) ? $option['content'] : '';
                $args['content_classes'] = 'ecs-pro-notice';
                break;
        }

        return $args;
    }

    protected function _register_controls() {
        $options = ecs_builder_options();
        $sections = ecs_builder_option_sections();
        $current_section = null;

        foreach ( $options as $key => $option ) {
            $section = isset( $option['section'] ) ? $option['section'] : 'advanced';

            if ( $section !== $current_section ) {
                if ( null !== $current_section ) {
                    $this->end_controls_section();
                }

                $this->start_controls_section(
                    'ecs_section_' . $section,
                    [
                        'label' => isset( $sections[ $section ] ) ? $sections[ $section ] : $section,
                        'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                    ]
                );

                $current_section = $section;
            }

            // Legacy extension point (pro used this before the shared registry existed).
            if ( 'advanced' === $key ) {
                do_action( 'ecs_elementor_widget_controls_before_advanced', $this );
            }

            $control_name = ecs_builder_option_control_name( $key, $option, 'elementor' );
            $this->add_control( $control_name, $this->convert_option( $key, $option, $options ) );
        }

        if ( null !== $current_section ) {
            $this->end_controls_section();
        }
    }

    /**
     * Convert raw Elementor settings into canonical values for the shared mapper.
     */
    protected function normalize_settings( $settings ) {
        $options = ecs_builder_options();
        $values = [];

        foreach ( $options as $key => $option ) {
            if ( 'notice' === $option['type'] ) {
                continue;
            }

            $name = ecs_builder_option_control_name( $key, $option, 'elementor' );
            $raw = isset( $settings[ $name ] ) ? $settings[ $name ] : null;
            $default = ecs_builder_option_default( $option, 'elementor' );

            switch ( $option['type'] ) {
                case 'toggle':
                    $values[ $key ] = null === $raw ? (bool) $default : 'yes' === $raw;
                    break;

                case 'multiselect':
                    if ( is_array( $raw ) ) {
                        $values[ $key ] = $raw;
                    } else {
                        $values[ $key ] = $raw ? array_filter( array_map( 'trim', explode( ',', $raw ) ) ) : [];
                    }
                    break;

                case 'repeater':
                    $rows = is_array( $raw ) ? $raw : (array) $default;
                    $normalized_rows = [];

                    foreach ( $rows as $row ) {
                        $normalized_row = [];

                        foreach ( $option['fields'] as $field_key => $field ) {
                            $value = isset( $row[ $field_key ] ) ? $row[ $field_key ] : ( isset( $field['default'] ) ? $field['default'] : '' );

                            if ( 'toggle' === $field['type'] ) {
                                $value = is_bool( $value ) ? $value : in_array( $value, [ 'yes', 'true' ], true );
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

        return $values;
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $values = $this->normalize_settings( $settings );

        $atts = ecs_builder_settings_to_atts( $values, [ 'skip' => [ 'advanced' ] ] );
        $atts = apply_filters( 'ecs_elementor_widget_atts', $atts, $settings );
        $atts = ecs_builder_apply_advanced_atts( $atts, isset( $values['advanced'] ) ? $values['advanced'] : [] );

        echo do_shortcode(
            '[ecs-list-events ' . implode( ' ', array_map( function ( $key, $value ) {
                return esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
            }, array_keys( $atts ), $atts ) ) . ']'
        );
    }

    protected function _content_template() {
        // Define your template variables here
    }
}
