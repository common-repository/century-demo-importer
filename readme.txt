=== Century Demo Importer ===
Contributors: CenturyPlugin
Tags: import, content, demo, theme options, customizer options, widgets, redux options
Donate link: http://centuryplugin.com/donation/
Requires at least: 4.7.0
Tested up to: 5.5
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 1.0.0
Version: 1.0.0


== Description ==
This plugin will import demo contents for the active theme which are configured with this plugin. By default the plugin will fetch the configuration files stored on our server.
If you want to configure your own theme with the plugin, you just need to point to your config.json file and pass it to the filter 
<strong>cdi_git_config_location</strong> 

Example: 

If your URL to config.json is 'https://example.com/theme-demos/config.json'

You will have to pass this to the filter 'cdi_git_config_location' like

<pre>
add_filter('cdi_git_config_location', 'git_url_config' );
function git_url_config(){
	$git_url = 'https://example.com/theme-demos/config.json';
	return $git_url;
}
</pre>


[Support] ( https://centuryplugin.com/support )


== Installation ==

1. Upload `century-demo-importer` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. After activating plugin go to Appearance >> Install Demos
4. Enjoy !!!


= Available Languages =
* English

= Features =
* <strong>Import all the contents of theme's demo.</strong>
* <strong>Import all widgets data.</strong>
* <strong>Import all menu, customizer settings and other themes settings.</strong>
* <strong>Import posts, pages, images, categories, custom post.</strong>


= Some Useful Links =
* <strong>Support Forum Link</strong>: http://centuryplugin.com/support/
* <strong>Website Link</strong>: http://centuryplugin.com/


== Frequently Asked Questions ==

= What does this plugin do? =
This plugin provides the ability to to import the demo contents, widgets, theme option settings and customizer settings in your site.


== Changelog ==

= 1.0.1 =
* Minor bugs fixed

= 1.0.0 =
* Plugin submitted to http://wordpress.org for review and approval


== Upgrade Notice ==
There is an update available for the Century Demo Importer plugin. Please update to recieve new updates and bug fixes.