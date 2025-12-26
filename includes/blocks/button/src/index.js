/**
 * WP ULike Block - Main Editor Script
 */

import { registerBlockType, getBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl, Spinner, ButtonGroup, Button, Icon } from '@wordpress/components';
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
		edit: ( { attributes, setAttributes, isSelected } ) => {
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
						<SelectControl
							label={ __( 'Content Types', 'wp-ulike' ) }
							value={ forType }
							options={ [
								{ label: __( 'Post', 'wp-ulike' ), value: 'post' },
								{ label: __( 'Comment', 'wp-ulike' ), value: 'comment' },
								{ label: __( 'Activity (BuddyPress)', 'wp-ulike' ), value: 'activity' },
								{ label: __( 'Topic (bbPress)', 'wp-ulike' ), value: 'topic' }
							] }
							onChange={ ( value ) => setAttributes( { for: value } ) }
							help={ __( 'Select the type of content this button will like.', 'wp-ulike' ) }
							__next40pxDefaultSize={ true }
							__nextHasNoMarginBottom={ true }
						/>

						<ToggleControl
							label={ __( 'Use Current Post ID', 'wp-ulike' ) }
							checked={ useCurrentPostId }
							onChange={ ( value ) => setAttributes( { useCurrentPostId: value } ) }
							help={ __( 'Automatically use the current post/page ID. Disable to set a custom ID.', 'wp-ulike' ) }
							__nextHasNoMarginBottom={ true }
						/>

						{ ! useCurrentPostId && (
							<TextControl
								label={ __( 'Custom Item ID', 'wp-ulike' ) }
								value={ itemId }
								onChange={ ( value ) => setAttributes( { itemId: value } ) }
								help={ __( 'Enter a specific post, comment, or item ID to like.', 'wp-ulike' ) }
								type="number"
								__next40pxDefaultSize={ true }
								__nextHasNoMarginBottom={ true }
							/>
						) }

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
									return (
										<button
											key={ tmpl.key || 'default' }
											type="button"
											onClick={ () => setAttributes( { template: tmpl.key } ) }
											className={ `wp-ulike-template-option ${ isSelected ? 'is-selected' : '' }` }
											style={ {
												display: 'flex',
												flexDirection: 'column',
												alignItems: 'center',
												justifyContent: 'center',
												padding: '10px 8px',
												border: `1.5px solid ${ isSelected ? '#0073aa' : '#ddd' }`,
												borderRadius: '3px',
												background: '#fff',
												cursor: 'pointer',
												transition: 'border-color 0.15s ease'
											} }
											onMouseEnter={ ( e ) => {
												if ( ! isSelected ) {
													e.currentTarget.style.borderColor = '#bbb';
												}
											} }
											onMouseLeave={ ( e ) => {
												if ( ! isSelected ) {
													e.currentTarget.style.borderColor = '#ddd';
												}
											} }
											title={ tmpl.name }
										>
											<div style={ {
												width: '50px',
												height: '50px',
												marginBottom: '6px',
												display: 'flex',
												alignItems: 'center',
												justifyContent: 'center'
											} }>
												{ tmpl.symbol ? (
													<img
														src={ tmpl.symbol }
														alt={ tmpl.name }
														style={ {
															width: '50px',
															height: '50px',
															objectFit: 'contain',
															filter: isSelected ? 'brightness(40%) sepia(100%) hue-rotate(170deg) saturate(250%)' : 'none',
															transition: 'filter 0.15s ease'
														} }
													/>
												) : (
													<Icon
														icon="admin-settings"
														size={ 32 }
														style={ {
															filter: isSelected ? 'brightness(40%) sepia(100%) hue-rotate(170deg) saturate(250%)' : 'none',
															transition: 'filter 0.15s ease',
															color: '#646970'
														} }
													/>
												) }
											</div>
											<span style={ {
												fontSize: '10px',
												textAlign: 'center',
												color: isSelected ? '#0073aa' : '#666',
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
