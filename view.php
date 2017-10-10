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
 * @package    mod_kalmediares
 * @copyright  (C) 2016-2017 Yamaguchi University <info-cc@ml.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/yukaltura/locallib.php');

if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

$id = optional_param('id', 0, PARAM_INT);  // Course Module ID.

// Retrieve module instance.
if (empty($id)) {
    print_error('invalid course module id - ' . $id, 'kalmediares');
}

if (!empty($id)) {
    if (! $cm = get_coursemodule_from_id('kalmediares', $id)) {
        print_error('invalid_coursemodule', 'kalmediares');
    }

    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('course_misconf');
    }

    if (! $kalmediares = $DB->get_record('kalmediares', array('id' => $cm->instance))) {
        print_error('invalid_id', 'kalmediares');
    }
}

require_course_login($course->id, true, $cm);

global $SESSION, $CFG, $USER, $COURSE;

$PAGE->set_url('/mod/kalmediares/view.php', array('id' => $id));
$PAGE->set_title(format_string($kalmediares->name));
$PAGE->set_heading($course->fullname);

$context = $PAGE->context;

// Try connection.
$kaltura = new yukaltura_connection();
$connection = $kaltura->get_connection(true, KALTURA_SESSION_LENGTH);

if ($connection) {
    if (local_yukaltura_has_mobile_flavor_enabled() && local_yukaltura_get_enable_html5()) {
        $uiconfid = local_yukaltura_get_player_uiconf('player_resource');
        $url = new moodle_url(local_yukaltura_html5_javascript_url($uiconfid));
        $PAGE->requires->js($url, true);
        $url = new moodle_url('/local/yukaltura/js/frameapi.js');
        $PAGE->requires->js($url, true);
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

    $url = $CFG->wwwroot . '/mod/kalmediares/trigger.php';
    $PAGE->requires->js_call_amd('mod_kalmediares/playtrigger', 'init', array($url, $id));
}

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_kalmediares');

echo $OUTPUT->box_start('generalbox');

echo $renderer->display_mod_info($kalmediares->media_title);

echo format_module_intro('kalmediares', $kalmediares, $cm->id);

echo $OUTPUT->box_end();

$clientipaddress = local_yukaltura_get_client_ipaddress(true);
if ($kalmediares->internal == 1 and !local_yukaltura_check_internal($clientipaddress)) {
    echo $renderer->create_access_error_markup($clientipaddress);
} else if ($connection) {

    // Embed a kaltura media.
    if (!empty($kalmediares->entry_id)) {

        try {
            $media = $connection->media->get($kalmediares->entry_id);

            if ($media !== null) {
                echo $renderer->embed_media($kalmediares);
            }
        } catch (Exception $ex) {
            echo '<p>';
            echo 'Media (id = ' . $kalmediares->entry_id. ') is not avctive.<br>';
            echo 'This media may have been deleted.';
            echo '</p>';
        }
    }

    if ($teacher == true || $admin == true) {
        echo $renderer->create_access_link_markup($cm->id);
    }

} else {
    echo $renderer->connection_failure();
}

echo $OUTPUT->footer();

/**
 * This function encode data.
 * @param object $data - source data.
 * @return object - encoded data.
 */
function json_safe_encode($data) {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
