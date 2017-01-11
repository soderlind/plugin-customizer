<?php
/*
Plugin Name: Plugin Customizer Demo
Version: 1.1.3
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
define( 'PLUGIN_CUSTOMIZER_DEMO_VERSION', '1.1.3' );
define( 'PLUGIN_CUSTOMIZER_DEMO_PATH',   plugin_dir_path( __FILE__ ) );

// add autoloader
require_once PLUGIN_CUSTOMIZER_DEMO_PATH . 'inc/ps-auto-loader.php';
$class_loader = new PS_Auto_Loader();
$class_loader->addNamespace( 'PluginCustomizer', PLUGIN_CUSTOMIZER_DEMO_PATH . 'src' );
$class_loader->register();


class PluginCustomizerDemo extends PluginCustomizer\Plugin_Customizer implements PluginCustomizer\Plugin_Customizer_Interface {

	function __construct() {
		PluginCustomizer\Plugin_Customizer::init( array(
			'name' => 'Customizer Demo', // name your plugin
			'url'  => plugins_url( '', __FILE__ ), // note, '' adds a slash to the end of url
			'path' => plugin_dir_path( __FILE__ ),
		) );
		$this->add_admin_menus();
	}

	/**
	 * Load default template
	 * Optionally load separate templates for the customizer sections.
	 *
	 * @author soderlind
	 * @version 1.1.0
	 */
	public function plugin_customizer_add_templates() {
		/**
		 * The default template used when opening the customizer
		 * @var array
		 */
		$default_url = array(
			'url' => parent::template_url( 'newsletter.php' ), // translates to templates/newsletter.php
		);
		/**
		 * Option, add a template to a section. key = section name, value = url to template.
		 *
		 * The last part, title and content in the exmple below, will translate to title.php
		 * and content.php in the templates folder.
		 */
		$section_urls = array(
			'newsletter_title_section' => parent::template_url( 'title.php' ), // translates to templates/title.php
			'newsletter_content_section'  => parent::template_url( 'content.php' ), // translates to templates/content.php
		);
		parent::add_templates( $default_url, $section_urls );
		/**
		 * If you don't have different templates per section, just load the default template
		 */
		// parent::add_templates( $default_url );
	}

	/**
	 * Load the preview script. The script is needed sice the transport is postmessage
	 * @author soderlind
	 * @version 1.0.0
	 */
	public function plugin_customizer_previewer_postmessage_script() {

		$handle = 'plugin-customizer-demo-init';
		$src = plugins_url( 'js/plugin-customizer-init.js' ,  __FILE__ );
		$deps = array( 'customize-preview', 'jquery' );
		$version = PLUGIN_CUSTOMIZER_DEMO_VERSION;
		$in_footer = 1;
		wp_enqueue_script( $handle, $src, $deps, $version , $in_footer );
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
				'default'		=> __( 'The Wizard of Oz', 'plugin-customizer' ),
			)
		);

		$wp_customize->add_setting(
			'newsletter_content',
			array(
				'type'			=> 'option',
				'capability'	=> 'manage_options',
				'transport'     => 'postMessage',
				'default'		=> 'This was to be an eventful day for the travelers.  They had hardly been walking an hour when they saw before them a great ditch that crossed the road and divided the forest as far as they could see on either side.  It was a very wide ditch, and when they crept up to the edge and looked into it they could see it was also very deep, and there were many big, jagged rocks at the bottom.  The sides were so steep that none of them could climb down, and for a moment it seemed that their journey must end. "What shall we do?" asked Dorothy despairingly. "I haven\'t the faintest idea," said the Tin Woodman, and the Lion shook his shaggy mane and looked thoughtful.', //esc_html( '<p> Nulla laoreet erat vitae aliquet tristique. Quisque quis suscipit enim, a elementum eros. Vestibulum quis lacus ligula. Mauris sit amet nibh tincidunt, gravida tortor ac, maximus nisi. Nunc turpis mauris, blandit vitae nibh non, blandit pharetra purus. Sed sed convallis diam. In ante nisi, pellentesque in laoreet at, fringilla ac neque. </p> <p> Cras consequat eros lectus, ac dignissim justo cursus ac. In mi tortor, ornare eu quam a, ultrices tincidunt magna. Fusce non purus quis ex convallis vehicula. Aenean purus diam, vulputate vel pellentesque ultrices, tincidunt ac lectus. Nullam est purus, lobortis ac aliquam eget, egestas a diam. Praesent eget sapien vitae orci faucibus tempus. Ut sit amet lorem elit. Curabitur et vestibulum nibh. </p> <p> Mauris vestibulum eget nisl sit amet gravida. Nullam in orci ut sem maximus dapibus blandit quis dolor. Sed pretium efficitur elit, eget vulputate sapien bibendum non. Proin congue posuere sagittis. Ut fermentum mauris ut gravida faucibus. Aenean a malesuada magna. Vestibulum nec viverra metus. Nunc aliquam eros orci, ut malesuada felis malesuada vitae. Phasellus vehicula non ex at scelerisque. </p>' ),
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
	 * Admin menus
	 */

	public function add_admin_menus() {
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
			wp_safe_redirect( \PluginCustomizer\Plugin_Customizer::get_customizer_url( admin_url() ) );
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
				$submenu[ $parent ][ $k ]['2'] = \PluginCustomizer\Plugin_Customizer::get_customizer_url( $parent );
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
		if ( is_admin() ) {
			$return_url = $this->_get_current_admin_page_url();
		} elseif ( is_object( $post ) ) {
			$return_url = get_permalink( $post->ID );
		} else {
			$return_url = esc_url( home_url( '/' ) );
		}

		$args = array(
			'id' => 'plugin-customizer-link',
			'title' => __( 'Plugin Customizer', 'plugin-customizer' ),
			'href' => \PluginCustomizer\Plugin_Customizer::get_customizer_url( $return_url ),
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
		 	\PluginCustomizer\Plugin_Customizer::get_customizer_url( 'admin.php?page=plugin-customizer', 'newsletter_title_section' ), __( 'Customize', 'plugin-customizer' ),
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
}

if ( defined( 'WPINC' ) ) {
	$GLOBALS['plugin-customizer-demo'] = PluginCustomizerDemo::instance();
}
