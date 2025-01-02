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
 * Kaltura media assignment single submission page
 *
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/renderer.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/single_submission_form.php');

header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

$id = required_param('cmid', PARAM_INT); // Course Module ID.
$userid = required_param('userid', PARAM_INT);
$tifirst = optional_param('tifirst', '', PARAM_TEXT);
$tilast = optional_param('tilast', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);

list($cm, $course, $kalmediaassignobj) = kalmediaassign_validate_cmid($id);

require_login($course->id, false, $cm);

if (!confirm_sesskey()) {
    throw new moodle_exception('confirmsesskeybad', 'error');
}

global $CFG, $PAGE, $OUTPUT, $USER;

$url = new moodle_url('/mod/kalmediaassign/single_submission.php');
$url->params(array('cmid' => $id,
                   'userid' => $userid));

$context = context_module::instance($cm->id);

$PAGE->set_url($url);
$PAGE->set_title(format_string($kalmediaassignobj->name));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

$previousurl = new moodle_url('/mod/kalmediaassign/grade_submissions.php',
                              array('cmid' => $cm->id,
                                    'tifirst' => $tifirst,
                                    'tilast' => $tilast,
                                    'page' => $page));
$prevousurlstring = get_string('singlesubmissionheader', 'kalmediaassign');
$PAGE->navbar->add($prevousurlstring, $previousurl);

require_capability('mod/kalmediaassign:gradesubmission', $context);

// Write a log.
$event = \mod_kalmediaassign\event\submission_detail_viewed::create(array(
    'objectid' => $kalmediaassignobj->id,
    'context' => context_module::instance($cm->id),
    'relateduserid' => $userid
));
$event->trigger();

// Get a single submission record.
$submission = kalmediaassign_get_submission($cm->instance, $userid);

// Get the submission user and the time they submitted the media.
$param = array('id' => $userid);
$user  = $DB->get_record('user', $param);

$submissionuserpic = $OUTPUT->user_picture($user);
$submissionmodified = ' - ';
$datestringlate = ' - ';
$datestring = ' - ';

$submissionuserinfo = fullname($user);

// Get grading information.
$gradinginfo    = grade_get_grades($cm->course, 'mod', 'kalmediaassign', $cm->instance, array($userid));
$gradingdisabled = $gradinginfo->items[0]->grades[$userid]->locked || $gradinginfo->items[0]->grades[$userid]->overridden;

// Get marking teacher information and the time the submission was marked.
$teacher = '';
if (!empty($submission)) {
    $datestringlate     = kalmediaassign_display_lateness($submission->timemodified, $kalmediaassignobj->timedue);
    $submissionmodified = userdate($submission->timemodified);
    $datestring         = userdate($submission->timemarked) . "&nbsp; (" . format_time(time() - $submission->timemarked) . ")";

    $submissionuserinfo .= '<br />'.$submissionmodified . " &nbsp; " . $datestringlate;

    $param   = array('id' => $submission->teacher);
    $teacher = $DB->get_record('user', $param);
}

$markingteacherpic   = '';
$markingtreacherinfo = '';

if (!empty($teacher)) {
    $markingteacherpic   = $OUTPUT->user_picture($teacher);
    $markingtreacherinfo = fullname($teacher).'<br />'.$datestring;
}

$comment = '';
if ($submission != null) {
    $comment = $submission->submissioncomment;
}

// Setup form data.
$formdata                           = new stdClass();
$formdata->submissionuserpic        = $submissionuserpic;
$formdata->submissionuserinfo       = $submissionuserinfo;
$formdata->markingteacherpic        = $markingteacherpic;
$formdata->markingteacherinfo       = $markingtreacherinfo;
$formdata->grading_info             = $gradinginfo;
$formdata->gradingdisabled          = $gradingdisabled;
$formdata->cm                       = $cm;
$formdata->context                  = $context;
$formdata->cminstance               = $kalmediaassignobj;
$formdata->submission               = $submission;
$formdata->userid                   = $userid;
$formdata->enableoutcomes           = $CFG->enableoutcomes;
$formdata->submissioncomment_editor = array('text' => $comment, 'format' => FORMAT_HTML);
$formdata->tifirst                  = $tifirst;
$formdata->tilast                   = $tilast;
$formdata->page                     = $page;

$submissionform = new kalmediaassign_singlesubmission_form(null, $formdata);

if ($submissionform->is_cancelled()) {
    redirect($previousurl);
} else if ($submitteddata = $submissionform->get_data()) {

    if (!isset($submitteddata->cancel) &&
        isset($submitteddata->xgrade) &&
        isset($submitteddata->submissioncomment_editor)) {

        /*
         * Flag used when an instructor is about to grade a user who does not have
         * a submittion (see KALDEV-126).
         */
        $updategrade = true;

        if ($submission) {

            if ($submission->grade == $submitteddata->xgrade &&
                0 == strcmp($submission->submissioncomment, $submitteddata->submissioncomment_editor['text'])) {

                $updategrade = false;
            } else {
                $submission->grade              = $submitteddata->xgrade;
                $submission->submissioncomment  = $submitteddata->submissioncomment_editor['text'];
                $submission->format             = $submitteddata->submissioncomment_editor['format'];
                $submission->timemarked         = time();
                $submission->teacher            = $USER->id;
                $DB->update_record('kalmediaassign_submission', $submission);
            }

        } else {

            // Check for unchanged values.
            if ('-1' == $submitteddata->xgrade &&
                empty($submitteddata->submissioncomment_editor['text'])) {

                $updategrade = false;
            } else {

                $submission = new stdClass();
                $submission->mediaassignid      = $cm->instance;
                $submission->userid             = $userid;
                $submission->grade              = $submitteddata->xgrade;
                $submission->submissioncomment  = $submitteddata->submissioncomment_editor['text'];
                $submission->format             = $submitteddata->submissioncomment_editor['format'];
                $submission->timemarked         = time();
                $submission->teacher            = $USER->id;

                $DB->insert_record('kalmediaassign_submission', $submission);
            }

        }

        if ($updategrade) {
            $kalmediaassignobj->cmidnumber = $cm->idnumber;

            $gradeobj = kalmediaassign_get_submission_grade_object($kalmediaassignobj->id, $userid);

            kalmediaassign_grade_item_update($kalmediaassignobj, $gradeobj);

            // Write a log.
            $event = \mod_kalmediaassign\event\grades_updated::create(array(
                'objectid' => $kalmediaassignobj->id,
                'context' => context_module::instance($cm->id),
                'relateduserid' => $userid
            ));
            $event->trigger();

        }

        // Handle outcome data.
        if (!empty($CFG->enableoutcomes)) {
            require_once($CFG->libdir.'/gradelib.php');

            $data = array();
            $gradinginfo = grade_get_grades($course->id, 'mod', 'kalmediassign',
                                            $kalmediassignobj->id, $userid);

            if (!empty($gradinginfo->outcomes)) {
                foreach ($gradinginfo->outcomes as $n => $old) {
                    $name = 'outcome_'.$n;
                    if (isset($submitteddata->{$name}[$userid]) &&
                        $old->grades[$userid]->grade != $submitteddata->{$name}[$userid]) {

                        $data[$n] = $submitteddata->{$name}[$userid];
                    }
                }
            }

            if (count($data) > 0) {
                grade_update_outcomes('mod/kalmediaassign', $course->id, 'mod',
                                      'kalmediassign', $kalmediassignobj->id, $userid, $data);
            }
        }

    }

    redirect($previousurl);

}

// Try connection.
$kaltura = new yukaltura_connection();
$connection = $kaltura->get_connection(false, true, KALTURA_SESSION_LENGTH);

if ($connection) {
    if (local_yukaltura_has_mobile_flavor_enabled() && local_yukaltura_get_enable_html5()) {
        $uiconfid = local_yukaltura_get_player_uiconf('player');
        $playertype = local_yukaltura_get_player_type($uiconfid, $connection);
        $url = new moodle_url(local_yukaltura_html5_javascript_url($uiconfid, $playertype));
        $PAGE->requires->js($url, true);
        if ($playertype == KALTURA_UNIVERSAL_STUDIO) {
            $url = new moodle_url('/local/yukaltura/js/frameapi.js');
            $PAGE->requires->js($url, true);
        }
    }
}

$pageheading = get_string('gradesubmission', 'kalmediaassign');

echo $OUTPUT->header();
echo $OUTPUT->heading($pageheading.': '.fullname($user));

$submissionform->set_data($formdata);

$submissionform->display();

echo $OUTPUT->footer();
