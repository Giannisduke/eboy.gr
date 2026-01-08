=== XML Feed for Skroutz & BestPrice for WooCommerce ===
Contributors: dichagr, theogk
Tags: skroutz, bestprice, xml, feed, marketplace
Stable tag: 1.2.1
Requires at least: 5.6
Tested up to: 6.8.3
WC requires at least: 6.2.0
WC tested up to: 10.2.2
Requires PHP: 7.4
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

This plugin helps you create an XML feed for Skroutz and BestPrice marketplaces.

== Description ==

XML Feed for Skroutz & BestPrice for WooCommerce is the ultimate tool for WooCommerce store owners looking to maximize their exposure on Greece's leading product marketplaces, Skroutz.gr and BestPrice.gr.

This plugin generates a fully compliant XML product feed, ensuring your store’s products are displayed accurately on these marketplaces.

With automatic updates for product additions, deletions, and modifications, this plugin removes the hassle of manually managing feeds, saving you time and effort.

The plugin provides many settings in the admin panel to create the product feed tailored to your needs. In addition, it provides developer-friendly ways to furtherly customize the feed to match specific advanced requirements.

This plugin will help you to easily pass the strict and challenging Skroutz XML validation process.

= Key Features =

- Fully compliant with Skroutz and BestPrice specifications.
- Full support for product variations with the new format required for Skroutz marketplace. Variations are grouped by size attributes. "Non-size" variations like color etc. appear in the feed as separate products, as per Skroutz requirements.
- Exclude or include products in the feed based on category, and/or tag from the plugin settings. If you need more control, there is a developer-friendly way to include/exclude products programmatically.
- Hide out of stock products from the feed, or products on backorder.
- NEW: Set a global price increase that will modify all product prices in the XML feed.
- NEW: Set a custom "Skroutz price" per product or variation that will be used instead of the regular price in the XML feed.
- Choose between long and short descriptions for product display. If one description is empty, the other is used as fallback.
- Use the new native WooCommerce v9.2+ field for EAN/Barcode, or let this plugin create a new custom field for you.
- Select multiple attributes for manufacturer, color, and size fields.
- Select a Manufacturer from a product attribute, or native "WooCommerce Brands", or even 3rd-party plugins like "Perfect Brands for WooCommerce" (by quadlayers). If no manufacturer info exists on a product, a default value "OEM" will be used to comply with Skroutz requirements.
- Skroutz availability management at global level, and at the product/variation level.
- Select the attributes to include in the product name from the plugin settings.
- Add a fixed shipping rate.
- Developer-friendly ways to customize **ALL** feed data fields, like ID, product title, SKU, availability, images, description, category, price, quantity, weight etc.
- Schedule automatic XML generation using WP Cron or server cron jobs from Plesk/cPanel/SiteGround/GoDaddy or whatever platform you use.
- Email notification in case XML generation fails.
- ZIP format available for huge product feeds.
- WP CLI support for generating the XML file.

= Really Fast & Lightweight =
- Optimized for speed, as it generates the XML way **faster** than any other "Skroutz feed" plugin, and also using far **less memory**, minimizing the stress on the server during the XML creation process.
- Optimized to run on servers with low resources. The minimum PHP memory officially required is 256MB, but it can run with even lower memory for small-sized eshops. The recommended memory for optimal performance is 512MB-1024MB+.

= Documentation =
[Video Setup Guide](https://youtu.be/Ssr_-QH-7zc)
[Documentation for Users/Shop managers](https://doc.clickup.com/2582906/p/h/2eubu-58615/d0b94a4b2c5331e/2eubu-58675)
[Documentation for Developers](https://doc.clickup.com/2582906/p/h/2eubu-58615/d0b94a4b2c5331e/2eubu-58695)

= Minimum Requirements =

* WooCommerce 6.2.0 or later
* WordPress 5.6 or later
* Minimum server PHP memory limit: 256MB (Recommended: 512MB)

== Screenshots ==

1. Plugin settings.

== Frequently Asked Questions ==

 = The automatic XML generation is not working. What am I doing wrong? =
 If you've enabled WP_Cron functionality from the plugin settings, please note that this method can be unreliable for automatic XML generation, especially for larger eshops. The way WordPress works, WP_Cron depends on website traffic to trigger scheduled tasks and may not run consistently on busy or low-traffic sites. It is generally recommended only for stores with a small number of products.
 If your store has thousands of products, we highly recommend setting up a real server cron job via your hosting control panel (such as Plesk, cPanel, or similar platforms). Server cron jobs provide reliable and timely execution regardless of site traffic and are much better suited for handling large XML feed generation tasks.
 Please refer to the [Documentation for Developers](https://doc.clickup.com/2582906/p/h/2eubu-58615/d0b94a4b2c5331e/2eubu-58695) for details on configuring a server cron job.

 = I found a bug or something doesn't work. How can I get support? =
You can post your problem in the [official support forum](https://wordpress.org/support/plugin/xml-feed-for-skroutz-for-woocommerce/), describing your problem with as much details as possible. Please include some error logs, or screenshots of the problem to make the process faster.
Please wait patiently until you receive our free support for this free plugin. We try to reply as soon as possible, but it can take some days in some occasions.

 = If it's so good, why it's free? =
We use this plugin for years in our customers eshops with great results and 100% success rate in XML validation and approval from Skroutz and BestPrice.
We just want to give something for free to the greek ecommerce community. If you insist on giving us money, you can buy some of our [premium plugins & addons](https://www.dicha.gr/plugins/).

 = I use a similar plugin for generating the Skroutz/BestPrice XML, but I have problems. Can I switch easily to this plugin? =
Yes, you can switch to this plugin, but please follow carefully the [migration guide](https://doc.clickup.com/2582906/p/h/2eubu-58615/d0b94a4b2c5331e/2eubu-58775) that corresponds to your previous plugin.
If there is no guide available, please contact us to help you with the switch.

 = Is the XML feed fully compliant with Skroutz.gr and BestPrice.gr specifications? =
Yes, the plugin adheres to all requirements and specifications of Skroutz.gr and BestPrice.gr, and it can help you to easily pass the strict and challenging Skroutz XML validation process.

 = Does the plugin support product variations? =
Yes, the plugin supports variations! Variations are grouped by size attributes. "Non-size" variations like color etc. appear in the feed as separate products, as per Skroutz requirements.

 = Can I set a custom price in the XML feed that will be different from the price in my eshop? =
Yes, you have two options. You can set a global price increase (percentage) that will modify all product prices in the XML feed.
Or you can set a custom "Skroutz price" per product or variation that will be used instead of the regular price in the XML feed.

 = Can I exclude specific products or categories from the feed? =
Absolutely. You can exclude or include products based on their categories and tags using the plugin settings. If you need more control, there is a developer-friendly way to include/exclude products programmatically.
Please read the [documentation for developers](https://doc.clickup.com/2582906/p/h/2eubu-58615/d0b94a4b2c5331e/2eubu-58695) to find out how.

 = Can I customize the product IDs in the XML feed? =
Yes, you can use custom product IDs and SKUs which is particularly helpful if you are migrating from another e-commerce CMS, or an old WooCommerce installation.
Please read the [documentation for developers](https://doc.clickup.com/2582906/p/h/2eubu-58615/d0b94a4b2c5331e/2eubu-58695) to find out how.

 = How often is the XML feed updated? =
The plugin allows you to schedule automatic updates using the native WP Cron, or server cron jobs from Plesk/cPanel/SiteGround/GoDaddy or whatever platform you use. You can customize the update frequency to meet your needs.

 = Can I use WP CLI or a server script to generate the XML? =
Yes, you can use the WP CLI or a server script to generate the XML.
Please read the [documentation for developers](https://doc.clickup.com/2582906/p/h/2eubu-58615/d0b94a4b2c5331e/2eubu-58695) to find out how.

 = Is there a way to know if XML generation failed? =
Yes, you can enable the XML monitor and enter your email in the appropriate field in plugin settings and you will get notified if the XML has not updated for over 4 hours (also customizable).

 = What are the system requirements for using this plugin? =
The plugin requires WooCommerce 6.2.0 or later and WordPress 5.6 or later.
The recommended available PHP memory for optimal performance is 512MB-1024MB+.
It is optimized to run on servers with low resources and the minimum PHP memory officially required is 256MB, but it can run with even lower memory for small-sized eshops.

== Installation ==

= Installation via the WordPress interface =
1. Download the plugin from [Official WP Plugin Repository](https://wordpress.org/plugins/xml-feed-for-skroutz-for-woocommerce/).
2. Upload Plugin from your WP Dashboard ( Plugins>Add New>Upload Plugin ) the xml-feed-for-skroutz-for-woocommerce.zip file.
3. Activate the plugin through the 'Plugins' menu in WordPress Dashboard.
4. Setup the plugin settings navigating through the left main menu: Digital Challenge > Skroutz/BestPrice XML

== Changelog ==
= 1.2.1 =
*Release Date - 10 Oct 2025*
* Fix: Fix a filter not firing when attribute inputs are empty in plugin settings.

= 1.2.0 =
*Release Date - 1 Oct 2025*
* Feature: New settings field to set a global price increase to all products.
* Feature: New product field to set a custom product "Skroutz Price" just for your feeds.
* Improvement: Better handling of huge XML files.
* Compatibility: Checked with the latest WordPress and WooCommerce versions.

= 1.1.2 =
*Release Date - 23 May 2025*
* Feature: Add option to add "One Size" to Skroutz <size> field, if product has no size information.
* Compatibility: Add compatibility with Perfect Brands for WooCommerce plugin for the manufacturer field.
* Improvement: Improve settings labels and instructions.
* Improvement: Improve the calculation of ean/barcode field.

= 1.1.1 =
*Release Date - 15 Apr 2025*
* Improvement: Filter only main query in admin product list.
* Compatibility: Add compatibility with all multilingual plugins for xml availability product field.
* Compatibility: Add compatibility with WPML to use greek translations (if exist) in Skroutz XML text fields.

= 1.1.0 =
*Release Date - 31 Mar 2025*
* Feature: Send an email alert in case of an error in XML generation.
* Feature: Add ability to remove size variations for specific categories that Skroutz does not support them.
* Improvement: Make XML generation cron more stable and reschedule in case of permanent failure.
* Improvement: Better calculation of mpn for variable products with size.
* Compatibility: Checked with the latest WordPress and WooCommerce versions.

= 1.0.4 =
*Release Date - 25 Feb 2025*
* Compatibility: Checked with the latest WordPress and WooCommerce versions.
* Fix: Fix a bug in quick edit field with missing selected option.

= 1.0.3 =
*Release Date - 07 Feb 2025*
* Feature: Add support for native WooCommerce Brands (added recently in WooCommerce core v9.6.0).
* Feature: Add missing support for native Woo EAN/Barcode field.
* Improvement: Easier XML generation via cron. More details on the Doc for developers.
* Improvement: Better instructions for the checkbox that enables the extra EAN/Barcode field.
* Fix: Fix availability column width in admin product list, caused by a deprecated class name.
* Compatibility: Checked with the latest WordPress and WooCommerce versions.

= 1.0.1 =
*Release Date - 26 Dec 2024*
* Feature: Support for XML generation via WP-CLI.

= 1.0.0 =
*Release Date - 20 Dec 2024*
* Initial public release.