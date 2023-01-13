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
 * Prints a particular instance of newmodule
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package   mod_kalmediares
 * @copyright (C) 2016-2023 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/yukaltura/locallib.php');

defined('MOODLE_INTERNAL') || die();

header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

$id = optional_param('id', 0, PARAM_INT);  // Course Module ID.

// Retrieve module instance.
if (empty($id)) {
    throw new moodle_exception('invalid_module', 'kalmediares', '', 'N/A');
}

if (!empty($id)) {
    if (! $cm = get_coursemodule_from_id('kalmediares', $id)) {
        throw new moodle_exception('invalid_module', 'kalmediares', '', $id);
    }

    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        throw new moodle_exception('course_misconf');
    }

    if (! $kalmediares = $DB->get_record('kalmediares', array('id' => $cm->instance))) {
        throw new moodle_exception('invalidid', 'kalmediares');
    }
}

require_course_login($course->id, true, $cm);

global $SESSION, $CFG, $USER, $COURSE;

$PAGE->set_url('/mod/kalmediares/view.php', array('id' => $id));
$PAGE->set_title(format_string($kalmediares->name));
$PAGE->set_heading($course->fullname);

// Try connection.
$kaltura = new yukaltura_connection();
$connection = $kaltura->get_connection(false, true, KALTURA_SESSION_LENGTH);

$playertype = KALTURA_UNIVERSAL_STUDIO;

if ($connection) {
    if (local_yukaltura_has_mobile_flavor_enabled() && local_yukaltura_get_enable_html5()) {
        $uiconfid = local_yukaltura_get_player_uiconf('player_resource');

        if (empty($kalmediares->uiconf_id)) {
            $kalmediares->uiconf_id = $uiconfid;
        }

        $playertype = local_yukaltura_get_player_type($kalmediares->uiconf_id, $connection);

        $url = new moodle_url(local_yukaltura_html5_javascript_url($kalmediares->uiconf_id, $playertype));
        $PAGE->requires->js($url, true);

        if ($playertype == KALTURA_UNIVERSAL_STUDIO) {
            $url = new moodle_url('/local/yukaltura/js/frameapi.js');
            $PAGE->requires->js($url, true);
        }
    }
}

$admin = false;

if (is_siteadmin()) {
       $admin = true;
}

$student = false;
$teacher = false;

$coursecontext = context_course::instance($COURSE->id);
$roles = get_user_roles($coursecontext, $USER->id);
foreach ($roles as $role) {
    if ($role->shortname == 'student' || $role->shortname == 'guest') {
        $student = true;
    }
    if ($role->shortname == 'teacher' || $role->shortname == 'editingteacher') {
        $teacher = true;
    }
}

if ($student == true) {
    $event = \mod_kalmediares\event\media_resource_viewed::create(array(
        'objectid' => $kalmediares->id,
        'context' => context_module::instance($cm->id)
    ));
    $event->trigger();

    try {
        $kalmediareslog = $DB->get_record('kalmediares_log',
                                          array('instanceid' => $cm->instance, 'userid' => $USER->id));
        $now = time();
        if (empty($kalmediareslog)) {
            $objectdata = array('instanceid' => $cm->instance, 'userid' => $USER->id, 'plays' => 0, 'views' => 1,
                                'first' => $now, 'last' => $now);
            $DB->insert_record('kalmediares_log', $objectdata);
        } else {
            $kalmediareslog->last = $now;
            $kalmediareslog->views = $kalmediareslog->views + 1;
            $DB->update_record('kalmediares_log', $kalmediareslog, false);
        }
    } catch (Exception $ex) {
        throw new moodle_exception('log_update_error', 'kalmediares', '', $ex->getMessage());
    }

    $url = $CFG->wwwroot . '/mod/kalmediares/trigger.php';
    $PAGE->requires->js_call_amd('mod_kalmediares/playtrigger', 'init', array($url, $id));
}

$entryobj = local_yukaltura_get_ready_entry_object($kalmediares->entry_id);
if (!empty($entryobj) && $admin == false) {
    // For completion trackings.
    if ((KalturaMediaType::VIDEO != $entryobj->mediaType &&
         KalturaMediaType::AUDIO != $entryobj->mediaType) ||
        $teacher == true && $student == false) {
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm);
    }
}

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_kalmediares');

echo $OUTPUT->box_start('generalbox');

echo $renderer->display_mod_info($kalmediares->media_title);

echo format_module_intro('kalmediares', $kalmediares, $cm->id);

echo $OUTPUT->box_end();

$clientipaddress = local_yukaltura_get_client_ipaddress(true);
if ($kalmediares->internal == 1 && !local_yukaltura_check_internal($clientipaddress)) {
    echo $renderer->create_access_error_markup($clientipaddress);
} else if ($connection) {

    // Embed a kaltura media.
    if (!empty($kalmediares->entry_id)) {

        try {
            $media = $connection->media->get($kalmediares->entry_id);

            if ($media !== null) {
                echo $renderer->embed_media($kalmediares, $connection);
            }
        } catch (Exception $ex) {
            echo '<p>';
            echo get_string('no_media', 'kalmediares', $kalmediares->entry_id);
            echo '</p>';
        }
    }

    if ($student == true && $kalmediares->publish_access_log == 1) {
        echo $renderer->create_student_playsviews_markup($cm->id, $kalmediares);
    }

    if ($teacher == true || $admin == true) {
        echo $renderer->create_access_link_markup($cm->id);
    }

} else {
    echo $renderer->connection_failure();
}

echo $OUTPUT->footer();
