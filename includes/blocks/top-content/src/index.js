/**
 * WP ULike Top Content Block
 */

import { registerBlockType, getBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import {
	PanelBody,
	SelectControl,
	RangeControl,
	ToggleControl,
	TextControl,
	Spinner,
	FormTokenField,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { ServerSideRender } from '@wordpress/server-side-render';
import { useMemo, useEffect } from '@wordpress/element';

import metadata from '../block.json';
import './editor.scss';

/** Build "Top Posts", "Top User(s)", etc. from existing strings. */
const formatTopTypeLabel = ( type ) => {
	const suffixes = {
		post: __( 'Posts', 'wp-ulike' ),
		comment: __( 'Comments', 'wp-ulike' ),
		users: __( 'User(s)', 'wp-ulike' ),
		activity: __( 'Activities', 'wp-ulike' ),
		topic: __( 'Topics', 'wp-ulike' ),
	};
	if ( ! suffixes[ type ] ) {
		return __( 'Top', 'wp-ulike' );
	}
	return sprintf( '%s %s', __( 'Top', 'wp-ulike' ), suffixes[ type ] );
};

const FALLBACK_CONTENT_TYPES = [
	{ value: 'post', label: formatTopTypeLabel( 'post' ) },
	{ value: 'comment', label: formatTopTypeLabel( 'comment' ) },
	{ value: 'users', label: formatTopTypeLabel( 'users' ) },
];

const STATIC_SORT_OPTIONS = [
	{ value: 'like', label: __( 'Like', 'wp-ulike' ) },
];

const PRO_SORT_OPTIONS = [
	{ value: 'dislike', label: __( 'Dislike', 'wp-ulike' ) },
];

const normalizeSortBy = ( value ) => {
	if ( Array.isArray( value ) ) {
		return value;
	}
	if ( typeof value === 'string' && value ) {
		return [ value ];
	}
	return [ 'like' ];
};

const getSortOptionsList = () => {
	const cfg = getEditorConfig();
	if ( cfg.sortOptions?.length ) {
		return cfg.sortOptions;
	}
	return cfg.hasPro ? [ ...STATIC_SORT_OPTIONS, ...PRO_SORT_OPTIONS ] : STATIC_SORT_OPTIONS;
};

const STATIC_SORT_ORDERS = [
	{ value: 'DESC', label: __( 'Descending', 'wp-ulike' ) },
	{ value: 'ASC', label: __( 'Ascending', 'wp-ulike' ) },
];

const STATIC_PERIOD_PRESETS = [
	{ value: 'all', label: __( 'All The Times', 'wp-ulike' ) },
	{ value: 'year', label: __( 'This Year', 'wp-ulike' ) },
	{ value: 'last_year', label: __( 'Last Year', 'wp-ulike' ) },
	{ value: 'month', label: __( 'Month', 'wp-ulike' ) },
	{ value: 'last_month', label: __( 'Last Month', 'wp-ulike' ) },
	{ value: 'week', label: __( 'This Week', 'wp-ulike' ) },
	{ value: 'last_week', label: __( 'Last Week', 'wp-ulike' ) },
	{ value: 'today', label: __( 'Today', 'wp-ulike' ) },
	{ value: 'yesterday', label: __( 'Yesterday', 'wp-ulike' ) },
	{ value: 'day_before_yesterday', label: __( 'Day Before Yesterday', 'wp-ulike' ) },
];

const STATIC_INTERVAL_UNITS = [
	{ value: 'DAY', label: __( 'day', 'wp-ulike' ) },
	{ value: 'WEEK', label: __( 'week', 'wp-ulike' ) },
	{ value: 'MONTH', label: __( 'month', 'wp-ulike' ) },
	{ value: 'HOUR', label: __( 'hour', 'wp-ulike' ) },
];

const getEditorConfig = () => window.wpUlikeTopContentBlock || {};

const getContentTypeOptions = () => {
	const cfg = getEditorConfig();
	if ( cfg.contentTypes?.length ) {
		return cfg.contentTypes;
	}
	return [ ...FALLBACK_CONTENT_TYPES ];
};

/** Strip trailing colons from legacy plugin strings for cleaner UI labels. */
const stripLabelColon = ( text ) => {
	if ( ! text ) {
		return text;
	}
	return String( text ).replace( /:+\s*$/, '' ).trim();
};

const uiLabel = ( text ) => stripLabelColon( text );

/** "Last {{days}} Days" using the real day count (not a fixed preview number). */
const formatLastDaysLabel = ( days ) => {
	const count = Math.max( 1, parseInt( days, 10 ) || 1 );
	return __( 'Last {{days}} Days', 'wp-ulike' ).replace( /\{\{days\}\}/g, String( count ) );
};

if ( ! getBlockType( metadata.name ) ) {
	registerBlockType( metadata.name, {
		...metadata,
		edit: ( { attributes, setAttributes } ) => {
			const blockProps = useBlockProps( {
				className: 'wp-block-wp-ulike-top-content',
				// ServerSideRender outputs real links; stop navigation inside the editor canvas.
				onClickCapture: ( event ) => {
					if ( event.target.closest( '.wp-ulike-top-content a[href]' ) ) {
						event.preventDefault();
					}
				},
			} );

			const {
				contentType,
				sortBy,
				sortOrder,
				periodMode,
				period,
				intervalValue,
				intervalUnit,
				dateStart,
				dateEnd,
				postTypes,
				taxonomy,
				taxonomyTerms,
				limit,
				showCount,
				showThumbnail,
				showRank,
				showHeading,
				showEngagedUsers,
				titleTrim,
				thumbnailSize,
				heading,
				profileUrl,
			} = attributes;

			const config = getEditorConfig();

			const contentTypeOptions = useMemo( () => getContentTypeOptions(), [ config.contentTypes ] );

			const sortOptions = useMemo( () => {
				return getSortOptionsList().map( ( item ) => ( {
					label: item.label,
					value: item.value,
				} ) );
			}, [ config.sortOptions, config.hasPro ] );

			const sortByList = useMemo( () => normalizeSortBy( sortBy ), [ sortBy ] );

			useEffect( () => {
				const allowed = sortOptions.map( ( item ) => item.value );
				const next = sortByList.filter( ( status ) => allowed.includes( status ) );

				if ( 0 === next.length ) {
					setAttributes( { sortBy: [ 'like' ] } );
					return;
				}

				if (
					! Array.isArray( sortBy ) ||
					next.length !== sortByList.length ||
					next.some( ( status, index ) => status !== sortByList[ index ] )
				) {
					setAttributes( { sortBy: next } );
				}
			}, [ sortBy, sortByList, sortOptions ] );

			const sortByTokens = useMemo(
				() =>
					sortByList.map( ( slug ) => {
						const found = sortOptions.find( ( item ) => item.value === slug );
						return found ? found.label : slug;
					} ),
				[ sortByList, sortOptions ]
			);

			const sortBySuggestions = useMemo(
				() => sortOptions.map( ( item ) => item.label ),
				[ sortOptions ]
			);

			const onSortByChange = ( tokens ) => {
				const slugs = tokens
					.map( ( token ) => {
						const found = sortOptions.find(
							( item ) => item.label === token || item.value === token
						);
						return found ? found.value : token;
					} )
					.filter( ( slug ) => sortOptions.some( ( item ) => item.value === slug ) );

				setAttributes( { sortBy: slugs.length ? slugs : [ 'like' ] } );
			};

			const periodPresetOptions = useMemo( () => {
				const source = config.periodPresets?.length ? config.periodPresets : STATIC_PERIOD_PRESETS;
				return source.map( ( item ) => ( {
					label: item.label,
					value: item.value,
				} ) );
			}, [ config.periodPresets ] );

			const periodSelectOptions = useMemo( () => {
				return [
					...periodPresetOptions.map( ( item ) => ( {
						label: item.label,
						value: `preset:${ item.value }`,
					} ) ),
					{
						label: formatLastDaysLabel( intervalValue ),
						value: 'mode:interval',
					},
					{
						label: uiLabel( __( 'Date Range', 'wp-ulike' ) ),
						value: 'mode:range',
					},
				];
			}, [ periodPresetOptions, intervalValue ] );

			const periodSelectValue =
				periodMode === 'preset' ? `preset:${ period }` : `mode:${ periodMode }`;

			const onContentTypeChange = ( value ) => {
				const next = { contentType: value };
				if ( value !== 'post' && value !== 'comment' ) {
					next.postTypes = [];
					next.taxonomy = '';
					next.taxonomyTerms = [];
				}
				setAttributes( next );
			};

			const onPeriodChange = ( value ) => {
				if ( value.startsWith( 'preset:' ) ) {
					setAttributes( {
						periodMode: 'preset',
						period: value.replace( 'preset:', '' ),
					} );
					return;
				}
				if ( value === 'mode:interval' ) {
					setAttributes( { periodMode: 'interval' } );
					return;
				}
				if ( value === 'mode:range' ) {
					setAttributes( { periodMode: 'range' } );
				}
			};

			const intervalUnitOptions = useMemo( () => {
				const source = config.intervalUnits?.length ? config.intervalUnits : STATIC_INTERVAL_UNITS;
				return source.map( ( item ) => ( {
					label: item.label,
					value: item.value,
				} ) );
			}, [] );

			const postTypeSuggestions = useMemo(
				() => ( config.postTypes || [] ).map( ( t ) => t.value ),
				[]
			);

			const postTypeValue = useMemo(
				() =>
					( postTypes || [] ).map( ( slug ) => {
						const found = ( config.postTypes || [] ).find( ( t ) => t.value === slug );
						return found ? found.label : slug;
					} ),
				[ postTypes ]
			);

			const primaryPostType = postTypes?.length ? postTypes[ 0 ] : 'post';

			const taxonomies = useSelect(
				( select ) => {
					const { getTaxonomies } = select( 'core' );
					return getTaxonomies( { type: primaryPostType } ) || [];
				},
				[ primaryPostType ]
			);

			const taxonomyOptions = useMemo(
				() =>
					( taxonomies || [] )
						.filter( ( tax ) => tax.visibility?.public !== false )
						.map( ( tax ) => ( {
							label: tax.name,
							value: tax.slug,
						} ) ),
				[ taxonomies ]
			);

			const terms = useSelect(
				( select ) => {
					if ( ! taxonomy ) {
						return [];
					}
					const { getEntityRecords } = select( 'core' );
					return getEntityRecords( 'taxonomy', taxonomy, { per_page: 100 } ) || [];
				},
				[ taxonomy ]
			);

			const termSuggestions = useMemo(
				() => ( terms || [] ).map( ( term ) => term.name ),
				[ terms ]
			);

			const termValues = useMemo(
				() =>
					( taxonomyTerms || [] ).map( ( id ) => {
						const term = ( terms || [] ).find( ( t ) => t.id === id );
						return term ? term.name : String( id );
					} ),
				[ taxonomyTerms, terms ]
			);

			const showPostFilters = contentType === 'post' || contentType === 'comment';
			const showProfileControl = contentType === 'users';
			const showSortOrderControl = contentType !== 'users';
			const showThumbnailControl = contentType !== 'activity' && contentType !== 'topic';
			const showEngagementExtras = contentType !== 'users';

			const onPostTypesChange = ( tokens ) => {
				const slugs = tokens.map( ( token ) => {
					const found = ( config.postTypes || [] ).find(
						( t ) => t.label === token || t.value === token
					);
					return found ? found.value : token;
				} );
				setAttributes( { postTypes: slugs } );
			};

			const onTermsChange = ( tokens ) => {
				const ids = tokens.map( ( token ) => {
					const found = ( terms || [] ).find( ( t ) => t.name === token );
					return found ? found.id : parseInt( token, 10 );
				} ).filter( ( id ) => ! Number.isNaN( id ) );
				setAttributes( { taxonomyTerms: ids } );
			};

			return (
				<>
					<InspectorControls>
						<PanelBody
							title={ uiLabel( __( 'Top', 'wp-ulike' ) ) }
							initialOpen={ true }
						>
							<SelectControl
								label={ uiLabel( __( 'Type:', 'wp-ulike' ) ) }
								value={ contentType }
								options={ contentTypeOptions }
								onChange={ onContentTypeChange }
								__next40pxDefaultSize={ true }
								__nextHasNoMarginBottom={ true }
							/>

							<FormTokenField
								label={ uiLabel( __( 'Status Filter', 'wp-ulike' ) ) }
								value={ sortByTokens }
								suggestions={ sortBySuggestions }
								onChange={ onSortByChange }
								__experimentalExpandOnFocus
								__nextHasNoMarginBottom
							/>

							{ showSortOrderControl && (
								<SelectControl
									label={ uiLabel( __( 'View By', 'wp-ulike' ) ) }
									value={ sortOrder }
									options={ ( config.sortOrders?.length ? config.sortOrders : STATIC_SORT_ORDERS ).map( ( item ) => ( {
										label: item.label,
										value: item.value,
									} ) ) }
									onChange={ ( value ) => setAttributes( { sortOrder: value } ) }
									__next40pxDefaultSize={ true }
									__nextHasNoMarginBottom={ true }
								/>
							) }

							<RangeControl
								label={ uiLabel( __( 'Number of items to show:', 'wp-ulike' ) ) }
								value={ limit }
								onChange={ ( value ) => setAttributes( { limit: value } ) }
								min={ 1 }
								max={ 20 }
								__nextHasNoMarginBottom={ true }
							/>

							{ showProfileControl && config.profileUrls?.length > 1 && (
								<SelectControl
									label={ uiLabel( __( 'Profile URL:', 'wp-ulike' ) ) }
									value={ profileUrl }
									options={ config.profileUrls.map( ( item ) => ( {
										label: item.label,
										value: item.value,
									} ) ) }
									onChange={ ( value ) => setAttributes( { profileUrl: value } ) }
									__next40pxDefaultSize={ true }
									__nextHasNoMarginBottom={ true }
								/>
							) }
						</PanelBody>

						<PanelBody
							title={ uiLabel( __( 'Period:', 'wp-ulike' ) ) }
							initialOpen={ false }
						>
							<SelectControl
								label={ uiLabel( __( 'Period:', 'wp-ulike' ) ) }
								value={ periodSelectValue }
								options={ periodSelectOptions }
								onChange={ onPeriodChange }
								__next40pxDefaultSize={ true }
								__nextHasNoMarginBottom={ true }
							/>

							{ periodMode === 'interval' && (
								<>
									<RangeControl
										label={ formatLastDaysLabel( intervalValue ) }
										value={ intervalValue }
										onChange={ ( value ) => setAttributes( { intervalValue: value } ) }
										min={ 1 }
										max={ 365 }
										__nextHasNoMarginBottom={ true }
									/>
									<SelectControl
										label={ uiLabel( __( 'day', 'wp-ulike' ) ) }
										value={ intervalUnit }
										options={ intervalUnitOptions }
										onChange={ ( value ) => setAttributes( { intervalUnit: value } ) }
										__next40pxDefaultSize={ true }
										__nextHasNoMarginBottom={ true }
									/>
								</>
							) }

							{ periodMode === 'range' && (
								<>
									<TextControl
										label={ uiLabel( __( 'Dates', 'wp-ulike' ) ) }
										type="date"
										value={ dateStart }
										onChange={ ( value ) => setAttributes( { dateStart: value } ) }
										__next40pxDefaultSize={ true }
										__nextHasNoMarginBottom={ true }
									/>
									<TextControl
										label={ uiLabel( __( 'Date Range', 'wp-ulike' ) ) }
										type="date"
										value={ dateEnd }
										onChange={ ( value ) => setAttributes( { dateEnd: value } ) }
										__next40pxDefaultSize={ true }
										__nextHasNoMarginBottom={ true }
									/>
								</>
							) }
						</PanelBody>

						{ showPostFilters && (
							<PanelBody
								title={ uiLabel( __( 'Show Filters', 'wp-ulike' ) ) }
								initialOpen={ false }
							>
								<FormTokenField
									label={ uiLabel( __( 'Select post types', 'wp-ulike' ) ) }
									value={ postTypeValue }
									suggestions={ postTypeSuggestions }
									onChange={ onPostTypesChange }
									__experimentalExpandOnFocus
									__nextHasNoMarginBottom
								/>
								{ contentType === 'post' && taxonomyOptions.length > 0 && (
									<>
										<SelectControl
											label={ uiLabel( __( 'Category', 'wp-ulike' ) ) }
											value={ taxonomy }
											options={ [
												{
													label: __( 'Select...', 'wp-ulike' ),
													value: '',
												},
												...taxonomyOptions,
											] }
											onChange={ ( value ) =>
												setAttributes( {
													taxonomy: value,
													taxonomyTerms: [],
												} )
											}
											__next40pxDefaultSize={ true }
											__nextHasNoMarginBottom={ true }
										/>
										{ taxonomy && (
											<FormTokenField
												label={ uiLabel( __( 'Select options...', 'wp-ulike' ) ) }
												value={ termValues }
												suggestions={ termSuggestions }
												onChange={ onTermsChange }
												__experimentalExpandOnFocus
												__nextHasNoMarginBottom
											/>
										) }
									</>
								) }
							</PanelBody>
						) }

						<PanelBody
							title={ uiLabel( __( 'Settings', 'wp-ulike' ) ) }
							initialOpen={ false }
						>
							<ToggleControl
								label={ uiLabel( __( 'Title:', 'wp-ulike' ) ) }
								help={ __( 'Show a heading above the list.', 'wp-ulike' ) }
								checked={ showHeading }
								onChange={ ( value ) => setAttributes( { showHeading: value } ) }
								__nextHasNoMarginBottom={ true }
							/>

							{ showHeading && (
								<TextControl
									label={ uiLabel( __( 'Customize', 'wp-ulike' ) ) }
									value={ heading }
									onChange={ ( value ) => setAttributes( { heading: value } ) }
									placeholder={ formatTopTypeLabel( contentType ) }
									__next40pxDefaultSize={ true }
									__nextHasNoMarginBottom={ true }
								/>
							) }

							<RangeControl
								label={ uiLabel( __( 'Title Trim (Length):', 'wp-ulike' ) ) }
								value={ titleTrim }
								onChange={ ( value ) => setAttributes( { titleTrim: value } ) }
								min={ 3 }
								max={ 30 }
								__nextHasNoMarginBottom={ true }
							/>

							<ToggleControl
								label={ uiLabel( __( 'Rank number', 'wp-ulike' ) ) }
								checked={ showRank }
								onChange={ ( value ) => setAttributes( { showRank: value } ) }
								__nextHasNoMarginBottom={ true }
							/>

							<ToggleControl
								label={ uiLabel( __( 'Activate Like Counter', 'wp-ulike' ) ) }
								checked={ showCount }
								onChange={ ( value ) => setAttributes( { showCount: value } ) }
								__nextHasNoMarginBottom={ true }
							/>

							{ showThumbnailControl && (
								<>
									<ToggleControl
										label={ uiLabel( __( 'Activate Thumbnail/Avatar', 'wp-ulike' ) ) }
										checked={ showThumbnail }
										onChange={ ( value ) => setAttributes( { showThumbnail: value } ) }
										__nextHasNoMarginBottom={ true }
									/>
									{ showThumbnail && (
										<RangeControl
											label={ uiLabel( __( 'Thumbnail/Avatar size:', 'wp-ulike' ) ) }
											value={ thumbnailSize }
											onChange={ ( value ) =>
												setAttributes( { thumbnailSize: value } )
											}
											min={ 24 }
											max={ 96 }
											__nextHasNoMarginBottom={ true }
										/>
									) }
								</>
							) }

							{ showEngagementExtras && (
								<ToggleControl
									label={ uiLabel( __( 'Engaged Users', 'wp-ulike' ) ) }
									checked={ showEngagedUsers }
									onChange={ ( value ) =>
										setAttributes( { showEngagedUsers: value } )
									}
									__nextHasNoMarginBottom={ true }
								/>
							) }
						</PanelBody>
					</InspectorControls>

					<div { ...blockProps }>
						<ServerSideRender
							block="wp-ulike/top-content"
							attributes={ attributes }
							LoadingResponsePlaceholder={ () => (
								<div className="wp-ulike-top-content-editor-loading">
									<Spinner />
									<span>{ __( 'Loading...', 'wp-ulike' ) }</span>
								</div>
							) }
							ErrorResponsePlaceholder={ () => (
								<p className="wp-ulike-top-content-editor-error">
									{ __( 'No data to display', 'wp-ulike' ) }
								</p>
							) }
						/>
					</div>
				</>
			);
		},
		save: () => null,
	} );
}
