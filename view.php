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
 * Kaltura media assignment
 *
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/yukaltura/locallib.php');
require_once(dirname(__FILE__) . '/locallib.php');

header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID.

// Retrieve module instance.
if (empty($id)) {
    throw new moodle_exception('invalidid', 'kalmediaassign');
}

if (!empty($id)) {

    if (! $cm = get_coursemodule_from_id('kalmediaassign', $id)) {
        throw new moodle_exception('invalidcoursemodule');
    }

    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        throw new moodle_exception('coursemisconf');
    }

    if (! $kalmediaassign = $DB->get_record('kalmediaassign', array("id" => $cm->instance))) {
        throw new moodle_exception('invalidid', 'kalmediaassign');
    }
}

require_course_login($course->id, true, $cm);

global $SESSION, $CFG, $USER, $COURSE;

// Connect to Kaltura.
$kaltura = new yukaltura_connection();
$connection = $kaltura->get_connection(false, true, KALTURA_SESSION_LENGTH);
$partnerid = '';
$srunconfid = '';
$host = '';

if ($connection) {

    // If a connection is made then include the JS libraries.
    $partnerid = local_yukaltura_get_partner_id();
    $host = local_yukaltura_get_host();

    $modalwidth  = 0;
    $modalheight = 0;

    list($modalwidth, $modalheight) = kalmediaassign_get_popup_player_dimensions();

    if (strcmp($CFG->theme, 'boost') == 0) {
        $modalheight = ((int)$modalheight + 20);
    }

    $PAGE->requires->js_call_amd('mod_kalmediaassign/preview', 'init', array($modalwidth, $modalheight));
    $PAGE->requires->js_call_amd('local_yukaltura/simpleselector', 'init',
                                 array($CFG->wwwroot . "/local/yukaltura/simple_selector.php",
                                       get_string('replace_media', 'mod_kalmediaassign')));
    $PAGE->requires->js_call_amd('local_yukaltura/properties', 'init',
                                 array($CFG->wwwroot . "/local/yukaltura/media_properties.php"));
    $PAGE->requires->js_call_amd('local_yumymedia/loaduploader', 'init',
                                 array($CFG->wwwroot . "/local/yumymedia/module_uploader.php"));
    $PAGE->requires->js_call_amd('local_yumymedia/loadrecorder', 'init',
                                 array($CFG->wwwroot . "/local/yumymedia/module_recorder.php"));
    $PAGE->requires->css('/local/yukaltura/css/simple_selector.css');
    $PAGE->requires->css('/local/yumymedia/css/module_uploader.css');
    $PAGE->requires->css('/mod/kalmediaassign/css/kalmediaassign.css', true);
}

$PAGE->set_url('/mod/kalmediaassign/view.php', array('id' => $id));
$PAGE->set_title(format_string($kalmediaassign->name));
$PAGE->set_heading($course->fullname);

require_login();

$modulecontext = context_module::instance(CONTEXT_MODULE, $cm->id);

// Update 'viewed' state if required by completion system.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

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

echo $OUTPUT->header();

$coursecontext = context_course::instance($COURSE->id);

$renderer = $PAGE->get_renderer('mod_kalmediaassign');

echo $OUTPUT->box_start('generalbox');

echo format_module_intro('kalmediaassign', $kalmediaassign, $cm->id);
echo $OUTPUT->box_end();

$entryobject   = null;
$disabled       = false;

if (empty($connection)) {
    echo $OUTPUT->notification(get_string('conn_failed_alt', 'local_yukaltura'));
    $disabled = true;
} else {
    echo $renderer->create_kaltura_hidden_markup($connection);
}

echo $renderer->display_mod_header($kalmediaassign);

if (has_capability('mod/kalmediaassign:gradesubmission', $coursecontext)) {
    echo $renderer->display_grading_summary($cm, $kalmediaassign, $coursecontext);
    echo $renderer->display_instructor_buttons($cm);
}

if (has_capability('mod/kalmediaassign:submit', $coursecontext)) {

    echo $renderer->display_submission_status($cm, $kalmediaassign, $coursecontext);

    $param = array('mediaassignid' => $kalmediaassign->id, 'userid' => $USER->id);
    $submission = $DB->get_record('kalmediaassign_submission', $param);

    if (!empty($submission->entry_id)) {
        $entryobject = local_yukaltura_get_ready_entry_object($submission->entry_id, false);
    }

    echo $renderer->display_submission($entryobject, $connection);

    $disabled = true;

    if (kalmediaassign_assignment_submission_opened($kalmediaassign, $submission) &&
        (!kalmediaassign_assignment_submission_expired($kalmediaassign) || $kalmediaassign->preventlate == 1)) {
           $disabled = false;
    }

    if (empty($submission->entry_id) && empty($submission->timecreated)) {
        echo $renderer->display_student_submit_buttons($cm, $disabled);
    } else {
        if ($disabled ||
            !kalmediaassign_assignment_submission_resubmit($kalmediaassign, $entryobject, $submission)) {

            $disabled = true;
        }

        echo $renderer->display_student_resubmit_buttons($cm, $USER->id, $disabled);
    }

    echo $renderer->display_grade_feedback($kalmediaassign, $coursecontext);
}

echo $OUTPUT->footer();
