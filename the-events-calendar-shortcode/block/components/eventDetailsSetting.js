import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function EventDetailsSetting( { attributes, setAttributes } ) {
	const enabled = typeof attributes.eventdetails === 'undefined' || attributes.eventdetails !== 'false';

	return (
		<ToggleControl
			label={ __( 'Show date', 'the-events-calendar-shortcode' ) }
			checked={ enabled }
			onChange={ ( checked ) => setAttributes( { eventdetails: checked ? 'true' : 'false' } ) }
		/>
	);
}
