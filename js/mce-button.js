/**
 * Display Post Metadata — TinyMCE Plugin
 *
 * Adds a toolbar button that opens a dialog for inserting the [metadata]
 * shortcode with selected elements and design variation.
 */
( function () {

	tinymce.PluginManager.add( 'metadata', function ( editor ) {

		var sh_tag = 'metadata';

		/**
		 * Build the shortcode string from dialog form data.
		 *
		 * @param {object} data  Form field values from TinyMCE dialog.
		 * @returns {string}
		 */
		function buildShortcode( data ) {
			var elements = [];

			var elementKeys = [
				'date', 'author', 'comments', 'sticky', 'views',
				'reading_time', 'modified', 'categories', 'tags', 'custom_fields'
			];

			elementKeys.forEach( function ( key ) {
				if ( data[ key ] ) {
					elements.push( key );
				}
			} );

			if ( elements.length === 0 ) {
				return '';
			}

			var sc = '[' + sh_tag + ' element="' + elements.join( ',' ) + '"';

			if ( data.design && data.design !== 'default' ) {
				sc += ' design="' + data.design + '"';
			}

			sc += ']';
			return sc;
		}

		// Register the popup command.
		editor.addCommand( 'metadata_panel_popup', function ( ui, v ) {

			editor.windowManager.open( {
				title: 'Display Post Metadata',
				width: 380,
				height: 'auto',
				body: [
					// --- Elements heading ---
					{
						type: 'container',
						html: '<p style="margin:8px 0 4px;font-weight:600;border-bottom:1px solid #eee;padding-bottom:6px;">Elements to display</p>'
					},
					{
						type   : 'checkbox',
						name   : 'date',
						label  : 'Date',
						text   : 'Yes',
						checked: v.date,
						tooltip: 'Display the publication date.'
					},
					{
						type   : 'checkbox',
						name   : 'author',
						label  : 'Author',
						text   : 'Yes',
						checked: v.author,
						tooltip: 'Display the post author.'
					},
					{
						type   : 'checkbox',
						name   : 'comments',
						label  : 'Comments',
						text   : 'Yes',
						checked: v.comments,
						tooltip: 'Display the comment count.'
					},
					{
						type   : 'checkbox',
						name   : 'sticky',
						label  : 'Sticky',
						text   : 'Yes',
						checked: v.sticky,
						tooltip: 'Show a sticky badge (only appears on sticky posts).'
					},
					{
						type   : 'checkbox',
						name   : 'views',
						label  : 'Views',
						text   : 'Yes',
						checked: v.views,
						tooltip: 'Display the post view count.'
					},
					{
						type   : 'checkbox',
						name   : 'reading_time',
						label  : 'Reading Time',
						text   : 'Yes',
						checked: v.reading_time,
						tooltip: 'Show the estimated reading time.'
					},
					{
						type   : 'checkbox',
						name   : 'modified',
						label  : 'Modified Date',
						text   : 'Yes',
						checked: v.modified,
						tooltip: 'Show the last modified date (only if different from publish date).'
					},
					{
						type   : 'checkbox',
						name   : 'categories',
						label  : 'Categories',
						text   : 'Yes',
						checked: v.categories,
						tooltip: 'List the post categories.'
					},
					{
						type   : 'checkbox',
						name   : 'tags',
						label  : 'Tags',
						text   : 'Yes',
						checked: v.tags,
						tooltip: 'List the post tags.'
					},
					{
						type   : 'checkbox',
						name   : 'custom_fields',
						label  : 'Custom Fields',
						text   : 'Yes',
						checked: v.custom_fields,
						tooltip: 'Display all non-protected custom meta fields.'
					},
					// --- Design heading ---
					{
						type: 'container',
						html: '<p style="margin:12px 0 4px;font-weight:600;border-bottom:1px solid #eee;padding-bottom:6px;">Design variation</p>'
					},
					{
						type  : 'listbox',
						name  : 'design',
						label : 'Design',
						value : v.design || 'default',
						values: [
							{ text: 'Default (horizontal bordered)', value: 'default'  },
							{ text: 'Inline (byline style)',          value: 'inline'   },
							{ text: 'Card (vertical with shadow)',    value: 'card'     },
							{ text: 'Minimal (pill badges)',          value: 'minimal'  }
						]
					}
				],
				onsubmit: function ( e ) {
					var sc = buildShortcode( e.data );
					if ( sc ) {
						editor.insertContent( sc );
					}
				}
			} );
		} );

		// Add the toolbar button.
		editor.addButton( 'metadata', {
			icon   : 'metadata',
			tooltip: 'Display Metadata',
			onclick: function () {
				editor.execCommand( 'metadata_panel_popup', '', {
					date        : true,
					author      : true,
					comments    : true,
					sticky      : false,
					views       : false,
					reading_time: false,
					modified    : false,
					categories  : false,
					tags        : false,
					custom_fields: false,
					design      : 'default'
				} );
			}
		} );

	} );

} )();
