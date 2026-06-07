import { useMemo, useEffect, useRef, forwardRef } from '@wordpress/element';
import { Dashicon } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { getDesignDefault } from '../utils/designDefaults';
import {
	DndContext,
	closestCenter,
	PointerSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';
import {
	arrayMove,
	SortableContext,
	verticalListSortingStrategy,
	useSortable,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import config from '../config/contentOrder';

export default function ContentOrder( props ) {
	const { setAttributes, attributes } = props;
	const filteredConfig = applyFilters( 'ecs.contentOrderConfig', config );
	const designConfig = filteredConfig?.[ attributes.design ] ?? [];
	const { contentorder = designConfig } = attributes;

	// Keep only items that belong to the current design, preserving user's order
	const designValues = designConfig.map( ( item ) => item.value );
	let items = [ ...contentorder ].filter( ( item ) =>
		designValues.includes( item.value )
	);

	// Add back any design config items that are missing
	designConfig.forEach( ( configItem ) => {
		if ( ! items.find( ( item ) => item.value === configItem.value ) ) {
			items.push( { ...configItem } );
		}
	} );

	// Remove duplicates
	items = items.filter(
		( item, index, self ) =>
			index === self.findIndex( ( t ) => t.value === item.value )
	);

	// Mark items as inactive when their feature toggle is off (purely visual —
	// the shortcode already checks individual attributes like thumb/venue/excerpt)
	items = items.map( ( item ) => {
		if ( typeof item.conditional === 'undefined' ) {
			return { ...item, inactive: false };
		}

		const { attribute, comparison, value } = item.conditional;
		const attrValue = typeof attributes[ attribute ] !== 'undefined'
			? attributes[ attribute ]
			: ( getDesignDefault( attributes.design, attribute ) || 'false' );

		let conditionMet = true;
		if ( comparison === '===' ) conditionMet = attrValue === value;
		if ( comparison === '!==' ) conditionMet = attrValue !== value;

		return { ...item, inactive: ! conditionMet };
	} );

	const itemIds = useMemo( () => items.map( ( item ) => item.value ), [ items ] );

	const sensors = useSensors(
		useSensor( PointerSensor, {
			activationConstraint: {
				distance: 8,
			},
		} )
	);

	function handleDragEnd( event ) {
		const { active, over } = event;

		if ( active.id !== over.id ) {
			const oldIndex = itemIds.findIndex( ( item ) => item === active.id );
			const newIndex = itemIds.findIndex( ( item ) => item === over.id );
			const newAttributes = arrayMove( items, oldIndex, newIndex );

			setAttributes( { contentorder: newAttributes } );
		}
	}

	function handleCheckboxChange( event ) {
		const { checked, value } = event.target;

		const newAttributes = items.map( ( item ) => {
			if ( item.value === value ) {
				return { ...item, checked };
			}
			return item;
		} );

		setAttributes( { contentorder: newAttributes } );
	}

	// Reset contentorder when design changes
	const previousDesignRef = useRef( attributes.design );

	useEffect( () => {
		if ( attributes.design !== previousDesignRef.current ) {
			previousDesignRef.current = attributes.design;
			setAttributes( { contentorder: designConfig } );
		}
	}, [ attributes.design ] );

	return items.length > 0 ? (
		<DndContext
			sensors={ sensors }
			collisionDetection={ closestCenter }
			onDragEnd={ handleDragEnd }
		>
			<SortableContext
				items={ itemIds }
				strategy={ verticalListSortingStrategy }
			>
				{ items.map( ( item ) => (
					<SortableItem
						key={ item.value }
						item={ item }
						onCheckboxChange={ handleCheckboxChange }
					/>
				) ) }
			</SortableContext>
		</DndContext>
	) : (
		<p>{ __( 'n/a', 'the-events-calendar-shortcode' ) }</p>
	);
}

function SortableItem( props ) {
	const { attributes, listeners, setNodeRef, transform, transition } =
		useSortable( { id: props.item.value } );

	const style = {
		transform: CSS.Translate.toString( transform ),
		transition,
		cursor: 'move',
	};

	return (
		<Item
			data-item-id={ props.item.value }
			item={ props.item }
			ref={ setNodeRef }
			style={ style }
			onCheckboxChange={ props.onCheckboxChange }
			{ ...attributes }
			{ ...listeners }
			{ ...props }
		/>
	);
}

const Item = forwardRef( ( { item, onCheckboxChange, ...props }, ref ) => {
	let className = 'ecs-contentorder-item';
	if ( ! item.checked ) className += ' unchecked';
	if ( item.inactive ) className += ' inactive';

	return (
		<div { ...props } ref={ ref } className={ className }>
			<Dashicon icon="menu-alt2" />
			<span className="ecs-contentorder-item-inner">
				<input
					type="checkbox"
					onChange={ ( event ) => onCheckboxChange( event, item ) }
					checked={ item.checked }
					disabled={ item.inactive }
					value={ item.value }
				/>
				{ item.label }
			</span>
		</div>
	);
} );
