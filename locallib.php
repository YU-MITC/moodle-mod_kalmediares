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
 * YU Kaltura Media assignment locallib
 *
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('KALASSIGN_FILTER_ALL', 0);
define('KALASSIGN_FILTER_REQ_GRADING', 1);
define('KALASSIGN_FILTER_SUBMITTED', 2);
define('KALASSIGN_FILTER_NOTSUBMITTEDYET', 3);

define('KALASSIGN_SUBMISSION_STATUS_NOTSUBMITTEDYET', 0);
define('KALASSIGN_SUBMISSION_STATUS_REQ_GRADING', 1);
define('KALASSIGN_SUBMISSION_STATUS_GRADED', 2);

define('KALASSIGN_GRADING_NOTGRADED', -1);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/gradelib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/mod/kalmediaassign/renderable.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/yukaltura/locallib.php');

if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

require_login();

if (file_exists($CFG->dirroot.'/calendar/lib.php')) {
    require_once($CFG->dirroot.'/calendar/lib.php');
}

/**
 * Check if the assignment submission end date has passed or if late submissions
 * are prohibited.
 *
 * @param object $kalmediaassign - Kaltura media assignment instance object.
 * @return bool - true if expired, otherwise false.
 */
function kalmediaassign_assignment_submission_expired($kalmediaassign) {
    if (empty($kalmediaassign->timedue) || $kalmediaassign->timedue <= 0) {
        return false;
    }

    if (time() <= $kalmediaassign->timedue) {
        return false;
    }

    return true;
}

/**
 * Check if the assignment submission is opened.
 * are prohibited
 *
 * @param object $kalmediaassign - Kaltura instance media assignment object.
 * @param object $submission - submission object.
 * @return bool - true if opened, otherwise false.
 */
function kalmediaassign_assignment_submission_opened($kalmediaassign, $submission = null) {
    if ($submission !== null && $submission->timemodified < $submission->timemarked && $kalmediaassign->resubmit == 0) {
        return false;
    }

    if (empty($kalmediaassign->timeavailable)) {
        return true;
    }

    if (time() > $kalmediaassign->timeavailable) {
        return true;
    }

    return false;
}

/**
 * Check if the assignment resubmission is allowed.
 * are prohibited
 *
 * @param object $kalmediaassign - Kaltura instance media assignment object.
 * @param object $entryobj - Kaltura media entry object.
 * @param object $submission - submission object.
 * @return bool - true if resubmission is allowed, otherwise false.
 */
function kalmediaassign_assignment_submission_resubmit($kalmediaassign, $entryobj, $submission = null) {
    global $USER;

    if ($submission !== null && $submission->timemodified < $submission->timemarked && $kalmediaassign->resubmit == 0) {
        return false;
    }

    if (kalmediaassign_assignment_submission_expired($kalmediaassign) && $kalmediaassign->preventlate == 0 ||
        !kalmediaassign_assignment_submission_opened($kalmediaassign)) {
        return false;
    }

    if ($entryobj == null) {
        return true;
    }

    $gradinginfo = grade_get_grades($kalmediaassign->course, 'mod', 'kalmediaassign', $kalmediaassign->id, $USER->id);

    $item = $gradinginfo->items[0];
    $grade = $item->grades[$USER->id];

    if ($grade->grade == null || $grade->grade == -1) {   // Nothing to show yet.
        return true;
    }

    if ($kalmediaassign->resubmit) {
        return true;
    }

    return false;
}

/**
 * This function returns remaining time to assignment closed.
 *
 * @param int $duetime - due time in seconds.
 *
 * @return string - remaining date time.
 */
function kalmediaassign_get_remainingdate($duetime) {
    $now = time();
    if ($now > $duetime) {
        return get_string('submissionclosed', 'kalmediaassign');
    }

    $remain = format_time($duetime - $now);

    return $remain;
}

/**
 * This function submit Kaltura media assignment.
 * @param string $mode - submission mode.
 * @return function - selected function from $mode.
 */
function kalmediaassign_submissions($mode) {

    $mailinfo = optional_param('mailinfo', null, PARAM_BOOL);

    if (optional_param('next', null, PARAM_BOOL)) {
        $mode = 'next';
    }
    if (optional_param('saveandnext', null, PARAM_BOOL)) {
        $mode = 'saveandnext';
    }

    if (is_null($mailinfo)) {
        if (optional_param('sesskey', null, PARAM_BOOL)) {
            set_user_preference('kalmediaassign_mailinfo', $mailinfo);
        } else {
            $mailinfo = get_user_preferences('kalmediaassign_mailinfo', 0);
        }
    } else {
        set_user_preference('kalmediaassign_mailinfo', $mailinfo);
    }

    $function = $mode . '_display';

    $function();
}

/**
 * Retrieve a list of users who have submitted assignments
 *
 * @param int $kalmediaassignid - assignment instance id
 * @param string $filter - filter results by assignments that have been submitted or
 * assignment that need to be graded or no filter at all
 *
 * @return mixed - collection of users or false
 */
function kalmediaassign_get_submissions($kalmediaassignid, $filter = '') {
    global $DB;

    $where = '';
    switch ($filter) {
        case KALASSIGN_FILTER_SUBMITTED:
            $where = ' timemodified > 0 AND ';
            break;
        case KALASSIGN_FILTER_REQ_GRADING:
            $where = ' timemarked < timemodified AND ';
            break;
    }

    $param = array('instanceid' => $kalmediaassignid);
    $where .= ' mediaassignid = :instanceid';

    // Reordering the fields returned to make it easier to use in the grade_get_grades function.
    $records = $DB->get_records_select('kalmediaassign_submission', $where, $param, 'timemodified DESC',
                                       'userid,mediaassignid,entry_id,grade,submissioncomment,'.
                                       'format,teacher,mailed,timemarked,timecreated,timemodified');

    if (empty($records)) {
        return false;
    }

    return $records;

}

/**
 * Retrieve a database record of kalmediaassign submission
 *
 * @param int $kalmediaassignid - assignment instance id
 * @param int $userid - user id in moodle
 *
 * @return mixed - collection of users or false
 */
function kalmediaassign_get_submission($kalmediaassignid, $userid) {
    global $DB;

    $param = array('instanceid' => $kalmediaassignid,
                   'userid' => $userid);
    $where = '';
    $where .= ' mediaassignid = :instanceid AND userid = :userid';

    // Reordering the fields returned to make it easier to use in the grade_get_grades function.
    $record = $DB->get_record_select('kalmediaassign_submission', $where, $param,
                                       'userid,id,mediaassignid,entry_id,grade,submissioncomment,'.
                                       'format,teacher,mailed,timemarked,timecreated,timemodified');

    if (empty($record)) {
        return false;
    }

    return $record;
}

/**
 * This function returns submission grade object.
 * @param int $instanceid - id of instance which is modifid by teacher.
 * @param int $userid - id of user which is affected by teacher.
 * @return object - submission grade object.
 */
function kalmediaassign_get_submission_grade_object($instanceid, $userid) {
    global $DB;

    $param = array('kmedia' => $instanceid,
                   'userid' => $userid);

    $sql = "SELECT u.id userid, s.grade rawgrade, s.submissioncomment feedback, s.format AS feedbackformat,
                   s.teacher usermodified, s.timemarked dategraded, s.timemodified datesubmitted
            FROM {user} u, {kalmediaassign_submission} s
            WHERE u.id = s.userid AND s.mediaassignid = :kmedia
                   AND u.id = :userid";

    $data = $DB->get_record_sql($sql, $param);

    if (-1 == $data->rawgrade) {
        $data->rawgrade = null;
    }

    return $data;
}

/**
 * This function returns submission status of ceertain user.
 * @param int $submission - Kaltura media assignment submission object.
 * @return string - status of submission.
 */
function kalmediaassign_get_submission_status($submission) {
    if (!$submission || empty($submission->entry_id) || $submission->timecreated == 0) {
        return KALASSIGN_SUBMISSON_STATUS_NOTSUBMITTEDYET;
    }

    if ($submission->timemodified > $submission->timemarked || $submission->grade == KALASSIGN_GRADING_NOTGRADED) {
        return KALASSIGN_SUBMISSION_STATUS_REQ_GRADING;
    }

    if (!empty($submission->teacher) && $submission->timemarked > 0 && $submission->grade != KALASSIGN_GRADING_NOTGRADED) {
        return KALASSIGN_SUBMISSION_STATUS_GRADED;
    }

    return KALASSIGN_SUBMISSION_STATUS_NOTSUBMITTEDYET;
}

/**
 * Print 2 tables of information with no action links -
 * the submission summary and the grading summary.
 *
 * @param stdClass $course - course object.
 * @param stdClass $user - user object to print the report for.
 * @param stdClass $coursemodule - course module object.
 * @param stdClass $kalmediaassign - Kaltura media assign object.
 * @return string - HTML summary for user.
 */
function kalmediaassign_get_student_summary($course, $user, $coursemodule, $kalmediaassign) {
    global $PAGE;
    $coursecontext = context_course::instance($course->id);
    $renderer = $PAGE->get_renderer('mod_kalmediaassign');
    echo $renderer->display_submission_status($coursemodule, $kalmediaassign, $coursecontext, $user);

    $submission = kalmediaassign_get_submission($kalmediaassign->id, $user->id);
    if ($submission) {
        $status = kalmediaassign_get_submission_status($submission);
        if ($status == KALASSIGN_SUBMISSION_STATUS_REQ_GRADING || $status == KALASSIGN_SUBMISSION_STATUS_GRADED) {
            echo $renderer->display_grade_feedback($kalmediaassign, $coursecontext, $user);
        }
    }
}

/**
 * This function validate Kasltura Media assignment module.
 * @param int $cmid - id of assignment which teacher want to view.
 * @return object - serach result.
 */
function kalmediaassign_validate_cmid ($cmid) {
    global $DB;

    if (! $cm = get_coursemodule_from_id('kalmediaassign', $cmid)) {
        throw new moodle_exception('invalidcoursemodule');
    }

    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        throw new moodle_exception('coursemisconf');
    }

    if (! $kalmediaassignobj = $DB->get_record('kalmediaassign', array('id' => $cm->instance))) {
        throw new moodle_exception('invalidid', 'kalmediaassign');
    }

    return array($cm, $course, $kalmediaassignobj);

}

/**
 * This function returns string about lateness of submission.
 * @param int $timesubmitted - timestamp which student submitted a media.
 * @param int $timedue - end time of media submission.
 * @return string - HTML markup for lateness of submission.
 */
function kalmediaassign_display_lateness($timesubmitted, $timedue) {
    if (!$timedue) {
        return '';
    }

    $timeremain = $timedue - $timesubmitted;
    if ($timeremain < 0) {
        $timetext = get_string('late', 'kalmediaassign', format_time($timeremain));
        $attr = array('class' => 'late', 'style' => 'color: red;');
    } else {
        $timetext = get_string('early', 'kalmediaassign', format_time($timeremain));
        $attr = array('class' => 'early');
    }

    return html_writer::tag('span', '(' . $timetext . ')', $attr);
}

/**
 * This function return media properties.
 * @return array - list of media properties.
 */
function kalmediaassign_get_media_properties() {
    return array('width' => '400',
                 'height' => '365',
                 'uiconf_id' => local_yukaltura_get_player_uiconf('player'),
                 'media_title' => 'Media assignment submission');
}

/**
 * Alerts teachers by email of new or changed assignments that need grading
 *
 * First checks whether the option to email teachers is set for this assignment.
 * Sends an email to ALL teachers in the course (or in the group if using separate groups).
 * Uses the methods kalmediaassign_email_teachers_text() and kalmediaassign_email_teachers_html() to construct the content.
 *
 * @param object $cm - kaltura media assignment course module object
 * @param string $name - name of the media assignment instance
 * @param object $submission - object The submission that has changed
 * @param object $context - context object of submission.
 * @return nothing.
 */
function kalmediaassign_email_teachers($cm, $name, $submission, $context) {
    global $CFG, $DB;

    $user = $DB->get_record('user', array('id' => $submission->userid));

    if ($teachers = kalmediaassign_get_graders($cm, $user, $context)) {

        $strsubmitted = get_string('submitted', 'kalmediaassign');

        foreach ($teachers as $teacher) {
            $info = new stdClass();
            $info->username = fullname($user, true);
            $info->assignment = format_string($name, true);
            $info->url = $CFG->wwwroot.'/mod/kalmediaassign/grade_submissions.php?cmid='.$cm->id;
            $info->timeupdated = userdate($submission->timemodified);
            $info->courseid = $cm->course;
            $info->cmid = $cm->id;

            $postsubject = $strsubmitted.': '.$user->username .' -> '. $name;
            $posttext = kalmediaassign_email_teachers_text($info);
            $posthtml = ($teacher->mailformat == 1) ? kalmediaassign_email_teachers_html($info) : '';

            $eventdata = new stdClass();
            $eventdata->modulename = 'kalmediaassign';
            $eventdata->userfrom = $user;
            $eventdata->userto = $teacher;
            $eventdata->subject = $postsubject;
            $eventdata->fullmessage = $posttext;
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml = $posthtml;
            $eventdata->smallmessage = $postsubject;

            $eventdata->name = 'kalmediaassign_updates';
            $eventdata->component = 'mod_kalmediaassign';
            $eventdata->notification = 1;
            $eventdata->contexturl = $info->url;
            $eventdata->contexturlname = $info->assignment;

            message_send($eventdata);
        }
    }
}

/**
 * Returns a list of teachers that should be grading given submission
 *
 * @param object $cm - kaltura media assignment course module object
 * @param object $user - moodle user object.
 * @param object $context - a context object.
 * @return array - list of graders.
 */
function kalmediaassign_get_graders($cm, $user, $context) {
    // Potential graders.
    $potgraders = get_users_by_capability($context, 'mod/kalmediaassign:gradesubmission', '', '', '', '', '', '', false, false);

    $graders = array();
    if (groups_get_activity_groupmode($cm) == SEPARATEGROUPS) {   // Separate groups are being used.
        if ($groups = groups_get_all_groups($cm->course, $user->id)) {  // Try to find all groups.
            foreach ($groups as $group) {
                foreach ($potgraders as $t) {
                    if ($t->id == $user->id) {
                        continue; // Do not send self.
                    }
                    if (groups_is_member($group->id, $t->id)) {
                        $graders[$t->id] = $t;
                    }
                }
            }
        } else {
            // User not in group, try to find graders without group.
            foreach ($potgraders as $t) {
                if ($t->id == $user->id) {
                    continue; // Do not send self.
                }
                if (!groups_get_all_groups($cm->course, $t->id)) { // Ugly hack.
                    $graders[$t->id] = $t;
                }
            }
        }
    } else {
        foreach ($potgraders as $t) {
            if ($t->id == $user->id) {
                continue; // Do not send self.
            }
            $graders[$t->id] = $t;
        }
    }
    return $graders;
}

/**
 * Creates the text content for emails to teachers
 *
 * @param object $info - The info used by the 'emailteachermail' language string
 * @return string - posted message.
 */
function kalmediaassign_email_teachers_text($info) {
    global $DB;

    $param    = array('id' => $info->courseid);
    $course   = $DB->get_record('course', $param);
    $posttext = '';

    if (!empty($course)) {
        $posttext  = format_string($course->shortname, true, $course->id).' -> '.
                     get_string('modulenameplural', 'kalmediaassign') . '  -> '.
                     format_string($info->assignment, true, $course->id)."\n";
        $posttext .= '---------------------------------------------------------------------'."\n";
        $posttext .= get_string("emailteachermail", "kalmediaassign", $info)."\n";
        $posttext .= "\n---------------------------------------------------------------------\n";
    }

    return $posttext;
}

 /**
  * Creates the html content for emails to teachers
  *
  * @param object $info - The info used by the 'emailteachermailhtml' language string
  * @return string - HTML markup which prints posted messages.
  */
function kalmediaassign_email_teachers_html($info) {
    global $CFG, $DB;

    $param    = array('id' => $info->courseid);
    $course   = $DB->get_record('course', $param);
    $posthtml = '';

    if (!empty($course)) {
        $posthtml  = '<p><font face="sans-serif">' .
                     '<a href="'.$CFG->wwwroot . '/course/view.php?id=' . $course->id.'">' .
                     format_string($course->shortname, true, $course->id) . '</a> ->' .
                     '<a href="'.$CFG->wwwroot . '/mod/kalmediaassign/view.php?id=' . $info->cmid . '"> ' .
                     format_string($info->assignment, true, $course->id) . '</a></font></p>';
        $posthtml .= '<hr /><font face="sans-serif">';
        $posthtml .= '<p>'.get_string('emailteachermailhtml', 'kalmediaassign', $info).'</p>';
        $posthtml .= '</font><hr />';
    }
    return $posthtml;
}

/**
 * This function returns list of student about a Kaltura Media assignment.
 * @param int $cm - module instance of submission.
 * @return array - list of student.
 */
function kalmediaassign_get_assignment_students($cm) {

    $context = context_module::instance($cm->id);
    $users = get_enrolled_users($context, 'mod/kalmediaassign:submit', 0, 'u.id');

    return $users;
}

/**
 * This functions returns an array with the height and width used in the configiruation for displaying a media.
 * @return array - An array whose first value is the width and second value is the height.
 */
function kalmediaassign_get_player_dimensions() {
    $kalturaconfig = get_config(KALTURA_PLUGIN_NAME);

    $width = (isset($kalturaconfig->kalmediaassign_player_width) && !empty($kalturaconfig->kalmediaassign_player_width)
             ) ? $kalturaconfig->kalmediaassign_player_width : KALTURA_ASSIGN_MEDIA_WIDTH;

    $height = (isset($kalturaconfig->kalmediaassign_player_height) && !empty($kalturaconfig->kalmediaassign_player_height)
              ) ? $kalturaconfig->kalmediaassign_player_height : KALTURA_ASSIGN_MEDIA_HEIGHT;

    return array($width, $height);
}


/**
 * This functions returns an array with the height and width used in the configiruation for displaying a media.
 * @return array - An array whose first value is the width and second value is the height.
 */
function kalmediaassign_get_popup_player_dimensions() {
    $kalturaconfig = get_config(KALTURA_PLUGIN_NAME);

    $width = (
              isset($kalturaconfig->kalmediaassign_popup_player_width) &&
              !empty($kalturaconfig->kalmediaassign_popup_player_width)
            ) ? $kalturaconfig->kalmediaassign_popup_player_width : KALTURA_ASSIGN_POPUP_MEDIA_WIDTH;

    $height = (
               isset($kalturaconfig->kalmediaassign_popup_player_height) &&
               !empty($kalturaconfig->kalmediaassign_popup_player_height)
              ) ? $kalturaconfig->kalmediaassign_popup_player_height : KALTURA_ASSIGN_POPUP_MEDIA_HEIGHT;

    return array($width, $height);
}


/**
 * Update the calendar entries for this assignment.
 *
 * @param object $kalmediaassign - Kaltura media assignment object.
 * @param int $coursemoduleid - Required to pass this in because it might not exist in the database yet.
 * @return bool
 */
function kalmediaassign_update_calendar($kalmediaassign, $coursemoduleid) {
    global $DB, $CFG;

    if ($kalmediaassign->timedue) {
        $event = new stdClass();

        $params = array('modulename' => 'kalmediaassign', 'instance' => $kalmediaassign->id);
        $event->id = $DB->get_field('event', 'id', $params);
        $event->name = $kalmediaassign->name;
        $event->timestart = $kalmediaassign->timedue;

        // Convert the links to pluginfile. It is a bit hacky but at this stage the files
        // might not have been saved in the module area yet.
        $intro = $kalmediaassign->intro;
        if ($draftid = file_get_submitted_draft_itemid('introeditor')) {
            $intro = file_rewrite_urls_to_pluginfile($intro, $draftid);
        }

        // We need to remove the links to files as the calendar is not ready
        // to support module events with file areas.
        $intro = strip_pluginfile_content($intro);
        if (kalmediaassign_show_intro($kalmediaassign)) {
            $event->description = array(
                'text' => $intro,
                'format' => $kalmediaassign->introformat
            );
        } else {
            $event->description = array(
                'text' => '',
                'format' => $kalmediaassign->introformat
            );
        }

        if ($event->id) {
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event);
        } else {
            unset($event->id);
            $event->courseid = $kalmediaassign->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->modulename = 'kalmediaassign';
            $event->instance = $kalmediaassign->id;
            $event->eventtype = 'due';
            $event->timeduration = 0;

            if (property_exists('calendar_event', 'type')) {
                $event->type = CALENDAR_EVENT_TYPE_ACTION;
            }

            if (property_exists('calendar_event', 'priority')) {
                $event->priority = null;
            }

            calendar_event::create($event);
        }
    } else {
        $DB->delete_records('event', array('modulename' => 'kalmediaassign', 'instance' => $kalmediaassign->id));
    }
}


/**
 * Based on the current assignment settings should we display the intro.
 * @param object $kalmediaassign - Kaltura media assignment object.
 * @return bool - if intro is visible, returns true. otherwise, returns false.
 */
function kalmediaassign_show_intro($kalmediaassign) {
    if ($kalmediaassign->alwaysshowdescription ||
            time() > $kalmediaassign->timeavailable) {
        return true;
    }
    return false;
}
