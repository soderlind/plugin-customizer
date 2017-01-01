/**
 * Code from https://github.com/xwp/wp-customizer-blank-slate
 *
 * Learn more at: https://make.xwp.co/2016/09/11/resetting-the-customizer-to-a-blank-slate/
 * Copyright (c) 2016 XWP (https://make.xwp.co/)
 */
/* global wp, jQuery */
/* exported PluginCustomizer */
var PluginCustomizer = (function( api, $ ) {
	'use strict';

	var component = {
		data: {
			url: null
		}
	};

	// api.preview.bind( 'active', function() {
	// 	api.previewer.previewUrl.set( args.url );
	// });

	/**
	 * Initialize functionality.
	 *
	 * @param {object} args Args.
	 * @param {string} args.url  Preview URL.
	 * @param {string} args.name Section ID.
	 * @returns {void}
	 */
	component.init = function init( home, sections ) {
		_.extend( component.data, home );
		_.extend( {}, sections );
		if ( ! home || ! home.url  ) {
			throw new Error( 'Missing args' );
		}

		api.bind( 'ready', function(){
			api.previewer.previewUrl.set(home.url);
		});
		if ( sections ) {
			// console.log(sections);
			_.each( sections, function( url , name ) {
				api.section( name , function( section ) {
					var previousUrl, clearPreviousUrl, previewUrlValue;
					previewUrlValue = api.previewer.previewUrl;
					clearPreviousUrl = function() {
						previousUrl = null;
					};

					section.expanded.bind( function( isExpanded ) {
						// var url;
						if ( isExpanded ) {
							// url = args.url;
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
			});
		}


	};
	return component;
} ( wp.customize, jQuery ) );
