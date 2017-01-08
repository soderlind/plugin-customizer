<?php
namespace PluginCustomizer;
if ( ! interface_exists( 'PluginCustomizer\Plugin_Customizer_Interface' ) ) {
	interface Plugin_Customizer_Interface {
		public function plugin_customizer_add_templates();
		public function plugin_customizer_previewer_postmessage_script();
		public function customizer_plugin_sections( $wp_customize );
		public function customizer_plugin_settings( $wp_customize );
		public function customizer_plugin_controls( $wp_customize );
	}
}
