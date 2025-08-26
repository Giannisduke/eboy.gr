=== LH Multisite CORS ===
Contributors: shawfactor
Donate link: https://lhero.org/portfolio/lh-multisite-cors/
Tags: CORS, REST, AJAX
Requires at least: 3.6
Tested up to: 6.0
Stable tag: 1.02
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows AJAX requests from other sites in your multisite network even if they are on another domain or subdomain

== Description ==

AJAX requests to this site from another (those containing an Origin header) will be allowed for any domain in your multisite setup, no configuration required

**Like this plugin? Please consider [leaving a 5-star review](https://wordpress.org/support/view/plugin-reviews/lh-multisite-cors/).**

**Love this plugin or want to help the LocalHero Project? Please consider [making a donation](https://lhero.org/portfolio/lh-multisite-cors/).**

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the entire `lh-multisite-cors` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Why do I need this plugin? =

If you want to integrate content from your site to JavaScript applications running on other domains then the CORS standard is a way to allow this. Allowing CORS for all sites is a security problem but given you are the super admin for sites on your own network they should be trustworthy, therefore CORS can be safely activated.

= What is the difference between CORS and JSONP? =

CORS is more modern and more secure since it works _with_ the browser's same-origin policy and XmlHttpRequest objects rather than bypassing them. 

= Ok I'm sold, where can I read more about CORS? =

You can find the CORS spec here: http://www.w3.org/TR/cors/ You can learn more about how to use CORS here: [https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)

= What is something does not work?  =

LH Multisite CORS, and all [https://lhero.org](LocalHero) plugins are made to WordPress standards. Therefore they should work with all well coded plugins and themes. However not all plugins and themes are well coded (and this includes many popular ones). 

If something does not work properly, firstly deactivate ALL other plugins and switch to one of the themes that come with core, e.g. twentyfirteen, twentysixteen etc.

If the problem persists please leave a post in the support forum: [https://wordpress.org/support/plugin/lh-multisite-cors/](https://wordpress.org/support/plugin/lh-multisite-cors/). I look there regularly and resolve most queries.

= What if I need a feature that is not in the plugin?  =

Please contact me for custom work and enhancements here: [https://shawfactor.com/contact/](https://shawfactor.com/contact/)

== Changelog ==
**1.00 April 12, 2016**  
Initial release.

== Changelog ==
**1.01 March 24, 2017**  
Fixed php strict errors

== Changelog ==
**1.02 July 27, 2017**  
Added class check