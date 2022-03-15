<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Message defines.
 *
 * @package   mod_kalmediares
 * @copyright (C) 2016-2022 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Kaltura Media Resource';

$string['modulenameplural'] = 'Kaltura Media Resources';
$string['modulename'] = 'Kaltura Media Resource';
$string['modulename_help'] = 'The Kaltura Media Resource enables a teacher to create a resource using a Kaltura media.';
$string['pluginadministration'] = 'Kaltura Media Resource';
$string['name'] = 'Name';
$string['description'] = 'Description';
$string['media_hdr'] = 'Media';
$string['add_media'] = 'Add existing media';
$string['upload_successful'] = 'Upload Successful';
$string['media_converting'] = 'Your media is still converting.  Please check the status of your media at a later time.';
$string['normal'] = 'Normal';
$string['media_preview'] = 'Preview';
$string['widescreen'] = 'Widescreen';
$string['media_preview_header'] = 'Preview';
$string['invalidid'] = 'Invalid ID';
$string['invalid_module'] = 'Invalid course module ID ({$a})';
$string['invalidaccess'] = 'Invalid Access';
$string['cannot_view'] = 'You cannot view this page.';
$string['replace_media'] = 'Replace media';
$string['custom_player'] = 'Custom player';
$string['kalmediares:addinstance'] = 'Add a Kaltura Media Resource';
$string['kalmediares:viewlog'] = 'View access logs of a Kaltura Media Resource';
$string['media_select'] = 'Select media';
$string['scr_loading'] = 'Loading...';
$string['access_control_hdr'] = 'Access control';
$string['internal'] = 'Internal only';
$string['publish_access_log_hdr'] = 'Publish access log';
$string['publish_access_log'] = 'Publish access log to students';
$string['publish_access_log_help'] = 'If this property is set to "Yes", student can view own statistics (number of "media plays" and "page views").';
$string['exclusion_time'] = 'Exclusion time (hours)';
$string['exclusion_time_help'] = 'Exclude the access log of the last few hours from the statistics presented to the students.';
$string['previewmedia'] = 'Preview';
$string['event_media_resource_viewed'] = 'Media resource viewed';
$string['event_media_resource_played'] = 'Media resource played';
$string['access_logs'] = 'Access logs';
$string['view_access_logs'] = 'View access logs';
$string['invalid_ipaddress'] = 'Sorry, this media is only available for a certain local area.<br>Your IP address is out of the range of local area.<br>';
$string['your_ipaddress'] = 'Your IP address';
$string['plays'] = 'plays';
$string['views'] = 'views';
$string['totalplays'] = 'Total plays';
$string['totalviews'] = 'Total views';
$string['no_student'] = 'There is no student in this course.';
$string['not_viewed'] = 'No student has been viewed this media.';
$string['no_media'] = 'Media (id = {$a}) is not avctive.<br>This media may have been deleted.';
$string['your_views'] = 'Page viewing: {$a} times before';
$string['your_plays'] = 'Media playing: {$a} times before';
$string['delay_stats_desc'] = 'Remarks : Your actions will be added to the statistics after about {$a} hours.';
$string['app_stats_warning'] = 'Remarks : In the mobile app, this plugin cannot count number of media playbacks.';
$string['log_update_error'] = 'Cannot update access log ({$a}).';
$string['log_get_error'] = 'Cannot get access log ({$a}).';

// Privacy strings.
$string['privacy:metadata:kalmediares_log'] = 'Information about the access logs to Kaltura media resources. This includes number of plays, numver of page views, first access time, and last access time.';
$string['privacy:metadata:kalmediares_log:instanceid'] = 'The ID of instance of Kaltura media resource.';
$string['privacy:metadata:kalmediares_log:userid'] = 'The ID of the user with the media resource.';
$string['privacy:metadata:kalmediares_log:plays'] = 'The number of plays to the Kaltura media resource';
$string['privacy:metadata:kalmediares_log:views'] = 'The number of page (resource) views to the Kaltura media resource.';
$string['privacy:metadata:kalmediares_log:first'] = 'The first acess time to the Kaltura media resource.';
$string['privacy:metadata:kalmediares_log:last'] = 'The last access time to the Kaltura media resource.';
