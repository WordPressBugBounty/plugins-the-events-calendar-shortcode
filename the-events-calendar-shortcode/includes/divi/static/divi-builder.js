/**
 * Visual Builder support for the Divi 4 and Divi 5 modules.
 *
 * Both builders show the module as an iframe of the server-rendered events (see
 * tecs_divi_render_preview()), so every design and its scripts work as they do on the
 * front end. The Divi 5 settings panel and Divi 4 conversion come from PHP
 * (TECS_Divi5_Module::get_metadata() and get_conversion_outline()).
 *
 * @since 4.1.0
 */
( function () {
	'use strict';

	var data = window.tecsDiviBuilder;

	if ( ! data ) {
		return;
	}

	function getReact() {
		return window.React || ( window.vendor && window.vendor.React );
	}

	// The preview page posts its height so the iframe fits the events without scrolling.
	window.addEventListener( 'message', function ( event ) {
		if ( ! event.data || ! event.data.tecsDiviPreviewHeight ) {
			return;
		}

		var frames = document.querySelectorAll( 'iframe.tecs-divi-preview-frame' );

		for ( var i = 0; i < frames.length; i++ ) {
			if ( frames[ i ].contentWindow === event.source ) {
				frames[ i ].style.height = event.data.tecsDiviPreviewHeight + 'px';
			}
		}
	} );

	function previewFrame( settings ) {
		var url = data.previewUrl + '&settings=' + encodeURIComponent( JSON.stringify( settings ) );

		return getReact().createElement( 'iframe', {
			key: url,
			src: url,
			title: data.i18n.previewTitle,
			className: 'tecs-divi-preview-frame',
			// Pointer events go to the module, so hovering it shows Divi's controls and clicking selects it.
			style: { width: '100%', height: '150px', border: 0, display: 'block', pointerEvents: 'none' },
		} );
	}

	/*
	 * Divi 4.
	 */
	if ( window.jQuery ) {
		window.jQuery( window ).on( 'et_builder_api_ready', function ( event, API ) {
			var React = getReact();

			// Divi 4 renders class components only (it reads render() from the prototype).
			function Divi4Module( props ) {
				React.Component.call( this, props );
			}

			Divi4Module.prototype = Object.create( React.Component.prototype );
			Divi4Module.prototype.constructor = Divi4Module;
			Divi4Module.prototype.render = function () {
				var props = this.props;
				var settings = {};

				data.fields.forEach( function ( name ) {
					if ( props[ name ] !== undefined ) {
						settings[ name ] = props[ name ];
					}
				} );

				return React.createElement( 'div', { className: 'ecs-divi-wrapper' }, previewFrame( settings ) );
			};

			Divi4Module.slug = data.slug;

			API.registerModules( [ Divi4Module ] );
		} );
	}

	/*
	 * Divi 5.
	 */
	var hooks = ( window.vendor && window.vendor.wp && window.vendor.wp.hooks ) || ( window.wp && window.wp.hooks );

	if ( ! hooks || ! data.metadata ) {
		return;
	}

	var fields = data.fieldDefinitions;

	function getValue( attrs, name ) {
		var attr = attrs && attrs[ name ];
		var value = attr && attr.innerContent && attr.innerContent.desktop ? attr.innerContent.desktop.value : undefined;

		return value === undefined || value === '' ? fields[ name ].default : value;
	}

	function matches( op, value, expected ) {
		switch ( op ) {
			case '==':
				return value === expected;
			case '!=':
				return value !== expected;
			case 'in':
				return expected.indexOf( value ) !== -1;
			case '!in':
				return expected.indexOf( value ) === -1;
		}

		return true;
	}

	// Mirrors ecs_builder_condition_met(), plus showing per-item fields (such as a filter's
	// label) only while that item is checked in its list.
	function isVisible( name, attrs ) {
		var field = fields[ name ];
		var visible = field.conditions.every( function ( rule ) {
			return matches( rule[ 1 ], getValue( attrs, rule[ 0 ] ), rule[ 2 ] );
		} );

		if ( visible && field.list ) {
			var list = fields[ field.list[ 0 ] ];
			var label = String( list.choices[ field.list[ 1 ] ] ).toLowerCase();
			var items = getValue( attrs, field.list[ 0 ] ) || [];

			visible = items.some( function ( item ) {
				var value = String( item.value || '' ).trim().toLowerCase();

				return ( value === label || value === field.list[ 1 ] ) && item.checked && '0' !== String( item.checked );
			} );
		}

		return visible;
	}

	hooks.addFilter( 'divi.iconLibrary.icon.map', 'tecs.events', function ( icons ) {
		var result = Object.assign( {}, icons );

		result[ data.metadata.moduleIcon ] = {
			name: data.metadata.moduleIcon,
			// Divi draws module icons in a 16 x 16 box. The logo fills 4 to 24 of the 28 x 28
			// static/icon.svg (padded for the Divi 4 picker), so the image is scaled by 0.8 and
			// shifted to fill the box.
			viewBox: '0 0 16 16',
			component: function () {
				return getReact().createElement( 'image', {
					href: data.iconUrl,
					x: -3.2,
					y: -3.2,
					width: 22.4,
					height: 22.4,
				} );
			},
		};

		return result;
	} );

	hooks.addAction( 'divi.moduleLibrary.registerModuleLibraryStore.after', 'tecs.events', function () {
		var React = getReact();
		var diviModule = window.divi.module;
		var metadata = data.metadata;

		Object.keys( fields ).forEach( function ( name ) {
			var item = metadata.attributes[ name ].settings.innerContent.item;

			if ( fields[ name ].conditions.length || fields[ name ].list ) {
				item.visible = function ( params ) {
					return isVisible( name, params.attrs );
				};
			}
		} );

		function ModuleStyles( props ) {
			return React.createElement(
				diviModule.StyleContainer,
				{ mode: props.mode, state: props.state, noStyleTag: props.noStyleTag },
				props.elements.style( {
					attrName: 'module',
					styleProps: {
						disabledOn: {
							disabledModuleVisibility: props.settings ? props.settings.disabledModuleVisibility : undefined,
						},
					},
				} ),
				React.createElement( diviModule.CssStyle, {
					selector: props.orderClass,
					attr: props.attrs.css,
					cssFields: metadata.customCssFields,
				} )
			);
		}

		function ModuleEdit( props ) {
			var settings = {};

			Object.keys( fields ).forEach( function ( name ) {
				var attr = props.attrs && props.attrs[ name ];

				if ( attr && attr.innerContent && attr.innerContent.desktop && attr.innerContent.desktop.value !== undefined ) {
					settings[ name ] = attr.innerContent.desktop.value;
				}
			} );

			return React.createElement(
				diviModule.ModuleContainer,
				{
					attrs: props.attrs,
					elements: props.elements,
					id: props.id,
					name: props.name,
					stylesComponent: ModuleStyles,
				},
				props.elements.styleComponents( { attrName: 'module' } ),
				React.createElement( 'div', { className: 'ecs-divi-wrapper' }, previewFrame( settings ) )
			);
		}

		// Divi 5's sortable list shows only saved items, so new modules start with the default
		// items (such as the filter bar's filters) already in their lists.
		var placeholderContent = {};

		Object.keys( fields ).forEach( function ( name ) {
			if ( 'list' === fields[ name ].kind ) {
				placeholderContent[ name ] = { innerContent: { desktop: { value: fields[ name ].default } } };
			}
		} );

		window.divi.moduleLibrary.registerModule( metadata, {
			placeholderContent: placeholderContent,
			conversionOutline: data.conversionOutline,
			renderers: {
				edit: ModuleEdit,
			},
		} );
	} );
}() );
