<?php
/*
Plugin Name: plugin customizer
Version: 0.1
Plugin URI:
Description:
Author: Per Soderlind
Author URI: https://soderlind.no
*/

/**
 *
 */

// require_once( dirname( __FILE__ ) . '/lib/customizer-blank-slate.php' );

class PluginCustomizer {

	protected static $_instance = null;
	private $plugin_customizer_trigger = 'trigger-plugin-customizer';


	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	function __construct() {

		// add menues
		add_action( 'admin_menu', array( $this, 'register_root_menu' ) );
		add_action( 'admin_menu', array( $this, 'register_sub_menu' ) );
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_customizer_url' ), 500 );
		add_action( 'admin_init', array( $this, 'root_menu_redirect_to_customizer' ) , 1 );

		// register customizer settings
		add_action( 'wp_loaded', function() {
				add_action( 'customize_register', array( $this, 'customize_plugin' ) );
		}, 9 );

		add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_plugin_cusomizer_scripts' ) );
	}

	public function enqueue_plugin_cusomizer_scripts() {
		$handle = 'plugin-cusomizer';
		$src = plugins_url( 'js/plugin-cusomizer-scripts.js' ,  __FILE__ );
		$deps = array( 'customize-controls' );
		$version = rand();
		$in_footer = 1;
		wp_enqueue_script( $handle, $src, $deps, $version , $in_footer );

		// 'http://customizer.dev/2016/09/11/hello-world/'
		$args = array(
			'url'     => 'http://customizer.dev/2016/09/11/hello-world/',
			'section' => 'form_title_section',
		);
		wp_add_inline_script(
			$handle,
			sprintf( 'PluginCustomizer.init( %s );', wp_json_encode( $args ) ),
			'after'
		);
	}

	public function register_root_menu() {

		add_menu_page(
			__( 'Plugin Customizer', 'plugin-customizer' ),
			__( 'Plugin Customizer', 'plugin-customizer' ),
			'manage_options',
			'redirect-customizer',
			'function'
		);
	}

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


	// from: http://wordpress.stackexchange.com/a/175574/14546
	function root_menu_redirect_to_customizer() {

	    $menu_redirect = isset( $_GET['page'] ) ? $_GET['page'] : false;

	    if ( 'redirect-customizer' == $menu_redirect ) {
	        wp_safe_redirect( $this->_get_customizer_url() );
	        exit();
	    }
	}


	function add_sub_menu_customizer_url( $parent = 'options-general.php' ) {
		global $menu, $submenu;

		// from: http://wordpress.stackexchange.com/a/131214/14546
		if ( ! isset( $submenu[ $parent ] ) ) {
			return;
		}
		foreach ( $submenu[ $parent ] as $k => $d ) {
			if ( 'plugin-template' == $d['2'] ) {
				$submenu[ $parent ][ $k ]['2'] = $this->_get_customizer_url();
				break;
			}
		}
	}


	function add_admin_bar_customizer_url( $wp_admin_bar ) {
		$args = array(
		    'id' => 'plugin-customizer-link',
		    'title' => __( 'Plugin Customizer', 'plugin-customizer' ),
		    'href' => $this->_get_customizer_url(),
		);

		$wp_admin_bar->add_node( $args );
	}


	private function _get_customizer_url() {
		$url = wp_customize_url();

		$url = add_query_arg( $this->plugin_customizer_trigger , 'on', $url );
		// $url = add_query_arg(  'url' , $this->get_preview_template_link( plugins_url( 'form-template.php', __FILE__))  , wp_customize_url() );
		// $url = add_query_arg( 'url' , $this->get_preview_template_link( home_url( '/' ) )  , $url );
		$url = add_query_arg( 'preview' , true  , $url );
		// $url = add_query_arg( 'url' , urlencode( plugins_url( 'form-template.php', __FILE__ ) ) , $url );
		$url = add_query_arg( 'customizer_blank_slate', 'on', $url );
		//autofocus
		$url = add_query_arg( 'autofocus[panel]', 'plugin_settings_panel', $url );
		//return url from customizer
		$url = add_query_arg( 'return', urlencode( admin_url() ), $url );

		$url = esc_url_raw( $url );
		return $url;
	}

	// function get_preview_post_link( $post = null, $query_args = array(), $preview_link = '' ) {
	function get_preview_template_link( $preview_link ) {

	        $query_args['preview'] = 'true';
	        $preview_link = add_query_arg( $query_args, $preview_link );

	    return  $preview_link;
	}


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
