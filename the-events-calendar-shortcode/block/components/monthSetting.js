import { Component } from '@wordpress/element';
import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

class MonthSetting extends Component {
	constructor( props ) {
		super( props );
		let { month } = props.attributes;
		month = typeof month === 'undefined' ? '' : month;
		const hasMonth = month !== '';
		const isSpecific = ( hasMonth && month !== 'current' );

		this.state = {
			filterEnabled: hasMonth,
			year: isSpecific ? month.slice( 0, 4 ) : '',
			month: isSpecific ? month.slice( 5 ) : '',
			monthValid: true,
			yearValid: true,
		};
	}

	handleToggle = ( enabled ) => {
		this.setState( { filterEnabled: enabled } );
		if ( enabled ) {
			this.props.setAttributes( { month: 'current' } );
		} else {
			this.props.setAttributes( { month: '' } );
			this.setState( { year: '', month: '', monthValid: true, yearValid: true } );
		}
	}

	handleCurrentChange = ( event ) => {
		if ( event.target.checked ) {
			this.props.setAttributes( { month: 'current' } );
		} else {
			this.props.setAttributes( { month: '' } );
			this.setState( { year: '', month: '', monthValid: true, yearValid: true } );
		}
	}

	handleYearChange = ( event ) => {
		const { month, monthValid } = this.state;

		if ( ! event.target.validity.patternMismatch && monthValid && month !== '' ) {
			this.props.setAttributes( { month: `${ event.target.value }-${ month }` } );
		} else {
			this.props.setAttributes( { month: '' } );
		}

		this.setState( {
			year: event.target.value,
			yearValid: ! event.target.validity.patternMismatch,
		} );
	}

	handleMonthChange = ( event ) => {
		const { year, yearValid } = this.state;

		if ( ! event.target.validity.patternMismatch && yearValid && year !== '' ) {
			this.props.setAttributes( { month: `${ year }-${ event.target.value }` } );
		} else {
			this.props.setAttributes( { month: '' } );
		}

		this.setState( {
			month: event.target.value,
			monthValid: ! event.target.validity.patternMismatch,
		} );
	}

	render() {
		const { month } = this.props.attributes;
		const enabled = this.state.filterEnabled;
		const current = month === 'current';

		return (
			<div className="ecs-settings-month">
				<ToggleControl
					label={ __( 'Filter by month', 'the-events-calendar-shortcode' ) }
					checked={ enabled }
					onChange={ this.handleToggle }
				/>

				{ enabled && (
					<>
						<div className="ecs-setting-current">
							<input
								id="ecs-setting-current"
								type="checkbox"
								checked={ current }
								onChange={ this.handleCurrentChange }
							/><label
								className="components-base-control__label"
								htmlFor="ecs-setting-current"
							>{ __( 'Current Month Only?', 'the-events-calendar-shortcode' ) }</label>
						</div>

						{ ! current && (
							<div className="ecs-setting-year-month">
								<div className="ecs-setting-text-field">
									<label
										className="ecs-setting-label"
										htmlFor="ecs-setting-year"
									>{ __( 'Year', 'the-events-calendar-shortcode' ) }</label>
									<input
										id="ecs-setting-year"
										style={ { borderColor: this.state.yearValid ? 'inherit' : 'red' } }
										type="text"
										placeholder="YYYY"
										value={ this.state.year }
										pattern="[0-9]{4}"
										onChange={ this.handleYearChange }
									/>
								</div>

								<div className="ecs-month-divider" />

								<div className="ecs-setting-text-field">
									<label
										className="ecs-setting-label"
										htmlFor="ecs-setting-month"
									>{ __( 'Month', 'the-events-calendar-shortcode' ) }</label>
									<input
										id="ecs-setting-month"
										style={ { borderColor: this.state.monthValid ? 'inherit' : 'red' } }
										type="text"
										placeholder="MM"
										value={ this.state.month }
										pattern="(0[1-9]|1[012])"
										onChange={ this.handleMonthChange }
									/>
								</div>
							</div>
						) }
					</>
				) }
			</div>
		);
	}
}

export default MonthSetting;
