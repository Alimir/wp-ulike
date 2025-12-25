/**
 * WP ULike Block - Main Editor Script
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

import metadata from '../block.json';
import './editor.css';
import './style.css';

registerBlockType( metadata.name, {
	...metadata,
	edit: ( { attributes, setAttributes, isSelected } ) => {
		const blockProps = useBlockProps();
		const {
			for: forType,
			itemId,
			useCurrentPostId,
			template,
			buttonType,
			wrapperClass
		} = attributes;

		const [ templates, setTemplates ] = useState( [] );
		const [ loading, setLoading ] = useState( true );

		// Fetch templates from REST API
		useEffect( () => {
			const fetchTemplates = async () => {
				try {
					// Use WordPress REST API
					const response = await apiFetch( {
						path: '/wp-ulike/v1/templates'
					} );
					if ( response && Array.isArray( response ) ) {
						setTemplates( response );
					}
				} catch ( error ) {
					console.error( 'Error fetching templates:', error );
					// Set empty array on error
					setTemplates( [] );
				} finally {
					setLoading( false );
				}
			};

			fetchTemplates();
		}, [] );

		// Build template options for SelectControl
		const templateOptions = [
			{ label: __( 'Default Template', 'wp-ulike' ), value: '' }
		];

		if ( templates.length > 0 ) {
			templates.forEach( ( tmpl ) => {
				templateOptions.push( {
					label: tmpl.name,
					value: tmpl.key,
					image: tmpl.symbol
				} );
			} );
		}

		// Filter button type options based on selected template
		const selectedTemplate = templates.find( ( t ) => t.key === template );
		const supportsText = selectedTemplate ? selectedTemplate.is_text_support : true;
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
					<PanelBody title={ __( 'WP ULike Settings', 'wp-ulike' ) } initialOpen={ true }>
						<SelectControl
							label={ __( 'Content Type', 'wp-ulike' ) }
							value={ forType }
							options={ [
								{ label: __( 'Post', 'wp-ulike' ), value: 'post' },
								{ label: __( 'Comment', 'wp-ulike' ), value: 'comment' },
								{ label: __( 'Activity (BuddyPress)', 'wp-ulike' ), value: 'activity' },
								{ label: __( 'Topic (bbPress)', 'wp-ulike' ), value: 'topic' }
							] }
							onChange={ ( value ) => setAttributes( { for: value } ) }
							help={ __( 'Select the type of content this button will like.', 'wp-ulike' ) }
						/>

						<ToggleControl
							label={ __( 'Use Current Post ID', 'wp-ulike' ) }
							checked={ useCurrentPostId }
							onChange={ ( value ) => setAttributes( { useCurrentPostId: value } ) }
							help={ __( 'Automatically use the current post/page ID. Disable to set a custom ID.', 'wp-ulike' ) }
						/>

						{ ! useCurrentPostId && (
							<TextControl
								label={ __( 'Custom Item ID', 'wp-ulike' ) }
								value={ itemId }
								onChange={ ( value ) => setAttributes( { itemId: value } ) }
								help={ __( 'Enter a specific post, comment, or item ID to like.', 'wp-ulike' ) }
								type="number"
							/>
						) }

						<SelectControl
							label={ __( 'Template', 'wp-ulike' ) }
							value={ template }
							options={ templateOptions }
							onChange={ ( value ) => setAttributes( { template: value } ) }
							help={ __( 'Choose a template style for the like button.', 'wp-ulike' ) }
						/>

						{ template && supportsText !== false && (
							<SelectControl
								label={ __( 'Button Type', 'wp-ulike' ) }
								value={ buttonType }
								options={ buttonTypeOptions }
								onChange={ ( value ) => setAttributes( { buttonType: value } ) }
								help={ __( 'Choose whether to display an image icon or text label.', 'wp-ulike' ) }
							/>
						) }

						<TextControl
							label={ __( 'Wrapper Class', 'wp-ulike' ) }
							value={ wrapperClass }
							onChange={ ( value ) => setAttributes( { wrapperClass: value } ) }
							help={ __( 'Add custom CSS classes to the wrapper element.', 'wp-ulike' ) }
						/>
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
