# YU Kaltura Media Package

"YU Kaltura Media Package" is a third-party's Kaltura plugin package for Moodle 2.9 or later.
This package is developed by the Media and Information Technology Center, Yamaguchi University.
By using this package, users can upload media to the Kaltura server, and easily embed the media in Moodle courses.
Moreover, this package provides some useful functions.
Since this package does not require Kaltura Application Framework (KAF), can work with Kaltura Community Edition (CE) and other editions.

In order to use this package, administrators must install "[YU Kaltura Media Local Libraries](https://moodle.org/plugins/local_yukaltura)" and "[YU Kaltura Media Gallery](https://moodle.org/plugins/local_yumymedia)".
These plugins provide functions such as uploading, playing back and deleting media files to users.

In addition, the administrators can install "[YU Kaltura Media Assignment](https://moodle.org/plugins/mod_kalmediaassign)" and "[YU Kaltura Media Resource](https://moodle.org/plugins/mod_kalmediares)".
These plugins provide teachers ability of creating resource and activity modules which use kaltura media in their Moodle courses.

Please note that there is a chance this module will not work on some Moodle environment.
Also, this package is only available in English. Stay tuned to future versions for other language supports.

Original plugin package ("Kaltura Video Package") has better functions than ours and is easy to use. So that, for customers of the "Kaltura SaaS Edition", use the original plugin package is the better.

YU Kaltura Media Resource for Moodle
------

This is a resource module.
Teachers can create media play page (embed media) in their courses, and can view students' play/view status.
Teachers can choose a media player from Kaltura players, and can set player's size (dimension).
Aditionally, the teacher can upload and record new media in resource editing page.
Students can play the embedded media.
This plugin is updated with stable releases. To follow active development on GitHub, click [here](https://github.com/YU-MITC/moodle-mod_kalmediares/).

Requirements
------

* PHP5.3 or greater.
* Web browsers must support the JavaScript and HTML5.
* System administrators must use the same communication protocol for all routes (between the web browser and the Moodle, between the Moodle and the Kaltura, and between the web browser and the Kaltura). It is better to use HTTPS as the communication protocol.
* Administrators must not delete "Default" access control profile from their Kaltura server. If they delete the "Default" profile, they must create new profile named "Default" before install our plugins.
* These plugins do not support Flash players. Therefore, please use HTML5 players.
* "local_yukaltura" and "local_yumymedia" plugins.

Supported themes
-----

* Clean
* Boost (version 1.1.7 and later)
* Classic (version 1.3.0 and later)

This plugin package might be able to work with other themes.

Installation
------

Unzip this plugin, and copy the directory (mod/kalmediares) under moodle root directory (ex. /moodle).
Installation will be completed after you log in as an administrator and access the notification menu.

After upgrading the plugin from version 1.2.x to 1.3.x (or later version), the administrators must execute the following command:

php /path/to/moodle/mod/kalmediares/cli/log_migration_1.2to1.3.php

This script reads the access logs of students from the Moodle standard log, and inserts records to new database table.
The verison 1.3.0 and laters use this table in order to display an access status list of students.

How to use
------

* User's guide, click [here](http://www.cc.yamaguchi-u.ac.jp/guides/cas/plugins/userguide_version1.3.pdf).
* Demonstration web page, click [here](http://www.cc.yamaguchi-u.ac.jp/guides/cas/plugins/demo/).

Targeted Moodle versions
------

Moodle 2.9, 3.0, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7

Branches
------

* MOODLE_29_STABLE -> Moodle2.9 branch
* MOODLE_30_STABLE -> Moodle3.0 branch
* MOODLE_31_STABLE -> Moodle3.1 branch
* MOODLE_32_STABLE -> Moodle3.2 branch
* MOODLE_33_STABLE -> Moodle3.3 branch
* MOODLE_34_STABLE -> Moodle3.4 branch
* MOODLE_35_STABLE -> Moodle3.5 branch
* MOODLE_36_STABLE -> Moodle3.6 branch
* MOODLE_36_STABLE -> Moodle3.7 branch

First clone the repository with "git clone", then "git checkout MOODLE_29_STABLE(branch name)" to switch branches.

Warning
------

* We are not responsible for any problem caused by this software. 
* This software follows the license policy of Moodle (GNU GPL v3).
* "Kaltura" is the registered trademark of the Kaltura Inc.
* Web-camera recording function in "My Media" supports the Mozilla Firefox, Google Chrome, Opera and Safari. For smartphones and tablets, you can record movies through a normal media uploader.
* Uploading and recording functions in resource and activity modules may not work well with smartphones. Because, low resolution screen cannot display these forms correctly.

Change log of YU Kaltura Media Resource
------

Version 1.3.0

* fixed statements in lib.php, renderer.php, export_excel.php, view.php, trigger.php, install.xml and upgrade.php, in order to reduce a time it takes to display students' access logs.
* fixed some statements in backup_kalmediares_stepslib.php, in order to backup resource's informations correctly.

Version 1.2.2

* fixed some statements (about UIConf ID) in view.php, in order to solve a problem that unnecessary javascript codes are loaded.
* fixed some statements in lib.php and mod_form.php, in order to display module's introduction on a moodle course page.

Version 1.2.1

* fixed some statements in renderer.php, according to changes of local plugin (local_yukaltura).
* executed minimization to playtrigger.js, base on JSDoc warnings.

Version 1.2.0

* fixed some statements in view.php, in order to permit teachers to upload/record new movie in editing page of resource module (In order to permit upload/record, administrators must set some items in configuration page of local_yukaltura).
* fixed some statements in media_resource_played.php, and media_resource_viewed.php, in order to respond to backup and restore mechanisms in recently versions of Moodle.

Version 1.1.8

* added statements about "Requirements" in README.md.
* fixed copyright statements in all scripts.

Version 1.1.7

* added statements about "Supported themes" in README.md.

Version 1.1.6

* added functions for course reset in lib.php. Actually, these functions do nothing.
* fixed statements about "How to use" in README.md.

Version 1.1.5

* added statements in README.md.

Version 1.1.4

* fixed issue that an error occurs in the "Administration->Course completion".
* supports auto completion tracking.

Version 1.1.3

* supported "Chrome OS" for recording view/play logs.

Version 1.1.2

* added statements in README.md.
* fixed last access timestamp and sort/order issue of access list (in renderer.php and export_excel.php).

Version 1.1.1

* fixed statements in README.md.

Version 1.1.0

* fixed some login check statement.

