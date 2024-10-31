=== NEVISTAS News ===
Contributors: twahl
Tags: widget, plugin, nevistas, news, airline news, airline industry news, cruise news, cruise industry news, gaming news, gaming industry news, hotel news, hotel industry, restaurant news, restaurant industry news, travel news, travel industry news, yoga news, travel consumer news, rss, feed
Requires at least: 2.3.3
Tested up to: 2.9.2
Stable tag: trunk

Displays news items from selectable Nevistas News RSS feeds, inline, as a widget or in a theme. Multiple feeds allowed. Caching.
== Description ==

Nevistas has a number of RSS feeds with current news available, on a 
number of topics including airline industry news, 
cruise industry news, hotel industry news, gaming industry news, 
otel industry news, restaurant industry news, travel industry news, 
travel comsumer news and yoga news. 

This widget allows the WP admin to select which
feed, how many items to show from that feed and optionally set a 
widget title. If no title is selected, the name of the feed is 
used. The feed is fetched for every view, so users are guaranteed
up to date information. No local storage of feed is done.
Clicking on a news item will of course take you straight to the
relevant article on the relevant Nevistas web site.

This plugin works both as a widget, as inline content
replacement and can be called from themes. Any number of 
inline replacements or theme calls allowed, but only one 
widget instance is supported in this release.

IMPORTANT - in order to call content you need to
first setup feeds in the Settings -> Nevistas News administration
area.

Leave the Key field blank, and select your default feed from the 
Feed pulldown menu and click save.

WIDGET
For widget use, simply use the widget as any other after
selecting which feed it should display. 

For INLINE CONTENT replacement, insert the one or more of 
the following strings in your content and they will be replaced by the relevant news feed.
For theme use, add the do_action function call described below.

1. **`<!--nevistas_news-->`** for the default feed
1. **`<!--nevistas_news#feedname-->`**

Shortcodes can be used if you have WordPress 2.5 or above,
in which case these replacement methods are also available.

1. **`[nevistas_news]`** for the default feed
1. **`[nevistas_news name="feedname"]`**

PLUGIN
Calling the plugin from a theme is done with the WP do_action()
system. This will degrade gracefully and not produce errors
or output if plugin is disabled or removed.

1. **`<?php do_action('nevistas_news'); ?>`** for the default feed
1. **`<?php do_action('nevistas_news', 'feedname'); ?>`**

Enable plugin, go to the Nevistas News page under 
Dashboard->Settings and read the initial information. Then 
go to the Nevistas News page under Dashboard->Manage and 
configure one or more feeds. Then use a widget or insert
relevant strings in your content or theme. 

Additional information:

The available options are as follows. 

**Name:** Optional feed name, that can be used in the 
widget or the inline replacement string to reference
a specific feed. Any feed without a name is considered
"default" and will be used if the replacement strings do
not reference a specific feed. If there are more than
one feed with the same name, a random of these is picked
every time it is used. This also applies to the default
feed(s). 

**Title:** Optional, which when set will be used in the
widget title or as a header above the news items when 
inline. If the title is empty, then a default title
of "Nevistas News : &lt;region&gt; : &lt;feed type&gt;" is used. 

**Feed:** A dropdown list of the current feeds provided
by Nevistas. This list is hard coded into the plugin, presumably
Nevistas does not change the list too often.

**News item length:** Short or long. The short version is really just 
the news item title as a one liner but probably the one most 
WP admins will use. The long version is the title followed by
a 3-4 line teaser. For the short version, the long text is 
available as a mouse rollover/tooltip.

**Max items to show:** As the title says, if the feed has
sufficient entries to fulfil the request. 

**Cache time:** The feeds are now fetched using WordPress 
builtin MagpieRSS system, which allows for caching of feeds
a specific number of seconds. Cached feeds are stored in
the backend database.

If you want to change the look&feel, the inline table is 
wrapped in a div with the id "nevistas_news-inline" and the
widget is wrapped in an li with id "nevistas_news". Let me 
know if you need more to properly skin it.

**[Download now!](http://nevistas.com/wordpress/download.php?f=nevistasnews.zip)**

[Support](http://www.hotelnewsresource.com/FBack-index.html)


== Installation ==

This section describes how to install the plugin and get it working.

1. Unzip into the `/wp-content/plugins/` directory.
2. Activate the plugin through the Dashboard->Plugins admin menu.
3. Set parameters - See Nevistas News configuation pages under Dashboard->Settings.
4. Activate the widget under Dashboard->Manage and on the widget page.

== Frequently Asked Questions ==

= Do you provide an API for your feeds =

Yes we do. Please call 416-840-6565 to receive a Developer API key

== Screenshots ==

1. screenshot_1.png
2. screenshot_1.png
== Changelog ==

1. 1.0 Initial release
1. 1.1 Fixed incorrect tools url


Known bugs:
  - None at this time
