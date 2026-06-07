import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function DateKeySetting( { attributes, setAttributes } ) {
	const options = [
		{ label: __( 'End Date', 'the-events-calendar-shortcode' ), value: 'End Date' },
		{ label: __( 'Start Date', 'the-events-calendar-shortcode' ), value: 'Start Date' },
	];

	return (
		<SelectControl
			label={ __( 'Event date key', 'the-events-calendar-shortcode' ) }
			value={ attributes.key || 'End Date' }
			onChange={ ( value ) => setAttributes( { key: value } ) }
			options={ options }
			help={ __( 'Hide events based on start or end date', 'the-events-calendar-shortcode' ) }
		/>
	);
}
