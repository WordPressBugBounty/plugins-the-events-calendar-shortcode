<?php
/**
 * Divi 5 module.
 *
 * Registered as a native Divi 5 module, so it is edited with the Divi 5 settings
 * panel instead of running through the Divi 4 compatibility layer. Each Divi field
 * (see tecs_divi_get_fields()) is saved as its own attribute, such as
 * design.innerContent.desktop.value, using the Divi 4 field name, so Divi 4 layouts
 * convert to it.
 *
 * @since 4.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Module;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementScriptData;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

if ( class_exists( 'TECS_Divi5_Module' ) ) {
    return;
}

/**
 * Divi 5 module that lists events with the [ecs-list-events] shortcode.
 *
 * @since 4.1.0
 */
class TECS_Divi5_Module implements DependencyInterface {

    /**
     * Module (block) name.
     *
     * @var string
     */
    const NAME = 'tecs/events';

    /**
     * Registers the module and its Divi 4 conversion.
     *
     * @since 4.1.0
     *
     * @return void
     */
    public function load() {
        add_action( 'init', [ __CLASS__, 'register' ] );
        add_filter( 'divi.moduleLibrary.conversion.moduleConversionOutline', [ __CLASS__, 'filter_conversion_outline' ], 10, 2 );
    }

    /**
     * Registers the module from divi5/module.json.
     *
     * @since 4.1.0
     *
     * @return void
     */
    public static function register() {
        ModuleRegistration::register_module(
            __DIR__ . '/divi5/',
            [
                'render_callback' => [ __CLASS__, 'render_callback' ],
            ]
        );

        // Divi only reads conversion outlines from a file in the admin, where its migration runs.
        if ( is_admin() ) {
            ModuleRegistration::process_conversion_outline( [ 'name' => self::NAME ] );
        }
    }

    /**
     * Module metadata for the Visual Builder: divi5/module.json plus a settings group
     * per section and an attribute per Divi field.
     *
     * @since 4.1.0
     *
     * @return array
     */
    public static function get_metadata() {
        $metadata = wp_json_file_decode( __DIR__ . '/divi5/module.json', [ 'associative' => true ] );
        $metadata['title'] = __( 'The Events Calendar Shortcode & Block', 'the-events-calendar-shortcode' );
        $metadata['titles'] = $metadata['title'];
        $priority = 10;

        foreach ( tecs_divi_get_sections() as $section => $label ) {
            $metadata['settings']['groups'][ 'tecs_' . $section ] = [
                'panel' => 'content',
                'priority' => $priority,
                'groupName' => 'tecs_' . $section,
                'multiElements' => true,
                'component' => [
                    'name' => 'divi/composite',
                    'props' => [
                        'groupLabel' => $label,
                    ],
                ],
            ];
            $priority += 10;
        }

        $priority = 10;

        foreach ( tecs_divi_get_fields() as $name => $field ) {
            $item = [
                'groupSlug' => 'tecs_' . $field['section'],
                'priority' => $priority,
                'render' => true,
                'attrName' => $name . '.innerContent',
                'label' => $field['label'],
                'description' => wp_strip_all_tags( $field['description'] ),
                'features' => [
                    'sticky' => false,
                    'responsive' => false,
                    'hover' => false,
                    'dynamicContent' => false,
                ],
                'component' => self::get_component( $field ),
            ];
            $priority += 10;

            $attribute = [
                'type' => 'object',
                'settings' => [
                    'innerContent' => [
                        'groupType' => 'group-item',
                        'item' => $item,
                    ],
                ],
            ];

            if ( isset( $field['default'] ) && 'notice' !== $field['kind'] ) {
                $attribute['default'] = [
                    'innerContent' => [
                        'desktop' => [
                            'value' => $field['default'],
                        ],
                    ],
                ];
            }

            $metadata['attributes'][ $name ] = $attribute;
        }

        return $metadata;
    }

    /**
     * Divi 5 field component of a Divi field.
     *
     * @since 4.1.0
     *
     * @param array $field Divi field.
     *
     * @return array
     */
    public static function get_component( $field ) {
        switch ( $field['kind'] ) {
            case 'select':
                $options = [];

                foreach ( $field['options'] as $value => $label ) {
                    $options[ $value ] = [ 'label' => $label ];
                }

                return [
                    'name' => 'divi/select',
                    'type' => 'field',
                    'props' => [ 'options' => (object) $options ],
                ];

            case 'toggle':
                return [ 'name' => 'divi/toggle', 'type' => 'field' ];

            case 'multiselect':
                $options = [];

                foreach ( $field['options'] as $value => $label ) {
                    $options[] = [
                        'value' => (string) $value,
                        'label' => $label,
                    ];
                }

                return [
                    'name' => 'divi/checkboxes',
                    'type' => 'field',
                    'props' => [ 'options' => $options ],
                ];

            case 'list':
                return [
                    'name' => 'divi/sortable-list',
                    'type' => 'field',
                    'props' => [
                        'isCheckbox' => true,
                        'useAddNewButton' => false,
                        'actions' => [ 'right' => [ 'move' ] ],
                    ],
                ];

            case 'textarea':
                return [ 'name' => 'divi/textarea', 'type' => 'field' ];

            case 'notice':
                return [
                    'name' => 'divi/warning',
                    'type' => 'field',
                    'props' => [ 'message' => $field['description'] ],
                ];

            default:
                return [ 'name' => 'divi/text', 'type' => 'field' ];
        }
    }

    /**
     * How Divi converts a Divi 4 module to this module.
     *
     * @since 4.1.0
     *
     * @return array
     */
    public static function get_conversion_outline() {
        $module = [];
        $value_expansion = [];

        foreach ( tecs_divi_get_fields() as $name => $field ) {
            if ( 'notice' === $field['kind'] ) {
                continue;
            }

            $module[ $name ] = $name . '.innerContent.*';

            if ( 'list' === $field['kind'] ) {
                $value_expansion[ $name ] = 'sortableListConverter';
            } elseif ( 'multiselect' === $field['kind'] ) {
                $value_expansion[ $name ] = 'tecsCommaListToArray';
            }
        }

        return [
            'advanced' => [
                'admin_label' => 'module.meta.adminLabel',
                'animation' => 'module.decoration.animation',
                'background' => 'module.decoration.background',
                'borders' => [ 'default' => 'module.decoration.border' ],
                'box_shadow' => [ 'default' => 'module.decoration.boxShadow' ],
                'disabled_on' => 'module.decoration.disabledOn',
                'filters' => [ 'default' => 'module.decoration.filters' ],
                'height' => 'module.decoration.sizing',
                'margin_padding' => 'module.decoration.spacing',
                'max_width' => 'module.decoration.sizing',
                'module' => 'module.advanced.htmlAttributes',
                'overflow' => 'module.decoration.overflow',
                'position_fields' => 'module.decoration.position',
                'scroll' => 'module.decoration.scroll',
                'sticky' => 'module.decoration.sticky',
                'transform' => 'module.decoration.transform',
                'transition' => 'module.decoration.transition',
                'z_index' => 'module.decoration.zIndex',
            ],
            'css' => [
                'before' => 'css.*.before',
                'main_element' => 'css.*.mainElement',
                'after' => 'css.*.after',
                'free_form' => 'css.*.freeForm',
            ],
            'module' => $module,
            'valueExpansionFunctionMap' => $value_expansion,
        ];
    }

    /**
     * Supplies the conversion outline, which Divi otherwise reads from a JSON file.
     *
     * @since 4.1.0
     *
     * @param array  $outline     Conversion outline.
     * @param string $module_name Module name.
     *
     * @return array
     */
    public static function filter_conversion_outline( $outline, $module_name ) {
        if ( self::NAME !== $module_name ) {
            return $outline;
        }

        add_filter( 'divi.moduleLibrary.conversion.valueExpansionFunctionMap', [ __CLASS__, 'filter_value_expansion_functions' ] );

        return self::get_conversion_outline();
    }

    /**
     * Adds the value conversion of multiple choice fields.
     *
     * @since 4.1.0
     *
     * @param array $functions Value expansion functions keyed by name.
     *
     * @return array
     */
    public static function filter_value_expansion_functions( $functions ) {
        $functions['tecsCommaListToArray'] = [ __CLASS__, 'comma_list_to_array' ];

        return $functions;
    }

    /**
     * Converts a Divi 4 comma separated value, such as categories, to a Divi 5 checkboxes value.
     *
     * @since 4.1.0
     *
     * @param mixed $value Divi 4 value.
     *
     * @return array
     */
    public static function comma_list_to_array( $value ) {
        return array_values( array_filter( array_map( 'trim', explode( ',', (string) $value ) ), 'strlen' ) );
    }

    /**
     * Module settings from saved block attributes, keyed by Divi field name.
     *
     * @since 4.1.0
     *
     * @param array $attrs Block attributes.
     *
     * @return array
     */
    public static function get_settings( $attrs ) {
        $settings = [];

        foreach ( $attrs as $name => $attr ) {
            if ( is_array( $attr ) && isset( $attr['innerContent']['desktop']['value'] ) ) {
                $settings[ $name ] = $attr['innerContent']['desktop']['value'];
            }
        }

        return $settings;
    }

    /**
     * Renders the module on the front end.
     *
     * @since 4.1.0
     *
     * @param array     $attrs    Block attributes saved by the Visual Builder.
     * @param string    $content  Rendered inner blocks.
     * @param \WP_Block $block    Parsed block.
     * @param object    $elements ModuleElements instance.
     *
     * @return string
     */
    public static function render_callback( $attrs, $content, $block, $elements ) {
        $parent = BlockParserStore::get_parent( $block->parsed_block['id'], $block->parsed_block['storeInstance'] );

        return Module::render(
            [
                'orderIndex' => $block->parsed_block['orderIndex'],
                'storeInstance' => $block->parsed_block['storeInstance'],
                'id' => $block->parsed_block['id'],
                'name' => $block->block_type->name,
                'moduleCategory' => $block->block_type->category,
                'attrs' => $attrs,
                'elements' => $elements,
                'stylesComponent' => [ __CLASS__, 'module_styles' ],
                'scriptDataComponent' => [ __CLASS__, 'module_script_data' ],
                'parentAttrs' => $parent->attrs ?? [],
                'parentId' => $parent->id ?? '',
                'parentName' => $parent->blockName ?? '',
                'children' => [
                    $elements->style_components( [ 'attrName' => 'module' ] ),
                    '<div class="ecs-divi-wrapper">' . tecs_divi_render( self::get_settings( $attrs ) ) . '</div>',
                ],
            ]
        );
    }

    /**
     * Adds the styles of the module's Design and Advanced options.
     *
     * @since 4.1.0
     *
     * @param array $args Style arguments from Module::render().
     *
     * @return void
     */
    public static function module_styles( $args ) {
        $elements = $args['elements'];
        $settings = $args['settings'] ?? [];
        $default_printed_style_attrs = $args['defaultPrintedStyleAttrs'] ?? [];

        Style::add(
            [
                'id' => $args['id'],
                'name' => $args['name'],
                'orderIndex' => $args['orderIndex'],
                'storeInstance' => $args['storeInstance'],
                'styles' => [
                    $elements->style(
                        [
                            'attrName' => 'module',
                            'styleProps' => [
                                'defaultPrintedStyleAttrs' => $default_printed_style_attrs['module']['decoration'] ?? [],
                                'disabledOn' => [
                                    'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
                                ],
                            ],
                        ]
                    ),
                    CssStyle::style(
                        [
                            'selector' => $args['orderClass'],
                            'attr' => $args['attrs']['css'] ?? [],
                            'cssFields' => \WP_Block_Type_Registry::get_instance()->get_registered( self::NAME )->customCssFields,
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * Sets the script data of the module's decoration options, such as animation and sticky.
     *
     * @since 4.1.0
     *
     * @param array $args Script data arguments from Module::render().
     *
     * @return void
     */
    public static function module_script_data( $args ) {
        ElementScriptData::set(
            [
                'id' => $args['id'] ?? '',
                'selector' => $args['selector'] ?? '',
                'attrs' => $args['attrs']['module']['decoration'] ?? [],
                'storeInstance' => $args['storeInstance'] ?? null,
            ]
        );
    }
}
