/**
 * Sidebar control on the core Gallery block.
 *
 * Stored on that gallery only. Galleries saved before this control stay unchanged.
 */
( function ( wp ) {
	if ( ! wp || ! wp.hooks || ! wp.compose || ! wp.element || ! wp.blockEditor || ! wp.components || ! wp.i18n ) {
		return;
	}

	const el = wp.element.createElement;
	const { __ } = wp.i18n;
	const { Fragment } = wp.element;

	const withGalleryLikeControl = wp.compose.createHigherOrderComponent( ( BlockEdit ) => {
		return ( props ) => {
			if ( ! props || props.name !== 'core/gallery' ) {
				return el( BlockEdit, props );
			}

			return el(
				Fragment,
				null,
				el( BlockEdit, props ),
				el(
					wp.blockEditor.InspectorControls,
					null,
					el(
						wp.components.PanelBody,
						{
							title: __( 'WP ULike', 'wp-ulike' ),
							initialOpen: true,
						},
						el( wp.components.ToggleControl, {
							label: __( 'Show a like button on each image', 'wp-ulike' ),
							help: __( 'Each image has its own count. Turn this off to leave the gallery unchanged.', 'wp-ulike' ),
							checked: !! props.attributes.wpUlike,
							onChange: ( value ) => {
								props.setAttributes( { wpUlike: !! value } );
							},
						} )
					)
				)
			);
		};
	}, 'withWpUlikeGalleryControl' );

	wp.hooks.addFilter( 'editor.BlockEdit', 'wp-ulike/gallery-like-control', withGalleryLikeControl );
}( window.wp ) );
