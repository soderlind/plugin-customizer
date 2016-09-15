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

class plugin_customizer{

	protected static $_instance = null;
	private $plugin_customizer_trigger = 'trigger-plugin-customizer';

	function __construct() {

		// add_action( 'admin_init', array( $this, 'redirect_to_plugin_template' ) );
		add_action( 'admin_init', array( $this, 'redirect_to_customizer' ) , 1 );
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_bar_menu', array( $this, 'add_plugin_customizer_admin_bar_link' ), 500 );

	    // add_action( 'wp_head',				 	array( $this, 'login_page_custom_head' ) );
		// add_action( 'wp_footer',			 	array( $this, 'login_page_custom_footer' ) );


		// only load controls for this plugin


		add_action( 'wp_loaded', function() {
			// if ( isset( $_GET[ $this->plugin_customizer_trigger ] ) ) {
				add_action( 'customize_register', array( $this, 'customize_plugin' ) );
			// }
		}, 9 );
		// add_action('customize_controls_print_styles', array($this,'my_custom_script') , 20 );

		// if ( isset( $_GET[ $this->plugin_customizer_trigger ] ) )  {
			// add_action( 'template_redirect', array( $this, 'load_plugin_template' ), 20 );
			// Hides the Admin Bar
			// define( 'IFRAME_REQUEST', true );
		if ( is_customize_preview() ) {
			add_filter( 'the_content', array( $this, 'template_preview_content' ), -999999 );
		}
		// }

		// // add our custom query vars to the whitelist
		// add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		//
		// // listen for the query var and load template
		// add_action( 'parse_query', array( $this, 'load_plugin_template' ) );
	}

	public function template_preview_content( $content ) {
		# code...
		return file_get_contents( dirname(__FILE) . '/form-template.php');
	}


	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}


	function my_custom_script() {
    	wp_enqueue_script( 'my-custom-script', plugin_dir_url( __FILE__ ) . '/js/my-custom-script.js', array(), rand() );
	}



	public function register_menu() {

		add_menu_page(
			__( 'Plugin Customizer', 'plugin-customizer' ),
			__( 'Plugin Customizer', 'plugin-customizer' ),
			'manage_options',
			'redirect-customizer',
			'function'
		);

		// add_options_page(
		// 	__( 'Plugin Customizer', 'plugin-customizer' ),
		// 	__( 'Plugin Customizer', 'plugin-customizer' ),
		// 	'manage_options',
		// 	'plugin-template',
		// 	'__return_null'
		// );
		// $this->add_menu_customizer_url();
	}

	// from: http://wordpress.stackexchange.com/a/175574/14546
	function redirect_to_customizer() {

	    $menu_redirect = isset($_GET['page']) ? $_GET['page'] : false;

	    if($menu_redirect == 'redirect-customizer' ) {
	        wp_safe_redirect( $this->_get_plugin_customizer_url() );
	        exit();
	    }
	}


	function add_submenu_customizer_url( $parent = 'options-general.php' ) {
		global $menu, $submenu;

		// from: http://wordpress.stackexchange.com/a/131214/14546
		if ( ! isset( $submenu[ $parent ] ) ) {
			return;
		}
		foreach ( $submenu[ $parent ] as $k => $d ) {
			if ( $d['2'] == 'plugin-template' ) {
				$submenu[ $parent ][ $k ]['2'] = $this->_get_plugin_customizer_url();
				break;
			}
		}
	}





	function add_plugin_customizer_admin_bar_link( $wp_admin_bar ) {

	  $args = array(
	    'id' => 'plugin-customizer-link',
	    'title' => __(  'Plugin Customizer', 'plugin-customizer' ),
	    'href' => $this->_get_plugin_customizer_url(),
	  );

	  $wp_admin_bar->add_node($args);
	}


	private function _get_plugin_customizer_url() {
		$url = wp_customize_url();


		// $url = add_query_arg( $this->plugin_customizer_trigger , 'on', $url );
		// $url = add_query_arg(  'url' , $this->get_preview_template_link( plugins_url( 'form-template.php', __FILE__))  , wp_customize_url() );
		$url = add_query_arg(  'url' , $this->get_preview_template_link( home_url( '/' ) )  , $url );
		// $url = add_query_arg(  'url' , home_url( '/' )  , $url );



		$url = add_query_arg( 'customizer_blank_slate', 'on', $url );
		//autofocus
		$url = add_query_arg( 'autofocus[panel]', 'plugin_settings_panel', $url );
		//self
		// $url = add_query_arg( 'url', urlencode( wp_nonce_url( site_url() . '/?wppublish-customizer=true', 'wppublish' ) ), $url );
		// $url = add_query_arg( 'url', urlencode( wp_nonce_url( plugins_url() . '/?customizer-template=true' , 'wppublish' ) ), $url );
		// $url = add_query_arg(  $this->plugin_customizer_trigger , 'on'  , $url );
		//return url from customizer
		// $url = add_query_arg( 'return', urlencode( add_query_arg( array( 'page' => 'ps-sandbox', 'tab' => 'email' ), admin_url( 'admin.php' ) ) ), $url );
		$url = add_query_arg( 'return', urlencode( admin_url() ), $url );

		$url = esc_url_raw( $url );
		return $url;
	}

	// function get_preview_post_link( $post = null, $query_args = array(), $preview_link = '' ) {
	function get_preview_template_link(  $preview_link  ) {
	    // $post = get_post( $post );
	    // if ( ! $post ) {
	    //     return;
	    // }

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

	public function redirect_to_custom_page() {
      if (!empty($_GET['page'])) {
        if(($_GET['page']== "abw")){
          wp_redirect(get_admin_url()."customize.php?url=".wp_login_url());
        }
      }
    }

	function my_page_template_redirect() {
		// if ( isset( $_GET[ $this->plugin_customizer_trigger ] ) )  {
	        wp_redirect( plugins_url( 'form-template.html', __FILE__ )  );
	        // exit();
	    // }
	}



	public function add_query_vars( $vars ) {
		$vars[] = $this->plugin_customizer_trigger;

		return $vars;
	}

	public function load_plugin_template( $original_template) {


		// @codingStandardsIgnoreStart
		// printf( '<pre>%s</pre>', print_r( 'heppppppp', true ) );
		// @codingStandardsIgnoreEnd

		// load this conditionally based on the query var
		// if ( get_query_var( $this->plugin_customizer_trigger ) ) {
		if ( isset( $_GET[ '$this->plugin_customizer_trigger' ] ) )  {
			// load the mailer class

			ob_start();

			wp_head();
			require_once( dirname(__FILE__) . '/form-template.php' );
			wp_footer();

			$message = ob_get_clean();

			// $email_heading = __( 'HTML Email Template!', 'woocommerce-email-customizer' );


			// wrap the content with the email template and then add styles
			// $message = $email->style_inline( $mailer->wrap_message( $email_heading, $message ) );


			return $message;

		}
		return $original_template;
	}



}

plugin_customizer::instance();
