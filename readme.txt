=== FV Feedburner Replacement ===

Contributors: FolioVision
Tags: feedburner,feed,subscribe,newsletter
Requires at least: 3.5
Tested up to: 3.5.1
Stable tag: 0.2.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Creates a landing page for your feed subscription and out of the box working newsletter subscription form.


== Description ==

Feedburner was a great service. It's a bit of a mystery why Google is shutting down Reader and Feedburner which were both popular services and offered Google valuable content intelligence. The shutdown is a real wakeup call to those of us who depend on external services.

Since Feedburner is no longer supported, we created this plugin to help you migrate your subscribers and to allow you to keep your feed on a URL controlled by you going forward. You will be immune to feed companies going out of business, shutting down services or hiking prices in the future. Take control of your own feed and your own subscribers!

Benefits:

* Boost number of your newsletter subscribers by putting subscription form to your feed address
* Keep your Feedburner RSS readers! When Feedburner subscribers come to your site, they will automatically be prompted to re-subscribe on your new feed address when they read the feed
* Upgrade RSS readers to email recipients. Show a subscription form for your Feedburner subscribers when they come to your website.
* Easy to set up and get started: built-in newsletter subscription form works out of the box
* Default subscription form works with [Newsletter](http://wordpress.org/extend/plugins/newsletter/) plugin by Satollo and uses double opt-in (confirmation emails)
* Optional CSV export for subscribers who sign up before you add your own mailing solution.

Never pay monthly fees no matter how many subscribers you have. Never risk losing your subscribers again.

**[More Information](http://foliovision.com/seo-tools/wordpress/plugins/fv-feedburner-replacement/)**

[Support](http://foliovision.com/support/fv-feedburner-replacement/) | [Change Log](http://foliovision.com/seo-tools/wordpress/plugins/fv-feedburner-replacement/changelog/) | [Installation](http://foliovision.com/seo-tools/wordpress/plugins/fv-feedburner-replacement/installation/)


== Installation ==

1. Install the plugin using Wordpress's built in installer or copy the plugin directory into your Wordpress plugins directory via ftp (usually wp-content/plugins) and activate it in Wordpress admin panel.
2. After activation, check the update notice - it will contain a link to test your feed.
3. If you use WP Super Cache, follow the intructions in the plugin activation notice to disable feed caching, or see [Installation guide](http://foliovision.com/seo-tools/wordpress/plugins/fv-feedburner-replacement/installation/)


== Screenshots ==

1. Plugin settings screen.

== Changelog ==

= 0.2.5 =
* Another test commit

= 0.2.4 =
* Another test commit

= 0.2.3 =
* And another test commit

= 0.2.2 =
* Another test commit

= 0.2.1 = 
* Testing new Commit script

= 0.2 =
* Bugfix for edit page link 
 
= 0.1 =
* Initial release


== Frequently Asked Questions ==

= How does it work? =

We use exactly the same technique as Feedburner - if you come in using a RSS reader, http://your-site.com/feed gives you the feed content, but if you come in via a web browser, you get a page with the subscription options (newsletter, feed links). 

= Does it work with caching? =

Since we check the HTTP referrer, we can't cache what gets served on the /feed URL. So in WP Super Cache, you have to exclude feeds from caching, see the [installation guide](http://foliovision.com/seo-tools/wordpress/plugins/fv-feedburner-replacement/installation/). Hyper Cache has feed caching turned off by default and it also [scores better in our tests](http://foliovision.com/2012/09/wordpress-speed-test-wp-super-cache-vs-hypercache).
