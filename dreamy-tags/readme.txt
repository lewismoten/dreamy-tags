=== Dreamy Tags ===
Contributors: lewismoten
Tags: tag cloud, taxonomy, categories, filter, widget
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.50
License: GPLv2 or later

Dreamy Tags displays a customizable tag cloud filtered by categories and tags for clean, meaningful blog and archive navigation.

== Screenshots ==
1. Block editor settings for Dreamy Tags.
2. Example of a filtered tag cloud output.

== Description ==

A specialized tag cloud generator designed for blogs, archives, and taxonomy-based layouts. Dreamy Tags allows you to filter displayed tags by category, exclude organizational tags, and control minimum usage thresholds for cleaner, more meaningful tag clouds.

== Installation ==
You can install Dreamy Tags directly from the WordPress Plugin Directory.

1. Upload the `dreamy-tags` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Use Dreamy Tags in one of the following ways:
   * Add the “Dreamy Tags” widget via Appearance > Widgets or the Site Editor.
   * Insert the “Dreamy Tags” block in the block editor.
   * Use the [dreamy_tags] shortcode.

== Development ==
Source code and build tools are maintained publicly on GitHub: https://github.com/lewismoten/dreamy-tags
Build instructions are documented in the repository README.

== Developer Notes ==
Developers may adjust the maximum number of posts scanned by the block
using the `lewismoten_dreamy_tags_max_posts` filter.

Default: 2000

Example:
add_filter( 'lewismoten_dreamy_tags_max_posts', function () {
    return 10000;
});

== License ==

This plugin is licensed under the GPLv2 or later.

All artwork and icons included with this plugin were created by the author and are licensed under the same GPL license as the plugin.

== Upgrade Notice ==

= 1.0.50 =
Initial WordPress.org release.

== Changelog ==

= 1.0.71 =
* bring back svg icon (#8)

= 1.0.70 =
* fix second Toggle control warning (#8)

= 1.0.69 =
* fix next warnings for Toggle control (#8)

= 1.0.68 =
* fix next warnings for UI controls (#8)

= 1.0.67 =
* fix warning for ComboBox size (#8)

= 1.0.66 =
* remove dupe/alt style loading (#8)

= 1.0.65 =
* use createElement alias (#8)

= 1.0.64 =
* fix version in canonical header (#8)

= 1.0.63 =
* fix block icon with svg (#8)

= 1.0.62 =
* do not inject version in widget (#8)

= 1.0.61 =
* avoid confusing plugin installer (#8)

= 1.0.60 =
* separate images from directory assets (#8)

= 1.0.59 =
* fix nested double-quotes (#8)

= 1.0.58 =
* canonical header and abspath style (#8)

= 1.0.57 =
* load language for text domain (#8)

= 1.0.56 =
* exclude directory assets (#8)

= 1.0.55 =
* include non-directory assets (#8)

= 1.0.54 =
* consolidate unique prefix (#8)

= 1.0.53 =
* use wp_enqueue commands for styles (#8)

= 1.0.51 =
* exclude directory assets (#8)

= 1.0.50 =
* Add explicit license disclosure for bundled artwork
* Minimum occurrences defaults to 2
* Add block preview image
* Toggle to include child categories
* Converted Unicode in files to ASCII
* Append change log via build script
* Add minimum tag threshold
* Show tag size relative to subset of posts matching
* Tags and filters can be searched by name in block editor settings
* Functional filtering and exclusion logic
