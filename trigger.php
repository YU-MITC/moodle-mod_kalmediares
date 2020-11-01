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
 * Acess event receive script and record student's access log.
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_kalmediares
 * @copyright  (C) 2016-2020 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/yukaltura/locallib.php');

defined('MOODLE_INTERNAL') || die();

$referer = $_SERVER['HTTP_REFERER'];

$id = optional_param('id', 0, PARAM_INT); // Course Module ID.
// $action = optional_param('action', 0, PARAM_RAW); // Action name.

// Retrieve module instance.
if (empty($id)) {
    print_error('invalid course module id - ' . $id, 'kalmediares');
}

$correcturl = new moodle_url('/mod/kalmediares/view.php');
$correcturl .= '?id=' . $id;

if ($referer != $correcturl) {
    print_error('invalid_access', 'kalmediares');
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

$PAGE->set_url('/mod/kalmediares/trigger.php', array('id' => $id));
$PAGE->set_title(format_string($kalmediares->name));
$PAGE->set_heading($course->fullname);

$context = $PAGE->context;

$student = false;

$coursecontext = context_course::instance($COURSE->id);
$roles = get_user_roles($coursecontext, $USER->id);

foreach ($roles as $role) {
    if ($role->shortname == 'student' || $role->shortname == 'guest') {
        $student = true;
    }
}

if ($student == true) {
    $event = \mod_kalmediares\event\media_resource_played::create(array(
        'objectid' => $kalmediares->id,
        'context' => context_module::instance($cm->id)
    ));
    $event->trigger();

    try {
        $kalmediareslog = $DB->get_record('kalmediares_log',
                                          array('instanceid' => $cm->instance, 'userid' => $USER->id));
        $now = time();
        if (empty($kalmediareslog)) {
            $objectdata = array('instanceid' => $cm->instance, 'userid' => $USER->id, 'plays' => 1, 'views' => 1,
                                'first' => $now, 'last' => $now);
            $DB->insert_record('kalmediares_log', $objectdata);
        } else {
            $kalmediareslog->last = $now;
            $kalmediareslog->plays = $kalmediareslog->plays + 1;
            $DB->update_record('kalmediares_log', $kalmediareslog, false);
        }
    } catch (Exception $ex) {
        print_error($ex->getMessage());
    }
}

$completion = new completion_info($course);
$completion->set_module_viewed($cm);
