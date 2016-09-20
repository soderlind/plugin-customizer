<?php
/*
Plugin Name: Plugin Customizer
Version: 1.0.0
Plugin URI:
Description:
Author: Per Soderlind
Author URI: https://soderlind.no

Does:

1) Add admin menu entries that shows you how to access the customiser from:
- Root menu
- Submenu
- Admin bar menu
- Option Page
2) Only load the Plugin Customizer when the menu entries above are selected.
3) Remove all other panels and sections from the customizer
4) Add preview template
5) Add sample customizer panels and settings, settings are saved as options.

*/

define( 'PLUGIN_CUSTOMIZER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PLUGIN_CUSTOMIZER_VERSION', '1.0.0' );

const PLUGIN_CUSTOMIZER_PARAM_NAME = 'plugin_customizer';
const PLUGIN_CUSTOMIZER_PARAM_VALUE = 'on';

require_once( PLUGIN_CUSTOMIZER_PLUGIN_DIR . '/class-plugin-customizer-blank-slate.php' );


class PluginCustomizer {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	function __construct() {
		/**
		 * Add admin menu, You'll most likely only need one of them.
		 */
		// root menu
		add_action( 'admin_menu', array( $this, 'register_root_menu' ) );
		add_action( 'admin_init', array( $this, 'root_menu_redirect_to_customizer' ) , 1 );
		// submenu
		add_action( 'admin_menu', array( $this, 'register_sub_menu' ) );
		// admin bar
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_customizer_url' ), 500 );
		// admin options page
		add_action( 'admin_menu', array( $this, 'add_option_page' ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );
		/**
		 * Parse request from previewer script and load template
		 */
		add_action( 'parse_request', array( $this, 'template_loader' ) );

		/**
		 * Only display the plugin customizer when call from the plugin menu links.
		 */
		if ( ! isset( $_GET[ PLUGIN_CUSTOMIZER_PARAM_NAME ] ) || PLUGIN_CUSTOMIZER_PARAM_VALUE !== wp_unslash( $_GET[ PLUGIN_CUSTOMIZER_PARAM_NAME ] ) ) {
			return;
		}

		/**
		 * Remove all other panels and sections from customizer
		 *
		 * See https://make.xwp.co/2016/09/11/resetting-the-customizer-to-a-blank-slate/ for how this is done
		 */
		PluginCustomizerBlankSlate::instance();

		/**
		 * Create preview template custom link.
		 *
		 * A custom permalink tutorial is at https://soderlind.no/wordpress-plugins-and-permalinks-how-to-use-pretty-links-in-your-plugin/
		 */
		add_filter( 'generate_rewrite_rules', array( $this, 'rewrite_rule' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_filter( 'admin_init', array( $this, 'flush_rewrite_rules' ) );

		/**
		 * Enqueue previewer script
		 *
		 * See https://make.xwp.co/2016/07/21/navigating-to-a-url-in-the-customizer-preview-when-a-section-is-expanded/
		 */
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'plugin_cusomizer_previewer_script' ) );

		/**
		 * Register plugin customizer settings.
		 *
		 * Priority must be 9
		 */
		add_action( 'wp_loaded', function() {
				add_action( 'customize_register', array( $this, 'customize_plugin' ) );
		}, 9 );
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
			  'plugin-customizer/templates/(.*)$' => sprintf( 'index.php?plugin-customizer-template=%s',$wp_rewrite->preg_index( 1 ) ),
		 );

		 $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
		 return $wp_rewrite->rules;
	}

	/**
	 * Add query variable for the rewrite rule  used by the template URL
	 *
	 * See: https://soderlind.no/wordpress-plugins-and-permalinks-how-to-use-pretty-links-in-your-plugin/
	 *
	 * @author soderlind
	 * @version 1.0.0
	 * @param   Array	$query_vars All defined query variables.
	 * @return  Array				The updated array with query variables.
	 */
	function query_vars( $query_vars ) {
		$query_vars[] = 'plugin-customizer-template';
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
		if ( ! isset( $rules['plugin-customizer/templates/(.*)$'] ) ) { // must be the same rule as in rewrite_rule($wp_rewrite)
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}
	}

	/**
	 * [template_loader description]
	 * @author soderlind
	 * @version 1.0.0
	 * @param   Object	$wp_query permalink structure.
	 */
	function template_loader( $wp_query ) {
		if ( isset( $wp_query->query_vars['plugin-customizer-template'] ) ) { // same as the first custom variable in query_vars( $query_vars )
			$template = $wp_query->query_vars['plugin-customizer-template'] . '.php';

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
			 * if either the child theme or the parent theme have overridden the template.
			 */
			load_template( $overridden_template );
		} else {
			/*
			 * If neither the child nor parent theme have overridden the template,
			 * we load the template from the 'templates' sub-directory of the directory this file is in.
			 */
			load_template( PLUGIN_CUSTOMIZER_PLUGIN_DIR . "/templates/{$template}" );
		}
	}

	/**
	 * Load the previewer script and pass the panel id and template URL as arguments to the script.
	 *
	 * @author soderlind
	 * @version 1.0.0
	 */
	public function plugin_cusomizer_previewer_script() {
		$handle = 'plugin-cusomizer';
		$src = plugins_url( 'js/plugin-cusomizer-scripts.js' ,  __FILE__ );
		$deps = array( 'customize-controls' );
		$version = rand();
		$in_footer = 1;
		wp_enqueue_script( $handle, $src, $deps, $version , $in_footer );
		/**
		 * Parameters sent to the previewer script is:
		 *  id 		The panel id
		 *  url		The custom permalink to the template. The last part, newsletter in the exmple below
		 *  		will translate to newsletter.php in the templates folder.
		 */
		$args = array(
			'id'  => 'plugin_settings_panel',
			'url' => home_url( 'plugin-customizer/templates/newsletter/' ),
		);
		wp_add_inline_script(
			$handle,
			sprintf( 'PluginCustomizer.init( %s );', wp_json_encode( $args ) ),
			'after'
		);
	}

	/**
	 * Create top level (root) menu. Will create URL with parameter page=redirect-customizer
	 *
	 * @author soderlind
	 * @version 1.0.0
	 */
	public function register_root_menu() {
		add_menu_page(
			__( 'Plugin Customizer', 'plugin-customizer' ),
			__( 'Plugin Customizer', 'plugin-customizer' ),
			'manage_options',
			'redirect-customizer',
			'function'
		);
	}

	/**
	 * Redirect to customizer url
	 *
	 * See http://wordpress.stackexchange.com/a/175574/14546
	 * @author soderlind
	 * @version 1.0.0
	 */
	function root_menu_redirect_to_customizer() {
		$menu_redirect = isset( $_GET['page'] ) ? $_GET['page'] : false;

		if ( 'redirect-customizer' == $menu_redirect ) {
			wp_safe_redirect( $this->_get_customizer_url( admin_url(), 'plugin_settings_panel' ) );
			exit();
		}
	}

	/**
	 * [register_sub_menu description]
	 * @author soderlind
	 * @version [version]
	 * @return  [type]	[description]
	 */
	public function register_sub_menu() {
		add_options_page(
			__( 'Plugin Customizer', 'plugin-customizer' ),
			__( 'Plugin Customizer', 'plugin-customizer' ),
			'manage_options',
			'plugin-template',
			'__return_null'
		);
		$this->add_sub_menu_customizer_url();
	}

	/**
	 * [add_sub_menu_customizer_url description]
	 *
	 * See http://wordpress.stackexchange.com/a/131214/14546
	 *
	 * @author soderlind
	 * @version [version]
	 * @param   string	$parent [description]
	 */
	function add_sub_menu_customizer_url( $parent = 'options-general.php' ) {
		global $submenu;

		if ( ! isset( $submenu[ $parent ] ) ) {
			return;
		}
		foreach ( $submenu[ $parent ] as $k => $d ) {
			if ( 'plugin-template' == $d['2'] ) {
				$submenu[ $parent ][ $k ]['2'] = $this->_get_customizer_url( $parent, 'plugin_settings_panel' );
				break;
			}
		}
	}

	/**
	 * [add_admin_bar_customizer_url description]
	 * @author soderlind
	 * @version [version]
	 * @param   [type]	$wp_admin_bar [description]
	 */
	function add_admin_bar_customizer_url( $wp_admin_bar ) {
		global $post;
		$return_url = ( is_admin() ) ? $this->_get_current_admin_page_url() :  get_permalink( $post->ID );

		$args = array(
			'id' => 'plugin-customizer-link',
			'title' => __( 'Plugin Customizer', 'plugin-customizer' ),
			'href' => $this->_get_customizer_url( $return_url, 'plugin_settings_panel' ),
		);

		$wp_admin_bar->add_node( $args );
	}

	// from https://core.trac.wordpress.org/ticket/27888
	private function _get_current_admin_page_url() {
		if ( ! is_admin() ) {
			return false;
		}
		global $pagenow;

		$url = $pagenow;
		$query_string = $_SERVER['QUERY_STRING'];

		if ( ! empty( $query_string ) ) {
			$url .= '?' . $query_string;
		}
		return $url;
	}

	public function add_option_page() {
		add_menu_page( 'Plugin Customizer Options', 'Plugin Customizer Options', 'manage_options', 'plugin-customizer', array( $this, 'options_page' ) );
	}

	public function settings_init() {
		register_setting( 'pluginPage', 'settings' );
		add_settings_section( 'pluginPage_section', __( 'Demonstrate how to load the customizer from an option page', 'plugin-customizer' ), '', 'pluginPage' );
		add_settings_field( 'option_page_customizer', __( 'Open customizer', 'plugin-customizer' ), array( $this, 'option_page_customizer_link' ), 'pluginPage', 'pluginPage_section' );
	}

	public function option_page_customizer_link() {
		printf( '<a href="%s">%s</a>', $this->_get_customizer_url( 'admin.php?page=plugin-customizer', 'plugin_settings_panel' ), __( 'Customize', 'plugin-customizer' ) );
	}

	public function options_page() {
		?>
		<form action='options.php' method='post'>
			<h2>Plugin Customizer</h2>
			<?php

			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );
			submit_button();
			?>
		</form>
		<?php
	}


	/**
	 * [_get_customizer_url description]
	 * @author soderlind
	 * @version [version]
	 * @param   string	$return_url [description]
	 * @return  [type]			    [description]
	 */
	private function _get_customizer_url( $return_url = '', $autofocus_panel = '' ) {
		if ( '' == $return_url ) {
			$return_url = admin_url();
		}
		$url = wp_customize_url();

		$url = add_query_arg( PLUGIN_CUSTOMIZER_PARAM_NAME , PLUGIN_CUSTOMIZER_PARAM_VALUE, $url );
		//autofocus
		if ( '' !== $autofocus_panel ) {
			$url = add_query_arg( 'autofocus[panel]', $autofocus_panel, $url );
		}
		//return url from customizer
		$url = add_query_arg( 'return', urlencode( $return_url ), $url );

		$url = esc_url_raw( $url );
		return $url;
	}


	/**
	 * [customize_plugin description]
	 *
	 * @author soderlind
	 * @version [version]
	 * @param   WP_Customize_Manager $wp_customize [description]
	 * @return  [type]							 [description]
	 */
	public function customize_plugin( WP_Customize_Manager $wp_customize ) {

		$wp_customize->add_panel( 'plugin_settings_panel', array(
			'title'					=> __( 'Plugin Customizer', 'plugin-customizer' ),
			'description'			=> __( 'Change Your Plugin Settings', 'plugin-customizer' ),
			'priority'				=> 30,
			)
		);

		$wp_customize->add_section(
			'form_title_section',
			array(
			'title'			=> __( 'Logo', 'plugin-customizer' ),
			'description'	=> __( 'Customize Your Logo Section', 'plugin-customizer' ),
			'priority'		=> 5,
			'panel'			=> 'plugin_settings_panel',
			)
		);

		$wp_customize->add_setting(
			'plugin_settings[form_title]',
			array(
			'type'			=> 'option',
			'capability'	=> 'edit_theme_options',
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Image_Control( $wp_customize,
				'form_title',
				array(
				'label'		=> __( 'Logo Image:', 'plugin-customizer' ),
				'section'	=> 'form_title_section',
				'priority'	=> 5,
				'settings'	=> 'plugin_settings[form_title]',
				)
			)
		);
	}
}

PluginCustomizer::instance();
