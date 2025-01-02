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
 * Kaltura media assignment form
 *
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');

if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

if (!confirm_sesskey()) {
    throw new moodle_exception('confirmsesskeybad', 'error');
}

$entryid = required_param('entry_id', PARAM_TEXT);
$cmid    = required_param('cmid', PARAM_INT);

global $USER, $OUTPUT, $DB, $PAGE;

if (! $cm = get_coursemodule_from_id('kalmediaassign', $cmid)) {
    throw new moodle_exception('invalidcoursemodule');
}

if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
    throw new moodle_exception('coursemisconf');
}

if (! $kalmediaassignobj = $DB->get_record('kalmediaassign', array('id' => $cm->instance))) {
    throw new moodle_exception('invalidid', 'kalmediaassign');
}

require_course_login($course->id, true, $cm);

$PAGE->set_url('/mod/kalmediaassign/view.php', array('id' => $course->id));
$PAGE->set_title(format_string($kalmediaassignobj->name));
$PAGE->set_heading($course->fullname);

require_login();

if (kalmediaassign_assignment_submission_expired($kalmediaassignobj) && !$kalmediaassignobj->preventlate) {
    throw new moodle_exception('assignmentexpired', 'kalmediaassign', 'course/view.php?id='. $course->id);
}

echo $OUTPUT->header();

if (empty($entryid)) {
    throw new moodle_exception('emptyentryid', 'kalmediaassign', $CFG->wwwroot . '/mod/kalmediaassign/view.php?id='.$cm->id);
}

$param = array('mediaassignid' => $kalmediaassignobj->id, 'userid' => $USER->id);
$submission = $DB->get_record('kalmediaassign_submission', $param);

$time = time();

if ($submission) {

    $submission->entry_id = $entryid;
    $submission->timemodified = $time;

    if (0 == $submission->timecreated) {
        $submission->timecreated = $time;
    }

    if ($DB->update_record('kalmediaassign_submission', $submission)) {

        $message = get_string('assignmentsubmitted', 'kalmediaassign');
        $continue = get_string('continue');

        echo $OUTPUT->notification($message, 'notifysuccess');

        echo html_writer::start_tag('center');

        $url = new moodle_url($CFG->wwwroot . '/mod/kalmediaassign/view.php', array('id' => $cm->id));

        echo $OUTPUT->single_button($url, $continue, 'post');
        echo html_writer::end_tag('center');

        // Write a log.
        $event = \mod_kalmediaassign\event\media_submitted::create(array(
            'objectid' => $kalmediaassignobj->id,
            'context' => context_module::instance($cm->id),
            'relateduserid' => $USER->id
        ));
        $event->trigger();
    } else {
         throw new moodle_exception('not_update', 'kalmediaassign');
    }
} else {
    $submission = new stdClass();
    $submission->entry_id = $entryid;
    $submission->userid = $USER->id;
    $submission->mediaassignid = $kalmediaassignobj->id;
    $submission->grade = -1;
    $submission->timecreated = $time;
    $submission->timemodified = $time;

    if ($DB->insert_record('kalmediaassign_submission', $submission)) {

        $message = get_string('assignmentsubmitted', 'kalmediaassign');
        $continue = get_string('continue');

        echo $OUTPUT->notification($message, 'notifysuccess');

        echo html_writer::start_tag('center');

        $url = new moodle_url($CFG->wwwroot . '/mod/kalmediaassign/view.php', array('id' => $cm->id));

        echo $OUTPUT->single_button($url, $continue, 'post');
        echo html_writer::end_tag('center');

    } else {
         throw new moodle_exception('not_insert', 'kalmediaassign');
    }
}

$context = $PAGE->context;

// Email an alert to the teacher.
if ($kalmediaassignobj->emailteachers) {
    kalmediaassign_email_teachers($cm, $kalmediaassignobj->name, $submission, $context);
}

echo $OUTPUT->footer();
