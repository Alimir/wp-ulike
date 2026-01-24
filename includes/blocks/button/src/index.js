/**
 * WP ULike Block - Main Editor Script
 */

import { registerBlockType, getBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { PanelBody, SelectControl, TextControl, ToggleControl, Spinner, ButtonGroup, Button, Icon, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

import metadata from '../block.json';
import './editor.css';

// Check if block is already registered to prevent duplicate registration
if ( ! getBlockType( metadata.name ) ) {
	registerBlockType( metadata.name, {
		...metadata,
		edit: ( { attributes, setAttributes, isSelected, clientId } ) => {
		const blockProps = useBlockProps();
		const {
			for: forType,
			itemId,
			useCurrentPostId,
			template,
			buttonType
		} = attributes;

		const [ templates, setTemplates ] = useState( [] );
		const [ defaultTemplateName, setDefaultTemplateName ] = useState( __( 'Use Settings Default', 'wp-ulike' ) );
		const [ loading, setLoading ] = useState( true );

		// Check if block is inside a comment-template block (check parent hierarchy)
		const isInCommentTemplate = useSelect(
			( select ) => {
				if ( ! clientId ) {
					return false;
				}

				const { getBlockParents, getBlockName } = select( 'core/block-editor' );
				const parents = getBlockParents( clientId );

				return parents.some(
					( parentId ) => getBlockName( parentId ) === 'core/comment-template'
				);
			},
			[ clientId ]
		);


		// Item type options (only Post and Comment)
		const itemTypeOptions = [
			{ label: __( 'Post', 'wp-ulike' ), value: 'post' },
			{ label: __( 'Comment', 'wp-ulike' ), value: 'comment' }
		];

		// Fetch templates from REST API (only once)
		useEffect( () => {
			let isMounted = true;

			const fetchTemplates = async () => {
				try {
					const response = await apiFetch( {
						path: '/wp-ulike/v1/templates'
					} );

					if ( ! isMounted ) return;

					if ( response && response.templates && Array.isArray( response.templates ) ) {
						setTemplates( response.templates );
						if ( response.default_template_name ) {
							setDefaultTemplateName( response.default_template_name );
						}
					} else if ( response && Array.isArray( response ) ) {
						setTemplates( response );
					}
				} catch ( error ) {
					if ( isMounted ) {
						console.error( 'Error fetching templates:', error );
						setTemplates( [] );
					}
				} finally {
					if ( isMounted ) {
						setLoading( false );
					}
				}
			};

			fetchTemplates();

			return () => {
				isMounted = false;
			};
		}, [] );

		// Build template options (memoized)
		const allTemplates = [
			{ key: '', name: defaultTemplateName, symbol: '', is_text_support: true },
			...templates
		];

		// Filter button type options based on selected template
		// Find selected template (including default)
		const selectedTemplate = allTemplates.find( ( t ) => t.key === template );
		const supportsText = selectedTemplate ? ( selectedTemplate.is_text_support !== false ) : true;
		const buttonTypeOptions = [
			{ label: __( 'Default', 'wp-ulike' ), value: '' },
			{ label: __( 'Image', 'wp-ulike' ), value: 'image' }
		];

		if ( supportsText ) {
			buttonTypeOptions.push( { label: __( 'Text', 'wp-ulike' ), value: 'text' } );
		}

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Settings', 'wp-ulike' ) } initialOpen={ true }>
						{ forType === 'comment' && ! isInCommentTemplate && (
							<div style={ { marginBottom: '20px' } }>
								<Notice
									status="info"
									isDismissible={ false }
									className="wp-ulike-comment-context-notice"
								>
									{ __( 'Comment buttons work best when placed inside a Comment Template block. They will automatically use the current comment ID.', 'wp-ulike' ) }
								</Notice>
							</div>
						) }

						<SelectControl
							label={ __( 'Item Type', 'wp-ulike' ) }
							value={ forType }
							options={ itemTypeOptions }
							onChange={ ( value ) => setAttributes( { for: value } ) }
							help={ __( 'Select the type of content to add interactive like/dislike buttons to.', 'wp-ulike' ) }
							__next40pxDefaultSize={ true }
							__nextHasNoMarginBottom={ true }
						/>

						<ToggleControl
							label={ __( 'Use Current Item ID', 'wp-ulike' ) }
							checked={ useCurrentPostId }
							onChange={ ( value ) => setAttributes( { useCurrentPostId: value } ) }
							help={ useCurrentPostId
								? __( 'Automatically uses the current post or comment ID. You can optionally add a custom ID below to combine with it.', 'wp-ulike' )
								: __( 'Disable to use a custom item ID instead of the current one.', 'wp-ulike' )
							}
							__nextHasNoMarginBottom={ true }
						/>

						<TextControl
							label={ __( 'Custom Item ID', 'wp-ulike' ) }
							value={ itemId }
							onChange={ ( value ) => setAttributes( { itemId: value } ) }
							help={ useCurrentPostId
								? __( 'Optional: Enter a number to combine with the current item ID. Example: If current ID is 42 and you enter 100, the final ID will be 42100. Useful for creating multiple interactive buttons on the same post. Note: Custom combined IDs will not appear in statistics/insights.', 'wp-ulike' )
								: __( 'Enter a specific item ID to use. Leave empty to automatically detect the current item ID. Note: Custom IDs will not appear in statistics/insights.', 'wp-ulike' )
							}
							type="number"
							placeholder={ useCurrentPostId ? __( 'Leave empty or enter number to combine', 'wp-ulike' ) : __( 'Enter item ID', 'wp-ulike' ) }
							__next40pxDefaultSize={ true }
							__nextHasNoMarginBottom={ true }
						/>

						<div className="wp-ulike-template-selector" style={ { marginBottom: '15px'} }>
							<label className="components-base-control__label" style={ { marginBottom: '8px', display: 'block' } }>
								{ __( 'Select a Template', 'wp-ulike' ) }
							</label>
							<div style={ {
								display: 'grid',
								gridTemplateColumns: 'repeat(auto-fill, minmax(85px, 1fr))',
								gap: '6px',
								marginBottom: '8px'
							} }>
								{ allTemplates.map( ( tmpl ) => {
									const isSelected = template === tmpl.key;
									const isLocked = tmpl.is_locked === true || tmpl.is_locked === 'true' || tmpl.is_locked === 1;
									return (
										<button
											key={ tmpl.key || 'default' }
											type="button"
											onClick={ () => {
												if ( ! isLocked ) {
													setAttributes( { template: tmpl.key } );
												}
											} }
											disabled={ isLocked }
											className={ `wp-ulike-template-option ${ isSelected ? 'is-selected' : '' } ${ isLocked ? 'is-locked' : '' }` }
											style={ {
												display: 'flex',
												flexDirection: 'column',
												alignItems: 'center',
												justifyContent: 'center',
												padding: '10px 8px',
												border: `1.5px solid ${ isSelected ? '#0073aa' : isLocked ? '#ccc' : '#ddd' }`,
												borderRadius: '3px',
												background: isLocked ? '#f5f5f5' : '#fff',
												cursor: isLocked ? 'not-allowed' : 'pointer',
												transition: 'border-color 0.15s ease',
												opacity: isLocked ? 0.6 : 1,
												position: 'relative'
											} }
											onMouseEnter={ ( e ) => {
												if ( ! isSelected && ! isLocked ) {
													e.currentTarget.style.borderColor = '#bbb';
												}
											} }
											onMouseLeave={ ( e ) => {
												if ( ! isSelected && ! isLocked ) {
													e.currentTarget.style.borderColor = '#ddd';
												}
											} }
											title={ isLocked ? `${ tmpl.name } (${ __( 'Pro Feature', 'wp-ulike' ) })` : tmpl.name }
										>
											<div style={ {
												width: '50px',
												height: '50px',
												marginBottom: '6px',
												display: 'flex',
												alignItems: 'center',
												justifyContent: 'center',
												position: 'relative'
											} }>
												{ tmpl.symbol ? (
													<img
														src={ tmpl.symbol }
														alt={ tmpl.name }
														style={ {
															width: '50px',
															height: '50px',
															objectFit: 'contain',
															filter: isSelected ? 'brightness(40%) sepia(100%) hue-rotate(170deg) saturate(250%)' : isLocked ? 'grayscale(100%) opacity(0.5)' : 'none',
															transition: 'filter 0.15s ease'
														} }
													/>
												) : (
													<Icon
														icon="admin-settings"
														size={ 32 }
														style={ {
															filter: isSelected ? 'brightness(40%) sepia(100%) hue-rotate(170deg) saturate(250%)' : isLocked ? 'grayscale(100%) opacity(0.5)' : 'none',
															transition: 'filter 0.15s ease',
															color: '#646970'
														} }
													/>
												) }
												{ isLocked && (
													<Icon
														icon="lock"
														size={ 16 }
														style={ {
															position: 'absolute',
															top: '2px',
															right: '2px',
															color: '#d63638',
															background: '#fff',
															borderRadius: '50%',
															padding: '2px'
														} }
													/>
												) }
											</div>
											<span style={ {
												fontSize: '10px',
												textAlign: 'center',
												color: isSelected ? '#0073aa' : isLocked ? '#999' : '#666',
												fontWeight: '400',
												lineHeight: '1.3',
												wordBreak: 'break-word'
											} }>
												{ tmpl.name }
											</span>
										</button>
									);
								} ) }
							</div>
						</div>

						{ template && supportsText !== false && (
							<SelectControl
								label={ __( 'Button Type', 'wp-ulike' ) }
								value={ buttonType }
								options={ buttonTypeOptions }
								onChange={ ( value ) => setAttributes( { buttonType: value } ) }
								help={ __( 'Choose whether to display an image icon or text label.', 'wp-ulike' ) }
								__next40pxDefaultSize={ true }
								__nextHasNoMarginBottom={ true }
							/>
						) }
					</PanelBody>
				</InspectorControls>

				<div { ...blockProps }>
					<ServerSideRender
						block="wp-ulike/button"
						attributes={ attributes }
						LoadingResponsePlaceholder={ () => (
							<div style={ {
								padding: '20px',
								textAlign: 'center',
								display: 'flex',
								alignItems: 'center',
								justifyContent: 'center',
								gap: '8px',
								minHeight: '60px'
							} }>
								<Spinner />
								<span style={ { color: '#757575', fontSize: '13px' } }>
									{ __( 'Loading preview...', 'wp-ulike' ) }
								</span>
							</div>
						) }
						ErrorResponsePlaceholder={ () => (
							<div style={ {
								padding: '20px',
								textAlign: 'center',
								color: '#cc1818',
								fontSize: '13px'
							} }>
								{ __( 'Error loading WP ULike button preview.', 'wp-ulike' ) }
							</div>
						) }
					/>
				</div>
			</>
		);
	},

	save: () => {
		// Save is handled server-side via render.php
		return null;
	}
	} );
}
