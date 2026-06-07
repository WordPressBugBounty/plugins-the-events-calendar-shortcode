import { useState, useEffect } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import BlockEdit from './blockEdit';
import BlockPreview from './blockPreview';

export default function Block( props ) {
	const [ PreviewComponent, setPreviewComponent ] = useState(
		() => applyFilters( 'ecs.blockPreview', BlockPreview )
	);

	useEffect( () => {
		const filtered = applyFilters( 'ecs.blockPreview', BlockPreview );
		if ( filtered !== PreviewComponent ) {
			setPreviewComponent( () => filtered );
		}
	}, [] );

	return (
		<>
			<BlockEdit { ...props } />
			<PreviewComponent { ...props } />
		</>
	);
}
