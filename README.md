# Dreamy Tags

![alt Dreamy Tags Banner](dreamy-tag-cloud/assets/banner-772x250.png)

![alt Dreamy Tags Icon](dreamy-tag-cloud/assets/icon-128x128.png)

The Dreamy Tags plug-in will allow you to display a tag cloud that filters based on category and tags. You can use Short Code or Gutenberg Code Blocks.

![alt Dreamy Tags Example](dreamy-tag-cloud/assets/example-512x512.jpg)

# Short Code

`[dreamy_tags cat="786439348" tags="786439775" exclude="786439762,786439759" auto_exclude="true"]`

* cat (optional, comma-delimited) - the category id's that posts must have at least one
* tags (optional, comma-delimited) - the tag id's that a post must have at least one
* exclude (optional, comma-delimited) - tag id's that should be excluded from the cloud
* auto_exclude (optional, boolean [true]) - indicates filtered tags should be excluded from the cloud

# Gutenberg Code Blocks

The code block settings map to the short code.

* Filter Categories - A post must be in one of these categories
* Filter Tags - A post must have one of these tags
* Exclude Tags - The following tags will not appear in the cloud
* auto-exclude filtered tags - The filter tags will not appear in the cloud

## Build
1. Open Terminal.
2. Navigate to your project folder (type `cd` and drag the folder in).
3. Run this command to make the script executable: `chmod +x build.sh`
4. Execute the script `./build.sh`
5. Upload the `dreamy-tag-cloud-v#.#.#.zip` to your wordpress plugin dashboard.