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
 * Kaltura media assignment grade submission page
 *
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/renderer.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/grade_preferences_form.php');

if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

$id      = required_param('cmid', PARAM_INT); // Course Module ID.
$mode    = optional_param('mode', 0, PARAM_TEXT);
$tifirst = optional_param('tifirst', '', PARAM_TEXT);
$tilast  = optional_param('tilast', '', PARAM_TEXT);
$page    = optional_param('page', 0, PARAM_INT);

$url = new moodle_url('/mod/kalmediaassign/grade_submissions.php');
$url->param('cmid', $id);

if (!empty($mode)) {
    if (!confirm_sesskey()) {
        throw new moodle_exception('confirmsesskeybad', 'error');
    }
}

if (! $cm = get_coursemodule_from_id('kalmediaassign', $id)) {
    throw new moodle_exception('invalidcoursemodule');
}

if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
    throw new moodle_exception('coursemisconf');
}

if (! $kalmediaassignobj = $DB->get_record('kalmediaassign', array('id' => $cm->instance))) {
    throw new moodle_exception('invalidid', 'kalmediaassign');
}

require_login($course->id, false, $cm);

global $PAGE, $OUTPUT, $USER, $COURSE;

$currentcrumb = get_string('singlesubmissionheader', 'kalmediaassign');
$PAGE->set_url($url);
$PAGE->set_title(format_string($kalmediaassignobj->name));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($currentcrumb);

require_login();

$renderer = $PAGE->get_renderer('mod_kalmediaassign');

// Connect to Kaltura.
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

$courseid    = $course->id;
$uiconfid    = local_yukaltura_get_player_uiconf('player');
$modalwidth  = 0;
$modalheight = 0;

list($modalwidth, $modalheight) = kalmediaassign_get_popup_player_dimensions();

if (strcmp($CFG->theme, 'boost') == 0 || strcmp($CFG->theme, 'classic') == 0) {
    $modalheight = ((int)$modalheight + 20);
}

$PAGE->requires->css('/mod/kalmediaassign/css/kalmediaassign.css', true);
$PAGE->requires->js_call_amd('mod_kalmediaassign/grading', 'init', array($modalwidth, $modalheight));

echo $OUTPUT->header();

require_capability('mod/kalmediaassign:gradesubmission', context_course::instance($COURSE->id));

if (empty($connection)) {
    echo $OUTPUT->notification(get_string('conn_failed_alt', 'local_yukaltura'));
} else {
    // Write a log.
    $event = \mod_kalmediaassign\event\submission_page_viewed::create(array(
        'objectid' => $kalmediaassignobj->id,
        'context' => context_module::instance($cm->id)
    ));
    $event->trigger();

    $prefform = new kalmediaassign_gradepreferences_form(null, array('cmid' => $cm->id, 'groupmode' => $cm->groupmode));
    $data = null;

    if ($data = $prefform->get_data()) {
        set_user_preference('kalmediaassign_group_filter', $data->group_filter);

        set_user_preference('kalmediaassign_filter', $data->filter);

        if ($data->perpage > 0) {
            set_user_preference('kalmediaassign_perpage', $data->perpage);
        }

        if (isset($data->quickgrade)) {
            set_user_preference('kalmediaassign_quickgrade', $data->quickgrade);
        } else {
            set_user_preference('kalmediaassign_quickgrade', '0');
        }

    }

    if (empty($data)) {
        $data = new stdClass();
    }

    $data->filter       = get_user_preferences('kalmediaassign_filter', 0);
    $data->perpage      = get_user_preferences('kalmediaassign_perpage', 10);
    $data->quickgrade   = get_user_preferences('kalmediaassign_quickgrade', 0);
    $data->group_filter = get_user_preferences('kalmediaassign_group_filter', 0);

    $gradedata = data_submitted();

    // Check if fast grading was passed to the form and process the data.
    if (!empty($gradedata->mode) && !empty($gradedata->users)) {

        $usersubmission = array();
        $time = time();
        $updated = false;

        foreach ($gradedata->users as $userid => $val) {

            $param = array('mediaassignid' => $kalmediaassignobj->id,
                           'userid' => $userid);

            $usersubmissions = $DB->get_record('kalmediaassign_submission', $param);

            if ($usersubmissions) {

                if (array_key_exists($userid, $gradedata->menu)) {

                    // Update grade.
                    if (($gradedata->menu[$userid] != $usersubmissions->grade)) {

                        $usersubmissions->grade = $gradedata->menu[$userid];
                        $usersubmissions->timemarked = $time;
                        $usersubmissions->teacher = $USER->id;

                        $updated = true;
                    }
                }

                if (array_key_exists($userid, $gradedata->submissioncomment)) {
                    if (0 != strcmp($usersubmissions->submissioncomment, $gradedata->submissioncomment[$userid])) {
                        $usersubmissions->submissioncomment = $gradedata->submissioncomment[$userid];
                        $updated = true;
                    }
                }

                // Trigger grade event.
                if ($DB->update_record('kalmediaassign_submission', $usersubmissions)) {
                    $grade = new stdClass();
                    $grade->userid = $userid;
                    $grade = kalmediaassign_get_submission_grade_object($kalmediaassignobj->id, $userid);

                    $kalmediaassignobj->cmidnumber = $cm->idnumber;

                    kalmediaassign_grade_item_update($kalmediaassignobj, $grade);

                    // Write a log only if updating.
                    $event = \mod_kalmediaassign\event\grades_updated::create(array(
                        'objectid' => $kalmediaassignobj->id,
                        'context' => context_module::instance($cm->id),
                        'relateduserid' => $userid
                    ));
                    $event->trigger();

                }

            } else {
                // No user submission however the instructor has submitted grade data.
                $usersubmissions = new stdClass();
                $usersubmissions->mediaassignid = $cm->instance;
                $usersubmissions->userid = $userid;
                $usersubmissions->entry_id = '';
                $usersubmissions->teacher = $USER->id;
                $usersubmissions->timemarked = $time;

                /*
                 * Need to prevent completely empty submissions from getting entered
                 * into the media submissions' table.
                 * Check for unchanged grade value and an empty feedback value.
                 */
                $emptygrade = array_key_exists($userid, $gradedata->menu) &&
                              '-1' == $gradedata->menu[$userid];

                $emptycomment = array_key_exists($userid, $gradedata->submissioncomment) &&
                                empty($gradedata->submissioncomment[$userid]);

                if ( $emptygrade && $emptycomment ) {
                    continue;
                }

                if (array_key_exists($userid, $gradedata->menu)) {
                    $usersubmissions->grade = $gradedata->menu[$userid];
                }

                if (array_key_exists($userid, $gradedata->submissioncomment)) {
                    $usersubmissions->submissioncomment = $gradedata->submissioncomment[$userid];
                }


                // Trigger grade event.
                if ($DB->insert_record('kalmediaassign_submission', $usersubmissions)) {

                    $grade = new stdClass();
                    $grade->userid = $userid;
                    $grade = kalmediaassign_get_submission_grade_object($kalmediaassignobj->id, $userid);

                    $kalmediaassignobj->cmidnumber = $cm->idnumber;

                    kalmediaassign_grade_item_update($kalmediaassignobj, $grade);

                    // Write a log only if updating.
                    $event = \mod_kalmediaassign\event\grades_updated::create(array(
                        'objectid' => $kalmediaassignobj->id,
                        'context' => context_module::instance($cm->id),
                        'relateduserid' => $userid
                    ));
                    $event->trigger();
                }
            }

            $updated = false;
        }
    }

    $renderer->display_submissions_table($cm, $data->group_filter, $data->filter, $data->perpage,
                                         $data->quickgrade, $tifirst, $tilast, $page);

    $prefform->set_data($data);
    $prefform->display();
}

echo $OUTPUT->footer();
