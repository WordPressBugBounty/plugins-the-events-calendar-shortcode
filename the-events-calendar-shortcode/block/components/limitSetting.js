import { __ } from '@wordpress/i18n';

export default function LimitSetting( { attributes, setAttributes } ) {
	if ( attributes.design === 'calendar' ) {
		return null;
	}

	return (
		<div className="ecs-setting-text-field">
			<label
				className="ecs-setting-label"
				htmlFor="ecs-setting-limit"
			>{ __( 'Number of events', 'the-events-calendar-shortcode' ) }</label>
			<input
				id="ecs-setting-limit"
				type="number"
				min={ 1 }
				value={ typeof attributes.limit !== 'undefined' ? attributes.limit : '5' }
				onChange={ ( e ) => setAttributes( { limit: parseInt( e.target.value ) } ) }
			/>
		</div>
	);
}
