import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function OrderSetting( { attributes, setAttributes } ) {
	const orderOptions = [
		{ label: __( 'Ascending', 'the-events-calendar-shortcode' ), value: 'ASC' },
		{ label: __( 'Descending', 'the-events-calendar-shortcode' ), value: 'DESC' },
	];

	return (
		<SelectControl
			label={ __( 'Order', 'the-events-calendar-shortcode' ) }
			value={ attributes.order }
			onChange={ ( value ) => setAttributes( { order: value } ) }
			options={ orderOptions }
		/>
	);
}
