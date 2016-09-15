jQuery(window).load(function(){

	console.log('hepp');
	jQuery('#accordion-section-form_title_section').on( 'click', function( e ) {
		var previousPreviewedUrl = wp.customize.previewer.previewUrl.get();
		console.log( previousPreviewedUrl );
	        if ( previousPreviewedUrl === this.href ) {
	                // URL is already being previewed, so do nothing.
	                e.preventDefault();
	                return;
	        }
		console.log( previousPreviewedUrl );
	    // Hard coded URL for testing purposes
	    // wp.customize.previewer.previewUrl.set('http://customizer.dev/wp-content/plugins/plugin-customizer/form-template.php');
	    wp.customize.previewer.previewUrl.set('http://customizer.dev/wp-admin/customize.php?preview=true&customizer_blank_slate=on&url=http://customizer.dev/wp-content/plugins/plugin-customizer/form-template.php');
	    // wp.customize.previewer.previewUrl.set( this.href );
	    // wp.customize.previewer.refresh();

		if ( previousPreviewedUrl !== wp.customize.previewer.previewUrl.get() ) {
			/*
			 * URL can successfully be previewed (and will now be),
			 * so don't let URL open in new window.
			 */
			e.preventDefault();
			return;
		}
	});
});
