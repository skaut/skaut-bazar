=== Scout bazar ===
Contributors: skaut, kalich5, davidulus, kulikjak, rbrounek, marekdedic
Tags: bazar, skaut, multisite, plugin, shortcode
Requires at least: 5.0
Tested up to: 5.9
Requires PHP: 5.6
Stable tag: 1.3.6
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Implementation of a simple bazaar with the possibility of online booking via email.

== Description ==

Implementation of a simple bazaar with the possibility of online booking via email.

Once activated, the plugin is inserted into any page using Shortcodes: **[skautbazar]**

The plugin also supports MultiSite, so you can have a different bazaar on each page, with its own settings and everything connected with it. There is a possibility of value ranges in the settings. That is, name, reception, email and phone. Everything but the phone is required.
When creating a new advertisement, the required fields are marked with an asterisk.

**User roles**

Ability to create a "Bazar" role (or roles) using some role creation plugin [WPFront User Role Editor](https://cs.wordpress.org/plugins/wpfront-user-role-editor/) or [User Role Editor](https://cs.wordpress.org/plugins/user-role-editor/), which may have rights only to advertisements.

== Installation ==

Installation is simple.

1. Download the plugin and activate
2. Default setting "settings -> Scout bazar"
	Set basic information about yourself in this setting
3. Load the page where you want to dump ads shordtcode: **[skautbazar]**

== Frequently Asked Questions ==

**How to set the plugin correctly?**

You have to go to "Settings" and find there the item "Scout Bazar" and there is the default setting.

You can enter your name, surname, email, telephone number, currency and the initial number of the advertisement.

Ability to create a "Bazar" role (or roles) using some role creation plugin [WPFront User Role Editor](https://cs.wordpress.org/plugins/wpfront-user-role-editor/) or [User Role Editor](https://cs.wordpress.org/plugins/user-role-editor/), which may have rights only to advertisements.

**Plugin support**

Official support is on [http://dobryweb.skauting.cz/](http://dobryweb.skauting.cz/)

**GITHUB**

[https://github.com/skaut/skaut-bazar](https://github.com/skaut/skaut-bazar)

== Screenshots ==

1. Impressions on pages
2. Listing of all bazaar items
3. Create a new item
4. Settings

== Changelog ==

= 1.3.6 =
* Fixed the "Interested" button

= 1.3.5 =
* Added data sanitization to post title

= 1.3.4 =
* Fixed security issues

= 1.3.3 =
* Error correction

= 1.3.2 =
* Minimum version of WordPress 4.9.6

= 1.3.1 =
* Added the ability to translate directly to WordPres.org

= 1.3 =
* Added the ability to send a message to the bidder
* Added option to hide last name
* Automatic filling of name and email into forms
* Repair of smaller glitches

= 1.2 =
* Fix user roles and add them
* After deleting the plugin, the roles created by the plugin will also be deleted

= 1.1 =
* Adding the ability to create roles for the plugin

= 1.0.4 =
* Icon

= 1.0.3 =
* Added introductory photos
* Screenshots

= 1.0.2 =
* Fixes on wordpress.org

= 1.0.1 =
* Fixes on wordpress.org

= 1.0 =
* Possibility to create advertisements
* Booking via email
* MultiSite support
* Category and tag support
* Translation of EN and CZ
* Support for shortcodes
