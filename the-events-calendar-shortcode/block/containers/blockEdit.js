import { v4 as uuid } from 'uuid';
import { useCallback } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function BlockEdit( props ) {
	const { attributes, setAttributes, settingsConfig } = props;

	const handleAddKeyValue = useCallback( () => {
		let keyValue = typeof attributes.keyValue === 'undefined' ? {} : JSON.parse( attributes.keyValue );
		const uid = `kv-${ uuid() }`;
		keyValue = { ...keyValue, [ uid ]: { key: '', value: '' } };
		setAttributes( { keyValue: JSON.stringify( keyValue ) } );
		return uid;
	}, [ attributes.keyValue, setAttributes ] );

	const handleRemoveKeyValue = useCallback( ( uid ) => {
		let keyValue = typeof attributes.keyValue === 'undefined' ? {} : JSON.parse( attributes.keyValue );
		delete keyValue[ uid ];
		setAttributes( { keyValue: JSON.stringify( keyValue ) } );
	}, [ attributes.keyValue, setAttributes ] );

	const settingKeys = Object.keys( settingsConfig ).filter( ( key ) => key !== 'other' );

	const renderKeyValuePairs = () => {
		let keyValue = typeof attributes.keyValue === 'undefined' ? {} : JSON.parse( attributes.keyValue );
		const KeyValueComponent = settingsConfig.other?.component;

		if ( ! KeyValueComponent ) {
			return null;
		}

		const entries = Object.keys( keyValue );

		return (
			<>
				{ entries.map( ( uid ) => (
					<div key={ uid } className="ecs-kv-row">
						<KeyValueComponent { ...props } uid={ uid } />
						<Button
							icon="no-alt"
							isDestructive
							onClick={ () => handleRemoveKeyValue( uid ) }
							label={ __( 'Remove', 'the-events-calendar-shortcode' ) }
							className="ecs-kv-remove"
						/>
					</div>
				) ) }
				<Button
					variant="secondary"
					onClick={ handleAddKeyValue }
					className="ecs-kv-add"
				>
					{ __( 'Add Key/Value Pair', 'the-events-calendar-shortcode' ) }
				</Button>
				<div className="ecs-setting-help">
					<a
						href="https://eventcalendarnewsletter.com/events-calendar-shortcode-pro-options/?utm_source=plugin&utm_medium=link&utm_campaign=block-advanced-help&utm_content=description&free=1"
						target="_blank"
						rel="noopener noreferrer"
					>{ __( 'View documentation on available options', 'the-events-calendar-shortcode' ) }</a>
					{ __( ' where key="value" in the shortcode can be entered in the boxes above', 'the-events-calendar-shortcode' ) }
				</div>
			</>
		);
	};

	return (
		<InspectorControls>
			<div className="ecs-sidebar-settings">
				{ settingKeys.map( ( key ) => {
					const config = settingsConfig[ key ];
					const SettingComponent = config.component;

					return (
						<div key={ key } className="ecs-setting-section">
							{ config.sectionLabel && (
								<div className="ecs-setting-section-label">{ config.sectionLabel }</div>
							) }
							<SettingComponent { ...props } />
						</div>
					);
				} ) }

				<div className="ecs-setting-section">
					{ renderKeyValuePairs() }
				</div>
			</div>
		</InspectorControls>
	);
}
