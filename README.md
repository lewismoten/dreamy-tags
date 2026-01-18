# Dreamy Tags

![alt Dreamy Tags Banner](dreamy-tags/assets/banner-640×320.jpg)

![alt Dreamy Tags Icon](dreamy-tags/assets/icon-128x128.png)

The Dreamy Tags plug-in will allow you to display a tag cloud that filters based on category and tags. You can use Short Code or Gutenberg Code Blocks.

# Short Code

`[dreamy_tags cat="786439348" tags="786439775" exclude="786439762,786439759" auto_exclude="true" min_count="5"]`

All arguments are optional. `cat`, `tags`, and `exclude` are comma-delimited.

* cat - the category id's that posts must have at least one
* tags - the tag id's that a post must have at least one
* exclude - tag id's that should be excluded from the cloud
* auto_exclude (boolean [true]) - indicates filtered tags should be excluded from the cloud
* min_count - (number [1]) - Minimum number of occurences tag must appear on a post filtered by cat & tags.

# Code Blocks

![alt Dreamy Tags Example](dreamy-tags/assets/block-settings.png)

The code block settings map to the short code.

* Filter Categories - A post must be in one of these categories
* Filter Tags - A post must have one of these tags
* Exclude Tags - The following tags will not appear in the cloud
* auto-exclude filtered tags - The filter tags will not appear in the cloud
* Minimum posts per tag - A tag must appear this many times in the filtered posts before it appears

## Build
1. Open Terminal.
2. Navigate to your project folder (type `cd` and drag the folder in).
3. Run this command to make the script executable: `chmod +x build.sh`
4. Execute the script `./build.sh`
5. Upload the `dreamy-tags-v#.#.#.zip` to your wordpress plugin dashboard.