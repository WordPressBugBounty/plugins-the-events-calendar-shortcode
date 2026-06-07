import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { getDesignDefault } from '../utils/designDefaults';

export default function VenueSetting( { attributes, setAttributes } ) {
	const { venue, design } = attributes;
	const isExplicit = typeof venue !== 'undefined' && venue !== '';
	const effectiveValue = isExplicit ? venue : ( getDesignDefault( design, 'venue' ) || 'false' );
	const enabled = effectiveValue !== 'false';

	return (
		<ToggleControl
			label={ __( 'Show venue information', 'the-events-calendar-shortcode' ) }
			checked={ enabled }
			onChange={ ( checked ) => setAttributes( { venue: checked ? 'true' : 'false' } ) }
		/>
	);
}
