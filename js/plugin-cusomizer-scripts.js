(function ( api ) {
    api.section( 'form_title_section', function( section ) {
        var previousUrl, clearPreviousUrl, previewUrlValue;
        previewUrlValue = api.previewer.previewUrl;
        clearPreviousUrl = function() {
            previousUrl = null;
        };

        section.expanded.bind( function( isExpanded ) {
            var url;
            if ( isExpanded ) {
                // url = api.settings.url.home;
                url = 'http://customizer.dev/2016/09/11/hello-world/';
                // previousUrl = previewUrlValue.get();
                previousUrl = previewUrlValue.get();
                previewUrlValue.set( url );
                previewUrlValue.bind( clearPreviousUrl );
            } else {
                previewUrlValue.unbind( clearPreviousUrl );
                if ( previousUrl ) {
                    previewUrlValue.set( previousUrl );
                }
            }
        } );
    } );
} ( wp.customize ) );
