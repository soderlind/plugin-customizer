<?php
/*
Plugin Name: Plugin Customizer
Version: 1.0.0
Plugin URI:
Description: Demonstrate how to use the WordPress customizer to set options in a plugin.
Author: Per Soderlind
Author URI: https://soderlind.no

It does:

1) Add admin menu entries that shows you how to access the customiser from. You'll most likely only need one of them:
- Root menu
- Submenu
- Admin bar menu
- Option Page
2) Only load the Plugin Customizer when the menu entries above are selected.
3) Remove all other panels and sections from the customizer
4) Add preview template
5) Add sample customizer settings, settings are saved as options.

*/

define( 'PLUGIN_CUSTOMIZER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
// define( 'PLUGIN_CUSTOMIZER_VERSION', '1.0.0' );
define( 'PLUGIN_CUSTOMIZER_VERSION', rand() );

const PLUGIN_CUSTOMIZER_PARAM_NAME = 'plugin_customizer';
const PLUGIN_CUSTOMIZER_PARAM_VALUE = 'on';


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
		 * When saving the setinngs, the customizer settings must be visible for admin-ajax.php
		 * so add it before the bailout test.
		 */
		$customize_register_priority = 9; // Priority must be between 2 and 9
		add_action( 'wp_loaded', function() {
			add_action( 'customize_register', array( $this, 'customizer_plugin_settings' ) );
		}, $customize_register_priority );
		/**
		 * Bailout if not called from the plugin menu links.
		 */
		if ( ! isset( $_GET[ PLUGIN_CUSTOMIZER_PARAM_NAME ] ) || PLUGIN_CUSTOMIZER_PARAM_VALUE !== wp_unslash( $_GET[ PLUGIN_CUSTOMIZER_PARAM_NAME ] ) ) {
			return;
		}
		/**
		 * Set the customizer title.
		 */
		add_filter( 'pre_option_blogname', function(){
			return 'Plugin Customizer';
		} );

		/**
		 * Remove all other panels and sections from the customizer.
		 */
		require_once( PLUGIN_CUSTOMIZER_PLUGIN_DIR . '/class-plugin-customizer-blank-slate.php' );
		PluginCustomizerBlankSlate::instance();

		/**
		 * Create preview template permalink rules.
		 * Also see plugin_cusomizer_previewer_script(), template_loader() and js/plugin-customizer-preview.js
		 *
		 * A 'how to create permalink' tutorial is at https://soderlind.no/wordpress-plugins-and-permalinks-how-to-use-pretty-links-in-your-plugin/
		 */
		add_filter( 'generate_rewrite_rules', array( $this, 'rewrite_rule' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_filter( 'admin_init', array( $this, 'flush_rewrite_rules' ) );

		/**
		 * Parse request from js/plugin-customizer-preview.js previewer script and load the template.
		 */
		add_action( 'parse_request', array( $this, 'template_loader' ) );

		/**
		 * Enqueue previewer script.
		 *
		 * See https://make.xwp.co/2016/07/21/navigating-to-a-url-in-the-customizer-preview-when-a-section-is-expanded/
		 */
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'plugin_cusomizer_previewer_script' ) );

		/**
		 * The plugin is using transport => postMessage, set in customizer_plugin_settings(), and needs
		 * javascript to update the preview.
		 *
		 * You can Learn more about postmessage at:
		 * https://developer.wordpress.org/themes/advanced-topics/customizer-api/#using-postmessage-for-improved-setting-previewing
		 */
		add_action( 'customize_preview_init', array( $this, 'plugin_customizer_init' ) );

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
		}, $customize_register_priority );
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
	 * Create the preview.
	 *
	 * The WordPress customizer uses ajax to communicate with the preview. To load the needed
	 * scripts, wp_head() and wp_footer() must be added to the template.
	 *
	 * @author soderlind
	 * @version 1.0.0
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
			 * if either the child theme or the parent theme have overridden the template, load it.
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
	 * Load the previewer script and pass the template URL as argument to the script.
	 * Optionally load separate templates for the customizer sections.
	 *
	 * @author soderlind
	 * @version 1.0.0
	 */
	public function plugin_cusomizer_previewer_script() {
		$handle = 'plugin-customizer-preview';
		$src = plugins_url( 'js/plugin-customizer-preview.js' ,  __FILE__ );
		$deps = array( 'customize-controls' );
		$version = PLUGIN_CUSTOMIZER_VERSION;
		$in_footer = 1;
		wp_enqueue_script( $handle, $src, $deps, $version , $in_footer );
		/**
		 * Parameters sent to the previewer script is:
		 *  url		The custom permalink to the template. The last part, newsletter in the exmple below
		 *  		will translate to newsletter.php in the templates folder.
		 */
		$default_url = array(
			'url' => home_url( 'plugin-customizer/templates/newsletter/?' . PLUGIN_CUSTOMIZER_PARAM_NAME . '=' . PLUGIN_CUSTOMIZER_PARAM_VALUE ),
		);
		/**
		 * Add a template to a section. key = section name, value = url to template.
		 *
		 * The last part, title and content in the exmple below, will translate to title.php
		 * and content.php in the templates folder.
		 */
		$section_urls = array(
			// 'newsletter_title_section'   => home_url( 'plugin-customizer/templates/title/' ),
			// 'newsletter_content_section' => home_url( 'plugin-customizer/templates/content/' ),
		);
		wp_add_inline_script(
			$handle,
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
	public function plugin_customizer_init() {
		$handle = 'plugin-cusomizer-init';
		$src = plugins_url( 'js/plugin-customizer-init.js' ,  __FILE__ );
		$deps = array( 'customize-preview', 'jquery' );
		$version = PLUGIN_CUSTOMIZER_VERSION;
		$in_footer = 1;
		wp_enqueue_script( $handle, $src, $deps, $version , $in_footer );
	}

	/**
	 * Admin menus
	 */

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
			wp_safe_redirect( $this->_get_customizer_url( admin_url() ) );
			exit();
		}
	}

	/**
	 * Create submenu
	 * @author soderlind
	 * @version 1.0.0
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
	 * Replace the 'plugin-template' string, in the submenu added by register_sub_menu(),
	 * with the customizer url.
	 *
	 * See http://wordpress.stackexchange.com/a/131214/14546
	 *
	 * @author soderlind
	 * @version 1.0.0
	 */
	function add_sub_menu_customizer_url( $parent = 'options-general.php' ) {
		global $submenu;

		if ( ! isset( $submenu[ $parent ] ) ) {
			return;
		}
		foreach ( $submenu[ $parent ] as $k => $d ) {
			if ( 'plugin-template' == $d['2'] ) {
				$submenu[ $parent ][ $k ]['2'] = $this->_get_customizer_url( $parent );
				break;
			}
		}
	}

	/**
	 * [add_admin_bar_customizer_url description]
	 * @author soderlind
	 * @version 1.0.0
	 * @param   [type]	$wp_admin_bar [description]
	 */
	function add_admin_bar_customizer_url( $wp_admin_bar ) {
		global $post;
		$return_url = ( is_admin() ) ? $this->_get_current_admin_page_url() :  get_permalink( $post->ID );

		$args = array(
			'id' => 'plugin-customizer-link',
			'title' => __( 'Plugin Customizer', 'plugin-customizer' ),
			'href' => $this->_get_customizer_url( $return_url ),
		);

		$wp_admin_bar->add_node( $args );
	}

	/**
	 * Find the current admin url.
	 *
	 * From: https://core.trac.wordpress.org/ticket/27888
	 *
	 * @author soderlind
	 * @version 1.0.0
	 * @return  String|Bool    The URL to the current admin page, or false if not in wp-admin.
	 */
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

	/**
	 * Add an example option page.
	 * @author soderlind
	 * @version 1.0.0
	 */
	public function add_option_page() {
		add_menu_page( 'Plugin Customizer Options', 'Plugin Customizer Options', 'manage_options', 'plugin-customizer', array( $this, 'options_page' ) );
	}

	public function settings_init() {
		register_setting( 'pluginPage', 'settings' );
		add_settings_section( 'pluginPage_section', __( 'Demonstrate how to load the customizer from an option page', 'plugin-customizer' ), '', 'pluginPage' );
		add_settings_field( 'option_page_customizer', __( 'Open customizer ', 'plugin-customizer' ), array( $this, 'option_page_customizer_link' ), 'pluginPage', 'pluginPage_section' );
	}

	public function option_page_customizer_link() {
		printf( '<a href="%s">%s</a><p class="description">%s</p>',
		 	$this->_get_customizer_url( 'admin.php?page=plugin-customizer', 'newsletter_title_section' ), __( 'Customize', 'plugin-customizer' ),
			__( 'Will use autofocus[section]=newletter_title to focus on the title (eg open it)', 'plugin-customizer' )
		);
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
	 * Create the customizer URL.
	 *
	 * @author soderlind
	 * @version 1.0.0
	 * @param   string    $return_url        The URL to return to when closing the customizer.
	 * @param   string    $autofocus_section Used for deep-linking, i.e. this section will be selected
	 *                                       when the customizer is opened.
	 * @return  string                       The customizer URL.
	 */
	private function _get_customizer_url( $return_url = '', $autofocus_section = '' ) {
		// If no return url, set the return url to the main admin url.
		if ( '' == $return_url ) {
			$return_url = admin_url();
		}
		$url = wp_customize_url();
		// Add parameter to identify this as a customizer url for this plugin.
		$url = add_query_arg( PLUGIN_CUSTOMIZER_PARAM_NAME , PLUGIN_CUSTOMIZER_PARAM_VALUE, $url );
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

	/**
	 * Simple customizer demo. Saves the settings as options.
	 */

	/**
	 * Add sections.
	 *
	 * @author soderlind
	 * @version 1.0.0
	 * @param   WP_Customize_Manager $wp_customize
	 */
	public function customizer_plugin_sections( $wp_customize ) {
		global $wp_customize;

		$wp_customize->add_section(
			'newsletter_title_section',
			array(
				'title'			=> __( 'Title', 'plugin-customizer' ),
				'description'	=> __( 'Customize Your Logo Section', 'plugin-customizer' ),
				'priority'		=> 5,
				'capability'	=> 'manage_options',
			)
		);
		$wp_customize->add_section(
			'newsletter_content_section',
			array(
				'title'			=> __( 'Content', 'plugin-customizer' ),
				'description'	=> __( 'Customize newsletter content', 'plugin-customizer' ),
				'priority'		=> 10,
				'capability'	=> 'manage_options',
			)
		);
		return true;
	}

	/**
	 * Add settings.
	 *
	 * @author soderlind
	 * @version 1.0.0
	 * @param   WP_Customize_Manager $wp_customize
	 */
	public function customizer_plugin_settings( $wp_customize ) {
		global $wp_customize;

		$wp_customize->add_setting(
			'newsletter_title',
			array(
				'type'			=> 'option',
				'capability'	=> 'manage_options',
				'transport'     => 'postMessage',
				'default'		=> __( 'Newsletter', 'plugin-customizer' ),
			)
		);

		$wp_customize->add_setting(
			'newsletter_content',
			array(
				'type'			=> 'option',
				'capability'	=> 'manage_options',
				'transport'     => 'postMessage',
				'default'		=> 'Nulla laoreet erat vitae aliquet tristique. Quisque quis suscipit enim, a elementum eros.', //esc_html( '<p> Nulla laoreet erat vitae aliquet tristique. Quisque quis suscipit enim, a elementum eros. Vestibulum quis lacus ligula. Mauris sit amet nibh tincidunt, gravida tortor ac, maximus nisi. Nunc turpis mauris, blandit vitae nibh non, blandit pharetra purus. Sed sed convallis diam. In ante nisi, pellentesque in laoreet at, fringilla ac neque. </p> <p> Cras consequat eros lectus, ac dignissim justo cursus ac. In mi tortor, ornare eu quam a, ultrices tincidunt magna. Fusce non purus quis ex convallis vehicula. Aenean purus diam, vulputate vel pellentesque ultrices, tincidunt ac lectus. Nullam est purus, lobortis ac aliquam eget, egestas a diam. Praesent eget sapien vitae orci faucibus tempus. Ut sit amet lorem elit. Curabitur et vestibulum nibh. </p> <p> Mauris vestibulum eget nisl sit amet gravida. Nullam in orci ut sem maximus dapibus blandit quis dolor. Sed pretium efficitur elit, eget vulputate sapien bibendum non. Proin congue posuere sagittis. Ut fermentum mauris ut gravida faucibus. Aenean a malesuada magna. Vestibulum nec viverra metus. Nunc aliquam eros orci, ut malesuada felis malesuada vitae. Phasellus vehicula non ex at scelerisque. </p>' ),
			)
		);
		return true;
	}

	/**
	 * Add contronls.
	 *
	 * @author soderlind
	 * @version 1.0.0
	 * @param   WP_Customize_Manager $wp_customize
	 */
	public function customizer_plugin_controls( $wp_customize ) {
		global $wp_customize;

		$wp_customize->add_control(  new WP_Customize_Control(
			$wp_customize,
			'newsletter_title',
			array(
				'label'    => __( 'Title', 'plugin-customizer' ),
				'type'     => 'text',
				'section'  => 'newsletter_title_section',
				'settings' => 'newsletter_title',
			)
		) );

		$wp_customize->add_control( new WP_Customize_Control(
			$wp_customize,
			'newsletter_content',
			array(
				'label' => __( 'Content', 'plugin-customizer' ),
				'section' => 'newsletter_content_section',
				'settings' => 'newsletter_content',
				'type' => 'textarea',
			)
		) );
		return true;
	}


	/**
	 * Add selective refresh.
	 *
	 * @author soderlind
	 * @version 1.0.0
	 * @param   WP_Customize_Manager $wp_customize
	 */
	public function customizer_plugin_selective_refresh( WP_Customize_Manager $wp_customize ) {
		global $wp_customize;

		$wp_customize->selective_refresh->add_partial( 'newsletter_title', array(
			'selector' => '#newsletter-title',
			'render_callback' => function() {
				get_option( 'newsletter_title' );
			},
		) );

		$wp_customize->selective_refresh->add_partial( 'newsletter_content', array(
			'selector' => '#newsletter-content',
			'render_callback' => function() {
				get_option( 'newsletter_content' );
			},
		) );
		return true;
	}
}

PluginCustomizer::instance();
