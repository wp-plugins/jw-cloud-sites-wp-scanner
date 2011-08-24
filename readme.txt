=== Cloud Sites WP Scanner ===

Contributors:      madjax
Plugin Name:       Cloud Sites WP Scanner
Plugin URI:        http://jacksonwhelan.com/plugins/cloud-sites-wp-scanner/
Tags:              Rackspace, Cloud Sites, security, permissions, hack
Requires at least: 2.9.2 
Tested up to:      3.2.1
Stable tag:        2.3.6
Version:           2.3.6

Some tools for securing WordPress on Rackspace's Cloud Sites hosting.

== Description ==

With this plugin you can easily adjust your file permissions on Rackspace Cloud Sites hosting for a more secure installation. You can remove write permissions, and then revert when needed to allow upgrading, theme and plugin installation. Also includes a few extra tools to look for and eliminate other common exploits in the cloud, looking for hidden php files, non-core files, modified core files, auto loading options with malicious code, and posts/pages injected with javascript.

Apologies for the rough edges, but  it's getting the job done. More to come.

PS - This plugin is for Rackspace Cloud Sites, do not use elsewhere.

Many thanks to donncha, duck_, ryan, azaozz, tott - authors of Exploit Scanner (http://wordpress.org/extend/plugins/exploit-scanner/) - for the file hashes.

== Installation ==

1. Ask yourself - "Am I using Rackspace Cloud Sites to host my website?" If the answer is yes, proceed to number 2.

2. Activate plugin.

3. Ask yourself - "Where is my website hosted?" If the answer is "Rackspace Cloud Sites", proceed to number 4.

4. From the admin menu, visit Tools > JW CS+WP Scanner.

== Support ==

http://wordpress.org/tags/jw-cloud-sites-wp-scanner?forum_id=10

== Changelog ==

= 2.3.6 =
* Add new WP version hashes, and update readme
* Extra reminders that this only works for Rackspace Cloud Sites, props to lady who installed elsewhere and called me at 3am when she hosed her site.

= 2.3.5 =
* Add hashes for 3.0.5 and 3.1

= 2.3.4 =
* Add hashes for 3.0.4

= 2.3.3 =
* Add hashes for 3.0.2
* Add hashes for 3.0.3

= 2.3.2 =
* Add hashes for 3.0.1
* Stop running post scan and core file scan by default.

= 2.3.1 =
* Fix hash loading problem.

= 2.3 =
* Eliminate false positives from option table scan.
* Include file hashes from Exploit Scanner to detect non-core files, and modified core files.
* Track deleted options in addition to deleted files.

= 2.2 =
* First public release.