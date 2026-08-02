=== YOGA — YouTube Opportunity & Growth Analyzer ===
Contributors: bestyoutubeviews
Tags: youtube, analyzer, video, restrictions, embeddability, growth
Requires at least: 6.2
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.1.2

An installable first build of YOGA for BestYouTubeViews.

== Installation ==

1. Upload and activate the plugin ZIP in WordPress.
2. Open Settings → YOGA Analyzer.
3. Add a YouTube Data API v3 key.
4. Create a page, ideally /free-tools/yoga/, and insert: [yoga_analyzer]
5. Test with a public YouTube video.

You can store the API key outside the database by adding this to wp-config.php:

define('YOGA_YOUTUBE_API_KEY', 'YOUR_KEY_HERE');

== Included in 0.1.0 ==

* Post-publish URL-only workflow.
* Support for standard YouTube links, youtu.be, Shorts, live URLs, embed URLs and direct video IDs.
* Video, channel and recent-upload context via YouTube Data API v3.
* Country restrictions, age restriction, embeddability, captions, definition, license and public settings.
* Public performance context and positive momentum labels.
* Rule-based operational action plan.
* Email gate with separate optional marketing consent.
* Saved private report link.
* JSON and CSV export plus copyable actions.
* CTA to the existing professional Video & Channel Analysis service.
* Rate limiting and API response caching.

== Deliberately deferred ==

* Pre-publish module.
* PDF and XLSX export.
* Visual world map.
* YouTube Studio CSV/screenshot import.
* AI-assisted title, description and thumbnail suggestions.
* OAuth/private videos.
* Continuous monitoring.


== 0.1.1 ==
* Removed the standalone app-style visual skin.
* YOGA now inherits the active BestYouTubeViews theme typography, buttons and colors.
* Removed gradients, shadows, pills and rounded corners.
* Changed all tool sections to flat, square, in-content layouts designed for the normal page/product content area.

== 0.1.2 ==
* Corrected theme collision that made mode selectors almost invisible on BestYouTubeViews.
* Mode selectors now use the exact BestYouTubeViews red and standard content text colors.
* Removed decorative mode icons and strengthened the flat, square-edged tab treatment.
* Preserved full inheritance of the theme typography and standard button/input styles.
