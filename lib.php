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
 * Library of interface functions and constants for module newmodule
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the newmodule specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_kalmediares
 * @copyright  (C) 2016-2018 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

defined('MOODLE_INTERNAL') || die();

require_login();

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $kalmediares - An object from the form in mod_form.php
 * @return int - The id of the newly inserted kalvidassign record
 */
function kalmediares_add_instance($kalmediares) {
    global $DB;

    $kalmediares->timecreated = time();

    $kalmediares->id = $DB->insert_record('kalmediares', $kalmediares);

    return $kalmediares->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $kalmediares - An object from the form in mod_form.php
 * @return boolean - Success/Fail
 */
function kalmediares_update_instance($kalmediares) {
    global $DB;

    $kalmediares->timemodified = time();
    $kalmediares->id = $kalmediares->instance;

    $updated = $DB->update_record('kalmediares', $kalmediares);

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
function kalmediares_delete_instance($id) {
    global $DB;

    if (! $kalmediares = $DB->get_record('kalmediares', array('id' => $id))) {
        return false;
    }

    $DB->delete_records('kalmediares', array('id' => $kalmediares->id));

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
 * @param object $kalmediares - An object from the form in mod_form.php.
 * @return object - outline of user.
 * @todo Finish documenting this function
 */
function kalmediares_user_outline($course, $user, $mod, $kalmediares) {
    $return = new stdClass;
    $return->time = 0;
    $return->info = ''; // TODO finish this function.
    return $return;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 * @param object $course - Moodle course object.
 * @param object $user - Moodle user object.
 * @param object $mod - Moodle module obuject.
 * @param object $kalmediares - An object from the form in mod_form.php.
 * @return boolean - this function always return true.
 * @todo Finish documenting this function
 */
function kalmediares_user_complete($course, $user, $mod, $kalmediares) {
    return true;  // TODO: finish this function.
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in kalvidres activities and print it out.
 * Return true if there was output, or false is there was none.
 * @param object $course - Moodle course object.
 * @param array $viewfullnames - fullnames of course.
 * @param int $timestart - timestamp.
 * @return boolean - True if anything was printed, otherwise false.
 * @todo Finish documenting this function
 */
function kalmediares_print_recent_activity($course, $viewfullnames, $timestart) {
    // TODO: finish this function
    return false;  // True if anything was printed, otherwise false.
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 * @return bool - this function always return false.
 */
function kalmediares_cron () {
    return false;
}

/**
 * Must return an array of users who are participants for a given instance
 * of kalvidres. Must include every user involved in the instance, independient
 * of his role (student, teacher, admin...). The returned objects must contain
 * at least id property. See other modules as example.
 *
 * @param int $kalmediaresid - ID of an instance of this module
 * @return mixed - false if no participants, array of objects otherwise
 */
function kalmediares_get_participants($kalmediaresid) {
    // TODO: finish this function.
    return false;
}

/**
 * This function return support status.
 * @param string $feature - FEATURE_xx constant for requested feature
 * @return mixed - True if module supports feature, null if doesn't know
 */
function kalmediares_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
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
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function kalmediares_reset_userdata($data) {
    return array();
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param object $course course object
 * @return array status array
 */
function kalmediares_reset_course_form_defaults($course) {
    return array();
}

