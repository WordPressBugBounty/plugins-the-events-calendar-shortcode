<?php
/**
 * WordPress Abilities API (WordPress 6.9+) integration.
 *
 * Lets AI agents and other clients (REST API, MCP Adapter) discover the
 * [ecs-list-events] options and build or preview a shortcode. Both abilities
 * read the shared builder option registry (includes/builder-options.php), so
 * they list the same options, choices and defaults as the page builders.
 *
 * @since 4.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'tecs_abilities_register_category' ) ) {
    /**
     * Registers the ability category.
     *
     * @since 4.1.0
     *
     * @return void
     */
    function tecs_abilities_register_category() {
        wp_register_ability_category( 'events-calendar-shortcode', [
            'label' => __( 'The Events Calendar Shortcode & Block', 'the-events-calendar-shortcode' ),
            'description' => __( 'List events from The Events Calendar with the [ecs-list-events] shortcode, block or page builder elements.', 'the-events-calendar-shortcode' ),
        ] );
    }
    add_action( 'wp_abilities_api_categories_init', 'tecs_abilities_register_category' );
}

if ( ! function_exists( 'tecs_abilities_options' ) ) {
    /**
     * Registry options that hold a value, without notices.
     *
     * @since 4.1.0
     *
     * @return array Options keyed by option key.
     */
    function tecs_abilities_options() {
        return array_filter( ecs_builder_options(), function ( $option ) {
            return 'notice' !== $option['type'];
        } );
    }
}

if ( ! function_exists( 'tecs_abilities_option_choices' ) ) {
    /**
     * Choices of an option.
     *
     * @since 4.1.0
     *
     * @param array $option Option definition.
     *
     * @return array Choice labels keyed by value.
     */
    function tecs_abilities_option_choices( $option ) {
        return ecs_builder_option_choices( $option, 'abilities' );
    }
}

if ( ! function_exists( 'tecs_abilities_option_schema' ) ) {
    /**
     * JSON Schema of the value of a registry option.
     *
     * @since 4.1.0
     *
     * @param array $option Option definition.
     *
     * @return array
     */
    function tecs_abilities_option_schema( $option ) {
        $description = trim( ( isset( $option['label'] ) ? $option['label'] : '' ) . '. ' . ( isset( $option['description'] ) ? $option['description'] : '' ), '. ' );
        $schema = [ 'description' => $description ];

        switch ( $option['type'] ) {
            case 'toggle':
                $schema['type'] = 'boolean';
                break;

            case 'number':
                $schema['type'] = 'number';
                break;

            case 'select':
                $schema['type'] = 'string';

                if ( empty( $option['choices_cb'] ) ) {
                    $choices = tecs_abilities_option_choices( $option );

                    // PHP turns choice keys like '2' into integers, so cast them back.
                    $schema['enum'] = array_map( 'strval', array_keys( $choices ) );
                    $schema['description'] .= '. ' . sprintf( __( 'One of: %s', 'the-events-calendar-shortcode' ), implode( ', ', array_map( function ( $value, $label ) {
                        return '"' . $value . '" (' . $label . ')';
                    }, array_keys( $choices ), $choices ) ) );
                }
                break;

            case 'multiselect':
                $schema['type'] = 'array';
                $schema['items'] = [ 'type' => 'string' ];
                break;

            case 'repeater':
                $properties = [];

                foreach ( $option['fields'] as $field_key => $field ) {
                    $properties[ $field_key ] = tecs_abilities_option_schema( $field );
                }

                $schema['type'] = 'array';
                $schema['items'] = [
                    'type' => 'object',
                    'properties' => $properties,
                    'additionalProperties' => false,
                ];
                break;

            default:
                $schema['type'] = 'string';
        }

        return $schema;
    }
}

if ( ! function_exists( 'tecs_abilities_integrations' ) ) {
    /**
     * Ways to place the event list on a page, and whether each is available on this site.
     *
     * @since 4.1.0
     *
     * @return array
     */
    function tecs_abilities_integrations() {
        $block_registered = class_exists( 'WP_Block_Type_Registry' ) && WP_Block_Type_Registry::get_instance()->is_registered( 'events-calendar-shortcode/block' );

        return [
            [
                'name' => 'shortcode',
                'available' => true,
                'usage' => __( 'Put the shortcode from build-shortcode in post content, a Shortcode block (core/shortcode), a text widget or any page builder shortcode element.', 'the-events-calendar-shortcode' ),
            ],
            [
                'name' => 'block',
                'id' => 'events-calendar-shortcode/block',
                'available' => $block_registered,
                'usage' => __( 'The Events Calendar Block in the block editor. Not registered when Divi or Cornerstone is active.', 'the-events-calendar-shortcode' ),
            ],
            [
                'name' => 'elementor',
                'id' => 'tecs_widget',
                'available' => did_action( 'elementor/loaded' ) > 0,
                'usage' => __( 'The Events Calendar Shortcode & Block widget in Elementor.', 'the-events-calendar-shortcode' ),
            ],
            [
                'name' => 'bricks',
                'id' => 'the-events-calendar-shortcode',
                'available' => class_exists( '\Bricks\Elements' ),
                'usage' => __( 'The Events Calendar Shortcode element in Bricks.', 'the-events-calendar-shortcode' ),
            ],
            [
                'name' => 'wpbakery',
                'id' => 'tecs_events',
                'available' => defined( 'WPB_VC_VERSION' ),
                'usage' => __( 'The Events Calendar Shortcode & Block element (under Content) in WPBakery Page Builder, saved as the [tecs_events] shortcode.', 'the-events-calendar-shortcode' ),
            ],
            [
                'name' => 'divi',
                'id' => 'tecs/events',
                'available' => function_exists( 'et_setup_theme' ) || defined( 'ET_BUILDER_PLUGIN_VERSION' ),
                'usage' => __( 'The Events Calendar Shortcode & Block module in the Divi Builder: tecs/events in Divi 5, the [tecs_divi_events] shortcode in Divi 4.', 'the-events-calendar-shortcode' ),
            ],
        ];
    }
}

if ( ! function_exists( 'tecs_abilities_get_options' ) ) {
    /**
     * Execute callback of the get-options ability.
     *
     * @since 4.1.0
     *
     * @return array
     */
    function tecs_abilities_get_options() {
        $options = [];

        foreach ( tecs_abilities_options() as $key => $option ) {
            $item = [
                'key' => $key,
                'label' => $option['label'],
                'section' => $option['section'],
                'type' => $option['type'],
                'default' => ecs_builder_option_default( $option, 'abilities' ),
            ];

            if ( in_array( $option['type'], [ 'select', 'multiselect' ], true ) ) {
                $item['choices'] = tecs_abilities_option_choices( $option );
            }

            if ( 'repeater' === $option['type'] ) {
                $item['fields'] = [];

                foreach ( $option['fields'] as $field_key => $field ) {
                    $item['fields'][ $field_key ] = array_filter( [
                        'label' => $field['label'],
                        'type' => $field['type'],
                        'choices' => isset( $field['choices'] ) ? tecs_abilities_option_choices( $field ) : null,
                    ] );
                }
            }

            if ( ! empty( $option['description'] ) ) {
                $item['description'] = $option['description'];
            }

            if ( ! empty( $option['condition'] ) ) {
                $item['condition'] = $option['condition'];
            }

            if ( ! empty( $option['map']['att'] ) ) {
                $item['attribute'] = $option['map']['att'];
            }

            $options[] = $item;
        }

        return [
            'shortcode' => 'ecs-list-events',
            'the_events_calendar_active' => function_exists( 'tribe_get_events' ),
            'sections' => ecs_builder_option_sections(),
            'options' => $options,
            'integrations' => tecs_abilities_integrations(),
            'documentation' => 'https://eventcalendarnewsletter.com/events-calendar-shortcode-pro-options/',
        ];
    }
}

if ( ! function_exists( 'tecs_abilities_build_shortcode' ) ) {
    /**
     * Execute callback of the build-shortcode ability.
     *
     * Maps the values the same way the page builders do, then leaves out
     * attributes that only repeat a default.
     *
     * @since 4.1.0
     *
     * @param array $input Option values keyed by option key, plus render.
     *
     * @return array
     */
    function tecs_abilities_build_shortcode( $input = [] ) {
        $input = is_array( $input ) ? $input : [];
        $render = ! empty( $input['render'] );
        unset( $input['render'] );

        $atts = array_diff_assoc(
            ecs_builder_settings_to_atts( $input, [ 'skip' => [ 'advanced' ] ] ),
            ecs_builder_settings_to_atts( [], [ 'skip' => [ 'advanced' ] ] )
        );
        $atts = ecs_builder_apply_advanced_atts( $atts, isset( $input['advanced'] ) ? $input['advanced'] : [] );

        $shortcode = '[ecs-list-events' . implode( '', array_map( function ( $key, $value ) {
            return ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
        }, array_keys( $atts ), $atts ) ) . ']';

        $result = [
            'shortcode' => $shortcode,
            'attributes' => (object) $atts,
        ];

        if ( $render ) {
            $result['html'] = do_shortcode( $shortcode );
        }

        return $result;
    }
}

if ( ! function_exists( 'tecs_abilities_can_use' ) ) {
    /**
     * Permission callback: the same capability as the plugin's admin page.
     *
     * @since 4.1.0
     *
     * @return bool
     */
    function tecs_abilities_can_use() {
        return current_user_can( tecs_get_capability() );
    }
}

if ( ! function_exists( 'tecs_abilities_register' ) ) {
    /**
     * Registers the abilities.
     *
     * @since 4.1.0
     *
     * @return void
     */
    function tecs_abilities_register() {
        $meta = [
            'show_in_rest' => true,
            'mcp' => [ 'public' => true ],
            'annotations' => [
                'readonly' => true,
                'destructive' => false,
                'idempotent' => true,
            ],
        ];

        wp_register_ability( 'the-events-calendar-shortcode/get-options', [
            'label' => __( 'Get event list options', 'the-events-calendar-shortcode' ),
            'description' => __( 'Lists every option of the [ecs-list-events] event list on this site (designs, display, ordering, filters) with its choices, default and the condition that shows it, plus which blocks and page builder elements are available. Read-only. Call it before build-shortcode.', 'the-events-calendar-shortcode' ),
            'category' => 'events-calendar-shortcode',
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'shortcode' => [ 'type' => 'string' ],
                    'the_events_calendar_active' => [ 'type' => 'boolean' ],
                    'sections' => [ 'type' => 'object' ],
                    'options' => [ 'type' => 'array' ],
                    'integrations' => [ 'type' => 'array' ],
                    'documentation' => [ 'type' => 'string' ],
                ],
            ],
            'execute_callback' => 'tecs_abilities_get_options',
            'permission_callback' => 'tecs_abilities_can_use',
            'meta' => $meta,
        ] );

        $properties = [];

        foreach ( tecs_abilities_options() as $key => $option ) {
            $properties[ $key ] = tecs_abilities_option_schema( $option );
        }

        $properties['render'] = [
            'type' => 'boolean',
            'description' => __( 'Also return the HTML the shortcode outputs now, to check which events it shows.', 'the-events-calendar-shortcode' ),
            'default' => false,
        ];

        wp_register_ability( 'the-events-calendar-shortcode/build-shortcode', [
            'label' => __( 'Build event list shortcode', 'the-events-calendar-shortcode' ),
            'description' => __( 'Turns option values (keys from get-options; leave out any to use its default) into an [ecs-list-events] shortcode, mapped the same way as the block and page builder elements. Set render to true to also get the HTML it outputs. Does not change the site: put the shortcode in a page yourself.', 'the-events-calendar-shortcode' ),
            'category' => 'events-calendar-shortcode',
            'input_schema' => [
                'type' => 'object',
                'properties' => $properties,
                'additionalProperties' => false,
                'default' => [],
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'shortcode' => [ 'type' => 'string' ],
                    'attributes' => [ 'type' => 'object' ],
                    'html' => [ 'type' => 'string' ],
                ],
                'required' => [ 'shortcode', 'attributes' ],
            ],
            'execute_callback' => 'tecs_abilities_build_shortcode',
            'permission_callback' => 'tecs_abilities_can_use',
            'meta' => $meta,
        ] );
    }
    add_action( 'wp_abilities_api_init', 'tecs_abilities_register' );
}
