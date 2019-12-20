=== Fix Distributor Post Parents ===
Contributors: hugomoran
Tags: distributor, post, page, extension
Requires at least: 3.8
Tested up to: 4.9
Stable tag: 1.1.0.0
License: GPLv2
Fixes handling of post relationships while distributing.
== Description ==
This plugin will prevent Distributor from deleting the post parent - child
relationships when a post is being pushed. In addition it will make sure not to
override an existing post parent.
== Installation ==
1. Upload 'plugin-directory' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' screen in WordPress
== Frequently Asked Questions ==
= Will this plugin work while pulling posts? =
Nope.
= Does this plugin work with WordPress Multisite? =
Absolutely! This plugin has been tested and
verified to work on the most current version of WordPress Multisite
== Changelog ==
= 1.0 =
* First official release