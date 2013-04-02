=== FV Feedburner Replacement ===

Contributors: FolioVision
Tags: feedburner,feed,subscribe,newsletter
Requires at least: 3.5
Tested up to: 3.5.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Creates a landing page for your feed subscription and out of the box working newsletter subscription form.


== Description ==

Since Feedburner is no longer supported, we created this plugin to help you migrate your subscribers.

Features:

* Boost number of your newsletter subscribers by putting subscription form to your feed address
* Tell your Feedburner subscribers to re-subscribe on your new feed address when they read the feed
* Show a subscription form for your Feedburner subscribers when they come to your website 
* Out of the box working newsletter subscription form
* Default subscription form works with [Newsletter](http://wordpress.org/extend/plugins/newsletter/) plugin by Satollo and uses double opt-in (confirmation emails)
* CSV export for subscribers who sign up using the default work.

**[More Information](http://foliovision.com/seo-tools/wordpress/plugins/fv-feedburner-replacement/)**

[Support](http://foliovision.com/support/fv-feedburner-replacement/) | [Change Log](http://foliovision.com/seo-tools/wordpress/plugins/fv-feedburner-replacement/changelog/) | [Installation](http://foliovision.com/seo-tools/wordpress/plugins/fv-feedburner-replacement/installation/)


== Installation ==

1. Install the plugin using Wordpress or copy the plugin directory into your Wordpress plugins directory (usually wp-content/plugins) and activate it in Wordpress admin panel.
2. After activation, check the update notice - it will contain a link to test your feed.
3. If you use WP Super Cache, follow the intructions in the plugin activation notice to disable feed caching, or see [Installation guide](http://foliovision.com/seo-tools/wordpress/plugins/fv-feedburner-replacement/installation/)


== Screenshots ==

1. Plugin settings screen.

== Changelog ==

= 0.2 =
* Bugfix for edit page link 
 
= 0.1 =
* Initial release


== Frequently Asked Questions ==

= How does it work? =

We use the same technique as Feedburner - if you come in using a RSS reader, http://your-site.com/feed gives you the feed content, but if you come in via a web browser, you get a page with the subscription options (newsletter, feed links). 

= Does it work with caching? =

Since we check the HTTP referrer, we can't cache what gets served on the /feed URL. So in WP Super Cache, you have to exclude feeds from caching, see the [installation guide](http://foliovision.com/seo-tools/wordpress/plugins/fv-feedburner-replacement/installation/). Hyper Cache has feed caching turned off by default and it also [scores better in our tests](http://foliovision.com/2012/09/wordpress-speed-test-wp-super-cache-vs-hypercache).