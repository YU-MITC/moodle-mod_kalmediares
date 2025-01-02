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
 * Kaltura media assignment library of hooks
 *
 * @package   mod_kalmediaassign
 * @copyright (C) 2016-2025 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

defined('MOODLE_INTERNAL') || die();


if (file_exists($CFG->dirroot.'/calendar/lib.php')) {
    require_once($CFG->dirroot.'/calendar/lib.php');
}


/**
 * Create and return event object.
 * @param object $kalmediaassign - Kaltura Media Assignment object.
 * return object - event object.
 */
function kalmediaassign_create_event($kalmediaassign) {
    $event = new stdClass();
    $event->name = $kalmediaassign->name;
    $event->description = format_module_intro('kalmediaassign', $kalmediaassign, $kalmediaassign->coursemodule);
    $event->courseid = $kalmediaassign->course;
    $event->groupid = 0;
    $event->userid = 0;
    $event->modulename = 'kalmediaassign';
    $event->instance = $kalmediaassign->id;
    $event->eventtype = 'due';
    $event->timestart = $kalmediaassign->timedue;
    $event->timeduration = 0;

    if (property_exists('calendar_event', 'type')) {
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
    }

    if (property_exists('calendar_event', 'priority')) {
        $event->priority = null;
    }

    return $event;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $kalmediaassign - An object from the form in mod_form.php
 * @return int - The id of the newly inserted kalmediaassign record
 */
function kalmediaassign_add_instance($kalmediaassign) {
    global $DB;

    $kalmediaassign->timecreated = time();

    $kalmediaassign->id = $DB->insert_record('kalmediaassign', $kalmediaassign);

    if ($kalmediaassign->timedue) {
        $event = kalmediaassign_create_event($kalmediaassign);
        calendar_event::create($event);
    }

    kalmediaassign_grade_item_update($kalmediaassign);

    return $kalmediaassign->id;
}

/**
 * Returns info object about the course module
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function kalmediaassign_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;

    $dbparams = array('id' => $coursemodule->instance);
    $fields = 'id, name, intro, introformat, timeavailable, alwaysshowdescription';
    if (! $kalmediaassign = $DB->get_record('kalmediaassign', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $kalmediaassign->name;
    if ($coursemodule->showdescription) {
        if ($kalmediaassign->alwaysshowdescription || time() > $kalmediaassign->timeavailable) {
            // Convert intro to html. Do not filter cached version, filters run at display time.
            $result->content = format_module_intro('kalmediaassign', $kalmediaassign, $coursemodule->id, false);
        }
    }

    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_kalmediaassign_get_completion_active_rule_descriptions($cm) {
    return [];
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $kalmediaassign - An object from the form in mod_form.php
 * @return boolean - Success/Fail
 */
function kalmediaassign_update_instance($kalmediaassign) {
    global $DB;

    $kalmediaassign->timemodified = time();
    $kalmediaassign->id = $kalmediaassign->instance;

    $updated = $DB->update_record('kalmediaassign', $kalmediaassign);

    if ($kalmediaassign->timedue) {
        $event = new stdClass();

        if ($event->id = $DB->get_field('event', 'id',
                                        array('modulename' => 'kalmediaassign', 'instance' => $kalmediaassign->id))) {

            $event->name = $kalmediaassign->name;
            $event->description = format_module_intro('kalmediaassign', $kalmediaassign, $kalmediaassign->coursemodule);
            $event->timestart = $kalmediaassign->timedue;

            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event);
        } else {
            $event = kalmediaassign_create_event($kalmediaassign);
            calendar_event::create($event);
        }
    } else {
        $DB->delete_records('event', array('modulename' => 'kalmediaassign', 'instance' => $kalmediaassign->id));
    }

    if ($updated) {
        kalmediaassign_grade_item_update($kalmediaassign);
    }

    return $updated;
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id - Id of the module instance
 * @return boolean - Success/Failure
 */
function kalmediaassign_delete_instance($id) {
    global $DB;

    $cm = get_coursemodule_from_instance('kalmediaassign', $id, 0, false, MUST_EXIST);

    if (!empty($cm)) {
        $DB->delete_records('course_modules_completion', array('coursemoduleid' => $cm->id));
    }

    $DB->delete_records('kalmediaassign_submission', array('mediaassignid' => $id));

    $DB->delete_records('event', array('modulename' => 'kalmediaassign', 'instance' => $id));

    $kalmediaassign = $DB->get_record('kalmediaassign', array('id' => $id));

    if (!empty($kalmediaassign)) {
        kalmediaassign_grade_item_delete($kalmediaassign);
        $DB->delete_records('kalmediaassign', array('id' => $id));
    }

    return true;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every assignment event in the site is checked, else
 * only assignment events belonging to the course specified are checked.
 *
 * @param int $courseid
 * @return bool
 */
function kalmediaassign_refresh_events($courseid = 0) {
    global $CFG, $DB;

    if ($courseid) {
        // Make sure that the course id is numeric.
        if (!is_numeric($courseid)) {
            return false;
        }
        if (!$kalmediaassigns = $DB->get_records('kalmediaassign', array('course' => $courseid))) {
            return false;
        }
        // Get course from courseid parameter.
        if (!$course = $DB->get_record('course', array('id' => $courseid), '*')) {
            return false;
        }
    } else {
        if (!$assigns = $DB->get_records('kalmediaassign')) {
            return false;
        }
    }
    foreach ($kalmediaassigns as $kalmediaassign) {
        // Use assignment's course column if courseid parameter is not given.
        if (!$courseid) {
            $courseid = $kalmediaassign->course;
            if (!$course = $DB->get_record('course', array('id' => $courseid), '*')) {
                continue;
            }
        }
        if (!$cm = get_coursemodule_from_instance('kalmediaassign', $kalmediaassign->id, $courseid, false)) {
            continue;
        }
        $context = context_module::instance($cm->id);
        kalmediaassign_update_calendar($kalmediaassign, $cm->id);
    }

    return true;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 * @param object $course - Moodle course object.
 * @param object $user - Moodle user object.
 * @param object $mod - Moodle moduble object.
 * @param object $kalmediaassign - An object from the form in mod_form.php.
 * @return object - outline of user.
 * @todo Finish documenting this function
 */
function kalmediaassign_user_outline($course, $user, $mod, $kalmediaassign) {
    global $CFG;

    require_once($CFG->libdir.'/gradelib.php');
    require_once($CFG->dirroot.'/grade/grading/lib.php');

    $gradinginfo = grade_get_grades($course->id, 'mod', 'kalmediaassign', $kalmediaassign->id, $user->id);
    $gradingitem = $gradinginfo->items[0];
    $gradebookgrade = $gradingitem->grades[$user->id];

    if (empty($gradebookgrade->str_long_grade)) {
        return null;
    }
    $result = new stdClass();
    $result->info = get_string('outlinegrade', 'kalmediaassign', $gradebookgrade->str_long_grade);
    $result->time = $gradebookgrade->dategraded;

    return $result;
}


/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 * @param object $course - Moodle course object.
 * @param object $user - Moodle user object.
 * @param object $coursemodule - course module object.
 * @param object $kalmediaassign - An object from the form in mod_form.php.
 */
function kalmediaassign_user_complete($course, $user, $coursemodule, $kalmediaassign) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/kalmediaassign/locallib.php');

    echo kalmediaassign_get_student_summary($course, $user, $coursemodule, $kalmediaassign);
}


/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in kalmediaassign activities and print it out.
 * Return true if there was output, or false is there was none.
 * @param object $course - Moodle course object.
 * @param array $viewfullnames - fullnames of course.
 * @param int $timestart - timestamp.
 * @return boolean - True if anything was printed, otherwise false.
 * @todo Finish documenting this function
 */
function kalmediaassign_print_recent_activity($course, $viewfullnames, $timestart) {
    // TODO: finish this function.
    return false;  // True if anything was printed, otherwise false.
}


/**
 * Obtains the automatic completion state for this module based on any conditions
 * in kamediaassign settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function kalmediaassign_get_completion_state($course, $cm, $userid, $type) {
    // Completion option is not enabled so just return $type.
    return $type;
}


/**
 * Must return an array of users who are participants for a given instance
 * of kalmediaassign. Must include every user involved in the instance,
 * independient of his role (student, teacher, admin...). The returned objects
 * must contain at least id property. See other modules as example.
 *
 * @param int $kalmediaassignid - ID of an instance of this module
 * @return boolean|array - false if no participants, array of objects otherwise
 */
function kalmediaassign_get_participants($kalmediaassignid) {
    // TODO: finish this function.
    return false;
}


/**
 * This function returns if a scale is being used by one kalmediaassign
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $kalmediaassignid - id of an instance of this module
 * @param int $scaleid - id of scale.
 * @return mixed - now, this function anywhere returns "false".
 * @todo Finish documenting this function
 */
function kalmediaassign_scale_used($kalmediaassignid, $scaleid) {

    $return = false;

    return $return;
}


/**
 * Checks if scale is being used by any instance of kalmediaassign.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param int $scaleid - id of scale.
 * @return bool - True if the scale is used by any kalmediaassign
 */
function kalmediaassign_scale_used_anywhere($scaleid) {
    global $DB;

    $param = array('grade' => -$scaleid);
    if ($scaleid && $DB->record_exists('kalmediaassign', $param)) {
        return true;
    } else {
        return false;
    }
}


/**
 * This function returns support status about a feature which is received as argument.
 * @param string $feature - FEATURE_xx constant for requested feature
 * @return mixed - True if module supports feature, null if doesn't know
 */
function kalmediaassign_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_ADVANCED_GRADING:
            return false;
        case FEATURE_PLAGIARISM:
            return true;
        case FEATURE_COMMENT:
            return true;
        default:
            return null;
    }
}


/**
 * Create/update grade item for given kaltura media assignment
 *
 * @param object $kalmediaassign - kalmediaassign object with extra cmidnumber
 * @param mixed $grades - optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int - 0 if ok, error code otherwise
 */
function kalmediaassign_grade_item_update($kalmediaassign, $grades = null) {

    require_once(dirname(dirname(dirname(__FILE__))) . '/lib/gradelib.php');

    $params = array('itemname' => $kalmediaassign->name, 'idnumber' => $kalmediaassign->cmidnumber);

    if ($kalmediaassign->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = $kalmediaassign->grade;
        $params['grademin'] = 0;

    } else if ($kalmediaassign->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid'] = -$kalmediaassign->grade;

    } else {
        $params['gradetype'] = GRADE_TYPE_TEXT; // Allow text comments only.
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/kalmediaassign', $kalmediaassign->course, 'mod', 'kalmediaassign',
                        $kalmediaassign->id, 0, $grades, $params);

}


/**
 * Removes all grades from gradebook
 *
 * @param int $courseid - id of course.
 * @param string  $type - optional type.
 * @return nothing.
 */
function kalmediaassign_reset_gradebook($courseid, $type='') {
    global $DB;

    $sql = "SELECT l.*, cm.idnumber as cmidnumber, l.course courseid
              FROM {kalmediaassign} l, {course_modules} cm, {modules} m
             WHERE m.name='kalmediaassign' AND m.id=cm.module AND cm.instance=l.id AND l.course=:course";

    $params = array ('course' => $courseid);

    if ($kalvisassigns = $DB->get_records_sql($sql, $params)) {

        foreach ($kalvisassigns as $kalvisassign) {
            kalmediaassign_grade_item_update($kalvisassign, 'reset');
        }
    }

}


/**
 * Actual implementation of the reset course functionality, delete all the
 * kaltura media submissions attempts for course $data->courseid.
 *
 * @param object $data - the data submitted from the reset course.
 * @return array - status array.
 *
 * TODO: test user data reset feature
 */
function kalmediaassign_reset_userdata($data) {
    global $DB;

    $componentstr = get_string('modulenameplural', 'kalmediaassign');
    $status = array();

    if (!empty($data->reset_kalmediaassign_userdata)) {
        $kalmediaassignsql = "SELECT l.id
                           FROM {kalmediaassign} l
                           WHERE l.course=:course";

        $params = array("course" => $data->courseid);
        $DB->delete_records_select('kalmediaassign_submission', "mediaassignid IN ($kalmediaassignsql)", $params);

        // Remove all grades from gradebook.
        if (empty($data->reset_gradebook_grades)) {
            kalmediaassign_reset_gradebook($data->courseid);
        }

        $status[] = array('component' => $componentstr,
                          'item' => get_string('deleteallsubmissions', 'kalmediaassign'),
                          'error' => false);
    }

    // Updating dates - shift may be negative too.
    if ($data->timeshift) {
        shift_course_mod_dates('kalmediaassign',
                               array('timedue', 'timeavailable'),
                               $data->timeshift, $data->courseid);
        $status[] = array('component' => $componentstr,
                          'item' => get_string('datechanged'),
                          'error' => false);
    }

    return $status;
}

/**
 * Defines which elements mod_kalmediaassign needs to add to reset form
 *
 * @param moodleform $mform The reset course form to extend
 */
function kalmediaassign_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'kalmediaassignheader', get_string('modulenameplural', 'kalmediaassign'));
    $mform->addElement('checkbox', 'reset_kalmediaassign_userdata', get_string('reset_userdata', 'kalmediaassign'));
}

/**
 * Defines default setting when reset course objects
 *
 * @param object $course - course object
 * @return array - setting parameter(s) for moodleform
 */
function kalmediaassign_reset_course_form_defaults($course) {
    return array('reset_kalmediaassign_userdata' => 1);
}

/**
 * This function deeltes a grade item.
 *
 * @param object $kalmediaassign - kaltura media assignment object.
 * @return array - status array.
 *
 * TODO: test user data reset feature
 */
function kalmediaassign_grade_item_delete($kalmediaassign) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/kalmediaassign', $kalmediaassign->course, 'mod', 'kalmediaassign', $kalmediaassign->id, 0,
            null, array('deleted' => 1));
}


/**
 * Function to be run periodically according to the moodle cron.
 * Finds all assignment notifications that have yet to be mailed out, and mails them.
 */
function kalmediaassign_cron () {
    return false;
}

/**
 * Return list of marked submissions that have not been mailed out for currently enrolled students
 *
 * @param int $starttime - start time for search submissions.
 * @param int $endtime - end time for search submissions.
 * @return array - list of marked submissions.
 */
function kalmediaassign_get_unmailed_submissions($starttime, $endtime) {

    global $DB;

    return $DB->get_records_sql("SELECT ks.*, k.course, k.name
                                     FROM {kalmediaassign_submission} ks,
                                     {kalmediaassign} k
                                     WHERE ks.mailed = 0
                                     AND ks.timemarked <= ?
                                     AND ks.timemarked >= ?
                                     AND ks.assignment = k.id", array($endtime, $starttime));
}


/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param stdClass $kalmediaassign - media assignment object.
 * @param stdClass $course - course object.
 * @param stdClass $cm - course module object.
 * @param stdClass $context - context object.
 * @since Moodle 3.0
 */
function kalmediaassign_view($kalmediaassign, $course, $cm, $context) {
    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

