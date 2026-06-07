import ServerSideRender from '@wordpress/server-side-render';

export default function BlockPreview( { attributes } ) {
	return (
		<div className="ecs-block-preview">
			<ServerSideRender
				block={ 'events-calendar-shortcode/block' }
				attributes={ attributes }
			/>
		</div>
	);
}
