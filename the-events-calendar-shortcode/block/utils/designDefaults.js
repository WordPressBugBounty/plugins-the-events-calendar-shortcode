import { applyFilters } from '@wordpress/hooks';

/**
 * Returns the design-specific default for a given attribute.
 * Can register actual per-design defaults via the
 * 'ecs.designAttributeDefaults' filter. When the attribute has never
 * been explicitly set by the user (undefined), this value should be
 * used for toggle state and content-order greying.
 */
export function getDesignDefault( design, attribute ) {
	const defaults = applyFilters( 'ecs.designAttributeDefaults', {} );
	return defaults[ design ]?.[ attribute ];
}
