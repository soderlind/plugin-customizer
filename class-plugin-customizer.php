<?php

if ( ! class_exists( 'PluginCustomizer' ) ) {
	abstract class PluginCustomizer {

		private static $customize_register_priority = 9; // Priority must be between 2 and 9
		public static $id;
		private static $org_theme;
		private static $theme_name = 'twentysixteen';
		function __construct() {
			/**
			 * When saving the setinngs, the customizer settings must be visible for admin-ajax.php
			 * so add it before the bailout test.
			 */
			self::$id = sanitize_title( get_called_class() , 'plugin_customizer' );
			add_action( 'wp_loaded', function() {
				add_action( 'customize_register', array( $this, 'customizer_plugin_settings' ) );
			}, self::$customize_register_priority );
			/**
			 * Bailout if not called from the plugin menu links.
			 */
			if ( ! isset( $_GET[ self::$id ] ) || 'on' !== wp_unslash( $_GET[ self::$id ] ) ) {
				return;
			}
			/**
			 * Set the customizer title.
			 */
			add_filter( 'pre_option_blogname', function(){
				return $this->plugin_name();
			} );

			/**
			 * WordPres Customizer is dependant on functionality in the theme. Just in case
			 * the current theme doesn't support WordPress Customizer we'll use a theme
			 * that supports it.
			 */
			add_filter( 'theme_root', array( $this, 'switch_theme_root_plugin' ) );
			add_filter( 'theme_root_uri', array( $this, 'switch_theme_root_plugin' ) );
			add_filter( 'template', array( $this, 'plugin_theme_name' ) );
			add_filter( 'stylesheet', array( $this, 'plugin_theme_name' ) );

			add_filter( 'pre_option_current_theme', array( $this, 'plugin_theme_name' ) );

			add_filter( 'pre_option_stylesheet', array( $this, 'plugin_theme_name' ) );
			add_filter( 'pre_option_template', array( $this, 'plugin_theme_name' ) );
			// Handle custom theme roots.
			add_filter( 'pre_option_stylesheet_root', function() {
				return get_raw_theme_root( self::$theme_name, true );
			} );
			add_filter( 'pre_option_template_root', function() {
				return get_raw_theme_root( self::$theme_name, true );
			} );

			/**
			 * Remove all other panels and sections from the customizer.
			 */
			require_once( plugin_dir_path( __FILE__ ) . '/class-plugin-customizer-blank-slate.php' );
			PluginCustomizerBlankSlate::instance();

			/**
			 * Create preview template permalink rules.
			 * Also see plugin_customizer_configure_previewer(), template_loader() and js/plugin-customizer-preview-templates.js
			 *
			 * A 'how to create permalink' tutorial is at https://soderlind.no/wordpress-plugins-and-permalinks-how-to-use-pretty-links-in-your-plugin/
			 */
			add_filter( 'generate_rewrite_rules', array( $this, 'rewrite_rule' ) );
			add_filter( 'query_vars', array( $this, 'query_vars' ) );
			add_filter( 'admin_init', array( $this, 'flush_rewrite_rules' ) );

			/**
			 * Parse request from js/plugin-customizer-preview-templates.js previewer script and load the template.
			 */
			add_action( 'parse_request', array( $this, 'template_loader' ) );

			/**
			 * Enqueue previewer script.
			 *
			 * See https://make.xwp.co/2016/07/21/navigating-to-a-url-in-the-customizer-preview-when-a-section-is-expanded/
			 */
			add_action( 'customize_controls_enqueue_scripts', array( $this, 'plugin_customizer_configure_previewer' ) );
			add_action( 'customize_controls_enqueue_scripts', array( $this, 'plugin_customizer_add_templates' ), 11 );

			/**
			 * The plugin is using transport => postMessage, set in customizer_plugin_settings(), and needs
			 * javascript to update the preview.
			 *
			 * You can Learn more about postmessage at:
			 * https://developer.wordpress.org/themes/advanced-topics/customizer-api/#using-postmessage-for-improved-setting-previewing
			 */
			add_action( 'customize_preview_init', array( $this, 'plugin_customizer_previewer_postmessage_script' ) );
			/**
			 * Register plugin customizer settings.
			 */
			add_action( 'wp_loaded', function() {
				add_action( 'customize_register', array( $this, 'customizer_plugin_sections' ) );
				add_action( 'customize_register', array( $this, 'customizer_plugin_controls' ) );

				/**
				 * If you plan to use selective refresh, set the transport in customizer_plugin_settings()
				 * to 'refresh' and uncomment the line below. Also comment out the customize_preview_init action above.
				 *
				 * You can learn more about selective refresh at:
				 * https://make.wordpress.org/core/2016/02/16/selective-refresh-in-the-customizer/
				 */
				// add_action( 'customize_register', array( $this, 'customizer_plugin_selective_refresh' ) );
			}, self::$customize_register_priority );

		}

		/**
		 * Create rewrite rule for the template URL used by js/plugin-cusomizer-scripts.js
		 *
		 * See: https://soderlind.no/wordpress-plugins-and-permalinks-how-to-use-pretty-links-in-your-plugin/
		 *
		 * @author soderlind
		 * @version 1.0.0
		 * @param   Object	$wp_rewrite Permalink structure
		 * @return  Array				 Rewrite rules
		 */
		function rewrite_rule( $wp_rewrite ) {
			 $new_rules = array(
				  sprintf( '%s/templates/(.*)$', self::$id ) => sprintf( 'index.php?plugin-customizer-template-%s=%s', self::$id, $wp_rewrite->preg_index( 1 ) ),
			 );

			 $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
			 return $wp_rewrite->rules;
		}

		/**
		 * Add query variable for the rewrite rule used by the template URL
		 *
		 * See: https://soderlind.no/wordpress-plugins-and-permalinks-how-to-use-pretty-links-in-your-plugin/
		 *
		 * @author soderlind
		 * @version 1.0.0
		 * @param   Array	$query_vars All defined query variables.
		 * @return  Array				The updated array with query variables.
		 */
		function query_vars( $query_vars ) {
			$query_vars[] = sprintf( 'plugin-customizer-template-%s', self::$id );
			return $query_vars;
		}

		/**
		 * Flush the permalink structure.
		 *
		 * See: https://soderlind.no/wordpress-plugins-and-permalinks-how-to-use-pretty-links-in-your-plugin/
		 *
		 * @author soderlind
		 * @version 1.0.0
		 */
		function flush_rewrite_rules() {
			$rules = $GLOBALS['wp_rewrite']->wp_rewrite_rules();
			if ( ! isset( $rules[ sprintf( '%s/templates/(.*)$', self::$id ) ] ) ) { // must be the same rule as in rewrite_rule($wp_rewrite)
				global $wp_rewrite;
				$wp_rewrite->flush_rules();
			}
		}

		/**
		 * Create the preview.
		 *
		 * The WordPress customizer uses ajax to communicate with the preview. To load the needed
		 * scripts, wp_head() and wp_footer() must be added to the template.
		 *
		 * @author soderlind
		 * @version 1.0.0
		 */
		function template_loader( $wp_query ) {
			$key = sprintf( 'plugin-customizer-template-%s', self::$id );
			if ( isset( $wp_query->query_vars[ $key ] ) ) { // same as the first custom variable in query_vars( $query_vars )
				$template = $wp_query->query_vars[ $key ] . '.php';
				wp_head();
				$this->_load_template( $template );
				wp_footer();

				exit( 0 );
			}
		}

		/**
		 * Load the themplate and display the template if found
		 *
		 * Will try to locate the template in this order:
		 *	1) [child-theme]/plugin-customizer/
		 *	2) [parent-theme]/plugin-customizer/
		 *	3) [plugin directory]/templates/
		 *
		 * @author soderlind
		 * @version 1.0.0
		 * @param   string	$template Path to the template
		 */
		private function _load_template( $template ) {
			// from: https://developer.wordpress.org/reference/functions/load_template/#comment-727
			if ( $overridden_template = locate_template( "plugin-customizer/{$template}" ) ) {
				/*
				 * locate_template() returns path to file.
				 * if either the child theme or the parent theme have overridden the template, load it.
				 */
				load_template( $overridden_template );
			} else {
				/*
				 * If neither the child nor parent theme have overridden the template,
				 * we load the template from the 'templates' sub-directory of the directory this file is in.
				 */
				load_template( plugin_dir_path( __FILE__ ) . "/templates/{$template}" );
			}
		}
		/**
		 * Load the previewer script and pass the template URL as argument to the script.
		 * Optionally load separate templates for the customizer sections.
		 *
		 * @author soderlind
		 * @version 1.0.0
		 */
		public function plugin_customizer_configure_previewer() {
			$src = plugins_url( 'js/plugin-customizer-preview-templates.js' ,  __FILE__ );
			$deps = array( 'customize-controls' );
			$version = CLOUD2PNG_VERSION;
			$in_footer = 1;
			wp_enqueue_script( self::$id, $src, $deps, $version , $in_footer );
		}

		abstract public function plugin_customizer_add_templates();

		public function template_url( $template ) {
			$template = basename( $template, '.php' );
			$template_url = home_url( sprintf( '%s/templates/%s/?%s=on', self::$id, $template, self::$id ) );

			return $template_url;
		}

		public function add_templates( $default_url, $section_urls = array() ) {
			wp_add_inline_script(
				self::$id,
				sprintf( 'PluginCustomizer.init( %s, %s );',
					wp_json_encode( $default_url ),
					wp_json_encode( $section_urls )
				),
				'after'
			);
		}

		/**
		 * Load the preview script. The script is needed sice the transport is postmessage
		 * @author soderlind
		 * @version 1.0.0
		 */
		abstract public function plugin_customizer_previewer_postmessage_script();

		/**
		 * Create the customizer URL.
		 *
		 * @author soderlind
		 * @version 1.0.0
		 * @param   string    $return_url        The URL to return to when closing the customizer.
		 * @param   string    $autofocus_section Used for deep-linking, i.e. this section will be selected
		 *                                       when the customizer is opened.
		 * @return  string                       The customizer URL.
		 */
		protected function get_customizer_url( $return_url = '', $autofocus_section = '' ) {
			// If no return url, set the return url to the main admin url.
			if ( '' == $return_url ) {
				$return_url = admin_url();
			}
			$url = wp_customize_url();
			// Add parameter to identify this as a customizer url for this plugin.
			$url = add_query_arg( self::$id , 'on', $url );
			// If autofocus, add parameter to url.
			if ( '' !== $autofocus_section ) {
				$url = add_query_arg( 'autofocus[section]', $autofocus_section, $url );
			}
			//Add parameter for return url.
			$url = add_query_arg( 'return', urlencode( $return_url ), $url );
			//Sanitize url.
			$url = esc_url_raw( $url );
			return $url;
		}


		public function plugin_name() {
			return 'Plugin Customizer';
		}

		abstract public function customizer_plugin_sections( $wp_customize );
		abstract public function customizer_plugin_settings( $wp_customize );
		abstract public function customizer_plugin_controls( $wp_customize );

		// inspired by https://gist.github.com/franz-josef-kaiser/8608140
		public function switch_theme_root_plugin( $org_root ) {
			$crrent_theme = wp_get_theme( self::$theme_name );
			// if theme exists, no point in changing theme root.
			if ( $crrent_theme->exists() ) {
				return $org_root;
			}

			if ( 'theme_root_uri' === current_filter() ) {
				remove_filter( current_filter(), array( $this, __FUNCTION__ ) );
				$new_theme_root_uri = plugins_url( '/theme', __FILE__ );
				return $new_theme_root_uri;
			}
			// If we made it so far we are in the 'theme_root' filter.
			$new_theme_root = plugin_dir_path( __FILE__ ) . 'theme';
			# Too early to use register_theme_directory()
			if ( ! in_array( $new_theme_root, $GLOBALS['wp_theme_directories'] ) ) {
				$GLOBALS['wp_theme_directories'][] = $new_theme_root;
			}
			remove_filter( current_filter(), array( $this, __FUNCTION__ ) );

			return $new_theme_root;
		}

		public function plugin_theme_name() {
			return self::$theme_name;
		}
	}
}
