/**
 * Display Post Metadata — Gutenberg Block (no JSX / no build step)
 *
 * Registers the 'display-post-metadata/metadata' block.
 * Uses ServerSideRender for the editor preview so the output always
 * matches the frontend exactly.
 */
( function () {

	var el                = wp.element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var ServerSideRender  = wp.serverSideRender;
	var __                = wp.i18n.__;

	var PanelBody      = wp.components.PanelBody;
	var ToggleControl  = wp.components.ToggleControl;
	var SelectControl  = wp.components.SelectControl;
	var ColorPicker    = wp.components.ColorPicker;
	var RangeControl   = wp.components.RangeControl;
	var PanelColorSettings = wp.blockEditor.PanelColorSettings;

	// -----------------------------------------------------------------------
	// Helper — wraps a ColorPicker inside a labelled container
	// -----------------------------------------------------------------------
	function LabeledColorPicker( props ) {
		return el(
			'div',
			{ style: { marginBottom: '16px' } },
			el( 'p', { style: { margin: '0 0 6px', fontWeight: 600, fontSize: '12px' } }, props.label ),
			el( ColorPicker, {
				color:            props.value,
				onChangeComplete: function ( c ) { props.onChange( c.hex ); },
				disableAlpha:     true
			} )
		);
	}

	// -----------------------------------------------------------------------
	// Block definition
	// -----------------------------------------------------------------------
	registerBlockType( 'display-post-metadata/metadata', {

		title:       __( 'Post Metadata', 'display-post-metadata' ),
		description: __( 'Display post metadata — date, author, comments, views, reading time, categories, tags, and more.', 'display-post-metadata' ),
		category:    'common',
		icon:        'tag',
		keywords:    [ __( 'metadata' ), __( 'post info' ), __( 'date' ), __( 'author' ) ],

		// ------------------------------------------------------------------
		// Attributes — mirror the PHP block registration in block.php
		// ------------------------------------------------------------------
		attributes: {
			showDate        : { type: 'boolean', default: true  },
			showAuthor      : { type: 'boolean', default: true  },
			showComments    : { type: 'boolean', default: true  },
			showSticky      : { type: 'boolean', default: false },
			showViews       : { type: 'boolean', default: false },
			showReadingTime : { type: 'boolean', default: false },
			showModified    : { type: 'boolean', default: false },
			showCategories  : { type: 'boolean', default: false },
			showTags        : { type: 'boolean', default: false },
			showCustomFields: { type: 'boolean', default: false },
			design          : { type: 'string',  default: 'default' },
			textColor       : { type: 'string',  default: '' },
			bgColor         : { type: 'string',  default: '' },
			borderColor     : { type: 'string',  default: '' },
			iconColor       : { type: 'string',  default: '' },
			fontSize        : { type: 'integer', default: 0 },
			iconSize        : { type: 'integer', default: 0 },
			borderRadius    : { type: 'integer', default: 0 }
		},

		// ------------------------------------------------------------------
		// Edit — block editor UI
		// ------------------------------------------------------------------
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			var elementToggles = [
				{ attr: 'showDate',         label: __( 'Date',          'display-post-metadata' ) },
				{ attr: 'showAuthor',       label: __( 'Author',        'display-post-metadata' ) },
				{ attr: 'showComments',     label: __( 'Comments',      'display-post-metadata' ) },
				{ attr: 'showSticky',       label: __( 'Sticky',        'display-post-metadata' ) },
				{ attr: 'showViews',        label: __( 'Views',         'display-post-metadata' ) },
				{ attr: 'showReadingTime',  label: __( 'Reading Time',  'display-post-metadata' ) },
				{ attr: 'showModified',     label: __( 'Modified Date', 'display-post-metadata' ) },
				{ attr: 'showCategories',   label: __( 'Categories',    'display-post-metadata' ) },
				{ attr: 'showTags',         label: __( 'Tags',          'display-post-metadata' ) },
				{ attr: 'showCustomFields', label: __( 'Custom Fields', 'display-post-metadata' ) }
			];

			// Build inspector controls
			var inspector = el(
				InspectorControls,
				{},

				// Elements panel
				el(
					PanelBody,
					{ title: __( 'Elements', 'display-post-metadata' ), initialOpen: true },
					elementToggles.map( function ( item ) {
						return el( ToggleControl, {
							key:      item.attr,
							label:    item.label,
							checked:  attributes[ item.attr ],
							onChange: function ( val ) {
								var update = {};
								update[ item.attr ] = val;
								setAttributes( update );
							}
						} );
					} )
				),

				// Design panel
				el(
					PanelBody,
					{ title: __( 'Design', 'display-post-metadata' ), initialOpen: false },
					el( SelectControl, {
						label:    __( 'Variation', 'display-post-metadata' ),
						value:    attributes.design,
						options: [
							{ label: __( 'Default (horizontal bordered)', 'display-post-metadata' ), value: 'default'  },
							{ label: __( 'Inline (byline style)',          'display-post-metadata' ), value: 'inline'   },
							{ label: __( 'Card (vertical with shadow)',    'display-post-metadata' ), value: 'card'     },
							{ label: __( 'Minimal (pill badges)',          'display-post-metadata' ), value: 'minimal'  }
						],
						onChange: function ( val ) { setAttributes( { design: val } ); }
					} )
				),

				// Style panel
				el(
					PanelBody,
					{ title: __( 'Style Overrides', 'display-post-metadata' ), initialOpen: false },

					el( RangeControl, {
						label:    __( 'Font Size (px)', 'display-post-metadata' ),
						value:    attributes.fontSize || 15,
						min:      10,
						max:      32,
						onChange: function ( val ) { setAttributes( { fontSize: val } ); }
					} ),

					el( RangeControl, {
						label:    __( 'Icon Size (px)', 'display-post-metadata' ),
						value:    attributes.iconSize || 18,
						min:      12,
						max:      48,
						onChange: function ( val ) { setAttributes( { iconSize: val } ); }
					} ),

					el( RangeControl, {
						label:    __( 'Border Radius (px)', 'display-post-metadata' ),
						value:    attributes.borderRadius || 0,
						min:      0,
						max:      50,
						onChange: function ( val ) { setAttributes( { borderRadius: val } ); }
					} ),

					el( LabeledColorPicker, {
						label:    __( 'Text Color', 'display-post-metadata' ),
						value:    attributes.textColor,
						onChange: function ( c ) { setAttributes( { textColor: c } ); }
					} ),

					el( LabeledColorPicker, {
						label:    __( 'Icon Color', 'display-post-metadata' ),
						value:    attributes.iconColor,
						onChange: function ( c ) { setAttributes( { iconColor: c } ); }
					} ),

					el( LabeledColorPicker, {
						label:    __( 'Background Color', 'display-post-metadata' ),
						value:    attributes.bgColor,
						onChange: function ( c ) { setAttributes( { bgColor: c } ); }
					} ),

					el( LabeledColorPicker, {
						label:    __( 'Border Color', 'display-post-metadata' ),
						value:    attributes.borderColor,
						onChange: function ( c ) { setAttributes( { borderColor: c } ); }
					} )
				)
			);

			// Server-side preview
			var preview = el( ServerSideRender, {
				block:      'display-post-metadata/metadata',
				attributes: attributes
			} );

			return [ inspector, preview ];
		},

		// ------------------------------------------------------------------
		// Save — null because output is fully server-side rendered
		// ------------------------------------------------------------------
		save: function () {
			return null;
		}

	} );

} )();
