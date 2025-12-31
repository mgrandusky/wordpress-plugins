=== YouTube Feed Display ===
Contributors: masongrandusky
Donate link: https://example.com/donate
Tags: youtube, feed, video, gallery, lightbox
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display your YouTube channel feed in a responsive grid with a built-in lightbox player.

== Description ==
YouTube Feed Display shows the latest videos from a YouTube channel in a responsive tile grid. Includes:

* Thumbnail grid with play overlay
* Click thumbnails to open videos in a responsive lightbox (autoplay)
* Title links optionally open in lightbox on mobile
* Start-time support via `?t=` or `?start=` in links
* Simple settings page to configure API key, channel ID, number of videos, and lightbox behavior

== Installation ==
1. Upload the `youtube-feed-display` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to YouTube Feed → YouTube Feed Settings to set your API key and Channel ID
4. Add shortcode `[youtube_feed]` to a page to display the feed

== Frequently Asked Questions ==
= Why is nothing displayed? =
Ensure you have set a valid YouTube Data API v3 key and the correct channel ID in the plugin settings.

= How do I make titles open in the lightbox on desktop? =
Go to Lightbox settings and enable "Force Titles in Lightbox".

== Screenshots ==
1. Grid layout with overlay play icon
2. Lightbox player opened on click

== Changelog ==
= 1.0.0 =
* Initial release with lightbox, settings, and start-time support

== Upgrade Notice ==
No upgrade notice for initial release.
