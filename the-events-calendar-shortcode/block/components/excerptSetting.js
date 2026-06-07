import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { getDesignDefault } from '../utils/designDefaults';

export default function ExcerptSetting( { attributes, setAttributes } ) {
	let { excerpt, design } = attributes;

	if ( excerpt && excerpt !== 'false' && isNaN( parseInt( excerpt ) ) ) {
		excerpt = '100';
		setAttributes( { excerpt: '100' } );
	}

	const isExplicit = typeof excerpt !== 'undefined';
	const designDefault = getDesignDefault( design, 'excerpt' );
	const effectiveValue = isExplicit ? excerpt : ( designDefault || 'false' );
	const enabled = effectiveValue !== 'false';

	return (
		<div className="ecs-settings-excerpt">
			<ToggleControl
				label={ __( 'Show excerpt of events', 'the-events-calendar-shortcode' ) }
				checked={ enabled }
				onChange={ ( checked ) => setAttributes( { excerpt: checked ? '100' : 'false' } ) }
			/>

			{ enabled && (
				<div className="ecs-setting-text-field">
					<label
						className="ecs-setting-label"
						htmlFor="ecs-setting-excerpt-length"
					>{ __( 'Length', 'the-events-calendar-shortcode' ) }</label>
					<input
						id="ecs-setting-excerpt-length"
						style={ { borderColor: ! isNaN( parseInt( excerpt ) ) ? 'inherit' : 'red' } }
						type="text"
						value={ excerpt }
						pattern="[0-9]*"
						onChange={ ( e ) => {
							if ( ! e.target.validity.patternMismatch ) {
								setAttributes( { excerpt: e.target.value } );
							} else {
								setAttributes( { excerpt: '100' } );
							}
						} }
					/>
				</div>
			) }

			<div className="ecs-setting-help">
				{ __( 'Want to show the full description of events or HTML from your excerpt? ', 'the-events-calendar-shortcode' ) }
				<a
					href="https://eventcalendarnewsletter.com/the-events-calendar-shortcode/?utm_source=plugin&utm_medium=link&utm_campaign=block-excerpt-help&utm_content=description"
					target="_blank"
					rel="noopener noreferrer"
				>{ __( 'Upgrade to Pro', 'the-events-calendar-shortcode' ) }</a>
			</div>
		</div>
	);
}
