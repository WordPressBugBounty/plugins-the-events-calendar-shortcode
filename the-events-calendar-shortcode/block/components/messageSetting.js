import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function MessageSetting( { attributes, setAttributes } ) {
	return (
		<TextControl
			label={ __( 'No events message', 'the-events-calendar-shortcode' ) }
			value={ attributes.message || '' }
			onChange={ ( value ) => setAttributes( { message: value } ) }
			placeholder={ __( 'There are no upcoming events at this time.', 'the-events-calendar-shortcode' ) }
		/>
	);
}
