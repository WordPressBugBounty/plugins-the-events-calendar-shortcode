<?php
/**
 * WPBakery Page Builder integration.
 *
 * Maps the shared builder option registry (includes/builder-options.php) to a
 * WPBakery element. The element uses a wrapper shortcode [tecs_events] whose
 * handler converts the saved params back into an [ecs-list-events] shortcode,
 * the same way the Elementor widget and Bricks element render.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'tecs_wpbakery_notice_field' ) ) {
    /**
     * Custom WPBakery param type used for informational/upsell notices.
     */
    function tecs_wpbakery_notice_field( $settings, $value ) {
        $content = isset( $settings['content'] ) ? $settings['content'] : '';

        return '<div class="ecs-pro-notice">' .
               ( ! empty( $settings['notice_heading'] ) ? '<strong>' . esc_html( $settings['notice_heading'] ) . '</strong>' : '' ) .
               '<p>' . wp_kses_post( $content ) . '</p>' .
               '<input type="hidden" name="' . esc_attr( $settings['param_name'] ) . '" class="wpb_vc_param_value" value="" />' .
               '</div>';
    }
}

if ( ! function_exists( 'tecs_wpbakery_options' ) ) {
    /**
     * The shared builder options, adjusted for WPBakery.
     *
     * WPBakery always shows and saves one row for a param_group with no rows,
     * filling it from the child defaults. Content Order defaults to no rows (the
     * design's own order), so that blank row would save as "Title" and hide the
     * rest of each event. Its Item dropdown gets an empty "Design default" choice
     * instead, and rows without an item are ignored.
     */
    function tecs_wpbakery_options() {
        $options = ecs_builder_options();

        if ( isset( $options['contentorder']['fields']['item'] ) ) {
            $item = $options['contentorder']['fields']['item'];
            $item['choices'] = [ '' => __( 'Design default', 'the-events-calendar-shortcode' ) ] + ecs_builder_option_choices( $item, 'wpbakery' );
            $item['default'] = '';
            unset( $item['choices_cb'] );
            $options['contentorder']['fields']['item'] = $item;
        }

        return $options;
    }
}

if ( ! function_exists( 'tecs_wpbakery_dependency' ) ) {
    /**
     * Convert a registry condition (list of [ field, op, value ] rules) into a
     * WPBakery dependency. WPBakery only supports a single-element dependency,
     * so we pick the most specific rule we can express.
     */
    function tecs_wpbakery_dependency( $option, $options ) {
        if ( empty( $option['condition'] ) ) {
            return null;
        }

        // Prefer the last equality/in rule (most specific), fall back to the first rule.
        $rule = null;

        foreach ( $option['condition'] as $candidate ) {
            if ( in_array( $candidate[1], [ '==', 'in' ], true ) ) {
                $rule = $candidate;
            }
        }

        if ( null === $rule ) {
            $rule = $option['condition'][0];
        }

        list( $field, $op, $expected ) = $rule;

        if ( ! isset( $options[ $field ] ) ) {
            return null;
        }

        $field_option = $options[ $field ];
        $name = ecs_builder_option_control_name( $field, $field_option, 'wpbakery' );
        $is_toggle = isset( $field_option['type'] ) && 'toggle' === $field_option['type'];

        switch ( $op ) {
            case '==':
                if ( $is_toggle ) {
                    return $expected ? [ 'element' => $name, 'value' => [ 'true' ] ] : [ 'element' => $name, 'is_empty' => true ];
                }

                return [ 'element' => $name, 'value' => array_map( 'strval', (array) $expected ) ];
            case 'in':
                return [ 'element' => $name, 'value' => array_map( 'strval', (array) $expected ) ];
            case '!=':
                if ( $is_toggle ) {
                    return $expected ? [ 'element' => $name, 'is_empty' => true ] : [ 'element' => $name, 'value' => [ 'true' ] ];
                }

                if ( '' === $expected ) {
                    return [ 'element' => $name, 'not_empty' => true ];
                }

                return [ 'element' => $name, 'value_not_equal_to' => (string) $expected ];
            case '!in':
                // Express as an allow-list using the known choices.
                $choices = ecs_builder_option_choices( $field_option, 'wpbakery' );

                if ( empty( $choices ) ) {
                    return null;
                }

                $allowed = array_diff( array_map( 'strval', array_keys( $choices ) ), array_map( 'strval', (array) $expected ) );

                return [ 'element' => $name, 'value' => array_values( $allowed ) ];
        }

        return null;
    }
}

if ( ! function_exists( 'tecs_wpbakery_param' ) ) {
    /**
     * Convert a single registry option into a WPBakery param definition.
     */
    function tecs_wpbakery_param( $key, $option, $options, $in_group = false ) {
        $sections = ecs_builder_option_sections();

        $param = [
            'param_name' => ecs_builder_option_control_name( $key, $option, 'wpbakery' ),
            'heading' => isset( $option['label'] ) ? $option['label'] : '',
        ];

        if ( ! $in_group ) {
            $section = isset( $option['section'] ) ? $option['section'] : 'advanced';
            $param['group'] = isset( $sections[ $section ] ) ? $sections[ $section ] : $sections['advanced'];

            $dependency = tecs_wpbakery_dependency( $option, $options );

            if ( $dependency ) {
                $param['dependency'] = $dependency;
            }
        }

        if ( ! empty( $option['description'] ) ) {
            $param['description'] = $option['description'];
        }

        $default = ecs_builder_option_default( $option, 'wpbakery' );

        switch ( $option['type'] ) {
            case 'select':
                $choices = ecs_builder_option_choices( $option, 'wpbakery' );
                $param['type'] = 'dropdown';
                // WPBakery dropdowns expect label => value pairs.
                $param['value'] = array_flip( array_map( 'strval', $choices ) );
                $param['std'] = (string) $default;
                break;

            case 'toggle':
                $param['type'] = 'checkbox';
                $param['value'] = [ __( 'Yes', 'the-events-calendar-shortcode' ) => 'true' ];
                $param['std'] = $default ? 'true' : '';
                break;

            case 'number':
                $param['type'] = 'textfield';
                $param['std'] = '' === $default || null === $default ? '' : (string) $default;
                break;

            case 'text':
                $param['type'] = 'textfield';
                $param['std'] = (string) $default;

                if ( ! empty( $option['placeholder'] ) ) {
                    $param['settings'] = [ 'placeholder' => $option['placeholder'] ];
                }
                break;

            case 'multiselect':
                $choices = ecs_builder_option_choices( $option, 'wpbakery' );
                $param['type'] = 'checkbox';
                $param['value'] = array_flip( array_map( 'strval', $choices ) );
                $param['std'] = '';
                break;

            case 'repeater':
                $param['type'] = 'param_group';
                $param['params'] = [];

                $first_field = key( $option['fields'] );
                $title_field = isset( $option['title_field'] ) ? $option['title_field'] : $first_field;

                foreach ( $option['fields'] as $field_key => $field ) {
                    $child = tecs_wpbakery_param( $field_key, $field, $options, true );

                    // Show the row's main field and title in its collapsed header.
                    if ( in_array( $field_key, [ $first_field, $title_field ], true ) ) {
                        $child['admin_label'] = true;
                    }

                    $param['params'][] = $child;
                }

                $default_rows = [];

                foreach ( (array) $default as $row ) {
                    $default_row = [];

                    foreach ( $option['fields'] as $field_key => $field ) {
                        $value = isset( $row[ $field_key ] ) ? $row[ $field_key ] : null;

                        if ( 'toggle' === $field['type'] ) {
                            $value = $value ? 'true' : '';
                        }

                        $default_row[ $field_key ] = null === $value ? '' : (string) $value;
                    }

                    $default_rows[] = $default_row;
                }

                $param['value'] = rawurlencode( wp_json_encode( $default_rows ) );
                break;

            case 'notice':
                $param['type'] = 'tecs_notice';
                $param['content'] = isset( $option['content'] ) ? $option['content'] : '';
                // The notice prints its own bold heading; WPBakery's field label would repeat it.
                $param['notice_heading'] = $param['heading'];
                $param['heading'] = '';
                break;
        }

        return $param;
    }
}

if ( ! function_exists( 'tecs_wpbakery_params' ) ) {
    function tecs_wpbakery_params() {
        $options = tecs_wpbakery_options();
        $params = [];

        foreach ( $options as $key => $option ) {
            $params[] = tecs_wpbakery_param( $key, $option, $options );
        }

        return apply_filters( 'ecs_wpbakery_params', $params, $options );
    }
}

if ( ! function_exists( 'tecs_wpbakery_map' ) ) {
    function tecs_wpbakery_map() {
        if ( ! function_exists( 'vc_map' ) ) {
            return;
        }

        if ( function_exists( 'vc_add_shortcode_param' ) ) {
            vc_add_shortcode_param( 'tecs_notice', 'tecs_wpbakery_notice_field' );
        }

        vc_map( [
            'name' => __( 'The Events Calendar Shortcode & Block', 'the-events-calendar-shortcode' ),
            'base' => 'tecs_events',
            'description' => __( 'List and promote your events', 'the-events-calendar-shortcode' ),
            'category' => __( 'Content', 'js_composer' ),
            // CSS class (see includes/wpbakery/static/admin.css). WPBakery only
            // treats .png/.jpg/.gif paths as image icons — .svg is ignored.
            'icon' => 'icon-ecs-wpbakery',
            'params' => tecs_wpbakery_params(),
        ] );
    }
}

if ( ! function_exists( 'tecs_wpbakery_normalize_settings' ) ) {
    /**
     * Convert raw [tecs_events] shortcode attributes into canonical values for
     * the shared registry mapper.
     */
    function tecs_wpbakery_normalize_settings( $atts ) {
        $options = tecs_wpbakery_options();
        $values = [];

        foreach ( $options as $key => $option ) {
            if ( 'notice' === $option['type'] ) {
                continue;
            }

            $name = ecs_builder_option_control_name( $key, $option, 'wpbakery' );
            $raw = isset( $atts[ $name ] ) ? $atts[ $name ] : null;
            $default = ecs_builder_option_default( $option, 'wpbakery' );

            switch ( $option['type'] ) {
                case 'toggle':
                    $values[ $key ] = null === $raw ? (bool) $default : 'true' === $raw;
                    break;

                case 'multiselect':
                    $values[ $key ] = null === $raw || '' === $raw ? [] : array_filter( array_map( 'trim', explode( ',', $raw ) ) );
                    break;

                case 'repeater':
                    if ( null === $raw ) {
                        $rows = (array) $default;
                    } else {
                        $rows = function_exists( 'vc_param_group_parse_atts' ) ? (array) vc_param_group_parse_atts( $raw ) : [];
                    }

                    $normalized_rows = [];

                    foreach ( $rows as $row ) {
                        $normalized_row = [];

                        foreach ( $option['fields'] as $field_key => $field ) {
                            $value = isset( $row[ $field_key ] ) ? $row[ $field_key ] : null;

                            if ( 'toggle' === $field['type'] ) {
                                $value = is_bool( $value ) ? $value : 'true' === $value;
                            } elseif ( null === $value ) {
                                $value = isset( $field['default'] ) ? $field['default'] : '';
                            }

                            $normalized_row[ $field_key ] = $value;
                        }

                        $normalized_rows[] = $normalized_row;
                    }

                    $values[ $key ] = $normalized_rows;
                    break;

                default:
                    $values[ $key ] = null === $raw ? ( null === $default ? '' : (string) $default ) : (string) $raw;
                    break;
            }
        }

        return $values;
    }
}

if ( ! function_exists( 'tecs_wpbakery_render' ) ) {
    /**
     * [tecs_events] shortcode handler: builds and renders [ecs-list-events].
     */
    function tecs_wpbakery_render( $atts ) {
        $atts = is_array( $atts ) ? $atts : [];

        $values = tecs_wpbakery_normalize_settings( $atts );

        $shortcode_atts = ecs_builder_settings_to_atts( $values, [ 'skip' => [ 'advanced' ] ] );
        $shortcode_atts = apply_filters( 'ecs_wpbakery_atts', $shortcode_atts, $atts );
        $shortcode_atts = ecs_builder_apply_advanced_atts( $shortcode_atts, isset( $values['advanced'] ) ? $values['advanced'] : [] );

        return do_shortcode(
            '[ecs-list-events ' . implode( ' ', array_map( function ( $key, $value ) {
                return esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
            }, array_keys( $shortcode_atts ), $shortcode_atts ) ) . ']'
        );
    }
}

if ( ! function_exists( 'tecs_wpbakery_editor_preview_styles' ) ) {
    /**
     * Turns off the events' links in the WPBakery frontend editor preview.
     *
     * Otherwise clicking an event opens it in a new tab or loads it in the editor's preview
     * frame. The block editor preview does the same (see block/styles/index.scss).
     *
     * @since 4.1.0
     *
     * @return void
     */
    function tecs_wpbakery_editor_preview_styles() {
        if ( ! function_exists( 'vc_is_page_editable' ) || ! vc_is_page_editable() ) {
            return;
        }

        wp_register_style( 'tecs-wpbakery-editor-preview', false, [], null );
        wp_enqueue_style( 'tecs-wpbakery-editor-preview' );
        wp_add_inline_style( 'tecs-wpbakery-editor-preview', '.ecs-events a, .ecs-event-list a, .ecs-view-all-events a { pointer-events: none; }' );
    }
    add_action( 'wp_enqueue_scripts', 'tecs_wpbakery_editor_preview_styles' );
}
