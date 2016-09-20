<?php
/**
 * Code from https://github.com/xwp/wp-customizer-blank-slate
 *
 * Learn more at: https://make.xwp.co/2016/09/11/resetting-the-customizer-to-a-blank-slate/
 * Copyright (c) 2016 XWP (https://make.xwp.co/)
 */

class PluginCustomizerBlankSlate {

	protected static $_instance = null;


	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	function __construct() {
		add_filter( 'customize_loaded_components', function() {

			$priority = 1;
			add_action( 'wp_loaded', function() {

				global $wp_customize;
				remove_all_actions( 'customize_register' );
				$wp_customize->register_panel_type( 'WP_Customize_Panel' );
				$wp_customize->register_section_type( 'WP_Customize_Section' );
				$wp_customize->register_section_type( 'WP_Customize_Sidebar_Section' );
				$wp_customize->register_control_type( 'WP_Customize_Color_Control' );
				$wp_customize->register_control_type( 'WP_Customize_Media_Control' );
				$wp_customize->register_control_type( 'WP_Customize_Upload_Control' );
				$wp_customize->register_control_type( 'WP_Customize_Image_Control' );
				$wp_customize->register_control_type( 'WP_Customize_Background_Image_Control' );
				$wp_customize->register_control_type( 'WP_Customize_Cropped_Image_Control' );
				$wp_customize->register_control_type( 'WP_Customize_Site_Icon_Control' );
				$wp_customize->register_control_type( 'WP_Customize_Theme_Control' );
			}, $priority );
			$components = array();

			return $components;
		} );
		add_action( 'customize_controls_init', function() {
			global $wp_customize;
			$wp_customize->set_preview_url(
				add_query_arg(
					array( PLUGIN_CUSTOMIZER_PARAM_NAME => PLUGIN_CUSTOMIZER_PARAM_VALUE ),
					$wp_customize->get_preview_url()
				)
			);
		} );

		add_action( 'customize_controls_enqueue_scripts', function() {
			$handle = 'plugin-customizer-blank-slate';
			$src = plugins_url( 'js/plugin-customizer-blank-slate.js', __FILE__ );
			$deps = array( 'customize-controls' );
			$ver = false;
			$in_footer = true;
			wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );

			$args = array(
				'queryParamName' => PLUGIN_CUSTOMIZER_PARAM_NAME,
				'queryParamValue' => PLUGIN_CUSTOMIZER_PARAM_VALUE,
			);
			wp_add_inline_script(
				$handle,
				sprintf( 'PluginCustomizerBlankSlate.init( %s );', wp_json_encode( $args ) ),
				'after'
			);
		} );
	}
}
