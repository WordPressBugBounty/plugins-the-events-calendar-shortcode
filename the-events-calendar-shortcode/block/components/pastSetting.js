import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function PastSetting( { attributes, setAttributes } ) {
	return (
		<ToggleControl
			label={ __( 'Show only past events', 'the-events-calendar-shortcode' ) }
			checked={ attributes.past === 'yes' }
			onChange={ ( checked ) => setAttributes( { past: checked ? 'yes' : '' } ) }
		/>
	);
}
