<?php
/**
 * Divi 4 and Divi 5 integration.
 *
 * Converts the shared builder option registry (includes/builder-options.php) into
 * Divi fields: a Divi 4 module (divi4-module.php) and a native Divi 5 module
 * (divi5-module.php). Both save each option under the same name, so Divi 5 converts
 * Divi 4 layouts, and both render through tecs_divi_render().
 *
 * Divi has no repeater field, so registry repeaters become:
 * - a sortable list with checkboxes (order and enabled) when the first sub-option is a
 *   select, plus one field per choice for each other sub-option (such as the filter bar labels);
 * - a textarea of key="value" pairs for the Advanced/Other key and value rows.
 *
 * @since 4.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'TECS_DIVI_MODULE_SLUG' ) ) {
    /**
     * Divi 4 shortcode of the module, also the d4Shortcode of the Divi 5 module.
     *
     * @since 4.1.0
     */
    define( 'TECS_DIVI_MODULE_SLUG', 'tecs_divi_events' );
}

if ( ! defined( 'TECS_DIVI_EMPTY_CHOICE' ) ) {
    /**
     * Divi value of a select choice whose registry value is an empty string, such as the Standard design.
     *
     * @since 4.1.0
     */
    define( 'TECS_DIVI_EMPTY_CHOICE', '_none' );
}

if ( ! function_exists( 'tecs_divi_control_name' ) ) {
    /**
     * Divi field name of a registry option.
     *
     * Names that Divi uses for its own module settings get a prefix.
     *
     * @since 4.1.0
     *
     * @param string $key    Option key.
     * @param array  $option Option definition.
     *
     * @return string
     */
    function tecs_divi_control_name( $key, $option ) {
        $name = ecs_builder_option_control_name( $key, $option, 'divi' );

        if ( in_array( $name, [ 'id', 'class', 'content', 'module', 'css', 'admin_label' ], true ) ) {
            $name = 'tecs_' . $name;
        }

        return $name;
    }
}

if ( ! function_exists( 'tecs_divi_is_list_repeater' ) ) {
    /**
     * Whether a repeater is shown as a sortable list: its first sub-option is a select
     * of fixed choices and it has a toggle for each row.
     *
     * @since 4.1.0
     *
     * @param array $option Repeater option.
     *
     * @return bool
     */
    function tecs_divi_is_list_repeater( $option ) {
        $fields = isset( $option['fields'] ) ? $option['fields'] : [];
        $first = reset( $fields );

        return $first && 'select' === $first['type'] && in_array( 'toggle', wp_list_pluck( $fields, 'type' ), true );
    }
}

if ( ! function_exists( 'tecs_divi_raw_value' ) ) {
    /**
     * Converts a canonical registry value (bool for toggles) to the Divi format ('on'/'off').
     *
     * @since 4.1.0
     *
     * @param mixed  $value Canonical value.
     * @param string $type  Option type.
     *
     * @return mixed
     */
    function tecs_divi_raw_value( $value, $type ) {
        if ( 'toggle' === $type ) {
            return $value ? 'on' : 'off';
        }

        if ( is_array( $value ) ) {
            return array_map( 'strval', $value );
        }

        $value = null === $value ? '' : (string) $value;

        // Divi 5 shows an empty select when the chosen option's value is an empty string.
        return 'select' === $type && '' === $value ? TECS_DIVI_EMPTY_CHOICE : $value;
    }
}

if ( ! function_exists( 'tecs_divi_select_options' ) ) {
    /**
     * Converts registry choices to Divi select options (value => label).
     *
     * @since 4.1.0
     *
     * @param array $choices Registry choices.
     *
     * @return array
     */
    function tecs_divi_select_options( $choices ) {
        $options = [];

        foreach ( $choices as $value => $label ) {
            $options[ tecs_divi_raw_value( $value, 'select' ) ] = (string) $label;
        }

        return $options;
    }
}

if ( ! function_exists( 'tecs_divi_select_value' ) ) {
    /**
     * Converts a saved Divi select value back to the registry value.
     *
     * @since 4.1.0
     *
     * @param string $value Saved value.
     *
     * @return string
     */
    function tecs_divi_select_value( $value ) {
        return TECS_DIVI_EMPTY_CHOICE === $value ? '' : $value;
    }
}

if ( ! function_exists( 'tecs_divi_conditions' ) ) {
    /**
     * Converts registry conditions to Divi field names and value formats.
     *
     * @since 4.1.0
     *
     * @param array $conditions Registry conditions, a list of [ option key, op, value ].
     * @param array $options    Registry options.
     *
     * @return array List of [ field name, op, value ].
     */
    function tecs_divi_conditions( $conditions, $options ) {
        $result = [];

        foreach ( (array) $conditions as $rule ) {
            list( $key, $op, $expected ) = $rule;

            if ( ! isset( $options[ $key ] ) ) {
                continue;
            }

            $type = $options[ $key ]['type'];
            $expected = is_array( $expected )
                ? array_map( function ( $value ) use ( $type ) {
                    return tecs_divi_raw_value( $value, $type );
                }, $expected )
                : tecs_divi_raw_value( $expected, $type );
            $result[] = [ tecs_divi_control_name( $key, $options[ $key ] ), $op, $expected ];
        }

        return $result;
    }
}

if ( ! function_exists( 'tecs_divi_get_fields' ) ) {
    /**
     * Divi fields for the registry options, keyed by field name.
     *
     * Each field has a kind (select, toggle, text, number, multiselect, list, textarea or
     * notice), the option key and section, a label, a description, a default in the Divi
     * format, options (value => label), conditions and, for lists and their per-choice
     * fields, the repeater details used to rebuild its rows.
     *
     * @since 4.1.0
     *
     * @return array
     */
    function tecs_divi_get_fields() {
        $options = ecs_builder_options();
        $fields = [];

        foreach ( $options as $key => $option ) {
            $name = tecs_divi_control_name( $key, $option );
            $default = ecs_builder_option_default( $option, 'divi' );

            $field = [
                'key' => $key,
                'kind' => $option['type'],
                'section' => isset( $option['section'] ) ? $option['section'] : 'advanced',
                'label' => isset( $option['label'] ) ? $option['label'] : '',
                'description' => isset( $option['description'] ) ? $option['description'] : '',
                'conditions' => tecs_divi_conditions( isset( $option['condition'] ) ? $option['condition'] : [], $options ),
            ];

            switch ( $option['type'] ) {
                case 'select':
                    $field['options'] = tecs_divi_select_options( ecs_builder_option_choices( $option, 'divi' ) );
                    $field['default'] = tecs_divi_raw_value( $default, 'select' );
                    break;

                case 'multiselect':
                    $field['options'] = array_map( 'strval', ecs_builder_option_choices( $option, 'divi' ) );
                    $field['default'] = [];

                    if ( ! empty( $option['taxonomy'] ) ) {
                        $field['taxonomy'] = $option['taxonomy'];
                        $field['options'] = tecs_divi_term_options( $option['taxonomy'], $field['options'] );
                    }
                    break;

                case 'notice':
                    $field['label'] = isset( $option['label'] ) ? $option['label'] : '';
                    $field['description'] = isset( $option['content'] ) ? $option['content'] : '';
                    break;

                case 'repeater':
                    if ( tecs_divi_is_list_repeater( $option ) ) {
                        $fields = array_merge( $fields, tecs_divi_get_list_fields( $key, $name, $option, $field, (array) $default ) );
                        continue 2;
                    }

                    if ( ! isset( $option['fields']['key'], $option['fields']['value'] ) ) {
                        continue 2;
                    }

                    $field['kind'] = 'textarea';
                    $field['default'] = '';
                    $field['description'] = trim( __( 'Any other shortcode options, such as key="value" key2="value2".', 'the-events-calendar-shortcode' ) . ' ' . $field['description'] );
                    break;

                default:
                    $field['default'] = tecs_divi_raw_value( $default, $option['type'] );
            }

            $fields[ $name ] = $field;
        }

        /**
         * Filters the Divi module fields built from the builder option registry.
         *
         * @since 4.1.0
         *
         * @param array $fields  Fields keyed by Divi field name.
         * @param array $options Builder options.
         */
        return apply_filters( 'ecs_divi_fields', $fields, $options );
    }
}

if ( ! function_exists( 'tecs_divi_term_options' ) ) {
    /**
     * Re-keys term choices by term ID, the value Divi 4's categories field saves.
     *
     * @since 4.1.0
     *
     * @param string $taxonomy Taxonomy name.
     * @param array  $choices  Term names keyed by slug.
     *
     * @return array Term names keyed by term ID.
     */
    function tecs_divi_term_options( $taxonomy, $choices ) {
        $options = [];

        foreach ( $choices as $slug => $label ) {
            $term = get_term_by( 'slug', $slug, $taxonomy );

            if ( $term ) {
                $options[ (string) $term->term_id ] = $label;
            }
        }

        return $options;
    }
}

if ( ! function_exists( 'tecs_divi_get_list_fields' ) ) {
    /**
     * Fields for a repeater shown as a sortable list.
     *
     * @since 4.1.0
     *
     * @param string $key     Repeater option key.
     * @param string $name    Repeater field name.
     * @param array  $option  Repeater option.
     * @param array  $field   Common field properties.
     * @param array  $default Default rows.
     *
     * @return array Fields keyed by field name.
     */
    function tecs_divi_get_list_fields( $key, $name, $option, $field, $default ) {
        $sub_fields = $option['fields'];
        $choice_key = key( $sub_fields );
        $choices = ecs_builder_option_choices( $sub_fields[ $choice_key ] );
        $toggle_key = array_search( 'toggle', wp_list_pluck( $sub_fields, 'type' ), true );

        // Default rows first (in their order), then the remaining choices, unchecked.
        $items = [];
        $default_rows = [];

        foreach ( $default as $row ) {
            if ( isset( $row[ $choice_key ], $choices[ $row[ $choice_key ] ] ) ) {
                $default_rows[ $row[ $choice_key ] ] = $row;
            }
        }

        foreach ( array_merge( array_keys( $default_rows ), array_diff( array_keys( $choices ), array_keys( $default_rows ) ) ) as $index => $choice ) {
            $checked = isset( $default_rows[ $choice ] ) && ! empty( $default_rows[ $choice ][ $toggle_key ] );
            $items[] = [
                'value' => (string) $choices[ $choice ],
                'checked' => $checked ? '1' : '0',
                'dragID' => (string) $index,
            ];
        }

        $fields = [];
        $fields[ $name ] = array_merge( $field, [
            'kind' => 'list',
            'default' => $items,
            'choices' => array_map( 'strval', $choices ),
            'choice_key' => $choice_key,
            'toggle_key' => $toggle_key,
            'description' => trim( $field['description'] . ' ' . __( 'Drag to reorder, and check the items to show.', 'the-events-calendar-shortcode' ) ),
        ] );

        // One field per choice for each other sub-option, such as the filter bar labels.
        foreach ( $sub_fields as $sub_key => $sub_field ) {
            if ( $sub_key === $choice_key || $sub_key === $toggle_key ) {
                continue;
            }

            foreach ( $choices as $choice => $choice_label ) {
                if ( ! ecs_builder_condition_met( $sub_field, [ $choice_key => $choice ] ) ) {
                    continue;
                }

                $sub_default = isset( $default_rows[ $choice ][ $sub_key ] ) ? $default_rows[ $choice ][ $sub_key ] : ( isset( $sub_field['default'] ) ? $sub_field['default'] : '' );
                $sub = array_merge( $field, [
                    'key' => $key,
                    'kind' => $sub_field['type'],
                    'label' => sprintf(
                        // translators: %1$s: list item, such as Category. %2$s: setting, such as Label.
                        __( '%1$s: %2$s', 'the-events-calendar-shortcode' ),
                        $choice_label,
                        $sub_field['label']
                    ),
                    'description' => isset( $sub_field['description'] ) ? $sub_field['description'] : '',
                    'default' => tecs_divi_raw_value( $sub_default, $sub_field['type'] ),
                    'list' => [ $name, $choice, $sub_key ],
                ] );

                if ( 'select' === $sub_field['type'] ) {
                    $sub['options'] = tecs_divi_select_options( ecs_builder_option_choices( $sub_field ) );
                }

                $fields[ $name . '_' . sanitize_key( $choice ) . '_' . $sub_key ] = $sub;
            }
        }

        return $fields;
    }
}

if ( ! function_exists( 'tecs_divi_get_sections' ) ) {
    /**
     * Option groups (toggles) of the module, keyed by section.
     *
     * @since 4.1.0
     *
     * @return array
     */
    function tecs_divi_get_sections() {
        $used = array_unique( wp_list_pluck( tecs_divi_get_fields(), 'section' ) );

        return array_intersect_key( ecs_builder_option_sections(), array_flip( $used ) );
    }
}

if ( ! function_exists( 'tecs_divi_parse_list' ) ) {
    /**
     * Reads a sortable list value saved by Divi 4 (encoded JSON) or Divi 5 (array).
     *
     * @since 4.1.0
     *
     * @param mixed $value Saved value.
     *
     * @return array|null List of [ value, checked ] items, null when nothing was saved.
     */
    function tecs_divi_parse_list( $value ) {
        if ( is_string( $value ) ) {
            if ( '' === $value ) {
                return null;
            }

            $value = json_decode( str_replace( [ '%91', '%93', '%92', '%22', '&#91;', '&#93;' ], [ '[', ']', '\\', '"', '[', ']' ], $value ), true );
        }

        if ( ! is_array( $value ) ) {
            return null;
        }

        $items = [];

        foreach ( $value as $item ) {
            if ( is_array( $item ) && isset( $item['value'] ) ) {
                $items[] = [
                    'value' => trim( wp_strip_all_tags( (string) $item['value'] ) ),
                    'checked' => ! empty( $item['checked'] ) && '0' !== (string) $item['checked'],
                ];
            }
        }

        return $items;
    }
}

if ( ! function_exists( 'tecs_divi_get_values' ) ) {
    /**
     * Converts saved Divi settings to canonical registry values.
     *
     * @since 4.1.0
     *
     * @param array $settings Saved values keyed by Divi field name, from either Divi version.
     *
     * @return array Values keyed by option key, as ecs_builder_settings_to_atts() expects.
     */
    function tecs_divi_get_values( $settings ) {
        $options = ecs_builder_options();
        $fields = tecs_divi_get_fields();
        $values = [];

        foreach ( $fields as $name => $field ) {
            if ( 'notice' === $field['kind'] || ! empty( $field['list'] ) ) {
                continue;
            }

            $key = $field['key'];
            $default = isset( $field['default'] ) ? $field['default'] : '';
            $raw = isset( $settings[ $name ] ) ? $settings[ $name ] : null;
            $is_empty = null === $raw || '' === $raw;

            switch ( $field['kind'] ) {
                case 'toggle':
                    $values[ $key ] = 'on' === ( $is_empty ? $default : $raw );
                    break;

                case 'multiselect':
                    $items = $is_empty ? [] : ( is_array( $raw ) ? $raw : explode( ',', (string) $raw ) );
                    $items = array_values( array_filter( array_map( 'trim', array_map( 'strval', $items ) ), 'strlen' ) );

                    // Term IDs back to the slugs the shortcode expects.
                    if ( ! empty( $field['taxonomy'] ) ) {
                        $items = array_values( array_filter( array_map( function ( $item ) use ( $field ) {
                            $term = is_numeric( $item ) ? get_term( (int) $item, $field['taxonomy'] ) : null;

                            return $term && ! is_wp_error( $term ) ? $term->slug : ( is_numeric( $item ) ? '' : $item );
                        }, $items ) ) );
                    }

                    $values[ $key ] = $items;
                    break;

                case 'list':
                    $values[ $key ] = tecs_divi_get_list_rows( $name, $field, $settings, $fields );
                    break;

                case 'textarea':
                    $rows = [];
                    $parsed = $is_empty ? [] : shortcode_parse_atts( (string) $raw );

                    foreach ( (array) $parsed as $att => $att_value ) {
                        if ( is_string( $att ) ) {
                            $rows[] = [ 'key' => $att, 'value' => $att_value ];
                        }
                    }

                    $values[ $key ] = $rows;
                    break;

                default:
                    $value = $is_empty ? $default : $raw;
                    $values[ $key ] = is_array( $value ) ? '' : tecs_divi_select_value( (string) $value );
            }
        }

        return $values;
    }
}

if ( ! function_exists( 'tecs_divi_get_list_rows' ) ) {
    /**
     * Rebuilds repeater rows from a sortable list and its per-choice fields.
     *
     * @since 4.1.0
     *
     * @param string $name     List field name.
     * @param array  $field    List field.
     * @param array  $settings Saved values keyed by Divi field name.
     * @param array  $fields   All Divi fields.
     *
     * @return array
     */
    function tecs_divi_get_list_rows( $name, $field, $settings, $fields ) {
        $items = tecs_divi_parse_list( isset( $settings[ $name ] ) ? $settings[ $name ] : null );

        if ( null === $items ) {
            $items = tecs_divi_parse_list( $field['default'] );
        }

        // Items match a choice by label or by key, ignoring case.
        $lookup = [];

        foreach ( $field['choices'] as $choice => $label ) {
            $lookup[ strtolower( $label ) ] = $choice;
            $lookup[ strtolower( $choice ) ] = $choice;
        }

        $rows = [];

        foreach ( $items as $item ) {
            $match = strtolower( $item['value'] );

            if ( ! isset( $lookup[ $match ] ) ) {
                continue;
            }

            $choice = $lookup[ $match ];
            $row = [
                $field['choice_key'] => $choice,
                $field['toggle_key'] => $item['checked'],
            ];

            foreach ( $fields as $sub_name => $sub_field ) {
                if ( empty( $sub_field['list'] ) || $sub_field['list'][0] !== $name || $sub_field['list'][1] !== $choice ) {
                    continue;
                }

                $raw = isset( $settings[ $sub_name ] ) ? $settings[ $sub_name ] : null;
                $value = null === $raw ? $sub_field['default'] : $raw;
                $row[ $sub_field['list'][2] ] = 'toggle' === $sub_field['kind'] ? 'on' === $value : tecs_divi_select_value( (string) $value );
            }

            $rows[] = $row;
        }

        return $rows;
    }
}

if ( ! function_exists( 'tecs_divi_get_shortcode_atts' ) ) {
    /**
     * Converts saved Divi settings to [ecs-list-events] attributes.
     *
     * @since 4.1.0
     *
     * @param array $settings Saved values keyed by Divi field name.
     *
     * @return array
     */
    function tecs_divi_get_shortcode_atts( $settings ) {
        $values = tecs_divi_get_values( is_array( $settings ) ? $settings : [] );

        $atts = ecs_builder_settings_to_atts( $values, [ 'skip' => [ 'advanced' ] ] );

        /**
         * Filters the shortcode attributes of a Divi module.
         *
         * @since 4.1.0
         *
         * @param array $atts     Shortcode attributes.
         * @param array $settings Saved values keyed by Divi field name.
         * @param array $values   Values keyed by option key.
         */
        $atts = apply_filters( 'ecs_divi_atts', $atts, $settings, $values );

        return ecs_builder_apply_advanced_atts( $atts, isset( $values['advanced'] ) ? $values['advanced'] : [] );
    }
}

if ( ! function_exists( 'tecs_divi_render' ) ) {
    /**
     * Renders the events for saved Divi settings.
     *
     * @since 4.1.0
     *
     * @param array $settings Saved values keyed by Divi field name.
     *
     * @return string
     */
    function tecs_divi_render( $settings ) {
        $atts = tecs_divi_get_shortcode_atts( $settings );

        return do_shortcode(
            '[ecs-list-events ' . implode( ' ', array_map( function ( $key, $value ) {
                return esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
            }, array_keys( $atts ), $atts ) ) . ']'
        );
    }
}

if ( ! function_exists( 'tecs_divi_is_divi5' ) ) {
    /**
     * Whether the Divi 5 builder is active.
     *
     * @since 4.1.0
     *
     * @return bool
     */
    function tecs_divi_is_divi5() {
        return function_exists( 'et_builder_d5_enabled' ) && et_builder_d5_enabled();
    }
}

if ( ! function_exists( 'tecs_divi_get_preview_url' ) ) {
    /**
     * URL of the Visual Builder preview, without the settings.
     *
     * @since 4.1.0
     *
     * @return string
     */
    function tecs_divi_get_preview_url() {
        return add_query_arg(
            [
                'tecs_divi_preview' => '1',
                '_wpnonce' => wp_create_nonce( 'tecs_divi_preview' ),
            ],
            home_url( '/' )
        );
    }
}

if ( ! function_exists( 'tecs_divi_render_preview' ) ) {
    /**
     * Outputs the Visual Builder preview of a module as a standalone page.
     *
     * The builders show it in an iframe, so designs that need their scripts (calendar,
     * map, slider, filter bar, pagination) work as they do on the front end.
     *
     * @since 4.1.0
     *
     * @return void
     */
    function tecs_divi_render_preview() {
        if ( empty( $_GET['tecs_divi_preview'] ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_posts' ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'tecs_divi_preview' ) ) {
            wp_die( esc_html__( 'Sorry, you are not allowed to preview this module.', 'the-events-calendar-shortcode' ), '', [ 'response' => 403 ] );
        }

        // A shortcode typed in a Code module (see tecs_divi_preview_shortcode()), or the settings of the module.
        $shortcode = isset( $_GET['shortcode'] ) ? wp_unslash( $_GET['shortcode'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- checked below and rendered by the shortcode

        if ( '' !== $shortcode && ! preg_match( '/^' . get_shortcode_regex( [ 'ecs-list-events' ] ) . '$/s', $shortcode ) ) {
            wp_die( esc_html__( 'Sorry, you are not allowed to preview this module.', 'the-events-calendar-shortcode' ), '', [ 'response' => 400 ] );
        }

        // Sanitized by the shortcode, which escapes each attribute.
        $settings = isset( $_GET['settings'] ) ? json_decode( wp_unslash( $_GET['settings'] ), true ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $settings = is_array( $settings ) ? $settings : [];

        add_filter( 'show_admin_bar', '__return_false' );

        // Render first so the scripts and styles the shortcode enqueues print in the page.
        $events = '' !== $shortcode ? do_shortcode( $shortcode ) : tecs_divi_render( $settings );

        nocache_headers();
        ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <?php wp_head(); ?>
    <style>html, body { margin: 0 !important; padding: 0 !important; background: transparent !important; min-height: 0 !important; } .tecs-divi-preview { display: flow-root; }</style>
</head>
<body <?php body_class( 'tecs-divi-preview-page' ); ?>>
    <?php // entry-content applies the theme's post content styles, as on the front end. ?>
    <div class="tecs-divi-preview entry-content"><div class="ecs-divi-wrapper"><?php echo $events; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output ?></div></div>
    <?php wp_footer(); ?>
    <script>
    ( function () {
        var preview = document.querySelector( '.tecs-divi-preview' );
        var last = 0;
        function sendHeight() {
            var height = Math.ceil( preview.getBoundingClientRect().bottom + window.scrollY );
            if ( height !== last ) {
                last = height;
                window.parent.postMessage( { tecsDiviPreviewHeight: height }, window.location.origin );
            }
        }
        // Links would navigate inside the builder, so keep them inert like the block editor does.
        document.addEventListener( 'click', function ( event ) {
            var link = event.target.closest( 'a[href]' );
            if ( link && '#' !== link.getAttribute( 'href' ).charAt( 0 ) ) {
                event.preventDefault();
            }
        }, true );
        if ( window.ResizeObserver ) {
            new ResizeObserver( sendHeight ).observe( preview );
        }
        window.addEventListener( 'load', sendHeight );
        sendHeight();
    }() );
    </script>
</body>
</html>
        <?php
        exit;
    }
    add_action( 'template_redirect', 'tecs_divi_render_preview', 0 );
}

if ( ! function_exists( 'tecs_divi_preview_shortcode' ) ) {
    /**
     * Shows the events shortcode of a Code module in the Visual Builder as an iframe of the preview page.
     *
     * The Divi 5 builder renders shortcodes typed in a module (such as the Code module) through
     * Divi's preview page and copies only its HTML and styles into the builder, so the scripts
     * of the designs (calendar, map, slider, filter bar, pagination) would not run. Divi 4 runs
     * them, so its preview is unchanged.
     *
     * @since 4.1.0
     *
     * @param false|string $output Short-circuit output, false to render the shortcode.
     * @param string       $tag    Shortcode name.
     * @param array|string $attr   Shortcode attributes.
     * @param array        $m      Regular expression match of the shortcode.
     *
     * @return false|string
     */
    function tecs_divi_preview_shortcode( $output, $tag, $attr, $m ) {
        // Divi checks the preview nonce before it renders the shortcode.
        if ( 'ecs-list-events' !== $tag || empty( $_POST['is_fb_preview'] ) || ! function_exists( 'is_et_pb_preview' ) || ! is_et_pb_preview() || ! tecs_divi_is_divi5() ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return $output;
        }

        $url = add_query_arg( 'shortcode', rawurlencode( $m[0] ), tecs_divi_get_preview_url() );

        // The class lets divi-builder.js resize it to the events, as for the Divi module.
        return '<iframe src="' . esc_url( $url ) . '" title="' . esc_attr__( 'Events preview', 'the-events-calendar-shortcode' ) . '" class="tecs-divi-preview-frame" style="width:100%;height:150px;border:0;display:block;pointer-events:none;"></iframe>';
    }
    add_filter( 'pre_do_shortcode_tag', 'tecs_divi_preview_shortcode', 10, 4 );
}

if ( ! function_exists( 'tecs_divi_get_builder_data' ) ) {
    /**
     * Data for the Visual Builder script.
     *
     * @since 4.1.0
     *
     * @return array
     */
    function tecs_divi_get_builder_data() {
        $data = [
            'slug' => TECS_DIVI_MODULE_SLUG,
            'fields' => array_keys( tecs_divi_get_fields() ),
            'previewUrl' => tecs_divi_get_preview_url(),
            'i18n' => [
                'previewTitle' => __( 'Events preview', 'the-events-calendar-shortcode' ),
            ],
        ];

        if ( tecs_divi_is_divi5() ) {
            include_once __DIR__ . '/divi5-module.php';

            $data['iconUrl'] = plugins_url( 'includes/divi/static/icon.svg', TECS_CORE_PLUGIN_FILE );
            $data['fieldDefinitions'] = tecs_divi_get_fields();
            $data['metadata'] = TECS_Divi5_Module::get_metadata();
            $data['conversionOutline'] = TECS_Divi5_Module::get_conversion_outline();
        }

        return $data;
    }
}

if ( ! function_exists( 'tecs_divi_enqueue_builder_script' ) ) {
    /**
     * Loads the Visual Builder script of the Divi 4 or Divi 5 module.
     *
     * @since 4.1.0
     *
     * @return void
     */
    function tecs_divi_enqueue_builder_script() {
        if ( ! function_exists( 'et_core_is_fb_enabled' ) || ! et_core_is_fb_enabled() ) {
            return;
        }

        $is_divi5 = tecs_divi_is_divi5();

        // Divi 5 loads it with its own builder assets, in the builder iframe (app window) only:
        // its module library, which this script needs, does not load in the top window.
        if ( $is_divi5 !== ( 'divi_visual_builder_assets_before_enqueue_app_window_scripts' === current_action() ) ) {
            return;
        }

        wp_enqueue_script(
            'tecs-divi-builder',
            plugins_url( 'includes/divi/static/divi-builder.js', TECS_CORE_PLUGIN_FILE ),
            $is_divi5 ? [ 'divi-module-library', 'divi-vendor-wp-hooks' ] : [ 'react', 'jquery' ],
            Events_Calendar_Shortcode::VERSION,
            true
        );
        wp_localize_script( 'tecs-divi-builder', 'tecsDiviBuilder', tecs_divi_get_builder_data() );
    }
    add_action( 'wp_enqueue_scripts', 'tecs_divi_enqueue_builder_script' );
    add_action( 'divi_visual_builder_assets_before_enqueue_app_window_scripts', 'tecs_divi_enqueue_builder_script' );
}

if ( ! function_exists( 'tecs_divi_load_divi4_module' ) ) {
    /**
     * Registers the Divi 4 module.
     *
     * @since 4.1.0
     *
     * @return void
     */
    function tecs_divi_load_divi4_module() {
        if ( class_exists( 'ET_Builder_Module' ) && ! class_exists( 'TECS_Divi_Module' ) ) {
            include_once __DIR__ . '/divi4-module.php';
            new TECS_Divi_Module();
        }
    }
    add_action( 'et_builder_ready', 'tecs_divi_load_divi4_module' );
}

if ( ! function_exists( 'tecs_divi_load_divi5_module' ) ) {
    /**
     * Registers the Divi 5 module.
     *
     * @since 4.1.0
     *
     * @param object $dependency_tree Divi 5 module dependency tree.
     *
     * @return void
     */
    function tecs_divi_load_divi5_module( $dependency_tree ) {
        include_once __DIR__ . '/divi5-module.php';

        $dependency_tree->add_dependency( new TECS_Divi5_Module() );
    }
    add_action( 'divi_module_library_modules_dependency_tree', 'tecs_divi_load_divi5_module' );
}
