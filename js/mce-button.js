(function() {
    tinymce.PluginManager.add('metadata', function( editor, url ) {
        var sh_tag = 'metadata';

        //add popup
        editor.addCommand('metadata_panel_popup', function(ui, v) {

            //setup defaults
            var  date = false;
            console.log(v.date);
            if (v.date)
                date = v.date;
            if (v.author)
                date = v.author;
            if (v.comments)
                date = v.comments;
            if (v.sticky)
                date = v.sticky;
            if (v.views)
                date = v.views;
            if (v.custom_fields)
                date = v.custom_fields;

            //open the popup
            editor.windowManager.open( {
                title: 'Display Post Metadata',
                body: [
                    {
                        type   : 'checkbox',
                        name   : 'date',
                        label  : 'Date',
                        text   : 'Yes',
                        checked : v.date,
                        tooltip: 'Display the date..'
                    },{
                        type   : 'checkbox',
                        name   : 'author',
                        label  : 'Author',
                        text   : 'Yes',
                        checked : v.author,
                        tooltip: 'Display the author.'
                    },{
                        type   : 'checkbox',
                        name   : 'comments',
                        label  : 'Comments',
                        text   : 'Yes',
                        checked : v.comments,
                        tooltip: 'Display the number of comments.'
                    },{
                        type   : 'checkbox',
                        name   : 'sticky',
                        label  : 'Sticky',
                        text   : 'Yes',
                        checked : v.sticky,
                        tooltip: 'Display sticky if a post/page is sticky.'
                    },{
                        type   : 'checkbox',
                        name   : 'views',
                        label  : 'Views',
                        text   : 'Yes',
                        checked : v.views,
                        tooltip: 'Display number of post/page views.'
                    },{
                        type   : 'checkbox',
                        name   : 'custom_fields',
                        label  : 'Custom Fields',
                        text   : 'Yes',
                        checked : v.custom_fields,
                        tooltip: 'Display custom fields of post/page.'
                    }
                ],
                onsubmit: function( e ) { //when the ok button is clicked

                    var counter = 0;

                    //start the shortcode tag
                    var shortcode_str = '[' + sh_tag + ' element="';

                    //check for date
                    if (typeof e.data.date != 'undefined' && e.data.date == true) {
                        shortcode_str += 'date';
                        counter++;
                    }

                    //check for author
                    if (typeof e.data.author != 'undefined' && e.data.author == true) {

                        if( counter > 0 )
                            shortcode_str += ',';

                        shortcode_str += 'author';
                        counter++;
                    }

                    //check for comments
                    if (typeof e.data.comments != 'undefined' && e.data.comments == true) {

                        if( counter > 0 )
                            shortcode_str += ',';

                        shortcode_str += 'comments';
                        counter++;
                    }

                    //check for sticky
                    if (typeof e.data.sticky != 'undefined' && e.data.sticky == true) {

                        if( counter > 0 )
                            shortcode_str += ',';

                        shortcode_str += 'sticky';
                        counter++;
                    }

                    //check for views
                    if (typeof e.data.views != 'undefined' && e.data.views == true) {

                        if( counter > 0 )
                            shortcode_str += ',';

                        shortcode_str += 'views';
                        counter++;
                    }

                    //check for custom fields
                    if (typeof e.data.custom_fields != 'undefined' && e.data.custom_fields == true) {

                        if( counter > 0 )
                            shortcode_str += ',';

                        shortcode_str += 'custom_fields';
                        // counter++;
                    }


                    shortcode_str += '" ]';

                    //insert shortcode to tinymce
                    editor.insertContent( shortcode_str);
                }
            });
        });

        //add button
        editor.addButton('metadata', {
            icon: 'metadata',
            tooltip: 'Display Metadata',
            onclick: function() {
                editor.execCommand('metadata_panel_popup','',{
                    date          : true,
                    author        : true,
                    comments      : true,
                    sticky        : true,
                    views         : true,
                    custom_fields : false
                });
            }
        });
    });
})();