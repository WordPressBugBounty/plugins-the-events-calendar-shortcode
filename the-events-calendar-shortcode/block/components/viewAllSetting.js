import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function ViewAllSetting( { attributes, setAttributes } ) {
	return (
		<ToggleControl
			label={ __( 'Show "View All Events" link', 'the-events-calendar-shortcode' ) }
			checked={ attributes.viewall === 'true' }
			onChange={ ( checked ) => setAttributes( { viewall: checked ? 'true' : 'false' } ) }
		/>
	);
}
