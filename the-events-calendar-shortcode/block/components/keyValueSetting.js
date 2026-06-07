import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';

class KeyValueSetting extends Component {
	handleKeyChange = ( key ) => {
		this.updateKeyValueAttribute( 'key', key );
	}

	handleValueChange = ( value ) => {
		this.updateKeyValueAttribute( 'value', value );
	}

	updateKeyValueAttribute = ( type, newValue ) => {
		const { uid } = this.props;
		let { keyValue } = this.props.attributes;

		keyValue = typeof keyValue === 'undefined' ? {} : JSON.parse( keyValue );
		keyValue[ uid ] = { ...keyValue[ uid ], [ type ]: newValue };

		this.props.setAttributes( { keyValue: JSON.stringify( keyValue ) } );
	}

	render() {
		let { keyValue } = this.props.attributes;

		keyValue = typeof keyValue === 'undefined' ? {} : JSON.parse( keyValue );
		const item = keyValue[ this.props.uid ];

		return (
			<div className="ecs-key-value">
				<TextControl
					label={ __( 'Key', 'the-events-calendar-shortcode' ) }
					value={ item.key }
					onChange={ this.handleKeyChange }
				/>
				<TextControl
					label={ __( 'Value', 'the-events-calendar-shortcode' ) }
					value={ item.value }
					onChange={ this.handleValueChange }
				/>
			</div>
		);
	}
}

export default KeyValueSetting;
