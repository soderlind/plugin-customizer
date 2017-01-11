<?php

namespace PluginCustomizer;

define( 'PLUGIN_CUSTOMIZER_VERSION', '1.1.3' );

if ( ! class_exists( 'PluginCustomizer\Plugin_Customizer' ) ) {
	abstract class Plugin_Customizer implements Plugin_Customizer_Interface {

		private  $customize_register_priority = 9; // Priority must be between 2 and 9
		private  $slug;
		private  $org_theme;
		private  $plugin_name;
		private  $plugin_url;
		private  $plugin_root;
		private  $theme_name = 'twentysixteen';

		/**
		 * Singleton from: from http://stackoverflow.com/a/15870364/1434155
		 */
		private static $instances = array();
		protected function __construct() {}
		protected function __clone() {}
		public function __wakeup() {
			throw new Exception( 'Cannot unserialize singleton' );
		}

		public static function instance() {
			$class = get_called_class(); // late-static-bound class name
			if ( ! isset( self::$instances[ $class ] ) ) {
				self::$instances[ $class ] = new static();
			}
			return self::$instances[ $class ];
		}


		function init( $config = array() ) {

			if ( ! count( $config ) ) {
				wp_die( /*$message = '', $title = '', $args = array()*/ );
			}

			$config = wp_parse_args( $config, array(
				'name' => 'Plugin Customizer',
				'url'  => plugins_url( '', dirname( __FILE__ ) ),
				'path' => plugin_dir_path( dirname( __FILE__ ) ),
			) );

			$this->slug = sanitize_title( $config['name'] , 'plugin-customizer' );
			$this->plugin_name = $config['name'];
			$this->plugin_url  = $config['url'];
			$this->plugin_root = $config['path'];

			/**
			 * When saving the setinngs, the customizer settings must be visible for admin-ajax.php
			 * so add it before the bailout test.
			 */
			add_action( 'wp_loaded', function() {
				add_action( 'customize_register', array( $this, 'customizer_plugin_settings' ) );
			}, $this->customize_register_priority );
			/**
			 * Bailout if not called from the plugin menu links.
			 */
			if ( ! isset( $_GET[ $this->slug ] ) || 'on' !== wp_unslash( $_GET[ $this->slug ] ) ) {
				return;
			}
			/**
			 * Set the customizer title.
			 */
			add_filter( 'pre_option_blogname', function(){
				return $this->plugin_name;
			} );

			/**
			 * WordPres Customizer is dependant on functionality in the theme. Just in case
			 * the current theme doesn't support WordPress Customizer we'll use a theme
			 * that supports it.
			 */
			add_action( 'setup_theme', function() {
				add_filter( 'theme_root', array( $this, 'switch_theme_root_path' ) );
				add_filter( 'template_directory_uri', array( $this, 'switch_template_uri' ) );
				add_filter( 'stylesheet_uri', array( $this, 'switch_template_uri' ) );
				add_filter( 'pre_option_stylesheet', function(){
					return $this->theme_name;
				} );
				add_filter( 'pre_option_template', function(){
					return $this->theme_name;
				} );
			} );
			/**
			 * Remove all other panels and sections from the customizer.
			 */
			$blank_slate = Blank_Slate::instance();
			$blank_slate->init( $this->slug, $this->plugin_url, $this->plugin_root );

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
			}, $this->customize_register_priority );

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
				  sprintf( '%s/templates/(.*)$', $this->slug ) => sprintf( 'index.php?plugin-customizer-template-%s=%s', $this->slug, $wp_rewrite->preg_index( 1 ) ),
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
			$query_vars[] = sprintf( 'plugin-customizer-template-%s', $this->slug );
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
			if ( ! isset( $rules[ sprintf( '%s/templates/(.*)$', $this->slug ) ] ) ) { // must be the same rule as in rewrite_rule($wp_rewrite)
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
			$key = sprintf( 'plugin-customizer-template-%s', $this->slug );
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
		 * @param   string	$template plugin_root to the template
		 */
		private function _load_template( $template ) {
			// from: https://developer.wordpress.org/reference/functions/load_template/#comment-727
			if ( $overridden_template = locate_template( "plugin-customizer/{$template}" ) ) {
				/*
				 * locate_template() returns plugin_root to file.
				 * if either the child theme or the parent theme have overridden the template, load it.
				 */
				load_template( $overridden_template );
			} else {
				/*
				 * If neither the child nor parent theme have overridden the template,
				 * we load the template from the 'templates' sub-directory of the directory this file is in.
				 */
				load_template( $this->plugin_root . "templates/{$template}" );
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
			$src = $this->plugin_url . '/src/assets/js/plugin-customizer-preview-templates.js';
			$deps = array( 'customize-controls' );
			$version = PLUGIN_CUSTOMIZER_VERSION;
			$in_footer = 1;
			wp_enqueue_script( $this->slug, $src, $deps, $version , $in_footer );
		}

		abstract public function plugin_customizer_add_templates();

		public function template_url( $template ) {
			$template = basename( $template, '.php' );
			$template_url = home_url( sprintf( '%s/templates/%s/?%s=on', $this->slug, $template, $this->slug ) );

			return $template_url;
		}

		public function add_templates( $default_url, $section_urls = array() ) {
			wp_add_inline_script(
				$this->slug,
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
			$url = add_query_arg( $this->slug , 'on', $url );
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


		abstract public function customizer_plugin_sections( $wp_customize );
		abstract public function customizer_plugin_settings( $wp_customize );
		abstract public function customizer_plugin_controls( $wp_customize );

		// inspired by https://gist.github.com/franz-josef-kaiser/8608140
		public function switch_theme_root_path( $org_theme_root ) {
			$crrent_theme = wp_get_theme( $this->theme_name );
			// if theme exists, no point in changing theme root.
			if ( $crrent_theme->exists() ) {
				return $org_theme_root;
			}

			$new_theme_root = $this->plugin_root . 'src/assets';
			# Too early to use register_theme_directory()
			if ( ! in_array( $new_theme_root, $GLOBALS['wp_theme_directories'] ) ) {
				$GLOBALS['wp_theme_directories'][] = $new_theme_root;
			}

			return $new_theme_root;
		}

		public function switch_template_uri( $uri  ) {
			$new_theme_root_uri = $this->plugin_url . 'src/assets/' . $this->theme_name;
			return $new_theme_root_uri;
		}

		public function plugin_theme_name() {
			return $this->theme_name;
		}
	}
}
