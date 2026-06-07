import { Fragment } from '@wordpress/element';
import { ToggleControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { getDesignDefault } from '../utils/designDefaults';

export default function ThumbnailSetting( { attributes, setAttributes } ) {
	const { thumb, thumbwidth, thumbheight, thumbsize, design } = attributes;
	const isExplicit = typeof thumb !== 'undefined';
	const effectiveValue = isExplicit ? thumb : ( getDesignDefault( design, 'thumb' ) || 'false' );
	const enabled = effectiveValue === 'true';

	const defaultWidth = getDesignDefault( design, 'thumbwidth' ) || '';
	const defaultHeight = getDesignDefault( design, 'thumbheight' ) || '';
	const displayWidth = typeof thumbwidth !== 'undefined' ? thumbwidth : defaultWidth;
	const displayHeight = typeof thumbheight !== 'undefined' ? thumbheight : defaultHeight;

	return (
		<div className="ecs-settings-thumb">
			<ToggleControl
				label={ __( 'Show thumbnail image', 'the-events-calendar-shortcode' ) }
				checked={ enabled }
				onChange={ ( checked ) => setAttributes( { thumb: checked ? 'true' : 'false' } ) }
			/>

			{ enabled && (
				<Fragment>
					<div className="ecs-settings-thumb-width-height">
						<div className="ecs-setting-text-field">
							<label
								className="ecs-setting-label"
								htmlFor="ecs-setting-thumbwidth"
							>{ __( 'Width', 'the-events-calendar-shortcode' ) }</label>
							<input
								id="ecs-setting-thumbwidth"
								type="text"
								value={ displayWidth }
								placeholder={ defaultWidth }
								onChange={ ( e ) => setAttributes( {
									thumbwidth: isNaN( parseInt( e.target.value ) ) ? '' : parseInt( e.target.value ).toString(),
								} ) }
							/>
						</div>

						<div className="ecs-thumb-divider">x</div>

						<div className="ecs-setting-text-field">
							<label
								className="ecs-setting-label"
								htmlFor="ecs-setting-thumbheight"
							>{ __( 'Height', 'the-events-calendar-shortcode' ) }</label>
							<input
								id="ecs-setting-thumbheight"
								type="text"
								value={ displayHeight }
								placeholder={ defaultHeight }
								onChange={ ( e ) => setAttributes( {
									thumbheight: isNaN( parseInt( e.target.value ) ) ? '' : parseInt( e.target.value ).toString(),
								} ) }
							/>
						</div>

						<div className="ecs-thumb-divider"><em>or</em></div>
					</div>
					<div className="ecs-settings-thumb-size">
						<TextControl
							label={ __( 'Size', 'the-events-calendar-shortcode' ) }
							value={ thumbsize || '' }
							onChange={ ( value ) => setAttributes( { thumbsize: value } ) }
						/>
					</div>
					<div className="ecs-setting-help">
						{ __( 'This differs depending on your theme, but typical defaults include "medium" and "large"', 'the-events-calendar-shortcode' ) }
					</div>
				</Fragment>
			) }
		</div>
	);
}
