/* global wp, jQuery */
/* exported PluginCustomizer */

var PluginCustomizer = (function( api, $ ) {
	'use strict';

	var component = {
		data: {
			panel: null,
			url: null
		}
	};

	/**
	 * Initialize functionality.
	 *
	 * @param {object} args Args.
	 * @param {string} args.url  Preview URL.
	 * @param {string} args.panel  Section ID.
	 * @returns {void}
	 */
	component.init = function init( args ) {
		_.extend( component.data, args );
		if ( ! args || ! args.url || ! args.panel) {
			throw new Error( 'Missing args' );
		}

		api.panel( args.panel , function( panel ) {
			var previousUrl, clearPreviousUrl, previewUrlValue;
			previewUrlValue = api.previewer.previewUrl;
			clearPreviousUrl = function() {
				previousUrl = null;
			};

			console.log(args.url);

			panel.expanded.bind( function( isExpanded ) {
				var url;
				if ( isExpanded ) {
					url = args.url;
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
	};
	return component;
} ( wp.customize, jQuery ) );
