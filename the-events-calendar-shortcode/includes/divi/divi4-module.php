<?php
/**
 * Divi 4 module.
 *
 * @since 4.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Divi 4 module that lists events with the [ecs-list-events] shortcode.
 *
 * @since 4.1.0
 */
class TECS_Divi_Module extends ET_Builder_Module {

    /**
     * Module slug, also the shortcode Divi 4 saves.
     *
     * @var string
     */
    public $slug = TECS_DIVI_MODULE_SLUG;

    /**
     * Visual Builder support. The preview is rendered by includes/divi/static/divi-builder.js.
     *
     * @var string
     */
    public $vb_support = 'on';

    /**
     * Path to the module icon. Declared because Divi does not, and PHP 8.2 deprecates
     * dynamic properties.
     *
     * @var string
     */
    public $icon_path;

    /**
     * Module credits shown in the builder.
     *
     * @var array
     */
    protected $module_credits = [
        'module_uri' => 'https://eventcalendarnewsletter.com/the-events-calendar-shortcode/',
        'author' => 'Event Calendar Newsletter',
        'author_uri' => 'https://eventcalendarnewsletter.com/',
    ];

    /**
     * Sets up the module name, icon and option groups.
     *
     * @since 4.1.0
     *
     * @return void
     */
    public function init() {
        $this->name = esc_html__( 'The Events Calendar Shortcode & Block', 'the-events-calendar-shortcode' );
        $this->plural = esc_html__( 'The Events Calendar Shortcode & Block', 'the-events-calendar-shortcode' );
        $this->icon_path = __DIR__ . '/static/icon.svg';
        $this->main_css_element = '%%order_class%%';

        $this->settings_modal_toggles = [
            'general' => [
                'toggles' => tecs_divi_get_sections(),
            ],
        ];

        // The designs bring their own typography.
        $this->advanced_fields = [
            'fonts' => false,
            'text' => false,
            'button' => false,
            'link_options' => false,
        ];
    }

    /**
     * Module fields, from the builder option registry.
     *
     * @since 4.1.0
     *
     * @return array
     */
    public function get_fields() {
        $fields = [];

        foreach ( tecs_divi_get_fields() as $name => $field ) {
            $divi_field = [
                'label' => $field['label'],
                'description' => $field['description'],
                'option_category' => 'basic_option',
                'toggle_slug' => $field['section'],
            ];

            switch ( $field['kind'] ) {
                case 'select':
                    $divi_field['type'] = 'select';
                    $divi_field['options'] = $field['options'];
                    break;

                case 'toggle':
                    $divi_field['type'] = 'yes_no_button';
                    $divi_field['options'] = [
                        'off' => et_builder_i18n( 'No' ),
                        'on' => et_builder_i18n( 'Yes' ),
                    ];
                    break;

                case 'multiselect':
                    if ( ! empty( $field['taxonomy'] ) ) {
                        $divi_field['type'] = 'categories';
                        $divi_field['taxonomy_name'] = $field['taxonomy'];
                        $divi_field['renderer_options'] = [
                            'use_terms' => true,
                            'term_name' => $field['taxonomy'],
                        ];
                    } else {
                        $divi_field['type'] = 'multiple_checkboxes';
                        $divi_field['options'] = $field['options'];
                    }
                    break;

                case 'list':
                    $divi_field['type'] = 'sortable_list';
                    $divi_field['checkbox'] = true;
                    $divi_field['right_actions'] = 'move';
                    // Divi 4 checks items with checked === 1 (Divi 5 uses '1').
                    $divi_field['default'] = wp_json_encode(
                        array_map(
                            function ( $item ) {
                                $item['checked'] = (int) $item['checked'];
                                return $item;
                            },
                            $field['default']
                        )
                    );
                    break;

                case 'textarea':
                    $divi_field['type'] = 'textarea';
                    break;

                case 'notice':
                    $divi_field['type'] = 'warning';
                    $divi_field['value'] = true;
                    $divi_field['display_if'] = true;
                    $divi_field['message'] = $field['description'];
                    $divi_field['description'] = '';
                    break;

                default:
                    $divi_field['type'] = 'text';
            }

            if ( ! isset( $divi_field['default'] ) && isset( $field['default'] ) && ! is_array( $field['default'] ) ) {
                $divi_field['default'] = $field['default'];
            }

            foreach ( $field['conditions'] as $rule ) {
                list( $condition_field, $op, $value ) = $rule;
                $divi_field[ in_array( $op, [ '==', 'in' ], true ) ? 'show_if' : 'show_if_not' ][ $condition_field ] = $value;
            }

            $fields[ $name ] = $divi_field;
        }

        return $fields;
    }

    /**
     * Renders the module on the front end.
     *
     * @since 4.1.0
     *
     * @param array  $attrs       Unprocessed attributes.
     * @param string $content     Module content.
     * @param string $render_slug Module slug.
     *
     * @return string
     */
    public function render( $attrs, $content, $render_slug ) {
        return '<div class="ecs-divi-wrapper">' . tecs_divi_render( $this->props ) . '</div>';
    }
}
