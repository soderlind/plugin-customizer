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
			id: null,
			url: null
		}
	};

	/**
	 * Initialize functionality.
	 *
	 * @param {object} args Args.
	 * @param {string} args.url  Preview URL.
	 * @param {string} args.id  Panel ID.
	 * @returns {void}
	 */
	component.init = function init( args ) {
		_.extend( component.data, args );
		if ( ! args || ! args.url || ! args.id ) {
			throw new Error( 'Missing args' );
		}

		api.panel( args.id , function( panel ) {
			var previousUrl, clearPreviousUrl, previewUrlValue;
			previewUrlValue = api.previewer.previewUrl;
			clearPreviousUrl = function() {
				previousUrl = null;
			};

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
