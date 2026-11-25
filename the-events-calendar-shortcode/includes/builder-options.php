<?php
/**
 * Shared page-builder option registry.
 *
 * Defines every option exposed in the Elementor, Bricks and WPBakery
 * integrations once. Each builder adapter converts these definitions into its
 * native control format, then uses ecs_builder_settings_to_atts() to map the
 * saved (normalized) values back to [ecs-list-events] shortcode attributes.
 *
 * The pro plugin extends/reorders this list via the `ecs_builder_options`
 * filter (mirroring how the block editor uses `ecs_block_attributes`).
 *
 * Option schema:
 * - section (string)          Key from ecs_builder_option_sections().
 * - label (string)
 * - type (string)             select|toggle|number|text|multiselect|repeater|notice
 * - choices (array)           value => label (select/multiselect).
 * - choices_cb (callable)     Lazy choices (e.g. taxonomy terms), resolved at control build time.
 * - choices_filter (array)    builder => legacy filter name applied to the choices.
 * - taxonomy (string)         Taxonomy of term choices keyed by slug, for builders that store term IDs (Divi).
 * - default (mixed)           Canonical default: bool for toggle, string/number otherwise.
 * - default_filter (array)    builder => legacy filter name applied to the default.
 * - builder_defaults (array)  builder => default override (legacy control defaults).
 * - condition (array)         List of [ option_key, op, value ], ANDed. ops: ==, !=, in, !in.
 *                             Toggle fields compare against booleans.
 * - condition_filters (array) builder => legacy filter applied to the builder-converted condition.
 * - builder_keys (array)      builder => control name override (legacy control names).
 * - map (array|false)         false/absent: not mapped to a shortcode attribute directly.
 *                             [ 'att' => name ]                        scalar passthrough (skips ''/null)
 *                             [ 'att' => name, 'join' => ',' ]         arrays joined to a list
 *                             [ 'att' => name, 'on' => x, 'off' => y ] toggles (null off = omit)
 *                             [ 'cb' => callable ]                     custom: fn( $atts, $value, $values )
 * - min (int), placeholder (string), description (string)
 * - content (string)          HTML for notice types.
 * - fields (array)            Repeater sub-options (same schema, no section).
 * - title_field (string)      Repeater field used as the row title.
 * - free_notice (bool)        Upsell notice, only present when pro is not active.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ecs_builder_option_sections' ) ) {
    function ecs_builder_option_sections() {
        return apply_filters( 'ecs_builder_option_sections', [
            'design' => __( 'Design', 'the-events-calendar-shortcode' ),
            'display' => __( 'Display', 'the-events-calendar-shortcode' ),
            'contentorder' => __( 'Content Order', 'the-events-calendar-shortcode' ),
            'ordering' => __( 'Ordering & Pagination', 'the-events-calendar-shortcode' ),
            'filterbar' => __( 'Filter Bar', 'the-events-calendar-shortcode' ),
            'event_filters' => __( 'Event Filters', 'the-events-calendar-shortcode' ),
            'date_filters' => __( 'Date Filters', 'the-events-calendar-shortcode' ),
            'location' => __( 'Location', 'the-events-calendar-shortcode' ),
            'advanced' => __( 'Advanced / Other', 'the-events-calendar-shortcode' ),
        ] );
    }
}

if ( ! function_exists( 'ecs_builder_upgrade_link' ) ) {
    function ecs_builder_upgrade_link( $content ) {
        return 'https://eventcalendarnewsletter.com/the-events-calendar-shortcode/?utm_source=plugin&utm_medium=link&utm_campaign=elementor-block&utm_content=' . $content;
    }
}

if ( ! function_exists( 'ecs_builder_category_choices' ) ) {
    function ecs_builder_category_choices() {
        $choices = [];
        $terms = get_terms( [
            'taxonomy' => 'tribe_events_cat',
            'hide_empty' => false,
        ] );

        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $choices[ $term->slug ] = $term->name;
            }
        }

        return $choices;
    }
}

if ( ! function_exists( 'ecs_builder_option_choices' ) ) {
    /**
     * Resolve the (possibly lazy) choices for an option, applying any
     * builder-specific legacy filter.
     */
    function ecs_builder_option_choices( $option, $builder = '' ) {
        $choices = isset( $option['choices'] ) ? $option['choices'] : [];

        if ( ! empty( $option['choices_cb'] ) && is_callable( $option['choices_cb'] ) ) {
            $choices = call_user_func( $option['choices_cb'] );
        }

        if ( $builder && ! empty( $option['choices_filter'][ $builder ] ) ) {
            $choices = apply_filters( $option['choices_filter'][ $builder ], $choices );
        }

        return $choices;
    }
}

if ( ! function_exists( 'ecs_builder_option_default' ) ) {
    function ecs_builder_option_default( $option, $builder = '' ) {
        $default = isset( $option['default'] ) ? $option['default'] : null;

        if ( $builder && isset( $option['builder_defaults'][ $builder ] ) ) {
            $default = $option['builder_defaults'][ $builder ];
        }

        if ( $builder && ! empty( $option['default_filter'][ $builder ] ) ) {
            $default = apply_filters( $option['default_filter'][ $builder ], $default );
        }

        return $default;
    }
}

if ( ! function_exists( 'ecs_builder_option_control_name' ) ) {
    function ecs_builder_option_control_name( $key, $option, $builder ) {
        if ( ! empty( $option['builder_keys'][ $builder ] ) ) {
            return $option['builder_keys'][ $builder ];
        }

        return $key;
    }
}

if ( ! function_exists( 'ecs_builder_condition_met' ) ) {
    /**
     * Evaluate an option's declarative condition against normalized values.
     */
    function ecs_builder_condition_met( $option, $values ) {
        if ( empty( $option['condition'] ) ) {
            return true;
        }

        foreach ( $option['condition'] as $rule ) {
            list( $field, $op, $expected ) = $rule;
            $actual = array_key_exists( $field, $values ) ? $values[ $field ] : null;

            switch ( $op ) {
                case '==':
                    if ( $actual != $expected ) {
                        return false;
                    }
                    break;
                case '!=':
                    if ( $actual == $expected ) {
                        return false;
                    }
                    break;
                case 'in':
                    if ( ! in_array( $actual, (array) $expected ) ) {
                        return false;
                    }
                    break;
                case '!in':
                    if ( in_array( $actual, (array) $expected ) ) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }
}

if ( ! function_exists( 'ecs_builder_settings_to_atts' ) ) {
    /**
     * Map normalized builder values to [ecs-list-events] shortcode attributes.
     *
     * $values is keyed by canonical option key with canonical value types
     * (bool for toggles, array for multiselect/repeater, string otherwise).
     */
    function ecs_builder_settings_to_atts( $values, $args = [] ) {
        $options = ecs_builder_options();
        $skip = isset( $args['skip'] ) ? (array) $args['skip'] : [];
        $atts = [];

        foreach ( $options as $key => $option ) {
            if ( in_array( $key, $skip, true ) || empty( $option['map'] ) ) {
                continue;
            }

            // Options hidden by their condition don't emit attributes (same as the builder UIs).
            if ( ! ecs_builder_condition_met( $option, $values ) ) {
                continue;
            }

            $value = array_key_exists( $key, $values ) ? $values[ $key ] : ( isset( $option['default'] ) ? $option['default'] : null );
            $map = $option['map'];

            if ( ! empty( $map['cb'] ) ) {
                $atts = call_user_func( $map['cb'], $atts, $value, $values );
                continue;
            }

            if ( array_key_exists( 'on', $map ) ) {
                $mapped = $value ? $map['on'] : ( array_key_exists( 'off', $map ) ? $map['off'] : null );

                if ( null !== $mapped ) {
                    $atts[ $map['att'] ] = $mapped;
                }
                continue;
            }

            if ( is_array( $value ) ) {
                $value = implode( isset( $map['join'] ) ? $map['join'] : ',', array_filter( array_map( 'strval', $value ), 'strlen' ) );
            }

            if ( null === $value || '' === $value ) {
                continue;
            }

            $atts[ $map['att'] ] = (string) $value;
        }

        return $atts;
    }
}

if ( ! function_exists( 'ecs_builder_apply_advanced_atts' ) ) {
    /**
     * Apply the Advanced/Other key-value rows last so they override everything.
     */
    function ecs_builder_apply_advanced_atts( $atts, $rows ) {
        if ( ! is_array( $rows ) ) {
            return $atts;
        }

        foreach ( $rows as $row ) {
            if ( empty( $row['key'] ) ) {
                continue;
            }

            $atts[ $row['key'] ] = isset( $row['value'] ) ? $row['value'] : '';
        }

        return $atts;
    }
}

if ( ! function_exists( 'ecs_builder_map_limit' ) ) {
    function ecs_builder_map_limit( $atts, $value, $values ) {
        $atts['limit'] = intval( is_numeric( $value ) ? $value : 5 );

        return $atts;
    }
}

if ( ! function_exists( 'ecs_builder_map_thumbnail' ) ) {
    function ecs_builder_map_thumbnail( $atts, $value, $values ) {
        $atts['thumb'] = $value ? 'true' : 'false';

        if ( ! $value ) {
            return $atts;
        }

        $choice = isset( $values['thumbnail_dimensions_choice'] ) ? $values['thumbnail_dimensions_choice'] : 'default';

        if ( 'width_and_height' === $choice ) {
            $atts['thumbwidth'] = intval( isset( $values['thumbnail_width'] ) && is_numeric( $values['thumbnail_width'] ) ? $values['thumbnail_width'] : 150 );
            $atts['thumbheight'] = intval( isset( $values['thumbnail_height'] ) && is_numeric( $values['thumbnail_height'] ) ? $values['thumbnail_height'] : 150 );
        } elseif ( 'size' === $choice && ! empty( $values['thumbnail_size'] ) ) {
            $atts['thumbsize'] = $values['thumbnail_size'];
        }

        return $atts;
    }
}

if ( ! function_exists( 'ecs_builder_map_excerpt' ) ) {
    function ecs_builder_map_excerpt( $atts, $value, $values ) {
        if ( $value ) {
            $atts['excerpt'] = (string) intval( isset( $values['excerpt_length'] ) && is_numeric( $values['excerpt_length'] ) ? $values['excerpt_length'] : 100 );
        } else {
            $atts['excerpt'] = 'false';
        }

        return $atts;
    }
}

if ( ! function_exists( 'ecs_builder_map_contentorder' ) ) {
    function ecs_builder_map_contentorder( $atts, $value, $values ) {
        $items = [];

        foreach ( (array) $value as $row ) {
            if ( ! empty( $row['enabled'] ) && ! empty( $row['item'] ) ) {
                $items[] = $row['item'];
            }
        }

        if ( ! empty( $items ) ) {
            $atts['contentorder'] = implode( ',', $items );
        }

        return $atts;
    }
}

if ( ! function_exists( 'ecs_builder_map_datekey' ) ) {
    function ecs_builder_map_datekey( $atts, $value, $values ) {
        if ( $value && 'End Date' !== $value ) {
            $atts['key'] = $value;
        }

        return $atts;
    }
}

if ( ! function_exists( 'ecs_builder_options' ) ) {
    function ecs_builder_options() {
        $is_pro = defined( 'TECS_VERSION' );

        $options = [];

        // Section: Design
        $options['design'] = [
            'section' => 'design',
            'label' => __( 'Design', 'the-events-calendar-shortcode' ),
            'type' => 'select',
            'choices' => [
                '' => __( 'Standard', 'the-events-calendar-shortcode' ),
            ],
            'choices_filter' => [
                'elementor' => 'ecs_elementor_designs',
                'bricks' => 'ecs_bricks_designs',
                'wpbakery' => 'ecs_wpbakery_designs',
                'divi' => 'ecs_divi_designs',
                'abilities' => 'ecs_abilities_designs',
            ],
            'default' => '',
            'default_filter' => [
                'elementor' => 'ecs_elementor_design_default',
                'bricks' => 'ecs_bricks_design_default',
                'wpbakery' => 'ecs_wpbakery_design_default',
                'divi' => 'ecs_divi_design_default',
                'abilities' => 'ecs_abilities_design_default',
            ],
            'builder_keys' => [
                'elementor' => 'design_choice',
            ],
            'map' => [ 'att' => 'design' ],
        ];

        if ( ! $is_pro ) {
            $options['design_notice_pro'] = [
                'section' => 'design',
                'label' => __( 'Want more designs?', 'the-events-calendar-shortcode' ),
                'type' => 'notice',
                'free_notice' => true,
                'builder_keys' => [
                    'bricks' => 'design_notice_pro',
                ],
                'content' => sprintf( esc_html__( '%sUpgrade to Pro%s for more designs (including a full calendar view), pagination and a filter bar!', 'the-events-calendar-shortcode' ), '<a target="_blank" href="' . ecs_builder_upgrade_link( 'page-builder' ) . '">', '</a>' ) . '<p><a target="_blank" href="https://demo.eventcalendarnewsletter.com/the-events-calendar-shortcode/?utm_source=plugin&utm_medium=link&utm_campaign=elementor-block&utm_content=page-builder">' . esc_html__( 'View the demo', 'the-events-calendar-shortcode' ) . '</a></p>',
            ];
        }

        // Section: Display
        $options['limit'] = [
            'section' => 'display',
            'label' => __( 'Number of Events', 'the-events-calendar-shortcode' ),
            'type' => 'number',
            'default' => 5,
            'min' => 1,
            'condition' => [ [ 'design', '!=', 'calendar' ] ],
            'condition_filters' => [
                'elementor' => 'ecs_elementor_limit_condition',
            ],
            'map' => [ 'cb' => 'ecs_builder_map_limit' ],
        ];

        $options['eventdetails'] = [
            'section' => 'display',
            'label' => __( 'Show Date', 'the-events-calendar-shortcode' ),
            'type' => 'toggle',
            'default' => true,
            'map' => [ 'att' => 'eventdetails', 'on' => 'true', 'off' => 'false' ],
        ];

        $options['thumb'] = [
            'section' => 'display',
            'label' => __( 'Show thumbnail image', 'the-events-calendar-shortcode' ),
            'type' => 'toggle',
            'default' => false,
            'builder_defaults' => [
                'bricks' => true,
            ],
            'condition' => [ [ 'design', '!=', 'grouped' ] ],
            'condition_filters' => [
                'elementor' => 'ecs_elementor_thumbnail_condition',
                'bricks' => 'ecs_bricks_thumbnail_condition',
            ],
            'builder_keys' => [
                'elementor' => 'thumbnail',
                'bricks' => 'thumbnail',
                'wpbakery' => 'thumbnail',
            ],
            'map' => [ 'cb' => 'ecs_builder_map_thumbnail' ],
        ];

        $options['thumbnail_dimensions_choice'] = [
            'section' => 'display',
            'label' => __( 'Thumbnail Size', 'the-events-calendar-shortcode' ),
            'type' => 'select',
            'choices' => [
                'default' => __( 'Default', 'the-events-calendar-shortcode' ),
                'width_and_height' => __( 'Width & Height', 'the-events-calendar-shortcode' ),
                'size' => __( 'Size', 'the-events-calendar-shortcode' ),
            ],
            'default' => 'default',
            'condition' => [ [ 'thumb', '==', true ], [ 'design', '!=', 'grouped' ] ],
        ];

        $options['thumbnail_width'] = [
            'section' => 'display',
            'label' => __( 'Thumbnail Width', 'the-events-calendar-shortcode' ),
            'type' => 'number',
            'default' => 150,
            'min' => 1,
            'condition' => [ [ 'thumb', '==', true ], [ 'thumbnail_dimensions_choice', '==', 'width_and_height' ], [ 'design', '!=', 'grouped' ] ],
            'condition_filters' => [
                'elementor' => 'ecs_elementor_thumbnail_width_and_height_conditions',
            ],
        ];

        $options['thumbnail_height'] = [
            'section' => 'display',
            'label' => __( 'Thumbnail Height', 'the-events-calendar-shortcode' ),
            'type' => 'number',
            'default' => 150,
            'min' => 1,
            'condition' => [ [ 'thumb', '==', true ], [ 'thumbnail_dimensions_choice', '==', 'width_and_height' ], [ 'design', '!=', 'grouped' ] ],
            'condition_filters' => [
                'elementor' => 'ecs_elementor_thumbnail_width_and_height_conditions',
            ],
        ];

        $options['thumbnail_size'] = [
            'section' => 'display',
            'label' => __( 'Thumbnail Size', 'the-events-calendar-shortcode' ),
            'type' => 'text',
            'default' => '',
            'condition' => [ [ 'thumb', '==', true ], [ 'thumbnail_dimensions_choice', '==', 'size' ], [ 'design', '!=', 'grouped' ] ],
            'condition_filters' => [
                'elementor' => 'ecs_elementor_thumbnail_size_conditions',
            ],
            'description' => __( 'A registered image size name (eg. medium, large).', 'the-events-calendar-shortcode' ),
        ];

        $options['venue'] = [
            'section' => 'display',
            'label' => __( 'Show venue information', 'the-events-calendar-shortcode' ),
            'type' => 'toggle',
            'default' => false,
            'builder_defaults' => [
                'bricks' => true,
            ],
            'map' => [ 'att' => 'venue', 'on' => 'true', 'off' => 'false' ],
        ];

        $options['excerpt'] = [
            'section' => 'display',
            'label' => __( 'Show excerpt of events', 'the-events-calendar-shortcode' ),
            'type' => 'toggle',
            'default' => false,
            'builder_defaults' => [
                'bricks' => true,
            ],
            'map' => [ 'cb' => 'ecs_builder_map_excerpt' ],
        ];

        $options['excerpt_length'] = [
            'section' => 'display',
            'label' => __( 'Excerpt Length', 'the-events-calendar-shortcode' ),
            'type' => 'number',
            'default' => 100,
            'min' => 1,
            'condition' => [ [ 'excerpt', '==', true ] ],
        ];

        if ( ! $is_pro ) {
            $options['excerpt_notice_pro'] = [
                'section' => 'display',
                'label' => __( 'Want more control over the excerpt?', 'the-events-calendar-shortcode' ),
                'type' => 'notice',
                'free_notice' => true,
                'builder_keys' => [
                    'elementor' => 'excerpt_pro',
                ],
                'condition' => [ [ 'excerpt', '==', true ] ],
                'content' => sprintf( esc_html__( '%sUpgrade to Pro%s to show the full description, HTML in your excerpts, and more!', 'the-events-calendar-shortcode' ), '<a target="_blank" href="' . ecs_builder_upgrade_link( 'page-builder' ) . '">', '</a>' ),
            ];
        }

        // Section: Content Order
        $options['contentorder'] = [
            'section' => 'contentorder',
            'label' => __( 'Content Order', 'the-events-calendar-shortcode' ),
            'type' => 'repeater',
            'default' => [],
            'title_field' => 'item',
            'description' => __( 'Customize which parts of each event show and in what order. Leave empty to use the design default.', 'the-events-calendar-shortcode' ),
            'fields' => [
                'item' => [
                    'label' => __( 'Item', 'the-events-calendar-shortcode' ),
                    'type' => 'select',
                    'choices' => apply_filters( 'ecs_builder_contentorder_choices', [
                        'title' => __( 'Title', 'the-events-calendar-shortcode' ),
                        'thumbnail' => __( 'Thumbnail', 'the-events-calendar-shortcode' ),
                        'date' => __( 'Date', 'the-events-calendar-shortcode' ),
                        'venue' => __( 'Venue', 'the-events-calendar-shortcode' ),
                        'excerpt' => __( 'Excerpt', 'the-events-calendar-shortcode' ),
                    ] ),
                    'default' => 'title',
                ],
                'enabled' => [
                    'label' => __( 'Enabled', 'the-events-calendar-shortcode' ),
                    'type' => 'toggle',
                    'default' => true,
                ],
            ],
            'map' => [ 'cb' => 'ecs_builder_map_contentorder' ],
        ];

        // Section: Ordering & Pagination
        $options['orderby'] = [
            'section' => 'ordering',
            'label' => __( 'Order By', 'the-events-calendar-shortcode' ),
            'type' => 'select',
            'choices' => [
                'startdate' => __( 'Start Date', 'the-events-calendar-shortcode' ),
                'enddate' => __( 'End Date', 'the-events-calendar-shortcode' ),
                'title' => __( 'Title', 'the-events-calendar-shortcode' ),
            ],
            'default' => 'startdate',
            'map' => [ 'att' => 'orderby' ],
        ];

        $options['order'] = [
            'section' => 'ordering',
            'label' => __( 'Order', 'the-events-calendar-shortcode' ),
            'type' => 'select',
            'choices' => [
                'ASC' => __( 'Ascending', 'the-events-calendar-shortcode' ),
                'DESC' => __( 'Descending', 'the-events-calendar-shortcode' ),
            ],
            'default' => 'ASC',
            'map' => [ 'att' => 'order' ],
        ];

        $options['viewall'] = [
            'section' => 'ordering',
            'label' => __( 'Show View All Events link', 'the-events-calendar-shortcode' ),
            'type' => 'toggle',
            'default' => false,
            'map' => [ 'att' => 'viewall', 'on' => 'true', 'off' => 'false' ],
        ];

        // Section: Event Filters
        $options['cat'] = [
            'section' => 'event_filters',
            'label' => __( 'Category', 'the-events-calendar-shortcode' ),
            'type' => 'multiselect',
            'choices_cb' => 'ecs_builder_category_choices',
            'choices_filter' => [
                'bricks' => 'ecs_bricks_categories',
            ],
            'taxonomy' => 'tribe_events_cat',
            'default' => [],
            'map' => [ 'att' => 'cat', 'join' => ',' ],
        ];

        // Section: Date Filters
        $options['month'] = [
            'section' => 'date_filters',
            'label' => __( 'Month', 'the-events-calendar-shortcode' ),
            'type' => 'text',
            'default' => '',
            'placeholder' => 'YYYY-MM',
            'description' => __( 'Show events from a specific month: "current" or a month like 2026-09.', 'the-events-calendar-shortcode' ),
            'map' => [ 'att' => 'month' ],
        ];

        $options['past'] = [
            'section' => 'date_filters',
            'label' => __( 'Show only past events?', 'the-events-calendar-shortcode' ),
            'type' => 'toggle',
            'default' => false,
            'map' => [ 'att' => 'past', 'on' => 'yes', 'off' => null ],
        ];

        if ( ! $is_pro ) {
            $options['past_notice_pro'] = [
                'section' => 'date_filters',
                'label' => __( 'Specify a date range or number of days', 'the-events-calendar-shortcode' ),
                'type' => 'notice',
                'free_notice' => true,
                'builder_keys' => [
                    'elementor' => 'past_pro',
                ],
                'condition' => [ [ 'past', '==', true ] ],
                'content' => sprintf( esc_html__( '%sGet more date-related options%s to show events from a specific year, number of days, and even a specific date range.', 'the-events-calendar-shortcode' ), '<a target="_blank" href="' . ecs_builder_upgrade_link( 'page-builder' ) . '">', '</a>' ),
            ];
        }

        $options['datekey'] = [
            'section' => 'date_filters',
            'label' => __( 'Date Key', 'the-events-calendar-shortcode' ),
            'type' => 'select',
            'choices' => [
                'End Date' => __( 'End Date', 'the-events-calendar-shortcode' ),
                'Start Date' => __( 'Start Date', 'the-events-calendar-shortcode' ),
            ],
            'default' => 'End Date',
            'description' => __( 'Whether to include events until the end date has passed, or only until the start date.', 'the-events-calendar-shortcode' ),
            'map' => [ 'cb' => 'ecs_builder_map_datekey' ],
        ];

        // Section: Advanced / Other
        $options['message'] = [
            'section' => 'advanced',
            'label' => __( 'No Events Message', 'the-events-calendar-shortcode' ),
            'type' => 'text',
            'default' => '',
            'placeholder' => __( 'There are no upcoming events.', 'the-events-calendar-shortcode' ),
            'map' => [ 'att' => 'message' ],
        ];

        $options['advanced'] = [
            'section' => 'advanced',
            'label' => __( 'Advanced/Other', 'the-events-calendar-shortcode' ),
            'type' => 'repeater',
            'default' => [],
            'title_field' => 'key',
            'fields' => [
                'key' => [
                    'label' => __( 'Key', 'the-events-calendar-shortcode' ),
                    'type' => 'text',
                    'default' => '',
                ],
                'value' => [
                    'label' => __( 'Value', 'the-events-calendar-shortcode' ),
                    'type' => 'text',
                    'default' => '',
                ],
            ],
        ];

        $options['advanced_description'] = [
            'section' => 'advanced',
            'label' => '',
            'type' => 'notice',
            'content' => sprintf( esc_html__( '%sView documentation on available options%s where key="value" in the shortcode can be entered in the boxes above.', 'the-events-calendar-shortcode' ), '<a target="_blank" href="https://eventcalendarnewsletter.com/events-calendar-shortcode-pro-options/?utm_source=plugin&utm_medium=link&utm_campaign=block-advanced-help&utm_content=page-builder' . ( ! $is_pro ? '&free=1' : '' ) . '">', '</a>' ),
        ];

        return apply_filters( 'ecs_builder_options', $options );
    }
}
