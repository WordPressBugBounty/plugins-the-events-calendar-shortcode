import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function OrderBySetting( { attributes, setAttributes } ) {
	const orderByOptions = [
		{ label: __( 'Start Date', 'the-events-calendar-shortcode' ), value: 'startdate' },
		{ label: __( 'End Date', 'the-events-calendar-shortcode' ), value: 'enddate' },
		{ label: __( 'Title', 'the-events-calendar-shortcode' ), value: 'title' },
	];

	return (
		<SelectControl
			label={ __( 'Order by', 'the-events-calendar-shortcode' ) }
			value={ attributes.orderby }
			onChange={ ( value ) => setAttributes( { orderby: value } ) }
			options={ orderByOptions }
		/>
	);
}
