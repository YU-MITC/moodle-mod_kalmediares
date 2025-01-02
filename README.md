# YU Kaltura Media Package
"YU Kaltura Media Package" is a third-party's Kaltura plugin package (a series of plugins) for Moodle 4.3 or later. This package is developed by the Center for Information Infrastructure, Yamaguchi University. By using this package, users can upload media to the Kaltura server, and easily embed the media in Moodle courses. Moreover, this package provides some useful functions. Since this package does not require Kaltura Application Framework (KAF), can work with Kaltura Community Edition (CE) and other editions.

In order to use this package, administrators must install "[YU Kaltura Media Local Libraries](https://moodle.org/plugins/local_yukaltura)" and "[YU Kaltura Media Gallery](https://moodle.org/plugins/local_yumymedia)".
These plugins provide functions such as uploading, playing back and deleting media files to users.

In addition, the administrators can install "[YU Kaltura Media Assignment](https://moodle.org/plugins/mod_kalmediaassign)", "[YU Kaltura Media Resource](https://moodle.org/plugins/mod_kalmediares)", and "[YU Kaltura Media for Atto](https://moodle.org/plugins/atto_yukaltura)".
These plugins provide teachers ability of creating resource and activity modules which use kaltura media in their Moodle courses.
And, user can embed his/her media into text area (introduction or page content) through the Atto HTML editor.

Please note that there is a chance this module will not work on some Moodle environment. Also, this package is only available in English and Japanese. Stay tuned to future versions for other language supports.

Original plugin package ("Kaltura Video Package") has better functions than ours and is easy to use. So that, for customers of the "Kaltura SaaS Edition", use the original plugin package is the better.

YU Kaltura Media Assignment for Moodle
------
This is an activity module. Each student can submit a media from their "My Media", and teachers can play submitted medias, and grade each media. Aditionally, the student can upload and record new media in submission page.
This plugin is updated with stable releases. To follow active development on GitHub, click [here](https://github.com/YU-MITC/moodle-mod_kalmediaassign/).

Requirements
------

* PHP 8.0 or greater.
* Web browsers must support the JavaScript and HTML5.
* System administrators must use the HTTPS protocol for their Moodle site and Kaltura server.
* Administrators must not delete "Default" access control profile from their Kaltura server. If they delete the "Default" profile, they must create new profile named "Default" before install our plugins.
* These plugins do not support Flash players. Therefore, please use HTML5 or OVP players.
* "local_yukaltura" and "local_yumymedia" plugins.

Supported themes
-----

* Boost (version 1.1.7 and later)
* Classic (version 1.3.0 and later)

This plugin package might be able to work with other themes.

Installation
------

Unzip this plugin, and copy the directory (mod/kalmediaasign) under moodle root directory (ex. /moodle).
Installation will be completed after you log in as an administrator and access the notification menu.

How to use
------

* User's guide, click [here](http://www.cc.yamaguchi-u.ac.jp/guides/cas/plugins/userguide_version3.0.pdf).
* Demonstration web page, click [here](http://www.cc.yamaguchi-u.ac.jp/guides/cas/plugins/demo/).

Targeted Moodle versions
------

Moodle 4.3, 4.4, 4.5

Branches
------

* MOODLE_403_STABLE -> Moodle 4.3 branch
* MOODLE_404_STABLE -> Moodle 4.4 branch
* MOODLE_405_STABLE -> Moodle 4.5 branch

First clone the repository with "git clone", then "git checkout MOODLE_403_STABLE(branch name)" to switch branches.

Warning
------

* We are not responsible for any problem caused by this software. 
* This software follows the license policy of Moodle (GNU GPL v3).
* "Kaltura" is the registered trademark of the Kaltura Inc.
* Web-camera recording function supports the Mozilla Firefox, Google Chrome, Opera and Safari. For smartphones and tablets, you can record movie through a normal media uploader.
* Uploading and recording functions in resource and activity modules may not work well with smartphones. Because, low resolution screen cannot display these forms correctly.

Known issues
------

* In some browsers, preview window (modal window) cannot receive MPEG-DASH/HLS/HDS streaming data. And, if Kaltura server employs HTTPS and users embed their media into web sites employs HTTP, Kaltura players cannot receive streaming data. For local_yumymedia and mod_kalmediaassign, we recommend Kaltura players which receive video using progressive download.

Change log of YU Kaltura Media Assignment
------

Version 3.0.0

* fixed copyright statements in various files.
* fixed javascript files, in order to resolve an issue data comparison use "undefined".

Version 2.1.0

* fixed copyright statements in various files.
* fixed provider.php, in order to only support formats employed in Moodle 3.5 and laters.

Version 2.0.0

* fixed copyright statements in various files.
* fixed various files in order to delete statements using print_error function.
* fixed grade_submission.php, renderer.php, single_submission_form.php, view.php, and preview.js, in order to support Kaltura OVP media players (TV Platform studio).
* fixed grades_updated.php to solve a misstake about target table.
* fixed lib.php to solve a misstake about refresh of events.

Version 1.5.0

* fixed grade_preferences.php, lib.php, locallib.php, and renderer.php, in order to support completion tracking, calendar event, outline report, and complete report.
* fixed submission.php, in order to resolve an issue about late submissions.
* fixed single_submission_form.php, in order to resolve a playback issue of submitted media.
* fixed README.md, in order to support the Moodle 3.10.

Version 1.4.2

* fixed provider.php, renderer.php, and restore_kalmediaassign_activity_task.class.php, in order to corresponding to Moodle coding style.
* fixed README.md in order to support the Moodle 3.9.

Version 1.4.1

* fixed copyright statements in all files.
* fixed renderer.php, single_submission.php, and view.php, in order to adopt upload URI.

Version 1.4.0

* fixed comments in backup and restore scripts.
* fixed javascript files based on JSDoc warnings.
* fixed javascript files in order to support the Safari 12.x/13.x on macOS.
* added privacy functions ans strings to comply with GDPR.
* fixed some statements in grade_submission.php, in order to adjust player size for "classic" theme.
* fixed "Requirements" in README.md.

Version 1.3.2

* fixed  backup and restore scripts, in order to backup/restore courses in the Moodle 3.x.

Version 1.3.1

* fixed locallib.php, in order to resolve a problem about submission remaining datetime.
* removed unused comments from renderer.php.

Version 1.3.0

* fixed some statements in backup_kalmediaassign_stepslib.php, in order to backup assignment's informations correctly.
* fixed some statements in renderer.php and grade_submissions.php, in order to correctly display submissions that have been submitted or require grading.
* fixed some statements in locallib.php, renderer.php, and view.php, in order to correctly judge whether the submission / resubmission is permitted.

Version 1.2.2

* fixed lib.php, mod_form.php, upgrade.php and install,xml, in order to display module's introduction on a moodle course page.

Version 1.2.1

* fixed some statements in single_submission_form.php, according to changes of local plugin (local_yukaltura).
* fixed some statements in preview.php, based on JSDoc warnings.

Version 1.2.0

* fixed some statements in view.php, in order to permit students to upload/record new movie in editing page of activity module (In order to permit upload/record, administrators must set some items in configuration page of local_yukaltura).
* fixed some statements in grades_updated.php, media_submitted.php, submission_detail_viewed.php, and submission_page_viewed.php, in order to respond to backup and restore mechanisms in recently versions of Moodle.

Version 1.1.8

* added statements about "Requirements" in README.md.
* fixed some statements for support "Boost" theme in grade_submission.php, and kalmediaassign.css.
* fixed copyright statements in all scripts.

Version 1.1.7

* added statements about "Supported themes" in README.md.

Version 1.1.6

* added procedures for course reset in lib.php.
* fixed statements about "How to use" in README.md.

Version 1.1.5

* added precedures for submitted and deleted entry in grade_submissions.php and renderer.php.

Version 1.1.4

* fixed statements in README.md.

Version 1.1.3

* fixed issue that the plugin presents "lated submission" for submissions when duetime is not set.

Version 1.1.2

* added statements in README.md.
* separate player setting from Media Resource.

Version 1.1.1

* fixed statements in README.md.

Version 1.1.0

* fixed some login check statement.
* fixed modal window dimension in quick grading.

