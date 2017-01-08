
<img src="assets/plugin-customizer-small.png" width="200" style="float:right"/>
# Plugin Customizer
<div style="clear:right;"></div>
[![Build Status](https://travis-ci.org/soderlind/plugin-customizer.svg?branch=master)](https://travis-ci.org/soderlind/plugin-customizer) [![Code Climate](https://codeclimate.com/github/soderlind/plugin-customizer/badges/gpa.svg)](https://codeclimate.com/github/soderlind/plugin-customizer)

## The goal

The reason I made Plugin Customizer is to make it easy to add WordPress Customizer to your plugins.

## Use

1. Clone or [download](https://github.com/soderlind/plugin-customizer/archive/master.zip) this repository and copy the `src` folder to your plugin.
1. Add the autoloder to your plugin.
1. Add Plugin_Customizer class and Plugin_Customizer_Interface to your plugin class.

It you'd like to learn more, read the inline comments in the [demo plugin](plugin-customizer-demo.php) and [src/Plugin_Customizer.php](src/Plugin_Customizer.php)

```php
// add autoloader
require_once PLUGIN_CUSTOMIZER_DEMO_PATH . 'inc/ps-auto-loader.php';
$class_loader = new PS_Auto_Loader();
$class_loader->addNamespace( 'PluginCustomizer', PLUGIN_CUSTOMIZER_DEMO_PATH . 'src' );
$class_loader->register();

class MyPlugin extends PluginCustomizer\Plugin_Customizer implements PluginCustomizer\Plugin_Customizer_Interface {

}
```
Btw, an interface can be considered as a reminder of which methods you must add to your MyPlugin class. They are:

```php
interface Plugin_Customizer_Interface {
	public function plugin_customizer_add_templates();
	public function plugin_customizer_previewer_postmessage_script();
	public function customizer_plugin_sections( $wp_customize );
	public function customizer_plugin_settings( $wp_customize );
	public function customizer_plugin_controls( $wp_customize );
}
```

### plugin_customizer_add_templates();

`plugin_customizer_add_templates()` let you add the [templates](templates) you'd like to use in the preview.

```php
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
		'newsletter_title_section'    => parent::template_url( 'title.php' ), // translates to templates/title.php
		'newsletter_content_section'  => parent::template_url( 'content.php' ), // translates to templates/content.php
	);
	parent::add_templates( $default_url, $section_urls );
	/**
	 * If you don't have different templates per section, just load the default template
	 */
	// parent::add_templates( $default_url );
}
```

### plugin_customizer_previewer_postmessage_script();

Use the `plugin_customizer_previewer_postmessage_script()` to add your [postmessage](https://developer.wordpress.org/themes/advanced-topics/customizer-api/#using-postmessage-for-improved-setting-previewing) javascript [file](js/plugin-customizer-init.js).

```php
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
```
### customizer_plugin_sections(), customizer_plugin_settings() and customizer_plugin_controls()

In these functions you add the needed sections, settings and controls followin the specification the [Customizer API](https://developer.wordpress.org/themes/advanced-topics/customizer-api/).

The demo plugin has a [simple implementation](https://github.com/soderlind/plugin-customizer/blob/master/plugin-customizer-demo.php#L92-L191).

## Demo

You know the drill, [download](https://github.com/soderlind/plugin-customizer/archive/master.zip), add and activate the plugin. It will add the `Plugin Customizer`  admin menu entries in:
- Root menu
- Submenu
- Admin bar menu
- Option Page


## Copyright and License

**Plugin Customizer** is copyright 2016 Per Soderlind

**Plugin Customizer** is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.

**Plugin Customizer** is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the [GNU General Public License](LICENSE) for more details.

You should have received a copy of the GNU Lesser General Public License along with the Extension. If not, see http://www.gnu.org/licenses/.
