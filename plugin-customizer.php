<?php
/*
Plugin Name: plugin customizer
Version: 0.1
Plugin URI:
Description:
Author: Per Soderlind
Author URI: https://soderlind.no
*/

define( 'PLUGIN_CUSTOMIZER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

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

		add_filter( 'generate_rewrite_rules', array( $this, 'my_permalink_rewrite_rule' ) );
		add_filter( 'query_vars', array( $this, 'my_permalink_query_vars' ) );
		add_filter( 'admin_init', array( $this, 'my_permalink_flush_rewrite_rules' ) );
		add_action( 'parse_request', array( $this, 'my_permalink_parse_request' ) );
		// add_action( 'admin_init', array( $this, 'load_template' ) );

		// register customizer settings
		add_action( 'wp_loaded', function() {
				add_action( 'customize_register', array( $this, 'customize_plugin' ) );
		}, 9 );

		add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_plugin_cusomizer_scripts' ) );
	}

	// https://developer.wordpress.org/reference/functions/load_template/#comment-727

	function my_permalink_rewrite_rule( $wp_rewrite ) {
		 $new_rules = array(
			  'plugin-customizer-template/(.*)$' => sprintf( 'index.php?plugin-customizer-template=%s',$wp_rewrite->preg_index( 1 ) ),
			  /*
			  // a more complex permalink:
			  'my-permalink/([^/]+)/([^.]+).html$' => sprintf("index.php?my_permalink_variable_01=%s&my_permalink_variable_02=%s",$wp_rewrite->preg_index(1),$wp_rewrite->preg_index(2))
			  */
		 );

		 $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
		 return $wp_rewrite->rules;
	}

	function my_permalink_query_vars( $query_vars ) {
		$query_vars[] = 'plugin-customizer-template';
		/*
		// need more variables?:
		$query_vars[] = 'my_permalink_variable_02';
		$query_vars[] = 'my_permalink_variable_03';
		*/
		return $query_vars;
	}

	function my_permalink_parse_request( $wp_query ) {
		if ( isset( $wp_query->query_vars['plugin-customizer-template'] ) ) { // same as the first custom variable in my_permalink_query_vars( $query_vars )

			require_once( PLUGIN_CUSTOMIZER_PLUGIN_DIR . '/include/class-plugin-customizer-template-loader.php' );



			$template_names = array(
			   // "plugin-customizer-template-{$panel}.php",
			   // "plugin-customizer-template-{$section}.php",
			//    'plugin-customizer-template.php',
			   "{$wp_query->query_vars['plugin-customizer-template']}.php",
			);

			$template_loader = new Plugin_Customizer_Template_Loader();
			$template = $template_loader->locate_template( $template_names );


			$classes   = array( 'plugin-customizer' );
			// $classes[] = $args['show_playlist'] ? '' : 'is-playlist-hidden';
			// $classes[] = sprintf( 'cue-theme-%s', sanitize_html_class( $args['theme'] ) );
			// $classes   = implode( ' ', array_filter( $classes ) );
			// if ( $args['container'] ) {
			// 	echo '<div class="cue-playlist-container">';
			// }
			// do_action( 'cue_before_playlist', $post, $tracks, $args );
			wp_head();
			include( $template );
			wp_footer();

			exit( 0 );
		}
	}

	function my_permalink_flush_rewrite_rules() {
		$rules = $GLOBALS['wp_rewrite']->wp_rewrite_rules();
		if ( ! isset( $rules['plugin-customizer-template/(.*)$'] ) ) { // must be the same rule as in my_permalink_rewrite_rule($wp_rewrite)
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}
	}


	public function load_template() {
		if ( isset( $_GET['preview'] ) ) {
			add_filter( 'template_include', function() {
				return plugins_url( 'plugin-customizer-theme.php', __FILE__ );
			});
			// require_once( PLUGIN_CUSTOMIZER_PLUGIN_DIR . '/include/class-plugin-customizer-template-loader.php' );
			//
			// $template_names = array(
			// 	// "plugin-customizer-template-{$panel}.php",
			// 	// "plugin-customizer-template-{$section}.php",
			// 	'plugin-customizer-template.php',
			// );
			//
			// $template_loader = new Plugin_Customizer_Template_Loader();
			// $template = $template_loader->locate_template( $template_names );
			//
			// $classes   = array( 'plugin-customizer' );
			// // $classes[] = $args['show_playlist'] ? '' : 'is-playlist-hidden';
			// // $classes[] = sprintf( 'cue-theme-%s', sanitize_html_class( $args['theme'] ) );
			// // $classes   = implode( ' ', array_filter( $classes ) );
			// // if ( $args['container'] ) {
			// // 	echo '<div class="cue-playlist-container">';
			// // }
			// // do_action( 'cue_before_playlist', $post, $tracks, $args );
			// include( $template );
		}
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
			// 'type'  => 'panel',
			'panel' => 'plugin_settings_panel',
			// 'url'    => 'http://customizer.dev/2016/09/11/hello-world/',
			'url'    => 'http://customizer.dev/plugin-customizer-template/plugin-customizer-template/',
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
		// $url = add_query_arg( 'preview' , true  , $url );
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
