=== CC-Cache ===
Contributors: ClearcodeHQ, PiotrPress
Tags: cache, apache, mod_rewrite, clearcode
Requires at least: 4.4.2
Tested up to: 4.6.1
Stable tag: trunk
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

A simple and fast cache plugin based on static-rendered html files served by Apache with mod_rewrite.

== Description ==

The CC-Cache plugin supports caching of Posts, Pages, and any public Custom Post Types - including single and archive pages.
It also supports a "static page display" option: Front page, Posts page, and standard Latest Posts listing. 
This plugin is compatible with Multisite WordPress installations.

= How does it work? =

When a user (i.e. someone who is logged out of WordPress) opens a page for the first time, the plugin saves all the rendered html to a file in the wp-content/cache directory.
From this moment onwards, any user who accesses the site will be served content directly from the generated html file. WordPress at this point is not initialized for this page.
The generated cache file will be removed when you make changes to the corresponding Post/Page, and then the process starts from the beginning.
You can also clear all cached files from the Cache options page (visit the 'Settings > Cache' page in wp-admin), or manually delete files from the wp-content/cache directory.

= Tips & Tricks =

You can check if a page's content is served from a cache file by opening the page's source code in the browser and scrolling down to the closing `<body>` html tag.
If the content is cached, you should see a comment with the date and time the page was last cached, for example:
`<!-- Cache @ 2016-04-15 12:34:56 -->`

You can disable cache for a single request and get the raw html by adding `cache=false` to a URL's parameter, for example:
`http://example.com/?cache=false`

You can disable cache for a single request and get the raw html by adding `cache=false` HTTP header.

Logged-in users always get the raw html.

= Ideas for future versions =

1. Add support for Categories, Tags, Taxonomies, and Authors.
2. Add support for RSS/Atom feeds.
3. Add support for Nginx and IIS servers.
4. Add support for other WordPress Filesystem Methods.
5. Add regenerate function for all cache files.

== Installation ==

= From your WordPress Dashboard =

1. Go to 'Plugins > Add New'
2. Search for 'CC-Cache'
3. Activate the plugin from the Plugin section in your WordPress Dashboard.

= From WordPress.org =

1. Download 'CC-Cache'.
2. Upload the 'cc-cache' directory to your '/wp-content/plugins/' directory using your favorite method (ftp, sftp, scp, etc...)
3. Activate the plugin from the Plugin section in your WordPress Dashboard.

= Once Activated =

1. Visit the 'Settings > Cache' page, select your preferred options and save them.
2. Add the following constant to your `wp-config.php` file: `define( 'FS_METHOD', 'direct' );`.
3. Add the rule listed in 'Settings > Cache' to the beginning of your `.htaccess` file.

You can disable the cache function for individual posts and pages by marking the Disable checkbox in Edit Post/Edit Page.

= Multisite =

The plugin can be activated and used for just about any use case.

* Activate at the site level to load the plugin on that site only.
* Activate at the network level for full integration with all sites in your network (this is the most common type of multisite installation).

== Requirements ==

1. Apache server
2. mod_rewrite
3. Write access to wp-content/cache directory
4. PHP interpreter version >= 5.3

== Screenshots ==

1. **WordPress Cache Settings** - Visit the 'Settings > Cache' page, select your preferred options and save them.
2. **Post Cache Settings** - You can disable the cache function for individual posts and pages by marking the Disable checkbox in Edit Post/Edit Page.

== Changelog ==

= 1.3.1 =
*Release date: 03.10.2016*

* Corrected notice of undefined index: clear

= 1.3.0 =
*Release date: 15.09.2016*

* Added 'save empty cache file and incorrect content prevention' feature by internal request verification.
* Added feature to check cookies and disable cache for logged-in users.
* Added feature to check for cache=false HTTP header to disable cache for single requests.
* Corrected error when using object (e.g. table) in .htaccess rules generation.

= 1.2.0 =
*Release date: 05.09.2016*

* Added .htaccess file to increase plugin's security.
* Added verification condition for ABSPATH constant defined for all php files, including templates
* Added condition to enqueue css style file only when admin bar displays.
* Added support for get_sites function introduced in WordPress 4.6 version.
* Added new filters to templates vars.
* Added try/catch block to support Minify exceptions.
* Moved div and span html code to corresponding template files.
* Corrected error of missing class on activation.
* Corrected error with dirlist() function.

= 1.1.0 =
*Release date: 20.05.2016*

* Added auto regenerate cache files function when saving post.
* Added Minify HTML option.
* Added cache=false URL's parameter to disable cache for single request.

= 1.0.0 =
*Release date: 16.04.2016*

* First stable version of the plugin.